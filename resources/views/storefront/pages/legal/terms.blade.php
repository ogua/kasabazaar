@extends('storefront.layouts.app')

@section('title', 'Terms of Use')
@section('meta_description', 'The terms that govern your use of the ' . config('app.name') . ' marketplace as a shopper or as a vendor.')

@section('content')
    @php
        $sections = [
            'Agreement to these terms',
            'Who we are and what we do',
            'Your account',
            'Buying on the marketplace',
            'Prices, payment and currency',
            'Delivery',
            'Returns, cancellations and refunds',
            'Selling on the marketplace',
            'Prohibited items and conduct',
            'Intellectual property',
            'Content you submit',
            'Suspension and termination',
            'Liability',
            'Other group companies',
            'Governing law and disputes',
            'Changes to these terms',
        ];

        // Names the registered entity only when one is confirmed; otherwise the
        // trading name stands alone rather than reading "X, the operator of X".
        $counterparty = config('group.company.legal_entity')
            ? config('group.company.legal_entity').', the operator of '.config('app.name')
            : config('app.name');

        $intro = 'These terms form a binding agreement between you and '.$counterparty.'. Please read them before you use the marketplace — by browsing, buying or selling here you accept them.';
    @endphp

    <x-storefront.legal-layout title="Terms of Use" :intro="$intro" :sections="$sections">
        <section id="agreement-to-these-terms">
            <h2>1. Agreement to these terms</h2>
            <p>
                By accessing {{ config('app.name') }}, creating an account, placing an order or listing a product,
                you agree to these Terms of Use, our <a href="{{ route('storefront.privacy') }}">Privacy Policy</a>,
                our <a href="{{ route('storefront.delivery-policy') }}">Delivery Policy</a>, our
                <a href="{{ route('storefront.returns') }}">Returns &amp; Refunds Policy</a> and our
                <a href="{{ route('storefront.cookies') }}">Cookie Policy</a>. Together these make up the agreement
                between us. If you do not accept them, do not use the marketplace.
            </p>
            <p>You must be at least 18 years old, and legally able to enter a contract, to hold an account.</p>
        </section>

        <section id="who-we-are-and-what-we-do">
            <h2>2. Who we are and what we do</h2>
            <p>
                @if ($entity = config('group.company.legal_entity'))
                    {{ config('app.name') }} is operated by <strong>{{ $entity }}</strong>, a company of the
                @else
                    {{ config('app.name') }} is the marketplace of the
                @endif
                <a href="{{ config('group.parent.url') }}" target="_blank" rel="noopener">{{ config('group.parent.name') }}</a>@if ($address = config('group.contact.address')), registered at {{ $address }}@endif.
            </p>
            <p>
                We run a <strong>multi-vendor marketplace</strong>. Independent vendors list and sell their own
                products here. For those items:
            </p>
            <ul>
                <li>the <strong>contract of sale is between you and the vendor</strong>, not between you and us;</li>
                <li>the vendor is responsible for the accuracy of the listing, the condition of the goods, packing, and meeting its own delivery promise;</li>
                <li>we provide the platform, take payment on the vendor's behalf, arrange or coordinate delivery, and step in to mediate when something goes wrong.</li>
            </ul>
            <p>
                Where a listing is marked as sold by {{ config('app.name') }} itself, we are the seller and these
                terms apply to us in that role.
            </p>
        </section>

        <section id="your-account">
            <h2>3. Your account</h2>
            <p>
                You are responsible for everything done through your account and for keeping your password secret.
                Tell us immediately if you think someone else has access to it. Give accurate registration details and
                keep them current — we rely on your phone number and address to deliver your orders.
            </p>
            <p>One person or business may hold one account. Accounts may not be sold or transferred.</p>
        </section>

        <section id="buying-on-the-marketplace">
            <h2>4. Buying on the marketplace</h2>
            <p>
                A listing is an invitation to buy, not a binding offer. Your order is an offer to buy, and the
                contract forms only when the vendor accepts and confirms your order. If an item turns out to be out of
                stock or listed at an obviously incorrect price, the vendor may decline the order and we will refund
                you in full.
            </p>
            <p>
                Your cart may hold items from several vendors. At checkout we split them into one order per vendor,
                but you pay once. Each order is then fulfilled, tracked, delivered and, if necessary, refunded
                separately.
            </p>
            <p>
                Product images are illustrative. Colours vary between screens, and packaging may change. Where a
                material difference exists between the listing and what arrives, our
                <a href="{{ route('storefront.returns') }}">Returns &amp; Refunds Policy</a> applies.
            </p>
        </section>

        <section id="prices-payment-and-currency">
            <h2>5. Prices, payment and currency</h2>
            <p>
                Prices are shown in Ghana Cedis (GHS) and include any applicable VAT unless the listing says
                otherwise. Delivery charges are shown separately at checkout before you pay.
            </p>
            <p>
                We accept card and Mobile Money payments through <strong>Paystack</strong>, and card payments through
                <strong>Stripe</strong> for international customers. Payment is taken when you place the order and is
                held until the vendor is due settlement. We do not store your card details.
            </p>
            <p>
                If a payment is reversed, charged back or found to be fraudulent, we may suspend the order and your
                account while we investigate.
            </p>
        </section>

        <section id="delivery">
            <h2>6. Delivery</h2>
            <p>
                Delivery windows, charges and coverage are set out in full in our
                <a href="{{ route('storefront.delivery-policy') }}">Delivery Policy</a>. In summary: most orders in
                Accra and Kumasi arrive within 1–3 business days, other regions within 3–7 business days, and items
                shipped in from abroad within 10–21 business days.
            </p>
            <p>
                Deliveries are carried by the vendor, by a partner courier, or by one of the group's own logistics
                companies — <strong>RDD Shipping</strong> for road freight and inbound international parcels, and
                <strong>Neoride Africa</strong> for last-mile drops in Kumasi and Ejisu.
            </p>
            <p>
                Estimates are not guarantees. Risk in the goods passes to you on delivery to the address you gave us.
            </p>
        </section>

        <section id="returns-cancellations-and-refunds">
            <h2>7. Returns, cancellations and refunds</h2>
            <p>
                You may cancel an order at no cost any time before it is dispatched, from your
                <a href="{{ route('storefront.account.orders') }}">order history</a>. After delivery, you have
                <strong>{{ config('group.returns.window_days') }} days</strong> to request a return for an item that
                is faulty, damaged, incomplete or materially different from its listing. Approved refunds are returned
                to your original payment method within {{ config('group.returns.refund_days') }} days.
            </p>
            <p>
                The full rules, including which items cannot be returned and who pays return shipping, are in our
                <a href="{{ route('storefront.returns') }}">Returns &amp; Refunds Policy</a>.
            </p>
        </section>

        <section id="selling-on-the-marketplace">
            <h2>8. Selling on the marketplace</h2>
            <p>If your vendor application is approved, these additional terms apply to you:</p>
            <ul>
                <li>You must be a legally registered business or a sole trader able to trade lawfully in Ghana, and you must give us accurate registration and settlement details.</li>
                <li>Your listings must be accurate, must not infringe anyone's rights, and must comply with Ghanaian law — including labelling, safety and consumer-protection rules for your category.</li>
                <li>You must honour the price shown at the time the shopper ordered, and dispatch within the handling time stated on your listing.</li>
                <li>We deduct our commission and payment-processing costs from each sale. Rates and the settlement schedule are set out in your vendor agreement, which prevails over this section if the two conflict.</li>
                <li>You must respond to shopper messages and return requests within two business days.</li>
                <li>Repeated late dispatch, cancellations, counterfeit goods or unresolved disputes may lead to withheld settlement, delisting or removal from the marketplace.</li>
            </ul>
            <p>
                Vendors act as independent businesses. Nothing in these terms makes a vendor our employee, agent,
                partner or joint venturer.
            </p>
        </section>

        <section id="prohibited-items-and-conduct">
            <h2>9. Prohibited items and conduct</h2>
            <p>You may not list, buy or use the marketplace for:</p>
            <ul>
                <li>counterfeit, stolen or illegally imported goods;</li>
                <li>weapons, ammunition, explosives or controlled drugs;</li>
                <li>prescription medicines, unregistered supplements or unapproved medical devices;</li>
                <li>live animals, human remains or body parts;</li>
                <li>adult content, hate material, or anything promoting violence or discrimination;</li>
                <li>anything else prohibited by Ghanaian law or by our payment processors' rules.</li>
            </ul>
            <p>
                You also may not scrape the site, interfere with its operation, attempt to access other users'
                accounts, place fake orders, manipulate reviews, or use the marketplace to launder money.
            </p>
        </section>

        <section id="intellectual-property">
            <h2>10. Intellectual property</h2>
            <p>
                The {{ config('app.name') }} name, logo, site design, code and copy belong to
                {{ config('group.company.legal_name') }} or its licensors. The KASAROSE mark and the marks of the other
                {{ config('group.parent.name') }} businesses may not be used without written permission. Product
                images and descriptions supplied by vendors remain theirs, licensed to us for use on and in the
                promotion of the marketplace.
            </p>
        </section>

        <section id="content-you-submit">
            <h2>11. Content you submit</h2>
            <p>
                Reviews, questions, photos and messages you post must be your own, honest, and free of unlawful or
                abusive material. By posting, you grant us a non-exclusive, royalty-free licence to display and
                distribute that content in connection with the marketplace. We may remove content that breaches these
                terms, and we do not pay for content you submit.
            </p>
        </section>

        <section id="suspension-and-termination">
            <h2>12. Suspension and termination</h2>
            <p>
                You may close your account at any time. We may suspend or close an account, cancel orders or delist
                products where we reasonably believe these terms have been breached, where required by law, or where
                doing so is necessary to protect other users. Where we can, we will tell you why and give you a chance
                to put things right first.
            </p>
            <p>
                Closing an account does not cancel obligations already incurred — outstanding orders, refunds and
                vendor settlements are still honoured.
            </p>
        </section>

        <section id="liability">
            <h2>13. Liability</h2>
            <p>
                We provide the marketplace with reasonable skill and care, but we do not warrant that it will be
                uninterrupted or error-free, and we are not the manufacturer or, for vendor listings, the seller of
                the goods.
            </p>
            <p>
                Nothing in these terms limits liability for death or personal injury caused by negligence, for fraud,
                or for anything that cannot lawfully be limited under Ghanaian law. Subject to that, our total
                liability arising out of any order is limited to the amount you paid for that order, and we are not
                liable for indirect or consequential loss, loss of profit, or loss of business opportunity.
            </p>
        </section>

        <section id="other-group-companies">
            <h2>14. Other group companies</h2>
            <p>
                {{ config('app.name') }} is one business within the {{ config('group.parent.name') }}. The others are:
            </p>
            <ul>
                @foreach (config('group.companies') as $company)
                    <li>
                        <strong>{{ $company['name'] }}</strong> — {{ $company['role'] }}.
                        <a href="{{ $company['url'] }}" target="_blank" rel="noopener">{{ $company['url_label'] }}</a>
                    </li>
                @endforeach
            </ul>
            <p>
                Each is a separate business with its own terms, its own contracts and its own liabilities. These terms
                govern {{ config('app.name') }} only. Where an order is carried by RDD Shipping or delivered by
                Neoride Africa on our behalf, we remain your point of contact for that order and you do not need to
                deal with them directly — but if you buy a service from one of those companies in its own right, that
                company's terms apply to it, not these.
            </p>
        </section>

        <section id="governing-law-and-disputes">
            <h2>15. Governing law and disputes</h2>
            <p>
                These terms are governed by {{ config('group.legal.governing_law') }} and are subject to the exclusive
                jurisdiction of {{ config('group.legal.jurisdiction') }}.
            </p>
            <p>
                Before starting formal proceedings, please raise the issue with us at
                <a href="mailto:{{ config('group.contact.legal_email') }}">{{ config('group.contact.legal_email') }}</a>
                — we resolve almost every dispute this way, usually within ten business days.
            </p>
        </section>

        <section id="changes-to-these-terms">
            <h2>16. Changes to these terms</h2>
            <p>
                We may update these terms as the marketplace changes. The effective date at the top of this page shows
                the current version. Material changes are announced by email or an on-site notice before they take
                effect; continuing to use {{ config('app.name') }} after that date means you accept the new terms.
            </p>
        </section>
    </x-storefront.legal-layout>
@endsection
