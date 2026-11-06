/**
 * Digital Crazy Hub - Real-Time Workshop Countdown Timer
 */

(function () {
  "use strict";

  function initCountdown() {
    const targetDateStr = (window.DCH_CONFIG && window.DCH_CONFIG.targetIsoDate)
      ? window.DCH_CONFIG.targetIsoDate
      : "2026-11-24T19:00:00+05:30";

    const targetTime = new Date(targetDateStr).getTime();

    const daysEls   = document.querySelectorAll(".timer-days");
    const hoursEls  = document.querySelectorAll(".timer-hours");
    const minsEls   = document.querySelectorAll(".timer-mins");
    const secsEls   = document.querySelectorAll(".timer-secs");
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
          el.textContent = "Workshop In Session - Registrations Closed";
          el.classList.add("text-danger");
        });
        return;
      }

      const days  = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const mins  = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const secs  = Math.floor((distance % (1000 * 60)) / 1000);

      daysEls.forEach(el => el.textContent = days < 10 ? "0" + days : days);
      hoursEls.forEach(el => el.textContent = hours < 10 ? "0" + hours : hours);
      minsEls.forEach(el => el.textContent = mins < 10 ? "0" + mins : mins);
      secsEls.forEach(el => el.textContent = secs < 10 ? "0" + secs : secs);
    }

    update();
    setInterval(update, 1000);
  }

  window.DCH_COUNTDOWN = {
    init: initCountdown
  };
})();

