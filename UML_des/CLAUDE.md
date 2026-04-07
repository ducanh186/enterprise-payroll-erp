# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working in `UML_des/`.

## Purpose

This directory contains UML and business-flow reference artifacts for Enterprise Payroll ERP.

These files are useful for:
- understanding domain scope
- cross-checking terminology
- validating whether code modules still map to the original business design

They are reference material, not executable source of truth over the live codebase.

## Diagrams

Typical artifacts in this folder describe:
- domain entities and their relationships
- actors and use cases
- attendance and payroll business flows
- module breakdown and sequence ideas

## How To Use This Folder

Use UML assets to:
- understand business intent before changing models or service logic
- verify naming consistency between business language and code
- explain module boundaries to stakeholders

Do not assume every diagram is perfectly up to date with current implementation. Always reconcile UML with:
- `backend/app/Models/`
- `backend/routes/api.php`
- `backend/app/Services/`
- `frontend/src/App.tsx`

## Mapping Guidelines

- entity diagrams roughly map to `backend/app/Models/`
- use cases roughly map to backend modules and frontend routed pages
- payroll and attendance sequence diagrams roughly map to service-layer flows
- report-related diagrams should be validated against both `ReportService` and SQL report procedures

## Caution

If UML and code disagree:
1. trust the current code for implementation truth
2. treat UML as business/reference context
3. update UML separately if stakeholders need documentation brought back in sync
