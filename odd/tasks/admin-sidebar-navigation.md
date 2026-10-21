# Complete administrator sidebar navigation

## Goal

Expose every existing administrator-facing GET page in the authenticated sidebar, consistently across dashboard, accounts, customers, users, and roles, while preserving permission-based visibility. Do not add a transfer link because no transfer GET/form view exists.

## Tasks

- [x] Add regression coverage for consistent admin navigation and permission visibility.
- [x] Update controller-provided navigation entries using existing route/component conventions.
- [x] Run focused tests, formatting, and independent review.

## Evidence

- Sidebar renders navigation supplied by each controller.
- Current navigation differs between dashboard, accounts, details, customers, users, and roles.
- Existing GET destinations include dashboard, accounts, users, roles, customers, and Adapter demo.
- `accounts.transfer` is POST-only; no transfer view exists, so it must not be linked as a page.
- Focused verification passed: 24 tests, 120 assertions; Pint and `git diff --check` passed; independent navigation review passed.
- A delegated worker briefly reported an out-of-scope edit to the untracked Adapter demo controller; an independent audit could not establish its origin, so it was preserved untouched. The sidebar links its already-existing named GET route.

## Commits

- Pending. No commit authorized by the user.
