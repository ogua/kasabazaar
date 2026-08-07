/**
 * Bridges Wolmart's jQuery-plugin-driven UI (Swiper carousels, zoom, magnific-popup
 * quick view) with Livewire's DOM morphing. Any element these plugins touch must be
 * wrapped in wire:ignore in the Blade view, or Livewire's diff will corrupt the
 * extra markup the plugins inject (e.g. Swiper's .swiper-wrapper). This script
 * re-initializes those plugins after Livewire finishes rendering new markup.
 */
(function () {
    function initCarousels(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;

        root.querySelectorAll('.swiper:not(.swiper-initialized)').forEach(function (el) {
            if (window.jQuery && window.jQuery.fn.wolmartSwiper) {
                window.jQuery(el).wolmartSwiper();
            }
        });
    }

    function initZoom(scope) {
        if (!window.jQuery || !window.jQuery.fn.zoom) return;
        var root = scope && scope.querySelectorAll ? scope : document;

        root.querySelectorAll('[data-zoom]').forEach(function (el) {
            window.jQuery(el).zoom();
        });
    }

    function reinitAll(scope) {
        initCarousels(scope);
        initZoom(scope);
    }

    document.addEventListener('DOMContentLoaded', function () {
        reinitAll(document);
    });

    // Livewire 3/4: fires after a component's DOM has been morphed/updated.
    document.addEventListener('livewire:navigated', function () {
        reinitAll(document);
    });

    if (window.Livewire) {
        window.Livewire.hook('morph.updated', function (el) {
            reinitAll(el);
        });
    } else {
        document.addEventListener('livewire:init', function () {
            window.Livewire.hook('morph.updated', function (el) {
                reinitAll(el);
            });
        });
    }
})();
