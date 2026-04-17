# Kasabazaar Mobile — Pickup Schedules, Expenses & Income

> **Reference implementations:**
> - `app/Filament/Resources/PickupScheduleResource.php`
> - `app/Filament/Resources/ExpenseResource.php`
> - `app/Filament/Resources/IncomeResource.php`
> - `app/Http/Controllers/Api/V1/PickupScheduleController.php`
> - `app/Http/Controllers/Api/V1/ExpenseController.php`
> - `app/Http/Controllers/Api/V1/IncomeController.php`

---

## Table of Contents

1. [Pickup Schedules](#1-pickup-schedules)
2. [Expenses](#2-expenses)
3. [Incomes](#3-incomes)
4. [Shared Patterns](#4-shared-patterns)
5. [TypeScript Types](#5-typescript-types)
6. [Zod Schemas](#6-zod-schemas)
7. [API Modules](#7-api-modules)
8. [React Query Hooks](#8-react-query-hooks)
9. [File & Folder Structure](#9-file--folder-structure)
10. [Done Checklist](#10-done-checklist)

---

## 1. Pickup Schedules

### Permissions

| Action | Permission |
|--------|-----------|
| View list | `view_any_pickup_schedule` |
| View detail | `view_pickup_schedule` |
| Create | `create_pickup_schedule` |
| Update / status change | `update_pickup_schedule` |

### Status Values & Colors

| Value | Label | Color |
|-------|-------|-------|
| `scheduled` | Scheduled | Info `#3498DB` |
| `confirmed` | Confirmed | Primary `#1A3C5E` |
| `in-progress` | In Progress | Warning `#F39C12` |
| `completed` | Completed | Success `#2ECC71` |
| `cancelled` | Cancelled | Danger `#E74C3C` |

### API Endpoints

| Method | Endpoint | Params / Notes |
|--------|----------|----------------|
| GET | `/api/v1/pickup-schedules` | `?status=&per_page=` |
| POST | `/api/v1/pickup-schedules` | Create new schedule |
| GET | `/api/v1/pickup-schedules/{id}` | Detail |
| PUT | `/api/v1/pickup-schedules/{id}` | Update (status, assigned_to, etc.) |

> No DELETE — schedules are updated to `cancelled` instead.

### API Response Shape

```typescript
interface PickupSchedule {
  id: string;
  branch_id: string;
  client_id: string;
  shipment_id: string | null;
  assigned_to: string | null;
  scheduled_at: string;          // ISO datetime
  pickup_location: string;
  contact_phone: string | null;
  status: 'scheduled' | 'confirmed' | 'in-progress' | 'completed' | 'cancelled';
  notes: string | null;
  items_description: string | null;
  created_at: string;
  client: { id: string; name: string; phone: string } | null;
  assigned_staff: { id: string; name: string } | null;
}
```

### List Screen — `app/(app)/pickups/index.tsx`

**Layout:**
- Header: `"Pickup Schedule"` + count badge (upcoming only)
- Filter chips: All | Scheduled | Confirmed | In Progress | Completed | Cancelled
- Default filter: upcoming only (`scheduled_at >= now`)
- FlashList of `PickupCard` components sorted by `scheduled_at` ascending
- FAB `+` (requires `create_pickup_schedule`)

**PickupCard fields:**
- Client name (badge)
- `scheduled_at` — color-coded: red = past, amber = today, green = future
- `pickup_location` (truncated to 30 chars)
- Status badge
- Assigned staff name (or "Unassigned")

### Detail Screen — `app/(app)/pickups/[id].tsx`

**Sections:**
1. **Header card** — client name, scheduled datetime, status badge
2. **Location** — pickup_location, contact_phone
3. **Assignment** — assigned staff name
4. **Items** — items_description (text block)
5. **Linked Shipment** — shipping_reference badge if present
6. **Notes** — notes text block
7. **Action bar** (bottom):
   - "Update Status" button (requires `update_pickup_schedule`) → opens bottom sheet
   - "Edit" button → EditScreen

**Status Update Bottom Sheet:**
- Status picker (all 5 options)
- Notes textarea
- Submit → `PUT /api/v1/pickup-schedules/{id}` with `{ status, notes }`

### Create/Edit Form — `app/(app)/pickups/create.tsx` & `edit.tsx`

| Field | Type | Validation | Notes |
|-------|------|-----------|-------|
| client_id | Searchable picker (`GET /api/v1/lookup/clients`) | Required | Auto-fills `contact_phone` + `pickup_location` from client |
| scheduled_at | DateTime picker | Required, min = yesterday | |
| pickup_location | Text input | Required | Auto-filled from client.address |
| contact_phone | Phone input | Optional | Auto-filled from client.phone |
| assigned_to | Staff picker (`GET /api/v1/lookup/staff`) | Optional | |
| items_description | Textarea | Optional | Brief description |
| notes | Textarea | Optional | |

> `shipment_id` is optional but intentionally omitted from the create form (not in current API store validation). Can be added via edit.

---

## 2. Expenses

### Permissions

| Action | Permission |
|--------|-----------|
| View list | `view_any_expense` |
| View detail | `view_expense` |
| Create | `create_expense` |
| Edit | `update_expense` |
| Delete | `delete_expense` |

### Expense Stage Values

| Value | Label |
|-------|-------|
| `pre_shipment` | Pre-Shipment |
| `during_shipment` | During Shipment |
| `post_shipment` | Post-Shipment |

### API Endpoints

| Method | Endpoint | Params / Notes |
|--------|----------|----------------|
| GET | `/api/v1/expenses` | `?expense_stage=&expense_category_id=&date_from=&date_to=&per_page=` |
| POST | `/api/v1/expenses` | Multipart if uploading receipt |
| GET | `/api/v1/expenses/{id}` | Detail |
| PUT | `/api/v1/expenses/{id}` | Multipart if updating receipt |
| DELETE | `/api/v1/expenses/{id}` | |
| GET | `/api/v1/expense-categories` | Returns `{ id, name, code, description }[]` |

### API Response Shape

```typescript
interface Expense {
  id: string;
  branch_id: string;
  shipment_id: string | null;
  expense_category_id: string;
  reference: string;             // auto-generated server-side
  title: string;
  description: string | null;
  amount_usd: number;
  exchange_rate: number;
  amount_ghs: number;            // server-computed = amount_usd × exchange_rate
  expense_date: string;          // date string "YYYY-MM-DD"
  expense_stage: 'pre_shipment' | 'during_shipment' | 'post_shipment' | null;
  vendor_name: string | null;
  receipt_path: string | null;   // full URL from server (asset('storage/...'))
  recorded_by: string;
  created_at: string;
  category: { id: string; name: string } | null;
}
```

### List Screen — `app/(app)/finance/expenses/index.tsx`

**Layout:**
- Header: `"Expenses"` + total (sum of `amount_usd` for current page — optional)
- Filter row: category picker + stage picker + date range (from/to)
- FlashList of `ExpenseCard` sorted by `expense_date` desc
- FAB `+` (requires `create_expense`)

**ExpenseCard fields:**
- `reference` (small, gray)
- `title` (bold)
- Category badge
- `expense_stage` badge
- `amount_usd` formatted as `$0.00` (danger color)
- `amount_ghs` formatted as `₵0.00`
- `expense_date`
- Shipment ref (small, if linked)

### Create/Edit Form — `app/(app)/finance/expenses/create.tsx`

| Field | Type | Validation | Notes |
|-------|------|-----------|-------|
| expense_category_id | Picker (`GET /api/v1/expense-categories`) | Required | |
| title | Text | Required, max 255 | |
| expense_stage | Picker: `pre_shipment` / `during_shipment` / `post_shipment` | Required | |
| description | Textarea | Optional | |
| vendor_name | Text | Optional | |
| shipment_id | Searchable picker (search shipments by reference) | Optional | Auto-sets `branch_id` server-side |
| expense_date | Date picker | Required, default today | |
| amount_usd | Decimal | Required, min 0 | |
| exchange_rate | Decimal | Required | Auto-filled from `GET /api/v1/lookup/exchange-rate` |
| amount_ghs | Read-only | — | `amount_usd × exchange_rate`, shown as preview |
| receipt | Image picker (`expo-image-picker`) | Optional, max 2MB | Sent as multipart `receipt` field |

**Submit:** `POST /api/v1/expenses` as `multipart/form-data` when receipt is attached, otherwise `application/json`.

**Edit:** Same form, pre-filled. Receipt shows existing image if `receipt_path` is set. Tap to replace.

---

## 3. Incomes

### Permissions

| Action | Permission |
|--------|-----------|
| View list | `view_any_income` |
| View detail | `view_income` |
| Create | `create_income` |
| Edit | `update_income` |
| Delete | `delete_income` |

### Status Values

| Value | Label | Color |
|-------|-------|-------|
| `pending` | Pending | Warning `#F39C12` |
| `received` | Received | Success `#2ECC71` |
| `cancelled` | Cancelled | Danger `#E74C3C` |

### Payment Method Values (exact strings for API)

| Value | Label |
|-------|-------|
| `cash` | Cash |
| `bank_transfer` | Bank Transfer |
| `mobile_money` | Mobile Money |
| `cheque` | Cheque |
| `card` | Card |
| `other` | Other |

### API Endpoints

| Method | Endpoint | Params / Notes |
|--------|----------|----------------|
| GET | `/api/v1/incomes` | `?status=&income_category_id=&date_from=&date_to=&per_page=` |
| POST | `/api/v1/incomes` | Multipart if uploading receipt |
| GET | `/api/v1/incomes/{id}` | Detail |
| PUT | `/api/v1/incomes/{id}` | Multipart if updating receipt |
| DELETE | `/api/v1/incomes/{id}` | |
| GET | `/api/v1/income-categories` | Returns `{ id, name, code, description }[]` |

### API Response Shape

```typescript
interface Income {
  id: string;
  branch_id: string;
  income_category_id: string;
  shipment_id: string | null;
  reference: string;             // auto-generated server-side
  title: string;
  description: string | null;
  amount_usd: number;
  exchange_rate: number;
  amount_ghs: number;            // server-computed
  source_name: string | null;
  source_contact: string | null;
  income_date: string;           // "YYYY-MM-DD"
  payment_method: 'cash' | 'bank_transfer' | 'mobile_money' | 'cheque' | 'card' | 'other' | null;
  payment_reference: string | null;
  receipt_path: string | null;   // full URL
  status: 'pending' | 'received' | 'cancelled';
  recorded_by: string;
  created_at: string;
  category: { id: string; name: string } | null;
}
```

### List Screen — `app/(app)/finance/incomes/index.tsx`

**Layout:**
- Header: `"Income"` with total display
- Filter row: category picker + status picker + date range
- FlashList of `IncomeCard` sorted by `income_date` desc
- FAB `+` (requires `create_income`)

**IncomeCard fields:**
- `reference` (small, gray)
- `title` (bold)
- Category badge
- Status badge
- Payment method badge
- `amount_usd` formatted as `$0.00` (success color)
- `amount_ghs` formatted as `₵0.00`
- `income_date`

### Create/Edit Form — `app/(app)/finance/incomes/create.tsx`

| Field | Type | Validation | Notes |
|-------|------|-----------|-------|
| income_category_id | Picker (`GET /api/v1/income-categories`) | Required | |
| title | Text | Required, max 255 | |
| status | Picker: `pending` / `received` / `cancelled` | Required | Default `pending` |
| payment_method | Picker: 6 options above | Required | |
| description | Textarea | Optional | |
| payer_name | Text | Optional | `source_name` field |
| source_contact | Text | Optional | |
| payment_reference | Text | Optional | |
| shipment_id | Searchable picker | Optional | |
| income_date | Date picker | Required, default today | |
| amount_usd | Decimal | Required, min 0 | |
| exchange_rate | Decimal | Required | Auto-filled from lookup |
| amount_ghs | Read-only preview | — | `amount_usd × exchange_rate` |
| receipt | Image picker | Optional, max 2MB | Multipart `receipt` field |

---

## 4. Shared Patterns

### Amount / Exchange Rate Auto-Fill (Expenses & Incomes)

On form mount, call `GET /api/v1/lookup/exchange-rate` and pre-fill `exchange_rate`.
When `amount_usd` or `exchange_rate` changes, recompute preview:

```typescript
const amountGhs = (amountUsd * exchangeRate).toFixed(2);
```

Never send `amount_ghs` to the API — it is **server-computed** on create.

### Receipt / Image Upload (Expenses & Incomes)

```typescript
// Use expo-image-picker to select image
const result = await ImagePicker.launchImageLibraryAsync({
  mediaTypes: ImagePicker.MediaTypeOptions.Images,
  quality: 0.8,
});

// Build FormData for multipart upload
const formData = new FormData();
formData.append('receipt', {
  uri: result.assets[0].uri,
  name: 'receipt.jpg',
  type: 'image/jpeg',
} as any);
// ... append other fields
await api.post('/expenses', formData, {
  headers: { 'Content-Type': 'multipart/form-data' },
});
```

For **JSON-only** requests (no receipt), send as `application/json`.

### Scheduled Date Color Logic (Pickup Schedules)

```typescript
function getScheduledDateColor(scheduledAt: string): string {
  const date = dayjs(scheduledAt);
  if (date.isBefore(dayjs(), 'day')) return Colors.danger;   // past
  if (date.isSame(dayjs(), 'day'))   return Colors.warning;  // today
  return Colors.success;                                      // future
}
```

### Shipment Picker (Shared)

For `shipment_id` fields in expenses, incomes, and pickup schedules — use a searchable bottom sheet that calls `GET /api/v1/shipments?search={query}` and displays `shipping_reference + client.name`.

---

## 5. TypeScript Types

```typescript
// src/types/pickupSchedule.ts
export interface PickupSchedule {
  id: string;
  branch_id: string;
  client_id: string;
  shipment_id: string | null;
  assigned_to: string | null;
  scheduled_at: string;
  pickup_location: string;
  contact_phone: string | null;
  status: 'scheduled' | 'confirmed' | 'in-progress' | 'completed' | 'cancelled';
  notes: string | null;
  items_description: string | null;
  created_at: string;
  client: { id: string; name: string; phone: string } | null;
  assigned_staff: { id: string; name: string } | null;
}

// src/types/expense.ts
export interface Expense {
  id: string;
  branch_id: string;
  shipment_id: string | null;
  expense_category_id: string;
  reference: string;
  title: string;
  description: string | null;
  amount_usd: number;
  exchange_rate: number;
  amount_ghs: number;
  expense_date: string;
  expense_stage: 'pre_shipment' | 'during_shipment' | 'post_shipment' | null;
  vendor_name: string | null;
  receipt_path: string | null;
  recorded_by: string;
  created_at: string;
  category: { id: string; name: string } | null;
}

export interface ExpenseCategory {
  id: string;
  name: string;
  code: string;
  description: string | null;
}

// src/types/income.ts
export type IncomeStatus = 'pending' | 'received' | 'cancelled';
export type PaymentMethod = 'cash' | 'bank_transfer' | 'mobile_money' | 'cheque' | 'card' | 'other';

export interface Income {
  id: string;
  branch_id: string;
  income_category_id: string;
  shipment_id: string | null;
  reference: string;
  title: string;
  description: string | null;
  amount_usd: number;
  exchange_rate: number;
  amount_ghs: number;
  source_name: string | null;
  source_contact: string | null;
  income_date: string;
  payment_method: PaymentMethod | null;
  payment_reference: string | null;
  receipt_path: string | null;
  status: IncomeStatus;
  recorded_by: string;
  created_at: string;
  category: { id: string; name: string } | null;
}

export interface IncomeCategory {
  id: string;
  name: string;
  code: string;
  description: string | null;
}
```

---

## 6. Zod Schemas

```typescript
import { z } from 'zod';

// ── Pickup Schedule ──────────────────────────────────────────────────────────
export const createPickupSchema = z.object({
  client_id:         z.string().uuid(),
  scheduled_at:      z.string().datetime(),
  pickup_location:   z.string().min(1).max(255),
  contact_phone:     z.string().max(30).optional(),
  assigned_to:       z.string().uuid().optional(),
  items_description: z.string().optional(),
  notes:             z.string().optional(),
  shipment_id:       z.string().uuid().optional(),
});

export const updatePickupStatusSchema = z.object({
  status: z.enum(['scheduled', 'confirmed', 'in-progress', 'completed', 'cancelled']),
  notes:  z.string().optional(),
});

// ── Expense ─────────────────────────────────────────────────────────────────
export const createExpenseSchema = z.object({
  expense_category_id: z.string().uuid(),
  title:               z.string().min(1).max(255),
  expense_stage:       z.enum(['pre_shipment', 'during_shipment', 'post_shipment']),
  description:         z.string().optional(),
  vendor_name:         z.string().max(255).optional(),
  shipment_id:         z.string().uuid().optional(),
  expense_date:        z.string(),                   // "YYYY-MM-DD"
  amount_usd:          z.number().min(0),
  exchange_rate:       z.number().min(0),
  // receipt is handled as FormData, not in schema
});

// ── Income ──────────────────────────────────────────────────────────────────
export const createIncomeSchema = z.object({
  income_category_id: z.string().uuid(),
  title:              z.string().min(1).max(255),
  status:             z.enum(['pending', 'received', 'cancelled']).default('pending'),
  payment_method:     z.enum(['cash', 'bank_transfer', 'mobile_money', 'cheque', 'card', 'other']),
  description:        z.string().optional(),
  source_name:        z.string().max(255).optional(),
  source_contact:     z.string().max(100).optional(),
  payment_reference:  z.string().max(100).optional(),
  shipment_id:        z.string().uuid().optional(),
  income_date:        z.string(),                   // "YYYY-MM-DD"
  amount_usd:         z.number().min(0),
  exchange_rate:      z.number().min(0),
});
```

---

## 7. API Modules

```typescript
// src/api/pickupSchedules.ts
import api from './client';
import type { ApiResponse, PaginatedResponse, PickupSchedule } from '@/types';

export const pickupSchedulesApi = {
  list: (params?: { status?: string; per_page?: number; page?: number }) =>
    api.get<PaginatedResponse<PickupSchedule>>('/pickup-schedules', { params }),

  detail: (id: string) =>
    api.get<ApiResponse<PickupSchedule>>(`/pickup-schedules/${id}`),

  create: (data: {
    client_id: string;
    scheduled_at: string;
    pickup_location: string;
    contact_phone?: string;
    assigned_to?: string;
    items_description?: string;
    notes?: string;
    shipment_id?: string;
  }) => api.post<ApiResponse<PickupSchedule>>('/pickup-schedules', data),

  update: (id: string, data: Partial<{
    status: string;
    assigned_to: string;
    scheduled_at: string;
    pickup_location: string;
    contact_phone: string;
    notes: string;
    items_description: string;
  }>) => api.put<ApiResponse<PickupSchedule>>(`/pickup-schedules/${id}`, data),
};


// src/api/expenses.ts
import api from './client';
import type { ApiResponse, PaginatedResponse, Expense, ExpenseCategory } from '@/types';

export const expensesApi = {
  list: (params?: {
    expense_stage?: string;
    expense_category_id?: string;
    date_from?: string;
    date_to?: string;
    per_page?: number;
    page?: number;
  }) => api.get<PaginatedResponse<Expense>>('/expenses', { params }),

  detail: (id: string) =>
    api.get<ApiResponse<Expense>>(`/expenses/${id}`),

  create: (data: FormData | object) =>
    api.post<ApiResponse<Expense>>('/expenses', data, {
      headers: data instanceof FormData
        ? { 'Content-Type': 'multipart/form-data' }
        : { 'Content-Type': 'application/json' },
    }),

  update: (id: string, data: FormData | object) =>
    api.put<ApiResponse<Expense>>(`/expenses/${id}`, data, {
      headers: data instanceof FormData
        ? { 'Content-Type': 'multipart/form-data' }
        : { 'Content-Type': 'application/json' },
    }),

  destroy: (id: string) =>
    api.delete<ApiResponse<null>>(`/expenses/${id}`),

  categories: () =>
    api.get<ApiResponse<ExpenseCategory[]>>('/expense-categories'),
};


// src/api/incomes.ts
import api from './client';
import type { ApiResponse, PaginatedResponse, Income, IncomeCategory } from '@/types';

export const incomesApi = {
  list: (params?: {
    status?: string;
    income_category_id?: string;
    date_from?: string;
    date_to?: string;
    per_page?: number;
    page?: number;
  }) => api.get<PaginatedResponse<Income>>('/incomes', { params }),

  detail: (id: string) =>
    api.get<ApiResponse<Income>>(`/incomes/${id}`),

  create: (data: FormData | object) =>
    api.post<ApiResponse<Income>>('/incomes', data, {
      headers: data instanceof FormData
        ? { 'Content-Type': 'multipart/form-data' }
        : { 'Content-Type': 'application/json' },
    }),

  update: (id: string, data: FormData | object) =>
    api.put<ApiResponse<Income>>(`/incomes/${id}`, data, {
      headers: data instanceof FormData
        ? { 'Content-Type': 'multipart/form-data' }
        : { 'Content-Type': 'application/json' },
    }),

  destroy: (id: string) =>
    api.delete<ApiResponse<null>>(`/incomes/${id}`),

  categories: () =>
    api.get<ApiResponse<IncomeCategory[]>>('/income-categories'),
};
```

---

## 8. React Query Hooks

```typescript
// src/hooks/usePickupSchedules.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { pickupSchedulesApi } from '@/api/pickupSchedules';

export const pickupKeys = {
  all:    ['pickups'] as const,
  list:   (filters: object) => ['pickups', 'list', filters] as const,
  detail: (id: string)      => ['pickups', 'detail', id] as const,
};

export function usePickupList(filters?: object) {
  return useQuery({
    queryKey: pickupKeys.list(filters ?? {}),
    queryFn:  () => pickupSchedulesApi.list(filters),
  });
}

export function useUpdatePickupStatus() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: { status: string; notes?: string } }) =>
      pickupSchedulesApi.update(id, data),
    onSuccess: (_, { id }) => {
      qc.invalidateQueries({ queryKey: pickupKeys.all });
      qc.invalidateQueries({ queryKey: pickupKeys.detail(id) });
    },
  });
}


// src/hooks/useExpenses.ts
import { useQuery, useMutation, useInfiniteQuery, useQueryClient } from '@tanstack/react-query';
import { expensesApi } from '@/api/expenses';

export const expenseKeys = {
  all:        ['expenses'] as const,
  list:       (f: object) => ['expenses', 'list', f] as const,
  detail:     (id: string) => ['expenses', 'detail', id] as const,
  categories: ['expense-categories'] as const,
};

export function useExpenseCategories() {
  return useQuery({
    queryKey: expenseKeys.categories,
    queryFn:  () => expensesApi.categories(),
    staleTime: 5 * 60_000,   // 5 min — categories don't change often
  });
}

export function useCreateExpense() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: FormData | object) => expensesApi.create(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: expenseKeys.all }),
  });
}

export function useDeleteExpense() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => expensesApi.destroy(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: expenseKeys.all }),
  });
}


// src/hooks/useIncomes.ts — same pattern as useExpenses
export const incomeKeys = {
  all:        ['incomes'] as const,
  list:       (f: object) => ['incomes', 'list', f] as const,
  detail:     (id: string) => ['incomes', 'detail', id] as const,
  categories: ['income-categories'] as const,
};
```

---

## 9. File & Folder Structure

```
app/(app)/
├── pickups/
│   ├── index.tsx          ← List with filter chips + FAB
│   ├── create.tsx         ← Create form
│   └── [id]/
│       ├── index.tsx      ← Detail (sections + action bar)
│       └── edit.tsx       ← Edit form (pre-filled)
│
└── finance/
    ├── expenses/
    │   ├── index.tsx      ← List with filters + FAB
    │   ├── create.tsx     ← Create form (with image picker)
    │   └── [id]/
    │       ├── index.tsx  ← Detail
    │       └── edit.tsx   ← Edit form
    └── incomes/
        ├── index.tsx
        ├── create.tsx
        └── [id]/
            ├── index.tsx
            └── edit.tsx

src/
├── api/
│   ├── pickupSchedules.ts
│   ├── expenses.ts
│   └── incomes.ts
├── hooks/
│   ├── usePickupSchedules.ts
│   ├── useExpenses.ts
│   └── useIncomes.ts
├── components/
│   ├── pickups/
│   │   ├── PickupCard.tsx
│   │   └── StatusUpdateSheet.tsx
│   └── finance/
│       ├── ExpenseCard.tsx
│       ├── IncomeCard.tsx
│       └── ReceiptPicker.tsx    ← Reusable image picker for receipt upload
└── types/
    ├── pickupSchedule.ts
    ├── expense.ts
    └── income.ts
```

---

## 10. Done Checklist

### Pickup Schedules
- [ ] List loads with `scheduled_at >= now` default filter
- [ ] Filter chips (All / status values) update list correctly
- [ ] `scheduled_at` badge color: red = past, amber = today, green = future
- [ ] Client auto-fills `contact_phone` and `pickup_location` on selection
- [ ] Staff picker loads from `GET /api/v1/lookup/staff`
- [ ] Status update bottom sheet submits `PUT` correctly
- [ ] Navigation badge on tab shows upcoming count
- [ ] No DELETE — cancelled status used instead

### Expenses
- [ ] List filters: category, stage, date range all work
- [ ] Categories loaded from `/api/v1/expense-categories` and cached 5 min
- [ ] Exchange rate auto-fills from lookup on form mount
- [ ] `amount_ghs` preview updates on `amount_usd` or `exchange_rate` change
- [ ] Receipt image picked via `expo-image-picker` and sent as multipart
- [ ] Existing receipt shows thumbnail in edit form with tap-to-replace
- [ ] Delete confirmation before `DELETE /api/v1/expenses/{id}`
- [ ] `shipment_id` uses searchable picker (not text field)
- [ ] `amount_ghs` is NOT sent to API (server-computed)

### Incomes
- [ ] List filters: category, status, date range all work
- [ ] Categories loaded from `/api/v1/income-categories` and cached 5 min
- [ ] Exchange rate auto-fills on form mount
- [ ] `amount_ghs` preview updates reactively
- [ ] Status picker: pending / received / cancelled
- [ ] Payment method picker: cash / bank_transfer / mobile_money / cheque / card / other
- [ ] Receipt upload works same as expenses
- [ ] `shipment_id` uses searchable picker
- [ ] Delete confirmation before `DELETE /api/v1/incomes/{id}`

### Shared
- [ ] `ReceiptPicker` component reused across expense and income forms
- [ ] `amount_ghs` shown as read-only preview (`₵` prefix) in both forms
- [ ] Multipart / JSON switching logic correct based on receipt presence
- [ ] All lists use `useInfiniteQuery` + FlashList infinite scroll
- [ ] Pull-to-refresh on all list screens
