/**
 * Digital Crazy Hub - Thank You Page Controller
 * Verifies authenticated enrollment session via /api/verify-status.php
 * and dynamically renders all student, workshop, and payment details.
 */

(function () {
  "use strict";

  async function initThankYou() {
    const urlParams = new URLSearchParams(window.location.search);
    const enrollmentId = urlParams.get('enrollment_id') || urlParams.get('uuid');
    const token = urlParams.get('token');

    const loadingEl    = document.getElementById('loadingState');
    const verifiedEl   = document.getElementById('verifiedState');
    const unverifiedEl = document.getElementById('unverifiedState');

    if (!enrollmentId || !token) {
      if (loadingEl) loadingEl.classList.add('d-none');
      if (unverifiedEl) unverifiedEl.classList.remove('d-none');
      return;
    }

    try {
      const apiBase = window.DCH_CONFIG?.apiBaseUrl || "/api";
      const res = await fetch(`${apiBase}/verify-status.php?enrollment_id=${encodeURIComponent(enrollmentId)}&token=${encodeURIComponent(token)}`);
      const data = await res.json();

      if (loadingEl) loadingEl.classList.add('d-none');

      if (data.verified && data.enrollment && data.enrollment.payment_status === 'PAID') {
        const e = data.enrollment;
        const w = data.workshop || {};

        const congratsHeading = document.getElementById('congratsHeading');
        if (congratsHeading) congratsHeading.textContent = `Congratulations, ${e.name || 'Student'}!`;

        const setTxt = (id, val) => {
          const el = document.getElementById(id);
          if (el) el.textContent = val || '-';
        };

        setTxt('dispEnrollmentId', e.enrollment_id || enrollmentId);
        setTxt('dispStudentName', e.name);
        setTxt('dispEmail', e.email);
        setTxt('dispPhone', e.phone);
        setTxt('dispLocation', e.location);
        setTxt('dispProfile', e.current_profile);

        setTxt('dispWorkshopDate', e.workshop_date || w.date || '05-11-2026');
        setTxt('dispWorkshopTime', e.workshop_time || w.time || '7:00 PM IST');
        setTxt('dispPlatform', (w.platform || 'Zoom Live') + (w.language ? ` (${w.language})` : ''));
        setTxt('dispPaymentId', e.payment_id || 'PAID');
        setTxt('dispAmount', e.amount_paid || '₹49.00');
        setTxt('dispStatus', e.payment_status || 'PAID');

        if (data.links?.whatsapp_community) {
          const waBtn = document.getElementById('whatsappCommunityBtn');
          if (waBtn) waBtn.href = data.links.whatsapp_community;
        }

        if (data.links?.download_invoice) {
          const dlBtn = document.getElementById('downloadInvoiceBtn');
          if (dlBtn) dlBtn.href = data.links.download_invoice;
        }

        if (data.links?.google_calendar) {
          const calBtn = document.getElementById('gcalBtn');
          if (calBtn) calBtn.href = data.links.google_calendar;
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

