@extends('storefront.layouts.app')

@section('title', 'Privacy Policy')
@section('meta_description', 'How ' . config('app.name') . ' collects, uses, shares and protects your personal data, including the data shared with vendors and with the other KasaBazaar Group of Companies businesses.')

@section('content')
    @php
        $sections = [
            'Who we are',
            'The data we collect',
            'How we use your data',
            'Who we share your data with',
            'Sharing within our group',
            'International transfers',
            'How long we keep your data',
            'Your rights',
            'Security',
            'Children',
            'Changes to this policy',
        ];

        $intro = 'This policy explains what personal data '.config('app.name').' collects when you browse, buy or sell on our marketplace, why we collect it, who we share it with, and the choices you have.';
    @endphp

    <x-storefront.legal-layout title="Privacy Policy" :intro="$intro" :sections="$sections">
        <section id="who-we-are">
            <h2>1. Who we are</h2>
            <p>
                @if ($entity = config('group.company.legal_entity'))
                    {{ config('app.name') }} is the online marketplace operated by <strong>{{ $entity }}</strong>, a
                    company of the
                @else
                    {{ config('app.name') }} is the online marketplace of the
                @endif
                <a href="{{ config('group.parent.url') }}" target="_blank" rel="noopener">{{ config('group.parent.name') }}</a>.
                For the purposes of data protection law we are the <strong>data controller</strong> of the personal
                data described in this policy.
            </p>
            <p>
                @if ($address = config('group.contact.address'))
                    Our registered address is {{ $address }}.
                @endif
                You can reach our privacy contact at
                <a href="mailto:{{ config('group.contact.privacy_email') }}">{{ config('group.contact.privacy_email') }}</a>
                or on {{ config('group.contact.phone_gh') }}.
            </p>
            <p>
                We handle personal data in line with Ghana's <strong>Data Protection Act, 2012 (Act 843)</strong>.
                Where you contact us from outside Ghana, we apply the same standards to your data.
            </p>
        </section>

        <section id="the-data-we-collect">
            <h2>2. The data we collect</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>What it includes</th>
                        <th>Why we need it</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Account data</td>
                        <td>Name, email address, phone number, password (stored hashed, never in plain text)</td>
                        <td>To create and secure your account and let you sign in</td>
                    </tr>
                    <tr>
                        <td>Order data</td>
                        <td>Items ordered, prices, delivery address, order status, communications with a vendor</td>
                        <td>To process, deliver and support your orders</td>
                    </tr>
                    <tr>
                        <td>Payment data</td>
                        <td>Payment method type, last four digits, transaction reference, authorisation result</td>
                        <td>To take payment and handle refunds and disputes</td>
                    </tr>
                    <tr>
                        <td>Vendor data</td>
                        <td>Business name, registration details, contact person, settlement bank or Mobile Money details</td>
                        <td>To review vendor applications and pay out earnings</td>
                    </tr>
                    <tr>
                        <td>Technical data</td>
                        <td>IP address, device and browser type, pages viewed, referring page</td>
                        <td>To keep the site secure, diagnose faults and measure performance</td>
                    </tr>
                    <tr>
                        <td>Marketing preferences</td>
                        <td>Whether you have opted in to our emails, and which ones you open</td>
                        <td>To send only the messages you asked for</td>
                    </tr>
                </tbody>
            </table>
            <p>
                <strong>We never see or store your full card number.</strong> Card and Mobile Money details are
                captured directly by our payment processors — Paystack (Ghana) and Stripe (international) — on their
                own systems. We receive only the result of the transaction and a reference.
            </p>
        </section>

        <section id="how-we-use-your-data">
            <h2>3. How we use your data</h2>
            <p>We use your personal data for the following purposes, on the following legal bases:</p>
            <ul>
                <li><strong>To perform our contract with you</strong> — creating your account, processing orders and payments, arranging delivery, handling returns and refunds, and paying vendors their earnings.</li>
                <li><strong>For our legitimate business interests</strong> — preventing fraud and abuse, securing the platform, resolving disputes between shoppers and vendors, improving the marketplace, and understanding which products and categories are in demand.</li>
                <li><strong>To meet legal obligations</strong> — keeping tax and accounting records, responding to lawful requests from regulators, and meeting the record-keeping rules our payment processors impose on us.</li>
                <li><strong>With your consent</strong> — sending marketing emails. You can withdraw consent at any time, without affecting anything we did before you withdrew it. We set no advertising or profiling cookies at all; see our <a href="{{ route('storefront.cookies') }}">Cookie Policy</a>.</li>
            </ul>
            <p>
                We do not sell your personal data, and we do not use it to make automated decisions that produce
                legal effects for you.
            </p>
        </section>

        <section id="who-we-share-your-data-with">
            <h2>4. Who we share your data with</h2>
            <p>
                {{ config('app.name') }} is a marketplace, not the seller of record for most items. That means some
                sharing is unavoidable:
            </p>
            <ul>
                <li><strong>Vendors.</strong> When you place an order, the vendor fulfilling it receives your name, delivery address, phone number and the contents of that order — and nothing else. A vendor may use that data only to fulfil and support your order, and is contractually barred from marketing to you off-platform or selling your details on.</li>
                <li><strong>Delivery partners.</strong> Couriers and riders receive the delivery address, recipient name and phone number needed to complete the drop-off. Where the group's own companies carry the parcel, see section 5.</li>
                <li><strong>Payment processors.</strong> Paystack and Stripe process the payment and receive the data they need to do so and to satisfy their own anti-fraud and regulatory obligations.</li>
                <li><strong>Service providers.</strong> Hosting, email delivery, SMS and error-monitoring providers process data strictly on our instructions under written contracts.</li>
                <li><strong>Authorities.</strong> Where we are legally required to disclose data, or where disclosure is necessary to establish or defend a legal claim.</li>
            </ul>
        </section>

        <section id="sharing-within-our-group">
            <h2>5. Sharing within our group</h2>
            <p>
                {{ config('app.name') }} is one of the {{ config('group.parent.name') }}'s businesses. The others are:
            </p>
            <ul>
                @foreach (config('group.companies') as $company)
                    <li>
                        <strong>{{ $company['name'] }}</strong> ({{ $company['role'] }}) —
                        <a href="{{ $company['url'] }}" target="_blank" rel="noopener">{{ $company['url_label'] }}</a>
                    </li>
                @endforeach
            </ul>
            <p>
                We share personal data with these companies only where there is a specific operational reason to do
                so, and only the minimum needed:
            </p>
            <ul>
                <li><strong>RDD Shipping</strong> receives recipient name, delivery address, phone number and parcel details for orders it carries — including nationwide road freight and any item shipped in from abroad. It also processes tracking events so you can follow a shipment.</li>
                <li><strong>Neoride Africa</strong> receives recipient name, delivery address and phone number for last-mile deliveries its riders complete in Kumasi and Ejisu.</li>
                <li><strong>KasaBazaar</strong> provides shared corporate functions — finance, accounting, fraud review and group-level customer support — and its staff may access order and payment records for those purposes.</li>
            </ul>
            <p>
                Each group company is responsible for its own use of the data it receives and publishes its own
                privacy policy on its own site. Marketing lists are <strong>not</strong> shared between group
                companies: opting in to {{ config('app.name') }} emails does not opt you in to anyone else's.
            </p>
        </section>

        <section id="international-transfers">
            <h2>6. International transfers</h2>
            <p>
                The group operates in both Ghana and the United States, and some of our service providers are based
                outside Ghana. Where personal data leaves Ghana, we transfer it only to recipients bound by written
                contractual terms requiring a level of protection equivalent to that required by Act 843, and only
                for the purposes described in this policy.
            </p>
        </section>

        <section id="how-long-we-keep-your-data">
            <h2>7. How long we keep your data</h2>
            <ul>
                <li><strong>Account data</strong> — for as long as your account is open, then up to 12 months after closure in case you return.</li>
                <li><strong>Order and payment records</strong> — six years from the end of the relevant financial year, to meet tax and accounting requirements.</li>
                <li><strong>Vendor records</strong> — six years after the vendor relationship ends, for the same reason.</li>
                <li><strong>Technical logs</strong> — 90 days, unless retained longer as part of a security investigation.</li>
                <li><strong>Marketing preferences</strong> — until you withdraw consent, plus a suppression record so we do not accidentally email you again.</li>
            </ul>
        </section>

        <section id="your-rights">
            <h2>8. Your rights</h2>
            <p>Under Act 843 you have the right to:</p>
            <ul>
                <li>ask what personal data we hold about you and get a copy of it;</li>
                <li>have inaccurate or incomplete data corrected;</li>
                <li>ask us to delete data we no longer have a lawful reason to keep;</li>
                <li>object to processing based on our legitimate interests, and to stop direct marketing at any time;</li>
                <li>withdraw a consent you previously gave;</li>
                <li>complain to the Data Protection Commission of Ghana if you believe we have handled your data unlawfully.</li>
            </ul>
            <p>
                Most of this you can do yourself from your
                <a href="{{ route('storefront.account.profile') }}">account profile</a>. For anything else, write to
                <a href="mailto:{{ config('group.contact.privacy_email') }}">{{ config('group.contact.privacy_email') }}</a>.
                We respond within 30 days and never charge for a first request.
            </p>
        </section>

        <section id="security">
            <h2>9. Security</h2>
            <p>
                All traffic to {{ config('app.name') }} is encrypted in transit with TLS. Passwords are stored using a
                one-way hash and cannot be read by our staff. Access to customer records inside the group is limited
                to staff who need it for their role and is logged. Payment card data never touches our servers.
            </p>
            <p>
                No system is perfectly secure. If a breach is likely to put your rights at risk, we will notify you
                and the Data Protection Commission without undue delay.
            </p>
        </section>

        <section id="children">
            <h2>10. Children</h2>
            <p>
                {{ config('app.name') }} is not intended for anyone under 18, and we do not knowingly collect data
                from children. If you believe a child has given us personal data, contact us and we will delete it.
            </p>
        </section>

        <section id="changes-to-this-policy">
            <h2>11. Changes to this policy</h2>
            <p>
                We update this policy when our practices change. The effective date at the top of this page always
                shows the current version. Where a change materially affects your rights, we will tell you by email
                or with a notice on the site before it takes effect.
            </p>
            <p>
                See also our <a href="{{ route('storefront.cookies') }}">Cookie Policy</a> for how we use cookies and
                similar technologies, and our <a href="{{ route('storefront.terms') }}">Terms of Use</a> for the rules
                that govern your use of the marketplace.
            </p>
        </section>
    </x-storefront.legal-layout>
@endsection
