# Testing

The template uses PHPUnit, with Mockery for repository mocks and Faker for fixture data. Unit tests never hit a real database. That is what the Repository layer and Mockery are for; see `@docs/claude/architecture.md`.

**Run `cp .env.example .env` before you run tests, even locally with no real credentials needed.** `phpunit.xml` hardcodes its own test `APP_KEY`, so the suite does not need a generated one. But Laravel's `artisan test` command, through `nunomaduro/collision`, still tries to read `.env` on every invocation, regardless of PHPUnit's own config. Without `.env`, tests still pass, but each test logs a spurious `file_get_contents(.../.env)` warning. See the README's Local Setup section.

## New Code → New Tests

Every new public service method, and every new controller action, needs tests before you consider the work done. This applies to both happy paths and rejection or error paths. A service method with only a happy-path test has not been tested; it has been demoed.

## Feature Test Coverage Checklist

For each controller action, test:

- The happy path: valid input, and the correct redirect or response. Use `assertSessionHas('success')` for redirecting actions.
- Each required field, missing in turn. Use `assertSessionHasErrors`.
- Invalid enum values. Use `assertSessionHasErrors`.
- The wrong type for FK fields, for example a non-integer value. Use `assertSessionHasErrors`. Do not test invalid ID *values* here; test those at the service layer instead. See Validation Rules below.
- Duplicate values, where uniqueness matters. Use `assertSessionHasErrors` or `assertUnprocessable`.
- File upload validation, such as the wrong MIME type or a missing file, where a file input exists.
- Blocked operations, where the service may reject the request, for example a delete blocked because dependents exist.

## Service Unit Test Checklist

For each service method, test:

- Every `throw ValidationException` path, including partial and boundary cases, not just the fully-invalid case.
- Boolean flags that get forwarded to repositories. Verify these with Mockery `->with(...)` constraints, not just a call count.
- Data payloads passed to `create`, `update`, or `upsert` repository calls. Assert the specific fields that matter for business correctness, using `withArgs` or `andReturnUsing` to capture them. Do not just assert that the method was called.
- Derived values, such as computed dates, totals, or financial-year-style rollups. Verify that these reach the repository correctly, not just that the repo method was called once.
- Tests that rely on Mockery expectations alone, with no `$this->assert*` calls. Use `->once()` or `->times()` on these, so Mockery verifies the call count. Otherwise, PHPUnit marks the test risky, since the assertion stays implicit rather than explicit.

## Validation Rules

Controller `$request->validate()` rules must cover:

- **Enum fields**: always use `in:value1,value2,...`.
- **FK fields**: use `integer` or `string` to reject the wrong *type*. Do **not** use `exists:table,id`.
- **Uniqueness**: do **not** use `Rule::unique` in controllers.

**Why not `exists:table,id` or `Rule::unique`:** both run direct database queries. These queries bypass the Repository layer, which breaks the mock-based feature test pattern. The validator would hit a real database, and no real database exists in a unit-test context. Enforce these checks at the service layer instead:

- **FK existence**: have the service check that the record exists when it fetches it, for example when a `find...()` repository call returns `null`. Throw `ValidationException::withMessages([...])` if the record does not exist.
- **Uniqueness**: add a `nameExists...()` method, or an equivalent, to the relevant repository interface and its Eloquent implementation. Check it in the service method, and throw `ValidationException::withMessages([...])` on a duplicate.

This approach keeps every check mockable, and consistent with the layered architecture. A controller test never needs a real database to exercise a validation failure.

## Layering Enforcement

Run `bin/check-layers` as part of the "done" checklist, for any change that touches controllers, services, or repositories. See `@docs/claude/architecture.md`. GitHub Actions also runs this script on every push and pull request, so drift missed locally still gets caught before merge. See `@docs/claude/ci.md`.
