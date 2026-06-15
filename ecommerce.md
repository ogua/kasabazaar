# E-Commerce Marketplace Module — Implementation Plan

## Context

Kasabazaar is currently a logistics/shipping management platform. This plan adds a full Marketplace layer: staff manage products/orders through the Staff Mobile App; customers browse, cart, checkout, and track orders through the Client Mobile App. The Laravel backend serves all new APIs under `/api/v1/marketplace/`.

**Key findings from codebase exploration:**
- No Cart, Order, or e-commerce product models exist — all must be created from scratch.
- The existing `Product` model (`products` table) serves `QuotationItem` and `ShipmentItem` only — **it is not touched**.
- Paystack is fully wired (raw `Http::withToken()` calls + HMAC-SHA512 webhook). Same pattern reused for marketplace payments.
- **Stripe is not installed** — `stripe/stripe-php` must be added via composer.
- `PushNotificationService` (FCM) + database notifications already work — new notifications reuse this.
- `BaseApiController` provides `success()`, `error()`, `paginated()` — all staff controllers extend it.
- `CustomerBaseController` provides `clientId()` — all customer marketplace controllers extend it.
- `EnsureCustomer` middleware (`auth:sanctum, customer`) already gates customer routes — reused here.
- Customers have `branch_id` on `users` table directly (not in `branch_user` pivot). `resolveBranch()` from `BaseApiController` cannot be used for customers.
- All models use `HasUuids`. Soft deletes on audit-critical models.
- `Expense::generateReference()` is the pattern for auto-generated reference numbers.
- FCM is fired by calling `PushNotificationService::sendToUser()` directly in services/observers, **not** via notification channels (`via()` only returns `['database']`).

---

## Phase 1 — Database Migrations (14 new tables)

### 1. `create_ecommerce_categories_table`
```
id (uuid PK), branch_id (uuid FK branches),
name (string), slug (string), description (text nullable),
image (string nullable), parent_id (uuid nullable FK self — for subcategories),
is_active (bool default true), sort_order (int default 0), timestamps
unique(branch_id, slug)
```

### 2. `create_ecommerce_products_table`
```
id (uuid PK), branch_id (uuid FK branches),
category_id (uuid nullable FK ecommerce_categories),
name (string), slug (string), sku (string nullable),
description (text nullable), specifications (JSON nullable),
price_ghs (decimal 10,2), price_usd (decimal 10,2 nullable),
discount_price_ghs (decimal 10,2 nullable), discount_price_usd (decimal 10,2 nullable),
weight (decimal 8,3 nullable), stock (int default 0),
low_stock_threshold (int default 5),
is_active (bool default false), is_featured (bool default false),
timestamps, softDeletes
unique(branch_id, slug)
```

> The existing `products` table and `Product` model are **not touched**.

### 3. `create_ecommerce_product_images_table`
```
id (uuid PK), ecommerce_product_id (uuid FK ecommerce_products),
path (string), sort_order (int default 0), is_primary (bool default false), timestamps
```

### 4. `create_ecommerce_inventory_logs_table`
```
id (uuid PK), ecommerce_product_id (uuid FK ecommerce_products), branch_id (uuid FK),
type (enum: increase, decrease, adjustment, sale, return),
quantity_change (int), quantity_before (int), quantity_after (int),
reason (string nullable), reference_type (string nullable), reference_id (uuid nullable),
created_by (uuid FK users), timestamps
```

### 5. `create_delivery_addresses_table`
```
id (uuid PK), user_id (uuid FK users),
full_name, phone, alternative_phone (nullable), email (nullable),
country, region, city, suburb (nullable), street (nullable),
house_number (nullable), digital_address (nullable),
landmark (nullable), postal_code (nullable),
latitude (decimal 10,8 nullable), longitude (decimal 11,8 nullable),
delivery_notes (text nullable), is_default (bool default false), timestamps
```

### 6. `create_ecommerce_carts_table`
```
id (uuid PK), user_id (uuid unique FK users), branch_id (uuid FK),
coupon_code (string nullable), expires_at (timestamp nullable), timestamps
```

### 7. `create_ecommerce_cart_items_table`
```
id (uuid PK), cart_id (uuid FK ecommerce_carts),
ecommerce_product_id (uuid FK ecommerce_products),
quantity (int default 1), price_ghs (decimal 10,2 — snapshot at add time), timestamps
unique(cart_id, ecommerce_product_id)
```

### 8. `create_ecommerce_orders_table`
```
id (uuid PK), order_number (string unique),
user_id (uuid FK users), branch_id (uuid FK branches),
status (enum: pending, awaiting_payment, paid, processing, packed,
               dispatched, in_transit, delivered, cancelled, refunded),
subtotal_ghs (decimal 10,2), shipping_fee_ghs (decimal 10,2 default 0),
discount_ghs (decimal 10,2 default 0), total_ghs (decimal 10,2),
total_usd (decimal 10,2 nullable), exchange_rate (decimal 10,4 nullable),
payment_status (enum: pending, paid, failed, refunded),
payment_gateway (string nullable), payment_reference (string nullable),
coupon_code (string nullable), notes (text nullable),
cancelled_reason (text nullable), cancelled_by (uuid nullable FK users),
approved_by (uuid nullable FK users),
timestamps, softDeletes
```

> `cancelled_by` and `approved_by` provide audit trail of which staff or customer performed the action.

### 9. `create_ecommerce_order_items_table`
```
id (uuid PK), order_id (uuid FK ecommerce_orders),
ecommerce_product_id (uuid nullable FK ecommerce_products — nullable so history survives deletion),
product_name (string snapshot), product_sku (string nullable snapshot),
quantity (int), unit_price_ghs (decimal 10,2), total_ghs (decimal 10,2), timestamps
```

### 10. `create_order_delivery_details_table`
```
id (uuid PK), order_id (uuid unique FK ecommerce_orders),
full_name, phone, alternative_phone (nullable), email (nullable),
country, region, city, suburb (nullable), street (nullable),
house_number (nullable), digital_address (nullable),
landmark (nullable), postal_code (nullable),
latitude (decimal 10,8 nullable), longitude (decimal 11,8 nullable),
delivery_notes (text nullable), timestamps
```

### 11. `create_ecommerce_order_status_history_table`
```
id (uuid PK), order_id (uuid FK ecommerce_orders), status (string),
notes (text nullable), created_by (uuid nullable FK users),
$table->timestamp('created_at')->useCurrent()   ← no updated_at (immutable log)
```

### 12. `create_ecommerce_order_shipments_table`
```
id (uuid PK), order_id (uuid unique FK ecommerce_orders),
tracking_number (string unique), courier (string nullable),
delivery_person_id (uuid nullable FK staff),
status (enum: pending, assigned, picked_up, in_transit, delivered, failed),
estimated_delivery (date nullable), dispatched_at (timestamp nullable),
delivered_at (timestamp nullable), notes (text nullable), timestamps
```

### 13. `create_ecommerce_shipment_tracking_logs_table`
```
id (uuid PK), order_shipment_id (uuid FK ecommerce_order_shipments),
latitude (decimal 10,8), longitude (decimal 11,8),
status (string nullable), recorded_at (timestamp), timestamps
```

### 14. `create_ecommerce_order_ratings_table`
```
id (uuid PK), order_id (uuid unique FK ecommerce_orders),
user_id (uuid FK users), rating (tinyint 1–5),
comment (text nullable),
$table->timestamp('created_at')->useCurrent()   ← no updated_at (immutable rating)
```

---

## Phase 2 — Models (14 new, 0 existing modified)

All use `HasUuids`. Soft deletes on `EcommerceProduct` and `EcommerceOrder`.

| Model | Table | Key relationships |
|---|---|---|
| `EcommerceCategory` | `ecommerce_categories` | BelongsTo Branch; BelongsTo parent (self); HasMany children; HasMany EcommerceProduct |
| `EcommerceProduct` | `ecommerce_products` | BelongsTo Branch; BelongsTo EcommerceCategory; HasMany EcommerceProductImage; HasMany EcommerceInventoryLog; HasMany EcommerceCartItem; HasMany EcommerceOrderItem |
| `EcommerceProductImage` | `ecommerce_product_images` | BelongsTo EcommerceProduct |
| `EcommerceInventoryLog` | `ecommerce_inventory_logs` | BelongsTo EcommerceProduct; BelongsTo Branch; BelongsTo User (createdBy) |
| `DeliveryAddress` | `delivery_addresses` | BelongsTo User |
| `EcommerceCart` | `ecommerce_carts` | BelongsTo User; BelongsTo Branch; HasMany EcommerceCartItem |
| `EcommerceCartItem` | `ecommerce_cart_items` | BelongsTo EcommerceCart; BelongsTo EcommerceProduct |
| `EcommerceOrder` | `ecommerce_orders` | BelongsTo User; BelongsTo Branch; HasMany EcommerceOrderItem; HasOne OrderDeliveryDetail; HasMany EcommerceOrderStatusHistory; HasOne EcommerceOrderShipment; HasOne EcommerceOrderRating |
| `EcommerceOrderItem` | `ecommerce_order_items` | BelongsTo EcommerceOrder; BelongsTo EcommerceProduct (nullable) |
| `OrderDeliveryDetail` | `order_delivery_details` | BelongsTo EcommerceOrder |
| `EcommerceOrderStatusHistory` | `ecommerce_order_status_history` | BelongsTo EcommerceOrder; BelongsTo User |
| `EcommerceOrderShipment` | `ecommerce_order_shipments` | BelongsTo EcommerceOrder; BelongsTo Staff (deliveryPerson); HasMany EcommerceShipmentTrackingLog |
| `EcommerceShipmentTrackingLog` | `ecommerce_shipment_tracking_logs` | BelongsTo EcommerceOrderShipment |
| `EcommerceOrderRating` | `ecommerce_order_ratings` | BelongsTo EcommerceOrder; BelongsTo User |

**`EcommerceOrder::booted()` extras:**
- Auto-generates `order_number` = `KMB-{YYYYMMDD}-{0001}` (daily counter, same pattern as `Expense::generateReference()`)
- `scopeForCustomer(Builder $q, string $userId)`

**`EcommerceProductImage::booted()` extras:**
- When `is_primary` is set to `true` on a new/updated image, automatically set all other images for the same product to `is_primary = false`.

**`EcommerceCart::booted()` extras:**
- `static::creating(fn ($m) => $m->expires_at ??= now()->addDays(7))`

**`EcommerceProduct::booted()` extras:**
- `static::creating(fn ($m) => $m->slug ??= Str::slug($m->name))`

---

## Phase 3 — Enums (4 new in `app/Enums/`)

All implement `HasLabel` + `HasColor` (Filament contracts, matching existing enum style):

- `EcommerceOrderStatus` — pending, awaiting_payment, paid, processing, packed, dispatched, in_transit, delivered, cancelled, refunded
- `EcommerceOrderPaymentStatus` — pending, paid, failed, refunded
- `EcommerceOrderShipmentStatus` — pending, assigned, picked_up, in_transit, delivered, failed
- `EcommerceInventoryLogType` — increase, decrease, adjustment, sale, return

---

## Phase 4 — Services (3 new in `app/Services/`)

### `EcommerceOrderService`
```php
createFromCart(User $user, string $deliveryAddressId, string $notes = ''): EcommerceOrder
```
Opens DB transaction, then:
1. Validates `DeliveryAddress` belongs to `$user` (ownership check)
2. Validates cart has ≥ 1 item; all items have `stock >= quantity` (throws `InsufficientStockException` on failure)
3. Resolves current rate via `ExchangeRateLog::latest('created_at')->first()?->rate ?? 1`
4. Creates `EcommerceOrder` + `EcommerceOrderItem` records (snapshotting name/sku/price from LIVE product, not cart item)
5. Creates `OrderDeliveryDetail` (snapshot of the selected delivery address)
6. Calls `EcommerceInventoryService::deductForOrder()` (stock deducted at checkout time)
7. Creates initial `EcommerceOrderStatusHistory` entry (status = **pending**)
8. Clears cart items: `$cart->items()->delete()` (cart record preserved)
9. Dispatches `EcommerceOrderPlaced` notification + calls `PushNotificationService::sendToUser()` (FCM)
10. Returns order

### `EcommerceInventoryService`
```php
adjust(EcommerceProduct $product, int $change, EcommerceInventoryLogType $type, User $user, ?string $reason = null, ?string $refId = null): EcommerceInventoryLog
deductForOrder(EcommerceOrder $order, User $user): void
restoreForOrder(EcommerceOrder $order, User $user): void
```
- `deductForOrder`: For each order item, calls `adjust(product, -quantity, 'sale', ...)` 
- `restoreForOrder`: For each order item, calls `adjust(product, +quantity, 'return', $user, 'order cancelled/refunded', $order->id)`

### `EcommercePaymentService`
```php
getGateway(string $country): string                          // 'paystack' | 'stripe'
initiatePaystack(EcommerceOrder $order, User $user): array
initiateStripe(EcommerceOrder $order, User $user): array
verifyAndRecordPaystack(string $reference): EcommerceOrder
verifyAndRecordStripe(string $paymentIntentId): EcommerceOrder
```
- `getGateway()`: `stripos($country, 'ghana') !== false ? 'paystack' : 'stripe'` (case-insensitive Ghana check)
- Paystack: follows `CustomerPaymentController` pattern — `Http::withToken()` to Paystack API. Amount = `(int) round($order->total_ghs * 100)` (pesewas).
- Stripe: uses `\Stripe\StripeClient`. Amount = `(int) round($order->total_usd * 100)` (cents). **Always use `(int) round()` to avoid float precision errors.**
- On success: sets `payment_status = paid`, `status = paid`, appends status history, dispatches `EcommerceOrderPaymentConfirmed` notification + FCM

---

## Phase 5 — API Resources (8 new in `app/Http/Resources/Ecommerce/`)

| Resource | Contents |
|---|---|
| `EcommerceCategoryResource` | id, name, slug, image_url, parent_id, children[], products_count (via `withCount`) |
| `EcommerceProductResource` | id, name, sku, price_ghs, price_usd, discount_price_ghs, stock, is_active, is_featured, category (EcommerceCategoryResource), images[] |
| `EcommerceProductImageResource` | id, path, url (`asset('storage/' . $this->path)`), sort_order, is_primary |
| `EcommerceCartResource` | branch_id, items[] (EcommerceCartItemResource with live stock), subtotal_ghs, coupon_code |
| `EcommerceCartItemResource` | id, quantity, price_ghs, stock (live from product), in_stock (bool), is_available (false if product soft-deleted), product (EcommerceProductResource) |
| `EcommerceOrderResource` | id, order_number, status, payment_status, total_ghs, items_count, created_at, user (id, name, email — for staff view) |
| `EcommerceOrderDetailResource` | full order + items + delivery_detail + shipment + status_history + rating (whenLoaded) |
| `DeliveryAddressResource` | id + all address fields + is_default |

---

## Phase 6 — Notifications (7 new in `app/Notifications/Ecommerce/`)

All: `implements ShouldQueue`, `use Queueable`, `via()` returns `['database']`. FCM fired separately.
All FCM data payloads include `order_id` so the client app can navigate to the order on tap.

| Class | Fired by | FCM data |
|---|---|---|
| `EcommerceOrderPlaced` | `EcommerceOrderService::createFromCart()` | type: ecommerce_order, order_id |
| `EcommerceOrderApproved` | `AdminEcommerceOrderController::approve()` | type: ecommerce_order, order_id |
| `EcommerceOrderPaymentConfirmed` | `EcommercePaymentService::verify*()` | type: ecommerce_order, order_id |
| `EcommerceOrderProcessing` | `AdminEcommerceOrderController::process()` | type: ecommerce_order, order_id |
| `EcommerceOrderPacked` | `AdminEcommerceOrderController::pack()` | type: ecommerce_order, order_id |
| `EcommerceOrderDispatched` | `AdminEcommerceOrderController::dispatch()` | type: ecommerce_order, order_id, tracking_number |
| `EcommerceOrderDelivered` | `AdminEcommerceOrderController::deliver()` | type: ecommerce_order, order_id |

---

## Phase 7 — Controllers

### Staff — `App\Http\Controllers\Api\V1\Ecommerce\Admin\`
All extend `BaseApiController`. Middleware: `auth:sanctum`. Branch via `resolveBranch()` / `X-Branch-Id`.

| Controller | Methods |
|---|---|
| `EcommerceCategoryController` | CRUD + `toggleActive()` |
| `AdminEcommerceProductController` | CRUD + `uploadImage()`, `deleteImage()`, `toggleActive()`, `toggleFeatured()` |
| `EcommerceInventoryController` | `adjust()`, `logs()`, `lowStock()`, `outOfStock()` |
| `AdminEcommerceOrderController` | `index()`, `show()`, `approve()`, `process()`, `pack()`, `dispatch()`, `deliver()`, `cancel()`, `refund()` |
| `AdminEcommerceShipmentController` | `create()`, `update()`, `addTracking()` |
| `EcommerceSalesReportController` | `sales(?period=daily\|weekly\|monthly)`, `orders()`, `productPerformance()`, `lowStock()` |

**`AdminEcommerceOrderController::index()` filters:** `?status=`, `?date_from=`, `?date_to=`, `?search=` (order_number or customer name). Paginated via `paginated()`.

### Customer — `App\Http\Controllers\Api\V1\Ecommerce\Customer\`
All extend `CustomerBaseController`. Middleware: `auth:sanctum, customer`. Branch via `auth()->user()->branch_id`.

> `CustomerBaseController` needs a new helper method: `protected function customerBranchId(): string { return auth()->user()->branch_id; }` — referenced throughout customer controllers.

| Controller | Methods |
|---|---|
| `EcommerceHomeController` | `index()` — featured products, new arrivals, best sellers, active categories (all scoped to `customerBranchId()`) |
| `EcommerceProductCatalogController` | `index()` (search, filter: category_id/price_min/price_max/in_stock, sort: newest/popular) **always `where('is_active', true)`**, `show()` |
| `EcommerceCategoryBrowseController` | `index()`, `products($id)` |
| `EcommerceCartController` | `show()`, `addItem()`, `updateItem($itemId)`, `removeItem($itemId)`, `clear()`, `applyCoupon()` (stub 501) |
| `DeliveryAddressController` | CRUD + `setDefault($id)` |
| `EcommerceCheckoutController` | `checkout()` |
| `CustomerEcommerceOrderController` | `index(?status=)`, `show()`, `tracking()`, `cancel()`, `rate()` |
| `EcommercePaymentController` | `initiate()`, `verifyPaystack()`, `verifyStripe()` |

### Webhooks — `App\Http\Controllers\Api\V1\Ecommerce\`
- `EcommercePaystackWebhookController::handle()` — HMAC-SHA512 + idempotency check
- `EcommerceStripeWebhookController::handle()` — `\Stripe\Webhook::constructEvent()` + idempotency check

---

## Phase 8 — Stripe Package

```bash
composer require stripe/stripe-php
```

Add to `config/services.php`:
```php
'stripe' => [
    'secret_key'     => env('STRIPE_SECRET_KEY'),
    'publishable_key'=> env('STRIPE_PUBLISHABLE_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

Add to `.env.example`: `STRIPE_SECRET_KEY=`, `STRIPE_PUBLISHABLE_KEY=`, `STRIPE_WEBHOOK_SECRET=`

---

## Phase 9 — Cart Cleanup Command

New artisan command `app:cleanup-expired-ecommerce-carts` registered in `routes/console.php`:
```php
Schedule::command('app:cleanup-expired-ecommerce-carts')->daily();
```
Deletes `EcommerceCart` records where `expires_at < now()` (cascade-deletes `EcommerceCartItem`).

---

## Phase 10 — Routes

Add inside the existing `Route::prefix('v1')->group()` in `routes/api.php`:

```php
Route::prefix('marketplace')->group(function () {

    // Public webhooks — no Sanctum, signature-verified
    Route::post('webhooks/paystack', [EcommercePaystackWebhookController::class, 'handle']);
    Route::post('webhooks/stripe',   [EcommerceStripeWebhookController::class, 'handle']);

    // Customer marketplace
    Route::middleware(['auth:sanctum', 'customer'])->group(function () {
        Route::get('home',                              [EcommerceHomeController::class, 'index']);
        Route::get('categories',                        [EcommerceCategoryBrowseController::class, 'index']);
        Route::get('categories/{id}/products',          [EcommerceCategoryBrowseController::class, 'products']);
        Route::get('products',                          [EcommerceProductCatalogController::class, 'index']);
        Route::get('products/{id}',                     [EcommerceProductCatalogController::class, 'show']);

        Route::get('cart',                              [EcommerceCartController::class, 'show']);
        Route::post('cart/items',                       [EcommerceCartController::class, 'addItem']);
        Route::put('cart/items/{itemId}',               [EcommerceCartController::class, 'updateItem']);
        Route::delete('cart/items/{itemId}',            [EcommerceCartController::class, 'removeItem']);
        Route::delete('cart',                           [EcommerceCartController::class, 'clear']);
        Route::post('cart/coupon',                      [EcommerceCartController::class, 'applyCoupon']);

        Route::apiResource('addresses', DeliveryAddressController::class);
        Route::patch('addresses/{id}/default',          [DeliveryAddressController::class, 'setDefault']);

        Route::post('checkout',                         [EcommerceCheckoutController::class, 'checkout']);

        Route::get('orders',                            [CustomerEcommerceOrderController::class, 'index']);
        Route::get('orders/{id}',                       [CustomerEcommerceOrderController::class, 'show']);
        Route::get('orders/{id}/tracking',              [CustomerEcommerceOrderController::class, 'tracking']);
        Route::delete('orders/{id}',                    [CustomerEcommerceOrderController::class, 'cancel']);
        Route::post('orders/{id}/rate',                 [CustomerEcommerceOrderController::class, 'rate']);

        Route::post('payments/initiate',                [EcommercePaymentController::class, 'initiate']);
        Route::post('payments/verify/paystack',         [EcommercePaymentController::class, 'verifyPaystack']);
        Route::post('payments/verify/stripe',           [EcommercePaymentController::class, 'verifyStripe']);
    });

    // Staff admin
    Route::middleware('auth:sanctum')->prefix('staff')->group(function () {
        Route::apiResource('categories', Admin\EcommerceCategoryController::class);
        Route::patch('categories/{id}/toggle-active',    [Admin\EcommerceCategoryController::class, 'toggleActive']);

        Route::apiResource('products', Admin\AdminEcommerceProductController::class);
        Route::post('products/{id}/images',              [Admin\AdminEcommerceProductController::class, 'uploadImage']);
        Route::delete('products/{id}/images/{imageId}',  [Admin\AdminEcommerceProductController::class, 'deleteImage']);
        Route::patch('products/{id}/toggle-active',      [Admin\AdminEcommerceProductController::class, 'toggleActive']);
        Route::patch('products/{id}/toggle-featured',    [Admin\AdminEcommerceProductController::class, 'toggleFeatured']);

        Route::post('inventory/adjust',                  [Admin\EcommerceInventoryController::class, 'adjust']);
        Route::get('inventory/logs',                     [Admin\EcommerceInventoryController::class, 'logs']);
        Route::get('inventory/low-stock',                [Admin\EcommerceInventoryController::class, 'lowStock']);
        Route::get('inventory/out-of-stock',             [Admin\EcommerceInventoryController::class, 'outOfStock']);

        Route::get('orders',                             [Admin\AdminEcommerceOrderController::class, 'index']);
        Route::get('orders/{id}',                        [Admin\AdminEcommerceOrderController::class, 'show']);
        Route::patch('orders/{id}/approve',              [Admin\AdminEcommerceOrderController::class, 'approve']);
        Route::patch('orders/{id}/process',              [Admin\AdminEcommerceOrderController::class, 'process']);
        Route::patch('orders/{id}/pack',                 [Admin\AdminEcommerceOrderController::class, 'pack']);
        Route::patch('orders/{id}/dispatch',             [Admin\AdminEcommerceOrderController::class, 'dispatch']);
        Route::patch('orders/{id}/deliver',              [Admin\AdminEcommerceOrderController::class, 'deliver']);
        Route::patch('orders/{id}/cancel',               [Admin\AdminEcommerceOrderController::class, 'cancel']);
        Route::patch('orders/{id}/refund',               [Admin\AdminEcommerceOrderController::class, 'refund']);

        Route::post('orders/{orderId}/shipment',         [Admin\AdminEcommerceShipmentController::class, 'create']);
        Route::put('orders/{orderId}/shipment',          [Admin\AdminEcommerceShipmentController::class, 'update']);
        Route::post('orders/{orderId}/shipment/tracking',[Admin\AdminEcommerceShipmentController::class, 'addTracking']);

        Route::get('reports/sales',                      [Admin\EcommerceSalesReportController::class, 'sales']);
        Route::get('reports/orders',                     [Admin\EcommerceSalesReportController::class, 'orders']);
        Route::get('reports/products',                   [Admin\EcommerceSalesReportController::class, 'productPerformance']);
        Route::get('reports/low-stock',                  [Admin\EcommerceSalesReportController::class, 'lowStock']);
    });
});
```

---

## File Creation Summary

| Layer | Count | Files/Paths |
|---|---|---|
| Migrations | **14** | 14 tables listed in Phase 1 |
| Models | **14** | EcommerceCategory, EcommerceProduct, EcommerceProductImage, EcommerceInventoryLog, DeliveryAddress, EcommerceCart, EcommerceCartItem, EcommerceOrder, EcommerceOrderItem, OrderDeliveryDetail, EcommerceOrderStatusHistory, EcommerceOrderShipment, EcommerceShipmentTrackingLog, EcommerceOrderRating |
| Enums | 4 | EcommerceOrderStatus, EcommerceOrderPaymentStatus, EcommerceOrderShipmentStatus, EcommerceInventoryLogType |
| Services | 3 | EcommerceOrderService, EcommerceInventoryService, EcommercePaymentService |
| Resources | 8 | EcommerceCategoryResource, EcommerceProductResource, EcommerceProductImageResource, EcommerceCartResource, EcommerceCartItemResource, EcommerceOrderResource, EcommerceOrderDetailResource, DeliveryAddressResource |
| Notifications | 7 | EcommerceOrderPlaced, EcommerceOrderApproved, EcommerceOrderPaymentConfirmed, EcommerceOrderProcessing, EcommerceOrderPacked, EcommerceOrderDispatched, EcommerceOrderDelivered |
| Controllers — Admin | 6 | EcommerceCategoryController, AdminEcommerceProductController, EcommerceInventoryController, AdminEcommerceOrderController, AdminEcommerceShipmentController, EcommerceSalesReportController |
| Controllers — Customer | 8 | EcommerceHomeController, EcommerceProductCatalogController, EcommerceCategoryBrowseController, EcommerceCartController, DeliveryAddressController, EcommerceCheckoutController, CustomerEcommerceOrderController, EcommercePaymentController |
| Controllers — Webhooks | 2 | EcommercePaystackWebhookController, EcommerceStripeWebhookController |
| Commands | 1 | `app/Console/Commands/CleanupExpiredEcommerceCarts.php` |
| Model change | 1 | `CustomerBaseController` — add `customerBranchId()` helper |
| Routes | 1 | Update `routes/api.php` |
| Config | 1 | Update `config/services.php` + `.env.example` |

---

## Reused Existing Code

| Existing file | Reused for |
|---|---|
| `app/Http/Controllers/Api/V1/BaseApiController.php` | Staff ecommerce controllers extend it |
| `app/Http/Controllers/Api/V1/Customer/CustomerBaseController.php` | Customer ecommerce controllers + new `customerBranchId()` helper |
| `app/Http/Controllers/Api/V1/Customer/CustomerPaymentController.php` | Paystack initiate/verify pattern |
| `app/Http/Controllers/Api/V1/Customer/PaystackWebhookController.php` | HMAC-SHA512 verification pattern |
| `app/Services/PushNotificationService.php` | FCM push in EcommerceOrderService + status controllers |
| `app/Notifications/PaymentReceived.php` | Database-only notification class pattern |
| `app/Http/Middleware/EnsureCustomer.php` | Gates all customer ecommerce routes |
| `app/Models/ExchangeRateLog.php` | Current GHS rate in `EcommerceOrderService` |
| `app/Models/Expense.php::generateReference()` | Order number generation pattern |
| `app/Http/Controllers/Api/V1/ShipmentController::uploadMedia()` | Image upload pattern (public disk) |

---

## Phase 11 — Staff Mobile App (`D:\Mobile\rdd-shipping`)

### Architecture
Expo Router + Zustand + React Query + Axios. Existing `src/api/client.ts` already sends `X-Branch-Id` header on every request. Staff marketplace endpoints are at `/api/v1/marketplace/staff/*` — no API client changes needed.

### New API Module — `src/api/ecommerce.ts`
Follows same pattern as `src/api/shipments.ts`:
```typescript
export const ecommerceApi = {
  categories:    { list, create, update, destroy, toggleActive },
  products:      { list, create, update, destroy, uploadImage, deleteImage, toggleActive, toggleFeatured },
  inventory:     { adjust, logs, lowStock, outOfStock },
  orders:        { list, show, approve, process, pack, dispatch, deliver, cancel, refund },
  shipment:      { create, update, addTracking },
  reports:       { sales, orders, products, lowStock },
};
```

### New Screen Files (under `app/(app)/(main)/ecommerce/`)

| File | Purpose |
|---|---|
| `categories/index.tsx` | List categories, toggle active |
| `categories/create.tsx` | Form: name, description, image picker, parent category selector |
| `categories/[id].tsx` | Edit category + delete |
| `products/index.tsx` | Product list with filters (active/inactive, low-stock, out-of-stock, category) |
| `products/create.tsx` | Multi-section form: basic info, pricing (GHS/USD), stock, category, repeatable key-value specs, image uploads (sequential with "Uploading N of M..." progress) |
| `products/[id].tsx` | Edit form + image gallery management + quick stock adjust |
| `inventory/index.tsx` | Tabs: Low Stock / Out of Stock / Logs |
| `inventory/adjust.tsx` | Stock adjustment: product picker, type, quantity, reason |
| `orders/index.tsx` | Order list: status filter chips, date range, count badges |
| `orders/[id].tsx` | Order detail: items, delivery address, status timeline, action buttons (Dispatch disabled until shipment assigned), Approve modal has "Shipping Fee" input, customer rating display (read-only) |
| `reports/index.tsx` | Period selector, revenue KPI cards, order count, product performance table, low-stock alert count |

### Drawer Navigation Update — `app/(app)/(main)/_layout.tsx`
New "E-Commerce" section:
```
E-Commerce:
  ├── Products           → /ecommerce/products
  ├── Categories         → /ecommerce/categories
  ├── Inventory          → /ecommerce/inventory
  ├── Orders             → /ecommerce/orders
  └── E-Com Reports      → /ecommerce/reports
```
Existing `Products` drawer item (→ `/products`) is **not touched** — it shows the old Product model for quotations.

### New TypeScript Types — `src/types/index.ts`
```typescript
interface EcommerceCategory { id, name, slug, image, parent_id, is_active, children, products_count }
interface EcommerceProduct  { id, name, sku, price_ghs, price_usd, discount_price_ghs, stock, low_stock_threshold, is_active, is_featured, category, images }
interface EcommerceOrder    { id, order_number, status, payment_status, payment_gateway, subtotal_ghs, total_ghs, items_count, created_at, user, delivery_detail }
interface EcommerceOrderDetail extends EcommerceOrder { items[], delivery_detail, status_history[], shipment?, rating? }
interface InventoryLog      { id, type, quantity_change, quantity_before, quantity_after, reason, created_by, created_at }
```

---

## Phase 12 — Client Mobile App (`D:\Mobile\rdd-shipping-client`)

### Architecture
Expo Router + Zustand + React Query + Axios. Existing `src/api/client.ts` uses `baseURL = /api/v1/customer`. Marketplace is a **separate Axios instance** to avoid touching existing code.

### New Axios Instance — `src/api/marketplaceClient.ts`
```typescript
export const marketplaceApiClient = axios.create({
  baseURL: `${BASE_URL}/api/v1/marketplace`,
  timeout: 15_000,
});
// Same request interceptor: attaches customer_token Bearer token
// Same 401 interceptor: clears session on unauthorized
```

### New API Module — `src/api/marketplace.ts`
```typescript
export const marketplaceApi = {
  home:      { index() },
  categories:{ list(), products(id) },
  products:  { list(params), show(id) },
  cart:      { show(), addItem(data), updateItem(id,data), removeItem(id), clear() },
  addresses: { list(), create(data), update(id,data), destroy(id), setDefault(id) },
  checkout:  { checkout(data) },
  orders:    { list(params), show(id), tracking(id), cancel(id), rate(id, data) },
  payments:  { initiate(data), verifyPaystack(data), verifyStripe(data) },
};
```

### New Tab Structure — `src/app/(app)/(tabs)/_layout.tsx`
Replace 4 tabs with 5:
```
[Home]  [Shop]  [Cart🛒]  [Orders]  [Account]
```
Existing Shipments / Pickups tabs move to Home screen quick actions and Account page links.

### New Tab Screen Files
| File | Purpose |
|---|---|
| `shop/index.tsx` | Marketplace Home: hero banner, category row (horizontal scroll with products_count), featured products grid, new arrivals (FlashList), best sellers |
| `shop/catalog.tsx` | Catalog: search with debounce, category filter chips, price range, sort (newest/popular), paginated FlashList |
| `cart/index.tsx` | Cart: FlashList of cart items with qty stepper, live stock warnings ("Only N left"), subtotal, proceed to checkout |
| `orders/index.tsx` | Order history: status filter chips, FlashList of order cards |
| `account/index.tsx` | Existing screen + "My Delivery Addresses" and "My Orders" quick links |

### New Stack Screen Files
| File | Purpose |
|---|---|
| `product/[id].tsx` | Product detail: image carousel, name, price (with discount strike-through), stock badge, description, specs accordion, Add to Cart / Buy Now |
| `addresses/index.tsx` | Address list: default badge, add/edit/delete/set-default |
| `addresses/create.tsx` | Address form with cascading country/region/city pickers (reuse pattern from `pickups/request.tsx`) |
| `addresses/[id].tsx` | Edit address (same as create, pre-filled) |
| `checkout/index.tsx` | Multi-step: Step 1 Review Cart → Step 2 Select Address → Step 3 Review Summary → Step 4 Payment Method → Step 5 Place Order |
| `checkout/payment.tsx` | Paystack WebView or Stripe card form, with deep-link callback handling |
| `checkout/success.tsx` | Confirmation: order number, items, "Track Order" / "Continue Shopping" |
| `orders/[id].tsx` | Order detail: items, delivery address, payment badge, status timeline, tracking section, rating UI (visible only after delivery + no existing rating) |

### Rating UI in `orders/[id].tsx`
- Show "Rate This Delivery" card when `order.status === 'delivered'` AND `order.rating === null`
- 5-star interactive picker + optional comment
- Submit → `marketplaceApi.orders.rate(orderId, { rating, comment })`
- After submit → show "Thanks for your rating: ★★★★☆" (read-only)

### Zustand Store — `src/stores/cartStore.ts`
```typescript
interface CartStore {
  itemCount: number;         // drives tab badge
  setItemCount: (n: number) => void;
}
```

### Payment Integration
- **Paystack:** Open `authorization_url` in `expo-web-browser`. On deep-link return (`rdd-client://payment/complete`), call `verifyPaystack({ reference })`.
- **Stripe:** Use `@stripe/stripe-react-native` (`npm install` + `expo prebuild`). Wrap root in `<StripeProvider publishableKey={process.env.EXPO_PUBLIC_STRIPE_PK!}>`.
- Add `EXPO_PUBLIC_STRIPE_PK=pk_live_...` to `.env`.

### Deep Linking Setup — `app.json`
```json
{
  "expo": {
    "scheme": "rdd-client",
    "intentFilters": [
      { "action": "VIEW", "data": [{ "scheme": "rdd-client", "host": "payment" }], "category": ["BROWSABLE", "DEFAULT"] }
    ]
  }
}
```

### Push Notification Tap — `src/app/_layout.tsx`
```typescript
Notifications.addNotificationResponseReceivedListener((response) => {
  const data = response.notification.request.content.data;
  if (data.type === 'ecommerce_order' && data.order_id) {
    router.push(`/orders/${data.order_id}`);
  }
});
```

---

## Phase 13 — State Machine Enforcement

### Order Status Flow
```
checkout → PENDING          (stock deducted here)
staff approve() → AWAITING_PAYMENT  + EcommerceOrderApproved notification
customer pays → PAID
staff process() → PROCESSING
staff pack() → PACKED
staff dispatch() → DISPATCHED  (requires shipment to exist first)
staff addTracking() / auto → IN_TRANSIT
staff deliver() → DELIVERED
```

### Cancel / Refund Stock Rules
Stock restoration via `EcommerceInventoryService::restoreForOrder()`:
```
If cancelled from: pending OR awaiting_payment → restore stock (deducted at checkout)
If cancelled from: paid OR processing OR packed → restore stock
If cancelled from: dispatched OR in_transit → do NOT restore (goods have left)
If delivered → do NOT restore
```

Cancellation sets `cancelled_by = auth()->id()` on the order.

### Per-Method Transitions (AdminEcommerceOrderController)
```php
approve()   { from: pending              → awaiting_payment; set approved_by; notify EcommerceOrderApproved }
process()   { from: paid                 → processing;       notify EcommerceOrderProcessing }
pack()      { from: processing           → packed;           notify EcommerceOrderPacked }
dispatch()  { from: packed               → dispatched;       abort_unless shipment exists; notify EcommerceOrderDispatched }
deliver()   { from: dispatched|in_transit→ delivered;        notify EcommerceOrderDelivered }
cancel()    { from: any except delivered|refunded → cancelled; restore stock per rules above }
refund()    { from: paid|delivered       → refunded }
```

---

## Phase 14 — Additional Implementation Notes

### Payment Security
```php
// EcommercePaymentController::initiate()
abort_unless($order->user_id === auth()->id(), 403, 'Access denied.');
abort_unless($order->status->value === 'awaiting_payment', 422, 'Order is not ready for payment.');
```

### Delivery Address Ownership at Checkout
```php
// EcommerceOrderService::createFromCart()
$address = DeliveryAddress::where('user_id', $user->id)->findOrFail($deliveryAddressId);
```

### Product Visibility to Customers
`EcommerceProductCatalogController` always applies `->where('is_active', true)->where('branch_id', $this->customerBranchId())` in both `index()` and `show()`.

### Gateway Country Normalization
```php
// EcommercePaymentService::getGateway()
return stripos($country, 'ghana') !== false ? 'paystack' : 'stripe';
```

### Cart `firstOrCreate` Pattern
`EcommerceCartController::show()` and `addItem()` use:
```php
$cart = EcommerceCart::firstOrCreate(
    ['user_id' => auth()->id()],
    ['branch_id' => $this->customerBranchId()]
);
```
Cart is auto-created on first access if none exists.

### Paystack Idempotency
```php
if (EcommerceOrder::where('payment_reference', $reference)->where('payment_status', 'paid')->exists()) {
    return response()->json(['status' => 'ok']);
}
```

### Stripe Integer Amount
```php
'amount' => (int) round($order->total_usd * 100),  // cents — avoids float precision
```

### Primary Image Swap — `EcommerceProductImage::booted()`
```php
static::saved(function ($image) {
    if ($image->is_primary) {
        static::where('ecommerce_product_id', $image->ecommerce_product_id)
              ->where('id', '!=', $image->id)
              ->update(['is_primary' => false]);
    }
});
```

### Soft-Deleted Products in Cart
`EcommerceCartResource` loads items with `withTrashed()` on product; marks `is_available: false` for trashed products. Checkout controller rejects orders containing unavailable items.

### Specifications JSON Format
```json
[{"key": "Material", "value": "100% Cotton"}, {"key": "Weight", "value": "350g"}]
```
Staff app product form uses a repeatable key-value row component.

### Slug Uniqueness Per Branch
`unique(branch_id, slug)` on both `ecommerce_categories` and `ecommerce_products` — same slug can exist across branches.

### Category Image Storage
Category image stored to `storage/public/ecommerce-category-images/{id}/`. URL via `asset('storage/' . $path)`. Same public disk pattern as product images.

---

## Phase 15 — Filament Admin Panel Resources (Optional)

Located in `app/Filament/Resources/` — follow existing `ProductResource` pattern:

| Resource | Key fields |
|---|---|
| `EcommerceCategoryResource` | name, parent (Select), is_active (Toggle), sort_order |
| `EcommerceProductResource` | name, sku, category (Select), price_ghs, stock, is_active (Toggle), is_featured (Toggle) |
| `EcommerceOrderResource` | order_number, status (Badge), payment_status (Badge), total_ghs, user, created_at — read-only + status action buttons |

Register in `AdminPanelProvider` under a new **"E-Commerce"** navigation group.

After adding Filament resources, run:
```bash
php artisan shield:generate --all   # regenerates RBAC permissions for new resources
```

---

## Verification Steps

1. `php artisan migrate` — confirm 14 new tables; `products` table unchanged.
2. `POST /api/v1/marketplace/staff/categories` + `POST /api/v1/marketplace/staff/products` — confirm `ecommerce_products` with `is_active=false`, `stock=0`.
3. `POST /api/v1/marketplace/staff/inventory/adjust { type: increase, quantity: 50 }` — confirm `stock=50` + inventory log.
4. `GET /api/v1/marketplace/home` — products scoped to customer's branch_id.
5. Cart flow: `POST cart/items → GET cart → PUT cart/items/{id} → DELETE cart/items/{id}`.
6. `POST checkout { delivery_address_id }` — confirm order `status=pending` + `OrderDeliveryDetail` snapshot. Inactive products rejected.
7. `PATCH staff/orders/{id}/approve { shipping_fee_ghs: 10 }` — confirm `status=awaiting_payment`, totals recalculated, `approved_by` set.
8. `POST payments/initiate` on `awaiting_payment` order → `payment_gateway=paystack` (Ghana) or `stripe` (international).
9. Simulate Paystack webhook → confirm `payment_status=paid`, `EcommerceInventoryLog` sale record, notification in DB.
10. Staff workflow: process → pack → dispatch (fails if no shipment) → deliver → confirm status history has 7 rows.
11. `POST orders/{id}/rate { rating: 5 }` — confirm `EcommerceOrderRating` created; second attempt returns 422.
12. Cancel from `pending` → stock restored. Cancel from `dispatched` → stock NOT restored.
13. `GET inventory/low-stock` — products with `stock < low_stock_threshold` appear.
