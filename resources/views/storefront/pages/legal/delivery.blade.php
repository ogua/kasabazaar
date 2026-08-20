@extends('storefront.layouts.app')

@section('title', 'Delivery Policy')
@section('meta_description', 'Delivery timeframes, charges, coverage and tracking for ' . config('app.name') . ' orders across Ghana, carried by our vendors and the KasaBazaar Group logistics network.')

@section('content')
    @php
        $sections = [
            'Where we deliver',
            'How long delivery takes',
            'Delivery charges',
            'Who carries your order',
            'Tracking your order',
            'Receiving your delivery',
            'Failed and refused deliveries',
            'Delays outside our control',
            'Damaged or missing items',
        ];

        $intro = 'How long your order takes, what it costs, who carries it and what to do when a delivery does not go to plan.';
    @endphp

    <x-storefront.legal-layout title="Delivery Policy" :intro="$intro" :sections="$sections">
        <section id="where-we-deliver">
            <h2>1. Where we deliver</h2>
            <p>
                {{ config('app.name') }} delivers to <strong>every region of Ghana</strong>. Coverage is densest in
                Greater Accra and Ashanti, where our own riders and courier partners operate daily; other regions are
                served through the group's road-freight network and reputable third-party couriers.
            </p>
            <p>
                Individual vendors may set a narrower delivery area for bulky or perishable goods. Where that applies,
                the limit is shown on the product page and again at checkout before you pay — you will never be
                charged for a delivery we cannot make.
            </p>
            <p>
                We do not currently deliver outside Ghana. If you need an item forwarded abroad, the group's freight
                company <strong>RDD Shipping</strong>
                (<a href="{{ collect(config('group.companies'))->firstWhere('key', 'rdd-shipping')['url'] }}" target="_blank" rel="noopener">rddshipping.com</a>)
                handles international door-to-door shipping as a separate service.
            </p>
        </section>

        <section id="how-long-delivery-takes">
            <h2>2. How long delivery takes</h2>
            <p>
                Timeframes below are counted in <strong>business days from dispatch</strong>, not from the moment you
                order. Vendors have their own handling time — usually one business day — which is shown on the product
                page and added to these estimates.
            </p>
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Estimated time</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (config('group.delivery') as $service)
                        <tr>
                            <td><strong>{{ $service['service'] }}</strong></td>
                            <td>{{ $service['estimate'] }}</td>
                            <td>{{ $service['notes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p>
                These are good-faith estimates based on our actual delivery record, not guarantees. Where an order is
                running materially late we contact you before the window closes rather than after it.
            </p>
        </section>

        <section id="delivery-charges">
            <h2>3. Delivery charges</h2>
            <p>
                The delivery charge is calculated at checkout from the delivery address, the weight and size of the
                items, and the vendor's location. It is always shown in full <strong>before</strong> you pay — we do
                not add charges after an order is placed.
            </p>
            <ul>
                <li>Orders containing items from several vendors may attract more than one delivery charge, because each vendor dispatches separately. The breakdown is itemised at checkout.</li>
                <li>Bulky items (furniture, large appliances) are quoted individually and may require a dedicated vehicle.</li>
                <li>Some vendors offer free delivery above a spend threshold. Where they do, it is applied automatically.</li>
                <li>Items shipped in from abroad may attract customs duties and import taxes, which are payable by you and are not included in the delivery charge.</li>
            </ul>
        </section>

        <section id="who-carries-your-order">
            <h2>4. Who carries your order</h2>
            <p>
                {{ config('app.name') }} is part of the {{ config('group.parent.name') }}, and most of our deliveries
                stay inside the group's own logistics network. Depending on the route, your order may be carried by:
            </p>
            <ul>
                <li><strong>The vendor's own delivery team</strong>, for local drops within their city.</li>
                <li><strong>Our own riders and courier partners</strong>, for same-city delivery in Accra and Kumasi.</li>
                <li><strong>RDD Shipping</strong> — the group's freight arm — for nationwide road freight and for anything arriving from our US warehouse.</li>
                <li><strong>Neoride Africa</strong> — the group's mobility company — for last-mile delivery in Kumasi and Ejisu.</li>
            </ul>
            <p>
                Whoever carries the parcel, <strong>{{ config('app.name') }} remains your point of contact</strong>.
                You do not need to chase a carrier yourself; raise it with us and we will.
            </p>
            <p>
                We share only the recipient's name, delivery address, phone number and parcel details with a carrier —
                see our <a href="{{ route('storefront.privacy') }}">Privacy Policy</a> for what that means for your
                data.
            </p>
        </section>

        <section id="tracking-your-order">
            <h2>5. Tracking your order</h2>
            <p>
                Every order gets a status you can follow from
                <a href="{{ route('storefront.account.orders') }}">My Orders</a>, or from the
                <a href="{{ route('storefront.track-order') }}">order tracking page</a> using your order number and
                email address if you checked out as a guest.
            </p>
            <p>
                Statuses run: <strong>Pending</strong> → <strong>Confirmed</strong> → <strong>Processing</strong> →
                <strong>Dispatched</strong> → <strong>Out for delivery</strong> → <strong>Delivered</strong>. We email
                you at dispatch and again on delivery. Where a shipment is carried by RDD Shipping, its tracking
                events are pulled through and shown on the same page.
            </p>
        </section>

        <section id="receiving-your-delivery">
            <h2>6. Receiving your delivery</h2>
            <p>
                Our rider will call the phone number on the order before arriving. Please make sure someone aged 18 or
                over is available to receive the parcel, and inspect it before signing.
            </p>
            <p>
                Anyone at the delivery address may accept on your behalf. Once a parcel is signed for at the address
                you gave us, it is treated as delivered, and risk in the goods passes to you.
            </p>
        </section>

        <section id="failed-and-refused-deliveries">
            <h2>7. Failed and refused deliveries</h2>
            <p>
                If nobody is available, our rider will attempt delivery <strong>twice more</strong> over the following
                two business days, and will call ahead each time.
            </p>
            <ul>
                <li>After three failed attempts the parcel returns to the vendor, and we contact you to arrange redelivery. A second delivery charge applies.</li>
                <li>If an order is undeliverable because the address or phone number given was wrong or incomplete, redelivery is charged at the normal rate.</li>
                <li>If you refuse a delivery without a valid reason under our <a href="{{ route('storefront.returns') }}">Returns &amp; Refunds Policy</a>, we refund the item price less the original and return delivery costs.</li>
            </ul>
        </section>

        <section id="delays-outside-our-control">
            <h2>8. Delays outside our control</h2>
            <p>
                Some delays we cannot prevent: customs and port clearance, severe weather, road closures, national
                strikes, public holidays, or a carrier's own network failure. Where one of these hits your order we
                will tell you as soon as we know, give a revised estimate, and — if the delay is substantial — offer
                you a full refund instead of waiting.
            </p>
        </section>

        <section id="damaged-or-missing-items">
            <h2>9. Damaged or missing items</h2>
            <p>
                Check your parcel on arrival. If something is damaged, missing or clearly not what you ordered, tell
                us within <strong>48 hours</strong> of delivery through
                <a href="{{ route('storefront.account.orders') }}">My Orders</a> or by writing to
                <a href="mailto:{{ config('group.contact.email') }}">{{ config('group.contact.email') }}</a>. Photos
                of the packaging and the item help us settle the claim quickly.
            </p>
            <p>
                Claims raised inside that window are resolved by replacement or full refund at your choice, at no cost
                to you. After 48 hours we can still help, but the claim is handled under our
                <a href="{{ route('storefront.returns') }}">Returns &amp; Refunds Policy</a> instead.
            </p>
        </section>
    </x-storefront.legal-layout>
@endsection
