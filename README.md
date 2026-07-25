# Template

A Laravel starter for apps deployed to shared hosting (DreamHost-class: no superuser access, Apache + PHP cgi-fcgi, MySQL, no Node on the server). Extracted from existing apps so future apps don't have to reinvent users, roles, Google sign-in, or the deploy dance from scratch.

## Status

Scaffolded and verified: a real Laravel 13.8 / PHP 8.5 app implementing everything in `docs/claude/` — RBAC, Google OAuth, `AUTH_DISABLED`, audit logging, layering + `bin/check-layers`, CDN-only frontend, deploy scripts, and a passing PHPUnit suite. Read the docs first if you're new here; they're the reference the code was built against, not just background.

## Local setup

Standard Laravel — nothing template-specific:

```bash
composer install
cp .env.example .env
php artisan key:generate
# create a local MySQL database matching DB_DATABASE in .env, then:
php artisan migrate --seed
php artisan serve
```

Or `composer run setup` does all of the above (`composer install`, `.env`, key, migrate, seed) in one step.

**`.env` must exist before running tests, even though `phpunit.xml` hardcodes its own test `APP_KEY`.** Without it, `php artisan test` still passes, but every test logs a spurious `file_get_contents(.../.env): Failed to open stream` warning — that's `nunomaduro/collision`'s `TestCommand` trying to read `.env` on every invocation (to know which env vars to clear before delegating to PHPUnit), unrelated to PHPUnit's own config. `cp .env.example .env` (no need to generate a key just for tests) makes the warnings disappear. This isn't checked in — `.env` is gitignored, as it should be — so it's a one-time step after cloning, not something the repo can do for you.

## Keeping this updated

Dependabot is configured (`.github/dependabot.yml`) to watch Composer dependencies weekly, grouping minor/patch bumps into one PR and opening major bumps separately so they stand out for review. There's no equivalent npm/Node surface to worry about — that's a direct payoff of not carrying a build step.

Patch/minor Dependabot PRs are generally safe to merge once CI (or a local `composer test` + `bin/check-layers`) is green. A **major** version bump (Laravel 13→14, PHP 8.5→8.6, etc.) deserves a deliberate pass, not just a merge click:

1. Read the upgrade guide for what changed.
2. Bump and run the full verification: `php artisan migrate:fresh --seed`, `php artisan test`, `bin/check-layers`, then a manual smoke test (login, a permission-gated admin page, `AUTH_DISABLED`).
3. Run `composer audit` while you're in there.

That verification pass matters more than it sounds — it's exactly how the unparenthesized-ternary bug in the roles view got caught originally, and a version bump is precisely the kind of change that can reintroduce something like it silently.

One thing this doesn't do: propagate updates to apps that have already forked from this template. Bumping `Template`'s own dependencies only affects the starting point for whichever app clones it *next* — LevyWise, MYBOS, and anything already bootstrapped stay on whatever they started with. That's a deliberate scope limit, not an oversight.

## Documents

| File | Covers |
| --- | --- |
| [`CLAUDE.md`](CLAUDE.md) | Project brief — tech stack, environments, guidelines. The file every new app's own `CLAUDE.md` starts from. |
| [`docs/claude/architecture.md`](docs/claude/architecture.md) | Controller → Service → Repository → Model layering, `check-layers` enforcement |
| [`docs/claude/auth.md`](docs/claude/auth.md) | Full RBAC, Google OAuth, `AUTH_DISABLED`, audit logging — the schema and the flow |
| [`docs/claude/ui.md`](docs/claude/ui.md) | CDN Tailwind + Alpine, no build step, semantic colour tokens |
| [`docs/claude/deployment.md`](docs/claude/deployment.md) | Shared-hosting deploy steps, `.env` discipline, deploy scripts |
| [`docs/claude/testing.md`](docs/claude/testing.md) | PHPUnit conventions, coverage checklists, the validation/mocking rules that keep tests DB-free |

## Settled going in

- **PHP 8.5** everywhere.
- **Latest stable** versions of every dependency, rather than matching either app's historical pins.
- **Full RBAC by default** — `roles` + `role_permissions`, `hasPermission()`, `permission:{key}` middleware.
- **`AUTH_DISABLED`** env flag ships standard, for local dev and demo sites that need auth bypassed. Must be set to true to disable auth; the default is off.
- Good architecture (the layering discipline) and good test practice (PHPUnit + Mockery, DB-free unit tests) are explicit goals of the template, not incidental to whatever ships fastest.

## Next

Work out the bootstrap step — turning a clone of this template into a fresh, renamed app (new `APP_NAME`, namespace if needed, fresh `APP_KEY`, fresh git history) in one pass. Best validated against whichever real app uses this template first, rather than designed speculatively.
