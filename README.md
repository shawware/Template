# Template

This is a Laravel starter for apps that are deployed to shared hosting. The target hosting is DreamHost-class: no superuser access, Apache with PHP cgi-fcgi, MySQL, and no Node on the server. The template comes from existing apps. Future apps do not need to rebuild users, roles, Google sign-in, or the deploy steps from scratch.

## Status

The template is scaffolded and verified. It is a real Laravel 13.8 / PHP 8.5 app. It implements everything in `docs/claude/`: RBAC, Google OAuth, `AUTH_DISABLED`, audit logging, the layering rules with `bin/check-layers`, a CDN-only frontend, deploy scripts, and a passing PHPUnit suite. If you are new here, read the docs first. The code was built to match the docs. The docs are not just background material.

## Local setup

This setup is standard Laravel. It has no template-specific steps.

```bash
composer install
cp .env.example .env
php artisan key:generate
# create a local MySQL database matching DB_DATABASE in .env, then:
php artisan migrate --seed
php artisan serve
```

Or run `composer run setup`. This one command does all of the steps above: `composer install`, `.env` setup, key generation, migrate, and seed.

**`.env` must exist before you run tests.** This applies even though `phpunit.xml` sets its own test `APP_KEY`. Without `.env`, `php artisan test` still passes. But every test logs a warning: `file_get_contents(.../.env): Failed to open stream`. This warning comes from `nunomaduro/collision`'s `TestCommand`. It tries to read `.env` on every run, to know which env vars to clear before it hands off to PHPUnit. This is unrelated to PHPUnit's own config. Run `cp .env.example .env` to stop the warnings. You do not need to generate a key just for tests. This step is not checked in, because `.env` is gitignored, as it should be. It is a one-time step after you clone the repo. The repo cannot do this step for you.

## Keeping this updated

Dependabot is configured in `.github/dependabot.yml`. It watches Composer dependencies weekly. It groups minor and patch bumps into one PR. It opens major bumps as separate PRs, so they stand out for review. There is no equivalent npm/Node surface to watch. That is a direct payoff of not carrying a build step.

Patch and minor Dependabot PRs are generally safe to merge once CI is green, or once a local `composer test` and `bin/check-layers` both pass. A **major** version bump — Laravel 13 to 14, PHP 8.5 to 8.6, and similar — deserves a deliberate pass. Do not just click merge.

1. Read the upgrade guide for what changed.
2. Bump the version. Then run the full verification: `php artisan migrate:fresh --seed`, `php artisan test`, `bin/check-layers`, and a manual smoke test. The smoke test should cover login, a permission-gated admin page, and `AUTH_DISABLED`.
3. Run `composer audit` while you are at it.

This verification pass matters more than it sounds. It is exactly how the unparenthesized-ternary bug in the roles view was caught in the first place. A version bump is exactly the kind of change that can bring back a bug like it, silently.

This template does not propagate updates to apps that have already forked from it. Bumping `Template`'s own dependencies only affects the starting point for the next app that clones it. LevyWise, MYBOS, and any app already bootstrapped stay on whatever they started with. This is a deliberate scope limit, not an oversight.

## Documents

| File | Covers |
| --- | --- |
| [`CLAUDE.md`](CLAUDE.md) | Project brief: tech stack, environments, guidelines. Every new app's own `CLAUDE.md` starts from this file. |
| [`docs/claude/architecture.md`](docs/claude/architecture.md) | Controller → Service → Repository → Model layering, and `check-layers` enforcement |
| [`docs/claude/auth.md`](docs/claude/auth.md) | Full RBAC, Google OAuth, `AUTH_DISABLED`, and audit logging: the schema and the flow |
| [`docs/claude/ui.md`](docs/claude/ui.md) | CDN Tailwind and Alpine, no build step, semantic colour tokens |
| [`docs/claude/deployment.md`](docs/claude/deployment.md) | Shared-hosting deploy steps, `.env` discipline, deploy scripts |
| [`docs/claude/testing.md`](docs/claude/testing.md) | PHPUnit conventions, coverage checklists, and the validation/mocking rules that keep tests free of a database |

## Settled going in

- **PHP 8.5** everywhere.
- **Latest stable** versions of every dependency, rather than versions matched to either app's history.
- **Full RBAC by default**: `roles` and `role_permissions` tables, a `hasPermission()` method, and `permission:{key}` middleware.
- **`AUTH_DISABLED`** is a standard env flag, for local dev and for demo sites that need auth turned off. You must set it to true to disable auth. The default is off.
- Good architecture and good test practice are explicit goals of the template. The layering discipline and the PHPUnit-plus-Mockery, database-free unit tests are not incidental to whatever ships fastest.

## Next

The next task is to work out the bootstrap step. This step turns a clone of this template into a fresh, renamed app in one pass: new `APP_NAME`, a new namespace if needed, a fresh `APP_KEY`, and fresh git history. This step should be validated against the first real app that uses this template, rather than designed ahead of that need.
