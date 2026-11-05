/**
 * Digital Crazy Hub - Universal Enrollment Modal & Payment Controller
 * Triggered by all CTAs across the landing page.
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
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span>Securing Your Seat...</span>
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
    const city = (formData.get("city") || "").toString().trim();
    const profession = (formData.get("profession") || "").toString().trim();

    // Validation
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

    setSubmitting(true);

    try {
      // 1. Create Order on backend
      const res = await fetch("backend/api/create-order.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: name,
          phone: cleanPhone,
          email: email,
          city: city,
          profession: profession
        })
      });

      const orderData = await res.json();

      if (!orderData.success) {
        throw new Error(orderData.message || "Failed to initiate workshop enrollment.");
      }

      // 2. Razorpay Flow
      const options = {
        key: orderData.key_id,
        amount: orderData.amount,
        currency: orderData.currency,
        name: "Digital Crazy Hub",
        description: orderData.workshop?.title || "₹49 Digital Marketing Workshop",
        image: "assets/images/DCH.png",
        order_id: orderData.order_id,
        prefill: {
          name: orderData.user?.name || name,
          email: orderData.user?.email || email,
          contact: orderData.user?.phone || cleanPhone
        },
        theme: {
          color: "#2563eb"
        },
        handler: async function (response) {
          // 3. Payment Succeeded -> Verify Signature on Server
          await verifyPaymentOnServer({
            razorpay_order_id: response.razorpay_order_id,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature: response.razorpay_signature,
            enrollment_uuid: orderData.enrollment_uuid
          });
        },
        modal: {
          ondismiss: function () {
            setSubmitting(false);
          }
        }
      };

      // Handle Test/Mock Simulation Mode or when Razorpay SDK is unavailable
      if (orderData.is_mock || typeof window.Razorpay === "undefined") {
        // In local/test mode without external Razorpay key, simulate seamless completion
        setTimeout(async () => {
          await verifyPaymentOnServer({
            razorpay_order_id: orderData.order_id,
            razorpay_payment_id: "pay_simulated_" + Math.random().toString(36).substring(2, 10),
            razorpay_signature: "sig_simulated_" + Math.random().toString(36).substring(2, 15),
            enrollment_uuid: orderData.enrollment_uuid
          });
        }, 800);
        return;
      }

      const rzp = new window.Razorpay(options);
      rzp.on("payment.failed", function (failResponse) {
        setSubmitting(false);
        const errReason = failResponse.error?.description || "Payment failed or was cancelled.";
        window.location.href = `payment-failed.html?error=${encodeURIComponent(errReason)}&order=${encodeURIComponent(orderData.order_id)}`;
      });
      rzp.open();

    } catch (err) {
      setSubmitting(false);
      showError(err.message || "Connection error. Please try again.");
    }
  }

  async function verifyPaymentOnServer(payload) {
    try {
      const res = await fetch("backend/api/verify-payment.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data.success && data.redirect_url) {
        window.location.href = data.redirect_url;
      } else {
        window.location.href = data.redirect_url || "payment-failed.html?error=verification_failed";
      }
    } catch (e) {
      window.location.href = "payment-failed.html?error=network_verification_failed";
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

