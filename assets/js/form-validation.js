/**
 * Digital Crazy Hub - Frontend Form Validation Module
 */

(function () {
  "use strict";

  function cleanIndianPhone(rawPhone) {
    let digits = (rawPhone || "").replace(/[^0-9]/g, "");
    if (digits.length === 12 && digits.startsWith("91")) {
      digits = digits.substring(2);
    } else if (digits.length === 11 && digits.startsWith("0")) {
      digits = digits.substring(1);
    }
    return digits;
  }

  function validateEnrollmentForm(data) {
    const errors = [];

    const name = (data.name || "").trim();
    if (name.length < 2) {
      errors.push("Please enter your full name (at least 2 characters).");
    }

    const cleanPhone = cleanIndianPhone(data.phone);
    if (!/^[6-9]\d{9}$/.test(cleanPhone)) {
      errors.push("Please enter a valid 10-digit Indian WhatsApp mobile number starting with 6, 7, 8, or 9.");
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const email = (data.email || "").trim();
    if (!emailRegex.test(email)) {
      errors.push("Please enter a valid email address.");
    }

    const location = (data.location || data.city || "").trim();
    if (location.length < 2) {
      errors.push("Location is mandatory. Please enter your city or location.");
    }

    const currentProfile = (data.current_profile || data.profession || "").trim();
    if (!currentProfile) {
      errors.push("Current Profile is mandatory. Please select your current profile.");
    }

    return {
      isValid: errors.length === 0,
      errors: errors,
      sanitized: {
        name: name,
        phone: cleanPhone,
        email: email,
        location: location,
        current_profile: currentProfile
      }
    };
  }

  window.DCH_VALIDATOR = {
    cleanPhone: cleanIndianPhone,
    validateEnrollment: validateEnrollmentForm
  };
})();

