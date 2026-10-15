# 🎓 Academic Hub

<p align="center">
  <img src="public/images/academic-hub-emblem-transparent.png" alt="Academic Hub Logo" width="130" style="border-radius: 20px;">
</p>

<p align="center">
  <strong>Sistem Manajemen Perkuliahan, Arsip Materi & Pengingat Tugas Terintegrasi untuk Mahasiswa</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Status-Active%20&%20Maintained-success?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/Framework-Laravel%2011%20&%20React%20(Inertia.js)-red?style=for-the-badge&logo=laravel&logoColor=white" alt="Framework">
  <img src="https://img.shields.io/badge/UI-Dual%20Theme%20(Dark%20&%20Light)-indigo?style=for-the-badge" alt="Theme">
  <img src="https://img.shields.io/badge/Notification-Telegram%20Bot%20API-26A5E4?style=for-the-badge&logo=telegram&logoColor=white" alt="Notification">
  <img src="https://img.shields.io/badge/Test%20Suite-118%20Passed%20(360%20Assertions)-emerald?style=for-the-badge" alt="Tests">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
</p>

---

## 🌟 Mengenal Academic Hub

**Academic Hub** adalah platform asisten akademik terpadu (*all-in-one*) yang dirancang khusus untuk membantu mahasiswa mengorganisir jadwal kuliah, mengarsipkan materi pembelajaran, dan memantau batas waktu tugas secara efektif dalam satu ruang kerja digital yang modern dan intuitif.

Permasalahan umum mahasiswa adalah slide materi yang tercecer di puluhan grup pesan instan serta tenggat waktu tugas yang terlewat akibat tidak adanya sistem pengingat terpusat. Academic Hub menjawab tantangan tersebut dengan memadukan **aplikasi web responsif (React + Inertia.js)** dan **asisten bot Telegram interaktif** yang proaktif mengingatkan jadwal kuliah harian serta peringatan deadline tugas secara otomatis.

---

## 🏛️ Arsitektur & Gambaran Sistem

Academic Hub dibangun menggunakan arsitektur monolit modern yang efisien:

```
┌─────────────────────────────────────────────────────────────────┐
│                    Pengguna (Mahasiswa)                         │
└────────────────┬───────────────────────────────┬────────────────┘
                 │ Akses Web (Browser)           │ Pesan & Notifikasi
                 ▼                               ▼
┌─────────────────────────────────┐   ┌───────────────────────────┐
│   Frontend Modern (Inertia.js)   │   │  Bot Telegram Interaktif  │
│  - React + Tailwind CSS         │   │  - Push Notifikasi Cerdas │
│  - Mode Gelap & Terang Otomatis │   │  - Keyboard Menu Shortcut │
│  - Layout Mobile Friendly       │   │  - Webhook Secret Guard   │
└────────────────┬────────────────┘   └───────────▲───────────────┘
                 │                                │
                 ▼                                │ API Webhook /
┌─────────────────────────────────────────────────┴───────────────┐
│                    Backend Core (Laravel 11)                    │
│  - Route & Controller Middleware (Auth, Verified, Policies)     │
│  - Eloquent ORM dengan Hubungan Chaperoned & Database Indexes   │
│  - Storage Manager (Dukungan Multi-Disk: Lokal & S3)            │
│  - Scheduler & Queue Worker (Cron Webhook Runner)               │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────┐   ┌───────────────────────────┐
│    Basis Data Relasional        │   │    Penyimpanan Berkas     │
│  (SQLite / MySQL / PostgreSQL)  │   │  (Storage Lokal / AWS S3) │
└─────────────────────────────────┘   └───────────────────────────┘
```

---

## 🚀 Fitur Utama Platform

### 1. 📊 Dasbor Akademik Komprehensif
- **Kartu Metrik Cepat**: Rangkuman jumlah mata kuliah terdaftar, tugas aktif, jadwal kuliah hari ini (atau status libur), serta status keterhubungan bot Telegram.
- **Indikator Urgensi Tugas Cerdas**: Hirarki visual batas waktu otomatis yang membedakan:
  - 🔴 **Lewat Tenggat**: Tugas yang telah melewati batas waktu pengumpulan.
  - 🚨 **< 24 Jam (Mendesak)**: Peringatan intensif agar tugas segera diselesaikan.
  - 🟡 **< 3 Hari (Siaga)**: Peringatan persiapan awal penyelesaian tugas.
  - ⚪ **Mendatang**: Tugas dengan batas waktu yang masih longgar.
- **Penyaring Hari Perkuliahan**: Navigasi instan untuk melihat jadwal kuliah per hari (*Senin – Sabtu*) atau melihat agenda khusus hari ini.
- **Tandai Selesai Cepat**: Mahasiswa dapat mencentang tugas yang telah selesai langsung dari dasbor tanpa harus berpindah halaman.
- **Akses Cepat Portal Kampus**: Komponen bilah pintasan untuk membuka tautan SIAKAD, SPADA, OCW, atau LMS kampus dalam satu klik.

### 2. 📚 Manajemen Mata Kuliah & Informasi Perkuliahan
- **Detail Informasi Lengkap**: Menyimpan kode mata kuliah, nama dosen pengampu, nama ruangan/gedung kelas, jadwal hari, serta jam mulai dan selesai perkuliahan.
- **Kontak Penanggung Jawab (PJ)**: Menyimpan nama dan nomor kontak PJ matkul dengan tombol langsung untuk menghubungi via WhatsApp.
- **Tautan Presensi Terintegrasi**: Akses cepat menuju link presensi daring resmi kampus per mata kuliah.
- **Preset Paket Otomatis**: Mendukung pendaftaran mahasiswa baru dengan otomatisasi seeding seluruh mata kuliah program studi (contoh: paket D3 Teknik Informatika).

### 3. 📝 Pelacak Tugas Kuliah (Assignment Tracker)
- **Kategori Tugas**: Pengelompokan tugas menjadi tugas individu (👤) atau tugas kelompok (👥).
- **Tingkat Prioritas**: Penandaan prioritas tinggi (*High*), sedang (*Medium*), dan rendah (*Low*).
- **Multi-Tautan Referensi & Pengumpulan**: Mahasiswa dapat menyimpan banyak tautan sekaligus (Google Docs, Google Drive, LMS kampus, repository GitHub) dalam satu kartu tugas.
- **Riwayat Tugas Selesai**: Riwayat tugas yang sudah tuntas tersimpan rapi dalam folder akordeon yang dapat dibuka-tutup kapan saja.

### 4. 📑 Arsip & Pratinjau Berkas Materi Perkuliahan
- **Pengelompokan Berbasis Pertemuan**: Berkas tertata rapi sesuai sesi perkuliahan (*Pertemuan 1 hingga Pertemuan 16*).
- **Multi-Format Dokumen & Gambar**: Mendukung pengunggahan berkas modul (`PDF`, `DOC`, `DOCX`, `PPT`, `PPTX`, `ZIP`, `RAR`) serta gambar (`JPG`, `PNG`, `WEBP`, `GIF`).
- **Pratinjau Aman Langsung di Peramban**: Mahasiswa dapat langsung membaca modul atau dokumen PDF di dalam browser tanpa harus mengunduh berkas ke memori perangkat.
- **Keamanan Penyimpanan Berkas**: Validasi ketat tipe berkas (mitigasi berkas berbahaya/SVG/XSS), sanitasi nama berkas unduhan, dan penghapusan otomatis dari penyimpanan saat mata kuliah atau akun pengguna dihapus.

### 5. 🤖 Asisten Bot Telegram Terintegrasi
- **Pengingat Jadwal Kuliah Pagi**: Notifikasi proaktif harian dikirimkan ke Telegram mahasiswa pada jam yang ditentukan (bawaan pukul 06:00 WIB), mencakup daftar kelas, dosen, ruangan, dan tugas yang harus dikumpulkan hari ini.
- **Pengingat Deadline Bertahap**: Notifikasi otomatis dua fase (fase persiapan H-24 jam dan fase peringatan mendesak H-3 jam) lengkap dengan tombol interaktif untuk membuka link tugas.
- **Ringkasan Akademik Mingguan (*Weekly Digest*)**: Rangkuman target perkuliahan dan tugas yang dikirim setiap awal pekan.
- **Pintasan Perintah Interaktif**:
  - `/jadwal` : Menampilkan jadwal kuliah dan kelas aktif hari ini.
  - `/semua_jadwal` : Menampilkan seluruh jadwal kuliah selama satu pekan.
  - `/tugas` : Menampilkan daftar seluruh tugas aktif beserta sisa waktunya.
  - `/materi` : Menampilkan ringkasan arsip materi per mata kuliah dengan tombol penelusuran.
  - `/portal` : Menampilkan daftar tautan portal dan SIAKAD kampus yang tersimpan.
  - `/reset` : Meminta tautan instan untuk mengatur ulang kata sandi bagi akun yang terhubung.
  - `/help` : Menampilkan panduan penggunaan dan informasi status notifikasi akun.

### 6. ⚙️ Personalisasi Pengaturan & Keamanan Akun
- **Waktu Notifikasi Fleksibel**: Pengguna bebas mengatur jam pengiriman jadwal harian serta durasi pengingat deadline tugas sesuai preferensi pribadi.
- **Multi-Portal Kampus**: Pengguna dapat menambahkan, mengedit, atau menghapus banyak bookmark portal kampus sekaligus.
- **Keamanan Webhook & Cron**: Webhook Telegram diverifikasi melalui mekanisme *Secret Token* berbasis `hash_equals()`, dan endpoint scheduler `/cron/run-schedule` diproteksi secara terisolasi.
- **Perlindungan Privasi Data**: Mendukung penghapusan akun mandiri secara menyeluruh yang secara otomatis membersihkan seluruh data basis data dan berkas fisik terkait di media penyimpanan.

---

## 🛠️ Tumpukan Teknologi (Tech Stack)

| Lapisan | Teknologi | Deskripsi / Penggunaan |
|---|---|---|
| **Backend Framework** | [Laravel 11](https://laravel.com) | Routing, Model Eloquent, Validasi Request, Artisan Commands, Database Migrations |
| **Language** | [PHP 8.2+](https://www.php.net) | Bahasa pemrograman utama sisi server dengan *strict typing* |
| **Frontend Adapter** | [Inertia.js](https://inertiajs.com) | Menghubungkan Laravel backend dengan React tanpa memerlukan REST API terpisah |
| **Frontend Framework** | [React 18](https://react.dev) | Komponen UI reaktif, interaktif, dan modular |
| **Styling & Design** | [Tailwind CSS 3](https://tailwindcss.com) | Sistem desain utilitas, tata letak responsif, dan dukungan tema gelap (*Dark Mode*) |
| **Ikonografi** | [Lucide React](https://lucide.dev) | Ikon antarmuka yang bersih, konsisten, dan modern |
| **Notifikasi Eksternal** | [Telegram Bot API](https://core.telegram.org/bots/api) | Layanan pesan proaktif dan antarmuka bot dua arah |
| **Penyimpanan Berkas** | Local Storage / S3 | Penyimpanan modul materi kuliah dengan abstraksi Laravel Filesystem |
| **Pengujian Otomatis** | Pest & PHPUnit | 118 skenario pengujian fungsional dan unit backend |

---

## 💻 Panduan Instalasi & Menjalankan Lokal

Ikuti langkah-langkah di bawah ini untuk menjalankan Academic Hub di komputer lokal Anda:

### 1. Prasyarat Sistem
- **Git**
- **PHP** versi 8.2 atau lebih baru (dengan ekstensi `pdo`, `mbstring`, `openssl`, `curl`)
- **Composer** (Manajer paket PHP)
- **Node.js** (v18+) dan **NPM**

### 2. Kloning Repositori
```bash
git clone https://github.com/0neNopen/academic-hub.git
cd academic-hub
```

### 3. Pemasangan Dependensi
```bash
# Pasang dependensi PHP (Backend)
composer install

# Pasang dependensi Node.js (Frontend)
npm install
```

### 4. Konfigurasi Lingkungan (`.env`)
Salin file konfigurasi contoh dan buat kunci aplikasi baru:
```bash
cp .env.example .env
php artisan key:generate
```

Secara bawaan, aplikasi siap menggunakan database **SQLite**. Anda cukup memastikan baris berikut ada di `.env`:
```ini
DB_CONNECTION=sqlite
```
*(File database SQLite akan otomatis dibuat saat migrasi dijalankan).*

### 5. Migrasi Basis Data & Tautan Penyimpanan
Jalankan migrasi basis data dan buat simbolik link untuk direktori penyimpanan materi:
```bash
php artisan migrate
php artisan storage:link
```

### 6. Konfigurasi Bot Telegram (Opsional)
Jika Anda ingin menguji fitur bot Telegram:
1. Buat bot baru melalui **[@BotFather](https://t.me/BotFather)** di Telegram.
2. Salin token bot yang diberikan dan masukkan ke dalam file `.env`:
   ```ini
   TELEGRAM_BOT_TOKEN=token_bot_anda_disini
   TELEGRAM_BOT_USERNAME=username_bot_anda
   TELEGRAM_WEBHOOK_SECRET=buat_kunci_acak_disini
   ```

### 7. Menjalankan Server Pengembangan
Jalankan dua terminal terpisah:

```bash
# Terminal 1: Menjalankan server backend Laravel
php artisan serve
```

```bash
# Terminal 2: Menjalankan Vite bundler untuk frontend
npm run dev
```

Buka peramban web dan akses: **`http://localhost:8000`**

### 8. Menjalankan Pengujian Otomatis (Automated Tests)
Untuk memverifikasi keutuhan logika seluruh fitur sistem:
```bash
php artisan test
```

---

## ☁️ Panduan Deployment (Cloud & Serverless)

Academic Hub dirancang ringan dan siap di-deploy pada berbagai layanan cloud modern (Render, Railway, Fly.io, VPS):

1. **Build Aset Frontend**:
   ```bash
   npm run build
   ```
2. **Optimasi Cache Laravel**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. **Pemicu Scheduler Cron Otomatis**:
   Pada penyedia hosting yang tidak memiliki akses cron daemon (seperti paket gratis Render), gunakan layanan gratis seperti [cron-job.org](https://cron-job.org) untuk memanggil rute cron scheduler secara periodik (misal setiap 10 atau 15 menit):
   ```
   GET https://domain-anda.com/cron/run-schedule?key=NILAI_CRON_KEY_ANDA
   ```
4. **Mendaftarkan Webhook Telegram**:
   Jalankan perintah Artisan sekali setelah aplikasi terbit di domain publik ber-HTTPS:
   ```bash
   php artisan telegram:set-webhook
   ```

---

## 🤝 Berkontribusi

Kontribusi untuk pengembangan Academic Hub sangat diapresiasi!
1. Lakukan **Fork** pada repositori ini.
2. Buat branch baru untuk fitur atau perbaikan Anda (`git checkout -b fitur/nama-fitur-baru`).
3. Lakukan commit perubahan Anda (`git commit -m 'Menambahkan fitur nama-fitur-baru'`).
4. Pastikan seluruh pengujian lulus (`php artisan test` dan `npm run build`).
5. Push ke branch Anda (`git push origin fitur/nama-fitur-baru`).
6. Buka **Pull Request** di GitHub.

---

## 📄 Lisensi

Projek ini didistribusikan di bawah lisensi terbuka [MIT License](LICENSE). Anda bebas menggunakan, memodifikasi, dan mengembangkannya untuk kepentingan akademik maupun pribadi.
