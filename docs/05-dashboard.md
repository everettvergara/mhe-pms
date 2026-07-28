# 05 - Dashboard Specification

## Purpose

The Dashboard is the landing page after successful login.

It serves two purposes:

1.  Executive Summary (KPIs and Charts)
2.  Work Queue (Items requiring immediate action)

The dashboard shall be role-based.

------------------------------------------------------------------------

# Dashboard Types

-   FAST Administrator Dashboard
-   Supplier Dashboard

------------------------------------------------------------------------

# General Standards

-   Bootstrap 5 Cards
-   Chart.js
-   Responsive Layout
-   Auto-refresh (default every 5 minutes, configurable)
-   Manual Refresh button
-   Clickable KPI cards and charts (drill-down to filtered List Pages)

Filters available where applicable:

-   Year
-   Month
-   Supplier
-   Site
-   MHE Type

------------------------------------------------------------------------

# FAST Administrator Dashboard

## KPI Cards

Display:

-   Total PMS
-   Draft PMS
-   No Findings
-   With Findings
-   Pending Action Plans
-   Waiting for FAST Confirmation
-   Confirmed Action Plans
-   Rejected Action Plans
-   Cancelled PMS

Clicking a KPI opens the corresponding filtered List Page.

------------------------------------------------------------------------

# Work Queue

## Pending FAST Confirmations

Purpose

Display Action Plans marked "Implemented" by Suppliers that require FAST
verification.

Columns

-   PMS No.
-   Supplier
-   Site
-   MHE Unit
-   Checklist Item
-   Action Plan
-   Implemented Date
-   Responsible
-   Status

Actions

-   Review
-   Confirm
-   Reject

------------------------------------------------------------------------

## Pending Checklists

Display submitted PMS containing unresolved findings.

Columns

-   PMS No.
-   Supplier
-   Site
-   Technician
-   Submitted Date
-   Findings
-   Action Plan Status

Action

-   View

------------------------------------------------------------------------

## Recent PMS

Columns

-   PMS No.
-   Supplier
-   Site
-   Technician
-   Status
-   Submitted Date

------------------------------------------------------------------------

## Recent Confirmations

Columns

-   Action Plan
-   Supplier
-   Site
-   Confirmed By
-   Confirmation Date

------------------------------------------------------------------------

# Dashboard Reports

## Monthly Supplier Compliance

Bar Chart

X Axis

Supplier

Y Axis

Compliance %

------------------------------------------------------------------------

## Monthly Site Compliance

Bar Chart

X Axis

Site

Y Axis

Compliance %

Support Supplier Filter.

------------------------------------------------------------------------

## Monthly PMS Trend

Line Chart

January through December

Y Axis

Completed PMS

------------------------------------------------------------------------

## PMS Status Distribution

Pie Chart

-   Draft
-   With Findings
-   No Findings
-   Cancelled

------------------------------------------------------------------------

## Action Plan Status

Bar Chart

-   Pending
-   Waiting for Confirmation
-   Confirmed
-   Rejected
-   Cancelled

------------------------------------------------------------------------

## Top Sites with Findings

Horizontal Bar Chart

Top 10 Sites

Ordered Descending.

------------------------------------------------------------------------

## Suppliers with Most Findings

Horizontal Bar Chart

Top Suppliers

Ordered Descending.

------------------------------------------------------------------------

# Supplier Dashboard

Supplier users shall only see records belonging to their supplier.

------------------------------------------------------------------------

## KPI Cards

-   My Draft PMS
-   Submitted PMS
-   With Findings
-   Pending Action Plans
-   Waiting for FAST Confirmation
-   Confirmed Action Plans
-   Rejected Action Plans

------------------------------------------------------------------------

# Work Queue

## My Draft Checklists

Columns

-   PMS No.
-   Site
-   MHE Unit
-   Last Updated

Action

-   Continue Editing

------------------------------------------------------------------------

## My Pending Checklists

Display submitted PMS with unresolved findings.

Columns

-   PMS No.
-   Site
-   Submitted Date
-   Action Plan Status

Action

-   View

------------------------------------------------------------------------

## Action Plans Requiring Attention

Display Pending and Rejected Action Plans.

Columns

-   PMS No.
-   Checklist Item
-   Responsible
-   Due Date
-   Status

Action

-   Update Progress

------------------------------------------------------------------------

## Waiting for FAST Confirmation

Display Action Plans marked Implemented.

Columns

-   PMS No.
-   Site
-   Action Plan
-   Implemented Date

------------------------------------------------------------------------

## Recently Confirmed

Columns

-   PMS No.
-   Site
-   Action Plan
-   Confirmation Date

------------------------------------------------------------------------

# Supplier Dashboard Reports

## Monthly PMS Compliance

Bar Chart

Per Assigned Site

------------------------------------------------------------------------

## Monthly Submission Trend

Line Chart

Monthly PMS Submitted

------------------------------------------------------------------------

## Findings Summary

Bar Chart

Open Findings

Closed Findings

Rejected Findings

------------------------------------------------------------------------

## Action Plan Status Summary

Pie Chart

Pending

Waiting

Confirmed

Rejected

Cancelled

------------------------------------------------------------------------

# Dashboard Drill-down

Every KPI card and chart shall open the related List Page with filters
automatically applied.

Examples

Pending Action Plans

→ Action Plan List filtered by Pending

Waiting Confirmation

→ Confirmation List

Supplier Compliance

→ PMS List filtered by Supplier

Site Compliance

→ PMS List filtered by Site

------------------------------------------------------------------------

# Performance

-   Use aggregate SQL queries.
-   Avoid N+1 queries.
-   Cache dashboard summaries where appropriate.
-   Paginate work queues.
-   Refresh charts asynchronously.

------------------------------------------------------------------------

# UI Guidelines

Follow the approved FAST light theme.

Dashboard shall use:

-   White cards
-   Light gray page background
-   Corporate blue highlights
-   Orange accents for warnings
-   Compact tables
-   Consistent spacing
-   Minimal visual clutter

Use the approved corporate dashboard layout as visual inspiration.

The dashboard must prioritize actionable work queues above analytical
charts so users immediately see items requiring attention after login.
