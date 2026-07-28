# 00 - Project Overview

## Project Name

**MHE Preventive Maintenance Automation System**

------------------------------------------------------------------------

# Purpose

The MHE Preventive Maintenance Automation System is a web-based
application developed for **FAST Logistics** to automate the preventive
maintenance (PMS) process performed by accredited maintenance suppliers.

The application replaces paper-based maintenance checklists with a
centralized workflow that allows suppliers to submit maintenance
inspections while enabling FAST personnel to review, monitor, verify,
and confirm completed maintenance activities.

The system focuses on accountability, standardization, auditability, and
reporting while maintaining a simple and efficient workflow.

------------------------------------------------------------------------

# Objectives

The project aims to:

-   Digitize Preventive Maintenance checklists.
-   Standardize inspection procedures across all suppliers.
-   Track maintenance findings and corrective actions.
-   Monitor supplier compliance.
-   Provide complete audit trails.
-   Eliminate manual spreadsheet and paper tracking.
-   Provide management dashboards and reports.
-   Improve visibility of unresolved maintenance findings.

------------------------------------------------------------------------

# Scope

The application covers the complete lifecycle of a Preventive
Maintenance inspection.

Supplier Workflow

1.  Login
2.  Create PMS Checklist
3.  Complete inspection checklist
4.  Save as Draft or Submit
5.  Create Action Plans for all findings
6.  Update Action Plan progress
7.  Mark Action Plan as Implemented

FAST Workflow

1.  Review submitted PMS
2.  Review Action Plans
3.  Verify completed work
4.  Confirm or Reject implementation
5.  Monitor supplier performance
6.  Generate reports

------------------------------------------------------------------------

# Users

## FAST Administrator

Responsibilities

-   Maintain all master files
-   Manage users
-   Manage suppliers
-   Review PMS records
-   Verify Action Plans
-   Confirm or Reject supplier implementation
-   Access dashboards and reports
-   Manage system configuration

------------------------------------------------------------------------

## Supplier User

Responsibilities

-   Complete preventive maintenance checklists
-   Save drafts
-   Submit completed PMS
-   Create Action Plans
-   Update Action Plan progress
-   View only supplier-owned records
-   Maintain personal profile

Supplier users may access multiple FAST Sites assigned to them.

Supplier users shall never access another supplier's information.

------------------------------------------------------------------------

# Core Modules

-   Dashboard
-   User Management
-   User Access
-   Supplier Management
-   Site Master
-   MHE Type Master
-   Checklist Builder
-   Preventive Maintenance
-   Action Plans
-   FAST Confirmation
-   Reports
-   Activity Logs
-   My Profile
-   Change Password

------------------------------------------------------------------------

# Key Features

-   Web-based application
-   Responsive Bootstrap interface
-   Laravel authentication
-   Role-based authorization
-   Audit trail
-   Server-side searching
-   Server-side filtering
-   Pagination
-   Dashboard analytics
-   Chart.js reporting
-   Excel/PDF export
-   Approval workflow
-   Confirmation workflow

------------------------------------------------------------------------

# Workflow Summary

    Supplier
        │
        ▼
    Create PMS
        │
        ▼
    Complete Checklist
        │
        ▼
    Submit PMS
        │
        ▼
    Findings?
     ┌─────────────┐
     │             │
    No            Yes
     │             │
     ▼             ▼
    Completed   Create Action Plans
                      │
                      ▼
          Supplier Implements Actions
                      │
                      ▼
     Marks Action Plan as Implemented
                      │
                      ▼
          Waiting for FAST Confirmation
                      │
              ┌───────┴────────┐
              ▼                ▼
         Confirmed         Rejected

------------------------------------------------------------------------

# Guiding Principles

The application shall remain focused on Preventive Maintenance
management.

This is **not** an ERP, CMMS, or Asset Management System.

The solution shall remain lightweight, maintainable, and easy to use
while providing complete visibility of maintenance activities.

Future enhancements shall be implemented only when business requirements
justify them.

------------------------------------------------------------------------

# Success Criteria

The project shall be considered successful when it provides:

-   Faster PMS processing
-   Standardized inspection forms
-   Accurate supplier accountability
-   Complete maintenance history
-   Faster management reporting
-   Reduced manual paperwork
-   Transparent Action Plan tracking
-   Reliable FAST verification workflow

------------------------------------------------------------------------

# Document Relationships

This document introduces the project.

Subsequent documents define:

-   Technology standards
-   UI/UX standards
-   Authentication
-   Navigation
-   Dashboards
-   Master files
-   Transactions
-   Reports
-   Database design
-   Business rules
-   Development standards

All developers and AI coding assistants shall read this document before
proceeding to the remaining specifications.
