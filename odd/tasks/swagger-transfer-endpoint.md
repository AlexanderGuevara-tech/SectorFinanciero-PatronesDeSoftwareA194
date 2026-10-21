# Swagger UI for account transfer endpoint

## Goal
Expose an integrated Swagger UI and document the existing `POST /accounts/transfer` route without changing its transaction behavior or weakening its current authentication and authorization.

## Tasks
- [x] Select and add a Laravel-version-compatible Swagger/OpenAPI integration dependency and configuration.
- [x] Document the existing transfer endpoint's request, validation, authentication/authorization, and response behavior; expose the Swagger UI.
- [x] Add focused coverage and run formatter/tests relevant to changed PHP code.

## Constraints
- Preserve all pre-existing working-tree changes and unrelated files.
- Keep transfer execution as POST; do not add GET mutation behavior or alter the transfer domain logic.
- Existing route uses web session authentication and `can:manage-accounts`; Swagger documents session and CSRF requirements. Swagger UI and spec endpoints require authentication and `manage-accounts`.
- Do not commit without explicit user authorization.

## Evidence
- Added `darkaonline/l5-swagger` 11.2.0; Composer resolved dependencies under PHP 8.3.16 and Laravel 13.17 constraints.
- OpenAPI generation succeeded with `php artisan l5-swagger:generate`.
- Focused coverage passed: `php artisan test --compact tests/Feature/SwaggerTransferDocumentationTest.php` (2 tests, 10 assertions).
- Pint passed on the changed PHP files.
- No commit was created.
- Final status inspection exposed unrelated documentation deletions not present in the initial status snapshot. An independent read-only audit could not attribute them; no restore or cleanup was attempted. Further review is paused pending a clean attribution check.
