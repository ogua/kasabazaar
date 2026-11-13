{{--
   Rotating KASAROSE MARKET products on sale and live auctions (App\Services\PublicMarketShowcase).
   @include('site.partials.market-slider', ['slides' => [...]]) — sits in the market banner's art cell.
--}}
<div class="market-banner-art market-slider swiper" id="market-slider" aria-label="Products on sale and live auctions">
   <div class="swiper-wrapper">
      @foreach ($slides as $slide)
         <a class="swiper-slide market-slide" href="{{ $slide['url'] }}" target="_blank" rel="noopener">
            <span class="market-slide-photo">
               <img src="{{ $slide['image'] }}" alt="{{ $slide['name'] }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
               <span class="market-slide-badge is-{{ $slide['kind'] }}">{{ $slide['badge'] }}</span>
            </span>
            <span class="market-slide-name">{{ $slide['name'] }}</span>
            <span class="market-slide-price">
               {{ $slide['price'] }}
               @if ($slide['was'])
                  <s>{{ $slide['was'] }}</s>
               @endif
            </span>
            @if ($slide['note'])
               <span class="market-slide-note"><i class="fa-regular fa-clock" aria-hidden="true"></i> {{ $slide['note'] }}</span>
            @endif
         </a>
      @endforeach
   </div>
   <div class="swiper-pagination"></div>
</div>
@push('scripts')
<script>
   (function () {
      var el = document.getElementById('market-slider');
      if (!el || typeof Swiper === 'undefined') { return; }
      var count = el.querySelectorAll('.swiper-slide').length;
      new Swiper(el, {
         slidesPerView: 1,
         spaceBetween: 16,
         loop: count > 2,
         autoplay: count > 1 ? { delay: 3500, disableOnInteraction: false, pauseOnMouseEnter: true } : false,
         pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
         breakpoints: { 576: { slidesPerView: Math.min(2, count) } }
      });
   })();
</script>
@endpush
