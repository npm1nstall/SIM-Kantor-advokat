# SIM-Kantor Advokat ⚖️
Sistem Informasi Manajemen Kantor Hukum + AI Assistant Chatbot  
**Tech Stack:** CodeIgniter 3.1.11 | PHP 7.4+ | MySQL 5.7+ | Bootstrap 5.3 | FontAwesome 6 | TCPDF 6.6+ | Google AI API

Aplikasi manajemen data klien, perkara, keuangan, dan SDM untuk kantor hukum. Implementasi CI3 real-world: Multi-role authentication, workflow 4 tahap, upload berkas, generate PDF, role-based dashboard, monitoring jadwal sidang klien.

Tugas Akhir - Sistem Informasi

---

### 🔑 Akun Demo Login
| Role | Username/Telp | Password | Akses |
| --- | --- | --- | --- |
| **Klien** | `08123456789` | *kosongin* | Liat perkara + Upload bukti bayar + Jadwal sidang |
| **Admin** | `admin` | `admin123` | CRUD staff + Validasi berkas + Surat + Edit perkara |
| **Kuasa Hukum** | `kuasa1` | `kh123` | Validasi hukum + Input jadwal sidang + Hasil sidang |
| **Keuangan** | `keuangan` | `keu123` | Terbit invoice + Verifikasi pembayaran + Ajukan dana |
| **Pimpinan** | `pimpinan` | `pim123` | Dashboard KPI + Approval dana operasional |

> **HATI-HATI - Security Note:** Password demo pake MD5 khusus tugas akhir. Production wajib `password_hash()` + `password_verify()`. Jangan dipake real.

---

### 📦 Library Pihak Ketiga - WAJIB DOWNLOAD
Repo tidak include library berat biar clone ringan <5MB. Download manual:

**1. CodeIgniter 3.1.11**  
Lokasi: `/system/`  
Download: [codeigniter.com/download](https://codeigniter.com/download)  
Ekstrak folder `system` ke root project

**2. TCPDF Library**  
Lokasi: `/application/libraries/tcpdf/`  
Download: [github.com/tecnickcom/TCPDF](https://github.com/tecnickcom/TCPDF) → Download ZIP  
Ekstrak folder `tcpdf` ke `application/libraries/`

**3. Bootstrap 5 + FontAwesome 6**  
Pake CDN. Offline: download dari getbootstrap.com & fontawesome.com

---

### 📁 Struktur Project
SIM-Kantor-Advokat/
├── application/
│   ├── config/database.example.php  ← Copy jadi database.php
│   ├── controllers/
│   │   ├── Auth.php          ← Login dual role Klien + Staff
│   │   ├── Dashboard.php     ← Dashboard beda role + Modul SDM
│   │   ├── Keuangan.php      ← Workflow 4 tahap + Invoice + Upload bukti
│   │   └── Perkara.php       ← CRUD + Filter klien + Cetak PDF + Jadwal sidang
│   ├── libraries/tcpdf/      ← DOWNLOAD SENDIRI dari GitHub
│   ├── models/
│   │   ├── M_perkara.php     ← Query antrean beda role + LEFT JOIN + Filter klien
│   │   └── M_keuangan.php    ← Query keuangan + hitung tagihan
│   └── views/
│       ├── klien/            ← View khusus klien: v_jadwal.php, v_keuangan.php
│       ├── perkara/          ← View admin: v_daftar.php, v_proses_sidang.php
│       └── keuangan/         ← View keuangan: v_ajukan_dana.php, v_tagihan.php
├── system/                   ← DOWNLOAD SENDIRI CodeIgniter 3.1.11
├── uploads/
│   ├── perkara/.gitkeep      ← Berkas perkara PDF/DOCX/JPG
│   └── pembayaran/.gitkeep   ← Bukti transfer JPG/PNG/PDF
└── db_hukum.sql              ← File database lengkap + akun demo

---

### 💡 Fitur & Logic Bisnis Terbaru

**1. Multi-Role Authentication + Filter Klien**  
Klien login cukup nomor telp tanpa password. Staff login nama/telp + password. Session terpisah `klien_logged_in` vs `admin_logged_in`.  
**HATI-HATI:** Klien hanya liat perkara miliknya via `WHERE TELP_KLIEN = session`. Data "Pendaftaran Akun Baru" auto hide pake `not_like()`.

**2. Workflow Operasional 4 Tahap + Jadwal Sidang**  
`Klien Upload Berkas` → `Admin Verifikasi` → `Kuasa Hukum Validasi Hukum + Input Jadwal` → `Keuangan Terbit Invoice` → `Klien Upload Bukti Bayar` → `Keuangan Verifikasi Lunas`

Status di tabel `KEUANGAN.STATUS_VERIFIKASI_OPS`: `Pending Admin` → `Pending Kuasa Hukum` → `Pending Keuangan` → `Selesai`

**3. Modul Jadwal Sidang Klien**  
Klien punya menu "Jadwal Sidang" terpisah. Tampil card per perkara: No Internal, Tgl Registrasi, Penugasan Tim, Tgl Sidang, Agenda, Disposisi, Hasil Sidang. Auto hide card "Belum Dijadwalkan" kalo `status_halaman='jadwal_sidang'`.

**4. Modul Keuangan Lengkap**  
Pengajuan dana operasional staff, approval pimpinan, terbit invoice otomatis, klien upload bukti transfer, verifikasi lunas, hitung total pendapatan. Upload bukti wajib `enctype="multipart/form-data"`.

**5. Dashboard Role-Based**  
Admin: Total perkara, staff, surat, antrean verifikasi  
Keuangan: Pending invoice, total pembayaran lunas, summary status  
Pimpinan: KPI perkara proses/selesai, total pendapatan, recent data  
Klien: Status perkara + notifikasi tagihan + jadwal sidang berikutnya

**6. Upload Berkas & Bukti Bayar Aman**  
Upload PDF/DOCX/JPG/PNG max 5MB. File name di-encrypt pake `encrypt_name=TRUE` biar aman. Folder `/uploads/perkara/` dan `/uploads/pembayaran/` permission 755/777.

**7. Cetak Laporan PDF**  
Generate laporan data perkara format A4 pake TCPDF. Include: no perkara, judul, tanggal, nama klien, status, agenda sidang, hasil sidang.

**8. Timezone WIB Akurat**  
`date_default_timezone_set('Asia/Jakarta')`. Input datetime-local auto convert `2026-06-22T08:00` → `2026-06-22 08:00:00` MySQL DATETIME. Format balik pake `date('Y-m-d\TH:i', strtotime())`.

---

### ⚙️ Cara Install 5 Menit

**1. Download Library Berat**  
Download CI3 + TCPDF sesuai petunjuk di atas. Taruh di folder yg benar. Jangan sampe folder `system` ketinggalan.

**2. Import Database**  
Buka phpMyAdmin → Import file `db_hukum.sql`  
Nama database default: `db_hukum`  
**HATI-HATI:** Kalo error "Table exists", drop dulu database lama.

**3. Setting Koneksi DB**  
Copy `application/config/database.example.php` → rename jadi `database.php`  
Edit: hostname, username, password, database

```php
$db['default'] = array(
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'db_hukum',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
);
4. Setting Timezone
Buka application/config.php → set: $config['time_reference'] = 'Asia/Jakarta';
5. Permission Folder Upload
Folder /uploads/perkara/ dan /uploads/pembayaran/ kasih permission 755 atau 777 biar bisa upload.6. Akses Aplikasi
http://localhost/SIM-Kantor-Advokat/auth untuk login

🛠️ Teknologi & LibraryFramework: CodeIgniter 3.1.11 MVC Pattern
Frontend: Bootstrap 5.3 + FontAwesome 6.4 + Card Responsive
Database: MySQL 5.7+ / MariaDB, Query Builder CI3
PDF Generator: TCPDF 6.6+ untuk laporan A4
AI Integration: Google AI Assistant Chatbot API
Session: CI3 Session Library, dual role separation
