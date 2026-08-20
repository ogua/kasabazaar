@extends('storefront.layouts.app')

@section('title', 'Returns & Refunds Policy')
@section('meta_description', 'How to cancel an order, return an item and get a refund on ' . config('app.name') . ', including timeframes, non-returnable items and how disputes with vendors are resolved.')

@section('content')
    @php
        $sections = [
            'Cancelling before dispatch',
            'The return window',
            'What you can return',
            'What you cannot return',
            'How to start a return',
            'Return shipping costs',
            'Refunds',
            'Replacements and exchanges',
            'Disputes with a vendor',
            'Vendor obligations',
        ];

        $intro = 'Something not right? You have '.config('group.returns.window_days').' days from delivery to raise a return, and approved refunds are back with you within '.config('group.returns.refund_days').' days.';
    @endphp

    <x-storefront.legal-layout title="Returns &amp; Refunds Policy" :intro="$intro" :sections="$sections">
        <section id="cancelling-before-dispatch">
            <h2>1. Cancelling before dispatch</h2>
            <p>
                You can cancel any order <strong>free of charge</strong> at any time before the vendor dispatches it.
                Open <a href="{{ route('storefront.account.orders') }}">My Orders</a>, choose the order and select
                Cancel. The full amount, including delivery, is refunded to your original payment method.
            </p>
            <p>
                Once an order shows as <strong>Dispatched</strong> it can no longer be cancelled — at that point it
                becomes a return, covered by the rest of this policy.
            </p>
        </section>

        <section id="the-return-window">
            <h2>2. The return window</h2>
            <p>
                You have <strong>{{ config('group.returns.window_days') }} days from the delivery date</strong> to
                raise a return request. For items that arrive damaged, incomplete or plainly wrong, tell us within
                <strong>48 hours</strong> instead — those claims are settled fastest and at no cost to you (see our
                <a href="{{ route('storefront.delivery-policy') }}">Delivery Policy</a>).
            </p>
            <p>
                Items must come back in the condition you received them: unused, with all parts, manuals, accessories,
                free gifts, tags and — where the packaging is part of the product's value — the original box.
            </p>
        </section>

        <section id="what-you-can-return">
            <h2>3. What you can return</h2>
            <p>We accept returns where the item:</p>
            <ul>
                <li>arrived <strong>damaged or defective</strong>;</li>
                <li>is <strong>not what you ordered</strong> — wrong item, wrong size, wrong colour, wrong quantity;</li>
                <li>is <strong>materially different from its listing</strong> — missing a feature the listing claimed, or in a materially worse condition than described;</li>
                <li>arrived <strong>incomplete</strong>, with parts or accessories missing;</li>
                <li>is <strong>past its expiry date</strong> on arrival, or has an unreasonably short remaining shelf life;</li>
                <li>is <strong>counterfeit</strong> or not genuine.</li>
            </ul>
            <p>
                Some vendors additionally accept change-of-mind returns. Where they do, it is stated on the product
                page; the item must be unused and in its sealed original packaging, and you pay return shipping.
            </p>
        </section>

        <section id="what-you-cannot-return">
            <h2>4. What you cannot return</h2>
            <p>
                For health, safety and hygiene reasons, the following cannot be returned unless they arrive faulty,
                damaged or wrongly supplied:
            </p>
            <ul>
                <li>underwear, swimwear, and pierced jewellery;</li>
                <li>cosmetics, skincare, fragrance and personal-care items once opened or the seal is broken;</li>
                <li>fresh food, frozen food, drinks and other perishables;</li>
                <li>made-to-order, personalised or custom-built items;</li>
                <li>digital goods, gift cards, airtime and vouchers once delivered or redeemed;</li>
                <li>items clearly listed as final sale or clearance;</li>
                <li>items damaged by misuse, unauthorised repair, or normal wear after use.</li>
            </ul>
            <p>Nothing here limits any right you have under Ghanaian consumer-protection law.</p>
        </section>

        <section id="how-to-start-a-return">
            <h2>5. How to start a return</h2>
            <ol>
                <li>Open <a href="{{ route('storefront.account.orders') }}">My Orders</a> and select the order.</li>
                <li>Choose <strong>Request a return</strong>, pick the items, and tell us the reason.</li>
                <li>Upload photos where the item is damaged, wrong or not as described — this settles most claims without a physical inspection.</li>
                <li>The vendor has <strong>two business days</strong> to respond. If they do not, we decide the claim ourselves.</li>
                <li>Once approved you get a return authorisation and instructions — either a collection is arranged or you are given a drop-off point.</li>
            </ol>
            <p>
                Checked out as a guest? Email
                <a href="mailto:{{ config('group.contact.email') }}">{{ config('group.contact.email') }}</a> with your
                order number instead.
            </p>
            <p><strong>Do not send an item back before your return is authorised</strong> — unauthorised returns cannot be tracked or refunded.</p>
        </section>

        <section id="return-shipping-costs">
            <h2>6. Return shipping costs</h2>
            <table>
                <thead>
                    <tr>
                        <th>Reason for return</th>
                        <th>Who pays return shipping</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Damaged, defective, wrong or not as described</td>
                        <td>The vendor. We arrange free collection.</td>
                    </tr>
                    <tr>
                        <td>Incomplete or counterfeit</td>
                        <td>The vendor. We arrange free collection.</td>
                    </tr>
                    <tr>
                        <td>Change of mind, where the vendor allows it</td>
                        <td>You. The cost is deducted from your refund unless you ship it back yourself.</td>
                    </tr>
                    <tr>
                        <td>Order refused on delivery without a valid reason</td>
                        <td>You. Original and return delivery costs are deducted from the refund.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section id="refunds">
            <h2>7. Refunds</h2>
            <p>
                Once the returned item reaches the vendor and passes inspection — or once we approve a photo-based
                claim without a physical return — the refund is issued to your <strong>original payment
                method</strong> within {{ config('group.returns.refund_days') }} days.
            </p>
            <ul>
                <li><strong>Mobile Money and card refunds via Paystack</strong> typically land within 3–7 business days of being issued.</li>
                <li><strong>Card refunds via Stripe</strong> typically land within 5–10 business days, depending on your bank.</li>
                <li>Where the whole order is returned, the original delivery charge is refunded too. Where only part of it comes back, the delivery charge is not refunded.</li>
            </ul>
            <p>
                We cannot refund to a different account or a different person from the one that paid. If your original
                payment method has since been closed, contact us and we will agree an alternative.
            </p>
        </section>

        <section id="replacements-and-exchanges">
            <h2>8. Replacements and exchanges</h2>
            <p>
                For a faulty, damaged or wrongly supplied item you can ask for a replacement instead of a refund. If
                the vendor has the item in stock it is sent at no cost to you; if not, we refund you in full.
            </p>
            <p>
                Straight exchanges for a different size, colour or model are not supported across the marketplace —
                return the item for a refund and place a new order.
            </p>
        </section>

        <section id="disputes-with-a-vendor">
            <h2>9. Disputes with a vendor</h2>
            <p>
                Vendors sell in their own name, but you are not left to argue with them alone. If a vendor rejects
                your return and you disagree, escalate it to us from the same order screen or by writing to
                <a href="mailto:{{ config('group.contact.email') }}">{{ config('group.contact.email') }}</a>.
            </p>
            <p>
                We review the listing, the photos, the messages between you and the vendor, and the delivery record,
                and decide within <strong>five business days</strong>. Because we hold the payment until the vendor is
                settled, we can refund you directly where the claim is upheld — you are not dependent on the vendor
                agreeing.
            </p>
        </section>

        <section id="vendor-obligations">
            <h2>10. Vendor obligations</h2>
            <p>Every vendor on {{ config('app.name') }} agrees, as a condition of selling here, to:</p>
            <ul>
                <li>respond to a return request within two business days;</li>
                <li>accept returns that meet the grounds in section 3;</li>
                <li>bear the cost of returns caused by their own error;</li>
                <li>accept our decision where a dispute is escalated to us.</li>
            </ul>
            <p>
                A vendor who repeatedly fails these obligations has settlement withheld and may be removed from the
                marketplace, as set out in our <a href="{{ route('storefront.terms') }}">Terms of Use</a>.
            </p>
        </section>
    </x-storefront.legal-layout>
@endsection
