<?php
require __DIR__ . "/includes/proteksi.php";
require_once __DIR__ . "/../api/lib/json_store.php";

$pathProduk = __DIR__ . "/../data/produk.json";
$pesanSukses = "";
$pesanError  = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";
    $data = bukaJsonTerkunci($pathProduk, $handle);
    if (!isset($data["products"]) || !is_array($data["products"])) {
        $data["products"] = [];
    }

    if ($aksi === "tambah") {
        $idBaru = 1;
        foreach ($data["products"] as $item) {
            if ((int)$item["id"] >= $idBaru) {
                $idBaru = (int)$item["id"] + 1;
            }
        }

        $nama       = trim((string)($_POST["nama"] ?? ""));
        $pathCode   = trim((string)($_POST["path"] ?? ("Path " . $idBaru)));
        $asal       = trim((string)($_POST["asal"] ?? "Layanan Medis"));
        $kategori   = trim((string)($_POST["kategori"] ?? "tindakan-medis"));
        $harga      = max(0, (int)($_POST["harga"] ?? 0));
        $biayaJasa  = max(0, (int)($_POST["biaya_jasa"] ?? 0));
        $stok       = max(0, (int)($_POST["stok"] ?? 0));
        $jenisInput = trim((string)($_POST["jenis_input"] ?? "none"));
        $labelInput = trim((string)($_POST["label_input"] ?? "Langsung Tindakan Pemeriksaan"));
        $img        = trim((string)($_POST["img"] ?? "img/logo.svg"));
        $deskripsi  = trim((string)($_POST["deskripsi"] ?? ""));
        $slug       = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $nama));

        $data["products"][] = [
            "id"          => $idBaru,
            "slug"        => trim($slug, "-"),
            "path"        => $pathCode,
            "nama"        => $nama,
            "asal"        => $asal,
            "kategori"    => $kategori,
            "harga"       => $harga,
            "biaya_jasa"  => $biayaJasa,
            "stok"        => $stok,
            "butuh_input" => $jenisInput !== "none",
            "jenis_input" => $jenisInput,
            "label_input" => $labelInput,
            "deskripsi"   => $deskripsi,
            "img"         => $img,
            "alt"         => $nama,
        ];

        tutupJsonTerkunci($handle, $data);
        $pesanSukses = "Layanan medis '$nama' berhasil ditambahkan.";
    } elseif ($aksi === "ubah") {
        $idUbah = (int)($_POST["id_produk"] ?? 0);
        $ditemukan = false;

        foreach ($data["products"] as $idx => $item) {
            if ((int)$item["id"] === $idUbah) {
                $nama       = trim((string)($_POST["nama"] ?? $item["nama"]));
                $jenisInput = trim((string)($_POST["jenis_input"] ?? ($item["jenis_input"] ?? "none")));
                $slug       = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $nama));

                $data["products"][$idx] = [
                    "id"          => $idUbah,
                    "slug"        => trim($slug, "-"),
                    "path"        => trim((string)($_POST["path"] ?? ($item["path"] ?? "Path " . $idUbah))),
                    "nama"        => $nama,
                    "asal"        => trim((string)($_POST["asal"] ?? ($item["asal"] ?? ""))),
                    "kategori"    => trim((string)($_POST["kategori"] ?? ($item["kategori"] ?? "tindakan-medis"))),
                    "harga"       => max(0, (int)($_POST["harga"] ?? 0)),
                    "biaya_jasa"  => max(0, (int)($_POST["biaya_jasa"] ?? 0)),
                    "stok"        => max(0, (int)($_POST["stok"] ?? 0)),
                    "butuh_input" => $jenisInput !== "none",
                    "jenis_input" => $jenisInput,
                    "label_input" => trim((string)($_POST["label_input"] ?? ($item["label_input"] ?? ""))),
                    "deskripsi"   => trim((string)($_POST["deskripsi"] ?? ($item["deskripsi"] ?? ""))),
                    "img"         => trim((string)($_POST["img"] ?? ($item["img"] ?? "img/logo.svg"))),
                    "alt"         => $nama,
                ];
                $ditemukan = true;
                break;
            }
        }

        tutupJsonTerkunci($handle, $data);
        if ($ditemukan) {
            $pesanSukses = "Data layanan medis #$idUbah berhasil diperbarui.";
        } else {
            $pesanError = "Layanan medis tidak ditemukan.";
        }
    } elseif ($aksi === "hapus") {
        $idHapus = (int)($_POST["id_hapus"] ?? 0);
        // Wajib array_values(array_filter(...)) sesuai aturan emas SKILL.md
        $data["products"] = array_values(array_filter($data["products"], function ($item) use ($idHapus) {
            return (int)$item["id"] !== $idHapus;
        }));
        tutupJsonTerkunci($handle, $data);
        $pesanSukses = "Layanan medis #$idHapus berhasil dihapus.";
    } else {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

$dataProduk   = bacaJson($pathProduk);
$daftarProduk = $dataProduk["products"] ?? [];

function rupiahProduk(int $n): string {
    return "Rp " . number_format($n, 0, ",", ".");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Layanan Medis — Admin Rahma Medika</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark admin-navbar py-3 mb-4">
    <div class="container">
      <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="dashboard.php">
        <img src="../img/logo.svg" alt="Logo" width="34" height="34">
        <span>Admin Rahma Medika</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navAdmin">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
          <li class="nav-item"><a class="nav-link admin-nav-link" href="dashboard.php">📊 Dashboard</a></li>
          <li class="nav-item"><a class="nav-link admin-nav-link active" href="produk.php">🩺 Layanan Medis (Produk)</a></li>
          <li class="nav-item"><a class="nav-link admin-nav-link" href="transaksi.php">🧾 Rekap Transaksi</a></li>
          <li class="nav-item"><a class="nav-link admin-nav-link" href="pengguna.php">👥 Pengguna Admin</a></li>
        </ul>
        <div class="d-flex align-items-center gap-2">
          <a href="../index.html" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-3">🌐 Lihat Website</a>
          <a href="logout.php" class="btn btn-sm btn-danger rounded-pill px-3">Keluar</a>
        </div>
      </div>
    </div>
  </nav>

  <div class="container pb-5">
    <?php if ($pesanSukses !== ""): ?>
      <div class="alert alert-success rounded-4"><?= htmlspecialchars($pesanSukses) ?></div>
    <?php endif; ?>
    <?php if ($pesanError !== ""): ?>
      <div class="alert alert-danger rounded-4"><?= htmlspecialchars($pesanError) ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Form Tambah / Ubah Layanan Medis -->
      <div class="col-lg-4">
        <div class="content-card" id="card_form_produk">
          <h5 class="fw-bold mb-3" id="judul_form_produk">➕ Tambah Layanan Medis Baru</h5>
          <form method="POST" action="produk.php" id="form_admin_produk">
            <input type="hidden" name="aksi" id="aksi_form_produk" value="tambah">
            <input type="hidden" name="id_produk" id="id_produk" value="">

            <div class="row g-2 mb-2">
              <div class="col-4">
                <label class="form-label small fw-semibold">Kode Path</label>
                <input type="text" name="path" id="path" class="form-control form-control-sm" placeholder="Path 7" required>
              </div>
              <div class="col-8">
                <label class="form-label small fw-semibold">Nama Layanan Medis *</label>
                <input type="text" name="nama" id="nama" class="form-control form-control-sm" placeholder="Contoh: Tindakan Nebulizer" required>
              </div>
            </div>

            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small fw-semibold">Sub-Unit / Jenis</label>
                <input type="text" name="asal" id="asal" class="form-control form-control-sm" placeholder="Pemeriksaan Lab" required>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Kategori</label>
                <select name="kategori" id="kategori" class="form-select form-select-sm">
                  <option value="tindakan-medis">tindakan-medis</option>
                  <option value="cek-lab">cek-lab</option>
                  <option value="tanda-vital">tanda-vital</option>
                </select>
              </div>
            </div>

            <div class="row g-2 mb-2">
              <div class="col-4">
                <label class="form-label small fw-semibold">Biaya (Rp) *</label>
                <input type="number" name="harga" id="harga" class="form-control form-control-sm" min="0" value="25000" required>
              </div>
              <div class="col-4">
                <label class="form-label small fw-semibold">Jasa (Rp) *</label>
                <input type="number" name="biaya_jasa" id="biaya_jasa" class="form-control form-control-sm" min="0" value="10000" required>
              </div>
              <div class="col-4">
                <label class="form-label small fw-semibold">Kuota/Stok *</label>
                <input type="number" name="stok" id="stok" class="form-control form-control-sm" min="0" value="30" required>
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-semibold">Tipe Input Medis (Flowchart 2)</label>
              <select name="jenis_input" id="jenis_input" class="form-select form-select-sm">
                <option value="none">Langsung Tindakan (Seperti Path 2, 3, 4)</option>
                <option value="infus">Input Jenis Cairan & Riwayat Penyakit (Path 1)</option>
                <option value="rawat_luka">Input Jenis Luka & Alergi (Path 5)</option>
                <option value="tekanan_darah">Input Keluhan Pasien (Path 6)</option>
              </select>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-semibold">Label Keterangan Input</label>
              <input type="text" name="label_input" id="label_input" class="form-control form-control-sm" value="Langsung Tindakan Pemeriksaan">
            </div>

            <div class="mb-2">
              <label class="form-label small fw-semibold">Path Gambar (img/...)</label>
              <input type="text" name="img" id="img" class="form-control form-control-sm" value="img/logo.svg">
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Deskripsi Layanan</label>
              <textarea name="deskripsi" id="deskripsi" class="form-control form-control-sm" rows="3" required></textarea>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary rounded-pill fw-bold flex-grow-1">Simpan Layanan</button>
              <button type="button" id="btn_batal_ubah" class="btn btn-outline-secondary rounded-pill" onclick="resetFormProdukAdmin()" hidden>Batal</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Tabel Daftar Layanan Medis -->
      <div class="col-lg-8">
        <div class="content-card">
          <h5 class="fw-bold mb-3">🩺 Daftar Katalog Layanan Medis (<code>data/produk.json</code>)</h5>
          <div class="table-responsive">
            <table id="tabel-admin" class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Path</th>
                  <th>Layanan Medis</th>
                  <th>Kategori</th>
                  <th>Biaya & Jasa</th>
                  <th>Kuota</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($daftarProduk as $p): ?>
                  <tr>
                    <td><span class="badge bg-info text-dark fw-bold"><?= htmlspecialchars($p["path"] ?? ("Path " . $p["id"])) ?></span></td>
                    <td>
                      <div class="fw-bold"><?= htmlspecialchars($p["nama"]) ?></div>
                      <div class="small text-muted"><?= htmlspecialchars($p["label_input"] ?? "") ?></div>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p["kategori"] ?? "") ?></span></td>
                    <td class="small">
                      <div>Biaya: <strong><?= rupiahProduk((int)$p["harga"]) ?></strong></div>
                      <div class="text-primary">Jasa: +<?= rupiahProduk((int)($p["biaya_jasa"] ?? 0)) ?></div>
                    </td>
                    <td>
                      <span class="badge <?= ((int)$p["stok"] > 5) ? 'bg-success' : 'bg-danger' ?>">
                        <?= (int)$p["stok"] ?>
                      </span>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <button
                          type="button"
                          class="btn btn-sm btn-warning rounded-pill px-2"
                          onclick='isiFormUbahProduk(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                        >
                          ✏️ Ubah
                        </button>
                        <form method="POST" action="produk.php" onsubmit="return confirm('Hapus layanan medis ini?');">
                          <input type="hidden" name="aksi" value="hapus">
                          <input type="hidden" name="id_hapus" value="<?= (int)$p["id"] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2">🗑️</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest"></script>
  <script src="js/admin.js"></script>
</body>
</html>

