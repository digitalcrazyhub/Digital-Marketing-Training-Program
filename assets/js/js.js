/**
 * Digital Crazy Hub - Central Workshop Configuration
 * Single Source of Truth for Date, Time, Language, Platform, Pricing, and Contact.
 */

const WORKSHOP_CONFIG = {
  title: "₹49 AI-Integrated Digital Marketing Paid Workshop",
  headline: "49% of Businesses Need Digital Marketing to Grow.",
  sellingPoint: "100% PRACTICAL DIGITAL MARKETING TRAINING",
  description: "Learn how to plan and execute powerful digital marketing strategies using SEO, Social Media Marketing, Google Ads, Meta Ads, Content Marketing, Lead Generation, Website Development, Analytics, and AI-Powered Marketing Tools to drive business growth and build a successful digital presence.",
  
  // Schedule
  dateFormatted: "November 5, 2026",
  day: "Thursday",
  time: "7:00 PM IST",
  duration: "3 Hours",
  targetIsoDate: "2026-11-05T19:00:00+05:30",
  timezone: "Asia/Kolkata",
  
  // Delivery
  language: "Tamil",
  platform: "Live on Zoom",
  
  // Pricing
  price: 49,
  currency: "INR",
  displayPrice: "₹49",
  originalPrice: "₹1,999",
  savePercent: "97% OFF",
  seatsTotal: 100,
  
  // Contact & Support
  whatsappSupportNumber: "919047700201",
  supportEmail: "info@digitalcrazyhub.com",
  whatsappCommunityUrl: "https://chat.whatsapp.com/sample-dch-digital-marketing"
};

// Auto-populate elements with config attributes
document.addEventListener("DOMContentLoaded", () => {
  const elements = {
    "[data-cfg-date]": WORKSHOP_CONFIG.dateFormatted,
    "[data-cfg-day]": WORKSHOP_CONFIG.day,
    "[data-cfg-time]": WORKSHOP_CONFIG.time,
    "[data-cfg-duration]": WORKSHOP_CONFIG.duration,
    "[data-cfg-language]": WORKSHOP_CONFIG.language,
    "[data-cfg-platform]": WORKSHOP_CONFIG.platform,
    "[data-cfg-price]": WORKSHOP_CONFIG.displayPrice,
    "[data-cfg-original-price]": WORKSHOP_CONFIG.originalPrice,
    "[data-cfg-save]": WORKSHOP_CONFIG.savePercent,
    "[data-cfg-selling-point]": WORKSHOP_CONFIG.sellingPoint
  };

  Object.entries(elements).forEach(([selector, value]) => {
    document.querySelectorAll(selector).forEach(el => {
      el.textContent = value;
    });
  });
});
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
/**
 * Digital Crazy Hub - 42 Student Training Gallery & Lightbox Controller
 * Powers 4 independent horizontal marquee tracks with click-to-preview lightbox modal.
 * Verified with exact file extensions on disk.
 */

(function () {
  "use strict";

  const GALLERY_IMAGES = [
    // Track 1 (11 images)
    { src: "assets/images/Training/training/training-01.jpeg", caption: "Hands-on Classroom Training - Practical Digital Marketing" },
    { src: "assets/images/Training/training/training-02.jpeg", caption: "Practical Digital Marketing Session - Live Strategy" },
    { src: "assets/images/Training/training/training-03.jpg", caption: "Interactive Student Workshop - Campaign Optimization" },
    { src: "assets/images/Training/training/training-04.jpg", caption: "Real-world Campaign Demonstration - Analytics & Ads" },
    { src: "assets/images/Training/training/training-05.jpg", caption: "In-Person Mentorship & Live Q&A Discussion" },
    { src: "assets/images/Training/vels-college/vels-01.jpg", caption: "Vels University Campus Workshop - Digital Marketing" },
    { src: "assets/images/Training/vels-college/vels-02.jpg", caption: "Vels College Seminar - Lead Generation & Content Marketing" },
    { src: "assets/images/Training/vels-college/vels-03.jpg", caption: "Practical SEO & Ads Masterclass at Vels" },
    { src: "assets/images/Training/vels-college/vels-04.jpg", caption: "Student Engagement & Live Ad Setup at Vels" },
    { src: "assets/images/Training/vels-college/vels-05.jpg", caption: "Group Training & Certification Moment at Vels" },
    { src: "assets/images/Training/SNS-college/sns-01.jpg", caption: "SNS College of Technology Seminar - Digital Growth" },

    // Track 2 (11 images)
    { src: "assets/images/Training/SNS-college/sns-02.jpg", caption: "AI Marketing Tools Workshop at SNS College" },
    { src: "assets/images/Training/SNS-college/sns-03.jpg", caption: "Hands-on Student Lab Session at SNS College" },
    { src: "assets/images/Training/SNS-college/sns-04.jpg", caption: "Digital Strategy & Campaign Presentation at SNS" },
    { src: "assets/images/Training/SNS-college/sns-05.jpg", caption: "Campaign Planning Masterclass at SNS College" },
    { src: "assets/images/Training/SNS-college/sns-06.jpg", caption: "Campus Auditorium Training Session at SNS" },
    { src: "assets/images/Training/SNS-college/sns-07.jpeg", caption: "Student Interaction & Career Mentoring at SNS" },
    { src: "assets/images/Training/SDC-college/SDC-01.png", caption: "SDC College Digital Marketing Workshop - Practical Session" },
    { src: "assets/images/Training/SDC-college/SDC-02.jpg", caption: "Live Social Media Marketing Lab at SDC" },
    { src: "assets/images/Training/SDC-college/SDC-03.jpg", caption: "Student Practical Training & Analytics Setup at SDC" },
    { src: "assets/images/Training/SDC-college/SDC-04.jpg", caption: "Google & Meta Ads Setup Session at SDC" },
    { src: "assets/images/Training/SDC-college/SDC-05.jpg", caption: "Full Classroom Practical Session at SDC" },

    // Track 3 (10 images)
    { src: "assets/images/Training/SDC-college/SDC-06.jpg", caption: "AI Tools Demonstration & Live Prompt Engineering at SDC" },
    { src: "assets/images/Training/SDC-college/SDC-07.jpg", caption: "Interactive Q&A with Students at SDC" },
    { src: "assets/images/Training/SDC-college/SDC-08.jpg", caption: "Live Case Study Discussion & ROI Analysis at SDC" },
    { src: "assets/images/Training/SDC-college/SDC-09.jpg", caption: "Real-world Digital Marketing Execution at SDC" },
    { src: "assets/images/Training/SDC-college/SDC-10.jpg", caption: "Student Mentoring & Practical Project Review at SDC" },
    { src: "assets/images/Training/SDC-college/SDC-11.jpg", caption: "Group Workshop Finale at SDC College" },
    { src: "assets/images/Training/Event-SQL/event-sql-01.png", caption: "Tech & Marketing Summit Session - Digital Leadership" },
    { src: "assets/images/Training/Event-SQL/event-sql-02.jpeg", caption: "Analytics & Performance Marketing Workshop" },
    { src: "assets/images/Training/Event-SQL/event-sql-03.jpeg", caption: "Practical Tech Training & Live Campaign Demos" },
    { src: "assets/images/Training/Event-SQL/event-sql-04.jpeg", caption: "Industry Professional Training Session - Growth Hacks" },

    // Track 4 (10 images)
    { src: "assets/images/Training/Event-SQL/event-sql-05.jpeg", caption: "Corporate Digital Skills Training & Implementation" },
    { src: "assets/images/Training/Event-SQL/event-sql-06.jpeg", caption: "Hands-on Marketing Tech Demonstration" },
    { src: "assets/images/Training/Event-SQL/event-sql-07.jpeg", caption: "Live Audience Engagement & Marketing Frameworks" },
    { src: "assets/images/Training/Event-SQL/event-sql-08.jpeg", caption: "Career Growth & Practical Marketing Guidance" },
    { src: "assets/images/Training/Event-SQL/event-sql-09.jpeg", caption: "Executive Digital Transformation Session" },
    { src: "assets/images/Training/Event-SQL/event-sql-10.jpeg", caption: "Growth Marketing & AI Integration Masterclass" },
    { src: "assets/images/Training/Event-SQL/event-sql-11.jpeg", caption: "Masterclass Group Practical Session" },
    { src: "assets/images/Training/Event-SQL/event-sql-12.jpeg", caption: "In-depth Campaign Strategy Lecture" },
    { src: "assets/images/Training/Event-SQL/event-sql-13.jpeg", caption: "Live Student Projects Evaluation & Feedback" },
    { src: "assets/images/Training/Event-SQL/event-sql-14.jpg", caption: "Certificate Presentation & Achievement Moment" }
  ];

  let currentLightboxIndex = 0;
  let lightboxModal = null;
  let lightboxImg = null;
  let lightboxCaption = null;
  let lightboxCounter = null;

  function initGallery() {
    lightboxModal = document.getElementById("galleryLightbox");
    lightboxImg = document.getElementById("lightboxImage");
    lightboxCaption = document.getElementById("lightboxCaption");
    lightboxCounter = document.getElementById("lightboxCounter");

    // Populate the 4 tracks if they exist
    populateTrack("marqueeTrack1", GALLERY_IMAGES.slice(0, 11), 0);
    populateTrack("marqueeTrack2", GALLERY_IMAGES.slice(11, 22), 11);
    populateTrack("marqueeTrack3", GALLERY_IMAGES.slice(22, 32), 22);
    populateTrack("marqueeTrack4", GALLERY_IMAGES.slice(32, 42), 32);

    setupLightboxEvents();
  }

  function populateTrack(trackId, images, globalOffset) {
    const trackEl = document.getElementById(trackId);
    if (!trackEl) return;

    // Render items twice for infinite seamless scroll loop
    const renderItems = (items) => {
      return items.map((item, idx) => {
        const globalIdx = globalOffset + idx;
        return `
          <div class="marquee-item" data-gallery-index="${globalIdx}">
            <img src="${item.src}" alt="${item.caption}" loading="lazy" class="marquee-img">
            <div class="marquee-overlay">
              <span class="marquee-zoom-icon"><i class="ph ph-magnifying-glass-plus"></i></span>
              <p class="marquee-cap">${item.caption}</p>
            </div>
          </div>
        `;
      }).join("");
    };

    trackEl.innerHTML = renderItems(images) + renderItems(images);

    // Bind click events on marquee items
    trackEl.querySelectorAll(".marquee-item").forEach(item => {
      item.addEventListener("click", () => {
        const idx = parseInt(item.getAttribute("data-gallery-index"), 10);
        openLightbox(idx);
      });
    });
  }

  function openLightbox(index) {
    if (!lightboxModal || index < 0 || index >= GALLERY_IMAGES.length) return;
    currentLightboxIndex = index;
    updateLightbox();
    lightboxModal.classList.add("active");
    lightboxModal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeLightbox() {
    if (!lightboxModal) return;
    lightboxModal.classList.remove("active");
    lightboxModal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  }

  function updateLightbox() {
    const item = GALLERY_IMAGES[currentLightboxIndex];
    if (!item) return;

    if (lightboxImg) {
      lightboxImg.src = item.src;
      lightboxImg.alt = item.caption;
    }
    if (lightboxCaption) {
      lightboxCaption.textContent = item.caption;
    }
    if (lightboxCounter) {
      lightboxCounter.textContent = `${currentLightboxIndex + 1} / ${GALLERY_IMAGES.length}`;
    }
  }

  function prevImage() {
    currentLightboxIndex = (currentLightboxIndex - 1 + GALLERY_IMAGES.length) % GALLERY_IMAGES.length;
    updateLightbox();
  }

  function nextImage() {
    currentLightboxIndex = (currentLightboxIndex + 1) % GALLERY_IMAGES.length;
    updateLightbox();
  }

  function setupLightboxEvents() {
    if (!lightboxModal) return;

    const closeBtn = document.getElementById("lightboxClose");
    const prevBtn = document.getElementById("lightboxPrev");
    const nextBtn = document.getElementById("lightboxNext");

    if (closeBtn) closeBtn.addEventListener("click", closeLightbox);
    if (prevBtn) prevBtn.addEventListener("click", prevImage);
    if (nextBtn) nextBtn.addEventListener("click", nextImage);

    // Click on backdrop to close
    lightboxModal.addEventListener("click", (e) => {
      if (e.target === lightboxModal || e.target.classList.contains("lightbox-backdrop")) {
        closeLightbox();
      }
    });

    // Keyboard navigation
    document.addEventListener("keydown", (e) => {
      if (!lightboxModal.classList.contains("active")) return;
      if (e.key === "Escape") closeLightbox();
      else if (e.key === "ArrowLeft") prevImage();
      else if (e.key === "ArrowRight") nextImage();
    });

    // Mobile touch swipe gestures
    let touchStartX = 0;
    let touchEndX = 0;

    lightboxModal.addEventListener("touchstart", (e) => {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    lightboxModal.addEventListener("touchend", (e) => {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
    }, { passive: true });

    function handleSwipe() {
      const diff = touchEndX - touchStartX;
      if (Math.abs(diff) > 50) {
        if (diff > 0) {
          prevImage();
        } else {
          nextImage();
        }
      }
    }
  }

  window.DCH_GALLERY = {
    open: openLightbox,
    close: closeLightbox,
    images: GALLERY_IMAGES
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initGallery);
  } else {
    initGallery();
  }
})();
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
/**
 * Digital Crazy Hub - Video Testimonials YouTube Facade Controller
 * Lazy loads YouTube videos on click to maximize performance, prevent lag,
 * and eliminate browser permissions-policy and WebGPU adapter console warnings.
 */

(function () {
  "use strict";

  function initTestimonials() {
    const cards = document.querySelectorAll(".yt-facade-card");

    cards.forEach(card => {
      const videoId = card.getAttribute("data-video-id");
      if (!videoId) return;

      // Ensure poster background is set if not already inline
      const thumbEl = card.querySelector(".yt-poster");
      if (thumbEl && !thumbEl.style.backgroundImage) {
        thumbEl.style.backgroundImage = `url('https://i.ytimg.com/vi/${videoId}/hqdefault.jpg')`;
      }

      card.addEventListener("click", function (e) {
        e.preventDefault();
        loadVideoPlayer(card, videoId);
      });
    });
  }

  function loadVideoPlayer(container, videoId) {
    if (container.classList.contains("playing")) return;

    // Create iframe
    const iframe = document.createElement("iframe");
    iframe.setAttribute("src", `https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1`);
    iframe.setAttribute("title", "Student Success Testimonial");
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("allow", "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; compute-pressure");
    iframe.setAttribute("allowfullscreen", "true");
    iframe.className = "yt-embed-iframe";

    // Clear contents and insert player
    container.innerHTML = "";
    container.appendChild(iframe);
    container.classList.add("playing");
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initTestimonials);
  } else {
    initTestimonials();
  }
})();
/**
 * Digital Crazy Hub - Main Landing Page Script
 * Statistics animation, FAQ Accordion, Sticky Bottom Bar, Back to Top, Anchor Scroll
 */

(function () {
  "use strict";

  // 1. Stats Counter Animation using IntersectionObserver
  function initCounters() {
    const counterElements = document.querySelectorAll(".counter-val");
    if (!counterElements.length) return;

    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const target = parseInt(el.getAttribute("data-target"), 10) || 0;
          const suffix = el.getAttribute("data-suffix") || "";
          const prefix = el.getAttribute("data-prefix") || "";
          const duration = 1600; // ms
          const start = 0;
          const startTime = performance.now();

          function step(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            // Ease out quad
            const easeProgress = 1 - (1 - progress) * (1 - progress);
            const current = Math.floor(start + (target - start) * easeProgress);

            el.textContent = `${prefix}${current.toLocaleString()}${suffix}`;

            if (progress < 1) {
              requestAnimationFrame(step);
            } else {
              el.textContent = `${prefix}${target.toLocaleString()}${suffix}`;
            }
          }

          requestAnimationFrame(step);
          obs.unobserve(el);
        }
      });
    }, { threshold: 0.3 });

    counterElements.forEach(el => observer.observe(el));
  }

  // 2. FAQ Accordion
  function initFAQ() {
    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach(item => {
      const questionBtn = item.querySelector(".faq-question");
      const answerEl = item.querySelector(".faq-answer");

      if (!questionBtn || !answerEl) return;

      questionBtn.addEventListener("click", () => {
        const isOpen = item.classList.contains("active");

        // Close all other items for clean accordion behavior
        faqItems.forEach(other => {
          if (other !== item && other.classList.contains("active")) {
            other.classList.remove("active");
            const otherBtn = other.querySelector(".faq-question");
            const otherAns = other.querySelector(".faq-answer");
            if (otherBtn) otherBtn.setAttribute("aria-expanded", "false");
            if (otherAns) otherAns.style.maxHeight = null;
          }
        });

        if (isOpen) {
          item.classList.remove("active");
          questionBtn.setAttribute("aria-expanded", "false");
          answerEl.style.maxHeight = null;
        } else {
          item.classList.add("active");
          questionBtn.setAttribute("aria-expanded", "true");
          answerEl.style.maxHeight = answerEl.scrollHeight + "px";
        }
      });
    });
  }

  // 3. Sticky Bottom Mobile Bar & Back-to-Top Button
  function initScrollTriggers() {
    const stickyBar = document.getElementById("mobileStickyBar");
    const backToTop = document.getElementById("backToTopBtn");
    const heroSection = document.getElementById("heroSection");

    window.addEventListener("scroll", () => {
      const scrollY = window.scrollY || window.pageYOffset;

      // Show/hide sticky mobile bar after 350px
      if (stickyBar) {
        if (scrollY > 350) {
          stickyBar.classList.add("visible");
        } else {
          stickyBar.classList.remove("visible");
        }
      }

      // Back to top button
      if (backToTop) {
        if (scrollY > 500) {
          backToTop.classList.add("visible");
        } else {
          backToTop.classList.remove("visible");
        }
      }
    }, { passive: true });

    if (backToTop) {
      backToTop.addEventListener("click", (e) => {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
    }
  }

  // 4. Smooth Anchor Scrolling
  function initSmoothScroll() {
    document.querySelectorAll("a[href^='#']:not([href='#']):not([data-open-enrollment])").forEach(anchor => {
      anchor.addEventListener("click", function (e) {
        const targetId = this.getAttribute("href");
        if (targetId && targetId !== "#") {
          const targetEl = document.querySelector(targetId);
          if (targetEl) {
            e.preventDefault();
            targetEl.scrollIntoView({ behavior: "smooth", block: "start" });
          }
        }
      });
    });
  }

  // Run on DOM ready
  function init() {
    initCounters();
    initFAQ();
    initScrollTriggers();
    initSmoothScroll();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
