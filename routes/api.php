<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\Cashbook\CashbookDirectorAccountController;
use App\Http\Controllers\Api\V1\Cashbook\CashbookEntryController;
use App\Http\Controllers\Api\V1\Cashbook\CashbookLedgerController;
use App\Http\Controllers\Api\V1\Cashbook\CashbookLoanController;
use App\Http\Controllers\Api\V1\Cashbook\CashbookWHTController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ContactMessageController;
use App\Http\Controllers\Api\V1\ContainerController;
use App\Http\Controllers\Api\V1\Customer\CustomerAuthController;
use App\Http\Controllers\Api\V1\Customer\CustomerComplaintController;
use App\Http\Controllers\Api\V1\Customer\CustomerInvoiceController;
use App\Http\Controllers\Api\V1\Customer\CustomerLookupController;
use App\Http\Controllers\Api\V1\Customer\CustomerNotificationController;
use App\Http\Controllers\Api\V1\Customer\CustomerPaymentController;
use App\Http\Controllers\Api\V1\Customer\CustomerPickupController;
use App\Http\Controllers\Api\V1\Customer\CustomerRatingController;
use App\Http\Controllers\Api\V1\Customer\CustomerShipmentController;
use App\Http\Controllers\Api\V1\Customer\CustomerShipmentRequestController;
use App\Http\Controllers\Api\V1\Customer\PaystackWebhookController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\Ecommerce\Admin;
use App\Http\Controllers\Api\V1\Ecommerce\Customer as EcommerceCustomer;
use App\Http\Controllers\Api\V1\Ecommerce\EcommercePaystackWebhookController;
use App\Http\Controllers\Api\V1\Ecommerce\EcommerceStripeWebhookController;
use App\Http\Controllers\Api\V1\Ecommerce\VendorApplicationController;
use App\Http\Controllers\Api\V1\ExchangeRateController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\FeedbackController;
use App\Http\Controllers\Api\V1\FinancialReportController;
use App\Http\Controllers\Api\V1\IncomeController;
use App\Http\Controllers\Api\V1\Investment\InvestmentPaystackWebhookController;
use App\Http\Controllers\Api\V1\Investment\InvestmentStripeWebhookController;
use App\Http\Controllers\Api\V1\Investment\InvestmentTransferWebhookController;
use App\Http\Controllers\Api\V1\Investor\InvestorCompanyPerformanceController;
use App\Http\Controllers\Api\V1\Investor\InvestorDocumentController;
use App\Http\Controllers\Api\V1\Investor\InvestorInvestmentController;
use App\Http\Controllers\Api\V1\Investor\InvestorWithdrawalRequestController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PayrollEntryController;
use App\Http\Controllers\Api\V1\PayrollPeriodController;
use App\Http\Controllers\Api\V1\PickupScheduleController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\QuotationController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\ShipmentRequestController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\StaffRoleController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VehicleController;
use App\Http\Controllers\Api\V1\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Legacy endpoint (keep for backwards compatibility) ──────────────────────
Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');

// ─── API v1 ──────────────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // ── Public ──────────────────────────────────────────────────────────────
    Route::middleware('throttle:10,1')->post('auth/login', [AuthController::class, 'login']);
    Route::middleware('throttle:60,1')->get('shipments/track/{tracking_number}', [ShipmentController::class, 'publicTrack']);
    Route::get('exchange-rates/current', [ExchangeRateController::class, 'current']);

    // ── Authenticated ────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('auth/password', [AuthController::class, 'changePassword']);

        // Dashboard
        Route::get('dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('dashboard/recent-shipments', [DashboardController::class, 'recentShipments']);

        // Shipments
        Route::apiResource('shipments', ShipmentController::class);
        Route::get('shipments/{id}/trackings', [ShipmentController::class, 'trackings']);
        Route::post('shipments/{id}/trackings', [ShipmentController::class, 'addTracking']);
        Route::get('shipments/{id}/media', [ShipmentController::class, 'media']);
        Route::post('shipments/{id}/media', [ShipmentController::class, 'uploadMedia']);
        Route::get('shipments/{id}/items', [ShipmentController::class, 'items']);

        // Clients
        Route::apiResource('clients', ClientController::class);
        Route::get('clients/{id}/shipments', [ClientController::class, 'shipments']);
        Route::get('clients/{id}/interactions', [ClientController::class, 'interactions']);
        Route::get('clients/{id}/ratings', [ClientController::class, 'ratings']);

        // Products
        Route::apiResource('products', ProductController::class);

        // Quotations
        Route::apiResource('quotations', QuotationController::class);

        // Invoices (no create/delete from mobile)
        Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show', 'update']);

        // Payments (no update/delete from mobile)
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);

        // Expenses
        Route::apiResource('expenses', ExpenseController::class);
        Route::get('expense-categories', [ExpenseController::class, 'categories']);

        // Incomes
        Route::apiResource('incomes', IncomeController::class);
        Route::get('income-categories', [IncomeController::class, 'categories']);

        // Exchange Rates
        Route::apiResource('exchange-rates', ExchangeRateController::class)->only(['index', 'store']);

        // Staff
        Route::apiResource('staff', StaffController::class);
        Route::get('staff/{id}/payroll-entries', [StaffController::class, 'payrollEntries']);

        // Staff Roles
        Route::apiResource('staff-roles', StaffRoleController::class)->only(['index', 'store', 'show']);

        // Payroll
        Route::apiResource('payroll-periods', PayrollPeriodController::class);
        Route::post('payroll-entries', [PayrollEntryController::class, 'store']);
        Route::get('payroll-entries/{id}', [PayrollEntryController::class, 'show']);
        Route::put('payroll-entries/{id}', [PayrollEntryController::class, 'update']);

        // Vehicles
        Route::apiResource('vehicles', VehicleController::class);
        Route::get('vehicles/{id}/maintenances', [VehicleController::class, 'maintenances']);
        Route::post('vehicles/{id}/maintenances', [VehicleController::class, 'addMaintenance']);
        Route::put('vehicles/{id}/maintenances/{maintenanceId}', [VehicleController::class, 'updateMaintenance']);
        Route::delete('vehicles/{id}/maintenances/{maintenanceId}', [VehicleController::class, 'deleteMaintenance']);
        Route::get('vehicles/{id}/trips', [VehicleController::class, 'trips']);

        // Trips
        Route::apiResource('trips', TripController::class);
        Route::get('trips/{id}/shipments', [TripController::class, 'shipments']);
        Route::post('trips/{id}/shipments', [TripController::class, 'assignShipment']);
        Route::delete('trips/{id}/shipments/{shipmentId}', [TripController::class, 'removeShipment']);
        Route::put('trips/{id}/shipments/{shipmentId}', [TripController::class, 'updateDelivery']);

        // Pickup Schedules
        Route::apiResource('pickup-schedules', PickupScheduleController::class)->except(['destroy']);
        Route::post('pickup-schedules/{id}/convert', [PickupScheduleController::class, 'convert']);

        // Containers (by UUID — existing)
        Route::apiResource('containers', ContainerController::class)->only(['index', 'show', 'update']);
        Route::get('containers/{id}/shipments', [ContainerController::class, 'shipments']);

        // Shipment-containers (by container_number — for mobile Container Status modal)
        Route::get('shipment-containers', [ContainerController::class, 'containerNumbers']);
        Route::put('shipment-containers/{containerNumber}', [ContainerController::class, 'updateByNumber']);
        Route::post('shipment-containers/{containerNumber}/bulk-status', [ContainerController::class, 'bulkStatusUpdate']);

        // Customer Feedback
        Route::get('feedback', [FeedbackController::class, 'index']);
        Route::get('feedback/{id}', [FeedbackController::class, 'show']);
        Route::put('feedback/{id}', [FeedbackController::class, 'update']);

        // Contact Messages
        Route::get('contact-messages', [ContactMessageController::class, 'index']);
        Route::get('contact-messages/{id}', [ContactMessageController::class, 'show']);
        Route::put('contact-messages/{id}', [ContactMessageController::class, 'update']);

        // Cashbook
        Route::prefix('cashbook')->group(function () {
            Route::apiResource('entries', CashbookEntryController::class);
            Route::get('income-ledger', [CashbookLedgerController::class, 'income']);
            Route::get('expenditure-ledger', [CashbookLedgerController::class, 'expenditure']);
            Route::get('loans', [CashbookLoanController::class, 'index']);
            Route::post('loans', [CashbookLoanController::class, 'store']);
            Route::put('loans/{id}', [CashbookLoanController::class, 'update']);
            Route::get('withholding-tax', [CashbookWHTController::class, 'index']);
            Route::post('withholding-tax', [CashbookWHTController::class, 'store']);
            Route::put('withholding-tax/{id}', [CashbookWHTController::class, 'update']);
            Route::get('director-account', [CashbookDirectorAccountController::class, 'index']);
            Route::post('director-account', [CashbookDirectorAccountController::class, 'store']);
            Route::put('director-account/{id}', [CashbookDirectorAccountController::class, 'update']);
            Route::delete('director-account/{id}', [CashbookDirectorAccountController::class, 'destroy']);
        });

        // Branches
        Route::get('branches', [BranchController::class, 'index']);
        Route::get('branches/{id}', [BranchController::class, 'show']);
        Route::put('branches/{id}', [BranchController::class, 'update']);

        // Users (admin)
        Route::apiResource('users', UserController::class);

        // Reports
        Route::prefix('reports')->group(function () {
            // Financial report endpoints (specific routes must come before {id} wildcard)
            Route::get('financial-dashboard', [FinancialReportController::class, 'financialDashboard']);
            Route::get('expenses/export', [FinancialReportController::class, 'exportExpenses']);
            Route::get('expenses', [FinancialReportController::class, 'expenses']);
            Route::get('incomes/export', [FinancialReportController::class, 'exportIncomes']);
            Route::get('incomes', [FinancialReportController::class, 'incomes']);
            Route::get('payroll/export', [FinancialReportController::class, 'exportPayroll']);
            Route::get('payroll', [FinancialReportController::class, 'payroll']);
            Route::get('shipments/export', [FinancialReportController::class, 'exportShipments']);
            Route::get('shipments', [FinancialReportController::class, 'shipments']);
            Route::get('profit-loss-summary', [FinancialReportController::class, 'profitLossSummary']);
            Route::get('client-growth', [FinancialReportController::class, 'clientGrowth']);
            Route::get('receivables-aging', [FinancialReportController::class, 'receivablesAging']);
            Route::get('container-detail/{reference}', [FinancialReportController::class, 'containerDetail']);

            // Legacy saved-report endpoints (wildcard must come last)
            Route::get('/', [ReportController::class, 'index']);
            Route::get('{id}', [ReportController::class, 'show']);
        });

        // Lookup / Pickers
        Route::prefix('lookup')->group(function () {
            Route::get('branches', [LookupController::class, 'branches']);
            Route::get('clients', [LookupController::class, 'clients']);
            Route::get('products', [LookupController::class, 'products']);
            Route::get('staff', [LookupController::class, 'staff']);
            Route::get('vehicles', [LookupController::class, 'vehicles']);
            Route::get('expense-categories', [LookupController::class, 'expenseCategories']);
            Route::get('income-categories', [LookupController::class, 'incomeCategories']);
            Route::get('staff-roles', [LookupController::class, 'staffRoles']);
            Route::get('exchange-rate', [LookupController::class, 'exchangeRate']);
            Route::get('previous-receivers', [LookupController::class, 'previousReceivers']);
            Route::get('cashbook-cost-centers', [LookupController::class, 'cashbookCostCenters']);
            Route::get('countries', [LookupController::class, 'countries']);
            Route::get('states', [LookupController::class, 'states']);
            Route::get('cities', [LookupController::class, 'cities']);
        });

        // Shipment Requests (client-submitted, staff review/approve)
        Route::get('shipment-requests', [ShipmentRequestController::class, 'index']);
        Route::get('shipment-requests/{id}', [ShipmentRequestController::class, 'show']);
        Route::patch('shipment-requests/{id}/review', [ShipmentRequestController::class, 'markUnderReview']);
        Route::post('shipment-requests/{id}/approve', [ShipmentRequestController::class, 'approve']);
        Route::post('shipment-requests/{id}/reject', [ShipmentRequestController::class, 'reject']);

        // ── Driver (role-specific) ─────────────────────────────────────────
        Route::prefix('driver')->group(function () {
            Route::get('profile', [DriverController::class, 'profile']);
            Route::get('schedule', [DriverController::class, 'schedule']);
            Route::get('trips', [DriverController::class, 'trips']);
            Route::get('trips/{id}', [DriverController::class, 'tripDetail']);
            Route::put('trips/{id}/status', [DriverController::class, 'updateTripStatus']);
            Route::put('trips/{id}/shipments/{shipmentId}', [DriverController::class, 'updateDelivery']);
        });

        // ── Investor (role-specific) ────────────────────────────────────────
        Route::prefix('investor')->middleware('investor')->group(function () {
            Route::get('investments', [InvestorInvestmentController::class, 'index']);
            Route::post('investments', [InvestorInvestmentController::class, 'store']);
            Route::get('investments/{id}', [InvestorInvestmentController::class, 'show']);
            Route::get('investments/{id}/transactions', [InvestorInvestmentController::class, 'transactions']);
            Route::get('investments/{id}/agreement', [InvestorDocumentController::class, 'agreement']);
            Route::post('investments/{id}/signed-agreement', [InvestorDocumentController::class, 'uploadSignedAgreement']);
            Route::post('investments/{id}/verify', [InvestorInvestmentController::class, 'verify']);

            Route::get('withdrawal-requests', [InvestorWithdrawalRequestController::class, 'index']);
            Route::post('withdrawal-requests', [InvestorWithdrawalRequestController::class, 'store']);
            Route::get('withdrawal-requests/{id}', [InvestorWithdrawalRequestController::class, 'show']);

            Route::get('agreement', [InvestorDocumentController::class, 'combinedAgreement']);
            Route::get('statement', [InvestorDocumentController::class, 'statement']);
            Route::get('statements', [InvestorDocumentController::class, 'statements']);

            Route::get('company-performance', [InvestorCompanyPerformanceController::class, 'index']);
            Route::get('reports/shipments', [InvestorCompanyPerformanceController::class, 'shipments']);
            Route::get('reports/income', [InvestorCompanyPerformanceController::class, 'income']);
            Route::get('reports/expenses', [InvestorCompanyPerformanceController::class, 'expenses']);
        });
    });

    // ─── Customer API ─────────────────────────────────────────────────────────
    Route::prefix('customer')->group(function () {

        // Public
        Route::get('config', [CustomerAuthController::class, 'config']);
        Route::get('branches', [CustomerAuthController::class, 'branches']);
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('auth/register', [CustomerAuthController::class, 'register']);
            Route::post('auth/login', [CustomerAuthController::class, 'login']);
            Route::post('auth/reset-password', [CustomerAuthController::class, 'resetPassword']);
        });
        Route::middleware('throttle:5,1')->post('auth/forgot-password', [CustomerAuthController::class, 'forgotPassword']);

        // Paystack webhook (signature-verified, no auth)
        Route::post('webhooks/paystack', [PaystackWebhookController::class, 'handle']);

        // Authenticated customers
        Route::middleware(['auth:sanctum', 'active', 'customer'])->group(function () {
            Route::post('auth/logout', [CustomerAuthController::class, 'logout']);
            Route::delete('auth/account', [CustomerAuthController::class, 'deleteAccount']);
            Route::get('auth/me', [CustomerAuthController::class, 'me']);
            Route::put('auth/profile', [CustomerAuthController::class, 'updateProfile']);
            Route::put('auth/password', [CustomerAuthController::class, 'changePassword']);

            Route::get('shipments', [CustomerShipmentController::class, 'index']);
            Route::get('shipments/{id}', [CustomerShipmentController::class, 'show']);

            Route::get('pickups', [CustomerPickupController::class, 'index']);
            Route::post('pickups', [CustomerPickupController::class, 'store']);
            Route::get('pickups/{id}', [CustomerPickupController::class, 'show']);
            Route::put('pickups/{id}', [CustomerPickupController::class, 'update']);
            Route::delete('pickups/{id}', [CustomerPickupController::class, 'destroy']);

            Route::get('invoices', [CustomerInvoiceController::class, 'index']);
            Route::get('invoices/{id}', [CustomerInvoiceController::class, 'show']);

            Route::get('payments', [CustomerPaymentController::class, 'index']);
            Route::post('payments/initiate', [CustomerPaymentController::class, 'initiate']);
            Route::post('payments/verify', [CustomerPaymentController::class, 'verify']);

            Route::post('device-tokens', [CustomerNotificationController::class, 'storeDeviceToken']);
            Route::delete('device-tokens', [CustomerNotificationController::class, 'removeDeviceToken']);

            Route::get('complaints', [CustomerComplaintController::class, 'index']);
            Route::post('complaints', [CustomerComplaintController::class, 'store']);
            Route::get('complaints/{id}', [CustomerComplaintController::class, 'show']);

            Route::post('ratings', [CustomerRatingController::class, 'store']);

            // Lookup (country/state/city/products/previous-receivers for pickers)
            Route::prefix('lookup')->group(function () {
                Route::get('countries', [CustomerLookupController::class, 'countries']);
                Route::get('states', [CustomerLookupController::class, 'states']);
                Route::get('cities', [CustomerLookupController::class, 'cities']);
                Route::get('products', [CustomerLookupController::class, 'products']);
                Route::get('previous-receivers', [CustomerLookupController::class, 'previousReceivers']);
            });

            // Shipment requests (client-submitted, pending staff approval)
            Route::get('shipment-request-stats', [CustomerShipmentRequestController::class, 'stats']);
            Route::get('shipment-requests', [CustomerShipmentRequestController::class, 'index']);
            Route::post('shipment-requests', [CustomerShipmentRequestController::class, 'store']);
            Route::get('shipment-requests/{id}', [CustomerShipmentRequestController::class, 'show']);
            Route::put('shipment-requests/{id}', [CustomerShipmentRequestController::class, 'update']);
            Route::delete('shipment-requests/{id}', [CustomerShipmentRequestController::class, 'cancel']);
        });
    });

    // ─── Marketplace (E-Commerce) ─────────────────────────────────────────────
    Route::prefix('marketplace')->group(function () {

        // Webhooks — signature-verified, no Sanctum
        Route::post('webhooks/paystack', [EcommercePaystackWebhookController::class, 'handle']);
        Route::post('webhooks/stripe', [EcommerceStripeWebhookController::class, 'handle']);

        // Public — vendor onboarding applications (reviewed by staff in Filament)
        Route::middleware('throttle:5,1')->post('vendor-applications', [VendorApplicationController::class, 'store']);

        // Public browsing — no login required (web guests). If a Sanctum Bearer
        // token is sent anyway (mobile), CustomerBaseController::customerBranchId()
        // still resolves it and scopes to that customer's own branch; guests fall
        // back to config('ecommerce.public_branch_id'). 'active' is safe here since
        // it no-ops when there's no authenticated user.
        Route::middleware(['active'])->group(function () {
            Route::get('home', [EcommerceCustomer\EcommerceHomeController::class, 'index']);

            Route::get('categories', [EcommerceCustomer\EcommerceCategoryBrowseController::class, 'index']);
            Route::get('categories/{id}/products', [EcommerceCustomer\EcommerceCategoryBrowseController::class, 'products']);

            Route::get('products', [EcommerceCustomer\EcommerceProductCatalogController::class, 'index']);
            Route::get('products/{id}', [EcommerceCustomer\EcommerceProductCatalogController::class, 'show']);
            Route::get('products/{id}/reviews', [EcommerceCustomer\ProductReviewController::class, 'index']);

            Route::get('vendors', [EcommerceCustomer\VendorStorefrontController::class, 'index']);
            Route::get('vendors/{slug}', [EcommerceCustomer\VendorStorefrontController::class, 'show']);
            Route::get('vendors/{slug}/products', [EcommerceCustomer\VendorStorefrontController::class, 'products']);

            // Cart is guest-accessible (gap D.2): unauthenticated requests are
            // keyed by the X-Guest-Session-Id header and merged into the user's
            // cart on login/register (see CustomerAuthController::mergeGuestCart).
            // Coupon application still requires login (per-user usage limits).
            Route::get('cart', [EcommerceCustomer\EcommerceCartController::class, 'show']);
            Route::post('cart/items', [EcommerceCustomer\EcommerceCartController::class, 'addItem']);
            Route::put('cart/items/{itemId}', [EcommerceCustomer\EcommerceCartController::class, 'updateItem']);
            Route::delete('cart/items/{itemId}', [EcommerceCustomer\EcommerceCartController::class, 'removeItem']);
            Route::delete('cart', [EcommerceCustomer\EcommerceCartController::class, 'clear']);
            Route::post('cart/coupon', [EcommerceCustomer\EcommerceCartController::class, 'applyCoupon']);

            // Guest order tracking — order number + phone/email lookup, no login required.
            Route::middleware('throttle:10,1')->post('track-order', [EcommerceCustomer\PublicOrderTrackingController::class, 'track']);
        });

        // Customer marketplace — genuinely requires login (checkout/orders/payments)
        Route::middleware(['auth:sanctum', 'active', 'customer'])->group(function () {
            Route::apiResource('addresses', EcommerceCustomer\DeliveryAddressController::class);
            Route::patch('addresses/{id}/default', [EcommerceCustomer\DeliveryAddressController::class, 'setDefault']);

            Route::post('checkout', [EcommerceCustomer\EcommerceCheckoutController::class, 'checkout']);

            Route::get('orders', [EcommerceCustomer\CustomerEcommerceOrderController::class, 'index']);
            Route::get('orders/{id}', [EcommerceCustomer\CustomerEcommerceOrderController::class, 'show']);
            Route::get('orders/{id}/tracking', [EcommerceCustomer\CustomerEcommerceOrderController::class, 'tracking']);
            Route::delete('orders/{id}', [EcommerceCustomer\CustomerEcommerceOrderController::class, 'cancel']);
            Route::post('orders/{id}/rate', [EcommerceCustomer\CustomerEcommerceOrderController::class, 'rate']);

            Route::post('payments/initiate', [EcommerceCustomer\EcommercePaymentController::class, 'initiate']);
            Route::post('payments/verify/paystack', [EcommerceCustomer\EcommercePaymentController::class, 'verifyPaystack']);
            Route::post('payments/verify/stripe', [EcommerceCustomer\EcommercePaymentController::class, 'verifyStripe']);

            Route::post('coupons/validate', [EcommerceCustomer\CouponController::class, 'validateCode']);
            Route::get('shipping-methods', [EcommerceCustomer\ShippingMethodController::class, 'forAddress']);

            Route::get('wishlist', [EcommerceCustomer\WishlistController::class, 'index']);
            Route::post('wishlist/{productId}', [EcommerceCustomer\WishlistController::class, 'store']);
            Route::delete('wishlist/{productId}', [EcommerceCustomer\WishlistController::class, 'destroy']);

            Route::post('products/{id}/reviews', [EcommerceCustomer\ProductReviewController::class, 'store']);
        });

        // Vendor self-service — vendor accounts are provisioned only via an
        // approved EcommerceVendorApplication (see VendorProvisioningService),
        // never self-registered.
        Route::prefix('vendor')->group(function () {
            Route::middleware('throttle:10,1')->group(function () {
                Route::post('auth/login', [Vendor\VendorAuthController::class, 'login']);
                Route::post('auth/forgot-password', [Vendor\VendorAuthController::class, 'forgotPassword']);
                Route::post('auth/reset-password', [Vendor\VendorAuthController::class, 'resetPassword']);
            });

            Route::middleware(['auth:sanctum', 'active', 'vendor'])->group(function () {
                Route::post('auth/logout', [Vendor\VendorAuthController::class, 'logout']);
                Route::get('auth/me', [Vendor\VendorAuthController::class, 'me']);
                Route::put('auth/password', [Vendor\VendorAuthController::class, 'changePassword']);

                Route::get('store', [Vendor\VendorStoreSettingsController::class, 'show']);
                Route::put('store', [Vendor\VendorStoreSettingsController::class, 'update']);
                Route::post('store/logo', [Vendor\VendorStoreSettingsController::class, 'uploadLogo']);
                Route::post('store/banner', [Vendor\VendorStoreSettingsController::class, 'uploadBanner']);

                Route::get('categories', [Vendor\VendorCategoryController::class, 'index']);

                Route::apiResource('products', Vendor\VendorProductController::class);
                Route::post('products/{id}/images', [Vendor\VendorProductController::class, 'uploadImage']);
                Route::delete('products/{id}/images/{imageId}', [Vendor\VendorProductController::class, 'deleteImage']);
                Route::patch('products/{id}/toggle-active', [Vendor\VendorProductController::class, 'toggleActive']);

                Route::post('inventory/adjust', [Vendor\VendorInventoryController::class, 'adjust']);
                Route::get('inventory/logs', [Vendor\VendorInventoryController::class, 'logs']);
                Route::get('inventory/low-stock', [Vendor\VendorInventoryController::class, 'lowStock']);

                Route::get('orders', [Vendor\VendorOrderController::class, 'index']);
                Route::get('orders/{id}', [Vendor\VendorOrderController::class, 'show']);
                Route::patch('orders/{id}/process', [Vendor\VendorOrderController::class, 'process']);
                Route::patch('orders/{id}/pack', [Vendor\VendorOrderController::class, 'pack']);
                Route::patch('orders/{id}/dispatch', [Vendor\VendorOrderController::class, 'dispatch']);
                Route::post('orders/{orderId}/shipment', [Vendor\VendorShipmentController::class, 'create']);
                Route::post('orders/{orderId}/shipment/tracking', [Vendor\VendorShipmentController::class, 'addTracking']);

                Route::get('reviews', [Vendor\VendorReviewController::class, 'index']);
                Route::post('reviews/{id}/reply', [Vendor\VendorReviewController::class, 'reply']);

                Route::get('coupons', [Vendor\VendorCouponController::class, 'index']);
                Route::post('coupons', [Vendor\VendorCouponController::class, 'store']);
                Route::put('coupons/{id}', [Vendor\VendorCouponController::class, 'update']);

                Route::get('earnings/summary', [Vendor\VendorEarningsController::class, 'summary']);
                Route::get('earnings/transactions', [Vendor\VendorEarningsController::class, 'transactions']);
                Route::get('payouts', [Vendor\VendorPayoutController::class, 'index']);
                Route::post('payouts', [Vendor\VendorPayoutController::class, 'store']);

                Route::get('reports/sales', [Vendor\VendorReportController::class, 'sales']);
                Route::get('reports/products', [Vendor\VendorReportController::class, 'productPerformance']);
            });
        });

        // Staff admin marketplace
        Route::middleware(['auth:sanctum', 'active'])->prefix('staff')->group(function () {
            Route::apiResource('categories', Admin\EcommerceCategoryController::class);
            Route::patch('categories/{id}/toggle-active', [Admin\EcommerceCategoryController::class, 'toggleActive']);
            Route::post('categories/{id}/image', [Admin\EcommerceCategoryController::class, 'uploadImage']);

            Route::apiResource('products', Admin\AdminEcommerceProductController::class);
            Route::post('products/{id}/images', [Admin\AdminEcommerceProductController::class, 'uploadImage']);
            Route::delete('products/{id}/images/{imageId}', [Admin\AdminEcommerceProductController::class, 'deleteImage']);
            Route::patch('products/{id}/toggle-active', [Admin\AdminEcommerceProductController::class, 'toggleActive']);
            Route::patch('products/{id}/toggle-featured', [Admin\AdminEcommerceProductController::class, 'toggleFeatured']);

            Route::post('inventory/adjust', [Admin\EcommerceInventoryController::class, 'adjust']);
            Route::get('inventory/logs', [Admin\EcommerceInventoryController::class, 'logs']);
            Route::get('inventory/low-stock', [Admin\EcommerceInventoryController::class, 'lowStock']);
            Route::get('inventory/out-of-stock', [Admin\EcommerceInventoryController::class, 'outOfStock']);

            Route::get('orders', [Admin\AdminEcommerceOrderController::class, 'index']);
            Route::get('orders/{id}', [Admin\AdminEcommerceOrderController::class, 'show']);
            Route::patch('orders/{id}/approve', [Admin\AdminEcommerceOrderController::class, 'approve']);
            Route::patch('orders/{id}/process', [Admin\AdminEcommerceOrderController::class, 'process']);
            Route::patch('orders/{id}/pack', [Admin\AdminEcommerceOrderController::class, 'pack']);
            Route::patch('orders/{id}/dispatch', [Admin\AdminEcommerceOrderController::class, 'dispatch']);
            Route::patch('orders/{id}/deliver', [Admin\AdminEcommerceOrderController::class, 'deliver']);
            Route::patch('orders/{id}/cancel', [Admin\AdminEcommerceOrderController::class, 'cancel']);
            Route::patch('orders/{id}/refund', [Admin\AdminEcommerceOrderController::class, 'refund']);

            Route::post('orders/{orderId}/shipment', [Admin\AdminEcommerceShipmentController::class, 'create']);
            Route::put('orders/{orderId}/shipment', [Admin\AdminEcommerceShipmentController::class, 'update']);
            Route::post('orders/{orderId}/shipment/tracking', [Admin\AdminEcommerceShipmentController::class, 'addTracking']);

            Route::get('reports/sales', [Admin\EcommerceSalesReportController::class, 'sales']);
            Route::get('reports/orders', [Admin\EcommerceSalesReportController::class, 'orders']);
            Route::get('reports/products', [Admin\EcommerceSalesReportController::class, 'productPerformance']);
            Route::get('reports/low-stock', [Admin\EcommerceSalesReportController::class, 'lowStock']);
        });
    });

    // ─── Investment Webhooks (signature-verified, no Sanctum) ────────────────
    Route::prefix('investments')->group(function () {
        Route::post('webhooks/paystack', [InvestmentPaystackWebhookController::class, 'handle']);
        Route::post('webhooks/stripe', [InvestmentStripeWebhookController::class, 'handle']);
        Route::post('webhooks/paystack-transfer', [InvestmentTransferWebhookController::class, 'handle']);
    });
});
