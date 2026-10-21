# Visible account family selection and display

## Goal

Make Personal/Business family selection visible during account creation and display the persisted family in account list/detail, enabling an honest UI demonstration of Abstract Factory.

## Tasks

- [x] Add regression coverage for family selection and persisted display.
- [x] Update account creation form and account list/detail to show the selected/persisted family.
- [x] Run focused tests, formatting, and independent verification.

## Evidence

- `PeticionAbrirCuenta` already validates `familia` as `personal|empresarial` and defaults to personal.
- `AbrirCuenta` already routes package construction through `FabricaPaquetesCuentas`.
- Existing schema stores `familia`; UI currently omits selector and display.
- Family-specific policy names exist, but do not claim different fees/overdraft limits unless implementation supports them.
- Focused tests passed: 20 tests, 80 assertions. Independent review passed; `git diff --check` passed.
- Native review preflight found the current workspace candidate spans several separate uncommitted tasks; no candidate review was started to avoid reviewing the accumulated tree as one unit.

## Commits

- Pending. No commit authorized by the user.
