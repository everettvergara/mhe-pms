# 07 - Preventive Maintenance Module

# Purpose

The Preventive Maintenance (PMS) module is the core transaction of the
system.

It allows Supplier Users to perform preventive maintenance inspections
on Material Handling Equipment (MHE) using standardized checklists
maintained by FAST Administrators.

The module captures inspection results, determines whether findings
exist, and initiates the Action Plan workflow.

------------------------------------------------------------------------

# Actors

Primary

-   Supplier User

Secondary

-   FAST Administrator

------------------------------------------------------------------------

# Module Pages

-   PMS List
-   PMS Data Entry
-   PMS View (Read-only)

------------------------------------------------------------------------

# Business Workflow

    Create PMS

    ↓

    Save Draft

    ↓

    Complete Checklist

    ↓

    Forward for Approval

    ↓

    System evaluates checklist

    ↓

    No Findings
        │
        └── PMS Status = No Findings

    With Findings
        │
        └── PMS Status = With Findings

    ↓

    Supplier creates Action Plans

    ↓

    FAST monitors progress

    ↓

    FAST confirms completed Action Plans

------------------------------------------------------------------------

# PMS Status

-   Draft
-   With Findings
-   No Findings
-   Cancelled

Submitted records become read-only.

Cancelled records become read-only.

------------------------------------------------------------------------

# Action Plan Status

Automatically maintained by the system.

Values

-   None
-   Pending
-   Waiting for FAST Confirmation
-   Confirmed
-   Rejected
-   Cancelled

------------------------------------------------------------------------

# PMS List Page

Purpose

Display all Preventive Maintenance records.

Supplier Users

-   View only their supplier records.

FAST Administrators

-   View all records.

------------------------------------------------------------------------

## Toolbar

-   New PMS
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

Support keyword search using

-   PMS No.
-   Technician
-   Unit Number
-   Serial Number

------------------------------------------------------------------------

## Filters

-   Supplier
-   Site
-   MHE Type
-   Status
-   Action Plan Status
-   Date From
-   Date To

------------------------------------------------------------------------

## Grid Columns

-   PMS No.
-   Site
-   Supplier
-   Technician
-   MHE Type
-   Unit No.
-   Serial No.
-   Status
-   Action Plan Status
-   Date Created
-   Updated By

Clicking a row opens the PMS Data Entry page.

------------------------------------------------------------------------

# PMS Data Entry

## Header Information

### General Information

-   Site \*
-   Technician Name \*
-   Date/Time From \*
-   Date/Time To \*
-   MHE Type \*
-   MHE Unit Number \*
-   Serial Number \*

------------------------------------------------------------------------

### System Information

-   PMS Number (Auto-generated)
-   Status
-   Action Plan Status

------------------------------------------------------------------------

# Checklist Section

The checklist shall be generated dynamically from the Checklist Builder
master files.

Display order

Checklist Group

↓

Checklist Items

Each Checklist Item shall contain

-   Good (Radio)
-   No Good (Radio)
-   Remarks

Business Rules

-   One answer is required.
-   Remarks become mandatory when "No Good" is selected.

------------------------------------------------------------------------

# Buttons

## Draft

Save the record.

Status remains Draft.

User may continue editing.

------------------------------------------------------------------------

## Forward for Approval

Validate:

-   Header completed.
-   Every checklist item answered.
-   Remarks entered for every "No Good".

System automatically evaluates the checklist.

If all answers are Good

Status

No Findings

If one or more answers are No Good

Status

With Findings

------------------------------------------------------------------------

## Cancel

Changes status to Cancelled.

Confirmation dialog required.

Cancelled records become read-only.

------------------------------------------------------------------------

## Back

Return to List Page preserving filters and page position.

------------------------------------------------------------------------

# View Mode

Read-only display.

Display

-   Header
-   Checklist
-   Action Plans
-   Audit Information

------------------------------------------------------------------------

# Business Rules

Supplier Users

-   Create PMS
-   Edit own Drafts
-   View own Submitted PMS
-   Cannot edit Submitted PMS
-   Cannot view other suppliers

FAST Administrator

-   View all PMS
-   Cannot modify Supplier responses
-   May review Action Plans

------------------------------------------------------------------------

# Validation Rules

Required

-   Site
-   Technician
-   Date From
-   Date To
-   MHE Type
-   Unit Number
-   Serial Number

Checklist

-   Every item must be answered.

Remarks

Required when

Answer = No Good

Date Validation

Date To must be greater than Date From.

------------------------------------------------------------------------

# Audit Information

Display

-   Created By
-   Created At
-   Updated By
-   Updated At

------------------------------------------------------------------------

# Relationships

PMS Header

1

↓

Many

PMS Details

Each PMS Detail references one Checklist Item.

Each PMS Detail may have zero or more Action Plans.

------------------------------------------------------------------------

# Activity Logging

Log

-   Create Draft
-   Update Draft
-   Submit PMS
-   Cancel PMS
-   View PMS

Capture

-   User
-   Date/Time
-   Module
-   Action

------------------------------------------------------------------------

# Sample Workflow

Supplier

↓

Creates Draft PMS

↓

Completes Checklist

↓

Submits PMS

↓

System detects findings

↓

With Findings

↓

Supplier creates Action Plans

↓

FAST reviews until all Action Plans are confirmed.

------------------------------------------------------------------------

# Future Enhancements

Out of Scope

-   Photo Attachments
-   Technician Signature
-   QR Code Scanning
-   Offline Mobile Entry
-   Scheduled PMS Generation

Version 1.0 focuses exclusively on checklist-based preventive
maintenance.
