<?php

use App\Services\Kasabazaar\CheckoutApi;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.kasabazaar.base_url' => 'https://kasabazaar.test/api/v1']);
});

it('passes the shopper\'s WhatsApp opt-in to the kasabazaar checkout', function (bool $optIn) {
    Http::fake(['kasabazaar.test/*' => Http::response(['success' => true, 'data' => ['id' => 'grp-1']], 201)]);

    app(CheckoutApi::class)->checkout('addr-1', 'Leave at gate', $optIn);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/marketplace/checkout')
        && $request['delivery_address_id'] === 'addr-1'
        && $request['whatsapp_opt_in'] === $optIn);
})->with([
    'opted in' => [true],
    'not opted in' => [false],
]);
