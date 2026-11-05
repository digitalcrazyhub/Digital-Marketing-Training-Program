/**
 * Digital Crazy Hub - Main Application Initializer
 * Coordinates component boot sequences cleanly without monolithic coupling.
 */

(function () {
  "use strict";

  function initApp() {
    // 1. Workshop Countdown
    if (window.DCH_COUNTDOWN && typeof window.DCH_COUNTDOWN.init === "function") {
      window.DCH_COUNTDOWN.init();
    }

    // 2. Training Gallery & Lightbox
    if (window.DCH_GALLERY && typeof window.DCH_GALLERY.init === "function") {
      window.DCH_GALLERY.init();
    }

    // 3. Video Testimonials
    if (window.DCH_TESTIMONIALS && typeof window.DCH_TESTIMONIALS.init === "function") {
      window.DCH_TESTIMONIALS.init();
    }

    // 4. Accessible FAQ Accordions
    if (window.DCH_FAQ && typeof window.DCH_FAQ.init === "function") {
      window.DCH_FAQ.init();
    }

    // 5. Navigation, Back to Top & Mobile Sticky Bar
    if (window.DCH_NAVIGATION && typeof window.DCH_NAVIGATION.init === "function") {
      window.DCH_NAVIGATION.init();
    }

    // 6. Universal Enrollment Modal
    if (window.DCH_ENROLLMENT && typeof window.DCH_ENROLLMENT.init === "function") {
      window.DCH_ENROLLMENT.init();
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initApp);
  } else {
    initApp();
  }
})();

