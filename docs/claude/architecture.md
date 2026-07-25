# Architecture

## Application Layers

The codebase is structured in strict layers. Each layer may only depend on the layer directly below it.

```text
Controllers  →  Services  →  Repositories  →  Models / DB
```

**Rules:**

- Controllers inject Services only — never repositories or models directly
- Services inject Repositories only — never models or DB facades directly
- Repositories contain all Eloquent and DB facade calls; no business logic
- Services contain all business logic; no Eloquent or DB calls

**Locations:**

- `app/Http/Controllers/` — HTTP layer; validate input, call service, return response
- `app/Services/` — business logic and orchestration
- `app/Repositories/` — data access; one interface + one Eloquent implementation per model group
- `app/Models/` — Eloquent model definitions only

**Dependency injection:**

- All repository interfaces are bound to their Eloquent implementations in `AppServiceProvider::register()`
- Laravel's container resolves dependencies automatically via constructor type-hints
- In unit tests, repository interfaces are mocked (Mockery) — no database required

**Repositories the template ships with:**

- `UserRepositoryInterface` → `EloquentUserRepository`
- `RoleRepositoryInterface` → `EloquentRoleRepository`
- `UserLoginRepositoryInterface` → `EloquentUserLoginRepository`
- `AuditLogRepositoryInterface` → `EloquentAuditLogRepository`

Every app-specific model group gets its own interface + Eloquent pair, bound the same way, added to the list above in that app's own copy of this doc.

## Other Directories

**`app/Console/Commands/`** — artisan commands (`CreateSuperuser`, one-off admin/maintenance commands). A command is a thin entry point, the same way a controller is: it parses input (arguments/options/prompts) and calls a Service — it doesn't contain business logic itself. `bin/check-layers` doesn't currently scan `app/Console/`, so this is enforced by discipline, not tooling; a command with real logic in its `handle()` method is a sign that logic belongs in a Service instead, where it's testable and reusable from the web side too. Dev-only commands (not meant to ship, or only useful locally) live under `app/Console/Commands/Dev/`.

**`routes/console.php`** — closure-based commands (`Artisan::command(...)`) and the schedule. Shared hosting has no persistent worker process and typically only one usable cron slot, so the pattern is: a single crontab entry runs `php artisan schedule:run` every minute, and everything else — draining the queue, periodic sync jobs — is registered here as scheduled tasks rather than as separate crontab lines or a long-running `queue:work`:

```php
Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
Schedule::command('app:sync-something')->daily()->withoutOverlapping();
```

**`database/migrations/`** — one timestamped file per schema change. The template ships the auth migrations (`users`, `roles`, `role_permissions`, `user_logins`, `audit_log`); app-specific tables are added the same way as features are built, never by editing an already-deployed migration.

**`database/seeders/`** — `DatabaseSeeder.php` is the entry point (`php artisan db:seed`). `RolesSeeder` seeds the built-in `superuser` role; app-specific seeders (additional roles, reference/lookup data) are added alongside it and registered in `DatabaseSeeder::run()`. Seeders should be idempotent (`updateOrCreate`, not `create`) so re-running one in production doesn't duplicate rows.

**`database/factories/`** — Eloquent model factories for tests and local fixture data. Keep these separate from seeders in purpose: factories generate throwaway/random data for tests and `db:seed --class=...` demo runs; seeders own deterministic, meaningful production data (roles, permissions, reference tables).

## Enforcement

`bin/check-layers` is a grep-based script that verifies the rules above aren't violated:

- Controllers contain no `use App\Repositories\...` imports, no `use App\Models\...` imports, no `DB::` calls
- Services contain no `DB::` calls and no direct Eloquent query methods (`::where`, `::find`, `::create`, `->save()`, `::all()`, `::first()`)
- Blade templates contain no `DB::` calls or Eloquent query methods
- Every `RepositoryInterface` under `app/Repositories/` has a matching binding in `AppServiceProvider`

Run it before considering a change done. It's a shell script, not a CI job — shared hosting has no CI, so this is the substitute: fast enough to run on every save, cheap enough that there's no excuse not to.

When an app accumulates its own renamed/retired classes, add a project-specific "stale reference" check to `bin/check-layers` (a grep for old class names, scoped to `app/`, `routes/`, `tests/`) — it isn't part of the shared template because it's app-specific by nature, but the pattern is worth repeating.

## Testing

See `@docs/claude/testing.md` for the full testing conventions (coverage checklists, validation rules, mocking patterns). The short version: PHPUnit, Mockery for repository mocks, no database required for unit tests, and new code doesn't count as done without tests.
