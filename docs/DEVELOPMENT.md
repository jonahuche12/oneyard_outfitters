## [2026-09-21] — Stage 3 / Unit 3A — Authorization Core
**Type:** SECURITY
**Status:** COMPLETED

### Context

The RBAC foundation established users, roles, permissions, role assignments, permission assignments, and an idempotent RBAC configuration. However, RBAC data alone did not enforce authorization at the HTTP request boundary.

### Change

Implemented the centralized authorization core:

- Added permission capability methods to the `User` model:
  - `hasPermission()`
  - `hasAnyPermission()`
  - `hasAllPermissions()`
- Added `EnsureUserHasPermission` middleware.
- Registered the `permission` middleware alias in `bootstrap/app.php`.
- Added feature tests covering authentication and permission enforcement.
- Tested permission inheritance through multiple roles.

### Reason

To establish a centralized server-side authorization mechanism before implementing business-module policies.

### Dependencies

- Laravel authentication foundation
- RBAC database schema
- Role model
- Permission model
- User-role relationship
- Role-permission relationship

### Risk / Side Effects

Protected routes using the `permission` middleware now reject authenticated users who do not possess the required permission with HTTP 403.

No existing business routes were changed because business modules have not yet been implemented.

### Validation

Full test suite executed successfully:

- 22 tests passed
- 29 assertions passed

Authorization tests confirmed:

- Guest authentication protection
- Unauthorized authenticated users receive 403
- Authorized users can access protected routes
- Permissions inherited through multiple roles
- `hasPermission()` behavior
- `hasAnyPermission()` behavior
- `hasAllPermissions()` behavior

### Troubleshooting Notes

No failure occurred during final Unit 3A verification.

The RBAC suite previously contained an incorrect expectation of 41 permissions while the approved catalogue contains 40. The test was corrected to validate the actual permission catalogue. The complete suite subsequently passed.

### Files / Components

- `app/Models/User.php`
- `app/Http/Middleware/EnsureUserHasPermission.php`
- `bootstrap/app.php`
- `tests/Feature/AuthorizationTest.php`
- `tests/Feature/RbacTest.php`

### Follow-up

Proceed to authorization architecture review before implementing record-level policies.

## [2026-09-21] — Phase 2 / Unit 4B — Contact Foundation
**Type:** DATABASE
**Status:** COMPLETED

### Context

Organizations require multiple human contacts for communication, procurement, administration, and operational coordination. Contacts must remain separate business records rather than being embedded directly into the organization record.

### Change

Implemented the Contact foundation:

- Created `contacts` migration.
- Added Organization foreign key.
- Added first, middle, and last names.
- Added organizational position.
- Added primary and alternate phone numbers.
- Added email and optional WhatsApp number.
- Added primary-contact flag.
- Added active/inactive state.
- Added internal notes.
- Added timestamps and soft deletion.
- Added relevant indexes.
- Created Contact Eloquent model.
- Added Contact → Organization relationship.
- Added Organization → Contacts relationship.
- Created Contact factory.
- Created Contact feature tests.

### Design Decisions

An organization may have multiple contacts.

An organization is not required to have a primary contact at all times.

The initial database design permits the application to manage the primary-contact invariant without introducing a database-specific uniqueness implementation prematurely.

Automatic primary-contact switching is intentionally deferred to the Contact application/service layer.

Contacts are business records and are not authenticated users.

### Referential Integrity

Contacts reference their owning organization through a foreign key.

Hard deletion of an organization cascades to its contacts.

Soft deletion of an organization does not trigger the database cascade, preserving the organization's historical contact records until an explicit hard deletion occurs.

### Validation

Full application test suite passed:

- 34 tests passed
- 49 assertions passed

Contact tests confirmed:

- Contact creation
- Organization relationship
- Organization contact retrieval
- Active-state default
- Boolean casting
- Soft deletion
- Hard-delete cascade

Existing Organization, RBAC, and Authorization tests remained green.

### Files / Components

- `app/Models/Contact.php`
- `app/Models/Organization.php`
- `database/migrations/*_create_contacts_table.php`
- `database/factories/ContactFactory.php`
- `tests/Feature/ContactTest.php`

### Follow-up

Proceed to Phase 3 — Products & Requirements.

## 2026-09-21 — 6A-2 / Product Specifications — RBAC Permissions
**Type:** SECURITY
**Status:** COMPLETED

### Context
Product Specifications require role-based access control before application-layer implementation.

### Change
Added the following permissions:
- specifications.view
- specifications.create
- specifications.update

Updated the RBAC seeder so:
- Super Admin receives all seeded permissions.
- Administrator and Sales receive full Product Specification access.
- Procurement, Production, Quality Control, Finance, and Delivery receive view-only access.

Updated RBAC feature tests to verify permission creation, role assignments, full-access roles, view-only roles, and seeder idempotency.

### Reason
Product Specifications are organization-specific operational records and require controlled access before CRUD and application workflows are introduced.

### Dependencies
- Product Specification models and migrations from 6A-1.
- Existing RBAC foundation.

### Risk / Side Effects
No specification delete permission was introduced.
Existing RBAC role and permission behavior remains intact.

### Validation
Full test suite passed:
83 passed (195 assertions)

### Troubleshooting Notes
Initial RBAC test failures were caused by outdated expected permission lists and a missing RbacSeeder import. These were corrected.

### Files / Components
- database/seeders/RbacSeeder.php
- tests/Feature/RbacTest.php

### Follow-up
Proceed to 6A-3: Product Specification validation and application layer.