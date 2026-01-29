<?php
session_start();
require_once('../../fpdf/fpdf.php');
include "../../config/database.php";

/* Proteksi admin */
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

// Filter parameter
$filter_month = $_GET['month'] ?? date('m');
$filter_year = $_GET['year'] ?? date('Y');

// Validasi
if (!is_numeric($filter_month) || $filter_month < 1 || $filter_month > 12) {
    $filter_month = date('m');
}
if (!is_numeric($filter_year) || $filter_year < 2020 || $filter_year > date('Y')) {
    $filter_year = date('Y');
}

// Hitung statistik utama
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(total_price) as total_revenue,
        AVG(total_price) as avg_transaction,
        MIN(total_price) as min_transaction,
        MAX(total_price) as max_transaction
    FROM transactions 
    WHERE MONTH(transaction_date) = '$filter_month' 
    AND YEAR(transaction_date) = '$filter_year'
");
$stats = mysqli_fetch_assoc($stats_query);

// Data harian
$daily_query = mysqli_query($conn, "
    SELECT 
        DAY(transaction_date) as day,
        SUM(total_price) as daily_revenue,
        COUNT(*) as transaction_count
    FROM transactions 
    WHERE MONTH(transaction_date) = '$filter_month' 
    AND YEAR(transaction_date) = '$filter_year'
    GROUP BY DAY(transaction_date)
    ORDER BY day
");

// Game terlaris
$best_selling_query = mysqli_query($conn, "
    SELECT 
        g.title,
        COUNT(td.id_detail) as total_sold,
        SUM(g.price) as total_revenue
    FROM transaction_details td
    JOIN games g ON td.id_game = g.id_game
    JOIN transactions t ON td.id_transaction = t.id_transaction
    WHERE MONTH(t.transaction_date) = '$filter_month' 
    AND YEAR(t.transaction_date) = '$filter_year'
    GROUP BY td.id_game
    ORDER BY total_sold DESC
    LIMIT 10
");

// Buat PDF class untuk laporan analisis
class SalesAnalysisPDF extends FPDF
{
    private $month;
    private $year;

    function __construct($month, $year, $orientation = 'P', $unit = 'mm', $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->month = $month;
        $this->year = $year;
    }

    function Header()
    {
        // Logo
        if (file_exists('../../assets/images/logo.png')) {
            $this->Image('../../assets/images/logo.png', 10, 8, 25);
        }

        // Title
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(59, 130, 246);
        $this->Cell(0, 10, 'LAPORAN ANALISIS PENJUALAN', 0, 1, 'C');

        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(0);
        $this->Cell(0, 8, 'Midnight Play Store', 0, 1, 'C');

        // Periode
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 6, 'Periode: ' . date('F Y', mktime(0, 0, 0, $this->month, 1, $this->year)), 0, 1, 'C');

        // Tanggal cetak
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 5, 'Dicetak: ' . date('d/m/Y H:i'), 0, 1, 'C');

        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function ChapterTitle($title)
    {
        $this->SetFont('Arial', 'B', 14);
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, $title, 0, 1, 'L', true);
        $this->Ln(3);
    }

    function StatsBox($label, $value, $border = true)
    {
        $this->SetFont('Arial', '', 10);
        $this->Cell(60, 8, $label, $border ? 1 : 0, 0);

        $this->SetFont('Arial', 'B', 11);
        if (strpos($label, 'Pendapatan') !== false || strpos($label, 'Rp') !== false) {
            $this->SetTextColor(0, 128, 0);
        } else {
            $this->SetTextColor(0, 0, 0);
        }
        $this->Cell(0, 8, $value, $border ? 1 : 1, 1);
        $this->SetTextColor(0, 0, 0);
    }

    function DailyTable($daily_data)
    {
        // Table header
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(59, 130, 246);
        $this->SetTextColor(255);
        $this->SetDrawColor(200, 200, 200);

        $this->Cell(20, 8, 'Hari', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Pendapatan (Rp)', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Transaksi', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Rata-rata', 1, 1, 'C', true);

        // Table data
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);

        $fill = false;
        $total_days = count($daily_data);

        for ($day = 1; $day <= $total_days; $day++) {
            if (isset($daily_data[$day])) {
                $revenue = $daily_data[$day]['revenue'];
                $transactions = $daily_data[$day]['transactions'];
                $average = $transactions > 0 ? $revenue / $transactions : 0;

                $this->Cell(20, 7, $day, 1, 0, 'C', $fill);
                $this->Cell(40, 7, number_format($revenue, 0, ',', '.'), 1, 0, 'R', $fill);
                $this->Cell(30, 7, $transactions, 1, 0, 'C', $fill);
                $this->Cell(40, 7, number_format($average, 0, ',', '.'), 1, 1, 'R', $fill);
            } else {
                $this->Cell(20, 7, $day, 1, 0, 'C', $fill);
                $this->Cell(40, 7, '0', 1, 0, 'R', $fill);
                $this->Cell(30, 7, '0', 1, 0, 'C', $fill);
                $this->Cell(40, 7, '0', 1, 1, 'R', $fill);
            }
            $fill = !$fill;
        }
    }

    function BestSellingTable($data)
    {
        // Table header
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(59, 130, 246);
        $this->SetTextColor(255);

        $this->Cell(10, 8, 'No', 1, 0, 'C', true);
        $this->Cell(80, 8, 'Nama Game', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Terjual', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Pendapatan', 1, 1, 'C', true);

        // Table data
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);

        $fill = false;
        $counter = 1;

        while ($row = mysqli_fetch_assoc($data)) {
            $this->Cell(10, 7, $counter, 1, 0, 'C', $fill);
            $this->Cell(80, 7, substr($row['title'], 0, 40), 1, 0, 'L', $fill);
            $this->Cell(30, 7, $row['total_sold'], 1, 0, 'C', $fill);
            $this->SetTextColor(0, 128, 0);
            $this->Cell(40, 7, number_format($row['total_revenue'], 0, ',', '.'), 1, 1, 'R', $fill);
            $this->SetTextColor(0, 0, 0);

            $fill = !$fill;
            $counter++;
        }
    }

    function SummaryBox($text)
    {
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(100, 100, 100);
        $this->MultiCell(0, 5, $text);
        $this->Ln(5);
    }
}

// Buat PDF
$pdf = new SalesAnalysisPDF($filter_month, $filter_year);
$pdf->AliasNbPages();
$pdf->AddPage();

// Ringkasan Eksekutif
$pdf->ChapterTitle('RINGKASAN EKSEKUTIF');
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, "Laporan analisis penjualan untuk periode " .
    date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)) .
    ". Berikut adalah performa keuangan toko game Midnight Play.");
$pdf->Ln(10);

// Statistik Utama
$pdf->ChapterTitle('STATISTIK UTAMA');
$pdf->StatsBox('Total Pendapatan:', 'Rp ' . number_format($stats['total_revenue'] ?? 0, 0, ',', '.'));
$pdf->StatsBox('Total Transaksi:', ($stats['total_transactions'] ?? 0) . ' transaksi');
$pdf->StatsBox('Rata-rata Transaksi:', 'Rp ' . number_format($stats['avg_transaction'] ?? 0, 0, ',', '.'));
$pdf->StatsBox('Transaksi Tertinggi:', 'Rp ' . number_format($stats['max_transaction'] ?? 0, 0, ',', '.'));
$pdf->StatsBox('Transaksi Terendah:', 'Rp ' . number_format($stats['min_transaction'] ?? 0, 0, ',', '.'));
$pdf->Ln(10);

// Data Harian
$pdf->ChapterTitle('ANALISIS HARIAN - ' . date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)));

// Prepare daily data
$daily_data = [];
while ($row = mysqli_fetch_assoc($daily_query)) {
    $daily_data[$row['day']] = [
        'revenue' => $row['daily_revenue'],
        'transactions' => $row['transaction_count']
    ];
}

// Fill missing days
$days_in_month = date('t', mktime(0, 0, 0, $filter_month, 1, $filter_year));
for ($day = 1; $day <= $days_in_month; $day++) {
    if (!isset($daily_data[$day])) {
        $daily_data[$day] = [
            'revenue' => 0,
            'transactions' => 0
        ];
    }
}
ksort($daily_data);

$pdf->DailyTable($daily_data);
$pdf->Ln(10);

// Game Terlaris
$pdf->ChapterTitle('GAME TERLARIS');
if (mysqli_num_rows($best_selling_query) > 0) {
    mysqli_data_seek($best_selling_query, 0);
    $pdf->BestSellingTable($best_selling_query);

    // Calculate percentage of top games
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(100, 100, 100);

    $total_game_revenue = 0;
    mysqli_data_seek($best_selling_query, 0);
    while ($row = mysqli_fetch_assoc($best_selling_query)) {
        $total_game_revenue += $row['total_revenue'];
    }

    $percentage = ($total_game_revenue / ($stats['total_revenue'] ?: 1)) * 100;
    $pdf->Cell(0, 5, "Top 10 game menyumbang " . number_format($percentage, 1) . "% dari total pendapatan.", 0, 1);
} else {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 10, 'Tidak ada data penjualan game untuk periode ini.', 0, 1, 'C');
}
$pdf->Ln(10);

// Kesimpulan
$pdf->ChapterTitle('KESIMPULAN DAN REKOMENDASI');
$pdf->SetFont('Arial', '', 10);

$conclusions = [];

// Revenue analysis
if ($stats['total_revenue'] > 0) {
    $conclusions[] = "• Total pendapatan: Rp " . number_format($stats['total_revenue'], 0, ',', '.');
    $conclusions[] = "• Rata-rata transaksi: Rp " . number_format($stats['avg_transaction'], 0, ',', '.');

    // Find best day
    $best_day = 0;
    $best_revenue = 0;
    foreach ($daily_data as $day => $data) {
        if ($data['revenue'] > $best_revenue) {
            $best_revenue = $data['revenue'];
            $best_day = $day;
        }
    }
    if ($best_day > 0) {
        $conclusions[] = "• Hari dengan penjualan tertinggi: Hari ke-$best_day (Rp " . number_format($best_revenue, 0, ',', '.') . ")";
    }

    // Activity analysis
    $active_days = 0;
    foreach ($daily_data as $data) {
        if ($data['transactions'] > 0) {
            $active_days++;
        }
    }
    $activity_percentage = ($active_days / $days_in_month) * 100;
    $conclusions[] = "• Aktivitas penjualan: $active_days hari aktif dari $days_in_month hari (" . number_format($activity_percentage, 1) . "%)";
} else {
    $conclusions[] = "• Tidak ada transaksi pada periode ini.";
    $conclusions[] = "• Rekomendasi: Lakukan promosi atau review strategi penjualan.";
}

foreach ($conclusions as $conclusion) {
    $pdf->Cell(5, 6, '', 0, 0);
    $pdf->MultiCell(0, 6, $conclusion);
}

$pdf->Ln(10);

// Catatan Akhir
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 5, "Catatan: Laporan ini dibuat otomatis oleh sistem Midnight Play. Data berdasarkan transaksi yang tercatat dalam database.");
$pdf->Cell(0, 5, "Untuk informasi lebih lanjut, hubungi administrator sistem.", 0, 1);

// Output PDF
$filename = 'Analisis_Penjualan_' . date('F_Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)) . '.pdf';
$pdf->Output('I', $filename);
?>