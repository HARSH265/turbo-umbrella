# SSMS Development Tasks

## Working Rule ✅
**Before starting any new feature:**
1. Update `dev-plan-next-phase.md` with feature details
2. Create todo list for tracking
3. Then proceed with implementation

---

## Completed Tasks ✅

| Task | Module | Status |
|------|--------|--------|
| T001 | Role-Matrix Tests (all modules) | ✅ Done |
| T002 | Tenant Scope Service & Trait | ✅ Done |
| T003 | DB-level Business Protections | ✅ Done |
| T004 | Storage Disk Normalization | ✅ Done |
| T005 | Notification UX Hardening | ✅ Done |
| T006 | Blade/UI Rule Alignment | ✅ Done |
| T007 | Auto Maintenance Generation | ✅ Done |
| T008 | UI Enhancement (theme, pagination) | ✅ Done |
| T009 | Vehicle Management | ✅ Done |
| T010 | Amenity Booking | ✅ Done |

---

## In Progress

| Task | Module | Description |
|------|--------|-------------|
| T011 | Reports & Analytics | Dashboard charts, statistics |
| T012 | PDF Bills | Professional maintenance bills |

---

## Pending

| Task | Module | Description |
|------|--------|-------------|
| T013 | Export Data | Excel/PDF export |
| T014 | Global Search | Search across modules |
| T015 | Payment Gateway | Online payment integration |

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

## Files Created Recently

### Vehicle Management
- `app/Models/Vehicle.php`
- `app/Http/Controllers/VehicleController.php`
- `database/migrations/2026_05_13_120000_create_vehicles_table.php`
- `resources/views/vehicles/`

### Amenity Booking
- `app/Models/Amenity.php`
- `app/Models/AmenityBooking.php`
- `app/Http/Controllers/AmenityController.php`
- `database/migrations/2026_05_13_130000_create_amenities_table.php`
- `database/migrations/2026_05_13_130500_create_amenity_bookings_table.php`
- `resources/views/amenities/`