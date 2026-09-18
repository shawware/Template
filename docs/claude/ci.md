# Continuous Integration

GitHub Actions runs a CI workflow on every push to `main` and on every pull request. The workflow lives at `.github/workflows/ci.yml`. Shared hosting itself has no CI, but the repository's GitHub remote does, and it costs nothing extra on a public or a standard private repo.

## What the workflow checks

1. **Tests** — `php artisan test`, against the same in-memory SQLite setup `phpunit.xml` already uses. No database service container is needed.
2. **Layering rules** — `bin/check-layers`. See `@docs/claude/architecture.md`.
3. **Code style** — `vendor/bin/pint --test`, a dry run that fails if any file is not already Pint-formatted.
4. **Dependency audit** — `composer audit`, checking `composer.lock` against known vulnerability advisories.

### Static analysis (security) — temporarily disabled

`vendor/bin/psalm --taint-analysis` is meant to trace untrusted input (request data, query strings, uploaded files) through the code to dangerous sinks (raw SQL, `exec`, file paths, `eval`). The `psalm/plugin-laravel` plugin teaches Psalm about Eloquent and Laravel's other magic methods, cutting down on false positives. `psalm.xml` sets `findUnusedCode="false"`, since Laravel routes and service-provider bindings call code in ways Psalm cannot see, and would otherwise misreport as unused.

This step is commented out of `composer.json`'s `ci` script and `.github/workflows/ci.yml`. `vimeo/psalm` 6.17.2 (the latest release compatible with `psalm/plugin-laravel` 3.16.1) crashes with `Unrecognized template '$key'` while scanning Laravel's own nested conditional-return docblocks, for example `config()` and `cache()` in `Illuminate\Foundation\helpers.php`:

```
@return ($key is null ? \Illuminate\Cache\CacheManager : ($key is string ? mixed : bool))
```

This happens during Psalm's autoload-file scan, before it reaches any project code, so it cannot be worked around with a `psalm.xml` `ignoreFiles` entry — those only apply to `projectFiles`, not to the composer `files`-autoloaded sources Psalm scans to learn global function signatures. The only known fix is `psalm/plugin-laravel` 4.x, which requires `vimeo/psalm ^7.0.0-beta19`; psalm 7 has no stable release yet.

**Re-enable this step** once `vimeo/psalm` 7 reaches a stable release: `composer require --dev vimeo/psalm:^7.0 psalm/plugin-laravel:^4.0`, confirm `vendor/bin/psalm --taint-analysis` runs clean, then uncomment the step in both `composer.json` and `.github/workflows/ci.yml`.

## Running these checks locally

`composer run ci` runs everything the workflow runs, in the same order:

```bash
composer run ci
```

Each step also runs on its own, so you can run just the one you need:

```bash
php artisan test
bin/check-layers
vendor/bin/pint --test
vendor/bin/psalm --taint-analysis
composer audit
```

Drop `--test` from the Pint command to have it fix style issues in place, instead of only reporting them.

## Keeping this passing after a dependency bump

A `composer update` can reintroduce a style or analysis failure indirectly, for example by pulling in a new transitive dependency with a known vulnerability. Run the full list above after any non-trivial dependency bump, not only `composer audit` on its own. See the README's "Keeping this updated" section for the full major-version-bump checklist.
