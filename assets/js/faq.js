/**
 * Digital Crazy Hub - FAQ Accordion Controller
 */

(function () {
  "use strict";

  function initFaq() {
    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach(item => {
      const btn = item.querySelector(".faq-question");
      if (!btn) return;

      btn.addEventListener("click", () => {
        const isExpanded = btn.getAttribute("aria-expanded") === "true";

        // Close other items
        faqItems.forEach(other => {
          if (other !== item) {
            other.classList.remove("active");
            const otherBtn = other.querySelector(".faq-question");
            if (otherBtn) otherBtn.setAttribute("aria-expanded", "false");
          }
        });

        // Toggle current item
        if (isExpanded) {
          item.classList.remove("active");
          btn.setAttribute("aria-expanded", "false");
        } else {
          item.classList.add("active");
          btn.setAttribute("aria-expanded", "true");
        }
      });
    });
  }

  window.DCH_FAQ = {
    init: initFaq
  };
})();

