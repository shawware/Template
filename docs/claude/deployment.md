# Deployment Guide

The target is DreamHost-class shared hosting, deployed through git. There is no superuser access, no persistent worker process, and no Node or npm on the server.

## Hosting Notes

- **Document root**: set this to `/public` in the hosting panel, not the project root.
- **PHP**: cgi-fcgi SAPI, **version 8.5**, for example `/usr/local/php85/bin/php` on DreamHost.
- **Database**: MySQL 8.0. The host is whatever the provider assigns; it is not `127.0.0.1`.
- **No npm or Node.js anywhere, including the server**: Tailwind and Alpine.js load from a CDN. See `@docs/claude/ui.md`. The template has no `package.json`, and no build step to run, on the server or off it.

## First-Time Setup

### 1. Generate a deploy key on the server

```bash
ssh-keygen -t ed25519 -C "yourapp.example.com deploy" -f ~/.ssh/deploy_key -N ""
```

### 2. Add the public key to GitHub

Copy `~/.ssh/deploy_key.pub`. Add it as a read-only deploy key, under repo → Settings → Deploy keys.

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

> Quote any value that contains spaces, for example `APP_NAME="Your App"`. Run `./scripts/check-env.sh` locally to validate `.env` before you deploy. This script parses `.env` with the same `phpdotenv` library Laravel uses, so it catches a bad quote or a stray space before that error reaches the server.

**Convention: always use `config(...)`, never a bare `env()` outside `config/*.php`.** `env()` reads the process environment directly. It returns `null` once `config:cache` has run in production. Anything read with `env()` outside a config file breaks silently, the first time caching is turned on.

**Convention: `.env` lives outside `public/`, as a sibling of it, never inside the web root.** If the hosting panel's document root is ever misconfigured to the project root instead of `/public`, an `.env` file inside `public/` becomes directly downloadable.

### 8. Generate app key

```bash
/usr/local/php85/bin/php artisan key:generate
```

### 9. Apply the database schema

```bash
/usr/local/php85/bin/php artisan migrate
```

This command is safe to re-run. It skips any migration that has already been applied.

### 10. Configure Google OAuth

In the [Google Cloud Console](https://console.cloud.google.com/), open the OAuth 2.0 client. Add the redirect URI for this environment: `https://yourapp.example.com/user/auth/google/callback`. Register a separate URI for each environment: production, demo, and staging.

```dotenv
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_ALLOWED_DOMAIN=yourdomain.com
```

> Google OAuth requires HTTPS. It will not work over plain `http://`.

### 11. Create the first admin user

```bash
/usr/local/php85/bin/php artisan app:create-superuser
```

This command is interactive. It prompts for a name, email, and password. It creates the `superuser` role if that role does not exist yet, then creates the account. From there, the superuser manages other users and roles through the admin UI.

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

- **`scripts/check-env.sh [path-to-.env]`**: validates `.env` syntax, using Laravel's own `phpdotenv` parser, before it ever reaches the server. Run this script locally before every deploy.
- **`scripts/deploy-package.sh [output-file]`**: packages the app into a `.tar.gz` file for upload. This excludes `.git`, `.env`, dev-only tooling such as `tests/`, `phpunit.xml`, `CLAUDE.md`, and `docs/`, local databases, and OS files such as `.DS_Store`. Use this script as an alternative to `git pull`, on hosts where SSH deploy keys are not practical.

## Security Notes

- Set `APP_DEBUG=false` in production. Otherwise, stack traces expose file paths and schema details.
- Do not commit `.env`. Verify `.gitignore` covers it.
- The CDN-loaded Tailwind (Play CDN) has no SRI hash option. This is a documented, accepted risk for small internal tools. Self-hosting a compiled build, just to enable SRI, is not worth the build step it would bring back. Alpine.js, and any other CDN script with a static URL, should carry an SRI hash. Generate one at [srihash.org](https://www.srihash.org).
- Run `composer audit` before each deploy, to catch known CVEs in `composer.lock`.
