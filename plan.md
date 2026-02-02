# KasaBaZaar Enhancement Implementation Plan

## Project Overview

This document outlines the implementation plan for enhancing the KasaBaZaar shipping management system with the following features:

1. **Expense Tracking per Shipment** - Track expenses incurred for each shipment
2. **Currency Conversion (USD to GHS)** - Record cedi equivalent for dollar-based shipments
3. **External Income Tracking** - Record income from sources outside shipments
4. **Staff & Payroll Management** - Manage staff with roles and payroll
5. **User Account Integration** - Staff accounts linked to system login
6. **Comprehensive Reporting System** - Various reports including shipment analysis
7. **Business Analytics** - Client growth, profit/loss tracking, and statistics

---

## Phase 1: Database Schema Design

### 1.1 New Tables Required

#### `expense_categories` Table
Categorize different types of expenses for better tracking.

```php
Schema::create('expense_categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');                    // e.g., "Customs", "Transportation", "Storage"
    $table->string('code')->unique();          // e.g., "CUSTOMS", "TRANSPORT"
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

#### `expenses` Table
Track expenses per shipment with currency conversion.

```php
Schema::create('expenses', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('shipment_id')->constrained('shipments')->cascadeOnDelete();
    $table->foreignUuid('expense_category_id')->constrained('expense_categories');
    $table->foreignUuid('branch_id')->constrained('branches');
    $table->foreignUuid('recorded_by')->constrained('users');

    // Expense details
    $table->string('reference')->unique();     // Auto-generated expense reference
    $table->string('title');                   // Brief description
    $table->text('description')->nullable();   // Detailed notes

    // Amount in original currency (USD)
    $table->decimal('amount_usd', 12, 2);
    $table->decimal('exchange_rate', 10, 4);   // Rate at time of expense
    $table->decimal('amount_ghs', 12, 2);      // Calculated: amount_usd * exchange_rate

    // Timing
    $table->date('expense_date');
    $table->enum('expense_stage', ['pre_shipment', 'during_shipment', 'post_shipment']);

    // Documentation
    $table->string('receipt_path')->nullable();
    $table->string('vendor_name')->nullable();

    $table->timestamps();
    $table->softDeletes();
});
```

#### `exchange_rate_logs` Table
Historical exchange rate tracking for audit.

```php
Schema::create('exchange_rate_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('from_currency', 3)->default('USD');
    $table->string('to_currency', 3)->default('GHS');
    $table->decimal('rate', 10, 4);
    $table->date('rate_date');
    $table->string('source')->nullable();      // API source or manual
    $table->foreignUuid('recorded_by')->nullable()->constrained('users');
    $table->timestamps();

    $table->unique(['from_currency', 'to_currency', 'rate_date']);
});
```

#### `staff_roles` Table
Define various staff roles within the organization.

```php
Schema::create('staff_roles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');                    // e.g., "Driver", "Accountant", "Shipping Manager"
    $table->string('code')->unique();          // e.g., "DRIVER", "ACCOUNTANT"
    $table->text('description')->nullable();
    $table->decimal('base_salary', 12, 2)->nullable();  // Default salary for role
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

#### Update `staff` Table (Existing)
Enhance existing staff table with role and salary information.

```php
Schema::table('staff', function (Blueprint $table) {
    $table->foreignUuid('staff_role_id')->nullable()->constrained('staff_roles');
    $table->foreignUuid('user_id')->nullable()->constrained('users');  // Link to user account
    $table->decimal('salary', 12, 2)->nullable();
    $table->date('hire_date')->nullable();
    $table->enum('employment_status', ['active', 'inactive', 'terminated', 'on_leave'])->default('active');
    $table->string('employee_id')->unique()->nullable();  // Employee reference number
    $table->text('notes')->nullable();
});
```

#### `payroll_periods` Table
Define payroll periods (monthly, bi-weekly, etc.).

```php
Schema::create('payroll_periods', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');                    // e.g., "January 2026"
    $table->date('start_date');
    $table->date('end_date');
    $table->date('pay_date');
    $table->enum('status', ['draft', 'processing', 'approved', 'paid', 'cancelled'])->default('draft');
    $table->foreignUuid('branch_id')->constrained('branches');
    $table->foreignUuid('approved_by')->nullable()->constrained('users');
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### `payroll_entries` Table
Individual payroll entries per staff member per period.

```php
Schema::create('payroll_entries', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
    $table->foreignUuid('staff_id')->constrained('staff');

    // Earnings
    $table->decimal('base_salary', 12, 2);
    $table->decimal('overtime', 12, 2)->default(0);
    $table->decimal('bonus', 12, 2)->default(0);
    $table->decimal('allowances', 12, 2)->default(0);

    // Deductions
    $table->decimal('tax', 12, 2)->default(0);
    $table->decimal('ssnit', 12, 2)->default(0);        // Ghana Social Security
    $table->decimal('other_deductions', 12, 2)->default(0);

    // Net
    $table->decimal('gross_pay', 12, 2);
    $table->decimal('total_deductions', 12, 2);
    $table->decimal('net_pay', 12, 2);

    // Status
    $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
    $table->date('paid_at')->nullable();
    $table->string('payment_reference')->nullable();

    $table->text('notes')->nullable();
    $table->timestamps();
});
```

#### `income_categories` Table
Categorize different types of external income.

```php
Schema::create('income_categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');                    // e.g., "Consulting", "Equipment Rental", "Interest"
    $table->string('code')->unique();          // e.g., "CONSULTING", "RENTAL"
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

#### `incomes` Table
Track external income not tied to shipments.

```php
Schema::create('incomes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('income_category_id')->constrained('income_categories');
    $table->foreignUuid('branch_id')->constrained('branches');
    $table->foreignUuid('recorded_by')->constrained('users');

    // Optional shipment link (for shipment-related additional income)
    $table->foreignUuid('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();

    // Income details
    $table->string('reference')->unique();     // Auto-generated income reference
    $table->string('title');                   // Brief description
    $table->text('description')->nullable();   // Detailed notes

    // Amount in original currency (USD)
    $table->decimal('amount_usd', 12, 2);
    $table->decimal('exchange_rate', 10, 4);   // Rate at time of income
    $table->decimal('amount_ghs', 12, 2);      // Calculated: amount_usd * exchange_rate

    // Source information
    $table->string('source_name')->nullable(); // Payer/source name
    $table->string('source_contact')->nullable(); // Contact info

    // Timing
    $table->date('income_date');
    $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'card', 'other'])->default('cash');
    $table->string('payment_reference')->nullable(); // Bank ref, cheque no, etc.

    // Documentation
    $table->string('receipt_path')->nullable();

    // Status
    $table->enum('status', ['pending', 'received', 'cancelled'])->default('received');

    $table->timestamps();
    $table->softDeletes();
});
```

#### `reports` Table
Store generated report metadata.

```php
Schema::create('reports', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('branch_id')->constrained('branches');
    $table->foreignUuid('generated_by')->constrained('users');

    $table->string('report_type');             // e.g., "shipment_by_container", "profit_loss"
    $table->string('title');
    $table->json('parameters')->nullable();    // Report filters/parameters
    $table->json('data')->nullable();          // Cached report data

    $table->date('period_start')->nullable();
    $table->date('period_end')->nullable();

    $table->string('file_path')->nullable();   // PDF path if generated
    $table->timestamp('generated_at');

    $table->timestamps();
});
```

---

## Phase 2: Model Development

### 2.1 New Models

#### `ExpenseCategory` Model
Location: `app/Models/ExpenseCategory.php`

```php
class ExpenseCategory extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
```

#### `Expense` Model
Location: `app/Models/Expense.php`

```php
class Expense extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'shipment_id', 'expense_category_id', 'branch_id', 'recorded_by',
        'reference', 'title', 'description', 'amount_usd', 'exchange_rate',
        'amount_ghs', 'expense_date', 'expense_stage', 'receipt_path', 'vendor_name'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_ghs' => 'decimal:2',
    ];

    // Relationships
    public function shipment(): BelongsTo;
    public function category(): BelongsTo;
    public function branch(): BelongsTo;
    public function recordedBy(): BelongsTo;

    // Auto-generate reference
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($expense) {
            $expense->reference = self::generateReference();
            $expense->amount_ghs = $expense->amount_usd * $expense->exchange_rate;
        });
    }

    public static function generateReference(): string
    {
        // Format: EXP-YYYYMMDD-XXXX
    }
}
```

#### `ExchangeRateLog` Model
Location: `app/Models/ExchangeRateLog.php`

#### `StaffRole` Model
Location: `app/Models/StaffRole.php`

#### `PayrollPeriod` Model
Location: `app/Models/PayrollPeriod.php`

#### `PayrollEntry` Model
Location: `app/Models/PayrollEntry.php`

#### `Report` Model
Location: `app/Models/Report.php`

#### `IncomeCategory` Model
Location: `app/Models/IncomeCategory.php`

```php
class IncomeCategory extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }
}
```

#### `Income` Model
Location: `app/Models/Income.php`

```php
class Income extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'income_category_id', 'branch_id', 'recorded_by', 'shipment_id',
        'reference', 'title', 'description', 'amount_usd', 'exchange_rate',
        'amount_ghs', 'source_name', 'source_contact', 'income_date',
        'payment_method', 'payment_reference', 'receipt_path', 'status'
    ];

    protected $casts = [
        'income_date' => 'date',
        'amount_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_ghs' => 'decimal:2',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    // Auto-generate reference
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($income) {
            $income->reference = self::generateReference();
            $income->amount_ghs = $income->amount_usd * $income->exchange_rate;
        });
    }

    public static function generateReference(): string
    {
        // Format: INC-YYYYMMDD-XXXX
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now())->count() + 1;
        return sprintf('INC-%s-%04d', $date, $count);
    }
}
```

### 2.2 Model Updates

#### Update `Shipment` Model
Add expense relationship and financial summary methods.

```php
// Add to Shipment.php

public function expenses(): HasMany
{
    return $this->hasMany(Expense::class);
}

public function getTotalExpensesUsdAttribute(): float
{
    return $this->expenses()->sum('amount_usd');
}

public function getTotalExpensesGhsAttribute(): float
{
    return $this->expenses()->sum('amount_ghs');
}

public function getNetProfitUsdAttribute(): float
{
    return $this->total - $this->total_expenses_usd;
}

// Parse shipping reference for container info
public static function parseShippingReferenceExtended(string $reference): array
{
    // Returns: container_number, year, container_sequence, client_sequence
}

// Scope for filtering by container
public function scopeByContainer($query, string $containerNumber);

// Scope for filtering by year
public function scopeByYear($query, int $year);

// Scope for filtering by container sequence
public function scopeByContainerSequence($query, int $sequence);
```

#### Update `Staff` Model
Add role and user account relationships.

```php
// Add to Staff.php

public function role(): BelongsTo
{
    return $this->belongsTo(StaffRole::class, 'staff_role_id');
}

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function payrollEntries(): HasMany
{
    return $this->hasMany(PayrollEntry::class);
}
```

#### Update `User` Model
Add staff relationship.

```php
// Add to User.php

public function staff(): HasOne
{
    return $this->hasOne(Staff::class);
}

public function isStaff(): bool
{
    return $this->staff()->exists();
}
```

---

## Phase 3: Service Layer

### 3.1 New Services

#### `ExchangeRateService`
Location: `app/Services/ExchangeRateService.php`

```php
class ExchangeRateService
{
    // Get current exchange rate (uses worksome/exchange package)
    public function getCurrentRate(string $from = 'USD', string $to = 'GHS'): float;

    // Get rate for specific date
    public function getRateForDate(Carbon $date, string $from = 'USD', string $to = 'GHS'): float;

    // Log exchange rate
    public function logRate(float $rate, string $from, string $to, ?int $userId = null): ExchangeRateLog;

    // Convert amount
    public function convert(float $amount, float $rate): float;
}
```

#### `ExpenseService`
Location: `app/Services/ExpenseService.php`

```php
class ExpenseService
{
    // Create expense with auto currency conversion
    public function createExpense(Shipment $shipment, array $data): Expense;

    // Get expense summary for shipment
    public function getShipmentExpenseSummary(Shipment $shipment): array;

    // Get expense summary by category
    public function getExpensesByCategory(Carbon $startDate, Carbon $endDate): Collection;
}
```

#### `PayrollService`
Location: `app/Services/PayrollService.php`

```php
class PayrollService
{
    // Create payroll period
    public function createPayrollPeriod(array $data): PayrollPeriod;

    // Generate payroll entries for all active staff
    public function generatePayrollEntries(PayrollPeriod $period): Collection;

    // Calculate net pay
    public function calculateNetPay(PayrollEntry $entry): float;

    // Process payroll
    public function processPayroll(PayrollPeriod $period): bool;
}
```

#### `ReportService`
Location: `app/Services/ReportService.php`

```php
class ReportService
{
    // Generate shipment by container report
    public function shipmentsByContainer(string $containerNumber): array;

    // Shipments by year
    public function shipmentsByYear(int $year): array;

    // Shipments by container sequence
    public function shipmentsByContainerSequence(int $year, int $sequence): array;

    // Profit and loss report
    public function profitLossReport(Carbon $startDate, Carbon $endDate): array;

    // Client growth report
    public function clientGrowthReport(int $year): array;

    // Monthly client additions
    public function newClientsPerMonth(int $year): array;

    // Expense analysis report
    public function expenseAnalysisReport(Carbon $startDate, Carbon $endDate): array;

    // Staff payroll summary
    public function payrollSummaryReport(int $year): array;

    // Container shipment detail report
    public function containerShipmentDetail(string $reference): array;
}
```

#### `StaffAccountService`
Location: `app/Services/StaffAccountService.php`

```php
class StaffAccountService
{
    // Create user account for staff
    public function createStaffAccount(Staff $staff, string $email, string $password): User;

    // Link existing user to staff
    public function linkUserToStaff(User $user, Staff $staff): void;

    // Assign role-based permissions
    public function assignRolePermissions(User $user, StaffRole $role): void;
}
```

#### `IncomeService`
Location: `app/Services/IncomeService.php`

```php
class IncomeService
{
    // Create income with auto currency conversion
    public function createIncome(array $data): Income;

    // Get income summary by category
    public function getIncomeByCategory(Carbon $startDate, Carbon $endDate): Collection;

    // Get total income for period
    public function getTotalIncome(Carbon $startDate, Carbon $endDate): array;

    // Get income trend (monthly)
    public function getMonthlyIncomeTrend(int $year): array;
}
```

---

## Phase 4: Filament Admin Resources

### 4.1 New Filament Resources

#### `ExpenseCategoryResource`
Location: `app/Filament/Resources/ExpenseCategoryResource.php`

Features:
- CRUD for expense categories
- Active/inactive toggle
- View associated expenses

#### `ExpenseResource`
Location: `app/Filament/Resources/ExpenseResource.php`

Features:
- Create expenses linked to shipments
- Auto-fetch current exchange rate
- Auto-calculate GHS amount
- Filter by shipment, category, date range
- Upload receipt documents
- View expense history

Form Fields:
```php
Forms\Components\Select::make('shipment_id')
    ->relationship('shipment', 'shipping_reference')
    ->searchable()
    ->preload()
    ->required()
    ->reactive(),

Forms\Components\Select::make('expense_category_id')
    ->relationship('category', 'name')
    ->required(),

Forms\Components\TextInput::make('title')
    ->required()
    ->maxLength(255),

Forms\Components\Textarea::make('description'),

Forms\Components\TextInput::make('amount_usd')
    ->numeric()
    ->required()
    ->prefix('$')
    ->reactive()
    ->afterStateUpdated(fn ($state, Set $set) =>
        $set('amount_ghs', $state * $this->getCurrentRate())),

Forms\Components\TextInput::make('exchange_rate')
    ->numeric()
    ->required()
    ->default(fn () => $this->getCurrentRate()),

Forms\Components\TextInput::make('amount_ghs')
    ->numeric()
    ->prefix('GH₵')
    ->disabled()
    ->dehydrated(),

Forms\Components\DatePicker::make('expense_date')
    ->required()
    ->default(now()),

Forms\Components\Select::make('expense_stage')
    ->options([
        'pre_shipment' => 'Pre-Shipment',
        'during_shipment' => 'During Shipment',
        'post_shipment' => 'Post-Shipment',
    ])
    ->required(),

Forms\Components\TextInput::make('vendor_name'),

Forms\Components\FileUpload::make('receipt_path')
    ->directory('receipts')
    ->acceptedFileTypes(['application/pdf', 'image/*']),
```

#### `StaffRoleResource`
Location: `app/Filament/Resources/StaffRoleResource.php`

Features:
- CRUD for staff roles
- Predefined roles: Driver, Accountant, Shipping Manager, CEO, Admin, Warehouse Staff
- Base salary setting per role
- View staff in each role

#### Update `StaffResource`
Location: `app/Filament/Resources/StaffResource.php`

Add:
- Role selection dropdown
- User account creation/linking
- Salary management
- Employment status
- Hire date tracking

#### `PayrollPeriodResource`
Location: `app/Filament/Resources/PayrollPeriodResource.php`

Features:
- Create payroll periods
- Generate payroll entries for period
- Approve/process payroll
- View payment status
- Export payroll data

#### `PayrollEntryResource`
Location: `app/Filament/Resources/PayrollEntryResource.php`

Features:
- Edit individual payroll entries
- Adjust earnings and deductions
- Mark as paid
- View payroll history per staff

#### `ReportResource`
Location: `app/Filament/Resources/ReportResource.php`

Features:
- Generate various report types
- Set parameters and date ranges
- View/download generated reports
- Schedule recurring reports

#### `IncomeCategoryResource`
Location: `app/Filament/Resources/IncomeCategoryResource.php`

Features:
- CRUD for income categories
- Active/inactive toggle
- View associated income entries

#### `IncomeResource`
Location: `app/Filament/Resources/IncomeResource.php`

Features:
- Record external income
- Auto-fetch current exchange rate
- Auto-calculate GHS amount
- Filter by category, date range, payment method
- Upload supporting documents
- Link to shipment (optional)
- Track payment status

Form Fields:
```php
Forms\Components\Select::make('income_category_id')
    ->relationship('category', 'name')
    ->required()
    ->createOptionForm([
        Forms\Components\TextInput::make('name')->required(),
        Forms\Components\TextInput::make('code')->required(),
        Forms\Components\Textarea::make('description'),
    ]),

Forms\Components\Select::make('shipment_id')
    ->relationship('shipment', 'shipping_reference')
    ->searchable()
    ->preload()
    ->nullable()
    ->helperText('Optional: Link to a shipment if this income is related'),

Forms\Components\TextInput::make('title')
    ->required()
    ->maxLength(255),

Forms\Components\Textarea::make('description'),

Forms\Components\TextInput::make('source_name')
    ->label('Payer/Source Name'),

Forms\Components\TextInput::make('source_contact')
    ->label('Contact Info'),

Forms\Components\TextInput::make('amount_usd')
    ->numeric()
    ->required()
    ->prefix('$')
    ->reactive()
    ->afterStateUpdated(fn ($state, Set $set, Get $get) =>
        $set('amount_ghs', $state * $get('exchange_rate'))),

Forms\Components\TextInput::make('exchange_rate')
    ->numeric()
    ->required()
    ->default(fn () => app(ExchangeRateService::class)->getCurrentRate())
    ->reactive()
    ->afterStateUpdated(fn ($state, Set $set, Get $get) =>
        $set('amount_ghs', $get('amount_usd') * $state)),

Forms\Components\TextInput::make('amount_ghs')
    ->numeric()
    ->prefix('GH₵')
    ->disabled()
    ->dehydrated(),

Forms\Components\DatePicker::make('income_date')
    ->required()
    ->default(now()),

Forms\Components\Select::make('payment_method')
    ->options([
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'mobile_money' => 'Mobile Money',
        'cheque' => 'Cheque',
        'card' => 'Card',
        'other' => 'Other',
    ])
    ->required()
    ->default('cash'),

Forms\Components\TextInput::make('payment_reference')
    ->label('Payment Reference')
    ->helperText('Bank reference, cheque number, etc.'),

Forms\Components\Select::make('status')
    ->options([
        'pending' => 'Pending',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
    ])
    ->default('received'),

Forms\Components\FileUpload::make('receipt_path')
    ->label('Supporting Document')
    ->directory('income-receipts')
    ->acceptedFileTypes(['application/pdf', 'image/*']),
```

### 4.2 New Filament Pages

#### `ReportDashboard`
Location: `app/Filament/Pages/ReportDashboard.php`

A dedicated reporting page with:
- Report type selector
- Dynamic parameter inputs
- Date range picker
- Generate and preview reports
- Export to PDF/Excel

Report Types:
1. **Shipments by Container** - All shipments for a specific container (e.g., CON51)
2. **Shipments by Year** - All shipments in a given year
3. **Shipments by Container Sequence** - All shipments for C2, C3, etc.
4. **Container Detail Report** - Full breakdown showing:
   - Client information
   - Receivers and their items
   - Payment status
   - Expenses incurred
5. **Profit & Loss Statement** - Revenue vs expenses
6. **Client Growth Analysis** - New clients per year/month
7. **Expense Analysis** - Breakdown by category
8. **Payroll Summary** - Total staff costs

#### `FinancialDashboard`
Location: `app/Filament/Pages/FinancialDashboard.php`

Dashboard showing:
- Total revenue (shipments + external income)
- Total expenses
- Net profit/loss
- Monthly comparison charts
- Expense breakdown pie chart
- Income breakdown pie chart
- Top expense categories
- Top income sources

### 4.3 New Widgets

#### `ExpenseSummaryWidget`
Shows total expenses, breakdown by category, comparison to revenue.

#### `ProfitLossWidget`
Real-time profit/loss calculation with monthly trends.

#### `ClientGrowthWidget`
Chart showing new client acquisitions over time.

#### `PayrollOverviewWidget`
Current payroll status, total staff costs, pending payments.

#### `ExchangeRateWidget`
Current USD/GHS rate with historical comparison.

#### `IncomeOverviewWidget`
Shows total external income, breakdown by category, monthly trends.

```php
class IncomeOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Income (USD)', '$' . number_format(Income::where('status', 'received')->sum('amount_usd'), 2)),
            Stat::make('Total Income (GHS)', 'GH₵' . number_format(Income::where('status', 'received')->sum('amount_ghs'), 2)),
            Stat::make('This Month', '$' . number_format(
                Income::where('status', 'received')
                    ->whereMonth('income_date', now()->month)
                    ->sum('amount_usd'), 2
            )),
        ];
    }
}
```

---

## Phase 5: Shipment Expense Integration

### 5.1 Update ShipmentResource

Add expense management directly in shipment view:

#### Relation Manager: `ExpensesRelationManager`
Location: `app/Filament/Resources/ShipmentResource/RelationManagers/ExpensesRelationManager.php`

Features:
- View all expenses for shipment
- Add new expenses inline
- Edit/delete expenses
- Total expense summary

#### Add to ShipmentResource Infolist:
```php
Infolists\Components\Section::make('Expense Summary')
    ->schema([
        Infolists\Components\TextEntry::make('total_expenses_usd')
            ->money('USD')
            ->label('Total Expenses (USD)'),

        Infolists\Components\TextEntry::make('total_expenses_ghs')
            ->money('GHS')
            ->label('Total Expenses (GHS)'),

        Infolists\Components\TextEntry::make('net_profit_usd')
            ->money('USD')
            ->label('Net Profit (USD)'),
    ]),
```

### 5.2 Shipment Financial Tab

Add a new tab to shipment view showing:
- Revenue (total shipment value)
- All expenses with categories
- Currency breakdown (USD vs GHS)
- Net profit calculation
- Visual expense breakdown

---

## Phase 6: Staff & User Account Integration

### 6.1 Staff Account Creation Flow

When creating/editing staff:

1. Option to create new user account or link existing
2. Auto-generate email from staff name if not provided
3. Send welcome email with login credentials
4. Assign permissions based on staff role

```php
// In StaffResource form
Forms\Components\Section::make('User Account')
    ->schema([
        Forms\Components\Toggle::make('create_user_account')
            ->label('Create Login Account')
            ->reactive()
            ->default(false),

        Forms\Components\TextInput::make('user_email')
            ->email()
            ->required()
            ->visible(fn (Get $get) => $get('create_user_account')),

        Forms\Components\TextInput::make('user_password')
            ->password()
            ->required()
            ->visible(fn (Get $get) => $get('create_user_account')),
    ]),
```

### 6.2 Role-Based Permissions

Map staff roles to Spatie permissions:

| Staff Role | System Permissions |
|------------|-------------------|
| CEO | Super admin, all access |
| Shipping Manager | Shipments, clients, receivers, reports |
| Accountant | Payments, expenses, payroll, financial reports |
| Driver | View assigned shipments, update delivery status |
| Warehouse Staff | Inventory, shipment items, packing |
| Admin | User management, settings |

### 6.3 Predefined Staff Roles

Create seeder with default roles:

```php
$roles = [
    ['name' => 'Chief Executive Officer', 'code' => 'CEO', 'base_salary' => 10000],
    ['name' => 'Shipping Manager', 'code' => 'SHIP_MGR', 'base_salary' => 3000],
    ['name' => 'Accountant', 'code' => 'ACCOUNTANT', 'base_salary' => 2500],
    ['name' => 'Driver', 'code' => 'DRIVER', 'base_salary' => 1500],
    ['name' => 'Warehouse Staff', 'code' => 'WAREHOUSE', 'base_salary' => 1200],
    ['name' => 'Administrative Officer', 'code' => 'ADMIN', 'base_salary' => 2000],
    ['name' => 'Customer Service', 'code' => 'CUSTOMER_SVC', 'base_salary' => 1800],
];
```

---

## Phase 7: Reporting System

### 7.1 Report Types Implementation

#### 7.1.1 Shipments per Container Report

```php
public function shipmentsByContainer(string $containerNumber): array
{
    // Example: containerNumber = "CON51"
    $shipments = Shipment::where('shipping_reference', 'like', "{$containerNumber}-%")
        ->with(['client', 'receivers.items', 'expenses', 'payments'])
        ->get();

    return [
        'container' => $containerNumber,
        'total_shipments' => $shipments->count(),
        'total_revenue' => $shipments->sum('total'),
        'total_expenses' => $shipments->sum('total_expenses_usd'),
        'shipments' => $shipments->map(fn ($s) => [
            'reference' => $s->shipping_reference,
            'client' => [
                'name' => $s->client->name,
                'phone' => $s->client->phone,
            ],
            'receivers' => $s->receivers->map(fn ($r) => [
                'name' => $r->name,
                'contact' => $r->phone,
                'location' => $r->city?->name ?? $r->address,
                'items' => $r->items->map(fn ($i) => [
                    'product' => $i->product->name,
                    'quantity' => $i->quantity,
                    'description' => $i->description,
                ]),
            ]),
            'status' => $s->status,
            'total' => $s->total,
            'paid' => $s->paid,
        ]),
    ];
}
```

#### 7.1.2 Shipments by Year Report

```php
public function shipmentsByYear(int $year): array
{
    $yearSuffix = substr($year, -2); // 2026 -> 26

    $shipments = Shipment::where('shipping_reference', 'like', "%-{$yearSuffix}-%")
        ->with(['client', 'expenses'])
        ->get();

    return [
        'year' => $year,
        'total_shipments' => $shipments->count(),
        'monthly_breakdown' => $shipments->groupBy(fn ($s) => $s->shipped_at?->format('F')),
        'total_revenue' => $shipments->sum('total'),
        'total_expenses' => $shipments->sum('total_expenses_usd'),
        'net_profit' => $shipments->sum('total') - $shipments->sum('total_expenses_usd'),
    ];
}
```

#### 7.1.3 Shipments by Container Sequence Report

```php
public function shipmentsByContainerSequence(int $year, int $sequence): array
{
    $yearSuffix = substr($year, -2);
    $seqCode = "C{$sequence}";

    // Pattern: CON*-26-C2-*
    $shipments = Shipment::where('shipping_reference', 'like', "%-{$yearSuffix}-{$seqCode}-%")
        ->with(['client', 'receivers', 'expenses'])
        ->get();

    return [
        'year' => $year,
        'container_sequence' => $seqCode,
        'shipments' => $shipments,
    ];
}
```

#### 7.1.4 Container Detail Report (with client/receiver breakdown)

```php
public function containerShipmentDetail(string $reference): array
{
    // Parse reference: CON51-26-C2-002
    $parsed = Shipment::parseShippingReferenceExtended($reference);

    $shipment = Shipment::where('shipping_reference', $reference)
        ->with(['client', 'receivers.items.product', 'expenses.category', 'payments'])
        ->first();

    return [
        'reference' => $reference,
        'container_info' => $parsed,
        'client' => [
            'name' => strtoupper($shipment->client->name),
            'phone' => $shipment->client->phone,
        ],
        'receivers' => $shipment->receivers->map(fn ($r) => [
            'name' => $r->name ?: 'SELF',
            'contact' => $r->phone,
            'location' => strtoupper($r->city?->name ?? $r->address),
            'items' => $r->items,
        ]),
        'financials' => [
            'total' => $shipment->total,
            'paid' => $shipment->paid,
            'balance' => $shipment->total - $shipment->paid,
            'expenses_usd' => $shipment->total_expenses_usd,
            'expenses_ghs' => $shipment->total_expenses_ghs,
            'net_profit' => $shipment->net_profit_usd,
        ],
    ];
}
```

#### 7.1.5 Profit & Loss Report

```php
public function profitLossReport(Carbon $startDate, Carbon $endDate): array
{
    $shipments = Shipment::whereBetween('created_at', [$startDate, $endDate])->get();
    $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->get();
    $externalIncome = Income::where('status', 'received')
        ->whereBetween('income_date', [$startDate, $endDate])->get();
    $payroll = PayrollEntry::whereHas('payrollPeriod', fn ($q) =>
        $q->whereBetween('pay_date', [$startDate, $endDate])
    )->sum('net_pay');

    $totalRevenue = $shipments->sum('total') + $externalIncome->sum('amount_usd');

    return [
        'period' => ['start' => $startDate, 'end' => $endDate],
        'revenue' => [
            'shipment_revenue' => $shipments->sum('total'),
            'external_income' => $externalIncome->sum('amount_usd'),
            'total_revenue' => $totalRevenue,
            'collected' => $shipments->sum('paid') + $externalIncome->sum('amount_usd'),
            'outstanding' => $shipments->sum('total') - $shipments->sum('paid'),
        ],
        'expenses' => [
            'shipment_expenses' => $expenses->sum('amount_usd'),
            'payroll' => $payroll,
            'total' => $expenses->sum('amount_usd') + $payroll,
        ],
        'profit_loss' => [
            'gross_profit' => $totalRevenue - $expenses->sum('amount_usd'),
            'net_profit' => $totalRevenue - $expenses->sum('amount_usd') - $payroll,
        ],
    ];
}
```

#### 7.1.7 External Income Report

```php
public function externalIncomeReport(Carbon $startDate, Carbon $endDate): array
{
    $incomes = Income::where('status', 'received')
        ->whereBetween('income_date', [$startDate, $endDate])
        ->with(['category', 'shipment', 'recordedBy'])
        ->get();

    return [
        'period' => ['start' => $startDate, 'end' => $endDate],
        'total_usd' => $incomes->sum('amount_usd'),
        'total_ghs' => $incomes->sum('amount_ghs'),
        'by_category' => $incomes->groupBy('category.name')->map(fn ($items) => [
            'count' => $items->count(),
            'total_usd' => $items->sum('amount_usd'),
            'total_ghs' => $items->sum('amount_ghs'),
        ]),
        'by_payment_method' => $incomes->groupBy('payment_method')->map(fn ($items) => [
            'count' => $items->count(),
            'total_usd' => $items->sum('amount_usd'),
        ]),
        'monthly_breakdown' => $incomes->groupBy(fn ($i) => $i->income_date->format('F Y')),
        'entries' => $incomes,
    ];
}
```

#### 7.1.6 Client Growth Report

```php
public function clientGrowthReport(int $year): array
{
    $clients = Client::whereYear('created_at', $year)
        ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
        ->groupBy('month')
        ->get();

    $yearlyTotal = Client::whereYear('created_at', $year)->count();
    $previousYearTotal = Client::whereYear('created_at', $year - 1)->count();

    return [
        'year' => $year,
        'total_new_clients' => $yearlyTotal,
        'previous_year_total' => $previousYearTotal,
        'growth_rate' => $previousYearTotal > 0
            ? (($yearlyTotal - $previousYearTotal) / $previousYearTotal) * 100
            : 100,
        'monthly_breakdown' => $clients,
    ];
}
```

### 7.2 Report PDF Generation

Create Blade templates for each report type:

- `resources/views/reports/shipments-by-container.blade.php`
- `resources/views/reports/profit-loss.blade.php`
- `resources/views/reports/client-growth.blade.php`
- `resources/views/reports/expense-analysis.blade.php`
- `resources/views/reports/payroll-summary.blade.php`
- `resources/views/reports/container-detail.blade.php`
- `resources/views/reports/external-income.blade.php`

Use existing DomPDF integration for PDF generation.

#### Report Views (7 files)
- `resources/views/reports/shipments-by-container.blade.php`
- `resources/views/reports/profit-loss.blade.php`
- `resources/views/reports/client-growth.blade.php`
- `resources/views/reports/expense-analysis.blade.php`
- `resources/views/reports/payroll-summary.blade.php`
- `resources/views/reports/container-detail.blade.php`
- `resources/views/reports/external-income.blade.php`

---

## Phase 8: Dashboard Widgets & Analytics

### 8.1 Financial Dashboard Widgets

#### Revenue Overview Widget
```php
class RevenueOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Revenue', '$' . number_format(Shipment::sum('total'), 2)),
            Stat::make('Collected', '$' . number_format(Shipment::sum('paid'), 2)),
            Stat::make('Outstanding', '$' . number_format(
                Shipment::sum('total') - Shipment::sum('paid'), 2
            )),
        ];
    }
}
```

#### Expense Overview Widget
```php
class ExpenseOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Expenses (USD)', '$' . number_format(Expense::sum('amount_usd'), 2)),
            Stat::make('Total Expenses (GHS)', 'GH₵' . number_format(Expense::sum('amount_ghs'), 2)),
            Stat::make('This Month', '$' . number_format(
                Expense::whereMonth('expense_date', now()->month)->sum('amount_usd'), 2
            )),
        ];
    }
}
```

#### Profit/Loss Chart Widget
```php
class ProfitLossChartWidget extends ChartWidget
{
    // Monthly profit/loss trend chart
    // Compare revenue vs expenses over 12 months
}
```

#### Client Growth Chart Widget
```php
class ClientGrowthChartWidget extends ChartWidget
{
    // Bar chart showing new clients per month
    // Year-over-year comparison
}
```

### 8.2 Exchange Rate Display

Add current exchange rate to dashboard header:

```php
class ExchangeRateWidget extends Widget
{
    protected static string $view = 'filament.widgets.exchange-rate';

    public function getCurrentRate(): float
    {
        return app(ExchangeRateService::class)->getCurrentRate();
    }
}
```

---

## Phase 9: Implementation Order

### Step-by-Step Implementation Sequence

#### Week 1: Foundation
1. Create all database migrations
2. Create all new models
3. Update existing models (Shipment, Staff, User)
4. Run migrations
5. Create seeders for staff roles and expense categories

#### Week 2: Core Services
1. Implement ExchangeRateService
2. Implement ExpenseService
3. Implement PayrollService
4. Implement ReportService
5. Implement StaffAccountService
6. Write unit tests for services

#### Week 3: Admin Resources - Part 1
1. Create ExpenseCategoryResource
2. Create ExpenseResource
3. Create StaffRoleResource
4. Update StaffResource with role/account integration
5. Add ExpensesRelationManager to ShipmentResource

#### Week 4: Admin Resources - Part 2
1. Create PayrollPeriodResource
2. Create PayrollEntryResource
3. Create ReportResource
4. Create ReportDashboard page
5. Create FinancialDashboard page

#### Week 5: Reports & Analytics
1. Implement all report types in ReportService
2. Create report Blade templates
3. Implement PDF generation for reports
4. Create dashboard widgets
5. Integrate exchange rate display

#### Week 6: Testing & Refinement
1. End-to-end testing
2. UI/UX refinement
3. Permission configuration
4. Documentation
5. Deployment

---

## Phase 10: Files to Create/Modify

### New Files

#### Migrations (10 files)
- `database/migrations/YYYY_MM_DD_create_expense_categories_table.php`
- `database/migrations/YYYY_MM_DD_create_expenses_table.php`
- `database/migrations/YYYY_MM_DD_create_exchange_rate_logs_table.php`
- `database/migrations/YYYY_MM_DD_create_income_categories_table.php`
- `database/migrations/YYYY_MM_DD_create_incomes_table.php`
- `database/migrations/YYYY_MM_DD_create_staff_roles_table.php`
- `database/migrations/YYYY_MM_DD_add_columns_to_staff_table.php`
- `database/migrations/YYYY_MM_DD_create_payroll_periods_table.php`
- `database/migrations/YYYY_MM_DD_create_payroll_entries_table.php`
- `database/migrations/YYYY_MM_DD_create_reports_table.php`

#### Models (9 files)
- `app/Models/ExpenseCategory.php`
- `app/Models/Expense.php`
- `app/Models/ExchangeRateLog.php`
- `app/Models/IncomeCategory.php`
- `app/Models/Income.php`
- `app/Models/StaffRole.php`
- `app/Models/PayrollPeriod.php`
- `app/Models/PayrollEntry.php`
- `app/Models/Report.php`

#### Services (6 files)
- `app/Services/ExchangeRateService.php`
- `app/Services/ExpenseService.php`
- `app/Services/IncomeService.php`
- `app/Services/PayrollService.php`
- `app/Services/ReportService.php`
- `app/Services/StaffAccountService.php`

#### Filament Resources (8 files)
- `app/Filament/Resources/ExpenseCategoryResource.php`
- `app/Filament/Resources/ExpenseResource.php`
- `app/Filament/Resources/IncomeCategoryResource.php`
- `app/Filament/Resources/IncomeResource.php`
- `app/Filament/Resources/StaffRoleResource.php`
- `app/Filament/Resources/PayrollPeriodResource.php`
- `app/Filament/Resources/PayrollEntryResource.php`
- `app/Filament/Resources/ReportResource.php`

#### Filament Relation Managers (1 file)
- `app/Filament/Resources/ShipmentResource/RelationManagers/ExpensesRelationManager.php`

#### Filament Pages (2 files)
- `app/Filament/Pages/ReportDashboard.php`
- `app/Filament/Pages/FinancialDashboard.php`

#### Filament Widgets (7 files)
- `app/Filament/Widgets/RevenueOverviewWidget.php`
- `app/Filament/Widgets/ExpenseOverviewWidget.php`
- `app/Filament/Widgets/IncomeOverviewWidget.php`
- `app/Filament/Widgets/ProfitLossChartWidget.php`
- `app/Filament/Widgets/ClientGrowthChartWidget.php`
- `app/Filament/Widgets/PayrollOverviewWidget.php`
- `app/Filament/Widgets/ExchangeRateWidget.php`

#### Report Views (6 files)
- `resources/views/reports/shipments-by-container.blade.php`
- `resources/views/reports/profit-loss.blade.php`
- `resources/views/reports/client-growth.blade.php`
- `resources/views/reports/expense-analysis.blade.php`
- `resources/views/reports/payroll-summary.blade.php`
- `resources/views/reports/container-detail.blade.php`

#### Seeders (3 files)
- `database/seeders/StaffRoleSeeder.php`
- `database/seeders/ExpenseCategorySeeder.php`
- `database/seeders/IncomeCategorySeeder.php`

#### Enums (2 files)
- `app/Enums/ExpenseStage.php`
- `app/Enums/PayrollStatus.php`

### Files to Modify

- `app/Models/Shipment.php` - Add expense relationships and financial methods
- `app/Models/Staff.php` - Add role and user account relationships
- `app/Models/User.php` - Add staff relationship
- `app/Filament/Resources/StaffResource.php` - Add role selection and account creation
- `app/Filament/Resources/ShipmentResource.php` - Add expense relation manager
- `database/seeders/DatabaseSeeder.php` - Include new seeders

---

## Summary

This implementation plan covers:

1. **Expense Tracking** - Full expense management per shipment with USD/GHS conversion
2. **External Income Tracking** - Record income from external sources with categorization
3. **Exchange Rate Integration** - Automatic rate fetching and historical logging
4. **Staff Management** - Roles (CEO, Accountant, Driver, etc.) with user accounts
5. **Payroll System** - Complete payroll periods, entries, and processing
6. **Reporting System** - 8+ report types with PDF generation
7. **Financial Analytics** - Dashboard widgets for profit/loss, revenue, expenses, income
8. **Client Analytics** - Growth tracking per year/month

**Total new files:** ~52 files
**Total modified files:** ~6 files
**Estimated tables:** 10 new tables + 1 modified

### Income Categories (Default)

The following income categories will be seeded:

```php
$categories = [
    ['name' => 'Consulting Services', 'code' => 'CONSULTING'],
    ['name' => 'Equipment Rental', 'code' => 'RENTAL'],
    ['name' => 'Commission', 'code' => 'COMMISSION'],
    ['name' => 'Interest Income', 'code' => 'INTEREST'],
    ['name' => 'Insurance Refund', 'code' => 'INSURANCE_REFUND'],
    ['name' => 'Customs Refund', 'code' => 'CUSTOMS_REFUND'],
    ['name' => 'Late Payment Fees', 'code' => 'LATE_FEES'],
    ['name' => 'Storage Fees', 'code' => 'STORAGE'],
    ['name' => 'Documentation Fees', 'code' => 'DOCUMENTATION'],
    ['name' => 'Other Income', 'code' => 'OTHER'],
];
```

The system will enable comprehensive financial tracking to determine company profitability and growth, including all income streams (shipments and external sources) versus all expenses (operational and payroll).

---

## Phase 11: Additional Strategic Features (Recommended Enhancements)

The following features are recommended to make the system more comprehensive, user-friendly, and strategically valuable.

### 11.1 Driver & Fleet Management

#### `vehicles` Table
Track company vehicles/trucks used for deliveries.

```php
Schema::create('vehicles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('branch_id')->constrained('branches');

    $table->string('registration_number')->unique();  // License plate
    $table->string('vehicle_type');                   // Truck, Van, Motorcycle
    $table->string('make')->nullable();               // Toyota, Mercedes, etc.
    $table->string('model')->nullable();
    $table->year('year')->nullable();
    $table->string('color')->nullable();

    // Capacity
    $table->decimal('max_weight_kg', 10, 2)->nullable();
    $table->decimal('max_volume_cbm', 10, 2)->nullable();  // Cubic meters

    // Status
    $table->enum('status', ['available', 'in_use', 'maintenance', 'retired'])->default('available');

    // Insurance & Documentation
    $table->date('insurance_expiry')->nullable();
    $table->date('roadworthy_expiry')->nullable();
    $table->date('registration_expiry')->nullable();

    // Maintenance tracking
    $table->date('last_service_date')->nullable();
    $table->integer('last_service_mileage')->nullable();
    $table->integer('current_mileage')->nullable();
    $table->date('next_service_due')->nullable();

    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### `trips` Table
Track delivery trips with drivers and vehicles.

```php
Schema::create('trips', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('branch_id')->constrained('branches');
    $table->foreignUuid('vehicle_id')->constrained('vehicles');
    $table->foreignUuid('driver_id')->constrained('staff');        // Primary driver
    $table->foreignUuid('assistant_id')->nullable()->constrained('staff');  // Helper/assistant

    $table->string('trip_reference')->unique();       // TRIP-YYYYMMDD-XXX

    // Route information
    $table->string('origin');
    $table->string('destination');
    $table->text('route_description')->nullable();
    $table->decimal('distance_km', 10, 2)->nullable();

    // Timing
    $table->dateTime('scheduled_departure');
    $table->dateTime('scheduled_arrival')->nullable();
    $table->dateTime('actual_departure')->nullable();
    $table->dateTime('actual_arrival')->nullable();

    // Status
    $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'delayed'])->default('scheduled');

    // Costs
    $table->decimal('fuel_cost', 10, 2)->default(0);
    $table->decimal('toll_fees', 10, 2)->default(0);
    $table->decimal('driver_allowance', 10, 2)->default(0);
    $table->decimal('other_costs', 10, 2)->default(0);
    $table->decimal('total_cost', 10, 2)->default(0);

    // Mileage
    $table->integer('start_mileage')->nullable();
    $table->integer('end_mileage')->nullable();

    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### `trip_shipments` Pivot Table
Link shipments to trips (many-to-many).

```php
Schema::create('trip_shipments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('trip_id')->constrained('trips')->cascadeOnDelete();
    $table->foreignUuid('shipment_id')->constrained('shipments')->cascadeOnDelete();
    $table->enum('delivery_status', ['pending', 'delivered', 'failed', 'partial'])->default('pending');
    $table->text('delivery_notes')->nullable();
    $table->dateTime('delivered_at')->nullable();
    $table->string('receiver_signature')->nullable();  // Signature image path
    $table->timestamps();

    $table->unique(['trip_id', 'shipment_id']);
});
```

#### `vehicle_maintenance` Table
Track vehicle maintenance history.

```php
Schema::create('vehicle_maintenances', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('vehicle_id')->constrained('vehicles');
    $table->foreignUuid('recorded_by')->constrained('users');

    $table->string('maintenance_type');           // Oil change, Tire replacement, Repair, etc.
    $table->text('description');
    $table->string('service_provider')->nullable();
    $table->decimal('cost', 10, 2);
    $table->integer('mileage_at_service')->nullable();
    $table->date('service_date');
    $table->date('next_service_date')->nullable();

    $table->string('receipt_path')->nullable();
    $table->text('notes')->nullable();

    $table->timestamps();
});
```

### 11.2 Strategic Dashboard & KPIs

#### `kpi_targets` Table
Set and track business targets.

```php
Schema::create('kpi_targets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('branch_id')->nullable()->constrained('branches');  // Null = company-wide

    $table->string('metric');                     // revenue, shipments, clients, expenses
    $table->enum('period_type', ['monthly', 'quarterly', 'yearly']);
    $table->integer('year');
    $table->integer('period');                    // Month (1-12), Quarter (1-4), or 1 for yearly
    $table->decimal('target_value', 15, 2);
    $table->decimal('achieved_value', 15, 2)->default(0);
    $table->decimal('achievement_percentage', 5, 2)->default(0);

    $table->timestamps();
});
```

#### Strategic Dashboard Widgets

**CEO Dashboard** - High-level business overview:
- Revenue vs Target (gauge chart)
- Profit Margin Trend (line chart)
- Top Performing Branches
- Cash Flow Summary
- Outstanding Receivables Aging
- Year-over-Year Growth Comparison
- Container Utilization Rate
- Average Revenue per Shipment

**Operations Dashboard**:
- Active Trips Map
- Driver Performance Metrics
- Vehicle Utilization
- Pending Deliveries
- On-Time Delivery Rate
- Container Fill Rate

### 11.3 Customer Relationship Management (CRM)

#### `client_interactions` Table
Track all client touchpoints.

```php
Schema::create('client_interactions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('client_id')->constrained('clients');
    $table->foreignUuid('staff_id')->constrained('staff');

    $table->enum('interaction_type', ['call', 'email', 'meeting', 'whatsapp', 'visit', 'complaint', 'inquiry']);
    $table->string('subject');
    $table->text('notes');
    $table->enum('outcome', ['positive', 'neutral', 'negative', 'follow_up_needed'])->nullable();
    $table->dateTime('follow_up_date')->nullable();
    $table->boolean('follow_up_completed')->default(false);

    $table->timestamps();
});
```

#### `client_ratings` Table
Rate clients for internal purposes.

```php
Schema::create('client_ratings', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('client_id')->constrained('clients');

    $table->enum('payment_reliability', ['excellent', 'good', 'average', 'poor'])->default('good');
    $table->enum('shipment_frequency', ['high', 'medium', 'low', 'inactive'])->default('medium');
    $table->decimal('total_lifetime_value', 15, 2)->default(0);
    $table->integer('total_shipments')->default(0);
    $table->boolean('is_vip')->default(false);
    $table->text('internal_notes')->nullable();

    $table->timestamps();
});
```

### 11.4 Notifications & Alerts System

#### `alerts` Table
System-wide alerts and notifications.

```php
Schema::create('alerts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('branch_id')->nullable()->constrained('branches');
    $table->foreignUuid('user_id')->nullable()->constrained('users');  // Null = all users

    $table->string('alert_type');                 // vehicle_maintenance, payment_due, license_expiry, etc.
    $table->string('title');
    $table->text('message');
    $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
    $table->string('related_model')->nullable();  // Vehicle, Payment, etc.
    $table->uuid('related_id')->nullable();

    $table->boolean('is_read')->default(false);
    $table->boolean('is_dismissed')->default(false);
    $table->timestamp('read_at')->nullable();

    $table->timestamps();
});
```

#### Automated Alerts:
- **Vehicle Alerts**: Insurance expiry (30, 14, 7 days), roadworthy expiry, service due
- **Payment Alerts**: Overdue payments (7, 14, 30, 60, 90 days)
- **Staff Alerts**: Contract expiry, work permit expiry
- **Inventory Alerts**: Low stock warnings
- **Shipment Alerts**: Delayed shipments, pending pickups

### 11.5 Document Management

#### `documents` Table
Centralized document storage.

```php
Schema::create('documents', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('branch_id')->nullable()->constrained('branches');
    $table->foreignUuid('uploaded_by')->constrained('users');

    $table->string('documentable_type');          // Shipment, Vehicle, Staff, Client
    $table->uuid('documentable_id');

    $table->string('title');
    $table->string('document_type');              // invoice, contract, license, receipt, etc.
    $table->string('file_path');
    $table->string('file_name');
    $table->string('mime_type');
    $table->integer('file_size');

    $table->date('expiry_date')->nullable();
    $table->boolean('is_archived')->default(false);

    $table->timestamps();
    $table->softDeletes();
});
```

### 11.6 Advanced Reporting

#### New Strategic Reports

1. **Profitability by Container Report**
   - Revenue per container
   - Expenses per container
   - Net profit per container
   - Container utilization rate

2. **Driver Performance Report**
   - Trips completed
   - On-time delivery rate
   - Fuel efficiency
   - Customer complaints
   - Revenue generated

3. **Vehicle Performance Report**
   - Trip count per vehicle
   - Fuel consumption
   - Maintenance costs
   - Revenue generated
   - Utilization percentage

4. **Receivables Aging Report**
   - 0-30 days outstanding
   - 31-60 days outstanding
   - 61-90 days outstanding
   - 90+ days outstanding
   - Bad debt risk assessment

5. **Route Analysis Report**
   - Most profitable routes
   - Route frequency
   - Average delivery time per route
   - Cost per kilometer

6. **Client Segmentation Report**
   - High-value clients
   - Frequent shippers
   - Declining clients
   - New client acquisition trend

7. **Comparative Period Report**
   - This month vs last month
   - This quarter vs last quarter
   - This year vs last year
   - Growth percentage trends

8. **Cash Flow Report**
   - Daily/weekly/monthly cash inflows
   - Cash outflows (expenses, payroll)
   - Net cash position
   - Projected cash flow

9. **Container Profitability Matrix**
   - Compare all containers side by side
   - Revenue, expenses, profit for each
   - Identify best/worst performing containers

10. **Seasonal Trend Report**
    - Shipment volume by month/season
    - Revenue patterns
    - Peak period identification
    - Capacity planning insights

### 11.7 Workflow Automation

#### `workflow_rules` Table
Automate repetitive tasks.

```php
Schema::create('workflow_rules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('branch_id')->nullable()->constrained('branches');

    $table->string('name');
    $table->string('trigger_event');              // shipment_created, payment_received, etc.
    $table->json('conditions')->nullable();       // When conditions
    $table->json('actions');                      // What to do
    $table->boolean('is_active')->default(true);

    $table->timestamps();
});
```

#### Example Automations:
- Auto-send SMS when shipment status changes
- Auto-generate invoice when shipment is created
- Auto-assign trips to available drivers
- Auto-escalate overdue payments
- Auto-notify when container is full

### 11.8 Mobile-Friendly Features

#### Driver Mobile App Features (Future Consideration):
- View assigned trips
- Update delivery status
- Capture proof of delivery (photo + signature)
- Report vehicle issues
- Log fuel purchases
- Real-time GPS tracking

#### Client Mobile Features:
- Track shipments in real-time
- View payment history
- Request quotes
- Submit new shipment requests

### 11.9 Audit Trail & Compliance

#### `audit_logs` Table
Track all system changes.

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->nullable()->constrained('users');

    $table->string('auditable_type');
    $table->uuid('auditable_id');
    $table->string('event');                      // created, updated, deleted
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();

    $table->timestamps();
});
```

### 11.10 Budget & Forecasting

#### `budgets` Table
Set and track budgets.

```php
Schema::create('budgets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('branch_id')->nullable()->constrained('branches');

    $table->string('category');                   // payroll, fuel, maintenance, marketing
    $table->enum('period_type', ['monthly', 'quarterly', 'yearly']);
    $table->integer('year');
    $table->integer('period');
    $table->decimal('budgeted_amount', 15, 2);
    $table->decimal('actual_amount', 15, 2)->default(0);
    $table->decimal('variance', 15, 2)->default(0);
    $table->decimal('variance_percentage', 5, 2)->default(0);

    $table->timestamps();
});
```

---

## Phase 12: Updated File Summary

### Additional New Files (Phase 11)

#### Migrations (10 additional files)
- `database/migrations/YYYY_MM_DD_create_vehicles_table.php`
- `database/migrations/YYYY_MM_DD_create_trips_table.php`
- `database/migrations/YYYY_MM_DD_create_trip_shipments_table.php`
- `database/migrations/YYYY_MM_DD_create_vehicle_maintenances_table.php`
- `database/migrations/YYYY_MM_DD_create_kpi_targets_table.php`
- `database/migrations/YYYY_MM_DD_create_client_interactions_table.php`
- `database/migrations/YYYY_MM_DD_create_client_ratings_table.php`
- `database/migrations/YYYY_MM_DD_create_alerts_table.php`
- `database/migrations/YYYY_MM_DD_create_documents_table.php`
- `database/migrations/YYYY_MM_DD_create_budgets_table.php`

#### Models (10 additional files)
- `app/Models/Vehicle.php`
- `app/Models/Trip.php`
- `app/Models/TripShipment.php`
- `app/Models/VehicleMaintenance.php`
- `app/Models/KpiTarget.php`
- `app/Models/ClientInteraction.php`
- `app/Models/ClientRating.php`
- `app/Models/Alert.php`
- `app/Models/Document.php`
- `app/Models/Budget.php`

#### Services (5 additional files)
- `app/Services/TripService.php`
- `app/Services/VehicleService.php`
- `app/Services/AlertService.php`
- `app/Services/KpiService.php`
- `app/Services/BudgetService.php`

#### Filament Resources (8 additional files)
- `app/Filament/Resources/VehicleResource.php`
- `app/Filament/Resources/TripResource.php`
- `app/Filament/Resources/VehicleMaintenanceResource.php`
- `app/Filament/Resources/ClientInteractionResource.php`
- `app/Filament/Resources/AlertResource.php`
- `app/Filament/Resources/DocumentResource.php`
- `app/Filament/Resources/KpiTargetResource.php`
- `app/Filament/Resources/BudgetResource.php`

#### Filament Pages (3 additional files)
- `app/Filament/Pages/CeoDashboard.php`
- `app/Filament/Pages/OperationsDashboard.php`
- `app/Filament/Pages/FleetManagement.php`

#### Filament Widgets (10 additional files)
- `app/Filament/Widgets/RevenueVsTargetWidget.php`
- `app/Filament/Widgets/ProfitMarginTrendWidget.php`
- `app/Filament/Widgets/TopBranchesWidget.php`
- `app/Filament/Widgets/ReceivablesAgingWidget.php`
- `app/Filament/Widgets/DriverPerformanceWidget.php`
- `app/Filament/Widgets/VehicleStatusWidget.php`
- `app/Filament/Widgets/ActiveTripsWidget.php`
- `app/Filament/Widgets/AlertsWidget.php`
- `app/Filament/Widgets/BudgetVarianceWidget.php`
- `app/Filament/Widgets/CashFlowWidget.php`

#### Report Views (10 additional files)
- `resources/views/reports/driver-performance.blade.php`
- `resources/views/reports/vehicle-performance.blade.php`
- `resources/views/reports/receivables-aging.blade.php`
- `resources/views/reports/route-analysis.blade.php`
- `resources/views/reports/client-segmentation.blade.php`
- `resources/views/reports/comparative-period.blade.php`
- `resources/views/reports/cash-flow.blade.php`
- `resources/views/reports/container-profitability.blade.php`
- `resources/views/reports/seasonal-trend.blade.php`
- `resources/views/reports/budget-variance.blade.php`

---

## Updated Grand Total

### Original Features (Phases 1-10)
- **Tables:** 10 new + 1 modified
- **Files:** ~52 files

### Additional Features (Phases 11-12)
- **Tables:** 10 new
- **Files:** ~56 files

### Grand Total
- **New Tables:** 20
- **Modified Tables:** 1
- **Total New Files:** ~108 files

---

## Recommended Implementation Priority

### Priority 1 (Core - Must Have)
1. Expense tracking per shipment
2. Income tracking
3. Staff roles & payroll
4. Basic reporting

### Priority 2 (Important - Should Have)
1. Driver & fleet management
2. Trip tracking
3. Advanced financial reports
4. Alerts system

### Priority 3 (Nice to Have - Future)
1. CRM features
2. Budget & forecasting
3. Workflow automation
4. Mobile app features
5. Real-time GPS tracking

This phased approach allows you to launch with core features and incrementally add advanced capabilities based on business needs and user feedback.
