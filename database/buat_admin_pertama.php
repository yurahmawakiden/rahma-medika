<?php
require __DIR__ . "/../api/lib/json_store.php";
$path = __DIR__ . "/../data/pengguna.json";
$data = bukaJsonTerkunci($path, $handle);

$sudahAda = false;
foreach ($data as $p) {
    if (isset($p["username"]) && $p["username"] === "admin") {
        $sudahAda = true;
        break;
    }
}

if (!$sudahAda) {
    $idBaru = 1;
    foreach ($data as $p) {
        if ($p["id"] >= $idBaru) $idBaru = $p["id"] + 1;
    }

    $data[] = [
        "id"         => $idBaru,
        "nama"       => "Administrator Rahma Medika",
        "username"   => "admin",
        "kata_sandi" => password_hash("admin123", PASSWORD_DEFAULT),
    ];
}

tutupJsonTerkunci($handle, $data);
echo "Admin pertama berhasil dibuat. Username: admin | Sandi: admin123\n";

