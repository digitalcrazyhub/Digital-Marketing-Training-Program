/**
 * Digital Crazy Hub - reCAPTCHA v3 Frontend Helper
 */

(function () {
  "use strict";

  async function getRecaptchaToken(action = "enrollment_submit") {
    if (window.grecaptcha && window.RECAPTCHA_SITE_KEY) {
      try {
        return await window.grecaptcha.execute(window.RECAPTCHA_SITE_KEY, { action: action });
      } catch (err) {
        console.warn("reCAPTCHA execution error:", err);
        return "";
      }
    }
    return "";
  }

  window.DCH_RECAPTCHA = {
    getToken: getRecaptchaToken
  };
})();

