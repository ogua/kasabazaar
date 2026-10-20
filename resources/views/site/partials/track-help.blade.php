{{-- "Something wrong? Call us" — shown with every tracking error and under every result. --}}
<div class="track-help">
   <span class="track-help-label"><i class="fa-solid fa-headset"></i> Call us:</span>
   @foreach ([
      ['label' => config('website.contact.phone_gh_1'), 'tel' => config('website.contact.phone_gh_1_tel'), 'place' => 'Ghana'],
      ['label' => config('website.contact.phone_gh_2'), 'tel' => config('website.contact.phone_gh_2_tel'), 'place' => 'Ghana'],
      ['label' => config('website.contact.phone_us_1'), 'tel' => config('website.contact.phone_us_1_tel'), 'place' => 'USA'],
   ] as $line)
      <a href="tel:{{ $line['tel'] }}">
         <i class="fa-solid fa-phone"></i>{{ $line['label'] }}
         <span class="small text-muted fw-normal">({{ $line['place'] }})</span>
      </a>
   @endforeach
</div>
