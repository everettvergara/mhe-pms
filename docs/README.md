# MHE Preventive Maintenance Automation

## README

Welcome to the **MHE Preventive Maintenance Automation System**
documentation.

This project is a Laravel + MariaDB web application developed for **FAST
Logistics** to automate the Preventive Maintenance (PMS) process
performed by accredited suppliers (e.g. Toyota, Global, Boeing) for
Material Handling Equipment (MHE) such as forklifts.

The objective is to replace manual checklist forms with a centralized
web-based workflow that provides:

-   Standardized preventive maintenance checklists
-   Supplier submission and tracking
-   Action Plan management
-   FAST verification and confirmation workflow
-   Compliance dashboards
-   Reporting and audit trails

------------------------------------------------------------------------

# Technology Stack

-   Laravel 12
-   PHP 8.5+
-   MariaDB / MySQL 8
-   Bootstrap 5
-   Chart.js
-   jQuery
-   Blade Templates
-   Laravel Breeze Authentication

------------------------------------------------------------------------

# Documentation Structure

    /docs

    README.md
    00-project-overview.md
    01-technology-stack.md
    02-ui-ux-standards.md
    03-authentication-security.md
    04-navigation-modules.md
    05-dashboard.md
    06-master-files.md
    07-preventive-maintenance.md
    08-action-plans.md
    09-reports.md
    10-business-rules.md
    11-validation-rules.md
    12-database-design.md
    13-seeders.md
    14-development-standards.md
    15-future-enhancements.md

------------------------------------------------------------------------

# Development Rules

Before implementing any feature, read **all** documents under the
`/docs` folder.

The documents are hierarchical. Earlier documents define standards that
later documents inherit.

Do not begin implementation until the architecture, workflows, business
rules, and UI standards have been reviewed.

------------------------------------------------------------------------

# Project Scope

The application includes:

-   Authentication
-   Dashboard
-   User Management
-   Supplier Management
-   Site Maintenance
-   MHE Type Maintenance
-   Checklist Builder
-   Preventive Maintenance Checklist
-   Action Plans
-   FAST Confirmation Workflow
-   Reports
-   Activity Logs
-   User Profile
-   Change Password

------------------------------------------------------------------------

# Design Philosophy

This is **not** an ERP system.

It is a focused workflow application centered on Preventive Maintenance
Checklists.

The design goals are:

-   Simple
-   Fast
-   Consistent
-   Mobile-friendly
-   Easy to maintain
-   Audit-ready

Every module shall follow the same navigation and user experience.

------------------------------------------------------------------------

# UI Principles

-   Bootstrap 5
-   Dedicated List Page and Data Entry Page for every module
-   No CRUD modal dialogs
-   Responsive layout
-   Consistent spacing and typography
-   Server-side pagination, searching and filtering
-   Clean, professional appearance aligned with FAST branding

------------------------------------------------------------------------

# User Roles

## FAST Administrator

Responsible for:

-   Master Files
-   User Management
-   Dashboard
-   Reports
-   Confirmation of completed Action Plans
-   Overall system administration

## Supplier User

Responsible for:

-   Completing PMS Checklists
-   Managing Action Plans
-   Updating implementation progress
-   Viewing only their own supplier records

------------------------------------------------------------------------

# Workflow Summary

Supplier logs in

↓

Creates PMS Checklist

↓

Completes Inspection Checklist

↓

Submit PMS

↓

If findings exist

↓

Create Action Plans

↓

Supplier implements actions

↓

Supplier marks Implemented

↓

FAST reviews

↓

FAST Confirms or Rejects

↓

Action Plan Closed

------------------------------------------------------------------------

# General Standards

-   Every module shall have List and Data Entry pages.
-   All transactions shall maintain complete audit trails.
-   All permissions shall be enforced through Laravel Policies.
-   Validation shall occur on both client and server.
-   Submitted records become read-only unless permitted by workflow.
-   Soft Deletes shall be used where appropriate.

------------------------------------------------------------------------

# Cursor AI Instructions

Before implementing any feature:

1.  Read every document under `/docs`.
2.  Follow the standards exactly.
3.  Do not invent UI patterns that conflict with the documented
    standards.
4.  Reuse components wherever possible.
5.  Maintain consistent coding style throughout the project.
6.  Ask for clarification only when business rules conflict.

This documentation is the authoritative specification for the project.
