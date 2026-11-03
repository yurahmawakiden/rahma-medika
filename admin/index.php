<?php
session_start();
require_once __DIR__ . "/../api/lib/json_store.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$pesanError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username  = trim((string)($_POST["username"] ?? ""));
    $kataSandi = (string)($_POST["kata_sandi"] ?? "");

    $daftarPengguna = bacaJson(__DIR__ . "/../data/pengguna.json");
    $akunCocok = null;

    foreach ($daftarPengguna as $p) {
        if (isset($p["username"]) && $p["username"] === $username) {
            if (password_verify($kataSandi, $p["kata_sandi"] ?? "")) {
                $akunCocok = $p;
                break;
            }
        }
    }

    if ($akunCocok !== null) {
        $_SESSION["admin_id"]       = $akunCocok["id"];
        $_SESSION["admin_nama"]     = $akunCocok["nama"];
        $_SESSION["admin_username"] = $akunCocok["username"];
        header("Location: dashboard.php");
        exit;
    } else {
        $pesanError = "Username atau kata sandi salah. Silakan coba kembali.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin — Rahma Medika</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="text-center mb-4">
        <img src="../img/logo.svg" alt="Logo Rahma Medika" width="58" height="58" class="mb-2">
        <h4 class="fw-bold text-dark mb-1">Panel Admin Rahma Medika</h4>
        <p class="text-muted small mb-0">Masuk untuk mengelola layanan medis & rekap transaksi pasien</p>
      </div>

      <?php if ($pesanError !== ""): ?>
        <div class="alert alert-danger rounded-3 small py-2">
          <?= htmlspecialchars($pesanError) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="index.php">
        <div class="mb-3">
          <label for="username" class="form-label fw-semibold small">Username</label>
          <input type="text" id="username" name="username" class="form-control rounded-3" placeholder="admin" required autofocus>
        </div>
        <div class="mb-3">
          <label for="kata_sandi" class="form-label fw-semibold small">Kata Sandi</label>
          <input type="password" id="kata_sandi" name="kata_sandi" class="form-control rounded-3" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 mb-3">
          Masuk ke Dashboard →
        </button>
      </form>

      <div class="text-center border-top pt-3">
        <small class="text-muted d-block mb-2">Akun bawaan: <code>admin</code> / <code>admin123</code></small>
        <a href="../index.html" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
          ← Kembali ke Halaman Utama Website
        </a>
      </div>
    </div>
  </div>
</body>
</html>

