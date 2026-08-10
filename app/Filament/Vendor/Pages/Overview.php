<?php

namespace App\Filament\Vendor\Pages;

use App\Services\Kasabazaar\VendorApi;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;

class Overview extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected string $view = 'filament.vendor.pages.overview';

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public array $summary = [];

    public array $recentOrders = [];

    public function mount(VendorApi $vendorApi): void
    {
        $this->summary = $vendorApi->earningsSummary();
        $this->recentOrders = $vendorApi->orders(['per_page' => 5])->data;
    }
}
