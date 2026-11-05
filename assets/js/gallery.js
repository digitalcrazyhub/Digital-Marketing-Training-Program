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
