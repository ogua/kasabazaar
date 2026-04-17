# Reports Module — React Native Implementation Reference

> **Source:** Derived from `FinancialDashboard`, `ExpenseReport`, `IncomeReport`, `PayrollReport`, and `ShipmentReports` Filament pages.
> **App stack:** React Native (Expo SDK 51), TypeScript, TanStack Query v5, Victory Native XL charts, Axios.
> **Theme:** Navy (#1A3C5E) + Amber (#F4A225) — matches existing `mobile.md` design system.

---

## Table of Contents

1. [Reports Overview & Navigation](#1-reports-overview--navigation)
2. [New API Endpoints Required](#2-new-api-endpoints-required)
3. [TypeScript Interfaces](#3-typescript-interfaces)
4. [TanStack Query Hooks](#4-tanstack-query-hooks)
5. [Screen: Financial Dashboard](#5-screen-financial-dashboard)
6. [Screen: Expense Report](#6-screen-expense-report)
7. [Screen: Income Report](#7-screen-income-report)
8. [Screen: Payroll Report](#8-screen-payroll-report)
9. [Screen: Shipment Reports](#9-screen-shipment-reports)
10. [Shared Components](#10-shared-components)
11. [File & Folder Structure](#11-file--folder-structure)
12. [Missing Component Implementations](#12-missing-component-implementations)
13. [Currency Utility](#13-currency-utility)
14. [Additional Reports (ReportService)](#14-additional-reports-reportservice)
15. [Backend: FinancialReportController (PHP)](#15-backend-financialreportcontroller-php)
16. [Backend: routes/api.php additions](#16-backend-routesapiphp-additions)

---

## 1. Reports Overview & Navigation

The Reports module sits inside the existing **Admin** drawer/bottom-tab navigator as a **Reports** stack.

```
ReportsStack
├── ReportsHome          (index — list of report tiles)
├── FinancialDashboard   (full-width widgets + charts)
├── ExpenseReport        (table + summary + charts)
├── IncomeReport         (table + summary + charts)
├── PayrollReport        (table + summary + chart)
└── ShipmentReports      (type selector + generated table)
```

### Navigation definitions

```typescript
// src/navigation/ReportsStack.tsx
import { createNativeStackNavigator } from '@react-navigation/native-stack';

export type ReportsStackParamList = {
  ReportsHome: undefined;
  FinancialDashboard: undefined;
  ExpenseReport: undefined;
  IncomeReport: undefined;
  PayrollReport: undefined;
  ShipmentReports: undefined;
};

const Stack = createNativeStackNavigator<ReportsStackParamList>();

export function ReportsStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="ReportsHome"        component={ReportsHomeScreen} />
      <Stack.Screen name="FinancialDashboard" component={FinancialDashboardScreen} />
      <Stack.Screen name="ExpenseReport"      component={ExpenseReportScreen} />
      <Stack.Screen name="IncomeReport"       component={IncomeReportScreen} />
      <Stack.Screen name="PayrollReport"      component={PayrollReportScreen} />
      <Stack.Screen name="ShipmentReports"    component={ShipmentReportsScreen} />
    </Stack.Navigator>
  );
}
```

---

## 2. New API Endpoints Required

The existing `GET /api/v1/reports` only lists saved report records. The following **new** endpoints must be added to `ReportController` (or a new `FinancialReportController`) to power the mobile screens.

### 2.1 Financial Dashboard Summary

```
GET /api/v1/reports/financial-dashboard
  ?start_date=2026-04-01
  &end_date=2026-04-30
  &container_number=5          (optional)
```

**Response shape:**
```json
{
  "data": {
    "overview": {
      "total_income_ghs":   0.00,
      "total_expense_ghs":  0.00,
      "total_payroll_ghs":  0.00,
      "net_profit_ghs":     0.00,
      "total_income_usd":   0.00,
      "total_expense_usd":  0.00,
      "net_profit_usd":     0.00
    },
    "kpis": {
      "total_shipments":     0,
      "delivered_shipments": 0,
      "pending_shipments":   0,
      "active_containers":   0,
      "delivery_rate":       0.00
    },
    "container_profit": [
      {
        "container_number": 5,
        "label": "CON5",
        "income_ghs": 0.00,
        "expense_ghs": 0.00,
        "profit_ghs": 0.00,
        "shipment_count": 0
      }
    ],
    "top_states": [
      { "state": "Greater Accra", "count": 0, "revenue_ghs": 0.00 }
    ],
    "monthly_trend": [
      { "month": "Jan", "income_ghs": 0.00, "expense_ghs": 0.00 }
    ]
  }
}
```

### 2.2 Expense Report

```
GET /api/v1/reports/expenses
  ?start_date=2026-04-01
  &end_date=2026-04-30
  &per_page=50
  &page=1
```

**Response shape:**
```json
{
  "summary": {
    "this_period_ghs":  0.00,
    "last_period_ghs":  0.00,
    "this_period_usd":  0.00,
    "last_period_usd":  0.00,
    "growth_percent":   0.00,
    "total_count":      0,
    "by_category": [
      { "category": "Freight", "count": 0, "total_usd": 0.00, "total_ghs": 0.00 }
    ],
    "by_stage": [
      { "stage": "origin", "count": 0, "total_usd": 0.00, "total_ghs": 0.00 }
    ],
    "start_date": "2026-04-01",
    "end_date":   "2026-04-30"
  },
  "data": [...],       // paginated
  "meta": { "current_page": 1, "last_page": 1, "total": 0, "per_page": 50 }
}
```

**Single expense item:**
```json
{
  "id": "uuid",
  "reference": "EXP-0001",
  "date": "2026-04-01",
  "category": "Freight",
  "description": "Port handling",
  "amount_usd": 0.00,
  "amount_ghs": 0.00,
  "exchange_rate": 15.50,
  "branch": "Accra",
  "shipment_ref": "RDD-01-26-001",
  "recorded_by": "John Doe",
  "expense_stage": "origin"
}
```

### 2.3 Income Report

```
GET /api/v1/reports/incomes
  ?start_date=2026-04-01
  &end_date=2026-04-30
  &per_page=50
  &page=1
```

**Response shape:**
```json
{
  "summary": {
    "this_period_ghs":  0.00,
    "last_period_ghs":  0.00,
    "this_period_usd":  0.00,
    "last_period_usd":  0.00,
    "growth_percent":   0.00,
    "total_count":      0,
    "by_category": [
      { "category": "Shipping Fees", "count": 0, "total_usd": 0.00, "total_ghs": 0.00 }
    ],
    "by_method": [
      { "method": "bank_transfer", "count": 0, "total_usd": 0.00, "total_ghs": 0.00 }
    ],
    "start_date": "2026-04-01",
    "end_date":   "2026-04-30"
  },
  "data": [...],
  "meta": { "current_page": 1, "last_page": 1, "total": 0, "per_page": 50 }
}
```

**Single income item:**
```json
{
  "id": "uuid",
  "reference": "INC-0001",
  "date": "2026-04-01",
  "category": "Shipping Fees",
  "description": "Container CON5 fees",
  "amount_usd": 0.00,
  "amount_ghs": 0.00,
  "exchange_rate": 15.50,
  "branch": "Accra",
  "shipment_ref": "RDD-01-26-001",
  "recorded_by": "Jane Doe",
  "status": "received",
  "payment_method": "bank_transfer"
}
```

### 2.4 Payroll Report

```
GET /api/v1/reports/payroll
  ?start_date=2026-04-01
  &end_date=2026-04-30
  &per_page=50
  &page=1
```

**Response shape:**
```json
{
  "summary": {
    "this_period":       0.00,
    "last_period":       0.00,
    "growth_percent":    0.00,
    "total_employees":   0,
    "avg_salary":        0.00,
    "total_deductions":  0.00,
    "total_bonuses":     0.00,
    "total_count":       0,
    "by_status": [
      { "status": "paid", "count": 0, "total": 0.00, "avg": 0.00 }
    ],
    "start_date": "2026-04-01",
    "end_date":   "2026-04-30"
  },
  "data": [...],
  "meta": { "current_page": 1, "last_page": 1, "total": 0, "per_page": 50 }
}
```

**Single payroll item:**
```json
{
  "id": "uuid",
  "employee": "Staff Name",
  "period": "Apr 01 - Apr 30, 2026",
  "pay_date": "2026-04-30",
  "gross_salary": 0.00,
  "deductions": 0.00,
  "bonuses": 0.00,
  "net_salary": 0.00,
  "status": "paid"
}
```

### 2.5 Shipment Reports

```
GET /api/v1/reports/shipments
  ?report_type=by_container | by_year | profit_loss | client_shipments
  &year=2026
  &container_sequence=5          (for by_container, profit_loss)
  &client_id=uuid                (for client_shipments)
  &start_date=2026-01-01         (for client_shipments)
  &end_date=2026-04-30           (for client_shipments)
```

**Response shape:**
```json
{
  "report_type": "by_container",
  "title": "Shipments by Container Report",
  "generated_at": "2026-04-05T12:00:00Z",
  "data": [...]
}
```

**by_container item:**
```json
{
  "container_number": 5,
  "label": "CON5",
  "year": 2026,
  "shipment_count": 12,
  "total_revenue_ghs": 0.00,
  "total_expense_ghs": 0.00,
  "profit_ghs": 0.00,
  "states": ["Greater Accra", "Ashanti"]
}
```

**by_year item:**
```json
{
  "month": "January",
  "month_number": 1,
  "year": 2026,
  "shipment_count": 0,
  "revenue_ghs": 0.00,
  "expense_ghs": 0.00,
  "profit_ghs": 0.00
}
```

**profit_loss item:**
```json
{
  "shipping_reference": "RDD-01-26-001",
  "client": "Client Name",
  "origin": "New York",
  "destination": "Accra",
  "status": "delivered",
  "revenue_ghs": 0.00,
  "expense_ghs": 0.00,
  "profit_ghs": 0.00,
  "profit_margin": 0.00
}
```

**client_shipments item:**
```json
{
  "shipping_reference": "RDD-01-26-001",
  "status": "delivered",
  "origin": "New York",
  "destination": "Accra",
  "shipped_at": "2026-01-15",
  "delivered_at": "2026-03-10",
  "total_ghs": 0.00,
  "total_usd": 0.00,
  "items_count": 3
}
```

---

## 3. TypeScript Interfaces

```typescript
// src/types/reports.ts

// ─── Common ──────────────────────────────────────────────────────────────────

export interface DateRangeFilter {
  start_date: string;  // 'YYYY-MM-DD'
  end_date: string;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

export interface CategoryBreakdown {
  category: string;
  count: number;
  total_usd: number;
  total_ghs: number;
}

// ─── Financial Dashboard ─────────────────────────────────────────────────────

export interface FinancialOverview {
  total_income_ghs:  number;
  total_expense_ghs: number;
  total_payroll_ghs: number;
  net_profit_ghs:    number;
  total_income_usd:  number;
  total_expense_usd: number;
  net_profit_usd:    number;
}

export interface ManagementKPI {
  total_shipments:     number;
  delivered_shipments: number;
  pending_shipments:   number;
  active_containers:   number;
  delivery_rate:       number;
}

export interface ContainerProfit {
  container_number: number;
  label: string;
  income_ghs:     number;
  expense_ghs:    number;
  profit_ghs:     number;
  shipment_count: number;
}

export interface TopState {
  state:       string;
  count:       number;
  revenue_ghs: number;
}

export interface MonthlyTrend {
  month:       string;
  income_ghs:  number;
  expense_ghs: number;
}

export interface FinancialDashboardData {
  overview:        FinancialOverview;
  kpis:            ManagementKPI;
  container_profit: ContainerProfit[];
  top_states:      TopState[];
  monthly_trend:   MonthlyTrend[];
}

// ─── Expense Report ──────────────────────────────────────────────────────────

export interface ExpenseItem {
  id:            string;
  reference:     string;
  date:          string;
  category:      string;
  description:   string;
  amount_usd:    number;
  amount_ghs:    number;
  exchange_rate: number;
  branch:        string;
  shipment_ref:  string;
  recorded_by:   string;
  expense_stage: string;
}

export interface ExpenseSummary {
  this_period_ghs: number;
  last_period_ghs: number;
  this_period_usd: number;
  last_period_usd: number;
  growth_percent:  number;
  total_count:     number;
  by_category:     CategoryBreakdown[];
  by_stage:        { stage: string; count: number; total_usd: number; total_ghs: number }[];
  start_date:      string;
  end_date:        string;
}

export interface ExpenseReportResponse {
  summary: ExpenseSummary;
  data:    ExpenseItem[];
  meta:    PaginationMeta;
}

// ─── Income Report ───────────────────────────────────────────────────────────

export interface IncomeItem {
  id:             string;
  reference:      string;
  date:           string;
  category:       string;
  description:    string;
  amount_usd:     number;
  amount_ghs:     number;
  exchange_rate:  number;
  branch:         string;
  shipment_ref:   string;
  recorded_by:    string;
  status:         string;
  payment_method: string;
}

export interface IncomeSummary {
  this_period_ghs: number;
  last_period_ghs: number;
  this_period_usd: number;
  last_period_usd: number;
  growth_percent:  number;
  total_count:     number;
  by_category:     CategoryBreakdown[];
  by_method:       { method: string; count: number; total_usd: number; total_ghs: number }[];
  start_date:      string;
  end_date:        string;
}

export interface IncomeReportResponse {
  summary: IncomeSummary;
  data:    IncomeItem[];
  meta:    PaginationMeta;
}

// ─── Payroll Report ──────────────────────────────────────────────────────────

export interface PayrollItem {
  id:           string;
  employee:     string;
  period:       string;
  pay_date:     string;
  gross_salary: number;
  deductions:   number;
  bonuses:      number;
  net_salary:   number;
  status:       string;
}

export interface PayrollSummary {
  this_period:      number;
  last_period:      number;
  growth_percent:   number;
  total_employees:  number;
  avg_salary:       number;
  total_deductions: number;
  total_bonuses:    number;
  total_count:      number;
  by_status: { status: string; count: number; total: number; avg: number }[];
  start_date: string;
  end_date:   string;
}

export interface PayrollReportResponse {
  summary: PayrollSummary;
  data:    PayrollItem[];
  meta:    PaginationMeta;
}

// ─── Shipment Reports ────────────────────────────────────────────────────────

export type ShipmentReportType = 'by_container' | 'by_year' | 'profit_loss' | 'client_shipments';

export interface ShipmentReportParams {
  report_type:        ShipmentReportType;
  year?:              number;
  container_sequence?: number;
  client_id?:         string;
  start_date?:        string;
  end_date?:          string;
}

export interface ByContainerItem {
  container_number: number;
  label:            string;
  year:             number;
  shipment_count:   number;
  total_revenue_ghs: number;
  total_expense_ghs: number;
  profit_ghs:       number;
  states:           string[];
}

export interface ByYearItem {
  month:          string;
  month_number:   number;
  year:           number;
  shipment_count: number;
  revenue_ghs:    number;
  expense_ghs:    number;
  profit_ghs:     number;
}

export interface ProfitLossItem {
  shipping_reference: string;
  client:             string;
  origin:             string;
  destination:        string;
  status:             string;
  revenue_ghs:        number;
  expense_ghs:        number;
  profit_ghs:         number;
  profit_margin:      number;
}

export interface ClientShipmentItem {
  shipping_reference: string;
  status:             string;
  origin:             string;
  destination:        string;
  shipped_at:         string;
  delivered_at:       string | null;
  total_ghs:          number;
  total_usd:          number;
  items_count:        number;
}

export interface ShipmentReportResponse {
  report_type:  ShipmentReportType;
  title:        string;
  generated_at: string;
  data:         ByContainerItem[] | ByYearItem[] | ProfitLossItem[] | ClientShipmentItem[];
}
```

---

## 4. TanStack Query Hooks

```typescript
// src/hooks/useReports.ts
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/axios';   // configured Axios instance from mobile.md

// ── Financial Dashboard ───────────────────────────────────────────────────────

export function useFinancialDashboard(
  filters: { start_date: string; end_date: string; container_number?: number | null }
) {
  return useQuery({
    queryKey: ['reports', 'financial-dashboard', filters],
    queryFn: async (): Promise<FinancialDashboardData> => {
      const { data } = await api.get('/reports/financial-dashboard', { params: filters });
      return data.data;
    },
    staleTime: 5 * 60 * 1000,
  });
}

// ── Expense Report ────────────────────────────────────────────────────────────

export function useExpenseReport(
  filters: DateRangeFilter & { page?: number; per_page?: number }
) {
  return useQuery({
    queryKey: ['reports', 'expenses', filters],
    queryFn: async (): Promise<ExpenseReportResponse> => {
      const { data } = await api.get('/reports/expenses', { params: filters });
      return data;
    },
    staleTime: 5 * 60 * 1000,
  });
}

// ── Income Report ─────────────────────────────────────────────────────────────

export function useIncomeReport(
  filters: DateRangeFilter & { page?: number; per_page?: number }
) {
  return useQuery({
    queryKey: ['reports', 'incomes', filters],
    queryFn: async (): Promise<IncomeReportResponse> => {
      const { data } = await api.get('/reports/incomes', { params: filters });
      return data;
    },
    staleTime: 5 * 60 * 1000,
  });
}

// ── Payroll Report ────────────────────────────────────────────────────────────

export function usePayrollReport(
  filters: DateRangeFilter & { page?: number; per_page?: number }
) {
  return useQuery({
    queryKey: ['reports', 'payroll', filters],
    queryFn: async (): Promise<PayrollReportResponse> => {
      const { data } = await api.get('/reports/payroll', { params: filters });
      return data;
    },
    staleTime: 5 * 60 * 1000,
  });
}

// ── Shipment Reports ──────────────────────────────────────────────────────────

export function useShipmentReport(params: ShipmentReportParams, enabled: boolean) {
  return useQuery({
    queryKey: ['reports', 'shipments', params],
    queryFn: async (): Promise<ShipmentReportResponse> => {
      const { data } = await api.get('/reports/shipments', { params });
      return data;
    },
    enabled,
    staleTime: 5 * 60 * 1000,
  });
}
```

---

## 5. Screen: Financial Dashboard

### 5.1 State & Filters

```typescript
// src/screens/reports/FinancialDashboardScreen.tsx
interface Filters {
  start_date:       string;
  end_date:         string;
  container_number: number | null;
}
```

Default: `start_date = startOfMonth`, `end_date = today`, `container_number = null`.

### 5.2 Layout

```
┌─────────────────────────────────────────┐
│  ← Financial Dashboard            [🔄]  │
├─────────────────────────────────────────┤
│  Filter Bar (DateRange + Container)     │
├─────────────────────────────────────────┤
│  OverviewCard (full-width)              │
│   Income GHS | Expense GHS | Net Profit │
│   Income USD | Expense USD | Net USD    │
├─────────────────────────────────────────┤
│  KPI Row (2 cols)                       │
│   Total Ships | Delivered               │
│   Pending     | Delivery Rate %         │
├─────────────────────────────────────────┤
│  Monthly Trend Chart (Bar)             │
│   Income vs Expense by month           │
├─────────────────────────────────────────┤
│  Container Profit List (horizontal     │
│  scroll cards)                         │
├─────────────────────────────────────────┤
│  Top States List (rank rows)           │
└─────────────────────────────────────────┘
```

### 5.3 Component Code

```typescript
// src/screens/reports/FinancialDashboardScreen.tsx
import React, { useState } from 'react';
import { ScrollView, RefreshControl, StyleSheet, View } from 'react-native';
import { useFinancialDashboard } from '@/hooks/useReports';
import { DateRangeFilter } from '@/components/reports/DateRangeFilter';
import { OverviewCard } from '@/components/reports/OverviewCard';
import { KPIGrid } from '@/components/reports/KPIGrid';
import { MonthlyTrendChart } from '@/components/reports/MonthlyTrendChart';
import { ContainerProfitScroll } from '@/components/reports/ContainerProfitScroll';
import { TopStatesList } from '@/components/reports/TopStatesList';
import { ReportHeader } from '@/components/reports/ReportHeader';
import { LoadingOverlay } from '@/components/ui/LoadingOverlay';
import { Colors } from '@/theme/colors';
import dayjs from 'dayjs';

export function FinancialDashboardScreen() {
  const [filters, setFilters] = useState({
    start_date:       dayjs().startOf('month').format('YYYY-MM-DD'),
    end_date:         dayjs().format('YYYY-MM-DD'),
    container_number: null as number | null,
  });

  const { data, isLoading, refetch, isRefetching } = useFinancialDashboard(filters);

  return (
    <View style={styles.container}>
      <ReportHeader title="Financial Dashboard" onBack />
      <ScrollView
        style={styles.scroll}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} />}
      >
        <DateRangeFilter
          startDate={filters.start_date}
          endDate={filters.end_date}
          onChange={(s, e) => setFilters(f => ({ ...f, start_date: s, end_date: e }))}
        />

        {isLoading ? (
          <LoadingOverlay />
        ) : data ? (
          <>
            <OverviewCard overview={data.overview} />
            <KPIGrid kpis={data.kpis} />
            <MonthlyTrendChart data={data.monthly_trend} />
            <ContainerProfitScroll items={data.container_profit} />
            <TopStatesList items={data.top_states} />
          </>
        ) : null}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgLight },
  scroll:    { flex: 1 },
});
```

### 5.4 OverviewCard

```typescript
// src/components/reports/OverviewCard.tsx
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '@/theme/colors';
import { formatGHS, formatUSD } from '@/utils/currency';

interface Props { overview: FinancialOverview }

export function OverviewCard({ overview }: Props) {
  const netPositive = overview.net_profit_ghs >= 0;

  return (
    <View style={styles.card}>
      <Text style={styles.title}>Financial Overview</Text>
      <View style={styles.row}>
        <MetricCell label="Income"  value={formatGHS(overview.total_income_ghs)}  color={Colors.success} />
        <MetricCell label="Expense" value={formatGHS(overview.total_expense_ghs)} color={Colors.danger} />
        <MetricCell
          label="Net Profit"
          value={formatGHS(overview.net_profit_ghs)}
          color={netPositive ? Colors.success : Colors.danger}
        />
      </View>
      <View style={styles.row}>
        <MetricCell label="Inc (USD)" value={formatUSD(overview.total_income_usd)} color={Colors.success} />
        <MetricCell label="Exp (USD)" value={formatUSD(overview.total_expense_usd)} color={Colors.danger} />
        <MetricCell
          label="Net (USD)"
          value={formatUSD(overview.net_profit_usd)}
          color={netPositive ? Colors.success : Colors.danger}
        />
      </View>
    </View>
  );
}

function MetricCell({ label, value, color }: { label: string; value: string; color: string }) {
  return (
    <View style={styles.cell}>
      <Text style={[styles.value, { color }]}>{value}</Text>
      <Text style={styles.label}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  card:  { backgroundColor: Colors.primary, margin: 16, borderRadius: 12, padding: 20 },
  title: { color: '#fff', fontSize: 16, fontWeight: '600', marginBottom: 16 },
  row:   { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 12 },
  cell:  { flex: 1, alignItems: 'center' },
  value: { fontSize: 15, fontWeight: '700' },
  label: { fontSize: 11, color: 'rgba(255,255,255,0.7)', marginTop: 2 },
});
```

### 5.5 Monthly Trend Chart (Victory Native)

```typescript
// src/components/reports/MonthlyTrendChart.tsx
import { VictoryBar, VictoryChart, VictoryGroup, VictoryAxis, VictoryLegend } from 'victory-native';
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '@/theme/colors';

interface Props { data: MonthlyTrend[] }

export function MonthlyTrendChart({ data }: Props) {
  const incomeData  = data.map((d, x) => ({ x: x + 1, y: d.income_ghs / 1000, label: d.month }));
  const expenseData = data.map((d, x) => ({ x: x + 1, y: d.expense_ghs / 1000 }));

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Income vs Expense (GHS '000)</Text>
      <VictoryChart width={360} height={220} domainPadding={{ x: 20 }}>
        <VictoryAxis
          tickValues={data.map((_, i) => i + 1)}
          tickFormat={data.map(d => d.month.slice(0, 3))}
          style={{ tickLabels: { fontSize: 10, fill: Colors.textSecond } }}
        />
        <VictoryAxis dependentAxis
          style={{ tickLabels: { fontSize: 10, fill: Colors.textSecond } }}
        />
        <VictoryGroup offset={12} colorScale={[Colors.success, Colors.danger]}>
          <VictoryBar data={incomeData}  barWidth={10} cornerRadius={{ top: 3 }} />
          <VictoryBar data={expenseData} barWidth={10} cornerRadius={{ top: 3 }} />
        </VictoryGroup>
        <VictoryLegend
          x={20} y={0}
          orientation="horizontal"
          data={[
            { name: 'Income',  symbol: { fill: Colors.success } },
            { name: 'Expense', symbol: { fill: Colors.danger  } },
          ]}
          style={{ labels: { fontSize: 11 } }}
        />
      </VictoryChart>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { backgroundColor: '#fff', margin: 16, borderRadius: 12, padding: 16 },
  title:     { fontSize: 14, fontWeight: '600', color: Colors.primary, marginBottom: 8 },
});
```

---

## 6. Screen: Expense Report

### 6.1 Layout

```
┌─────────────────────────────────────────┐
│  ← Expense Report             [PDF] [XLS]│
├─────────────────────────────────────────┤
│  DateRangeFilter                        │
├─────────────────────────────────────────┤
│  SummaryCards (2 cols)                  │
│   This Period GHS | Last Period GHS     │
│   This Period USD | Growth %            │
├─────────────────────────────────────────┤
│  PieChart: Expenses by Category         │
├─────────────────────────────────────────┤
│  StageBreakdown horizontal scroll       │
├─────────────────────────────────────────┤
│  Section: Expense Transactions          │
│  FlashList of ExpenseRow items          │
│  [Load More] pagination                 │
└─────────────────────────────────────────┘
```

### 6.2 Component Code

```typescript
// src/screens/reports/ExpenseReportScreen.tsx
import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { useExpenseReport } from '@/hooks/useReports';
import { DateRangeFilter } from '@/components/reports/DateRangeFilter';
import { SummaryCards } from '@/components/reports/SummaryCards';
import { CategoryPieChart } from '@/components/reports/CategoryPieChart';
import { ExpenseRow } from '@/components/reports/ExpenseRow';
import { ReportHeader } from '@/components/reports/ReportHeader';
import { useExportReport } from '@/hooks/useExportReport';
import { Colors } from '@/theme/colors';
import dayjs from 'dayjs';

export function ExpenseReportScreen() {
  const [filters, setFilters] = useState({
    start_date: dayjs().startOf('month').format('YYYY-MM-DD'),
    end_date:   dayjs().format('YYYY-MM-DD'),
    page:       1,
    per_page:   50,
  });

  const { data, isLoading, refetch } = useExpenseReport(filters);
  const { exportPdf, exportExcel } = useExportReport('expenses', filters);

  const summary = data?.summary;
  const items   = data?.data ?? [];

  return (
    <View style={styles.container}>
      <ReportHeader
        title="Expense Report"
        onBack
        actions={[
          { label: 'PDF',   icon: 'file-pdf-box',    color: Colors.danger,  onPress: exportPdf   },
          { label: 'Excel', icon: 'microsoft-excel',  color: Colors.success, onPress: exportExcel },
        ]}
      />
      <FlashList
        data={items}
        estimatedItemSize={70}
        keyExtractor={item => item.id}
        renderItem={({ item }) => <ExpenseRow item={item} />}
        ListHeaderComponent={
          <>
            <DateRangeFilter
              startDate={filters.start_date}
              endDate={filters.end_date}
              onChange={(s, e) => setFilters(f => ({ ...f, start_date: s, end_date: e, page: 1 }))}
            />
            {summary && (
              <>
                <SummaryCards
                  cards={[
                    { label: 'This Period',  value: summary.this_period_ghs, currency: 'GHS', color: Colors.danger },
                    { label: 'Last Period',  value: summary.last_period_ghs, currency: 'GHS', color: Colors.textSecond },
                    { label: 'This (USD)',   value: summary.this_period_usd, currency: 'USD', color: Colors.danger },
                    { label: 'Growth',       value: summary.growth_percent,  suffix: '%',     color: summary.growth_percent > 0 ? Colors.danger : Colors.success },
                  ]}
                />
                <CategoryPieChart
                  title="By Category"
                  data={summary.by_category.map(c => ({ label: c.category, value: c.total_ghs }))}
                />
              </>
            )}
            <Text style={styles.sectionTitle}>Transactions ({summary?.total_count ?? 0})</Text>
          </>
        }
        ListFooterComponent={
          data?.meta && data.meta.current_page < data.meta.last_page ? (
            <TouchableOpacity
              style={styles.loadMore}
              onPress={() => setFilters(f => ({ ...f, page: f.page + 1 }))}
            >
              <Text style={styles.loadMoreText}>Load More</Text>
            </TouchableOpacity>
          ) : null
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container:    { flex: 1, backgroundColor: Colors.bgLight },
  sectionTitle: { fontSize: 15, fontWeight: '600', color: Colors.primary, paddingHorizontal: 16, paddingVertical: 12 },
  loadMore:     { margin: 16, padding: 14, backgroundColor: Colors.primary, borderRadius: 8, alignItems: 'center' },
  loadMoreText: { color: '#fff', fontWeight: '600' },
});
```

### 6.3 ExpenseRow

```typescript
// src/components/reports/ExpenseRow.tsx
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '@/theme/colors';
import { formatGHS, formatUSD } from '@/utils/currency';

export function ExpenseRow({ item }: { item: ExpenseItem }) {
  return (
    <View style={styles.row}>
      <View style={styles.left}>
        <Text style={styles.ref}>{item.reference}</Text>
        <Text style={styles.category}>{item.category} · {item.expense_stage}</Text>
        <Text style={styles.meta}>{item.branch} · {item.shipment_ref}</Text>
      </View>
      <View style={styles.right}>
        <Text style={styles.amountGhs}>{formatGHS(item.amount_ghs)}</Text>
        <Text style={styles.amountUsd}>{formatUSD(item.amount_usd)}</Text>
        <Text style={styles.date}>{item.date}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  row:        { flexDirection: 'row', backgroundColor: '#fff', padding: 14, marginHorizontal: 16, marginBottom: 8, borderRadius: 10, elevation: 1 },
  left:       { flex: 1 },
  right:      { alignItems: 'flex-end', justifyContent: 'center' },
  ref:        { fontSize: 14, fontWeight: '600', color: Colors.primary },
  category:   { fontSize: 12, color: Colors.textSecond, marginTop: 2 },
  meta:       { fontSize: 11, color: Colors.textSecond, marginTop: 2 },
  amountGhs:  { fontSize: 14, fontWeight: '700', color: Colors.danger },
  amountUsd:  { fontSize: 12, color: Colors.textSecond },
  date:       { fontSize: 11, color: Colors.textSecond, marginTop: 2 },
});
```

---

## 7. Screen: Income Report

### 7.1 Layout (mirrors Expense Report)

```
┌─────────────────────────────────────────┐
│  ← Income Report             [PDF] [XLS]│
├─────────────────────────────────────────┤
│  DateRangeFilter                        │
├─────────────────────────────────────────┤
│  SummaryCards: This/Last GHS, USD, Growth│
├─────────────────────────────────────────┤
│  PieChart: By Category                  │
├─────────────────────────────────────────┤
│  BarChart: By Payment Method            │
├─────────────────────────────────────────┤
│  Income Transaction Rows (FlashList)    │
└─────────────────────────────────────────┘
```

### 7.2 Key Differences from Expense Report

| Aspect | Income | Expense |
|--------|--------|---------|
| Amount color | `Colors.success` | `Colors.danger` |
| Status badge | `received` / `pending` | `expense_stage` |
| Extra breakdown | `by_method` (payment method) | `by_stage` |
| Only counted | `status = received` | all expenses |

### 7.3 IncomeRow

```typescript
// src/components/reports/IncomeRow.tsx
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '@/theme/colors';
import { formatGHS, formatUSD } from '@/utils/currency';

const statusColor: Record<string, string> = {
  received: Colors.success,
  pending:  Colors.warning,
  cancelled: Colors.danger,
};

export function IncomeRow({ item }: { item: IncomeItem }) {
  return (
    <View style={styles.row}>
      <View style={styles.left}>
        <Text style={styles.ref}>{item.reference}</Text>
        <Text style={styles.category}>{item.category}</Text>
        <Text style={styles.meta}>{item.payment_method} · {item.shipment_ref}</Text>
      </View>
      <View style={styles.right}>
        <Text style={styles.amountGhs}>{formatGHS(item.amount_ghs)}</Text>
        <Text style={styles.amountUsd}>{formatUSD(item.amount_usd)}</Text>
        <Text style={[styles.status, { color: statusColor[item.status] ?? Colors.textSecond }]}>
          {item.status.toUpperCase()}
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  row:       { flexDirection: 'row', backgroundColor: '#fff', padding: 14, marginHorizontal: 16, marginBottom: 8, borderRadius: 10, elevation: 1 },
  left:      { flex: 1 },
  right:     { alignItems: 'flex-end', justifyContent: 'center' },
  ref:       { fontSize: 14, fontWeight: '600', color: Colors.primary },
  category:  { fontSize: 12, color: Colors.textSecond, marginTop: 2 },
  meta:      { fontSize: 11, color: Colors.textSecond, marginTop: 2 },
  amountGhs: { fontSize: 14, fontWeight: '700', color: Colors.success },
  amountUsd: { fontSize: 12, color: Colors.textSecond },
  status:    { fontSize: 11, fontWeight: '600', marginTop: 2 },
});
```

---

## 8. Screen: Payroll Report

### 8.1 Layout

```
┌─────────────────────────────────────────┐
│  ← Payroll Report            [PDF] [XLS]│
├─────────────────────────────────────────┤
│  DateRangeFilter                        │
├─────────────────────────────────────────┤
│  SummaryCards                           │
│   Total Net | Avg Salary | Employees    │
│   Deductions | Bonuses | Growth %       │
├─────────────────────────────────────────┤
│  BarChart: By Status (paid/pending)     │
├─────────────────────────────────────────┤
│  Payroll Entry Rows (FlashList)         │
└─────────────────────────────────────────┘
```

### 8.2 PayrollRow

```typescript
// src/components/reports/PayrollRow.tsx
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '@/theme/colors';
import { formatGHS } from '@/utils/currency';

const statusColor: Record<string, string> = {
  paid:    Colors.success,
  pending: Colors.warning,
  cancelled: Colors.danger,
};

export function PayrollRow({ item }: { item: PayrollItem }) {
  return (
    <View style={styles.row}>
      <View style={styles.left}>
        <Text style={styles.employee}>{item.employee}</Text>
        <Text style={styles.period}>{item.period}</Text>
        <Text style={styles.meta}>
          Gross: {formatGHS(item.gross_salary)} · Deductions: {formatGHS(item.deductions)}
        </Text>
      </View>
      <View style={styles.right}>
        <Text style={styles.net}>{formatGHS(item.net_salary)}</Text>
        {item.bonuses > 0 && (
          <Text style={styles.bonus}>+{formatGHS(item.bonuses)} bonus</Text>
        )}
        <Text style={[styles.status, { color: statusColor[item.status] ?? Colors.textSecond }]}>
          {item.status.toUpperCase()}
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  row:      { flexDirection: 'row', backgroundColor: '#fff', padding: 14, marginHorizontal: 16, marginBottom: 8, borderRadius: 10, elevation: 1 },
  left:     { flex: 1 },
  right:    { alignItems: 'flex-end', justifyContent: 'center' },
  employee: { fontSize: 14, fontWeight: '600', color: Colors.primary },
  period:   { fontSize: 12, color: Colors.textSecond, marginTop: 2 },
  meta:     { fontSize: 11, color: Colors.textSecond, marginTop: 2 },
  net:      { fontSize: 15, fontWeight: '700', color: Colors.primary },
  bonus:    { fontSize: 12, color: Colors.success },
  status:   { fontSize: 11, fontWeight: '600', marginTop: 2 },
});
```

---

## 9. Screen: Shipment Reports

### 9.1 State

```typescript
interface ShipmentReportState {
  report_type:        ShipmentReportType | null;
  year:               number;
  container_sequence: number | null;
  client_id:          string | null;
  start_date:         string | null;
  end_date:           string | null;
  generated:          boolean;
}
```

### 9.2 Layout

```
┌─────────────────────────────────────────┐
│  ← Shipment Reports                     │
├─────────────────────────────────────────┤
│  Report Type Picker (4 options)         │
│   • Shipments by Container              │
│   • Shipments by Year                   │
│   • Profit/Loss by Container            │
│   • Client Shipment History             │
├─────────────────────────────────────────┤
│  Contextual Filters (based on type)     │
│   Year Picker                           │
│   Container Picker (optional)           │
│   Client Picker + DateRange             │
├─────────────────────────────────────────┤
│  [Generate Report]  [Export PDF/Excel]  │
├─────────────────────────────────────────┤
│  ─── Generated Results ───              │
│  Dynamic table based on report_type     │
└─────────────────────────────────────────┘
```

### 9.3 Component Code

```typescript
// src/screens/reports/ShipmentReportsScreen.tsx
import React, { useState } from 'react';
import { ScrollView, View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { useShipmentReport } from '@/hooks/useReports';
import { ReportTypeSelector } from '@/components/reports/ReportTypeSelector';
import { ShipmentReportFilters } from '@/components/reports/ShipmentReportFilters';
import { ShipmentReportTable } from '@/components/reports/ShipmentReportTable';
import { ReportHeader } from '@/components/reports/ReportHeader';
import { Colors } from '@/theme/colors';

const REPORT_TYPES = [
  { value: 'by_container',    label: 'By Container' },
  { value: 'by_year',         label: 'By Year' },
  { value: 'profit_loss',     label: 'Profit / Loss' },
  { value: 'client_shipments',label: 'Client History' },
] as const;

export function ShipmentReportsScreen() {
  const [params, setParams] = useState<Partial<ShipmentReportParams>>({
    year: new Date().getFullYear(),
  });
  const [generated, setGenerated] = useState(false);

  const { data, isLoading, refetch } = useShipmentReport(
    params as ShipmentReportParams,
    generated && !!params.report_type
  );

  function generate() {
    setGenerated(true);
    if (generated) refetch();
  }

  return (
    <View style={styles.container}>
      <ReportHeader title="Shipment Reports" onBack />
      <ScrollView>
        <ReportTypeSelector
          options={REPORT_TYPES}
          value={params.report_type ?? null}
          onChange={type => setParams({ report_type: type, year: params.year })}
        />

        {params.report_type && (
          <ShipmentReportFilters
            reportType={params.report_type}
            params={params}
            onChange={updates => setParams(p => ({ ...p, ...updates }))}
          />
        )}

        <TouchableOpacity
          style={[styles.generateBtn, !params.report_type && styles.disabledBtn]}
          onPress={generate}
          disabled={!params.report_type}
        >
          <Text style={styles.generateText}>Generate Report</Text>
        </TouchableOpacity>

        {isLoading && <Text style={styles.loadingText}>Generating...</Text>}

        {data && (
          <ShipmentReportTable reportType={data.report_type} data={data.data} />
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container:    { flex: 1, backgroundColor: Colors.bgLight },
  generateBtn:  { margin: 16, padding: 15, backgroundColor: Colors.primary, borderRadius: 10, alignItems: 'center' },
  disabledBtn:  { opacity: 0.5 },
  generateText: { color: '#fff', fontSize: 15, fontWeight: '700' },
  loadingText:  { textAlign: 'center', color: Colors.textSecond, padding: 20 },
});
```

### 9.4 ShipmentReportTable — dynamic columns per report type

```typescript
// src/components/reports/ShipmentReportTable.tsx
import { View, Text, ScrollView, StyleSheet } from 'react-native';
import { Colors } from '@/theme/colors';
import { formatGHS } from '@/utils/currency';

interface Props {
  reportType: ShipmentReportType;
  data:       any[];
}

export function ShipmentReportTable({ reportType, data }: Props) {
  if (reportType === 'by_year') return <ByYearTable data={data as ByYearItem[]} />;
  if (reportType === 'profit_loss') return <ProfitLossTable data={data as ProfitLossItem[]} />;
  if (reportType === 'client_shipments') return <ClientShipmentsTable data={data as ClientShipmentItem[]} />;
  return <ByContainerTable data={data as ByContainerItem[]} />;
}

function ByContainerTable({ data }: { data: ByContainerItem[] }) {
  return (
    <ScrollView horizontal>
      <View>
        <Row cells={['Container', 'Shipments', 'Revenue', 'Expense', 'Profit']} header />
        {data.map(item => (
          <Row key={item.container_number} cells={[
            item.label,
            String(item.shipment_count),
            formatGHS(item.total_revenue_ghs),
            formatGHS(item.total_expense_ghs),
            formatGHS(item.profit_ghs),
          ]} profit={item.profit_ghs} />
        ))}
      </View>
    </ScrollView>
  );
}

function ByYearTable({ data }: { data: ByYearItem[] }) {
  return (
    <ScrollView horizontal>
      <View>
        <Row cells={['Month', 'Shipments', 'Revenue', 'Expense', 'Profit']} header />
        {data.map(item => (
          <Row key={item.month_number} cells={[
            item.month,
            String(item.shipment_count),
            formatGHS(item.revenue_ghs),
            formatGHS(item.expense_ghs),
            formatGHS(item.profit_ghs),
          ]} profit={item.profit_ghs} />
        ))}
      </View>
    </ScrollView>
  );
}

function ProfitLossTable({ data }: { data: ProfitLossItem[] }) {
  return (
    <ScrollView horizontal>
      <View>
        <Row cells={['Reference', 'Client', 'Revenue', 'Expense', 'Profit', 'Margin']} header />
        {data.map(item => (
          <Row key={item.shipping_reference} cells={[
            item.shipping_reference,
            item.client,
            formatGHS(item.revenue_ghs),
            formatGHS(item.expense_ghs),
            formatGHS(item.profit_ghs),
            `${item.profit_margin.toFixed(1)}%`,
          ]} profit={item.profit_ghs} />
        ))}
      </View>
    </ScrollView>
  );
}

function ClientShipmentsTable({ data }: { data: ClientShipmentItem[] }) {
  return (
    <ScrollView horizontal>
      <View>
        <Row cells={['Reference', 'Status', 'Origin', 'Destination', 'Total (GHS)']} header />
        {data.map(item => (
          <Row key={item.shipping_reference} cells={[
            item.shipping_reference,
            item.status,
            item.origin,
            item.destination,
            formatGHS(item.total_ghs),
          ]} />
        ))}
      </View>
    </ScrollView>
  );
}

function Row({ cells, header, profit }: { cells: string[]; header?: boolean; profit?: number }) {
  const profitColor = profit !== undefined
    ? (profit >= 0 ? Colors.success : Colors.danger)
    : Colors.textPrimary;

  return (
    <View style={[styles.row, header && styles.headerRow]}>
      {cells.map((cell, i) => (
        <Text
          key={i}
          style={[
            styles.cell,
            header && styles.headerCell,
            !header && i === cells.length - 1 && profit !== undefined && { color: profitColor, fontWeight: '700' },
          ]}
          numberOfLines={1}
        >
          {cell}
        </Text>
      ))}
    </View>
  );
}

const CELL_WIDTH = 110;
const styles = StyleSheet.create({
  row:        { flexDirection: 'row', borderBottomWidth: 1, borderColor: Colors.border },
  headerRow:  { backgroundColor: Colors.primary },
  cell:       { width: CELL_WIDTH, padding: 10, fontSize: 12, color: Colors.textPrimary },
  headerCell: { color: '#fff', fontWeight: '600', fontSize: 12 },
});
```

---

## 10. Shared Components

### 10.1 DateRangeFilter

```typescript
// src/components/reports/DateRangeFilter.tsx
import { View, Text, TouchableOpacity, StyleSheet, Platform } from 'react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useState } from 'react';
import { Colors } from '@/theme/colors';
import dayjs from 'dayjs';

interface Props {
  startDate: string;
  endDate:   string;
  onChange:  (start: string, end: string) => void;
}

export function DateRangeFilter({ startDate, endDate, onChange }: Props) {
  const [show, setShow] = useState<'start' | 'end' | null>(null);

  function handleChange(type: 'start' | 'end', date: Date | undefined) {
    setShow(null);
    if (!date) return;
    const formatted = dayjs(date).format('YYYY-MM-DD');
    if (type === 'start') onChange(formatted, endDate);
    else onChange(startDate, formatted);
  }

  return (
    <View style={styles.container}>
      <TouchableOpacity style={styles.btn} onPress={() => setShow('start')}>
        <Text style={styles.label}>From</Text>
        <Text style={styles.value}>{startDate}</Text>
      </TouchableOpacity>
      <Text style={styles.sep}>→</Text>
      <TouchableOpacity style={styles.btn} onPress={() => setShow('end')}>
        <Text style={styles.label}>To</Text>
        <Text style={styles.value}>{endDate}</Text>
      </TouchableOpacity>

      {show && (
        <DateTimePicker
          value={dayjs(show === 'start' ? startDate : endDate).toDate()}
          mode="date"
          display={Platform.OS === 'ios' ? 'inline' : 'default'}
          onChange={(_, date) => handleChange(show, date)}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flexDirection: 'row', alignItems: 'center', margin: 16, backgroundColor: '#fff', borderRadius: 10, padding: 12, elevation: 1 },
  btn:       { flex: 1, alignItems: 'center' },
  label:     { fontSize: 11, color: Colors.textSecond },
  value:     { fontSize: 13, fontWeight: '600', color: Colors.primary, marginTop: 2 },
  sep:       { color: Colors.textSecond, marginHorizontal: 8 },
});
```

### 10.2 SummaryCards

```typescript
// src/components/reports/SummaryCards.tsx
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '@/theme/colors';

interface CardData {
  label:    string;
  value:    number;
  currency?: 'GHS' | 'USD';
  suffix?:  string;
  color:    string;
}

export function SummaryCards({ cards }: { cards: CardData[] }) {
  return (
    <View style={styles.grid}>
      {cards.map((card, i) => (
        <View key={i} style={styles.card}>
          <Text style={[styles.value, { color: card.color }]}>
            {card.currency === 'GHS'
              ? `GH₵ ${Number(card.value).toLocaleString('en-GH', { minimumFractionDigits: 2 })}`
              : card.currency === 'USD'
              ? `$ ${Number(card.value).toLocaleString('en-US', { minimumFractionDigits: 2 })}`
              : `${card.value.toFixed(1)}${card.suffix ?? ''}`}
          </Text>
          <Text style={styles.label}>{card.label}</Text>
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  grid:  { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: 16, gap: 8 },
  card:  { width: '47%', backgroundColor: '#fff', borderRadius: 10, padding: 14, elevation: 1 },
  value: { fontSize: 15, fontWeight: '700' },
  label: { fontSize: 11, color: Colors.textSecond, marginTop: 4 },
});
```

### 10.3 CategoryPieChart

```typescript
// src/components/reports/CategoryPieChart.tsx
import { VictoryPie, VictoryLegend } from 'victory-native';
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '@/theme/colors';

const PALETTE = [
  Colors.primary, Colors.accent, Colors.success, Colors.danger,
  Colors.info, Colors.warning, '#9B59B6', '#1ABC9C',
];

interface Props {
  title: string;
  data:  { label: string; value: number }[];
}

export function CategoryPieChart({ title, data }: Props) {
  const total   = data.reduce((s, d) => s + d.value, 0);
  const slices  = data.slice(0, 8).map((d, i) => ({
    x: d.label,
    y: d.value,
    label: total > 0 ? `${((d.value / total) * 100).toFixed(0)}%` : '0%',
  }));

  return (
    <View style={styles.container}>
      <Text style={styles.title}>{title}</Text>
      <VictoryPie
        data={slices}
        width={300}
        height={200}
        colorScale={PALETTE}
        innerRadius={50}
        labelRadius={90}
        style={{ labels: { fontSize: 10, fill: '#fff', fontWeight: '600' } }}
        padding={{ top: 10, bottom: 10 }}
      />
      <VictoryLegend
        x={10}
        y={0}
        width={340}
        orientation="horizontal"
        itemsPerRow={3}
        data={slices.map((s, i) => ({ name: s.x, symbol: { fill: PALETTE[i] } }))}
        style={{ labels: { fontSize: 10 } }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { backgroundColor: '#fff', margin: 16, borderRadius: 12, padding: 16, alignItems: 'center' },
  title:     { fontSize: 14, fontWeight: '600', color: Colors.primary, alignSelf: 'flex-start', marginBottom: 4 },
});
```

### 10.4 ReportHeader

```typescript
// src/components/reports/ReportHeader.tsx
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Colors } from '@/theme/colors';

interface Action {
  label:   string;
  icon:    string;
  color:   string;
  onPress: () => void;
}

interface Props {
  title:   string;
  onBack?: boolean;
  actions?: Action[];
}

export function ReportHeader({ title, onBack, actions = [] }: Props) {
  const navigation = useNavigation();

  return (
    <View style={styles.header}>
      {onBack && (
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.back}>
          <MaterialCommunityIcons name="arrow-left" size={24} color={Colors.primary} />
        </TouchableOpacity>
      )}
      <Text style={styles.title}>{title}</Text>
      <View style={styles.actions}>
        {actions.map((a, i) => (
          <TouchableOpacity key={i} onPress={a.onPress} style={styles.actionBtn}>
            <MaterialCommunityIcons name={a.icon as any} size={22} color={a.color} />
          </TouchableOpacity>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  header:    { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', paddingHorizontal: 16, paddingVertical: 12, borderBottomWidth: 1, borderColor: Colors.border },
  back:      { marginRight: 12 },
  title:     { flex: 1, fontSize: 17, fontWeight: '700', color: Colors.primary },
  actions:   { flexDirection: 'row', gap: 8 },
  actionBtn: { padding: 4 },
});
```

### 10.5 Export Hook (PDF download via Expo FileSystem + Sharing)

> **Important:** React Native does not have Node's `Buffer`. Use `FileSystem.downloadAsync` to
> download binary files directly — it handles Base64 encoding internally.

```typescript
// src/hooks/useExportReport.ts
import * as FileSystem from 'expo-file-system';
import * as Sharing from 'expo-sharing';
import { Alert } from 'react-native';
import { getAuthToken } from '@/lib/auth';   // your token helper
import { API_BASE_URL } from '@/lib/axios';  // e.g. 'https://api.kasabazaar.com/api/v1'

export function useExportReport(
  reportType: 'expenses' | 'incomes' | 'payroll' | 'shipments',
  params: Record<string, any>
) {
  async function download(format: 'pdf' | 'excel') {
    try {
      const ext      = format === 'pdf' ? 'pdf' : 'xlsx';
      const filename = `${reportType}-report-${params.start_date ?? 'all'}-to-${params.end_date ?? 'all'}.${ext}`;
      const fileUri  = FileSystem.documentDirectory + filename;

      // Build query string
      const query = new URLSearchParams({ ...params, format }).toString();
      const url   = `${API_BASE_URL}/reports/${reportType}/export?${query}`;
      const token = await getAuthToken();

      // FileSystem.downloadAsync handles binary — no Buffer needed
      const result = await FileSystem.downloadAsync(url, fileUri, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: format === 'pdf'
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        },
      });

      if (result.status !== 200) {
        throw new Error(`Server returned ${result.status}`);
      }

      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(result.uri, {
          mimeType: format === 'pdf'
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          dialogTitle: `Share ${reportType} report`,
          UTI: format === 'pdf' ? 'com.adobe.pdf' : 'org.openxmlformats.spreadsheetml.sheet',
        });
      }
    } catch (e) {
      Alert.alert('Export Failed', 'Could not download the report. Try again.');
      console.error('Export error:', e);
    }
  }

  return {
    exportPdf:   () => download('pdf'),
    exportExcel: () => download('excel'),
  };
}
```

> **Backend note:** Add export endpoints alongside the data endpoints:
> `GET /api/v1/reports/expenses/export?format=pdf&start_date=...&end_date=...`
> These return a binary file download (reusing the same DomPDF/Excel logic from the Filament pages).

---

## 11. File & Folder Structure

```
src/
├── screens/
│   └── reports/
│       ├── ReportsHomeScreen.tsx         ← tile grid linking to each report
│       ├── FinancialDashboardScreen.tsx
│       ├── ExpenseReportScreen.tsx
│       ├── IncomeReportScreen.tsx
│       ├── PayrollReportScreen.tsx
│       └── ShipmentReportsScreen.tsx
│
├── components/
│   └── reports/
│       ├── ReportHeader.tsx
│       ├── DateRangeFilter.tsx
│       ├── SummaryCards.tsx
│       ├── OverviewCard.tsx
│       ├── KPIGrid.tsx
│       ├── CategoryPieChart.tsx
│       ├── MonthlyTrendChart.tsx
│       ├── ContainerProfitScroll.tsx
│       ├── TopStatesList.tsx
│       ├── ExpenseRow.tsx
│       ├── IncomeRow.tsx
│       ├── PayrollRow.tsx
│       ├── ReportTypeSelector.tsx
│       ├── ShipmentReportFilters.tsx
│       └── ShipmentReportTable.tsx
│
├── hooks/
│   ├── useReports.ts
│   └── useExportReport.ts
│
├── types/
│   └── reports.ts
│
└── navigation/
    └── ReportsStack.tsx
```

---

## Quick Reference: Backend Endpoints Summary

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/reports/financial-dashboard` | Financial overview, KPIs, trends |
| GET | `/api/v1/reports/expenses` | Paginated expenses + summary |
| GET | `/api/v1/reports/expenses/export` | PDF / Excel download |
| GET | `/api/v1/reports/incomes` | Paginated incomes + summary |
| GET | `/api/v1/reports/incomes/export` | PDF / Excel download |
| GET | `/api/v1/reports/payroll` | Paginated payroll + summary |
| GET | `/api/v1/reports/payroll/export` | PDF / Excel download |
| GET | `/api/v1/reports/shipments` | Shipment report (type-based) |
| GET | `/api/v1/reports/shipments/export` | PDF / Excel download |

**Query params common to all:** `start_date`, `end_date`
**Expense/Income/Payroll pagination:** `page`, `per_page`
**Shipment-specific:** `report_type`, `year`, `container_sequence`, `client_id`
**Additional (ReportService):** `report_type=profit_loss_summary | client_growth | receivables_aging | container_detail`

---

## 12. Missing Component Implementations

### 12.1 KPIGrid

```typescript
// src/components/reports/KPIGrid.tsx
import { View, Text, StyleSheet } from 'react-native';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Colors } from '@/theme/colors';

interface Props { kpis: ManagementKPI }

const KPI_ITEMS = [
  { key: 'total_shipments',     label: 'Total Shipments', icon: 'package-variant',     color: Colors.info    },
  { key: 'delivered_shipments', label: 'Delivered',       icon: 'check-circle-outline', color: Colors.success },
  { key: 'pending_shipments',   label: 'Pending',         icon: 'clock-outline',        color: Colors.warning },
  { key: 'active_containers',   label: 'Active Containers',icon: 'train-car-container', color: Colors.primary },
] as const;

export function KPIGrid({ kpis }: Props) {
  return (
    <View style={styles.grid}>
      {KPI_ITEMS.map(item => (
        <View key={item.key} style={styles.card}>
          <MaterialCommunityIcons name={item.icon as any} size={28} color={item.color} />
          <Text style={[styles.value, { color: item.color }]}>
            {item.key === 'delivery_rate'
              ? `${kpis[item.key].toFixed(1)}%`
              : kpis[item.key].toLocaleString()}
          </Text>
          <Text style={styles.label}>{item.label}</Text>
        </View>
      ))}
      <View style={[styles.card, styles.rateCard]}>
        <MaterialCommunityIcons name="percent" size={28} color={Colors.accent} />
        <Text style={[styles.value, { color: Colors.accent }]}>
          {kpis.delivery_rate.toFixed(1)}%
        </Text>
        <Text style={styles.label}>Delivery Rate</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  grid:     { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: 16, gap: 8, marginBottom: 8 },
  card:     { width: '47%', backgroundColor: '#fff', borderRadius: 12, padding: 14, alignItems: 'center', elevation: 1 },
  rateCard: { width: '97%' },
  value:    { fontSize: 22, fontWeight: '800', marginTop: 6 },
  label:    { fontSize: 11, color: Colors.textSecond, marginTop: 4, textAlign: 'center' },
});
```

### 12.2 ContainerProfitScroll

```typescript
// src/components/reports/ContainerProfitScroll.tsx
import { ScrollView, View, Text, StyleSheet } from 'react-native';
import { Colors } from '@/theme/colors';
import { formatGHS } from '@/utils/currency';

interface Props { items: ContainerProfit[] }

export function ContainerProfitScroll({ items }: Props) {
  if (!items.length) return null;

  return (
    <View style={styles.wrapper}>
      <Text style={styles.title}>Container Profit</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.scroll}>
        {items.map(item => {
          const positive = item.profit_ghs >= 0;
          return (
            <View key={item.container_number} style={styles.card}>
              <Text style={styles.label}>{item.label}</Text>
              <Text style={styles.count}>{item.shipment_count} shipments</Text>
              <View style={styles.divider} />
              <Row label="Income"  value={formatGHS(item.income_ghs)}  color={Colors.success} />
              <Row label="Expense" value={formatGHS(item.expense_ghs)} color={Colors.danger}  />
              <View style={styles.divider} />
              <Text style={[styles.profit, { color: positive ? Colors.success : Colors.danger }]}>
                {positive ? '+' : ''}{formatGHS(item.profit_ghs)}
              </Text>
              <Text style={styles.profitLabel}>Net Profit</Text>
            </View>
          );
        })}
      </ScrollView>
    </View>
  );
}

function Row({ label, value, color }: { label: string; value: string; color: string }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={[styles.rowValue, { color }]}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper:    { marginHorizontal: 16, marginBottom: 16 },
  title:      { fontSize: 14, fontWeight: '600', color: Colors.primary, marginBottom: 8 },
  scroll:     { paddingRight: 8, gap: 8 },
  card:       { width: 160, backgroundColor: '#fff', borderRadius: 12, padding: 14, elevation: 1 },
  label:      { fontSize: 16, fontWeight: '800', color: Colors.primary },
  count:      { fontSize: 11, color: Colors.textSecond, marginTop: 2 },
  divider:    { height: 1, backgroundColor: Colors.border, marginVertical: 8 },
  row:        { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 4 },
  rowLabel:   { fontSize: 11, color: Colors.textSecond },
  rowValue:   { fontSize: 11, fontWeight: '600' },
  profit:     { fontSize: 15, fontWeight: '800', textAlign: 'center', marginTop: 4 },
  profitLabel:{ fontSize: 10, color: Colors.textSecond, textAlign: 'center' },
});
```

### 12.3 TopStatesList

```typescript
// src/components/reports/TopStatesList.tsx
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '@/theme/colors';
import { formatGHS } from '@/utils/currency';

interface Props { items: TopState[] }

export function TopStatesList({ items }: Props) {
  if (!items.length) return null;

  const max = Math.max(...items.map(i => i.count), 1);

  return (
    <View style={styles.wrapper}>
      <Text style={styles.title}>Top Delivery States</Text>
      {items.slice(0, 8).map((item, idx) => (
        <View key={item.state} style={styles.row}>
          <Text style={styles.rank}>#{idx + 1}</Text>
          <View style={styles.body}>
            <View style={styles.topLine}>
              <Text style={styles.state}>{item.state}</Text>
              <Text style={styles.count}>{item.count} shipments</Text>
            </View>
            <View style={styles.barBg}>
              <View style={[styles.barFill, { width: `${(item.count / max) * 100}%` }]} />
            </View>
            <Text style={styles.revenue}>{formatGHS(item.revenue_ghs)}</Text>
          </View>
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: { backgroundColor: '#fff', margin: 16, borderRadius: 12, padding: 16, elevation: 1 },
  title:   { fontSize: 14, fontWeight: '600', color: Colors.primary, marginBottom: 12 },
  row:     { flexDirection: 'row', alignItems: 'flex-start', marginBottom: 12 },
  rank:    { width: 28, fontSize: 12, fontWeight: '700', color: Colors.textSecond, paddingTop: 2 },
  body:    { flex: 1 },
  topLine: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 4 },
  state:   { fontSize: 13, fontWeight: '600', color: Colors.primary },
  count:   { fontSize: 12, color: Colors.textSecond },
  barBg:   { height: 6, backgroundColor: Colors.border, borderRadius: 3, marginBottom: 4 },
  barFill: { height: 6, backgroundColor: Colors.accent, borderRadius: 3 },
  revenue: { fontSize: 11, color: Colors.success, fontWeight: '600' },
});
```

### 12.4 ReportTypeSelector

```typescript
// src/components/reports/ReportTypeSelector.tsx
import { View, Text, TouchableOpacity, StyleSheet, ScrollView } from 'react-native';
import { Colors } from '@/theme/colors';

interface Option<T extends string> {
  value: T;
  label: string;
}

interface Props<T extends string> {
  options:  readonly Option<T>[];
  value:    T | null;
  onChange: (value: T) => void;
}

export function ReportTypeSelector<T extends string>({ options, value, onChange }: Props<T>) {
  return (
    <View style={styles.wrapper}>
      <Text style={styles.label}>Report Type</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
        {options.map(opt => {
          const active = opt.value === value;
          return (
            <TouchableOpacity
              key={opt.value}
              style={[styles.chip, active && styles.chipActive]}
              onPress={() => onChange(opt.value)}
            >
              <Text style={[styles.chipText, active && styles.chipTextActive]}>
                {opt.label}
              </Text>
            </TouchableOpacity>
          );
        })}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper:       { margin: 16 },
  label:         { fontSize: 12, color: Colors.textSecond, marginBottom: 8, fontWeight: '500' },
  row:           { gap: 8, paddingRight: 8 },
  chip:          { paddingHorizontal: 16, paddingVertical: 10, borderRadius: 20, borderWidth: 1.5, borderColor: Colors.border, backgroundColor: '#fff' },
  chipActive:    { backgroundColor: Colors.primary, borderColor: Colors.primary },
  chipText:      { fontSize: 13, fontWeight: '600', color: Colors.textSecond },
  chipTextActive:{ color: '#fff' },
});
```

### 12.5 ShipmentReportFilters

```typescript
// src/components/reports/ShipmentReportFilters.tsx
import { View, Text, StyleSheet } from 'react-native';
import { Picker } from '@react-native-picker/picker';
import { DateRangeFilter } from './DateRangeFilter';
import { Colors } from '@/theme/colors';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/axios';

interface Props {
  reportType: ShipmentReportType;
  params:     Partial<ShipmentReportParams>;
  onChange:   (updates: Partial<ShipmentReportParams>) => void;
}

export function ShipmentReportFilters({ reportType, params, onChange }: Props) {
  // Fetch clients for client_shipments type
  const { data: clients } = useQuery({
    queryKey: ['lookup', 'clients'],
    queryFn:  async () => {
      const { data } = await api.get('/lookup/clients');
      return data.data as { id: string; name: string }[];
    },
    enabled: reportType === 'client_shipments',
  });

  const years = Array.from({ length: 7 }, (_, i) => new Date().getFullYear() - i);

  return (
    <View style={styles.wrapper}>
      {/* Year picker — shown for by_container, by_year, profit_loss */}
      {['by_container', 'by_year', 'profit_loss'].includes(reportType) && (
        <View style={styles.field}>
          <Text style={styles.label}>Year</Text>
          <View style={styles.pickerWrap}>
            <Picker
              selectedValue={params.year ?? new Date().getFullYear()}
              onValueChange={v => onChange({ year: Number(v) })}
              style={styles.picker}
            >
              {years.map(y => <Picker.Item key={y} label={String(y)} value={y} />)}
            </Picker>
          </View>
        </View>
      )}

      {/* Container picker — by_container and profit_loss */}
      {['by_container', 'profit_loss'].includes(reportType) && (
        <ContainerPicker
          value={params.container_sequence ?? null}
          onChange={v => onChange({ container_sequence: v })}
        />
      )}

      {/* Client picker + date range — client_shipments */}
      {reportType === 'client_shipments' && (
        <>
          <View style={styles.field}>
            <Text style={styles.label}>Client</Text>
            <View style={styles.pickerWrap}>
              <Picker
                selectedValue={params.client_id ?? ''}
                onValueChange={v => onChange({ client_id: v as string })}
                style={styles.picker}
              >
                <Picker.Item label="Select a client..." value="" />
                {(clients ?? []).map(c => (
                  <Picker.Item key={c.id} label={c.name} value={c.id} />
                ))}
              </Picker>
            </View>
          </View>
          <DateRangeFilter
            startDate={params.start_date ?? ''}
            endDate={params.end_date ?? ''}
            onChange={(s, e) => onChange({ start_date: s, end_date: e })}
          />
        </>
      )}
    </View>
  );
}

function ContainerPicker({
  value, onChange,
}: { value: number | null; onChange: (v: number | null) => void }) {
  // GET /api/v1/shipment-containers returns { container_number, is_cleared, shipment_count }
  // No year filter supported — endpoint returns all containers
  const { data: containers } = useQuery({
    queryKey: ['shipment-containers'],
    queryFn:  async () => {
      const { data } = await api.get('/shipment-containers');
      return data.data as { container_number: number; is_cleared: boolean; shipment_count: number }[];
    },
  });

  return (
    <View style={styles.field}>
      <Text style={styles.label}>Container (optional)</Text>
      <View style={styles.pickerWrap}>
        <Picker
          selectedValue={value ?? ''}
          onValueChange={v => onChange(v !== '' ? Number(v) : null)}
          style={styles.picker}
        >
          <Picker.Item label="All Containers" value="" />
          {(containers ?? []).map(c => (
            <Picker.Item
              key={c.container_number}
              label={`CON${c.container_number} (${c.shipment_count} shipments)`}
              value={c.container_number}
            />
          ))}
        </Picker>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper:    { marginHorizontal: 16 },
  field:      { marginBottom: 12 },
  label:      { fontSize: 12, color: Colors.textSecond, marginBottom: 4, fontWeight: '500' },
  pickerWrap: { backgroundColor: '#fff', borderRadius: 10, borderWidth: 1, borderColor: Colors.border, overflow: 'hidden' },
  picker:     { height: 48, color: Colors.primary },
});
```

### 12.6 ReportsHomeScreen

```typescript
// src/screens/reports/ReportsHomeScreen.tsx
import { View, Text, TouchableOpacity, ScrollView, StyleSheet } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { ReportsStackParamList } from '@/navigation/ReportsStack';
import { Colors } from '@/theme/colors';

type Nav = NativeStackNavigationProp<ReportsStackParamList>;

const REPORT_TILES = [
  {
    screen:      'FinancialDashboard' as const,
    title:       'Financial Dashboard',
    description: 'Income, expenses, KPIs & container profit',
    icon:        'view-dashboard-outline',
    color:       Colors.primary,
    badge:       'Management',
  },
  {
    screen:      'ExpenseReport' as const,
    title:       'Expense Report',
    description: 'All expenses by category, stage & branch',
    icon:        'cash-minus',
    color:       Colors.danger,
  },
  {
    screen:      'IncomeReport' as const,
    title:       'Income Report',
    description: 'Received income by category & payment method',
    icon:        'cash-plus',
    color:       Colors.success,
  },
  {
    screen:      'PayrollReport' as const,
    title:       'Payroll Report',
    description: 'Staff salaries, deductions & bonuses',
    icon:        'account-cash-outline',
    color:       Colors.info,
  },
  {
    screen:      'ShipmentReports' as const,
    title:       'Shipment Reports',
    description: 'By container, year, P&L or client history',
    icon:        'chart-bar',
    color:       Colors.accent,
  },
] as const;

export function ReportsHomeScreen() {
  const navigation = useNavigation<Nav>();

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Reports</Text>
        <Text style={styles.headerSub}>Tap a report to view and export</Text>
      </View>
      <ScrollView contentContainerStyle={styles.list}>
        {REPORT_TILES.map(tile => (
          <TouchableOpacity
            key={tile.screen}
            style={styles.tile}
            onPress={() => navigation.navigate(tile.screen)}
            activeOpacity={0.7}
          >
            <View style={[styles.iconWrap, { backgroundColor: tile.color + '15' }]}>
              <MaterialCommunityIcons name={tile.icon as any} size={28} color={tile.color} />
            </View>
            <View style={styles.tileBody}>
              <View style={styles.tileTitleRow}>
                <Text style={styles.tileTitle}>{tile.title}</Text>
                {'badge' in tile && (
                  <View style={styles.badge}>
                    <Text style={styles.badgeText}>{tile.badge}</Text>
                  </View>
                )}
              </View>
              <Text style={styles.tileDesc}>{tile.description}</Text>
            </View>
            <MaterialCommunityIcons name="chevron-right" size={20} color={Colors.textSecond} />
          </TouchableOpacity>
        ))}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container:    { flex: 1, backgroundColor: Colors.bgLight },
  header:       { backgroundColor: Colors.primary, padding: 24, paddingTop: 48 },
  headerTitle:  { color: '#fff', fontSize: 24, fontWeight: '800' },
  headerSub:    { color: 'rgba(255,255,255,0.7)', fontSize: 13, marginTop: 4 },
  list:         { padding: 16, gap: 12 },
  tile:         { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 14, padding: 16, elevation: 1, gap: 14 },
  iconWrap:     { width: 52, height: 52, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  tileBody:     { flex: 1 },
  tileTitleRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  tileTitle:    { fontSize: 15, fontWeight: '700', color: Colors.primary },
  tileDesc:     { fontSize: 12, color: Colors.textSecond, marginTop: 3 },
  badge:        { backgroundColor: Colors.accent + '25', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 10 },
  badgeText:    { fontSize: 10, fontWeight: '700', color: Colors.accent },
});
```

### 12.7 IncomeReportScreen (full component)

```typescript
// src/screens/reports/IncomeReportScreen.tsx
import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { useIncomeReport } from '@/hooks/useReports';
import { DateRangeFilter } from '@/components/reports/DateRangeFilter';
import { SummaryCards } from '@/components/reports/SummaryCards';
import { CategoryPieChart } from '@/components/reports/CategoryPieChart';
import { IncomeRow } from '@/components/reports/IncomeRow';
import { ReportHeader } from '@/components/reports/ReportHeader';
import { useExportReport } from '@/hooks/useExportReport';
import { VictoryBar, VictoryChart, VictoryAxis } from 'victory-native';
import { Colors } from '@/theme/colors';
import dayjs from 'dayjs';

export function IncomeReportScreen() {
  const [filters, setFilters] = useState({
    start_date: dayjs().startOf('month').format('YYYY-MM-DD'),
    end_date:   dayjs().format('YYYY-MM-DD'),
    page:       1,
    per_page:   50,
  });

  const { data, isLoading } = useIncomeReport(filters);
  const { exportPdf, exportExcel } = useExportReport('incomes', filters);

  const summary = data?.summary;
  const items   = data?.data ?? [];

  return (
    <View style={styles.container}>
      <ReportHeader
        title="Income Report"
        onBack
        actions={[
          { label: 'PDF',   icon: 'file-pdf-box',   color: Colors.danger,  onPress: exportPdf   },
          { label: 'Excel', icon: 'microsoft-excel', color: Colors.success, onPress: exportExcel },
        ]}
      />
      <FlashList
        data={items}
        estimatedItemSize={70}
        keyExtractor={item => item.id}
        renderItem={({ item }) => <IncomeRow item={item} />}
        ListHeaderComponent={
          <>
            <DateRangeFilter
              startDate={filters.start_date}
              endDate={filters.end_date}
              onChange={(s, e) => setFilters(f => ({ ...f, start_date: s, end_date: e, page: 1 }))}
            />
            {summary && (
              <>
                <SummaryCards
                  cards={[
                    { label: 'This Period', value: summary.this_period_ghs, currency: 'GHS', color: Colors.success },
                    { label: 'Last Period', value: summary.last_period_ghs, currency: 'GHS', color: Colors.textSecond },
                    { label: 'This (USD)',  value: summary.this_period_usd, currency: 'USD', color: Colors.success },
                    { label: 'Growth',      value: summary.growth_percent,  suffix: '%',     color: summary.growth_percent >= 0 ? Colors.success : Colors.danger },
                  ]}
                />
                <CategoryPieChart
                  title="By Category"
                  data={summary.by_category.map(c => ({ label: c.category, value: c.total_ghs }))}
                />
                {/* Payment method bar chart */}
                {summary.by_method.length > 0 && (
                  <View style={styles.chart}>
                    <Text style={styles.chartTitle}>By Payment Method</Text>
                    <VictoryChart height={180} domainPadding={{ x: 30 }}>
                      <VictoryAxis style={{ tickLabels: { fontSize: 10, angle: -20, fill: Colors.textSecond } }} />
                      <VictoryAxis dependentAxis style={{ tickLabels: { fontSize: 10, fill: Colors.textSecond } }} />
                      <VictoryBar
                        data={summary.by_method.map((m, i) => ({ x: m.method.replace('_', ' '), y: m.total_ghs / 1000 }))}
                        style={{ data: { fill: Colors.success } }}
                        cornerRadius={{ top: 4 }}
                        barWidth={28}
                      />
                    </VictoryChart>
                  </View>
                )}
              </>
            )}
            <Text style={styles.sectionTitle}>
              Transactions ({summary?.total_count ?? 0})
            </Text>
          </>
        }
        ListFooterComponent={
          data?.meta && data.meta.current_page < data.meta.last_page ? (
            <TouchableOpacity
              style={styles.loadMore}
              onPress={() => setFilters(f => ({ ...f, page: f.page + 1 }))}
            >
              <Text style={styles.loadMoreText}>Load More</Text>
            </TouchableOpacity>
          ) : null
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container:    { flex: 1, backgroundColor: Colors.bgLight },
  chart:        { backgroundColor: '#fff', margin: 16, borderRadius: 12, padding: 16 },
  chartTitle:   { fontSize: 14, fontWeight: '600', color: Colors.primary, marginBottom: 4 },
  sectionTitle: { fontSize: 15, fontWeight: '600', color: Colors.primary, paddingHorizontal: 16, paddingVertical: 12 },
  loadMore:     { margin: 16, padding: 14, backgroundColor: Colors.primary, borderRadius: 8, alignItems: 'center' },
  loadMoreText: { color: '#fff', fontWeight: '600' },
});
```

### 12.8 PayrollReportScreen (full component)

```typescript
// src/screens/reports/PayrollReportScreen.tsx
import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { usePayrollReport } from '@/hooks/useReports';
import { DateRangeFilter } from '@/components/reports/DateRangeFilter';
import { SummaryCards } from '@/components/reports/SummaryCards';
import { PayrollRow } from '@/components/reports/PayrollRow';
import { ReportHeader } from '@/components/reports/ReportHeader';
import { useExportReport } from '@/hooks/useExportReport';
import { VictoryBar, VictoryChart, VictoryAxis } from 'victory-native';
import { Colors } from '@/theme/colors';
import { formatGHS } from '@/utils/currency';
import dayjs from 'dayjs';

export function PayrollReportScreen() {
  const [filters, setFilters] = useState({
    start_date: dayjs().startOf('month').format('YYYY-MM-DD'),
    end_date:   dayjs().format('YYYY-MM-DD'),
    page:       1,
    per_page:   50,
  });

  const { data } = usePayrollReport(filters);
  const { exportPdf, exportExcel } = useExportReport('payroll', filters);

  const summary = data?.summary;
  const items   = data?.data ?? [];

  return (
    <View style={styles.container}>
      <ReportHeader
        title="Payroll Report"
        onBack
        actions={[
          { label: 'PDF',   icon: 'file-pdf-box',   color: Colors.danger,  onPress: exportPdf   },
          { label: 'Excel', icon: 'microsoft-excel', color: Colors.success, onPress: exportExcel },
        ]}
      />
      <FlashList
        data={items}
        estimatedItemSize={80}
        keyExtractor={item => item.id}
        renderItem={({ item }) => <PayrollRow item={item} />}
        ListHeaderComponent={
          <>
            <DateRangeFilter
              startDate={filters.start_date}
              endDate={filters.end_date}
              onChange={(s, e) => setFilters(f => ({ ...f, start_date: s, end_date: e, page: 1 }))}
            />
            {summary && (
              <>
                <SummaryCards
                  cards={[
                    { label: 'Total Net Pay',   value: summary.this_period,      currency: 'GHS', color: Colors.primary  },
                    { label: 'Employees',       value: summary.total_employees,                   color: Colors.info     },
                    { label: 'Avg Salary',      value: summary.avg_salary,        currency: 'GHS', color: Colors.textSecond },
                    { label: 'Total Deductions',value: summary.total_deductions,  currency: 'GHS', color: Colors.danger   },
                    { label: 'Total Bonuses',   value: summary.total_bonuses,     currency: 'GHS', color: Colors.success  },
                    { label: 'Growth',          value: summary.growth_percent,    suffix: '%',     color: summary.growth_percent >= 0 ? Colors.warning : Colors.success },
                  ]}
                />
                {/* By-status bar chart */}
                {summary.by_status.length > 0 && (
                  <View style={styles.chart}>
                    <Text style={styles.chartTitle}>By Status</Text>
                    <VictoryChart height={180} domainPadding={{ x: 40 }}>
                      <VictoryAxis style={{ tickLabels: { fontSize: 11, fill: Colors.textSecond } }} />
                      <VictoryAxis dependentAxis style={{ tickLabels: { fontSize: 10, fill: Colors.textSecond } }} />
                      <VictoryBar
                        data={summary.by_status.map(s => ({ x: s.status, y: s.total / 1000 }))}
                        style={{ data: { fill: Colors.primary } }}
                        cornerRadius={{ top: 4 }}
                        barWidth={36}
                        labels={({ datum }) => formatGHS(datum.y * 1000)}
                      />
                    </VictoryChart>
                  </View>
                )}
              </>
            )}
            <Text style={styles.sectionTitle}>
              Entries ({summary?.total_count ?? 0})
            </Text>
          </>
        }
        ListFooterComponent={
          data?.meta && data.meta.current_page < data.meta.last_page ? (
            <TouchableOpacity
              style={styles.loadMore}
              onPress={() => setFilters(f => ({ ...f, page: f.page + 1 }))}
            >
              <Text style={styles.loadMoreText}>Load More</Text>
            </TouchableOpacity>
          ) : null
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container:    { flex: 1, backgroundColor: Colors.bgLight },
  chart:        { backgroundColor: '#fff', margin: 16, borderRadius: 12, padding: 16 },
  chartTitle:   { fontSize: 14, fontWeight: '600', color: Colors.primary, marginBottom: 4 },
  sectionTitle: { fontSize: 15, fontWeight: '600', color: Colors.primary, paddingHorizontal: 16, paddingVertical: 12 },
  loadMore:     { margin: 16, padding: 14, backgroundColor: Colors.primary, borderRadius: 8, alignItems: 'center' },
  loadMoreText: { color: '#fff', fontWeight: '600' },
});
```

---

## 13. Currency Utility

```typescript
// src/utils/currency.ts

export function formatGHS(value: number | null | undefined, decimals = 2): string {
  if (value == null) return 'GH₵ 0.00';
  return `GH₵ ${Number(value).toLocaleString('en-GH', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  })}`;
}

export function formatUSD(value: number | null | undefined, decimals = 2): string {
  if (value == null) return '$ 0.00';
  return `$ ${Number(value).toLocaleString('en-US', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  })}`;
}

export function formatPercent(value: number | null | undefined, decimals = 1): string {
  if (value == null) return '0%';
  const sign = value > 0 ? '+' : '';
  return `${sign}${Number(value).toFixed(decimals)}%`;
}

export function formatNumber(value: number | null | undefined): string {
  if (value == null) return '0';
  return Number(value).toLocaleString();
}
```

---

## 14. Additional Reports (ReportService)

The `ReportService` exposes four additional methods not covered by the Filament pages. These should be surfaced as additional report options in the mobile app.

### 14.1 New report types to add

Extend `ShipmentReportType` in `src/types/reports.ts`:

```typescript
export type ExtendedReportType =
  | ShipmentReportType
  | 'profit_loss_summary'   // full P&L (date-based)
  | 'client_growth'         // new clients per month
  | 'receivables_aging'     // outstanding balances
  | 'container_detail';     // single shipment deep-dive
```

### 14.2 Profit/Loss Summary Report

**Endpoint:**
```
GET /api/v1/reports/profit-loss-summary
  ?start_date=2026-01-01
  &end_date=2026-04-30
  &branch_id=uuid   (optional)
```

**Response shape** (maps directly from `ReportService::profitLossReport`):
```json
{
  "data": {
    "period": { "start": "2026-01-01", "end": "2026-04-30" },
    "revenue": {
      "shipment_revenue": 0.00,
      "external_income":  0.00,
      "total_revenue":    0.00,
      "collected":        0.00,
      "outstanding":      0.00
    },
    "expenses": {
      "shipment_expenses": 0.00,
      "payroll":           0.00,
      "total":             0.00
    },
    "profit_loss": {
      "gross_profit":      0.00,
      "net_profit":        0.00,
      "margin_percentage": 0.00
    }
  }
}
```

**TypeScript interface:**
```typescript
export interface ProfitLossSummary {
  period:      { start: string; end: string };
  revenue: {
    shipment_revenue: number;
    external_income:  number;
    total_revenue:    number;
    collected:        number;
    outstanding:      number;
  };
  expenses: {
    shipment_expenses: number;
    payroll:           number;
    total:             number;
  };
  profit_loss: {
    gross_profit:      number;
    net_profit:        number;
    margin_percentage: number;
  };
}
```

**Hook:**
```typescript
export function useProfitLossSummary(filters: DateRangeFilter & { branch_id?: string }) {
  return useQuery({
    queryKey: ['reports', 'profit-loss-summary', filters],
    queryFn: async (): Promise<ProfitLossSummary> => {
      const { data } = await api.get('/reports/profit-loss-summary', { params: filters });
      return data.data;
    },
    staleTime: 5 * 60 * 1000,
  });
}
```

### 14.3 Client Growth Report

**Endpoint:**
```
GET /api/v1/reports/client-growth?year=2026
```

**Response shape:**
```json
{
  "data": {
    "year": 2026,
    "total_new_clients": 0,
    "previous_year_total": 0,
    "growth_rate": 0.00,
    "monthly_breakdown": {
      "1": { "month": "January", "count": 0 },
      "2": { "month": "February", "count": 0 }
    }
  }
}
```

**TypeScript interface:**
```typescript
export interface ClientGrowthReport {
  year:                 number;
  total_new_clients:    number;
  previous_year_total:  number;
  growth_rate:          number;
  monthly_breakdown:    Record<string, { month: string; count: number }>;
}
```

**Hook:**
```typescript
export function useClientGrowthReport(year: number) {
  return useQuery({
    queryKey: ['reports', 'client-growth', year],
    queryFn: async (): Promise<ClientGrowthReport> => {
      const { data } = await api.get('/reports/client-growth', { params: { year } });
      return data.data;
    },
    staleTime: 10 * 60 * 1000,
  });
}
```

### 14.4 Receivables Aging Report

**Endpoint:**
```
GET /api/v1/reports/receivables-aging?branch_id=uuid (optional)
```

**Response shape:**
```json
{
  "data": {
    "total_outstanding": 0.00,
    "total_shipments":   0,
    "aging": {
      "0-30":  { "count": 0, "amount": 0.00 },
      "31-60": { "count": 0, "amount": 0.00 },
      "61-90": { "count": 0, "amount": 0.00 },
      "90+":   { "count": 0, "amount": 0.00 }
    }
  }
}
```

**TypeScript interface:**
```typescript
export interface AgingBucket { count: number; amount: number }
export interface ReceivablesAgingReport {
  total_outstanding: number;
  total_shipments:   number;
  aging: {
    '0-30':  AgingBucket;
    '31-60': AgingBucket;
    '61-90': AgingBucket;
    '90+':   AgingBucket;
  };
}
```

**Aging Report Screen section layout:**
```
┌─────────────────────────────────────────┐
│  ← Receivables Aging                    │
├─────────────────────────────────────────┤
│  Total Outstanding  GH₵ xxx,xxx.xx      │
│  Total Shipments    xxx                 │
├─────────────────────────────────────────┤
│  Aging Buckets (4 horizontal cards)     │
│   0-30 days  | 31-60 days              │
│   61-90 days | 90+ days                │
│  each shows: count + amount             │
├─────────────────────────────────────────┤
│  StackedBar showing proportion          │
└─────────────────────────────────────────┘
```

**Hook:**
```typescript
export function useReceivablesAging(branchId?: string) {
  return useQuery({
    queryKey: ['reports', 'receivables-aging', branchId],
    queryFn: async (): Promise<ReceivablesAgingReport> => {
      const { data } = await api.get('/reports/receivables-aging', {
        params: branchId ? { branch_id: branchId } : {},
      });
      return data.data;
    },
    staleTime: 5 * 60 * 1000,
  });
}
```

### 14.5 Container Shipment Detail

**Endpoint:**
```
GET /api/v1/reports/container-detail/:reference
  e.g. /api/v1/reports/container-detail/RDD-01-26-001
```

**Response shape** (maps from `ReportService::containerShipmentDetail`):
```json
{
  "data": {
    "reference": "RDD-01-26-001",
    "container_info": {},
    "client": { "name": "JOHN DOE", "phone": "+233..." },
    "receivers": [
      {
        "name": "SELF",
        "contact": "+233...",
        "location": "ACCRA",
        "items": [...]
      }
    ],
    "financials": {
      "total": 0.00,
      "paid": 0.00,
      "balance": 0.00,
      "expenses_usd": 0.00,
      "expenses_ghs": 0.00,
      "net_profit": 0.00
    },
    "expenses": [...],
    "payments": [...]
  }
}
```

**Hook:**
```typescript
export function useContainerDetail(reference: string, enabled = true) {
  return useQuery({
    queryKey: ['reports', 'container-detail', reference],
    queryFn: async () => {
      const { data } = await api.get(`/reports/container-detail/${reference}`);
      return data.data;
    },
    enabled: enabled && !!reference,
    staleTime: 5 * 60 * 1000,
  });
}
```

Add these 4 additional reports to `ReportsHomeScreen` tiles and `ReportsStack`:

```typescript
// Additional stack screens
{ name: 'ProfitLossSummary',   component: ProfitLossSummaryScreen  },
{ name: 'ClientGrowth',        component: ClientGrowthScreen        },
{ name: 'ReceivablesAging',    component: ReceivablesAgingScreen    },
{ name: 'ContainerDetail',     component: ContainerDetailScreen, params: { reference: string } },
```

---

## 15. Backend: FinancialReportController (PHP)

Create a dedicated controller at `app/Http/Controllers/Api/V1/FinancialReportController.php`.

```php
<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Shipment;
use App\Enums\IncomeStatus;
use App\Models\PayrollEntry;
use App\Service\ReportService;
use App\Exports\ExpensesExport;
use App\Exports\IncomesExport;
use App\Exports\PayrollExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportController extends BaseApiController
{
    public function __construct(protected ReportService $reportService) {}

    // ── Financial Dashboard ────────────────────────────────────────────────────

    public function financialDashboard(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $start  = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end    = $request->input('end_date',   now()->format('Y-m-d'));
        $conNum = $request->input('container_number'); // integer or null

        // ── Income query ─────────────────────────────────────────────────────
        // Income has no container_number column — filter via shipment relationship
        $incomeBase = Income::whereBetween('income_date', [$start, $end])
                            ->where('status', IncomeStatus::Received);
        if ($conNum) {
            $incomeBase->whereHas('shipment', fn($q) => $q->where('container_number', $conNum));
        }

        // ── Expense query ────────────────────────────────────────────────────
        // Expense has no container_number column — filter via shipment relationship
        $expenseBase = Expense::whereBetween('expense_date', [$start, $end]);
        if ($conNum) {
            $expenseBase->whereHas('shipment', fn($q) => $q->where('container_number', $conNum));
        }

        // ── Shipment query ───────────────────────────────────────────────────
        $shipmentBase = Shipment::whereBetween('created_at', [$start, $end]);
        if ($conNum) {
            $shipmentBase->where('container_number', $conNum);
        }

        $totalIncomeGhs  = (clone $incomeBase)->sum('amount_ghs');
        $totalIncomeUsd  = (clone $incomeBase)->sum('amount_usd');
        $totalExpenseGhs = (clone $expenseBase)->sum('amount_ghs');
        $totalExpenseUsd = (clone $expenseBase)->sum('amount_usd');
        $totalPayroll    = PayrollEntry::whereHas('payrollPeriod', fn($q) =>
            $q->whereBetween('pay_date', [$start, $end]))->sum('net_salary');

        $shipments   = (clone $shipmentBase)->get();
        $totalShips  = $shipments->count();
        $delivered   = $shipments->filter(fn($s) => $s->status?->value === 'delivered')->count();
        $pending     = $shipments->filter(fn($s) => in_array($s->status?->value, ['pending', 'processing']))->count();
        $activeConts = Shipment::whereNull('delivered_at')
                               ->whereNotNull('container_number')
                               ->selectRaw('COUNT(DISTINCT container_number) as cnt')
                               ->value('cnt') ?? 0;

        // ── Container profit breakdown ────────────────────────────────────────
        // Join expenses through their shipment_id → shipments.container_number
        $containerProfit = Shipment::selectRaw('container_number, COUNT(*) as shipment_count, SUM(total) as income_ghs')
            ->whereNotNull('container_number')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('container_number')
            ->orderBy('container_number')
            ->get()
            ->map(function ($row) use ($start, $end) {
                // Expenses linked via shipment — join through shipments table
                $expGhs = Expense::whereHas('shipment', fn($q) => $q->where('container_number', $row->container_number))
                    ->whereBetween('expense_date', [$start, $end])
                    ->sum('amount_ghs');
                return [
                    'container_number' => $row->container_number,
                    'label'            => 'CON' . $row->container_number,
                    'income_ghs'       => (float) $row->income_ghs,
                    'expense_ghs'      => (float) $expGhs,
                    'profit_ghs'       => (float) $row->income_ghs - (float) $expGhs,
                    'shipment_count'   => (int) $row->shipment_count,
                ];
            })
            ->values();

        // ── Top delivery states ───────────────────────────────────────────────
        // receivers table column is 'state_region' (not 'state') — confirmed from migration
        $topStates = Shipment::join('receivers', 'shipments.id', '=', 'receivers.shipment_id')
            ->selectRaw('receivers.state_region, COUNT(*) as `count`, SUM(shipments.total) as revenue_ghs')
            ->whereNotNull('receivers.state_region')
            ->whereBetween('shipments.created_at', [$start, $end])
            ->groupBy('receivers.state_region')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'state'       => $r->state_region,
                'count'       => (int) $r->count,
                'revenue_ghs' => (float) $r->revenue_ghs,
            ])
            ->values();

        // Monthly trend (12 months ending at end date)
        $monthlyTrend = collect(range(11, 0))->map(function ($i) use ($end) {
            $month     = Carbon::parse($end)->subMonths($i);
            $monthStart = $month->copy()->startOfMonth()->format('Y-m-d');
            $monthEnd   = $month->copy()->endOfMonth()->format('Y-m-d');
            return [
                'month'       => $month->format('M'),
                'income_ghs'  => (float) Income::whereBetween('income_date',  [$monthStart, $monthEnd])
                                    ->where('status', IncomeStatus::Received)->sum('amount_ghs'),
                'expense_ghs' => (float) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount_ghs'),
            ];
        })->values();

        return $this->success([
            'overview' => [
                'total_income_ghs'  => (float) $totalIncomeGhs,
                'total_expense_ghs' => (float) $totalExpenseGhs,
                'total_payroll_ghs' => (float) $totalPayroll,
                'net_profit_ghs'    => (float) $totalIncomeGhs - $totalExpenseGhs - $totalPayroll,
                'total_income_usd'  => (float) $totalIncomeUsd,
                'total_expense_usd' => (float) $totalExpenseUsd,
                'net_profit_usd'    => (float) $totalIncomeUsd - $totalExpenseUsd,
            ],
            'kpis' => [
                'total_shipments'     => $totalShips,
                'delivered_shipments' => $delivered,
                'pending_shipments'   => $pending,
                'active_containers'   => $activeConts,
                'delivery_rate'       => $totalShips > 0 ? round(($delivered / $totalShips) * 100, 1) : 0,
            ],
            'container_profit' => $containerProfit,
            'top_states'       => $topStates,
            'monthly_trend'    => $monthlyTrend,
        ]);
    }

    // ── Expenses ───────────────────────────────────────────────────────────────

    public function expenses(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $start   = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end     = $request->input('end_date',   now()->format('Y-m-d'));
        $perPage = (int) $request->input('per_page', 50);

        $paginated = Expense::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->whereBetween('expense_date', [$start, $end])
            ->orderBy('expense_date', 'desc')
            ->paginate($perPage);

        // Summary
        $thisTotal    = Expense::whereBetween('expense_date', [$start, $end])->sum('amount_ghs');
        $thisTotalUsd = Expense::whereBetween('expense_date', [$start, $end])->sum('amount_usd');

        $days         = Carbon::parse($start)->diffInDays(Carbon::parse($end));
        $prevEnd      = Carbon::parse($start)->subDay()->format('Y-m-d');
        $prevStart    = Carbon::parse($prevEnd)->subDays($days)->format('Y-m-d');
        $lastTotal    = Expense::whereBetween('expense_date', [$prevStart, $prevEnd])->sum('amount_ghs');
        $lastTotalUsd = Expense::whereBetween('expense_date', [$prevStart, $prevEnd])->sum('amount_usd');
        $growth       = $lastTotal > 0 ? round((($thisTotal - $lastTotal) / $lastTotal) * 100, 2) : 0;

        $byCategory = Expense::with('category')
            ->whereBetween('expense_date', [$start, $end])
            ->get()
            ->groupBy(fn($e) => $e->category?->name ?? 'Uncategorized')
            ->map(fn($g) => ['count' => $g->count(), 'total_usd' => $g->sum('amount_usd'), 'total_ghs' => $g->sum('amount_ghs')])
            ->map(fn($v, $k) => array_merge(['category' => $k], $v))
            ->sortByDesc('total_ghs')
            ->values();

        $byStage = Expense::whereBetween('expense_date', [$start, $end])
            ->get()
            ->groupBy(fn($e) => $e->expense_stage?->value ?? 'N/A')
            ->map(fn($g) => ['count' => $g->count(), 'total_usd' => $g->sum('amount_usd'), 'total_ghs' => $g->sum('amount_ghs')])
            ->map(fn($v, $k) => array_merge(['stage' => $k], $v))
            ->values();

        $items = $paginated->getCollection()->map(fn($e) => [
            'id'            => $e->id,
            'reference'     => $e->reference,
            'date'          => $e->expense_date?->format('Y-m-d'),
            'category'      => $e->category?->name ?? 'Uncategorized',
            'description'   => $e->description,
            'amount_usd'    => (float) $e->amount_usd,
            'amount_ghs'    => (float) $e->amount_ghs,
            'exchange_rate' => (float) $e->exchange_rate,
            'branch'        => $e->branch?->name ?? 'N/A',
            'shipment_ref'  => $e->shipment?->shipping_reference ?? 'General',
            'recorded_by'   => $e->recordedBy?->name ?? 'System',
            'expense_stage' => $e->expense_stage?->value ?? 'N/A',
        ]);

        return response()->json([
            'success' => true,
            'summary' => [
                'this_period_ghs' => (float) $thisTotal,
                'last_period_ghs' => (float) $lastTotal,
                'this_period_usd' => (float) $thisTotalUsd,
                'last_period_usd' => (float) $lastTotalUsd,
                'growth_percent'  => $growth,
                'total_count'     => $paginated->total(),
                'by_category'     => $byCategory,
                'by_stage'        => $byStage,
                'start_date'      => $start,
                'end_date'        => $end,
            ],
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function exportExpenses(Request $request)
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $start  = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end    = $request->input('end_date',   now()->format('Y-m-d'));
        $format = $request->input('format', 'pdf');
        $file   = "expense-report-{$start}-to-{$end}";

        if ($format === 'excel') {
            return Excel::download(new ExpensesExport($start, $end), "{$file}.xlsx");
        }

        $expenses = Expense::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->whereBetween('expense_date', [$start, $end])->get();

        $pdf = Pdf::loadView('reports.expense-pdf', compact('expenses'));
        return response()->streamDownload(fn() => print($pdf->output()), "{$file}.pdf");
    }

    // ── Incomes ────────────────────────────────────────────────────────────────

    public function incomes(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $start   = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end     = $request->input('end_date',   now()->format('Y-m-d'));
        $perPage = (int) $request->input('per_page', 50);

        $paginated = Income::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->whereBetween('income_date', [$start, $end])
            ->orderBy('income_date', 'desc')
            ->paginate($perPage);

        $q = fn($s, $e) => Income::whereBetween('income_date', [$s, $e])->where('status', IncomeStatus::Received);

        $thisGhs  = $q($start, $end)->sum('amount_ghs');
        $thisUsd  = $q($start, $end)->sum('amount_usd');
        $days     = Carbon::parse($start)->diffInDays(Carbon::parse($end));
        $prevEnd  = Carbon::parse($start)->subDay()->format('Y-m-d');
        $prevStart= Carbon::parse($prevEnd)->subDays($days)->format('Y-m-d');
        $lastGhs  = $q($prevStart, $prevEnd)->sum('amount_ghs');
        $lastUsd  = $q($prevStart, $prevEnd)->sum('amount_usd');
        $growth   = $lastGhs > 0 ? round((($thisGhs - $lastGhs) / $lastGhs) * 100, 2) : 0;

        $byCategory = Income::with('category')->where('status', IncomeStatus::Received)
            ->whereBetween('income_date', [$start, $end])->get()
            ->groupBy(fn($i) => $i->category?->name ?? 'Uncategorized')
            ->map(fn($g) => ['count' => $g->count(), 'total_usd' => $g->sum('amount_usd'), 'total_ghs' => $g->sum('amount_ghs')])
            ->map(fn($v, $k) => array_merge(['category' => $k], $v))
            ->sortByDesc('total_ghs')->values();

        $byMethod = Income::where('status', IncomeStatus::Received)
            ->whereBetween('income_date', [$start, $end])->get()
            ->groupBy(fn($i) => $i->payment_method?->value ?? 'N/A')
            ->map(fn($g) => ['count' => $g->count(), 'total_usd' => $g->sum('amount_usd'), 'total_ghs' => $g->sum('amount_ghs')])
            ->map(fn($v, $k) => array_merge(['method' => $k], $v))->values();

        $items = $paginated->getCollection()->map(fn($i) => [
            'id'             => $i->id,
            'reference'      => $i->reference,
            'date'           => $i->income_date?->format('Y-m-d'),
            'category'       => $i->category?->name ?? 'Uncategorized',
            'description'    => $i->description,
            'amount_usd'     => (float) $i->amount_usd,
            'amount_ghs'     => (float) $i->amount_ghs,
            'exchange_rate'  => (float) $i->exchange_rate,
            'branch'         => $i->branch?->name ?? 'N/A',
            'shipment_ref'   => $i->shipment?->shipping_reference ?? 'External',
            'recorded_by'    => $i->recordedBy?->name ?? 'System',
            'status'         => $i->status?->value ?? 'N/A',
            'payment_method' => $i->payment_method?->value ?? 'N/A',
        ]);

        return response()->json([
            'success' => true,
            'summary' => [
                'this_period_ghs' => (float) $thisGhs,
                'last_period_ghs' => (float) $lastGhs,
                'this_period_usd' => (float) $thisUsd,
                'last_period_usd' => (float) $lastUsd,
                'growth_percent'  => $growth,
                'total_count'     => $paginated->total(),
                'by_category'     => $byCategory,
                'by_method'       => $byMethod,
                'start_date'      => $start,
                'end_date'        => $end,
            ],
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function exportIncomes(Request $request)
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $start  = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end    = $request->input('end_date',   now()->format('Y-m-d'));
        $format = $request->input('format', 'pdf');
        $file   = "income-report-{$start}-to-{$end}";

        if ($format === 'excel') {
            return Excel::download(new IncomesExport($start, $end), "{$file}.xlsx");
        }

        $incomes = Income::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->whereBetween('income_date', [$start, $end])->get();

        $pdf = Pdf::loadView('reports.income-pdf', compact('incomes'));
        return response()->streamDownload(fn() => print($pdf->output()), "{$file}.pdf");
    }

    // ── Payroll ────────────────────────────────────────────────────────────────

    public function payroll(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $start   = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end     = $request->input('end_date',   now()->format('Y-m-d'));
        $perPage = (int) $request->input('per_page', 50);

        $inPeriod = fn($q) => $q->whereHas('payrollPeriod', fn($p) => $p->whereBetween('pay_date', [$start, $end]));

        $paginated = PayrollEntry::with(['payrollPeriod', 'employee'])
            ->whereHas('payrollPeriod', fn($p) => $p->whereBetween('pay_date', [$start, $end]))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $thisNet  = $inPeriod(PayrollEntry::query())->sum('net_salary');
        $days     = Carbon::parse($start)->diffInDays(Carbon::parse($end));
        $prevEnd  = Carbon::parse($start)->subDay()->format('Y-m-d');
        $prevStart= Carbon::parse($prevEnd)->subDays($days)->format('Y-m-d');
        $prevQ    = fn($q) => $q->whereHas('payrollPeriod', fn($p) => $p->whereBetween('pay_date', [$prevStart, $prevEnd]));
        $lastNet  = $prevQ(PayrollEntry::query())->sum('net_salary');
        $growth   = $lastNet > 0 ? round((($thisNet - $lastNet) / $lastNet) * 100, 2) : 0;

        $allEntries    = $inPeriod(PayrollEntry::query())->get();
        // Use selectRaw for reliable DISTINCT count across all DB drivers
        $totalEmp      = $inPeriod(PayrollEntry::query())->selectRaw('COUNT(DISTINCT staff_id) as cnt')->value('cnt') ?? 0;
        $avgSalary     = $totalEmp > 0 ? $thisNet / $totalEmp : 0;
        $totalDeduct   = $allEntries->sum('total_deductions');
        $totalBonus    = $allEntries->sum('bonus');

        $byStatus = $allEntries
            ->groupBy(fn($p) => $p->status?->value ?? 'N/A')
            ->map(fn($g) => ['count' => $g->count(), 'total' => $g->sum('net_salary'), 'avg' => $g->avg('net_salary')])
            ->map(fn($v, $k) => array_merge(['status' => $k], $v))->values();

        // PayrollEntry columns: gross_pay (cast), net_pay (cast), net_salary (computed in booted),
        // total_deductions, bonus. The existing Filament page + PayrollExport use net_salary & gross_salary
        // as DB columns (booted sets net_salary; gross_salary may map to gross_pay or be its own column).
        $items = $paginated->getCollection()->map(fn($e) => [
            'id'          => $e->id,
            'employee'    => $e->employee?->name ?? 'N/A',
            'period'      => $e->payrollPeriod
                ? $e->payrollPeriod->start_date->format('M d') . ' - ' . $e->payrollPeriod->end_date->format('M d, Y')
                : 'N/A',
            'pay_date'    => $e->payrollPeriod?->pay_date?->format('Y-m-d'),
            'gross_salary'=> (float) ($e->gross_salary ?? $e->gross_pay ?? 0),
            'deductions'  => (float) $e->total_deductions,
            'bonuses'     => (float) $e->bonus,
            'net_salary'  => (float) ($e->net_salary ?? $e->net_pay ?? 0),
            'status'      => $e->status?->value ?? 'N/A',
        ]);

        return response()->json([
            'success' => true,
            'summary' => [
                'this_period'      => (float) $thisNet,
                'last_period'      => (float) $lastNet,
                'growth_percent'   => $growth,
                'total_employees'  => $totalEmp,
                'avg_salary'       => (float) $avgSalary,
                'total_deductions' => (float) $totalDeduct,
                'total_bonuses'    => (float) $totalBonus,
                'total_count'      => $paginated->total(),
                'by_status'        => $byStatus,
                'start_date'       => $start,
                'end_date'         => $end,
            ],
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function exportPayroll(Request $request)
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $start  = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end    = $request->input('end_date',   now()->format('Y-m-d'));
        $format = $request->input('format', 'pdf');
        $file   = "payroll-report-{$start}-to-{$end}";

        if ($format === 'excel') {
            return Excel::download(new PayrollExport($start, $end), "{$file}.xlsx");
        }

        $payrolls = PayrollEntry::with(['payrollPeriod', 'employee'])
            ->whereHas('payrollPeriod', fn($p) => $p->whereBetween('pay_date', [$start, $end]))->get();

        $pdf = Pdf::loadView('reports.payroll-pdf', compact('payrolls'));
        return response()->streamDownload(fn() => print($pdf->output()), "{$file}.pdf");
    }

    // ── Shipments ─────────────────────────────────────────────────────────────

    public function shipments(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $type = $request->input('report_type');
        $year = (int) $request->input('year', now()->year);
        $seq  = $request->input('container_sequence') ? (int) $request->input('container_sequence') : null;
        $clientId = $request->input('client_id');

        $data = match ($type) {
            'by_container'    => $this->reportService->shipmentsByContainer($year, $seq),
            'by_year'         => $this->reportService->shipmentsByYear($year),
            'profit_loss'     => $this->reportService->shipmentsByContainerSequence($year, $seq),
            'client_shipments'=> $this->reportService->clientShipmentHistory(
                $clientId,
                $request->input('start_date'),
                $request->input('end_date')
            ),
            default => collect(),
        };

        $titles = [
            'by_container'    => 'Shipments by Container Report',
            'by_year'         => 'Shipments by Year Report',
            'profit_loss'     => 'Profit/Loss Report',
            'client_shipments'=> 'Client Shipment History Report',
        ];

        return $this->success([
            'report_type'  => $type,
            'title'        => $titles[$type] ?? 'Shipment Report',
            'generated_at' => now()->toIso8601String(),
            'data'         => $data->values(),
        ]);
    }

    public function exportShipments(Request $request)
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $type   = $request->input('report_type', 'by_year');
        $format = $request->input('format', 'pdf');
        $year   = (int) $request->input('year', now()->year);
        $seq    = $request->input('container_sequence') ? (int) $request->input('container_sequence') : null;

        // Regenerate Eloquent collection directly — ShipmentReportExport needs loaded relations
        // (do NOT decode the JSON response; that strips Eloquent model methods and relations)
        $data = match ($type) {
            'by_container'    => $this->reportService->shipmentsByContainer($year, $seq),
            'by_year'         => $this->reportService->shipmentsByYear($year),
            'profit_loss'     => $this->reportService->shipmentsByContainerSequence($year, $seq),
            'client_shipments'=> $this->reportService->clientShipmentHistory(
                $request->input('client_id'),
                $request->input('start_date'),
                $request->input('end_date')
            ),
            default => collect(),
        };

        $titles = [
            'by_container'    => 'Shipments by Container Report',
            'by_year'         => 'Shipments by Year Report',
            'profit_loss'     => 'Profit/Loss Report',
            'client_shipments'=> 'Client Shipment History Report',
        ];
        $title = $titles[$type] ?? 'Shipment Report';
        $file  = str_replace([' ', '/'], '_', strtolower($title)) . '_' . now()->format('Y-m-d');

        if ($format === 'excel') {
            return Excel::download(new \App\Exports\ShipmentReportExport($data, $type), "{$file}.xlsx");
        }

        $pdf = Pdf::loadView('reports.shipment-report-pdf', [
            'data'              => $data,
            'reportType'        => $type,
            'title'             => $title,
            'year'              => $year,
            'containerSequence' => $seq,
        ]);

        return response()->streamDownload(fn() => print($pdf->output()), "{$file}.pdf");
    }

    // ── Additional ReportService endpoints ────────────────────────────────────

    public function profitLossSummary(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $start    = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $end      = Carbon::parse($request->input('end_date',   now()));
        $branchId = $request->input('branch_id');

        return $this->success($this->reportService->profitLossReport($start, $end, $branchId));
    }

    public function clientGrowth(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $year = (int) $request->input('year', now()->year);
        return $this->success($this->reportService->clientGrowthReport($year));
    }

    public function receivablesAging(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        $branchId = $request->input('branch_id');
        $result   = $this->reportService->receivablesAgingReport($branchId);

        // ReportService returns raw Eloquent collection in 'shipments' — exclude it,
        // expose only the aggregate data safe for JSON serialization
        return $this->success([
            'total_outstanding' => $result['total_outstanding'],
            'total_shipments'   => $result['total_shipments'],
            'aging'             => $result['aging'],
        ]);
    }

    public function containerDetail(Request $request, string $reference): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_report'), 403);

        return $this->success($this->reportService->containerShipmentDetail($reference));
    }
}
```

---

## 16. Backend: routes/api.php additions

Add these routes inside the `auth:sanctum` middleware group, after the existing `// Reports` block:

```php
// ── Financial Reports ────────────────────────────────────────────────────────
Route::prefix('reports')->group(function () {
    // Existing generic report list (keep)
    Route::get('/',    [ReportController::class, 'index']);
    Route::get('/{id}',[ReportController::class, 'show']);

    // Financial Dashboard
    Route::get('financial-dashboard', [FinancialReportController::class, 'financialDashboard']);

    // Expenses
    Route::get('expenses',        [FinancialReportController::class, 'expenses']);
    Route::get('expenses/export', [FinancialReportController::class, 'exportExpenses']);

    // Incomes
    Route::get('incomes',         [FinancialReportController::class, 'incomes']);
    Route::get('incomes/export',  [FinancialReportController::class, 'exportIncomes']);

    // Payroll
    Route::get('payroll',         [FinancialReportController::class, 'payroll']);
    Route::get('payroll/export',  [FinancialReportController::class, 'exportPayroll']);

    // Shipments
    Route::get('shipments',       [FinancialReportController::class, 'shipments']);
    Route::get('shipments/export',[FinancialReportController::class, 'exportShipments']);

    // Additional ReportService reports
    Route::get('profit-loss-summary',           [FinancialReportController::class, 'profitLossSummary']);
    Route::get('client-growth',                 [FinancialReportController::class, 'clientGrowth']);
    Route::get('receivables-aging',             [FinancialReportController::class, 'receivablesAging']);
    Route::get('container-detail/{reference}',  [FinancialReportController::class, 'containerDetail']);
});
```

Add the import at the top of `routes/api.php`:
```php
use App\Http\Controllers\Api\V1\FinancialReportController;
```

---

## Updated Quick Reference: All Backend Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/reports/financial-dashboard` | Overview, KPIs, container profit, top states, monthly trend |
| GET | `/api/v1/reports/expenses` | Paginated expenses + summary (by category, stage) |
| GET | `/api/v1/reports/expenses/export` | PDF or Excel (`?format=pdf\|excel`) |
| GET | `/api/v1/reports/incomes` | Paginated incomes + summary (by category, method) |
| GET | `/api/v1/reports/incomes/export` | PDF or Excel |
| GET | `/api/v1/reports/payroll` | Paginated payroll entries + summary |
| GET | `/api/v1/reports/payroll/export` | PDF or Excel |
| GET | `/api/v1/reports/shipments` | Shipment report (`?report_type=by_container\|by_year\|profit_loss\|client_shipments`) |
| GET | `/api/v1/reports/shipments/export` | PDF or Excel |
| GET | `/api/v1/reports/profit-loss-summary` | Full P&L (revenue, expenses, payroll, margin) |
| GET | `/api/v1/reports/client-growth` | New clients per month by year |
| GET | `/api/v1/reports/receivables-aging` | Outstanding balances by age bucket |
| GET | `/api/v1/reports/container-detail/:reference` | Deep single-shipment financials |

**Common params:** `start_date`, `end_date`, `branch_id` (where applicable)
**Pagination:** `page`, `per_page`
**Shipment-specific:** `report_type`, `year`, `container_sequence`, `client_id`
