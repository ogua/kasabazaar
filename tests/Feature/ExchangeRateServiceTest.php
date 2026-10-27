<?php

namespace Tests\Feature;

use App\Models\ExchangeRateLog;
use App\Service\ExchangeRateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Which USD→GHS rate counts as "current" — used for new shipments, payments,
 * expenses, the dashboards and cedi checkouts.
 */
class ExchangeRateServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ExchangeRateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        ExchangeRateLog::query()->delete();
        Cache::forget(ExchangeRateService::cacheKey('USD', 'GHS'));
        $this->service = app(ExchangeRateService::class);
    }

    public function test_a_newer_api_rate_replaces_an_old_manual_rate(): void
    {
        $this->rate(10, now()->subMonth(), 'manual');
        $this->rate(11.6714, today(), 'api');

        $this->assertSame(11.6714, $this->service->getCurrentRate());
    }

    public function test_a_manual_rate_for_today_wins_over_todays_api_rate(): void
    {
        $this->rate(11.6220, today()->subDay(), 'api');
        $this->service->setTodayRate(12.05);

        $this->assertSame(12.05, $this->service->getCurrentRate());
    }

    public function test_the_daily_sync_does_not_overwrite_a_rate_staff_set_today(): void
    {
        $this->service->setTodayRate(12.05);
        $this->service->logRate(11.67, 'USD', 'GHS', 'api');

        $today = ExchangeRateLog::whereDate('rate_date', today())->sole();
        $this->assertSame('manual', $today->source);
        $this->assertEquals(12.05, (float) $today->rate);
    }

    public function test_the_daily_sync_still_updates_its_own_rate_for_today(): void
    {
        $this->service->logRate(11.60, 'USD', 'GHS', 'api');
        $this->service->logRate(11.67, 'USD', 'GHS', 'api');

        $this->assertEquals(11.67, (float) ExchangeRateLog::whereDate('rate_date', today())->sole()->rate);
    }

    public function test_a_future_dated_rate_is_not_used_yet(): void
    {
        $this->rate(11.67, today(), 'api');
        $this->rate(13, today()->addDay(), 'manual');

        $this->assertSame(11.67, $this->service->getCurrentRate());
    }

    public function test_editing_or_deleting_a_rate_takes_effect_immediately(): void
    {
        $log = $this->rate(11.67, today(), 'api');
        $this->rate(11.50, today()->subDay(), 'api');

        $this->assertSame(11.67, $this->service->getCurrentRate());

        $log->update(['rate' => 11.80]);
        $this->assertSame(11.8, $this->service->getCurrentRate());

        $log->delete();
        $this->assertSame(11.5, $this->service->getCurrentRate());
    }

    private function rate(float $rate, $date, string $source): ExchangeRateLog
    {
        return ExchangeRateLog::create([
            'from_currency' => 'USD',
            'to_currency' => 'GHS',
            'rate' => $rate,
            'rate_date' => $date,
            'source' => $source,
        ]);
    }
}
