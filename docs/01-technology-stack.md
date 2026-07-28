# 01 - Technology Stack

## Purpose

This document defines the official technology stack, development
standards, architecture, and coding conventions for the MHE Preventive
Maintenance Automation System.

All developers and AI coding assistants must follow these standards
throughout the project.

------------------------------------------------------------------------

# Primary Technology Stack

  Component         Standard
  ----------------- -----------------------------
  Framework         Laravel 12
  Language          PHP 8.5+
  Database          MariaDB 11.x / MySQL 8.x
  Frontend          Blade
  CSS Framework     Bootstrap 5
  JavaScript        Vanilla JavaScript + jQuery
  Charts            Chart.js
  Authentication    Laravel Breeze
  Authorization     Laravel Policies & Gates
  ORM               Eloquent ORM
  Package Manager   Composer
  Build Tool        Vite
  Version Control   Git

------------------------------------------------------------------------

# Development Philosophy

The project shall remain:

-   Lightweight
-   Modular
-   Easy to maintain
-   Easy to understand
-   Enterprise coding standards
-   Minimal external dependencies

Do not introduce unnecessary frameworks or libraries.

Prefer native Laravel functionality whenever possible.

------------------------------------------------------------------------

# Architecture

The project shall follow Laravel's MVC architecture.

    Browser

    ↓

    Routes

    ↓

    Controller

    ↓

    Service Layer

    ↓

    Model

    ↓

    Database

Business logic shall not reside inside Controllers.

Controllers should remain thin and delegate processing to Services.

------------------------------------------------------------------------

# Standard Folder Structure

    app/

        Http/

            Controllers/

            Requests/

            Middleware/

        Models/

        Policies/

        Services/

        Providers/

    resources/

        views/

        js/

        css/

    routes/

    database/

        migrations/

        seeders/

        factories/

    tests/

    docs/

------------------------------------------------------------------------

# Laravel Components

Every major module shall include:

-   Migration
-   Model
-   Controller
-   Form Request
-   Policy
-   Service
-   Seeder
-   Factory
-   Feature Test

Repositories are optional and should only be introduced when business
complexity requires them.

------------------------------------------------------------------------

# Database Standards

Database Engine

MariaDB 11.x or MySQL 8.x

Character Set

utf8mb4

Collation

utf8mb4_unicode_ci

Primary Keys

BIGINT UNSIGNED AUTO_INCREMENT

Foreign Keys

Use proper foreign key constraints.

Indexes

Create indexes for:

-   Foreign Keys
-   Status
-   Dates
-   Frequently searched fields

------------------------------------------------------------------------

# Table Naming

Use plural snake_case.

Examples

users

suppliers

sites

mhe_types

checklist_groups

checklist_items

pms_headers

pms_details

action_plans

action_plan_comments

------------------------------------------------------------------------

# Column Naming

Primary Key

id

Foreign Keys

supplier_id

site_id

user_id

mhe_type_id

Boolean Fields

is_active

is_deleted

Dates

created_at

updated_at

deleted_at

Status

status

Use descriptive names.

Avoid abbreviations.

------------------------------------------------------------------------

# Soft Deletes

Use Soft Deletes for:

-   Master Files
-   Users
-   Suppliers
-   Sites
-   MHE Types

Do not physically delete records unless explicitly required.

------------------------------------------------------------------------

# Authentication

Use Laravel Breeze.

Features

-   Login
-   Logout
-   Forgot Password
-   Reset Password
-   Email Verification (optional)
-   Session Management

------------------------------------------------------------------------

# Authorization

Use Laravel Policies.

Never perform authorization directly inside Blade templates.

All access shall be validated in Controllers and Policies.

------------------------------------------------------------------------

# Validation

All forms shall use Laravel Form Requests.

Validation shall occur:

-   Client-side
-   Server-side

Business rules shall never rely solely on JavaScript validation.

------------------------------------------------------------------------

# Error Handling

Use Laravel Exception Handling.

Display user-friendly messages.

Do not expose stack traces in production.

Errors shall be logged using Laravel logging.

------------------------------------------------------------------------

# Activity Logging

Log important activities:

-   Login
-   Logout
-   Create
-   Update
-   Delete
-   Cancel
-   Submit
-   Confirm
-   Reject
-   Password Change

Store:

-   User
-   Module
-   Action
-   Date/Time
-   IP Address (optional)

------------------------------------------------------------------------

# Frontend Standards

Framework

Bootstrap 5

Icons

Bootstrap Icons

Theme

Light Theme

Responsive

Desktop First

Forms

Bootstrap Floating Labels where appropriate.

Tables

Bootstrap Responsive Tables

Buttons

Bootstrap Button Groups

Alerts

Bootstrap Alerts

Notifications

Bootstrap Toasts

------------------------------------------------------------------------

# JavaScript Standards

Use Vanilla JavaScript whenever practical.

Use jQuery only when it simplifies development.

Avoid unnecessary JavaScript frameworks.

Use AJAX for:

-   Search
-   Filters
-   Dynamic dropdowns
-   Partial refreshes

------------------------------------------------------------------------

# Charts

Use Chart.js.

Supported Charts

-   Bar
-   Line
-   Pie
-   Doughnut

All dashboard charts shall support responsive resizing.

------------------------------------------------------------------------

# Reporting

Reports shall support:

-   Browser View
-   Excel Export
-   PDF Export

------------------------------------------------------------------------

# Coding Standards

Follow PSR-12.

Controllers

Thin.

Services

Business Logic.

Models

Relationships only.

Views

Presentation only.

Avoid duplicate code.

Create reusable Blade components where appropriate.

------------------------------------------------------------------------

# Naming Standards

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

Route Names

dot.notation

------------------------------------------------------------------------

# Route Standards

Resource routes shall be used whenever possible.

Example

    Route::resource('suppliers', SupplierController::class);

Protect routes using middleware.

Example

    auth

    verified (optional)

    role

    policy

------------------------------------------------------------------------

# Security Standards

Use CSRF protection.

Hash passwords.

Escape output.

Validate uploads.

Validate MIME types.

Prevent mass assignment.

Use HTTPS in production.

Never trust client-side data.

------------------------------------------------------------------------

# Performance Standards

Use eager loading.

Avoid N+1 queries.

Paginate all list pages.

Cache lookup tables where appropriate.

Optimize dashboard queries using aggregate SQL.

------------------------------------------------------------------------

# Testing Standards

Every module shall include:

-   Feature Tests
-   Unit Tests (where applicable)

Critical workflows shall be tested before deployment.

------------------------------------------------------------------------

# Documentation Rule

Whenever a new module or feature is introduced:

1.  Update the relevant document under `/docs`.
2.  Update database documentation if schema changes.
3.  Update business rules if workflow changes.
4.  Keep documentation synchronized with implementation.

The `/docs` folder shall remain the single source of truth for the
project.
