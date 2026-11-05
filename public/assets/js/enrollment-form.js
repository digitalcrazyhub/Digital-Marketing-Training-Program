/**
 * Digital Crazy Hub - Universal Enrollment Modal & Form Submission Controller
 */

(function () {
  "use strict";

  let modal = null;
  let form = null;
  let submitBtn = null;
  let errorEl = null;

  function init() {
    modal = document.getElementById("enrollmentModal");
    form = document.getElementById("enrollmentForm");
    submitBtn = document.getElementById("enrollSubmitBtn");
    errorEl = document.getElementById("enrollFormError");

    // Bind all buttons that open enrollment
    document.querySelectorAll(".btn-enroll, [data-open-enrollment], a[href='#enroll']").forEach(btn => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        openModal();
      });
    });

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
      submitBtn.dataset.origText = submitBtn.innerHTML;
      submitBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:inline-block; width:1rem; height:1rem; border:2px solid currentColor; border-right-color:transparent; border-radius:50%; animation:spin 0.75s linear infinite;"></span>
        <span>Securing Enrollment...</span>
      `;
    } else {
      submitBtn.disabled = false;
      if (submitBtn.dataset.origText) {
        submitBtn.innerHTML = submitBtn.dataset.origText;
      }
    }
  }

  async function handleEnrollSubmit(e) {
    e.preventDefault();
    clearError();

    const formData = new FormData(form);
    const rawData = {
      name: formData.get("name"),
      phone: formData.get("phone"),
      email: formData.get("email"),
      location: formData.get("location") || formData.get("city"),
      current_profile: formData.get("current_profile") || formData.get("profession")
    };

    const validation = window.DCH_VALIDATOR
      ? window.DCH_VALIDATOR.validateEnrollment(rawData)
      : { isValid: true, sanitized: rawData, errors: [] };

    if (!validation.isValid) {
      showError(validation.errors[0]);
      return;
    }

    setSubmitting(true);

    let recaptchaToken = "";
    if (window.DCH_RECAPTCHA) {
      recaptchaToken = await window.DCH_RECAPTCHA.getToken("enrollment_submit");
    }

    const payload = Object.assign({}, validation.sanitized, {
      recaptcha_token: recaptchaToken
    });

    try {
      const apiEndpoint = (window.DCH_CONFIG?.apiBaseUrl || "/api") + "/create-enrollment.php";
      const res = await fetch(apiEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.message || data.error || "Unable to save enrollment. Please try again.");
      }

      // Persist details locally for seamless checkout transfer
      const storedLead = Object.assign({}, validation.sanitized, {
        enrollment_id: data.enrollment_id,
        order_id: data.order_id || "",
        verification_token: data.verification_token || ""
      });

      try {
        localStorage.setItem("dch_lead_info", JSON.stringify(storedLead));
        localStorage.setItem("dch_active_order", JSON.stringify(data));
      } catch (_) { }

      // Redirect to payment checkout page
      const queryParams = new URLSearchParams({
        enrollment_id: data.enrollment_id,
        order_id: data.order_id || "",
        name: validation.sanitized.name,
        email: validation.sanitized.email,
        phone: validation.sanitized.phone,
        location: validation.sanitized.location,
        current_profile: validation.sanitized.current_profile
      });

      window.location.href = `payment-page.html?${queryParams.toString()}`;

    } catch (err) {
      setSubmitting(false);
      showError(err.message || "An unexpected error occurred. Please try again.");
    }
  }

  window.DCH_ENROLLMENT = {
    open: openModal,
    close: closeModal,
    init: init
  };
})();

