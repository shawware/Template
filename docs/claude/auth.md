# Authentication & RBAC

Session-based (Laravel web guard). Two login methods: email/password and Google OAuth (`laravel/socialite`).

## Auth Bypass

`AUTH_DISABLED=true` in `.env` disables auth entirely (local dev, demo sites). Backed by `config('app.auth_enabled')`:

```php
// config/app.php
'auth_enabled' => !env('AUTH_DISABLED', false),
```

Login page redirects to `/` when disabled. All auth middleware no-ops; permission checks return `true` so the full UI renders. Wire this in from day one — retrofitting it later means auditing every permission check.

## Google OAuth

```php
// config/services.php
'google' => [
    'client_id'      => env('GOOGLE_CLIENT_ID'),
    'client_secret'  => env('GOOGLE_CLIENT_SECRET'),
    'redirect'       => env('APP_URL') . '/user/auth/google/callback',
    'allowed_domain' => env('GOOGLE_ALLOWED_DOMAIN', ''),
],
```

Restricted to one Workspace domain via `GOOGLE_ALLOWED_DOMAIN` (empty = unrestricted; set it for any internal-only app). `GoogleService::checkDomain()` compares the email's domain and throws a `ValidationException` on mismatch before any user record is touched.

Users from the allowed domain are auto-registered on first login with the app's **default role** (`config('auth.default_role')`, set via `DEFAULT_ROLE` in `.env`) — this is deliberately configurable rather than a hardcoded role name. New auto-registrations get `auto_registered = 1`, `reviewed_at = null`; surface an unreviewed-registrations badge on the admin nav so a superuser notices and reviews them.

`GoogleService::handleCallback()`:

1. Check domain
2. Find existing user by `google_id`, falling back to `email`
3. If found and suspended → reject with a validation error
4. If found → backfill `google_id`/`avatar_url` if missing, return the user
5. If not found → create a new user with the default role, `auto_registered = true`

## Suspension

`users.is_suspended` blocks login via any method. Google-auth users must be **suspended, not deleted** — deleting a user who signs in via Google just lets them re-register on next login.

## RBAC

Custom `roles` + `role_permissions` tables — not a package (no spatie/permission). `roles.is_superuser = 1` bypasses all permission checks. Roles are user-manageable (create/rename/delete) except the superuser role.

`User::hasPermission(string $permission)` is the single check method:

```php
public function hasPermission(string $permission): bool
{
    if (!$this->relationLoaded('role')) {
        $this->load('role.rolePermissions');
    }
    if (!$this->role) return false;
    if ($this->role->is_superuser) return true;
    return $this->role->rolePermissions->contains('permission', $permission);
}
```

Eager-load `role.rolePermissions` wherever a request will make several permission checks, to avoid N+1.

## Permissions

Defined in `config/permissions.php`, grouped by nav section — key => label:

```php
return [
    'Admin' => [
        'admin.users' => 'Manage users',
        'admin.roles' => 'Manage roles',
        'audit.view'  => 'View audit log',
    ],
    // Add app-specific groups here as features are built —
    // e.g. 'Reports' => ['report.summary' => 'Summary', ...]
];
```

The template ships only the `admin.*`/`audit.view` group and the built-in `superuser` role (seeded by `RolesSeeder`, `is_superuser = true`, no explicit permissions needed since superuser bypasses the check). Every other role and permission is app-specific — add them to `config/permissions.php` and `RolesSeeder` as features are built.

First superuser is created via an artisan command (`php artisan app:create-superuser` — rename the signature's namespace prefix to match the app), which creates the `superuser` role if missing, then prompts for name/email/password.

## Password Reset

Superuser only — no self-service email reset flow. A superuser can also set a password for a Google-only account, giving it a password-based fallback login.

## Middleware

- `auth.if_enabled` — replaces Laravel's built-in `auth` middleware; no-ops when `AUTH_DISABLED`
- `permission:{key}` — aborts 403 if the user lacks the named permission; no-ops when `AUTH_DISABLED`

```php
class AuthIfEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.auth_enabled')) return $next($request);
        if (!auth()->check()) return redirect('/user/login');
        return $next($request);
    }
}

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!config('app.auth_enabled')) return $next($request);
        if (!auth()->user()?->hasPermission($permission)) abort(403);
        return $next($request);
    }
}
```

Share a `canSee` view helper from `AppServiceProvider::boot()` so Blade can conditionally render nav items without duplicating the bypass check:

```php
View::share('canSee', fn(string $permission): bool =>
    !config('app.auth_enabled') || (auth()->user()?->hasPermission($permission) ?? false)
);
```

## Routes

Auth routes stay **outside** the `auth.if_enabled` group, to avoid redirect loops:

```php
Route::get('/user/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/user/login', [LoginController::class, 'login']);
Route::post('/user/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/user/auth/google', [GoogleController::class, 'redirect']);
Route::get('/user/auth/google/callback', [GoogleController::class, 'callback']);

Route::middleware('auth.if_enabled')->group(function () {
    // everything else
    Route::middleware('permission:admin.users')->group(function () {
        Route::get('/admin/users', [AdminUserController::class, 'index']);
        // ...
    });
});
```

## Login History

Every successful login (either method) inserts a row into `user_logins` (`user_id`, `logged_in_at`, `ip_address`, `auth_method`). `LoginService::recordLogin()` is the single call site for both the password and Google flows — don't duplicate the insert in the controllers.

## Audit Logging

A custom `AuditLogger` (bound to `AuditLoggerInterface`) writes to `audit_log` on every action worth a trail — logins/logouts, user/role management, and any app-specific mutations:

```php
$this->audit->log(AuditActions::USER_LOGIN, 'user', (string) $user->id, "Logged in via google: {$user->name}");
```

Keep action name constants in one `AuditActions` class (`user.login`, `role.created`, etc.) rather than scattering string literals — it's the only place to check when auditing "what actions get logged."

## Schema

**`users`** (Laravel default, extended)

- Standard columns (`name`, `email`, `password` nullable, `remember_token`) plus:
- `role_id` FK → `roles`, nullable, `ON DELETE SET NULL`
- `google_id` VARCHAR(100) UNIQUE, nullable
- `avatar_url` VARCHAR(500), nullable
- `is_suspended` BOOLEAN DEFAULT false
- `auto_registered` BOOLEAN DEFAULT false
- `reviewed_at` TIMESTAMP, nullable

**`roles`**

- `id`, `name` VARCHAR(50) UNIQUE, `display_name` VARCHAR(100), `is_superuser` BOOLEAN DEFAULT false, timestamps

**`role_permissions`**

- `role_id` FK → `roles` (`ON DELETE CASCADE`), `permission` VARCHAR(100)
- Composite primary key `(role_id, permission)`

**`user_logins`**

- `id`, `user_id` FK → `users` (`ON DELETE CASCADE`), `logged_in_at`, `ip_address` VARCHAR(45) nullable, `auth_method` ENUM(`password`, `google`)
- Index on `(user_id, logged_in_at)`

**`audit_log`**

- `id`, `user_id` FK → `users` (`ON DELETE SET NULL`), nullable, `action` VARCHAR(100), `entity_type` VARCHAR(50), `entity_id` VARCHAR(100), `description` TEXT nullable, `changes` JSON nullable, `created_at`
- Indexes on `(entity_type, entity_id)` and `(user_id, created_at)`

## Required `.env` Keys

```dotenv
AUTH_DISABLED=false
DEFAULT_ROLE=member          # role auto-assigned to new Google sign-ups — rename to fit the app

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_ALLOWED_DOMAIN=       # empty = any Google account may sign in
```
