/**
 * Digital Crazy Hub - Universal Enrollment Modal & Form Controller
 * Validates candidate inputs, triggers server-side enrollment (MySQL + Google Sheets),
 * and seamlessly redirects to payment checkout.
 */

(function () {
  "use strict";

  let modal = null;
  let form = null;
  let submitBtn = null;
  let errorEl = null;

  function initEnrollment() {
    modal = document.getElementById("enrollmentModal");
    form = document.getElementById("enrollmentForm");
    submitBtn = document.getElementById("enrollSubmitBtn");
    errorEl = document.getElementById("enrollFormError");

    // Bind all CTA buttons that open the modal
    document.querySelectorAll(".btn-enroll, [data-open-enrollment], a[href='#enroll']").forEach(btn => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        openModal();
      });
    });

    // Close buttons
    if (modal) {
      modal.querySelectorAll("[data-close-enrollment], .modal-close").forEach(btn => {
        btn.addEventListener("click", closeModal);
      });

      modal.addEventListener("click", (e) => {
        if (e.target === modal || e.target.classList.contains("modal-backdrop")) {
          closeModal();
        }
      });
    }

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && modal && modal.classList.contains("active")) {
        closeModal();
      }
    });

    if (form) {
      form.addEventListener("submit", handleEnrollSubmit);
    }
  }

  function openModal() {
    if (!modal) return;
    clearError();
    modal.classList.add("active");
    modal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";

    // Focus first input
    const firstInput = modal.querySelector("input[name='name']");
    if (firstInput) {
      setTimeout(() => firstInput.focus(), 150);
    }
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove("active");
    modal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    clearError();
  }

  function showError(msg) {
    if (errorEl) {
      errorEl.textContent = msg;
      errorEl.classList.remove("d-none");
      errorEl.scrollIntoView({ behavior: "smooth", block: "nearest" });
    } else {
      alert(msg);
    }
  }

  function clearError() {
    if (errorEl) {
      errorEl.textContent = "";
      errorEl.classList.add("d-none");
    }
  }

  function setSubmitting(isSubmitting) {
    if (!submitBtn) return;
    if (isSubmitting) {
      submitBtn.disabled = true;
      submitBtn.dataset.originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:inline-block; width:1rem; height:1rem; border:2px solid currentColor; border-right-color:transparent; border-radius:50%; animation:spin 0.75s linear infinite;"></span>
        <span>Securing Enrollment...</span>
      `;
    } else {
      submitBtn.disabled = false;
      if (submitBtn.dataset.originalText) {
        submitBtn.innerHTML = submitBtn.dataset.originalText;
      }
    }
  }

  async function handleEnrollSubmit(e) {
    e.preventDefault();
    clearError();

    const formData = new FormData(form);
    const name = (formData.get("name") || "").toString().trim();
    let phone = (formData.get("phone") || "").toString().trim();
    const email = (formData.get("email") || "").toString().trim();
    const location = (formData.get("location") || formData.get("city") || "").toString().trim();
    const currentProfile = (formData.get("current_profile") || formData.get("profession") || "").toString().trim();

    // Client-side validation
    if (name.length < 2) {
      showError("Please enter your full name (minimum 2 characters).");
      return;
    }

    // Clean phone number
    let cleanPhone = phone.replace(/[^0-9]/g, "");
    if (cleanPhone.length === 12 && cleanPhone.startsWith("91")) {
      cleanPhone = cleanPhone.substring(2);
    } else if (cleanPhone.length === 11 && cleanPhone.startsWith("0")) {
      cleanPhone = cleanPhone.substring(1);
    }

    if (!/^[6-9]\d{9}$/.test(cleanPhone)) {
      showError("Please enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.");
      return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      showError("Please enter a valid email address.");
      return;
    }

    if (location.length < 2) {
      showError("Location is mandatory. Please enter your city or location.");
      return;
    }

    if (!currentProfile) {
      showError("Please select your current profile.");
      return;
    }

    setSubmitting(true);

    // Optional reCAPTCHA v3 token retrieval
    let recaptchaToken = "";
    if (window.grecaptcha && window.RECAPTCHA_SITE_KEY) {
      try {
        recaptchaToken = await window.grecaptcha.execute(window.RECAPTCHA_SITE_KEY, { action: "enrollment_submit" });
      } catch (recaptchaErr) {
        console.warn("reCAPTCHA execution warning:", recaptchaErr);
      }
    }

    const leadData = {
      name: name,
      phone: cleanPhone,
      email: email,
      location: location,
      city: location,
      current_profile: currentProfile,
      profession: currentProfile,
      recaptcha_token: recaptchaToken
    };

    try {
      // Step 1 - Step 4: Submit to server API (Creates MySQL NOT PAID + Google Sheets NOT PAID)
      const res = await fetch("backend/api/create-enrollment.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(leadData)
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.message || data.error || "Unable to process enrollment. Please try again.");
      }

      // Persist lead details with generated enrollment_id
      leadData.enrollment_id = data.enrollment_id;
      leadData.order_id = data.order_id || "";
      leadData.verification_token = data.verification_token || "";

      try {
        localStorage.setItem("dch_lead_info", JSON.stringify(leadData));
        localStorage.setItem("dch_active_enrollment", JSON.stringify(data));
      } catch (_) { }

      // Navigate to checkout with enrollment_id
      const queryParams = new URLSearchParams({
        enrollment_id: data.enrollment_id,
        order_id: data.order_id || "",
        name: name,
        email: email,
        phone: cleanPhone,
        location: location,
        current_profile: currentProfile
      });

      window.location.href = `payment-page.html?${queryParams.toString()}`;

    } catch (err) {
      setSubmitting(false);
      showError(err.message || "An error occurred while saving your enrollment. Please try again.");
    }
  }

  window.DCH_ENROLLMENT = {
    open: openModal,
    close: closeModal
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initEnrollment);
  } else {
    initEnrollment();
  }
})();
