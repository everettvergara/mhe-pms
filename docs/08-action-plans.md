# 08 - Action Plans

# Purpose

The Action Plan module manages corrective actions for checklist items
marked **No Good** during Preventive Maintenance inspections.

It provides a controlled workflow where Suppliers propose and implement
corrective actions while FAST verifies and confirms completion.

The module maintains complete history and accountability.

------------------------------------------------------------------------

# Actors

Primary

-   Supplier User

Secondary

-   FAST Administrator

------------------------------------------------------------------------

# Module Pages

Supplier

-   Action Plan List
-   Action Plan Data Entry
-   Action Plan View

FAST Administrator

-   Action Plan List
-   Action Plan Confirmation List
-   Action Plan Confirmation Details

------------------------------------------------------------------------

# Relationships

Preventive Maintenance

↓

PMS Detail (Checklist Item)

↓

Action Plans

↓

Action Plan Comments

One Checklist Finding may contain multiple Action Plans.

One Action Plan may contain multiple Comments.

------------------------------------------------------------------------

# Workflow

``` text
Checklist Item = No Good

↓

Supplier Creates Action Plan

↓

Pending

↓

Supplier Adds Progress Comments

↓

Supplier Marks Implemented

↓

Waiting for FAST Confirmation

↓

FAST Reviews

     ┌───────────────┐
     │               │
 Confirm          Reject
     │               │
     ▼               ▼
Confirmed      Supplier Updates
                   │
                   └──────► Waiting for FAST Confirmation
```

------------------------------------------------------------------------

# Action Plan Status

-   Pending
-   Waiting for FAST Confirmation
-   Confirmed
-   Rejected
-   Cancelled

Status is managed by workflow.

Users shall not arbitrarily edit status.

------------------------------------------------------------------------

# Action Plan List

Purpose

Display Action Plans requiring attention.

Supplier Users

View only their supplier records.

FAST Administrators

View all records.

------------------------------------------------------------------------

## Toolbar

-   New Action Plan
-   Search
-   Filters
-   Refresh
-   Export

------------------------------------------------------------------------

## Default Page Size

50

Options

-   25
-   50
-   100
-   250

------------------------------------------------------------------------

## Search

-   PMS Number
-   Site
-   Supplier
-   Responsible
-   Action Plan

------------------------------------------------------------------------

## Filters

-   Supplier
-   Site
-   Status
-   Date Range
-   MHE Type

------------------------------------------------------------------------

## Grid Columns

-   Action Plan No.
-   PMS No.
-   Supplier
-   Site
-   Checklist Group
-   Checklist Item
-   Responsible
-   Timeline
-   Status
-   Updated At

Clicking a row opens the Data Entry page.

------------------------------------------------------------------------

# Action Plan Data Entry

## General Information

-   PMS Number (Read-only)
-   Site (Read-only)
-   Supplier (Read-only)
-   Checklist Group (Read-only)
-   Checklist Item (Read-only)
-   Finding Remarks (Read-only)

------------------------------------------------------------------------

## Action Plan

Fields

-   Action Plan Title \*
-   Description \*
-   Responsible Person \*
-   Timeline From \*
-   Timeline To \*

Status (Read-only)

------------------------------------------------------------------------

## Progress Comments

Every Action Plan contains a chronological timeline.

Fields

-   Comment \*
-   Progress Status \*

Progress Status

-   Pending
-   Implemented
-   Cancelled

Supplier Users may append comments only.

Existing comments cannot be edited or deleted.

------------------------------------------------------------------------

# Supplier Buttons

Draft records are not applicable.

Buttons

-   Save
-   Update
-   Add Progress Comment
-   Mark as Implemented
-   Cancel Action Plan
-   Back

Business Rules

Mark as Implemented

System changes Action Plan Status to:

Waiting for FAST Confirmation

Updates PMS Header Action Plan Status accordingly.

------------------------------------------------------------------------

# FAST Confirmation

## Confirmation List

Purpose

Display Action Plans awaiting verification.

Columns

-   Action Plan No.
-   PMS No.
-   Supplier
-   Site
-   Checklist Item
-   Implemented Date
-   Responsible
-   Status

Actions

-   Review

------------------------------------------------------------------------

## Confirmation Details

Display

-   PMS Information
-   Checklist Finding
-   Action Plan
-   Progress Timeline
-   Audit Information

FAST Decision

Fields

-   Confirmation Remarks \*

Buttons

-   Confirm
-   Reject
-   Back

------------------------------------------------------------------------

# Confirm

System

-   Status = Confirmed
-   Save Confirmed By
-   Save Confirmed Date
-   Update PMS Header Action Plan Status

If every Action Plan under the PMS is Confirmed

PMS Action Plan Status = Confirmed

------------------------------------------------------------------------

# Reject

System

-   Status = Rejected
-   Save Rejected By
-   Save Rejected Date
-   Save Remarks

Supplier may edit the Action Plan again.

Supplier may add new comments.

Supplier may mark Implemented again.

------------------------------------------------------------------------

# Automatic PMS Status Synchronization

The PMS Header Action Plan Status shall always reflect the latest state
of all Action Plans.

Rules

No Action Plans

→ None

Any Pending

→ Pending

Any Waiting

→ Waiting for FAST Confirmation

Any Rejected

→ Rejected

All Confirmed

→ Confirmed

All Cancelled

→ Cancelled

------------------------------------------------------------------------

# Validation Rules

Required

-   Action Plan Title
-   Description
-   Responsible
-   Timeline From
-   Timeline To

Timeline To must be greater than Timeline From.

Confirmation Remarks are required when rejecting.

------------------------------------------------------------------------

# Authorization

Supplier

-   Create Action Plans
-   Update Pending
-   Add Comments
-   Mark Implemented
-   View Own Records

Supplier cannot Confirm or Reject.

FAST Administrator

-   View All
-   Confirm
-   Reject

FAST cannot modify Supplier comments or Action Plan details.

------------------------------------------------------------------------

# Audit Information

Display

-   Created By
-   Created At
-   Updated By
-   Updated At
-   Confirmed By
-   Confirmed At
-   Rejected By
-   Rejected At

------------------------------------------------------------------------

# Activity Logging

Log

-   Create Action Plan
-   Update Action Plan
-   Add Comment
-   Mark Implemented
-   Confirm
-   Reject
-   Cancel

Capture

-   User
-   Action
-   Module
-   Date/Time

------------------------------------------------------------------------

# Future Enhancements

Out of Scope

-   File Attachments
-   Before/After Photos
-   Email Notifications
-   SMS Notifications
-   Escalation Rules
-   Automatic Reminder Emails

Version 1.0 focuses on a complete corrective action workflow with FAST
verification and full audit history.
