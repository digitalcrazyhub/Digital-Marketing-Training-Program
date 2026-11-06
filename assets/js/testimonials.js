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

