<?php
date_default_timezone_set("Asia/Makassar");
require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/lib/json_store.php";

function teks(string $s): string {
    $converted = @iconv("UTF-8", "ISO-8859-1//TRANSLIT", $s);
    return $converted !== false ? $converted : $s;
}

function formatRupiahStruk(int $angka): string {
    return "Rp " . number_format($angka, 0, ",", ".");
}

$kode = trim((string)($_GET["kode"] ?? ""));
if ($kode === "") {
    http_response_code(400);
    header("Content-Type: text/plain; charset=utf-8");
    echo "Kode transaksi tidak disertakan.";
    exit;
}

$daftarTransaksi = bacaJson(__DIR__ . "/../data/transaksi.json");
$transaksi = null;
foreach ($daftarTransaksi as $trx) {
    if (isset($trx["kode_transaksi"]) && strcasecmp($trx["kode_transaksi"], $kode) === 0) {
        $transaksi = $trx;
        break;
    }
}

if ($transaksi === null) {
    http_response_code(404);
    header("Content-Type: text/plain; charset=utf-8");
    echo "Data transaksi dengan kode " . htmlspecialchars($kode) . " tidak ditemukan.";
    exit;
}

$dataProduk = bacaJson(__DIR__ . "/../data/produk.json");
$site = $dataProduk["site"] ?? [
    "nama"            => "Rahma Medika",
    "tagline"         => "Klinik Pratama & Layanan Homecare Medis Terpercaya",
    "alamat"          => "Jl. Kesehatan Raya No. 18, Makassar",
    "telepon"         => "6281234567890",
    "email"           => "layanan@rahma-medika.test",
];

$pdf = new FPDF("P", "mm", "A4");
$pdf->SetTitle(teks("Struk Layanan Medis - " . $transaksi["kode_transaksi"]));
$pdf->SetAuthor(teks($site["nama"] ?? "Rahma Medika"));
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// Logo simetris di bagian atas & geser SetY agar tidak menimpa teks judul
$logoPath = __DIR__ . "/../img/logo.svg";
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 96, 12, 18, 18);
}
$pdf->SetY(33);

// Header Klinik Rahma Medika
$pdf->SetFont("Arial", "B", 16);
$pdf->SetTextColor(3, 105, 161);
$pdf->Cell(0, 7, teks(strtoupper($site["nama"] ?? "RAHMA MEDIKA")), 0, 1, "C");

$pdf->SetFont("Arial", "", 10);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(0, 5, teks($site["tagline"] ?? "Klinik Pratama & Layanan Homecare Medis"), 0, 1, "C");
$pdf->Cell(0, 5, teks(($site["alamat"] ?? "") . " | WA: " . ($site["telepon"] ?? "-")), 0, 1, "C");

$pdf->Ln(3);
$pdf->SetDrawColor(14, 165, 233);
$pdf->SetLineWidth(0.6);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// Judul Bukti Transaksi
$pdf->SetFont("Arial", "B", 12);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(0, 7, teks("STRUK PEMERIKSAAN & TINDAKAN LAYANAN MEDIS"), 0, 1, "C");
$pdf->Ln(2);

// Box Data Umum Pasien (Sesuai Flowchart 2: Nama, No WA, Umur)
$pdf->SetFillColor(240, 249, 255);
$pdf->SetDrawColor(186, 230, 253);
$pdf->SetLineWidth(0.3);
$yBox = $pdf->GetY();
$pdf->Rect(15, $yBox, 180, 26, "FD");

$pdf->SetXY(19, $yBox + 3);
$pdf->SetFont("Arial", "B", 9);
$pdf->SetTextColor(3, 105, 161);
$pdf->Cell(85, 5, teks("DATA UMUM PASIEN"), 0, 0, "L");
$pdf->Cell(85, 5, teks("INFORMASI TRANSAKSI"), 0, 1, "L");

$pdf->SetFont("Arial", "", 9);
$pdf->SetTextColor(30, 41, 59);

$pdf->SetX(19);
$pdf->Cell(28, 5, teks("Nama Pasien"), 0, 0, "L");
$pdf->Cell(57, 5, teks(": " . ($transaksi["nama_pemesan"] ?? "-")), 0, 0, "L");
$pdf->Cell(32, 5, teks("Kode Struk"), 0, 0, "L");
$pdf->SetFont("Arial", "B", 9);
$pdf->Cell(55, 5, teks(": " . ($transaksi["kode_transaksi"] ?? "-")), 0, 1, "L");

$pdf->SetFont("Arial", "", 9);
$pdf->SetX(19);
$pdf->Cell(28, 5, teks("No. WhatsApp"), 0, 0, "L");
$pdf->Cell(57, 5, teks(": " . ($transaksi["kontak"] ?? "-")), 0, 0, "L");
$pdf->Cell(32, 5, teks("Tanggal / Waktu"), 0, 0, "L");
$pdf->Cell(55, 5, teks(": " . ($transaksi["tanggal"] ?? "-")), 0, 1, "L");

$umurTeks = !empty($transaksi["umur"]) ? ($transaksi["umur"] . " Tahun") : "-";
$pdf->SetX(19);
$pdf->Cell(28, 5, teks("Umur Pasien"), 0, 0, "L");
$pdf->Cell(57, 5, teks(": " . $umurTeks), 0, 0, "L");
$pdf->Cell(32, 5, teks("Status Layanan"), 0, 0, "L");
$pdf->Cell(55, 5, teks(": LUNAS / TERVERIFIKASI"), 0, 1, "L");

$pdf->SetY($yBox + 30);

// Tabel Header Rincian Layanan Medis
$pdf->SetFont("Arial", "B", 9);
$pdf->SetFillColor(3, 105, 161);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(3, 105, 161);

$pdf->Cell(10, 8, teks("No"), 1, 0, "C", true);
$pdf->Cell(68, 8, teks("Jenis Layanan & Catatan Medis"), 1, 0, "L", true);
$pdf->Cell(14, 8, teks("Jml"), 1, 0, "C", true);
$pdf->Cell(28, 8, teks("Biaya Layanan"), 1, 0, "R", true);
$pdf->Cell(28, 8, teks("Biaya Jasa"), 1, 0, "R", true);
$pdf->Cell(32, 8, teks("Subtotal"), 1, 1, "R", true);

// Isi Tabel Layanan
$pdf->SetFont("Arial", "", 9);
$pdf->SetTextColor(30, 41, 59);
$pdf->SetDrawColor(203, 213, 225);

$no = 1;
$subtotalBiayaHitung = 0;
$totalJasaHitung = 0;

foreach (($transaksi["items"] ?? []) as $item) {
    $qty        = (int)($item["jumlah"] ?? 1);
    $harga      = (int)($item["harga"] ?? 0);
    $biayaJasa  = (int)($item["biaya_jasa"] ?? 0);
    $subItem    = (int)($item["subtotal"] ?? (($harga + $biayaJasa) * $qty));

    $subtotalBiayaHitung += $harga * $qty;
    $totalJasaHitung     += $biayaJasa * $qty;

    $namaLayanan = ($item["path"] ?? "") !== ""
        ? "[" . $item["path"] . "] " . ($item["nama"] ?? "-")
        : ($item["nama"] ?? "-");

    $pdf->SetFont("Arial", "B", 9);
    $pdf->Cell(10, 7, teks((string)$no), "LTR", 0, "C");
    $pdf->Cell(68, 7, teks($namaLayanan), "LTR", 0, "L");
    $pdf->SetFont("Arial", "", 9);
    $pdf->Cell(14, 7, teks((string)$qty . "x"), "LTR", 0, "C");
    $pdf->Cell(28, 7, teks(formatRupiahStruk($harga)), "LTR", 0, "R");
    $pdf->Cell(28, 7, teks(formatRupiahStruk($biayaJasa)), "LTR", 0, "R");
    $pdf->SetFont("Arial", "B", 9);
    $pdf->Cell(32, 7, teks(formatRupiahStruk($subItem)), "LTR", 1, "R");

    // Rincian Input Medis Spesifik Path (Path 1: Cairan & Riwayat, Path 5: Luka & Alergi, Path 6: Keluhan)
    $detail = $item["detail_medis"] ?? [];
    $barisDetail = [];
    if (!empty($detail["jenis_cairan"])) {
        $barisDetail[] = "Jenis Cairan: " . $detail["jenis_cairan"];
    }
    if (!empty($detail["riwayat_penyakit"])) {
        $barisDetail[] = "Riwayat Penyakit: " . $detail["riwayat_penyakit"];
    }
    if (!empty($detail["jenis_luka"])) {
        $barisDetail[] = "Jenis Luka: " . $detail["jenis_luka"];
    }
    if (!empty($detail["alergi"])) {
        $barisDetail[] = "Alergi: " . $detail["alergi"];
    }
    if (!empty($detail["keluhan"])) {
        $barisDetail[] = "Keluhan: " . $detail["keluhan"];
    }
    if (!empty($detail["catatan"])) {
        $barisDetail[] = "Catatan: " . $detail["catatan"];
    }

    $teksDetail = count($barisDetail) > 0
        ? implode(" | ", $barisDetail)
        : "Pemeriksaan standar sesuai prosedur klinis Rahma Medika";

    $pdf->SetFont("Arial", "I", 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(10, 6, "", "LBR", 0, "C");
    $pdf->Cell(170, 6, teks("  * " . $teksDetail), "LBR", 1, "L");
    $pdf->SetTextColor(30, 41, 59);

    $no++;
}

$subtotalBiayaAkhir = (int)($transaksi["subtotal_biaya"] ?? $subtotalBiayaHitung);
$totalJasaAkhir     = (int)($transaksi["total_jasa"] ?? $totalJasaHitung);
$totalAkhir         = (int)($transaksi["total"] ?? ($subtotalBiayaAkhir + $totalJasaAkhir));

$pdf->Ln(3);

// Ringkasan Hitung Total Biaya & Jasa (Sesuai Flowchart 2)
$pdf->SetFont("Arial", "", 9);
$pdf->Cell(120, 6, "", 0, 0);
$pdf->Cell(30, 6, teks("Total Biaya Layanan"), 0, 0, "L");
$pdf->Cell(30, 6, teks(formatRupiahStruk($subtotalBiayaAkhir)), 0, 1, "R");

$pdf->Cell(120, 6, "", 0, 0);
$pdf->Cell(30, 6, teks("Total Jasa Medis"), 0, 0, "L");
$pdf->Cell(30, 6, teks(formatRupiahStruk($totalJasaAkhir)), 0, 1, "R");

$pdf->SetFont("Arial", "B", 11);
$pdf->SetFillColor(224, 242, 254);
$pdf->SetTextColor(3, 105, 161);
$pdf->Cell(110, 9, "", 0, 0);
$pdf->Cell(40, 9, teks("TOTAL BIAYA & JASA"), 1, 0, "L", true);
$pdf->Cell(30, 9, teks(formatRupiahStruk($totalAkhir)), 1, 1, "R", true);

$pdf->Ln(10);
$pdf->SetTextColor(71, 85, 105);
$pdf->SetFont("Arial", "", 9);
$pdf->Cell(110, 5, teks("Catatan Penting:"), 0, 0, "L");
$pdf->Cell(70, 5, teks("Makassar, " . date("d-m-Y", strtotime($transaksi["tanggal"] ?? "now"))), 0, 1, "C");

$pdf->SetFont("Arial", "I", 8);
$pdf->Cell(110, 5, teks("- Simpan struk ini sebagai bukti sah tindakan & pemeriksaan medis."), 0, 0, "L");
$pdf->SetFont("Arial", "", 9);
$pdf->Cell(70, 5, teks("Petugas Layanan Medis,"), 0, 1, "C");

$pdf->SetFont("Arial", "I", 8);
$pdf->Cell(110, 5, teks("- Hubungi WhatsApp klinik bila membutuhkan konsultasi lanjutan."), 0, 1, "L");
$pdf->Ln(10);
$pdf->SetFont("Arial", "B", 9);
$pdf->Cell(110, 5, teks("Semoga Lekas Sembuh & Sehat Selalu!"), 0, 0, "L");
$pdf->Cell(70, 5, teks("( Tim Medis Rahma Medika )"), 0, 1, "C");

$pdf->Output("I", "Struk-" . $transaksi["kode_transaksi"] . ".pdf");

