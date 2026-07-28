# 03 - Authentication & Security

## Purpose

This document defines the authentication, authorization, account
management, and security standards for the MHE Preventive Maintenance
Automation System.

All user access shall follow the workflows and rules defined in this
document.

------------------------------------------------------------------------

# Authentication

Authentication shall be implemented using **Laravel Breeze**.

Supported features:

-   Login
-   Logout
-   Forgot Password
-   Reset Password
-   Remember Me
-   Session Timeout

------------------------------------------------------------------------

# User Roles

The system shall support two (2) roles only.

## FAST Administrator

Responsibilities

-   Full system administration
-   Master File maintenance
-   User management
-   Dashboard access
-   Reports
-   Review PMS
-   Confirm / Reject Action Plans

------------------------------------------------------------------------

## Supplier User

Responsibilities

-   Create PMS
-   Edit own Draft PMS
-   Submit PMS
-   Create Action Plans
-   Update Action Plans
-   Maintain own profile
-   View only supplier-owned records

Supplier Users shall never access records belonging to another supplier.

Supplier Users may be assigned to one or more FAST Sites.

------------------------------------------------------------------------

# Login Flow

    User

    ↓

    Login Page

    ↓

    Enter Username / Email

    ↓

    Enter Password

    ↓

    Authenticate

    ↓

    Success

    ↓

    Dashboard

    ↓

    Role-specific Menu

Failed authentication shall display a generic error message.

Do not disclose whether the username or password is incorrect.

------------------------------------------------------------------------

# Logout Flow

User Menu

↓

Logout

↓

Invalidate Session

↓

Redirect to Login Page

------------------------------------------------------------------------

# Forgot Password

Flow

Forgot Password

↓

Enter Email Address

↓

Send Password Reset Link

↓

Reset Password

↓

Login

------------------------------------------------------------------------

# User Profile

Every authenticated user shall have access to:

-   My Profile
-   Change Password

Users may only edit their own profile.

------------------------------------------------------------------------

## My Profile

Fields

-   Username (Read-only)
-   Full Name
-   Email Address
-   Contact Number
-   Profile Picture (optional)
-   Role (Read-only)
-   Supplier (Read-only)
-   Status (Read-only)

Buttons

-   Save Changes
-   Cancel

Validation

-   Email must be unique.
-   Username cannot be modified.
-   Role cannot be modified.
-   Supplier assignment cannot be modified.

------------------------------------------------------------------------

# Change Password

Fields

-   Current Password
-   New Password
-   Confirm New Password

Buttons

-   Update Password
-   Cancel

Validation

-   Current Password must match.
-   New Password must satisfy password policy.
-   Confirmation must match.
-   New Password shall not equal Current Password.

Password change shall be recorded in the Activity Log.

------------------------------------------------------------------------

# Password Policy

Minimum Requirements

-   Minimum 8 characters
-   At least one uppercase letter
-   At least one lowercase letter
-   At least one number
-   At least one special character

Passwords shall be stored using Laravel Hash.

Passwords shall never be stored in plain text.

------------------------------------------------------------------------

# Session Management

Authenticated users shall have a secure session.

Requirements

-   CSRF Protection
-   Session Regeneration after Login
-   Automatic Logout after configurable inactivity
-   Secure Cookies
-   HTTPS in Production

------------------------------------------------------------------------

# Authorization

Authorization shall use Laravel Policies.

Access shall be validated in Controllers and Policies.

Blade templates shall never be relied upon as the only security
mechanism.

------------------------------------------------------------------------

# Menu Visibility

## FAST Administrator

Dashboard

Preventive Maintenance

Action Plans

Action Plan Confirmation

Reports

Masters

Administration

Activity Logs

Settings

My Profile

Logout

------------------------------------------------------------------------

## Supplier User

Dashboard

Preventive Maintenance

Action Plans

My Profile

Logout

Supplier users shall not see administration menus.

------------------------------------------------------------------------

# Data Access Rules

## Super Admin

Users tagged as Super Admin in User Maintenance may access all suppliers
and sites in all listings, regardless of supplier or site assignments.

Super Admin controls data visibility only. Role permissions still govern
module access and workflow actions.

------------------------------------------------------------------------

## FAST Administrator

May access all records when tagged as Super Admin.

Non-super-admin FAST Administrators with supplier and/or site assignments
see only matching records in listings.

------------------------------------------------------------------------

## Supplier User

May access only:

-   Their own Supplier
-   Assigned Sites
-   Their own Draft PMS
-   Their own Submitted PMS
-   Their own Action Plans

Unless tagged as Super Admin.

Supplier users shall never view, edit, or export another supplier's
records.

------------------------------------------------------------------------

# Record Editing Rules

Draft PMS

Editable by Supplier.

Submitted PMS

Read-only.

Cancelled PMS

Read-only.

Confirmed Action Plans

Read-only.

Rejected Action Plans

Supplier may update and resubmit.

------------------------------------------------------------------------

# Login Activity

Record

-   Login
-   Logout
-   Failed Login (optional)
-   Password Change
-   Profile Update

Store

-   User
-   Date/Time
-   IP Address (optional)
-   Browser (optional)

------------------------------------------------------------------------

# Security Standards

-   Use CSRF protection.
-   Escape output.
-   Validate all user input.
-   Validate uploaded files.
-   Prevent Mass Assignment.
-   Enforce HTTPS in production.
-   Protect routes using authentication middleware.
-   Use authorization policies for sensitive operations.

------------------------------------------------------------------------

# Route Protection

All authenticated routes shall use:

-   auth middleware

Administrative modules shall additionally validate:

-   Role
-   Policy

------------------------------------------------------------------------

# Account Status

Users may be:

-   Active
-   Inactive

Inactive users shall not be permitted to log in.

------------------------------------------------------------------------

# Future Security Enhancements

Future versions may include:

-   Two-Factor Authentication (2FA)
-   Single Sign-On (SSO)
-   Microsoft Entra ID Integration
-   LDAP / Active Directory
-   Password Expiration Policies
-   Session Management Dashboard

These features are out of scope for Version 1.0.

------------------------------------------------------------------------

# Summary

Authentication shall remain simple, secure, and role-based.

The system shall provide a secure login experience while ensuring that:

-   Users only access authorized information.
-   Supplier data remains isolated.
-   FAST Administrators maintain full visibility.
-   Every sensitive activity is auditable.
