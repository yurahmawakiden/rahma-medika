// js/App-09.js - Fetch produk/layanan medis, filter kategori, render grid & alur navigasi Flowchart 1 & 2

window.katalogProduk = [];
window.infoSitus = {};
let kategoriAktif = "semua";
let kataKunciPencarian = "";

function formatRupiah(angka) {
  return "Rp " + Number(angka || 0).toLocaleString("id-ID");
}

async function muatDataProduk(kategori = "semua") {
  kategoriAktif = kategori;
  const gridEl = document.getElementById("daftar-produk");
  if (gridEl) {
    gridEl.innerHTML = `
      <div class="col-12 text-center py-5">
        <div class="spinner-border text-info" role="status"></div>
        <p class="mt-2 text-muted fw-semibold">Memuat daftar layanan medis Rahma Medika...</p>
      </div>
    `;
  }

  try {
    const url =
      kategori && kategori !== "semua"
        ? `api/produk.php?kategori=${encodeURIComponent(kategori)}`
        : `api/produk.php`;
    const res = await fetch(url);
    const data = await res.json();

    if (data.site) {
      window.infoSitus = data.site;
      terapkanInfoSitus(data.site);
    }

    if (Array.isArray(data.menu)) {
      renderNavigasiMenu(data.menu);
    }

    // Simpan semua produk jika memuat kategori 'semua'
    const daftar = Array.isArray(data.products) ? data.products : [];
    if (kategori === "semua" || window.katalogProduk.length === 0) {
      window.katalogProduk = daftar;
      renderPathCepat(daftar);
    }

    renderGridProduk(daftar);
  } catch (err) {
    console.error("Gagal memuat layanan:", err);
    if (gridEl) {
      gridEl.innerHTML = `
        <div class="col-12">
          <div class="alert alert-danger rounded-4">
            Gagal memuat data layanan medis. Pastikan server PHP aktif.
          </div>
        </div>
      `;
    }
  }
}

function terapkanInfoSitus(site) {
  const elNama = document.querySelectorAll(".nama-toko-dinamis");
  elNama.forEach((el) => (el.textContent = site.nama || "Rahma Medika"));

  const elTagline = document.getElementById("tagline-situs");
  if (elTagline && site.tagline) elTagline.textContent = site.tagline;

  const elAlamat = document.getElementById("alamat-situs");
  if (elAlamat && site.alamat) elAlamat.textContent = site.alamat;

  const elJam = document.getElementById("jam-situs");
  if (elJam && site.jam_operasional) elJam.textContent = site.jam_operasional;
}

function renderNavigasiMenu(menuList) {
  const navContainer = document.getElementById("menu-navigasi-dinamis");
  if (!navContainer) return;

  navContainer.innerHTML = menuList
    .map((m) => {
      const targetId = m.id === "produk" ? "layanan" : m.id;
      return `
        <li class="nav-item">
          <a class="nav-link nav-link-medika" href="#${targetId}" onclick="navigasiKeMenu('${targetId}')">
            ${m.label}
          </a>
        </li>
      `;
    })
    .join("");
}

// Fungsi Alur Navigasi sesuai Flowchart 1
window.navigasiKeMenu = function (menuId) {
  document.querySelectorAll(".nav-link-medika").forEach((el) => {
    el.classList.toggle("active", el.getAttribute("href") === `#${menuId}`);
  });

  const statusNavEl = document.getElementById("status-alur-navigasi");
  if (statusNavEl) {
    const labelMap = {
      beranda: "Proses Halaman Beranda → Klik tombol hubungi kami?",
      layanan: "Proses Halaman Layanan → Menampilkan Layanan → Pilih Layanan Medis? (Menuju A)",
      kontak: "Proses Halaman Kontak → Klik ikon sosial media? (IG, FB, TIKTOK)"
    };
    statusNavEl.textContent = labelMap[menuId] || "Menampilkan halaman website";
  }
};

// Keputusan Flowchart 1 pada Beranda: "Klik tombol hubungi kami?"
window.aksiKlikHubungiKami = function (pilihanYa) {
  const infoDecision = document.getElementById("feedback-decision-beranda");
  if (pilihanYa) {
    if (infoDecision) {
      infoDecision.hidden = false;
      infoDecision.className = "alert alert-info mt-3 mb-0 rounded-4 fw-semibold";
      infoDecision.innerHTML =
        "✅ <strong>Keputusan: Ya</strong> — Mengarahkan ke formulir <em>Input teks kirim WA/Email</em>.";
    }
    const formKontak = document.getElementById("kontak");
    if (formKontak) {
      formKontak.scrollIntoView({ behavior: "smooth" });
      setTimeout(() => {
        const inputNamaPesan = document.getElementById("pesan_nama");
        if (inputNamaPesan) inputNamaPesan.focus();
      }, 450);
    }
  } else {
    if (infoDecision) {
      infoDecision.hidden = false;
      infoDecision.className = "alert alert-secondary mt-3 mb-0 rounded-4";
      infoDecision.innerHTML =
        "ℹ️ <strong>Keputusan: Tidak</strong> — Anda dapat lanjut melihat katalog Layanan Medis atau menuju bagian bawah halaman.";
    }
  }
};

// Keputusan Flowchart 1 di akhir: "Keluar dari website?"
window.aksiKeluarWebsite = function (keluarYa) {
  const boxSelesai = document.getElementById("panel-status-keluar");
  if (!boxSelesai) return;
  boxSelesai.hidden = false;

  if (keluarYa) {
    boxSelesai.innerHTML = `
      <div class="p-4 rounded-4 text-center" style="background:#dcfce7; border:2px solid #22c55e; color:#064e3b;">
        <div class="simbol-selesai mb-2">🏁 Selesai</div>
        <h5 class="fw-bold mb-1">Terima Kasih Telah Mengunjungi Website Rahma Medika!</h5>
        <p class="mb-2 small">Alur navigasi website telah selesai sesuai Flowchart. Semoga sehat selalu.</p>
        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="aksiKeluarWebsite(false)">
          Masuk Kembali ke Halaman Website
        </button>
      </div>
    `;
  } else {
    boxSelesai.innerHTML = `
      <div class="p-3 rounded-4 text-center" style="background:#e0f2fe; border:2px solid #38bdf8; color:#0c4a6e;">
        🔄 <strong>Keputusan: Tidak</strong> — Kembali menampilkan halaman website Rahma Medika.
      </div>
    `;
    window.scrollTo({ top: 0, behavior: "smooth" });
    setTimeout(() => {
      boxSelesai.hidden = true;
    }, 2500);
  }
};

// Render 6 Path Cepat sesuai Flowchart 2 (Path 1 s/d Path 6)
function renderPathCepat(products) {
  const container = document.getElementById("grid-6-path");
  if (!container) return;

  const ikonPath = {
    1: "💧",
    2: "🩸",
    3: "🧪",
    4: "🧫",
    5: "🩹",
    6: "🩺"
  };

  container.innerHTML = products
    .map((p) => {
      const ikon = ikonPath[p.id] || "➕";
      const infoInput = p.butuh_input
        ? `<span class="badge bg-purple-subtle text-purple border border-purple-subtle" style="background:#f3e8ff;color:#6b21a8;font-size:0.7rem;">${p.label_input}</span>`
        : `<span class="badge" style="background:#dcfce7;color:#15803d;font-size:0.7rem;">Langsung Tindakan</span>`;

      return `
        <button type="button" class="btn-path-card" onclick="pilihLayananBerdasarkanId(${p.id})">
          <span class="badge-path">${p.path || "Path " + p.id}</span>
          <div class="fs-3 my-1">${ikon}</div>
          <div class="fw-bold text-dark small">${p.nama}</div>
          ${infoInput}
          <div class="fw-bold text-primary small mt-1">
            ${formatRupiah(p.harga)} <span class="text-muted fw-normal" style="font-size:0.7rem;">(+Jasa ${formatRupiah(p.biaya_jasa || 0)})</span>
          </div>
        </button>
      `;
    })
    .join("");
}

function renderGridProduk(products) {
  const gridEl = document.getElementById("daftar-produk");
  if (!gridEl) return;

  const hasilFilter = products.filter((item) => {
    if (!kataKunciPencarian) return true;
    const q = kataKunciPencarian.toLowerCase();
    return (
      (item.nama && item.nama.toLowerCase().includes(q)) ||
      (item.deskripsi && item.deskripsi.toLowerCase().includes(q)) ||
      (item.asal && item.asal.toLowerCase().includes(q)) ||
      (item.path && item.path.toLowerCase().includes(q))
    );
  });

  if (hasilFilter.length === 0) {
    gridEl.innerHTML = `
      <div class="col-12 text-center py-5">
        <div class="p-4 rounded-4 bg-white border">
          <p class="mb-2 fw-bold text-secondary">Tidak ada layanan medis yang cocok dengan filter pencarian.</p>
          <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="resetFilterProduk()">
            Tampilkan Semua Layanan
          </button>
        </div>
      </div>
    `;
    return;
  }

  gridEl.innerHTML = hasilFilter
    .map((p) => {
      const habis = Number(p.stok) <= 0;
      const badgeInput = p.butuh_input
        ? `<div class="badge-input-req">📋 ${p.label_input}</div>`
        : `<div class="badge-input-direct">⚡ ${p.label_input || "Langsung Pemeriksaan"}</div>`;

      return `
        <div class="col-md-6 col-lg-4">
          <div class="card-layanan">
            <div class="position-relative">
              <img src="${p.img}" alt="${p.alt || p.nama}" class="card-layanan-img" onerror="this.src='img/logo.svg'">
              <span class="position-absolute top-0 start-0 m-3 badge rounded-pill px-3 py-2 fw-bold" style="background:#0284c7;color:#fff;">
                ${p.path || "Path " + p.id}
              </span>
              <span class="position-absolute top-0 end-0 m-3 badge rounded-pill px-3 py-2 fw-semibold" style="background:rgba(255,255,255,0.92);color:#0f172a;border:1px solid #cbd5e1;">
                Kuota: ${p.stok}
              </span>
            </div>
            <div class="card-layanan-body">
              <div class="small text-uppercase fw-bold text-info mb-1">${p.asal || p.kategori}</div>
              <h5 class="fw-bold text-dark mb-2">${p.nama}</h5>
              <div>${badgeInput}</div>
              <p class="text-muted small mb-3 flex-grow-1">${p.deskripsi}</p>
              <div class="p-2 rounded-3 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                <div class="d-flex justify-content-between small">
                  <span class="text-muted">Biaya Layanan/Tindakan:</span>
                  <span class="fw-bold text-dark">${formatRupiah(p.harga)}</span>
                </div>
                <div class="d-flex justify-content-between small">
                  <span class="text-muted">Biaya Jasa Medis:</span>
                  <span class="fw-semibold text-primary">+ ${formatRupiah(p.biaya_jasa || 0)}</span>
                </div>
              </div>
              <button
                type="button"
                class="btn ${habis ? "btn-secondary" : "btn-primary"} w-100 rounded-pill fw-bold py-2"
                ${habis ? "disabled" : ""}
                onclick="pilihLayananBerdasarkanId(${p.id})"
              >
                ${habis ? "Kuota Habis" : "🩺 Pilih Layanan Medis (" + (p.path || "Path") + ")"}
              </button>
            </div>
          </div>
        </div>
      `;
    })
    .join("");
}

window.filterKategoriProduk = function (kategori, btnEl) {
  document.querySelectorAll(".btn-filter-kategori").forEach((b) => b.classList.remove("active"));
  if (btnEl) btnEl.classList.add("active");
  muatDataProduk(kategori);
};

window.resetFilterProduk = function () {
  kataKunciPencarian = "";
  const inputCari = document.getElementById("input-cari-produk");
  if (inputCari) inputCari.value = "";
  document.querySelectorAll(".btn-filter-kategori").forEach((b) => {
    b.classList.toggle("active", b.dataset.kategori === "semua");
  });
  muatDataProduk("semua");
};

document.addEventListener("DOMContentLoaded", () => {
  muatDataProduk("semua");

  const inputCari = document.getElementById("input-cari-produk");
  if (inputCari) {
    inputCari.addEventListener("input", (e) => {
      kataKunciPencarian = e.target.value.trim();
      renderGridProduk(window.katalogProduk);
    });
  }
});

