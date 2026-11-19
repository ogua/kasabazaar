/*
 * Hover layer for the inline-SVG charts (resources/views/site/partials/chart.blade.php):
 * a tooltip per point, a crosshair on area charts and a highlight on bar charts.
 * The charts are complete without it.
 */
(function () {
   'use strict';

   function init(figure) {
      var svg = figure.querySelector('svg');
      var tip = figure.querySelector('.ksr-chart__tip');
      var plot = figure.querySelector('.ksr-chart__plot');
      var cross = figure.querySelector('.ksr-chart__cross');
      var dot = figure.querySelector('.ksr-chart__hover-dot');
      var bars = figure.querySelectorAll('.ksr-chart__bar');
      if (!svg || !tip) {
         return;
      }

      function show(hit) {
         var i = hit.getAttribute('data-i');
         var x = parseFloat(hit.getAttribute('data-x'));
         var y = parseFloat(hit.getAttribute('data-y'));
         var box = svg.viewBox.baseVal;
         var rect = svg.getBoundingClientRect();
         var plotRect = plot.getBoundingClientRect();
         var scale = rect.width / box.width;

         tip.querySelector('.ksr-chart__tip-label').textContent = hit.getAttribute('data-label');
         tip.querySelector('.ksr-chart__tip-value').textContent = hit.getAttribute('data-value');
         tip.hidden = false;

         var left = rect.left - plotRect.left + x * scale;
         var top = rect.top - plotRect.top + y * scale;
         var half = tip.offsetWidth / 2;
         left = Math.max(half, Math.min(plotRect.width - half, left));
         tip.style.left = left + 'px';
         tip.style.top = Math.max(0, top - tip.offsetHeight - 12) + 'px';

         if (cross) {
            cross.setAttribute('x1', x);
            cross.setAttribute('x2', x);
            cross.setAttribute('visibility', 'visible');
            dot.setAttribute('cx', x);
            dot.setAttribute('cy', y);
            dot.setAttribute('visibility', 'visible');
         }
         figure.classList.add('is-hovering');
         bars.forEach(function (bar) {
            bar.classList.toggle('is-active', bar.getAttribute('data-i') === i);
         });
      }

      function hide() {
         tip.hidden = true;
         if (cross) {
            cross.setAttribute('visibility', 'hidden');
            dot.setAttribute('visibility', 'hidden');
         }
         figure.classList.remove('is-hovering');
      }

      figure.querySelectorAll('.ksr-chart__hit').forEach(function (hit) {
         hit.addEventListener('mouseenter', function () { show(hit); });
         hit.addEventListener('touchstart', function () { show(hit); }, { passive: true });
      });
      svg.addEventListener('mouseleave', hide);
   }

   document.querySelectorAll('.ksr-chart').forEach(init);
})();
