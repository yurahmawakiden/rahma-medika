<?php
date_default_timezone_set("Asia/Makassar");
require_once __DIR__ . "/lib/json_store.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "gagal", "pesan" => "Metode HTTP harus POST."]);
    exit;
}

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$namaPemesan = trim((string)($input["nama_pemesan"] ?? ""));
$kontak      = trim((string)($input["kontak"] ?? ""));
$umur        = isset($input["umur"]) && $input["umur"] !== "" ? (int)$input["umur"] : 0;
$itemsInput  = $input["items"] ?? $input["keranjang"] ?? [];

if ($namaPemesan === "" || $kontak === "") {
    http_response_code(400);
    echo json_encode([
        "status" => "gagal",
        "pesan"  => "Data Umum Pasien (Nama dan Nomor WA) wajib diisi lengkap."
    ]);
    exit;
}

if ($umur < 0 || $umur > 130) {
    http_response_code(400);
    echo json_encode([
        "status" => "gagal",
        "pesan"  => "Umur pasien tidak valid."
    ]);
    exit;
}

if (!is_array($itemsInput) || count($itemsInput) === 0) {
    http_response_code(400);
    echo json_encode([
        "status" => "gagal",
        "pesan"  => "Daftar layanan medis masih kosong. Silakan pilih jenis layanan terlebih dahulu."
    ]);
    exit;
}

$pathProduk = __DIR__ . "/../data/produk.json";
$dataProduk = bukaJsonTerkunci($pathProduk, $handleProduk);

if (!isset($dataProduk["products"]) || !is_array($dataProduk["products"])) {
    flock($handleProduk, LOCK_UN);
    fclose($handleProduk);
    http_response_code(500);
    echo json_encode([
        "status" => "gagal",
        "pesan"  => "Data katalog layanan medis tidak ditemukan."
    ]);
    exit;
}

// Indeks produk berdasarkan ID
$mapIndexProduk = [];
foreach ($dataProduk["products"] as $idx => $prod) {
    $mapIndexProduk[(int)$prod["id"]] = $idx;
}

// Hitung total kuantitas per ID layanan (jika ada beberapa tindakan dengan layanan yang sama)
$totalQtyPerProduk = [];
foreach ($itemsInput as $reqItem) {
    $idProd = (int)($reqItem["id"] ?? 0);
    $qty    = (int)($reqItem["jumlah"] ?? $reqItem["qty"] ?? 1);
    if ($idProd <= 0 || $qty <= 0) {
        flock($handleProduk, LOCK_UN);
        fclose($handleProduk);
        http_response_code(400);
        echo json_encode([
            "status" => "gagal",
            "pesan"  => "Kuantitas layanan tidak valid."
        ]);
        exit;
    }
    if (!isset($mapIndexProduk[$idProd])) {
        flock($handleProduk, LOCK_UN);
        fclose($handleProduk);
        http_response_code(404);
        echo json_encode([
            "status" => "gagal",
            "pesan"  => "Layanan medis dengan ID #$idProd tidak ditemukan."
        ]);
        exit;
    }
    $totalQtyPerProduk[$idProd] = ($totalQtyPerProduk[$idProd] ?? 0) + $qty;
}

// Validasi ketersediaan kuota/stok layanan
foreach ($totalQtyPerProduk as $idProd => $totalQty) {
    $idxProd = $mapIndexProduk[$idProd];
    $prod = $dataProduk["products"][$idxProd];
    $stokTersedia = (int)($prod["stok"] ?? 0);
    if ($totalQty > $stokTersedia) {
        flock($handleProduk, LOCK_UN);
        fclose($handleProduk);
        http_response_code(400);
        echo json_encode([
            "status" => "gagal",
            "pesan"  => "Permintaan stok tidak sesuai: " . ($prod["nama"] ?? "Layanan") . " hanya tersisa " . $stokTersedia . " kuota."
        ]);
        exit;
    }
}

// Susun item transaksi & validasi input khusus tiap Path sesuai Flowchart 2
$itemsTransaksi = [];
$subtotalBiaya  = 0;
$totalJasa      = 0;

foreach ($itemsInput as $reqItem) {
    $idProd  = (int)$reqItem["id"];
    $qty     = (int)($reqItem["jumlah"] ?? $reqItem["qty"] ?? 1);
    $idxProd = $mapIndexProduk[$idProd];
    $prod    = $dataProduk["products"][$idxProd];

    $harga     = (int)($prod["harga"] ?? 0);
    $biayaJasa = (int)($prod["biaya_jasa"] ?? 0);
    $detailMedis = is_array($reqItem["detail_medis"] ?? null) ? $reqItem["detail_medis"] : [];

    // Sanitasi string detail medis sesuai Path 1, Path 5, Path 6
    $detailBersih = [];
    if (!empty($detailMedis["jenis_cairan"])) {
        $detailBersih["jenis_cairan"] = trim((string)$detailMedis["jenis_cairan"]);
    }
    if (!empty($detailMedis["riwayat_penyakit"])) {
        $detailBersih["riwayat_penyakit"] = trim((string)$detailMedis["riwayat_penyakit"]);
    }
    if (!empty($detailMedis["jenis_luka"])) {
        $detailBersih["jenis_luka"] = trim((string)$detailMedis["jenis_luka"]);
    }
    if (!empty($detailMedis["alergi"])) {
        $detailBersih["alergi"] = trim((string)$detailMedis["alergi"]);
    }
    if (!empty($detailMedis["keluhan"])) {
        $detailBersih["keluhan"] = trim((string)$detailMedis["keluhan"]);
    }
    if (!empty($reqItem["catatan_medis"])) {
        $detailBersih["catatan"] = trim((string)$reqItem["catatan_medis"]);
    }

    $subBiayaItem = $harga * $qty;
    $subJasaItem  = $biayaJasa * $qty;
    $subTotalItem = $subBiayaItem + $subJasaItem;

    $subtotalBiaya += $subBiayaItem;
    $totalJasa     += $subJasaItem;

    $itemsTransaksi[] = [
        "id"             => $idProd,
        "path"           => $prod["path"] ?? ("Path " . $idProd),
        "nama"           => $prod["nama"],
        "kategori"       => $prod["kategori"] ?? "",
        "harga"          => $harga,
        "biaya_jasa"     => $biayaJasa,
        "jumlah"         => $qty,
        "subtotal_biaya" => $subBiayaItem,
        "subtotal_jasa"  => $subJasaItem,
        "subtotal"       => $subTotalItem,
        "detail_medis"   => $detailBersih,
    ];
}

// Kurangi stok secara atomic pada data/produk.json
foreach ($totalQtyPerProduk as $idProd => $totalQty) {
    $idxProd = $mapIndexProduk[$idProd];
    $dataProduk["products"][$idxProd]["stok"] = max(0, (int)$dataProduk["products"][$idxProd]["stok"] - $totalQty);
}
tutupJsonTerkunci($handleProduk, $dataProduk);

// Simpan catatan transaksi ke data/transaksi.json
$totalAkhir    = $subtotalBiaya + $totalJasa;
$kodeTransaksi = buatKodeTransaksi("RM");

$pathTransaksi = __DIR__ . "/../data/transaksi.json";
$dataTransaksi = bukaJsonTerkunci($pathTransaksi, $handleTransaksi);

$transaksiBaru = [
    "kode_transaksi" => $kodeTransaksi,
    "tanggal"        => date("Y-m-d H:i:s"),
    "nama_pemesan"   => $namaPemesan,
    "kontak"         => $kontak,
    "umur"           => $umur > 0 ? $umur : null,
    "items"          => $itemsTransaksi,
    "subtotal_biaya" => $subtotalBiaya,
    "total_jasa"     => $totalJasa,
    "total"          => $totalAkhir,
];

$dataTransaksi[] = $transaksiBaru;
tutupJsonTerkunci($handleTransaksi, $dataTransaksi);

echo json_encode([
    "status"         => "sukses",
    "pesan"          => "Transaksi layanan medis berhasil diproses.",
    "kode_transaksi" => $kodeTransaksi,
    "subtotal_biaya" => $subtotalBiaya,
    "total_jasa"     => $totalJasa,
    "total"          => $totalAkhir,
    "transaksi"      => $transaksiBaru,
]);

