/**
 * Digital Crazy Hub - Navigation, Back to Top & Mobile Sticky Bar
 */

(function () {
  "use strict";

  function initNavigation() {
    const backToTopBtn = document.getElementById("backToTopBtn");
    const mobileStickyBar = document.getElementById("mobileStickyBar");
    const heroSection = document.getElementById("heroSection") || document.querySelector(".hero-section");

    function handleScroll() {
      const scrollPos = window.pageYOffset || document.documentElement.scrollTop;

      // Back to Top button
      if (backToTopBtn) {
        if (scrollPos > 400) {
          backToTopBtn.classList.add("visible");
        } else {
          backToTopBtn.classList.remove("visible");
        }
      }

      // Mobile Sticky Bottom Bar
      if (mobileStickyBar && heroSection) {
        const heroBottom = heroSection.offsetTop + heroSection.offsetHeight - 100;
        if (scrollPos > heroBottom) {
          mobileStickyBar.classList.add("active");
        } else {
          mobileStickyBar.classList.remove("active");
        }
      }
    }

    window.addEventListener("scroll", handleScroll, { passive: true });
    handleScroll();

    if (backToTopBtn) {
      backToTopBtn.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
    }

    // Smooth scroll for internal hashes
    document.querySelectorAll("a[href^='#']:not([href='#']):not([href='#enroll'])").forEach(link => {
      link.addEventListener("click", (e) => {
        const targetId = link.getAttribute("href");
        const targetEl = document.querySelector(targetId);
        if (targetEl) {
          e.preventDefault();
          targetEl.scrollIntoView({ behavior: "smooth" });
        }
      });
    });
  }

  window.DCH_NAVIGATION = {
    init: initNavigation
  };
})();

