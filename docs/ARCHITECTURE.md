# Oneyard Outfitters — Development Log

## Purpose

This document records significant development activities that are important
for understanding, maintaining, debugging, testing, and continuing the
Oneyard Outfitters application.

This is an engineering record, not a general activity diary.

Only activities that meet the project's documentation standard should be
added.

---

# Documentation Standard

An activity should be logged when it materially affects one or more of:

- Architecture
- Database structure
- Business logic
- Authentication or authorization
- Security
- Data integrity
- Application configuration
- Dependencies
- Testing
- Deployment
- Troubleshooting
- Requirements
- System behavior
- Maintainability

Routine development actions should not be logged.

---

# Entry Format

Each significant activity should use the following structure:

## [DATE] — [STAGE / UNIT] — [SHORT TITLE]

**Type:** FEATURE | ARCHITECTURE | DATABASE | SECURITY | BUG | FIX | TEST | CONFIGURATION | DECISION | REQUIREMENT | DEPLOYMENT

**Status:** PLANNED | IN PROGRESS | COMPLETED | BLOCKED | REVERTED

### Context

What was being built or what problem existed?

### Change

What was actually changed?

Include relevant files, tables, routes, models, services, commands,
configuration, or other technical components.

### Reason

Why was this implementation or decision necessary?

### Dependencies

What existing components does this change depend on?

### Risk / Side Effects

What could this change affect?

### Validation

What was checked?

Include:

- Commands executed
- Tests executed
- Database verification
- Manual verification
- Expected result
- Actual result

Never state that something passed unless it was actually verified.

### Troubleshooting Notes

If a problem occurred, document:

- Symptom
- Evidence
- Root cause
- Fix
- Verification

If no issue occurred:

`None.`

### Files / Components

List the important files, tables, routes, models, or other components
affected by the activity.

### Follow-up

What remains to be done, if anything?

---

# Development History

## 2026-09-21 — Stage 2 / Unit 2A — RBAC Database Schema

**Type:** DATABASE

**Status:** COMPLETED

### Context

The application requires a centralized role-based authorization foundation
before business-module authorization can be implemented.

### Change

Added the RBAC database structure:

- `roles`
- `permissions`
- `role_user`
- `permission_role`

The schema establishes:

`User → Role → Permission`

Role assignments and permission assignments are protected against
duplicates through composite unique constraints.

Foreign keys use cascading deletes for relationship cleanup.

### Reason

The application is an internal staff system and requires centralized
authorization rather than authorization rules being scattered throughout
controllers and business modules.

### Dependencies

- Laravel authentication `users` table
- MySQL
- Eloquent authorization models to be added in subsequent units

### Risk / Side Effects

The pivot tables depend on the primary key type of the existing `users`
table.

The implementation currently assumes the standard Laravel integer/bigint
user primary key.

### Validation

Migration implementation was prepared but execution must be verified in the
developer environment.

Expected database tables:

- `roles`
- `permissions`
- `role_user`
- `permission_role`

### Troubleshooting Notes

None.

### Files / Components

- `database/migrations/*_create_roles_table.php`
- `database/migrations/*_create_permissions_table.php`
- `database/migrations/*_create_role_user_table.php`
- `database/migrations/*_create_permission_role_table.php`

### Follow-up

- Implement RBAC Eloquent models and relationships.
- Add roles and permissions through the RBAC seeder.
- Add automated RBAC tests.
- Implement authorization enforcement in a later stage.

---

## 2026-09-21 — Stage 2 / Unit 2B — RBAC Eloquent Relationships

**Type:** ARCHITECTURE

**Status:** COMPLETED

### Context

The RBAC database structure needs an Eloquent representation so the
application can work with roles and permissions through Laravel's ORM.

### Change

Added:

- `Role` model
- `Permission` model
- `User → roles`
- `Role → users`
- `Role → permissions`
- `Permission → roles`

The authorization relationship intentionally follows:

`User → Role → Permission`

No direct `User → Permission` relationship was introduced.

### Reason

Keeping permissions behind roles provides one centralized authorization
configuration and avoids competing direct-permission and role-permission
authorization paths.

### Dependencies

- `roles` table
- `permissions` table
- `role_user`
- `permission_role`

### Risk / Side Effects

Authorization is not enforced by these relationships alone.

Permission middleware and policies remain future work.

### Validation

Model and relationship implementation prepared.

Runtime relationship verification must be performed in the developer
environment.

### Troubleshooting Notes

During implementation, an invalid attempt to create a direct
`Permission → User` relationship was identified and removed because the
current architecture does not contain a direct user-permission pivot.

### Files / Components

- `app/Models/Role.php`
- `app/Models/Permission.php`
- `app/Models/User.php`

### Follow-up

- Seed initial roles and permissions.
- Add automated relationship tests.
- Implement centralized authorization enforcement later.

---

## 2026-09-21 — Stage 2 / Unit 2C — Initial RBAC Configuration

**Type:** CONFIGURATION

**Status:** COMPLETED

### Context

The RBAC schema and Eloquent relationships require an initial permission
catalogue and operational staff roles.

### Change

Added:

- Initial permission catalogue
- Initial system roles
- Role-permission assignments
- Idempotent `RbacSeeder`
- `DatabaseSeeder` integration

Initial roles:

- Super Admin
- Administrator
- Sales
- Procurement
- Production
- Quality Control
- Finance
- Delivery

Permissions follow the resource/action convention, for example:

`organizations.view`

`organizations.create`

`quotations.send`

`orders.approve`

`payments.reverse`

### Reason

Authorization requires a controlled vocabulary of capabilities before
middleware and policies can enforce access.

### Dependencies

- RBAC migrations
- `Role` model
- `Permission` model
- Eloquent relationships

### Risk / Side Effects

Changing seeded role permissions changes the capabilities granted to users
assigned to those roles.

The seeder uses `updateOrCreate` and relationship `sync()` to remain
idempotent.

### Validation

Expected seeded configuration:

- 8 roles
- 40 permissions

The developer environment must verify the actual seed result.

### Troubleshooting Notes

None.

### Files / Components

- `database/seeders/RbacSeeder.php`
- `database/seeders/DatabaseSeeder.php`

### Follow-up

- Build automated RBAC tests.
- Add controlled user-role assignment.
- Implement permission middleware and policies in the appropriate stage.

## 2026-09-21 — Stage 2 / Unit 2D — Permission Count Test Correction

**Type:** BUG

**Status:** COMPLETED

### Context

The RBAC test suite reported one failure in the permission seeder test.

### Symptom

The test expected 41 permissions, while the seeded database contained 40.

### Evidence

Command:

`php artisan test tests/Feature/RbacTest.php`

Result before correction:

- 12 tests passed
- 1 test failed
- Actual permission count: 40
- Expected permission count: 41

### Root Cause

The test contained an incorrect hard-coded permission count.

The RBAC seeder defines 40 permissions, not 41.

### Fix

Replaced the hard-coded permission-count assertion with an explicit
permission-slug catalogue comparison.

This verifies both the number and identity of the seeded permissions.

### Validation

Pending re-run after the test correction.

### Files / Components

- `tests/Feature/RbacTest.php`
- `docs/DEVELOPMENT_LOG.md`

### Follow-up

Re-run the targeted RBAC test and then the complete test suite.