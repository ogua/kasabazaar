{{-- Mother's Day Overlay — 2026-05-09 & 2026-05-10 --}}
@if(in_array(\Carbon\Carbon::now()->format('Y-m-d'), ['2026-05-09', '2026-05-10']))

<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap" rel="stylesheet">

<style>
#md-overlay {
  position: fixed;
  inset: 0;
  z-index: 999999;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}
#md-overlay::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(160deg, rgba(80,0,25,0.92) 0%, rgba(180,20,70,0.88) 50%, rgba(60,0,20,0.95) 100%);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
}

/* ── Petals ── */
.md-petal {
  position: absolute;
  top: -8vh;
  pointer-events: none;
  user-select: none;
  animation: mdPetalFall linear infinite;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}
@keyframes mdPetalFall {
  0%   { transform: translateY(-8vh)  translateX(0px)   rotate(0deg);   opacity: 1;   }
  30%  { transform: translateY(30vh)  translateX(18px)  rotate(110deg);               }
  60%  { transform: translateY(60vh)  translateX(-12px) rotate(220deg);               }
  100% { transform: translateY(108vh) translateX(8px)   rotate(360deg); opacity: 0.15;}
}

/* ── Sparkles ── */
.md-spark {
  position: absolute;
  pointer-events: none;
  color: #ffd0e0;
  animation: mdSparkle ease-in-out infinite;
  filter: drop-shadow(0 0 5px #ff80a0);
}
@keyframes mdSparkle {
  0%,100% { opacity: 0; transform: scale(0) rotate(0deg);   }
  50%      { opacity: 1; transform: scale(1.3) rotate(180deg); }
}

/* ── Ribbon shimmer bar (top of flier) ── */
.md-shimmer-bar {
  height: 6px;
  border-radius: 6px 6px 0 0;
  background: linear-gradient(90deg, #a0043c, #ff8aaa, #ffd0dc, #ff8aaa, #a0043c);
  background-size: 300% 100%;
  animation: mdShimmer 2.8s linear infinite;
}
@keyframes mdShimmer {
  0%   { background-position: 300% center; }
  100% { background-position: -300% center; }
}

/* ── Flier wrapper ── */
.md-flier-wrap {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: mdCardFloat 4s ease-in-out infinite;
  max-width: min(460px, 88vw);
}
@keyframes mdCardFloat {
  0%,100% { transform: translateY(0px);  }
  50%      { transform: translateY(-9px); }
}

/* ── Slideshow container ── */
.md-slideshow {
  position: relative;
  width: 100%;
  overflow: hidden;
  border-radius: 0 0 16px 16px;
  box-shadow:
    0 0 0 3px rgba(255,160,190,0.5),
    0 0 0 7px rgba(160,4,60,0.2),
    0 24px 70px rgba(100,0,30,0.6);
}

/* slide-1 sits in normal flow → defines container height */
.md-slide-1 {
  display: block;
  width: 100%;
}
/* slide-2 overlays slide-1 absolutely */
.md-slide-2 {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/*
  Crossfade + Ken Burns:
  - Each slide is fully visible for ~4 s then fades over 1 s
  - Cycle = 10 s; slide-2 starts at the midpoint (-5 s delay)
  - Subtle scale zoom (1 → 1.05) while on-screen, resets while hidden
*/
@keyframes mdCrossfade {
  0%   { opacity: 1; transform: scale(1);    }
  40%  { opacity: 1; transform: scale(1.05); }
  50%  { opacity: 0; transform: scale(1.05); }
  90%  { opacity: 0; transform: scale(1);    }
  100% { opacity: 1; transform: scale(1);    }
}
.md-slide-1 { animation: mdCrossfade 10s ease-in-out infinite; }
.md-slide-2 { animation: mdCrossfade 10s ease-in-out infinite; animation-delay: -5s; }

/* ── Slide dot indicators ── */
.md-dots {
  display: flex;
  gap: 8px;
  justify-content: center;
  margin-top: 0.55rem;
}
.md-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: rgba(255,255,255,0.35);
  animation: mdDotActive 10s ease-in-out infinite;
}
.md-dot-2 { animation-delay: -5s; }
@keyframes mdDotActive {
  0%,45%  { background: rgba(255,255,255,0.9); transform: scale(1.3); }
  50%,95% { background: rgba(255,255,255,0.3); transform: scale(1);   }
  100%    { background: rgba(255,255,255,0.9); transform: scale(1.3); }
}

/* ── Close button ── */
.md-close-btn {
  margin-top: 1.1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #a0043c, #e8608a);
  color: #fff;
  border: none;
  border-radius: 50px;
  padding: 0.7rem 2rem;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  letter-spacing: 0.04em;
  box-shadow: 0 6px 24px rgba(160,4,60,0.45);
  transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
  font-family: 'Dancing Script', cursive;
  font-size: 1.1rem;
}
.md-close-btn:hover {
  transform: translateY(-2px) scale(1.04);
  box-shadow: 0 10px 32px rgba(160,4,60,0.55);
  filter: brightness(1.1);
}
</style>

<div id="md-overlay">

  {{-- Petals --}}
  @php
    $flowers = ['🌸','🌺','🌷','🌹','💮','🌼','💐','🌸','🌺','🌷'];
    for ($i = 0; $i < 30; $i++) {
      $petalData[] = [
        'char'  => $flowers[$i % count($flowers)],
        'left'  => round(fmod($i * 3.45, 100), 1),
        'size'  => round(1.0 + ($i % 4) * 0.35, 2),
        'dur'   => round(9 + ($i % 9), 1),
        'delay' => round(-($i * 0.55), 2),
      ];
    }
  @endphp
  @foreach($petalData as $p)
    <span class="md-petal" style="left:{{$p['left']}}%;font-size:{{$p['size']}}rem;animation-duration:{{$p['dur']}}s;animation-delay:{{$p['delay']}}s;">{{$p['char']}}</span>
  @endforeach

  {{-- Sparkles --}}
  @foreach([
    ['9%','7%','2.1s','0s'],['18%','90%','2.6s','0.5s'],['50%','4%','1.9s','0.9s'],
    ['52%','93%','2.4s','0.3s'],['80%','10%','2.0s','1.1s'],['78%','88%','1.8s','0.7s'],
    ['33%','2%','2.3s','1.4s'],['30%','96%','2.1s','0.8s'],
  ] as [$t,$l,$d,$dl])
    <span class="md-spark" style="top:{{$t}};left:{{$l}};font-size:1.4rem;animation-duration:{{$d}};animation-delay:{{$dl}};">✦</span>
  @endforeach

  {{-- Flier slideshow --}}
  <div class="md-flier-wrap">
    <div class="md-shimmer-bar" style="width:100%;"></div>

    <div class="md-slideshow">
      {{-- Slide 1 — pink CEO flier (shown first) --}}
      <img class="md-slide-1"
           src="{{ URL::to('images/mother-day/mothers-day-2.jpeg') }}"
           alt="Happy Mother's Day — Mrs Rose Adel">
      {{-- Slide 2 — family flier (crossfades in after 5 s) --}}
      <img class="md-slide-2"
           src="{{ URL::to('images/mother-day/mothers-day-1.jpeg') }}"
           alt="Happy Mother's Day — Mr & Mrs Adel">
    </div>

    {{-- Dot indicators --}}
    <div class="md-dots">
      <span class="md-dot md-dot-1"></span>
      <span class="md-dot md-dot-2"></span>
    </div>

    <button class="md-close-btn"
            onclick="document.getElementById('md-overlay').style.display='none';sessionStorage.setItem('md_closed_2026','1');">
      🌷 &nbsp;Continue to Website
    </button>
  </div>

</div>

<script>
  (function(){
    if (sessionStorage.getItem('md_closed_2026') === '1') {
      var el = document.getElementById('md-overlay');
      if (el) el.style.display = 'none';
    }
  })();
</script>

@endif
