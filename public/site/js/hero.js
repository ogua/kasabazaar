/* Homepage hero slider: fade between slides, drive the journey steps and the pause control. */
(function () {
   'use strict';

   var hero = document.querySelector('.ksr-hero');
   if (!hero || typeof Swiper === 'undefined') {
      return;
   }

   var DELAY = 7000;
   var SPEED = 900;
   var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
   var steps = hero.querySelectorAll('.ksr-step');
   var toggle = hero.querySelector('.ksr-toggle');

   hero.style.setProperty('--ksr-delay', DELAY + 'ms');
   hero.style.setProperty('--ksr-speed', SPEED + 'ms');

   function markStep(index) {
      steps.forEach(function (step, i) {
         var active = i === index;
         var bar = step.querySelector('.ksr-step__bar i');

         step.classList.toggle('is-active', active);
         step.setAttribute('aria-current', active ? 'step' : 'false');

         // Restart the progress animation on the newly active step.
         bar.style.animation = 'none';
         void bar.offsetWidth;
         bar.style.animation = '';
      });
   }

   var swiper = new Swiper(hero.querySelector('.ksr-hero__slider'), {
      effect: 'fade',
      fadeEffect: { crossFade: true },
      speed: reduceMotion ? 0 : SPEED,
      rewind: true,
      autoHeight: true,
      autoplay: { delay: DELAY, disableOnInteraction: false },
      keyboard: { enabled: true, onlyInViewport: true },
      navigation: { nextEl: hero.querySelector('.ksr-next'), prevEl: hero.querySelector('.ksr-prev') },
      a11y: { prevSlideMessage: 'Previous slide', nextSlideMessage: 'Next slide' },
      on: {
         init: function (s) {
            markStep(s.activeIndex);
         },
         slideChange: function (s) {
            markStep(s.activeIndex);
         },
      },
   });

   steps.forEach(function (step, i) {
      step.addEventListener('click', function () {
         swiper.slideTo(i);
      });
   });

   function setPaused(paused) {
      hero.classList.toggle('is-paused', paused);
      toggle.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
      toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
   }

   if (reduceMotion) {
      swiper.autoplay.stop();
      setPaused(true);
   }

   toggle.addEventListener('click', function () {
      if (hero.classList.contains('is-paused')) {
         swiper.autoplay.start();
         markStep(swiper.activeIndex);
         setPaused(false);
      } else {
         swiper.autoplay.stop();
         setPaused(true);
      }
   });

   hero.classList.add('is-ready');
})();
