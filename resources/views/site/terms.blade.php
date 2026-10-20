@extends('site.layouts.app')

@section('title', 'Terms & Conditions')
@section('meta_description', 'Terms & Conditions for KASAROSE LOGISTICS — shipping, payment, liability and SMS terms, and use of this website.')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Terms &amp; Conditions</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Terms &amp; Conditions</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <section class="sec-padding">
      <div class="container">
         <div class="row justify-content-center">
            <div class="col-lg-9">
               <p class="text-mute">Last updated: October 20, 2026</p>

               <p>These Terms &amp; Conditions ("Terms") govern your use of this website and the shipping,
                  package-forwarding and logistics services provided by
                  <strong>{{ config('group.company.legal_name') }}</strong>, trading as KASAROSE LOGISTICS (known as
                  KasaBazaar until 2026 &mdash; "we", "us"). By requesting a quote, booking a shipment, or
                  using this website, you agree to these Terms and to our
                  <a href="{{ route('privacy-policy') }}">Privacy Policy</a>.</p>

               <p>KASAROSE LOGISTICS is the parent company of the {{ config('group.parent.group_name') }}. These Terms
                  cover KASAROSE LOGISTICS only; the group's other companies operate separately and publish their own
                  terms &mdash; see <a href="#sister-companies">Our Sister Companies</a>.</p>

               <h3 class="display-5 mt-5">1. Eligibility and Acceptance</h3>
               <p>You must be at least 18 years old and legally able to enter a contract to use our services. If you
                  use our services on behalf of a business, you confirm you are authorised to bind that business to
                  these Terms.</p>

               <h3 class="display-5 mt-5">2. Our Services and This Website</h3>
               <p>We provide freight forwarding, package consolidation and door-to-door shipping between the United
                  States and Ghana, together with related domestic delivery, warehousing and customs-coordination
                  services, and real estate and property management services. Service availability, routes and
                  transit times may change without notice.</p>
               <p>Submitting a contact or quote request through this website is an inquiry only &mdash; it is not a
                  binding contract, order or reservation. Any shipping quote, service pricing or engagement is
                  confirmed separately, directly with our team.</p>

               <h3 class="display-5 mt-5">3. Quotes, Charges and Payment</h3>
               <ul class="ct-list">
                  <li>Quotations are estimates based on the information you provide and are valid for the period
                     stated on the quote. Final charges are based on the actual weight, dimensions, contents and
                     declared value of your shipment.</li>
                  <li>Charges may include freight, handling, consolidation, storage, customs duties and taxes, and
                     last-mile delivery.</li>
                  <li>Payments are processed securely through <strong>Paystack</strong>. We do not store your full card
                     details.</li>
                  <li>Shipments will not be released for delivery until all outstanding charges, including any duties
                     and taxes we have advanced on your behalf, are paid in full.</li>
                  <li>Storage fees may apply where a shipment is not collected or cleared within the free storage
                     period we notify to you.</li>
               </ul>

               <h3 class="display-5 mt-5">4. Your Responsibilities</h3>
               <ul class="ct-list">
                  <li>Provide accurate and complete sender and recipient details, item descriptions, weights,
                     dimensions and declared values.</li>
                  <li>Ensure that every item you ship is lawful to export from the country of origin and to import
                     into the destination country.</li>
                  <li>Package items adequately for international transit. We are not liable for damage caused by
                     inadequate packing.</li>
                  <li>Pay all customs duties, taxes and levies assessed by the destination authorities.</li>
               </ul>

               <h3 class="display-5 mt-5">5. Prohibited and Restricted Items</h3>
               <p>You may not ship items that are prohibited or restricted by law, by carriers, or by customs
                  authorities in the United States or Ghana, including but not limited to: currency and negotiable
                  instruments; firearms, ammunition and explosives; illegal drugs and controlled substances; hazardous
                  or flammable materials; perishable goods without prior arrangement; counterfeit goods; and human
                  remains. We may refuse, hold, return or dispose of any shipment that breaches this section, and you
                  remain responsible for the resulting charges.</p>

               <h3 class="display-5 mt-5">6. Transit Times, Customs and Delivery</h3>
               <p>Transit times are estimates and are not guaranteed &mdash; see our
                  <a href="{{ route('delivery-policy') }}">Delivery Policy</a> for current estimates. Delays caused by
                  customs inspection, weather, carrier scheduling, incomplete documentation or unpaid charges are
                  outside our control. Risk in the goods passes to the recipient on delivery to the address provided.
                  If delivery cannot be completed after reasonable attempts, the shipment will be held for collection
                  and storage fees may apply.</p>

               <h3 class="display-5 mt-5">7. Liability and Claims</h3>
               <p>Our liability for loss of or damage to a shipment is limited to the lower of its declared value or
                  the actual documented value of the goods, and in any event to the maximum permitted by applicable law
                  and by the underlying carriers. We are not liable for indirect or consequential loss, loss of profit,
                  or loss of business opportunity. Claims must be submitted in writing within seven (7) days of
                  delivery or, in the case of non-delivery, within thirty (30) days of the expected delivery date,
                  with supporting evidence of value.</p>

               <h3 class="display-5 mt-5" id="sms-terms">8. SMS / Text Message Terms</h3>
               <p>When you book a shipment or send us a request through this website, you may opt in to receive SMS
                  (text message) notifications. By providing your mobile number and opting in, you agree to the
                  following:</p>
               <ul class="ct-list">
                  <li><strong>Program description:</strong> KASAROSE LOGISTICS sends automated transactional messages
                     about your shipment &mdash; booking confirmation, pickup, departure, port arrival, out-for-delivery
                     and delivery confirmation, payment receipts, and tracking-number updates.</li>
                  <li><strong>Consent:</strong> Messages are sent only to numbers that opted in. Consent to receive SMS
                     is not a condition of purchasing any goods or services.</li>
                  <li><strong>Message frequency:</strong> Varies with shipment activity &mdash; typically 3&ndash;10
                     messages over the life of a shipment.</li>
                  <li><strong>Cost:</strong> Message and data rates may apply, depending on your mobile carrier and
                     plan.</li>
                  <li><strong>Opt out:</strong> Reply <strong>STOP</strong> to any message to cancel. You will receive
                     one confirmation message and no further messages. Reply <strong>HELP</strong> for assistance, or
                     contact us using the details below.</li>
                  <li><strong>Carriers:</strong> Mobile carriers are not liable for delayed or undelivered
                     messages.</li>
                  <li><strong>Providers and privacy:</strong> Messages are delivered through our messaging providers,
                     Twilio and Arkesel. Mobile numbers and opt-in consent are handled in line with our
                     <a href="{{ route('privacy-policy') }}#sms-notifications">Privacy Policy</a> and are never sold,
                     rented, or shared with third parties or other group companies for their own marketing or
                     promotional purposes.</li>
               </ul>
               <p>The full programme terms are also on our <a href="{{ route('sms-terms') }}">SMS Terms</a> page.</p>

               <h3 class="display-5 mt-5">9. Website Content and Acceptable Use</h3>
               <p>The KASAROSE LOGISTICS name, logo, website design, text and images belong to
                  {{ config('group.company.legal_name') }} or its licensors and may not be used without written
                  permission. Service descriptions on this site are provided for general information and are subject
                  to change without notice. You may not scrape the site, interfere with its operation, submit false
                  information through our forms, attempt to access other users' accounts, or use it for any unlawful
                  purpose. The website is provided "as is", without warranties of any kind.</p>

               <h3 class="display-5 mt-5">10. Suspension and Termination</h3>
               <p>We may refuse service, suspend an account or cancel a booking where we reasonably believe these
                  Terms have been breached, where required by law, or where necessary to protect our customers, staff
                  or partners. Obligations already incurred &mdash; including outstanding charges and duties advanced
                  on your behalf &mdash; survive termination.</p>

               <h3 class="display-5 mt-5">11. Indemnity</h3>
               <p>You agree to indemnify KASAROSE LOGISTICS against any claims, penalties, duties, fines and costs
                  arising from inaccurate shipment information you provide, from shipping prohibited or restricted
                  items, or from your breach of these Terms.</p>

               <h3 class="display-5 mt-5" id="sister-companies">12. Our Sister Companies</h3>
               <p>KASAROSE LOGISTICS is the parent company of the {{ config('group.parent.group_name') }}. The group's
                  other companies are:</p>
               <ul class="ct-list">
                  @foreach (config('group.companies') as $company)
                  <li><strong>{{ $company['name'] }}</strong> &mdash; {{ $company['role'] }}
                     (<a href="{{ $company['url'] }}" target="_blank" rel="noopener">{{ $company['url_label'] }}</a>).</li>
                  @endforeach
               </ul>
               <p>Each is a separate business with its own terms, contracts and liabilities. These Terms govern
                  KASAROSE LOGISTICS only. Where we carry a shipment on behalf of KASAROSE MARKET, or where Neoride
                  Africa completes a last-mile delivery on our behalf, KASAROSE LOGISTICS remains your point of
                  contact for that shipment. If you buy a service from one of those companies in its own right
                  &mdash; including buying or selling on KASAROSE MARKET &mdash; that company's terms apply to it, not
                  these.</p>

               <h3 class="display-5 mt-5">13. Limitation of Liability</h3>
               <p>To the extent permitted by law, KASAROSE LOGISTICS is not liable for any indirect or consequential
                  loss arising from your use of this website. Nothing in these Terms limits liability that cannot
                  legally be limited.</p>

               <h3 class="display-5 mt-5">14. Governing Law</h3>
               <p>These Terms are governed by the laws of the Republic of Ghana, and any dispute is subject to the
                  exclusive jurisdiction of the courts of Ghana. Where services touch the United States, applicable
                  US federal and state laws also apply to that portion of the service.</p>

               <h3 class="display-5 mt-5">15. Changes to These Terms</h3>
               <p>We may update these Terms from time to time. Changes are posted on this page with a new "Last
                  updated" date. Continued use of our services or website after a change takes effect constitutes
                  acceptance of the revised Terms.</p>

               <h3 class="display-5 mt-5">16. Contact Us</h3>
               <p>Questions about these Terms can be sent to
                  <a href="mailto:{{ config('website.contact.email_support') }}">{{ config('website.contact.email_support') }}</a>,
                  by phone on <a href="tel:{{ config('website.contact.phone_us_1_tel') }}">{{ config('website.contact.phone_us_1') }}</a> (US) or
                  <a href="tel:{{ config('website.contact.phone_gh_1_tel') }}">{{ config('website.contact.phone_gh_1') }}</a> (Ghana),
                  or via our <a href="{{ route('contact-us') }}">Contact page</a>.</p>
            </div>
         </div>
      </div>
   </section>

@endsection
