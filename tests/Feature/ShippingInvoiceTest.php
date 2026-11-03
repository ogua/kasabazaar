<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Product;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Service\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShippingInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::fake();

        $suffix = Str::random(8);
        $this->branch = Branch::create([
            'name' => 'Invoice Branch',
            'slug' => 'invoice-'.$suffix,
            'country' => 'USA',
            'state' => 'Ohio',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Josephine Arhin',
            'phone' => '03472812273',
            'email' => 'josephine-'.$suffix.'@example.com',
            'address' => '5400 Pacific Crest Ln',
        ]);

        $this->shipment = Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $this->branch->id,
            'origin_branch_id' => 'Ohio',
            'destination_branch_id' => 'Ghana',
            'status' => 'warehouse',
            'shipping_reference' => 'CON77-26-C43-001',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'shipping_cost' => 277,
            'discount' => 35,
            'vat' => 0,
            'insurance' => 0,
            'total' => 242,
        ]);

        $receiver = Receiver::create(['shipment_id' => $this->shipment->id, 'receiver_name' => 'Ruth Afari', 'receiver_phone' => '+233243852617']);
        ShipmentItem::create([
            'shipment_id' => $this->shipment->id,
            'receiver_id' => $receiver->id,
            'product_id' => Product::create(['name' => 'Box '.$suffix, 'category' => 'general', 'value' => 50])->id,
            'quantity' => 4,
            'item_cost' => 200,
        ]);
    }

    private function pay(float $amount): void
    {
        $this->shipment->payments()->create([
            'branch_id' => $this->branch->id, 'amount' => $amount, 'amount_usd' => $amount, 'currency' => 'USD',
            'paying_method' => 'CASH', 'paid_on' => now(), 'payment_ref' => 'PAY-'.Str::random(6), 'payment_type' => 'credit',
        ]);
    }

    public function test_the_invoice_shows_sender_route_receivers_and_charges(): void
    {
        $this->get(route('shipping-invoice', $this->shipment->id))
            ->assertOk()
            ->assertSeeInOrder([
                'INVOICE NO.', 'CON77-26-C43-001',
                'Sender &amp; Shipment', 'Josephine Arhin', 'Ohio', 'Ghana',
                'RECEIVER 1', 'Ruth Afari', '+233243852617', '$200.00',
                'Item / shipping charges', '$277.00', 'Discount', '$35.00',
            ], false)
            ->assertSee($this->shipment->tracking_number)
            ->assertSee('Insurance declined');
    }

    public function test_an_unpaid_invoice_shows_the_total_and_balance_due(): void
    {
        $this->pay(100);

        $this->get(route('shipping-invoice', $this->shipment->id))
            ->assertOk()
            ->assertDontSee('TOTAL PAID')
            ->assertSeeInOrder(['TOTAL', '$242.00', 'Amount paid', '$100.00', 'Balance due', '$142.00']);
    }

    public function test_a_fully_paid_invoice_says_total_paid(): void
    {
        $this->pay(242);

        $this->get(route('shipping-invoice', $this->shipment->id))
            ->assertOk()
            ->assertSeeInOrder(['TOTAL PAID', '$242.00'])
            ->assertDontSee('Balance due');
    }

    public function test_an_insured_shipment_shows_its_cover(): void
    {
        $this->shipment->update(['insurance_accepted' => true, 'insurance' => 50, 'total' => 292]);

        $this->get(route('shipping-invoice', $this->shipment->id))
            ->assertOk()
            ->assertSee('Insurance accepted')
            ->assertDontSee('Insurance declined');
    }

    public function test_the_invoice_renders_as_a_pdf(): void
    {
        $pdf = InvoiceService::generatePdf($this->shipment);

        $this->assertStringStartsWith('%PDF', $pdf->output());
        $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }
}
