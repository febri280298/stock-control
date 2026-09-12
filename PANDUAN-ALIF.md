# Panduan Kerja — Alif

Repo bersama: **https://github.com/febri280298/stock-control**

Pembagiannya: **Alif push ke GitHub, Febri yang deploy ke server.**
Jangan deploy sendiri ke server — Febri punya `deploy.sh` yang mengurus
backup, migrasi, dan verifikasi.

---

## A. SEKALI SAJA — samakan laptop dengan main

Laptopmu masih di branch `fitur-po-agustus`. Pekerjaanmu sudah digabung ke
`main` dan sudah jalan di server produksi — tapi **itu tidak mengubah apa pun
di laptopmu**. Git tidak mengirim perubahan turun ke komputermu; kamu yang
harus menariknya.

Setelah penggabungan itu ada 14 file yang berubah di `main`. Kalau kamu lanjut
menulis kode dari salinan lama, perubahan itu akan terhapus lagi saat di-push.
**Lakukan ini dulu sebelum menulis kode baru.**

### 1. Amankan yang belum sempat di-commit

```bash
git status
```

Kalau masih ada perubahan, commit dulu di branch yang sekarang:

```bash
git add -A
git commit -m "simpan pekerjaan terakhir"
git push origin fitur-po-agustus
```

### 2. Pindah ke main dan tarik versi terbaru

```bash
git checkout main
git fetch origin
git reset --hard origin/main
```

`.env` dan `vendor/` tidak terhapus — keduanya di-gitignore.

### 3. Bersihkan cache lalu cek jalan

```bash
php artisan config:clear
php artisan view:clear
php artisan serve
```

Tidak perlu `composer install` maupun `php artisan migrate` untuk langkah ini
— sudah dicek, `composer.lock` sama persis dan tidak ada migration baru sejak
branch-mu. Package dan struktur database di laptopmu sudah cocok.

Mulai sekarang, kerja selalu dari `main`. Branch lama boleh dihapus:

```bash
git branch -D fitur-po-agustus
```

---

## Yang berubah dari versi di laptopmu

| Berkas | Perubahan |
|---|---|
| `public/js/main.js`, `login.php` | `API_URL` tidak lagi hardcode ke `192.168.1.5`, sekarang menyesuaikan sendiri lewat `location.origin` |
| `public/app.php`, `login.php` | Logo kembali ke `bti.png` (bukan `stokin.png`) |
| `SuratJalanController` | Cetak PDF pakai dompdf + Blade, bukan FPDI. Penyimpanan, daftar, dan unduh ulang buatanmu tetap dipakai |
| `resources/views/pdf/surat-jalan.blade.php` | Form FM-PCD-002 digambar ulang, lengkap dengan PROJECT dan NO. PO |
| `app/Support/PdfText.php` | **Baru** — mengepas teks panjang memakai metrik font asli |
| `TransactionController@chart` | Perbaikan: `COALESCE(qty_ok, qty)`, sebelumnya barang masuk hilang dari grafik |
| `deploy.sh` | **Baru** — punya Febri, jangan dijalankan dari laptopmu |

---

## B. SETIAP KALI ada perubahan baru

### Sebelum mulai kerja

```bash
git checkout main
git pull --rebase origin main
composer install      # kalau composer.lock ikut berubah
php artisan migrate   # kalau ada migration baru
```

### Sesudah selesai

```bash
git add -A
git commit -m "pesan singkat, mis: tambah filter tanggal di rekap PO"
git pull --rebase origin main
git push origin main
```

`git pull --rebase` sebelum push itu wajib — mencegah bentrok kalau Febri
juga sedang mengubah sesuatu.

Lalu **kabari Febri** kalau sudah di-push, supaya dia bisa deploy.

### Untuk fitur besar, pakai branch

Supaya Febri bisa meninjau sebelum masuk ke `main` (`main` = yang dipakai
produksi):

```bash
git checkout -b fitur/nama-singkat
# ... kerja, commit seperti biasa ...
git push -u origin fitur/nama-singkat
```

Lalu buka Pull Request di GitHub dan kabari Febri.

### Kalau kena konflik saat rebase

Buka file yang ditandai, cari baris `<<<<<<<`, rapikan manual, lalu:

```bash
git add <file yang tadi bentrok>
git rebase --continue
```

Kalau bingung, batalkan dengan `git rebase --abort` lalu kabari Febri.

---

## Cek sebelum push

- [ ] Aplikasi jalan di lokal, tidak ada error
- [ ] Migration baru sudah dicoba `php artisan migrate`
- [ ] **File pendukung ikut ter-commit.** Ini sudah dua kali jadi masalah:
      template PDF surat jalan dan `warehouse-bg.jpg` tidak ikut karena
      ter-gitignore. Cek dengan `git status --ignored` kalau ada file baru
      yang tidak muncul di `git status`.
- [ ] **Jangan hardcode `API_URL`.** Sekarang menyesuaikan sendiri. Kalau
      diganti jadi `http://192.168.x.x/...`, produksi mati saat di-deploy.
- [ ] `.env` tidak dipaksa ikut ter-commit

---

## Jebakan di kode ini

**Mass assignment membuang field yang tidak terdaftar, tanpa pesan error.**
Sudah dua kali bikin bug diam-diam:

- `Transaction::$fillable` belum memuat `qty_ok` → `approveQc()` menyimpan
  status tapi `qty_ok` tetap NULL
- `User` memakai `#[Fillable(['name','email','password'])]` tanpa `role` →
  `updateOrCreate` membuang role, semua user jadi role bawaan

Kalau menambah kolom, **selalu tambahkan juga ke `$fillable`**, lalu buktikan
nilainya benar-benar tersimpan di database — jangan cuma lihat respons API 200.

**Role wajib huruf kecil** (`admin`, `pcd`, `marketing`, `user`).
`EnsureRole` membandingkan persis pakai `in_array()`.

**Kolom nullable yang diisi belakangan butuh fallback.** `qty_ok` hanya terisi
lewat `approveQc()`; transaksi yang langsung "After Check QC" meninggalkannya
NULL. Pakai `qty_ok ?? qty` di PHP atau `COALESCE(qty_ok, qty)` di query.

---

## Masih ditunggu darimu

- `public/warehouse-bg.jpg` — latar halaman login masih polos karena file ini
  belum pernah terkirim
