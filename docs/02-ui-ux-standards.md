# 02 - UI / UX Standards

## Purpose

This document defines the official UI/UX standards for the MHE
Preventive Maintenance Automation System.

All modules shall follow these standards to provide a consistent,
professional, and easy-to-use experience.

------------------------------------------------------------------------

# Design Philosophy

The application shall be:

-   Clean
-   Modern
-   Fast
-   Professional
-   Consistent
-   Responsive
-   Simple

Avoid unnecessary animations and visual clutter.

------------------------------------------------------------------------

# Branding

The application theme shall be based on the FAST Logistics corporate
branding.

Theme Style

-   Light Theme
-   Clean White Workspace
-   Corporate Blue
-   Minimalist
-   Flat Design with subtle shadows

------------------------------------------------------------------------

# Standard Color Palette

## Primary

FAST Blue

`#005BAC`

Buttons, links, active menu

------------------------------------------------------------------------

## Secondary

Light Blue

`#4F9DDA`

Charts, highlights

------------------------------------------------------------------------

## Accent

Orange

`#F58220`

Notifications

Warnings

Important Actions

------------------------------------------------------------------------

## Success

Green

`#28A745`

------------------------------------------------------------------------

## Warning

Amber

`#FFC107`

------------------------------------------------------------------------

## Danger

Red

`#DC3545`

------------------------------------------------------------------------

## Information

Cyan

`#17A2B8`

------------------------------------------------------------------------

## Background

Page

`#F5F7FA`

Cards

`#FFFFFF`

Sidebar

`#005BAC`

Top Navigation

`#FFFFFF`

------------------------------------------------------------------------

# Typography

Font Family

Inter

Fallback

Segoe UI

Arial

sans-serif

Font Sizes

Page Title

28px

Section Title

20px

Body

14px

Table

13px

------------------------------------------------------------------------

# Layout

Top Navigation

Fixed

Sidebar

Collapsible

Left-aligned

Main Content

Fluid Container

Footer

Simple copyright

------------------------------------------------------------------------

# Sidebar

Menu Groups

Dashboard

Transactions

Masters

Reports

Administration

Settings

Profile

Logout

Current menu shall be highlighted using the Primary Blue.

------------------------------------------------------------------------

# Cards

Use Bootstrap Cards.

Radius

8px

Shadow

Small

Padding

20px

Cards shall be used for:

-   KPI
-   Forms
-   Sections
-   Dashboard Widgets

------------------------------------------------------------------------

# Dashboard

Dashboard shall contain:

-   KPI Cards
-   Charts
-   Pending Work Queues
-   Recent Activities

Use a responsive grid.

Charts

Chart.js

Responsive

------------------------------------------------------------------------

# Forms

Every form shall use Bootstrap form controls.

Floating labels where appropriate.

Required fields shall display a red asterisk.

Read-only fields shall have a light gray background.

Sections shall be grouped using cards.

------------------------------------------------------------------------

# Buttons

Primary

Blue

Secondary

Gray

Success

Green

Warning

Amber

Danger

Red

Examples

Save

Update

Submit

Confirm

Reject

Cancel

Delete

Back

------------------------------------------------------------------------

# List Page Standard

Every module shall begin with a List Page.

Default Records Per Page

50

Options

25

50

100

250

Toolbar

-   New
-   Search
-   Filters
-   Refresh
-   Export

Table Features

-   Responsive
-   Sortable Columns
-   Server-side Search
-   Server-side Filters
-   Pagination
-   Sticky Header
-   Status Badges

Clicking a table row shall open the corresponding Data Entry Page.

------------------------------------------------------------------------

# Data Entry Page Standard

Purpose

Create

Edit

View

Layout

Header

-   Title
-   Breadcrumb
-   Status Badge

Body

Bootstrap Cards

Footer Buttons

Save

Update

Save Draft

Forward

Confirm

Reject

Delete

Cancel

Back

Buttons shall appear only when permitted by business rules.

------------------------------------------------------------------------

# Status Badges

Draft

Gray

Submitted

Blue

With Findings

Orange

No Findings

Green

Waiting Confirmation

Cyan

Confirmed

Green

Rejected

Red

Cancelled

Dark Gray

------------------------------------------------------------------------

# Tables

Compact rows

Alternating row hover

Sortable headers

Responsive

Sticky header

Right-aligned numeric values

------------------------------------------------------------------------

# Search and Filters

All list pages shall provide:

-   Keyword Search
-   Advanced Filters
-   Clear Filters
-   Remember previous filters when returning from Data Entry.

------------------------------------------------------------------------

# Dialogs

Use Bootstrap confirmation dialogs for:

Delete

Cancel

Confirm

Reject

Do not use modal dialogs for Create/Edit forms.

------------------------------------------------------------------------

# Notifications

Use Bootstrap Toasts.

Success

Green

Warning

Amber

Error

Red

Information

Blue

------------------------------------------------------------------------

# Breadcrumbs

Every page shall display breadcrumb navigation.

Example

Dashboard

Preventive Maintenance

PMS List

PMS Details

------------------------------------------------------------------------

# Responsive Design

Support

Desktop

Tablet

Mobile

Sidebar shall collapse automatically on smaller screens.

Tables shall scroll horizontally when necessary.

------------------------------------------------------------------------

# Accessibility

Use sufficient color contrast.

Buttons shall include icons and labels.

All form controls shall have labels.

Keyboard navigation shall be supported where practical.

------------------------------------------------------------------------

# Consistency Rules

Every module shall follow identical navigation patterns.

Every module shall contain:

-   List Page
-   Data Entry Page

Users always navigate:

Menu

↓

List Page

↓

Select Record or New

↓

Data Entry Page

↓

Save / Update / Submit

↓

Return to previous List Page with filters, sorting, page number, and
scroll position preserved whenever possible.

No module shall invent a different layout or workflow.

This document defines the application's visual design system and shall
be followed throughout the project.
