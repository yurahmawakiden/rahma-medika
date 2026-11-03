// js/kirimpesan.js - Integrasi WhatsApp Web, Mailpit API (Port 8025), & Sosial Media Interaktif (IG, FB, TIKTOK)

const NOMOR_WA_ADMIN = "6281234567890";
const EMAIL_KLINIK = "layanan@rahma-medika.test";

function ambilInputKontak() {
  const nama = (document.getElementById("pesan_nama")?.value || "").trim();
  const kontak = (document.getElementById("pesan_email")?.value || "").trim();
  const isi = (document.getElementById("pesan_isi")?.value || "").trim();
  return { nama, kontak, isi };
}

function tampilkanStatusPesan(tipe, pesanHtml) {
  const el = document.getElementById("status-kirim-pesan");
  if (!el) return;
  const kelas =
    tipe === "sukses"
      ? "alert-success"
      : tipe === "loading"
      ? "alert-info"
      : "alert-danger";
  el.innerHTML = `<div class="alert ${kelas} rounded-4 mt-3 mb-0">${pesanHtml}</div>`;
}

// 1. Kirim via WhatsApp Direct Link (type="button")
window.kirimPesanWA = function (event) {
  if (event) event.preventDefault();
  const { nama, kontak, isi } = ambilInputKontak();

  if (!nama || !isi) {
    tampilkanStatusPesan("error", "Mohon isi <strong>Nama Anda</strong> dan <strong>Isi Pesan / Konsultasi</strong> terlebih dahulu.");
    return;
  }

  const nomorTujuan = (window.infoSitus && window.infoSitus.telepon) ? window.infoSitus.telepon : NOMOR_WA_ADMIN;
  const teksPesan = `Halo Admin *Rahma Medika*,\n\nSaya *${nama}* (${kontak || "Pengunjung Website"}).\n\n*Pesan / Konsultasi:*\n${isi}`;
  const url = `https://wa.me/${nomorTujuan}?text=${encodeURIComponent(teksPesan)}`;

  tampilkanStatusPesan(
    "sukses",
    `✅ Membuka WhatsApp menuju nomor klinik (<strong>${nomorTujuan}</strong>)...`
  );
  window.open(url, "_blank");
};

// 2. Kirim via Mailpit API lokal Laragon port 8025 (type="button")
window.kirimPesanEmail = async function (event) {
  if (event) event.preventDefault();
  const { nama, kontak, isi } = ambilInputKontak();

  if (!nama || !kontak || !isi) {
    tampilkanStatusPesan(
      "error",
      "Mohon lengkapi <strong>Nama</strong>, <strong>Alamat Email</strong>, dan <strong>Isi Pesan</strong> untuk mengirim email."
    );
    return;
  }

  tampilkanStatusPesan("loading", "⏳ Mengirim pesan email ke server Mailpit Rahma Medika...");

  const emailTujuan = (window.infoSitus && window.infoSitus.email) ? window.infoSitus.email : EMAIL_KLINIK;
  const payload = {
    From: {
      Email: kontak.includes("@") ? kontak : "pasien@rahma-medika.test",
      Name: nama
    },
    To: [
      {
        Email: emailTujuan,
        Name: "Klinik Rahma Medika"
      }
    ],
    Subject: `[Konsultasi Website Rahma Medika] Pesan dari ${nama}`,
    Text: `Pengirim: ${nama} (${kontak})\n\nIsi Pesan:\n${isi}`,
    HTML: `
      <div style="font-family:sans-serif;padding:20px;border:1px solid #bae6fd;border-radius:12px;background:#f0f9ff;">
        <h3 style="color:#0369a1;margin-top:0;">Pesan Baru dari Website Rahma Medika</h3>
        <p><strong>Nama Pengirim:</strong> ${nama}</p>
        <p><strong>Kontak / Email:</strong> ${kontak}</p>
        <hr style="border:none;border-top:1px solid #bae6fd;">
        <p><strong>Isi Pesan / Konsultasi:</strong></p>
        <p style="background:#fff;padding:12px;border-radius:8px;">${isi.replace(/\n/g, "<br>")}</p>
      </div>
    `
  };

  try {
    const res = await fetch("http://localhost:8025/api/v1/send", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    if (!res.ok) {
      throw new Error("Server Mailpit merespons dengan kode " + res.status);
    }

    tampilkanStatusPesan(
      "sukses",
      `✅ Pesan email dari <strong>${nama}</strong> berhasil dikirim ke kotak masuk Mailpit (<code>${emailTujuan}</code>).`
    );
    const formPesan = document.getElementById("form-kontak-pesan");
    if (formPesan) formPesan.reset();
  } catch (err) {
    tampilkanStatusPesan(
      "error",
      `⚠️ Tidak dapat terhubung ke Mailpit lokal (<code>http://localhost:8025</code>). Pastikan layanan Mailpit pada Laragon aktif, atau gunakan tombol <strong>Kirim via WhatsApp</strong>.`
    );
  }
};

// 3. Interaksi Klik Ikon Sosial Media sesuai Flowchart 1: "Klik ikon sosial media? -> Ya -> Menampilkan sosial media IG, FB, TIKTOK"
window.tampilkanSosmed = function (platform) {
  const panelEl = document.getElementById("panel-tampilan-sosmed");
  if (!panelEl) return;

  document.querySelectorAll(".sosmed-btn").forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.platform === platform);
  });

  if (platform === "tidak") {
    panelEl.innerHTML = `
      <div class="alert alert-secondary rounded-4 mb-0">
        ℹ️ <strong>Keputusan: Tidak</strong> — Anda memilih tidak membuka sosial media. Silakan lanjutkan navigasi atau keluar dari website.
      </div>
    `;
    return;
  }

  const dataSosmed = (window.infoSitus && window.infoSitus.sosmed) || {
    instagram: {
      nama: "@rahmamedika.id",
      url: "https://instagram.com/rahmamedika.id",
      followers: "12.4K Pengikut",
      bio: "Edukasi Kesehatan Harian, Info Homecare Infus, Rawat Luka & Cek Lab Cepat."
    },
    facebook: {
      nama: "Klinik Rahma Medika Official",
      url: "https://facebook.com/rahmamedika.official",
      followers: "8.9K Pengikut",
      bio: "Komunitas Pasien & Informasi Jadwal Layanan Kesehatan Masyarakat Rahma Medika."
    },
    tiktok: {
      nama: "@rahmamedika.care",
      url: "https://tiktok.com/@rahmamedika.care",
      followers: "25.1K Pengikut",
      bio: "Video Singkat Tips Kesehatan, Cara Rawat Luka Steril & Cek Tanda Vital."
    }
  };

  const renderSatuKartu = (kode, judul, ikon, warna, item) => `
    <div class="col-md-4">
      <div class="bg-white p-3 rounded-4 border h-100 d-flex flex-column justify-content-between shadow-sm">
        <div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="fs-4">${ikon}</span>
            <div>
              <div class="fw-bold text-dark">${judul}</div>
              <div class="small fw-semibold" style="color:${warna};">${item.nama}</div>
            </div>
          </div>
          <span class="badge bg-light text-secondary border mb-2">${item.followers}</span>
          <p class="small text-muted mb-3">${item.bio}</p>
        </div>
        <a href="${item.url}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary rounded-pill fw-bold w-100">
          Kunjungi ${judul} ↗
        </a>
      </div>
    </div>
  `;

  let kontenKartu = "";
  if (platform === "semua" || platform === "instagram") {
    kontenKartu += renderSatuKartu("ig", "Instagram (IG)", "📸", "#e1306c", dataSosmed.instagram);
  }
  if (platform === "semua" || platform === "facebook") {
    kontenKartu += renderSatuKartu("fb", "Facebook (FB)", "📘", "#1877f2", dataSosmed.facebook);
  }
  if (platform === "semua" || platform === "tiktok") {
    kontenKartu += renderSatuKartu("tiktok", "TikTok", "🎵", "#0f172a", dataSosmed.tiktok);
  }

  panelEl.innerHTML = `
    <div class="sosmed-display-card mt-3">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
          <span class="badge bg-purple text-white px-3 py-1 rounded-pill" style="background:#7e22ce;">Output Informasi Sosial Media</span>
          <h6 class="fw-bold text-dark mt-2 mb-0">Menampilkan Sosial Media IG, FB, TIKTOK Rahma Medika</h6>
        </div>
        <button type="button" class="btn btn-sm btn-light rounded-pill border" onclick="tampilkanSosmed('semua')">
          Tampilkan Semua (IG, FB, TikTok)
        </button>
      </div>
      <div class="row g-3">
        ${kontenKartu}
      </div>
    </div>
  `;
};

