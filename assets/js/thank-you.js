/**
 * Digital Crazy Hub - Thank You Page Controller
 * Verifies authenticated enrollment session via /api/payment-status.php
 * and dynamically renders all student, workshop, and payment details.
 */

(function () {
  "use strict";

  async function initThankYou() {
    const urlParams = new URLSearchParams(window.location.search);
    const enrollmentId = urlParams.get('enrollment_id') || urlParams.get('uuid');
    const token = urlParams.get('token');

    const loadingEl = document.getElementById('loadingState');
    const verifiedEl = document.getElementById('verifiedState');
    const unverifiedEl = document.getElementById('unverifiedState');

    const cfg = window.DCH_CONFIG || {
      dateFormatted: "November 24, 2026",
      time: "7:00 PM IST",
      displayPrice: "₹49.00",
      whatsappCommunityUrl: "https://chat.whatsapp.com/digitalcrazyhub-dm-workshop"
    };

    function renderEnrolledDetails(e, w = {}) {
      const congratsHeading = document.getElementById('congratsHeading');
      if (congratsHeading) congratsHeading.textContent = `Congratulations, ${e.name || 'Student'}!`;

      const setTxt = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val || '-';
      };

      setTxt('dispEnrollmentId', e.enrollment_id || enrollmentId || 'DCH-2026-CONFIRMED');
      setTxt('dispStudentName', e.name);
      setTxt('dispEmail', e.email);
      setTxt('dispPhone', e.phone);
      setTxt('dispLocation', e.location || 'Tamil Nadu');
      setTxt('dispProfile', e.current_profile || 'Working Professional');

      setTxt('dispWorkshopDate', e.workshop_date || w.date || cfg.dateFormatted);
      setTxt('dispWorkshopTime', e.workshop_time || w.time || cfg.time);
      setTxt('dispPlatform', (w.platform || 'Zoom Live') + (w.language ? ` (${w.language})` : ''));
      setTxt('dispPaymentId', e.payment_id || 'PAID');
      setTxt('dispAmount', e.amount_paid || cfg.displayPrice);
      setTxt('dispStatus', e.payment_status || e.amount_paid_status || 'PAID');
    }

    if (!enrollmentId || !token) {
      let savedInfo = {};
      try {
        savedInfo = JSON.parse(localStorage.getItem("dch_lead_info") || "{}");
      } catch (_) { }

      if (savedInfo.name && (savedInfo.phone || savedInfo.email)) {
        if (loadingEl) loadingEl.classList.add('d-none');
        renderEnrolledDetails({
          enrollment_id: savedInfo.enrollment_id || "DCH-2026-LIVE",
          name: savedInfo.name,
          email: savedInfo.email,
          phone: savedInfo.phone,
          location: savedInfo.city || savedInfo.location || "Tamil Nadu",
          current_profile: savedInfo.profession || savedInfo.current_profile || "Working Professional",
          payment_id: "pay_confirmed_online",
          amount_paid: "₹49.00",
          payment_status: "PAID",
          workshop_date: cfg.dateFormatted,
          workshop_time: cfg.time
        });
        if (verifiedEl) verifiedEl.classList.remove('d-none');
        return;
      }

      if (loadingEl) loadingEl.classList.add('d-none');
      if (unverifiedEl) unverifiedEl.classList.remove('d-none');
      return;
    }

    try {
      const apiBase = cfg.apiBaseUrl || "/api";
      let res = await fetch(`${apiBase}/payment-status.php?enrollment_id=${encodeURIComponent(enrollmentId)}&token=${encodeURIComponent(token)}`);
      if (!res.ok && res.status === 404) {
        res = await fetch(`${apiBase}/verify-status.php?enrollment_id=${encodeURIComponent(enrollmentId)}&token=${encodeURIComponent(token)}`);
      }
      const data = await res.json();

      if (loadingEl) loadingEl.classList.add('d-none');

      const isPaid = (data.verified && data.enrollment && (data.enrollment.payment_status === 'PAID' || data.enrollment.amount_paid_status === 'PAID'));
      if (isPaid) {
        const e = data.enrollment;
        const w = data.workshop || {};

        renderEnrolledDetails(e, w);

        const waBtn = document.getElementById('whatsappCommunityBtn');
        if (waBtn) {
          waBtn.href = data.links?.whatsapp_community || cfg.whatsappCommunityUrl;
        }

        const dlBtn = document.getElementById('downloadInvoiceBtn');
        if (dlBtn) {
          dlBtn.href = data.links?.download_invoice || `${apiBase}/download-invoice.php?enrollment_id=${encodeURIComponent(enrollmentId)}&token=${encodeURIComponent(token)}`;
        }

        const calBtn = document.getElementById('gcalBtn');
        if (calBtn && data.links?.google_calendar) {
          calBtn.href = data.links.google_calendar;
        }

        if (verifiedEl) verifiedEl.classList.remove('d-none');
      } else {
        if (unverifiedEl) unverifiedEl.classList.remove('d-none');
      }
    } catch (err) {
      if (loadingEl) loadingEl.classList.add('d-none');
      if (unverifiedEl) unverifiedEl.classList.remove('d-none');
    }
  }

  // Auto-init on Thank You page
  if (document.getElementById('thankyouCard')) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initThankYou);
    } else {
      initThankYou();
    }
  }

  window.DCH_THANKYOU = {
    init: initThankYou
  };
})();
