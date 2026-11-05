/**
 * Digital Crazy Hub - Main Landing Page Script
 * Statistics animation, FAQ Accordion, Sticky Bottom Bar, Back to Top, Anchor Scroll
 */

(function () {
  "use strict";

  // 1. Stats Counter Animation using IntersectionObserver
  function initCounters() {
    const counterElements = document.querySelectorAll(".counter-val");
    if (!counterElements.length) return;

    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const target = parseInt(el.getAttribute("data-target"), 10) || 0;
          const suffix = el.getAttribute("data-suffix") || "";
          const prefix = el.getAttribute("data-prefix") || "";
          const duration = 1600; // ms
          const start = 0;
          const startTime = performance.now();

          function step(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            // Ease out quad
            const easeProgress = 1 - (1 - progress) * (1 - progress);
            const current = Math.floor(start + (target - start) * easeProgress);

            el.textContent = `${prefix}${current.toLocaleString()}${suffix}`;

            if (progress < 1) {
              requestAnimationFrame(step);
            } else {
              el.textContent = `${prefix}${target.toLocaleString()}${suffix}`;
            }
          }

          requestAnimationFrame(step);
          obs.unobserve(el);
        }
      });
    }, { threshold: 0.3 });

    counterElements.forEach(el => observer.observe(el));
  }

  // 2. FAQ Accordion
  function initFAQ() {
    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach(item => {
      const questionBtn = item.querySelector(".faq-question");
      const answerEl = item.querySelector(".faq-answer");

      if (!questionBtn || !answerEl) return;

      questionBtn.addEventListener("click", () => {
        const isOpen = item.classList.contains("active");

        // Close all other items for clean accordion behavior
        faqItems.forEach(other => {
          if (other !== item && other.classList.contains("active")) {
            other.classList.remove("active");
            const otherBtn = other.querySelector(".faq-question");
            const otherAns = other.querySelector(".faq-answer");
            if (otherBtn) otherBtn.setAttribute("aria-expanded", "false");
            if (otherAns) otherAns.style.maxHeight = null;
          }
        });

        if (isOpen) {
          item.classList.remove("active");
          questionBtn.setAttribute("aria-expanded", "false");
          answerEl.style.maxHeight = null;
        } else {
          item.classList.add("active");
          questionBtn.setAttribute("aria-expanded", "true");
          answerEl.style.maxHeight = answerEl.scrollHeight + "px";
        }
      });
    });
  }

  // 3. Sticky Bottom Mobile Bar & Back-to-Top Button
  function initScrollTriggers() {
    const stickyBar = document.getElementById("mobileStickyBar");
    const backToTop = document.getElementById("backToTopBtn");
    const heroSection = document.getElementById("heroSection");

    window.addEventListener("scroll", () => {
      const scrollY = window.scrollY || window.pageYOffset;

      // Show/hide sticky mobile bar after 350px
      if (stickyBar) {
        if (scrollY > 350) {
          stickyBar.classList.add("visible");
        } else {
          stickyBar.classList.remove("visible");
        }
      }

      // Back to top button
      if (backToTop) {
        if (scrollY > 500) {
          backToTop.classList.add("visible");
        } else {
          backToTop.classList.remove("visible");
        }
      }
    }, { passive: true });

    if (backToTop) {
      backToTop.addEventListener("click", (e) => {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
    }
  }

  // 4. Smooth Anchor Scrolling
  function initSmoothScroll() {
    document.querySelectorAll("a[href^='#']:not([href='#']):not([data-open-enrollment])").forEach(anchor => {
      anchor.addEventListener("click", function (e) {
        const targetId = this.getAttribute("href");
        if (targetId && targetId !== "#") {
          const targetEl = document.querySelector(targetId);
          if (targetEl) {
            e.preventDefault();
            targetEl.scrollIntoView({ behavior: "smooth", block: "start" });
          }
        }
      });
    });
  }

  // Run on DOM ready
  function init() {
    initCounters();
    initFAQ();
    initScrollTriggers();
    initSmoothScroll();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

