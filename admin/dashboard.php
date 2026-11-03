<?php
require __DIR__ . "/includes/proteksi.php";
require_once __DIR__ . "/../api/lib/json_store.php";

$dataProduk      = bacaJson(__DIR__ . "/../data/produk.json");
$daftarProduk    = $dataProduk["products"] ?? [];
$daftarTransaksi = bacaJson(__DIR__ . "/../data/transaksi.json");
$daftarPengguna  = bacaJson(__DIR__ . "/../data/pengguna.json");

$totalLayanan = count($daftarProduk);
$totalKuota   = 0;
foreach ($daftarProduk as $p) {
    $totalKuota += (int)($p["stok"] ?? 0);
}

$totalTransaksi = count($daftarTransaksi);
$totalOmzet     = 0;
$totalJasaMedis = 0;
foreach ($daftarTransaksi as $t) {
    $totalOmzet     += (int)($t["total"] ?? 0);
    $totalJasaMedis += (int)($t["total_jasa"] ?? 0);
}

$transaksiTerbaru = array_slice(array_reverse($daftarTransaksi), 0, 5);

function rupiahAdmin(int $nominal): string {
    return "Rp " . number_format($nominal, 0, ",", ".");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin — Rahma Medika</title>
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
          <li class="nav-item"><a class="nav-link admin-nav-link active" href="dashboard.php">📊 Dashboard</a></li>
          <li class="nav-item"><a class="nav-link admin-nav-link" href="produk.php">🩺 Layanan Medis (Produk)</a></li>
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
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
      <div>
        <h3 class="fw-bold mb-0">Selamat Datang, <?= htmlspecialchars($_SESSION["admin_nama"] ?? "Admin") ?>!</h3>
        <p class="text-muted small mb-0">Ringkasan operasional layanan medis & transaksi pasien Rahma Medika</p>
      </div>
      <span class="badge bg-primary rounded-pill px-3 py-2">Akun: <?= htmlspecialchars($_SESSION["admin_username"] ?? "admin") ?></span>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#ffffff,#e0f2fe);">
          <div class="text-muted small fw-semibold">Jenis Layanan Medis (Path)</div>
          <div class="fs-2 fw-bold text-primary my-1"><?= $totalLayanan ?> Layanan</div>
          <div class="small text-secondary">Total Sisa Kuota: <strong><?= $totalKuota ?></strong></div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#ffffff,#f3e8ff);">
          <div class="text-muted small fw-semibold">Total Transaksi Pasien</div>
          <div class="fs-2 fw-bold my-1" style="color:#7e22ce;"><?= $totalTransaksi ?> Pasien</div>
          <div class="small text-secondary">Tercatat di <code>transaksi.json</code></div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#ffffff,#fef3c7);">
          <div class="text-muted small fw-semibold">Akumulasi Jasa Medis</div>
          <div class="fs-3 fw-bold my-1" style="color:#b45309;"><?= rupiahAdmin($totalJasaMedis) ?></div>
          <div class="small text-secondary">Total komponen jasa tenaga medis</div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#ffffff,#dcfce7);">
          <div class="text-muted small fw-semibold">Total Omzet (Biaya & Jasa)</div>
          <div class="fs-3 fw-bold text-success my-1"><?= rupiahAdmin($totalOmzet) ?></div>
          <div class="small text-secondary">Admin Aktif: <strong><?= count($daftarPengguna) ?></strong></div>
        </div>
      </div>
    </div>

    <div class="content-card">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="fw-bold mb-0">🧾 5 Transaksi Pasien Terbaru</h5>
        <a href="transaksi.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua Transaksi →</a>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Kode Struk</th>
              <th>Tanggal</th>
              <th>Data Umum Pasien</th>
              <th>Layanan Medis (Path)</th>
              <th>Total Biaya & Jasa</th>
              <th>Struk PDF</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($transaksiTerbaru) === 0): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Belum ada transaksi layanan medis tercatat.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($transaksiTerbaru as $trx): ?>
                <tr>
                  <td><span class="badge bg-dark"><?= htmlspecialchars($trx["kode_transaksi"] ?? "-") ?></span></td>
                  <td class="small"><?= htmlspecialchars($trx["tanggal"] ?? "-") ?></td>
                  <td>
                    <div class="fw-bold"><?= htmlspecialchars($trx["nama_pemesan"] ?? "-") ?></div>
                    <div class="small text-muted">
                      WA: <?= htmlspecialchars($trx["kontak"] ?? "-") ?>
                      <?= !empty($trx["umur"]) ? " | Umur: " . (int)$trx["umur"] . " Thn" : "" ?>
                    </div>
                  </td>
                  <td class="small">
                    <?php foreach (($trx["items"] ?? []) as $it): ?>
                      <div>&bull; <strong><?= htmlspecialchars($it["path"] ?? "") ?>:</strong> <?= htmlspecialchars($it["nama"] ?? "") ?> (<?= (int)($it["jumlah"] ?? 1) ?>x)</div>
                    <?php endforeach; ?>
                  </td>
                  <td class="fw-bold text-primary"><?= rupiahAdmin((int)($trx["total"] ?? 0)) ?></td>
                  <td>
                    <a href="../api/struk.php?kode=<?= urlencode($trx["kode_transaksi"] ?? "") ?>" target="_blank" class="btn btn-sm btn-success rounded-pill px-3">
                      🖨️ Cetak PDF
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

