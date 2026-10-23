@extends('site.layouts.app')

@section('title', 'Verify a Payment')
@section('meta_description', 'Check that a KASAROSE LOGISTICS shipment payment went through, using the payment reference on your receipt.')

@section('content')

   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Verify a Payment</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('our-tracking') }}">Tracking</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Verify a Payment</li>
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
            <div class="col-lg-10 mx-auto">
               <div class="tracking-form-wrapper">
                  <div class="sec-intro text-center mb-0">
                     <span class="sub-title2">Did My Payment Go Through?</span>
                     <h2 class="sec-title">Enter Your Payment Reference</h2>
                     <p>It starts with <strong>KRP-</strong> and is in the receipt we sent by email and SMS.</p>
                  </div>
                  <form method="get" action="{{ route('tracking-payment.verify') }}" class="tracking-form d-sm-flex">
                     <label for="reference" class="visually-hidden">Payment reference</label>
                     <input type="text" id="reference" name="reference"
                        class="form-control @error('reference') is-invalid @enderror"
                        value="{{ $reference }}" placeholder="KRP-XXXXXXXXXXXXXX" required autofocus>
                     <button type="submit" class="btn btn-primary">
                        Verify <i class="fa fa-arrow-right"></i><span></span>
                     </button>
                  </form>
                  @error('reference')
                     <div class="alert alert-danger mb-0" role="alert">{{ $message }}</div>
                  @enderror
               </div>
            </div>
         </div>
      </div>
   </section>

@endsection
