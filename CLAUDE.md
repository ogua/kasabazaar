# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

```bash
# Start all dev services (server + queue + logs + Vite) concurrently
composer run dev

# Or individually:
php artisan serve
npm run dev
php artisan queue:listen
php artisan pail          # real-time log viewer

# Migrations
php artisan migrate
php artisan migrate:fresh --seed

# Code style
./vendor/bin/pint

# Tests
php artisan test
php artisan test --filter TestClassName

# Filament
php artisan filament:upgrade
php artisan shield:generate --all    # regenerate RBAC permissions
php artisan shield:super-admin --user=EMAIL

# Custom commands
php artisan app:exchange-rate        # sync exchange rates
php artisan sitemap:generate
```

## Architecture Overview

**Kasabazaar** is a multi-tenant shipping & logistics platform for Rose Door to Door / Kasabazaar Limited (Ghana). It manages shipments, fleet, payroll, financials, and a cashbook — served via a Filament admin panel and a REST API consumed by a mobile app.

### Multi-Tenancy: Branch as Tenant

`Branch` is the tenant model. Users belong to multiple branches via a `BelongsToMany` pivot. Every resource (Shipment, Trip, Staff, etc.) is scoped to a branch. The admin panel path is `/admin/{branch_slug}/...`.

`AdminPanelProvider` wires this up — it registers `RegisterBranch` as the tenant registration page and `SyncShieldTenant` middleware keeps Filament Shield permissions in sync per-tenant switch.

### Two Filament Panels

| Panel | Path | Purpose |
|-------|------|---------|
| `admin` | `/admin` | Staff-facing full management UI (multi-tenant by Branch) |
| `client` | `/client` | Client-facing shipment tracking — 4 resources filtered by `ShippingStatus` |

The admin panel uses primary color `#A0043C` (maroon) / info `#003151` (dark blue). Navigation is organized into groups: **Cashbook**, **Fleet Management**, **Finance**, **Payroll**, **Staff Management**, **Messaging**, **Customer Feedback**, **Reports**, **Website**, **Roles & Permissions**.

### REST API (`/api/v1`)

Authentication via Laravel Sanctum bearer tokens. All protected routes use `auth:sanctum`. Resources follow Laravel's `apiResource` pattern with driver-specific routes under `/api/v1/driver/*`.

The API mirrors the admin panel's domain: shipments, clients, invoices, payments, expenses, incomes, cashbook, fleet (vehicles/trips), payroll, and reports.

### Key Domain Models

- **`Shipment`** — Core entity. Uses `HasUuids`. Stores `exchange_rate_at_shipment` (GHS/USD at creation time). Has items, media, messages, container, invoice, payments, expenses.
- **`Trip`** — Groups shipments for a vehicle+driver route. `BelongsToMany` Shipments via `trip_shipments`.
- **`CashbookEntry`** — Main ledger. `booted()` auto-cascades balance recalculation across entries and auto-updates monthly income/expenditure ledger tables on every save.
- **`Branch`** — Tenant root. Nearly every model `BelongsTo Branch`.
- **`User`** — Implements `FilamentUser` + `HasTenants`. Uses Sanctum API tokens + Spatie roles/permissions.

All primary-key models use `HasUuids`. Soft deletes on `Trip` (and some others).

### Financial / Cashbook Module

Seven DB tables: `cashbook_entries`, `cashbook_shipment_details`, `cashbook_income_ledger`, `cashbook_expenditure_ledger`, `cashbook_director_account`, `cashbook_loans`, `cashbook_withholding_tax`.

Enums: `CashbookCostCenter` (21 cases), `IncomeLedgerType`, `ExpenditureLedgerType` (19 cases). The `analysis` column on `cashbook_entries` is auto-filled from `cost_center` via model observer logic in `booted()`.

Filament page: `MonthlyCashbook` at `/admin/{tenant}/monthly-cashbook`.

### Authorization

Filament Shield (wraps Spatie Permissions) manages RBAC. Permissions are prefixed: `view_`, `view_any_`, `create_`, `update_`, `restore_`, `delete_`, `force_delete_` per resource. A `super_admin` role bypasses all gates. 40+ Policy classes provide fine-grained model authorization.

### PDF & Excel Generation

- PDFs (packing slips, invoices, shipping labels, quotations): `barryvdh/laravel-dompdf` + `spatie/laravel-pdf`, generated via web routes like `/packing-slip/{id}`.
- Excel exports: `maatwebsite/excel` via 5 Export classes in `app/Exports/`.

### Exchange Rate

`worksome/exchange` handles USD↔GHS conversion. Rates are synced via `php artisan app:exchange-rate` (scheduled). Shipments snapshot the rate at creation in `exchange_rate_at_shipment`.

### AppServiceProvider Notes

- `Model::unguard()` is called globally — all models are mass-assignable.
- Custom `LoginResponse` binding redirects after Filament login.
- Paystack facade aliased as `Paystack`.
