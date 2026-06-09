# KasaBazaar — Audit Quick Reference
Generated: 2026-06-05 | Full plan: MASTER_PLAN.md

---

## 🔴 Fix Immediately (Production Breaking)

| ID | Location | Issue | Fix |
|----|----------|-------|-----|
| C1 | `app/Enums/TripStatus.php` | PascalCase values (`'InProgress'`) — mobile expects snake_case (`'in_progress'`) | Change all enum values to snake_case |
| C2 | `app/Enums/DeliveryStatus.php` | PascalCase values (`'Pending'`) — mobile expects lowercase | Change all enum values to lowercase |
| C3 | `D:\Mobile\rdd-shipping\src\stores\authStore.ts` | `isDriver` always false — `user.staff` doesn't exist | Use `user.roles.includes('driver')` |
| C4 | `D:\Mobile\rdd-shipping\src\api\reports.ts` | `POST /reports/generate` endpoint does not exist in Laravel | Remove call or add endpoint |
| CB-1 | `CashbookEntryController.php` | `cost_center` validation accepts any string — invalid values crash on retrieval | Validate against enum cases |
| PAY-1 | `PayrollEntryController.php` | No `store()` action — entries cannot be created via API | Add store() or generate-entries endpoint |

---

## 🟠 Fix Before New Features

| ID | Location | Issue |
|----|----------|-------|
| H1 | Branch selector (mobile) | `Branch.location` field missing from API response |
| H2 | `app/Enums/ShippingStatus.php` | `'Shipped'` (capital S) inconsistent with other lowercase values |
| H3 | Auth store / User type | `user.roles` (array) vs `user.role` (string) — permissions broken |
| H5 | `routes/api.php` | No customer-facing API routes exist |
| H6 | `app/Service/NotificationService.php` | Notifications only log, never send — no FCM setup |
| H7 | `config/services.php` | No Paystack config — mobile payment impossible |
| DB-1 | Migration `2024_12_24_000001` | `->after('discount')` references non-existent column |
| DB-3 | Migration `2024_12_20_155916` | `->nullable("debit")` — invalid PHP syntax in enum column |
| CAST-1 | `app/Models/Shipment.php` | 4 boolean columns not cast — API returns 0/1 instead of true/false |
| CAST-2 | `app/Models/Trip.php` | `scheduled_date` missing datetime cast |
| CAST-3 | `app/Models/Payment.php` | `balance` and `change` not cast as decimal |
| MEDIA-1 | ShipmentMedia response | Laravel returns `type` but mobile type expects `media_type` |
| Dashboard | `DashboardController.php` | `pending_payments_count` ≠ `pending_payments`; `revenue_this_month_ghs` ≠ `revenue_ghs`; `revenue_usd` missing entirely |
| FL-2 | `vehicles/create.tsx` | `branch_id: ''` hardcoded — vehicles created with no branch |
| MSG-1 | `messages/index.tsx` | Filter sends `'new'`/`'closed'` — Laravel only knows `'pending'`/`'read'`/`'replied'` |
| CB-8 | `incomes/create.tsx` | `'momo'` sent for payment method — Laravel expects `'mobile_money'` |
| MOB-2 | `shipments/create.tsx` | `receiver_id_type` and `receiver_id_number` never sent — data loss |
| PAY-2 | `PayrollEntryController.php` | Computed fields (gross_pay etc.) accepted as input but immediately overwritten |
| PAY-4 | `payroll_entries` migration | No unique constraint on (payroll_period_id, staff_id) — double-pay risk |

---

## 🟡 Fix in Phase 5–6

| ID | Issue |
|----|-------|
| DB-2 | Column typos in production: `recorderd_by`, `is_diclaimer_aggred`, `is_agreement_aggred` |
| M1 | No API Resource classes — response shapes inconsistent |
| M2 | No Form Request classes — validation scattered |
| M3 | `origin_branch_id`/`destination_branch_id` are string columns, not FK |
| M4 | Tracking model is a stub |
| M5 | Two competing pickup item models |
| CB-2 | CashbookDirectorAccount missing UPDATE/DELETE endpoints |
| CB-3 | CashbookWithholdingTax missing softDeletes |
| CB-7 | Cashbook response has extra nesting — triple `.data.data.data` in mobile |
| PAY-7 | Employment status mismatch: `resigned`/`probation` in mobile, not in Laravel enum |
| FL-1 | Vehicle status only 2 values in UI — `in_use` and `retired` missing |
| PK-1 | Pickup `'in-progress'` confirmed correct — do NOT change |
| MOB-1 | `shipment_type` sent alongside `client_existence` — redundant field |
| MOB-4 | Driver trip status transitions incomplete — `cancelled`/`delayed` not in UI |
| RESP-1 | `assigned_staff` vs `assignedUser` — pick one and standardize |
| RESP-2 | `enteredby()` relationship vs `entered_by` response key in Payment |

---

## Missing Features to Build (Not Bugs — New Work)

| Feature | Phase | Endpoint |
|---------|-------|---------|
| Pickup → Shipment conversion | Phase 3 | `POST /pickup-schedules/{id}/convert` |
| Customer registration | Phase 4 | `POST /api/v1/customer/auth/register` |
| Customer email verification | Phase 4 | `POST /api/v1/customer/auth/verify-email` |
| Customer password reset | Phase 4 | `POST /api/v1/customer/auth/forgot-password` |
| Customer shipment list | Phase 4 | `GET /api/v1/customer/shipments` |
| Customer pickup request | Phase 4 | `POST /api/v1/customer/pickups` |
| Paystack mobile initiation | Phase 4 | `POST /api/v1/customer/payments/initiate` |
| Paystack webhook | Phase 4 | `POST /api/v1/customer/webhooks/paystack` |
| FCM push notifications | Phase 4/5 | Device token storage + FCM HTTP v1 |
| Customer complaints | Phase 4 | `POST /api/v1/customer/complaints` |
| Customer ratings | Phase 4 | `POST /api/v1/customer/ratings` |
| Invoice PDF download | Phase 4 | `GET /api/v1/customer/invoices/{id}/pdf` |
| Add shipments to trip (mobile) | Phase 6 | Use existing trip endpoints |
| Per-entry payroll mark-as-paid | Phase 6 | `PUT /payroll-entries/{id}` with status=paid |
| Feedback reply from mobile | Phase 6 | `PUT /feedback/{id}` |
| Receipt upload for expenses | Phase 6 | Update expense form + S3/local storage |

---

## API Response vs Mobile — Field Match Summary

| Entity | Fields Matching | Field Mismatches | Notes |
|--------|----------------|-----------------|-------|
| Shipment | ~20 fields ✅ | `is_received` (0/1 vs bool), `media.type` vs `media_type`, extra `branch_id`, `origin_branch`, `destination_branch` in response | 3 critical mismatches |
| Trip | ~18 fields ✅ | `status` PascalCase, `delivery_status` PascalCase | 2 critical — C1, C2 |
| Client | All fields ✅ | None | Good |
| Vehicle | All fields ✅ | None | Good |
| PickupSchedule | All fields ✅ | `assigned_staff` vs `assignedUser` key name | 1 key name issue |
| Dashboard | 5 of 8 fields ✅ | `pending_payments_count`, `revenue_this_month_ghs` wrong names; `revenue_usd` missing | Dashboard KPIs all broken |
| User | N/A | No User TypeScript interface defined at all | Auth works but no type safety |
| Lookup | All ✅ | `Branch.location` missing | Fixed by BranchResource |
