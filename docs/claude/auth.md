# Authentication & RBAC

Authentication is session-based, using the Laravel web guard. Users can log in two ways: with email and password, or with Google OAuth through `laravel/socialite`.

## Auth Bypass

Setting `AUTH_DISABLED=true` in `.env` disables auth entirely. Use this for local dev and demo sites. This setting is backed by `config('app.auth_enabled')`:

```php
// config/app.php
'auth_enabled' => !env('AUTH_DISABLED', false),
```

When auth is disabled, the login page redirects to `/`. All auth middleware does nothing. Permission checks return `true`, so the full UI renders. Wire this in from day one. Retrofitting it later means auditing every permission check.

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

Google sign-in is restricted to one Workspace domain, through `GOOGLE_ALLOWED_DOMAIN`. An empty value means unrestricted; set a value for any internal-only app. `GoogleService::checkDomain()` compares the email's domain, and throws a `ValidationException` on a mismatch, before it touches any user record.

Users from the allowed domain are auto-registered on first login, with the app's **default role**. This role comes from `config('auth.default_role')`, set through `DEFAULT_ROLE` in `.env`. The default role is deliberately configurable, rather than a hardcoded role name. New auto-registrations get `auto_registered = 1` and `reviewed_at = null`. Add an unreviewed-registrations badge to the admin nav, so a superuser notices and reviews them.

`GoogleService::handleCallback()` runs these steps, in order:

1. Check the domain.
2. Find an existing user by `google_id`. If none is found, fall back to `email`.
3. If a matching user is found and suspended, reject the login with a validation error.
4. If a matching user is found, backfill `google_id` or `avatar_url` where missing, and return the user.
5. If no matching user is found, create a new user with the default role, and set `auto_registered = true`.

## Suspension

The `users.is_suspended` flag blocks login through any method. Google-auth users must be **suspended, not deleted**. If you delete a user who signs in through Google, that user can just re-register on the next login.

## RBAC

The app uses custom `roles` and `role_permissions` tables, not a package such as spatie/permission. When `roles.is_superuser = 1`, that role bypasses all permission checks. Roles are user-manageable: you can create, rename, and delete them, except for the superuser role.

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

Eager-load `role.rolePermissions` wherever a request will make several permission checks. This avoids an N+1 query problem.

## Permissions

Permissions are defined in `config/permissions.php`, grouped by nav section, as key/label pairs:

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

The template ships only the `admin.*` and `audit.view` group, and the built-in `superuser` role. `RolesSeeder` seeds this role with `is_superuser = true`, and needs no explicit permissions, since a superuser bypasses the check. Every other role and permission is app-specific. Add them to `config/permissions.php` and `RolesSeeder` as you build each feature.

You create the first superuser with an artisan command: `php artisan app:create-superuser`. Rename the signature's namespace prefix to match the app. This command creates the `superuser` role if it does not exist yet, then prompts for a name, email, and password.

## Password Reset

Only a superuser can reset a password. There is no self-service email reset flow. A superuser can also set a password for a Google-only account. This gives that account a password-based fallback login.

## Middleware

- `auth.if_enabled` replaces Laravel's built-in `auth` middleware. It does nothing when `AUTH_DISABLED` is set.
- `permission:{key}` aborts with a 403 error if the user lacks the named permission. It does nothing when `AUTH_DISABLED` is set.

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

Share a `canSee` view helper from `AppServiceProvider::boot()`. This lets Blade conditionally render nav items, without duplicating the bypass check:

```php
View::share('canSee', fn(string $permission): bool =>
    !config('app.auth_enabled') || (auth()->user()?->hasPermission($permission) ?? false)
);
```

## Routes

Auth routes stay **outside** the `auth.if_enabled` group. This avoids redirect loops:

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

Every successful login, through either method, inserts a row into `user_logins`, with `user_id`, `logged_in_at`, `ip_address`, and `auth_method`. `LoginService::recordLogin()` is the single call site for both the password and Google flows. Do not duplicate the insert in the controllers.

## Audit Logging

A custom `AuditLogger`, bound to `AuditLoggerInterface`, writes to `audit_log` on every action worth a trail. This includes logins, logouts, user and role management, and any app-specific mutations:

```php
$this->audit->log(AuditActions::USER_LOGIN, 'user', (string) $user->id, "Logged in via google: {$user->name}");
```

Keep action name constants in one `AuditActions` class, such as `user.login` and `role.created`, rather than scattering string literals through the code. This class is the only place you need to check to see what actions get logged.

## Schema

**`users`** (Laravel default, extended)

- Standard columns: `name`, `email`, `password` (nullable), `remember_token`. Plus:
- `role_id`: FK to `roles`, nullable, `ON DELETE SET NULL`
- `google_id`: VARCHAR(100), unique, nullable
- `avatar_url`: VARCHAR(500), nullable
- `is_suspended`: BOOLEAN, default false
- `auto_registered`: BOOLEAN, default false
- `reviewed_at`: TIMESTAMP, nullable

**`roles`**

- `id`, `name` (VARCHAR(50), unique), `display_name` (VARCHAR(100)), `is_superuser` (BOOLEAN, default false), timestamps

**`role_permissions`**

- `role_id`: FK to `roles` (`ON DELETE CASCADE`); `permission`: VARCHAR(100)
- Composite primary key `(role_id, permission)`

**`user_logins`**

- `id`, `user_id` (FK to `users`, `ON DELETE CASCADE`), `logged_in_at`, `ip_address` (VARCHAR(45), nullable), `auth_method` (ENUM: `password`, `google`)
- Index on `(user_id, logged_in_at)`

**`audit_log`**

- `id`, `user_id` (FK to `users`, `ON DELETE SET NULL`, nullable), `action` (VARCHAR(100)), `entity_type` (VARCHAR(50)), `entity_id` (VARCHAR(100)), `description` (TEXT, nullable), `changes` (JSON, nullable), `created_at`
- Indexes on `(entity_type, entity_id)` and `(user_id, created_at)`

## Required `.env` Keys

```dotenv
AUTH_DISABLED=false
DEFAULT_ROLE=member          # role auto-assigned to new Google sign-ups — rename to fit the app

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_ALLOWED_DOMAIN=       # empty = any Google account may sign in
```
