<?php
// generate_pdf.php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

// Include FPDF
require('../../fpdf/fpdf.php'); // Sesuaikan path ke fpdf.php Anda

// Query untuk mendapatkan data transaksi
$transactions = mysqli_query($conn, "
    SELECT t.id_transaction, t.transaction_date, t.total_price, u.username
    FROM transactions t
    JOIN users u ON t.id_user = u.id_user
    ORDER BY t.transaction_date DESC
");

// Query untuk statistik
$totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total_transactions, SUM(total_price) as total_revenue FROM transactions");
$stats = mysqli_fetch_assoc($totalQuery);

// Buat class PDF kustom dengan header dan footer
class PDF extends FPDF
{
    // Page header
    function Header()
    {
        // Logo atau judul
        $this->SetFont('Arial', 'B', 20);
        $this->Cell(0, 10, 'LAPORAN TRANSAKSI', 0, 1, 'C');
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 5, 'MIDNIGHT PLAY - Admin Report', 0, 1, 'C');
        $this->Ln(10);

        // Garis pemisah
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(10);
    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Dibuat pada: ' . date('d/m/Y H:i'), 0, 0, 'R');
    }

    // Tabel dengan styling
    function ImprovedTable($header, $data)
    {
        // Colors, line width and bold font
        $this->SetFillColor(59, 89, 152); // Biru Steam-like
        $this->SetTextColor(255);
        $this->SetDrawColor(59, 89, 152);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B');

        // Header
        $w = array(25, 40, 50, 40, 35);
        for ($i = 0; $i < count($header); $i++)
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        $this->Ln();

        // Color and font restoration
        $this->SetFillColor(240, 248, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 10);

        // Data
        $fill = false;
        $total = 0;
        foreach ($data as $row) {
            $this->Cell($w[0], 6, '#' . $row['id_transaction'], 'LR', 0, 'C', $fill);
            $this->Cell($w[1], 6, $row['username'], 'LR', 0, 'L', $fill);
            $this->Cell($w[2], 6, date('d/m/Y H:i', strtotime($row['transaction_date'])), 'LR', 0, 'C', $fill);
            $this->Cell($w[3], 6, 'Rp ' . number_format($row['total_price'], 0, ',', '.'), 'LR', 0, 'R', $fill);

            // Status berdasarkan total harga (contoh)
            $status = $row['total_price'] > 100000 ? 'Besar' : 'Standar';
            $this->Cell($w[4], 6, $status, 'LR', 0, 'C', $fill);
            $this->Ln();
            $fill = !$fill;
            $total += $row['total_price'];
        }

        // Closing line
        $this->Cell(array_sum($w), 0, '', 'T');

        // Total
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Total Pendapatan: Rp ' . number_format($total, 0, ',', '.'), 0, 1, 'R');
    }
}

// Buat instance PDF
$pdf = new PDF();
$pdf->AliasNbPages(); // Untuk nomor halaman total
$pdf->AddPage('L'); // Landscape mode

// Informasi laporan
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Periode: Semua Transaksi', 0, 1);
$pdf->Cell(0, 8, 'Admin: ' . $_SESSION['username'], 0, 1);
$pdf->Ln(5);

// Statistik
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Ringkasan Statistik:', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(100, 8, 'Total Transaksi: ' . ($stats['total_transactions'] ?? 0) . ' transaksi', 0, 0);
$pdf->Cell(100, 8, 'Total Pendapatan: Rp ' . number_format($stats['total_revenue'] ?? 0, 0, ',', '.'), 0, 1);
$pdf->Ln(10);

// Header tabel
$header = array('ID Transaksi', 'Username', 'Tanggal Transaksi', 'Total Harga', 'Kategori');
$data = array();

// Ambil data dari query
while ($row = mysqli_fetch_assoc($transactions)) {
    $data[] = $row;
}

// Tampilkan tabel
$pdf->ImprovedTable($header, $data);

// Tambah halaman baru untuk ringkasan
$pdf->AddPage('P'); // Portrait mode
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'RINGKASAN LAPORAN', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);
$pdf->MultiCell(0, 8, "Laporan ini berisi semua transaksi yang dilakukan di platform MIDNIGHT PLAY. Total terdapat " . ($stats['total_transactions'] ?? 0) . " transaksi dengan pendapatan kumulatif sebesar Rp " . number_format($stats['total_revenue'] ?? 0, 0, ',', '.') . ".", 0, 'J');

$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Detail Informasi:', 0, 1);
$pdf->SetFont('Arial', '', 11);

$pdf->Cell(60, 8, 'Tanggal Generate:', 0, 0);
$pdf->Cell(0, 8, date('d/m/Y H:i:s'), 0, 1);

$pdf->Cell(60, 8, 'Jumlah Data:', 0, 0);
$pdf->Cell(0, 8, count($data) . ' transaksi', 0, 1);

$pdf->Cell(60, 8, 'Format File:', 0, 0);
$pdf->Cell(0, 8, 'PDF Report', 0, 1);

// Output PDF
$pdf->Output('I', 'Laporan_Transaksi_' . date('Y-m-d') . '.pdf');
?>