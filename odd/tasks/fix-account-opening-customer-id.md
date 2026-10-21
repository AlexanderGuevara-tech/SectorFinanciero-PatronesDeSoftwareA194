# Fix account opening customer ID

## Goal

Prevent inline customer creation from passing a null customer ID to `AbrirCuenta::ejecutar()`.

## Tasks

- [x] Reproduce the empty `customer_id` regression with a focused feature test.
- [x] Fix validated request mapping to use the persisted inline customer's ID.
- [x] Run focused verification and format changed PHP files.

## Evidence

- Runtime error: `AbrirCuenta::ejecutar()` received `null` for `int $customerId` from `ControladorCuentas`.
- Root cause: `PeticionAbrirCuenta::validated()` retained the originally validated null value after `passedValidation()` merged the newly persisted customer ID.
- RED: the inline-customer test reproduced the HTTP 500 with `customer_id` empty.
- GREEN: `tests/Feature/PeticionAbrirCuentaTest.php` passes: 4 tests, 9 assertions.
- Independent verification passed; `git diff --check` and Pint passed.

## Commits

- Pending. No commit authorized by the user.
