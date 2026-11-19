{{--
   A single-series chart rendered as inline SVG — no chart library, so it works without JS
   and prints. public/site/js/charts.js adds the hover tooltip and crosshair.

   @include('site.partials.chart', [
       'id' => 'revenue-chart',
       'type' => 'bar',                // bar | area
       'title' => 'Monthly revenue',   // used for the table caption and aria-label
       'points' => [['label' => 'Jan 2026', 'short' => 'Jan', 'value' => 1200.0, 'display' => '$1,200'], ...],
       'axis' => fn (float $v): string => '$'.number_format($v),   // y-axis tick text; null hides the axis numbers
       'tone' => 'primary',            // primary (red) | navy
       'valueHeading' => 'Revenue',
   ])
--}}
@php
   $width = 720;
   $height = 300;
   $left = $axis ? 58 : 14;
   $right = 14;
   $top = 18;
   $bottom = 34;
   $plotW = $width - $left - $right;
   $plotH = $height - $top - $bottom;
   $count = max(count($points), 1);
   $max = max(array_map(fn ($p) => (float) $p['value'], $points) ?: [0]);

   // A "nice" axis: 4 steps of 1, 2, 2.5 or 5 x 10^n.
   $rawStep = $max > 0 ? $max / 4 : 1;
   $magnitude = 10 ** floor(log10($rawStep));
   $step = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $magnitude)->first(fn ($s) => $s >= $rawStep);
   $axisMax = $step * 4;
   $y = fn (float $v): float => round($top + $plotH - ($v / $axisMax) * $plotH, 2);
   $band = $plotW / $count;
   $cx = fn (int $i): float => round($left + $band * $i + $band / 2, 2);
   $labelEvery = $count > 18 ? 3 : ($count > 9 ? 2 : 1);
   $baseline = $top + $plotH;
@endphp

<figure class="ksr-chart ksr-chart--{{ $tone }}" id="{{ $id }}" data-chart="{{ $type }}">
   <div class="ksr-chart__plot">
      <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="{{ $title }}" preserveAspectRatio="xMidYMid meet">
         @if ($type === 'area')
            <defs>
               <linearGradient id="{{ $id }}-fill" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0" class="ksr-chart__fill-top"/>
                  <stop offset="1" class="ksr-chart__fill-bottom"/>
               </linearGradient>
            </defs>
         @endif

         {{-- Gridlines and axis --}}
         @for ($t = 0; $t <= 4; $t++)
            @php $ty = $y($step * $t); @endphp
            <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $ty }}" y2="{{ $ty }}" class="ksr-chart__grid{{ $t === 0 ? ' is-base' : '' }}"/>
            @if ($axis)
               <text x="{{ $left - 10 }}" y="{{ $ty + 4 }}" text-anchor="end" class="ksr-chart__tick">{{ $axis($step * $t) }}</text>
            @endif
         @endfor

         @foreach ($points as $i => $point)
            @if ($i % $labelEvery === 0 || $i === $count - 1)
               <text x="{{ $cx($i) }}" y="{{ $height - 10 }}" text-anchor="middle" class="ksr-chart__tick">{{ $point['short'] }}</text>
            @endif
         @endforeach

         @if ($type === 'bar')
            @php $barW = max(min($band - 4, 30), 3); @endphp
            @foreach ($points as $i => $point)
               @php
                  $value = (float) $point['value'];
                  $x0 = round($cx($i) - $barW / 2, 2);
                  $y0 = $y($value);
                  $h = $baseline - $y0;
                  $r = min(4, $h, $barW / 2);
               @endphp
               @if ($h > 0.5)
                  <path class="ksr-chart__bar" data-i="{{ $i }}"
                        d="M{{ $x0 }},{{ $baseline }} V{{ $y0 + $r }} Q{{ $x0 }},{{ $y0 }} {{ $x0 + $r }},{{ $y0 }} H{{ $x0 + $barW - $r }} Q{{ $x0 + $barW }},{{ $y0 }} {{ $x0 + $barW }},{{ $y0 + $r }} V{{ $baseline }} Z"/>
               @endif
            @endforeach
         @else
            @php
               $coords = collect($points)->map(fn ($p, $i) => [$cx($i), $y((float) $p['value'])])->values();
               $line = $coords->map(fn ($c, $i) => ($i === 0 ? 'M' : 'L').$c[0].','.$c[1])->implode(' ');
               $last = $coords->last();
               $first = $coords->first();
            @endphp
            @if ($coords->isNotEmpty())
               <path d="{{ $line }} L{{ $last[0] }},{{ $baseline }} L{{ $first[0] }},{{ $baseline }} Z" fill="url(#{{ $id }}-fill)"/>
               <path d="{{ $line }}" class="ksr-chart__line"/>
               <circle cx="{{ $last[0] }}" cy="{{ $last[1] }}" r="5" class="ksr-chart__dot"/>
               <text x="{{ $last[0] - 10 }}" y="{{ $last[1] - 12 }}" text-anchor="end" class="ksr-chart__direct">{{ end($points)['display'] }}</text>
            @endif
            <line class="ksr-chart__cross" x1="0" x2="0" y1="{{ $top }}" y2="{{ $baseline }}" visibility="hidden"/>
            <circle class="ksr-chart__hover-dot" r="6" cx="0" cy="0" visibility="hidden"/>
         @endif

         {{-- Hit targets: a full-height column per point, wider than any mark. --}}
         @foreach ($points as $i => $point)
            <rect class="ksr-chart__hit" x="{{ round($left + $band * $i, 2) }}" y="{{ $top }}" width="{{ round($band, 2) }}" height="{{ $plotH }}"
                  data-i="{{ $i }}" data-x="{{ $cx($i) }}" data-y="{{ $y((float) $point['value']) }}"
                  data-label="{{ $point['label'] }}" data-value="{{ $point['display'] }}"/>
         @endforeach
      </svg>
      <div class="ksr-chart__tip" role="status" aria-live="polite" hidden>
         <span class="ksr-chart__tip-label"></span>
         <strong class="ksr-chart__tip-value"></strong>
      </div>
   </div>

   <details class="ksr-chart__table">
      <summary>View as table</summary>
      <div class="table-responsive">
         <table class="table table-sm mb-0">
            <caption class="visually-hidden">{{ $title }}</caption>
            <thead><tr><th scope="col">Month</th><th scope="col" class="text-end">{{ $valueHeading }}</th></tr></thead>
            <tbody>
               @foreach ($points as $point)
                  <tr><td>{{ $point['label'] }}</td><td class="text-end">{{ $point['display'] }}</td></tr>
               @endforeach
            </tbody>
         </table>
      </div>
   </details>
</figure>
