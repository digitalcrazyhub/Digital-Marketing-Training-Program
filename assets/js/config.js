/**
 * Digital Crazy Hub - Central Frontend Configuration
 * Single Source of Truth for Domain, API Endpoints, Schedule, Pricing, and Delivery.
 */

function resolveApiBaseUrl() {
  const loc = window.location;
  if (loc.hostname !== 'localhost' && loc.hostname !== '127.0.0.1') {
    return '/api';
  }
  const path = loc.pathname;
  const publicIndex = path.indexOf('/public');
  if (publicIndex !== -1) {
    return path.substring(0, publicIndex + 7) + '/api';
  }
  const folderMatch = path.match(/^\/([^\/]+)/);
  if (folderMatch && folderMatch[1] && !folderMatch[1].includes('.')) {
    return '/' + folderMatch[1] + '/api';
  }
  return '/api';
}

const WORKSHOP_CONFIG = {
  // Domain & API Routing (Phase 6: Must use training.digitalcrazyhub.com directly)
  baseUrl: "https://training.digitalcrazyhub.com",
  apiBaseUrl: resolveApiBaseUrl(),

  // Core Program Details
  title: "₹49 AI-Integrated Digital Marketing Paid Workshop",
  headline: "49% of Businesses Need Digital Marketing to Grow.",
  sellingPoint: "100% PRACTICAL DIGITAL MARKETING TRAINING",
  description: "Learn how to plan and execute powerful digital marketing strategies using SEO, Social Media Marketing, Google Ads, Meta Ads, Content Marketing, Lead Generation, Website Development, Analytics, and AI-Powered Marketing Tools to drive business growth and build a successful digital presence.",

  // Schedule
  dateFormatted: "November 24, 2026",
  day: "Tuesday",
  time: "7:00 PM IST",
  duration: "3 Hours",
  targetIsoDate: "2026-11-24T19:00:00+05:30",
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
  whatsappCommunityUrl: "https://chat.whatsapp.com/digitalcrazyhub-dm-workshop"
};

// Global API helper
window.DCH_CONFIG = WORKSHOP_CONFIG;

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

