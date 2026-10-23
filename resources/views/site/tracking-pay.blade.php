@extends('site.layouts.app')

@section('title', 'Pay for Shipment '.$trackingNumber)
@section('meta_description', 'Pay the balance on a KASAROSE LOGISTICS shipment securely online.')
@section('meta_robots', 'noindex, nofollow')

@section('content')

   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Pay for Your Shipment</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('our-tracking', ['ref' => $trackingNumber]) }}">Tracking</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Payment</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>

   <section class="tracking-page sec-padding">
      <div class="container">
         <div class="row">
            <div class="col-lg-7 col-md-10 mx-auto">

               <div class="tracking-form-wrapper track-pay">
                  <p class="mb-1">Shipment</p>
                  <h2 class="display-5 mb-4">{{ $trackingNumber }}</h2>

                  @if (! $confirmed)
                     <h3 class="h5 mb-2"><i class="fa-solid fa-shield-halved text-primary me-1"></i> Confirm it&rsquo;s you</h3>
                     <p>To keep your account private, we show the balance only after you confirm the
                        <strong>sender&rsquo;s or receiver&rsquo;s phone number</strong> we have on file for this shipment.</p>

                     <form method="post" action="{{ route('tracking-payment.confirm-phone', $trackingNumber) }}" class="mt-4" novalidate>
                        @csrf
                        <label for="phone" class="form-label fw-bold">Phone number</label>
                        <input type="tel" id="phone" name="phone" inputmode="tel" autocomplete="tel"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}" placeholder="e.g. 024 123 4567 or +1 555 123 4567" required autofocus>
                        @error('phone')
                           <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="btn btn-primary mt-4 w-100">
                           Continue <i class="fa fa-arrow-right"></i><span></span>
                        </button>
                     </form>

                  @elseif ($balance <= 0)
                     <div class="alert alert-success mb-0" role="status">
                        <h3 class="h5 mb-1"><i class="fa-solid fa-circle-check"></i> This shipment is fully paid</h3>
                        <p class="mb-0">There is nothing left to pay. Thank you for shipping with us.</p>
                     </div>

                  @else
                     <div class="row g-3 mb-4">
                        <div class="col-4">
                           <div class="bg-shade round p-3 h-100">
                              <p class="mb-1 small text-uppercase">Total</p>
                              <p class="mb-0 fw-bold">${{ number_format($total, 2) }}</p>
                           </div>
                        </div>
                        <div class="col-4">
                           <div class="bg-shade round p-3 h-100">
                              <p class="mb-1 small text-uppercase">Paid</p>
                              <p class="mb-0 fw-bold">${{ number_format($paid, 2) }}</p>
                           </div>
                        </div>
                        <div class="col-4">
                           <div class="track-pay-due round p-3 h-100">
                              <p class="mb-1 small text-uppercase">Balance due</p>
                              <p class="mb-0 fw-bold">${{ number_format($balance, 2) }}</p>
                           </div>
                        </div>
                     </div>

                     <form method="post" action="{{ route('tracking-payment.store', $trackingNumber) }}" id="track-pay-form"
                        data-fee-percent="{{ $feePercent }}" data-exchange-rate="{{ $exchangeRate ?? 0 }}" novalidate>
                        @csrf
                        <label for="amount" class="form-label fw-bold">Amount to pay (USD)</label>
                        <div class="input-group">
                           <span class="input-group-text">$</span>
                           <input type="number" id="amount" name="amount" step="0.01" min="1" max="{{ $balance }}" inputmode="decimal"
                              class="form-control @error('amount') is-invalid @enderror"
                              value="{{ old('amount', number_format($balance, 2, '.', '')) }}" required>
                        </div>
                        <p class="small text-muted mt-1 mb-0">Pay the full balance, or any part of it from $1.00.</p>
                        @error('amount')
                           <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        @if ($feePercent > 0)
                           <dl class="track-pay-breakdown mt-4 mb-0">
                              <div><dt>Credited to your shipment</dt><dd id="track-pay-credit">${{ number_format($charge['amount'], 2) }}</dd></div>
                              <div><dt>Transaction charge ({{ rtrim(rtrim(number_format($feePercent, 2), '0'), '.') }}%)</dt><dd id="track-pay-fee">${{ number_format($charge['fee'], 2) }}</dd></div>
                              <div class="track-pay-breakdown-total"><dt>Total you pay</dt><dd id="track-pay-total">${{ number_format($charge['total'], 2) }}</dd></div>
                              @if ($exchangeRate)
                                 <div class="track-pay-breakdown-local"><dt>Charged in cedis <small>($1 = GH₵{{ number_format($exchangeRate, 2) }})</small></dt><dd id="track-pay-local">GH₵{{ number_format($charge['total'] * $exchangeRate, 2) }}</dd></div>
                              @endif
                           </dl>
                           <p class="small text-muted mt-1 mb-0">The transaction charge is the card processor&rsquo;s fee, so your whole payment goes to your balance.</p>
                        @endif

                        <label for="email" class="form-label fw-bold mt-4">Email for your receipt</label>
                        <input type="email" id="email" name="email" autocomplete="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" placeholder="you@example.com" required>
                        @error('email')
                           <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        <button type="submit" class="btn btn-primary mt-4 w-100" id="track-pay-button">
                           {{-- Not a <span>: the theme turns every span inside .btn into its hover ripple. --}}
                           Pay <b id="track-pay-amount">${{ number_format($charge['total'], 2) }}</b> securely <i class="fa-solid fa-lock"></i>
                        </button>
                     </form>

                     <p class="small text-muted mt-3 mb-0">
                        <i class="fa-solid fa-circle-info"></i>
                        You&rsquo;ll be taken to Paystack&rsquo;s secure checkout.
                        @if ($currency === 'USD')
                           You are charged in <strong>US dollars (USD)</strong>; your bank may add its own conversion fee.
                        @else
                           Amounts are in US dollars; Paystack charges the <strong>cedi equivalent</strong> at today&rsquo;s
                           rate (shown above), so you can pay by card or mobile money.
                        @endif
                        We send your receipt by email and SMS, and you can verify it any time with its reference.
                     </p>
                  @endif
               </div>

               <p class="text-center mt-4">
                  <a href="{{ route('our-tracking', ['ref' => $trackingNumber]) }}"><i class="fa-solid fa-arrow-left"></i> Back to tracking</a>
                  <span class="mx-2">&middot;</span>
                  <a href="{{ route('tracking-payment.verify') }}">Verify a payment</a>
               </p>
               @include('site.partials.track-help')
            </div>
         </div>
      </div>
   </section>

@endsection

@push('scripts')
<script>
   (function () {
      var form = document.getElementById('track-pay-form');
      if (!form) {
         return;
      }

      var amount = document.getElementById('amount');
      var button = document.getElementById('track-pay-button');
      var buttonHtml = button.innerHTML;

      var feePercent = parseFloat(form.dataset.feePercent) || 0;
      var exchangeRate = parseFloat(form.dataset.exchangeRate) || 0;
      var money = function (cents) { return '$' + (cents / 100).toFixed(2); };
      var setText = function (id, text) {
         var el = document.getElementById(id);
         if (el) {
            el.textContent = text;
         }
      };

      // Same gross-up as ShipmentPaymentService::chargeFor(): total = amount / (1 - rate),
      // rounded up to the cent, so the whole amount reaches the shipment.
      amount.addEventListener('input', function () {
         var value = parseFloat(amount.value);
         var cents = isNaN(value) || value < 0 ? 0 : Math.round(value * 100);
         var total = feePercent > 0 ? Math.ceil(Number((cents / (1 - feePercent / 100)).toFixed(6))) : cents;

         setText('track-pay-credit', money(cents));
         setText('track-pay-fee', money(total - cents));
         setText('track-pay-total', money(total));
         setText('track-pay-amount', money(total));
         if (exchangeRate > 0) {
            setText('track-pay-local', 'GH₵' + (Math.round(total * exchangeRate) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
         }
      });

      form.addEventListener('submit', function () {
         button.disabled = true;
         button.innerHTML = 'Opening secure checkout&hellip; <i class="fa fa-spinner fa-spin"></i>';
      });

      window.addEventListener('pageshow', function () {
         button.disabled = false;
         button.innerHTML = buttonHtml;
      });
   })();
</script>
@endpush
