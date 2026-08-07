<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\OrdersApi;
use Livewire\Component;

class OrderDetail extends Component
{
    public array $order = [];

    public array $tracking = [];

    public int $ratingValue = 5;

    public string $ratingComment = '';

    public bool $rated = false;

    public function mount(string $order, OrdersApi $ordersApi): void
    {
        $this->order = $ordersApi->show($order);
        $this->tracking = $ordersApi->tracking($order);
    }

    public function cancel(OrdersApi $ordersApi): void
    {
        $ordersApi->cancel($this->order['id']);
        $this->order = $ordersApi->show($this->order['id']);
    }

    public function submitRating(OrdersApi $ordersApi): void
    {
        $ordersApi->rate($this->order['id'], $this->ratingValue, $this->ratingComment);
        $this->rated = true;
    }

    public function render()
    {
        return view('livewire.storefront.account.order-detail')->layout('storefront.layouts.app', [
            'title' => 'Order '.($this->order['order_number'] ?? ''),
        ]);
    }
}
