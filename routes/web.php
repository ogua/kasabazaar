<?php

use App\Http\Controllers\ExternalShipmentController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\InvestorReportController;
use App\Http\Controllers\PublicShipmentController;
use App\Http\Controllers\PublicShipmentRequestController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\TrackingPaymentController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WebsiteEnquiryController;
use App\Livewire\AgreementForm;
use App\Livewire\CustomerFeedaback;
use App\Livewire\DisclaimerForm;
use App\Livewire\PaymentsuccessfulPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website (kasarose.com)
|--------------------------------------------------------------------------
|
| The KASAROSE LOGISTICS site, ported from the plain-PHP "KasaRose Website"
| repo in October 2026 (views in resources/views/site). The route names
| predate the port and are used by emails, PDFs and the shipment portal.
|
*/

Route::view('/', 'site.index')->name('home-page');
Route::view('/about-us', 'site.about')->name('about-us');
Route::view('/our-companies', 'site.our-companies')->name('our-companies');
Route::view('/our-services', 'site.services')->name('our-services');
Route::view('/quote', 'site.quote')->name('quote');
Route::get('/tracking', [WebsiteController::class, 'tracking'])->name('our-tracking');

// Paying a shipment balance from the tracking page (USD, Paystack). The balance is
// only shown after a phone or email check; receipts are public to whoever holds the reference.
Route::prefix('tracking')->name('tracking-payment.')->controller(TrackingPaymentController::class)->group(function () {
    Route::get('payment/callback', 'callback')->name('callback');
    Route::get('payment/verify', 'verify')->middleware('throttle:30,1')->name('verify');
    Route::get('payment/{reference}', 'show')->middleware('throttle:30,1')->name('show');
    Route::get('payment/{reference}/pdf', 'pdf')->middleware('throttle:10,1')->name('pdf');
    Route::get('{trackingNumber}/pay', 'create')->middleware('throttle:30,1')->name('create');
    Route::post('{trackingNumber}/pay/confirm', 'confirmContact')->middleware('throttle:10,1')->name('confirm');
    Route::post('{trackingNumber}/pay', 'store')->middleware('throttle:10,1')->name('store');
});
Route::view('/contact-us', 'site.contact')->name('contact-us');
Route::view('/feedback', 'site.feedback')->name('our-feeback');
Route::get('/news', [WebsiteController::class, 'news'])->name('our-blog');
Route::get('/news/{slug}', [WebsiteController::class, 'newsDetail'])->name('blog-details');
Route::view('/our-projects', 'site.projects')->name('our-projects');
Route::view('/faq', 'site.faq')->name('faq');
Route::view('/privacy', 'site.privacy-policy')->name('privacy-policy');
Route::view('/terms', 'site.terms')->name('terms');
Route::view('/delivery-policy', 'site.delivery-policy')->name('delivery-policy');
Route::view('/sms-terms', 'site.sms-terms')->name('sms-terms');
Route::get('/sitemap.xml', [WebsiteController::class, 'sitemap'])->name('sitemap');

Route::post('/enquiries', [WebsiteEnquiryController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('website.enquiries.store');

// The plain-PHP site's URLs, which printed invoices, receipts, emails, the carrier SMS
// registration and search engines still carry. Each 301s to its route, query string kept
// (tracking.php?ref=X -> /tracking?ref=X). /index.php itself is the front controller, so
// /index.php and /index.php/about-us already serve the real pages (canonical tags dedupe them).
foreach ([
    'about.php' => 'about-us',
    'our-companies.php' => 'our-companies',
    'services.php' => 'our-services',
    'quote.php' => 'quote',
    'tracking.php' => 'our-tracking',
    'contact.php' => 'contact-us',
    'feedback.php' => 'our-feeback',
    'news.php' => 'our-blog',
    'projects.php' => 'our-projects',
    'faq.php' => 'faq',
    'privacy-policy.php' => 'privacy-policy',
    'terms.php' => 'terms',
    'delivery-policy.php' => 'delivery-policy',
    'sms-terms.php' => 'sms-terms',
] as $legacyPath => $routeName) {
    Route::get($legacyPath, fn (Request $request) => redirect()->to(
        route($routeName).($request->getQueryString() ? '?'.$request->getQueryString() : ''),
        301
    ));
}

Route::get('news-detail.php', fn (Request $request) => filled($request->query('slug'))
    ? redirect()->route('blog-details', ['slug' => $request->query('slug')], 301)
    : redirect()->route('our-blog', [], 301));

// The seven service pages consolidated into one in September 2026.
foreach (array_keys(config('website.services')) as $serviceSlug) {
    Route::get("{$serviceSlug}.php", fn () => redirect()->to(route('our-services').'#'.$serviceSlug, 301));
}

Route::get('/packing-slip/{id}', [ShippingController::class, 'packingslip'])
    ->name('packing-slip');

Route::get('/shipping-label/{id}', [ShippingController::class, 'shippinglabel'])
    ->name('shipping-label');

Route::get('/shipping-invoice/{id}', [ShippingController::class, 'shippinginvoice'])
    ->name('shipping-invoice');

Route::get('/shipping-invoice-pdf/{id}', [ShippingController::class, 'shippinginvoicepdf'])
    ->name('shipping-invoice-pdf');

Route::get('/shipping-invoice-download/{id}', [ShippingController::class, 'downloadinvoicepdf'])
    ->name('shipping-invoice-download');

Route::get('/send-invoice-email/{id}', [ShippingController::class, 'sendinvoiceemail'])
    ->name('send-invoice-email');

Route::get('/shipping-receipt/{id}', [ShippingController::class, 'shippingreceipt'])
    ->name('shipping-receipt');

Route::get('/payment-receipt/{payment}', [ShippingController::class, 'paymentReceipt'])
    ->name('payment-receipt');

Route::get('/print-quotation/{record}', [ShippingController::class, 'printquotation'])
    ->name('print-quotation');

Route::get('/delivery-report/{id}', [ShippingController::class, 'deliveryReport'])
    ->name('delivery-report');

Route::get('/delivery-report-download/{id}', [ShippingController::class, 'downloadDeliveryReport'])
    ->name('delivery-report-download');

// Scannable QR label lookup — secured by the shipment's existing unguessable
// public_view_token (same trust model as /my-shipment/{token} above), not
// session auth, so a printed label works for both staff and any scanner.
// Deliberately excludes receiver PII — see shipment-label-print.blade.php.
Route::get('/shipment-label/{token}', [ShippingController::class, 'shipmentLabelLookup'])
    ->middleware('throttle:60,1')
    ->name('shipment-label-lookup');

// Signed (not session-authenticated) so both the Filament portals and the mobile
// app's in-app browser — which has no shared session cookie — can open the same
// links. Every place that generates these URLs must use URL::signedRoute().
Route::middleware('signed')->group(function () {
    Route::get('/investment-agreement/{investment}', [InvestmentController::class, 'agreement'])
        ->name('investment-agreement');

    Route::get('/investment-agreement/{investment}/download', [InvestmentController::class, 'agreementDownload'])
        ->name('investment-agreement-download');

    Route::get('/investor/{investor}/investment-agreement', [InvestmentController::class, 'combinedAgreement'])
        ->name('investment-agreement-combined');

    Route::get('/investment-statement/{statement}', [InvestmentController::class, 'annualStatement'])
        ->name('investment-annual-statement');

    Route::get('/investment-statement/{statement}/download', [InvestmentController::class, 'annualStatementDownload'])
        ->name('investment-annual-statement-download');

    Route::get('/investor/{investor}/investment-account-statement', [InvestmentController::class, 'accountStatement'])
        ->name('investment-account-statement');

    Route::get('/investor/{investor}/investment-account-statement/download', [InvestmentController::class, 'accountStatementDownload'])
        ->name('investment-account-statement-download');

    Route::get('/investor/{investor}/reports/shipments', [InvestorReportController::class, 'shipments'])
        ->name('investor-report-shipments');

    Route::get('/investor/{investor}/reports/shipments/download', [InvestorReportController::class, 'shipmentsDownload'])
        ->name('investor-report-shipments-download');

    Route::get('/investor/{investor}/reports/income', [InvestorReportController::class, 'income'])
        ->name('investor-report-income');

    Route::get('/investor/{investor}/reports/income/download', [InvestorReportController::class, 'incomeDownload'])
        ->name('investor-report-income-download');

    Route::get('/investor/{investor}/reports/expenses', [InvestorReportController::class, 'expenses'])
        ->name('investor-report-expenses');

    Route::get('/investor/{investor}/reports/expenses/download', [InvestorReportController::class, 'expensesDownload'])
        ->name('investor-report-expenses-download');
});

Route::get('/impersonate/start/{user}', [ImpersonationController::class, 'start'])
    ->middleware(['auth', 'signed'])
    ->name('impersonate.start');

Route::get('/impersonate/stop', [ImpersonationController::class, 'stop'])
    ->middleware('auth')
    ->name('impersonate.stop');

Route::get('/make-payment/{record}', DisclaimerForm::class)
    ->name('make-payment');

Route::get('/make-payment-agreement/{record}', AgreementForm::class)
    ->name('make-payment-agreement');

Route::get('/paid-successfully', PaymentsuccessfulPage::class)
    ->name('paid-successfully');

// Paystack redirects here after a payment made from the mobile app.
// Serves a tiny page that immediately deep-links back into the app so the
// in-app auth-session browser closes itself. The scheme must match the
// "scheme" in the mobile app's app.json; it is env-driven so it can be
// repointed without a code deploy (see config/services.php mobile.scheme).
// The deep link only auto-closes the browser: the app confirms the payment
// by verifying the reference, so a scheme mismatch degrades UX, not payments.
Route::get('/app/payment-complete', function (\Illuminate\Http\Request $request) {
    $reference = $request->query('reference', $request->query('trxref', ''));
    $deepLink = config('services.mobile.scheme').'://pay?reference='.urlencode($reference);

    return response(
        '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<meta http-equiv="refresh" content="0;url='.e($deepLink).'">'
        .'<title>Payment Complete</title>'
        .'<style>body{font-family:sans-serif;text-align:center;padding:48px 24px;color:#333}a{display:inline-block;margin-top:16px;padding:12px 24px;background:#A0043C;color:#fff;border-radius:10px;text-decoration:none;font-weight:700}</style>'
        .'</head><body>'
        .'<h2>Payment complete ✅</h2><p>Returning you to the app…</p>'
        .'<a href="'.e($deepLink).'">Open the app</a>'
        .'<script>window.location.href='.json_encode($deepLink).';</script>'
        .'</body></html>'
    );
})->name('app-payment-complete');

Route::get('/customer-feedback', CustomerFeedaback::class)
    ->name('customer-feedback');

Route::get('/complaint/{token?}', CustomerFeedaback::class)
    ->name('complaint-form');

// External Shipment Form Routes (for clients to fill out their own details)
Route::get('/shipment-form/{token}', [ExternalShipmentController::class, 'showForm'])
    ->name('external-shipment-form');

Route::post('/shipment-form/{token}', [ExternalShipmentController::class, 'submitForm'])
    ->name('external-shipment-submit');

Route::get('/shipment-form-states', [ExternalShipmentController::class, 'getStates'])
    ->name('external-shipment-states');

Route::get('/shipment-form-cities', [ExternalShipmentController::class, 'getCities'])
    ->name('external-shipment-cities');

Route::get('/shipment-form-expired', function () {
    return view('external.shipment-form-expired');
})->name('external-shipment-expired');

// Public pickup-request links. Each link carries a unique, unguessable token
// generated from the Shipment Requests page in the admin panel.
Route::get('/pickup-request/{token}', [PublicShipmentRequestController::class, 'show'])
    ->name('public-pickup-request');
Route::post('/pickup-request/{token}', [PublicShipmentRequestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public-pickup-request.store');
Route::get('/pickup-request/{token}/products', [PublicShipmentRequestController::class, 'products'])
    ->middleware('throttle:120,1')
    ->name('public-pickup-request.products');
Route::get('/pickup-request/{token}/submitted', [PublicShipmentRequestController::class, 'submitted'])
    ->name('public-pickup-request.submitted');

// No-login shipment activity page — for clients who'd rather not create a portal
// account. Secured by an unguessable token (same trust model as the external
// shipment form above), not session auth.
Route::get('/my-shipment/{token}', [PublicShipmentController::class, 'show'])
    ->name('public-shipment-view');

// Flexible part-payment from the no-login portal (any amount up to the balance).
Route::post('/my-shipment/{token}/pay', [PublicShipmentController::class, 'pay'])
    ->name('public-shipment-pay');
Route::get('/my-shipment/{token}/paid', [PublicShipmentController::class, 'paid'])
    ->name('public-shipment-paid');

// API Routes for internal use
Route::prefix('api')->middleware('web')->group(function () {
    Route::get('/previous-receivers', [\App\Http\Controllers\Api\ReceiverController::class, 'getPreviousReceivers'])
        ->name('api.previous-receivers');
});
