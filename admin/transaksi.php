<?php
require __DIR__ . "/includes/proteksi.php";
require_once __DIR__ . "/../api/lib/json_store.php";

$pathTransaksi = __DIR__ . "/../data/transaksi.json";
$pesanSukses = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["aksi"] ?? "") === "hapus_transaksi") {
    $kodeHapus = trim((string)($_POST["kode_transaksi"] ?? ""));
    $dataTrx = bukaJsonTerkunci($pathTransaksi, $handle);
    $dataTrx = array_values(array_filter($dataTrx, function ($t) use ($kodeHapus) {
        return ($t["kode_transaksi"] ?? "") !== $kodeHapus;
    }));
    tutupJsonTerkunci($handle, $dataTrx);
    $pesanSukses = "Transaksi $kodeHapus berhasil dihapus.";
}

$daftarTransaksi = array_reverse(bacaJson($pathTransaksi));

function rupiahTrx(int $n): string {
    return "Rp " . number_format($n, 0, ",", ".");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rekap Transaksi Pasien — Admin Rahma Medika</title>
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
          <li class="nav-item"><a class="nav-link admin-nav-link" href="produk.php">🩺 Layanan Medis (Produk)</a></li>
          <li class="nav-item"><a class="nav-link admin-nav-link active" href="transaksi.php">🧾 Rekap Transaksi</a></li>
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

    <div class="content-card">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
          <h5 class="fw-bold mb-0">🧾 Rekapitulasi Transaksi & Tindakan Medis Pasien</h5>
          <p class="text-muted small mb-0">Memuat data umum pasien (Nama, No WA, Umur), detail Path 1–6, rincian biaya & jasa, serta cetak struk PDF</p>
        </div>
      </div>

      <div class="table-responsive">
        <table id="tabel-admin" class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Kode Struk</th>
              <th>Waktu</th>
              <th>Data Umum Pasien</th>
              <th>Rincian Tindakan & Input Medis</th>
              <th>Biaya & Jasa</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($daftarTransaksi as $trx): ?>
              <tr>
                <td><span class="badge bg-dark"><?= htmlspecialchars($trx["kode_transaksi"] ?? "-") ?></span></td>
                <td class="small"><?= htmlspecialchars($trx["tanggal"] ?? "-") ?></td>
                <td>
                  <div class="fw-bold"><?= htmlspecialchars($trx["nama_pemesan"] ?? "-") ?></div>
                  <div class="small text-muted">WA: <?= htmlspecialchars($trx["kontak"] ?? "-") ?></div>
                  <div class="small text-muted">Umur: <?= !empty($trx["umur"]) ? (int)$trx["umur"] . " Tahun" : "-" ?></div>
                </td>
                <td class="small">
                  <?php foreach (($trx["items"] ?? []) as $it): ?>
                    <div class="mb-1">
                      <span class="badge bg-info text-dark"><?= htmlspecialchars($it["path"] ?? "Layanan") ?></span>
                      <strong><?= htmlspecialchars($it["nama"] ?? "-") ?></strong> (<?= (int)($it["jumlah"] ?? 1) ?>x)
                      <?php
                        $dm = $it["detail_medis"] ?? [];
                        $infoMedis = [];
                        if (!empty($dm["jenis_cairan"])) $infoMedis[] = "Cairan: " . htmlspecialchars($dm["jenis_cairan"]);
                        if (!empty($dm["riwayat_penyakit"])) $infoMedis[] = "Riwayat: " . htmlspecialchars($dm["riwayat_penyakit"]);
                        if (!empty($dm["jenis_luka"])) $infoMedis[] = "Luka: " . htmlspecialchars($dm["jenis_luka"]);
                        if (!empty($dm["alergi"])) $infoMedis[] = "Alergi: " . htmlspecialchars($dm["alergi"]);
                        if (!empty($dm["keluhan"])) $infoMedis[] = "Keluhan: " . htmlspecialchars($dm["keluhan"]);
                      ?>
                      <?php if (count($infoMedis) > 0): ?>
                        <div class="text-secondary fst-italic ms-2">↳ <?= implode(" | ", $infoMedis) ?></div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </td>
                <td class="small">
                  <div>Biaya: <?= rupiahTrx((int)($trx["subtotal_biaya"] ?? 0)) ?></div>
                  <div>Jasa: <?= rupiahTrx((int)($trx["total_jasa"] ?? 0)) ?></div>
                  <div class="fw-bold text-primary">Total: <?= rupiahTrx((int)($trx["total"] ?? 0)) ?></div>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="../api/struk.php?kode=<?= urlencode($trx["kode_transaksi"] ?? "") ?>" target="_blank" class="btn btn-sm btn-success rounded-pill px-3">
                      🖨️ Struk
                    </a>
                    <form method="POST" action="transaksi.php" onsubmit="return confirm('Hapus riwayat transaksi ini?');">
                      <input type="hidden" name="aksi" value="hapus_transaksi">
                      <input type="hidden" name="kode_transaksi" value="<?= htmlspecialchars($trx["kode_transaksi"] ?? "") ?>">
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest"></script>
  <script src="js/admin.js"></script>
</body>
</html>

