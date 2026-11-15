"use strict";

// sticky header
$(window).on('scroll', function () {
  if ($(this).scrollTop() > 95) {
    $('.navbar').addClass('sticky');
  } else {
    $('.navbar').removeClass('sticky');
  }
});

// smooth scroll
$('.smooth-scroll, .scroll_down a').on('click', function (event) {
  var $anchor = $(this);
  $('html, body').stop().animate({
    scrollTop: $($anchor.attr('href')).offset().top - 75
  }, 1500, 'easeInOutExpo');
  event.preventDefault();
});

// initialize swiper
var eventList = new Swiper('.event-list', {
  direction: 'vertical',
  spaceBetween: 10,
  slidesPerView: 5,
  autoplay: {
    delay: 4000,
    disableOnInteraction: false,
  },
  navigation: {
    nextEl: '.swiper-button-next',
    prevEl: '.swiper-button-prev',
  },
  watchSlidesVisibility: true,
  watchSlidesProgress: true,
});
var eventView = new Swiper('.event-view', {
  direction: 'horizontal',
  spaceBetween: 10,
  autoplay: {
    delay: 4000,
    disableOnInteraction: false,
  },
  thumbs: {
    swiper: eventList,
  },
});

var clients = new Swiper('.testimonials-container', {
  autoplay: {
    delay: 4000,
    disableOnInteraction: false,
  },
  navigation: {
    nextEl: '.swiper-button-next',
    prevEl: '.swiper-button-prev',
  },
});


var clients = new Swiper('.newslide-container', {
  autoplay: {
    delay: 4000,
    disableOnInteraction: false,
  },
  navigation: {
    nextEl: '.swiper-button-next',
    prevEl: '.swiper-button-prev',
  },
});

var clients = new Swiper('.evnt-container', {
  spaceBetween: 0,
  slidesPerView: 4,
  breakpoints: {
    // when window width is >= 320px
    320: {
      slidesPerView: 1
    },
    // when window width is >= 480px
    480: {
      slidesPerView: 2
    },
    // when window width is >= 768px
    768: {
      slidesPerView: 4
    }
  },
  autoplay: {
    delay: 4000,
    disableOnInteraction: false,
  },
  navigation: {
    nextEl: '.swiper-button-next',
    prevEl: '.swiper-button-prev',
  },
});

