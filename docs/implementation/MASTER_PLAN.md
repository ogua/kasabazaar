# KasaBazaar Platform — Full Architecture Plan (All Phases)

## Context

KasaBazaar is a production shipping & logistics platform for Rose Door to Door / Kasabazaar Limited (Ghana).
Three interconnected projects:

| Project | Path | State |
|---------|------|-------|
| Laravel + Filament admin | `c:\xampp\htdocs\Projects\kasabazaar` | Production |
| React Native Staff App | `D:\Mobile\rdd-shipping` | In testing — screens broken |
| React Native Customer App | `D:\Mobile\rdd-shipping-client` | Fresh Expo template — nothing built |

**Rule**: Phases execute in order. The customer app is NOT built until Phase 2 is complete. The API contract is the single source of truth.

---

## PHASE 1 — COMPLETE SYSTEM AUDIT (FINDINGS)

Audit completed via codebase exploration. All findings below, categorized by severity.

---

### 🔴 CRITICAL — Production Breaking

#### C1. TripStatus Enum Case Mismatch
- **Laravel** (`app/Enums/TripStatus.php`) returns: `'Scheduled'`, `'InProgress'`, `'Completed'`, `'Cancelled'`, `'Delayed'`, `'Loading'`, `'Planned'`
- **Staff App** (`src/types/trip.ts`) expects: `'scheduled'` | `'in_progress'` | `'completed'` | `'cancelled'` | `'delayed'` | `'loading'` | `'planned'`
- **Impact:** Every status badge, filter, and comparison in trips/delivery screens is broken.

#### C2. DeliveryStatus Enum Case Mismatch
- **Laravel** returns: `'Pending'`, `'Delivered'`, `'Failed'`, `'Partial'`
- **Staff App** expects: `'pending'` | `'delivered'` | `'failed'` | `'partial'`
- **Impact:** Delivery status update and display in driver/trip detail screens is broken.

#### C3. `isDriver` Flag Always False — Drivers Locked Out
- **Location:** `D:\Mobile\rdd-shipping\src\stores\authStore.ts`
- Code: `isDriver: user.staff?.role?.code === "DRIVER"` — `user.staff` does not exist in the `User` type or the API response.
- `isDriver` is always `false` → drivers are sent to the staff dashboard, never the driver flow.

#### C4. `POST /reports/generate` Endpoint Does Not Exist
- **Location:** `D:\Mobile\rdd-shipping\src\api\reports.ts`
- Any screen calling `reportsApi.generate()` receives a 404.

---

### 🟠 HIGH — Broken Features / Missing Backend

#### H1. `Branch.location` Field Not in API Response
- **Screen:** `D:\Mobile\rdd-shipping\app\(app)\branch-selector.tsx:44`
- Laravel Branch response has: `id, name, slug, country, state, address, email, phone` — no `location`.
- Branch selector displays blank location text.

#### H2. `ShippingStatus` Capitalization Inconsistency
- Laravel enum value: `'Shipped'` (capital S) — all other values are lowercase.
- Technical debt: any new code written assuming all-lowercase will silently break.

#### H3. `User.roles` Array vs `User.role` String — Permissions Broken
- `authStore.hasRole()` and `can()` check `user.roles.includes(...)` (array).
- But `formatUser()` in `AuthController` returns both `role` (string) and `roles` (array from Spatie).
- Mixed usage causes permission checks to silently fail.

#### H4. No Pickup → Shipment Conversion Workflow
- `PickupSchedule` model exists with status flow. `shipment_id` FK is nullable, never auto-populated.
- No mechanism to convert a completed pickup into a Shipment.

#### H5. No Customer-Facing API Routes
- All API routes are staff/admin-facing. No customer registration, no customer shipment history, no payment initiation from mobile.
- The Filament `/client` panel is web-only; no REST API for the customer mobile app.

#### H6. No Push Notifications
- `app/Service/NotificationService.php` exists but only logs — it does not send.
- No FCM setup, no Notification classes, no device token storage.

#### H7. No Paystack Webhook / Mobile Payment Initiation
- `ShippingController` has Paystack for web redirects only.
- No `POST /api/v1/payments/initiate` endpoint for mobile to initiate a payment.
- No webhook route to confirm Paystack callbacks.
- `config/services.php` has no Paystack configuration.

---

### 🟡 MEDIUM — Maintainability / Tech Debt

#### M1. No API Resource Classes (0 of 33 controllers use them)
- All response shapes are defined inline per controller.
- Adding/removing a field requires changes in multiple places.
- No single source of truth for API shape.

#### M2. No Form Request Classes (0 found)
- All validation is inline in controllers.
- Validation logic is duplicated across create/update endpoints.

#### M3. `origin_branch_id` / `destination_branch_id` Are String Columns, Not Foreign Keys
- Migration defines them as `string`, no FK constraint.
- Data integrity not enforced at DB level.

#### M4. Tracking Model Is a Stub
- `app/Models/Tracking.php` has no fillable attributes, no relationships, no casts.
- `/shipments/{id}/trackings` endpoint returns raw untyped data.

#### M5. Two Competing Pickup Item Models
- `PickupItems` (table: `pickup_items`, FK: `shipment_id`) — appears legacy.
- `ScheduleItem` (table: `schedule_items`, FK: `pickup_schedule_id`) — current.
- Ambiguous which to use.

#### M6. No Tests
- Only 3 placeholder test files exist. Zero API, auth, payment, or business logic tests.

#### M7. `reports.ts` Export Calls Return File Downloads — Mobile Mishandles Them
- `GET /reports/*/export?format=pdf|excel` returns a file, not JSON.
- Mobile handles it as JSON → silent failure.

---

### 🔵 LOW — Security / Performance

#### L1. No Rate Limiting on Public Track Endpoint
- `GET /shipments/track/{tracking_number}` — public, no throttle middleware.

#### L2. N+1 Queries in ShipmentController@index
- `client`, `originBranch`, `destinationBranch` lazy-loaded per row (60+ queries per page).

#### L3. Cashbook Cascade Rebalance Has No DB Transaction Lock
- `CashbookEntry::rebalanceAfter()` can produce corrupted balances under concurrent writes.

---

## PHASE 2 — API CONTRACT STANDARDIZATION

**Goal:** Fix all broken data contracts before building any new features. This phase makes the staff app work correctly and establishes the API shape for the customer app.

### 2.1 Canonical Response Envelope

`BaseApiController` already implements this. Enforce consistently across all controllers.

```json
// Single resource
{ "success": true, "message": "...", "data": {} }

// Paginated
{ "success": true, "message": "...", "data": [], "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 100 } }

// Validation error
{ "success": false, "message": "Validation failed", "errors": { "field": ["message"] } }

// Generic error
{ "success": false, "message": "Human readable error" }
```

### 2.2 Step-by-Step Implementation

#### Step 1 — Fix Enum Values in Laravel (Fixes C1, C2, H2)

**Files:**
- `app/Enums/TripStatus.php` — PascalCase values → snake_case
- `app/Enums/DeliveryStatus.php` — PascalCase values → lowercase
- `app/Enums/ShippingStatus.php` — `'Shipped'` → `'shipped'`

```php
// TripStatus.php — before/after pattern
case InProgress = 'in_progress';   // was 'InProgress'
case Scheduled  = 'scheduled';     // was 'Scheduled'
case Loading    = 'loading';       // was 'Loading'
case Planned    = 'planned';       // was 'Planned'
case Completed  = 'completed';     // was 'Completed'
case Cancelled  = 'cancelled';     // was 'Cancelled'
case Delayed    = 'delayed';       // was 'Delayed'
```

**Filament impact:** After changing enum values, search all Filament resources using these enums in `SelectFilter`, `Select`, `TextColumn::badge()`, and `getColor()` methods — update the string values to match.

#### Step 2 — Create API Resource Classes (Fixes M1)

Create `app/Http/Resources/` classes. Priority set:

| Resource Class | Key Fields |
|----------------|-----------|
| `UserResource` | id, name, email, phone, avatar, role, roles[], permissions[], branch_id, branches[], client_id, staff? |
| `BranchResource` | id, name, slug, country, state, address, phone, email, **location** (computed) |
| `ShipmentResource` | core fields + client{id,name,phone}, origin_branch{id,name}, destination_branch{id,name} |
| `ShipmentDetailResource` | extends ShipmentResource + receivers[], media[], invoice, payments_total, expenses |
| `ReceiverResource` | all fields + items[] |
| `ShipmentItemResource` | id, box_no, quantity, item_cost, product{id,name} |
| `ClientResource` | id, name, email, phone, country, state_region, city, address, created_at |
| `TripResource` | id, reference, status (snake_case), vehicle{}, driver{}, shipments_count, dates |
| `TripDetailResource` | extends TripResource + shipments[] with pivot delivery_status |
| `PickupScheduleResource` | id, status, scheduled_at, pickup_location, contact_phone, client{}, assigned_staff{} |
| `InvoiceResource` | id, shipment_id, total_amount, status, shipment{} |
| `PaymentResource` | id, shipment_id, amount_usd, amount_ghs, paying_method, paid_on, reference |

**BranchResource computed field** (fixes H1):
```php
'location' => trim(collect([$this->state, $this->country])->filter()->implode(', ')),
```

#### Step 3 — Fix AuthController Login Response (Fixes H3, C3)

Ensure `formatUser()` always returns:
- `roles: string[]` — always an array, never null
- `staff?: object` — included when the user is linked to a driver staff record

```php
// In AuthController formatUser()
'roles'  => $user->getRoleNames()->toArray(),   // always array
'staff'  => $user->staff ? [                    // only when driver
    'id'          => $user->staff->id,
    'employee_id' => $user->staff->employee_id,
    'role'        => ['id' => $user->staff->role->id, 'name' => $user->staff->role->name, 'code' => $user->staff->role->code],
] : null,
```

Requires: `User` model → add `hasOne(Staff::class)` relationship (User already has `client_id`; add `staff_id` or use `hasOne` via user_id on Staff).

#### Step 4 — Wire Resources Into Priority Controllers

Update these controllers to use the new Resources:
1. `AuthController` → `UserResource`
2. `ShipmentController` → `ShipmentResource` / `ShipmentDetailResource` + eager loading fix
3. `TripController` + `DriverController` → `TripResource` / `TripDetailResource`
4. `ClientController` → `ClientResource`
5. `BranchController` → `BranchResource`
6. `PickupScheduleController` → `PickupScheduleResource`
7. `InvoiceController` → `InvoiceResource`
8. `PaymentController` → `PaymentResource`

#### Step 5 — Fix Staff App TypeScript Types (Fixes C1, C2, C3, H1, H2, H3)

Files to update in `D:\Mobile\rdd-shipping\src\`:

| File | Change |
|------|--------|
| `types/shipment.ts` | `ShippingStatus`: `'Shipped'` → `'shipped'` |
| `types/trip.ts` | `TripStatus` → snake_case values; `DeliveryStatus` → lowercase |
| `types/auth.ts` | `Branch`: add `location: string`; `User`: add `roles: string[]`, `staff?: StaffRef \| null` |
| `stores/authStore.ts` | `isDriver` check: `user.roles?.includes('driver')` or `user.staff?.role?.code === 'DRIVER'` |
| `app/(app)/branch-selector.tsx` | `{item.location}` now works after BranchResource fix |
| `api/reports.ts` | Remove or comment out `generate()` method (endpoint doesn't exist) |

#### Step 6 — Add Throttle to Public Endpoint (Fixes L1)

`routes/api.php`: add `throttle:60,1` middleware to the public track endpoint.

#### Step 7 — Add Eager Loading to ShipmentController@index (Fixes L2)

```php
Shipment::with([
    'client:id,name,phone',
    'originBranch:id,name',
    'destinationBranch:id,name',
])->...->paginate();
```

---

## PHASE 3 — PICKUP TO SHIPMENT CONVERSION WORKFLOW

**Current state:** `PickupSchedule` has status flow `scheduled → confirmed → in-progress → completed → cancelled`. The `shipment_id` FK exists but is never populated. No conversion mechanism exists.

### 3.1 Pickup Status Flow (from project)
```
created → scheduled → confirmed → in-progress → completed → [converted | cancelled]
```

### 3.2 Shipment Status Flow (from project)
```
pending → pickup → shipped → cleared → delivered
cancelled (exit at any point)
```

### 3.3 Database Changes

**Migration: add conversion tracking to pickup_schedules**
```php
$table->timestamp('converted_at')->nullable();
$table->foreignUuid('converted_by')->nullable()->constrained('users');
```

**Migration: add pickup_schedule_id to shipments (conversion history)**
```php
$table->foreignUuid('pickup_schedule_id')->nullable()->constrained('pickup_schedules');
```

### 3.4 Conversion Logic — New Service Class

**Create:** `app/Services/PickupConversionService.php`

```php
public function convert(PickupSchedule $pickup, User $convertedBy): Shipment
{
    // Validate: status must be 'completed'
    // Create Shipment from pickup data (client, branch, items, location)
    // Auto-generate shipping_reference and tracking_number
    // Link: $pickup->update(['shipment_id' => $shipment->id, 'converted_at' => now(), 'converted_by' => $convertedBy->id])
    // Return the new Shipment
}
```

**Data mapping:** PickupSchedule → Shipment field map:
- `client_id` → `client_id`
- `branch_id` → `branch_id`
- `pickup_location` → stored in shipment notes/origin
- `ScheduleItem[]` (product_id, quantity, weight) → `ShipmentItem[]`
- Status → `pending` (just created from pickup)
- Auto-generate: `tracking_number`, `shipping_reference`

### 3.5 Filament Admin Action

In `app/Filament/Admin/Resources/PickupScheduleResource.php`:
- Add `Action::make('convert_to_shipment')` visible only when `status === 'completed'`
- Opens confirmation modal showing pickup summary
- On confirm: calls `PickupConversionService::convert()`
- Shows success toast with new shipment reference
- Redirects to ShipmentResource edit page for the new shipment

### 3.6 API Endpoint

`POST /api/v1/pickup-schedules/{id}/convert`
- Auth required, permission: `create_shipment`
- Validates: pickup status is `completed`
- Returns: the newly created `ShipmentResource`

### 3.7 Files to Create/Modify

| File | Action |
|------|--------|
| `app/Services/PickupConversionService.php` | Create |
| `app/Http/Controllers/Api/V1/PickupScheduleController.php` | Add `convert()` method |
| `routes/api.php` | Add `POST /pickup-schedules/{id}/convert` |
| `app/Filament/.../PickupScheduleResource.php` | Add convert Action |
| New migration | `add_conversion_fields_to_pickup_schedules` |
| New migration | `add_pickup_schedule_id_to_shipments` |
| `app/Models/PickupSchedule.php` | Add `shipment()`, `convertedBy()` relations |
| `app/Models/Shipment.php` | Add `pickupSchedule()` relation |

---

## PHASE 4 — CUSTOMER MOBILE APP

**Starting point:** `D:\Mobile\rdd-shipping-client` — fresh Expo 56 template. No API layer, no state management.

### 4.1 New Laravel Customer API Routes

Add a new route group `routes/api.php`:

```php
Route::prefix('customer')->group(function () {
    // Public
    Route::post('/auth/register', [CustomerAuthController::class, 'register']);
    Route::post('/auth/verify-email', [CustomerAuthController::class, 'verifyEmail']);
    Route::post('/auth/forgot-password', [CustomerAuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [CustomerAuthController::class, 'resetPassword']);

    // Authenticated customers
    Route::middleware(['auth:sanctum', 'customer'])->group(function () {
        Route::get('/auth/me', [CustomerAuthController::class, 'me']);
        Route::put('/auth/profile', [CustomerAuthController::class, 'updateProfile']);
        Route::put('/auth/password', [CustomerAuthController::class, 'changePassword']);

        Route::get('/shipments', [CustomerShipmentController::class, 'index']);
        Route::get('/shipments/{id}', [CustomerShipmentController::class, 'show']);

        Route::get('/pickups', [CustomerPickupController::class, 'index']);
        Route::post('/pickups', [CustomerPickupController::class, 'store']);
        Route::put('/pickups/{id}', [CustomerPickupController::class, 'update']);  // before approval only
        Route::delete('/pickups/{id}', [CustomerPickupController::class, 'destroy']); // cancel

        Route::get('/invoices', [CustomerInvoiceController::class, 'index']);
        Route::get('/invoices/{id}', [CustomerInvoiceController::class, 'show']);
        Route::get('/invoices/{id}/pdf', [CustomerInvoiceController::class, 'downloadPdf']);

        Route::get('/payments', [CustomerPaymentController::class, 'index']);
        Route::post('/payments/initiate', [CustomerPaymentController::class, 'initiate']); // Paystack
        Route::post('/payments/verify', [CustomerPaymentController::class, 'verify']);

        Route::get('/notifications', [CustomerNotificationController::class, 'index']);
        Route::put('/notifications/{id}/read', [CustomerNotificationController::class, 'markRead']);
        Route::post('/device-tokens', [CustomerNotificationController::class, 'storeDeviceToken']);

        Route::get('/complaints', [CustomerComplaintController::class, 'index']);
        Route::post('/complaints', [CustomerComplaintController::class, 'store']);
        Route::get('/complaints/{id}', [CustomerComplaintController::class, 'show']);

        Route::post('/ratings', [CustomerRatingController::class, 'store']);
    });

    // Paystack webhook (public, verified by signature)
    Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle']);
});
```

**New middleware:** `customer` — verifies `auth()->user()->client_id !== null` (user must be a customer, not staff).

### 4.2 New Laravel Backend Files for Customer API

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Api/V1/Customer/CustomerAuthController.php` | Register, verify, forgot/reset password, me, profile |
| `app/Http/Controllers/Api/V1/Customer/CustomerShipmentController.php` | Customer's own shipments only |
| `app/Http/Controllers/Api/V1/Customer/CustomerPickupController.php` | Request/manage pickups |
| `app/Http/Controllers/Api/V1/Customer/CustomerInvoiceController.php` | View/download invoices |
| `app/Http/Controllers/Api/V1/Customer/CustomerPaymentController.php` | Initiate/verify Paystack payments |
| `app/Http/Controllers/Api/V1/Customer/CustomerNotificationController.php` | Notifications + device token storage |
| `app/Http/Controllers/Api/V1/Customer/CustomerComplaintController.php` | Submit/track complaints |
| `app/Http/Controllers/Api/V1/Customer/CustomerRatingController.php` | Rate delivered shipments |
| `app/Http/Controllers/Api/V1/PaystackWebhookController.php` | Handle Paystack event callbacks |
| `app/Services/CustomerAuthService.php` | Registration, email verification, password reset |
| `app/Services/PaystackPaymentService.php` | Initiate, verify, webhook validation |
| `app/Notifications/EmailVerificationNotification.php` | Verification email |
| `app/Notifications/PasswordResetNotification.php` | Password reset email |
| `app/Notifications/ShipmentStatusNotification.php` | FCM push for shipment updates |
| `app/Models/DeviceToken.php` | Store FCM tokens per user |
| New migration | `create_device_tokens_table` (user_id, token, platform, created_at) |
| `app/Http/Middleware/EnsureCustomer.php` | `customer` middleware |
| `config/paystack.php` | Paystack keys + webhook secret |

### 4.3 Customer App Screen Structure

Expo Router file-based routing in `src/app/`:

```
src/app/
├── _layout.tsx                    (root: providers, fonts, splash)
├── (auth)/
│   ├── _layout.tsx
│   ├── login.tsx
│   ├── register.tsx
│   ├── verify-email.tsx
│   ├── forgot-password.tsx
│   └── reset-password.tsx
├── (app)/
│   ├── _layout.tsx                (check auth, redirect)
│   ├── (tabs)/
│   │   ├── _layout.tsx            (bottom tabs)
│   │   ├── home/
│   │   │   └── index.tsx          (dashboard: active shipments, quick actions)
│   │   ├── shipments/
│   │   │   ├── index.tsx          (shipment list)
│   │   │   └── [id].tsx           (shipment detail + timeline)
│   │   ├── pickups/
│   │   │   ├── index.tsx          (pickup history)
│   │   │   ├── request.tsx        (request new pickup)
│   │   │   └── [id].tsx           (pickup detail)
│   │   └── account/
│   │       ├── index.tsx          (profile overview)
│   │       ├── edit.tsx           (edit profile/photo)
│   │       ├── change-password.tsx
│   │       ├── notifications.tsx
│   │       └── complaints/
│   │           ├── index.tsx
│   │           ├── new.tsx
│   │           └── [id].tsx
├── track/
│   └── [tracking_number].tsx      (public tracking — no auth)
├── invoices/
│   ├── index.tsx
│   └── [id].tsx
├── payments/
│   ├── index.tsx
│   ├── pay.tsx                    (Paystack checkout)
│   └── success.tsx
└── ratings/
    └── [shipment_id].tsx
```

### 4.4 Customer App Tech Stack

Adopt the patterns proven in the staff app:

| Concern | Choice | Rationale |
|---------|--------|-----------|
| HTTP | Axios + interceptors (copy from staff app) | Consistent with staff app |
| Server state | React Query | Already used in staff app |
| Auth state | Zustand + SecureStore | Same as staff app |
| Forms | React Hook Form + Zod | Same as staff app |
| Navigation | Expo Router | Same as staff app |
| Push notifs | `expo-notifications` + FCM | Standard Expo approach |
| Payments | `react-native-paystack-webview` | Ghana market primary payment |
| PDF view | `expo-sharing` + `expo-file-system` | Download & open PDF invoices |
| Image pick | `expo-image-picker` | Profile photo + complaint images |

### 4.5 Customer App API Layer (`src/api/`)

Mirror the staff app's pattern. Files to create:

```
src/api/
├── client.ts          (axios instance — copy staff app, point to /api/v1/customer)
├── auth.ts            (register, login, verifyEmail, forgotPassword, resetPassword, me, updateProfile)
├── shipments.ts       (index, show — customer's own shipments only)
├── pickups.ts         (index, store, update, destroy)
├── invoices.ts        (index, show, downloadPdf)
├── payments.ts        (index, initiate, verify)
├── notifications.ts   (index, markRead, storeDeviceToken)
├── complaints.ts      (index, store, show)
├── ratings.ts         (store)
└── track.ts           (publicTrack — no auth, shared with staff app endpoint)
```

### 4.6 Customer App Types (`src/types/`)

```
src/types/
├── auth.ts            (CustomerUser, LoginResponse, RegisterDto)
├── shipment.ts        (Shipment, ShipmentTimeline, ShipmentMedia — read-only subset)
├── pickup.ts          (PickupSchedule, CreatePickupDto)
├── invoice.ts         (Invoice with nested shipment)
├── payment.ts         (Payment, PaystackInitResponse)
├── notification.ts    (Notification, DeviceTokenDto)
├── complaint.ts       (Complaint, CreateComplaintDto)
├── rating.ts          (Rating, CreateRatingDto)
└── common.ts          (ApiResponse, PaginatedResponse, Branch)
```

---

## PHASE 5 — LARAVEL BACKEND IMPROVEMENTS

### 5.1 Form Request Classes (Fixes M2)

Create `app/Http/Requests/` for all write operations. Priority:

| Request Class | Replaces |
|--------------|---------|
| `StoreShipmentRequest` | inline validation in ShipmentController@store |
| `UpdateShipmentRequest` | inline validation in ShipmentController@update |
| `StorePaymentRequest` | inline validation in PaymentController@store |
| `StorePickupScheduleRequest` | inline validation in PickupScheduleController@store |
| `UpdatePickupScheduleRequest` | inline validation in PickupScheduleController@update |
| `StoreTripRequest` | inline validation in TripController@store |
| `UpdateDeliveryRequest` | inline validation in TripController@updateDelivery |
| `CustomerRegisterRequest` | new |
| `StoreComplaintRequest` | new |
| `InitiatePaymentRequest` | new |

Pattern: each Request class has `authorize()` (return true or permission check) and `rules()`.

### 5.2 Service Layer

Create `app/Services/`:

| Service | Responsibilities |
|---------|----------------|
| `ShipmentService` | createShipment, updateShipment, generateReference, generateTracking |
| `PaymentService` | recordPayment, updateShipmentPaymentStatus, convertCurrency |
| `CustomerAuthService` | register, sendVerificationEmail, verifyEmail, forgotPassword, resetPassword |
| `PaystackPaymentService` | initiate, verify, handleWebhook, validateSignature |
| `PushNotificationService` | sendToUser, sendToMultiple, storeDeviceToken, removeToken |
| `PickupConversionService` | convert (Phase 3) |

Move business logic out of controllers into services. Controllers become thin: validate → call service → return resource.

### 5.3 Notifications System

**Laravel Notifications setup:**

1. Create `app/Notifications/`:
   - `ShipmentStatusUpdated` — push + email when shipment status changes
   - `PaymentReceived` — push when payment recorded
   - `PickupScheduled` — push when pickup confirmed
   - `PickupConverted` — push when pickup becomes shipment
   - `EmailVerification` — email with OTP or link
   - `PasswordReset` — email with reset link

2. Create `app/Models/DeviceToken.php` + migration
3. `PushNotificationService` wraps FCM HTTP v1 API directly (no extra package needed)
4. Register `ShipmentObserver`, `PaymentObserver` to fire notifications on model events

### 5.4 Fix `origin_branch_id` / `destination_branch_id` (Fixes M3)

Migration: convert string columns to proper UUID foreign keys referencing `branches.id`.
```php
// New migration: fix_branch_fks_on_shipments
$table->foreignUuid('origin_branch_id')->nullable()->change()->constrained('branches');
$table->foreignUuid('destination_branch_id')->nullable()->change()->constrained('branches');
```
**Risk:** Existing data must be verified before running. Add a pre-check that all existing values are valid branch UUIDs.

### 5.5 Fix Tracking Model (Fixes M4)

`app/Models/Tracking.php` — define proper attributes and relationship:
```php
protected $fillable = ['shipment_id', 'status', 'description', 'location', 'status_updated_at', 'recorded_by'];
public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
```
Create `TrackingResource` for consistent serialization.

### 5.6 Resolve Duplicate Pickup Item Models (Fixes M5)

Audit usage of `PickupItems` (pickup_items table) vs `ScheduleItem` (schedule_items table).
- If `pickup_items` has no live data/references in controllers → create a migration to drop it and remove the model.
- If it has references → migrate data to `schedule_items` format, then drop.

### 5.7 Paystack Configuration

Add to `config/services.php`:
```php
'paystack' => [
    'public_key'      => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key'      => env('PAYSTACK_SECRET_KEY'),
    'webhook_secret'  => env('PAYSTACK_WEBHOOK_SECRET'),
    'payment_url'     => 'https://api.paystack.co',
],
```

---

## PHASE 6 — MOBILE APP ARCHITECTURE

### 6.1 Staff App — Targeted Improvements (Do Not Rebuild)

The staff app has a solid foundation. Targeted fixes only:

1. **Fix the 5 critical type/bug issues** from Phase 2 (already covered)
2. **Shared mutation hook** — create `src/hooks/useMutation.ts` wrapping React Query's `useMutation` with standard `onSuccess`/`onError` toast pattern
3. **Standardize error extraction** — create `src/utils/apiError.ts`:
   ```typescript
   export const extractError = (err: unknown): string =>
     (err as any)?.response?.data?.message ?? 'Something went wrong';
   ```
   Replace all `error?.response?.data?.message ?? 'Error occurred'` patterns.
4. **Driver status fix** — after Phase 2 fixes, verify driver flow end-to-end

### 6.2 Customer App — Architecture from Scratch

Build with the right structure from day one. Do not copy the staff app's flat file structure.

**Feature-based directory:**
```
src/
├── api/                  (HTTP layer — all API calls)
├── app/                  (Expo Router screens)
├── components/
│   ├── ui/               (Button, Card, Input, StatusBadge, EmptyState, Skeleton, OfflineBanner)
│   ├── shipments/        (ShipmentCard, TrackingTimeline, StatusBadge)
│   ├── pickups/          (PickupCard, PickupForm)
│   └── payments/         (PaymentCard, PaystackModal)
├── stores/
│   ├── authStore.ts      (Zustand: user, token, branch)
│   └── settingsStore.ts  (Zustand: theme, notifications)
├── hooks/
│   ├── useAuth.ts        (login, logout, register mutations)
│   ├── useShipments.ts   (React Query wrappers)
│   ├── usePickups.ts
│   └── useNotifications.ts (push notification registration)
├── types/                (TypeScript interfaces)
├── utils/
│   ├── apiError.ts       (error message extractor)
│   ├── currency.ts       (GHS/USD formatting)
│   └── date.ts           (date formatting helpers)
└── constants/
    ├── Colors.ts          (adopt staff app palette: primary #1A3C5E, accent #F4A225)
    ├── Spacing.ts
    └── Typography.ts
```

### 6.3 Offline Handling (Both Apps)

Staff app already has `OfflineBanner`. Customer app should adopt same pattern:
- `NetInfo` listener → Zustand `isOnline` flag
- `OfflineBanner` component: slide-in banner when offline
- React Query `networkMode: 'offlineFirst'` for read queries
- Write mutations: queue in AsyncStorage when offline, replay on reconnect (use `react-native-queue` or simple custom queue)

### 6.4 TypeScript Strict Mode

Both apps already have `"strict": true` in tsconfig. Maintain this — do not relax.

---

## PHASE 7 — PERFORMANCE

### 7.1 API Response Size

- Use `only()` / `makeHidden()` on resources to exclude unused fields from list endpoints
- List endpoints return minimal fields; detail endpoints return full data
- Example: `ShipmentResource` (list) omits `media`, `expenses`; `ShipmentDetailResource` includes them

### 7.2 Database Query Optimization

| Query | Fix |
|-------|-----|
| ShipmentController@index | Eager load client, originBranch, destinationBranch |
| TripController@index | Eager load vehicle, driver |
| DriverController@trips | Eager load vehicle, shipments.client, shipments.receivers |
| ClientController@index | Add index on `clients.branch_id` |
| PaymentController@index | Add index on `payments.shipment_id`, `payments.branch_id` |

### 7.3 Database Indexes to Add

Migration `add_performance_indexes`:
```php
Schema::table('shipments', function (Blueprint $table) {
    $table->index(['branch_id', 'status']);
    $table->index(['container_number', 'status']);
    $table->index('tracking_number');
});
Schema::table('payments', function (Blueprint $table) {
    $table->index(['shipment_id', 'branch_id']);
});
Schema::table('pickup_schedules', function (Blueprint $table) {
    $table->index(['client_id', 'status']);
    $table->index(['branch_id', 'status']);
});
```

### 7.4 Mobile Performance

- React Query `staleTime: 5 * 60 * 1000` for lookup data (branches, products) — reduce refetches
- Shipment list: use `FlashList` instead of `FlatList` for better performance at scale
- Images: `expo-image` with `contentFit="cover"` and `cachePolicy="memory-disk"`
- Pagination: `fetchNextPage` on scroll — already implemented in staff app, adopt in customer app

### 7.5 Cashbook Concurrency (Fixes L3)

Wrap `CashbookEntry::rebalanceAfter()` in a DB transaction with row locking:
```php
DB::transaction(function () use ($entry) {
    CashbookEntry::where('date', '>=', $entry->date)->lockForUpdate()->get()
        ->each(fn($e) => $e->computeRunningBalance());
});
```

---

## PHASE 8 — DELIVERABLES FORMAT

For every issue implemented, the output follows this structure:

1. **Problem:** What is broken and why
2. **Impact:** What fails in production because of it
3. **Solution:** The code change with before/after
4. **Migration steps:** Any DB changes required (with rollback plan)
5. **Testing strategy:** How to verify the fix works end-to-end

---

## PAYROLL & CASHBOOK MODULE — SPECIFIC BUGS

### Payroll — Critical Implementation Gaps

#### PAY-1. 🔴 Payroll Entry CREATE Endpoint Completely Missing
- **`PayrollEntryController`** has only `show()` and `update()` — no `store()` action.
- **Route:** `GET|PUT /payroll-entries/{id}` only — no `POST /payroll-entries`.
- **Impact:** Payroll entries cannot be created via the API. The UI in `payroll/[id].tsx` shows entries but they must be auto-created by a backend process that doesn't exist.
- **Fix:** Add `store()` to `PayrollEntryController` — accepts `payroll_period_id`, `staff_id`, salary fields. Alternatively, add a `POST /payroll-periods/{id}/generate-entries` endpoint that auto-creates entries from all active staff salaries for the branch.

#### PAY-2. 🔴 Computed Payroll Fields Allowed as Writable Input
- **Controller validation** allows `gross_pay`, `total_deductions`, `net_salary` as PUT body fields.
- **Model booted hook** auto-computes these on every save — overwriting whatever the user sent.
- **Impact:** Misleading — API accepts them, ignores them, and overwrites. Any UI that lets users edit these fields is non-functional.
- **Fix:** Remove `gross_pay`, `total_deductions`, `net_salary` from `PayrollEntryController` validation rules. Mark them read-only. Only `base_salary`, `overtime`, `bonus`, `allowances`, `tax`, `ssnit`, `other_deductions` should be writable.

#### PAY-3. 🟠 PayrollPeriod UPDATE Allows Unvalidated Date Changes
- **Controller:** `start_date`, `end_date` included in `$request->only([...])` for update but not in validation rules.
- **Impact:** Unvalidated date strings pass through to the model, potentially breaking `end_date` >= `start_date` invariant.
- **Fix:** Add `'start_date' => 'sometimes|date'`, `'end_date' => 'sometimes|date|after_or_equal:start_date'` to `UpdatePayrollPeriod` rules.

#### PAY-4. 🟠 Missing Unique Constraint — Duplicate Entries Per Staff/Period
- **Migration:** `payroll_entries` table has no unique constraint on `(payroll_period_id, staff_id)`.
- **Impact:** Same staff member can have multiple entries in the same payroll period — double payment risk.
- **Fix:** New migration: `$table->unique(['payroll_period_id', 'staff_id'])`.

#### PAY-5. 🟠 Staff Update Allows Unvalidated Fields
- **Controller:** `hire_date` and `user_id` included in `StaffController::update()` `$request->only()` but not in validation.
- **Fix:** Add `'hire_date' => 'sometimes|date'`, `'user_id' => 'sometimes|nullable|uuid|exists:users,id'` to update validation.

#### PAY-6. 🟡 StaffRole Index Returns Only Active Roles — No Way to List All
- `StaffRoleController::index()` filters `is_active = true` hardcoded.
- No `?include_inactive=true` parameter.
- **Fix:** Add optional `include_inactive` query param, default false.

#### PAY-7. 🟡 Staff Employment Status Mismatch
- **Staff App list filter** shows: `active`, `on_leave`, `terminated`, `resigned`
- **Staff App create form** shows: `active`, `on_leave`, `probation`, `terminated`, `resigned`
- **Laravel validation** accepts: `active`, `inactive`, `terminated`, `on_leave`
- **Mismatches:**
  - `resigned` — in mobile but NOT in Laravel enum → silently rejected by validation
  - `probation` — in create form but NOT in Laravel enum → silently rejected
  - `inactive` — in Laravel enum but NOT in mobile filter
- **Fix:** Decide canonical set of statuses. Add `resigned` and `probation` to `EmploymentStatus` enum if they're real business states, OR remove them from the mobile UI.

#### PAY-8. 🟡 No UI for Marking Individual Entries as Paid
- `PayrollEntry` has `status: pending | approved | paid` and `paid_at`, `payment_reference` fields.
- **Staff App:** `payroll/[id].tsx` shows entries but only `Approve Period` action exists — no per-entry `Mark as Paid`.
- **Fix (Phase 6):** Add entry-level action button in period detail to set `status='paid'` + `paid_at` + `payment_reference`.

---

### Cashbook — Critical Implementation Gaps

#### CB-1. 🔴 `cost_center` Validation Accepts Any String
- **Controller validation:** `'cost_center' => 'required|string'` — no enum check.
- **Impact:** Any invalid string (typo, wrong value) passes validation and gets stored. The model's `fillAnalysisColumn()` method uses `CashbookCostCenter::from($value)` which will **throw a `ValueError`** at runtime if an invalid value is stored and later retrieved.
- **Fix:** Change validation to: `'cost_center' => 'required|in:' . implode(',', array_column(CashbookCostCenter::cases(), 'value'))`.

#### CB-2. 🟠 CashbookDirectorAccount Missing UPDATE and DELETE Endpoints
- **Controller:** Only `index()` and `store()` defined.
- **Impact:** Director account entries cannot be corrected or removed after creation.
- **Fix:** Add `update()` and `destroy()` methods + routes.

#### CB-3. 🟠 CashbookWithholdingTax Missing `softDeletes` — Inconsistent with Other Tables
- **WHT migration** has no `deleted_at` column or `softDeletes()`.
- All other cashbook tables (`cashbook_entries`, `cashbook_loans`, `cashbook_director_accounts`) use soft deletes.
- **Fix:** New migration to add `deleted_at` to `cashbook_withholding_tax`. Add `SoftDeletes` trait to `CashbookWithholdingTax` model.

#### CB-4. 🟡 Cashbook Cost Centers Fully Hardcoded in Mobile UI
- **Staff App:** 21 cost center values are hardcoded in the `COST_CENTERS` array in `cashbook/index.tsx`.
- Not fetched from an API endpoint.
- **Impact:** If backend enum changes (add/rename a cost center), mobile must be updated separately — easy to fall out of sync.
- **Fix (Phase 6):** Add `GET /lookup/cashbook-cost-centers` endpoint returning all `CashbookCostCenter` enum cases. Mobile fetches and caches this list. (Low priority — enum is stable.)

#### CB-5. 🟡 Auto-Computed Balance Logic Inconsistency
- **CashbookEntry model:** balances and analysis columns auto-computed in `booted()` observer.
- **CashbookLoan, CashbookWHT, CashbookDirectorAccount controllers:** `cl_balance` / `wht_amount` computed manually in controller code.
- **Impact:** No single pattern — harder to maintain, easier to introduce bugs.
- **Fix (Phase 5):** Move computation logic for Loan, WHT, and Director Account into their respective model `booted()` hooks (or `saving` observer). Use `saveQuietly()` only when explicitly needed to avoid recursion.

#### CB-6. 🟡 Analysis Columns Always Returned in Response — Payload Bloat
- Every `CashbookEntry` response includes all 21 analysis columns even though only one will ever be non-null.
- **Impact:** ~21 extra null fields per entry in the response payload.
- **Fix (Phase 2):** `CashbookEntryResource` should use `when()` to only include non-null analysis columns, OR include them only in the detail view (not the list).

#### CB-7. 🟡 Cashbook Entry Response Structure — Extra Nesting in Mobile
- **Staff App:** Uses `extractArray<CashbookEntry>(entriesQ.data?.data?.data)` — triple `.data` nesting.
- Other modules use `data.data` (double).
- **Impact:** Suggests the cashbook list endpoint wraps data in an extra object layer vs the standard `paginated()` response.
- **Fix:** Verify `CashbookEntryController::index()` uses `BaseApiController::paginated()` consistently. If it returns `{success, data: {entries: [...], meta: {...}}}` instead of `{success, data: [...], meta: {...}}`, fix to match the standard.

#### CB-8. 🟡 Income `payment_method` Value Mismatch
- **Staff App income form:** Hardcoded payment methods include `'momo'` as a value.
- **Laravel `PaymentMethod` enum:** Uses `'mobile_money'` (not `'momo'`).
- **Impact:** Income records with `payment_method = 'momo'` stored in DB won't match enum — model will fail to cast.
- **Fix:** Change mobile income form value from `'momo'` → `'mobile_money'`. Also audit if `Income` model uses `PaymentMethod` enum cast — add it if missing.

#### CB-9. 🟡 Expense API Uses FormData (Multipart) but No File Upload UI
- `expenses.ts` sends `FormData` for both create and update.
- The expense form in `expenses/create.tsx` has no file picker or receipt upload UI.
- **Impact:** FormData works for text fields but unnecessarily complex. If a receipt upload feature is planned, the UI needs to be added. If not, switch to JSON.
- **Fix (Phase 6):** Add receipt upload to expense form (`expo-image-picker`) and include `receipt_path` field in `Expense` model. OR switch to JSON if receipt upload is not planned.

---

## API RESPONSE vs MOBILE TYPE — FIELD-BY-FIELD COMPARISON

### Shipment Entity

| Field | Laravel Returns | Mobile Expects | Status |
|-------|----------------|----------------|--------|
| `id` | string | string | ✅ |
| `shipping_reference` | string\|null | string\|null | ✅ |
| `tracking_number` | string\|null | string\|null | ✅ |
| `status` | ShippingStatus | ShippingStatus | ⚠️ Case mismatch (C1/H2) |
| `payment_status` | PaymentStatus | PaymentStatus | ✅ |
| `shipping_cost` | number\|null | number\|null | ✅ |
| `total` | number | number | ✅ |
| `paid` | number | number | ✅ |
| `total_ghs` | number\|null | number\|null | ✅ |
| `exchange_rate_at_shipment` | number\|null | number\|null | ✅ |
| `container_number` | number\|null | number\|null | ✅ |
| `shipped_at` | string\|null | string\|null | ✅ |
| `estimated_delivery_date` | string\|null | string\|null | ✅ |
| `delivered_at` | string\|null | string\|null | ✅ |
| `created_at` | string | string | ✅ |
| `client` | {id,name,phone} | ShipmentClient | ✅ |
| `branch_id` | string | **NOT in mobile type** | 🔴 Missing in mobile |
| `origin_branch` | {id,name} | **NOT in mobile type** | 🔴 Missing in mobile |
| `destination_branch` | {id,name} | **NOT in mobile type** | 🔴 Missing in mobile |
| `vat` | number | number | ✅ |
| `vat_percentage` | number | number | ✅ |
| `insurance` | number\|null | number\|null | ✅ |
| `insurance_accepted` | boolean | boolean | ✅ (after cast fix) |
| `is_received` | 0\|1 (not cast!) | boolean | ⚠️ Type mismatch — needs CAST-1 fix |
| `external_token` | string | **NOT in mobile type** | 🔴 Missing in mobile |
| `client_existence` | string | **NOT in mobile type** | 🔴 Missing in mobile |
| `recorderd_by` | string (typo) | **NOT in mobile type** | Typo, ignored by mobile |
| `receivers` | Receiver[] (detail only) | Receiver[] | ✅ |
| `media` | {type, file_path, stage, caption, ...}[] | ShipmentMedia[] | ⚠️ See MEDIA-1 below |

#### MEDIA-1. 🟠 ShipmentMedia Field Name Mismatch
- **Laravel returns key:** `type` (e.g., `'image'` or `'video'`)
- **Mobile type definition** (`ShipmentMedia`): expects `media_type`
- **Impact:** Media type is `undefined` in all shipment detail screens. Video/image type detection breaks.
- **Fix:** Either rename the key in `ShipmentResource` to `media_type`, OR rename the mobile type field to `type`.

---

### Trip Entity

| Field | Laravel Returns | Mobile Expects | Status |
|-------|----------------|----------------|--------|
| `id` | string | string | ✅ |
| `trip_reference` | string | string | ✅ |
| `origin` | string | string | ✅ |
| `destination` | string | string | ✅ |
| `route_description` | string\|null | string\|null | ✅ |
| `distance_km` | number\|null | number\|null | ✅ |
| `scheduled_date` | string (not cast!) | string\|null | ⚠️ Needs CAST-2 fix for consistency |
| `scheduled_departure` | string\|null | string\|null | ✅ |
| `actual_departure` | string\|null | string\|null | ✅ |
| `actual_arrival` | string\|null | string\|null | ✅ |
| `status` | `'InProgress'` (PascalCase) | `'in_progress'` (snake) | 🔴 CRITICAL — C1 |
| `fuel_cost` | number\|null | number\|null | ✅ |
| `toll_fees` | number\|null | number\|null | ✅ |
| `driver_allowance` | number\|null | number\|null | ✅ |
| `other_costs` | number\|null | number\|null | ✅ |
| `total_cost` | number | number | ✅ |
| `start_mileage` | number\|null | number\|null | ✅ |
| `end_mileage` | number\|null | number\|null | ✅ |
| `notes` | string\|null | string\|null | ✅ |
| `vehicle` | {id, registration_number, make, model} | TripVehicle | ✅ |
| `driver` | {id, name} | TripStaff | ✅ |
| `assistant` | {id, name}\|null | TripStaff\|null | ✅ |
| `branch_id` | string | **NOT in mobile type** | 🔴 Missing in mobile |
| `delivery_status` (TripShipment pivot) | `'Pending'` (PascalCase) | `'pending'` | 🔴 CRITICAL — C2 |

---

### Dashboard Summary Entity

| Field | Laravel Returns | Mobile Expects | Status |
|-------|----------------|----------------|--------|
| `shipments_this_month` | number | number | ✅ |
| `pending_payments_count` | number | `pending_payments` | 🔴 Field name mismatch |
| `revenue_this_month_ghs` | number | `revenue_ghs` | 🔴 Field name mismatch |
| `revenue_usd` | **NOT returned** | number | 🔴 Missing in Laravel |
| `active_trips` | number | number | ✅ |
| `pending_shipments` | number | number | ✅ |
| `in_transit_shipments` | number | number | ✅ |
| `delivered_this_month` | number | number | ✅ |

**Fix:** Update `DashboardController::summary()` to return `pending_payments`, `revenue_ghs`, `revenue_usd` (or update mobile type to match Laravel's field names — pick one and be consistent).

---

### PickupSchedule Entity

| Field | Laravel Returns | Mobile Expects | Status |
|-------|----------------|----------------|--------|
| `assigned_staff` (response key) | {id, name}\|null | `assignedUser` | 🔴 Key name mismatch |
| All other fields | All present | All present | ✅ |

**Fix:** Standardize to `assigned_staff` — update mobile type to use `assigned_staff` instead of `assignedUser`.

---

### Lookup Entities

| Lookup | Field | Laravel | Mobile | Status |
|--------|-------|---------|--------|--------|
| `LookupProduct` | `value` | number | number\|string | ⚠️ Mobile allows string — unnecessary |
| `LookupBranch` | `location` | **NOT returned** | string | 🔴 Missing (fixed by BranchResource — Phase 2 Step 2) |
| `LookupStaff` | `role` object | **NOT returned** | not in type | ✅ Consistent — both don't have it |

---

### User/Auth Entity — No TypeScript Interface Defined

The mobile app has no `User` interface in `src/types/auth.ts`. The login response returns:
```
{ id, name, email, phone, avatar, role, client_id, branch_id, branches, permissions, roles, token }
```

The Zustand store casts this as `any` in several places. This is a type safety gap — TypeScript strict mode is enabled but the User shape isn't formally typed.

**Fix:** Define complete `User` interface in `src/types/auth.ts` matching the `AuthController::formatUser()` response.

---

## FLEET, VEHICLES, QUOTATIONS, FEEDBACK — SPECIFIC BUGS

### Fleet / Vehicles

#### FL-1. 🟠 Vehicle Status Enum Mismatch — Create Form Only Sends 2 of 4 Values
- **UI create form** sends: `'available'` or `'maintenance'`
- **Backend `VehicleStatus` enum:** `available | in_use | maintenance | retired`
- **Impact:** Cannot set vehicle to `in_use` or `retired` via mobile. This means vehicle status management is incomplete — you cannot retire a vehicle from the mobile app.
- **Fix:** Add `'in_use'` and `'retired'` to the vehicle status picker in `vehicles/create.tsx` and `vehicles/[id].tsx`.

#### FL-2. 🟠 Vehicle Create Sends `branch_id: ''` (Empty String)
- **Code:** `branch_id: ''` is hardcoded.
- **Impact:** Vehicle is created without a branch association, violating the multi-tenant model. All vehicle queries scope by branch.
- **Fix:** Pass `currentBranchId` from `useAuthStore()` instead.

#### FL-3. 🟡 No Shipment Assignment UI in Trip Detail
- Trip detail shows the "Shipments" tab but only allows updating delivery status.
- There is no "Add Shipment to Trip" button.
- **Impact:** Shipments must be manually linked to trips via the web portal. Drivers see an empty shipments tab.
- **Fix (Phase 6):** Add a shipment picker in the trip detail screen. Call `PUT /trips/{id}/shipments` (if endpoint exists) or `PATCH /shipments/{id}` with trip_id to assign.

#### FL-4. 🟡 Missing Delete Buttons — Multiple Screens
Backend supports DELETE for all of these; mobile doesn't expose it:
| Screen | Route | API Endpoint |
|--------|-------|-------------|
| Vehicles | `vehicles/[id].tsx` | `DELETE /vehicles/{id}` |
| Trips | `trips/[id].tsx` | `DELETE /trips/{id}` |
| Quotations | `quotations/[id].tsx` | `DELETE /quotations/{id}` |
| Pickup Schedules | `pickups/[id].tsx` | No delete route exists in Laravel |

- **Fix (Phase 6):** Add delete action with confirmation dialog to each detail screen where the API supports it.

#### FL-5. 🟡 Vehicle Maintenance — No Edit/Delete for Records
- Maintenance records can be added (`POST /vehicles/{id}/maintenances`) but not edited or deleted via mobile.
- **Fix (Phase 6):** Add swipe-to-delete and edit actions to maintenance list items.

---

### Pickup Status — Hyphen vs Underscore Issue

#### PK-1. 🔴 `'in-progress'` (hyphen) vs Likely `'in_progress'` (underscore)
- **Mobile UI hardcodes:** `'in-progress'` (with hyphen) as a status chip value.
- **Laravel `PickupSchedule::STATUSES`:** Constant array has `'in-progress'` (with hyphen) — confirmed from model.
- **BUT:** After Phase 2 enum standardization, there could be pressure to normalize this to `'in_progress'` to match `TripStatus::InProgress = 'in_progress'`.
- **Recommendation:** Leave `PickupSchedule` status as string constants (not a PHP enum), keeping hyphenated `'in-progress'`. Do not normalize — it's consistent within itself.

---

### Contact Messages — Status Mismatch

#### MSG-1. 🟠 Mobile Status Values Don't Match Laravel
- **Mobile filter shows:** `'new' | 'read' | 'replied' | 'closed'`
- **Laravel `ContactMessage::STATUSES`:** `'pending' | 'read' | 'replied'`
- **Mismatches:**
  - `'new'` in mobile → `'pending'` in Laravel — filter sends wrong value, always returns zero results
  - `'closed'` in mobile → doesn't exist in Laravel — always returns zero results
- **Fix:** Change mobile filter values to: `'pending' | 'read' | 'replied'`. Remove `'new'` and `'closed'` options.

---

### Feedback — Read-Only Problem

#### FB-1. 🟡 No Reply/Response Capability in Feedback or Messages Screens
- Both `feedback/index.tsx` and `messages/index.tsx` are list-only.
- Staff cannot respond to customer feedback or contact messages from mobile.
- **Backend supports:** `PUT /feedback/{id}` with status/internal_notes and `PUT /contact-messages/{id}` with reply.
- **Fix (Phase 6):** Add detail screens for both, with a reply text field and status update action.

---

### Quotations

#### QT-1. 🟡 No Edit/Delete for Quotations
- Quotation detail is view/export only.
- **Fix (Phase 6):** Add edit and delete actions.

#### QT-2. 🟡 Quotation HTML Export Has Hardcoded Company Name
- PDF footer: `"Rose Door to Door Delivery & Logistics — Kasabazaar Limited"` — hardcoded string.
- **Fix (Phase 5):** Pull branch name from Zustand store and embed dynamically.

---

## PHASE 2A — COLUMN & FIELD-LEVEL BUG FIXES (Must Complete Before Any New Features)

This section captures every concrete bug found in the database schema, model definitions, and API response fields. These must be fixed first — they affect every feature built on top.

---

### Database Schema Bugs

#### DB-1. `discount` Column Referenced But Never Created
- **File:** `database/migrations/2024_12_24_000001_add_shipping_enhancements_to_shipments_table.php`
- **Line:** `$table->decimal('insurance', 10, 2)->default(0)->after('discount');`
- **Bug:** The `after('discount')` positioning reference assumes a `discount` column exists, but no migration ever creates it.
- **Risk:** On a fresh `migrate:fresh`, the `after()` call silently positions the column differently on MySQL or throws an error on strict engines.
- **Fix:** Remove `->after('discount')` (or create the discount column if it's intended).

#### DB-2. Column Typos in Shipments Table — In Production
These column names were written wrong in the original migration and are now locked into the production database:

| Actual DB Column | Should Have Been | Impact |
|-----------------|-----------------|--------|
| `recorderd_by` | `recorded_by` | All code must use the typo — confusing |
| `is_diclaimer_aggred` | `is_disclaimer_agreed` | Same |
| `is_agreement_aggred` | `is_agreement_agreed` | Same |

- **Fix (Phase 5):** Create a migration to rename all three columns. Until then, all code must use the typo names. Document clearly.

#### DB-3. Payment Migration Syntax Error — Line 20
- **File:** `database/migrations/2024_12_20_155916_create_payments_table.php`
- **Line:** `enum('payment_type',['debit','credit'])->nullable("debit")`
- **Bug:** `->nullable()` does not accept parameters. The intent was `->default('debit')->nullable()`.
- **Fix:** Migration may already be applied in production — create a new migration to set the default correctly if needed. Verify in production.

#### DB-4. Receivers Table — Redundant `->nullable()` Calls
- **File:** receivers migration — lines 20-24 call `->nullable()->nullable()` on 5 columns.
- **Impact:** Functional (MySQL ignores the duplicate), but misleading to read.
- **Fix:** Cosmetic — acceptable as-is, note for cleanup.

---

### Model Casting Bugs

#### CAST-1. Boolean Columns Not Cast in Shipment Model
These columns are `boolean` in the DB but not in `$casts` in `app/Models/Shipment.php`:

| Column | DB Type | Missing Cast |
|--------|---------|--------------|
| `is_received` | boolean | `'is_received' => 'boolean'` |
| `signed_received_form` | boolean | `'signed_received_form' => 'boolean'` |
| `is_diclaimer_aggred` | boolean | `'is_diclaimer_aggred' => 'boolean'` |
| `is_agreement_aggred` | boolean | `'is_agreement_aggred' => 'boolean'` |

- **Impact:** API returns `0` / `1` instead of `false` / `true`. Mobile TypeScript types expect `boolean` — comparisons like `if (shipment.is_received)` may behave unexpectedly.
- **Fix:** Add all four to `$casts` in `Shipment.php`.

#### CAST-2. `scheduled_date` Missing Datetime Cast in Trip Model
- **File:** `app/Models/Trip.php`
- **Column:** `scheduled_date` — defined as `dateTime` in migration but not in `$casts`.
- **Impact:** Returns raw string instead of formatted ISO datetime. Mobile `new Date(trip.scheduled_date)` may fail on some formats.
- **Fix:** Add `'scheduled_date' => 'datetime'` to Trip `$casts`.

#### CAST-3. `balance` and `change` Not Cast in Payment Model
- **File:** `app/Models/Payment.php`
- **Impact:** Monetary values returned as strings without decimal precision.
- **Fix:** Add `'balance' => 'decimal:2'`, `'change' => 'decimal:2'` to Payment `$casts`.

---

### Controller Response / Relationship Name Mismatches

#### RESP-1. `assigned_staff` vs `assignedUser` in PickupScheduleController
- **Controller loads:** `->with('assignedUser:id,name')` (relationship named `assignedUser`)
- **Controller returns:** response key `assigned_staff`
- **Mobile type expects:** `assigned_staff` (matches the response, not the relationship name)
- **Fix:** Rename the relationship in `PickupSchedule` model from `assignedUser()` to `assignedStaff()`, OR consistently map it in a `PickupScheduleResource`. Document the intended response key.

#### RESP-2. `enteredby` vs `entered_by` in PaymentController
- **Model relationship:** `enteredby()` (camelCase run-together)
- **Controller loads:** `enteredby:id,name`
- **Controller returns key:** `entered_by`
- **Fix:** Rename the model relationship to `enteredBy()` (standard camelCase) and update all references. Map to `entered_by` in `PaymentResource`.

#### RESP-3. `container_number` Returned from Shipment but Not in Shipments Table
- **ShipmentController `formatShipment()`:** returns `container_number` as `$s->container_number`
- **The `container_number` column** lives in `shipment_containers` table, not `shipments`.
- **Shipment has:** `belongsTo(ShipmentContainer)` via `container_number` FK — so `$s->container_number` IS a column on shipments (it's the FK). Confirm via migration.
- **Fix:** Verify the column exists in shipments table migration. If it does, add explicit cast. If not, load via `container->container_number`.

---

### Staff App — Specific Field-Level Bugs

#### MOB-1. `shipment_type` Sent Alongside `client_existence` — Duplicate Intent
- **File:** `D:\Mobile\rdd-shipping\app\(app)\(main)\(tabs)\shipments\create.tsx`
- **Sends:** both `shipment_type: shipmentType` AND `client_existence: "new" | "existing"`
- **Laravel controller expects:** Only `client_existence` (per controller validation rules).
- **Fix:** Remove `shipment_type` from the POST body in `create.tsx`.

#### MOB-2. Receiver ID Fields Never Sent During Shipment Creation
- **Type definition:** `receiver_id_type: string | null`, `receiver_id_number: string | null` — present in `Receiver` interface.
- **Create form sends:** everything except `receiver_id_type` and `receiver_id_number`.
- **Impact:** These fields are always null for shipments created via mobile, even if entered on the form. Data loss.
- **Fix:** Add `receiver_id_type` and `receiver_id_number` to the receiver loop in `create.tsx`.

#### MOB-3. VAT Percentage Initialized as Empty String
- **File:** `create.tsx`
- **Code:** `const [vatPercentage, setVatPercentage] = useState("")`
- **Sent as:** `vat_percentage: vatPercentage ? parseFloat(vatPercentage) : undefined`
- **Risk:** If Laravel validates `vat_percentage` as `numeric`, an empty string triggers validation failure. The `undefined` fallback works, but the default should be `"0"` not `""`.
- **Fix:** Initialize as `useState("0")`.

#### MOB-4. Trip Status Transitions Incomplete in Driver App
- **File:** `D:\Mobile\rdd-shipping\app\(app)\driver\trips\[id].tsx`
- **Driver UI only handles:** `planned → in_progress → completed`
- **Defined statuses:** `planned | scheduled | loading | in_progress | completed | cancelled | delayed`
- **Impact:** Driver cannot mark a trip as `cancelled` or `delayed` via the app.
- **Fix:** Add `cancelled` and `delayed` to the `STATUS_FLOW` array. Add a "Report Problem" action that allows selecting `delayed` or `cancelled` with a required reason note.

#### MOB-5. `LookupStaff` Type Missing Role Code
- **Type:** `LookupStaff { id, name, phone, position, staff_role_id, employee_id }`
- **Missing:** `role: { code: string }` — needed for driver filtering client-side.
- **Fix:** Add `role?: { id: string; name: string; code: string }` to `LookupStaff` if the `/lookup/staff` endpoint returns it. Otherwise use `drivers_only: true` param server-side.

#### MOB-6. Payment `amount` Field Sent Redundantly with `amount_usd`
- **Sends:** `amount: usd` AND `amount_usd: usd` — same value, two field names.
- **Laravel model:** Has both `amount` and `amount_usd` columns (separate purpose — `amount` is the general ledger amount, `amount_usd` is the currency-explicit amount).
- **Fix:** Clarify intent — send `amount_usd` for the USD value and leave `amount` for the system to compute if it differs.

---

### Missing Implementation Gaps (New API Endpoints That Don't Exist Yet)

| Missing Endpoint | Needed By | Priority |
|-----------------|-----------|---------|
| `POST /customer/auth/register` | Customer App auth | P1 |
| `POST /customer/auth/verify-email` | Customer App auth | P1 |
| `POST /customer/auth/forgot-password` | Customer App auth | P1 |
| `POST /customer/auth/reset-password` | Customer App auth | P1 |
| `GET /customer/shipments` | Customer App shipments | P1 |
| `POST /pickup-schedules/{id}/convert` | Pickup→Shipment workflow | P1 |
| `POST /customer/payments/initiate` | Customer App Paystack | P1 |
| `POST /customer/payments/verify` | Customer App Paystack | P1 |
| `POST /customer/webhooks/paystack` | Payment confirmation | P1 |
| `POST /customer/device-tokens` | Push notifications | P2 |
| `GET /customer/complaints` | Customer App | P2 |
| `POST /customer/complaints` | Customer App | P2 |
| `POST /customer/ratings` | Customer App post-delivery | P2 |
| `GET /customer/invoices/{id}/pdf` | Customer App download | P2 |
| `GET /customer/notifications` | Customer App | P2 |

---

## GAP SUMMARY (Items Not in Original Scope That Were Discovered)

| Gap | Phase | Priority |
|----|-------|---------|
| No customer registration/auth endpoints in Laravel | Phase 4 | P1 |
| No FCM push notification infrastructure | Phase 4/5 | P1 |
| No Paystack webhook handler for mobile payments | Phase 4/5 | P1 |
| `POST /reports/generate` endpoint missing | Phase 2 | P1 |
| `User` has no `hasOne(Staff)` relationship (needed for driver detection) | Phase 2 | P1 |
| Filament Client panel is web-only, no REST API layer | Phase 4 | P1 |
| Tracking model is a stub with no attributes | Phase 5 | P2 |
| Two competing pickup item models (`PickupItems` vs `ScheduleItem`) | Phase 5 | P2 |
| `config/services.php` missing Paystack config entirely | Phase 4 | P1 |
| `origin_branch_id` / `destination_branch_id` are not FK columns | Phase 5 | P2 |
| Zero test coverage | Phase 5 | P3 |

---

## EXECUTION ORDER

```
Phase 1  → COMPLETE (audit done)
Phase 2  → Fix enums → Create Resources → Fix Auth response → Fix mobile types → Add eager loading
Phase 3  → Conversion migration → PickupConversionService → Filament action → API endpoint
Phase 5  → Form Requests → Services → Notifications → Paystack config → Fix Tracking model
Phase 4  → Customer API routes → Customer controllers → Customer mobile app (screens + API layer)
Phase 6  → Staff app targeted fixes → Customer app architecture polish
Phase 7  → DB indexes → Query optimization → Mobile FlashList + caching
Phase 8  → Write deliverable docs per issue as work completes
```

---

## FILES CREATED / MODIFIED SUMMARY

### Laravel — New Files
- `app/Http/Resources/` — 12 Resource classes
- `app/Http/Requests/` — 10 Form Request classes
- `app/Http/Controllers/Api/V1/Customer/` — 8 controllers
- `app/Http/Controllers/Api/V1/PaystackWebhookController.php`
- `app/Services/` — 6 Service classes
- `app/Notifications/` — 5 Notification classes
- `app/Models/DeviceToken.php`
- `app/Http/Middleware/EnsureCustomer.php`
- `config/paystack.php`
- 5+ new migrations

### Laravel — Modified Files
- `app/Enums/TripStatus.php`, `DeliveryStatus.php`, `ShippingStatus.php`
- `app/Http/Controllers/Api/V1/AuthController.php`
- `app/Http/Controllers/Api/V1/ShipmentController.php`
- `app/Http/Controllers/Api/V1/TripController.php`
- `app/Http/Controllers/Api/V1/DriverController.php`
- `app/Http/Controllers/Api/V1/BranchController.php`
- `app/Http/Controllers/Api/V1/PickupScheduleController.php`
- `app/Http/Controllers/Api/V1/InvoiceController.php`
- `app/Http/Controllers/Api/V1/PaymentController.php`
- `app/Models/User.php` (add hasOne Staff)
- `app/Models/Tracking.php` (define attributes)
- `app/Models/Shipment.php` (add pickupSchedule relation)
- `app/Models/PickupSchedule.php` (add conversion relations)
- `routes/api.php` (new customer routes, throttle)
- Filament resources using changed enums (PickupScheduleResource, TripResource, etc.)

### Staff App (`D:\Mobile\rdd-shipping`) — Modified Files
- `src/types/shipment.ts`
- `src/types/trip.ts`
- `src/types/auth.ts`
- `src/stores/authStore.ts`
- `src/api/reports.ts`

### Customer App (`D:\Mobile\rdd-shipping-client`) — New Files
- Full `src/` directory structure (API layer, stores, hooks, types, components, utils, constants)
- All screen files in `src/app/`
