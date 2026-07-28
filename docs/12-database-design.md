# 12 - Database Design

# Purpose

This document defines the logical database design for the MHE Preventive
Maintenance Automation System.

The database shall be normalized to at least Third Normal Form (3NF),
maintain referential integrity through foreign keys, and use Laravel
migrations as the single source of schema management.

------------------------------------------------------------------------

# Database Engine

-   MariaDB 11.x (Preferred)
-   MySQL 8.x (Supported)

Character Set

-   utf8mb4

Collation

-   utf8mb4_unicode_ci

Storage Engine

-   InnoDB

------------------------------------------------------------------------

# Naming Standards

Tables

-   plural snake_case

Columns

-   snake_case

Primary Key

-   id (BIGINT UNSIGNED)

Foreign Keys

-   \*\_id

Audit Fields

-   created_by
-   updated_by
-   created_at
-   updated_at
-   deleted_at (Soft Delete where applicable)

------------------------------------------------------------------------

# Entity Relationship Overview

``` mermaid
erDiagram

users ||--o{ pms_headers : creates
suppliers ||--o{ users : has
sites ||--o{ pms_headers : contains
mhe_types ||--o{ pms_headers : classifies

checklist_groups ||--o{ checklist_items : contains

pms_headers ||--o{ pms_details : has

checklist_items ||--o{ pms_details : references

pms_details ||--o{ action_plans : has

action_plans ||--o{ action_plan_comments : has
```

------------------------------------------------------------------------

# Tables

## users

Purpose

System users.

Columns

-   id
-   username
-   name
-   email
-   password
-   role_id
-   supplier_id (nullable)
-   status
-   is_super_admin
-   remember_token
-   created_by
-   updated_by
-   created_at
-   updated_at
-   deleted_at

Indexes

-   username
-   email
-   supplier_id
-   role

------------------------------------------------------------------------

## suppliers

Columns

-   id
-   supplier_code
-   supplier_name
-   contact_person
-   contact_number
-   email
-   address
-   status
-   created_by
-   updated_by
-   timestamps
-   deleted_at

Indexes

-   supplier_code
-   supplier_name

------------------------------------------------------------------------

## sites

Columns

-   id
-   site_code
-   site_name
-   description
-   status
-   created_by
-   updated_by
-   timestamps
-   deleted_at

Indexes

-   site_code
-   site_name

------------------------------------------------------------------------

## supplier_sites

Purpose

Many-to-many assignment between Users and Sites.

Columns

-   id
-   user_id
-   site_id
-   timestamps

Unique Index

-   user_id + site_id

------------------------------------------------------------------------

## user_suppliers

Purpose

Many-to-many assignment between Users and Suppliers.

Columns

-   id
-   user_id
-   supplier_id
-   timestamps

Unique Index

-   user_id + supplier_id

------------------------------------------------------------------------

## mhe_types

Columns

-   id
-   code
-   description
-   status
-   created_by
-   updated_by
-   timestamps
-   deleted_at

------------------------------------------------------------------------

## checklist_groups

Columns

-   id
-   group_name
-   sequence
-   status
-   created_by
-   updated_by
-   timestamps
-   deleted_at

------------------------------------------------------------------------

## checklist_items

Columns

-   id
-   checklist_group_id
-   sequence
-   description
-   status
-   created_by
-   updated_by
-   timestamps
-   deleted_at

Foreign Keys

-   checklist_group_id

------------------------------------------------------------------------

## pms_headers

Columns

-   id
-   pms_no
-   supplier_id
-   site_id
-   technician_name
-   date_from
-   date_to
-   mhe_type_id
-   unit_number
-   serial_number
-   status
-   action_plan_status
-   created_by
-   updated_by
-   timestamps

Indexes

-   pms_no
-   supplier_id
-   site_id
-   status
-   action_plan_status
-   date_from

------------------------------------------------------------------------

## pms_details

Columns

-   id
-   pms_header_id
-   checklist_item_id
-   answer
-   remarks
-   created_by
-   updated_by
-   timestamps

Allowed Values

answer

-   Good
-   No Good

Indexes

-   pms_header_id
-   checklist_item_id

------------------------------------------------------------------------

## action_plans

Columns

-   id
-   pms_detail_id
-   title
-   description
-   responsible_person
-   timeline_from
-   timeline_to
-   status
-   confirmed_by
-   confirmed_at
-   rejected_by
-   rejected_at
-   rejection_remarks
-   created_by
-   updated_by
-   timestamps

Indexes

-   pms_detail_id
-   status
-   responsible_person

Allowed Status

-   Pending
-   Waiting for FAST Confirmation
-   Confirmed
-   Rejected
-   Cancelled

------------------------------------------------------------------------

## action_plan_comments

Columns

-   id
-   action_plan_id
-   comment
-   progress_status
-   created_by
-   created_at

Allowed Progress Status

-   Pending
-   Implemented
-   Cancelled

Business Rule

Append-only.

------------------------------------------------------------------------

## activity_logs

Columns

-   id
-   user_id
-   module
-   action
-   record_id
-   description
-   ip_address
-   user_agent
-   created_at

------------------------------------------------------------------------

# Foreign Key Rules

-   Users reference Suppliers.
-   PMS Header references Supplier, Site, MHE Type.
-   PMS Detail references PMS Header and Checklist Item.
-   Action Plan references PMS Detail.
-   Action Plan Comment references Action Plan.

Use ON UPDATE CASCADE.

Use ON DELETE RESTRICT unless business rules require otherwise.

------------------------------------------------------------------------

# Soft Delete Policy

Soft Delete

-   users
-   suppliers
-   sites
-   mhe_types
-   checklist_groups
-   checklist_items

Transactions shall never be physically deleted.

------------------------------------------------------------------------

# Indexing Strategy

Create indexes on:

-   Foreign Keys
-   Status columns
-   Frequently searched fields
-   Date columns
-   PMS Number

Avoid unnecessary indexes on low-selectivity columns.

------------------------------------------------------------------------

# Migration Order

1.  suppliers
2.  sites
3.  mhe_types
4.  checklist_groups
5.  users
6.  supplier_sites
7.  checklist_items
8.  pms_headers
9.  pms_details
10. action_plans
11. action_plan_comments
12. activity_logs

------------------------------------------------------------------------

# Seeder Order

1.  Roles
2.  Users
3.  Suppliers
4.  Sites
5.  Supplier Site Assignments
6.  MHE Types
7.  Checklist Groups
8.  Checklist Items
9.  Sample PMS
10. Sample Action Plans
11. Sample Comments

------------------------------------------------------------------------

# Database Design Principles

-   Normalize data.
-   Enforce referential integrity.
-   Avoid duplicate data.
-   Use transactions for multi-table updates.
-   Maintain complete audit history.
-   Keep the schema extensible for future enhancements without breaking
    Version 1.0.
