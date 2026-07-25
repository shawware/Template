# {{APP_NAME}}

> This is the template `CLAUDE.md`. When bootstrapping a new app from this template, replace the title, the Problem/Objective sections, and the App Data section below with the new app's own content. Everything from Tech Stack onward is the shared foundation — keep it unless the new app has a specific reason to diverge, and note the reason here if it does.

## Problems We're Trying to Solve

_Describe the problem this app exists to solve._

## High-Level Objective

_One or two sentences: what does this app let its users do?_

## App Data

_Domain-specific facts worth Claude having on hand — e.g. a single-tenant app's organisation name, key identifiers, business-year boundaries. Delete this section if there's nothing domain-specific yet._

## Project Definition

### Project Requirements

_Bullet list of what the app needs to do, at the level of "accepts X as input", "stores Y", "lets the user configure Z"._

### Deployment Environment

Shared hosting, constrained:

- No superuser access
- Apache webserver with PHP cgi-fcgi SAPI
- composer available on the command line
- MySQL database
- No support for Java, Node.js, npm, npx on the server — Tailwind and Alpine.js are loaded from CDN, no build step required to deploy
- Deployment via `git clone` / `git pull` on the server
- Web hosting document root is configured to `/public` (not the project root)
- `public/index.php` uses `$app->usePublicPath(__DIR__)` accordingly
- `.env` values with spaces must be quoted (e.g. `APP_NAME="My App"`)

See `@docs/claude/deployment.md` for the full deploy walkthrough.

### Development Environment

- MacOS, full superuser access
- Apache webserver, MySQL database, PHP support
- PHPStorm, Chrome, Claude in Chrome

### Tech Stack and Patterns

- PHP 8.5
- Laravel (latest stable at time of scaffolding)
- PHPUnit
- REST, MVC, CRUD
- UI: Alpine.js + Tailwind CSS loaded from CDN, no build step, no Node dependency anywhere in the app — see `@docs/claude/ui.md`

### Scale

Assume a small system with few users unless the app's own requirements say otherwise. Concurrency is unlikely to be an issue, but use database transactions regardless.

### Authentication

Session-based (Laravel web guard) with email/password and Google OAuth (`laravel/socialite`). Full role-based access control (`roles` + `role_permissions`, not a package). `AUTH_DISABLED=true` in `.env` bypasses all auth (local dev / demo sites). See `@docs/claude/auth.md` for full details.

### Guidelines

- Keep it simple
- Follow the architectural rules
- Correctness above all else
- Lightweight
- Nice UI, but keep it minimalist
- Manual deployment support
- Build and test as you go
- New code needs new tests before the work is considered done — see `@docs/claude/testing.md`

### Data Storage

- RDBMS schema (MySQL in production and development)
- Database connection properties live in `.env`, never in the repo
- Schema reference: maintain a `docs/claude/schema.md` per app once domain tables exist (read on demand, not auto-loaded)

@docs/claude/architecture.md
@docs/claude/ui.md
@docs/claude/auth.md
@docs/claude/deployment.md
@docs/claude/testing.md
