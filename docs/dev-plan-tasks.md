# SSMS Development Tasks

## Task Format: `[Module].[Type].[Priority]`

---

## Completed Tasks ✅

### T001: Role-Matrix Tests - Maintenance Module
- **Type**: Test
- **Priority**: P0 (Highest)
- **Status**: ✅ Completed
- **File**: `tests/Feature/Maintenance/MaintenanceRoleMatrixTest.php`

### T002-T012: All Completed
- Role-matrix tests for all modules (Notices, Users, Files, Notifications)
- Tenant Scope Trait & Service
- DB constraints (tenant unique, maintenance policy)
- Storage audit & normalization
- Notification error handling
- Blade UI audit
- Auto Maintenance Generation

---

## T002: Role-Matrix Tests - Notices Module
- **Type**: Test
- **Priority**: P0
- **Status**: Not Started
- **Create**: `tests/Feature/Notices/NoticeRoleMatrixTest.php`
- **Tests**:
  - super-admin: create, publish, archive any
  - society-admin: manage within society
  - staff: view, create notices
  - resident: view published notices only

---

## T003: Role-Matrix Tests - Users Module
- **Type**: Test
- **Priority**: P0
- **Status**: Not Started
- **Create**: `tests/Feature/Users/UserRoleMatrixTest.php`
- **Tests**:
  - super-admin: manage all users, assign roles
  - society-admin: manage users in their society
  - staff: view only
  - resident: profile management only

---

## T004: Role-Matrix Tests - Files Module
- **Type**: Test
- **Priority**: P1
- **Status**: Not Started
- **Create**: `tests/Feature/Files/FileRoleMatrixTest.php`
- **Tests**:
  - Upload permissions per role
  - Download permissions per role
  - Delete permissions per role

---

## T005: Role-Matrix Tests - Notifications Module
- **Type**: Test
- **Priority**: P1
- **Status**: Not Started
- **Create**: `tests/Feature/Notifications/NotificationRoleMatrixTest.php`
- **Tests**:
  - View permissions
  - Mark as read permissions
  - Delete permissions

---

## T006: Tenant Scope Trait
- **Type**: Core
- **Priority**: P0
- **Status**: Not Started
- **Create**: `app/Models/Traits/BelongsToSociety.php`
- **Methods**:
  - `scopeForSociety($query, $societyId)`
  - `belongsToCurrentSociety()`
  - `validateSocietyAccess($societyId)`

---

## T007: Tenant Scope Service
- **Type**: Core
- **Priority**: P0
- **Status**: Not Started
- **Create**: `app/Services/TenantScopeService.php`
- **Methods**:
  - `validateAccess($model, $societyId)`
  - `filterBySociety($query, $modelClass, $societyId)`
  - `blockCrossSociety($model, $user)`

---

## T008: DB - Maintenance Policy Unique Constraint
- **Type**: Database
- **Priority**: P1
- **Status**: Not Started
- **Create**: `database/migrations/2026_05_13_000001_add_maintenance_policy_unique_constraint.php`
- **Constraint**: One active policy per society

---

## T009: DB - Tenant Unique Constraints
- **Type**: Database
- **Priority**: P1
- **Status**: Not Started
- **Create**: `database/migrations/2026_05_13_000002_add_tenant_unique_constraints.php`
- **Constraints**: Unique (society_id, name) for towers, flats

---

## T010: Storage - Audit & Normalize
- **Type**: Refactor
- **Priority**: P1
- **Status**: Not Started
- **Audit**: All Storage:: calls
- **Action**: Use explicit 'ssms' disk everywhere

---

## T011: Notification - Error Handling
- **Type**: UX
- **Priority**: P2
- **Status**: Not Started
- **Update**: `app/Http/Controllers/NotificationController.php`
- **Add**: Flash messages for failures

---

## T012: Blade - UI Audit
- **Type**: UX
- **Priority**: P2
- **Status**: Not Started
- **Review**: All views for role-based visibility
- **Fix**: Remove forbidden action buttons

---

## Quick Commands

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=VisitorRoleMatrixTest

# Run feature tests only
php artisan test --testsuite=feature
```

---

## Files Created
- `docs/dev-plan-next-phase.md` - Main plan
- `docs/dev-plan-tasks.md` - Task breakdown (this file)