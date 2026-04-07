# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working in `backend/database/`.

## Database Overview

This directory contains the Laravel schema lifecycle and the SQL Server handoff artifacts for Enterprise Payroll ERP.

There are two parallel but related sources here:
- Laravel migrations and seeders for application development and testing
- SQL Server scripts for client deployment/integration

Both should stay aligned on business meaning and contract shape.

## Structure

- `migrations/` - application schema definition
- `seeders/` - reference data and sample business data
- `factories/` - test/data generation helpers when present
- `sql/` - SQL Server scripts for tables, views, functions, procedures, and seed data

## Schema Coverage

The schema spans these domains:
1. Identity and RBAC
2. Organization and employee master data
3. Contracts, salary levels, payroll types, allowances
4. Attendance master data
5. Attendance transactional data
6. Attendance calculated data
7. Payroll parameters, payroll runs, payslips, items, adjustments
8. Reporting and system tables

## SQL Server Scripts

`database/sql/` currently contains:
- `01_tables.sql` - table creation snapshot aligned to the app schema
- `02_views.sql` - reporting and convenience views
- `03_functions.sql` - SQL scalar/business helper functions
- `04_stored_procedures.sql` - attendance, payroll, and report procedures
- `05_seed_data.sql` - SQL seed/master data snapshot

These scripts are intended for the client DB team and for SQL-first integration work.

## Seeders

Seeders populate:
- roles and permissions
- departments and positions
- users and employees
- contract and salary reference data
- shifts, holidays, and late/early rules
- attendance periods, logs, assignments, and requests
- payroll parameters, runs, and payslips
- report templates and system configuration

Seed ordering matters because many seeders assume parent records already exist.

## Commands

```bash
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
php artisan db:seed --class=UserSeeder
```

## SQL Server-Specific Notes

- money fields generally use `decimal(18,2)`
- identity insert handling exists for seed scenarios on `sqlsrv`
- SQL seed snapshots include explicit IDs where application data contracts rely on stable seeded records
- report templates store `sp_name` values for report wrapper procedures

## Alignment Rules

When changing database assets:
1. keep migrations and SQL scripts semantically aligned
2. keep seeded report template codes and `sp_name` mappings aligned with backend report logic
3. do not silently rename columns or status values used by services/frontend contracts
4. if a stored procedure replaces a service-side calculation, preserve the output shape expected by controllers and pages

## Practical Guidance

- Use migrations for application-owned schema evolution
- Use `database/sql/` when preparing SQL Server deliverables or procedure/function handoff
- Prefer additive changes over breaking schema changes
- When in doubt, verify against current models and service queries before modifying scripts
