{{-- CEO Birthday Overlay — 2026-05-11 --}}
@if(\Carbon\Carbon::now()->format('Y-m-d') === '2026-05-11')

<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap" rel="stylesheet">

<style>
#bd-overlay {
  position: fixed;
  inset: 0;
  z-index: 999999;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}
#bd-overlay::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 25% 35%, rgba(120,60,180,0.55) 0%, transparent 55%),
    radial-gradient(ellipse at 75% 65%, rgba(200,140,0,0.45)  0%, transparent 55%),
    linear-gradient(150deg, rgba(20,5,50,0.94) 0%, rgba(70,20,120,0.90) 45%, rgba(15,5,40,0.96) 100%);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

/* ── Confetti pieces ── */
.bd-confetti {
  position: absolute;
  top: -6vh;
  pointer-events: none;
  user-select: none;
  border-radius: 2px;
  animation: bdConfettiFall linear infinite;
}
@keyframes bdConfettiFall {
  0%   { transform: translateY(-6vh)   translateX(0px)   rotate(0deg)    scaleX(1);  opacity: 1;    }
  25%  { transform: translateY(25vh)   translateX(20px)  rotate(90deg)   scaleX(0.7);               }
  50%  { transform: translateY(52vh)   translateX(-15px) rotate(200deg)  scaleX(1.1);               }
  75%  { transform: translateY(76vh)   translateX(10px)  rotate(300deg)  scaleX(0.8);               }
  100% { transform: translateY(108vh)  translateX(-5px)  rotate(360deg)  scaleX(1);   opacity: 0.1; }
}

/* ── Emoji floaters ── */
.bd-float {
  position: absolute;
  pointer-events: none;
  animation: bdFloat ease-in-out infinite;
  filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3));
}
@keyframes bdFloat {
  0%,100% { transform: translateY(0px)   rotate(-5deg); opacity: 0.85; }
  50%      { transform: translateY(-18px) rotate(5deg);  opacity: 1;    }
}

/* ── Gold sparkles ── */
.bd-spark {
  position: absolute;
  pointer-events: none;
  animation: bdSparkle ease-in-out infinite;
  filter: drop-shadow(0 0 6px #ffd700);
}
@keyframes bdSparkle {
  0%,100% { opacity: 0; transform: scale(0) rotate(0deg);    }
  50%      { opacity: 1; transform: scale(1.4) rotate(180deg); }
}

/* ── Top shimmer bar (gold) ── */
.bd-shimmer-bar {
  height: 7px;
  border-radius: 6px 6px 0 0;
  background: linear-gradient(90deg, #7b2ff7, #ffd700, #fff4a0, #ffd700, #7b2ff7);
  background-size: 300% 100%;
  animation: bdShimmer 2.5s linear infinite;
}
@keyframes bdShimmer {
  0%   { background-position: 300% center; }
  100% { background-position: -300% center; }
}

/* ── Flier wrapper ── */
.bd-flier-wrap {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: bdCardFloat 4.5s ease-in-out infinite;
  max-width: min(460px, 88vw);
}
@keyframes bdCardFloat {
  0%,100% { transform: translateY(0px);   }
  50%      { transform: translateY(-10px); }
}

/* ── Flier image ── */
.bd-flier-img {
  width: 100%;
  border-radius: 0 0 16px 16px;
  display: block;
  box-shadow:
    0 0 0 3px rgba(255,215,0,0.45),
    0 0 0 7px rgba(123,47,247,0.25),
    0 24px 70px rgba(20,5,50,0.7),
    0 0 80px rgba(255,215,0,0.15);
}

/* ── Close button ── */
.bd-close-btn {
  margin-top: 1.1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #7b2ff7, #ffd700);
  color: #fff;
  border: none;
  border-radius: 50px;
  padding: 0.72rem 2rem;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  letter-spacing: 0.04em;
  font-family: 'Dancing Script', cursive;
  box-shadow: 0 6px 28px rgba(123,47,247,0.5), 0 0 20px rgba(255,215,0,0.25);
  transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
  text-shadow: 0 1px 3px rgba(0,0,0,0.25);
}
.bd-close-btn:hover {
  transform: translateY(-2px) scale(1.04);
  box-shadow: 0 10px 36px rgba(123,47,247,0.6), 0 0 30px rgba(255,215,0,0.35);
  filter: brightness(1.1);
}
</style>

<div id="bd-overlay">

  {{-- ── Confetti rectangles ── --}}
  @php
    $confettiColors = ['#ffd700','#7b2ff7','#ff69b4','#00cfff','#ff4500','#adff2f','#fff4a0','#e040fb'];
    $confettiPieces = [];
    for ($i = 0; $i < 45; $i++) {
      $confettiPieces[] = [
        'left'  => round(fmod($i * 2.28, 100), 1),
        'color' => $confettiColors[$i % count($confettiColors)],
        'w'     => 6 + ($i % 5) * 2,
        'h'     => 10 + ($i % 4) * 4,
        'dur'   => round(7 + ($i % 8), 1),
        'delay' => round(-($i * 0.42), 2),
        'op'    => round(0.55 + ($i % 5) * 0.09, 2),
      ];
    }
  @endphp
  @foreach($confettiPieces as $c)
    <div class="bd-confetti" style="
      left:{{$c['left']}}%;
      width:{{$c['w']}}px;height:{{$c['h']}}px;
      background:{{$c['color']}};
      animation-duration:{{$c['dur']}}s;
      animation-delay:{{$c['delay']}}s;
      opacity:{{$c['op']}};
    "></div>
  @endforeach

  {{-- ── Floating birthday emojis on sides ── --}}
  @foreach([
    ['12%','3%','1.8s','0s','2rem'],
    ['20%','94%','2.2s','0.5s','1.6rem'],
    ['45%','2%','2.5s','1.0s','1.8rem'],
    ['48%','96%','1.9s','0.3s','1.5rem'],
    ['72%','4%','2.1s','0.7s','2rem'],
    ['75%','93%','2.4s','1.2s','1.7rem'],
    ['88%','7%','1.7s','0.4s','1.5rem'],
    ['85%','91%','2.3s','0.9s','1.8rem'],
  ] as [$t,$l,$d,$dl,$sz])
    <span class="bd-float" style="top:{{$t}};left:{{$l}};font-size:{{$sz}};animation-duration:{{$d}};animation-delay:{{$dl}};">🎂</span>
  @endforeach
  @foreach([
    ['6%','15%','2.0s','0.6s'],['8%','80%','2.3s','0.2s'],
    ['60%','8%','1.9s','1.1s'],['62%','88%','2.2s','0.8s'],
  ] as [$t,$l,$d,$dl])
    <span class="bd-float" style="top:{{$t}};left:{{$l}};font-size:1.5rem;animation-duration:{{$d}};animation-delay:{{$dl}};">🎉</span>
  @endforeach

  {{-- ── Gold sparkles ── --}}
  @foreach([
    ['10%','8%','2.0s','0s'],['15%','88%','2.5s','0.4s'],['50%','5%','1.8s','0.9s'],
    ['52%','92%','2.3s','0.2s'],['82%','10%','2.1s','1.1s'],['80%','87%','1.9s','0.7s'],
    ['35%','3%','2.4s','1.3s'],['32%','95%','2.2s','0.8s'],
  ] as [$t,$l,$d,$dl])
    <span class="bd-spark" style="top:{{$t}};left:{{$l}};font-size:1.4rem;animation-duration:{{$d}};animation-delay:{{$dl}};">★</span>
  @endforeach

  {{-- ── Flier ── --}}
  <div class="bd-flier-wrap">
    <div class="bd-shimmer-bar" style="width:100%;"></div>
    <img class="bd-flier-img"
         src="{{ URL::to('images/birthday/birthday.jpeg') }}"
         alt="Happy Birthday — Mrs Rose Adel, CEO KasaBazaar Group">
    <button class="bd-close-btn"
            onclick="document.getElementById('bd-overlay').style.display='none';sessionStorage.setItem('bd_closed_2026','1');">
      🎂 &nbsp;Continue to Website
    </button>
  </div>

</div>

<script>
  (function(){
    if (sessionStorage.getItem('bd_closed_2026') === '1') {
      var el = document.getElementById('bd-overlay');
      if (el) el.style.display = 'none';
    }
  })();
</script>

@endif
