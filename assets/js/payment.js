/**
 * Digital Crazy Hub - Payment & Razorpay Checkout Controller
 */

(function () {
  "use strict";

  function initPaymentPage() {
    const submitBtn = document.getElementById("completeOrderBtn");
    const errorBox = document.getElementById("checkoutErrorBox");

    const fullNameInput = document.getElementById("fullNameInput");
    const emailInput = document.getElementById("emailInput");
    const phoneInput = document.getElementById("phoneInput");

    const tabStep1 = document.getElementById("tabStep1");
    const tabStep2 = document.getElementById("tabStep2");

    // Banner Lightbox elements
    const openBannerLightboxBtn = document.getElementById("openBannerLightbox");
    const closeBannerLightboxBtn = document.getElementById("closeBannerLightbox");
    const bannerImgLightbox = document.getElementById("bannerImgLightbox");

    // Hook up lightbox preview if present
    if (openBannerLightboxBtn && bannerImgLightbox) {
      openBannerLightboxBtn.addEventListener("click", () => {
        bannerImgLightbox.classList.add("active");
        bannerImgLightbox.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
      });
    }

    if (closeBannerLightboxBtn && bannerImgLightbox) {
      closeBannerLightboxBtn.addEventListener("click", () => {
        bannerImgLightbox.classList.remove("active");
        bannerImgLightbox.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
      });
      bannerImgLightbox.addEventListener("click", (e) => {
        if (e.target === bannerImgLightbox) {
          bannerImgLightbox.classList.remove("active");
          bannerImgLightbox.setAttribute("aria-hidden", "true");
          document.body.style.overflow = "";
        }
      });
    }

    // Retrieve submitted candidate info from URL query params or localStorage
    const params = new URLSearchParams(window.location.search);
    let savedInfo = {};
    try {
      savedInfo = JSON.parse(localStorage.getItem("dch_lead_info") || "{}");
    } catch (_) { }

    let currentLead = {
      enrollment_id: (params.get("enrollment_id") || savedInfo.enrollment_id || "").trim(),
      order_id: (params.get("order_id") || savedInfo.order_id || "").trim(),
      name: (params.get("name") || savedInfo.name || "").trim(),
      email: (params.get("email") || savedInfo.email || "").trim(),
      phone: (params.get("phone") || savedInfo.phone || "").trim(),
      city: (params.get("location") || params.get("city") || savedInfo.location || savedInfo.city || "").trim(),
      profession: (params.get("current_profile") || params.get("profession") || savedInfo.current_profile || savedInfo.profession || "Working Professional").trim()
    };

    function cleanPhone(raw) {
      return window.DCH_VALIDATOR ? window.DCH_VALIDATOR.cleanPhone(raw) : (raw || "").replace(/[^0-9]/g, "");
    }

    function syncInputs() {
      if (fullNameInput && currentLead.name) fullNameInput.value = currentLead.name;
      if (emailInput && currentLead.email) emailInput.value = currentLead.email;
      if (phoneInput && currentLead.phone) phoneInput.value = cleanPhone(currentLead.phone);
    }

    syncInputs();

    // Auto update currentLead on typing
    if (fullNameInput) {
      fullNameInput.addEventListener("input", () => {
        currentLead.name = fullNameInput.value.trim();
      });
    }
    if (emailInput) {
      emailInput.addEventListener("input", () => {
        currentLead.email = emailInput.value.trim();
      });
    }
    if (phoneInput) {
      phoneInput.addEventListener("input", () => {
        currentLead.phone = cleanPhone(phoneInput.value);
      });
    }

    function showError(msg) {
      if (!errorBox) return;
      errorBox.textContent = msg;
      errorBox.classList.remove("d-none");
      errorBox.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    function clearError() {
      if (!errorBox) return;
      errorBox.textContent = "";
      errorBox.classList.add("d-none");
    }

    function setLoading(isLoading) {
      if (!submitBtn) return;
      if (isLoading) {
        submitBtn.disabled = true;
        submitBtn.dataset.origText = submitBtn.innerHTML;
        submitBtn.innerHTML = `
          <span class="btn-complete-main"><i class="ph-bold ph-spinner" style="animation: spin 1s linear infinite;"></i> Initiating Secure Payment...</span>
          <span class="btn-complete-sub">Connecting to payment gateway...</span>
        `;
      } else {
        submitBtn.disabled = false;
        if (submitBtn.dataset.origText) {
          submitBtn.innerHTML = submitBtn.dataset.origText;
        }
      }
    }

    if (submitBtn) {
      submitBtn.addEventListener("click", handlePaymentSubmit);
    }

    async function handlePaymentSubmit(e) {
      if (e) e.preventDefault();
      clearError();

      const name = (fullNameInput ? fullNameInput.value : currentLead.name || "").trim();
      const email = (emailInput ? emailInput.value : currentLead.email || "").trim();
      const p = cleanPhone(phoneInput ? phoneInput.value : currentLead.phone || "");
      const city = (currentLead.city || "").trim();
      const profession = (currentLead.profession || "Working Professional").trim();

      if (name.length < 2) {
        showError("Please enter your full name.");
        if (fullNameInput) fullNameInput.focus();
        return;
      }

      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError("Please enter a valid email address.");
        if (emailInput) emailInput.focus();
        return;
      }

      if (!/^[6-9]\d{9}$/.test(p)) {
        showError("Please enter a valid 10-digit Indian WhatsApp mobile number.");
        if (phoneInput) phoneInput.focus();
        return;
      }

      // Save locally
      currentLead.name = name;
      currentLead.email = email;
      currentLead.phone = p;
      try {
        localStorage.setItem("dch_lead_info", JSON.stringify(currentLead));
      } catch (_) { }

      setLoading(true);

      try {
        const apiBase = window.DCH_CONFIG?.apiBaseUrl || "/api";
        const res = await fetch(`${apiBase}/create-order.php`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            enrollment_id: currentLead.enrollment_id || "",
            name: name,
            phone: p,
            email: email,
            location: city,
            city: city,
            current_profile: profession,
            profession: profession
          })
        });

        const orderData = await res.json();

        if (!orderData.success) {
          throw new Error(orderData.message || "Failed to initiate workshop order.");
        }

        const activeEnrollmentId = orderData.enrollment_id || currentLead.enrollment_id;

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
            contact: orderData.user?.phone || p
          },
          theme: {
            color: "#2563eb"
          },
          handler: async function (response) {
            await verifyPaymentOnServer({
              razorpay_order_id: response.razorpay_order_id,
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_signature: response.razorpay_signature,
              enrollment_id: activeEnrollmentId
            });
          },
          modal: {
            ondismiss: function () {
              setLoading(false);
            }
          }
        };

        if (orderData.is_mock) {
          setTimeout(async () => {
            await verifyPaymentOnServer({
              razorpay_order_id: orderData.order_id,
              razorpay_payment_id: "pay_simulated_" + Math.random().toString(36).substring(2, 10),
              razorpay_signature: "sig_simulated_" + Math.random().toString(36).substring(2, 15),
              enrollment_id: activeEnrollmentId
            });
          }, 800);
          return;
        }

        if (typeof window.Razorpay === "undefined") {
          setLoading(false);
          showError("Razorpay payment checkout failed to load. Please disable ad-blockers or refresh the page.");
          return;
        }

        const rzp = new window.Razorpay(options);
        rzp.on("payment.failed", function (failResponse) {
          setLoading(false);
          const errReason = failResponse.error?.description || "Payment failed or cancelled.";
          window.location.href = `payment-failed.html?error=${encodeURIComponent(errReason)}&order=${encodeURIComponent(orderData.order_id)}`;
        });
        rzp.open();

      } catch (err) {
        setLoading(false);
        showError(err.message || "Connection error. Please try again.");
      }
    }

    async function verifyPaymentOnServer(payload) {
      try {
        const apiBase = window.DCH_CONFIG?.apiBaseUrl || "/api";
        const res = await fetch(`${apiBase}/verify-payment.php`, {
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
  }

  // Auto-init if on payment page
  if (document.getElementById("completeOrderBtn")) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initPaymentPage);
    } else {
      initPaymentPage();
    }
  }

  window.DCH_PAYMENT = {
    init: initPaymentPage
  };
})();

