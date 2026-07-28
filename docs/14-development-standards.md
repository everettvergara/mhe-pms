# 14 - Development Standards

# Purpose

This document defines the mandatory software development standards for
the MHE Preventive Maintenance Automation System.

All developers and AI coding assistants shall strictly follow these
standards throughout the project to ensure consistency, maintainability,
scalability, and code quality.

This document is considered the official implementation guideline.

------------------------------------------------------------------------

# Development Philosophy

The application shall be:

-   Simple
-   Clean
-   Consistent
-   Modular
-   Maintainable
-   Secure
-   Testable

The objective is long-term maintainability rather than short-term
development speed.

------------------------------------------------------------------------

# AI Development Workflow

Before implementing any feature:

1.  Read every document under the `/docs` folder.
2.  Understand the business workflow.
3.  Review existing code before creating new code.
4.  Reuse existing components whenever possible.
5.  Do not duplicate functionality.

If documentation and implementation conflict, the documentation shall be
considered the source of truth until updated.

------------------------------------------------------------------------

# Development Strategy

Development shall be completed module-by-module.

Recommended order:

1.  Authentication
2.  Dashboard
3.  Master Files
4.  Preventive Maintenance
5.  Action Plans
6.  Reports
7.  Activity Logs
8.  Final Testing

Each module shall be completed before proceeding to the next.

Do not partially implement multiple modules simultaneously.

------------------------------------------------------------------------

# Standard Module Structure

Every business module shall contain:

-   Migration
-   Model
-   Controller
-   Service
-   Form Request
-   Policy
-   Routes
-   Views
-   Seeder
-   Factory
-   Feature Tests

Unit Tests shall be added where business logic exists.

------------------------------------------------------------------------

# Controller Standards

Controllers shall:

-   Handle HTTP requests only.
-   Perform authorization.
-   Delegate business logic to Services.
-   Return Views or JSON responses.

Avoid placing business logic inside Controllers.

Target maximum size:

Approximately 200 lines per Controller.

------------------------------------------------------------------------

# Service Layer

All business logic shall reside in Services.

Examples

-   PMS submission
-   Action Plan status updates
-   FAST confirmation
-   Dashboard calculations

Services shall be reusable.

------------------------------------------------------------------------

# Model Standards

Models shall contain:

-   Relationships
-   Accessors
-   Mutators
-   Scopes

Avoid placing business workflows inside Models.

------------------------------------------------------------------------

# Validation

All validation shall use Laravel Form Requests.

Controllers shall not contain validation rules.

Validation shall be centralized and reusable.

------------------------------------------------------------------------

# Authorization

Use Laravel Policies.

Never rely solely on Blade templates to secure functionality.

Every update, delete, confirm, and reject action shall be authorized.

------------------------------------------------------------------------

# Database Standards

Use Laravel Migrations exclusively.

Do not modify the database manually.

All schema changes shall be version controlled.

Use database transactions whenever multiple tables are updated.

------------------------------------------------------------------------

# Enumerations

Do not hardcode status strings.

Create PHP Enums for:

-   PMS Status
-   Action Plan Status
-   Progress Status
-   User Status
-   Roles

All comparisons shall use Enums.

------------------------------------------------------------------------

# UI Standards

Reuse common Blade components.

Examples

-   Cards
-   Tables
-   Toolbars
-   Status Badges
-   Buttons
-   Form Controls

Avoid duplicating UI code.

------------------------------------------------------------------------

# List Pages

Every List Page shall:

-   Use server-side pagination.
-   Use server-side searching.
-   Use server-side filtering.
-   Support sortable columns.
-   Display 50 records by default.

------------------------------------------------------------------------

# Data Entry Pages

Every Data Entry Page shall:

-   Follow the standard layout.
-   Group fields logically.
-   Display audit information.
-   Preserve a consistent button layout.

------------------------------------------------------------------------

# Status Badges

All statuses shall use reusable Blade components.

Never duplicate badge HTML.

------------------------------------------------------------------------

# Transactions

Whenever multiple records are modified:

Wrap operations inside database transactions.

Example

-   PMS Submission
-   Action Plan Confirmation
-   Status Synchronization

Rollback on failure.

------------------------------------------------------------------------

# Error Handling

Display user-friendly messages.

Log technical exceptions.

Do not expose stack traces in production.

------------------------------------------------------------------------

# Logging

Log all significant business actions.

Examples

-   Create
-   Update
-   Delete
-   Submit
-   Confirm
-   Reject
-   Cancel

Logs shall support future auditing.

------------------------------------------------------------------------

# Code Style

Follow PSR-12.

Use:

-   Type declarations
-   Return types
-   Constructor Property Promotion
-   Dependency Injection

Avoid:

-   Global functions
-   Static helper abuse
-   Duplicate logic

------------------------------------------------------------------------

# Naming Conventions

Classes

PascalCase

Methods

camelCase

Variables

camelCase

Database Tables

snake_case

Blade Files

kebab-case

Routes

dot.notation

------------------------------------------------------------------------

# Performance Standards

Always use eager loading.

Avoid N+1 queries.

Paginate large datasets.

Cache lookup data where appropriate.

Optimize dashboard queries using aggregate SQL.

------------------------------------------------------------------------

# Frontend Standards

Use:

-   Bootstrap 5
-   Bootstrap Icons
-   Chart.js
-   Vite

Avoid unnecessary third-party JavaScript libraries.

------------------------------------------------------------------------

# Testing Standards

Every completed module shall include:

-   Feature Tests
-   Seeder verification
-   Authorization testing
-   Validation testing

Business workflows shall be tested before moving to the next module.

------------------------------------------------------------------------

# Git Workflow

Recommended workflow:

Feature Branch

↓

Development

↓

Testing

↓

Main

Commit messages shall be meaningful.

Example

    feat: implement preventive maintenance module

    fix: action plan status synchronization

    refactor: reusable status badge component

------------------------------------------------------------------------

# Documentation Standards

Whenever functionality changes:

-   Update `/docs`
-   Update migrations if necessary
-   Update seeders if necessary
-   Update business rules
-   Update validation rules

Documentation shall remain synchronized with implementation.

------------------------------------------------------------------------

# Code Review Checklist

Before marking a feature complete:

-   Business rules implemented
-   Validation completed
-   Authorization completed
-   Activity logging added
-   Audit fields displayed
-   UI follows standards
-   Tests passing
-   Documentation updated

------------------------------------------------------------------------

# Definition of Done

A feature is considered complete only when:

-   Functional
-   Validated
-   Authorized
-   Logged
-   Tested
-   Documented
-   Seeded
-   Reviewed

No feature shall be considered complete if any of the above items are
missing.

------------------------------------------------------------------------

# Final Principle

The objective is not merely to produce working code.

The objective is to produce code that is:

-   Predictable
-   Maintainable
-   Consistent
-   Secure
-   Easy to extend

Every implementation decision shall prioritize clarity, simplicity, and
long-term maintainability.
