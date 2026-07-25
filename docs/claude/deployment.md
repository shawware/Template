# Deployment Guide

Target: DreamHost-class shared hosting, deployed via git. No superuser access, no persistent worker process, no Node/npm on the server.

## Hosting Notes

- **Document root**: set to `/public` in the hosting panel, not the project root
- **PHP**: cgi-fcgi SAPI, **8.5** (e.g. `/usr/local/php85/bin/php` on DreamHost)
- **Database**: MySQL 8.0, host is whatever the provider assigns (not `127.0.0.1`)
- **No npm/Node.js anywhere, including the server**: Tailwind and Alpine.js are loaded from CDN — see `@docs/claude/ui.md`. The template has no `package.json` and no build step to run, on the server or off it.

## First-Time Setup

### 1. Generate a deploy key on the server

```bash
ssh-keygen -t ed25519 -C "yourapp.example.com deploy" -f ~/.ssh/deploy_key -N ""
```

### 2. Add the public key to GitHub

Copy `~/.ssh/deploy_key.pub` and add it as a read-only deploy key: repo → Settings → Deploy keys.

### 3. Configure SSH on the server

```text
Host github.com
    IdentityFile ~/.ssh/deploy_key
    IdentitiesOnly yes
```

### 4. Clone the repo

```bash
git clone git@github.com:youruser/yourrepo.git /home/youruser/yourapp.example.com
```

### 5. Install dependencies

```bash
cd /home/youruser/yourapp.example.com
/usr/local/php85/bin/php /usr/local/php85/bin/composer install --no-dev --optimize-autoloader
```

### 6. Create storage directories

```bash
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 7. Create `.env`

```bash
cp .env.example .env
```

Set at minimum:

```dotenv
APP_NAME="Your App"
APP_ENV=production
APP_KEY=                    # generate with: php artisan key:generate
APP_DEBUG=false
APP_URL=https://yourapp.example.com
APP_TIMEZONE=Australia/Sydney

SESSION_DRIVER=file
CACHE_STORE=file

DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=yourapp
DB_USERNAME=yourapp
DB_PASSWORD=your-password
```

> Values containing spaces must be quoted (e.g. `APP_NAME="Your App"`). Run `./scripts/check-env.sh` locally to validate before deploying — it parses `.env` with the same `phpdotenv` Laravel uses, so a bad quote or stray space is caught before it breaks the server.

**Convention: always `config(...)`, never a bare `env()` outside `config/*.php`.** `env()` reads the process environment directly and returns `null` once `config:cache` has run in production — anything read via `env()` outside a config file silently breaks the first time caching is turned on.

**Convention: `.env` lives outside `public/`**, as a sibling of it — never inside the web root. If the hosting panel's document root is ever misconfigured to the project root instead of `/public`, an `.env` inside `public/` becomes directly downloadable.

### 8. Generate app key

```bash
/usr/local/php85/bin/php artisan key:generate
```

### 9. Apply the database schema

```bash
/usr/local/php85/bin/php artisan migrate
```

Safe to re-run — already-applied migrations are skipped.

### 10. Configure Google OAuth

In the [Google Cloud Console](https://console.cloud.google.com/), open the OAuth 2.0 client and add the redirect URI for this environment: `https://yourapp.example.com/user/auth/google/callback`. Register a separate URI per environment (production, demo, staging).

```dotenv
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_ALLOWED_DOMAIN=yourdomain.com
```

> Google OAuth requires HTTPS — it will not work over plain `http://`.

### 11. Create the first admin user

```bash
/usr/local/php85/bin/php artisan app:create-superuser
```

Interactive: prompts for name, email, password. Creates the `superuser` role if it doesn't exist, then the account. From there, the superuser manages other users and roles via the admin UI.

## Subsequent Deployments

```bash
cd /home/youruser/yourapp.example.com
git pull
/usr/local/php85/bin/php /usr/local/php85/bin/composer install --no-dev --optimize-autoloader
/usr/local/php85/bin/php artisan migrate
```

If config or routes changed, clear the caches:

```bash
/usr/local/php85/bin/php artisan config:clear
/usr/local/php85/bin/php artisan route:clear
/usr/local/php85/bin/php artisan view:clear
```

## Deploy Scripts

- **`scripts/check-env.sh [path-to-.env]`** — validates `.env` syntax using Laravel's own `phpdotenv` parser before it ever reaches the server. Run this locally before every deploy.
- **`scripts/deploy-package.sh [output-file]`** — packages the app into a `.tar.gz` for upload, excluding `.git`, `.env`, dev-only tooling (`tests/`, `phpunit.xml`, `CLAUDE.md`, `docs/`), local databases, and OS cruft (`.DS_Store`). An alternative to `git pull` on hosts where SSH deploy keys aren't practical.

## Security Notes

- `APP_DEBUG=false` in production — otherwise stack traces expose file paths and schema details
- `.env` not committed — verify `.gitignore`
- CDN-loaded Tailwind (Play CDN) has no SRI hash option — a documented, accepted risk for small internal tools; self-hosting a compiled build to enable SRI isn't worth the added build step it would reintroduce. Alpine.js (and any other CDN script with a static URL) should carry an SRI hash — generate one at [srihash.org](https://www.srihash.org).
- Run `composer audit` before each deploy to catch known CVEs in `composer.lock`.
