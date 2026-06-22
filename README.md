
---

### 💡 Fitur Utama

**1. Multi-Role Authentication**  
Klien login cukup nomor telp tanpa password. Staff login pake nama/telp + password. Session beda.

**2. Alur Operasional 4 Tahap**  
`Klien Upload Berkas` → `Admin Verifikasi` → `Kuasa Hukum Validasi` → `Keuangan Terbit Invoice` → `Klien Bayar`

**3. Manajemen Perkara**  
CRUD data perkara, upload berkas PDF/DOCX/JPG, filter otomatis: klien cuma liat perkara miliknya + hide data "Pendaftaran Akun Baru".

**4. Modul Keuangan**  
Pengajuan dana operasional, approval pimpinan, terbit invoice, klien upload bukti transfer, verifikasi lunas.

**5. Dashboard Role-Based**  
Admin: summary perkara, staff, surat.  
Keuangan: pending invoice, total pembayaran.  
Pimpinan: KPI perkara proses/selesai, total pendapatan.  
Klien: status perkara + notif tagihan.

**6. Cetak PDF Laporan**  
Generate PDF laporan perkara pake TCPDF library. Bisa dipake buat arsip.

**7. Jadwal Sidang**  
Input tanggal sidang + agenda + hasil sidang. Format datetime auto convert ke MySQL DATETIME.

---

### ⚙️ Cara Install & Jalanin

**1. Database**  
Import file `db_hukum.sql` ke phpMyAdmin/MySQL.  
DB name default: `db_hukum`

**2. Setting Koneksi**  
Copy `application/config/database.example.php` → rename jadi `database.php`  
Isi: username, password, nama database kamu

**3. Folder Upload**  
Bikin folder manual:  
`/uploads/perkara/` dan `/uploads/pembayaran/`  
Kasih permission 777 biar bisa upload

**4. Akses**  
Buka: `http://localhost/SIM-Kantor-advokat/auth`

---

### 📝 Catatan Penting

1. **Folder `system/` tidak diupload** ke GitHub karena size besar.  
   Download CodeIgniter 3.1.11 manual di [codeigniter.com](https://codeigniter.com/) terus ekstrak ke root project.

2. **FontAwesome pake CDN** biar ringan. Kalo mau offline, download webfonts dari fontawesome.com

3. **Bootstrap pake CDN**. Kalo offline, download di [getbootstrap.com](https://getbootstrap.com/)

4. **Security Note:** Password MD5 cuma buat demo tugas akhir. Real project wajib pake `password_hash()` + `password_verify()`.

5. **Timezone:** Udah diset `Asia/Jakarta` di controller biar jam masuk akurat WIB.

---

### 🎯 Teknologi & Library
- **Backend:** CodeIgniter 3.1.11 MVC
- **Frontend:** Bootstrap 5 + FontAwesome 6
- **Database:** MySQL / MariaDB 
- **PDF:** TCPDF Library
- **AI:** Google AI Assistant Chatbot API

---

Dibuat untuk Tugas Akhir Sistem Informasi.  
Repo ini nunjukin implementasi CI3 real-world: multi-role, workflow, upload file, dan role-based dashboard.
