{{--
   The closing call-to-action band.
   @include('site.partials.cta', ['eyebrow' => ..., 'heading' => ..., 'buttons' => [...]])
   `buttons` is a list of ['label' =>, 'href' =>, 'style' => 'primary'|'outline']; the
   default pair — Request a Quote / Contact Us — is what most pages want. `eyebrow` and
   `heading` are trusted copy and may carry HTML entities.
--}}
@php
    $eyebrow ??= 'Get Started';
    $heading ??= 'Ready to ship, invest, or move smarter?';
    $buttons ??= [
        ['label' => 'Request a Quote', 'href' => route('quote'), 'style' => 'primary'],
        ['label' => 'Contact Us', 'href' => route('contact-us'), 'style' => 'outline'],
    ];
@endphp
   <!-- CTA Section Start -->
   <section class="video-sec2 sec-padding jarallax" data-jarallax data-speed=".2">
      <div class="parallax-overly">
         <img src="{{ asset('site/images/serv-bg2.jpg') }}" alt="" class="jarallax-img">
      </div>
      <div class="container text-center">
         <span class="sub-title2 fadeInUp">{!! $eyebrow !!}</span>
         <h2 class="sec-title text-info">{!! $heading !!}</h2>
         <div class="d-flex justify-content-center gap-4 flex-wrap mt-4">
            @foreach ($buttons as $btn)
            <a href="{{ $btn['href'] }}"
               class="btn {{ $btn['style'] === 'outline' ? 'btn-outline' : 'btn-hover btn-primary' }}">
               {{ $btn['label'] }} <i class="fa fa-arrow-right"></i><span></span>
            </a>
            @endforeach
         </div>
      </div>
   </section>
   <!-- CTA Section End -->
