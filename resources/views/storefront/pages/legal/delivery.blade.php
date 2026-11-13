@extends('storefront.layouts.app')

@section('title', 'Delivery Policy')
@section('meta_description', 'Delivery timeframes, charges, coverage and tracking for ' . config('app.name') . ' orders across Ghana, carried by our vendors and the ' . config('group.parent.name') . ' network.')

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
            'Warehouse inspection and pickup',
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
                @php $logistics = collect(config('group.companies'))->firstWhere('key', 'kasarose-logistics'); @endphp
                We do not currently deliver outside Ghana. If you need an item forwarded abroad, our parent company
                <strong>{{ $logistics['name'] }}</strong>
                (<a href="{{ $logistics['url'] }}" target="_blank" rel="noopener">{{ $logistics['url_label'] }}</a>)
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
                Delivery is not free. The cost is <strong>entered by our delivery team after they have seen your
                items</strong>, because it depends on the delivery address, and the weight and size of the goods. It is
                not added to your order total at checkout.
            </p>
            <ul>
                <li>For <strong>Delivery</strong> and <strong>KASAROSE shipping</strong> orders you pay for the items first. We then tell you the delivery cost by SMS, email and in your account, and you pay it from your order page. <strong>You bear the delivery cost.</strong></li>
                <li>We only arrange delivery once the delivery cost is paid. Our delivery team then contacts you to agree how and when delivery will be made.</li>
                <li><strong>Pickup</strong> has no delivery cost.</li>
                <li>Orders containing items from several vendors may be split into more than one order, each with its own delivery cost where it is delivered.</li>
                <li>Bulky items (furniture, large appliances) are quoted individually and may require a dedicated vehicle.</li>
                <li>Some vendors offer free delivery above a spend threshold. Where they do, it is applied automatically.</li>
                <li>Items shipped in from abroad may attract customs duties and import taxes, which are payable by you and are not included in the delivery charge.</li>
            </ul>
        </section>

        <section id="who-carries-your-order">
            <h2>4. Who carries your order</h2>
            <p>
                {{ config('app.name') }} is part of the {{ config('group.parent.group_name') }}, and most of our deliveries
                stay inside the group's own logistics network. Depending on the route, your order may be carried by:
            </p>
            <ul>
                <li><strong>Our own riders and courier partners</strong>, for delivery in Accra, Kumasi and other regions.</li>
                <li><strong>KASAROSE LOGISTICS</strong> — our parent company — for nationwide road freight and for anything arriving from our US warehouse.</li>
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
                Statuses run: <strong>Order placed</strong> → <strong>Payment confirmed</strong> →
                <strong>Vendor preparing your order</strong> → <strong>Order ready</strong> →
                <strong>Sent to KASAROSE warehouse</strong> → <strong>Received at warehouse</strong> →
                <strong>Inspected</strong> → <strong>Dispatched</strong> → <strong>In transit</strong> →
                <strong>Delivered</strong>. For Pickup orders, the last steps are <strong>Ready for pickup</strong> and
                <strong>Collected</strong>. We message you at each warehouse step, at dispatch and on delivery. Where a shipment is carried by KASAROSE LOGISTICS, its tracking
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

        <section id="warehouse-inspection-and-pickup">
            <h2>10. Warehouse inspection and pickup</h2>
            <p>
                <strong>Vendors do not deliver to you directly.</strong> When an order is packed, the vendor sends it
                to one of our warehouses. We check it there, and only then does it go on to you. If an item fails
                inspection it is returned to the vendor and we contact you about a refund.
            </p>
            <p>
                If you choose <strong>Pickup</strong>, you pick a pickup station in Ghana or the USA at checkout. Its
                address and opening hours are shown on your order. We tell you when the order is ready, and you collect
                it there with your order number.
            </p>
        </section>
    </x-storefront.legal-layout>
@endsection
