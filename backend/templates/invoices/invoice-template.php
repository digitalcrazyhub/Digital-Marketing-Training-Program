<?php
/**
 * Digital Crazy Hub - Pure PHP PDF 1.4 Tax Invoice & Admission Pass Generator
 */

declare(strict_types=1);

namespace App\Templates\Invoice;

class InvoicePdfTemplate {
    private array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public static function generate(array $data, string $outputPath): bool {
        $pdf = new self($data);
        $content = $pdf->render();
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
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
        $workshopDate = $this->sanitizeText($this->data['workshop_date'] ?? 'November 5, 2026');
        $workshopTime = $this->sanitizeText($this->data['workshop_time'] ?? '7:00 PM IST');
        $amountPaid = number_format((float)($this->data['amount_paid'] ?? 49.00), 2);
        $invoiceNo = 'INV-' . $uuid;

        // Canvas dimensions (A4: 595.28 x 841.89 points)
        $w = 595.28;
        $h = 841.89;

        // Build drawing streams
        $stream = "";

        // 1. Header Banner Background (Navy #0f172a)
        $stream .= "q\n";
        $stream .= "0.06 0.11 0.18 rg\n";
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

        // Subheader Tagline
        $stream .= "BT\n";
        $stream .= "/F2 9 Tf\n";
        $stream .= "0.58 0.64 0.72 rg\n"; // Slate-400
        $stream .= "40 " . ($h - 72) . " Td (OFFICIAL TAX INVOICE & WORKSHOP ADMISSION PASS) Tj\n";
        $stream .= "ET\n";

        // MSME & Govt Registration Badge in Header
        $stream .= "BT\n";
        $stream .= "/F1 9 Tf\n";
        $stream .= "0.2 0.83 0.6 rg\n"; // Mint Green
        $stream .= "40 " . ($h - 90) . " Td (GOVT OF INDIA MSME REGISTERED TRAINING PROVIDER) Tj\n";
        $stream .= "ET\n";

        // Right side of Header: Invoice Number & Date
        $stream .= "BT\n";
        $stream .= "/F1 11 Tf\n";
        $stream .= "1 1 1 rg\n";
        $stream .= "380 " . ($h - 55) . " Td (INVOICE NO: $invoiceNo) Tj\n";
        $stream .= "/F2 9 Tf\n";
        $stream .= "0.8 0.85 0.9 rg\n";
        $stream .= "380 " . ($h - 72) . " Td (Date & Time: $paidAt) Tj\n";
        $stream .= "380 " . ($h - 87) . " Td (Payment Ref: $paymentId) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 2. Metadata Columns (Billed To & Session Details)
        $yMeta = $h - 220;

        // Billed To Box
        $stream .= "q\n";
        $stream .= "0.97 0.98 0.99 rg\n"; // surface-alt #f8fafc
        $stream .= "0.89 0.91 0.94 RG\n"; // border #e2e8f0
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
        $stream .= "322 " . ($yMeta + 52) . " Td (WORKSHOP DETAILS:) Tj\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "0.06 0.09 0.16 rg\n";
        $stream .= "322 " . ($yMeta + 37) . " Td (Schedule: $workshopDate | $workshopTime) Tj\n";
        $stream .= "/F2 9 Tf\n";
        $stream .= "0.28 0.33 0.41 rg\n";
        $stream .= "322 " . ($yMeta + 23) . " Td (Format: Live on Zoom Interactive) Tj\n";
        $stream .= "322 " . ($yMeta + 10) . " Td (Language: Tamil | Duration: 3 Hours) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 3. Itemized Invoice Table
        $yTable = $h - 265;
        $tableW = 515;

        // Table Header
        $stream .= "q\n";
        $stream .= "0.06 0.09 0.16 rg\n";
        $stream .= "40 $yTable $tableW 26 re f\n";

        $stream .= "BT\n";
        $stream .= "/F1 9 Tf\n";
        $stream .= "1 1 1 rg\n";
        $stream .= "50 " . ($yTable + 9) . " Td (#) Tj\n";
        $stream .= "75 " . ($yTable + 9) . " Td (ITEM DESCRIPTION) Tj\n";
        $stream .= "350 " . ($yTable + 9) . " Td (QTY) Tj\n";
        $stream .= "410 " . ($yTable + 9) . " Td (RATE) Tj\n";
        $stream .= "485 " . ($yTable + 9) . " Td (AMOUNT) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // Row 1: Workshop Pass
        $yRow1 = $yTable - 38;
        $stream .= "q\n";
        $stream .= "0.89 0.91 0.94 RG\n";
        $stream .= "0.5 w\n";
        $stream .= "40 $yRow1 $tableW 38 re S\n";

        $stream .= "BT\n";
        $stream .= "/F1 9 Tf\n";
        $stream .= "0.06 0.09 0.16 rg\n";
        $stream .= "50 " . ($yRow1 + 22) . " Td (1) Tj\n";
        $stream .= "75 " . ($yRow1 + 22) . " Td (Live 3-Hour AI-Integrated Digital Marketing Masterclass) Tj\n";
        $stream .= "/F2 8 Tf\n";
        $stream .= "0.39 0.45 0.55 rg\n";
        $stream .= "75 " . ($yRow1 + 9) . " Td (Includes Live Ad Setup, 50+ Templates, Certificate & WhatsApp VIP Access) Tj\n";

        $stream .= "/F2 9 Tf\n";
        $stream .= "0.06 0.09 0.16 rg\n";
        $stream .= "355 " . ($yRow1 + 22) . " Td (1) Tj\n";
        $stream .= "410 " . ($yRow1 + 22) . " Td (Rs. 41.53) Tj\n";
        $stream .= "485 " . ($yRow1 + 22) . " Td (Rs. 41.53) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // Row 2: Tax Calculation
        $yTax = $yRow1 - 85;
        $stream .= "q\n";
        $stream .= "0.97 0.98 0.99 rg\n";
        $stream .= "330 $yTax 225 80 re f\n";
        $stream .= "0.89 0.91 0.94 RG\n";
        $stream .= "0.5 w\n";
        $stream .= "330 $yTax 225 80 re S\n";

        $stream .= "BT\n";
        $stream .= "/F2 9 Tf\n";
        $stream .= "0.39 0.45 0.55 rg\n";
        $stream .= "342 " . ($yTax + 62) . " Td (Taxable Value:) Tj\n";
        $stream .= "475 " . ($yTax + 62) . " Td (Rs. 41.53) Tj\n";

        $stream .= "342 " . ($yTax + 46) . " Td (CGST (9%):) Tj\n";
        $stream .= "475 " . ($yTax + 46) . " Td (Rs. 3.74) Tj\n";

        $stream .= "342 " . ($yTax + 30) . " Td (SGST (9%):) Tj\n";
        $stream .= "475 " . ($yTax + 30) . " Td (Rs. 3.73) Tj\n";

        // Total
        $stream .= "/F1 11 Tf\n";
        $stream .= "0.06 0.09 0.16 rg\n";
        $stream .= "342 " . ($yTax + 10) . " Td (Total Paid (INR):) Tj\n";
        $stream .= "0.06 0.59 0.41 rg\n"; // Emerald green
        $stream .= "475 " . ($yTax + 10) . " Td (Rs. $amountPaid) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 4. Verification Stamp Box
        $yStamp = $yTax;
        $stream .= "q\n";
        $stream .= "0.94 0.99 0.96 rg\n";
        $stream .= "0.20 0.83 0.60 RG\n";
        $stream .= "1 w\n";
        $stream .= "40 $yStamp 260 80 re B\n";

        $stream .= "BT\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "0.02 0.44 0.29 rg\n";
        $stream .= "55 " . ($yStamp + 58) . " Td (PAYMENT STATUS: PAID & VERIFIED) Tj\n";
        $stream .= "/F2 8 Tf\n";
        $stream .= "0.13 0.33 0.24 rg\n";
        $stream .= "55 " . ($yStamp + 42) . " Td (Authenticated Razorpay Gateway Transaction) Tj\n";
        $stream .= "55 " . ($yStamp + 28) . " Td (Transaction ID: $paymentId) Tj\n";
        $stream .= "55 " . ($yStamp + 14) . " Td (Valid Admission Pass for Workshop) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 5. Instruction / WhatsApp Join Card
        $yCard = $yStamp - 130;
        $stream .= "q\n";
        $stream .= "0.94 0.99 0.96 rg\n";
        $stream .= "0.13 0.77 0.37 RG\n";
        $stream .= "1.5 w\n";
        $stream .= "40 $yCard $tableW 115 re B\n";

        $stream .= "BT\n";
        $stream .= "/F1 12 Tf\n";
        $stream .= "0.08 0.45 0.22 rg\n";
        $stream .= "55 " . ($yCard + 92) . " Td (IMPORTANT: JOIN YOUR VIP WHATSAPP COMMUNITY) Tj\n";

        $stream .= "/F2 9.5 Tf\n";
        $stream .= "0.08 0.35 0.18 rg\n";
        $stream .= "55 " . ($yCard + 72) . " Td (1. Your Zoom live training access link will be delivered exclusively inside this group.) Tj\n";
        $stream .= "55 " . ($yCard + 56) . " Td (2. The 50+ High-Converting Ad Templates & Action Checklist will be shared on session day.) Tj\n";
        $stream .= "55 " . ($yCard + 40) . " Td (3. Keep your Enrollment ID [$uuid] handy during live session attendance.) Tj\n";
        $stream .= "/F1 9.5 Tf\n";
        $stream .= "0.08 0.45 0.22 rg\n";
        $stream .= "55 " . ($yCard + 20) . " Td (Official Community Link: https://training.digitalcrazyhub.com/) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // 6. Security Footer & MSME Certification
        $stream .= "q\n";
        $stream .= "0.89 0.91 0.94 RG\n";
        $stream .= "0.5 w\n";
        $stream .= "40 70 $tableW 0.5 re S\n";

        $stream .= "BT\n";
        $stream .= "/F1 8 Tf\n";
        $stream .= "0.39 0.45 0.55 rg\n";
        $stream .= "40 54 Td (DIGITAL CRAZY HUB | www.digitalcrazyhub.com | training.digitalcrazyhub.com) Tj\n";
        $stream .= "/F2 7.5 Tf\n";
        $stream .= "40 42 Td (Govt. of India Ministry of MSME Registered Enterprise. Computer generated tax invoice - no physical signature required.) Tj\n";
        $stream .= "40 30 Td (Need assistance? Email: support@digitalcrazyhub.com | WhatsApp: +91 90477 00201) Tj\n";
        $stream .= "ET\n";
        $stream .= "Q\n";

        // Compile PDF structure
        return $this->buildPdfString($stream);
    }

    private function sanitizeText(string $str): string {
        $clean = preg_replace('/[^\x20-\x7E]/', '', $str);
        $clean = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], (string)$clean);
        return trim($clean);
    }

    private function buildPdfString(string $contents): string {
        $objects = [];

        // 1: Catalog
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";

        // 2: Pages
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";

        // 3: Page
        $objects[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>";

        // 4: Contents Stream
        $len = strlen($contents);
        $objects[4] = "<< /Length $len >>\nstream\n" . $contents . "\nendstream";

        // 5: Font F1 (Helvetica-Bold)
        $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        // 6: Font F2 (Helvetica)
        $objects[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";

        // Assembly
        $output = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $id => $obj) {
            $offsets[$id] = strlen($output);
            $output .= "$id 0 obj\n" . $obj . "\nendobj\n";
        }

        $xrefOffset = strlen($output);
        $output .= "xref\n";
        $output .= "0 " . (count($objects) + 1) . "\n";
        $output .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $output .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $output .= "trailer\n";
        $output .= "<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $output .= "startxref\n";
        $output .= "$xrefOffset\n";
        $output .= "%%EOF";

        return $output;
    }
}

