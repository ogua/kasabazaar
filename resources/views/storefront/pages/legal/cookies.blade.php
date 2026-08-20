@extends('storefront.layouts.app')

@section('title', 'Cookie Policy')
@section('meta_description', 'The cookies ' . config('app.name') . ' sets, what each one does, how long it lasts and how to control them.')

@section('content')
    @php
        $sections = [
            'What cookies are',
            'The cookies we set',
            'Third-party cookies',
            'Cookies and the wider group',
            'Managing cookies',
            'Do Not Track',
            'Changes to this policy',
        ];

        $intro = 'We use a small number of cookies — most of them strictly necessary to keep you signed in and your cart intact. This page lists every one and how to control it.';
    @endphp

    <x-storefront.legal-layout title="Cookie Policy" :intro="$intro" :sections="$sections">
        <section id="what-cookies-are">
            <h2>1. What cookies are</h2>
            <p>
                Cookies are small text files a site stores on your device so it can recognise you between page loads.
                We also use closely related technologies — browser local storage and session storage — and this policy
                covers those in the same way.
            </p>
            <p>
                Cookies are either <strong>session</strong> cookies, deleted when you close your browser, or
                <strong>persistent</strong> cookies, which last for a set period. They are either
                <strong>first-party</strong> (set by {{ config('app.name') }}) or <strong>third-party</strong> (set by
                another service we use).
            </p>
        </section>

        <section id="the-cookies-we-set">
            <h2>2. The cookies we set</h2>
            <table>
                <thead>
                    <tr>
                        <th>Cookie</th>
                        <th>Purpose</th>
                        <th>Type</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>kasarose_session</code></td>
                        <td>Keeps you signed in and holds your cart between pages. Without it the site cannot function.</td>
                        <td>Strictly necessary</td>
                        <td>2 hours after last activity</td>
                    </tr>
                    <tr>
                        <td><code>XSRF-TOKEN</code></td>
                        <td>Protects forms and checkout against cross-site request forgery.</td>
                        <td>Strictly necessary</td>
                        <td>2 hours</td>
                    </tr>
                    <tr>
                        <td><code>remember_web_*</code></td>
                        <td>Set only if you tick "remember me" at sign-in, so you stay signed in on this device.</td>
                        <td>Functional</td>
                        <td>5 years, or until you sign out</td>
                    </tr>
                </tbody>
            </table>
            <p>
                That is the complete list. <strong>Strictly necessary</strong> cookies are set without consent
                because the marketplace cannot work without them — you cannot sign in, hold a cart or check out with
                them blocked. The one <strong>functional</strong> cookie is set only if you ask for it by ticking
                "remember me". We set no analytics, advertising or profiling cookies at all.
            </p>
        </section>

        <section id="third-party-cookies">
            <h2>3. Third-party cookies</h2>
            <p>Two third parties may set cookies on pages you visit here:</p>
            <ul>
                <li><strong>Paystack</strong> — during checkout and payment, for fraud prevention and to complete the transaction. Governed by Paystack's own privacy and cookie policies.</li>
                <li><strong>Stripe</strong> — the same, for international card payments.</li>
            </ul>
            <p>
                We do not run advertising networks, retargeting pixels or social-media tracking scripts on
                {{ config('app.name') }}, and we do not sell data about your browsing to anyone.
            </p>
        </section>

        <section id="cookies-and-the-wider-group">
            <h2>4. Cookies and the wider group</h2>
            <p>
                {{ config('app.name') }} is part of the {{ config('group.parent.name') }}, alongside
                @foreach (config('group.companies') as $company)
                    <a href="{{ $company['url'] }}" target="_blank" rel="noopener">{{ $company['name'] }}</a>@if (! $loop->last){{ $loop->remaining === 1 ? ' and ' : ', ' }}@else.@endif
                @endforeach
            </p>
            <p>
                Our cookies are scoped to this domain only. <strong>We do not track you across the group's
                sites</strong>, and following a link from here to a sister company's site starts a fresh, separate
                cookie relationship governed by that site's own cookie policy. Cookie choices you make here do not
                carry over to those sites, and theirs do not carry over to us.
            </p>
        </section>

        <section id="managing-cookies">
            <h2>5. Managing cookies</h2>
            <p>
                You can clear or block cookies at any time from your browser settings — every major browser has a
                privacy section that lets you view stored cookies, delete them, and refuse new ones per site.
            </p>
            <p>
                Blocking strictly necessary cookies will break sign-in, the cart and checkout — there is no way around
                that, because those cookies <em>are</em> the mechanism. Blocking the functional cookie only means you
                have to sign in each visit.
            </p>
            <p>
                Because we set no tracking cookies, there is no consent banner and nothing to opt out of beyond your
                browser's own controls. Signing out clears the remember-me cookie for this device.
            </p>
        </section>

        <section id="do-not-track">
            <h2>6. Do Not Track</h2>
            <p>
                Some browsers send a "Do Not Track" signal. There is no agreed standard for how sites should respond,
                so we do not act on it — but since we run no advertising or cross-site tracking, there is nothing for
                it to switch off here.
            </p>
        </section>

        <section id="changes-to-this-policy">
            <h2>7. Changes to this policy</h2>
            <p>
                We update this page whenever we add or remove a cookie. The effective date at the top always shows the
                current version. For the wider picture on how we handle your data, see our
                <a href="{{ route('storefront.privacy') }}">Privacy Policy</a>.
            </p>
        </section>
    </x-storefront.legal-layout>
@endsection
