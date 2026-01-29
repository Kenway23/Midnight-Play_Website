<?php
session_start();
include "../config/database.php";
require "../fpdf/fpdf.php";

if (!isset($_SESSION['login'])) {
    header("Location: ../auth/auth_login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$username = $_SESSION['username'];

// Ambil semua transaksi user
$transactions = mysqli_query($conn, "
    SELECT t.*, u.username
    FROM transactions t
    JOIN users u ON t.id_user = u.id_user
    WHERE t.id_user = $id_user
    ORDER BY t.transaction_date DESC
");

// Hitung statistik
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(total_price) as total_spent,
        MIN(transaction_date) as first_purchase,
        MAX(transaction_date) as last_purchase
    FROM transactions 
    WHERE id_user = $id_user
");
$stats = mysqli_fetch_assoc($stats_query);

// Create PDF dengan orientasi landscape untuk data yang banyak
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);

// Header dengan background color
$pdf->SetFillColor(41, 128, 185); // Warna biru Midnight Play
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(0, 15, 'MIDNIGHT PLAY - PURCHASE HISTORY REPORT', 0, 1, 'C', true);
$pdf->Ln(5);

// User Information
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'USER INFORMATION', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 6, 'Username:', 0, 0);
$pdf->Cell(0, 6, $username, 0, 1);
$pdf->Cell(60, 6, 'User ID:', 0, 0);
$pdf->Cell(0, 6, $id_user, 0, 1);
$pdf->Cell(60, 6, 'Report Date:', 0, 0);
$pdf->Cell(0, 6, date('F d, Y, H:i:s'), 0, 1);
$pdf->Ln(5);

// Statistics Summary
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'PURCHASE SUMMARY', 0, 1);
$pdf->SetFont('Arial', '', 10);

// Tabel statistik
$pdf->SetFillColor(240, 248, 255);
$pdf->Cell(70, 7, 'Total Transactions', 1, 0, 'L', true);
$pdf->Cell(0, 7, number_format($stats['total_transactions'] ?? 0), 1, 1);

$pdf->Cell(70, 7, 'Total Amount Spent', 1, 0, 'L', true);
$pdf->Cell(0, 7, 'Rp ' . number_format($stats['total_spent'] ?? 0, 0, ',', '.'), 1, 1);

$pdf->Cell(70, 7, 'First Purchase Date', 1, 0, 'L', true);
$pdf->Cell(0, 7, date('F d, Y', strtotime($stats['first_purchase'] ?? date('Y-m-d'))), 1, 1);

$pdf->Cell(70, 7, 'Last Purchase Date', 1, 0, 'L', true);
$pdf->Cell(0, 7, date('F d, Y', strtotime($stats['last_purchase'] ?? date('Y-m-d'))), 1, 1);

$pdf->Ln(10);

// Transactions Header
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetFillColor(41, 128, 185);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'TRANSACTION DETAILS', 0, 1, 'C', true);
$pdf->Ln(5);

if (mysqli_num_rows($transactions) > 0) {
    // Header tabel
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(220, 230, 241);

    $pdf->Cell(15, 8, 'No', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Invoice ID', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Date & Time', 1, 0, 'C', true);
    $pdf->Cell(70, 8, 'Games Purchased', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Items', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Total Amount', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    $counter = 1;

    mysqli_data_seek($transactions, 0);
    while ($trx = mysqli_fetch_assoc($transactions)) {
        // Ambil detail games untuk transaksi ini
        $details = mysqli_query($conn, "
            SELECT g.title, td.price
            FROM transaction_details td
            JOIN games g ON td.id_game = g.id_game
            WHERE td.id_transaction = {$trx['id_transaction']}
        ");

        // Hitung jumlah game
        $total_items = mysqli_num_rows($details);

        // Ambil nama game (maksimal 3 game untuk ditampilkan)
        $game_titles = [];
        mysqli_data_seek($details, 0);
        while ($game = mysqli_fetch_assoc($details)) {
            $game_titles[] = $game['title'];
            if (count($game_titles) >= 3) {
                $game_titles[] = '...';
                break;
            }
        }

        $games_text = implode(', ', $game_titles);
        if (strlen($games_text) > 60) {
            $games_text = substr($games_text, 0, 57) . '...';
        }

        // Baris data
        $pdf->Cell(15, 8, $counter, 1, 0, 'C');
        $pdf->Cell(30, 8, '#' . $trx['id_transaction'], 1, 0, 'C');
        $pdf->Cell(50, 8, date('M d, Y', strtotime($trx['transaction_date'])), 1, 0, 'C');
        $pdf->Cell(70, 8, $games_text, 1, 0, 'L');
        $pdf->Cell(30, 8, $total_items . ' item(s)', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Rp ' . number_format($trx['total_price'], 0, ',', '.'), 1, 1, 'R');

        $counter++;

        // Add new page jika sudah mencapai batas
        if ($counter > 15 && $counter % 15 == 1) {
            $pdf->AddPage('L');
            // Ulang header tabel di halaman baru
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(220, 230, 241);
            $pdf->Cell(15, 8, 'No', 1, 0, 'C', true);
            $pdf->Cell(30, 8, 'Invoice ID', 1, 0, 'C', true);
            $pdf->Cell(50, 8, 'Date & Time', 1, 0, 'C', true);
            $pdf->Cell(70, 8, 'Games Purchased', 1, 0, 'C', true);
            $pdf->Cell(30, 8, 'Items', 1, 0, 'C', true);
            $pdf->Cell(40, 8, 'Total Amount', 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 9);
        }
    }

    // Total row
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(195, 8, 'GRAND TOTAL', 1, 0, 'R', true);
    $pdf->Cell(40, 8, 'Rp ' . number_format($stats['total_spent'] ?? 0, 0, ',', '.'), 1, 1, 'R', true);

} else {
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->Cell(0, 10, 'No purchase history found.', 0, 1, 'C');
}

// Footer dengan page number
$pdf->SetY(-20);
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 5, 'Generated by Midnight Play Purchase System', 0, 1, 'C');
$pdf->Cell(0, 5, 'Report ID: ' . uniqid(), 0, 1, 'C');
$pdf->Cell(0, 5, 'Page ' . $pdf->PageNo() . ' of {nb}', 0, 1, 'C');

// Alias untuk total pages
$pdf->AliasNbPages();

// Output PDF
$filename = 'MidnightPlay_Purchase_History_' . $username . '_' . date('Ymd_His') . '.pdf';
$pdf->Output('I', $filename);