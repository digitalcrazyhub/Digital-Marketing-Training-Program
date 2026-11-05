<?php
/**
 * Digital Crazy Hub - Pure PHP Native PDF Invoice Generator
 * Generates valid standard PDF/1.4 documents without external dependencies.
 */

class InvoicePdf {
    private array $data;
    private string $buffer = '';
    private array $offsets = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    public static function generate(array $data, string $outputPath): bool {
        $pdf = new self($data);
        $content = $pdf->render();
        @mkdir(dirname($outputPath), 0755, true);
        return file_put_contents($outputPath, $content) !== false;
    }

    public function render(): string {
        $name = $this->sanitizeText($this->data['name'] ?? 'Learner');
        $email = $this->sanitizeText($this->data['email'] ?? 'support@digitalcrazyhub.com');
        $phone = $this->sanitizeText($this->data['phone'] ?? '');
        $location = $this->sanitizeText($this->data['location'] ?? ($this->data['city'] ?? 'Tamil Nadu, India'));
        $profile = $this->sanitizeText($this->data['current_profile'] ?? ($this->data['profession'] ?? 'Working Professional'));
        $uuid = $this->sanitizeText($this->data['enrollment_id'] ?? ($this->data['uuid'] ?? 'DCH-PASS'));
        $paymentId = $this->sanitizeText($this->data['payment_id'] ?? 'PAID-RZP');
        $paidAt = $this->sanitizeText($this->data['paid_at'] ?? date('d M Y, h:i A'));
        $invoiceNo = 'INV-' . $uuid;

        // Canvas dimensions (A4: 595.28 x 841.89 points)
        $w = 595.28;
        $h = 841.89;

        // Build drawing streams
        $stream = "";

        // 1. Header Banner Background (Navy Gradient Simulation)
        $stream .= "q\n";
        $stream .= "0.06 0.11 0.18 rg\n"; // Dark Navy #0f172a
        $stream .= "0 " . ($h - 130) . " $w 130 re f\n";

        // Accent bottom line on header (Blue #2563eb)
        $stream .= "0.15 0.39 0.92 rg\n";
        $stream .= "0 " . ($h - 134) . " $w 4 re f\n";

        // Header Text: Company Name
        $stream .= "BT\n";
        $stream .= "/F1 22 Tf\n";
        $stream .= "1 1 1 rg\n"; // White
        $stream .= "40 " . ($h - 55) . " Td (DIGITAL CRAZY HUB) Tj\n";
        $stream .= "ET\n";

        $stream .= "BT\n";
        $stream .= "/F2 10 Tf\n";
        $stream .= "0.58 0.77 0.98 rg\n"; // Light blue #93c5fd
        $stream .= "40 " . ($h - 75) . " Td (Govt. of India MSME Registered Training Ecosystem) Tj\n";
        $stream .= "0.8 0.85 0.9 rg\n";
        $stream .= "40 " . ($h - 90) . " Td (Official Practical Training & Educational Services | UDYAM-TN-03-0184920) Tj\n";
        $stream .= "40 " . ($h - 105) . " Td (Website: digitalcrazyhub.com | Support: info@digitalcrazyhub.com) Tj\n";
        $stream .= "ET\n";

        // Top-Right: INVOICE Label
        $stream .= "BT\n";
        $stream .= "/F1 18 Tf\n";
        $stream .= "1 1 1 rg\n";
        $stream .= "410 " . ($h - 55) . " Td (TAX INVOICE) Tj\n";
        $stream .= "ET\n";

        $stream .= "BT\n";
        $stream .= "/F2 9 Tf\n";
        $stream .= "0.9 0.9 0.9 rg\n";
        $stream .= "410 " . ($h - 75) . " Td (Invoice No: $invoiceNo) Tj\n";
        $stream .= "410 " . ($h - 90) . " Td (Date: $paidAt) Tj\n";
        $stream .= "410 " . ($h - 105) . " Td (Status: PAID / CONFIRMED) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 2. Invoice Meta Cards: Bill To & Workshop Details
        $yMeta = $h - 220;

        // Bill To Box
        $stream .= "q\n";
        $stream .= "0.97 0.98 0.99 rg\n"; // Light slate #f8fafc
        $stream .= "0.89 0.91 0.94 RG\n"; // Border #e2e8f0
        $stream .= "1 w\n";
        $stream .= "40 $yMeta 245 70 re B\n";

        $stream .= "BT\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "0.15 0.39 0.92 rg\n";
        $stream .= "52 " . ($yMeta + 52) . " Td (BILLED TO:) Tj\n";
        $stream .= "/F1 11 Tf\n";
        $stream .= "0.06 0.09 0.16 rg\n";
        $stream .= "52 " . ($yMeta + 37) . " Td ($name) Tj\n";
        $stream .= "/F2 9 Tf\n";
        $stream .= "0.28 0.33 0.41 rg\n";
        $stream .= "52 " . ($yMeta + 23) . " Td (Email: $email) Tj\n";
        $stream .= "52 " . ($yMeta + 10) . " Td (Phone: $phone | $location) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // Admission Pass / Session Meta Box
        $stream .= "q\n";
        $stream .= "0.97 0.98 0.99 rg\n";
        $stream .= "0.89 0.91 0.94 RG\n";
        $stream .= "1 w\n";
        $stream .= "310 $yMeta 245 70 re B\n";

        $stream .= "BT\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "0.15 0.39 0.92 rg\n";
        $stream .= "322 " . ($yMeta + 52) . " Td (SESSION ACCESS PASS:) Tj\n";
        $stream .= "/F2 9 Tf\n";
        $stream .= "0.06 0.09 0.16 rg\n";
        $stream .= "322 " . ($yMeta + 37) . " Td (Enrollment ID: $uuid) Tj\n";
        $stream .= "322 " . ($yMeta + 23) . " Td (Payment Ref: $paymentId) Tj\n";
        $stream .= "322 " . ($yMeta + 10) . " Td (Live Workshop: 05 Nov 2026, 7:00 PM IST) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 3. Line Items Table Header
        $yTable = $h - 265;
        $stream .= "q\n";
        $stream .= "0.09 0.15 0.28 rg\n"; // Navy table header
        $stream .= "40 $yTable 515 26 re f\n";

        $stream .= "BT\n";
        $stream .= "/F1 9 Tf\n";
        $stream .= "1 1 1 rg\n";
        $stream .= "52 " . ($yTable + 8) . " Td (#  DESCRIPTION) Tj\n";
        $stream .= "320 " . ($yTable + 8) . " Td (SAC CODE) Tj\n";
        $stream .= "410 " . ($yTable + 8) . " Td (QTY) Tj\n";
        $stream .= "490 " . ($yTable + 8) . " Td (AMOUNT) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // Line Item 1
        $yItem = $yTable - 45;
        $stream .= "q\n";
        $stream .= "0.98 0.99 1.0 rg\n";
        $stream .= "40 $yItem 515 45 re f\n";
        $stream .= "0.89 0.91 0.94 RG\n";
        $stream .= "1 w\n";
        $stream .= "40 $yItem 515 45 re S\n";

        $stream .= "BT\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "0.06 0.09 0.16 rg\n";
        $stream .= "52 " . ($yItem + 26) . " Td (1  AI-Integrated Digital Marketing Paid Workshop) Tj\n";
        $stream .= "/F2 8.5 Tf\n";
        $stream .= "0.4 0.45 0.5 rg\n";
        $stream .= "66 " . ($yItem + 12) . " Td (3 Hours Live Hands-on Masterclass on Zoom with Q&A and VIP Community Pass) Tj\n";
        $stream .= "/F2 9 Tf\n";
        $stream .= "0.06 0.09 0.16 rg\n";
        $stream .= "330 " . ($yItem + 22) . " Td (999293) Tj\n";
        $stream .= "420 " . ($yItem + 22) . " Td (1) Tj\n";
        $stream .= "485 " . ($yItem + 22) . " Td (INR 41.53) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 4. Tax Breakdown & Total Summary
        $ySum = $yItem - 110;
        $stream .= "q\n";
        $stream .= "0.89 0.91 0.94 RG\n";
        $stream .= "1 w\n";
        $stream .= "310 $ySum 245 100 re S\n";

        $stream .= "BT\n";
        $stream .= "/F2 9 Tf\n";
        $stream .= "0.28 0.33 0.41 rg\n";
        $stream .= "325 " . ($ySum + 80) . " Td (Subtotal (Taxable Value):) Tj\n";
        $stream .= "485 " . ($ySum + 80) . " Td (INR 41.53) Tj\n";

        $stream .= "325 " . ($ySum + 62) . " Td (CGST (9%):) Tj\n";
        $stream .= "485 " . ($ySum + 62) . " Td (INR  3.74) Tj\n";

        $stream .= "325 " . ($ySum + 44) . " Td (SGST (9%):) Tj\n";
        $stream .= "485 " . ($ySum + 44) . " Td (INR  3.74) Tj\n";
        $stream .= "ET\n";

        // Total Highlight Row
        $stream .= "0.09 0.15 0.28 rg\n";
        $stream .= "310 " . ($ySum) . " 245 32 re f\n";

        $stream .= "BT\n";
        $stream .= "/F1 11 Tf\n";
        $stream .= "1 1 1 rg\n";
        $stream .= "325 " . ($ySum + 11) . " Td (TOTAL PAID:) Tj\n";
        $stream .= "485 " . ($ySum + 11) . " Td (INR 49.00) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 5. Left Side: Important Workshop Joining Instructions
        $yNotes = $ySum;
        $stream .= "q\n";
        $stream .= "0.95 0.99 0.96 rg\n"; // Light green #f0fdf4
        $stream .= "0.52 0.85 0.6 RG\n";
        $stream .= "1 w\n";
        $stream .= "40 $yNotes 250 100 re B\n";

        $stream .= "BT\n";
        $stream .= "/F1 9.5 Tf\n";
        $stream .= "0.09 0.5 0.24 rg\n";
        $stream .= "52 " . ($yNotes + 80) . " Td (WORKSHOP ATTENDEE PASS) Tj\n";
        $stream .= "/F2 8.5 Tf\n";
        $stream .= "0.08 0.35 0.18 rg\n";
        $stream .= "52 " . ($yNotes + 64) . " Td (1. Your seat is confirmed for the live masterclass.) Tj\n";
        $stream .= "52 " . ($yNotes + 49) . " Td (2. Zoom link is shared inside the VIP WhatsApp group.) Tj\n";
        $stream .= "52 " . ($yNotes + 34) . " Td (3. Keep your Enrollment ID handy during check-in.) Tj\n";
        $stream .= "52 " . ($yNotes + 19) . " Td (4. Digital Completion Certificate will be issued.) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 6. Security & Digital Verification Seal Box
        $ySeal = $yNotes - 80;
        $stream .= "q\n";
        $stream .= "0.96 0.97 0.99 rg\n";
        $stream .= "0.85 0.88 0.92 RG\n";
        $stream .= "1 w\n";
        $stream .= "40 $ySeal 515 65 re B\n";

        $stream .= "BT\n";
        $stream .= "/F1 9.5 Tf\n";
        $stream .= "0.15 0.39 0.92 rg\n";
        $stream .= "52 " . ($ySeal + 46) . " Td (DIGITAL VERIFICATION & PAYMENT ASSURANCE) Tj\n";
        $stream .= "/F2 8.5 Tf\n";
        $stream .= "0.3 0.35 0.45 rg\n";
        $stream .= "52 " . ($ySeal + 30) . " Td (This is an electronically generated official receipt issued under Section 31 of CGST Rules.) Tj\n";
        $stream .= "52 " . ($ySeal + 16) . " Td (Payment captured via Razorpay 256-Bit SSL Gateway. Zero paper waste - Eco Certified.) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 7. Footer
        $stream .= "BT\n";
        $stream .= "/F2 8 Tf\n";
        $stream .= "0.45 0.5 0.58 rg\n";
        $stream .= "140 40 Td (Copyright (C) 2026 Digital Crazy Hub. Registered MSME Govt. of India. All rights reserved.) Tj\n";
        $stream .= "ET\n";

        // Assemble Standard PDF Objects
        $streamLen = strlen($stream);

        $obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $obj3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $w $h] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>\nendobj\n";
        $obj4 = "4 0 obj\n<< /Length $streamLen >>\nstream\n" . $stream . "\nendstream\nendobj\n";
        $obj5 = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
        $obj6 = "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $this->buffer = "%PDF-1.4\n";
        $this->offsets = [];

        $this->addObj(1, $obj1);
        $this->addObj(2, $obj2);
        $this->addObj(3, $obj3);
        $this->addObj(4, $obj4);
        $this->addObj(5, $obj5);
        $this->addObj(6, $obj6);

        // XREF Table
        $xrefOffset = strlen($this->buffer);
        $this->buffer .= "xref\n";
        $this->buffer .= "0 7\n";
        $this->buffer .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 6; $i++) {
            $this->buffer .= sprintf("%010d 00000 n \n", $this->offsets[$i]);
        }

        $this->buffer .= "trailer\n";
        $this->buffer .= "<< /Size 7 /Root 1 0 R >>\n";
        $this->buffer .= "startxref\n";
        $this->buffer .= "$xrefOffset\n";
        $this->buffer .= "%%EOF\n";

        return $this->buffer;
    }

    private function addObj(int $id, string $content): void {
        $this->offsets[$id] = strlen($this->buffer);
        $this->buffer .= $content;
    }

    private function sanitizeText(string $text): string {
        $clean = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $clean);
    }
}

