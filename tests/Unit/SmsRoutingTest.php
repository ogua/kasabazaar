<?php

namespace Tests\Unit;

use App\Service\NotificationService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmsRoutingTest extends TestCase
{
    #[DataProvider('numbers')]
    public function test_resolves_the_expected_sms_driver(string $phone, string $expected): void
    {
        $this->assertSame($expected, NotificationService::resolveSmsDriverForPhone($phone));
    }

    public static function numbers(): array
    {
        return [
            'ghana international' => ['+233201234567', 'arkesel'],
            'ghana international no plus' => ['233201234567', 'arkesel'],
            'ghana local' => ['0201234567', 'arkesel'],
            'ghana local spaced' => ['024 123 4567', 'arkesel'],
            'ghana bare subscriber' => ['201234567', 'arkesel'],
            'us number' => ['+14155550123', 'twilio'],
            'us number no plus' => ['12125550123', 'twilio'],
            'uk number' => ['+442071838750', 'twilio'],
        ];
    }
}
