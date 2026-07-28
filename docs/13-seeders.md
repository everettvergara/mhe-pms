# 13 - Seeders

# Purpose

This document defines the required database seeders for the MHE
Preventive Maintenance Automation System.

The objective is to ensure that immediately after running migrations and
seeders, the application is fully usable for demonstration, testing,
development, and User Acceptance Testing (UAT).

------------------------------------------------------------------------

# Seeder Execution Order

Execute seeders in the following order:

1.  Role Seeder
2.  Supplier Seeder
3.  Site Seeder
4.  MHE Type Seeder
5.  Checklist Group Seeder
6.  Checklist Item Seeder
7.  User Seeder
8.  Supplier Site Assignment Seeder
9.  PMS Seeder
10. Action Plan Seeder
11. Action Plan Comment Seeder

------------------------------------------------------------------------

# 1. Role Seeder

Create the default system roles.

  Role
  --------------------
  FAST Administrator
  Supplier User

These roles shall not be deleted by seeders.

------------------------------------------------------------------------

# 2. Supplier Seeder

Create sample suppliers.

  Code   Supplier
  ------ ----------
  TOY    Toyota
  GBL    Global
  BOE    Boeing

Status

-   Active

------------------------------------------------------------------------

# 3. Site Seeder

Create sample FAST sites.

-   FAST Manila
-   FAST Laguna
-   FAST Cebu
-   FAST Clark
-   FAST Davao

Status

-   Active

------------------------------------------------------------------------

# 4. MHE Type Seeder

Create sample MHE Types.

-   Forklift
-   Reach Truck
-   Pallet Truck
-   Electric Stacker

Status

-   Active

------------------------------------------------------------------------

# 5. Checklist Group Seeder

Create default checklist groups.

1.  CHARGER
2.  STEERING MOTOR
3.  MAGNETIC CONTACTOR

Sequence shall follow the order above.

------------------------------------------------------------------------

# 6. Checklist Item Seeder

Populate each checklist group.

## CHARGER

-   Timer Function
-   Looseness in Connecting Parts
-   Function Voltage Measurement

## STEERING MOTOR

-   Rotation Sound
-   Looseness in Connecting Parts
-   Insulation Resistance
-   Brush / Spring Wear

## MAGNETIC CONTACTOR

-   Operating Condition and Timing
-   Looseness of Oil Mounting Parts
-   Main Circuit Lead Wire Looseness

------------------------------------------------------------------------

# 7. User Seeder

Create demonstration users.

## Super Administrators

All super administrators use FAST Administrator role and `is_super_admin = true`.
Password for each account equals the username.

| Username | Name | Email |
|----------|------|-------|
| admin | FAST Administrator | admin@example.com |
| andyF | Andy F | andyF@example.com |
| CharlesM | Charles M | CharlesM@example.com |
| markt | Mark T | markt@example.com |

Status: Active

------------------------------------------------------------------------

## Supplier Users

Toyota User

Global User

Boeing User

Each user shall:

-   Belong to the appropriate Supplier
-   Have Active status
-   Be assigned to one or more Sites

------------------------------------------------------------------------

# 8. Supplier Site Assignment Seeder

Assign supplier users to sample sites.

Example

Toyota

-   FAST Manila
-   FAST Laguna

Global

-   FAST Cebu

Boeing

-   FAST Clark
-   FAST Davao

------------------------------------------------------------------------

# 9. PMS Seeder

Generate realistic PMS records.

Create records with mixed statuses.

-   Draft
-   No Findings
-   With Findings
-   Cancelled

Generate multiple records per supplier.

Populate:

-   Technician
-   Site
-   MHE Type
-   Unit Number
-   Serial Number
-   Checklist Answers

Ensure at least one PMS contains findings.

------------------------------------------------------------------------

# 10. Action Plan Seeder

Generate Action Plans for PMS findings.

Include different statuses.

-   Pending
-   Waiting for FAST Confirmation
-   Confirmed
-   Rejected
-   Cancelled

Populate:

-   Title
-   Description
-   Responsible Person
-   Timeline
-   Audit Fields

------------------------------------------------------------------------

# 11. Action Plan Comment Seeder

Create realistic progress history.

Examples

-   Initial investigation completed.
-   Replacement parts ordered.
-   Component replaced.
-   Functional testing completed.
-   Ready for FAST verification.

Progress Status values

-   Pending
-   Implemented
-   Cancelled

Generate multiple comments for selected Action Plans.

------------------------------------------------------------------------

# Seeder Standards

Seeders shall:

-   Be idempotent where practical.
-   Use Laravel Factories for large datasets.
-   Generate realistic timestamps.
-   Preserve referential integrity.
-   Avoid duplicate key violations.

------------------------------------------------------------------------

# Development Dataset

The default dataset shall provide sufficient records to demonstrate:

-   Dashboard KPIs
-   Dashboard charts
-   Search
-   Filtering
-   Pagination
-   Reports
-   FAST confirmation workflow
-   Supplier workflow

------------------------------------------------------------------------

# Testing Dataset

Factories should support generation of:

-   100 PMS
-   1,000 PMS
-   10,000 PMS

for performance testing without code changes.

------------------------------------------------------------------------

# Demo Credentials

Provide sample credentials in development documentation only.

Super Administrators (password = username)

-   admin
-   andyF
-   CharlesM
-   markt

Supplier Users

Username: toyota — Password: password

Username: global — Password: password

Username: boeing — Password: password

These credentials shall never be used in production.

------------------------------------------------------------------------

# Seeder Goal

Running:

    php artisan migrate:fresh --seed

shall produce a fully functional demonstration environment with
representative data covering every major workflow in Version 1.0.
