// admin/js/admin.js - Inisialisasi Simple-DataTables & fungsi bantu modal CRUD Admin

document.addEventListener("DOMContentLoaded", () => {
  const tabelEl = document.getElementById("tabel-admin");
  if (tabelEl && typeof simpleDatatables !== "undefined") {
    new simpleDatatables.DataTable(tabelEl, {
      searchable: true,
      fixedHeight: false,
      perPage: 10,
      labels: {
        placeholder: "Cari data...",
        perPage: "entri per halaman",
        noRows: "Belum ada data tercatat",
        info: "Menampilkan {start} sampai {end} dari {rows} data"
      }
    });
  }
});

window.isiFormUbahProduk = function (produk) {
  document.getElementById("aksi_form_produk").value = "ubah";
  document.getElementById("judul_form_produk").textContent = "✏️ Ubah Layanan Medis (#" + produk.id + ")";
  document.getElementById("id_produk").value = produk.id;
  document.getElementById("nama").value = produk.nama || "";
  document.getElementById("path").value = produk.path || ("Path " + produk.id);
  document.getElementById("asal").value = produk.asal || "";
  document.getElementById("kategori").value = produk.kategori || "tindakan-medis";
  document.getElementById("harga").value = produk.harga || 0;
  document.getElementById("biaya_jasa").value = produk.biaya_jasa || 0;
  document.getElementById("stok").value = produk.stok || 0;
  document.getElementById("jenis_input").value = produk.jenis_input || "none";
  document.getElementById("label_input").value = produk.label_input || "";
  document.getElementById("img").value = produk.img || "img/logo.svg";
  document.getElementById("deskripsi").value = produk.deskripsi || "";

  const btnBatal = document.getElementById("btn_batal_ubah");
  if (btnBatal) btnBatal.hidden = false;

  document.getElementById("card_form_produk")?.scrollIntoView({ behavior: "smooth" });
};

window.resetFormProdukAdmin = function () {
  const form = document.getElementById("form_admin_produk");
  if (form) form.reset();
  document.getElementById("aksi_form_produk").value = "tambah";
  document.getElementById("judul_form_produk").textContent = "➕ Tambah Layanan Medis Baru";
  document.getElementById("id_produk").value = "";
  const btnBatal = document.getElementById("btn_batal_ubah");
  if (btnBatal) btnBatal.hidden = true;
};

