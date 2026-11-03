---
name: umkm-flatfile-store
description: >-
  Gunakan skill ini saat membuat, merancang, atau mereplikasi aplikasi web toko UMKM
  dengan arsitektur persis seperti proyek Kopi Nusantara: Frontend Vanilla JS (Fetch API + LocalStorage Cart),
  Backend PHP Native dengan Flat-File JSON Database ber-locking (flock), Struk PDF (Composer FPDF),
  Integrasi Kontak (WhatsApp & Mailpit), serta Panel Admin (Session Auth & CRUD).
---

# Panduan Pembuatan Aplikasi Web UMKM (Arsitektur Flat-File Store)

Dokumen ini adalah panduan standar dan cetak biru (_blueprint_) untuk membangun aplikasi web toko UMKM dari nol dengan pola kerja, struktur berkas, dan standar kode yang **identik persis** dengan proyek pelatihan _Kopi Nusantara_.

---

## 1. Ikhtisar Arsitektur & Teknologi

- **Frontend**: HTML5, Vanilla CSS / Bootstrap 5 CDN, Vanilla JavaScript (ES6) modern.
- **Backend API**: PHP Native (Procedural/Modular), format pertukaran data JSON.
- **Basis Data**: _Flat-File Storage_ (`.json`) di server lokal, dilengkapi mekanisme penguncian berkas (_file locking_ `flock`) untuk mencegah _race condition_.
- **Keamanan Data**: Perlindungan direktori data menggunakan Apache `.htaccess` (`Require all denied`) dan autentikasi sesi PHP ber-hash `password_hash()` / `password_verify()`.
- **Eksternal**:
  - `setasign/fpdf` (^1.9) via Composer untuk struk belanja PDF.
  - `Simple-DataTables` CDN untuk tabel admin dinamis.
  - WhatsApp Direct Link & Mailpit API (Laragon port 8025) untuk form kontak.

---

## 2. Struktur Direktori Standar

Setiap proyek UMKM baru harus mengikuti pohon direktori berikut:

```text
nama-proyek-umkm/
├── admin/
│   ├── css/
│   │   └── admin.css              # Gaya visual dashboard & form admin
│   ├── includes/
│   │   └── proteksi.php           # Middleware cek $_SESSION["admin_id"]
│   ├── js/
│   │   └── admin.js               # Inisialisasi Simple-DataTables
│   ├── dashboard.php              # Statistik ringkasan produk, transaksi, omzet
│   ├── index.php                  # Halaman login admin
│   ├── logout.php                 # Hancurkan sesi & redirect login
│   ├── pengguna.php               # Manajemen akun administrator
│   ├── produk.php                 # CRUD lengkap produk & sisa stok
│   └── transaksi.php              # Rekapitulasi pesanan masuk
├── api/
│   ├── lib/
│   │   └── json_store.php         # Engine baca/tulis JSON ber-locking (flock)
│   ├── checkout.php               # Validasi pesanan, potong stok, simpan transaksi
│   ├── produk.php                 # Endpoint JSON produk + filter kategori
│   └── struk.php                  # Generator PDF struk pembayaran via FPDF
├── composer.json                  # Dependensi setasign/fpdf
├── css/
│   └── style.css                  # Gaya visual etalase publik
├── data/
│   ├── .htaccess                  # Wajib: blokir akses HTTP langsung ke JSON
│   ├── pengguna.json              # Data akun admin ber-hash bcrypt
│   ├── produk.json                # Katalog produk, menu, dan profil UMKM
│   └── transaksi.json             # Catatan riwayat checkout
├── database/
│   └── buat_admin_pertama.php     # Skrip seeder akun admin awal (jalankan sekali)
├── img/                           # Foto produk & logo toko
├── js/
│   ├── App-09.js                  # Fetch produk, filter kategori, render grid
│   ├── keranjang.js               # State keranjang localStorage, stok limit, checkout
│   └── kirimpesan.js              # Integrasi WhatsApp Web & Mailpit API
└── index.html                     # Halaman muka (Landing page toko UMKM)
```

---

## 3. Langkah-Langkah Pembuatan Proyek Baru

### Tahap 1: Setup Proyek & Composer

1. Buat folder proyek di dalam root web server Laragon: `C:\laragon\www\nama-proyek-umkm`.
2. Buka terminal di folder tersebut dan pasang library FPDF:
   ```bash
   composer require setasign/fpdf
   ```
3. Buat file `data/.htaccess` dengan isi murni:
   ```apache
   # Blokir semua akses peramban langsung ke file data JSON
   Require all denied
   ```
   _(PENTING: Jangan sertakan tanda pagar markdown / backtick di dalam file `.htaccess`)._

---

### Tahap 2: Core Data Storage Engine (`api/lib/json_store.php`)

Berkas ini adalah jantung pengelolaan database flat-file. Wajib dibuat dengan fungsi penguncian berkas (_file locking_):

```php
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
    $handle = fopen($path, "c+");
    flock($handle, LOCK_EX); // Kunci eksklusif sebelum membaca
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

function buatKodeTransaksi(string $prefix = "TRX"): string {
    return $prefix . "-" . strtoupper(bin2hex(random_bytes(4)));
}
```

---

### Tahap 3: Struktur Awal Berkas Data JSON

#### 1. `data/produk.json`

_Gunakan selalu kunci `"products"` secara konsisten:_

```json
{
  "site": {
    "nama": "Nama Toko UMKM",
    "email": "halo@toko-umkm.test"
  },
  "menu": [
    { "id": "beranda", "label": "Beranda" },
    { "id": "produk", "label": "Produk" },
    { "id": "kontak", "label": "Kontak" }
  ],
  "products": [
    {
      "id": 1,
      "slug": "produk-unggulan-1",
      "nama": "Produk Unggulan Satu",
      "asal": "Daerah Asal",
      "kategori": "kategori-a",
      "harga": 50000,
      "stok": 15,
      "deskripsi": "Deskripsi singkat rasa atau keunikan produk.",
      "img": "img/produk1.jpg",
      "alt": "Produk Satu"
    }
  ]
}
```

#### 2. `data/transaksi.json`

Awali dengan array kosong:

```json
[]
```

#### 3. `data/pengguna.json`

Berisi array admin awal (dibuat via seeder di bawah).

---

### Tahap 4: Seeder Admin Pertama (`database/buat_admin_pertama.php`)

```php
<?php
require __DIR__ . "/../api/lib/json_store.php";
$path = __DIR__ . "/../data/pengguna.json";
$data = bukaJsonTerkunci($path, $handle);

$idBaru = 1;
foreach ($data as $p) {
    if ($p["id"] >= $idBaru) $idBaru = $p["id"] + 1;
}

$data[] = [
    "id"         => $idBaru,
    "nama"       => "Administrator UMKM",
    "username"   => "admin",
    "kata_sandi" => password_hash("admin123", PASSWORD_DEFAULT),
];

tutupJsonTerkunci($handle, $data);
echo "Admin pertama berhasil dibuat. Username: admin | Sandi: admin123\n";
```

---

### Tahap 5: Endpoint REST API

#### 1. `api/produk.php` (Filter Kategori Dinamis)

```php
<?php
date_default_timezone_set("Asia/Makassar"); // Sesuaikan zona waktu
require_once __DIR__ . "/lib/json_store.php";

header("Content-Type: application/json");
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

echo json_encode($data);
```

#### 2. `api/checkout.php` (Validasi Stok & Pemotongan Atomic)

- Wajib validasi: `nama_pemesan`, `kontak`, kuantitas > 0, dan kuantitas <= stok saat ini.
- Kurangi stok pada file `produk.json` dengan kunci eksklusif (`flock`).
- Tulis data transaksi baru ke `transaksi.json` dengan kode unik `KN-XXXXXX` atau `TRX-XXXXXX`.
- Kembalikan respons JSON:
  ```json
  { "status": "sukses", "kode_transaksi": "TRX-A1B2C3D4", "total": 150000 }
  ```

#### 3. `api/struk.php` (Cetak PDF Faktur)

- Pastikan memanggil `require __DIR__ . "/../vendor/autoload.php";`.
- Gunakan fungsi pembungkus transliterasi teks Latin-1 untuk FPDF:
  ```php
  function teks(string $s): string {
      return iconv("UTF-8", "ISO-8859-1//TRANSLIT", $s);
  }
  ```
- Atur posisi logo simetris dan geser kursor baris vertikal dengan `$pdf->SetY(...)` sebelum judul struk dicetak untuk mencegah logo menimpa teks.

---

### Tahap 6: Antarmuka Publik & Logika JavaScript

#### 1. `index.html`

Elemen wajib:

- Header toko, navigasi menu, dan tombol ikon keranjang belanja (`#tombol-keranjang-header`) ber-badge counter.
- Bagian Katalog Produk: Filter kategori tombol dinamis, input pencarian, dan kontainer grid `#daftar-produk`.
- Bagian Keranjang Belanja: Kontainer item `#isi-keranjang`, ringkasan `#total-keranjang`, notifikasi `#status-checkout`, dan form checkout (`nama_pemesan` & `kontak`).
- Bagian Kontak: Formulir pesan dengan dua tombol aksi terpisah:
  - Tombol `type="button"` memanggil `kirimPesanWA(event)`
  - Tombol `type="button"` memanggil `kirimPesanEmail(event)`

#### 2. `js/keranjang.js` (Validasi Kuantitas & Batas Stok)

- Simpan array barang belanja di `localStorage.getItem("keranjang_umkm")`.
- Pada fungsi `tambahKeKeranjang(produk)`:
  - Periksa sisa stok dari data produk. Jika jumlah pesanan mencapai batas stok maksimum, tampilkan alert/pesan: _"Permintaan stok tidak sesuai: [Nama Produk] hanya tersisa X item."_
- Pada fungsi render: pastikan form checkout kembali terlihat (`formEl.hidden = false;`) jika keranjang terisi kembali setelah checkout sebelumnya.
- Saat checkout berhasil, lakukan `form.reset()` dan hapus keranjang dari `localStorage`.

#### 3. `js/kirimpesan.js` (WhatsApp & Mailpit)

- **Kirim WhatsApp**: Buat URL format `https://wa.me/{nomorHp}?text={encodedPesan}` dan buka tab baru dengan `window.open(url, "_blank")`.
- **Kirim Mailpit**: Kirim HTTP POST JSON ke endpoint lokal `http://localhost:8025/api/v1/send`.

---

### Tahap 7: Panel Admin (Backoffice)

#### 1. `admin/includes/proteksi.php`

Sertakan di baris paling atas setiap berkas admin:

```php
<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit;
}
```

#### 2. `admin/produk.php` (CRUD)

- **Tambah**: Hitung auto-increment ID (`$idBaru = max(id) + 1`), append ke array, simpan via `tutupJsonTerkunci`.
- **Ubah**: Cari index elemen yang cocok berdasarkan `id`, timpa array pada index tersebut, simpan via `tutupJsonTerkunci`.
- **Hapus**: Gunakan `array_values(array_filter(...))` sebelum disimpan agar indeks numerik array tetap terurut rapi.

---

## 4. Daftar Standar Kualitas & Anti-Pattern (Aturan Emas)

1. **JANGAN campur aduk key data**: Pastikan seluruh skrip PHP dan JS merujuk ke kunci `"products"` (jangan bergantian dengan `"produk"`).
2. **JANGAN cetak output sebelum FPDF header**: Hindari spasi kosong, `echo`, atau warning PHP sebelum `$pdf->Output()`.
3. **Wajib Transliterasi Latin-1 di FPDF**: Selalu bungkus teks dengan `iconv("UTF-8", "ISO-8859-1//TRANSLIT", $str)`.
4. **Wajib type="button" untuk tombol WhatsApp/Email**: Mencegah submit ganda / reload halaman yang tidak disengaja.
5. **Wajib array_values saat hapus data**: Mencegah format JSON berubah dari `[...]` menjadi `{"0": ..., "2": ...}`.
6. **Wajib file locking saat penulisan data**: Jangan gunakan `file_put_contents` biasa pada file transaksi dan produk yang sering diubah oleh pengunjung.

---

## 5. Cara Menyesuaikan untuk Tema UMKM Baru

Saat mengadaptasi untuk usaha baru (misal: Toko Roti, Batik, Souvenir, Snack Khas Daerah dan Pelayanan medis):

1. **Ganti Entitas Atribut Produk**:
   - Ganti label `asal` menjadi varian yang relevan (misal: `rasa`, `ukuran`, atau `motif`).
2. **Kategori Produk**:
   - Sesuaikan pengelompokan (misal: `manis`/`gurih` untuk roti, atau `tulis`/`cap` untuk batik).
3. **Prefix Transaksi**:
   - Ubah awalan kode transaksi pada `buatKodeTransaksi("ROTI")` atau `buatKodeTransaksi("BATIK")`.
4. **Kontak & Identitas**:
   - Sesuaikan nomor WhatsApp penerima pesanan di `js/kirimpesan.js` dan email pengirim di `data/produk.json`.
