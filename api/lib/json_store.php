<?php
// api/lib/json_store.php

function bacaJson(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $teks = file_get_contents($path);
    $data = json_decode($teks, true);
    return $data ?? [];
}

function tulisJson(string $path, array $data): bool {
    $teks = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $teks, LOCK_EX) !== false;
}

// Operasi transaksi sensitif (seperti pengurangan stok checkout & CRUD produk)
function bukaJsonTerkunci(string $path, &$handle): array {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $handle = fopen($path, "c+");
    flock($handle, LOCK_EX); // Kunci eksklusif sebelum membaca
    clearstatcache(true, $path);
    $ukuran = filesize($path) ?: 0;
    $teks = $ukuran > 0 ? fread($handle, $ukuran) : "";
    $data = $teks !== "" ? json_decode($teks, true) : [];
    return $data ?? [];
}

function tutupJsonTerkunci($handle, array $data): void {
    $teks = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    rewind($handle);
    ftruncate($handle, 0); // Bersihkan isi lama
    fwrite($handle, $teks);
    fflush($handle);
    flock($handle, LOCK_UN); // Buka kunci setelah selesai
    fclose($handle);
}

function buatKodeTransaksi(string $prefix = "RM"): string {
    return $prefix . "-" . strtoupper(bin2hex(random_bytes(4)));
}

