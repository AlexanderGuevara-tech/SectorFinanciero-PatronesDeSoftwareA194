# Safe Adapter simulation demo

## Goal

Provide an authenticated, read-only UI that demonstrates KYC, risk, and transfer-limit Adapter ports using local simulated providers, without executing or persisting a transfer.

## Tasks

- [x] Add feature coverage for access control, simulation disclosure, adapter outcomes, and no financial side effects.
- [x] Implement a dedicated read-only demo route/controller/view using existing simulated adapters/policy without invoking transfer execution.
- [x] Run focused tests, formatting, and independent verification.

## Evidence

- The existing transfer route executes `TransferirFondos` and mutates account balances/ledger; it is unsafe as a demo endpoint.
- Existing adapter ports are bound to simulated implementations in `AppServiceProvider`.
- User explicitly selected a realistic local simulation with no external provider connection.
- Route: `GET /admin/adapters/demo` (`admin.adapters.demo`), protected by `auth` and `can:manage-accounts`.
- Scenarios shown: all approvals, KYC rejection, risk alert, and transfer-limit rejection.
- Tests: 3 passed, 19 assertions; independent verification and `git diff --check` passed.
- Native review was not started because the workspace candidate contains multiple independent uncommitted tasks and must not be reviewed as one accumulated change.

## Constraints

- The UI must state these are simulated integrations and not real provider calls.
- The demo must not invoke `TransferenciaWebFacade` or `TransferirFondos`, nor persist financial data.

## Commits

- Pending. No commit authorized by the user.
