# Digital Crazy Hub – ₹49 Digital Marketing Training Program
## Production Master Architecture & Integration Documentation

**Production Domain:** [https://training.digitalcrazyhub.com/](https://training.digitalcrazyhub.com/)  
**Core Technologies:** Core PHP, MySQL/MariaDB (PDO), Razorpay Payment Gateway, Google Sheets API v4 (Service Account OAuth2 JWT RS256), SMTP / PHPMailer, Dompdf / Native PDF 1.4 Generator, Vanilla JavaScript (ES6+), HTML5, CSS3.

---

## 1. Centralized Backend Directory Structure

All backend logic is isolated inside the `/backend/` directory:

```text
training-digitalcrazyhub/
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── video/
│
├── backend/
│   ├── api/
│   │   ├── submit-enrollment.php      # Form submission, validation, UNPAID DB row, Sheet append, RZP order
│   │   ├── create-enrollment.php      # Backward-compatible alias for submit-enrollment
│   │   ├── create-order.php           # Razorpay order generation (₹49 / 4900 paise)
│   │   ├── verify-payment.php         # Signature verification, PAID DB update, Sheet sync, Invoice, Emails
│   │   ├── payment-status.php         # Server-side verification for Thank-You page
│   │   ├── verify-status.php          # Backward-compatible alias for payment-status
│   │   ├── download-invoice.php       # Secure PDF download streaming endpoint
│   │   ├── webhook.php                # Razorpay webhook listener (payment.captured, order.paid)
│   │   └── .htaccess                  # Grants API execution, prevents indexing
│   │
│   ├── config/
│   │   ├── app.php                    # Workshop schedule, dates, pricing, reCAPTCHA settings
│   │   ├── config.php                 # Unified configuration entry point
│   │   ├── config.sample.php          # PHP array configuration sample
│   │   ├── database.php               # PDO database connection manager & fallback
│   │   ├── google-sheets.php          # Google Sheets 10-column schema & colors
│   │   ├── razorpay.php               # Razorpay credentials and webhook secret
│   │   ├── payment.php                # Payment config alias
│   │   ├── mail.php                   # SMTP configuration
│   │   ├── email.php                  # Email config alias
│   │   └── .htaccess                  # Strict deny from all
│   │
│   ├── services/
│   │   ├── EnrollmentService.php      # Complete lifecycle coordinator & state transitions
│   │   ├── PaymentService.php         # Razorpay checkout & HMAC-SHA256 signature verifier
│   │   ├── RazorpayService.php        # Core Razorpay API service
│   │   ├── GoogleSheetsService.php    # Pure PHP RS256 OAuth2 Google Sheets API v4 client
│   │   ├── InvoiceService.php         # Tax Invoice generator (strict PAID-only guard)
│   │   ├── EmailService.php           # SMTP delivery with multipart PDF attachment
│   │   ├── CaptchaService.php         # Google reCAPTCHA v3 verification
│   │   └── RecaptchaService.php       # Core reCAPTCHA handler
│   │
│   ├── controllers/
│   │   ├── EnrollmentController.php   # Handles enrollment & status endpoints
│   │   ├── PaymentController.php      # Handles order & verification endpoints
│   │   └── WebhookController.php      # Handles webhook processing
│   │
│   ├── database/
│   │   ├── migrations/
│   │   │   └── 001_create_enrollments_table.sql
│   │   └── schema/
│   │       └── database.sql           # Complete MySQL production schema
│   │
│   ├── helpers/
│   │   ├── Response.php               # JSON and file streaming helpers
│   │   ├── Security.php               # Input sanitizer, Indian phone cleaner, ID generator
│   │   └── Validator.php              # Server-side validation rules
│   │
│   ├── middleware/
│   │   └── SecurityMiddleware.php     # CORS, security headers, rate limiting
│   │
│   ├── storage/
│   │   ├── invoices/                  # Generated PDF Tax Invoices (INV-DCH-2026-XXXXXX.pdf)
│   │   ├── logs/                      # Audit logs, payment logs, and .eml archives
│   │   ├── queue/                     # Offline sync queue for resilient fallbacks
│   │   └── .htaccess                  # Strict access denial
│   │
│   ├── templates/
│   │   ├── invoices/
│   │   │   └── invoice-template.php   # PDF 1.4 A4 Tax Invoice template with MSME badge
│   │   ├── emails/
│   │   │   ├── student-confirmation.php # Customer confirmation email with PDF attached
│   │   │   └── company-notification.php # Company lead notification to info@digitalcrazyhub.com
│   │   ├── invoice-template.php       # Direct template alias
│   │   ├── payment-success-email.php  # Direct template alias
│   │   └── lead-notification-email.php# Direct template alias
│   │
│   ├── private/
│   │   └── .htaccess                  # Secure private directory
│   │
│   ├── tests/
│   │   ├── verify_flow.php            # 30-assertion automated test suite
│   │   └── test_endpoints_syntax.php  # PHP syntax linter for all API endpoints
│   │
│   ├── .htaccess                      # Root backend security wrapper
│   ├── bootstrap.php                  # PSR-4 autoloader, environment parser, class map
│   └── router.php                     # Centralized API dispatcher
│
├── index.html                         # Landing page with enrollment modal
├── payment-page.html                  # Razorpay checkout page with lightbox preview
├── thank-you.html                     # Authenticated confirmation & invoice download page
├── payment-failed.html                # Payment error & retry page
├── 404.html                           # Custom 404 error page
├── .htaccess                          # Web server rewrite rules & security headers
├── .env.example                       # Environment configuration template
└── README.md
```

---

## 2. Environment Setup (`.env`)

1. Copy `.env.example` to `.env` in the project root:
   ```bash
   cp .env.example .env
   ```
2. Populate the required credentials:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://training.digitalcrazyhub.com

   # Workshop Details
   WORKSHOP_TITLE="₹49 AI-Integrated Digital Marketing Paid Workshop"
   WORKSHOP_DATE="November 24, 2026"
   WORKSHOP_DAY="Tuesday"
   WORKSHOP_TIME="7:00 PM IST"
   WORKSHOP_PRICE=49.00
   WORKSHOP_AMOUNT_PAISE=4900

   # MySQL Database
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=dch_workshop
   DB_USER=your_db_username
   DB_PASS=your_db_password

   # Razorpay API
   RAZORPAY_KEY_ID=rzp_live_xxxxxxxxxxxxxx
   RAZORPAY_KEY_SECRET=your_razorpay_secret
   RAZORPAY_WEBHOOK_SECRET=your_webhook_secret

   # Google Sheets API Service Account (OAuth2 JWT)
   GOOGLE_SHEETS_SPREADSHEET_ID=1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   GOOGLE_SHEETS_SHEET_NAME=Enrollments
   GOOGLE_SERVICE_ACCOUNT_EMAIL=dch-sheets@your-project.iam.gserviceaccount.com
   GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC...\n-----END PRIVATE KEY-----\n"

   # SMTP Email
   SMTP_HOST=smtp.hostinger.com
   SMTP_PORT=465
   SMTP_SECURE=ssl
   SMTP_USER=info@digitalcrazyhub.com
   SMTP_PASS=your_email_password
   COMPANY_NOTIFICATION_EMAIL=info@digitalcrazyhub.com

   # Google reCAPTCHA v3
   RECAPTCHA_SITE_KEY=your_site_key
   RECAPTCHA_SECRET_KEY=your_secret_key
   ```

---

## 3. MySQL Database Setup

1. Open phpMyAdmin or your MySQL CLI client on Hostinger / cPanel / localhost.
2. Create the database:
   ```sql
   CREATE DATABASE IF NOT EXISTS `dch_workshop` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE `dch_workshop`;
   ```
3. Import the production schema from:
   `backend/database/schema/database.sql`
4. The schema creates:
   - `enrollments`: Primary enrollment table with unique indexed `enrollment_id` (`DCH-2026-XXXXXX`), `amount_paid_status ENUM('UNPAID', 'PAID') DEFAULT 'UNPAID'`, `payment_id`, `invoice_number`, `invoice_url`, `workshop_date`, `workshop_time`.
   - `payment_logs`: Audit trail for checkout events, webhook payloads, IP addresses, and user agents.
   - `sync_queue`: Background queue for resilient Google Sheets synchronization retries.

---

## 4. Google Sheets API v4 Setup

The backend utilizes **pure PHP RS256 signed JWTs** via OpenSSL. No heavy Composer packages are required.

### 4.1. Google Cloud Console Configuration
1. Open the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a project named **Digital Crazy Hub**.
3. Enable the **Google Sheets API** under **APIs & Services > Library**.
4. Go to **APIs & Services > Credentials**, click **Create Credentials > Service Account**.
5. Give the Service Account a name (e.g., `dch-sheets-sync`) and click **Create**.
6. Open the newly created Service Account, navigate to the **Keys** tab, click **Add Key > Create new key > JSON**, and download the key file.
7. Open the JSON file:
   - Copy `client_email` to `GOOGLE_SERVICE_ACCOUNT_EMAIL` in `.env`.
   - Copy `private_key` to `GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY` in `.env`.

### 4.2. Google Sheet Preparation
1. Create a new Google Sheet and rename the active tab to `Enrollments`.
2. Copy the Spreadsheet ID from the URL (`https://docs.google.com/spreadsheets/d/{SPREADSHEET_ID}/edit`) to `GOOGLE_SHEETS_SPREADSHEET_ID` in `.env`.
3. Click the **Share** button on the Google Sheet and add the Service Account email (`client_email`) with the **Editor** role.

### 4.3. Exact 10-Column Architecture (Columns A through J)
The backend automatically initializes and maintains the following exact 10 columns:

| Column | Name | Description | Form Submit Value | Payment Success Value |
| :---: | :--- | :--- | :--- | :--- |
| **A** | `Enrollment ID` | Unique ID (`DCH-2026-XXXXXX`) | Generated format | Unchanged |
| **B** | `Name` | Full Name | User input | Unchanged |
| **C** | `Email` | Email Address | User input | Unchanged |
| **D** | `Phone Number` | 10-digit Indian Mobile | 10 digits | Unchanged |
| **E** | `Location` | City / State | User input | Unchanged |
| **F** | `Current Profile` | Profession | User input | Unchanged |
| **G** | `Payment ID` | Razorpay Payment ID | *Blank* (`""`) | `pay_xxxxxxxxxxxxxx` |
| **H** | `Workshop Date` | Workshop Date | `November 24, 2026` | Unchanged |
| **I** | `Workshop Time` | Workshop Time | `7:00 PM IST` | Unchanged |
| **J** | `Amount Paid Status` | Payment Status | `UNPAID` | `PAID` |

### 4.4. Conditional Formatting Rules
The backend auto-applies Google Sheets conditional formatting on **Column J**:
- **`PAID`**: Light Green background (`#D4EDDA`), Dark Green text (`#155724`), Bold.
- **`UNPAID`**: Light Red background (`#F8D7DA`), Dark Red text (`#721C24`), Bold.

---

## 5. Razorpay Payment Gateway & Webhook Setup

### 5.1. API Credentials
1. Log in to the [Razorpay Dashboard](https://dashboard.razorpay.com/).
2. Navigate to **Settings > API Keys**.
3. Generate your Key ID and Key Secret. Add them to `.env`.

### 5.2. Webhook Setup
1. In the Razorpay Dashboard, navigate to **Settings > Webhooks > Add New Webhook**.
2. **Webhook URL:** `https://training.digitalcrazyhub.com/api/webhook.php`
3. **Secret:** Choose a strong secret string and add it to `RAZORPAY_WEBHOOK_SECRET` in `.env`.
4. **Active Events:**
   - `payment.captured`
   - `order.paid`
   - `payment.failed`
5. Click **Create Webhook**.

### 5.3. Idempotency & Signature Verification
- Inbound webhooks are validated with `hash_hmac('sha256', $payload, $webhookSecret)`.
- If an enrollment is already marked `PAID`, duplicate webhook events are logged and ignored without reprocessing.
- If an enrollment is `UNPAID`, the webhook transitions the status to `PAID`, updates Google Sheets, generates the invoice PDF, and dispatches confirmation emails.

---

## 6. Automated Invoice PDF & Dual Email System

### 6.1. Strict Unpaid Guard
**Strict Business Rule:** Invoices and emails **MUST NEVER** trigger for unpaid enrollments. They trigger strictly after successful Razorpay payment verification or webhook capture.

### 6.2. Tax Invoice PDF Generation
- Saved to: `/backend/storage/invoices/INV-DCH-2026-XXXXXX.pdf`
- Includes: Digital Crazy Hub logo & branding, MSME & Govt of India registration badge, Invoice Number, Payment ID, Order ID, Student Details, Course Name, Workshop Date (`November 24, 2026 @ 7:00 PM IST`), Amount Paid (`₹49.00`), and authorized signature badge.

### 6.3. Dual Email Notifications (SMTP)
1. **Email 1: Student Confirmation Email**
   - Recipient: Student's submitted email.
   - Subject: `Enrollment Confirmed – Digital Marketing Training Program | Digital Crazy Hub`
   - Content: Congratulatory message, workshop date & time, Zoom platform instructions, VIP WhatsApp Community link, and the generated PDF Invoice attached.
2. **Email 2: Company Lead & Payment Notification**
   - Recipient: `info@digitalcrazyhub.com`
   - Subject: `New Paid Enrollment – [Student Name] – DCH-2026-XXXXXX`
   - Content: Complete student lead breakdown, Order ID, Payment ID, amount paid (₹49), and timestamp.

---

## 7. Thank-You Page Security Verification

The Thank-You page (`thank-you.html`) performs authenticated server-side verification:
1. URL includes: `thank-you.html?enrollment_id=DCH-2026-XXXXXX&token=xxxxxxxxxxxxxxxx`
2. Frontend queries: `/api/payment-status.php?enrollment_id=...&token=...`
3. Backend validates that `token` matches `verification_token` and `amount_paid_status === 'PAID'`.
4. If verified, the green confirmation badge, dynamic student details, VIP WhatsApp button, and **Download Invoice (PDF)** button are displayed.
5. If unverified or unpaid, access is denied with a security warning.

---

## 8. Verification & Test Suite

Run the comprehensive 30-assertion test suite from the terminal:

```bash
php backend/tests/verify_flow.php
```

All 30 assertions test:
1. `AppConfig` and class maps load properly.
2. Workshop price (₹49 / 4900 paise) and date (`November 24, 2026`).
3. Google Sheets 10-column spec (`A` to `J`, Column J = `Amount Paid Status`).
4. Phone sanitization (10-digit Indian numbers `[6-9]\d{9}`).
5. Unique Enrollment ID format (`DCH-2026-XXXXXX`).
6. Initial form submission (`amount_paid_status = 'UNPAID'`, `payment_id = NULL`).
7. **Strict Rule Guard:** Unpaid enrollment throws an exception if invoice generation is attempted.
8. Payment signature verification via HMAC-SHA256 and status transition to `PAID`.
9. Automated PDF generation on disk (> 500 bytes).
10. Idempotency test (duplicate payment attempt safely returns `already_processed`).
11. Webhook processing and HMAC verification.
12. Google Sheets exact 10-column data mapping.

---

## 9. Web Server & Deployment (.htaccess)

The root `.htaccess` routes requests directly to `/backend/api/` while preventing public access to backend internals:

```apache
# API Routing
RewriteRule ^api/submit-enrollment(?:\.php)?$ backend/api/submit-enrollment.php [QSA,L]
RewriteRule ^api/create-enrollment(?:\.php)?$ backend/api/submit-enrollment.php [QSA,L]
RewriteRule ^api/create-order(?:\.php)?$ backend/api/create-order.php [QSA,L]
RewriteRule ^api/verify-payment(?:\.php)?$ backend/api/verify-payment.php [QSA,L]
RewriteRule ^api/payment-status(?:\.php)?$ backend/api/payment-status.php [QSA,L]
RewriteRule ^api/verify-status(?:\.php)?$ backend/api/payment-status.php [QSA,L]
RewriteRule ^api/download-invoice(?:\.php)?$ backend/api/download-invoice.php [QSA,L]
RewriteRule ^api/webhook(?:\.php)?$ backend/api/webhook.php [QSA,L]
RewriteRule ^api/(.*)$ backend/api/$1 [QSA,L]

# Block direct browser access to sensitive backend directories
RewriteRule ^backend/(config|database|helpers|middleware|services|storage|templates|private)/ - [F,L]
RewriteRule ^backend/(\.env.*|composer.*|package.*|README.*|phpunit.*) - [F,L]
```
