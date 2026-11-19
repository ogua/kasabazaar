<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Shipment;
use App\Services\PublicInvestorMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The public investors page (/investors) and the aggregates behind its charts.
 */
class InvestorsPageTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    private ?Client $shipper = null;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();
        Http::fake();
        Cache::forget(PublicInvestorMetrics::CACHE_KEY);

        $suffix = Str::random(8);
        $this->branch = Branch::create([
            'name' => 'Investor Test Branch',
            'slug' => 'investor-test-'.$suffix,
            'country' => 'USA',
            'state' => 'Michigan',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);
    }

    public function test_the_metrics_count_clients_and_billed_revenue_by_month(): void
    {
        $now = CarbonImmutable::parse('2026-11-18 12:00:00');
        $baseline = app(PublicInvestorMetrics::class)->summary($now);

        $this->client('2026-10-05');
        $this->client('2026-11-02');
        $this->client('2026-11-10');
        $this->shipment('2026-11-03', 1200);
        $this->shipment('2026-11-12', 800);
        $this->shipment('2026-11-14', 5000, 'cancelled');
        $this->shipment('2025-11-20', 1000);

        $summary = app(PublicInvestorMetrics::class)->summary($now);
        $november = collect($summary['months'])->firstWhere('key', '2026-11');
        $baselineNovember = collect($baseline['months'])->firstWhere('key', '2026-11');

        $this->assertCount(24, $summary['months']);
        $this->assertSame('2026-11', end($summary['months'])['key']);
        $this->assertSame('Nov 2026', $november['label']);
        $this->assertSame(2, $november['new_clients'] - $baselineNovember['new_clients']);
        // The three new clients plus the long-standing shipper behind the shipments.
        $this->assertSame(4, $november['clients_total'] - $baselineNovember['clients_total']);
        $this->assertEqualsWithDelta(2000.0, $november['revenue'] - $baselineNovember['revenue'], 0.001, 'Cancelled shipments are not revenue.');
        $this->assertSame(4, $summary['kpis']['clients_total'] - $baseline['kpis']['clients_total']);
        $this->assertSame(3, $summary['kpis']['shipments_total'] - $baseline['kpis']['shipments_total'], 'Cancelled shipments are not counted.');
        $this->assertSame(12, $summary['kpis']['states_served']);
    }

    public function test_growth_compares_two_windows_and_has_no_value_without_a_base(): void
    {
        $this->assertSame(50.0, PublicInvestorMetrics::growth(150, 100));
        $this->assertSame(-25.0, PublicInvestorMetrics::growth(75, 100));
        $this->assertNull(PublicInvestorMetrics::growth(10, 0));
    }

    public function test_money_and_counts_are_compacted_for_ticks_and_tiles(): void
    {
        $this->assertSame('$950', PublicInvestorMetrics::compactMoney(950));
        $this->assertSame('$48.5K', PublicInvestorMetrics::compactMoney(48_500));
        $this->assertSame('$2K', PublicInvestorMetrics::compactMoney(2_000));
        $this->assertSame('$1.2M', PublicInvestorMetrics::compactMoney(1_234_567));
        $this->assertSame('12K', PublicInvestorMetrics::compactNumber(12_000));
    }

    public function test_the_page_shows_both_charts_with_a_table_view_and_the_disclaimer(): void
    {
        $this->client(now()->toDateString());
        $this->shipment(now()->toDateString(), 1500);

        $this->get('/investors')
            ->assertOk()
            ->assertSee('id="clients-chart"', false)
            ->assertSee('id="revenue-chart"', false)
            ->assertSee('class="ksr-chart__bar"', false)
            ->assertSee('class="ksr-chart__line"', false)
            ->assertSee('View as table')
            ->assertSee('Billed shipment value in US dollars')
            ->assertSee(asset('site/js/charts.js'), false)
            ->assertSee('Nothing on this')
            ->assertSee(url('/investor'), false);
    }

    public function test_revenue_figures_can_be_hidden_while_growth_stays_visible(): void
    {
        config(['website.investors.show_revenue_figures' => false]);
        $this->shipment(now()->toDateString(), 123_456);

        $this->get('/investors')
            ->assertOk()
            ->assertSee('share of the best month')
            ->assertSee('100% of best month')
            ->assertDontSee('$123,456')
            ->assertDontSee('Billed shipment value in US dollars');
    }

    public function test_the_homepage_shows_the_growth_charts_and_links_to_the_investors_page(): void
    {
        $this->shipment(now()->toDateString(), 1500);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Our Growth', 'id="clients-chart"', 'id="revenue-chart"', 'See Investor Figures'], false)
            ->assertSee(route('investors'), false)
            ->assertSee(asset('site/css/investors.css'), false)
            ->assertSee(asset('site/js/charts.js'), false);
    }

    public function test_the_homepage_respects_the_hidden_revenue_setting(): void
    {
        config(['website.investors.show_revenue_figures' => false]);
        $this->shipment(now()->toDateString(), 123_456);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="revenue-chart"', false)
            ->assertDontSee('$123,456');
    }

    public function test_the_homepage_still_renders_when_the_figures_cannot_be_loaded(): void
    {
        Cache::shouldReceive('remember')->andThrow(new \RuntimeException('cache store unavailable'));

        $this->get('/')
            ->assertOk()
            ->assertSee('id="clients-chart"', false)
            ->assertSee('id="revenue-chart"', false);
    }

    public function test_the_page_is_linked_from_the_footer_and_listed_in_the_sitemap(): void
    {
        $this->get('/about-us')->assertOk()->assertSee(route('investors'), false);
        $this->get('/sitemap.xml')->assertOk()->assertSee(route('investors'), false);
    }

    private function client(string $date): Client
    {
        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Investor Test Client',
            'email' => 'client-'.Str::random(8).'@example.com',
            'phone' => '+2332000'.random_int(10000, 99999),
        ]);
        $client->forceFill(['created_at' => $date.' 09:00:00'])->saveQuietly();

        return $client;
    }

    private function shipment(string $date, float $total, string $status = 'shipped'): Shipment
    {
        $shipment = Shipment::create([
            'client_id' => ($this->shipper ??= $this->client('2020-01-15'))->id,
            'branch_id' => $this->branch->id,
            'origin_branch_id' => 'Michigan',
            'destination_branch_id' => 'Ghana',
            'status' => $status,
            'tracking_number' => Shipment::generateTrackingNumber(),
            'total' => $total,
        ]);
        $shipment->forceFill(['created_at' => $date.' 10:00:00'])->saveQuietly();

        return $shipment;
    }
}
