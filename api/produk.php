<?php
date_default_timezone_set("Asia/Makassar");
require_once __DIR__ . "/lib/json_store.php";

header("Content-Type: application/json; charset=utf-8");
$data = bacaJson(__DIR__ . "/../data/produk.json");
$key = isset($data['products']) ? 'products' : null;

if (isset($_GET['kategori']) && $key !== null && is_array($data[$key])) {
    $kategori = strtolower(trim($_GET['kategori']));
    if ($kategori !== '' && $kategori !== 'semua') {
        $data[$key] = array_values(array_filter($data[$key], function ($item) use ($kategori) {
            return isset($item['kategori']) && strtolower($item['kategori']) === $kategori;
        }));
    }
}

if (isset($_GET['q']) && $key !== null && is_array($data[$key])) {
    $q = strtolower(trim($_GET['q']));
    if ($q !== '') {
        $data[$key] = array_values(array_filter($data[$key], function ($item) use ($q) {
            $nama = strtolower($item['nama'] ?? '');
            $desk = strtolower($item['deskripsi'] ?? '');
            $asal = strtolower($item['asal'] ?? '');
            $path = strtolower($item['path'] ?? '');
            return str_contains($nama, $q) || str_contains($desk, $q) || str_contains($asal, $q) || str_contains($path, $q);
        }));
    }
}

echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

