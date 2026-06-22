# SIM-Kantor Advokat ⚖️
Sistem Informasi Manajemen Kantor Hukum + AI Assistant Chatbot  
**Tech Stack:** CodeIgniter 3.1.11 | PHP 7.4+ | MySQL | Bootstrap 5 | FontAwesome | TCPDF | Google AI API

Aplikasi manajemen data klien, perkara, keuangan, dan SDM untuk kantor hukum. Implementasi CI3 real-world: Multi-role authentication, workflow 4 tahap, upload berkas, generate PDF, role-based dashboard.

Tugas Akhir - Sistem Informasi

---

### 🔑 Akun Demo Login
| Role | Username/Telp | Password | Akses |
| --- | --- | --- | --- |
| **Klien** | `08123456789` | *kosongin* | Liat perkara + Upload bukti bayar |
| **Admin** | `admin` | `admin123` | CRUD staff + Validasi berkas + Surat |
| **Kuasa Hukum** | `kuasa1` | `kh123` | Validasi hukum + Input jadwal sidang |
| **Keuangan** | `keuangan` | `keu123` | Terbit invoice + Verifikasi pembayaran |
| **Pimpinan** | `pimpinan` | `pim123` | Dashboard KPI + Approval dana |

> **Security Note:** Password demo pake MD5 khusus tugas akhir. Production wajib `password_hash()` + `password_verify()`.

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
SIM-Kantor-advokat/
├── application/
│   ├── config/database.example.php  ← Copy jadi database.php
│   ├── controllers/
│   │   ├── Auth.php          ← Login dual role Klien + Staff
│   │   ├── Dashboard.php     ← Dashboard beda role + Modul SDM
│   │   ├── Keuangan.php      ← Workflow 4 tahap + Invoice + Upload bukti
│   │   └── Perkara.php       ← CRUD + Filter klien + Cetak PDF + Jadwal sidang
│   ├── libraries/tcpdf/      ← DOWNLOAD SENDIRI dari GitHub
│   ├── models/
│   │   ├── M_perkara.php     ← Query antrean beda role + LEFT JOIN
│   │   └── M_keuangan.php    ← Query keuangan + hitung tagihan
│   └── views/                ← Template Bootstrap 5 responsive
├── system/                   ← DOWNLOAD SENDIRI CodeIgniter 3.1.11
├── uploads/
│   ├── perkara/.gitkeep      ← Berkas perkara PDF/DOCX
│   └── pembayaran/.gitkeep   ← Bukti transfer JPG/PNG/PDF
└── db_hukum.sql              ← File database


---

### 💡 Fitur & Logic Bisnis

**1. Multi-Role Authentication**  
Klien login cukup nomor telp tanpa password. Staff login nama/telp + password. Session terpisah `klien_logged_in` vs `admin_logged_in`.

**2. Workflow Operasional 4 Tahap**  
`Klien Upload Berkas` → `Admin Verifikasi` → `Kuasa Hukum Validasi Hukum` → `Keuangan Terbit Invoice` → `Klien Upload Bukti Bayar` → `Keuangan Verifikasi Lunas`

Status di tabel `KEUANGAN.STATUS_VERIFIKASI_OPS`: `Pending Admin` → `Pending Kuasa Hukum` → `Pending Keuangan` → `Selesai`

**3. Filter Data Klien Otomatis**  
Klien hanya lihat perkara miliknya via `TELP_KLIEN`. Data "Pendaftaran Akun Baru" auto hide jika klien sudah punya perkara asli. Query `not_like('JUDUL_PERKARA', 'Pendaftaran')`.

**4. Modul Keuangan Lengkap**  
Pengajuan dana operasional staff, approval pimpinan, terbit invoice otomatis, klien upload bukti transfer, verifikasi lunas, hitung total pendapatan.

**5. Dashboard Role-Based**  
Admin: Total perkara, staff, surat, antrean verifikasi  
Keuangan: Pending invoice, total pembayaran lunas, summary status  
Pimpinan: KPI perkara proses/selesai, total pendapatan, recent data  
Klien: Status perkara + notifikasi tagihan belum bayar

**6. Upload Berkas & Bukti Bayar**  
Upload PDF/DOCX/JPG/PNG max 5MB. File name di-encrypt biar aman. Folder `/uploads/` permission 777.

**7. Cetak Laporan PDF**  
Generate laporan data perkara format A4 pake TCPDF. Include: no perkara, judul, tanggal, nama klien, status, agenda sidang.

**8. Timezone WIB Akurat**  
`date_default_timezone_set('Asia/Jakarta')`. Input datetime-local auto convert `2026-06-22T08:00` → `2026-06-22 08:00:00` MySQL DATETIME.

---

### ⚙️ Cara Install 5 Menit

**1. Download Library Berat**  
Download CI3 + TCPDF sesuai petunjuk di atas. Taruh di folder yg benar.

**2. Import Database**  
Buka phpMyAdmin → Import file `db_hukum.sql`  
Nama database default: `db_hukum`

**3. Setting Koneksi DB**  
Copy `application/config/database.example.php` → rename jadi `database.php`  
Edit: hostname, username, password, database

```php
$db['default'] = array(
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'db_hukum',
    // ...
);

🛠️ Teknologi & LibraryFramework: CodeIgniter 3.1.11 MVC PatternFrontend: Bootstrap 5.3 + FontAwesome 6.4Database: MySQL 5.7+ / MariaDBPDF Generator: TCPDF 6.6+AI Integration: Google AI Assistant Chatbot API
