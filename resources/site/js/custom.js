/**
 * Transhub - Transport HTML Template
 * Author: themevillage
 **/

'use strict';

(function ($) {

  //Lenis Scroll 
  const lenis = new Lenis({
    autoRaf: true,
  });

  //Preloader
  $(window).on('load', function () {
    var $preloader = $('#preloader');
    if ($preloader.length) $preloader.hide();
    $('body').css('overflow', 'visible');
  });

  /**
 * Tooltip
 */
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  })

  /**============================================
   * Priciing Tab Toggle
   */
  function tabtable_active() {
    const $elements = {
      monthly: $("#tv-nav-monthly"),
      yearly: $("#tv-nav-yearly"),
      switcher: $("#tv-switcher-input"),
      tabMonthly: $("#tv-tab-monthly"),
      tabYearly: $("#tv-tab-yearly")
    };

    const setActive = isMonthly => {
      $elements.switcher.prop("checked", isMonthly);
      $elements.monthly.toggleClass("is-active", isMonthly);
      $elements.yearly.toggleClass("is-active", !isMonthly);
      $elements.tabMonthly.toggleClass("tv-tab-hide", !isMonthly);
      $elements.tabYearly.toggleClass("tv-tab-hide", isMonthly);
    };

    [$elements.monthly, $elements.yearly].forEach($el =>
      $el.on("click", () => setActive($el.is($elements.monthly)))
    );

    $elements.switcher.on("click", () =>
      setActive(!$elements.monthly.hasClass("is-active"))
    );
  }
  if ($("#tv-nav-monthly").length) tabtable_active();

  // Select navbar and offcanvas elements
  var $navOffCanvasBtn = $('.offcanvas-nav-btn');
  var $navOffCanvas = $('.navbar:not(.navbar-clone) .offcanvas-nav');
  var bsOffCanvas;

  // Function to toggle the offcanvas
  function toggleOffCanvas() {
    if (bsOffCanvas) {
      bsOffCanvas._isShown ? bsOffCanvas.hide() : bsOffCanvas.show();
    }
  }

  // Initialize offcanvas if it exists
  if ($navOffCanvas.length) {
    bsOffCanvas = new bootstrap.Offcanvas($navOffCanvas[0], { scroll: true });
    $navOffCanvasBtn.on('click', toggleOffCanvas);
  }

  // Function to handle dropdown toggle
  function handleDropdownToggle(event) {
    const dropdownToggle = event.currentTarget;
    const submenu = dropdownToggle.nextElementSibling;

    // Close all open submenus
    if (!submenu.classList.contains("show")) {
      dropdownToggle
        .closest(".dropdown-menu")
        .querySelectorAll(".show")
        .forEach((el) => el.classList.remove("show"));
    }

    // Toggle the current submenu
    submenu.classList.toggle("show");

    // Handle closing of submenus on dropdown hide event
    const parentDropdown = dropdownToggle.closest("li.nav-item.dropdown.show");
    if (parentDropdown) {
      parentDropdown.addEventListener("hidden.bs.dropdown", () => {
        document.querySelectorAll(".dropdown-submenu .show").forEach((el) => el.classList.remove("show"));
      });
    }

    // Prevent the default action and stop event propagation
    event.preventDefault();
    event.stopPropagation();
  }

  // Attach event listeners to dropdown toggles
  document.querySelectorAll(".dropdown-menu a.dropdown-toggle").forEach((toggle) => {
    toggle.addEventListener("click", handleDropdownToggle);
  });

  $('.btn')
    .on('mouseenter', function (e) {
      var parentOffset = $(this).offset(),
        relX = e.pageX - parentOffset.left,
        relY = e.pageY - parentOffset.top;
      $(this).find('span').css({ top: relY, left: relX })
    })
    .on('mouseout', function (e) {
      var parentOffset = $(this).offset(),
        relX = e.pageX - parentOffset.left,
        relY = e.pageY - parentOffset.top;
      $(this).find('span').css({ top: relY, left: relX })
    });

  /**============================================
   *  Sticky Menu
   */
  $(window).on('scroll', function () {
    var $stickyHeight = $('.sticky-height');
    var $headerNavWrapper = $('.header-nav-wrapper');
    if (!$headerNavWrapper.length) return;

    var headerWrapperHeight = $headerNavWrapper.outerHeight();
    var $topHeader = $('.header-top');
    var topHeaderHeight = $topHeader.length ? $topHeader.outerHeight() : 0;
    var targetScroll = topHeaderHeight + 200;

    if ($(window).scrollTop() > targetScroll) {
      $headerNavWrapper.addClass('scroll-on');
      if ($stickyHeight.length) $stickyHeight.css('height', headerWrapperHeight + 'px');
    } else {
      $headerNavWrapper.removeClass('scroll-on');
      if ($stickyHeight.length) $stickyHeight.css('height', '0');
    }
  });



  /**==========================================
   * Offcanvsa Menu
   */
  $(".canvas-menu .navbar .dropdown-toggle").append('<i class="fas fa-angle-down"></i>');
  $(".canvas-menu .submenu").before('<i class="fas fa-angle-down switcher"></i>');
  $(".vertical-menu li i.switcher").on('click', function () {
    var $submenu = $(this).next(".submenu");
    $submenu.slideToggle(300);
    $submenu.parent().toggleClass("openmenu");
  });

  $("button.burger-menu").on('click', function () {
    $(".canvas-menu").toggleClass("open");
    $(".main-overlay").toggleClass("active");
  });

  $(".canvas-menu .canvas-close, .main-overlay").on('click', function () {
    $(".canvas-menu").removeClass("open");
    $(".main-overlay").removeClass("active");
  });

  // Initialize PureCounter
  new PureCounter({
    decimals: 0,
  });



  $(document).ready(function () {
    jarallax(document.querySelectorAll('.jarallax'), {
      speed: 0.5
    });
  });
  /**============================================
 * FancyBox Init
 */
  if (typeof Fancybox !== 'undefined' && Fancybox.bind) {
    Fancybox.bind('[data-fancybox]', {
      Thumbs: { autoStart: false },
      Toolbar: { display: ['close'] },
      animated: true,
    });
  }
  //Social Share

  const plusButtons = document.querySelectorAll('.social-share .plus');

  plusButtons.forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const parent = btn.closest('.team-entry');

      // Remove active class from all other .team-entry elements
      document.querySelectorAll('.team-entry.active').forEach(active => {
        if (active !== parent) active.classList.remove('active');
      });

      // Toggle active class on current one
      parent.classList.toggle('active');
    });
  });

  $(".team-card .link-icon").on("click", function (e) {
    e.preventDefault();
    var $this = $(this);
    var $teamCard = $this.closest(".team-card");

    // Toggle active class on social-share
    $teamCard.find(".social-share").toggleClass("active");

    // Also toggle active class on the clicked link-icon
    $this.toggleClass("icon-active");
  });

  //accordion active
  $('.faq-accordion .accordion-collapse').on('show.bs.collapse', function () {
    $(this).closest('.accordion-item').addClass('active');
  });

  $('.faq-accordion .accordion-collapse').on('hide.bs.collapse', function () {
    $(this).closest('.accordion-item').removeClass('active');
  });


  /**============================================
 * Hero Slider
 */
  /*   var heroSlider1 = new Swiper('.hero-slider', {
      slidesPerView: 1,
      loop: true,
      autoplay: true,
      speed: 1000,
      spaceBetween: 0,
      effect: 'fade',
      a11y: false,
      autoplay: {
        delay: 4000,
        disableOnInteraction: false
      },
      navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      },
    }); */
  var heroSlider1 = new Swiper('.hero-slider', {
    slidesPerView: 1,
    autoplay: {
      delay: 4000,
      disableOnInteraction: false
    },
    loop: true,
    spaceBetween: 0,
    effect: "creative",
    speed: 1500,
    creativeEffect: {
      prev: {
        scale: 1,
        opacity: 0,
        translate: [0, 0, 0],
      },
      next: {
        scale: 1.2,
        opacity: 0,
        translate: [0, 0, 0],
      },
    },
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
  });

  var heroSlider2 = new Swiper('.hero-slider2', {
   autoplay: {
      delay: 4000,
      disableOnInteraction: false
    }, 
    loop: true,
    spaceBetween: 0,
    effect: "creative",
    speed: 1500,
    creativeEffect: {
      prev: {

        scale: 1.1,
        opacity: 0,
        translate: [0, 0, 0],
      },
      next: {
        // Zoom in (grow + fade in)
        scale: 1.3,
        opacity: 0,
        translate: [0, 0, 0],
      },
    },
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },

    on: {
      slideChangeTransitionStart: function () {
        // reset all slide content
        gsap.set('.slide-content > *', {
          opacity: 0,
          y: 30
        });
        gsap.set('.hero-slider2 .abs-img', {
          opacity: 0,
          y: 30
        });
      },
      slideChangeTransitionEnd: function () {
        // animate active slide content
        const activeSlide = document.querySelector('.swiper-slide-active');

        if (activeSlide) {
          gsap.to(activeSlide.querySelectorAll('.slide-content > *'), {
            opacity: 1,
            y: 0,
            duration: 0.8,
            stagger: 0.15,
            ease: "power3.out"
          });
          gsap.to(activeSlide.querySelectorAll('.hero-slider2 .abs-img'), {
            opacity: 1,
            y: 0,
            duration: 0.8,
            stagger: 0.15,
            ease: "power3.out"
          });
        }
      }
    }
  });

  var serviceSlider2 = new Swiper('.service-slider2', {
    slidesPerView: 'auto',
    loop: true,
    speed: 600,
    autoplay: true,
    spaceBetween: 30,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },

  });

  if ($(".marque-active").length) {
    $(".marque-active").marquee({
      gap: 48,
      speed: 80,
      delayBeforeStart: 0,
      direction: "left",
      duplicated: true,
      pauseOnHover: true,
      startVisible: true,
    });
  }
  /**============================================
   * Services Carousel
   */
  var servicesCarousel = new Swiper('.services-carousel', {
    loop: true,
    spaceBetween: 30,
      speed: 600,
    autoplay: true,
    pagination: {
      el: '.ct-pagination .swiper-pagination',
      clickable: true,
    },

    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      992: { slidesPerView: 3 },
      1400: { slidesPerView: 'auto' },
    },
  });

  var videoSlider = new Swiper('.video-slider', {
    loop: true,
    speed: 600,
    effect: 'fade',
    spaceBetween: 0,
    autoplay: {
      delay: 6000,
      disableOnInteraction: false
    },


  });
  /**============================================
   * Brands Carousel
   */
  var brandsCarousel = new Swiper('.brands-carousel', {
    loop: true,
    autoplay: true,
    speed: 600,
    spaceBetween: 30,
    breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 4 },
      992: { slidesPerView: 6 },
    },
  });
  /**============================================
   * Brands Carousel
   */
  var workCarousel = new Swiper('.work-carousel', {
    loop: true,
    autoplay: true,
    speed: 1000,
    slidesPerView: 'auto',
    centerSlides: true,
      autoplay: {
        delay: 2500,
        disableOnInteraction: false,
      },
    spaceBetween: 30,

  });

  /**============================================
   * Brands Carousel
   */
var brandsCarousel2 = new Swiper('.brands-carousel2', {
  loop: true,
  spaceBetween: 0,
  slidesPerView: 5,
  autoplay: {
    delay: 2500, 
    disableOnInteraction: false,
  },
  freeMode: true,
  freeModeMomentum: false,
  grabCursor: true,
  breakpoints: {
    0: { slidesPerView: 2 },
    768: { slidesPerView: 4 },
    992: { slidesPerView: 5 },
  },
});


  /**============================================
   * Brands Carousel
   */
  var portfolioCarousel = new Swiper('.portfolio-carousel', {
    loop: true,
    autoplay: true,
    speed: 800,
    spaceBetween: 30,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      992: { slidesPerView: 3 },
      1400: { slidesPerView: 4 },
    },
  });
  /**============================================
   * Brands Carousel
   */
  var reviewSlider = new Swiper('.review-slider', {
    loop: true,
    autoplay: true,
    speed: 800,
    slidesPerView: 1,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    pagination: {
      el: ".swiper-pagination",
      clickable: true
    },
  });


  /**============================================
   * Brands Carousel
   */
  var review3 = new Swiper('.review3-carousel', {
    loop: true,
    autoplay: true,
    speed: 800,
    spaceBetween: 30,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      992: { slidesPerView: 3 },
    },
  });


  /** ===========================================
   * Product Thumb Gallery
   */
  var productThumb = new Swiper(".product-thumb", {
    spaceBetween: 10,
    slidesPerView: 4,
    freeMode: true,
    watchSlidesProgress: true,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    breakpoints: {
      // when window width is >= 640px
      320: {
        direction: "horizontal",
        slidesPerView: 3,
      },
      576: {
        direction: "vertical",
        slidesPerView: 4,
      },
    },
  })
  /** ===================================================
   * Product Cover Gallery
   */
  var ProductGallery = new Swiper(".coverItem", {
    spaceBetween: 10,
    thumbs: {
      swiper: productThumb,
    },
  })
  /**============================================
   * Product Cart Counter
   */
  $('.quantity').on('click', '.plus, .minus', function () {
    const $button = $(this);
    const $qtyInput = $button.closest('.quantity').find('.qty');

    let currentVal = parseFloat($qtyInput.val());
    let max = parseFloat($qtyInput.attr('max'));
    let min = parseFloat($qtyInput.attr('min'));
    let step = $qtyInput.attr('step');

    // Format values
    if (!currentVal || isNaN(currentVal)) currentVal = 1; // 👈 start from 1
    if (!max || isNaN(max)) max = '';
    if (!min || isNaN(min)) min = 1; // 👈 set minimum to 1
    if (step === 'any' || step === '' || step === undefined || isNaN(parseFloat(step))) step = 1;
    else step = parseFloat(step);

    const decimals = (step.toString().split('.')[1] || '').length;

    if ($button.hasClass('plus')) {
      if (max && currentVal >= max) {
        $qtyInput.val(max);
      } else {
        $qtyInput.val((currentVal + step).toFixed(decimals));
      }
    } else {
      if (currentVal <= min) {
        $qtyInput.val(min);
      } else {
        $qtyInput.val((currentVal - step).toFixed(decimals));
      }
    }
    $qtyInput.trigger('change');
  });


  //nice select
  $('.tv-select').niceSelect();

  /**===============================
 * Scroll Top (jQuery Version)
 */


  function scrollTop() {
    const $scrollTopBtn = $('.scroll-top');
    const $progressPath = $('.scroll-top path');

    if ($scrollTopBtn.length && $progressPath.length) {
      const progressPath = $progressPath[0];
      const pathLength = progressPath.getTotalLength();

      // Set up the path for scroll progress indicator
      $progressPath.css({
        'transition': 'none',
        'stroke-dasharray': `${pathLength} ${pathLength}`,
        'stroke-dashoffset': pathLength
      });

      progressPath.getBoundingClientRect(); // Trigger reflow
      $progressPath.css('transition', 'stroke-dashoffset 10ms linear');

      const updateProgress = function () {
        const scroll = $(window).scrollTop();
        const height = $(document).height() - $(window).height();
        const progress = pathLength - (scroll * pathLength / height);
        $progressPath.css('stroke-dashoffset', progress);
      };

      updateProgress();
      $(window).on('scroll', updateProgress);

      const offset = 50;

      $(window).on('scroll', function () {
        if ($(window).scrollTop() > offset) {
          $scrollTopBtn.addClass('show');
        } else {
          $scrollTopBtn.removeClass('show');
        }
      });

      $scrollTopBtn.on('click', function (event) {
        event.preventDefault();

        // Use jQuery's animate for smooth scrolling (simpler approach)
        $('html, body').animate({
          scrollTop: 0
        }, 600, 'swing');
      });
    }
  }
  $(document).ready(function () {
    scrollTop();
  });


  $(window).on('load', function () {

    var $grid = $('#gallery-container');

    // Initialize Isotope masonry
    $grid.isotope({
      itemSelector: '.grid-item',
      percentPosition: true,
      masonry: {
        columnWidth: '.grid-sizer',
      }
    });

    // Filter items on menu click
    $('.portfolio-menu .nav-link').on('click', function (e) {
      e.preventDefault();

      var filterValue = $(this).attr('data-filter');

      $grid.isotope({ filter: filterValue });

      // Active class toggle
      $('.portfolio-menu .nav-link').removeClass('active');
      $(this).addClass('active');
    });
  });


  /**============================================
   * GSAP for Title Section
   */
  let animatedTextElements = document.querySelectorAll('.sec-title');

  if (animatedTextElements.length) {
    let staggerAmount = 0.03,
      translateXValue = 20,
      delayValue = 0.1,
      easeType = "power2.out";

    animatedTextElements.forEach((element) => {
      let animationSplitText = new SplitText(element, { type: "chars, words" });
      gsap.from(animationSplitText.chars, {
        duration: 1,
        delay: delayValue,
        x: translateXValue,
        autoAlpha: 0,
        stagger: staggerAmount,
        ease: easeType,
        scrollTrigger: { trigger: element, start: "top 85%" },
      });
    });
  }


  /**============================================
   * GSAP fadeInUp
   */
  if ($(".fadeInUp").length > 0) {
    gsap.utils.toArray(".fadeInUp").forEach((item) => {
      let tv_fade_offset = item.getAttribute("data-fade-offset") || 40,
        tv_duration_value = item.getAttribute("data-duration") || 0.75,
        tv_fade_direction = item.getAttribute("data-fade-from") || "bottom",
        tv_onscroll_value = item.getAttribute("data-on-scroll") || 1,
        tv_delay_value = item.getAttribute("data-delay") || 0.15,
        tv_ease_value = item.getAttribute("data-ease") || "power2.out",
        tv_anim_setting = {
          opacity: 0,
          ease: tv_ease_value,
          duration: tv_duration_value,
          delay: tv_delay_value,
          x: (tv_fade_direction == "left" ? -tv_fade_offset : (tv_fade_direction == "right" ? tv_fade_offset : 0)),
          y: (tv_fade_direction == "top" ? -tv_fade_offset : (tv_fade_direction == "bottom" ? tv_fade_offset : 0)),
        };
      if (tv_onscroll_value == 1) {
        tv_anim_setting.scrollTrigger = {
          trigger: item,
          start: 'top 85%',
        };
      }
      gsap.from(item, tv_anim_setting);
    });
  }

  $('#ship_time').timepicker({
    timeFormat: 'h:mm p',
    interval: 60,
  });
  // DatePicker
  $('#ship_date').datepicker({
    format: 'mm-dd-yyyy'
  });

  // Progress Circle Animation
  const TOTAL_CIRCUMFERENCE = 188.5;
  const ANIMATION_DURATION = 1500;
  $('.progress-circle-item').each(function () {
    const $item = $(this);
    const $number = $item.find('.number');
    const $circle = $item.find('.progress-stroke');
    const targetPercentage = parseFloat($number.attr('data-target'));
    $({ Counter: 0 }).animate({ Counter: targetPercentage }, {
      duration: ANIMATION_DURATION,
      easing: 'swing',
      step: function () {
        $number.text(Math.ceil(this.Counter) + "%");
        let currentOffset = TOTAL_CIRCUMFERENCE - (TOTAL_CIRCUMFERENCE * (this.Counter / 100));
        $circle.css('stroke-dashoffset', currentOffset);
      }
    });
  });


})(jQuery);

