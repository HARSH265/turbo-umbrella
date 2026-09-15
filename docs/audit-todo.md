# SSMS Audit — Task Board

Work items arising from [audit-findings.md](audit-findings.md).
Each task names the finding(s) it clears, what will actually be done, and how it gets verified.

**Last updated:** 15 September 2026

---

## Status legend

| Status | Meaning |
|---|---|
| ✅ **COMPLETED** | Done and verified |
| 🔄 **IN PROGRESS** | Started, not finished |
| ⬜ **NOT STARTED** | Queued |
| ⏸️ **BLOCKED** | Waiting on a decision or permission |

---

## Board

| ID | Task | Findings | Priority | Status |
|---|---|---|---|---|
| T-01 | Fix `files:cleanup-orphans` data loss | F-02 | P0 | ⬜ NOT STARTED |
| T-02 | Put the project under git | F-03 | P0 | ⬜ NOT STARTED |
| T-03 | Resolve file soft-delete semantics | F-17 | P1 | ⬜ NOT STARTED |
| T-04 | Decide dispute scope for closed complaints | F-18 | P1 | ⏸️ BLOCKED |
| T-05 | Harden `Gate::before` against ability-name collisions | F-19 | P1 | ⬜ NOT STARTED |
| T-06 | Add route-sweep + dashboard smoke tests | F-05, F-06 | P2 | ⬜ NOT STARTED |
| T-07 | Cover Amenities, Vehicles, Files, ActivityLog | F-22 | P2 | ⬜ NOT STARTED |
| T-08 | Lift test helpers into `TestCase` | F-23 | P2 | ⬜ NOT STARTED |
| T-09 | Chunk and queue bulk notifications | F-25 | P3 | ⬜ NOT STARTED |
| T-10 | Make the notification bell lazy | F-26 | P3 | ⬜ NOT STARTED |
| T-11 | Delete dead files | F-34, F-35 | P4 | ⏸️ BLOCKED |
| T-12 | Decide resident visitor/vehicle permissions | F-36 | P4 | ⏸️ BLOCKED |
| T-13 | Unblock the environment | — | P0 | ✅ COMPLETED |
| T-14 | Restore the test suite | F-01, F-20, F-21 | P0 | ✅ COMPLETED |
| T-15 | Fix crashes and silent corruption | F-04…F-10 | P1 | ✅ COMPLETED |
| T-16 | Fix cache correctness | F-11…F-15 | P1 | ✅ COMPLETED |
| T-17 | Speed up RBAC | F-24 | P3 | ✅ COMPLETED |
| T-18 | Unify pagination | F-27…F-30 | P4 | ✅ COMPLETED |
| T-19 | Correct config and docs | F-16, F-31, F-32, F-33 | P4 | ✅ COMPLETED |

**12 findings open · 24 closed** — 12 open tasks, 7 completed.

> T-06 is the exception: the findings it references (F-05, F-06) are already fixed. It exists
> to add the regression tests that would have caught them, so they cannot come back.

---

# Open work

## T-01 · Fix `files:cleanup-orphans` data loss — ⬜ NOT STARTED
**Clears:** F-02 · **Priority:** P0 · **Est:** ~15 min

A scheduled-style command that permanently erases user-uploaded evidence. Do this first.

**What:**
1. `CleanupOrphanFiles.php` — change `Complaint::pluck('id')` and `Notice::pluck('id')` to `withTrashed()->pluck('id')`, so attachments of soft-deleted records are not treated as orphans.
2. `FileService::cleanupOrphans()` — return `0` immediately when `$validEntityIds` is empty. Laravel compiles `whereNotIn('x', [])` to `1 = 1`, which would match and delete every row for that module.
3. Add a `--dry-run` flag that reports what would be deleted without touching disk.

**Verify:** feature test with one live complaint, one soft-deleted complaint and one genuinely orphaned file — assert only the orphan is removed. Second test: zero complaints in table ⇒ zero files deleted.

---

## T-02 · Put the project under git — ⬜ NOT STARTED
**Clears:** F-03 · **Priority:** P0 · **Est:** ~10 min

There is a verified baseline right now (114 tests, 232 routes clean) and no way back to it.

**What:** `git init`, confirm `.gitignore` already excludes `vendor/`, `node_modules/`, `.env`, `storage/logs` (it does), then commit the current state as the baseline.

**Verify:** `git status` clean; `.env` and `vendor/` untracked.

> Run before T-01 if possible, so every later change is revertible.

---

## T-03 · Resolve file soft-delete semantics — ⬜ NOT STARTED
**Clears:** F-17 · **Priority:** P1 · **Est:** ~30 min

`FileService::delete()` erases the bytes but soft-deletes the row, leaving a record that
permanently points at nothing. Needs a decision, then consistency:

- **Option A (audit trail):** keep bytes on disk until a separate purge; soft delete only marks the row.
- **Option B (real delete):** `forceDelete()` the row alongside the file, and drop `SoftDeletes` from `File`.

**Recommendation:** Option A — it makes deletion genuinely reversible, and an attachment
register that can't produce the file is not much of an audit trail.

**Verify:** test that a soft-deleted `File` either still resolves on disk (A) or leaves no row (B).

---

## T-04 · Decide dispute scope for closed complaints — ⏸️ BLOCKED
**Clears:** F-18 · **Priority:** P1

`canDispute()` allows only `resolved`; the README promises resolved **or closed**.

**Needs from you:** should a resident be able to reopen a *closed* complaint?

- **Yes** → add `'closed'` to `canDispute()`, and relax the `updateStatus()` reopen guard to permit the dispute transition.
- **No** → correct the README instead.

Either way the fix is small; the direction is a product call.

---

## T-05 · Harden `Gate::before` — ⬜ NOT STARTED
**Clears:** F-19 · **Priority:** P1 · **Est:** ~20 min

Today it grants any ability whose name matches a permission slug, skipping the policy and
its tenant checks. Safe only by naming coincidence.

**What:** stop treating arbitrary ability names as permission slugs. Keep the super-admin
bypass; route permission checks through an explicit `@hasPermission` / `permission:`
middleware rather than the global `Gate::before` fallback. Where a policy exists, let it decide.

**Verify:** a society-admin holding `notices.update` must still be denied on another
society's notice — assert via a feature test that calls the ability by slug name.

---

## T-06 · Add route-sweep + dashboard smoke tests — ⬜ NOT STARTED
**Clears:** F-05, F-06 · **Priority:** P2 · **Est:** ~45 min

Both bugs were found by ad-hoc harnesses, not the suite. The 61 role-matrix tests never
rendered a dashboard or walked every route, so both crashes shipped silently. Make those
checks permanent.

**What:**
1. `DashboardRenderTest` — assert `/dashboard` returns 200 for super-admin, society-admin, resident and staff, with at least one complaint present so the badge branches actually execute.
2. `RouteSweepTest` — iterate `Route::getRoutes()`, substitute sample IDs, request each GET route as each role, and fail on any 5xx.

**Verify:** temporarily revert F-05 and confirm the new test fails.

> Highest value of the remaining work: this is the class of bug the suite is blind to.

---

## T-07 · Cover Amenities, Vehicles, Files, ActivityLog — ⬜ NOT STARTED
**Clears:** F-22 · **Priority:** P2 · **Est:** ~2 h

Zero tests today, and two shipped bugs (F-06, F-10) lived here.

**What:** role-matrix tests in the existing style — access control per role, cross-society
blocking, and the workflows that already broke: amenity creation (timestamps persisted),
vehicle listing as a resident, booking creation, file download authorisation via `FilePolicy`.

**Verify:** `php artisan test --filter=RoleMatrixTest` count rises from 61.

---

## T-08 · Lift test helpers into `TestCase` — ⬜ NOT STARTED
**Clears:** F-23 · **Priority:** P2 · **Est:** ~30 min

`createSocietyContext()` / `createUserWithRole()` / `assignRole()` exist in ~9 copies while
`tests/TestCase.php` is empty — and the README already claims the base class provides them.

**What:** move them to `TestCase` (or a `CreatesSocietyContext` trait), delete the private
copies, reconcile the signature difference in `MaintenanceRoleMatrixTest` (it takes an extra
`?Flat`). Do this **after** T-07 so new tests are written once, against the shared helpers.

**Verify:** all 114+ tests still pass; `grep -c "function createSocietyContext" tests/` returns 1.

---

## T-09 · Chunk and queue bulk notifications — ⬜ NOT STARTED
**Clears:** F-25 · **Priority:** P3 · **Est:** ~30 min

`sendToCollection()` loads every recipient and notifies serially; with `QUEUE_CONNECTION=sync`
a society-wide notice blocks the request for N inserts.

**What:** replace `->get()` with `->chunkById(200, …)`, and make `GeneralNotification`
implement `ShouldQueue`. Requires moving `.env` to `QUEUE_CONNECTION=database` plus a
running `queue:work` — note that in the README, since notifications currently need no worker.

**Verify:** publish a notice to a society with 500 seeded residents; request returns promptly and jobs land on the queue.

---

## T-10 · Make the notification bell lazy — ⬜ NOT STARTED
**Clears:** F-26 · **Priority:** P3 · **Est:** ~20 min

The View composer runs two queries on every page render, including pages with no bell.

**What:** bind them as lazy closures so they only execute if the view actually reads them,
and cache the unread count briefly per user.

**Verify:** query log on `/dashboard` shows no notification queries when the bell is not rendered.

---

## T-11 · Delete dead files — ⏸️ BLOCKED
**Clears:** F-34, F-35 · **Priority:** P4 · **Est:** ~5 min

**Blocked:** file deletion is refused under the current permission mode, and there is no git
history to recover from. Unblocks once T-02 lands.

**What:**

```
del app\Enums\NotificationChannel.php app\Http\Requests\FileUploadRequest.php app\Models\Notification.php resources\views\show.blade.php
```

Then drop the `FileUploadRequest` reference from the `config/files.php` header comment, and
remove the no-op `File::scopeOrphans()`.

**Verify:** `php artisan test` passes; `php artisan route:list` runs clean.

---

## T-12 · Decide resident visitor/vehicle permissions — ⏸️ BLOCKED
**Clears:** F-36 · **Priority:** P4

**Needs from you:** should residents be able to pre-register a visitor, and register their
own vehicle?

Today they can approve a visitor but not create one, and view vehicles but not add one. The
UI gates both consistently, so nothing is broken — but the asymmetry looks unintended.

- **Yes** → add `visitors.create` / `vehicles.create` to the resident block in `RolePermissionSeeder.php:43`, then reseed permissions.
- **No** → note the rationale in the README so it is not "fixed" by mistake later.

---

# Completed

## T-13 · Unblock the environment — ✅ COMPLETED
Bare `php` was XAMPP's 8.0.30 (Laravel 12 needs ≥8.2). PHP 8.3 was blocked by **Smart App
Control** — unsigned *and* carrying Mark of the Web from a downloaded zip. Cleared MOTW on
67 files (SAC left enabled), enabled `pdo_sqlite`/`sqlite3` for the test suite, restarted
MariaDB, added `.vscode/settings.json` + `tasks.json`.

## T-14 · Restore the test suite — ✅ COMPLETED
F-01, F-20, F-21. **7 passing → 114 passing.**

## T-15 · Fix crashes and silent corruption — ✅ COMPLETED
F-04 ticket collisions (+5 regression tests) · F-05 staff dashboard 500 · F-06 four
resident 500s · F-07 enum comparisons · F-08/F-09 visitor filter · F-10 amenity timestamps.

## T-16 · Fix cache correctness — ✅ COMPLETED
F-11 wrong table · F-12 wrong column · F-13 wrong statistic · F-14 whole-cache flush ·
F-15 noticeboard invalidation (verified live).

## T-17 · Speed up RBAC — ✅ COMPLETED
F-24. ~34 role/permission queries per request → **3**, memoized per request.

## T-18 · Unify pagination — ✅ COMPLETED
F-27 page sizes behind `config/pagination.php` · F-28 one shared `<x-pagination>` ·
F-29 record count always visible · F-30 wraps on mobile.

## T-19 · Correct config and docs — ✅ COMPLETED
F-16 `.env.example` · F-31 debug logging · F-32 README · F-33 `npm install`.

---

# Suggested order

1. **T-02** — git first, so everything after is revertible
2. **T-01** — active data-loss risk
3. **T-06** — catches the bug class the suite is blind to
4. **T-04, T-12** — your two decisions; both unblock small fixes
5. **T-05, T-03** — correctness hardening
6. **T-07 → T-08** — coverage, then deduplicate
7. **T-09, T-10** — performance
8. **T-11** — cleanup, once T-02 makes it safe
