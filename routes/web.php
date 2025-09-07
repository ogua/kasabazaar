<?php

use App\Livewire\AgreementForm;
use App\Livewire\DisclaimerForm;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;
use App\Livewire\PaymentsuccessfulPage;
use App\Http\Controllers\ShippingController;

Route::get('/', function () {
    //return view('welcome');
    return view('web.home');
})
->name('home-page');

Route::get('/about-us',[WebController::class,'aboutUs'])
->name('about-us');

Route::get('/our-services',[WebController::class,'ourservices'])
->name('our-services');

Route::get('/our-projects',[WebController::class,'ourprojects'])
->name('our-projects');

Route::get('/tracking',[WebController::class,'tracking'])
->name('our-tracking');

Route::get('/contact-us',[WebController::class,'contactus'])
->name('contact-us');

Route::get('/news',[WebController::class,'ourblog'])
->name('our-blog');

Route::get('/news/{slug}',[WebController::class,'blogDetails'])
->name('blog-details');

Route::get('/packing-slip/{id}',[ShippingController::class,'packingslip'])
->name('packing-slip');

Route::get('/shipping-invoice/{id}',[ShippingController::class,'shippinginvoice'])
->name('shipping-invoice');

Route::get('/print-quotation/{record}',[ShippingController::class,'printquotation'])
->name('print-quotation');

Route::get('/make-payment/{record}',DisclaimerForm::class)
->name('make-payment');

Route::get('/make-payment-agreement/{record}',AgreementForm::class)
->name('make-payment-agreement');

Route::get('/paid-successfully',PaymentsuccessfulPage::class)
->name('paid-successfully');

