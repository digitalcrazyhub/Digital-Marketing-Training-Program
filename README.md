# Digital Crazy Hub – Digital Marketing Training Program

Official production repository for the **Digital Crazy Hub ₹49 AI-Integrated Digital Marketing Training Workshop**.

Production Domain: [https://training.digitalcrazyhub.com/](https://training.digitalcrazyhub.com/)

---

## 1. Professional Project Architecture

```
digital-marketing-training-program/
│
├── public/                               # Web Document Root
│   ├── index.html                        # Main Landing Page (Zero large inline CSS/JS)
│   ├── thank-you.html                    # Authenticated Confirmation & Pass Page
│   ├── payment-page.html                 # Direct Checkout & Candidate Card Page
│   ├── payment-failed.html               # Payment Incomplete / Retry Page
│   ├── 404.html                          # Branded 404 Error Page
│   ├── robots.txt                        # Production SEO Robots Directive
│   ├── sitemap.xml                       # Production XML Sitemap (Subdomain root)
│   ├── .htaccess                         # Apache Security Headers, HTTPS & Caching
│   │
│   ├── assets/                           # Public Static Assets
│   │   ├── css/                          # Modular Component Stylesheets
│   │   │   ├── variables.css             # Global Design Tokens & Brand Colors
│   │   │   ├── reset.css                 # Base HTML5 Reset
│   │   │   ├── global.css                # Global Typography, Containers & Buttons
│   │   │   ├── header.css                # Sticky Header & Live Marquee Ticker
│   │   │   ├── hero.css                  # Split 60% Video / 40% Content Hero
│   │   │   ├── sections.css              # Workshop Cards, Curriculum, Gallery, FAQ
│   │   │   ├── enrollment-form.css       # Universal Enrollment Modal Styles
│   │   │   ├── thank-you.css             # Thank You Page & Pass Card Styles
│   │   │   ├── payment.css               # Checkout & Candidate Summary Styles
│   │   │   ├── footer.css                # Footer, Back-to-Top, Mobile Sticky Bar
│   │   │   ├── responsive.css            # Media Queries (1200px down to 360px)
│   │   │   └── animations.css            # Keyframe Animations & Glow Effects
│   │   │
│   │   ├── js/                           # Pure Vanilla JavaScript Modules
│   │   │   ├── config.js                 # Central Client-Side App Configuration
│   │   │   ├── recaptcha.js              # Google reCAPTCHA v3 Execution Helper
│   │   │   ├── form-validation.js        # Indian Mobile & Field Validation
│   │   │   ├── enrollment-form.js        # Modal Controller & Server-Side Submission
│   │   │   ├── countdown.js              # Real-Time Countdown to Workshop Date
│   │   │   ├── gallery.js                # 42-Image Infinite Marquee & Lightbox
│   │   │   ├── testimonials.js           # Video Testimonials & YouTube Facade
│   │   │   ├── faq.js                    # Accessible FAQ Accordion Controller
│   │   │   ├── navigation.js             # Mobile Sticky Bar & Smooth Scroll
│   │   │   ├── payment.js                # Razorpay Checkout & Verification Runner
│   │   │   ├── thank-you.js              # Authenticated Enrollment Verification
│   │   │   └── main.js                   # Application Boot Coordinator
│   │   │
│   │   ├── images/                       # Brand Logos, MSME & Training Galleries
│   │   │   ├── DCH.png                   # Official Digital Crazy Hub Logo
│   │   │   ├── msme.png                  # Govt. of India MSME Badge
│   │   │   ├── payment_secure.png        # Payment Security & Trust Seals
│   │   │   └── Training/                 # 42 Verified Training Session Photos
│   │   │
│   │   └── video/                        # Video Assets
│   │       └── banner-video.mp4          # Autoplay Looping Banner Video
│   │
│   └── api/                              # Thin Public API Entry Points
│       ├── create-enrollment.php         # POST: Register Lead (MySQL + Google Sheets)
│       ├── create-order.php              # POST: Create Razorpay Order
│   ├── api/                              # Thin Entry Gateways Forwarding to /backend/router.php
│   │   ├── index.php                     # Central API Gateway
│   │   ├── create-enrollment.php         # POST: Initiate Enrollment & Order
│   │   ├── create-order.php              # POST: Server-side Order Creation
│   │   ├── verify-payment.php            # POST: Verify Signature, Captures & Notifies
│   │   ├── verify-status.php             # GET: Authorize Thank You Page View
│   │   ├── webhook.php                   # POST: Asynchronous Webhook Capture
│   │   └── download-invoice.php          # GET: Stream Tax Invoice PDF
│   │
│   ├── assets/                           # Frontend Assets (CSS, JS, Images, Video)
│   ├── .htaccess                         # Public Rewrite & Browser Caching Rules
│   ├── robots.txt                        # Crawler Directives & Sitemap Reference
│   └── sitemap.xml                       # Production Sitemap
│
├── backend/                              # Centralized Backend Architecture (Private)
│   ├── bootstrap.php                     # PSR-4 Autoloader & Environment (.env) Loader
│   ├── router.php                        # Central API Request Dispatcher
│   ├── .htaccess                         # Direct HTTP Access Denied
│   │
│   ├── config/                           # Centralized Configuration
│   │   ├── app.php                       # Application & Workshop Settings
│   │   ├── database.php                  # PDO Connection & Resilient Fallback
│   │   ├── payment.php                   # Razorpay Credentials & Secrets
│   │   ├── email.php                     # Hostinger SMTP & Notification Settings
│   │   └── google-sheets.php             # Google Sheets API V4 & Formatting
│   │
│   ├── controllers/                      # Request Handlers
│   │   ├── EnrollmentController.php      # Enrollment, Status & Invoice Actions
│   │   ├── PaymentController.php         # Order Creation & Verification Actions
│   │   └── WebhookController.php         # Idempotent Razorpay Webhook Action
│   │
│   ├── services/                         # Core Business Logic Services
│   │   ├── EnrollmentService.php         # Master Orchestrator & Storage Engine
│   │   ├── RazorpayService.php           # Order Client & HMAC-SHA256 Signatures
│   │   ├── GoogleSheetsService.php       # Pure PHP RS256 JWT Google Sheets API v4
│   │   ├── EmailService.php              # Dual Email Dispatcher & Direct Socket SMTP
│   │   ├── InvoiceService.php            # Secured Paid-Only Tax Invoice Generator
│   │   └── RecaptchaService.php          # Google reCAPTCHA v3 Token Verifier
│   │
│   ├── helpers/                          # Reusable Utilities
│   │   ├── Security.php                  # Sanitization, Tokens, Rate Limiting
│   │   ├── Validator.php                 # Strict Input Validation Rules
│   │   └── Response.php                  # Standardized JSON & File Stream Output
│   │
│   ├── middleware/                       # Security & Request Middleware
│   │   └── SecurityMiddleware.php        # CORS, Headers, Rate Limiting
│   │
│   ├── database/                         # Backend Database Schemas & Migrations
│   │   ├── migrations/                   # Incremental Migration Scripts
│   │   └── schema/                       # Canonical Schema Files
│   │
│   ├── templates/                        # View Templates
│   │   ├── emails/                       # HTML & Plain Text Email Templates
│   │   │   ├── student-confirmation.php  # Student Confirmation with Attached PDF
│   │   │   └── company-notification.php  # Internal Company Lead Alert
│   │   └── invoices/
│   │       └── invoice-template.php      # Pure PHP PDF 1.4 Invoice Generator
│   │
│   ├── storage/                          # Private Application Storage
│   │   ├── .htaccess                     # Direct HTTP Access Denied
│   │   ├── invoices/                     # Generated Tax Invoice PDFs (INV-*.pdf)
│   │   ├── logs/                         # Audit Logs, Sync Logs, Email Logs
│   │   ├── cache/                        # Application Cache
│   │   ├── queue/                        # Resilient Offline Sync Queue Files
│   │   └── private/                      # Private Service Account Certificates
│   │
│   └── console/                          # CLI Commands & Background Jobs
│       └── sync-google-sheets.php        # Offline Sync Queue Retry Runner
│
├── database/                             # Database Schemas & Migrations
│   ├── .htaccess                         # Direct HTTP Access Denied
│   ├── database.sql                      # Complete MySQL InnoDB Production Schema
│   └── migrations/                       # Incremental Migration Scripts
│
├── tests/                                # Automated Test Suites
│   └── test_suite.php                    # 10 Core Scenarios (30/30 Pass)
│
├── .env.example                          # Environment Variables Template
├── .gitignore                            # Git Exclusion Rules
├── composer.json                         # Project Manifest & Autoloading
└── README.md                             # Documentation
```

---

## 2. Environment Configuration (`.env`)

Copy `.env.example` to `.env` and fill in your credentials:

```bash
cp .env.example .env
```

Key variables:
- `APP_URL=https://training.digitalcrazyhub.com`
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`
- `GOOGLE_SHEETS_SPREADSHEET_ID`, `GOOGLE_SERVICE_ACCOUNT_EMAIL`, `GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY`
- `SMTP_HOST=smtp.hostinger.com`, `SMTP_PORT=465`, `SMTP_USER`, `SMTP_PASS`
- `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`
- `CRON_SECRET=your_secret_key`

---

## 3. Hostinger Deployment Instructions

1. **Document Root**:
   - In Hostinger hPanel, set the subdomain document root of `training.digitalcrazyhub.com` to `/public_html/public` (or `/domains/training.digitalcrazyhub.com/public_html/public`).
   - If your host points directly to the repository root, the included root `.htaccess` and `index.php` will automatically rewrite and serve from `public/` securely without exposing `backend/`, `database/`, or `.env`.
2. **Database Setup**:
   - Create a MySQL database in hPanel.
   - Import `database/database.sql` via phpMyAdmin.
3. **Set Permissions**:
   - Ensure `backend/storage/invoices/`, `backend/storage/logs/`, and `backend/storage/queue/` have write permissions (`755` or `775`).
4. **Setup Cron Job**:
   - In hPanel, create a recurring cron job every 10 minutes:
     ```bash
     php /home/username/public_html/backend/console/sync-google-sheets.php
     ```

---

## 4. Testing

Run the automated test suite verifying all 10 core scenarios:

```bash
php tests/test_suite.php
```

All 30 tests verify complete enrollment, MySQL status updates, Google Sheets sync, HMAC signature verification, PDF invoice generation, dual emails, and Thank You verification.
