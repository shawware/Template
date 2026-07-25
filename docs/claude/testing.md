# Testing

PHPUnit, with Mockery for repository mocks and Faker for fixture data. Unit tests never hit a real database — that's what the Repository layer and Mockery are for (see `@docs/claude/architecture.md`).

**Run `cp .env.example .env` before running tests, even locally with no real credentials needed.** `phpunit.xml` hardcodes its own test `APP_KEY` so the suite doesn't need a generated one, but Laravel's `artisan test` command (via `nunomaduro/collision`) still tries to read `.env` on every invocation regardless of PHPUnit's own config. Without it, tests still pass but log a spurious `file_get_contents(.../.env)` warning per test. See the README's Local Setup section.

## New Code → New Tests

Every new public service method and every new controller action needs tests before the work is considered done. This applies to both happy paths and rejection/error paths — a service method that only has a happy-path test hasn't been tested, it's been demoed.

## Feature Test Coverage Checklist

For each controller action:

- The happy path (valid input, correct redirect/response — `assertSessionHas('success')` for redirecting actions)
- Each required field missing (`assertSessionHasErrors`)
- Invalid enum values (`assertSessionHasErrors`)
- Wrong type for FK fields, e.g. non-integer (`assertSessionHasErrors`) — but not invalid ID *values*, which are tested at the service layer instead (see Validation Rules below)
- Duplicate values where uniqueness matters (`assertSessionHasErrors` or `assertUnprocessable`)
- File upload validation (wrong MIME type, missing file) where a file input exists
- Blocked operations where the service may reject (e.g. delete blocked because dependents exist)

## Service Unit Test Checklist

For each service method:

- Every `throw ValidationException` path has a corresponding test, including partial/boundary cases — not just the fully-invalid case
- Boolean flags forwarded to repositories are argument-verified via Mockery `->with(...)` constraints, not just call-count
- Data payloads passed to `create`/`update`/`upsert` repository calls assert the specific fields that matter for business correctness (`withArgs` or `andReturnUsing` capture) — not just that the method was called
- Derived values (computed dates, totals, financial-year-style rollups) are verified as reaching the repository correctly, not just that the repo method was called once
- When a test relies on Mockery expectations alone (no `$this->assert*` calls), use `->once()` or `->times()` so Mockery verifies the call count — otherwise PHPUnit marks the test risky, and the assertion is implicit rather than explicit

## Validation Rules

Controller `$request->validate()` rules must cover:

- **Enum fields** — always `in:value1,value2,...`
- **FK fields** — `integer` (or `string`) to reject the wrong *type*; do **not** use `exists:table,id`
- **Uniqueness** — do **not** use `Rule::unique` in controllers

**Why not `exists:table,id` or `Rule::unique`:** both run direct database queries that bypass the Repository layer, which breaks the mock-based feature test pattern — the validator hits a real database that doesn't exist in a unit-test context. Enforce these at the service layer instead:

- **FK existence**: the service checks the record exists when it fetches it (e.g. a `find...()` repository call returning `null`) and throws `ValidationException::withMessages([...])` if not
- **Uniqueness**: add a `nameExists...()` (or equivalent) method to the relevant repository interface and Eloquent implementation, check it in the service method, and throw `ValidationException::withMessages([...])` on a duplicate

This keeps every check mockable and consistent with the layered architecture — a controller test never needs a real database to exercise a validation failure.

## Layering Enforcement

Run `bin/check-layers` as part of the "done" checklist for any change touching controllers, services, or repositories — see `@docs/claude/architecture.md`. It's a local script, not a CI job (shared hosting has no CI), so it only catches drift if it's actually run.
