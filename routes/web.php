<?php

use App\Livewire\AgreementForm;
use App\Livewire\DisclaimerForm;
use App\Livewire\CustomerFeedaback;
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

Route::get('/feedback',[WebController::class,'feeback'])
->name('our-feeback');

Route::get('/contact-us',[WebController::class,'contactus'])
->name('contact-us');

Route::get('/news',[WebController::class,'ourblog'])
->name('our-blog');

Route::get('/news/{slug}',[WebController::class,'blogDetails'])
->name('blog-details');

// Route::get('/sitemap',[WebController::class,'sitemap'])
// ->name('sitemap');

Route::get('/packing-slip/{id}',[ShippingController::class,'packingslip'])
->name('packing-slip');

Route::get('/shipping-invoice/{id}',[ShippingController::class,'shippinginvoice'])
->name('shipping-invoice');

Route::get('/shipping-invoice-pdf/{id}',[ShippingController::class,'shippinginvoicepdf'])
->name('shipping-invoice-pdf');

Route::get('/shipping-invoice-download/{id}',[ShippingController::class,'downloadinvoicepdf'])
->name('shipping-invoice-download');

Route::get('/send-invoice-email/{id}',[ShippingController::class,'sendinvoiceemail'])
->name('send-invoice-email');

Route::get('/shipping-receipt/{id}',[ShippingController::class,'shippingreceipt'])
->name('shipping-receipt');

Route::get('/print-quotation/{record}',[ShippingController::class,'printquotation'])
->name('print-quotation');

Route::get('/make-payment/{record}',DisclaimerForm::class)
->name('make-payment');

Route::get('/make-payment-agreement/{record}',AgreementForm::class)
->name('make-payment-agreement');

Route::get('/paid-successfully',PaymentsuccessfulPage::class)
->name('paid-successfully');

Route::get('/customer-feedback',CustomerFeedaback::class)
->name('customer-feedback');

