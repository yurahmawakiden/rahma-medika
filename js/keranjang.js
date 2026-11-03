// js/keranjang.js - State keranjang localStorage, stok limit, alur Flowchart 2 (Input Pasien -> 6 Path -> Ada Transaksi Lagi? -> Hitung Total Biaya & Jasa -> Cetak Struk)

const STORAGE_KEY_KERANJANG = "keranjang_umkm";
const STORAGE_KEY_PASIEN = "pasien_rahma_medika";

let layananSedangDipilih = null;

function ambilKeranjang() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_KERANJANG);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed : [];
  } catch (e) {
    return [];
  }
}

function simpanKeranjang(keranjang) {
  localStorage.setItem(STORAGE_KEY_KERANJANG, JSON.stringify(keranjang));
  renderKeranjang();
}

function ambilDataPasien() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_PASIEN);
    return raw ? JSON.parse(raw) : { nama_pemesan: "", kontak: "", umur: "" };
  } catch (e) {
    return { nama_pemesan: "", kontak: "", umur: "" };
  }
}

function simpanDataPasien(data) {
  localStorage.setItem(STORAGE_KEY_PASIEN, JSON.stringify(data));
  sinkronkanInputPasien(data);
}

function sinkronkanInputPasien(data) {
  const fields = [
    ["pasien_nama_awal", "nama_pemesan", data.nama_pemesan || ""],
    ["pasien_wa_awal", "kontak", data.kontak || ""],
    ["pasien_umur_awal", "umur", data.umur || ""]
  ];

  fields.forEach(([idAwal, idCheckout, val]) => {
    const el1 = document.getElementById(idAwal);
    const el2 = document.getElementById(idCheckout);
    if (el1 && el1.value !== String(val)) el1.value = val;
    if (el2 && el2.value !== String(val)) el2.value = val;
  });

  const badgePasien = document.getElementById("badge-status-pasien");
  if (badgePasien) {
    if (data.nama_pemesan && data.kontak && data.umur) {
      badgePasien.className = "badge rounded-pill bg-success px-3 py-2";
      badgePasien.textContent = `✓ Pasien Aktif: ${data.nama_pemesan} (${data.umur} Thn | WA: ${data.kontak})`;
    } else {
      badgePasien.className = "badge rounded-pill bg-warning text-dark px-3 py-2";
      badgePasien.textContent = "Silakan lengkapi Nama, No. WA, dan Umur Pasien";
    }
  }
}

window.simpanDataUmumPasien = function () {
  const nama = (document.getElementById("pasien_nama_awal")?.value || "").trim();
  const wa = (document.getElementById("pasien_wa_awal")?.value || "").trim();
  const umur = (document.getElementById("pasien_umur_awal")?.value || "").trim();

  if (!nama || !wa || !umur) {
    alert("Mohon lengkapi Data Umum Pasien: Nama, No. WA, dan Umur sesuai alur layanan medis.");
    return false;
  }

  simpanDataPasien({ nama_pemesan: nama, kontak: wa, umur: umur });
  tandaiStepAktif(2);

  const seksiPath = document.getElementById("seksi-pilih-path");
  if (seksiPath) {
    seksiPath.scrollIntoView({ behavior: "smooth", block: "center" });
  }
  return true;
};

function tandaiStepAktif(stepNomor) {
  for (let i = 1; i <= 5; i++) {
    const el = document.getElementById(`step-medis-${i}`);
    if (el) {
      el.classList.toggle("active", i === stepNomor);
    }
  }
}

// Dipanggil saat tombol Pilih Layanan atau tombol Path 1-6 diklik
window.pilihLayananBerdasarkanId = function (idProduk) {
  const produk = (window.katalogProduk || []).find((p) => Number(p.id) === Number(idProduk));
  if (!produk) return;

  // Cek total kuantitas produk ini di keranjang terlebih dahulu (Batas Stok)
  const keranjang = ambilKeranjang();
  const totalDiKeranjang = keranjang
    .filter((item) => Number(item.id) === Number(produk.id))
    .reduce((acc, item) => acc + Number(item.jumlah || 1), 0);

  if (totalDiKeranjang >= Number(produk.stok)) {
    alert(`Permintaan stok tidak sesuai: ${produk.nama} hanya tersisa ${produk.stok} item.`);
    return;
  }

  // Jika layanan membutuhkan input medis khusus sesuai Flowchart 2 (Path 1, Path 5, Path 6)
  if (produk.butuh_input) {
    bukaModalInputMedis(produk);
  } else {
    // Path 2 (Cek Gula Darah), Path 3 (Cek Kolesterol), Path 4 (Cek Asam Urat) langsung masuk tindakan
    tambahKeKeranjang(produk, {});
  }
};

function bukaModalInputMedis(produk) {
  layananSedangDipilih = produk;
  const modalEl = document.getElementById("modal-input-medis");
  const judulEl = document.getElementById("judul-modal-medis");
  const bodyEl = document.getElementById("body-modal-medis");

  if (!modalEl || !judulEl || !bodyEl) return;

  judulEl.innerHTML = `<span class="badge bg-primary me-2">${produk.path || "Path"}</span> ${produk.nama}`;

  if (produk.jenis_input === "infus") {
    // Path 1: Input Jenis Cairan, Riwayat Penyakit
    bodyEl.innerHTML = `
      <div class="simbol-input mb-3">
        <div class="fw-bold mb-2">💧 Input Path 1: Tindakan Infus</div>
        <div class="mb-3">
          <label class="form-label fw-semibold small">Jenis Cairan Infus <span class="text-danger">*</span></label>
          <select id="input_jenis_cairan" class="form-select mb-2" required>
            <option value="">-- Pilih Jenis Cairan Infus --</option>
            <option value="NaCl 0.9% (Normal Saline)">NaCl 0.9% (Normal Saline)</option>
            <option value="Ringer Laktat (RL)">Ringer Laktat (RL)</option>
            <option value="Dextrose 5% (Glukosa)">Dextrose 5% (Glukosa)</option>
            <option value="Infus Multivitamin / Immune Booster">Infus Multivitamin / Immune Booster</option>
          </select>
        </div>
        <div class="mb-1">
          <label class="form-label fw-semibold small">Riwayat Penyakit Pasien <span class="text-danger">*</span></label>
          <input type="text" id="input_riwayat_penyakit" class="form-control" placeholder="Contoh: Maag akut, dehidrasi, atau tidak ada" required>
        </div>
      </div>
    `;
  } else if (produk.jenis_input === "rawat_luka") {
    // Path 5: Input Jenis Luka, Alergi
    bodyEl.innerHTML = `
      <div class="simbol-input mb-3">
        <div class="fw-bold mb-2">🩹 Input Path 5: Tindakan Rawat Luka</div>
        <div class="mb-3">
          <label class="form-label fw-semibold small">Jenis Luka <span class="text-danger">*</span></label>
          <select id="input_jenis_luka" class="form-select mb-2" required>
            <option value="">-- Pilih Jenis Luka --</option>
            <option value="Luka Pasca Operasi (Post-Op)">Luka Pasca Operasi (Post-Op)</option>
            <option value="Luka Diabetes (Ulkus Diabetikum)">Luka Diabetes (Ulkus Diabetikum)</option>
            <option value="Luka Lecet / Kecelakaan Ringan">Luka Lecet / Kecelakaan Ringan</option>
            <option value="Luka Bakar Ringan / Dekubitus">Luka Bakar Ringan / Dekubitus</option>
          </select>
        </div>
        <div class="mb-1">
          <label class="form-label fw-semibold small">Riwayat Alergi (Obat / Plester / Antiseptik) <span class="text-danger">*</span></label>
          <input type="text" id="input_alergi" class="form-control" placeholder="Contoh: Tidak ada alergi / Alergi plester coklat" required>
        </div>
      </div>
    `;
  } else if (produk.jenis_input === "tekanan_darah") {
    // Path 6: Input Keluhan
    bodyEl.innerHTML = `
      <div class="simbol-input mb-3">
        <div class="fw-bold mb-2">🩺 Input Path 6: Cek Tekanan Darah</div>
        <div class="mb-1">
          <label class="form-label fw-semibold small">Keluhan Pasien Saat Ini <span class="text-danger">*</span></label>
          <textarea id="input_keluhan" class="form-control" rows="3" placeholder="Contoh: Pusing sejak pagi, tengkuk terasa berat, atau kontrol rutin" required></textarea>
        </div>
      </div>
    `;
  }

  modalEl.hidden = false;
}

window.tutupModalInputMedis = function () {
  const modalEl = document.getElementById("modal-input-medis");
  if (modalEl) modalEl.hidden = true;
  layananSedangDipilih = null;
};

window.konfirmasiInputMedis = function (event) {
  if (event) event.preventDefault();
  if (!layananSedangDipilih) return;

  const p = layananSedangDipilih;
  const detailMedis = {};

  if (p.jenis_input === "infus") {
    const cairan = (document.getElementById("input_jenis_cairan")?.value || "").trim();
    const riwayat = (document.getElementById("input_riwayat_penyakit")?.value || "").trim();
    if (!cairan || !riwayat) {
      alert("Mohon isi Jenis Cairan dan Riwayat Penyakit terlebih dahulu.");
      return;
    }
    detailMedis.jenis_cairan = cairan;
    detailMedis.riwayat_penyakit = riwayat;
  } else if (p.jenis_input === "rawat_luka") {
    const jenisLuka = (document.getElementById("input_jenis_luka")?.value || "").trim();
    const alergi = (document.getElementById("input_alergi")?.value || "").trim();
    if (!jenisLuka || !alergi) {
      alert("Mohon isi Jenis Luka dan Riwayat Alergi terlebih dahulu.");
      return;
    }
    detailMedis.jenis_luka = jenisLuka;
    detailMedis.alergi = alergi;
  } else if (p.jenis_input === "tekanan_darah") {
    const keluhan = (document.getElementById("input_keluhan")?.value || "").trim();
    if (!keluhan) {
      alert("Mohon isi Keluhan pasien terlebih dahulu.");
      return;
    }
    detailMedis.keluhan = keluhan;
  }

  tutupModalInputMedis();
  tambahKeKeranjang(p, detailMedis);
};

// Fungsi standar SKILL.md: tambahKeKeranjang(produk, detailMedis)
window.tambahKeKeranjang = function (produk, detailMedis = {}) {
  const keranjang = ambilKeranjang();

  // Hitung total kuantitas produk ini di keranjang untuk validasi stok
  const totalSaatIni = keranjang
    .filter((item) => Number(item.id) === Number(produk.id))
    .reduce((sum, item) => sum + Number(item.jumlah || 1), 0);

  if (totalSaatIni >= Number(produk.stok)) {
    alert(`Permintaan stok tidak sesuai: ${produk.nama} hanya tersisa ${produk.stok} item.`);
    return;
  }

  // Buat kunci unik berdasarkan ID + detail medis agar jika catatan sama digabung, jika berbeda tetap tercatat rapi
  const signatureMedis = JSON.stringify(detailMedis || {});
  const idxAda = keranjang.findIndex(
    (item) => Number(item.id) === Number(produk.id) && JSON.stringify(item.detail_medis || {}) === signatureMedis
  );

  if (idxAda !== -1) {
    keranjang[idxAda].jumlah += 1;
  } else {
    keranjang.push({
      uid: Date.now() + "-" + Math.random().toString(36).substring(2, 7),
      id: produk.id,
      path: produk.path || `Path ${produk.id}`,
      nama: produk.nama,
      harga: Number(produk.harga),
      biaya_jasa: Number(produk.biaya_jasa || 0),
      stok: Number(produk.stok),
      jumlah: 1,
      detail_medis: detailMedis || {}
    });
  }

  // Pastikan form checkout kembali terlihat jika sebelumnya sempat disembunyikan pasca checkout
  const formEl = document.getElementById("form-checkout");
  if (formEl) formEl.hidden = false;

  const statusEl = document.getElementById("status-checkout");
  if (statusEl) statusEl.innerHTML = "";

  simpanKeranjang(keranjang);
  tandaiStepAktif(3);
  tampilkanDecisionAdaTransaksiLagi(produk);
};

function tampilkanDecisionAdaTransaksiLagi(produkTerakhir) {
  const decisionBox = document.getElementById("kotak-decision-transaksi-lagi");
  const teksLayanan = document.getElementById("teks-layanan-ditambahkan");
  if (!decisionBox) return;

  if (teksLayanan && produkTerakhir) {
    teksLayanan.textContent = `${produkTerakhir.path || "Layanan"} - ${produkTerakhir.nama} berhasil masuk ke daftar tindakan.`;
  }
  decisionBox.hidden = false;
  decisionBox.scrollIntoView({ behavior: "smooth", block: "center" });
}

// Keputusan Flowchart 2: "Ada Transaksi Lagi?" -> Ya / Tidak
window.jawabAdaTransaksiLagi = function (masihAda) {
  const decisionBox = document.getElementById("kotak-decision-transaksi-lagi");
  if (decisionBox) decisionBox.hidden = true;

  if (masihAda) {
    // Ya -> Kembali ke "Pilih Jenis Layanan?"
    tandaiStepAktif(2);
    const seksiPath = document.getElementById("seksi-pilih-path");
    if (seksiPath) {
      seksiPath.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  } else {
    // Tidak -> Lanjut ke "Hitung Total Biaya & Jasa"
    tandaiStepAktif(4);
    const seksiKeranjang = document.getElementById("seksi-keranjang");
    if (seksiKeranjang) {
      seksiKeranjang.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }
};

window.ubahKuantitasKeranjang = function (uid, delta) {
  const keranjang = ambilKeranjang();
  const idx = keranjang.findIndex((item) => item.uid === uid);
  if (idx === -1) return;

  const item = keranjang[idx];
  const produkAsli = (window.katalogProduk || []).find((p) => Number(p.id) === Number(item.id));
  const batasStok = produkAsli ? Number(produkAsli.stok) : Number(item.stok);

  if (delta > 0) {
    const totalProdukSama = keranjang
      .filter((x) => Number(x.id) === Number(item.id))
      .reduce((acc, x) => acc + Number(x.jumlah), 0);

    if (totalProdukSama >= batasStok) {
      alert(`Permintaan stok tidak sesuai: ${item.nama} hanya tersisa ${batasStok} item.`);
      return;
    }
    item.jumlah += 1;
  } else {
    item.jumlah -= 1;
    if (item.jumlah <= 0) {
      keranjang.splice(idx, 1);
    }
  }

  simpanKeranjang(keranjang);
};

window.hapusItemKeranjang = function (uid) {
  const keranjang = ambilKeranjang().filter((item) => item.uid !== uid);
  simpanKeranjang(keranjang);
};

window.kosongkanKeranjang = function () {
  localStorage.removeItem(STORAGE_KEY_KERANJANG);
  renderKeranjang();
};

function formatRingkasanMedis(detail) {
  if (!detail || typeof detail !== "object") return "";
  const list = [];
  if (detail.jenis_cairan) list.push(`<strong>Jenis Cairan:</strong> ${detail.jenis_cairan}`);
  if (detail.riwayat_penyakit) list.push(`<strong>Riwayat Penyakit:</strong> ${detail.riwayat_penyakit}`);
  if (detail.jenis_luka) list.push(`<strong>Jenis Luka:</strong> ${detail.jenis_luka}`);
  if (detail.alergi) list.push(`<strong>Alergi:</strong> ${detail.alergi}`);
  if (detail.keluhan) list.push(`<strong>Keluhan:</strong> ${detail.keluhan}`);
  if (list.length === 0) return "";
  return `<div class="detail-medis-chip">${list.join(" &bull; ")}</div>`;
}

function renderKeranjang() {
  const keranjang = ambilKeranjang();
  const isiEl = document.getElementById("isi-keranjang");
  const totalEl = document.getElementById("total-keranjang");
  const subBiayaEl = document.getElementById("subtotal-biaya-layanan");
  const subJasaEl = document.getElementById("subtotal-biaya-jasa");
  const badgeEl = document.getElementById("badge-keranjang");
  const formEl = document.getElementById("form-checkout");

  const totalItem = keranjang.reduce((sum, item) => sum + Number(item.jumlah || 1), 0);
  if (badgeEl) badgeEl.textContent = totalItem;

  if (!isiEl) return;

  if (keranjang.length === 0) {
    isiEl.innerHTML = `
      <div class="text-center py-4 text-muted">
        <div class="fs-2 mb-2">🩺</div>
        <p class="mb-0">Belum ada layanan medis yang dipilih.<br><small>Silakan pilih Path 1 – Path 6 pada daftar Layanan Medis di atas.</small></p>
      </div>
    `;
    if (subBiayaEl) subBiayaEl.textContent = "Rp 0";
    if (subJasaEl) subJasaEl.textContent = "Rp 0";
    if (totalEl) totalEl.textContent = "Rp 0";
    return;
  }

  // Pastikan form checkout tampil jika keranjang terisi kembali
  if (formEl) formEl.hidden = false;

  let subtotalBiaya = 0;
  let totalJasa = 0;

  isiEl.innerHTML = keranjang
    .map((item) => {
      const subBiaya = Number(item.harga) * Number(item.jumlah);
      const subJasa = Number(item.biaya_jasa || 0) * Number(item.jumlah);
      const subTotalItem = subBiaya + subJasa;

      subtotalBiaya += subBiaya;
      totalJasa += subJasa;

      return `
        <div class="item-keranjang-row">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
              <span class="badge bg-info text-dark fw-bold me-1">${item.path || "Layanan"}</span>
              <strong class="text-dark">${item.nama}</strong>
              <div class="small text-muted mt-1">
                Biaya Tindakan: ${formatRupiah(item.harga)} + Jasa Medis: ${formatRupiah(item.biaya_jasa || 0)} / tindakan
              </div>
              ${formatRingkasanMedis(item.detail_medis)}
            </div>
            <div class="text-end">
              <div class="fw-bold text-primary mb-2">${formatRupiah(subTotalItem)}</div>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" onclick="ubahKuantitasKeranjang('${item.uid}', -1)">-</button>
                <span class="btn btn-light disabled fw-bold text-dark px-3">${item.jumlah}</span>
                <button type="button" class="btn btn-outline-primary" onclick="ubahKuantitasKeranjang('${item.uid}', 1)">+</button>
                <button type="button" class="btn btn-outline-danger" onclick="hapusItemKeranjang('${item.uid}')" title="Hapus">✕</button>
              </div>
            </div>
          </div>
        </div>
      `;
    })
    .join("");

  const grandTotal = subtotalBiaya + totalJasa;
  if (subBiayaEl) subBiayaEl.textContent = formatRupiah(subtotalBiaya);
  if (subJasaEl) subJasaEl.textContent = formatRupiah(totalJasa);
  if (totalEl) totalEl.textContent = formatRupiah(grandTotal);
}

async function prosesCheckout(event) {
  event.preventDefault();

  const keranjang = ambilKeranjang();
  const statusEl = document.getElementById("status-checkout");
  const formEl = document.getElementById("form-checkout");

  if (keranjang.length === 0) {
    alert("Daftar tindakan medis masih kosong. Silakan pilih jenis layanan terlebih dahulu.");
    return;
  }

  const namaPemesan = (document.getElementById("nama_pemesan")?.value || "").trim();
  const kontak = (document.getElementById("kontak")?.value || "").trim();
  const umur = (document.getElementById("umur")?.value || "").trim();

  if (!namaPemesan || !kontak || !umur) {
    alert("Mohon lengkapi Data Umum Pasien: Nama, No. WA, dan Umur.");
    return;
  }

  const btnSubmit = formEl?.querySelector('button[type="submit"]');
  if (btnSubmit) {
    btnSubmit.disabled = true;
    btnSubmit.textContent = "Memproses Transaksi & Struk...";
  }

  try {
    const res = await fetch("api/checkout.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        nama_pemesan: namaPemesan,
        kontak: kontak,
        umur: Number(umur),
        items: keranjang
      })
    });

    const hasil = await res.json();

    if (!res.ok || hasil.status !== "sukses") {
      throw new Error(hasil.pesan || "Terjadi kesalahan saat memproses transaksi.");
    }

    // Checkout berhasil: reset form & hapus keranjang dari localStorage (Sesuai SKILL.md)
    if (formEl) {
      formEl.reset();
      formEl.hidden = true;
    }
    localStorage.removeItem(STORAGE_KEY_KERANJANG);
    localStorage.removeItem(STORAGE_KEY_PASIEN);
    sinkronkanInputPasien({ nama_pemesan: "", kontak: "", umur: "" });
    renderKeranjang();
    tandaiStepAktif(5);

    // Muat ulang stok produk terbaru
    if (typeof muatDataProduk === "function") {
      muatDataProduk("semua");
    }

    const urlStruk = `api/struk.php?kode=${encodeURIComponent(hasil.kode_transaksi)}`;

    if (statusEl) {
      statusEl.innerHTML = `
        <div class="p-4 rounded-4 mt-3" style="background:#dcfce7; border:2px solid #22c55e; color:#064e3b;">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <span class="simbol-selesai">🏁 Selesai</span>
            <span class="badge bg-dark fs-6">${hasil.kode_transaksi}</span>
          </div>
          <h5 class="fw-bold mt-2 mb-1">Transaksi Layanan Medis Berhasil!</h5>
          <p class="small mb-3">
            Pasien: <strong>${namaPemesan}</strong> (${umur} Tahun) &bull; No. WA: <strong>${kontak}</strong>
          </p>
          <div class="p-3 rounded-3 bg-white border mb-3 small">
            <div class="d-flex justify-content-between">
              <span>Total Biaya Layanan/Tindakan:</span>
              <strong>${formatRupiah(hasil.subtotal_biaya)}</strong>
            </div>
            <div class="d-flex justify-content-between">
              <span>Total Biaya Jasa Medis:</span>
              <strong>${formatRupiah(hasil.total_jasa)}</strong>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between fs-6 text-primary">
              <strong>Total Biaya & Jasa:</strong>
              <strong>${formatRupiah(hasil.total)}</strong>
            </div>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <a href="${urlStruk}" target="_blank" class="btn btn-success rounded-pill fw-bold px-4">
              🖨️ Cetak Struk PDF (${hasil.kode_transaksi})
            </a>
            <button type="button" class="btn btn-outline-primary rounded-pill fw-semibold" onclick="mulaiTransaksiBaru()">
              ➕ Buat Transaksi Pasien Baru
            </button>
          </div>
        </div>
      `;
    }
  } catch (err) {
    if (statusEl) {
      statusEl.innerHTML = `
        <div class="alert alert-danger rounded-4 mt-3">
          <strong>Gagal Checkout:</strong> ${err.message}
        </div>
      `;
    }
  } finally {
    if (btnSubmit) {
      btnSubmit.disabled = false;
      btnSubmit.textContent = "🧾 Hitung Total & Cetak Struk Pembayaran";
    }
  }
}

window.mulaiTransaksiBaru = function () {
  const statusEl = document.getElementById("status-checkout");
  if (statusEl) statusEl.innerHTML = "";
  const formEl = document.getElementById("form-checkout");
  if (formEl) formEl.hidden = false;
  tandaiStepAktif(1);
  const seksiPasien = document.getElementById("seksi-data-pasien");
  if (seksiPasien) seksiPasien.scrollIntoView({ behavior: "smooth" });
};

document.addEventListener("DOMContentLoaded", () => {
  const dataPasien = ambilDataPasien();
  sinkronkanInputPasien(dataPasien);
  renderKeranjang();

  // Sinkronisasi input dua arah antara kartu Data Umum Pasien (Langkah 1) & Form Checkout
  const pairs = [
    ["pasien_nama_awal", "nama_pemesan", "nama_pemesan"],
    ["pasien_wa_awal", "kontak", "kontak"],
    ["pasien_umur_awal", "umur", "umur"]
  ];

  pairs.forEach(([id1, id2, key]) => {
    const el1 = document.getElementById(id1);
    const el2 = document.getElementById(id2);

    if (el1) {
      el1.addEventListener("input", () => {
        const current = ambilDataPasien();
        current[key] = el1.value;
        simpanDataPasien(current);
      });
    }
    if (el2) {
      el2.addEventListener("input", () => {
        const current = ambilDataPasien();
        current[key] = el2.value;
        simpanDataPasien(current);
      });
    }
  });

  const formCheckout = document.getElementById("form-checkout");
  if (formCheckout) {
    formCheckout.addEventListener("submit", prosesCheckout);
  }

  const btnKeranjangHeader = document.getElementById("tombol-keranjang-header");
  if (btnKeranjangHeader) {
    btnKeranjangHeader.addEventListener("click", () => {
      const seksi = document.getElementById("seksi-keranjang");
      if (seksi) seksi.scrollIntoView({ behavior: "smooth" });
    });
  }
});

