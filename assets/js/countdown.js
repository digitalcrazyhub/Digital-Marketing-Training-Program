/**
 * Digital Crazy Hub - Real-Time Countdown Timer
 * Uses WORKSHOP_CONFIG.targetIsoDate and Asia/Kolkata timezone.
 */

(function () {
  "use strict";

  function initCountdown() {
    const targetDateStr = (typeof WORKSHOP_CONFIG !== "undefined" && WORKSHOP_CONFIG.targetIsoDate)
      ? WORKSHOP_CONFIG.targetIsoDate
      : "2026-11-05T19:00:00+05:30";

    const targetTime = new Date(targetDateStr).getTime();

    const daysEls = document.querySelectorAll(".timer-days");
    const hoursEls = document.querySelectorAll(".timer-hours");
    const minsEls = document.querySelectorAll(".timer-mins");
    const secsEls = document.querySelectorAll(".timer-secs");
    const statusEls = document.querySelectorAll(".timer-status");

    function update() {
      const now = new Date().getTime();
      const distance = targetTime - now;

      if (distance <= 0) {
        daysEls.forEach(el => el.textContent = "00");
        hoursEls.forEach(el => el.textContent = "00");
        minsEls.forEach(el => el.textContent = "00");
        secsEls.forEach(el => el.textContent = "00");
        statusEls.forEach(el => {
          el.textContent = "Workshop In Session - Joining Closed";
          el.classList.add("text-danger");
        });
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const secs = Math.floor((distance % (1000 * 60)) / 1000);

      const pad = (n) => String(n).padStart(2, "0");

      daysEls.forEach(el => el.textContent = pad(days));
      hoursEls.forEach(el => el.textContent = pad(hours));
      minsEls.forEach(el => el.textContent = pad(mins));
      secsEls.forEach(el => el.textContent = pad(secs));
    }

    update();
    setInterval(update, 1000);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCountdown);
  } else {
    initCountdown();
  }
})();

