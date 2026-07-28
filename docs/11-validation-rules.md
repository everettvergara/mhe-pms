# 11 - Validation Rules

# Purpose

This document defines the standard validation rules for every module in
the MHE Preventive Maintenance Automation System.

Validation shall be implemented using Laravel Form Requests and enforced
on both the client and server. Client-side validation improves
usability; server-side validation is authoritative.

------------------------------------------------------------------------

# General Validation Rules

All forms shall:

-   Validate required fields.
-   Validate field length.
-   Validate data type.
-   Validate uniqueness where applicable.
-   Display user-friendly validation messages.
-   Prevent duplicate submissions where practical.

------------------------------------------------------------------------

# User Management

## Required

-   Username
-   Full Name
-   Email Address
-   Role
-   Password (Create)

## Validation

Username

-   Required
-   Unique
-   Maximum 50 characters

Email

-   Required
-   Valid email format
-   Unique

Password

-   Minimum 8 characters
-   Uppercase
-   Lowercase
-   Number
-   Special Character

Supplier

-   Required when Role = Supplier User

------------------------------------------------------------------------

# Supplier

Required

-   Supplier Code
-   Supplier Name

Validation

Supplier Code

-   Unique
-   Maximum 20 characters

Supplier Name

-   Unique
-   Maximum 150 characters

Email

-   Optional
-   Valid email format

------------------------------------------------------------------------

# Site

Required

-   Site Code
-   Site Name

Validation

Site Code

-   Unique

Site Name

-   Unique

------------------------------------------------------------------------

# MHE Type

Required

-   Code
-   Description

Validation

Code

-   Unique

Description

-   Required

------------------------------------------------------------------------

# Checklist Group

Required

-   Group Name
-   Sequence

Validation

Sequence

-   Integer
-   Greater than zero

Group Name

-   Unique

------------------------------------------------------------------------

# Checklist Item

Required

-   Checklist Group
-   Sequence
-   Checklist Description

Validation

Sequence

-   Integer
-   Greater than zero

------------------------------------------------------------------------

# Preventive Maintenance Header

Required

-   Site
-   Technician Name
-   Date From
-   Date To
-   MHE Type
-   Unit Number
-   Serial Number

Validation

Date To

-   Must be later than Date From

Technician Name

-   Maximum 150 characters

Unit Number

-   Maximum 100 characters

Serial Number

-   Maximum 100 characters

------------------------------------------------------------------------

# Preventive Maintenance Checklist

Every checklist item requires exactly one answer.

Allowed values

-   Good
-   No Good

Remarks

Required only when Answer = No Good.

Supplier cannot submit until every checklist item has been answered.

------------------------------------------------------------------------

# PMS Submission Validation

Before Forward for Approval:

The system shall verify:

-   Header completed
-   Checklist completed
-   Required remarks entered
-   Date validation passed

If validation fails:

Submission is rejected.

Status remains Draft.

------------------------------------------------------------------------

# Action Plans

Required

-   Action Plan Title
-   Description
-   Responsible Person
-   Timeline From
-   Timeline To

Validation

Timeline To

-   Later than Timeline From

Description

-   Maximum 2,000 characters

------------------------------------------------------------------------

# Progress Comments

Required

-   Comment
-   Progress Status

Allowed Progress Status

-   Pending
-   Implemented
-   Cancelled

Validation

Comment

-   Required
-   Maximum 2,000 characters

Existing comments cannot be edited or deleted.

------------------------------------------------------------------------

# FAST Confirmation

Confirm

No additional fields required.

Reject

Required

-   Confirmation Remarks

Validation

Confirmation Remarks

-   Required
-   Maximum 2,000 characters

------------------------------------------------------------------------

# Change Password

Required

-   Current Password
-   New Password
-   Confirm Password

Validation

Current Password

-   Must match authenticated user.

New Password

-   Minimum 8 characters
-   Uppercase
-   Lowercase
-   Number
-   Special Character
-   Must not equal Current Password

Confirmation Password

-   Must match New Password

------------------------------------------------------------------------

# My Profile

Required

-   Full Name
-   Email Address

Validation

Email

-   Valid
-   Unique

Username

-   Read-only

Role

-   Read-only

Supplier

-   Read-only

------------------------------------------------------------------------

# Search Validation

Search keywords

Maximum 255 characters.

Sort columns shall only allow predefined column names.

Sort direction

Only

-   ASC
-   DESC

Pagination

Allowed values

-   25
-   50
-   100
-   250

------------------------------------------------------------------------

# File Upload Validation (Future)

Profile Picture

Allowed

-   JPG
-   PNG
-   WEBP

Maximum

2 MB

Other attachments are outside Version 1.0.

------------------------------------------------------------------------

# Validation Messages

Messages shall be:

-   Clear
-   User-friendly
-   Specific

Example

✔ Technician Name is required.

✔ Date To must be later than Date From.

✔ Remarks are required when a checklist item is marked No Good.

Avoid exposing technical database errors to end users.

------------------------------------------------------------------------

# Implementation Standard

Every module shall use Laravel Form Request validation.

Controllers shall not contain validation logic.

Validation rules shall remain centralized, reusable, and maintainable.
