# Kasabazaar Mobile App — React Native Implementation Plan

> **Company:** Rose Door to Door and Delivery Company / Kasabazaar Limited
> **Platform:** React Native (Expo SDK 51) — iOS & Android
> **API Backend:** Laravel (this project) — fully implemented at `/api/v1`
> **Design:** Professional, navy + amber theme, Ghana-focused UX
> **Server api directory:** C:\xampp\htdocs\Projects\kasabazaar

---

## Table of Contents

1. [Tech Stack](#1-tech-stack)
2. [Design System](#2-design-system)
3. [Authentication & Biometrics](#3-authentication--biometrics)
4. [Permission Matrix](#4-permission-matrix)
5. [Navigation Structure](#5-navigation-structure)
6. [Screen-by-Screen Spec](#6-screen-by-screen-spec)
7. [API Endpoints Reference](#7-api-endpoints-reference)
8. [State Management](#8-state-management)
9. [Offline Support](#9-offline-support)
10. [File & Folder Structure](#10-file--folder-structure)
11. [Key Implementation Notes](#11-key-implementation-notes)
12. [Implementation Phases](#12-implementation-phases)

---

## 1. Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Framework | React Native (Expo) | SDK 51 |
| Language | TypeScript | 5.x |
| Navigation | React Navigation | v6 (Stack + Bottom Tabs + Drawer) |
| State — local | Zustand | 4.x |
| State — server | TanStack Query | v5 |
| Auth tokens | expo-secure-store | latest |
| Biometrics | expo-local-authentication | latest |
| HTTP | Axios | 1.x |
| Forms | React Hook Form + Zod | latest |
| Icons | @expo/vector-icons (MaterialCommunityIcons) | latest |
| Charts | Victory Native XL | latest |
| Camera / Media | expo-image-picker + expo-camera | latest |
| Print / Share | expo-print + expo-sharing | latest |
| File system | expo-file-system | latest |
| Notifications | expo-notifications | latest |
| Network detect | @react-native-community/netinfo | latest |
| Lists | @shopify/flash-list | latest |

---

## 2. Design System

### Color Palette

```typescript
// src/theme/colors.ts
export const Colors = {
  primary:    '#1A3C5E',   // deep navy blue
  accent:     '#F4A225',   // amber/gold
  success:    '#2ECC71',
  warning:    '#F39C12',
  danger:     '#E74C3C',
  info:       '#3498DB',

  bgLight:    '#F5F7FA',
  bgDark:     '#0D1B2A',
  surfLight:  '#FFFFFF',
  surfDark:   '#1A2B3C',
  textPrimary:'#1A1A2E',
  textSecond: '#6B7280',
  border:     '#E5E7EB',
  borderDark: '#2D3F50',
};
```

### Typography

```typescript
// src/theme/typography.ts
export const Typography = {
  h1:      { fontSize: 28, fontWeight: '700' },
  h2:      { fontSize: 22, fontWeight: '600' },
  h3:      { fontSize: 18, fontWeight: '600' },
  body:    { fontSize: 15, fontWeight: '400' },
  caption: { fontSize: 12, fontWeight: '400' },
  label:   { fontSize: 13, fontWeight: '500' },
};
```

### Spacing (4px grid)

```typescript
export const Spacing = { xs: 4, sm: 8, md: 16, lg: 24, xl: 32, '2xl': 48 };
```

### Component Standards

| Component | Spec |
|-----------|------|
| Card | 12px radius, shadow elevation 3 |
| Button (primary) | 10px radius, 48px min-height, accent bg |
| Input | 10px radius, 1px border, 48px height |
| Bottom Tab | 60px height, icon + label |
| Header | 56px height, primary bg |
| Status badge | Pill, 6px radius, colored bg + text |
| FAB | 56px, circular, accent bg, shadow 6 |

### Status Color Map

| Status | Color | Use |
|--------|-------|-----|
| `pending` | Warning `#F39C12` | Shipments, payments, feedback |
| `in transit` | Info `#3498DB` | Shipment |
| `delivered` | Success `#2ECC71` | Shipment, delivery |
| `cancelled` | Danger `#E74C3C` | Any |
| `paid` | Success `#2ECC71` | Payment |
| `partial` | Warning `#F39C12` | Payment |
| `planned` | Info `#3498DB` | Trip |
| `in_progress` | Accent `#F4A225` | Trip |
| `completed` | Success `#2ECC71` | Trip |
| `delayed` | Danger `#E74C3C` | Trip |
| `available` | Success `#2ECC71` | Vehicle |
| `in_use` | Info `#3498DB` | Vehicle |
| `maintenance` | Warning `#F39C12` | Vehicle |
| `retired` | Danger `#E74C3C` | Vehicle |
| `draft` | Secondary `#6B7280` | Payroll, invoices |
| `approved` | Info `#3498DB` | Payroll |

---

## 3. Authentication & Biometrics

### Auth Flow

```
App Launch
  └─► Check SecureStore for token
        ├─► No token → LoginScreen
        └─► Token exists
              └─► GET /api/v1/auth/me (validate token)
                    ├─► 401 → Clear store → LoginScreen
                    └─► 200 → Check biometric setting
                              ├─► biometric_enabled = false → Dashboard
                              └─► biometric_enabled = true
                                    └─► Show BiometricPromptScreen
                                          ├─► Success → Dashboard
                                          └─► Fail/Cancel → LoginScreen
```

### SecureStore Keys

| Key | Value |
|-----|-------|
| `@kasaba/token` | Sanctum bearer token |
| `@kasaba/user` | Serialized User JSON |
| `@kasaba/branch_id` | Active branch UUID |

### AsyncStorage Keys

| Key | Value |
|-----|-------|
| `@kasaba/biometric_enabled` | `'true'` / `'false'` |
| `@kasaba/theme` | `'light'` / `'dark'` |

### Biometric Implementation

```typescript
import * as LocalAuthentication from 'expo-local-authentication';

export async function authenticateWithBiometrics(): Promise<boolean> {
  const enrolled = await LocalAuthentication.isEnrolledAsync();
  if (!enrolled) return true; // skip biometric if not set up

  const result = await LocalAuthentication.authenticateAsync({
    promptMessage: 'Verify your identity',
    fallbackLabel: 'Use Password',
    disableDeviceFallback: false,
  });
  return result.success;
}
```

### Axios Interceptor (401 handling)

```typescript
// src/api/client.ts
api.interceptors.response.use(
  res => res,
  async error => {
    if (error.response?.status === 401) {
      await SecureStore.deleteItemAsync('@kasaba/token');
      useAuthStore.getState().logout();
      // navigate to login
    }
    return Promise.reject(error);
  }
);
```

---

## 4. Permission Matrix

Permissions are returned from `/api/v1/auth/login` as `permissions[]`. The backend uses Spatie with filament-shield naming: `{action}_{model}`.

### Roles

| Role | Description |
|------|-------------|
| `admin` | Full access, all branches |
| `branch_personnel` | Branch-scoped access |
| `customer` | Tracking + feedback only |
| *(driver staff)* | Driver-specific endpoints only; identified via `staff.role.code === 'DRIVER'` |

### Module Permission Map

| Module | View List | View Detail | Create | Edit | Delete |
|--------|-----------|-------------|--------|------|--------|
| Dashboard | — (all auth) | — | — | — | — |
| Shipments | `view_any_shipment` | `view_shipment` | `create_shipment` | `update_shipment` | `delete_shipment` |
| Clients | `view_any_client` | `view_client` | `create_client` | `update_client` | `delete_client` |
| Products | `view_any_product` | `view_product` | `create_product` | `update_product` | `delete_product` |
| Quotations | `view_any_quotation` | `view_quotation` | `create_quotation` | `update_quotation` | `delete_quotation` |
| Invoices | `view_any_invoice` | `view_invoice` | — | `update_invoice` | — |
| Payments | `view_any_payment` | `view_payment` | `create_payment` | — | — |
| Expenses | `view_any_expense` | `view_expense` | `create_expense` | `update_expense` | `delete_expense` |
| Incomes | `view_any_income` | `view_income` | `create_income` | `update_income` | `delete_income` |
| Exchange Rates | `view_any_exchange_rate_log` | — | `create_exchange_rate_log` | — | — |
| Staff | `view_any_staff` | `view_staff` | `create_staff` | `update_staff` | `delete_staff` |
| Staff Roles | `view_any_staff_role` | `view_staff_role` | `create_staff_role` | — | — |
| Payroll | `view_any_payroll_period` | `view_payroll_period` | `create_payroll_period` | `update_payroll_period` | `delete_payroll_period` |
| Vehicles | `view_any_vehicle` | `view_vehicle` | `create_vehicle` | `update_vehicle` | `delete_vehicle` |
| Trips | `view_any_trip` | `view_trip` | `create_trip` | `update_trip` | `delete_trip` |
| Pickup Schedules | `view_any_pickup_schedule` | `view_pickup_schedule` | `create_pickup_schedule` | `update_pickup_schedule` | — |
| Feedback | `view_any_customer_feedback` | `view_customer_feedback` | — | `update_customer_feedback` | — |
| Contact Messages | `view_any_contact_message` | `view_contact_message` | — | `update_contact_message` | — |
| Cashbook | `view_any_cashbook_entry` | `view_cashbook_entry` | `create_cashbook_entry` | `update_cashbook_entry` | `delete_cashbook_entry` |
| Containers | `view_any_shipment` | `view_shipment` | — | `update_shipment` | — |
| Branches | — (all auth) | — | — | `update_branch` | — |
| Users | `view_any_user` | `view_user` | `create_user` | `update_user` | `delete_user` |
| Reports | `view_any_report` | `view_report` | — | — | — |

### Client-side guard

```typescript
// src/hooks/usePermission.ts
export function usePermission() {
  const permissions = useAuthStore(s => s.permissions);
  return {
    can: (p: string) => permissions.includes(p),
    canAny: (...ps: string[]) => ps.some(p => permissions.includes(p)),
    isDriver: () => useAuthStore.getState().isDriver,
  };
}
```

---

## 5. Navigation Structure

```
RootStack (Stack.Navigator)
├── Auth Group  (no token)
│   ├── LoginScreen
│   └── BiometricScreen
│
└── App Group  (has token)
    ├── BranchSelectorScreen   (shown when user has > 1 branch and none selected)
    │
    ├── DriverNavigator        (shown when isDriver = true AND no admin permissions)
    │   ├── Tab: Schedule      (DriverScheduleScreen)
    │   ├── Tab: My Trips      (DriverTripsScreen + DriverTripDetailScreen)
    │   └── Tab: Profile       (ProfileScreen)
    │
    └── MainNavigator          (admin / branch_personnel)
        ├── DrawerNavigator
        │   │
        │   ├── BottomTabs
        │   │   ├── Tab: Dashboard
        │   │   ├── Tab: Shipments
        │   │   ├── Tab: Clients
        │   │   ├── Tab: Finance
        │   │   └── Tab: More  (opens drawer)
        │   │
        │   ├── Products        (ProductListScreen, ProductDetailScreen, ProductCreateScreen)
        │   ├── Quotations      (list, detail, create)
        │   ├── Staff & HR      (list, detail, create; payroll periods/entries)
        │   ├── Fleet           (vehicles list/detail/create, trips list/detail/create)
        │   ├── Cashbook        (month screen, entry form, ledgers, loans, WHT, director)
        │   ├── Operations      (pickups list/create, containers list/detail)
        │   ├── Customer Care   (feedback, contact messages)
        │   ├── Exchange Rates  (list + log form)
        │   ├── Reports         (list + viewer)
        │   ├── Branches        (list + detail — admin)
        │   ├── Users           (list + create — admin)
        │   └── Settings        (profile, change password, biometric toggle, theme)
```

---

## 6. Screen-by-Screen Spec

### 6.1 Login Screen

**Route:** `/login`

**Layout:**
- Full screen background gradient (primary `#1A3C5E` → dark `#0D1B2A`)
- Company logo centered (top 30% of screen)
- White card container (bottom 55%):
  - "Rose Door to Door" subtitle
  - Email input
  - Password input (show/hide toggle)
  - "Sign In" primary button (accent color)
  - Divider "or"
  - Biometric icon button (shown only if `LocalAuthentication.isEnrolledAsync()`)
- Validation: email format, password ≥ 6 chars

---

### 6.2 Dashboard Screen

**Route:** `/(app)/` | **Permission:** All authenticated

**Layout:**
- Header: Branch name (left), user avatar (right, tap → Profile)
- 2×2 summary card grid:
  - Shipments this month (icon: package)
  - Pending payments (icon: clock)
  - Revenue GHS this month (icon: trending-up)
  - Active trips (icon: truck)
- Recent Shipments section (last 5):
  - Each row: shipping_reference, client name, destination, status badge
  - Tap → ShipmentDetailScreen
- Quick Actions row:
  - + New Shipment (requires `create_shipment`)
  - Record Payment (requires `create_payment`)
  - Track Shipment (public)

**API:** `GET /api/v1/dashboard/summary`, `GET /api/v1/dashboard/recent-shipments`

---

### 6.3 Shipment List Screen

**Route:** `/(app)/shipments/` | **Permission:** `view_any_shipment`

**Layout:**
- Search bar (shipping_reference, tracking_number, client name)
- Horizontal filter chips: All / Pending / In Transit / Delivered / Cancelled / Cleared
- Date range filter (from, to)
- FlashList of ShipmentCards:
  - shipping_reference (bold, primary color)
  - Client name + phone
  - Status badge + payment_status badge
  - destination_branch name
  - total in USD + total_ghs in GHS
  - shipped_at date
- FAB "+" (requires `create_shipment`)

**API:** `GET /api/v1/shipments?status=&date_from=&date_to=&search=`

---

### 6.4 Shipment Detail Screen

**Route:** `/(app)/shipments/[id]` | **Permission:** `view_shipment`

**Sections (ScrollView with sticky section headers):**
1. **Header card** — shipping_reference (large), tracking_number, status badge, payment_status badge
2. **Client** — name, phone, email
3. **Route** — origin_branch → destination_branch, container_number, estimated_delivery_date, delivered_at
4. **Financials** — shipping_cost (USD), exchange_rate_at_shipment, vat_percentage, vat, insurance_accepted, insurance, total (USD), paid, payment_status
5. **Receivers** — accordion per receiver: receiver_name, receiver_phone, receiver_id_type, receiver_id_number, country, state_region, city, address
6. **Items** — per receiver: box_no, product name, quantity, item_cost
7. **Tracking Timeline** — vertical stepper (status, description, location, status_updated_at)
8. **Media** — horizontal photo strip (tap to expand); video icon for videos
9. **Actions bar** (bottom):
   - Edit (requires `update_shipment`)
   - + Payment (requires `create_payment`)
   - Invoice
   - + Media (camera icon)

**API:** `GET /api/v1/shipments/{id}`, `GET /api/v1/shipments/{id}/trackings`, `GET /api/v1/shipments/{id}/media`, `GET /api/v1/shipments/{id}/items`

---

### 6.5 Shipment Create Screen

**Route:** `/(app)/shipments/create` | **Permission:** `create_shipment`

**Multi-step form (4 steps, progress bar at top).**

> Reference implementation: `app/Filament/Resources/ShipmentResource/Pages/CreateShipment.php`

---

**Step 1 — Sender Info (Client & Route)**

| Field | Type | Notes |
|-------|------|-------|
| Shipment Type | Segmented control: `New Shipment` / `Add to Existing Container` | Controls reference generation |
| Existing Container | Searchable picker (`GET /api/v1/shipments?has_container=true`) | Only shown when type = "existing" |
| Client (Sender) | Searchable picker (`GET /api/v1/lookup/clients`) | Required; auto-detects new vs returning |
| Client Type | Read-only badge (New / Returning) | Auto-set after client is chosen |
| Origin (From) | Picker: Michigan / Illinois / Indiana / New York / New Jersey / Kentucky / Ohio / Ghana / Others | `origin_branch_id` |
| Destination (To) | Same options, default **Ghana** | `destination_branch_id` |

Hidden/auto-generated on submit:
- `tracking_number` — generated server-side (`KBZ` + 6-digit pad)
- `shipping_reference` — generated server-side format `CON{N}-{YY}-C{CY}-{CS}` (e.g. `CON50-26-C1-001`)
- `status` — always `pickup` on create
- `payment_status` — always `pending` on create

---

**Step 2 — Receivers & Items**

Each shipment has one or more **receivers**. Each receiver has one or more **items**.

**Receiver card** (repeatable, "+ Add Receiver" button):

| Field | Type | Notes |
|-------|------|-------|
| "Sender is Receiver" | Toggle | Auto-fills receiver fields from selected client |
| Previous Receiver | Searchable picker (from `GET /api/v1/lookup/previous-receivers?client_id=`) | Auto-fills fields below on select |
| receiver_name | Text | Required |
| receiver_phone | Phone input | |
| country | Picker (countries list) | Triggers state list |
| state_region | Picker (filtered by country) | Triggers city list |
| city | Picker (filtered by state) | |

**Items for this receiver** (nested repeatable):

| Field | Type | Notes |
|-------|------|-------|
| Product | Searchable picker (`GET /api/v1/lookup/products`) | Required |
| Quantity | Number | Required, min 1 |
| Value (USD) | Decimal | `item_cost`, required |
| Box # | Text | `box_no`, optional |

**Running totals** (auto-computed from all items across all receivers, shown at bottom):
- Item count, total quantity, subtotal ($)

---

**Step 3 — Payment (Optional)**

Record a payment now, or skip and add later from Shipment Detail.

**Amount summary card** (read-only): Subtotal | Total Due | Payments Added so far

**Payment entry** (repeatable, "+ Add Payment"):

| Field | Type | Notes |
|-------|------|-------|
| Payment Date | DateTime picker | Default now |
| Method | Picker: `CASH` / `Zelle` / `Cash App` / `BANK TRANSFER` / `CREDIT/DEBIT CARD` / `CHEQUE` / `PAYPAL` / `WAIVED` | |
| Amount (USD) | Decimal | Required |
| Exchange Rate | Decimal (auto-filled from `GET /api/v1/lookup/exchange-rate`) | |
| Amount (GHS) | Read-only, auto-computed = amount_usd × rate | |
| Bank Name | Text | Shown only when method = `BANK TRANSFER` |
| Account # | Text | Shown only when method = `BANK TRANSFER` |
| Cheque # | Text | Shown only when method = `CHEQUE` |
| Notes | Textarea | `payment_note` |

Balance due = Total - sum of payments entered.

---

**Step 4 — Complete & Review**

| Field | Type | Notes |
|-------|------|-------|
| Insurance Accepted | Toggle | `insurance_accepted` |
| Insurance Amount | Decimal (shown if toggle on) | `insurance` |
| Subtotal | Editable decimal | `shipping_cost`; auto-computed from items, editable |
| Discount | Decimal | Reduces total |
| VAT % | Decimal | `vat_percentage`; auto-computes `vat` |
| VAT Amount | Read-only | `vat` |
| Grand Total | Read-only | `total = subtotal − discount + insurance + vat` |
| Client Note | Textarea | `client_note` |
| Shipping Date | DateTime picker | `shipped_at`; required |
| Shipping Status | Picker (ShippingStatus enum) | Default `pickup` |
| Est. Delivery Date | Date picker | `estimated_delivery_date` |

**On submit (`POST /api/v1/shipments`):**
- Server auto-generates `tracking_number`, `shipping_reference`, `external_token`
- Invoice is auto-created
- Initial `ShipmentUpdate` record is created
- SMS sent to client phone and invoice emailed if available

---

**Zod schema (TypeScript) reference:**

```typescript
// Mirrors CreateShipment.php mutateFormDataBeforeCreate logic
const createShipmentSchema = z.object({
  shipment_type:            z.enum(['new', 'existing']),
  existing_shipment_id:     z.string().uuid().optional(),
  client_id:                z.string().uuid(),
  origin_branch_id:         z.string(),           // e.g. "Ghana", "Michigan"
  destination_branch_id:    z.string(),           // default "Ghana"
  shipped_at:               z.string().datetime(),
  estimated_delivery_date:  z.string().date().optional(),
  status:                   z.string().default('pickup'),
  shipping_cost:            z.number().min(0).default(0),
  discount:                 z.number().min(0).default(0),
  vat_percentage:           z.number().min(0).default(0),
  vat:                      z.number().min(0).default(0),
  insurance_accepted:       z.boolean().default(false),
  insurance:                z.number().min(0).default(0),
  total:                    z.number().min(0).default(0),
  client_note:              z.string().optional(),
  receivers: z.array(z.object({
    receiver_name:      z.string().min(1),
    receiver_phone:     z.string().optional(),
    country:            z.string().optional(),
    state_region:       z.string().optional(),
    city:               z.string().optional(),
    items: z.array(z.object({
      product_id:  z.string().uuid(),
      quantity:    z.number().int().min(1),
      item_cost:   z.number().min(0),
      box_no:      z.string().optional(),
    })),
  })),
  payments: z.array(z.object({
    paying_method:  z.string(),
    amount_usd:     z.number().min(0),
    exchange_rate:  z.number().min(0),
    amount_ghs:     z.number().min(0),
    amount:         z.number().min(0),        // mirrors amount_usd
    paid_on:        z.string().datetime(),
    payment_note:   z.string().optional(),
    bankname:       z.string().optional(),
    accountnumber:  z.string().optional(),
    cheque_no:      z.string().optional(),
    currency:       z.string().default('USD'),
    payment_type:   z.string().default('credit'),
  })).default([]),
});
```

---

### 6.6 Client List & Detail

**Routes:** `/(app)/clients/`, `/(app)/clients/[id]` | **Permission:** `view_any_client`

**List cards:** name, phone, email, id_type + id_number, city/country
**Create form fields:** name*, email, phone, id_type, id_number, country, state_region, city, address, notes

**Detail tabs:**
1. **Info** — all contact + ID fields
2. **Shipments** — client's shipments list (`GET /api/v1/clients/{id}/shipments`)
3. **Interactions** — interaction_type, notes, interaction_date, outcome (`GET /api/v1/clients/{id}/interactions`)
4. **Ratings** — star rating (1-5), feedback text (`GET /api/v1/clients/{id}/ratings`)

---

### 6.7 Expense List & Create

**Permission:** `view_any_expense` / `create_expense`

**List filters:** date range, expense_stage, category
**Create/Edit form fields (from expenses table):**

| Field | Type |
|-------|------|
| expense_category_id | Picker (`GET /api/v1/lookup/expense-categories`) |
| title | Text |
| description | Textarea |
| amount_usd | Decimal |
| exchange_rate | Decimal (auto-filled) |
| amount_ghs | Decimal (auto-computed = amount_usd × rate) |
| expense_date | Date picker |
| expense_stage | Picker (pre_shipment / during_shipment / post_shipment) |
| shipment_id | Optional picker |
| vendor_name | Text |
| receipt_path | Image upload |

---

### 6.8 Income List & Create

**Permission:** `view_any_income` / `create_income`

**Create/Edit form fields (from incomes table):**

| Field | Type |
|-------|------|
| income_category_id | Picker (`GET /api/v1/lookup/income-categories`) |
| title | Text |
| description | Textarea |
| amount_usd | Decimal |
| exchange_rate | Decimal (auto-filled) |
| amount_ghs | Decimal (auto-computed) |
| source_name | Text |
| source_contact | Text |
| income_date | Date picker |
| payment_method | Picker (cash / bank_transfer / mobile_money / cheque / card / other) |
| payment_reference | Text |
| shipment_id | Optional picker |
| status | Picker (pending / received / cancelled) |

---

### 6.9 Payment List & Create

**Permission:** `view_any_payment` / `create_payment`

**List filters:** date range (from/to), paying_method

**Create form fields (from payments table):**

| Field | Type | Notes |
|-------|------|-------|
| shipment_id | **Searchable picker** — search by `shipping_reference` (`GET /api/v1/shipments?search=`) | Optional; NOT a text input |
| payment_type | Picker: `debit` / `credit` | Default `credit` |
| paying_method | Picker: `CASH` / `Zelle` / `Cash App` / `BANK TRANSFER` / `CREDIT/DEBIT CARD` / `CHEQUE` / `PAYPAL` / `WAIVED` | Use exact strings (matching backend) |
| paid_on | DateTime picker | Default now |
| currency | Picker: `USD` / `GHS` | Default `USD` |
| amount_usd | Decimal | Required |
| exchange_rate | Decimal (auto-filled from `GET /api/v1/lookup/exchange-rate`) | |
| amount_ghs | Read-only, auto-computed = amount_usd × rate | |
| bankname | Text | Only when method = `BANK TRANSFER` |
| accountnumber | Text | Only when method = `BANK TRANSFER` |
| cheque_no | Text | Only when method = `CHEQUE` |
| payment_note | Textarea | Optional |

> **Important:** `shipment_id` must always be a **searchable picker** that displays `shipping_reference` as the label, not a plain text field. This gives staff proper UX when recording payments against a specific shipment.

---

### 6.10 Staff List & Detail

**Permission:** `view_any_staff` / `view_staff`

**List cards:** name, employee_id, position, employment_status badge, phone

**Detail:**
- Personal: name, email, phone
- Employment: position, staff_role name, employee_id, hire_date, salary, employment_status, notes
- **Payroll tab** — payroll_entries list: gross_pay, net_salary, status badge, paid_at

**Create/Edit form fields (from staff table):** name*, email, phone, position*, staff_role_id, salary, hire_date, employment_status, notes

---

### 6.11 Payroll Period List & Detail

**Permission:** `view_any_payroll_period`

**Period detail:**
- Header: name, start_date → end_date, pay_date, status badge
- Total gross / net / deductions summary cards
- Entries list (FlashList): staff name, base_salary, gross_pay, total_deductions, net_salary, status badge
- Approve button (requires `update_payroll_period`, only if status = 'draft')

**Entry detail (tap row):** base_salary, overtime, bonus, allowances, tax, ssnit, other_deductions, gross_pay, net_salary, payment_reference, notes

---

### 6.12 Vehicle List & Detail

**Permission:** `view_any_vehicle`

**Detail:**
- make, model, year, color, registration_number, vehicle_type
- max_weight_kg, max_volume_cbm, current_mileage
- Status badge
- Compliance card (traffic-light color): insurance_expiry, roadworthy_expiry, registration_expiry (red if expired/within 30 days, amber within 90, green otherwise)
- Service card: last_service_date, last_service_mileage, next_service_due
- **Maintenance tab** — records list + "Add Maintenance" FAB (requires `create_vehicle`)
- **Trips tab** — vehicle trips list

---

### 6.13 Trip List & Detail

**Permission:** `view_any_trip`

**Detail:**
- trip_reference, origin → destination, route_description, distance_km
- Dates grid: scheduled_date, scheduled_departure, scheduled_arrival, actual_departure, actual_arrival
- Status badge
- Staff: driver name, assistant name
- Vehicle: registration_number, make/model
- Costs breakdown: fuel_cost, toll_fees, driver_allowance, other_costs, **total_cost**
- Mileage: start_mileage, end_mileage
- **Shipments tab** — trip_shipments: shipment ref, client name, delivery_status badge, delivered_at, delivery_notes
  - "Update Delivery" button on each row (requires `update_trip`) → opens delivery update bottom sheet

---

### 6.14 Cashbook Month Screen

**Permission:** `view_any_cashbook_entry`

**Layout:**
- Month/Year selector (arrow navigation)
- Monthly summary row: opening_balance, total credits, total debits, closing balance
- Sub-tabs: **Entries** | **Income Ledger** | **Exp. Ledger** | **Loans** | **WHT** | **Director**
- Entries list: date, pv_no, details, chq_ref, bank_debit, momo_debit, bank_credit, momo_credit, bank_balance, momo_balance, cost_center badge
- FAB "+" for new entry (requires `create_cashbook_entry`)

---

### 6.15 Cashbook Entry Create/Edit

**Permission:** `create_cashbook_entry` / `update_cashbook_entry`

**Form fields (all from cashbook_entries table):**

| Field | Notes |
|-------|-------|
| date | Date picker |
| pv_no | Text |
| details | Text |
| chq_ref | Text |
| bank_debit, momo_debit | Decimal |
| bank_credit, momo_credit | Decimal |
| cost_center | Picker (21 options from CashbookCostCenter enum) |
| Analysis column | Shown based on cost_center: op_balance, sales, dir_transfer, shipping_fee, service_fee, momo_interest, property_management, refund, contra_receipt, import_duty, shipping_expenses, salaries_wages, bank_charges, ssnit, paye, withholding_tax, momo_charges, contra_payment, transportation, materials, donation |
| bank_balance, momo_balance | **Read-only** — auto-computed server-side |

---

### 6.16 Customer Feedback List & Detail

**Permission:** `view_any_customer_feedback`

**List cards:** customer_name, category, star rating, status badge, created_at

**Detail:** customer_name/email/phone, feedback_on, category, rating (star display), invoice_number, comment, complaint_type, complaint_reason, complaint_resolution, attachments gallery, response text + "Respond" button (requires `update_customer_feedback`), status update

---

### 6.17 Quotation List, Detail & Create

**Permission:** `view_any_quotation` / `create_quotation`

**Create form:** client_id picker, shipping_cost, items list (product_id, quantity, item_cost → sub_total auto-computed), total

**Detail actions:** Print/Share PDF (expo-print)

---

### 6.18 Pickup Schedule List & Create

**Permission:** `view_any_pickup_schedule` / `create_pickup_schedule`

**Create form (from pickup_schedules table):**

| Field | Type |
|-------|------|
| client_id | Searchable picker |
| shipment_id | Optional picker |
| assigned_to | Staff picker (`GET /api/v1/lookup/staff`) |
| scheduled_at | DateTime picker |
| pickup_location | Text |
| contact_phone | Phone |
| notes | Textarea |
| items_description | Textarea |

---

### 6.19 Container List & Detail

**Permission:** `view_any_shipment`

**List cards:** container_number, container_year, is_cleared badge, shipment count

**Detail:**
- container_number, container_year, is_cleared toggle (requires `update_shipment`), review text
- Shipments tab: all shipments in this container (shipping_reference, client name, status, destination)

---

### 6.20 Profile & Settings Screen

**Layout:**
- Avatar (upload via expo-image-picker → `PUT /api/v1/auth/profile`)
- Name, email, phone (editable inline)
- Branch selector (shown if user has multiple branches — updates `X-Branch-Id` header)
- Biometric toggle (updates AsyncStorage `@kasaba/biometric_enabled`)
- Theme toggle (light / dark)
- Change Password (sheet with current_password, new_password, confirm)
- Sign Out button (calls `POST /api/v1/auth/logout`, clears SecureStore)

---

## 7. API Endpoints Reference

All authenticated requests require:
- `Authorization: Bearer {token}` header
- `X-Branch-Id: {uuid}` header (branch-scoped resources)
- `Accept: application/json` header

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Login (public) |
| GET | `/api/v1/auth/me` | Current user |
| POST | `/api/v1/auth/logout` | Logout |
| PUT | `/api/v1/auth/profile` | Update name/phone/avatar |
| PUT | `/api/v1/auth/password` | Change password |

### Dashboard
| Method | Endpoint |
|--------|----------|
| GET | `/api/v1/dashboard/summary` |
| GET | `/api/v1/dashboard/recent-shipments` |

### Shipments
| Method | Endpoint | Notes |
|--------|----------|-------|
| GET | `/api/v1/shipments` | `?status=&date_from=&date_to=&search=` |
| POST | `/api/v1/shipments` | Create with receivers + items |
| GET | `/api/v1/shipments/{id}` | |
| PUT | `/api/v1/shipments/{id}` | |
| DELETE | `/api/v1/shipments/{id}` | |
| GET | `/api/v1/shipments/{id}/trackings` | Timeline |
| POST | `/api/v1/shipments/{id}/trackings` | Add tracking event |
| GET | `/api/v1/shipments/{id}/media` | Photos/videos |
| POST | `/api/v1/shipments/{id}/media` | Upload (multipart) |
| GET | `/api/v1/shipments/{id}/items` | Items flat list |
| GET | `/api/v1/shipments/track/{tracking_number}` | Public tracking |

### Clients, Products, Quotations, Invoices, Payments
| GET/POST | `/api/v1/clients` |
| GET/PUT/DELETE | `/api/v1/clients/{id}` |
| GET | `/api/v1/clients/{id}/shipments` |
| GET | `/api/v1/clients/{id}/interactions` |
| GET | `/api/v1/clients/{id}/ratings` |
| GET/POST/PUT/DELETE | `/api/v1/products/{id}` |
| GET/POST/PUT/DELETE | `/api/v1/quotations/{id}` |
| GET/PUT | `/api/v1/invoices/{id}` |
| GET/POST | `/api/v1/payments` |

### Finance
| GET/POST/PUT/DELETE | `/api/v1/expenses` |
| GET | `/api/v1/expense-categories` |
| GET/POST/PUT/DELETE | `/api/v1/incomes` |
| GET | `/api/v1/income-categories` |
| GET/POST | `/api/v1/exchange-rates` |
| GET | `/api/v1/exchange-rates/current` |

### Staff & Payroll
| GET/POST/PUT/DELETE | `/api/v1/staff` |
| GET | `/api/v1/staff/{id}/payroll-entries` |
| GET/POST | `/api/v1/staff-roles` |
| GET/POST/PUT/DELETE | `/api/v1/payroll-periods` |
| GET/PUT | `/api/v1/payroll-entries/{id}` |

### Fleet
| GET/POST/PUT/DELETE | `/api/v1/vehicles` |
| GET/POST | `/api/v1/vehicles/{id}/maintenances` |
| GET | `/api/v1/vehicles/{id}/trips` |
| GET/POST/PUT/DELETE | `/api/v1/trips` |
| GET | `/api/v1/trips/{id}/shipments` |
| PUT | `/api/v1/trips/{id}/shipments/{shipmentId}` |
| GET/POST/PUT | `/api/v1/pickup-schedules` |

### Cashbook
| GET/POST/PUT/DELETE | `/api/v1/cashbook/entries` | `?month=&year=` |
| GET | `/api/v1/cashbook/income-ledger` | `?month=&year=` |
| GET | `/api/v1/cashbook/expenditure-ledger` | `?month=&year=` |
| GET/POST/PUT | `/api/v1/cashbook/loans` |
| GET/POST/PUT | `/api/v1/cashbook/withholding-tax` | `?month=&year=` |
| GET/POST | `/api/v1/cashbook/director-account` |

### Operations & CRM
| GET/PUT | `/api/v1/containers/{id}` |
| GET | `/api/v1/containers/{id}/shipments` |
| GET/PUT | `/api/v1/feedback` |
| GET/PUT | `/api/v1/contact-messages` |

### Admin
| GET/POST/PUT/DELETE | `/api/v1/users` |
| GET/PUT | `/api/v1/branches/{id}` |
| GET | `/api/v1/reports` |

### Driver (guarded by staff.role.code = 'DRIVER')
| GET | `/api/v1/driver/profile` |
| GET | `/api/v1/driver/schedule` |
| GET | `/api/v1/driver/trips` |
| GET | `/api/v1/driver/trips/{id}` |
| PUT | `/api/v1/driver/trips/{id}/status` |
| PUT | `/api/v1/driver/trips/{id}/shipments/{shipmentId}` |

### Lookups (pickers)
| GET | `/api/v1/lookup/branches` |
| GET | `/api/v1/lookup/clients` |
| GET | `/api/v1/lookup/products` |
| GET | `/api/v1/lookup/staff` | `?drivers_only=true` |
| GET | `/api/v1/lookup/vehicles` | `?available_only=true` |
| GET | `/api/v1/lookup/expense-categories` |
| GET | `/api/v1/lookup/income-categories` |
| GET | `/api/v1/lookup/staff-roles` |
| GET | `/api/v1/lookup/exchange-rate` |
| GET | `/api/v1/lookup/previous-receivers` | `?client_id=` |

---

## 8. State Management

### Zustand Stores

```typescript
// src/stores/authStore.ts
interface AuthState {
  token: string | null;
  user: User | null;
  currentBranchId: string | null;
  permissions: string[];
  isDriver: boolean;
  isAuthenticated: boolean;
  can: (permission: string) => boolean;
  hasRole: (role: string) => boolean;
  setAuth: (token: string, user: User, branchId: string) => void;
  setCurrentBranch: (branchId: string) => void;
  logout: () => void;
}

// src/stores/settingsStore.ts
interface SettingsState {
  theme: 'light' | 'dark';
  biometricEnabled: boolean;
  setTheme: (theme: 'light' | 'dark') => void;
  setBiometric: (enabled: boolean) => void;
}
```

### React Query Setup

```typescript
// src/api/queryClient.ts
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,           // 30s for operational data
      retry: 2,
      refetchOnWindowFocus: true,
    },
  },
});

// Query key factories
export const shipmentKeys = {
  all:    ['shipments'] as const,
  list:   (filters: object) => ['shipments', 'list', filters] as const,
  detail: (id: string)     => ['shipments', 'detail', id] as const,
};
```

### Axios Setup

```typescript
// src/api/client.ts
export const api = axios.create({
  baseURL: process.env.EXPO_PUBLIC_API_URL + '/api/v1',
  timeout: 30_000,
  headers: { 'Accept': 'application/json' },
});

api.interceptors.request.use(config => {
  const { token, currentBranchId } = useAuthStore.getState();
  if (token)          config.headers.Authorization = `Bearer ${token}`;
  if (currentBranchId) config.headers['X-Branch-Id'] = currentBranchId;
  return config;
});
```

---

## 9. Offline Support

- Cache last-fetched list data in `AsyncStorage` with a timestamp key per query key
- Show stale indicator when offline (dimmed card + "Updated X ago" text)
- Queue write operations in `AsyncStorage` when offline; replay on reconnect via `NetInfo` event
- Offline queue entry shape: `{ id, method, url, body, timestamp }`
- Replay on `netInfo.isConnected === true` event, in FIFO order
- On conflict (409) during replay, show user notification

```typescript
// src/hooks/useOfflineQueue.ts
export function useOfflineQueue() {
  const { isConnected } = useNetInfo();
  useEffect(() => {
    if (isConnected) flushQueue(); // process queued mutations
  }, [isConnected]);
}
```

---

## 10. File & Folder Structure

```
kasabazaar-mobile/
├── app.json
├── package.json
├── tsconfig.json
├── .env                        (EXPO_PUBLIC_API_URL=http://your-server/api/v1)
│
├── app/                        (Expo Router file-based routing)
│   ├── _layout.tsx             (Root stack — auth check)
│   ├── (auth)/
│   │   ├── _layout.tsx
│   │   ├── login.tsx
│   │   └── biometric.tsx
│   └── (app)/
│       ├── _layout.tsx         (Drawer + bottom tabs layout)
│       ├── index.tsx           (Dashboard)
│       ├── branch-selector.tsx
│       ├── shipments/
│       │   ├── index.tsx
│       │   ├── create.tsx
│       │   └── [id]/
│       │       ├── index.tsx
│       │       ├── edit.tsx
│       │       ├── tracking.tsx
│       │       └── media.tsx
│       ├── clients/
│       │   ├── index.tsx
│       │   ├── create.tsx
│       │   └── [id].tsx
│       ├── products/
│       │   ├── index.tsx
│       │   └── [id].tsx
│       ├── finance/
│       │   ├── expenses/
│       │   │   ├── index.tsx
│       │   │   └── create.tsx
│       │   ├── incomes/
│       │   │   ├── index.tsx
│       │   │   └── create.tsx
│       │   ├── payments/
│       │   │   ├── index.tsx
│       │   │   └── create.tsx
│       │   └── invoices/
│       │       ├── index.tsx
│       │       └── [id].tsx
│       ├── quotations/
│       │   ├── index.tsx
│       │   ├── create.tsx
│       │   └── [id].tsx
│       ├── staff/
│       │   ├── index.tsx
│       │   ├── create.tsx
│       │   └── [id].tsx
│       ├── payroll/
│       │   ├── index.tsx
│       │   ├── create.tsx
│       │   └── [id]/
│       │       ├── index.tsx
│       │       └── entries/[entryId].tsx
│       ├── fleet/
│       │   ├── vehicles/
│       │   │   ├── index.tsx
│       │   │   ├── create.tsx
│       │   │   └── [id].tsx
│       │   └── trips/
│       │       ├── index.tsx
│       │       ├── create.tsx
│       │       └── [id].tsx
│       ├── cashbook/
│       │   ├── index.tsx       (month screen with sub-tabs)
│       │   ├── create.tsx
│       │   └── [id].tsx
│       ├── pickups/
│       │   ├── index.tsx
│       │   └── create.tsx
│       ├── containers/
│       │   ├── index.tsx
│       │   └── [id].tsx
│       ├── feedback/
│       │   ├── index.tsx
│       │   └── [id].tsx
│       ├── contacts/
│       │   ├── index.tsx
│       │   └── [id].tsx
│       ├── exchange-rates/
│       │   └── index.tsx
│       ├── reports/
│       │   └── index.tsx
│       ├── users/
│       │   ├── index.tsx
│       │   └── create.tsx
│       ├── branches/
│       │   └── index.tsx
│       ├── profile/
│       │   ├── index.tsx
│       │   └── change-password.tsx
│       └── driver/
│           ├── _layout.tsx
│           ├── schedule.tsx
│           ├── trips/
│           │   ├── index.tsx
│           │   └── [id].tsx
│           └── profile.tsx
│
└── src/
    ├── api/
    │   ├── client.ts           (axios instance + interceptors)
    │   ├── auth.ts
    │   ├── dashboard.ts
    │   ├── shipments.ts
    │   ├── clients.ts
    │   ├── products.ts
    │   ├── quotations.ts
    │   ├── invoices.ts
    │   ├── payments.ts
    │   ├── expenses.ts
    │   ├── incomes.ts
    │   ├── exchangeRates.ts
    │   ├── staff.ts
    │   ├── payroll.ts
    │   ├── vehicles.ts
    │   ├── trips.ts
    │   ├── pickupSchedules.ts
    │   ├── containers.ts
    │   ├── feedback.ts
    │   ├── contactMessages.ts
    │   ├── cashbook.ts
    │   ├── driver.ts
    │   ├── branches.ts
    │   ├── users.ts
    │   ├── reports.ts
    │   └── lookup.ts
    ├── components/
    │   ├── ui/
    │   │   ├── Button.tsx
    │   │   ├── Card.tsx
    │   │   ├── Badge.tsx
    │   │   ├── StatusBadge.tsx
    │   │   ├── Input.tsx
    │   │   ├── Select.tsx
    │   │   ├── SearchInput.tsx
    │   │   ├── DatePicker.tsx
    │   │   ├── Avatar.tsx
    │   │   ├── SectionHeader.tsx
    │   │   ├── EmptyState.tsx
    │   │   ├── LoadingSkeleton.tsx
    │   │   ├── PermissionGate.tsx
    │   │   ├── CurrencyDisplay.tsx
    │   │   ├── StarRating.tsx
    │   │   ├── BottomSheet.tsx
    │   │   ├── MultiStepForm.tsx
    │   │   └── SignaturePad.tsx
    │   ├── layout/
    │   │   ├── ScreenContainer.tsx
    │   │   ├── AppHeader.tsx
    │   │   └── FAB.tsx
    │   ├── shipments/
    │   │   ├── ShipmentCard.tsx
    │   │   ├── ShipmentStatusFilter.tsx
    │   │   └── TrackingTimeline.tsx
    │   ├── cashbook/
    │   │   ├── CashbookEntryCard.tsx
    │   │   └── LedgerTable.tsx
    │   ├── dashboard/
    │   │   ├── SummaryCard.tsx
    │   │   └── QuickActions.tsx
    │   └── driver/
    │       ├── TripCard.tsx
    │       └── DeliveryUpdateSheet.tsx
    ├── hooks/
    │   ├── useAuth.ts
    │   ├── usePermission.ts
    │   ├── useBranch.ts
    │   ├── useShipments.ts
    │   ├── useClients.ts
    │   ├── useVehicles.ts
    │   ├── useTrips.ts
    │   ├── useDriver.ts
    │   ├── useCashbook.ts
    │   └── useOfflineQueue.ts
    ├── stores/
    │   ├── authStore.ts
    │   └── settingsStore.ts
    ├── theme/
    │   ├── colors.ts
    │   ├── typography.ts
    │   ├── spacing.ts
    │   └── index.ts
    └── types/
        ├── auth.ts
        ├── shipment.ts
        ├── client.ts
        ├── trip.ts
        ├── cashbook.ts
        └── index.ts
```

---

## 11. Key Implementation Notes

### 1. Branch Header

Every API call must include `X-Branch-Id`. Set it in the Axios interceptor from `authStore.currentBranchId`. When the user switches branches in Profile screen, call `queryClient.invalidateQueries()` to force a full refetch.

### 2. PermissionGate Component

```tsx
// src/components/ui/PermissionGate.tsx
interface Props {
  permission: string;
  children: React.ReactNode;
  fallback?: React.ReactNode;
}
export function PermissionGate({ permission, children, fallback = null }: Props) {
  const { can } = usePermission();
  return can(permission) ? <>{children}</> : <>{fallback}</>;
}

// Usage
<PermissionGate permission="create_shipment">
  <FAB icon="plus" onPress={openCreate} />
</PermissionGate>
```

### 3. Driver Detection

On login, the `me` response includes a `is_driver` flag (or the app checks if `user.role === 'branch_personnel'` and calls `/api/v1/driver/profile` — if it returns 200, treat as driver). Use this to show the `DriverNavigator` instead of `MainNavigator`.

### 4. Currency Display

```tsx
// src/components/ui/CurrencyDisplay.tsx
export function CurrencyDisplay({ usd, ghs }: { usd?: number; ghs?: number }) {
  return (
    <View style={styles.row}>
      {usd !== undefined && <Text style={styles.usd}>${usd.toFixed(2)}</Text>}
      {ghs !== undefined && <Text style={styles.ghs}>₵{ghs.toFixed(2)}</Text>}
    </View>
  );
}
```

### 5. Date Display

- Date only: `25 Mar 2026` — use `dayjs(date).format('DD MMM YYYY')`
- Datetime: `25 Mar 2026, 14:30` — use `dayjs(date).format('DD MMM YYYY, HH:mm')`
- All dates from API are UTC strings. Dayjs handles local conversion.

### 6. Vehicle Compliance Warning

Color-code expiry dates:
- Red: expired or within 30 days
- Amber: 31-90 days
- Green: > 90 days

### 7. Shipment Reference Format

Shipping references follow the pattern `CON{N}-{YY}-C{CY}-{CS}`:
- `N` = global container number (increments for each new container)
- `YY` = 2-digit year (from `shipped_at` date)
- `CY` = container sequence within the year
- `CS` = client sequence within the container (zero-padded to 3 digits)

Example: `CON50-26-C1-001` = Container 50, year 2026, 1st container of 2026, 1st client.

**Never generate this on the mobile app.** Always let the server generate it and read it back from the POST response.

### 8. Cashbook Auto-Balance

`bank_balance` and `momo_balance` are **computed server-side** in `CashbookEntry::booted()`. Never send them in POST/PUT requests. Display them as read-only fields after save.

### 9. Pagination

All list endpoints use Laravel pagination. React Query `useInfiniteQuery` with `?page=` param. FlashList + `onEndReached` for infinite scroll. Show item count in header: `"142 Shipments"`.

### 10. Image Upload (multipart)

```typescript
// src/api/shipments.ts
export async function uploadShipmentMedia(shipmentId: string, uri: string, type: 'photo' | 'video') {
  const formData = new FormData();
  formData.append('file', { uri, name: 'media.jpg', type: 'image/jpeg' } as any);
  formData.append('media_type', type);
  return api.post(`/shipments/${shipmentId}/media`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
}
```

### 11. Delivery Signature (Driver)

Use `react-native-signature-canvas` for the signature pad. Export as base64 PNG string → send as `receiver_signature` in the delivery update request.

---

## 12. Implementation Phases

> **API backend is already implemented** (143 routes, 28 controllers).
> All phases below refer to **React Native mobile app** work only.
> Each phase includes: deliverables, exact files to create, key code patterns, and a done checklist.

---

### Phase 0 — Project Bootstrap (Days 1–2)

**Goal:** Working skeleton with correct tooling, env, and folder structure.

**Steps:**

1. **Init project**
   ```bash
   npx create-expo-app kasabazaar-mobile --template expo-template-blank-typescript
   cd kasabazaar-mobile
   ```

2. **Install all dependencies**
   ```bash
   npx expo install expo-secure-store expo-local-authentication expo-image-picker \
     expo-camera expo-print expo-sharing expo-file-system expo-notifications
   npx expo install @react-navigation/native @react-navigation/stack \
     @react-navigation/bottom-tabs @react-navigation/drawer
   npx expo install react-native-screens react-native-safe-area-context \
     react-native-gesture-handler react-native-reanimated
   npm install axios zustand @tanstack/react-query
   npm install react-hook-form zod @hookform/resolvers
   npm install @shopify/flash-list
   npm install dayjs
   npm install @react-native-community/netinfo
   npm install react-native-signature-canvas
   ```

3. **Create env file**
   ```
   # .env
   EXPO_PUBLIC_API_URL=http://192.168.x.x:8000
   ```

4. **Create all empty folders** per the structure in Section 10.

5. **Configure tsconfig.json** with path aliases:
   ```json
   { "compilerOptions": { "paths": { "@/*": ["./src/*"], "@app/*": ["./app/*"] } } }
   ```

**Files to create:**
- `app.json` (configure app name, icon, splash, permissions)
- `.env`
- `src/theme/colors.ts`
- `src/theme/typography.ts`
- `src/theme/spacing.ts`
- `src/theme/index.ts`

**Done checklist:**
- [ ] `npx expo start` runs without errors
- [ ] TypeScript compiles with `tsc --noEmit`
- [ ] All folders created
- [ ] `.env` file with API URL set

---

### Phase 1 — Design System & Foundation (Days 3–7)

**Goal:** Reusable UI components, Axios client, Zustand stores, navigation shell.

**Steps:**

1. **Build theme** (`src/theme/`)
   - Export `Colors`, `Typography`, `Spacing`, `useTheme()` hook that reads dark/light from settingsStore

2. **Build base UI components** (`src/components/ui/`)

   ```tsx
   // Button.tsx — primary, secondary, ghost, danger variants
   // Card.tsx — container with shadow and radius
   // StatusBadge.tsx — maps any status string to color
   // Input.tsx — controlled, error state, label
   // Select.tsx — modal picker (works iOS + Android)
   // EmptyState.tsx — icon + title + subtitle
   // LoadingSkeleton.tsx — animated placeholder
   // PermissionGate.tsx — as shown in section 11
   // CurrencyDisplay.tsx — USD + GHS side by side
   ```

3. **Create Axios client** (`src/api/client.ts`)
   - Base URL from `process.env.EXPO_PUBLIC_API_URL`
   - Request interceptor: inject `Authorization` + `X-Branch-Id`
   - Response interceptor: 401 → logout + navigate to login

4. **Create Zustand stores** (`src/stores/`)
   - `authStore.ts` — token, user, permissions, currentBranchId, isDriver flags, `can()`, `logout()`
   - `settingsStore.ts` — theme, biometricEnabled, persist to AsyncStorage

5. **Auth API module** (`src/api/auth.ts`)
   ```typescript
   export const authApi = {
     login: (email: string, password: string) =>
       api.post('/auth/login', { email, password }),
     me: () => api.get('/auth/me'),
     logout: () => api.post('/auth/logout'),
     updateProfile: (data: FormData) =>
       api.put('/auth/profile', data, { headers: { 'Content-Type': 'multipart/form-data' } }),
     changePassword: (data: { current_password: string; password: string }) =>
       api.put('/auth/password', data),
   };
   ```

6. **Auth screens**
   - `app/(auth)/login.tsx` — email/password form + biometric button
   - `app/(auth)/biometric.tsx` — calls `authenticateWithBiometrics()` then restores session

7. **Navigation shell** (`app/_layout.tsx`)
   ```tsx
   // Root: check SecureStore for token → route to (auth) or (app)
   // (app)/_layout.tsx: check isDriver → DriverNavigator or MainNavigator
   // MainNavigator: Drawer wrapping bottom tabs
   ```

8. **Branch selector screen** (`app/(app)/branch-selector.tsx`)
   - Shown if user has > 1 branch and no branch selected in store
   - List of user's branches → tap to set `currentBranchId` → redirect to Dashboard

**Files to create in this phase:**
- `src/theme/*` (4 files)
- `src/components/ui/*` (14 components)
- `src/components/layout/ScreenContainer.tsx`, `AppHeader.tsx`, `FAB.tsx`
- `src/api/client.ts`
- `src/api/auth.ts`
- `src/stores/authStore.ts`
- `src/stores/settingsStore.ts`
- `src/hooks/useAuth.ts`
- `src/hooks/usePermission.ts`
- `src/types/auth.ts`
- `app/_layout.tsx`
- `app/(auth)/_layout.tsx`
- `app/(auth)/login.tsx`
- `app/(auth)/biometric.tsx`
- `app/(app)/_layout.tsx`
- `app/(app)/branch-selector.tsx`

**Done checklist:**
- [ ] Login screen renders with email/password fields
- [ ] Login API call works against real Laravel server
- [ ] Token stored in SecureStore on success
- [ ] `can('view_any_shipment')` returns correct boolean from permissions array
- [ ] Biometric prompt shown after login if enrolled
- [ ] Branch selector shown when user has multiple branches
- [ ] Logout clears SecureStore and redirects to login
- [ ] Dark mode toggle changes theme across all components

---

### Phase 2 — Dashboard & Shipments (Days 8–18)

**Goal:** The most-used screens. Shipments are the core of the business.

**Steps:**

1. **Dashboard** (`app/(app)/index.tsx`)
   - React Query hook: `useQuery(['dashboard'], dashboardApi.summary)`
   - `SummaryCard` grid (2×2)
   - `QuickActions` row
   - Recent shipments FlashList

2. **Shipment API module** (`src/api/shipments.ts`)
   ```typescript
   export const shipmentsApi = {
     list:        (params: ShipmentFilters) => api.get('/shipments', { params }),
     detail:      (id: string)              => api.get(`/shipments/${id}`),
     create:      (data: CreateShipmentDto) => api.post('/shipments', data),
     update:      (id: string, data: any)   => api.put(`/shipments/${id}`, data),
     delete:      (id: string)              => api.delete(`/shipments/${id}`),
     trackings:   (id: string)              => api.get(`/shipments/${id}/trackings`),
     addTracking: (id: string, data: any)   => api.post(`/shipments/${id}/trackings`, data),
     media:       (id: string)              => api.get(`/shipments/${id}/media`),
     uploadMedia: (id: string, form: FormData) =>
       api.post(`/shipments/${id}/media`, form, { headers: { 'Content-Type': 'multipart/form-data' } }),
     items:       (id: string)              => api.get(`/shipments/${id}/items`),
     publicTrack: (tracking: string)        => api.get(`/shipments/track/${tracking}`),
   };
   ```

3. **Shipment list** (`app/(app)/shipments/index.tsx`)
   - `useInfiniteQuery` with `?page=&status=&search=` params
   - `SearchInput` + horizontal filter chips
   - `ShipmentCard` component — tap → detail
   - FAB (PermissionGate `create_shipment`)

4. **Shipment detail** (`app/(app)/shipments/[id]/index.tsx`)
   - Tabbed ScrollView with all sections from spec 6.4
   - `TrackingTimeline` component
   - Media horizontal scroll
   - Action bar at bottom

5. **Shipment create — 4-step form** (`app/(app)/shipments/create.tsx`)
   - `MultiStepForm` wrapper with 4-step progress bar
   - **Step 1 — Sender Info:** client picker, shipment type toggle (new vs existing container), origin/destination branch pickers (state names: Michigan, Illinois, Indiana, New York, New Jersey, Kentucky, Ohio, Ghana, Others)
   - **Step 2 — Receivers & Items:** dynamic receiver list, each with "Sender is Receiver" toggle + "Previous Receiver" quick-fill + nested items (product picker, qty, item_cost, box_no). Running subtotal auto-computed.
   - **Step 3 — Payment (Optional):** zero or more payment entries, each with method picker (CASH / Zelle / Cash App / BANK TRANSFER / CREDIT/DEBIT CARD / CHEQUE / PAYPAL / WAIVED), amount_usd, exchange_rate (auto-filled), amount_ghs (computed). Conditional fields for bank transfer and cheque.
   - **Step 4 — Complete:** insurance toggle + amount, discount, VAT%, grand total preview, client note, shipped_at date, status picker (default `pickup`), est. delivery date. Submit via `useMutation` → `POST /api/v1/shipments`.

6. **Payment screens** (`app/(app)/finance/payments/`)
   - List: `useInfiniteQuery`
   - Create form: Zod-validated, conditional fields based on paying_method

7. **Invoice screens** (`app/(app)/finance/invoices/`)
   - List + detail (read-mostly)
   - Print via `expo-print`

8. **Client screens** (`app/(app)/clients/`)
   - List, detail (tabbed), create form
   - Interactions and ratings sub-lists

**Files to create:**
- `src/api/dashboard.ts`
- `src/api/shipments.ts`
- `src/api/clients.ts`
- `src/api/payments.ts`
- `src/api/invoices.ts`
- `src/api/lookup.ts`
- `src/hooks/useShipments.ts`
- `src/hooks/useClients.ts`
- `src/components/dashboard/SummaryCard.tsx`
- `src/components/dashboard/QuickActions.tsx`
- `src/components/shipments/ShipmentCard.tsx`
- `src/components/shipments/ShipmentStatusFilter.tsx`
- `src/components/shipments/TrackingTimeline.tsx`
- `src/components/ui/MultiStepForm.tsx`
- `src/types/shipment.ts`
- `src/types/client.ts`
- All screen files for shipments, clients, payments, invoices

**Done checklist:**
- [ ] Dashboard loads real data from API
- [ ] Shipment list shows paginated results with search and filter
- [ ] Shipment detail shows all 9 sections
- [ ] Tracking timeline renders with correct status colors
- [ ] Media gallery shows uploaded photos
- [ ] 4-step shipment create submits correctly with receivers + items
- [ ] "Sender is Receiver" toggle auto-fills receiver from selected client
- [ ] "Previous Receiver" picker loads client's past receivers from API
- [ ] Shipment type "existing container" picker loads recent containers
- [ ] `origin_branch` and `destination_branch` show state name (not UUID)
- [ ] Step 3 payment optional — skipping it still creates shipment
- [ ] Step 4 grand total = subtotal − discount + insurance + VAT
- [ ] Server returns auto-generated `shipping_reference` (CON format) and `tracking_number`
- [ ] Payment create: `shipment_id` uses searchable picker (not text field) showing `shipping_reference`
- [ ] Payment create updates shipment's `paid` and `payment_status`
- [ ] Invoice detail shows correct shipment reference and client
- [ ] Client detail tabs work (shipments, interactions, ratings)

---

### Phase 3 — Finance (Days 19–25)

**Goal:** Expenses, incomes, exchange rates, quotations.

**Steps:**

1. **Expense screens** — list (FlashList), create/edit form with all fields from spec 6.7. Receipt image upload via `expo-image-picker`. Auto-compute `amount_ghs = amount_usd × exchange_rate`.

2. **Income screens** — list, create/edit form per spec 6.8.

3. **Exchange rate screen** — list of past rates + "Log Rate" form (from_currency, to_currency, rate, source).

4. **Quotation screens** — list, detail (with items accordion), create form (dynamic item rows), print/share via `expo-print` + `expo-sharing`.

5. **Auto-exchange-rate fill** — on form mount, call `GET /api/v1/lookup/exchange-rate` and pre-fill the `exchange_rate` field.

**Files to create:**
- `src/api/expenses.ts`
- `src/api/incomes.ts`
- `src/api/exchangeRates.ts`
- `src/api/quotations.ts`
- `src/hooks/useExchangeRate.ts`
- All screen files for expenses, incomes, exchange-rates, quotations

**Done checklist:**
- [ ] Expense create: amount_ghs auto-computes from USD × rate
- [ ] Receipt image uploads successfully
- [ ] Income create works with all payment methods
- [ ] Exchange rate screen shows history chart (Victory Native)
- [ ] Quotation create supports multiple item rows
- [ ] Quotation print generates PDF with company logo + items table

---

### Phase 4 — HR & Fleet (Days 26–36)

**Goal:** Staff management, payroll, vehicles, trips, pickup schedules.

**Steps:**

1. **Staff screens** — list (filterable by employment_status), detail with payroll history tab, create/edit form.

2. **Payroll screens** — period list, period detail (entries table + approve button), entry detail/edit.

3. **Vehicle screens** — list with status filter, detail (compliance color-coding for expiry dates), create/edit, maintenance tab + "Add Maintenance" form.

4. **Trip screens** — list (filterable by status + date), detail with shipments tab and delivery update bottom sheet, create form (vehicle picker `?available_only=true`, driver picker `?drivers_only=true`).

5. **Pickup schedule screens** — list, create form (staff picker, datetime picker for `scheduled_at`).

6. **DeliveryUpdateSheet component**
   ```tsx
   // src/components/driver/DeliveryUpdateSheet.tsx
   // Props: tripId, shipmentId, currentStatus
   // Contains: status picker, notes, delivered_at, SignaturePad
   // On submit: PUT /api/v1/trips/{tripId}/shipments/{shipmentId}
   ```

**Files to create:**
- `src/api/staff.ts`
- `src/api/payroll.ts`
- `src/api/vehicles.ts`
- `src/api/trips.ts`
- `src/api/pickupSchedules.ts`
- `src/hooks/useVehicles.ts`
- `src/hooks/useTrips.ts`
- `src/components/ui/SignaturePad.tsx`
- `src/components/driver/DeliveryUpdateSheet.tsx`
- `src/types/trip.ts`
- All screen files for staff, payroll, vehicles, fleet, pickups

**Done checklist:**
- [ ] Staff list shows employment_status badges
- [ ] Payroll period approve action works
- [ ] Vehicle compliance dates are color-coded correctly
- [ ] Trip create auto-fills only `available` vehicles
- [ ] Driver picker shows only staff with `drivers_only=true`
- [ ] Delivery update bottom sheet submits correctly
- [ ] Signature pad captures base64 and sends to API
- [ ] Trip status updates (planned → in_progress → completed)

---

### Phase 5 — Cashbook (Days 37–43)

**Goal:** Full cashbook module — entries, ledgers, loans, WHT, director account.

**Steps:**

1. **Cashbook month screen** (`app/(app)/cashbook/index.tsx`)
   - Month/year navigation (arrows, today default)
   - `GET /api/v1/cashbook/entries?month=&year=` → entries list
   - Sub-tabs using top tab navigator:
     - **Entries** — FlashList of `CashbookEntryCard`
     - **Income Ledger** — read-only table
     - **Exp. Ledger** — read-only table
     - **Loans** — list + add/edit
     - **WHT** — list + add/edit
     - **Director** — list + add

2. **Cashbook entry create/edit** (`app/(app)/cashbook/create.tsx`, `[id].tsx`)
   - All fields from spec 6.15
   - Cost center picker (21 options) — on change, show/hide the relevant analysis column
   - `bank_balance` and `momo_balance` shown as read-only after save

3. **CashbookEntryCard component**
   ```tsx
   // Compact card showing date, pv_no, details, bank/momo debit/credit, running balance
   // cost_center badge in accent color
   ```

**Files to create:**
- `src/api/cashbook.ts`
- `src/hooks/useCashbook.ts`
- `src/components/cashbook/CashbookEntryCard.tsx`
- `src/components/cashbook/LedgerTable.tsx`
- `src/types/cashbook.ts`
- All cashbook screen files

**Done checklist:**
- [ ] Month navigation fetches correct month data
- [ ] Entry list shows running bank_balance and momo_balance
- [ ] Cost center picker shows all 21 options
- [ ] Analysis column auto-fills based on cost_center
- [ ] bank_balance/momo_balance are read-only (server-computed)
- [ ] Loans CRUD works
- [ ] WHT auto-computes `wht_amount = gross × rate`
- [ ] Director account entries list and add work

---

### Phase 6 — CRM & Operations (Days 44–50)

**Goal:** Customer feedback, contact messages, containers, products.

**Steps:**

1. **Feedback screens** — list with star rating display, detail with response form (requires `update_customer_feedback`), status cycle: pending → reviewed → resolved.

2. **Contact messages screens** — list with status badge, detail with reply form.

3. **Container screens** — list (FlashList with container_number, year, is_cleared), detail (shipments tab + `is_cleared` toggle + review text edit).

4. **Product screens** — list (searchable, with sku + category), detail, create/edit.

**Files to create:**
- `src/api/feedback.ts`
- `src/api/contactMessages.ts`
- `src/api/containers.ts`
- `src/api/products.ts`
- All screen files

**Done checklist:**
- [ ] Feedback list shows star ratings
- [ ] Feedback response form submits correctly
- [ ] Container is_cleared toggle calls PUT endpoint
- [ ] Product create/edit works

---

### Phase 7 — Driver App (Days 51–57)

**Goal:** Complete driver experience — schedule, trip management, delivery updates with signature.

**Driver detection logic:**

```typescript
// In app/(app)/_layout.tsx, after loading user:
const checkDriver = async () => {
  try {
    await api.get('/driver/profile');
    useAuthStore.getState().setIsDriver(true);
  } catch {
    useAuthStore.getState().setIsDriver(false);
  }
};
```

**Steps:**

1. **Driver layout** (`app/(app)/driver/_layout.tsx`)
   - Bottom tabs: Schedule | My Trips | Profile
   - Shown when `isDriver = true` AND user does NOT have `view_any_shipment` permission (pure driver)
   - If driver also has admin permissions → show both navigators

2. **Driver schedule screen** (`app/(app)/driver/schedule.tsx`)
   - `GET /api/v1/driver/schedule`
   - Today's trips grouped first, then future dates
   - `TripCard` component: trip_reference, origin → destination, scheduled_departure, status badge, vehicle reg
   - Tap → Driver Trip Detail

3. **Driver trips screen** (`app/(app)/driver/trips/index.tsx`)
   - `GET /api/v1/driver/trips` with status filter
   - Paginated FlashList of `TripCard`

4. **Driver trip detail** (`app/(app)/driver/trips/[id].tsx`)
   - `GET /api/v1/driver/trips/{id}`
   - Trip info header
   - Status action button (contextual based on current status):
     - planned/scheduled/loading → "Start Trip" → PUT `{status: "in_progress"}`
     - in_progress → "Complete Trip" → bottom sheet (end_mileage, actual_arrival) → PUT `{status: "completed"}`
   - Progress bar: `delivered_count / total_shipments`
   - Shipments list per receiver with delivery_status badge
   - "Update Delivery" → opens `DeliveryUpdateSheet`

5. **Delivery update bottom sheet** (`src/components/driver/DeliveryUpdateSheet.tsx`)
   - delivery_status picker
   - delivery_notes textarea
   - delivered_at (auto-filled with now(), editable)
   - `SignaturePad` → captures base64 → sends as `receiver_signature`
   - Submit → `PUT /api/v1/driver/trips/{id}/shipments/{shipmentId}`
   - On success: optimistic update of delivery_status badge in parent list

**Files to create:**
- `src/api/driver.ts`
- `src/hooks/useDriver.ts`
- `src/components/driver/TripCard.tsx`
- `src/components/driver/DeliveryUpdateSheet.tsx`
- `src/components/ui/SignaturePad.tsx`
- `app/(app)/driver/_layout.tsx`
- `app/(app)/driver/schedule.tsx`
- `app/(app)/driver/trips/index.tsx`
- `app/(app)/driver/trips/[id].tsx`
- `app/(app)/driver/profile.tsx`

**Done checklist:**
- [ ] Driver navigator renders instead of main nav for pure drivers
- [ ] Schedule shows today's trips at top
- [ ] "Start Trip" button sets status to in_progress
- [ ] "Complete Trip" collects end_mileage + actual_arrival
- [ ] Progress bar reflects delivered_count accurately
- [ ] DeliveryUpdateSheet opens and submits correctly
- [ ] Signature pad renders and exports base64
- [ ] Delivered shipments sync status in parent trip list

---

### Phase 8 — Admin & User Management (Days 58–62)

**Goal:** Users, branches, reports, exchange rates — admin-only features.

**Steps:**

1. **Users screen** — list (admin only, `view_any_user`), create form (name, email, password, role, branches multi-select).

2. **Branches screen** — list of all user's branches, detail view, edit form (requires `update_branch`).

3. **Reports screen** — list of generated reports (title, report_type, period_start/end, generated_at), tap to view/download via `expo-file-system` + `expo-sharing`.

4. **Exchange rates screen** — list with chart (Victory Native), log new rate form.

**Done checklist:**
- [ ] Users list only visible to users with `view_any_user`
- [ ] Create user form includes branches multi-select
- [ ] Reports list shows downloadable file_path links

---

### Phase 9 — Profile, Settings & Polish (Days 63–70)

**Goal:** Profile, settings, dark mode, push notifications, performance polish.

**Steps:**

1. **Profile screen** — avatar upload (expo-image-picker → multipart PUT), name/phone editable fields, branch switcher, biometric toggle, theme toggle, change password sheet, sign out.

2. **Dark mode** — all components read from `settingsStore.theme`. Use `useColorScheme` for initial detection.

3. **Push notifications** (`expo-notifications`)
   - Register for push token on app start
   - Send token to server via profile update (add `push_token` field)
   - Handle foreground/background notification tap → navigate to relevant screen

4. **Loading skeletons** — add `LoadingSkeleton` placeholders on all list and detail screens while data is loading.

5. **Error boundaries** — wrap each tab in an `ErrorBoundary` component that shows a retry button.

6. **FlashList migration** — ensure all paginated lists use `@shopify/flash-list` for performance.

7. **Offline queue** — implement `useOfflineQueue` hook:
   ```typescript
   // On failed POST/PUT due to network error: store in AsyncStorage queue
   // On NetInfo isConnected: flush queue in order
   ```

**Done checklist:**
- [ ] Avatar upload works
- [ ] Branch switch invalidates all React Query caches
- [ ] Dark mode applied to every component
- [ ] Skeleton loaders on all list screens
- [ ] Error boundary catches and shows retry on all tab screens
- [ ] FlashList used for all lists > 20 items
- [ ] Offline queue stores and replays a create-shipment mutation

---

### Phase 10 — Testing & Deployment (Days 71–84)

**Goal:** Quality assurance, app store submission.

**Testing checklist:**

**Auth & Security**
- [ ] Token expired → redirects to login (no white screen)
- [ ] Wrong branch ID → 403 handled gracefully
- [ ] Biometric fallback to password works on real device

**Permissions**
- [ ] All screens with `PermissionGate` hide correctly for users without that permission
- [ ] FABs hidden when user lacks create permission
- [ ] Edit/Delete actions hidden when user lacks permission

**Shipments**
- [ ] 3-step create with 2 receivers and 3 items each → submits all data
- [ ] Media upload (photo + video) works on iOS and Android
- [ ] Public tracking works unauthenticated

**Driver**
- [ ] Pure driver account sees only driver nav (no admin screens)
- [ ] Driver+admin account sees both navigators
- [ ] Signature captures correctly on touchscreen

**Cashbook**
- [ ] bank_balance auto-updates after creating an entry
- [ ] Month navigation shows correct entries
- [ ] WHT wht_amount computed by server

**Offline**
- [ ] Turn off network → app shows stale indicator, not crash
- [ ] Create queued mutation offline → reconnect → mutation fires

**Performance**
- [ ] Lists with 100+ items scroll at 60fps
- [ ] Dashboard loads within 1.5s on WiFi
- [ ] No unnecessary re-renders (check with React DevTools)

**Build & Deploy:**

```bash
# iOS build
eas build --platform ios --profile production

# Android build
eas build --platform android --profile production

# OTA update
eas update --channel production --message "v1.0.0 release"
```

**EAS config (`eas.json`):**
```json
{
  "build": {
    "production": {
      "env": { "EXPO_PUBLIC_API_URL": "https://api.kasabazaar.com" }
    }
  }
}
```

---

## Driver Role — Quick Reference

Drivers log in with the same app. The backend identifies them via `staff.role.code === 'DRIVER'` in `DriverController::resolveDriverStaff()`. No Spatie permissions needed for driver endpoints.

| Driver endpoint | Guarded by |
|----------------|-----------|
| `GET /driver/profile` | `resolveDriverStaff()` — aborts 403 if user has no linked DRIVER staff |
| `GET /driver/schedule` | same |
| `GET /driver/trips` | same |
| `PUT /driver/trips/{id}/status` | same |
| `PUT /driver/trips/{id}/shipments/{shipmentId}` | same |

**Driver navigation override:**
```
isDriver = true AND !can('view_any_shipment')
  → show DriverNavigator (Schedule, My Trips, Profile)
isDriver = true AND can('view_any_shipment')
  → show MainNavigator + driver shortcuts
```
