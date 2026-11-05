/**
 * Digital Crazy Hub - Payment & Razorpay Checkout Controller
 */

(function () {
  "use strict";

  function initPaymentPage() {
    const submitBtn = document.getElementById("completeOrderBtn");
    const errorBox = document.getElementById("checkoutErrorBox");
    const summaryBox = document.getElementById("candidateSummaryBox");
    const editWrap = document.getElementById("candidateEditWrap");
    const toggleEditBtn = document.getElementById("toggleEditBtn");
    const closeEditBtn = document.getElementById("closeEditBtn");
    const saveEditBtn = document.getElementById("saveEditBtn");

    const fullNameInput = document.getElementById("fullNameInput");
    const emailInput = document.getElementById("emailInput");
    const phoneInput = document.getElementById("phoneInput");
    const cityInput = document.getElementById("cityInput");
    const professionSelect = document.getElementById("professionSelect");

    const displayName = document.getElementById("displayCandidateName");
    const displayPhone = document.getElementById("displayCandidatePhone");
    const displayEmail = document.getElementById("displayCandidateEmail");
    const displayExtra = document.getElementById("displayCandidateExtra");

    const tabStep1 = document.getElementById("tabStep1");
    const tabStep1Title = document.getElementById("tabStep1Title");
    const tabStep1Sub = document.getElementById("tabStep1Sub");

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

    function formatPhoneDisplay(p) {
      if (p && p.length === 10) {
        return `+91 ${p.slice(0, 5)} ${p.slice(5)}`;
      }
      return p ? `+91 ${p}` : "Not provided";
    }

    function syncInputs() {
      if (fullNameInput) fullNameInput.value = currentLead.name;
      if (emailInput) emailInput.value = currentLead.email;
      if (phoneInput) phoneInput.value = cleanPhone(currentLead.phone);
      if (cityInput) cityInput.value = currentLead.city;
      if (professionSelect && currentLead.profession) professionSelect.value = currentLead.profession;
    }

    function syncDisplayCard() {
      const p = cleanPhone(currentLead.phone);
      const hasDetails = currentLead.name.length >= 2 || p.length === 10;

      if (hasDetails) {
        if (displayName) displayName.textContent = currentLead.name || "Enrolled Learner";
        if (displayPhone) displayPhone.textContent = formatPhoneDisplay(p);
        if (displayEmail) displayEmail.textContent = currentLead.email || "Email pending";

        let extraStr = currentLead.profession || "Working Professional";
        if (currentLead.city) {
          extraStr += ` • ${currentLead.city}`;
        }
        if (displayExtra) displayExtra.textContent = extraStr;

        if (tabStep1) tabStep1.classList.add("tab-completed");
        if (tabStep1Title) tabStep1Title.innerHTML = `<i class="ph-fill ph-check-circle"></i><span>1 DETAILS CONFIRMED</span>`;
        if (tabStep1Sub) tabStep1Sub.textContent = "FROM ENROLLMENT FORM";

        if (summaryBox) summaryBox.classList.remove("d-none");
        if (editWrap) editWrap.classList.add("d-none");
      } else {
        if (tabStep1) tabStep1.classList.remove("tab-completed");
        if (tabStep1Title) tabStep1Title.innerHTML = `<i class="ph-bold ph-user"></i><span>1 YOUR INFO</span>`;
        if (tabStep1Sub) tabStep1Sub.textContent = "ENTER YOUR DETAILS";

        if (summaryBox) summaryBox.classList.add("d-none");
        if (editWrap) editWrap.classList.remove("d-none");
      }
    }

    syncInputs();
    syncDisplayCard();

    if (toggleEditBtn) {
      toggleEditBtn.addEventListener("click", () => {
        if (editWrap) editWrap.classList.toggle("d-none");
        if (fullNameInput && !editWrap.classList.contains("d-none")) {
          fullNameInput.focus();
        }
      });
    }

    if (closeEditBtn) {
      closeEditBtn.addEventListener("click", () => {
        if (editWrap) editWrap.classList.add("d-none");
      });
    }

    if (saveEditBtn) {
      saveEditBtn.addEventListener("click", () => {
        clearError();
        const n = fullNameInput ? fullNameInput.value.trim() : "";
        const e = emailInput ? emailInput.value.trim() : "";
        const p = cleanPhone(phoneInput ? phoneInput.value : "");
        const c = cityInput ? cityInput.value.trim() : "";
        const prof = professionSelect ? professionSelect.value.trim() : "";

        if (n.length < 2) {
          showError("Please enter your full name (at least 2 characters).");
          return;
        }
        if (!/^[6-9]\d{9}$/.test(p)) {
          showError("Please enter a valid 10-digit Indian WhatsApp mobile number.");
          return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)) {
          showError("Please enter a valid email address.");
          return;
        }

        currentLead.name = n;
        currentLead.email = e;
        currentLead.phone = p;
        currentLead.city = c;
        currentLead.profession = prof;

        try {
          localStorage.setItem("dch_lead_info", JSON.stringify(currentLead));
        } catch (_) { }

        syncDisplayCard();
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

      const name = (currentLead.name || (fullNameInput ? fullNameInput.value : "")).trim();
      const email = (currentLead.email || (emailInput ? emailInput.value : "")).trim();
      const p = cleanPhone(currentLead.phone || (phoneInput ? phoneInput.value : ""));
      const city = (currentLead.city || (cityInput ? cityInput.value : "")).trim();
      const profession = (currentLead.profession || (professionSelect ? professionSelect.value : "")).trim();

      if (name.length < 2) {
        if (editWrap) editWrap.classList.remove("d-none");
        showError("Please provide your full candidate name.");
        if (fullNameInput) fullNameInput.focus();
        return;
      }

      if (!/^[6-9]\d{9}$/.test(p)) {
        if (editWrap) editWrap.classList.remove("d-none");
        showError("Please provide a valid 10-digit Indian WhatsApp mobile number.");
        if (phoneInput) phoneInput.focus();
        return;
      }

      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        if (editWrap) editWrap.classList.remove("d-none");
        showError("Please provide a valid email address.");
        if (emailInput) emailInput.focus();
        return;
      }

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

        if (orderData.is_mock || typeof window.Razorpay === "undefined") {
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

