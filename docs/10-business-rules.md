# 10 - Business Rules

# Purpose

This document defines the functional business rules governing the MHE
Preventive Maintenance Automation System.

Business Rules take precedence over implementation details.

------------------------------------------------------------------------

# General Rules

1.  Every authenticated user shall have only one assigned role.
2.  Every transaction shall maintain complete audit information.
3.  Every module shall follow the standard List Page and Data Entry Page
    design.
4.  Submitted and confirmed transactions shall become read-only unless
    explicitly allowed by workflow.
5.  All dates and times shall use the server time.

------------------------------------------------------------------------

# User Rules

## FAST Administrator

May:

-   Maintain all Master Files
-   Confirm or Reject Action Plans
-   View reports
-   Manage users

When tagged as Super Admin, may view all suppliers and sites in listings.
Otherwise, listings are limited to assigned supplier and sites.

------------------------------------------------------------------------

## Supplier User

May:

-   Access only assigned Sites.
-   View only records belonging to their Supplier.
-   Create PMS.
-   Edit only Draft PMS.
-   Create and update Action Plans.
-   Maintain their own profile.
-   Change their own password.

Supplier users shall never access another Supplier's information unless
tagged as Super Admin.

------------------------------------------------------------------------

# Site Assignment Rules

Users may be assigned to one supplier and one or more FAST Sites via User
Maintenance. Super Admin users do not require assignments.

During PMS creation:

-   Site dropdown shall display only assigned Sites.
-   If no Sites are assigned, PMS creation shall not be allowed.

------------------------------------------------------------------------

# Preventive Maintenance Rules

A PMS consists of:

-   Header
-   Checklist Details

Checklist items are generated dynamically from the Checklist Builder.

Supplier Users cannot modify the checklist structure.

------------------------------------------------------------------------

# PMS Status Rules

Draft

-   Editable by Supplier.

With Findings

-   Generated automatically if one or more checklist answers are "No
    Good".

No Findings

-   Generated automatically if all checklist answers are "Good".

Cancelled

-   Read-only.
-   Cannot be reopened.

------------------------------------------------------------------------

# Submission Rules

A PMS cannot be submitted unless:

-   Header is complete.
-   Every checklist item has an answer.
-   Every "No Good" item contains remarks.

------------------------------------------------------------------------

# Checklist Rules

Each checklist item requires exactly one answer:

-   Good
-   No Good

Remarks are:

Optional

when Good

Required

when No Good

------------------------------------------------------------------------

# Findings Rules

Every checklist item marked "No Good" becomes a Finding.

Each Finding may have:

-   Zero or more Action Plans.

Findings remain unresolved until all Action Plans are confirmed by FAST.

------------------------------------------------------------------------

# Action Plan Rules

Action Plans are created only for Findings.

Each Action Plan shall contain:

-   Title
-   Description
-   Responsible Person
-   Timeline

Timeline To must be later than Timeline From.

------------------------------------------------------------------------

# Progress Comment Rules

Each Action Plan may contain multiple Progress Comments.

Comments are append-only.

Comments shall never be edited.

Comments shall never be deleted.

Every Progress Comment records:

-   Comment
-   Status
-   User
-   Date/Time

------------------------------------------------------------------------

# Action Plan Workflow

Pending

↓

Supplier updates progress

↓

Supplier marks Implemented

↓

Waiting for FAST Confirmation

↓

FAST reviews

↓

Confirmed

OR

Rejected

Rejected Action Plans may be updated and resubmitted.

------------------------------------------------------------------------

# FAST Confirmation Rules

Only FAST Administrators may:

-   Confirm Action Plans
-   Reject Action Plans

Confirmation records:

-   Confirmed By
-   Confirmed Date

Rejection records:

-   Rejected By
-   Rejected Date
-   Rejection Remarks

Rejection Remarks are mandatory.

------------------------------------------------------------------------

# PMS Action Plan Status Synchronization

The PMS Header Action Plan Status is system-generated.

Rules

No Action Plans

→ None

Any Pending

→ Pending

Any Waiting for Confirmation

→ Waiting for FAST Confirmation

Any Rejected

→ Rejected

All Confirmed

→ Confirmed

All Cancelled

→ Cancelled

Users shall not manually edit this field.

------------------------------------------------------------------------

# Search Rules

All List Pages shall support:

-   Keyword Search
-   Filters
-   Sortable Columns
-   Pagination

Default page size

50

Supported page sizes

25

50

100

250

------------------------------------------------------------------------

# Delete Rules

Master Files

Soft Delete only.

Transactions

Physical deletion is prohibited.

Cancelled transactions remain for audit purposes.

------------------------------------------------------------------------

# Profile Rules

Users may edit only their own profile.

Users may change only their own password.

Role, Username and Supplier assignment are maintained only by FAST
Administrators.

------------------------------------------------------------------------

# Reporting Rules

FAST Administrators

View all reports.

Supplier Users

View only reports containing their Supplier's data.

Exports shall respect user permissions.

------------------------------------------------------------------------

# Activity Logging Rules

The following actions shall be logged:

-   Login
-   Logout
-   Profile Update
-   Password Change
-   Create
-   Update
-   Delete
-   Cancel
-   Submit
-   Confirm
-   Reject
-   Add Progress Comment

Each log stores:

-   User
-   Module
-   Action
-   Date/Time

------------------------------------------------------------------------

# Audit Rules

Every transaction shall display:

-   Created By
-   Created At
-   Updated By
-   Updated At

Where applicable:

-   Submitted By
-   Submitted At
-   Confirmed By
-   Confirmed At
-   Rejected By
-   Rejected At
-   Cancelled By
-   Cancelled At

------------------------------------------------------------------------

# Future Business Rules

The architecture shall accommodate future support for:

-   Attachments
-   Digital Signatures
-   QR Codes
-   Scheduled PMS
-   Notifications
-   Escalation Workflows
-   Mobile Offline Capability

These features are outside Version 1.0.
