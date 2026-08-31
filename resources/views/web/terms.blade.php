@extends('web.sub-template')

@section('heading','Terms & Conditions - Rose Door To Door Shipping & Logistics')

@section('sub-heading','Terms & Conditions')

@section('main-content')

<section class="section">
    <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center">
            <div class="col-lg-10">

                <p class="text-muted mb-4"><em>Last updated: August 31, 2026</em></p>

                <p>These Terms &amp; Conditions ("Terms") govern your use of the Rose Door to Door Shipping and Delivery Service website and the shipping, package-forwarding and logistics services provided by <strong>{{ config('group.company.legal_name') }}</strong> ("RDD Shipping", "we", "us", or "our"). By requesting a quote, booking a shipment, or using this website, you agree to these Terms and to our <a href="{{ route('privacy-policy') }}">Privacy Policy</a>.</p>

                <p>RDD Shipping is the freight logistics arm of the <a href="{{ config('group.parent.url') }}" target="_blank" rel="noopener">{{ config('group.parent.name') }}</a>. These Terms cover RDD Shipping only; the group's other companies operate separately and publish their own terms — see section 12.</p>

                <hr class="my-4">

                <!-- 1 -->
                <h4 class="mt-4">1. Eligibility and Acceptance</h4>
                <p>You must be at least 18 years old and legally able to enter a contract to use our services. If you use our services on behalf of a business, you confirm you are authorised to bind that business to these Terms.</p>

                <hr class="my-4">

                <!-- 2 -->
                <h4 class="mt-4">2. Our Services</h4>
                <p>We provide freight forwarding, package consolidation and door-to-door shipping between the United States and Ghana, together with related domestic delivery, warehousing and customs-coordination services. Service availability, routes and transit times may change without notice.</p>

                <hr class="my-4">

                <!-- 3 -->
                <h4 class="mt-4">3. Quotes, Charges and Payment</h4>
                <ul>
                    <li>Quotations are estimates based on the information you provide and are valid for the period stated on the quote. Final charges are based on the actual weight, dimensions, contents and declared value of your shipment.</li>
                    <li>Charges may include freight, handling, consolidation, storage, customs duties and taxes, and last-mile delivery.</li>
                    <li>Payments are processed securely through <strong>Paystack</strong>. We do not store your full card details.</li>
                    <li>Shipments will not be released for delivery until all outstanding charges, including any duties and taxes we have advanced on your behalf, are paid in full.</li>
                    <li>Storage fees may apply where a shipment is not collected or cleared within the free storage period we notify to you.</li>
                </ul>

                <hr class="my-4">

                <!-- 4 -->
                <h4 class="mt-4">4. Your Responsibilities</h4>
                <ul>
                    <li>Provide accurate and complete sender and recipient details, item descriptions, weights, dimensions and declared values.</li>
                    <li>Ensure that every item you ship is lawful to export from the country of origin and to import into the destination country.</li>
                    <li>Package items adequately for international transit. We are not liable for damage caused by inadequate packing.</li>
                    <li>Pay all customs duties, taxes and levies assessed by the destination authorities.</li>
                </ul>

                <hr class="my-4">

                <!-- 5 -->
                <h4 class="mt-4">5. Prohibited and Restricted Items</h4>
                <p>You may not ship items that are prohibited or restricted by law, by carriers, or by customs authorities in the United States or Ghana, including but not limited to: currency and negotiable instruments; firearms, ammunition and explosives; illegal drugs and controlled substances; hazardous or flammable materials; perishable goods without prior arrangement; counterfeit goods; and human remains. We may refuse, hold, return or dispose of any shipment that breaches this section, and you remain responsible for the resulting charges.</p>

                <hr class="my-4">

                <!-- 6 -->
                <h4 class="mt-4">6. Transit Times, Customs and Delivery</h4>
                <p>Transit times are estimates and are not guaranteed. Delays caused by customs inspection, weather, carrier scheduling, incomplete documentation or unpaid charges are outside our control. Risk in the goods passes to the recipient on delivery to the address provided. If delivery cannot be completed after reasonable attempts, the shipment will be held for collection and storage fees may apply.</p>

                <hr class="my-4">

                <!-- 7 -->
                <h4 class="mt-4">7. Liability and Claims</h4>
                <p>Our liability for loss of or damage to a shipment is limited to the lower of its declared value or the actual documented value of the goods, and in any event to the maximum permitted by applicable law and by the underlying carriers. We are not liable for indirect or consequential loss, loss of profit, or loss of business opportunity. Claims must be submitted in writing within seven (7) days of delivery or, in the case of non-delivery, within thirty (30) days of the expected delivery date, with supporting evidence of value.</p>

                <hr class="my-4">

                <!-- 8 -->
                <h4 class="mt-4">8. SMS / Text Message Terms</h4>
                <p>When you book a shipment, you may opt in to receive SMS (text message) notifications about that shipment. By providing your mobile number and opting in, you agree to the following:</p>
                <ul>
                    <li><strong>Program description:</strong> RDD Shipping sends automated transactional messages about your shipment — booking confirmation, pickup, departure, customs and container clearance, out-for-delivery and delivery confirmation, payment receipts, and tracking-number updates.</li>
                    <li><strong>Consent:</strong> Messages are sent only to numbers that opted in during booking. Consent to receive SMS is not a condition of purchasing any goods or services.</li>
                    <li><strong>Message frequency:</strong> Varies with shipment activity — typically 3–10 messages over the life of a shipment.</li>
                    <li><strong>Cost:</strong> Message and data rates may apply, depending on your mobile carrier and plan.</li>
                    <li><strong>Opt out:</strong> Reply <strong>STOP</strong> to any message to cancel. You will receive one confirmation message and no further messages. Reply <strong>HELP</strong> for assistance, or contact us using the details in section 15.</li>
                    <li><strong>Carriers:</strong> Mobile carriers are not liable for delayed or undelivered messages.</li>
                    <li><strong>Providers and privacy:</strong> Messages are delivered through our messaging providers, Twilio and Arkesel. Mobile numbers and opt-in consent are handled in line with our <a href="{{ route('privacy-policy') }}">Privacy Policy</a> and are never sold, rented, or shared with third parties or other group companies for their own marketing or promotional purposes.</li>
                </ul>

                <hr class="my-4">

                <!-- 9 -->
                <h4 class="mt-4">9. Website Use and Intellectual Property</h4>
                <p>The RDD Shipping name, logo, website design, text and images belong to {{ config('group.company.legal_name') }} or its licensors and may not be used without written permission. You may not scrape the site, interfere with its operation, attempt to access other users' accounts, or use it for any unlawful purpose.</p>

                <hr class="my-4">

                <!-- 10 -->
                <h4 class="mt-4">10. Suspension and Termination</h4>
                <p>We may refuse service, suspend an account or cancel a booking where we reasonably believe these Terms have been breached, where required by law, or where necessary to protect our customers, staff or partners. Obligations already incurred — including outstanding charges and duties advanced on your behalf — survive termination.</p>

                <hr class="my-4">

                <!-- 11 -->
                <h4 class="mt-4">11. Indemnity</h4>
                <p>You agree to indemnify RDD Shipping against any claims, penalties, duties, fines and costs arising from inaccurate shipment information you provide, from shipping prohibited or restricted items, or from your breach of these Terms.</p>

                <hr class="my-4">

                <!-- 12 -->
                <h4 class="mt-4">12. Our Sister Companies</h4>
                <p>RDD Shipping is one of the companies in the {{ config('group.parent.name') }}. The others are:</p>
                <ul>
                    @foreach (config('group.companies') as $company)
                        <li>
                            <strong>{{ $company['name'] }}</strong> — {{ $company['role'] }}
                            (<a href="{{ $company['url'] }}" target="_blank" rel="noopener">{{ $company['url_label'] }}</a>).
                        </li>
                    @endforeach
                </ul>
                <p>Each is a separate business with its own terms, contracts and liabilities. These Terms govern RDD Shipping only. Where we carry a shipment on behalf of KASAROSE, or where Neoride Africa completes a last-mile delivery on our behalf, RDD Shipping remains your point of contact for that shipment. If you buy a service from one of those companies in its own right, that company's terms apply to it, not these.</p>

                <hr class="my-4">

                <!-- 13 -->
                <h4 class="mt-4">13. Governing Law</h4>
                <p>These Terms are governed by the laws of the Republic of Ghana, and any dispute is subject to the exclusive jurisdiction of the courts of Ghana. Where services touch the United States, applicable US federal and state laws also apply to that portion of the service.</p>

                <hr class="my-4">

                <!-- 14 -->
                <h4 class="mt-4">14. Changes to These Terms</h4>
                <p>We may update these Terms from time to time. Changes are posted on this page with a new "Last updated" date. Continued use of our services or website after a change takes effect constitutes acceptance of the revised Terms.</p>

                <hr class="my-4">

                <!-- 15 -->
                <h4 class="mt-4">15. Contact Us</h4>
                <p>If you have any questions about these Terms, please contact us:</p>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6><i class="bi bi-geo-alt me-2"></i>USA Office</h6>
                        <p>
                            <strong>Phone:</strong> +1 (773) 970-0129<br>
                            <strong>Phone:</strong> +1 (574) 440-7460
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-geo-alt me-2"></i>Ghana Office</h6>
                        <p>
                            <strong>Phone:</strong> +233 50 972 5081<br>
                            <strong>Phone:</strong> +233 50 972 5073
                        </p>
                    </div>
                    <div class="col-12">
                        <p><i class="bi bi-envelope me-2"></i><strong>Email:</strong> kasabazaar109@gmail.com</p>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

@endsection
