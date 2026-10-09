---
title: Academic Hub
emoji: 🎓
colorFrom: indigo
colorTo: blue
sdk: docker
app_port: 7860
pinned: false
---

# 🎓 Academic Hub

<p align="center">
  <img src="public/images/academic-hub-emblem-transparent.png" alt="Academic Hub Logo" width="130" style="border-radius: 20px;">
</p>

<p align="center">
  <strong>Sistem Manajemen Perkuliahan & Pengingat Tugas Otomatis untuk Mahasiswa</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Status-Active%20&%20Maintained-success?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/UI-Dual%20Theme%20(Dark%20&%20Light)-indigo?style=for-the-badge" alt="Theme">
  <img src="https://img.shields.io/badge/Notification-Telegram%20&%20WhatsApp-26A5E4?style=for-the-badge&logo=telegram&logoColor=white" alt="Notification">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
</p>

---

## 🌟 Mengenal Academic Hub

**Academic Hub** adalah platform asisten akademik all-in-one yang diciptakan untuk membantu mahasiswa mengorganisir kehidupan perkuliahan dengan tenang, terstruktur, dan bebas stres. 

Seringkali mahasiswa melewatkan batas waktu tugas kuliah atau kesulitan mencari slide materi perkuliahan yang tercecer di berbagai grup chat. Academic Hub hadir sebagai solusi terpusat: mencatat jadwal kuliah, mengarsipkan berkas materi setiap pertemuan, serta memantau deadline tugas dengan **pengingat cerdas otomatis yang dikirim langsung ke Telegram dan WhatsApp**.

---

## 🚀 Fitur Unggulan Sistem

### 📚 1. Manajemen Mata Kuliah & Jadwal Terstruktur
- **Pencatatan Lengkap**: Simpan informasi mata kuliah, dosen pengampu, kode matkul, jumlah SKS, hingga ruangan kelas.
- **Navigasi Cepat**: Cari dan saring mata kuliah berdasarkan hari atau semester aktif.
- **Ringkasan Kartu Kuliah**: Pantau jumlah tugas aktif dan materi yang tersimpan dalam satu kartu mata kuliah.

### ⏰ 2. Pelacak Tugas & Indikator Urgensi Deadline
- **Penanda Visual Otomatis**: Warna status tugas berubah dinamis sesuai kedekatan waktu deadline (🔴 Sangat Mendesak <24 Jam, 🟡 Siaga <3 Hari, 🟢 Aman >3 Hari).
- **Instruksi & Tautan Tugas**: Catat keterangan tugas dosen (format berkas, syarat pengumpulan) beserta tautan link Google Classroom / LMS kampus.
- **Riwayat Tugas Selesai**: Arsipkan tugas yang sudah selesai dikerjakan agar daftar tetap fokus dan rapi.

### 📑 3. Arsip & Pratinjau Berkas Materi Kuliah
- **Mendukung Ragam Format**: Unggah modul dokumen (`PDF`, `DOCX`, `PPTX`, `XLSX`, `TXT`) maupun gambar materi (`JPG`, `PNG`, `WEBP`).
- **Pratinjau Langsung di Browser**: Baca slide dan dokumen PDF tanpa wajib mengunduh berkas ke perangkat.
- **Pengelompokan Pertemuan**: Berkas tertata rapi per pertemuan kuliah (Pertemuan 1 sampai 16).

### 🤖 4. Bot Asisten Telegram Cerdas
- **Pengingat Deadline Otomatis**: Notifikasi H-24 (1 hari sebelum) dan peringatan darurat H-3 (3 jam sebelum deadline) terkirim otomatis tanpa perlu membuka website.
- **Sapaan Jadwal Pagi (06:00 WIB)**: Rangkuman agenda kuliah hari ini dan tugas yang harus dikumpulkan disapa setiap pagi.
- **Ringkasan Awal Pekan (Senin 07:00 WIB)**: Rekapitulasi target tugas untuk satu minggu ke depan.
- **Menu Shortcut Interaktif**: Cek jadwal hari ini (`/jadwal`), seluruh jadwal (`/semua_jadwal`), tugas aktif (`/tugas`), dan arsip materi (`/materi`) hanya dengan satu ketukan tombol Menu biru.

### 🌓 5. Pengalaman Pengguna Modern
- **Mode Terang & Gelap (Dark Mode)**: Tampilan nyaman dan elegan untuk belajar di siang hari maupun mengerjakan tugas di malam hari.
- **Desain Responsif**: Akses nyaman melalui smartphone, tablet, maupun laptop.

---

## 🛠️ Ringkasan Teknologi yang Digunakan

Projek ini dibangun menggunakan teknologi web modern yang cepat, andal, dan modular:
- **Core Platform**: [Laravel](https://laravel.com) & [PHP](https://www.php.net) (Stabilitas logika dan penjadwalan otomatis)
- **Frontend Interaktif**: [React](https://react.dev) & [Inertia.js](https://inertiajs.com) (Navigasi mulus tanpa reload)
- **Desain & Styling**: [Tailwind CSS](https://tailwindcss.com) & [Lucide Icons](https://lucide.dev)
- **Integrasi Pesan**: Telegram Bot API & WhatsApp Gateway

---

## 💻 Panduan Clone & Fork untuk Pengembang

Bagi mahasiswa, dosen, atau pengembang yang ingin **melakukan Clone atau Fork** projek ini untuk digunakan sendiri di kampus Anda atau dikembangkan lebih lanjut, silakan ikuti petunjuk langkah demi langkah berikut:

### 1. Prasyarat Lingkungan
Pastikan perangkat Anda telah terpasang:
- Git
- PHP >= 8.2
- Composer
- Node.js (v18+) & NPM

### 2. Fork & Kloning Repositori
Lakukan fork pada repositori ini di GitHub, lalu unduh ke komputer lokal Anda:
```bash
git clone https://github.com/<username-anda>/academic-hub.git
cd academic-hub
```

### 3. Pasang Dependensi
Pasang pustaka backend dan frontend:
```bash
# Dependensi Backend
composer install

# Dependensi Frontend
npm install
```

### 4. Konfigurasi Environment (`.env`)
Salin berkas template environment:
```bash
cp .env.example .env
php artisan key:generate
```

Buka file `.env` yang baru dibuat. Anda dapat menggunakan database lokal SQLite yang sangat praktis tanpa perlu menginstal server database tambahan:
```ini
DB_CONNECTION=sqlite
```
*(File database SQLite akan otomatis dibuat saat menjalankan perintah migrasi).*

### 5. Jalankan Migrasi Database & Storage Link
```bash
php artisan migrate
php artisan storage:link
```

### 6. Konfigurasi Bot Telegram Pribadi (Opsional)
Jika Anda ingin bot Telegram mengirimkan notifikasi ke akun Anda sendiri:
1. Buka aplikasi Telegram, cari akun resmi **[@BotFather](https://t.me/BotFather)**.
2. Ketik `/newbot`, ikuti petunjuk nama bot Anda, lalu salin **API Token** yang diberikan.
3. Masukkan token ke file `.env`:
   ```ini
   TELEGRAM_BOT_TOKEN=masukkan_token_botfather_disini
   TELEGRAM_BOT_USERNAME=username_bot_anda
   ```

### 7. Jalankan Aplikasi
Jalankan aplikasi di lingkungan lokal:
```bash
# Terminal 1: Backend Server
php artisan serve

# Terminal 2: Frontend Asset Bundler
npm run dev

# Terminal 3 (Opsional): Jalankan Penjadwal Pengingat
php artisan schedule:work
```
Akses aplikasi melalui peramban di: **`http://localhost:8000`**

---

## 💡 Ide & Rekomendasi Pengembangan Lanjutan

Bagi Anda yang ingin menjadikan repositori ini sebagai bahan tugas akhir, portofolio, atau proyek open-source kampus, berikut adalah beberapa rekomendasi fitur yang sangat menarik untuk ditambahkan:

1. **Sistem Presensi & Kehadiran Kuliah**:
   - Menambahkan catatan kehadiran per pertemuan (Hadir, Izin, Sakit, Alpa) dan persentase kehadiran agar tidak melewati batas minimal ujian kampus.
2. **Kalkulator Prediksi IPK & Target Nilai**:
   - Fitur simulasi nilai tugas, kuis, UTS, dan UAS untuk menghitung estimasi indeks prestasi semester (IPS) dan kumulatif (IPK).
3. **Notifikasi Grup Diskusi Telegram**:
   - Kemampuan bot untuk dimasukkan ke grup belajar atau grup kelas, sehingga pengingat tugas kelompok dapat diterima bersama oleh anggota tim.
4. **Sinkronisasi Kalender (Google Calendar / iCal)**:
   - Fitur ekspor jadwal kuliah langsung ke kalender ponsel mahasiswa dengan format `.ics`.
5. **AI Ringkasan Materi & Kuis Mandiri**:
   - Integrasi AI untuk merangkum berkas materi kuliah yang diunggah dan membuat kartu latihan soal (flashcards) otomatis sebelum ujian.

---

## 🤝 Berkontribusi

Kontribusi dari siapa pun selalu terbuka lebar!
1. Lakukan **Fork** pada projek ini.
2. Buat branch baru untuk fitur Anda (`git checkout -b fitur/nama-fitur-baru`).
3. Lakukan commit perubahan Anda (`git commit -m 'Menambahkan fitur nama-fitur-baru'`).
4. Push ke branch Anda (`git push origin fitur/nama-fitur-baru`).
5. Buat **Pull Request** di GitHub.

---

## 📄 Lisensi

Projek ini berlisensi di bawah lisensi terbuka [MIT License](LICENSE). Anda bebas menggunakan, memodifikasi, dan mendistribusikan projek ini baik untuk kebutuhan akademik maupun pengembangan pribadi.
