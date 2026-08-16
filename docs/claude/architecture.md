# Architecture

## Application Layers

The codebase uses strict layers. Each layer can depend only on the layer directly below it.

```text
Controllers  →  Services  →  Repositories  →  Models / DB
```

**Rules:**

- Controllers inject Services only. They never inject repositories or models directly.
- Services inject Repositories only. They never inject models, and never call the DB facade directly.
- Repositories hold all Eloquent and DB facade calls. They hold no business logic.
- Services hold all business logic. They make no Eloquent or DB calls.

**Locations:**

- `app/Http/Controllers/`: the HTTP layer. Controllers validate input, call a service, and return a response.
- `app/Services/`: business logic and orchestration.
- `app/Repositories/`: data access. Each model group has one interface and one Eloquent implementation.
- `app/Models/`: Eloquent model definitions only.

**Dependency injection:**

- `AppServiceProvider::register()` binds every repository interface to its Eloquent implementation.
- Laravel's container resolves dependencies automatically, using constructor type-hints.
- Unit tests mock repository interfaces with Mockery. They do not need a database.

**Repositories the template ships with:**

- `UserRepositoryInterface` → `EloquentUserRepository`
- `RoleRepositoryInterface` → `EloquentRoleRepository`
- `UserLoginRepositoryInterface` → `EloquentUserLoginRepository`
- `AuditLogRepositoryInterface` → `EloquentAuditLogRepository`

Every app-specific model group gets its own interface and Eloquent pair. Bind each pair the same way. Add each pair to the list above, in that app's own copy of this document.

## Other Directories

**`app/Console/Commands/`**: artisan commands, such as `CreateSuperuser` and one-off admin or maintenance commands. A command is a thin entry point, in the same way a controller is. It parses input, such as arguments, options, or prompts, and calls a Service. It does not contain business logic itself. `bin/check-layers` does not currently scan `app/Console/`. This rule is enforced by discipline, not tooling. If a command's `handle()` method contains real logic, that logic belongs in a Service instead. A Service is testable, and reusable from the web side too. Commands that are dev-only, either because they should not ship or because they are only useful locally, live under `app/Console/Commands/Dev/`.

**`routes/console.php`**: closure-based commands, defined with `Artisan::command(...)`, and the schedule. Shared hosting has no persistent worker process. It typically has only one usable cron slot. The pattern is this: a single crontab entry runs `php artisan schedule:run` every minute. Everything else — draining the queue, and periodic sync jobs — is registered here as a scheduled task. Do not add these as separate crontab lines, and do not run a long-running `queue:work` process.

```php
Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
Schedule::command('app:sync-something')->daily()->withoutOverlapping();
```

**`database/migrations/`**: one timestamped file per schema change. The template ships the auth migrations: `users`, `roles`, `role_permissions`, `user_logins`, and `audit_log`. Add app-specific tables the same way, as you build each feature. Never edit a migration that has already been deployed.

**`database/seeders/`**: `DatabaseSeeder.php` is the entry point, run with `php artisan db:seed`. `RolesSeeder` seeds the built-in `superuser` role. Add app-specific seeders alongside it, for example additional roles or reference and lookup data, and register each one in `DatabaseSeeder::run()`. Write seeders to be idempotent, using `updateOrCreate` rather than `create`. This way, re-running a seeder in production does not duplicate rows.

**`database/factories/`**: Eloquent model factories, for tests and for local fixture data. Keep these separate from seeders. Factories generate throwaway or random data, for tests and for `db:seed --class=...` demo runs. Seeders own deterministic, meaningful production data, such as roles, permissions, and reference tables.

## Enforcement

`bin/check-layers` is a grep-based script. It verifies that the rules above are not broken:

- Controllers contain no `use App\Repositories\...` imports, no `use App\Models\...` imports, and no `DB::` calls.
- Services contain no `DB::` calls, and no direct Eloquent query methods, such as `::where`, `::find`, `::create`, `->save()`, `::all()`, or `::first()`.
- Blade templates contain no `DB::` calls or Eloquent query methods.
- Every `RepositoryInterface` under `app/Repositories/` has a matching binding in `AppServiceProvider`.

Run this script before you consider a change done. It is a shell script, not a CI job. Shared hosting has no CI, so this script is the substitute. It is fast enough to run on every save, and cheap enough that there is no excuse to skip it.

When an app builds up its own renamed or retired classes, add a project-specific "stale reference" check to `bin/check-layers`. This check is a grep for old class names, scoped to `app/`, `routes/`, and `tests/`. It is not part of the shared template, because it is app-specific by nature. The pattern is still worth repeating in each app.

## Testing

See `@docs/claude/testing.md` for the full testing conventions: coverage checklists, validation rules, and mocking patterns. In short: use PHPUnit, use Mockery for repository mocks, unit tests need no database, and new code is not done until it has tests.
