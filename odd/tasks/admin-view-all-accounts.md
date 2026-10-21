# Administrator account listing scope

## Goal

Allow authorized administrators to see every account in the account index by default, while preserving customer-scoped visibility for non-administrators.

## Tasks

- [x] Add regression coverage for administrator-wide listing and customer isolation.
- [x] Implement authorization-aware account listing using existing application/domain boundaries.
- [x] Run focused feature tests and formatting; independently verify the diff.

## Evidence

- `ControladorCuentas::index()` currently returns an empty list when `cliente` is not supplied.
- Existing `RepositorioCuentas` contract supports both `porCliente()` and `todos()`.
- The user's explicit requirement: an administrator can view all accounts.
- Tests: `VistaCuentasTest.php` passed (13 tests, 60 assertions); `ListarCuentasTest.php` passed (3 tests, 4 assertions).
- Independent review found no access-control regression; Pint and `git diff --check` passed.

## Commits

- Pending. No commit authorized by the user.
