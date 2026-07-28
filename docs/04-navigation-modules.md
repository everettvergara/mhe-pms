# 04 - Navigation & Modules

## Purpose

This document defines the complete application navigation, module
hierarchy, and standard behavior for every module.

Every functional module shall follow the same navigation pattern and
user experience.

------------------------------------------------------------------------

# Navigation Principle

Every module shall consist of:

1.  List Page
2.  Data Entry Page

Users shall always enter through the List Page.

Navigation Flow

    Sidebar Menu

    ↓

    List Page

    ↓

    Search / Filter / Sort

    ↓

    Click Existing Record

    OR

    New

    ↓

    Data Entry Page

    ↓

    Save / Update / Submit / Confirm

    ↓

    Return to List Page

When returning to the List Page, preserve:

-   Current Page
-   Search Keyword
-   Filters
-   Sort Order
-   Scroll Position (where practical)

------------------------------------------------------------------------

# Standard List Page

Every List Page shall include:

## Header

-   Module Title
-   Breadcrumb

## Toolbar

-   New
-   Search
-   Filters
-   Refresh
-   Export (when applicable)

## Data Grid

Default Page Size

50 records

Available Page Sizes

-   25
-   50 (Default)
-   100
-   250

Features

-   Server-side Search
-   Server-side Filtering
-   Sortable Columns
-   Pagination
-   Responsive Table
-   Sticky Header
-   Status Badges

Clicking a table row shall open the Data Entry Page.

------------------------------------------------------------------------

# Standard Data Entry Page

Every Data Entry Page shall include:

Header

-   Page Title
-   Breadcrumb
-   Status Badge

Body

Logical Bootstrap Cards

Footer Buttons (module dependent)

-   Save
-   Update
-   Save Draft
-   Forward for Approval
-   Confirm
-   Reject
-   Cancel Transaction
-   Delete
-   Back to List

Display Audit Information.

------------------------------------------------------------------------

# FAST Administrator Menu

Dashboard

Transactions

-   Preventive Maintenance
-   Action Plans
-   Action Plan Confirmation

Masters

-   Suppliers
-   Sites
-   MHE Types
-   Checklist Groups
-   Checklist Items

Administration

-   Users
-   User Access / Roles

Reports

-   PMS Summary Report
-   Checklist Findings Report
-   Action Plan Report
-   Pending FAST Confirmation Report
-   Supplier Compliance Report

Each report is listed directly in the sidebar under Reports (no intermediate report picker page).

System

-   Activity Logs
-   My Profile
-   Change Password

Logout

------------------------------------------------------------------------

# Supplier Menu

Dashboard

Transactions

-   Preventive Maintenance
-   Action Plans

System

-   My Profile
-   Change Password

Logout

Supplier users shall not have access to Administration, Master Files,
Reports, or Activity Logs.

------------------------------------------------------------------------

# MODULES

## 1. Dashboard

Pages

-   Dashboard

Purpose

Display KPIs, Charts, Pending Work Queues, and Recent Activities.

------------------------------------------------------------------------

## 2. Users

Pages

-   User List
-   User Data Entry

Functions

-   Create
-   Edit
-   Activate
-   Deactivate
-   Reset Password

------------------------------------------------------------------------

## 3. User Access / Roles

Pages

-   Role List
-   Role Data Entry

Purpose

Maintain application roles and menu access.

------------------------------------------------------------------------

## 4. Suppliers

Pages

-   Supplier List
-   Supplier Data Entry

Purpose

Maintain accredited suppliers.

------------------------------------------------------------------------

## 5. Sites

Pages

-   Site List
-   Site Data Entry

Purpose

Maintain FAST sites.

------------------------------------------------------------------------

## 6. MHE Types

Pages

-   MHE Type List
-   MHE Type Data Entry

Purpose

Maintain Material Handling Equipment types.

------------------------------------------------------------------------

## 7. Checklist Groups

Pages

-   Checklist Group List
-   Checklist Group Data Entry

Purpose

Maintain checklist categories.

------------------------------------------------------------------------

## 8. Checklist Items

Pages

-   Checklist Item List
-   Checklist Item Data Entry

Purpose

Maintain checklist questions under each group.

------------------------------------------------------------------------

## 9. Preventive Maintenance

Pages

-   PMS List
-   PMS Data Entry
-   PMS View

Functions

-   Save Draft
-   Update Draft
-   Submit
-   Cancel

Status

-   Draft
-   With Findings
-   No Findings
-   Cancelled

------------------------------------------------------------------------

## 10. Action Plans

Pages

-   Action Plan List
-   Action Plan Data Entry
-   Action Plan View

Functions

-   Create
-   Update
-   Add Progress Comments

Status

-   Pending
-   Waiting for FAST Confirmation
-   Confirmed
-   Rejected
-   Cancelled

------------------------------------------------------------------------

## 11. Action Plan Confirmation

Administrator Only

Pages

-   Confirmation List
-   Confirmation Details

Functions

-   Review
-   Confirm
-   Reject

Purpose

FAST verifies supplier-completed Action Plans.

------------------------------------------------------------------------

## 12. Reports

Pages

-   PMS Summary Report
-   Checklist Findings Report
-   Action Plan Report
-   Pending FAST Confirmation Report
-   Supplier Compliance Report

Navigation

Each report is accessible directly from the sidebar under Reports.

Exports

-   Excel
-   PDF

------------------------------------------------------------------------

## 13. Activity Logs

Pages

-   Activity Log List
-   Activity Log Details

Purpose

View system audit history.

------------------------------------------------------------------------

## 14. My Profile

Pages

-   My Profile

Functions

-   Update Personal Information

------------------------------------------------------------------------

## 15. Change Password

Pages

-   Change Password

Functions

-   Update Password

------------------------------------------------------------------------

# Module Relationships

Suppliers

↓

Supplier Users

↓

Preventive Maintenance

↓

Checklist Items

↓

Findings

↓

Action Plans

↓

FAST Confirmation

↓

Reports

------------------------------------------------------------------------

# Standard Module Rules

Every module shall:

-   Have a List Page.
-   Have a Data Entry Page.
-   Support server-side pagination.
-   Support keyword search.
-   Support advanced filters.
-   Support sortable columns.
-   Display status badges.
-   Display audit fields.
-   Enforce role-based authorization.
-   Follow the common UI/UX standards.

No module shall use modal dialogs for Create/Edit operations.

Dedicated Data Entry pages shall always be used.

------------------------------------------------------------------------

# Navigation Consistency

Users shall always know:

-   Where they are.
-   How to return.
-   What actions are available.
-   The current status of the record.

The application shall maintain a consistent navigation experience across
all modules.
