<?php

namespace App\Filament\Pages;

use App\Enums\EcommerceOrderStatus;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderItem;
use App\Models\EcommerceProduct;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class EcommerceReportsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.ecommerce-reports';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'E-Commerce Reports';

    public ?string $period = 'monthly';

    public ?string $date_from = null;

    public ?string $date_to = null;

    public int $todayOrdersCount = 0;

    public float $thisMonthRevenue = 0;

    public int $pendingApprovalCount = 0;

    public int $awaitingPaymentCount = 0;

    public Collection $topProducts;

    public Collection $lowStockProducts;

    public function mount(): void
    {
        $this->date_from = now()->startOfMonth()->toDateString();
        $this->date_to = now()->endOfMonth()->toDateString();
        $this->topProducts = collect();
        $this->lowStockProducts = collect();

        $this->form->fill([
            'period' => 'monthly',
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ]);

        $this->loadData();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filters')
                    ->schema([
                        Select::make('period')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('monthly'),
                        DatePicker::make('date_from')
                            ->label('From'),
                        DatePicker::make('date_to')
                            ->label('To'),
                    ])
                    ->columns(3),
            ]);
    }

    public function applyFilters(): void
    {
        $data = $this->form->getState();
        $this->period = $data['period'];
        $this->date_from = $data['date_from'];
        $this->date_to = $data['date_to'];
        $this->loadData();
    }

    private function loadData(): void
    {
        $branchId = Filament::getTenant()?->id;
        $dateFrom = $this->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo = $this->date_to ?? now()->endOfMonth()->toDateString();

        $revenueStatuses = [
            EcommerceOrderStatus::Paid->value,
            EcommerceOrderStatus::Processing->value,
            EcommerceOrderStatus::Packed->value,
            EcommerceOrderStatus::Dispatched->value,
            EcommerceOrderStatus::InTransit->value,
            EcommerceOrderStatus::Delivered->value,
        ];

        $this->todayOrdersCount = EcommerceOrder::where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->count();

        $this->thisMonthRevenue = (float) EcommerceOrder::where('branch_id', $branchId)
            ->whereIn('status', $revenueStatuses)
            ->whereDate('created_at', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('created_at', '<=', now()->endOfMonth()->toDateString())
            ->sum('total_ghs');

        $this->pendingApprovalCount = EcommerceOrder::where('branch_id', $branchId)
            ->where('status', EcommerceOrderStatus::Pending->value)
            ->count();

        $this->awaitingPaymentCount = EcommerceOrder::where('branch_id', $branchId)
            ->where('status', EcommerceOrderStatus::AwaitingPayment->value)
            ->count();

        $this->topProducts = EcommerceOrderItem::query()
            ->join('ecommerce_orders', 'ecommerce_order_items.order_id', '=', 'ecommerce_orders.id')
            ->where('ecommerce_orders.branch_id', $branchId)
            ->whereDate('ecommerce_orders.created_at', '>=', $dateFrom)
            ->whereDate('ecommerce_orders.created_at', '<=', $dateTo)
            ->selectRaw('
                ecommerce_order_items.product_name,
                ecommerce_order_items.product_sku,
                SUM(ecommerce_order_items.quantity) as units_sold,
                SUM(ecommerce_order_items.total_ghs) as revenue_ghs
            ')
            ->groupBy('ecommerce_order_items.product_name', 'ecommerce_order_items.product_sku')
            ->orderByDesc('units_sold')
            ->limit(20)
            ->get();

        $this->lowStockProducts = EcommerceProduct::where('branch_id', $branchId)
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->orderBy('stock')
            ->get();
    }
}
