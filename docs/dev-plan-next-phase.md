# SSMS Next Phase Development Plan

## Current Status (as of May 2026)

### ✅ Completed
- Role-matrix feature tests (Visitors, Complaints, Maintenance, Notices, Users, Notifications)
- Workflow/state guards (Complaint, MaintenancePayment)
- Route regression tests (Visitor)
- Tenant-scope guards (MaintenanceTenantGuardTest)
- Form Request standardization (most controllers)
- Tenant Scope Service (`app/Services/TenantScopeService.php`)
- BelongsToSociety trait (`app/Models/Traits/BelongsToSociety.php`)

### In Progress
- Storage disk normalization - needs verification

### Not Started
- DB-level business protections
- Notification UX hardening
- Blade/UI rule alignment

---

## Phase 1: Role-Matrix Tests ✅ COMPLETED

Created comprehensive tests for all modules:

| Module | File | Status |
|--------|------|--------|
| Visitors | `tests/Feature/Visitors/VisitorRoleMatrixTest.php` | ✅ 16 tests |
| Complaints | `tests/Feature/Complaints/ComplaintRoleMatrixTest.php` | ✅ 8 tests |
| Maintenance | `tests/Feature/Maintenance/MaintenanceRoleMatrixTest.php` | ✅ 10 tests |
| Notices | `tests/Feature/Notices/NoticeRoleMatrixTest.php` | ✅ 10 tests |
| Users | `tests/Feature/Users/UserRoleMatrixTest.php` | ✅ 11 tests |
| Notifications | `tests/Feature/Notifications/NotificationRoleMatrixTest.php` | ✅ 5 tests |

**Total: 61 tests passing**

---

## Phase 2: Tenant Scoping Centralization ✅ COMPLETED

### Created Files:
- `app/Models/Traits/BelongsToSociety.php` - Trait for society-scoped models
- `app/Services/TenantScopeService.php` - Centralized tenant validation service

### Methods Available:
- `scopeForSociety()` - Query scope for filtering by society
- `isInSociety()` - Check if model belongs to society
- `validateAccess()` - Validate user access to model
- `blockCrossSociety()` - Prevent cross-society access

---

## Phase 3: DB-Level Business Protections ✅ COMPLETED

### Created Migration:
- `database/migrations/2026_05_13_000001_add_tenant_unique_constraints.php`

### Constraints Added:
| Table | Constraint | Description |
|-------|------------|-------------|
| towers | `towers_society_name_unique` | Unique tower name per society |
| flats | `flats_tower_flat_unique` | Unique flat number per tower |
| notices | `notices_society_title_unique` | Unique notice title per society |
| maintenances | `maintenances_flat_month_unique` | Unique maintenance per flat per month |

### Note:
- Maintenance policy single-active constraint is enforced at application level via `MaintenancePolicyService::activatePolicy()` which deactivates existing active policy before activating new one.

---

## Next Steps (Future Phases)

### Phase 4: Storage Disk Normalization (Medium Priority)
- Audit all `Storage::` calls
- Replace implicit default with explicit 'ssms' disk

### Phase 5: Notification UX Hardening (Medium Priority)
- Add flash messages for notification failures
- Handle missing/invalid notification targets

### Phase 6: Blade/UI Rule Alignment (Low Priority)
- Review role-based action visibility in views
- Ensure status transition buttons match backend rules

---

## Success Criteria

- ✅ All 4 roles tested across all modules
- ✅ Cross-society access blocked everywhere
- ✅ Invalid state transitions impossible (via existing guards)
- ✅ Route conflicts covered by tests
- ✅ File lifecycle consistent
- ⬜ UI matches backend authorization (future work)