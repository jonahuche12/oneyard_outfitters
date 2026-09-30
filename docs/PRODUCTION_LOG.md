# Oneyard Outfitters — Production Log

## CURRENT STATE

PROJECT: Oneyard Outfitters — Institutional Supply & Uniform Management System
CURRENT PHASE: Phase 2 — Application Operations
CURRENT FEATURE: Organization Management
CURRENT STAGE: Ready to begin Organization Management frontend
STATUS: Authentication, application shell, RBAC, staff management, and domain foundations verified
LAST COMPLETED: Authentication + Staff Management + full automated test suite
CURRENT WORK: Preparing Organization Management frontend and operational workflow
NEXT STEP: Build Organization List/Search → Create Organization → Organization Account → Edit Organization
BLOCKERS: None currently
KNOWN ISSUES: None currently blocking production
OPEN QUESTIONS: None currently blocking the next unit
DEFERRED SCOPE: Product catalogue/categories/products, supplier portal, advanced reporting, customer portal, complex inventory, automation

---

# PRODUCT OVERVIEW

Oneyard Outfitters is a Laravel-based institutional supply and uniform management system.

The system manages the operational relationship between Oneyard and organizations such as:

- Schools
- Companies
- Churches
- NGOs
- Government institutions
- Other organizations requiring uniforms or branded supplies

External organizations are business records in V1. They do not become authenticated system users.

The system is intended to replace fragmented operational records across:

- WhatsApp
- Phone conversations
- Notebooks
- Spreadsheets
- Supplier contacts
- Invoices
- Files
- Manual production records

The system will centralize organization information, requirements, quotations, orders, payments, production, deliveries, and follow-ups.

Core business workflow:

Register Organization
→ Update Organization Information
→ Add Organization Contact
→ Assess Organization
→ Record Follow-up
→ Add Product Specification
→ Create Quotation
→ Create Order
→ Record Payment
→ Update Production
→ Record Delivery
→ View / Search Organization Account

---

# APPROVED ARCHITECTURE

Technology stack:

- Laravel 13
- PHP 8.4
- MySQL
- Blade
- Tailwind CSS
- jQuery
- Laravel Fortify
- Eloquent ORM
- Laravel-native authorization
- PHPUnit/Pest feature testing

Frontend direction:

- Blade + jQuery
- No additional frontend framework unless explicitly justified and approved
- Existing jQuery AJAX uses `$.ajax()`
- JavaScript remains separate from Blade where appropriate

Security foundation:

USER
→ ROLE
→ PERMISSIONS
→ POLICIES / MIDDLEWARE
→ BUSINESS PIPELINE

Authorization flow:

Route
→ Authentication
→ Permission
→ Policy
→ Controller
→ Business Logic

UI permission hiding is not considered security. Backend authorization remains mandatory.

---

# BUSINESS ARCHITECTURE

The root business entity is Organization.

An Organization may have:

- Contacts
- Assessments
- Follow-ups
- Product Specifications
- Quotations
- Orders
- Payments
- Production records
- Deliveries

Product Catalogue is explicitly deferred.

Product Specification is an organization-specific requirement record and is NOT currently a global product catalogue.

The V1 system should not be expanded into a full ERP prematurely.

---

# IMPLEMENTATION ROADMAP

Phase 1 — Foundation

- Laravel application
- Authentication
- RBAC
- Authorization

Status: COMPLETED / VERIFIED

Phase 2 — Application Operations

- Application shell/frontend
- User/staff management
- Organization management

Current phase.

Phase 3 — Organization Intelligence

- Contacts
- Assessments
- Follow-ups

Phase 4 — Requirements

- Product Specifications

Phase 5 — Commercial

- Quotations
- Orders

Phase 6 — Financial / Fulfillment

- Payments
- Production
- Deliveries

Phase 7 — Organization Account

- Consolidated organization account
- Search
- Operational visibility

---

# PRODUCTION ENTRIES

## 2026-09-19 — FOUNDATION — Laravel Application Established

**Type:** CONFIGURATION  
**Status:** COMPLETED

### Context

A new Laravel project was established for Oneyard Outfitters.

### Change

Established the Laravel application foundation.

### Technical Environment

- Laravel 13.32.0
- PHP 8.4.1
- Composer 2.8.12
- MySQL
- Database sessions
- Database queue
- Local development environment

### Architecture

The application is being developed as a Laravel monolith using:

- Blade
- Tailwind
- jQuery
- Eloquent
- Laravel-native authorization

### Validation

Laravel application and environment were inspected using Artisan.

### Result

Foundation established.

---

## 2026-09-19 — AUTHENTICATION — Fortify Authentication

**Type:** FEATURE  
**Status:** COMPLETED / VERIFIED

### Context

The system requires secure authentication for internal staff.

### Change

Laravel Fortify was installed and configured.

Public registration was disabled.

Authentication was restricted to active internal users.

Fortify features retained for V1:

- Password reset
- Profile information update
- Password update

### Security Rule

Inactive users cannot authenticate.

### Custom Authentication

Authentication verifies:

- User existence
- Active status
- Password validity

### Validation

Authentication feature tests were implemented and passed.

Verified cases included:

- Login page
- Active user login
- Inactive user rejection
- Guest dashboard protection
- Authenticated dashboard access
- Logout

### Result

Authentication was verified by automated tests.

---

## 2026-09-20 — AUTHENTICATION — Fortify Login View Resolution

**Type:** BUG / FIX  
**Status:** COMPLETED / VERIFIED

### Context

The login page initially produced a Fortify container resolution error for `LoginViewResponse`.

### Root Cause

Fortify's login view response contract was not bound for the custom Blade login page.

### Change

A `LoginViewResponse` binding using `SimpleViewResponse` was added to `FortifyServiceProvider`.

### Result

The login page resolved correctly.

Authentication tests passed.

---

## 2026-09-20 — FRONTEND — Application Shell

**Type:** FEATURE  
**Status:** COMPLETED / VERIFIED

### Change

Created the main authenticated application shell.

The shell includes:

- Sidebar navigation
- Top navigation
- Current authenticated user information
- Logout
- Flash messages
- Validation/error display
- Permission-aware navigation

### Frontend

Blade + Tailwind was used.

jQuery was installed and exposed globally for application AJAX requirements.

### Result

Authenticated users have a consistent application interface.

---

## 2026-09-20 — AUTHORIZATION — RBAC Foundation

**Type:** SECURITY / FEATURE  
**Status:** COMPLETED / VERIFIED

### Change

Implemented role-based access control.

Roles established:

- super-admin
- admin
- sales
- procurement
- production
- quality-control
- finance
- delivery

Permissions use the convention:

`resource.action`

### Core Permission Areas

- organizations
- contacts
- requirements
- quotations
- orders
- payments
- production
- quality-control
- deliveries
- follow-ups
- suppliers
- procurement
- specifications
- users

### Database Structure

Implemented:

- roles
- permissions
- role_user
- permission_role

Unique constraints prevent duplicate role and permission assignments.

### Authorization

Implemented:

- User permission checks
- Permission middleware
- Policies
- Permission-aware application navigation

### Result

RBAC was verified through automated tests.

---

## 2026-09-20 — AUTHORIZATION — Active User Middleware

**Type:** SECURITY  
**Status:** COMPLETED / VERIFIED

### Change

Implemented `EnsureUserIsActive`.

Inactive authenticated users are:

- Logged out
- Session invalidated
- CSRF token regenerated
- Redirected to login

### Result

Inactive accounts cannot continue using the application.

---

## 2026-09-20 — DATABASE — Organization Foundation

**Type:** DATABASE  
**Status:** COMPLETED / VERIFIED

### Change

Created the Organization database foundation.

Organizations contain:

- organization_code
- name
- type
- ownership
- phone
- email
- address
- city
- area
- LGA
- state
- country
- website
- notes
- active state
- timestamps
- soft deletion

Indexes were added for commonly queried organization fields.

### Organization Code Decision

Organization references use:

`ORG-000001`
`ORG-000002`
`ORG-000003`

The code is:

- Server-generated
- Sequential
- Immutable
- Not used as a security credential

The database primary key is used to generate the reference after creation.

### Result

Organization domain foundation verified by tests.

---

## 2026-09-20 — ORGANIZATION DOMAIN — Contacts

**Type:** FEATURE  
**Status:** COMPLETED / VERIFIED

### Change

Implemented the Contact domain foundation.

Contacts belong to Organizations.

Contacts support:

- Organization relationship
- Active state
- Primary contact state
- Soft deletion

### Data Integrity

Hard deletion of an Organization cascades to its contacts.

Deleting a contact does not destroy related follow-up history.

### Result

Contact domain tests passed.

---

## 2026-09-20 — ORGANIZATION INTELLIGENCE — Assessments

**Type:** FEATURE  
**Status:** COMPLETED / VERIFIED

### Change

Implemented the Assessment domain foundation.

Assessments:

- Belong to Organizations
- Record the assessing user
- Support assessment dates
- Support estimated budgets
- Default to draft
- Support soft deletion

### Data Integrity

Deleting an assessed user preserves the assessment record.

### Result

Assessment tests passed.

---

## 2026-09-20 — ORGANIZATION INTELLIGENCE — Follow-ups

**Type:** FEATURE  
**Status:** COMPLETED / VERIFIED

### Change

Implemented Follow-up domain foundation.

Follow-ups:

- Belong to Organizations
- May belong to Contacts
- Record the staff member who recorded the follow-up
- Track follow-up dates
- Default to open
- Support soft deletion

### Data Integrity

Deleting a contact preserves follow-up history.

Deleting the recording user preserves follow-up history.

Hard deletion of an Organization cascades to its follow-ups.

### Result

Follow-up tests passed.

---

## 2026-09-20 — REQUIREMENTS — Product Specification Foundation

**Type:** FEATURE  
**Status:** COMPLETED / VERIFIED

### Context

Product Specification is an organization-specific requirement record.

It is deliberately NOT a product catalogue.

### Change

Implemented:

- Product Specification model
- Database structure
- Organization relationship
- Optional contact relationship
- Creator relationship
- Specification artifacts
- Status
- Product type
- Unit price
- Specification date
- Price update date

### Authorization

Implemented specification permissions and policies.

Permissions:

- specifications.view
- specifications.create
- specifications.update

No specification delete permission was introduced.

### Validation

Implemented:

- StoreProductSpecificationRequest
- UpdateProductSpecificationRequest
- Organization ownership validation
- Contact ownership validation
- Product type validation
- Status validation
- Non-negative unit price validation

Actions were implemented for creation and update.

### Result

Product Specification tests and validation tests passed.

---

## 2026-09-21 — USER MANAGEMENT — Staff Permissions

**Type:** SECURITY / FEATURE  
**Status:** COMPLETED / VERIFIED

### Context

Super Admin must be able to create and manage internal staff.

### Change

Added:

- users.view
- users.create
- users.update
- users.activate
- users.deactivate
- users.assign-roles

Super Admin receives all seeded permissions.

Other roles do not automatically receive staff-management permissions.

### Result

Staff-management authorization verified.

---

## 2026-09-21 — USER MANAGEMENT — Staff Management

**Type:** FEATURE  
**Status:** COMPLETED / VERIFIED

### Change

Implemented operational staff management:

- Staff list
- Staff search
- Staff creation
- Staff detail
- Staff editing
- Staff activation
- Staff deactivation
- Role assignment
- Permission-aware actions

### Security

Self-deactivation is prevented.

Authorization is enforced through `UserPolicy`.

### Routes

Staff resource routes and activation/deactivation endpoints were added.

### Frontend

Dedicated Blade views were created for:

- Staff index
- Staff create
- Staff show
- Staff edit

The application shell is used consistently.

### Result

Staff Management tests passed.

---

## 2026-09-21 — SEEDING — Super Admin Account

**Type:** SECURITY / CONFIGURATION  
**Status:** COMPLETED / VERIFIED

### Change

Implemented `SuperAdminSeeder`.

The Super Admin account is configured through environment variables:

- SUPER_ADMIN_NAME
- SUPER_ADMIN_EMAIL
- SUPER_ADMIN_PASSWORD

### Security

The password is never hardcoded into the seeder.

The existing password is not overwritten when the account already exists.

The account is activated and assigned the `super-admin` role.

The seeder is idempotent.

### Database Seeder

`DatabaseSeeder` runs:

1. RbacSeeder
2. SuperAdminSeeder

### Validation

`SuperAdminSeederTest` verifies:

- Account creation
- Active state
- Password hashing
- Super Admin role assignment
- Idempotent execution
- Existing password preservation

### Result

Super Admin seeding verified.

---

## 2026-09-21 — TESTING — Test Suite Stabilization

**Type:** TEST / FIX  
**Status:** COMPLETED / VERIFIED

### Issues Found

Two stale tests initially failed after the application architecture changed.

1. ExampleTest expected `/` to return HTTP 200.
2. RbacTest expected 43 permissions after six staff-management permissions were added.

### Fixes

ExampleTest was updated to verify the application's actual root redirect to `/dashboard`.

RbacTest was updated to include the six approved user-management permissions.

### Result

Focused tests passed.

---

## 2026-09-22 — TESTING — Full Regression Suite

**Type:** TEST  
**Status:** COMPLETED / VERIFIED

### Validation

Full Laravel test suite was executed.

### Result

106 tests passed.

264 assertions passed.

0 failures.

Final verified output:

Tests: 106 passed (264 assertions)

### Coverage Areas

The verified suite includes:

- Authentication
- Authorization
- RBAC
- Staff management
- Super Admin seeding
- Organizations
- Contacts
- Assessments
- Follow-ups
- Product Specifications
- Product Specification validation

---

## 2026-09-23 — AUTHENTICATION — Browser Login Verification

**Type:** TEST  
**Status:** COMPLETED / VERIFIED

### Context

A runtime login issue was investigated after successful automated authentication tests.

### Troubleshooting

The Super Admin account and password configuration were considered as possible runtime causes.

The browser login was subsequently tested directly.

### Result

The user successfully logged into the application using the correct credentials.

Authentication is therefore verified both by:

- Automated feature tests
- Actual browser login

No authentication code changes were required as part of this troubleshooting step.

---

# CURRENT VERIFIED FOUNDATION

The following areas are currently verified:

- Laravel application foundation
- Authentication
- Active-user enforcement
- Application shell
- RBAC
- Permission middleware
- Policies
- Super Admin seeding
- Staff management
- Organization database foundation
- Contact foundation
- Assessment foundation
- Follow-up foundation
- Product Specification foundation
- Product Specification validation
- Full automated test suite
- Browser login

Current automated test result:

**106 passed — 264 assertions — 0 failures**

---

# CURRENT PRODUCTION SCOPE

## REQUIRED NOW

The next operational workflow is Organization Management.

Required:

1. Organization navigation
2. Organization list
3. Organization search
4. Organization pagination
5. Create Organization
6. Automatic organization code
7. Organization account/show page
8. Update Organization
9. Backend authorization
10. Validation
11. Feature tests
12. Browser verification

## NEXT

After Organization Management is verified:

- Contacts frontend
- Contact creation/update
- Organization contact management

## DEFERRED

- Global Product Catalogue
- Product Categories
- Global Products
- Supplier portal
- Customer portal
- Advanced inventory
- Complex manufacturing/ERP functionality
- Advanced reporting
- Broad automation
- Unapproved abstractions

---

# ENGINEERING RULES

1. Build one logical production unit at a time.
2. Do not rewrite already-verified functionality without a concrete reason.
3. Do not introduce scope that is not required for the current unit.
4. Backend authorization must protect every protected operation.
5. UI permission checks are supplementary, not security.
6. Validate data server-side.
7. Preserve historical business records.
8. Use database transactions where multiple related writes must succeed together.
9. Avoid premature abstractions.
10. Do not claim tests passed unless they were actually executed.
11. Distinguish IMPLEMENTED from VERIFIED.
12. Documentation is updated after meaningful production work is completed or verified.
13. Keep production entries concise and factual.
14. Preserve historical entries; do not rewrite history to hide previous problems.
15. Every meaningful production step must update this log.

---

# CURRENT NEXT STEP

Build the Organization Management frontend and operational flow:

Organization List/Search
→ Create Organization
→ Organization Account
→ Update Organization
→ Test
→ Browser Verify

No Contacts implementation should be started until Organization Management is verified.
## [2026-09-23] — Staff Management Authorization & Test Verification

- Normalized Staff resource route parameters from `{staff}` to `{user}` so route model binding matches `StaffController` and Staff FormRequests.
- Verified the Staff route set contains the expected index, create, store, show, edit, update, activate, and deactivate operations.
- Diagnosed and fixed the Staff creation authorization error caused by calling `UserPolicy::assignRoles()` without a target User.
- Updated Staff creation role authorization to use the `users.assign-roles` permission directly because the target staff account does not exist until after creation.
- Preserved target-aware `assignRoles` policy authorization for existing staff accounts during updates.
- Updated Staff creation role synchronization so roles are assigned only when the authenticated user has `users.assign-roles`.
- Corrected malformed Blade directive nesting for the Staff deactivate action.
- Focused Staff Management test suite completed successfully: 7 tests passed, 15 assertions.
- Full Laravel test suite completed successfully: 106 tests passed, 263 assertions, 0 failures.
- Staff backend authorization, creation, activation, deactivation, and route behavior are now passing automated verification.
- Next production stage: complete and verify the Staff Management frontend so all permitted View, Edit, Activate, and Deactivate actions are correctly displayed and functional before moving to Organization Management.







## [2026-09-23] — Staff Management Frontend Verification Baseline

### Scope
Verified the current Staff Management frontend implementation against the existing backend routes, authorization structure, and automated test suite before expanding Staff functionality.

### Frontend Files Verified
- resources/views/staff/index.blade.php
- resources/views/staff/create.blade.php
- resources/views/staff/show.blade.php
- resources/views/staff/edit.blade.php

### Verified Frontend Capabilities
- Staff index displays staff accounts.
- Add Staff button is permission-aware.
- Staff search form targets the Staff index route.
- Staff pagination is rendered when required.
- View action is permission-aware.
- Edit action is permission-aware.
- Activate action is permission-aware and shown only for inactive accounts.
- Deactivate action is permission-aware and hidden for the authenticated user's own account.
- Staff creation form contains personal information, password and role sections.
- Role assignment controls are permission-aware.
- Staff detail page displays account information, status and assigned roles.
- Staff edit page supports account information, password changes and role assignment.
- Feedback and validation messages are displayed.
- Staff forms use the normalized named routes and Laravel CSRF/method spoofing.

### Automated Verification

Focused Staff test command:

    php artisan test tests/Feature/Staff/StaffManagementTest.php

Result:

    PASS
    Tests: 7 passed (15 assertions)
    Duration: 0.79s

Full test suite command:

    php artisan test

Result:

    PASS
    Tests: 106 passed (263 assertions)
    Duration: 5.04s

### Current Status
The existing Staff implementation has a clean automated baseline with zero test failures.

Staff Management is NOT yet marked complete.

Remaining verification/build work:
- Expand Staff feature-test coverage for all resource routes.
- Verify create, show, edit and update authorization.
- Verify Staff update validation and role replacement.
- Verify password update behavior.
- Verify activate/deactivate authorization boundaries.
- Verify search behavior.
- Verify pagination behavior.
- Verify rendered permission-based action visibility.
- Verify Dashboard → Staff navigation.
- Perform the complete Super Admin browser workflow.
- Perform final Staff regression test.
- Log the final Staff completion milestone.

### Production Decision
Do NOT begin Organization Management until Staff Management passes the expanded automated and end-to-end verification process.

### Next Stage
Expand and harden Staff Management feature tests before declaring the Staff module production-complete.


## [2026-09-23] — Staff Management Expanded Feature Tests Passed

### Scope
Completed expanded automated verification of the Staff Management module, including account access, creation, viewing, editing, updating, role assignment, activation, deactivation, validation, search, pagination, authentication, route model binding and inactive-account protection.

### Test Command

    php artisan test tests/Feature/Staff/StaffManagementTest.php

### Result

    PASS

    Tests:    32 passed (79 assertions)
    Duration: 1.62s

### Verified Scenarios

#### Access & Authorization
- Authorized users with `users.view` can access Staff Management.
- Users without `users.view` are denied Staff Management.
- Authorized users with `users.create` can access the Staff creation form.
- Users without `users.create` are denied the Staff creation form.
- Authorized users can view Staff accounts.
- Users without `users.view` cannot view Staff accounts.
- Authorized users with `users.update` can access Staff edit forms.
- Users without `users.update` cannot access Staff edit forms.
- Users without `users.update` cannot update Staff accounts.
- Users without `users.activate` cannot activate Staff accounts.
- Users without `users.deactivate` cannot deactivate Staff accounts.
- Users without `users.assign-roles` cannot access role-assignment controls.

#### Staff Creation
- Authorized users can create Staff accounts.
- Unauthorized users cannot create Staff accounts.
- Required Staff creation fields are validated.
- Duplicate Staff email addresses are rejected.
- Newly created accounts are active.
- Authorized role assignment during creation is verified.

#### Staff Updates
- Authorized users can update Staff information.
- Duplicate email addresses are rejected during updates.
- Staff passwords can be changed.
- Existing passwords remain unchanged when no new password is supplied.
- Authorized users with `users.assign-roles` can replace assigned Staff roles.
- Users without `users.assign-roles` cannot modify Staff roles.

#### Account Status
- Authorized administrators can deactivate Staff accounts.
- Authorized administrators can activate Staff accounts.
- Staff members cannot deactivate their own accounts.
- Inactive authenticated users are prevented from accessing Staff Management.
- Inactive users are redirected to login according to `EnsureUserIsActive`.
- The inactive-account error is verified under the `email` session error key.
- Inactive users are logged out by the active-account middleware.

#### Staff Discovery & Navigation
- Staff search works by name.
- Staff search works by email.
- Staff pagination is verified.
- Staff route model binding is verified.
- Missing Staff accounts return `404`.
- Unauthenticated users are redirected from Staff management routes.

#### Frontend Action Rendering
- Permission-aware Staff actions are rendered on the Staff index.
- Staff action states are represented for active and inactive accounts.

### Troubleshooting Record

The inactive-user test initially expected HTTP `403`.

Actual application behavior is HTTP `302` because:

    app/Http/Middleware/EnsureUserIsActive.php

logs out inactive users, invalidates their session, regenerates the CSRF token and redirects them to the login route with:

    Your account is inactive. Please contact an administrator.

The test was corrected to verify the actual middleware contract rather than changing the working middleware.

### Current Status

Staff Management expanded automated feature coverage is PASSING.

    32 tests
    79 assertions
    0 failures

Staff Management is NOT yet marked production-complete.

### Remaining Production Verification

- Run the complete application test suite.
- Verify Dashboard → Staff navigation.
- Verify the complete Staff frontend in the browser as Super Admin.
- Verify every Staff button and route end-to-end.
- Verify create → show → edit → role change → deactivate → activate workflow.
- Verify search and clear-search behavior in the browser.
- Perform final Staff regression testing.
- Record final Staff completion milestone.

### Production Gate

Organization Management remains blocked until Staff Management completes automated regression and end-to-end frontend verification.

### Next Stage

Run the complete application test suite:

    php artisan test


## [2026-09-23] — Full Application Regression Passed After Staff Test Expansion

### Scope
Completed full application regression testing after expanding Staff Management feature coverage.

### Test Command

    php artisan test

### Result

    PASS

    Tests:    131 passed (327 assertions)
    Duration: 5.53s

    Failures: 0

### Regression Coverage

The complete test suite passed across:

- Assessment functionality
- Authentication
- Authorization
- Contact functionality
- Follow-up functionality
- Organization model behavior
- Product Specification functionality
- Product Specification validation
- Role-Based Access Control
- Staff Management
- Super Admin seeding

### Staff Management Regression Status

Staff Management now passes:

    32 Staff tests
    79 Staff assertions
    0 failures

The complete application suite also confirms that the expanded Staff Management tests did not introduce regressions into existing application functionality.

### Important Staff Verification Boundary

Automated verification is now passing.

This does NOT yet constitute final Staff production completion because browser-level verification remains outstanding.

### Remaining Staff Production Verification

The following must still be verified through the actual application interface as the Super Admin:

1. Dashboard → Staff navigation.
2. Staff index rendering.
3. Add Staff button.
4. Staff creation form.
5. Staff account creation.
6. Redirect to Staff detail page after creation.
7. Active status display.
8. Assigned role display.
9. Edit Staff navigation.
10. Staff information update.
11. Role replacement.
12. Password update workflow.
13. Deactivate Account.
14. Inactive status display.
15. Activate Account.
16. Active status restoration.
17. Back to Staff navigation.
18. Staff search.
19. Clear search.
20. Pagination.
21. Permission-aware action visibility.
22. Self-deactivation protection.
23. Correct route behavior for every Staff action.

### Current Production Status

Staff Management has:

    ✓ Backend route verification
    ✓ Authorization verification
    ✓ Validation verification
    ✓ Account creation verification
    ✓ Account update verification
    ✓ Role assignment verification
    ✓ Password update verification
    ✓ Activation verification
    ✓ Deactivation verification
    ✓ Search verification
    ✓ Pagination verification
    ✓ Route model binding verification
    ✓ Authentication verification
    ✓ Inactive-account protection verification
    ✓ Expanded Staff feature suite
    ✓ Full application regression

Remaining:

    → Super Admin browser workflow verification
    → Final Staff production completion review

### Production Gate

Organization Management remains BLOCKED.

Do not begin Organization Management until the Staff browser workflow has been completed and the final Staff production milestone has been logged.

### Next Stage

Begin Super Admin browser-level verification of the complete Staff Management workflow.

## Staff Management — Super Admin Browser Verification Passed

Date: 2026-09-23

Completed end-to-end browser verification of Staff Management for the Super Admin.

Verified workflow:

Dashboard
→ Staff
→ Staff Index
→ Add Staff
→ Create Staff Account
→ Staff Show
→ Edit Staff
→ Update Staff
→ Deactivate
→ Activate
→ Back to Staff
→ Search
→ Clear Search

Verified:
- Staff navigation works.
- Add Staff button and create form work.
- Staff creation succeeds and redirects correctly.
- Created account displays the correct active status.
- Assigned roles are displayed.
- Staff editing works.
- Staff information and roles update correctly.
- Deactivation works.
- Activation works.
- Correct action buttons appear according to account status.
- Self-deactivation is prevented.
- Staff search works.
- Search clearing works.
- Frontend routes and buttons successfully connect to the expected backend operations.

Automated verification:
- Staff Management: 32 passed, 79 assertions.
- Full application regression: 131 passed, 327 assertions.
- 0 failures.

Result:
Staff Management is verified and complete for the Super Admin.

Next production area:
Organization Management.
## Organization Management — Backend Verification Complete

### Milestone
Organization Management backend implementation has passed its focused feature test suite.

### Verified
- Organization authorization and RBAC
- Authentication requirements
- Inactive-user protection
- Organization creation authorization
- Organization creation validation
- Organization type validation
- Organization ownership validation
- Organization viewing
- Organization editing
- Organization updating
- Organization code generation
- Organization code stability during updates
- Organization activation
- Organization deactivation
- Search by organization code
- Search by organization name
- Search by organization type
- Search by city
- Search by state
- Pagination
- Pagination query-string preservation
- Route model binding
- Missing organization handling
- Soft-deleted organization exclusion

### Test Command
`php artisan test tests/Feature/Organization/OrganizationManagementTest.php`

### Result
PASS

Tests: 32 passed (74 assertions)
Duration: 1.66s

### Important Implementation Detail
Organization codes are generated server-side in the format:

`ORG-000001`

A temporary unique server-generated code is used during the initial database insert because `organization_code` is required and the final sequential code depends on the database-generated organization ID. The permanent code is assigned within the same database transaction.

### Status
Organization Management backend is verified at the focused feature-test level.

### Next Production Step
Review and validate the Organization Management Blade views and UI workflow before running the full application regression suite.

## Organization Management — Automated Regression Verification Complete

### Milestone

Organization Management backend and application integration have passed the complete automated test suite.

### Verification Scope

* Organization model behavior
* Organization relationships
* Organization authorization and RBAC
* Authentication and inactive-user protection
* Organization creation and validation
* Organization viewing
* Organization editing and updating
* Organization code generation and stability
* Organization activation and deactivation
* Organization search
* Organization pagination
* Route model binding
* Soft-delete behavior
* Contact integration
* Assessment integration
* Follow-up integration
* Product Specification integration
* Existing authentication behavior
* Existing RBAC behavior
* Existing Staff Management behavior
* Super Admin seeding

### Focused Organization Test

Command:

`php artisan test tests/Feature/Organization/OrganizationManagementTest.php`

Result:

`32 passed (74 assertions)`

### Full Regression Test

Command:

`php artisan test`

Result:

`163 passed (401 assertions)`

Duration:

`6.60s`

### Status

PASS — no automated test failures detected.

Organization Management is verified against the complete current application test suite.

### Next Production Step

Perform manual browser verification of the complete Organization Management workflow:

LOGIN
→ DASHBOARD
→ ORGANIZATIONS
→ INDEX
→ ADD ORGANIZATION
→ CREATE
→ SHOW
→ EDIT
→ UPDATE
→ DEACTIVATE
→ ACTIVATE
→ BACK
→ SEARCH
→ CLEAR

Browser verification must confirm both functional behavior and the permission-aware UI before the Organization Management milestone is considered production-complete.



## Organization Management — Index Lifecycle UI Authorization and Button Refinement

### Milestone
Organization Management index lifecycle controls were aligned with the dedicated organization lifecycle authorization policy.

### Change Implemented
Updated:

`resources/views/organizations/index.blade.php`

The organization index previously used:

- `@can('update', $organization)` for Activate
- `@can('update', $organization)` for Deactivate

These controls were changed to:

- `@can('activate', $organization)`
- `@can('deactivate', $organization)`

This ensures the index UI uses the same authorization boundary as the backend OrganizationPolicy.

### Authorization Behavior
Organization lifecycle actions require:

- `organizations.update`
- Administrator role (`admin`) or Super Admin role (`super-admin`)

Therefore:

- Administrator can see and use Activate/Deactivate.
- Super Admin can see and use Activate/Deactivate.
- Sales cannot see these lifecycle buttons, even when Sales has `organizations.update`.
- View-only users cannot see these lifecycle buttons.
- Backend policy authorization remains enforced independently of UI visibility.

### UI Refinement
The lifecycle controls were visually improved:

- Activate uses an emerald status treatment.
- Deactivate uses a red warning treatment.
- Both buttons use consistent sizing and typography.
- Icons were added for clearer action recognition.
- Focus states were added for keyboard accessibility.
- Existing active/inactive conditional display behavior was preserved.

### Validation
Command:

`php artisan optimize:clear`

Result:

Cache and compiled application state cleared successfully.

Command:

`php artisan test`

Result:

`167 passed (409 assertions)`

Duration:

`8.15s`

### Status
PASS — full automated regression completed with zero failures.

The Organization Management index lifecycle UI is now aligned with backend lifecycle authorization.

### Next Production Step
Perform browser verification of the Organization Management index specifically for:

1. Administrator:
   - Active organization shows Deactivate.
   - Inactive organization shows Activate.
   - Activate changes status to Active.
   - Deactivate changes status to Inactive.

2. Super Admin:
   - Same lifecycle controls are available.

3. Sales:
   - Organizations remain accessible according to `organizations.view`.
   - Activate and Deactivate controls are not displayed.

4. View-only staff:
   - Organizations remain accessible.
   - Activate and Deactivate controls are not displayed.

5. Confirm the refined Activate and Deactivate button presentation in the browser.


## Contact Management — Scope and Authorization Design Established

### Milestone
Contact Management has been established as a required production module before Assessment Management.

### Business Flow Decision
The operational workflow is now defined as:

Organization
→ Contact Management
→ Assessment
→ Follow-up
→ Product Specification
→ Quotation
→ Order
→ Payment
→ Production
→ Delivery
→ Organization Account / Search

Contacts are not treated as a standalone operational stage that replaces the organization. They are relationship records belonging to an organization and provide the people/context required by later operational activities, especially follow-ups.

### Contact Management Purpose
The Contact module will allow Oneyard to maintain organization contacts and identify the primary contact for future interactions.

A single organization may have multiple contacts, while the system should maintain one primary contact at a time.

Example:

Organization
├── Principal
├── Procurement Officer
├── Accounts Officer
└── Other Contacts

One contact can be designated as the organization's primary contact.

### Contact Data Separation
Contact information and internal staff observations will be treated separately.

#### Contact Master Information
Includes controlled contact information such as:
- Name
- Position / role
- Phone
- Email
- Primary-contact status
- Active status
- Other approved contact fields

#### Internal Contact Notes
Operational staff may record Oneyard's experience with the contact, including communication preferences, interaction history, and useful relationship observations.

This separation prevents ordinary operational staff from unnecessarily modifying important contact information while still allowing them to contribute institutional knowledge.

### Proposed Contact Permissions
Dedicated permissions will be established:

- `contacts.view`
- `contacts.create`
- `contacts.update`
- `contacts.update-notes`
- `contacts.delete`
- `contacts.activate`
- `contacts.deactivate`

Exact role-to-permission assignments will be established during RBAC implementation based on operational requirements.

### Destructive Authorization
Contact deletion is restricted to:

- Administrator
- Super Admin

The policy must enforce this restriction at the backend.

The Delete control must also be hidden from unauthorized users in the UI.

Contact deletion should use soft deletion so historical organizational relationship data is not unnecessarily destroyed.

### Lifecycle
Contact activation and deactivation remain separate from deletion:

ACTIVE
→ DEACTIVATE
→ INACTIVE
→ ACTIVATE

DELETE
→ SOFT DELETED

### Primary Contact Rule
The system should maintain one primary contact per organization.

When a different contact becomes primary:

Previous primary
→ `is_primary = false`

New primary
→ `is_primary = true`

### Contact Account
The planned Contact account will provide:

- Contact identity
- Organization relationship
- Contact information
- Primary status
- Active status
- Internal notes
- Permission-aware actions

### Organization Integration
The Organization account will expose its contacts and provide an entry point to:

- View contacts
- Add contacts
- Identify the primary contact

Later Follow-up records should be capable of identifying the specific contact involved in the interaction.

### Production Principle
Contact Management must be implemented without weakening existing Organization Management, Staff Management, authentication, RBAC, or tested organization relationships.

Existing Contact model, migration, and relationship tests must be inspected before implementation. Existing verified functionality should not be unnecessarily rewritten.

### Next Production Step
Inspect the existing Contact foundation:

- Contact model
- Contacts migration
- Contact tests
- Existing Contact controllers/requests/views
- Contact routes

Then finalize the implementation design before generating or modifying Laravel application files.

### Status
DESIGN/SCOPE APPROVED FOR IMPLEMENTATION.


---

## 2026-09-23 — Contact Notes Foundation Completed

### Production Stage
Contact Management → Contact Notes Foundation → Model, Migration, Relationships, Factory, Tests

### Objective
Establish historical interaction tracking for organization contacts without overwriting the existing Contact record.

### Business Requirement
Contact information and historical staff interactions are separate concerns.

The existing `contacts.notes` field remains part of the Contact master record.

A separate `contact_notes` table now records historical staff interactions:

Contact
└── Contact Notes
    ├── note
    ├── recorded_by
    ├── created_at
    ├── updated_at
    └── deleted_at

Each interaction is stored as a separate historical record rather than replacing a previous note.

### Implemented

#### ContactNote Model
Created:

`app/Models/ContactNote.php`

The model:
- Uses HasFactory.
- Uses SoftDeletes.
- Defines fillable fields:
  - contact_id
  - recorded_by
  - note
- Belongs to Contact.
- Belongs to User through `recorded_by` using the `recorder()` relationship.

#### Contact Notes Migration
Created/finalized:

`database/migrations/2026_09_23_042956_create_contact_notes_table.php`

Schema:
- id
- contact_id
- recorded_by nullable
- note
- timestamps
- softDeletes

Foreign-key behavior:
- contact_id → contacts with cascadeOnDelete()
- recorded_by → users with nullOnDelete()

This preserves the interaction record if the recording staff account is deleted while removing the staff identity association.

Indexes:
- contact_id
- recorded_by

#### Contact Relationship
Added to `Contact`:

`interactionNotes()`

This intentionally avoids naming the relationship `notes()` because `Contact` already contains a database attribute named `notes`.

#### User Relationship
Added to `User`:

`contactNotesRecorded()`

This allows the system to retrieve historical contact interactions recorded by a particular staff member.

#### Factory
Created:

`database/factories/ContactNoteFactory.php`

The factory generates:
- Contact
- User
- Interaction note

### Validation

Focused ContactNote test suite:

`Tests\Feature\ContactNoteTest`

Result:

8 tests passed
11 assertions

Validated:
- ContactNote creation.
- Contact relationship.
- Recording-user relationship.
- Contact historical interaction relationship.
- User recorded-note relationship.
- Staff deletion preserving historical note.
- Contact deletion cascading to notes.
- ContactNote soft deletion.

Combined Contact foundation regression:

`Tests\Feature\ContactTest`
`Tests\Feature\ContactNoteTest`

Result:

15 tests passed
23 assertions

No failures.

### Architectural Decision

Historical contact interaction is now modeled independently from Contact master data.

This allows authorized operational staff to record organizational experience and interaction history without requiring permission to modify sensitive Contact master information such as:
- phone
- email
- alternate phone
- WhatsApp
- position
- identity information

The Contact Notes authorization layer will therefore be designed independently from Contact master-data authorization.

### Current Status
Contact Notes foundation: COMPLETE AND TESTED

Contact application layer: NOT YET IMPLEMENTED

Contact authorization/RBAC: NEXT STAGE

### Next Production Step
Design and implement Contact Notes and Contact-specific authorization boundaries before building the Contact controller, routes, and UI.


## Milestone — Contact RBAC and Authorization Verified
Date: 2026-09-23

### Completed
- Corrected `tests/Feature/RbacTest.php` to include all existing Contact Note permissions.
- Verified RBAC seeded permission count and role assignments.
- Verified `ContactPolicy` authorization behavior.
- Confirmed Laravel resolves `ContactPolicy` for the `Contact` model through policy discovery.
- Confirmed Admin can manage Contact lifecycle and deletion.
- Confirmed Sales can view/update Contact information and current notes but cannot activate, deactivate, or delete Contacts.
- Confirmed operational roles can view Contacts without Contact management access.

### Verification
- `php artisan test --filter=RbacTest`
  - 15 tests passed
  - 116 assertions
- `php artisan test --filter=ContactAuthorizationTest`
  - 5 tests passed
  - 40 assertions

### Result
Contact RBAC and backend authorization foundation is complete and verified.

### Next Stage
Proceed to the Contact application layer: routes, controller actions, request validation, views, lifecycle actions, and organization-account integration, following the existing application architecture and authorization rules.

## Contact Notes — Application Completion

### Implemented
- Added ContactNotePolicy using the existing application RBAC permission architecture.
- Enforced `contact-notes.view`, `contact-notes.create`, `contact-notes.update`, and `contact-notes.delete`.
- Completed Contact Note application workflow:
  - Record interaction
  - View interaction history
  - Edit interaction
  - Soft-delete interaction
  - Record staff member responsible for interaction
- Contact interaction history remains separate from the Contact master `notes` field.

### Troubleshooting
- Initial ContactNote application tests returned HTTP 403 for authorized users.
- Investigation confirmed that `ContactNotePolicy` did not exist in `app/Policies`.
- The application already relied on Laravel policy discovery by model/policy naming convention.
- Added `ContactNotePolicy` following the established `ContactPolicy` authorization pattern.
- Re-ran application tests successfully.

### Verification
- `php artisan test --filter=ContactNoteApplicationTest`
  - 4 tests passed
  - 12 assertions passed
- `php artisan test --filter='Contact(Test|AuthorizationTest|NoteTest)'`
  - 20 tests passed
  - 63 assertions passed

### Status
Contact and Contact Notes foundation is implemented and verified. Ready to continue to the next production feature.

## Assessment — Application Completion

### Implemented
- Completed the Assessment business module for organization intelligence.
- Added Assessment model, migration, factory, and organization/user relationships.
- Added Assessment RBAC permissions:
  - `assessments.view`
  - `assessments.create`
  - `assessments.update`
- Added Assessment policy using the established permission architecture.
- Added Store and Update Assessment Form Requests.
- Added Assessment controller with:
  - Assessment listing and search
  - Assessment creation
  - Assessment viewing
  - Assessment editing
  - Assessment updating
- Registered six Assessment resource routes.
- Added Assessment views:
  - Index
  - Create
  - Show
  - Edit
- Integrated Assessments into the Organization account view.
- Assessment records automatically capture the staff member who conducted the assessment.
- Assessment records use soft deletion.
- Assessment deletion is intentionally not exposed because no delete permission exists in the current workflow.

### Verification
- `php artisan route:list --path=assessments`
  - 6 routes registered successfully.
- `php artisan view:cache`
  - Blade templates cached successfully.
- `php artisan test --filter=Assessment`
  - 11 tests passed
  - 14 assertions passed
  - 1.12s

### Status
Assessment application module is complete and verified.

The system can now record, view, search, and update organization assessments while enforcing the established RBAC permissions.

### Next Production Stage
Follow-ups.


## Follow-up Module — Application Milestone Completed

### Scope
The Follow-up module has been implemented and integrated as the organization's historical operational activity record.

### Completed
- Follow-up model and migration operational.
- Organization and Contact relationships operational.
- Recording staff relationship operational.
- Follow-up factory operational.
- Follow-up RBAC permissions integrated into `DatabaseSeeder`.
- Follow-up authorization verified through `FollowUpPolicy`.
- Follow-up resource routes registered:
  - `follow-ups.index`
  - `follow-ups.create`
  - `follow-ups.store`
  - `follow-ups.show`
  - `follow-ups.edit`
  - `follow-ups.update`
- Follow-up application layer operational.
- Follow-up validation enforces organization/contact consistency.
- Follow-up status lifecycle supports:
  - `open`
  - `completed`
  - `cancelled`
- Follow-up records preserve historical activity and do not overwrite organization or contact master information.
- Delete capability is intentionally excluded because Follow-ups are historical operational records.

### Verification
- `php artisan optimize:clear` completed successfully.
- Follow-up routes confirmed with `php artisan route:list --name=follow-ups`.
- `php artisan view:cache` completed successfully.
- Follow-up feature tests passed:
  - 13 tests
  - 18 assertions
- Super Admin RBAC verification passed:
  - `follow-ups.view` = true
  - `follow-ups.create` = true
  - `follow-ups.update` = true
  - `can('create', FollowUp::class)` = true

### Status
Follow-up module is working and verified.

### Next Production Stage
Continue Follow-up UI integration and operational visibility from Organization and Contact accounts, then proceed toward Product Specifications.

## Production Specification Stage — Existing Architecture Discovery

### Status
Milestone in progress. No new Production Specification implementation was created during this checkpoint.

### Purpose
Before continuing the Production Specification stage, the existing application was inspected to determine what backend structures had already been created and to prevent duplicate architecture.

### Discovery
The application already contains a Product Specification foundation:
- `ProductSpecification` model exists.
- `ProductSpecificationArtifact` model exists.
- `product_specifications` migration exists.
- `product_specification_artifacts` migration exists.
- `StoreProductSpecificationRequest` exists.
- `UpdateProductSpecificationRequest` exists.
- `ProductSpecificationPolicy` exists.
- `CreateProductSpecification` and `UpdateProductSpecification` actions exist.
- Factories exist for both specifications and specification artifacts.
- Organization has a `productSpecifications()` relationship.
- Contact has a `productSpecifications()` relationship.
- User tracks specifications created and specification artifacts uploaded.
- Organization show already loads/counts product specifications.
- Feature tests already cover Product Specification and Product Specification Artifact relationships and behavior.

### Artifact Architecture Already Present
Product artifacts are already modeled separately from the specification through:

`ProductSpecification -> artifacts -> ProductSpecificationArtifact`

Artifact records currently support:
- artifact type
- title
- description
- file path
- original filename
- MIME type
- file size
- uploader
- current-version flag
- soft deletion

### Important Finding
The Production Specification stage therefore requires completion and integration of an existing foundation, not creation of a new specification architecture from scratch.

The existing backend also shows that specification capture already includes product-specific fields such as:
- item name
- product type
- description
- unit
- unit price
- material
- material details
- design details
- size details
- branding details
- quality requirements
- special instructions
- status
- notes

### Current Gap Identified
No Product Specification controller or specification views were found in the inspected application structure. File-upload handling was also not yet found in the existing controllers/resources.

Therefore, the next implementation work should focus on completing the application layer around the existing models and migrations, including specification UI, artifact upload handling, authorization integration, organization-account integration, and appropriate verification.

### Scope Decision
Product artifacts are a required part of the Production Specification workflow and must be implemented together with the specification experience rather than treated as a later unrelated feature.

### Next
Inspect the existing Product Specification actions, factories, RBAC definitions, routes, tests, and the exact migration/request behavior before implementing the missing application layer.

## Product Specification Authorization Correction — viewAny / view Separation

### Issue Identified
- Product Specification collection-level authorization was incorrectly calling:
  - `view` with `ProductSpecification::class`
- `ProductSpecificationPolicy::view()` requires a concrete `ProductSpecification` instance.
- Laravel therefore attempted to call the policy method with only the authenticated user and raised:
  - `ArgumentCountError`
  - Too few arguments to `ProductSpecificationPolicy::view()`

### Correction
- Added `viewAny(User $user)` to `ProductSpecificationPolicy`.
- `viewAny` uses the existing `specifications.view` permission.
- Product Specification index authorization now uses:
  - `Gate::authorize('viewAny', ProductSpecification::class)`
- Organization account collection-level checks now use:
  - `@can('viewAny', App\Models\ProductSpecification::class)`
- Individual specification checks continue to use:
  - `@can('view', $specification)`
- Existing permission slugs were not changed.

### Authorization Structure
- `viewAny` — access to Product Specification collections/lists.
- `view` — access to an individual specification.
- `create` — create a specification.
- `update` — modify a specification and manage its production artifacts.

### Verification
- Correction implemented after identifying the policy argument mismatch.
- Blade and authorization verification to continue after the correction.

### Status
Product Specification authorization architecture corrected.

---

## Product Specification Artifacts — Operational Handling Started

### Scope
Production artifacts are being treated as an integral part of the Product Specification workflow.

### Existing Artifact Architecture
`ProductSpecification -> artifacts -> ProductSpecificationArtifact`

Artifact records support:
- artifact type
- title
- description
- private file path
- original filename
- MIME type
- file size
- uploader
- current-version flag
- soft deletion

### Current Operational Handling
The Product Specification account now provides an artifact workspace for:
- viewing artifact history
- identifying the current artifact version
- identifying archived versions
- uploading new artifacts
- downloading stored artifacts
- removing artifacts
- recording uploader and file metadata

### Supported Artifact Types
- design
- material
- logo
- branding
- sample
- size chart
- measurement
- reference
- other

### Supported Files
- JPG
- JPEG
- PNG
- WEBP
- PDF

Maximum upload size:
- 10 MB

### Authorization
Artifact operations use the parent Product Specification authorization:
- View/download requires `specifications.view`.
- Upload/remove requires `specifications.update`.

No separate artifact permission system is introduced at this stage because artifacts are subordinate records of a Product Specification.

### Version Handling
Uploading a new artifact of the same artifact type marks the previous current artifact of that type as non-current before creating the new current artifact.

This preserves the historical artifact record rather than overwriting it.

### Storage
Artifacts are stored on the application's private local storage and are served through an authorized download action rather than public file URLs.

### Status
Artifact handling has entered the active Product Specification production stage.

### Next
Verify the artifact upload/version/download lifecycle with minimal targeted verification, then complete Product Specification integration across Organization and Contact accounts.

## Product Specification Artifact Lifecycle Verification — Completed

- Added one focused feature test covering the complete Product Specification Artifact lifecycle.
- Verified authenticated artifact upload through the production route.
- Verified uploaded artifact metadata and private local storage.
- Verified same-type versioning: uploading a replacement marks the previous artifact as archived (`is_current = false`) while the new artifact becomes current.
- Verified artifact history is preserved rather than overwritten.
- Verified authorized artifact download succeeds through the protected download route.
- Verified artifact removal uses soft deletion.
- Verified the underlying stored file remains available after soft deletion, preserving the physical artifact independently from the database lifecycle.
- No separate artifact permission layer was introduced; artifact operations remain governed by the parent Product Specification permissions.
- Verification scope was intentionally limited to this critical lifecycle rather than broad regression testing.
- Status: Product Specification Artifact handling verified and operational.


## Product Specification Artifact Lifecycle Verification — Completed

- Completed focused verification of the Product Specification Artifact lifecycle.
- Test: `ProductSpecificationArtifactTest`
- Result: 1 test passed, 19 assertions passed.
- Verified artifact upload through the multipart request.
- Verified artifact metadata persistence.
- Verified same-type versioning: uploading a new artifact marks the previous artifact as archived (`is_current = false`) while retaining its record and file.
- Verified current artifact remains marked `is_current = true`.
- Verified authorized artifact download returns successfully.
- Verified artifact soft deletion.
- Verified deleted artifact's physical file remains preserved.
- Verified historical artifact record remains available after the current artifact is removed.
- This confirms the artifact workflow is operational at the application-test level.
- Next verification: perform one real browser upload and confirm the resulting file exists under the private storage directory.

## 2026-09-24 — Organization Intelligence Export HTTP Verification

### Completed
- Added feature coverage for the organization intelligence export workflow.
- Verified an authorized Super Admin can download organization intelligence data.
- Verified the organization account exposes the Export Intelligence Data action.
- Verified the export endpoint returns HTTP 200.
- Verified the response is delivered as a JSON attachment.
- Verified the generated export contains:
  - Organization data
  - Contacts
  - Assessments
  - Follow-ups
  - Product specifications
- Verified product specification artifact files are excluded from the export payload.

### Verification
- Test: `OrganizationIntelligenceExportTest`
- Result: 1 test passed
- Assertions: 17
- Status: Passed

### Feature Status
Organization Intelligence Export is now verified at the HTTP/application level and can be treated as a completed production feature.

### Next Checkpoint
Review the current repository status and commit the completed Organization Intelligence Export work before proceeding to the next production feature.

## 2026-09-24 — Product Specification Artifact Browser Verification

### Completed
- Performed real browser verification of Product Specification artifact upload.
- Confirmed the artifact upload interface works successfully.
- Confirmed the uploaded artifact is persisted through the production application flow.
- Confirmed the artifact is stored in the application's private storage.
- Confirmed the uploaded artifact is available through the Product Specification artifact interface.
- Confirmed the artifact workflow operates correctly outside the automated test environment.

### Verification
- Browser upload: Passed
- Private storage persistence: Passed
- Artifact display/access: Passed

### Feature Status
Product Specification Artifact handling is fully verified through both focused automated testing and real browser interaction.

### Stage Status
Product Specifications are now considered production-complete.

### Next Stage
Begin Phase 5 — Commercial Operations:
- Quotations
- Orders

## 2026-09-24 — Quotation Resource Routing Verification

### Completed
- Registered `QuotationController` as the resource controller for quotations.
- Confirmed the six required routes are registered:
  - quotations.index
  - quotations.create
  - quotations.store
  - quotations.show
  - quotations.edit
  - quotations.update
- Confirmed Blade templates compile successfully with `php artisan view:cache`.
- Confirmed `git diff --check` reports no whitespace errors.

### Status
Quotation HTTP routing foundation verified.

### Next
Build the quotation creation interface and server-side quotation item workflow.

## 2026-09-24 — Quotation Resource Routing Verification

### Completed
- Registered `QuotationController` as the resource controller for quotations.
- Confirmed the six required routes are registered:
  - quotations.index
  - quotations.create
  - quotations.store
  - quotations.show
  - quotations.edit
  - quotations.update
- Confirmed Blade templates compile successfully with `php artisan view:cache`.
- Confirmed `git diff --check` reports no whitespace errors.

### Status
Quotation HTTP routing foundation verified.

### Next
Build the quotation creation interface and server-side quotation item workflow.

## 2026-09-24 — Quotation Foundation Verification

- Corrected `QuotationController` and `QuotationItemController` to use the project's established `Gate::authorize()` authorization convention.
- Verified quotation authorization and commercial foundation with `QuotationTest`.
- Result: **6 tests passed, 26 assertions**.
- Verified behaviors:
  - authorized user can create a draft quotation
  - users without quotation creation permission are denied
  - quotation contacts must belong to the selected organization
  - product specifications must belong to the quotation organization
  - product specification values are snapshotted into quotation items
  - quotation totals are calculated server-side
  - sent quotations cannot be updated
- Status: **PASSED**
- Next checkpoint: complete quotation lifecycle/UI verification and prepare the quotation stage for commit.

## 2026-09-24 — Quotation Item Calculation Regression Correction

- Removed explicit two-decimal rounding from quotation item `line_total` calculation during item creation and update.
- Removed the dedicated rounding-specific feature test because this behavior is not required by the current quotation specification.
- Re-ran the complete `QuotationTest` feature suite.
- Result: **7 tests passed, 29 assertions**.
- Verified remaining quotation behaviors:
  - authorized users can create draft quotations
  - users without quotation creation permission are denied
  - quotation contacts must belong to the selected organization
  - product specifications must belong to the quotation organization
  - product specification values are snapshotted into quotation items
  - quotation totals are calculated server-side
  - inactive organizations cannot receive new quotations
  - sent quotations cannot be updated
- Status: **PASSED**
- Next checkpoint: complete quotation draft UI/lifecycle verification.

## 2026-09-24 — Quotation Commercial Lifecycle Checkpoint

- Completed quotation draft item workflow:
  - draft quotation items can be edited
  - draft quotation items can be removed
  - quotation totals recalculate after item changes
- Completed quotation lifecycle actions:
  - draft → sent
  - draft → cancelled
  - sent → cancelled
- Lifecycle actions are permission-gated using existing quotation permissions.
- Sent quotations remain protected from normal quotation editing.
- Added dedicated send and cancel controller actions and routes.
- Added status-aware lifecycle controls to the quotation show interface.
- Feature verification result: **12 tests passed, 52 assertions**.
- Status: **PASSED**
- Next stage: proceed from Quotations into **Orders**, using accepted quotations as the commercial handoff.


### 2026-09-25 — Quotation Creation Workflow Corrected and Verified

**Phase:** Phase 5 — Commercial Operations

**IMPLEMENTED**
- Corrected quotation creation to operate from an Organization context.
- Organization is preselected and displayed as read-only during quotation creation.
- Removed the previous organization-selection/AJAX flow from the quotation creation form.
- Existing Product Specifications for the selected Organization are displayed directly.
- Staff can select Product Specifications using checkboxes.
- Staff can enter the expected quantity for each selected specification.
- Quotation creation no longer uses Product Specification `unit_price` as the quotation price.
- Newly created quotation items start with:
  - `unit_price = 0`
  - `line_total = 0`
- Product Specification descriptive information is snapshotted into quotation items.
- Actual quotation pricing remains editable from the quotation draft after creation.
- Preserved the existing quotation item pricing and lifecycle functionality.
- Updated quotation feature tests to reflect the corrected commercial workflow.

**VERIFIED**
- `php artisan test tests/Feature/QuotationTest.php`
- Result: **12 tests passed / 64 assertions / 0 failures**
- Verified draft quotation creation with selected Product Specifications and quantities.
- Verified initial quotation item pricing is zero until commercial pricing is entered.
- Verified invalid contact/organization relationships remain rejected.
- Verified Product Specification organization ownership remains enforced.
- Verified existing quotation item update/removal and quotation lifecycle tests continue to pass.

**BUSINESS FLOW NOW VERIFIED**
Organization Account
→ Create Quotation
→ Organization fixed/read-only
→ Select saved Product Specifications
→ Enter required quantities
→ Create Draft Quotation
→ Enter quoted prices
→ Calculate quotation totals
→ Send quotation

**NEXT**
- Perform browser verification of the corrected quotation creation workflow.
- After quotation creation is fully verified in the browser, proceed to the next Commercial Operations unit.
- Orders remain paused until quotation creation is completely verified.

### 2026-09-25 — Quotation-to-Order Customer Workflow Defined
**Phase:** Phase 5 — Commercial Operations

**PLANNED WORKFLOW**
- Organization account will provide access to all quotations previously generated for that organization.
- Staff can send a quotation to one or multiple active contacts belonging to the organization.
- Each quotation recipient will have a separate recipient record with a secure access token and response/delivery history.
- Email will contain a secure link to a customer-facing quotation page on the Oneyard platform.
- Contact can review quotation details and either accept or reject it.
- Rejection will require feedback explaining why the quotation was rejected so staff can make informed adjustments.
- Accepted quotations will present payment options of 30%, 60%, or 80% of the quotation total.
- Payment amounts will be calculated server-side from the quotation total.
- Paystack will be used to collect and verify payment.
- Payment will only be recorded as successful after trusted Paystack verification/webhook processing.
- A verified initial payment will create an Order linked to the accepted Quotation and Organization.
- Orders will retain total, amount paid, and outstanding balance so partial payments remain meaningful.
- Rejected quotations will remain historical records; revisions should create a new quotation rather than overwrite the rejected quotation.

**DOMAIN FLOW**
Organization → Product Specifications → Quotation → Send to Contact(s) → Customer Review → Accept/Reject → If Accepted: 30% / 60% / 80% Payment → Paystack Verification → Order Created

**PLANNED PRODUCTION UNITS**
1. Organization quotation history.
2. Send quotation to one or multiple organization contacts.
3. Customer-facing secure quotation portal with accept/reject and rejection feedback.
4. Paystack payment selection, initialization, verification, and payment records.
5. Verified-payment-to-Order creation with quotation linkage and balance tracking.
6. Browser and end-to-end verification.

**STATUS**
- Workflow defined and approved for production sequencing.
- No implementation or verification is claimed by this entry.
- Orders remain paused until the quotation workflow is completed and verified.

## 2026-09-26 — Quotation Recipient and Public Response Workflow Verified

**Phase:** Phase 5 — Commercial Operations

### IMPLEMENTED
- Added quotation recipient records for sending quotations to selected organization contacts.
- Added secure, unique recipient access tokens.
- Added quotation invitation email delivery through the configured mail system.
- Added recipient delivery, view, and response tracking.
- Added validation preventing quotations from being sent when a selected contact has no email address.
- Added public customer-facing quotation access without staff authentication.
- Added public quotation acceptance workflow.
- Added public quotation rejection workflow with mandatory rejection feedback.
- Added protection against duplicate recipient responses.
- Added quotation status synchronization for multiple recipients:
  - quotation becomes `accepted` when all recipients accept;
  - quotation becomes `rejected` when all recipients reject;
  - quotation remains `sent` when responses are mixed or still pending.
- Added a dedicated public quotation layout that does not depend on an authenticated staff user.

### VERIFIED
Quotation feature regression suite:

```text
php artisan test tests/Feature/QuotationTest.php

20 passed (104 assertions)
0 failures

## 2026-09-26 — Quotation Draft Pricing and Authorization Corrections Verified

**Phase:** Phase 5 — Commercial Operations

### IMPLEMENTED
- Corrected quotation draft creation so Product Specification `unit_price` is not copied into the quotation's commercial price.
- New quotation items now start with `unit_price = 0` and `line_total = 0`.
- Product Specification descriptive information continues to be snapshotted into quotation items.
- Commercial quotation pricing remains independently editable after draft creation.
- Changed quotation recipient sending authorization from `quotations.update` to the dedicated `quotations.send` permission.
- Removed the obsolete direct "Send Quotation" lifecycle action from the quotation lifecycle partial; sending is performed through the selected-contact workflow.
- Updated quotation feature tests to reflect the corrected zero-price draft behavior and `quotations.send` authorization.
- Updated RBAC regression expectations to include the implemented `organizations.export` permission.

### VERIFIED

Focused quotation regression:

php artisan test tests/Feature/QuotationTest.php

20 passed (104 assertions)

Focused RBAC regression:

php artisan test tests/Feature/RbacTest.php

15 passed (117 assertions)

Full application regression:

php artisan test

206 passed (621 assertions)
Duration: 10.06s
0 failures

### CURRENT QUOTATION STATUS
- Quotation creation backend and tests are green.
- Draft commercial pricing is correctly separated from Product Specification pricing.
- Quotation recipient authorization is aligned with `quotations.send`.
- Public quotation acceptance/rejection workflow remains verified.
- Full application regression remains green.

### NEXT
- Perform browser verification of the complete quotation workflow:
  Organization Account → Create Quotation → Select Product Specifications → Enter Quantities → Create Draft → Enter Commercial Prices → Calculate Totals → Select Contacts → Send → Customer Review → Accept/Reject.
- Do not begin Orders or Paystack implementation until quotation browser verification is completed.

## 2026-09-27 — Quotation Payment Flow Direction

### IMPLEMENTED / VERIFIED CONTEXT
- Quotation recipient payment percentage selection is implemented and verified.
- Supported initial payment options are 30%, 60%, and 80%.
- The selected payment percentage and calculated payment amount are persisted on the quotation recipient.
- `amount_paid` is initialized at `0`.

### NEW BUSINESS FLOW DECISION
The quotation recipient must **not** have the quotation treated as commercially completed merely by clicking `Confirm & Continue`.

The intended flow is now:

1. Contact selects the initial payment percentage.
2. Contact clicks `Confirm & Continue`.
3. The system initializes a Paystack transaction for the selected payment amount.
4. The contact is redirected to Paystack to complete payment.
5. The system independently verifies the payment with Paystack.
6. Only after successful payment verification does the system continue the quotation-to-order workflow.
7. The quotation payment/acceptance state is recorded.
8. An Order is created from the confirmed quotation.
9. The confirmed payment is recorded against the appropriate business records.
10. Notifications are sent to:
    - the contact/customer;
    - the staff member who created the quotation;
    - the Super Admin.
11. The confirmed Order then becomes available for the Production workflow.

### SECURITY / BUSINESS RULE
Returning from Paystack must **not** by itself be treated as proof of payment. Server-side payment verification is required before the quotation can proceed to Order and Production.

### IMPLEMENTATION BOUNDARY
The Paystack integration, payment confirmation handling, Order creation, notification workflow, and Production transition have **not yet been implemented** in this milestone.

This entry records the approved workflow direction before implementation begins.


## 2026-09-27 — Paystack Payment Verification Progress

### IMPLEMENTED
- Added Paystack payment transaction persistence through `PaymentTransaction`.
- Implemented server-side Paystack transaction initialization.
- Implemented Paystack callback verification against the stored transaction reference.
- Successful Paystack responses are checked for successful gateway status and matching payment amount before acceptance.
- Failed or mismatched payments remain unaccepted.
- Recipient payment fields are persisted for the selected 30%, 60%, or 80% initial payment.
- Repeated callback handling prevents an already-paid transaction from being processed again.

### VERIFIED
- Paystack transaction initialization tests pass.
- Payment percentage validation tests pass.
- Failed Paystack payment handling passes.
- Wrong payment amount handling passes.
- Quotation recipient acceptance is still intentionally blocked until payment is independently confirmed.

### CURRENT VERIFICATION STATUS
- `tests/Feature/QuotationTest.php`: 24 passed, 2 failed, 146 assertions.
- The remaining failures are the successful payment verification and repeated callback tests.
- Test fixtures now correctly represent Paystack amounts in kobo.
- The remaining issue is the application's payment amount comparison during verification and is being investigated.
- No Order creation, business payment record, notifications, or Production transition has been implemented yet.

### NEXT STEP
- Inspect and correct the payment amount comparison.
- Re-run the quotation feature suite.
- Only after the verification suite passes, proceed to the Order/payment fulfillment stage.

## 2026-09-27 — Paystack Payment Verification VERIFIED

### VERIFIED
- `tests/Feature/QuotationTest.php` now passes completely.
- Result: **26 passed / 157 assertions / 0 failures**.
- Successful Paystack verification marks the `PaymentTransaction` as paid.
- Successful verified payment accepts the quotation recipient.
- Verified payment amount must match the stored transaction amount.
- Failed Paystack transactions remain unaccepted.
- Incorrect payment amounts remain unaccepted.
- Repeated Paystack callbacks do not process the same transaction twice.
- Payment amount comparison now avoids strict floating-point equality issues.

### FULFILLMENT GATE
Paystack initialization and server-side payment verification are now verified.

The next implementation unit is the post-payment fulfillment flow:

1. Create the business `Payment` record from the verified gateway transaction.
2. Create an `Order` from the accepted quotation.
3. Make fulfillment idempotent so repeated callbacks cannot create duplicate payments or orders.
4. Keep the entire payment-record/order transition transactional.
5. Only after successful fulfillment will notifications and Production availability be implemented.

### NOT YET IMPLEMENTED
- Business Payment model/table.
- Order model/table.
- Payment recording from verified Paystack transactions.
- Order creation from accepted quotations.
- Fulfillment transaction/idempotency.
- Customer/staff notifications.
- Production transition.

2026-09-27 — Paystack → Payment → Order Fulfillment VERIFIED

IMPLEMENTED
- Wired successful Paystack payment verification into business fulfillment.
- First successfully verified payment is sufficient to accept a quotation.
- Multi-recipient quotations can become accepted while other recipients remain pending.
- Created business Payment from the verified gateway transaction.
- Created one Order per quotation.
- Generated immutable server-based order number ORD-######.
- Snapshotted quotation items into Order Items.
- Preserved transactional fulfillment and duplicate-order protection.
- Preserved payment amount verification against the recipient's expected payment.
- Repeated fulfillment does not create duplicate Payment or Order records.

VERIFIED
- tests/Feature/QuotationTest.php: 26 passed / 164 assertions / 0 failures.
- tests/Feature/QuotationFulfillmentTest.php: 6 passed / 20 assertions / 0 failures.
- Combined regression: 32 passed / 184 assertions / 0 failures.
- Verified multi-recipient payment flow confirms that one successful payment accepts the quotation and creates the business Payment + Order while another recipient remains pending.

BUSINESS RULE
- The first successfully verified payment is sufficient to accept the quotation.
- Additional recipients do not need to respond before fulfillment begins.

NOT YET IMPLEMENTED
- Payment/order/customer notifications.
- Production workflow transition.
- Production records and production tracking.

NEXT
- Implement fulfillment notifications as the next logical milestone.



## 2026-09-27 — Payment Fulfillment Notifications VERIFIED

### IMPLEMENTED
- Added customer `PaymentConfirmed` Mailable and email view.
- Added internal `OrderCreated` Mailable and email view.
- Fulfillment now registers notifications with `DB::afterCommit()`.
- Customer notification is sent to the quotation recipient who completed the verified payment.
- Internal order notification is sent only to active staff with `orders.view`.
- Replaced per-user permission checks with an RBAC relationship query.
- Existing fulfillment idempotency prevents duplicate notifications.

### VERIFIED
- `tests/Feature/QuotationFulfillmentTest.php`: 8 passed / 28 assertions / 0 failures.
- `tests/Feature/QuotationTest.php` + `tests/Feature/QuotationFulfillmentTest.php`: 34 passed / 194 assertions / 0 failures.
- Verified customer payment confirmation notification.
- Verified authorized internal staff notification.
- Verified unauthorized and inactive staff are excluded.
- Verified repeated fulfillment does not send duplicate notifications.

### NEXT
- Continue fulfillment workflow toward production processing.


## 2026-09-27 — Queued Payment Notifications VERIFIED
### IMPLEMENTED
- Changed post-payment customer and internal order notifications from synchronous mail delivery to queued mail delivery.
- Payment and Order creation remain inside the fulfillment transaction.
- Notifications are queued through `DB::afterCommit()` only after the payment/order transaction commits.
- Customer payment confirmation is queued to the verified payment recipient.
- Internal order notifications are queued only for active staff with `orders.view`.

### VERIFIED
- `PaymentConfirmed` implements `ShouldQueue`.
- `OrderCreated` implements `ShouldQueue`.
- Fulfillment uses `Mail::queue()` rather than synchronous `Mail::send()`.
- The Paystack callback can return the quotation-page redirect without waiting for SMTP delivery.
- Existing queue infrastructure uses the database queue connection.

### DECISION
- Do not expand the notification test suite further at this stage; continue production with the existing fulfillment coverage.

### NEXT
- Begin Order Management production work.

## 2026-09-27 — Order Management Foundation and Fulfillment State CHECKPOINT

### IMPLEMENTED
- Added Order policy with `orders.view`, `orders.create`, `orders.update`, and `orders.approve` authorization rules.
- Added Order listing and Order detail controllers.
- Added Order index and show views using the existing Oneyard UI design.
- Added Order approval route and controller action for pending orders.
- Added permission-aware Orders navigation in the application sidebar.
- Added a permission-aware Orders entry on the dashboard.
- Orders are created automatically by verified quotation payment fulfillment rather than through manual Order creation.
- Order records retain immutable quotation item snapshots for fulfillment.

### VERIFIED
- `php artisan route:list --path=orders` shows:
  - `GET|HEAD orders`
  - `GET|HEAD orders/{order}`
  - `POST orders/{order}/approve`
- `php artisan view:clear` completed successfully.
- `php artisan test --filter=Order` passed: 3 tests / 17 assertions / 0 failures.
- `php artisan test tests/Feature/QuotationFulfillmentTest.php` passed: 8 tests / 30 assertions / 0 failures.
- Verified payment creates the business Payment and Order.
- Verified quotation items are snapshotted into Order items.
- Verified repeated fulfillment does not create duplicate Payment or Order.
- Verified unpaid transactions, unaccepted quotations, and payment amount mismatches cannot create fulfillment records.
- Verified customer and authorized staff fulfillment notifications remain covered.
- Verified repeated fulfillment does not send duplicate notifications.

### CURRENT BUSINESS STATE
- Orders currently begin in `pending` status after verified quotation payment.
- Authorized staff can approve a pending Order.
- The intended operational flow is:
  `pending → approved → in_production → ready → delivered`.
- Initial verified payment is recorded against the quotation/payment transaction, but full Order payment is not considered complete merely because the initial gateway transaction succeeded.
- Remaining balance collection and delivery completion still need to be implemented as part of the fulfillment workflow.

### NEXT
- Add Expected Delivery Days to quotation creation and editing.
- Display the expected delivery period on internal and customer-facing quotations.
- Snapshot the quotation delivery period into the Order when fulfillment creates the Order.
- Add expected delivery date information to the Order.
- Allow appropriately authorized staff to revise the Order delivery estimate during production while preserving the original quotation history.
- Continue into Order lifecycle and Production Management.


## 2026-09-27 — Expected Delivery Days and Quotation Email Failure Handling CHECKPOINT

### IMPLEMENTED
- Added required Expected Delivery Days to quotation creation and editing.
- Added Expected Delivery information to internal and customer-facing quotation views.
- Added `expected_delivery_days` and `expected_delivery_date` to Orders.
- Order fulfillment snapshots the quotation delivery estimate when the Order is created.
- Order expected delivery date is calculated from the Order date using calendar days.
- Added Expected Delivery information to the Order detail workflow.
- Added safe handling for quotation email SMTP transport failures.
- Failed quotation email delivery is logged and does not mark the recipient as sent.
- Failed quotation email delivery does not falsely mark the quotation as sent.
- Added regression coverage for SMTP transport failure handling.

### VERIFIED
- `php artisan test tests/Feature/QuotationTest.php` passed: 27 tests / 170 assertions / 0 failures.
- `php artisan test tests/Feature/QuotationFulfillmentTest.php` passed: 8 tests / 30 assertions / 0 failures.
- Verified quotation delivery information is available to internal and customer-facing quotation views.
- Verified Order fulfillment snapshots quotation delivery days and calculates the expected delivery date.
- Verified quotation email transport failures return a normal application error instead of exposing the Symfony exception page.
- Verified failed recipients retain a null `sent_at` value.
- Verified failed email delivery does not change a draft quotation to sent.

### CURRENT BUSINESS STATE
- Quotations carry an expected delivery period in calendar days.
- Orders snapshot the quotation delivery estimate at fulfillment.
- The Order operational delivery estimate can subsequently be revised without changing the historical quotation.
- Customer quotation email failures are handled as application errors and logged for troubleshooting.

### NEXT
- Refine the Order delivery display to show the remaining time as a badge, for example `15 days to delivery`.
- Use a warmer/urgent badge state when fewer than 5 days remain.
- Continue with authorized Order delivery-estimate updates.

## 2026-09-27 — Order Delivery Estimate Update — VERIFIED

### IMPLEMENTED
- Added `expected_delivery_days` and `expected_delivery_date` support to Orders.
- Added authorized Order edit/update flow for delivery estimates.
- Added `orders.update` policy authorization.
- Added validation requiring delivery days between 1 and 365.
- Added validation requiring a valid expected delivery date.
- Added permission-aware delivery-estimate editing UI.
- Preserved all existing Order fields and lifecycle status during estimate updates.

### VERIFIED
- Authorized staff can update an Order delivery estimate.
- Staff without `orders.update` permission are denied with HTTP 403.
- Invalid delivery-day values are rejected.
- Invalid delivery dates are rejected.
- Existing Order status, financial values, terms, and notes remain unchanged.
- Secure public Order tracking tests remain passing.
- `tests/Feature/OrderTest.php`: 17 passed / 88 assertions.
- Full regression: 238 passed / 804 assertions / 0 failures.

### STATUS
Order delivery-estimate update is implemented and regression-verified.

## 2026-09-28 — Order Coordination & Production Plan Design — DESIGN APPROVED

### DESIGN DECISIONS

The approved Order enters production when an authorized staff member assigns the Order to another active staff member who will become the Order Coordinator for that specific Order.

- Order coordination is assignment-based, not a permanent staff role.
- A staff member must be authorized to assign Orders through the appropriate Order permission.
- Once assigned, the selected staff member becomes the Order Coordinator for that Order.
- The Order Coordinator is responsible for organizing and monitoring execution of the Order.
- The Order Coordinator can create the Production Plan for the assigned Order.
- The Production Plan consists of predefined production activities selected for that particular Order.
- Production activities are selected using checkboxes from a controlled system activity list.
- Quality Control is mandatory for every production plan and cannot be removed.
- Delivery is mandatory for every production plan and cannot be removed.
- Other activities are selected according to the Order, such as Material Purchase, Material Preparation, Cutting, Sewing, Embroidery, Printing, Branding, Assembly, Finishing and Packaging.
- Production activities and Procurement Requirements are separate concepts.
- An activity such as Material Purchase may exist in the Production Plan without itself being a Procurement Requirement.
- A Procurement Requirement is created separately when a specific material or item must be sourced.
- Procurement Requirements are therefore subordinate to the operational need identified by the Order Coordinator, but are not the same thing as production activities.
- The Order Coordinator can create Procurement Requirements for materials/items required by the Production Plan.
- Procurement staff with procurement permissions can independently source against the same Procurement Requirement.
- Multiple sourcing offers can therefore exist for one Procurement Requirement.
- The Procurement Center remains a separate operational domain from Production Activities.
- The coordinator organizes the complete Order while specialized staff perform work according to their permissions.
- Procurement staff manage sourcing activities and offers.
- Production staff perform production activities.
- Quality Control staff perform required quality inspection.
- Delivery staff perform delivery operations.
- The coordinator monitors and coordinates these activities rather than becoming the sole person responsible for performing them.
- The system is designed so the Order does not depend on one person performing every operational task.

### PRODUCTION PLAN STRUCTURE

Approved Order
→ Assign Order Coordinator
→ Create Production Plan
→ Select Required Production Activities
→ Create Procurement Requirements where required
→ Coordinate Production
→ Quality Control
→ Delivery

### REQUIRED ACTIVITIES

Every Production Plan must contain:

- Quality Control
- Delivery

These activities are mandatory and cannot be deselected.

### EXAMPLE ACTIVITIES

The controlled activity list may include:

- Material Purchase
- Material Preparation
- Material Receipt
- Cutting
- Sewing
- Embroidery
- Printing
- Branding
- Assembly
- Finishing
- Packaging
- Quality Control
- Delivery

The final activity list will be implemented as controlled system data rather than allowing arbitrary activity names to be entered for every Order.

### IMPORTANT DOMAIN SEPARATION

Production Activity:
"What needs to happen to complete this Order?"

Procurement Requirement:
"What specific material/item must be sourced, and under what sourcing and financial limits?"

A Production Activity may lead to one or more Procurement Requirements, but the two records must remain separate.

### STATUS

Order Coordination and Production Plan architecture approved for implementation.

This milestone records the approved design only. Implementation and tests are not yet verified.


## 2026-09-28 — Production Coordination & Production Plan — VERIFIED

### IMPLEMENTED
- Added production coordinator assignment workflow for approved Orders.
- Added assignment history with reassignment support; previous assignments are ended rather than overwritten.
- Enforced one active coordinator assignment per Order.
- Coordinator candidates must be active staff with `production.manage`.
- `orders.assign` controls who may assign or reassign coordinators.
- First assignment of an approved Order moves it to `in_production`.
- Added data-driven Production Activities and Production Plan creation.
- Required Production Activities must always be included; current required activities are Quality Control and Delivery.
- Inactive Production Activities cannot be selected.
- Production Plans record the current coordinator.
- Duplicate Production Plans are prevented.
- Only the current Order coordinator with `production.manage` may create a Production Plan.
- Added `coordinator_id` to `production_plans` through a corrective migration after identifying the schema/model mismatch.
- Procurement remains a Production Activity while the detailed Procurement Center workflow remains a separate process.

### VERIFIED
- `tests/Feature/OrderProductionPlanTest.php`: **13 passed / 51 assertions**
- Full regression: **251 passed / 856 assertions / 0 failures**
- Production coordination and Production Plan creation are verified without regression to previously completed modules.


## 2026-09-28 — Production Coordination & Production Plan Foundation — VERIFIED

### IMPLEMENTED
- Added production coordinator assignment workflow for approved Orders.
- Added coordinator reassignment support while preserving previous assignment history.
- Previous coordinator assignments are ended rather than overwritten.
- Enforced one active coordinator assignment per Order.
- Coordinator candidates must be active staff with `production.manage`.
- `orders.assign` controls who may assign or reassign coordinators.
- First coordinator assignment moves an approved Order to `in_production`.
- Added data-driven Production Activities.
- Added Production Plan creation for the current Order coordinator.
- Quality Control and Delivery are required Production Activities.
- Inactive Production Activities cannot be selected.
- Production Plans record the coordinating staff member.
- Duplicate Production Plans are prevented.
- Procurement remains a Production Activity while the detailed Procurement Center workflow remains a separate process.
- Added production activity execution with pending → started → completed state transitions.
- Only the current Order coordinator with `production.manage` may execute Production Plan activities.
- Invalid, repeated, cross-order, and out-of-production activity execution is rejected.

### VERIFIED
- Production coordination and Production Plan creation focused tests passed.
- Production Activity execution focused tests passed: 19 passed / 87 assertions / 0 failures.
- Previous full regression passed: 251 passed / 856 assertions / 0 failures.

### CURRENT CORRECTION
- Database verification confirmed coordinator assignment records are being created correctly and active assignments retain `ended_at = null`.
- The `Order::currentAssignment()` relationship is currently being corrected so the active coordinator is resolved correctly by the Order view.
- This display/relationship correction is not yet marked as verified.

### NEXT
- Verify the corrected `currentAssignment()` relationship.
- Complete the Production Plan creation interface using a modal without changing the existing backend workflow.

## 2026-09-28 — Production Activity Completion Evidence — VERIFIED

### IMPLEMENTED
- Reused the existing `ProductionPlanActivityEvidence` model and `production_plan_activity_evidence` table.
- Completed the existing evidence migration with activity and uploader foreign keys.
- Added MySQL-safe explicit foreign-key names for the evidence table.
- Added the existing `ProductionPlanActivity::evidences()` relationship verification.
- Updated Production Activity completion to require completion evidence.
- Supported JPG, JPEG, PNG, WEBP and PDF evidence files.
- Limited evidence uploads to 10 MB.
- Stored production evidence on the private local filesystem disk.
- Recorded evidence uploader, original filename, MIME type, file size and optional note.
- Kept the existing production state transition requirement: started → completed.
- Evidence creation and activity completion occur within the same database transaction.
- Uploaded evidence is removed if the completion transaction fails.
- Existing completion tests were updated to submit and verify evidence.

### VERIFIED
- Evidence migration completed successfully.
- Production Plan focused suite passed: 19 passed / 92 assertions / 0 failures.
- Evidence persistence, metadata and private file storage are covered by the focused test suite.

### NEXT
- Production Plan execution interface:
  - Collapse Production Plan activities by default.
  - Add expand/collapse chevron control.
  - Add activity completion toggle.
  - Open completion confirmation/evidence modal before completion.
  - Allow coordinator to upload evidence and add a completion note.
  - Preserve completed activity state in the interface.
  - Restrict unmarking to Admin and Super Admin in the next execution unit.

## 2026-09-28 — Production Activity Unmark Modal Flow — IMPLEMENTED

### IMPLEMENTED
- Replaced the legacy browser `window.confirm()` unmark flow.
- Reused the existing completed-activity toggle icon as the Admin/Super Admin unmark trigger.
- Wired the completed toggle to the dedicated Production Unmark modal.
- Added AJAX submission for the unmark modal.
- Preserved the optional unmark reason/note field in the submitted request.
- Added modal Cancel and backdrop close behavior.
- Added Escape-key handling for the unmark modal.
- Restored page scrolling and focus state when the modal closes.
- Updated the activity card immediately after successful unmarking.
- Updated production progress counters after unmarking.
- Preserved existing completion evidence behavior.
- Kept the existing backend authorization and state-transition rules unchanged.

### VERIFIED
- Not yet browser-verified.
- Not yet recorded as a production regression milestone.

### NEXT
- Build the frontend assets.
- Browser-verify Admin/Super Admin unmark flow.
- Verify Coordinator cannot access the unmark action.
- Verify activity returns from Completed to In Progress without removing existing evidence.
- Verify modal closing restores scrolling and page interaction.
- Run the focused Production Plan test suite and full regression after frontend verification.
- Decide separately whether unmark reasons should be persisted as historical activity records.


## 2026-09-28 — Production Activity Unmark Modal Flow — VERIFIED

### IMPLEMENTED
- Replaced the legacy browser `window.confirm()` unmark flow.
- Reused the existing completed-activity toggle icon as the Admin/Super Admin unmark trigger.
- Wired the completed toggle to the dedicated Production Unmark modal.
- Added AJAX submission for the unmark modal.
- Preserved the optional unmark reason/note field in the submitted request.
- Added modal Cancel and backdrop close behavior.
- Added Escape-key handling for the unmark modal.
- Restored page scrolling and focus state when the modal closes.
- Updated the activity card immediately after successful unmarking.
- Updated production progress counters after unmarking.
- Preserved existing completion evidence.
- Kept the backend authorization and state-transition rules unchanged.

### VERIFIED
- Focused unmark/production-plan tests passed: 27 tests / 112 assertions / 0 failures.
- Browser verification passed.
- Admin/Super Admin can open the unmark modal from a completed activity.
- Coordinator does not receive the unmark action.
- Completed activity returns to In Progress after confirmation.
- Existing completion evidence remains preserved.
- Modal close behavior restores normal page interaction.

### DECISION
- Unmark reasons will not be converted into a separate historical activity-log system at this stage.
- Procurement is the next production unit.

### NEXT
- Begin Procurement Center foundation.
- Support both order-linked and independent procurement.
- Allow Order Coordinators to create/update procurement requirements for orders they coordinate.
- Allow Admin/Super Admin to create/update both order-linked and independent procurement.
- Support Product Specification Artifact references and new procurement-specific uploads.
- Support procurement units such as Yard, Piece, Meter, etc.
- Add maximum unit price.
- Add procurement staff offers/bids against open procurement listings.

## 2026-09-29 — Procurement Foundation & Authorization Milestone

### STATUS: IMPLEMENTED + VERIFIED

### Procurement Foundation
Implemented the initial Procurement Center domain foundation.

Procurement architecture supports two procurement sources:

1. Order-linked procurement
   - `order_id` references an existing order.
   - Production coordinators can manage procurement requirements for orders currently assigned to them.
   - Procurement requirements may reference existing Product Specification artifacts.

2. Independent procurement
   - `order_id` is nullable.
   - Used for general materials, stock, packaging, consumables, replacement materials, and other procurement needs not tied to a specific order.
   - Creation and management are restricted to Admin and Super Admin staff with `procurement.manage`.

### Procurement Tables
Implemented and migrated:

- `procurements`
- `procurement_attachments`
- `procurement_offers`

`procurements` includes:
- order linkage
- creator/updater tracking
- item name and description
- quantity
- unit
- maximum unit price
- required-by date
- offer deadline
- priority
- status
- notes
- timestamps
- soft deletes

`procurement_attachments` supports:
- existing Product Specification artifact references
- new procurement-specific uploaded file references
- uploader tracking
- original filename
- MIME type
- file size
- notes

`procurement_offers` supports:
- submitting staff member
- quantity
- unit price
- calculated total price
- offer status
- submission timestamp
- notes

### Models
Implemented:
- `Procurement`
- `ProcurementAttachment`
- `ProcurementOffer`

Relationships added between:
- Order → procurements
- User → procurementOffers
- Procurement → order
- Procurement → creator/updater
- Procurement → attachments
- Procurement → offers
- ProcurementAttachment → ProductSpecificationArtifact
- ProductSpecificationArtifact → procurementAttachments
- ProcurementOffer → submitting User

Procurement uses soft deletes.

### Authorization Boundary
Implemented `ProcurementPolicy`.

Verified rules:

- `procurement.view` controls procurement visibility.
- Admin/Super Admin with `procurement.manage` may create independent procurement.
- Coordinators cannot create independent procurement.
- Coordinators with `procurement.manage` may create procurement for their currently assigned order.
- Coordinators cannot create procurement for another/unassigned order.
- Admin/Super Admin with `procurement.manage` may update any procurement.
- Coordinators may update procurement belonging to their currently assigned order.
- Coordinators cannot update procurement belonging to another order.
- Coordinators cannot update independent procurement.

Important implementation detail:
`createForOrder()` accepts `Order` as its policy subject/context because the authorization decision occurs before the Procurement record exists.

Gate calls for this ability explicitly target `ProcurementPolicy`:

`Gate::forUser($user)->allows('createForOrder', [Procurement::class, $order])`

### Verification
Procurement focused test suite:

- 19 tests passed
- 33 assertions
- 0 failures

The suite verifies:
- order-linked procurement relationships
- independent procurement
- creator/updater tracking
- attachments
- Product Specification artifact references
- new uploaded file references
- procurement offers
- user → procurement offer relationship
- decimal/date casts
- soft deletion
- procurement view authorization
- independent procurement creation authorization
- order-linked coordinator creation authorization
- unauthorized order access
- Admin update authorization
- coordinator update authorization
- cross-order update protection
- independent procurement update protection

### Existing Regression Context
Before Procurement authorization work, the project had reached:

- 265 full-regression tests passed
- 918 assertions
- 0 failures

The Procurement model-focused suite subsequently reached 9 passed / 23 assertions, and the authorization expansion now stands at 19 passed / 33 assertions.

### Current Project Position
The Procurement domain foundation and authorization boundary are complete.

NOT YET IMPLEMENTED:
- ProcurementController
- Procurement Center routes
- Procurement create/update Form Requests
- Procurement Center listing UI
- Procurement detail/show UI
- Order-linked procurement creation workflow
- Independent procurement creation workflow
- Product Specification artifact selection/upload workflow
- Procurement HTTP feature tests
- Procurement offer/bidding workflow
- Supplier module

### Exact Restart Point
Next production unit is:

**Build the Procurement Center backend HTTP workflow.**

Start by inspecting and following the existing controller/route conventions, especially `OrderController`.

Next sequence:

1. Create `ProcurementController`.
2. Add Procurement Center routes.
3. Add create/update validation.
4. Implement order-linked procurement creation.
5. Implement independent procurement creation for Admin/Super Admin.
6. Implement attachment selection/upload handling.
7. Implement Procurement Center listing/show.
8. Add HTTP feature tests.
9. Run focused Procurement tests.
10. Run full regression.
11. Browser-verify the Procurement Center.
12. Append the next milestone to this log.

Do not begin the procurement offer/bidding workflow until the procurement requirement workflow is implemented and verified.


---

## 2026-09-29 — Procurement RBAC Correction

### Issue
A staff user assigned the Production role could still see and access the Procurement Center even after Procurement access was expected to be removed.

### Analysis
The `User::hasPermission()` implementation was verified to resolve permissions directly through the user's assigned roles.

The affected staff user had multiple roles, including:
- sales
- production
- quality-control
- finance
- delivery

The Production role was found to contain `procurement.view`, which granted Procurement visibility and access.

### Implemented
- Removed `procurement.view` from the Production role in `database/seeders/RbacSeeder.php`.
- Retained Procurement permissions for the intended administrative and dedicated Procurement roles.
- Re-ran `RbacSeeder`.
- Because the seeder synchronizes role permissions using `sync()`, the stale `production → procurement.view` relationship was removed from the database.

### Authorization Behavior
Procurement authorization continues to use:
- `ProcurementPolicy::viewAny()`
- `ProcurementPolicy::view()`
- `User::hasPermission('procurement.view')`

The Procurement navigation remains permission-aware through the application's policy authorization.

### Verified
- Production role no longer grants `procurement.view`.
- A Production-assigned staff user no longer has effective `procurement.view`.
- Procurement access is therefore denied to Production staff without another role granting the permission.
- Procurement remains available to roles that legitimately have the permission.
- Full test suite passed.

### Test Result
- 296 tests passed
- 983 assertions
- 0 failures
- Duration: 16.44s

### Status
**IMPLEMENTED + VERIFIED**

---

---

## 2026-09-29 — Procurement Center Index UI

### Implemented
- Added `resources/views/procurements/index.blade.php`.
- Added Procurement Center page structure using the existing application UI components.
- Added permission-aware Create Procurement actions.
- Added search by procurement requirement and order number.
- Added status filtering.
- Added pagination with query-string preservation.
- Added requirement, linked order, quantity, required-by date, priority, and status display.
- Added independent-procurement handling when no order is linked.
- Added permission-aware empty-state Create Procurement action.

### Route Verification
Verified Procurement routes are registered:
- `procurements.index`
- `procurements.create`
- `procurements.store`
- `procurements.show`
- `procurements.edit`
- `procurements.update`
- `orders.procurements.create`
- `orders.procurements.store`

### View Verification
- Procurement view directory contains the index view.
- `php artisan view:clear` completed successfully.
- No compiled-view cache remained after clearing.

### Status
**IMPLEMENTED + VERIFIED**

---


---

## 2026-09-29 — Procurement Center Index Completed

### Implemented
- Added Procurement Center index view at `resources/views/procurements/index.blade.php`.
- Added permission-aware Procurement navigation and Create Procurement actions.
- Added procurement requirement search.
- Added order-number search.
- Added status filtering.
- Added pagination and query-string preservation.
- Added requirement, order, quantity, required-by date, priority, and status display.
- Added independent procurement handling.

### Tested
`tests/Feature/ProcurementPolicyTest.php`

- 17 tests passed
- 46 assertions
- 0 failures

Coverage includes:
- Procurement index authorization
- Procurement navigation authorization
- Requirement-name search
- Order-number search
- Status filtering
- Independent procurement creation
- Order-linked procurement creation
- Procurement update authorization
- Order-link protection

### Browser Verification
- Procurement navigation verified.
- Procurement Center page verified.
- Search verified.
- Status filtering verified.
- Clear/filter behavior verified.
- Procurement table and actions verified.
- Permission boundary verified.

### Status
**IMPLEMENTED + TESTED + BROWSER VERIFIED**

---


## 2026-09-29 — Procurement Commission and Reference Photos Completed

### Implemented
- Added `commission_per_unit` to procurement requirements.
- Added commission validation to procurement create/update requests.
- Added commission display to procurement create/edit/show workflow.
- Added procurement reference-photo uploads with a maximum of 3 photos.
- Restricted reference photos to JPEG, JPG, PNG, and WebP.
- Limited each reference photo to 5 MB.
- Stored procurement reference photos on the private local disk using UUID filenames.
- Added secure procurement attachment delivery through the Procurement authorization policy.
- Added immediate client-side photo previews for Procurement Create.
- Added existing-photo thumbnails and new-photo previews for Procurement Edit.
- Added first reference-photo thumbnails to the Procurement Center.
- Added full reference-photo gallery to the Procurement Show page.
- Kept procurement photo JavaScript in the external Vite-managed JavaScript structure.
- Updated Procurement HTTP test fixtures to include the required commission field.

### Tested
- Procurement policy/HTTP tests passed after updating the shared procurement payload.
- Procurement model tests passed.
- Full application regression passed.
- Procurement photo upload and commission workflow verified.

### Browser Verification
- Create Procurement photo preview verified.
- Edit Procurement existing and newly selected photo previews verified.
- Three-photo limit verified.
- Procurement Center thumbnail verified.
- Procurement Show photo gallery verified.
- Protected photo access verified.
- Commission field workflow verified.

### Status
**IMPLEMENTED + TESTED + BROWSER VERIFIED**

## 2026-09-30 — RECONCILIATION — Test and Git State Verified Against Log

**Type:** DOCUMENTATION / TEST
**Status:** COMPLETED

### Verified (user-run output)
- `php artisan test`: 336 passed / 1125 assertions / 0 failures.
- Organization Intelligence Export committed in 05dafaa.

### Findings
- ProcurementOfferTest exists and passes (29 tests): Procurement Offers backend implemented but was not logged.
  Covered: submission authorization, ready-status and deadline rules, quantity/max-price limits, server-side
  total/user/status/timestamp, owner-only view/update/withdraw, admin view, 3-offer limit per staff.
- Migration add_withdrawal_reason_to_procurement_offers_table created 2026-09-30; dev DB migration state unconfirmed.
- Quotation, payment, order, production and procurement code untracked in git; HEAD 1 commit ahead of origin/main.
- Two log files exist: docs/PRODUCTION_LOG.md (tracked) and docs/production-log.md (untracked).
- docs/TROUBLESHOOTING.md modified; changes not logged.

### Open
- Offer UI, award/selection lifecycle, withdrawal-reason behaviour not yet inspected.
- Quotation/Order browser verification, Paystack webhook, queue worker, commission rules unresolved.

### Next
Checkpoint commit and push, inspect Procurement Offer implementation, design remaining offer lifecycle for approval.

## 2026-09-30 — Procurement Offer Withdrawal Reason — Progress Milestone

### Implemented
- Added `withdrawal_reason` persistence to `procurement_offers`.
- Added `WithdrawProcurementOfferRequest` validation requiring a withdrawal reason with a maximum length of 1000 characters.
- Updated `ProcurementOfferController::withdraw()` to persist both withdrawn status and the validated withdrawal reason.
- Added `withdrawal_reason` to `ProcurementOffer` mass assignment.
- Focused `ProcurementOfferTest` suite currently passes: 31 tests / 113 assertions.
- Withdrawal reason input is currently present directly inside the withdrawal forms on the Procurement and Offer detail views.

### Current UI State
- The withdrawal reason is still displayed inline beside the Withdraw action.
- This is being refined to a modal workflow so the offer cards remain compact.
- The current Blade withdrawal block was inspected and confirmed in `resources/views/procurements/show.blade.php`.

### Next Step
- Replace the inline withdrawal reason form with a modal workflow triggered by the Withdraw button.
- Apply the same modal workflow to `resources/views/procurements/offers/show.blade.php`.
- Preserve authorization, CSRF protection, validation errors, and the existing withdrawal route/controller.
- Then run Blade compilation and the Procurement Offer tests, followed by broader Procurement verification and browser verification.


---

## 2026-09-30 — Procurement Offer Withdrawal Modal Refinement VERIFIED

### Completed
Replaced the inline Procurement Offer withdrawal forms with a reusable modal-based withdrawal workflow on:

- `resources/views/procurements/show.blade.php`
- `resources/views/procurements/offers/show.blade.php`

Added dedicated JavaScript:

- `resources/js/procurements/offers.js`

Registered the module through:

- `resources/js/app.js`

### Behavior
- Withdrawal action remains permission-controlled by the existing `withdraw` policy.
- Existing `procurements.offers.withdraw` POST route preserved.
- CSRF protection preserved.
- Withdrawal reason remains required and limited to 1000 characters.
- Validation errors reopen the withdrawal modal.
- The correct offer is restored after validation failure.
- Cancel, backdrop, and Escape close the modal.
- Focus returns to the triggering button after closing.
- No AJAX/fetch introduced; existing server-side POST/redirect workflow preserved.

### Verification
- Blade/application cache compilation passed.
- `ProcurementOfferTest` passed.
- Procurement tests passed.
- Procurement policy tests passed.
- JavaScript production build passed.
- Browser verification passed.

### Status
**VERIFIED**

### Next Logical Unit
Procurement Offer Award/Selection lifecycle.

The withdrawal workflow is now considered closed. No further withdrawal UI changes should be introduced unless a later lifecycle requirement exposes a concrete defect.
