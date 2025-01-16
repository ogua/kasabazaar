<?php

use App\Http\Controllers\ShippingController;
use App\Livewire\DisclaimerForm;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/packing-slip/{id}',[ShippingController::class,'packingslip'])
->name('packing-slip');

Route::get('/shipping-invoice/{id}',[ShippingController::class,'shippinginvoice'])
->name('shipping-invoice');

Route::get('/print-quotation/{record}',[ShippingController::class,'printquotation'])
->name('print-quotation');

Route::get('/make-payment/{record}',DisclaimerForm::class)
->name('make-payment');

Route::get('/make-payment-agreement/{record}',DisclaimerForm::class)
->name('make-payment-agreement');

