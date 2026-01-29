<?php
session_start();
require_once('../../fpdf/fpdf.php');
include "../../config/database.php";

/* Proteksi admin */
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

// Cek parameter ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID transaksi tidak valid");
}

$transaction_id = (int) $_GET['id'];

// Ambil data transaksi dengan prepared statement
$stmt = $conn->prepare("
    SELECT t.*, u.username
    FROM transactions t
    JOIN users u ON t.id_user = u.id_user
    WHERE t.id_transaction = ?
");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$transaction_result = $stmt->get_result();

if ($transaction_result->num_rows == 0) {
    die("Transaksi tidak ditemukan");
}

$transaction = $transaction_result->fetch_assoc();
$stmt->close();

// Ambil detail transaksi
$stmt = $conn->prepare("
    SELECT td.*, g.title, g.price
    FROM transaction_details td
    JOIN games g ON td.id_game = g.id_game
    WHERE td.id_transaction = ?
    ORDER BY td.id_detail
");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$details_result = $stmt->get_result();

// Buat PDF class khusus untuk invoice
class InvoicePDF extends FPDF
{
    private $store_info = [
        'name' => "Midnight Play",
        'email' => "info@midnightplay.com"
    ];

    private $colors = [
        'primary' => [59, 130, 246],
        'secondary' => [100, 100, 100],
        'success' => [25, 135, 84],
        'danger' => [220, 53, 69],
        'light' => [240, 240, 240],
        'gray' => [200, 200, 200]
    ];

    function Header()
    {
        // Logo atau store name
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColorArray($this->colors['primary']);
        $this->Cell(0, 10, $this->store_info['name'], 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->SetTextColorArray($this->colors['secondary']);
        $this->Cell(0, 5, $this->store_info['email'], 0, 1, 'C');

        // Line separator
        $this->SetDrawColorArray($this->colors['gray']);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY() + 5, 200, $this->GetY() + 5);
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColorArray($this->colors['secondary']);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function SetTextColorArray($rgb)
    {
        $this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
    }

    function SetDrawColorArray($rgb)
    {
        $this->SetDrawColor($rgb[0], $rgb[1], $rgb[2]);
    }

    function SetFillColorArray($rgb)
    {
        $this->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
    }

    function InvoiceTitle($transaction_id)
    {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, 'INVOICE', 0, 1, 'C');

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'No: INV-' . str_pad($transaction_id, 6, '0', STR_PAD_LEFT), 0, 1, 'C');

        $this->Ln(5);
    }

    function CustomerInfo($transaction)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 8, 'INFORMASI CUSTOMER', 0, 1);
        $this->SetLineWidth(0.3);
        $this->SetDrawColorArray($this->colors['gray']);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(2);

        $this->SetFont('Arial', '', 10);

        $info = [
            'Customer ID:' => '#U' . str_pad($transaction['id_user'], 4, '0', STR_PAD_LEFT),
            'Username:' => $transaction['username'],
            'Tanggal Transaksi:' => date('d/m/Y H:i', strtotime($transaction['transaction_date']))
        ];

        foreach ($info as $label => $value) {
            $this->Cell(45, 6, $label, 0, 0);
            $this->Cell(0, 6, $value, 0, 1);
        }

        $this->Ln(10);
    }

    function TransactionDetails($details_result, $transaction)
    {
        // Table header
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColorArray($this->colors['primary']);
        $this->SetTextColor(255);
        $this->SetDrawColorArray($this->colors['gray']);
        $this->SetLineWidth(0.3);

        // Column widths
        $w = [10, 80, 25, 25, 25, 25];

        // Header
        $headers = ['No', 'Nama Game', 'Harga', 'Diskon', 'Subtotal'];
        $this->Cell($w[0], 8, $headers[0], 1, 0, 'C', true);
        $this->Cell($w[1], 8, $headers[1], 1, 0, 'C', true);
        $this->Cell($w[2], 8, $headers[2], 1, 0, 'C', true);
        $this->Cell($w[4], 8, $headers[3], 1, 0, 'C', true);
        $this->Cell($w[5], 8, $headers[4], 1, 0, 'C', true);
        $this->Ln();

        // Table data
        $this->SetFillColor(255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);

        $counter = 1;
        $total_amount = 0;

        $details_result->data_seek(0);
        while ($row = $details_result->fetch_assoc()) {
            $subtotal = $row['price'];
            $total_amount += $subtotal;

            // Alternate row background
            $fill = ($counter % 2 == 0);

            $this->Cell($w[0], 6, $counter, 1, 0, 'C', $fill);
            $this->Cell($w[1], 6, $this->truncateText($row['title'], 40), 1, 0, 'L', $fill);
            $this->Cell($w[2], 6, $this->formatCurrency($row['price']), 1, 0, 'R', $fill);
            $this->Cell($w[4], 6, '-', 1, 0, 'C', $fill);
            $this->Cell($w[5], 6, $this->formatCurrency($subtotal), 1, 0, 'R', $fill);
            $this->Ln();

            $counter++;
        }

        // Empty row for spacing
        $this->Cell(array_sum($w), 2, '', 'LR', 1);

        // Summary
        $this->SetFont('Arial', 'B', 10);

        $items_count = $counter - 1; // Hitung jumlah item

        $summary = [
            'Total Items:' => $items_count,
            'Subtotal:' => $total_amount,
            'PPN (0%):' => 0
        ];

        $first = true;
        foreach ($summary as $label => $value) {
            $this->Cell($w[0] + $w[1] + $w[2] + $w[3] + $w[4], 8, $label, 1, 0, 'R');

            if ($first) {
                $this->Cell($w[5], 8, $value, 1, 1, 'C');
                $first = false;
            } else {
                $this->Cell($w[5], 8, $this->formatCurrency($value), 1, 1, 'R');
            }
        }

        // Grand Total
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColorArray($this->colors['light']);
        $this->Cell($w[0] + $w[1] + $w[2] + $w[3] + $w[4], 10, 'GRAND TOTAL:', 1, 0, 'R', true);
        $this->SetTextColorArray($this->colors['danger']);
        $this->Cell($w[5], 10, 'Rp ' . $this->formatCurrency($transaction['total_price']), 1, 1, 'R', true);

        $this->Ln(10);

        return $items_count;
    }

    function truncateText($text, $length)
    {
        if (strlen($text) > $length) {
            return substr($text, 0, $length - 3) . '...';
        }
        return $text;
    }

    function formatCurrency($amount)
    {
        return number_format($amount, 0, ',', '.');
    }

    function PaymentInfo()
    {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 8, 'INFORMASI PEMBAYARAN', 0, 1);
        $this->SetLineWidth(0.3);
        $this->SetDrawColorArray($this->colors['gray']);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(2);

        $this->SetFont('Arial', '', 9);

        $payment_info = [
            'Metode Pembayaran:' => 'Midnight Play Digital Payment',
            'Status:' => 'LUNAS',
            'Tipe:' => 'Digital Goods (Game License)'
        ];

        foreach ($payment_info as $label => $value) {
            $this->Cell(0, 6, $label . ' ' . $value, 0, 1);
        }

        $this->Ln(5);
    }

    function NotesSection()
    {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 8, 'CATATAN DAN KETENTUAN', 0, 1);
        $this->SetLineWidth(0.3);
        $this->SetDrawColorArray($this->colors['gray']);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(2);

        $this->SetFont('Arial', '', 9);

        $notes = [
            "Invoice ini sah sebagai bukti pembelian digital.",
            "Game yang telah dibeli tidak dapat dikembalikan (non-refundable).",
            "Lisensi game berlaku selamanya dan terikat pada akun pembeli.",
            "Untuk bantuan teknis, hubungi support@midnightplay.com.",
            "Invoice ini dibuat secara otomatis oleh sistem."
        ];

        foreach ($notes as $index => $note) {
            $this->Cell(5, 5, '', 0, 0);
            $this->MultiCell(0, 5, ($index + 1) . ". " . $note);
        }

        $this->Ln(5);
    }

    function ThankYouSection()
    {
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColorArray($this->colors['secondary']);
        $this->Cell(0, 8, 'Terima kasih telah berbelanja di Midnight Play Store!', 0, 1, 'C');
        $this->Cell(0, 5, 'Game Anda telah ditambahkan ke library akun.', 0, 1, 'C');
    }

    function AdminStamp()
    {
        $this->Ln(10);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColorArray($this->colors['secondary']);
        $this->Cell(0, 5, 'Dicetak oleh Admin pada: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
        $this->Cell(0, 5, 'Dokumen resmi - Untuk keperluan administrasi', 0, 1, 'R');
    }
}

// Buat PDF
$pdf = new InvoicePDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();

// Judul Invoice
$pdf->InvoiceTitle($transaction['id_transaction']);

// Informasi Customer
$pdf->CustomerInfo($transaction);

// Detail Transaksi
$total_items = $pdf->TransactionDetails($details_result, $transaction);

// Informasi Pembayaran
$pdf->PaymentInfo();

// Catatan
$pdf->NotesSection();

// Thank you message
$pdf->ThankYouSection();

// Admin stamp
$pdf->AdminStamp();

// Output PDF
$filename = 'Invoice_' . $transaction['id_transaction'] . '_' . date('Ymd_His') . '.pdf';
$pdf->Output('I', $filename);

// Tutup koneksi
$stmt->close();
$conn->close();
exit(); // Pastikan tidak ada output lain setelah PDF