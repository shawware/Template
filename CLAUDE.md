# {{APP_NAME}}

> This file is the template `CLAUDE.md`. When you bootstrap a new app from this template, replace the title, the Problem/Objective sections, and the App Data section below with the new app's own content. Everything from Tech Stack onward is the shared foundation. Keep it unless the new app has a specific reason to diverge. If it does diverge, note the reason here.

## Problems We're Trying to Solve

_Describe the problem this app exists to solve._

## High-Level Objective

_State in one or two sentences what this app lets its users do._

## App Data

_List domain-specific facts worth Claude having on hand. Examples: a single-tenant app's organisation name, key identifiers, business-year boundaries. Delete this section if there is nothing domain-specific yet._

## Project Definition

### Project Requirements

_List what the app needs to do, at the level of "accepts X as input", "stores Y", "lets the user configure Z"._

### Deployment Environment

This app runs on shared hosting, with these constraints:

- No superuser access
- Apache webserver with PHP cgi-fcgi SAPI
- Composer is available on the command line
- MySQL database
- The server does not support Java, Node.js, npm, or npx. Tailwind and Alpine.js load from a CDN. No build step is needed to deploy.
- Deployment uses `git clone` or `git pull` on the server
- The web hosting document root is set to `/public`, not the project root
- `public/index.php` uses `$app->usePublicPath(__DIR__)` to match this
- `.env` values that contain spaces must be quoted, e.g. `APP_NAME="My App"`

See `@docs/claude/deployment.md` for the full deploy walkthrough.

### Development Environment

- macOS, with full superuser access
- Apache webserver, MySQL database, PHP support
- PHPStorm, Chrome, Claude in Chrome

### Tech Stack and Patterns

- PHP 8.5
- Laravel, latest stable version at the time of scaffolding
- PHPUnit
- REST, MVC, CRUD
- UI: Alpine.js and Tailwind CSS, loaded from a CDN. No build step, no Node dependency anywhere in the app. See `@docs/claude/ui.md`.

### Scale

Assume a small system with few users, unless the app's own requirements say otherwise. Concurrency is unlikely to be an issue. Use database transactions regardless.

### Authentication

Authentication is session-based, using the Laravel web guard. Users can log in with email and password, or with Google OAuth through `laravel/socialite`. The app has full role-based access control, built from `roles` and `role_permissions` tables rather than a package. Setting `AUTH_DISABLED=true` in `.env` bypasses all auth. Use this for local dev and demo sites. See `@docs/claude/auth.md` for full details.

### Guidelines

- Keep it simple.
- Follow the architectural rules.
- Correctness comes above all else.
- Keep the app lightweight.
- Give the UI a nice, minimalist design.
- Support manual deployment.
- Build and test as you go.
- New code needs new tests before you consider the work done. See `@docs/claude/testing.md`.

### Data Storage

- The app uses an RDBMS schema: MySQL, in both production and development.
- Database connection properties live in `.env`, never in the repo.
- Once an app has domain tables, maintain a `docs/claude/schema.md` file for it as a schema reference. Read this file on demand; do not auto-load it.

@docs/claude/architecture.md
@docs/claude/ui.md
@docs/claude/auth.md
@docs/claude/deployment.md
@docs/claude/testing.md
@docs/claude/ci.md
