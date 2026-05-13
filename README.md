# SSMS

Society and Staff Management System built on Laravel 12 for managing residential societies, users, flats, complaints, maintenance billing, notices, visitors, files, activity logs, and in-app notifications.

## Overview

SSMS is a role-based web application for multi-society operations. It supports:

- Super admin level management across societies
- Society admin level operations within one society
- Resident self-service for complaints, maintenance, notices, and visitors
- Staff workflows for complaint handling and operational follow-up

The application uses centralized services for:

- file handling
- activity logging
- user notifications

This keeps business workflows consistent across modules.

## Tech Stack

- PHP `^8.2`
- Laravel `^12`
- Laravel Breeze for authentication
- Blade views
- MySQL in app usage
- SQLite in test environment
- Tailwind-based UI

## Core Modules

### 1. Authentication and Profile

Handled through Laravel Breeze plus project-specific extensions.

Features:

- login / logout
- registration
- email verification
- password reset
- profile update
- soft-delete account flow
- active-user enforcement through middleware

Important customizations:

- `phone` is required for users
- users support soft deletes
- inactive users are blocked through `user.active` middleware

### 2. Users and Roles

The system uses role and permission based access control.

Main roles currently used in code:

- `super-admin`
- `society-admin`
- `resident`
- `staff`

User module supports:

- create / edit / delete users
- role assignment
- profile photo upload via centralized file service
- active / inactive toggling
- relation with society and flats

Primary files:

- `app/Models/User.php`
- `app/Models/Role.php`
- `app/Models/Permission.php`
- `app/Http/Controllers/UserController.php`

### 3. Societies, Towers, Flats

This is the physical structure layer of the project.

Hierarchy:

- Society
- Tower
- Flat
- Flat Residents

Capabilities:

- manage societies
- manage towers inside a society
- manage flats inside towers
- assign residents to flats
- track occupancy status
- connect flats to complaints, visitors, and maintenance

Primary files:

- `app/Http/Controllers/SocietyController.php`
- `app/Http/Controllers/TowerController.php`
- `app/Http/Controllers/FlatController.php`
- `app/Models/Society.php`
- `app/Models/Tower.php`
- `app/Models/Flat.php`
- `app/Models/FlatResident.php`

### 4. Complaint Management

Complaint workflow is one of the main operational modules.

Supported lifecycle:

- resident raises complaint
- complaint can include file attachments
- admin assigns complaint to staff
- staff/admin add comments
- complaint moves through statuses
- complaint can be resolved
- resident can reopen a resolved/closed complaint by disputing it

Current status values:

- `open`
- `in_progress`
- `resolved`
- `disputed`
- `closed`

Implemented behaviors:

- duplicate complaint check on create
- assignment updates status automatically
- comment thread with internal/public visibility
- dispute modal for resident-side reopening
- notifications on create, assign, status change, resolve, and dispute
- centralized file attachments for evidence
- activity logging for complaint lifecycle changes

Primary files:

- `app/Http/Controllers/ComplaintController.php`
- `app/Services/ComplaintService.php`
- `app/Models/Complaint.php`
- `app/Models/ComplaintComment.php`
- `resources/views/complaints/*`

### 5. Maintenance Management

Maintenance module supports society billing operations.

Features:

- maintenance policy creation and activation
- monthly / cycle-based generation
- resident and admin bill listing
- payment recording
- partial payment validation by policy
- late fee calculation
- overdue status handling
- notifications for billing and payment events

Maintenance services:

- `MaintenanceService`
  - summaries and reporting helpers
- `MaintenanceGenerationService`
  - generate bills for flats
- `MaintenancePaymentService`
  - record payments and update status
- `MaintenanceLateFeeService`
  - apply overdue and late-fee logic
- `MaintenancePolicyService`
  - activate/deactivate policies

Implemented notification behavior:

- bill generated -> resident notified
- payment recorded -> resident notified
- admin pays on behalf of resident -> resident notified with performer context
- resident pays -> society admin notified
- payment status change -> resident notified
- overdue transition -> resident notified
- late fee applied -> resident notified

Primary files:

- `app/Http/Controllers/MaintenanceController.php`
- `app/Http/Controllers/MaintenancePolicyController.php`
- `app/Services/Maintenance*.php`
- `app/Models/Maintenance*.php`
- `resources/views/maintenance/*`

### 6. Notices

Notice module handles announcements and targeted communication.

Features:

- create and update notices
- target all residents of a society or only specific recipients
- file attachments through centralized file service
- recipient read-tracking for specific notices
- notifications for published notices

Implemented safeguards:

- society admins are scoped to their own society
- targeted recipients must belong to the same society

Primary files:

- `app/Http/Controllers/NoticeController.php`
- `app/Models/Notice.php`
- `resources/views/notices/*`

### 7. Visitors

Visitor module tracks guest entry/exit and approvals.

Features:

- visitor registration
- approval / rejection
- exit recording
- resident visibility for their own flat visitors
- admin/staff operational visibility

Primary files:

- `app/Http/Controllers/VisitorController.php`
- `app/Models/Visitor.php`
- `resources/views/visitors/*`

### 8. Centralized Files

The project uses a dedicated centralized file handling service.

Responsibilities:

- upload single and multiple files
- module-specific file validation
- storage path generation
- download handling
- delete / deactivate / cleanup support

Modules currently using it:

- complaints
- notices
- users
- generic file download/delete routes

Primary files:

- `app/Services/FileService.php`
- `app/Models/File.php`
- `app/Exceptions/FileException.php`
- `config/files.php`

### 9. Centralized Activity Logs

Activity log service is used as the audit trail layer.

Logged events include:

- create
- update
- delete
- login / logout
- maintenance system events

Used across:

- complaints
- notices
- users
- societies
- towers
- flats
- maintenance flows

Primary files:

- `app/Services/ActivityLogService.php`
- `app/Models/ActivityLog.php`
- `app/Http/Controllers/ActivityLogController.php`

### 10. Centralized Notifications

The project uses Laravel database notifications through a centralized service.

Notification features:

- send to single user
- send to role
- send to society
- mark read
- mark all read
- clear read notifications
- unread badge in layout
- personal notification listing page
- open notification and auto-mark as read

Current integrated flows:

- complaint created
- complaint assigned
- complaint updated
- complaint resolved
- complaint disputed
- maintenance generated
- maintenance payment recorded
- maintenance status updated
- maintenance overdue
- maintenance late fee applied
- notice published

Primary files:

- `app/Services/NotificationService.php`
- `app/Notifications/GeneralNotification.php`
- `app/Enums/NotificationType.php`
- `app/Http/Controllers/NotificationController.php`
- `resources/views/notifications/index.blade.php`

## UI and UX State

Current implemented UX improvements include:

- notification bell in top navbar
- unread count badge
- notification dropdown with recent personal notifications
- notifications entry in sidebar
- full notifications page
- complaint comments section made independently scrollable
- pagination shown on complaint listing
- filter-preserving pagination across module list screens

## Route Surface

Main authenticated route groups cover:

- dashboard
- profile
- complaints
- maintenance
- maintenance policies
- notices
- visitors
- societies
- towers
- flats
- users
- files
- activity logs
- notifications

See:

- `routes/web.php`
- `routes/auth.php`

## Important Business Rules Implemented

### Society Isolation

Implemented in custom business modules so society admins cannot access data across societies.

Applied to:

- complaints
- notices
- notice recipients
- maintenance view logic

### Resident Complaint Dispute

Residents can reopen work if a complaint is marked resolved/closed but the issue still exists.

Flow:

- resident clicks `Reopen & Dispute`
- enters reason
- optionally uploads fresh proof
- complaint status becomes `disputed`
- thread comment is created
- staff / society admin / resident notifications are triggered

### Payment on Behalf of Resident

If an admin records a maintenance payment for a resident:

- resident gets notification that payment was done on their behalf
- society admin still sees collection notification

## Centralized Config and Policy Files

Key config files:

- `config/files.php`
- `config/filesystems.php`
- `config/logging.php`
- `config/database.php`

Project-specific notes:

- `config/database.php` uses `env('DB_CONNECTION', 'mysql')` as default to support test environment correctly
- file rules are module-based through `config/files.php`

## Setup

### Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan seed  # Seeds test users and roles
npm run build
```

### Default Credentials (After Seeding)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@ssms.local | Admin@123 |
| Society Admin | societyadmin@ssms.local | Admin@123 |
| Resident | john@ssms.local | Resident@123 |
| Resident | jane@ssms.local | Resident@123 |
| Staff | ramesh@ssms.local | Staff@123 |

Test society: **Green Valley Apartments** (TEST001)
- Tower A with 5 flats (A-101 to A-105)

### Development

```bash
composer run dev
```

This starts:

- Laravel dev server
- queue listener
- logs
- Vite dev process

### Run Tests

```bash
composer test
```

## Database and Migrations

Project contains custom migrations for:

- users
- roles and permissions
- societies / towers / flats / flat residents
- complaints and comments
- notices
- visitors
- files
- activity logs
- system config
- notifications
- maintenance core tables
- maintenance support tables
- maintenance policy tables

Important recent custom migrations include:

- maintenance alignment updates
- complaint disputed status support

## Testing Status

The test suite was aligned with project customizations such as:

- required `phone` field for users
- soft deleted users
- sqlite-safe test database config
- sqlite-safe maintenance migration behavior

Recent state verified during implementation:

- `php artisan test` passing

### Role-Matrix Feature Tests

The project includes comprehensive role-based feature tests covering all major modules:

| Module | Test File | Tests |
|--------|-----------|-------|
| Visitors | `tests/Feature/Visitors/VisitorRoleMatrixTest.php` | 16 |
| Complaints | `tests/Feature/Complaints/ComplaintRoleMatrixTest.php` | 8 |
| Maintenance | `tests/Feature/Maintenance/MaintenanceRoleMatrixTest.php` | 10 |
| Notices | `tests/Feature/Notices/NoticeRoleMatrixTest.php` | 10 |
| Users | `tests/Feature/Users/UserRoleMatrixTest.php` | 11 |
| Notifications | `tests/Feature/Notifications/NotificationRoleMatrixTest.php` | 5 |

**Total: 61 role-matrix tests** covering:
- Role-based access control (super-admin, society-admin, staff, resident)
- Cross-society access blocking
- Tenant isolation verification
- Workflow state transitions
- Route regression checks

Run tests:
```bash
php artisan test --filter=RoleMatrixTest
```

### Test Infrastructure

Key test helpers:
- `tests/TestCase.php` - Base test class with tenant context helpers
- `createSocietyContext()` - Create test societies with required fields
- `createUserWithRole()` - Create users with specific roles
- `assignRole()` - Assign roles to users

## Recent Implementation Summary

The following major work has been completed in this codebase:

- replaced default Laravel README placeholder with project-specific documentation
- fixed cross-society access issues in complaints and notices
- restored and completed complaint dispute workflow
- wired centralized notifications into complaint, maintenance, and notice flows
- added user-facing notification UI with unread bell and personal list
- added notification auto-open and mark-as-read behavior
- fixed file-service integration for user profile photo replacement
- removed stale duplicate legacy service classes under `app/Models`
- fixed pagination consistency across module list pages
- fixed complaint comments scrolling behavior
- fixed resident maintenance query ambiguity
- fixed test environment issues around DB connection, migrations, and user factory
- restored admin/resident maintenance payment notification logic

## Known Operational Notes

- if complaint disputed status enum is newly added on an existing MySQL database, migrations must be run
- queue/listener setup should remain active if notification delivery is queued
- notification feature depends on `notifications` table and `Notifiable` user model integration

## Directory Guide

Useful folders:

- `app/Http/Controllers` -> request entry points
- `app/Services` -> business logic
- `app/Models` -> Eloquent models
- `app/Notifications` -> database notification payloads
- `app/Enums` -> statuses, types, channels
- `app/Http/Middleware` -> access and request guards
- `resources/views` -> Blade UI
- `database/migrations` -> schema
- `database/seeders` -> base system data

## Development Roadmap

### Completed Phases

1. **Role-Matrix Feature Tests** ✅
   - 61 tests covering all major modules and roles
   - Cross-society access blocking verified
   - Workflow state transitions tested

2. **Tenant Scoping Centralization** ✅
   - `app/Models/Traits/BelongsToSociety.php` - Model trait for society-scoped queries
   - `app/Services/TenantScopeService.php` - Centralized tenant validation service

3. **DB-Level Business Protections** ✅
   - Unique constraints for towers (society + name)
   - Unique constraints for flats (tower + flat_number)
   - Unique constraints for notices (society + title)
   - Unique constraints for maintenances (flat + month)

4. **Storage Disk Normalization** ✅
   - Dedicated 'ssms' disk in config/filesystems.php
   - All file operations use explicit 'ssms' disk
   - FileService and File model aligned

5. **Notification UX Hardening** ✅
   - Info message for invalid notification target URLs
   - Proper error handling for missing notifications
   - Safe URL validation before redirect

6. **Blade/UI Rule Alignment** ✅
   - Sidebar navigation uses role-based permissions
   - Action buttons use role-gated variables
   - Status transitions match backend rules

### Documentation Files

- `docs/dev-plan-next-phase.md` - Current development status and next steps
- `docs/dev-plan-tasks.md` - Detailed task breakdown
- `docs/ai-roadmap-compact.md` - AI execution roadmap
- `docs/enhancement-roadmap.md` - Full enhancement suggestions

---

## Suggested Next Documentation Enhancements

Future README improvements can include:

- ER diagram
- role-permission matrix
- screen-by-screen module screenshots
- API/route matrix
- deployment checklist
- backup and queue configuration notes

---

If you continue development on this project, update this README whenever:

- a new business workflow is added
- a module's status lifecycle changes
- centralized service behavior changes
- roles/permissions are added or renamed
- notification flows are expanded
