# Aplikasi Verifikasi Keaslian E-ICV

Aplikasi web untuk memverifikasi keaslian **Electronic International Certificate of Vaccination (E-ICV)** berdasarkan hasil scan barcode/QR Code pada **Balai Kekarantinaan Kesehatan (BKK) Kelas I Bandung — Kementerian Kesehatan RI**.

Dibangun menggunakan **Laravel 12**, **Vue 3**, **Tailwind CSS 4**, dan **MySQL**.

## Fitur Utama

- **Validasi 8 Awalan Link Resmi Kemenkes RI**
  Hasil scan barcode dinyatakan memiliki awalan resmi (**ASLI**) jika diawali salah satu URL berikut:
  1. `https://sinkarkes.kemkes.go.id/welcome/check_document?t=`
  2. `https://www.sinkarkes.kemkes.go.id/welcome/check_document?t=`
  3. `https://kespel.depkes.go.id/`
  4. `https://www.kespel.depkes.go.id/`
  5. `https://www.sinkarkes.kemkes.go.id/index.php/welcome/check_document?t=`
  6. `https://www.sinkarkes.kemkes.go.id/`
  7. `https://sinkarkes.kemkes.go.id/`
  8. `https://sinkarkes.kemkes.go.id/index.php/welcome/check_document?t=`
- **Pembacaan Otomatis Isi Link SINKARKES**
  Membaca data sertifikat langsung dari portal resmi SINKARKES Kemenkes RI dan menampilkan **Nama Pemilik Sertifikat E-ICV**, nomor E-ICV, nomor paspor, tanggal lahir, serta rincian vaksinasi (jenis vaksin, produsen/batch, tanggal vaksin, masa berlaku, lokasi & dokter supervisi).
- **3 Mode Pemindaian Barcode / QR Code**
  - **Kamera Live** (`html5-qrcode`)
  - **Upload Foto / Screenshot E-ICV** (`jsqr` + fallback `html5-qrcode`, mendukung *drag & drop* dan `Ctrl+V`)
  - **Scanner Barcode USB / Input Manual URL**
- **Riwayat Verifikasi & Laporan MySQL**
  - Ringkasan statistik real-time (Total Verifikasi, Asli, Palsu/Ditolak, Scan Hari Ini)
  - Pencarian & filter berdasarkan nama, nomor E-ICV, paspor, status, dan tanggal
  - Cetak Bukti Verifikasi (dengan kop resmi BKK Kelas I Bandung)
  - Export riwayat ke file CSV

## Instalasi & Menjalankan Proyek

### Prasyarat
- PHP `>= 8.2` (ekstensi `curl`, `dom`, `mbstring`, `openssl`, `pdo_mysql`)
- Composer `2.x`
- Node.js `>= 20` & NPM
- MySQL `8.0+` / MariaDB `10.4+`

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/paradigma-monolith/verifikasiiecv.git
cd verifikasiiecv

# 2. Install dependensi PHP & Frontend
composer install
npm install

# 3. Salin file environment & generate application key
cp .env.example .env
php artisan key:generate
```

Sesuaikan koneksi MySQL pada `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=verifikasi_eicv
DB_USERNAME=root
DB_PASSWORD=mysql
```

Buat database `verifikasi_eicv` di MySQL (atau impor `database/verifikasi_eicv.sql`), lalu jalankan migrasi & build aset:

```bash
php artisan migrate
npm run build
php artisan serve
```

Buka browser ke `http://127.0.0.1:8000`.

## Pengujian Otomatis

```bash
php artisan test
```
