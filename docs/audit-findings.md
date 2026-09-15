# SSMS Codebase Audit — Findings Register

**Audit date:** 15 September 2026
**Scope:** Full codebase (17 controllers, 15 services, 22 models, 32 migrations, ~7.7k lines Blade)
**Method:** Static review, live MySQL schema/data cross-check, runtime log analysis,
PHPUnit suite, per-role dashboard rendering, and a 58-route × 4-role HTTP sweep.

Companion task board: [audit-todo.md](audit-todo.md)

---

## How to read this

Every finding has a stable ID (`F-01` …). The TODO references these IDs, so a finding
and the work item that clears it stay linked.

| Severity | Meaning |
|---|---|
| **P0** | Data loss, or blocks all work |
| **P1** | Wrong behaviour users hit — crash or silent corruption |
| **P2** | Missing safety net (tests, guards) |
| **P3** | Performance |
| **P4** | Cleanup, docs, consistency |

**Status:** `DONE` · `IN PROGRESS` · `NOT STARTED`

---

## Summary

| Severity | Done | In progress | Not started | Total |
|---|---|---|---|---|
| P0 | 1 | 0 | 2 | 3 |
| P1 | 13 | 0 | 3 | 16 |
| P2 | 2 | 0 | 2 | 4 |
| P3 | 1 | 0 | 2 | 3 |
| P4 | 7 | 0 | 3 | 10 |
| **Total** | **24** | **0** | **12** | **36** |

---

## P0 — Data loss or total blockers

### F-01 · Test suite could not run at all — `DONE`
`database/migrations/2026_05_13_160000_add_society_id_to_amenity_bookings_table.php`

Backfill used `DB::table()->join()->update()`, which compiles to MySQL-only
`UPDATE … JOIN`. SQLite (the test database, per `phpunit.xml`) cannot resolve a joined
table's column in the SET clause, so **every test died during migration**.

- **Evidence:** `SQLSTATE[HY000]: General error: 1 no such column: amenities.society_id` — 100 failed, 7 passed.
- **Fix:** Rewrote as a correlated subquery valid on both drivers.
- **Note:** The README claimed "`php artisan test` passing". It was not.

### F-02 · `files:cleanup-orphans` destroys recoverable attachments — `NOT STARTED`
`app/Console/Commands/CleanupOrphanFiles.php:37,43`

Builds its valid-ID set with `Complaint::pluck('id')` / `Notice::pluck('id')`, which
respect the SoftDeletes global scope. Attachments belonging to **soft-deleted** records
are therefore classed as orphans and erased from disk, while the record itself remains
restorable.

Second hazard: `FileService::cleanupOrphans()` filters with
`whereNotIn('entity_id', $ids)`, and Laravel compiles an empty array to `1 = 1`. If
either table were ever empty, the command deletes **every file for that module**.

- **Impact:** Permanent, unrecoverable loss of user-uploaded evidence.
- **Trigger:** Anyone running the command; it reads as routine maintenance.

### F-03 · Project is not under version control — `NOT STARTED`

No git repository. No way to revert an edit, review a change, or return to a known-good
state. Every other fix in this register is riskier than it needs to be as a result.

---

## P1 — Wrong behaviour users hit

### F-04 · Duplicate complaint ticket numbers — `DONE`
`app/Models/Complaint.php:127`

Three defects in one method:

1. Sequence derived from `created_at`, but the unique index is on `ticket_number`. Seeded/backdated rows restarted the count at `0001` and collided.
2. Soft-deleted complaints excluded from the lookup, though they still occupy the unique index.
3. `substr($ticket, -4)` capped the daily sequence at 9999 and then read the wrong digits.

Plus a read-then-insert race with no lock.

- **Evidence:** Already firing in production use — `storage/logs/laravel.log`: `Duplicate entry 'CMP-20260913-0001' for key 'complaints_ticket_number_unique'`.
- **Fix:** Derive from `ticket_number` with `withTrashed()`, offset past the prefix, plus a bounded retry in `ComplaintService::create()`.
- **Cover:** `tests/Feature/Complaints/ComplaintTicketNumberTest.php` (5 tests).

### F-05 · Staff dashboard returned 500 for every staff user — `DONE`
`resources/views/dashboard/staff.blade.php:102,111`

`ucfirst()` and `str_replace()` were passed a `ComplaintPriority` / `ComplaintStatus`
enum. PHP 8.1+ raises a TypeError.

- **Evidence:** `ucfirst(): Argument #1 ($string) must be of type string, App\Enums\ComplaintPriority given`
- **Found by:** Rendering `/dashboard` per role — not covered by the 61 role-matrix tests.

### F-06 · Four resident-only 500s from `wherePivot()` misuse — `DONE`
`VehicleController:33,186` · `FlatController:47` · `AmenityController:197`

`wherePivot()` was called inside `whereHas()` closures. Those closures receive a plain
Builder, not the relation, so the call emits an invalid `pivot.is_active` column.

- **Evidence:** `SQLSTATE[42S22]: Unknown column 'pivot' in 'where clause'`
- **Broke:** vehicles list, vehicle detail, amenity booking form, flats list — all for residents.
- **Fix:** `whereHas` already joins the pivot, so qualify it: `where('flat_residents.is_active', true)`.
- **Note:** The other seven `wherePivot()` calls are on genuine relations and were correct.

### F-07 · Enum compared to string — badge colours never matched — `DONE`
`dashboard/staff` · `dashboard/resident` · `flats/show` · `users/show`

`$complaint->status === 'open'` against an enum is always false, so every badge fell
through to the default branch. Silent: no error, just permanently wrong colours.

### F-08 · Visitor history unreachable through the UI — `DONE`
`VisitorController:62` · `visitors/index.blade.php`

The log defaults to today's entries (correct for a gate log), and supports `?show_all=1`
to see history — but the view never exposed it, and the date input was pre-filled with
today so it could not be cleared. **8 seeded visitors were invisible with no way to reach them.**

- **Fix:** Added an "All dates" checkbox; stopped pre-filling the date input.

### F-09 · `has()` vs `filled()` made the visitor filter inconsistent — `DONE`
`VisitorController:62`

An empty date input still submits `date=`, so `$request->has('date')` was true and
silently dropped the today-default. The filter behaved differently depending on whether
the form had been touched.

### F-10 · Amenities created through the UI had NULL timestamps — `DONE`
`AmenityController:84`

Used raw `DB::table('amenities')->insertGetId($validated)`, bypassing Eloquent — no
timestamps, no casts, no model events.

- **Verified:** Fix confirmed against live DB inside a rolled-back transaction.

### F-11 · `CacheService` queried a non-existent table — `DONE`
`CacheService::getDashboardStats()`

Joined `user_roles`; the actual pivot is **`user_role`** (singular).

### F-12 · `CacheService` queried a non-existent column — `DONE`
`CacheService::getComplaintStats()`

Filtered `complaints.society_id`. That column does not exist — complaints reach society
via `flat → tower`. Now joined correctly; also added the missing `disputed` count.

### F-13 · `getDashboardStats()` returned a wrong statistic — `DONE`

`total_flats` and `occupied_flats` ran the identical query, on a builder already
filtered to occupied. `total_flats` could never be correct.

### F-14 · `flushPattern()` wiped the entire cache — `DONE`

Took a `$pattern` argument, ignored it, and called `Cache::flush()`. Replaced with an
explicit `flushKeys(array)`.

### F-15 · Noticeboards never invalidated — `DONE`

Cached per user for 60s with no invalidation. `flushDashboardCache()` existed but was
never called and named keys nothing used.

- **Fix:** Version-key invalidation hooked on the `Notice` model, covering create/update/publish/archive/pin/delete.
- **Verified:** New notice now appears immediately.

### F-16 · `.env.example` shipped a broken configuration — `DONE`

`DB_CONNECTION=sqlite` with every DB key commented out, and `FILESYSTEM_DISK=local` —
which the project's own `.env` warns "breaks profile photos, complaint attachments and
notice attachments". A fresh clone was guaranteed broken.

### F-17 · File "soft" delete is unrecoverable — `NOT STARTED`
`app/Services/FileService.php:198-209`

`delete()` removes the file from disk **and** soft-deletes the row. The row survives for
audit but points at bytes that no longer exist — `existsOnDisk()` returns false and
`getUrlAttribute()` returns null forever. A soft delete that cannot be undone is
misleading; pick one semantic.

### F-18 · Disputes cannot reopen closed complaints — `NOT STARTED`
`app/Services/ComplaintAccessService.php:68`

`canDispute()` requires status `resolved`. The README documents "resident can reopen a
resolved/**closed** complaint by disputing it". Either the feature is incomplete or the
docs overstate it — a product decision, not a mechanical fix.

### F-19 · `Gate::before` can bypass policies — `NOT STARTED`
`app/Providers/AuthServiceProvider.php`

Returns `true` for any ability whose **name matches a permission slug**, short-circuiting
the policy entirely — including its tenant checks. Not currently exploitable: policy
abilities are named `view` / `update` / `publish`, which match no slug. It becomes a real
hole the moment someone renames an ability to `notices.update`.

---

## P2 — Missing safety nets

### F-20 · `RegistrationTest` asserted removed behaviour — `DONE`

Self-registration is disabled by design (`RegisteredUserController` aborts 403, no route
registered), but the stock Breeze test still asserted registration worked. Rewritten to
lock the intended behaviour in.

### F-21 · Tests depended on the developer's local `.env` — `DONE`

`VisitorRouteRegressionTest` asserts absolute URLs against `http://localhost`, but
`APP_URL` leaked in as `http://localhost:8000`. Pinned `APP_URL` in `phpunit.xml`.

### F-22 · Four modules have zero test coverage — `NOT STARTED`

**Amenities, Vehicles, Files, ActivityLog.** Not a coincidence — F-06 and F-10 both lived
in exactly these files and were invisible to the suite.

### F-23 · Test helpers duplicated roughly nine times — `NOT STARTED`

`createSocietyContext()` / `createUserWithRole()` / `assignRole()` are copy-pasted
privately into each role-matrix file while `tests/TestCase.php` sits empty — despite the
README claiming the base class provides them.

---

## P3 — Performance

### F-24 · RBAC lookups hit the DB on every call — `DONE`
`app/Models/User.php`

`hasRole()` / `hasPermission()` queried every time, with `hasPermission()` costing two
queries (it calls `isSuperAdmin()` first). The sidebar alone makes 17 checks, and
`Gate::before` fires on every `@can` and `authorize()`.

- **Measured:** ~34 queries → **3 per request**, resolved once and memoized.
- Invalidated via `flushAccessCache()` wherever roles change.

### F-25 · Society-wide notifications are serial and synchronous — `NOT STARTED`
`NotificationService::sendToCollection()`

Loads every recipient into memory and notifies one at a time. With
`QUEUE_CONNECTION=sync`, publishing a notice to 500 residents blocks the request for 500
inserts. Needs chunking and a queued notification.

### F-26 · Notification bell costs 2 queries on every page — `NOT STARTED`
`AppServiceProvider` View composer

Runs an unread count and a recent-notifications query on every render, including pages
that never display the bell.

---

## P4 — Cleanup, consistency, docs

### F-27 · Page sizes were inconsistent — `DONE`

Three values with no rationale: `paginate(5)`, `(15)`, `(20)`. Maintenance paged every 5
rows while Vehicles showed no pager until row 21. Unified behind
`config/pagination.php` (`PAGINATION_PER_PAGE`, default 15).

### F-28 · Two different pagination styles — `DONE`

Ten views used the custom component; `amenities/index`, `societies/index` and
`activity-logs/index` used `{{ $x->links() }}` (Tailwind default). Extracted a shared
`<x-pagination>` component used by all.

### F-29 · Record count vanished on single-page results — `DONE`

`<x-data-table>` hid the whole pagination block unless `lastPage() > 1`, so listings that
fit on one page showed no "Showing X of Y" at all — which read as "pagination is
missing". Now always shown when there is data.

### F-30 · Pager did not wrap on narrow screens — `DONE`

`.pagination-wrapper` used `justify-between` with no `flex-wrap`.

### F-31 · Leftover debug logging — `DONE`

`logger('CREATED amenity: …')` in `AmenityController`.

### F-32 · README contained false statements — `DONE`

`php artisan seed` (not a command — it is `db:seed`) · claimed `tests/TestCase.php`
provides helpers it does not · claimed the suite passes when it could not run ·
Vehicles and Amenities modules entirely undocumented.

### F-33 · Frontend dependencies were never installed — `DONE`

No `node_modules`; a prebuilt `public/build` was committed in its place, so no CSS or JS
change could actually be compiled.

### F-34 · Four dead files — `NOT STARTED`

`app/Enums/NotificationChannel.php` (0 bytes) · `app/Http/Requests/FileUploadRequest.php`
(0 bytes) · `app/Models/Notification.php` (0 bytes) · `resources/views/show.blade.php`
(stale duplicate of `complaints/show.blade.php`). All verified unreferenced. Empty class
files fatal if anything ever autoloads them.

### F-35 · `File::scopeOrphans()` is a no-op stub — `NOT STARTED`

Returns its query unchanged with a comment saying the implementation is pending. Unused —
the real logic lives in `FileService::cleanupOrphans()` — but it is a trap for anyone who
finds it and assumes it filters.

### F-36 · Resident permissions may be incomplete — `NOT STARTED`
`database/seeders/RolePermissionSeeder.php:43`

Residents hold `visitors.update` (approve/reject) but not `visitors.create`, and
`vehicles.view` but not `vehicles.create`. So a resident can approve a visitor but not
pre-register one, and can see vehicles but not register their own. Coherent if the gate
guard registers visitors and admins register vehicles — but it should be a decision, not
an accident. **Needs a product answer.**

---

## Verification baseline

Established 15 September 2026, after the `DONE` items above:

- **114 tests passing** (276 assertions) — was 7 passing / 100 failing
- **232 route/role combinations** clean — 58 GET routes × 4 roles, zero server errors
- All four role dashboards render 200
- Assets build cleanly via `npm run build`

### Reproducing the checks

The two harnesses that found F-05 and F-06 are not part of the test suite. Both drive the
HTTP kernel through `artisan tinker` with `Auth::login()`:

1. Render `/dashboard` for one user per role, assert 200.
2. Loop every GET route × every role, flag any 500.

Worth rebuilding as real feature tests — see [T-06](audit-todo.md) in the task board.
