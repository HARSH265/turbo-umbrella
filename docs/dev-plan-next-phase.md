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
- Storage disk normalization
- DB-level business protections
- Notification UX hardening
- Blade/UI rule alignment
- Auto Maintenance Generation (scheduled)
- UI Enhancement (consistent theme, pagination, styling)

### ✅ All Phases Complete

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

## Phase 4: Storage Disk Normalization ✅ COMPLETED

### Changes Made:
- Added dedicated 'ssms' disk in `config/filesystems.php`
- Updated `config/files.php` to use 'ssms' as default
- Updated `app/Models/File.php` to use 'ssms' disk
- Updated `app/Services/FileService.php` to use 'ssms' disk
- Created `storage/app/ssms` directory

### Disk Configuration:
```php
'ssms' => [
    'driver' => 'local',
    'root' => storage_path('app/ssms'),
    'url' => env('APP_URL').'/storage/ssms',
    'visibility' => 'public',
]
```

### Audit Results:
- All `Storage::` calls now use explicit 'ssms' disk
- No direct Storage calls in controllers (all via FileService)
- FileService, File model, and config all aligned

---

## Next Steps (Future Phases)

### Phase 5: Notification UX Hardening ✅ COMPLETED

### Changes Made:
- Updated `NotificationController@open()` to show info message when target URL is invalid
- Added message: "The notification link is no longer available."

### Already Implemented:
- Error flash messages for missing notifications
- Proper 404 handling
- Safe URL validation before redirect

---

## Phase 6: Blade/UI Rule Alignment ✅ COMPLETED

### Audit Results:
- **Sidebar navigation**: Uses `@if(Auth::user()->hasPermission(...))` - properly role-gated
- **Visitor show page**: Has `$canApproveAction`, `$canRejectAction` variables for action buttons
- **Maintenance pages**: Proper role-based access controls in place
- **Complaints**: Backend workflow guards in place, UI reflects state properly

### No Issues Found:
- Status transition buttons match backend rules
- Society-scoped dropdowns properly filtered
- No forbidden action buttons visible to unauthorized roles

---

## Roadmap Complete ✅
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
- ✅ UI matches backend authorization

---

## Phase 7: Auto Maintenance Generation ✅ COMPLETED

### Created Files:
- `app/Console/Commands/GenerateMaintenanceCommand.php` - Artisan command for maintenance generation
- `app/Console/Commands/ApplyLateFeesCommand.php` - Artisan command for late fee application

### Scheduler (in `bootstrap/app.php`):
```php
// Auto-generate maintenance on 1st of every month at midnight
$schedule->command('maintenance:generate')
    ->monthlyOn(1, '00:00')
    ->withoutOverlapping()
    ->onOneServer();

// Apply late fees daily at 2 AM
$schedule->command('maintenance:apply-late-fees')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();
```

### Command Usage:
```bash
# Generate for all societies (current month)
php artisan maintenance:generate

# Generate for specific society
php artisan maintenance:generate --society=1

# Generate for specific month
php artisan maintenance:generate --month=2026-05

# Dry run (preview without creating)
php artisan maintenance:generate --dry-run
```

### Features:
- Skips societies without active maintenance policy
- Skips flats that already have maintenance for the month
- Supports billing cycle (monthly, quarterly, half-yearly, yearly)
- Logs all generation activity
- Sends notifications to residents

---

## Phase 8: UI Enhancement ✅ COMPLETED

### Changes Made:

#### 1. Theme CSS (`resources/css/app.css`)
Created consistent design system with CSS variables:
- **Primary Color**: Emerald (#059669)
- **Semantic Colors**: Success (green), Warning (amber), Danger (red), Info (blue)
- **Consistent spacing and typography**

#### 2. Enhanced Components
- **Data Table**: Improved pagination with numbered pages, better styling
- **Cards**: Consistent border, shadow, and padding
- **Badges**: Predefined badge classes (success, warning, danger, info, gray)
- **Buttons**: Consistent btn classes
- **Form Inputs**: form-input and form-select classes

#### 3. Standardized Index Pages
All index pages now follow consistent pattern:
- Page header: `page-header` with `page-header-title` and `page-header-subtitle`
- Filter card: Uses `<x-card>` component
- Table: Uses `<x-data-table>` with consistent badges
- **Removed Add buttons from data tables** (user preference)

#### 4. Updated Pages
- Maintenance, Users, Visitors, Flats, Complaints, Notices, Towers, Societies

---

## What's Next
- Phase 9: [Add your next feature here]