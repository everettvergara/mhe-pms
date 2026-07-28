# 06 - Master Files

## Purpose

Master Files provide the reference data used throughout the Preventive
Maintenance Automation System.

Only FAST Administrators may maintain Master Files unless otherwise
specified.

Every Master File shall follow the standard module design defined in
previous documents.

------------------------------------------------------------------------

# Standard Module Design

Every Master File shall contain:

-   List Page
-   Data Entry Page

## List Page

Default page size:

-   50 records

Supported page sizes:

-   25
-   50
-   100
-   250

Features

-   New Button
-   Search
-   Advanced Filters
-   Refresh
-   Sortable Columns
-   Pagination
-   Responsive Table
-   Status Badge

Clicking a record opens the Data Entry Page.

## Data Entry Page

Contains:

-   General Information
-   Audit Information

Buttons

-   Save
-   Update
-   Delete (Soft Delete)
-   Back

Deleted records shall not be physically removed.

------------------------------------------------------------------------

# 1. User Management

Purpose

Maintain system users.

Pages

-   User List
-   User Data Entry

Fields

-   Username
-   Full Name
-   Email Address
-   Password
-   Confirm Password
-   Role
-   Assigned Suppliers (one or more, Supplier Users)
-   Super Admin
-   Assigned Sites (Supplier Users)
-   Status

Status

-   Active
-   Inactive

Business Rules

-   Username must be unique.
-   Email must be unique.
-   Supplier is required for Supplier Users (at least one).
-   Assigned Sites are required for Supplier Users (at least one).
-   Super Admin users have no supplier or site assignments.
-   Super Admin users can access all suppliers and sites in listings.
-   Non-super-admin users see only records matching their supplier and site assignments.
-   Supplier is blank for Super Admin and FAST Administrators without assignments.
-   Inactive users cannot login.

Search

-   Username
-   Name
-   Email

Filters

-   Role
-   Status
-   Supplier

------------------------------------------------------------------------

# 2. User Access / Roles

Purpose

Maintain application roles.

Pages

-   Role List
-   Role Data Entry

Default Roles

-   FAST Administrator
-   Supplier User

Permissions

Dashboard

Masters

Transactions

Reports

Administration

Business Rules

Version 1.0 supports only the two standard roles.

------------------------------------------------------------------------

# 3. Suppliers

Purpose

Maintain accredited suppliers.

Pages

-   Supplier List
-   Supplier Data Entry

Fields

-   Supplier Code
-   Supplier Name
-   Contact Person
-   Contact Number
-   Email Address
-   Address
-   Status

Business Rules

Supplier Code must be unique.

Search

-   Code
-   Supplier Name

Filters

-   Status

Sample Data

-   Toyota
-   Global
-   Boeing

------------------------------------------------------------------------

# 4. Sites

Purpose

Maintain FAST warehouse or operational sites.

Pages

-   Site List
-   Site Data Entry

Fields

-   Site Code
-   Site Name
-   Description
-   Status

Business Rules

Site Code must be unique.

Search

-   Site Code
-   Site Name

Filters

-   Status

Sample Data

-   FAST Manila
-   FAST Laguna
-   FAST Cebu

------------------------------------------------------------------------

# 5. MHE Types

Purpose

Maintain Material Handling Equipment categories.

Pages

-   MHE Type List
-   MHE Type Data Entry

Fields

-   Code
-   Description
-   Status

Sample Data

-   Forklift
-   Reach Truck
-   Pallet Truck
-   Electric Stacker

------------------------------------------------------------------------

# 6. Checklist Groups

Purpose

Maintain maintenance checklist sections.

Pages

-   Checklist Group List
-   Checklist Group Data Entry

Fields

-   Group Name
-   Sequence
-   Status

Business Rules

Sequence determines display order.

Example

CHARGER

STEERING MOTOR

MAGNETIC CONTACTOR

------------------------------------------------------------------------

# 7. Checklist Items

Purpose

Maintain checklist questions.

Pages

-   Checklist Item List
-   Checklist Item Data Entry

Fields

-   Checklist Group
-   Sequence
-   Checklist Description
-   Status

Business Rules

Each Checklist Item belongs to one Checklist Group.

Sequence determines display order inside the group.

Example

Group

CHARGER

Items

-   Timer Function
-   Looseness in Connecting Parts
-   Function Voltage Measurement

------------------------------------------------------------------------

# Common Validation Rules

Every Master File shall:

-   Require mandatory fields.
-   Prevent duplicate key values.
-   Validate maximum field lengths.
-   Validate email addresses where applicable.
-   Display user-friendly validation messages.

------------------------------------------------------------------------

# Audit Fields

Every Master File table shall contain:

-   Created By
-   Updated By
-   Created At
-   Updated At
-   Deleted At (Soft Delete)

------------------------------------------------------------------------

# Search & Filters

All Master File List Pages shall support:

Search

-   Keyword

Filters

-   Status

Additional filters when applicable.

------------------------------------------------------------------------

# Exports

Where applicable, List Pages shall support:

-   Excel
-   PDF

------------------------------------------------------------------------

# Activity Logging

Log:

-   Create
-   Update
-   Delete
-   Restore (future)
-   Status Change

Capture:

-   User
-   Module
-   Action
-   Date/Time

------------------------------------------------------------------------

# Relationships

Suppliers

↓

Supplier Users

↓

Assigned Sites

↓

Preventive Maintenance

Checklist Groups

↓

Checklist Items

↓

Preventive Maintenance Details

------------------------------------------------------------------------

# Seeder Requirements

Seed the application with demonstration data for:

-   Roles
-   Users
-   Suppliers
-   Sites
-   MHE Types
-   Checklist Groups
-   Checklist Items

The application shall be immediately usable after running migrations and
seeders.
