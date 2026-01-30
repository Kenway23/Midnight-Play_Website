<?php
session_start();
require "../fpdf/fpdf.php";
include "../config/database.php";

if (!isset($_SESSION['login'])) {
    header("Location:/auth/auth_login.php");
    exit();
}

$transaction_id = $_GET['id'] ?? '';
$user_id = $_SESSION['id_user'];

if (!$transaction_id || !is_numeric($transaction_id)) {
    die("ID transaksi tidak valid");
}

// Ambil data transaksi
$stmt = mysqli_prepare($conn, "
    SELECT t.*
    FROM transactions t
    WHERE t.id_transaction = ? 
    AND t.id_user = ?
");

if (!$stmt)
    die("Query preparation failed");
mysqli_stmt_bind_param($stmt, "ii", $transaction_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0)
    die("Transaction not found");
$transaction = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Ambil detail item
$stmt_details = mysqli_prepare($conn, "
    SELECT td.*, g.title, g.genre, g.price
    FROM transaction_details td
    JOIN games g ON td.id_game = g.id_game
    WHERE td.id_transaction = ?
    ORDER BY g.title
");

mysqli_stmt_bind_param($stmt_details, "i", $transaction_id);
mysqli_stmt_execute($stmt_details);
$details_result = mysqli_stmt_get_result($stmt_details);
$items_count = mysqli_num_rows($details_result);

// Hitung total
$total_calculated = 0;
$items_data = [];
while ($item = mysqli_fetch_assoc($details_result)) {
    $quantity = $item['quantity'] ?? 1;
    $price = $item['price'] ?? 0;
    $subtotal = $price * $quantity;
    $total_calculated += $subtotal;

    $items_data[] = [
        'title' => $item['title'] ?? 'Unknown Game',
        'genre' => $item['genre'] ?? 'Unknown',
        'price' => $price,
        'quantity' => $quantity,
        'subtotal' => $subtotal
    ];
}

// Create PDF class
class UserPDF extends FPDF
{
    function Header()
    {
        // Header with store name
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(102, 192, 244); // Midnight Play blue
        $this->Cell(0, 10, 'MIDNIGHT PLAY STORE', 0, 1, 'C');
        $this->SetFont('Arial', 'I', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 5, 'Digital Game Store', 0, 1, 'C');
        $this->Ln(5);

        // Invoice title
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'INVOICE', 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        // Page number and footer text
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | Thank you for shopping with us!', 0, 0, 'C');
    }
}

// Generate PDF
$pdf = new UserPDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Store Info
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Jl. Digital No. 123, Jakarta', 0, 1, 'C');
$pdf->Cell(0, 5, 'Telp: (021) 1234-5678 | Email: info@midnightplay.com', 0, 1, 'C');
$pdf->Ln(10);

// Invoice Details
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Invoice #INV-' . str_pad($transaction_id, 6, '0', STR_PAD_LEFT), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 6, 'Date:', 0, 0);
$pdf->Cell(0, 6, date('F d, Y H:i:s', strtotime($transaction['transaction_date'])), 0, 1);
$pdf->Cell(50, 6, 'Transaction ID:', 0, 0);
$pdf->Cell(0, 6, '#TRX-' . str_pad($transaction_id, 6, '0', STR_PAD_LEFT), 0, 1);
$pdf->Cell(50, 6, 'Payment Method:', 0, 0);
$pdf->Cell(0, 6, htmlspecialchars($transaction['payment_method'] ?? 'Digital Payment'), 0, 1);
$pdf->Cell(50, 6, 'Status:', 0, 0);
$pdf->SetTextColor(0, 128, 0);
$pdf->Cell(0, 6, 'COMPLETED', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(10);

// Items Header
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(10, 10, 'No', 1, 0, 'C', true);
$pdf->Cell(100, 10, 'Game Title', 1, 0, 'C', true);
$pdf->Cell(25, 10, 'Price', 1, 0, 'C', true);
$pdf->Cell(15, 10, 'Qty', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Subtotal', 1, 1, 'C', true);

// Items Content
$pdf->SetFont('Arial', '', 10);
$counter = 1;
foreach ($items_data as $item) {
    $pdf->Cell(10, 8, $counter, 1, 0, 'C');

    // Game Title (truncate if too long)
    $title = substr($item['title'], 0, 50);
    $pdf->Cell(100, 8, $title, 1, 0, 'L');

    $pdf->Cell(25, 8, 'Rp ' . number_format($item['price'], 0, ',', '.'), 1, 0, 'R');
    $pdf->Cell(15, 8, $item['quantity'], 1, 0, 'C');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 128, 0);
    $pdf->Cell(30, 8, 'Rp ' . number_format($item['subtotal'], 0, ',', '.'), 1, 1, 'R');
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Genre info
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(10, 4, '', 0, 0);
    $pdf->Cell(100, 4, 'Genre: ' . $item['genre'], 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    $counter++;
}

// Jika tidak ada items
if ($items_count == 0) {
    $pdf->Cell(180, 20, 'No items in this transaction', 1, 1, 'C');
}

// Total Section
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(140, 8, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(30, 8, 'Rp ' . number_format($total_calculated, 0, ',', '.'), 0, 1, 'R');
$pdf->Cell(140, 8, 'Tax (0%):', 0, 0, 'R');
$pdf->Cell(30, 8, 'Rp 0', 0, 1, 'R');
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(102, 192, 244); // Blue color
$pdf->Cell(140, 10, 'GRAND TOTAL:', 0, 0, 'R');
$pdf->Cell(30, 10, 'Rp ' . number_format($transaction['total_price'], 0, ',', '.'), 0, 1, 'R');
$pdf->SetTextColor(0, 0, 0);

// Footer Notes
$pdf->Ln(15);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 5, 'Thank you for shopping at Midnight Play Store!', 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, 'Games have been added to your library and are accessible forever.', 0, 1, 'C');
$pdf->Cell(0, 5, 'For support, contact: support@midnightplay.com', 0, 1, 'C');
$pdf->Cell(0, 5, 'Generated on: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
$pdf->Cell(0, 5, '&copy; ' . date('Y') . ' Midnight Play Store. All rights reserved.', 0, 1, 'C');

// Output PDF
$pdf->Output('I', 'invoice-' . $transaction_id . '.pdf');
?>