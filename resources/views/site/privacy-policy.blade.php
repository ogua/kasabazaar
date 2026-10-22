@extends('site.layouts.app')

@section('title', 'Privacy Policy')
@section('meta_description', 'Privacy Policy for KASAROSE LOGISTICS — what information we collect through this website and our shipping services, how we use it, SMS notifications, and how data passes between the group companies.')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Privacy Policy</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Privacy Policy</li>
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
               <p class="text-mute">Last updated: October 21, 2026</p>

               <p>This Privacy Policy explains what information KASAROSE LOGISTICS, operated by
                  <strong>{{ config('group.company.legal_name') }}</strong> ("we", "us"), collects through this website and when you use our shipping and logistics
                  services, how we use it, and the choices you have.</p>

               <p><strong>It covers KASAROSE LOGISTICS only.</strong> KASAROSE LOGISTICS is the parent company of the
                  {{ config('group.parent.group_name') }}. The group's other companies are separately operated, hold
                  their own customer data and publish their own privacy policies on their own sites &mdash; see
                  <a href="#group-sharing">Sharing Within the {{ config('group.parent.group_name') }}</a> below.</p>

               <h3 class="display-5 mt-5">Information We Collect</h3>
               <p><strong>Through this website's forms</strong>, we collect only what you choose to give us:</p>
               <ul class="ct-list">
                  <li>Contact form: your name, email address, phone number and message.</li>
                  <li>Quote request form: your name, email address, phone number, and shipment details you provide
                     (such as service type, weight, dimensions, origin and destination).</li>
                  <li>Feedback form: your name, contact details, rating and comments, and the tracking number or
                     reference of the shipment you are telling us about.</li>
                  <li>Whether you agreed to receive SMS or WhatsApp updates, where you gave us a phone number.</li>
               </ul>
               <p><strong>When you ship with us</strong>, we also collect:</p>
               <ul class="ct-list">
                  <li>Sender and recipient names, phone numbers, email addresses and addresses (in the USA and
                     Ghana).</li>
                  <li>Your mobile number and consent to receive SMS or WhatsApp notifications, captured when you
                     book a shipment.</li>
                  <li>Shipment details: item description, weight, dimensions and declared value.</li>
                  <li>Payment information, processed through our payment partner (Paystack).</li>
               </ul>
               <p>This website sets only the cookies it needs to work securely (a session cookie and a security
                  token for its forms). We do not use tracking cookies, analytics scripts or advertising pixels.
                  Like any website, our servers see what your browser sends with a normal page request (such as your
                  IP address and browser type) in standard web server logs.</p>

               <h3 class="display-5 mt-5">Shipment Tracking</h3>
               <p>Our <a href="{{ route('our-tracking') }}">Tracking</a> page does not ask you to create an account or log in.
                  When you enter a tracking number or shipment reference, we look that number up in our shipment
                  records and show you the status of that one shipment: its reference, its current status, the
                  branches it is travelling between, the dates it moved, and any status notes our staff have
                  recorded against it.</p>
               <p>When you search with the tracking number itself (not the shipment reference), we also list the
                  items in the shipment &mdash; item name, quantity and box number, or a vehicle&rsquo;s year, make
                  and model &mdash; grouped by recipient, with each recipient shown by first name and last initial
                  only, so the people at the other end can recognise their goods.</p>
               <p>We deliberately do not show anything else held against a shipment &mdash; no sender names, no
                  full recipient names, no addresses or phone numbers, no vehicle identification numbers, and no
                  prices, values or payment information. Anyone holding the tracking number can see what is
                  described above, so treat your tracking number as you would any other reference to your
                  shipment. We do not store the numbers you type into the tracking form beyond ordinary web
                  server logs.</p>

               <h3 class="display-5 mt-5">How We Use Your Information</h3>
               <ul class="ct-list">
                  <li>To respond to your inquiry, quote request or feedback &mdash; for example, to call or email you
                     back, or to prepare a shipping quote.</li>
                  <li>To process, manage and track your shipments between the USA and Ghana, and to generate
                     shipping labels, packing slips and delivery documents.</li>
                  <li>To communicate with you about your shipment status, invoices and receipts.</li>
                  <li>To send SMS/text message or WhatsApp notifications about your shipment and payments, where you
                     have opted in.</li>
                  <li>To process payments securely through our payment processor.</li>
                  <li>To comply with legal obligations applicable in Ghana and the United States.</li>
               </ul>
               <p>We do not sell or rent your information.</p>

               <h3 class="display-5 mt-5" id="sms-notifications">SMS / Text Message Notifications</h3>
               <p>When you book a shipment, you may provide a mobile number and opt in to receive SMS (text message)
                  updates about that shipment. If you opt in, we send automated transactional messages triggered by
                  activity on your order &mdash; such as booking confirmation, pickup, departure, arrival at the port
                  in Ghana, out-for-delivery and delivery confirmation, payment receipts, and tracking-number
                  updates. These messages are sent by KASAROSE LOGISTICS under the sender ID
                  <strong>KASAROSE</strong>.</p>
               <ul class="ct-list">
                  <li><strong>Consent:</strong> SMS updates are sent only to numbers that opted in. Opting in is not a
                     condition of using our services.</li>
                  <li><strong>Message frequency:</strong> Varies with shipment activity &mdash; typically 3&ndash;10
                     messages over the life of a shipment.</li>
                  <li><strong>Rates:</strong> Message and data rates may apply, depending on your mobile carrier and
                     plan.</li>
                  <li><strong>Opting out:</strong> Reply <strong>STOP</strong> to any message to unsubscribe, or
                     <strong>HELP</strong> for assistance. You may also contact us using the details below.</li>
                  <li><strong>Providers:</strong> Messages are delivered through our messaging providers, Twilio and
                     Arkesel, acting as data processors on our behalf.</li>
                  <li><strong>No sharing:</strong> Mobile numbers collected for SMS notifications, and your opt-in
                     consent, are never sold, rented, or shared with third parties or other group companies for their
                     own marketing or promotional purposes.</li>
               </ul>
               <p>This programme is also documented on our <a href="{{ route('sms-terms') }}">SMS Terms</a> page.</p>
               <p id="whatsapp-notifications"><strong>WhatsApp:</strong> if you separately agree to receive updates
                  on WhatsApp, the same shipment and payment updates are sent to your WhatsApp number instead of by
                  SMS, through WhatsApp (operated by Meta) and our WhatsApp messaging provider. WhatsApp's own privacy
                  policy applies to its handling of those messages. You can ask us to stop WhatsApp updates at any
                  time using the contact details below; we will then use SMS only if you have opted in to it.</p>

               <h3 class="display-5 mt-5">Sharing of Your Information</h3>
               <p>We share your information only in the following circumstances:</p>
               <ul class="ct-list">
                  <li><strong>Service providers:</strong> payment processors (Paystack), SMS/messaging providers
                     (Twilio, Arkesel, and WhatsApp for customers who opted in to it), logistics partners and warehouse operators, strictly to fulfil your
                     shipment.</li>
                  <li><strong>Legal requirements:</strong> when required by law, regulation or government authority
                     in Ghana or the United States.</li>
                  <li><strong>Business protection:</strong> to protect the rights, property or safety of
                     {{ config('group.company.legal_name') }}, our customers or the public.</li>
               </ul>

               <h4 class="mt-4" id="group-sharing">Sharing Within the {{ config('group.parent.group_name') }}</h4>
               <p>KASAROSE LOGISTICS is the parent company of the group. The other companies are:</p>
               <ul class="ct-list">
                  @foreach (config('group.companies') as $company)
                  <li><strong>{{ $company['name'] }}</strong> &mdash; {{ $company['role'] }}
                     (<a href="{{ $company['url'] }}" target="_blank" rel="noopener">{{ $company['url_label'] }}</a>).
                     Its customers' accounts and orders are governed by its own privacy policy, not this one.</li>
                  @endforeach
               </ul>
               <p>We share personal data with these companies only where there is a specific operational reason,
                  and only the minimum needed:</p>
               <ul class="ct-list">
                  <li><strong>KASAROSE MARKET</strong> passes us recipient names, delivery addresses, phone numbers and
                     parcel details for the marketplace orders we carry, and we return tracking events so shoppers
                     can follow a shipment. Data about a KASAROSE MARKET order that we did not carry stays with
                     KASAROSE MARKET.</li>
                  <li><strong>Neoride Africa</strong> receives recipient name, address and phone number where its
                     riders complete a last-mile delivery on our behalf in Kumasi and Ejisu.</li>
                  <li>An inquiry sent through this website that belongs with another company &mdash; for example, a
                     marketplace order question &mdash; is passed to that company.</li>
               </ul>
               <p>Each company is responsible for its own use of the data it receives. <strong>Marketing lists are
                  never shared between group companies</strong>, and we do not track you across the group's
                  websites.</p>

               <h3 class="display-5 mt-5">Payment Information</h3>
               <p>All payment transactions are processed through <strong>Paystack</strong>, a secure third-party
                  payment processor. We do not store your full card details on our servers. Please review Paystack's
                  privacy policy for how they handle your payment data.</p>

               <h3 class="display-5 mt-5">How We Store &amp; Protect Your Information</h3>
               <p>Form submissions are stored in our customer-service system and copied by email to our support
                  team. Shipment and financial records are kept for as long as needed for the purposes above,
                  including accounting, legal compliance and dispute resolution &mdash; typically a minimum of seven
                  (7) years. We take reasonable technical and organisational steps to protect the information you
                  share with us, but no method of electronic transmission or storage is completely secure, and we
                  cannot guarantee absolute security.</p>

               <h3 class="display-5 mt-5">International Data Transfers</h3>
               <p>Our operations span the United States and Ghana, so your information may be transferred between
                  these countries. We handle it with the same level of care wherever it is processed.</p>

               <h3 class="display-5 mt-5">Your Choices</h3>
               <p>You can ask us what information we hold about you, ask us to correct it, ask us to delete it
                  (subject to legal retention requirements), or object to certain uses of it, by emailing
                  <a href="mailto:{{ config('website.contact.email_support') }}">{{ config('website.contact.email_support') }}</a>.
                  We will respond to reasonable requests within a reasonable timeframe.</p>

               <h3 class="display-5 mt-5">Children's Privacy</h3>
               <p>Our services are intended for businesses and individuals seeking shipping, real estate or
                  property management services, and are not directed at children. We do not knowingly collect
                  information from children; if you believe we have, please contact us so we can delete it.</p>

               <h3 class="display-5 mt-5">Changes to This Policy</h3>
               <p>We may update this Privacy Policy from time to time. Changes will be posted on this page with an
                  updated "Last updated" date.</p>

               <h3 class="display-5 mt-5">Contact Us</h3>
               <p>Questions about this Privacy Policy can be sent to
                  <a href="mailto:{{ config('website.contact.email_support') }}">{{ config('website.contact.email_support') }}</a>,
                  by phone on <a href="tel:{{ config('website.contact.phone_us_1_tel') }}">{{ config('website.contact.phone_us_1') }}</a> (US) or
                  <a href="tel:{{ config('website.contact.phone_gh_1_tel') }}">{{ config('website.contact.phone_gh_1') }}</a> (Ghana),
                  or via our <a href="{{ route('contact-us') }}">Contact page</a>.</p>
            </div>
         </div>
      </div>
   </section>

@endsection
