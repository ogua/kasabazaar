# Financial Tracking System - KasaBaZaar

## Overview
The system tracks complete financial operations including income, expenses, payroll, and profit calculations.

## Income Sources

### 1. Shipment Income (Primary Revenue)
- **Source**: `payments` table
- **Type**: `payment_type = 'credit'`
- **Description**: All payments received for shipments
- **Fields**:
  - `shipment_id` - Links to the shipment
  - `amount` - Payment amount in GHS
  - `paid_on` - Payment date
  - `payment_ref` - Payment reference
  - `paying_type` - Payment method (cash, mobile money, bank transfer, etc.)

### 2. External Income (Secondary Revenue)
- **Source**: `incomes` table
- **Description**: Other revenue streams not related to shipments
- **Categories**: (stored in `income_categories` table)
  - Consulting fees
  - Storage fees
  - Packaging services
  - Insurance commissions
  - Late payment fees
  - Handling charges
  - Document processing
  - Referral bonuses
  - Partnership income
  - Investment returns
  - Grants & subsidies
  - Miscellaneous income
- **Fields**:
  - `amount_usd` - Amount in USD
  - `amount_ghs` - Amount in GHS (converted)
  - `exchange_rate` - Exchange rate used
  - `income_date` - Date of income
  - `status` - Pending/Received/Cancelled
  - `income_category_id` - Category reference

## Expenses

### Shipment Expenses
- **Source**: `expenses` table
- **Linked to**: Specific shipments via `shipment_id`
- **Categories**: (stored in `expense_categories` table)
  - Fuel & Transport
  - Vehicle Maintenance
  - Port & Terminal Fees
  - Customs & Duties
  - Storage & Warehousing
  - Packaging Materials
  - Insurance
  - Documentation
  - Staff Allowances
  - Utilities
  - Office Expenses
  - Miscellaneous
- **Stages**:
  - Pre-shipment
  - During shipment
  - Post-shipment
- **Fields**:
  - `amount_usd` - Amount in USD
  - `amount_ghs` - Amount in GHS
  - `expense_date` - Date of expense
  - `expense_stage` - Pre/During/Post shipment

## Payroll

### Staff Salaries
- **Source**: `payroll_entries` table
- **Linked to**: `payroll_periods` for period management
- **Components**:
  - Base salary
  - Overtime
  - Bonuses
  - Allowances
  - Tax deductions (Ghana PAYE)
  - SSNIT (5.5%)
  - Other deductions
- **Fields**:
  - `gross_pay` - Total before deductions
  - `total_deductions` - Sum of all deductions
  - `net_salary` - Final payment to staff
  - `status` - Pending/Approved/Paid

## Profit Calculation

### Formula
```
Total Income = Shipment Income + External Income
Total Costs = Expenses + Payroll
Net Profit = Total Income - Total Costs
```

### Shipment-Level Profit
```php
// For individual shipments
$shipmentRevenue = $shipment->payments()
    ->where('payment_type', 'credit')
    ->sum('amount');

$shipmentExpenses = $shipment->expenses()
    ->sum('amount_ghs');

$shipmentProfit = $shipmentRevenue - $shipmentExpenses;
```

### Period Profit (Monthly/Yearly)
```php
// Monthly profit
$startDate = now()->startOfMonth();
$endDate = now()->endOfMonth();

$shipmentIncome = Payment::where('payment_type', 'credit')
    ->whereBetween('paid_on', [$startDate, $endDate])
    ->sum('amount');

$externalIncome = Income::where('status', IncomeStatus::Received)
    ->whereBetween('income_date', [$startDate, $endDate])
    ->sum('amount_ghs');

$totalIncome = $shipmentIncome + $externalIncome;

$expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
    ->sum('amount_ghs');

$payroll = PayrollEntry::whereHas('payrollPeriod', function($q) use ($startDate, $endDate) {
    $q->whereBetween('pay_date', [$startDate, $endDate]);
})->sum('net_salary');

$totalCosts = $expenses + $payroll;
$netProfit = $totalIncome - $totalCosts;
```

## Unpaid Shipments Tracking

### Identification
Shipments are considered unpaid or partially paid when:
```sql
-- No payments recorded
SELECT * FROM shipments
WHERE NOT EXISTS (
    SELECT 1 FROM payments
    WHERE payments.shipment_id = shipments.id
    AND payment_type = 'credit'
)

-- Or partial payment
SELECT * FROM shipments
WHERE (
    SELECT COALESCE(SUM(amount), 0)
    FROM payments
    WHERE payments.shipment_id = shipments.id
    AND payment_type = 'credit'
) < total
```

### Outstanding Balance Calculation
```php
$paidAmount = $shipment->payments()
    ->where('payment_type', 'credit')
    ->sum('amount');

$balanceDue = $shipment->total - $paidAmount;
```

## Financial Dashboard

### Main Metrics (FinancialOverviewWidget)
1. **Total Income (Monthly)**
   - Combines shipment income + external income
   - Shows 7-day trend chart
   - Breakdown of sources in description

2. **Total Costs (Monthly)**
   - Combines expenses + payroll
   - Shows 7-day trend chart
   - Breakdown in description

3. **Net Profit (Monthly)**
   - Total Income - Total Costs
   - Color-coded (green for profit, red for loss)
   - Trend indicator

4. **Unpaid Shipments**
   - Count of unpaid/partially paid shipments
   - Total outstanding amount
   - Clickable to filter shipment list

### Detailed Widgets
- **ExpenseStatsWidget** - Expense analysis by category
- **IncomeStatsWidget** - External income tracking
- **PayrollStatsWidget** - Payroll status and totals
- **ExpensesByCategoryChart** - Doughnut chart of expenses
- **MonthlyExpenseIncomeChart** - Bar chart comparing expenses vs income
- **UnpaidShipmentsWidget** - Table of all unpaid shipments with:
  - Shipping reference
  - Client name
  - Total amount
  - Paid amount
  - Balance due
  - Quick action to record payment

## Reports Available

### Financial Reports (via ShipmentReports page)
1. **Shipments by Container** - All shipments in a specific container
2. **Shipments by Year** - Annual shipment summary
3. **Profit/Loss by Container** - Revenue vs expenses per container
4. **Client Shipment History** - Client-specific reports

### Accessing Reports
Navigate to: Admin → Reports → Shipment Reports

## Database Relationships

```
Payment ─belongs to→ Shipment
Payment ─belongs to→ Branch
Payment ─belongs to→ User (recorded by)

Expense ─belongs to→ Shipment
Expense ─belongs to→ ExpenseCategory
Expense ─belongs to→ Branch
Expense ─belongs to→ User (recorded by)

Income ─belongs to→ IncomeCategory
Income ─belongs to→ Branch
Income ─belongs to→ User (recorded by)
Income ─can belong to→ Shipment (optional link)

PayrollEntry ─belongs to→ PayrollPeriod
PayrollEntry ─belongs to→ Staff

Shipment ─has many→ Payments
Shipment ─has many→ Expenses
Shipment ─has many→ Incomes (optional)
```

## Key Features

1. **Multi-Currency Support**
   - USD and GHS tracking
   - Exchange rate logging
   - Automatic conversion

2. **Comprehensive Tracking**
   - All income sources captured
   - Expense categorization
   - Payroll with Ghana tax calculations

3. **Real-Time Profit Monitoring**
   - Dashboard widgets update automatically
   - Trend charts for visual analysis
   - Unpaid shipments highlighted

4. **Payment Status**
   - Track partial payments
   - Outstanding balances
   - Payment history per shipment

5. **Period Analysis**
   - Monthly, quarterly, yearly views
   - Compare periods
   - Budget vs actual tracking

## Navigation

- **Financial Dashboard**: Admin → Reports → Financial Dashboard
- **Expenses**: Admin → Finance → Expenses
- **External Income**: Admin → Finance → Incomes
- **Payroll**: Admin → Payroll → Payroll Periods
- **Shipments**: Admin → Shipments → All Shipments
- **Payments**: Admin → Shipments → Payments

## Notes

- All financial data is branch-scoped (multi-tenant)
- Soft deletes enabled for audit trail
- All financial transactions logged with user who recorded them
- Exchange rates logged for historical accuracy
- PDF report generation available for all financial reports
