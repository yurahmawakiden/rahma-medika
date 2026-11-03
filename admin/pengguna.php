<?php
require __DIR__ . "/includes/proteksi.php";
require_once __DIR__ . "/../api/lib/json_store.php";

$pathPengguna = __DIR__ . "/../data/pengguna.json";
$pesanSukses = "";
$pesanError  = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";
    $data = bukaJsonTerkunci($pathPengguna, $handle);

    if ($aksi === "tambah") {
        $nama      = trim((string)($_POST["nama"] ?? ""));
        $username  = trim((string)($_POST["username"] ?? ""));
        $kataSandi = (string)($_POST["kata_sandi"] ?? "");

        $duplikat = false;
        $idBaru = 1;
        foreach ($data as $p) {
            if ((int)$p["id"] >= $idBaru) {
                $idBaru = (int)$p["id"] + 1;
            }
            if (strcasecmp($p["username"] ?? "", $username) === 0) {
                $duplikat = true;
            }
        }

        if ($duplikat) {
            flock($handle, LOCK_UN);
            fclose($handle);
            $pesanError = "Username '$username' sudah terdaftar.";
        } elseif ($nama === "" || $username === "" || $kataSandi === "") {
            flock($handle, LOCK_UN);
            fclose($handle);
            $pesanError = "Semua kolom wajib diisi.";
        } else {
            $data[] = [
                "id"         => $idBaru,
                "nama"       => $nama,
                "username"   => $username,
                "kata_sandi" => password_hash($kataSandi, PASSWORD_DEFAULT),
            ];
            tutupJsonTerkunci($handle, $data);
            $pesanSukses = "Akun administrator '$username' berhasil ditambahkan.";
        }
    } elseif ($aksi === "hapus") {
        $idHapus = (int)($_POST["id_hapus"] ?? 0);
        if (count($data) <= 1) {
            flock($handle, LOCK_UN);
            fclose($handle);
            $pesanError = "Tidak dapat menghapus satu-satunya akun administrator.";
        } else {
            // Wajib array_values(array_filter(...)) sesuai SKILL.md
            $data = array_values(array_filter($data, function ($p) use ($idHapus) {
                return (int)$p["id"] !== $idHapus;
            }));
            tutupJsonTerkunci($handle, $data);
            $pesanSukses = "Akun administrator #$idHapus berhasil dihapus.";
        }
    } else {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

$daftarPengguna = bacaJson($pathPengguna);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Pengguna Admin — Rahma Medika</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
          <li class="nav-item"><a class="nav-link admin-nav-link" href="transaksi.php">🧾 Rekap Transaksi</a></li>
          <li class="nav-item"><a class="nav-link admin-nav-link active" href="pengguna.php">👥 Pengguna Admin</a></li>
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
      <div class="col-lg-4">
        <div class="content-card">
          <h5 class="fw-bold mb-3">➕ Tambah Administrator</h5>
          <form method="POST" action="pengguna.php">
            <input type="hidden" name="aksi" value="tambah">
            <div class="mb-3">
              <label class="form-label small fw-semibold">Nama Lengkap</label>
              <input type="text" name="nama" class="form-control" placeholder="Petugas Medis Rahma" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Username</label>
              <input type="text" name="username" class="form-control" placeholder="petugas1" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Kata Sandi</label>
              <input type="password" name="kata_sandi" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Simpan Akun Admin</button>
          </form>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="content-card">
          <h5 class="fw-bold mb-3">👥 Daftar Akun Administrator (<code>data/pengguna.json</code>)</h5>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Nama Lengkap</th>
                  <th>Username</th>
                  <th>Hash Keamanan</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($daftarPengguna as $p): ?>
                  <tr>
                    <td>#<?= (int)$p["id"] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($p["nama"]) ?></td>
                    <td><code><?= htmlspecialchars($p["username"]) ?></code></td>
                    <td><span class="badge bg-success-subtle text-success border">bcrypt (password_hash)</span></td>
                    <td>
                      <form method="POST" action="pengguna.php" onsubmit="return confirm('Hapus akun admin ini?');">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id_hapus" value="<?= (int)$p["id"] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Hapus</button>
                      </form>
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
</body>
</html>

