<?php

namespace Tests\Feature;

use App\Models\EcommerceOrder;
use App\Models\User;
use App\Notifications\Ecommerce\EcommerceOrderApproved;
use App\Notifications\Ecommerce\EcommerceOrderCancelled;
use App\Notifications\Ecommerce\EcommerceOrderDelivered;
use App\Notifications\Ecommerce\EcommerceOrderDispatched;
use App\Notifications\Ecommerce\EcommerceOrderPacked;
use App\Notifications\Ecommerce\EcommerceOrderPaymentConfirmed;
use App\Notifications\Ecommerce\EcommerceOrderPlaced;
use App\Notifications\Ecommerce\EcommerceOrderProcessing;
use App\Notifications\Ecommerce\EcommerceOrderRefunded;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Some SMS gateways drop the "_" in "?order_number=", which leaves the phone
 * with a link that stops at "?order" and opens an empty tracker. The order
 * number therefore goes in the path, which needs no special characters.
 */
class EcommerceOrderTrackingLinkTest extends TestCase
{
    public static function notifications(): array
    {
        return [
            'placed' => [EcommerceOrderPlaced::class],
            'approved' => [EcommerceOrderApproved::class],
            'payment confirmed' => [EcommerceOrderPaymentConfirmed::class],
            'processing' => [EcommerceOrderProcessing::class],
            'packed' => [EcommerceOrderPacked::class],
            'dispatched' => [EcommerceOrderDispatched::class],
            'delivered' => [EcommerceOrderDelivered::class],
            'cancelled' => [EcommerceOrderCancelled::class],
            'refunded' => [EcommerceOrderRefunded::class],
        ];
    }

    #[DataProvider('notifications')]
    public function test_sms_tracking_link_puts_the_order_number_in_the_path(string $notificationClass): void
    {
        config(['app.frontend_url' => 'https://kasarosemarket.com/']);

        $order = (new EcommerceOrder)->forceFill([
            'order_number' => 'KMB-20261105-0001',
            'total_ghs' => '200.00',
        ]);

        $notification = new $notificationClass($order);
        $notifiable = new User(['name' => 'Ama']);
        $expectedUrl = 'https://kasarosemarket.com/track-order/KMB-20261105-0001';

        $this->assertSame($expectedUrl, $notification->toMail($notifiable)->actionUrl);

        // Not every status SMS carries a link; the ones that do must use the path form.
        $sms = $notification->toSms($notifiable);
        if (str_contains($sms, 'track-order')) {
            $this->assertStringContainsString($expectedUrl, $sms);
            $this->assertStringNotContainsString('order_number', $sms);
            $this->assertStringNotContainsString('?', $sms);
        }
    }
}
