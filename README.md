
---

### 💡 Fitur Utama
**1. Multi-Role Auth**  
Klien login no telp tanpa password. Staff login nama/telp + password.

**2. Workflow 4 Tahap**  
`Klien Upload` → `Admin Verif` → `Kuasa Hukum Validasi` → `Keuangan Invoice` → `Klien Bayar`

**3. Manajemen Perkara**  
CRUD + upload berkas PDF/DOCX/JPG + filter klien auto hide "Pendaftaran Akun".

**4. Modul Keuangan**  
Pengajuan dana, approval pimpinan, invoice, upload bukti transfer.

**5. Dashboard KPI**  
Admin/Keuangan/Pimpinan/Klien beda tampilan + data real-time.

**6. Cetak PDF TCPDF**  
Generate laporan perkara jadi PDF A4. Library TCPDF download manual.

**7. Timezone WIB**  
Jam masuk `Asia/Jakarta`, input datetime auto convert ke MySQL DATETIME.

---

### ⚙️ Cara Install 5 Menit
**1. Download Library**  
Download CI3 + TCPDF sesuai petunjuk di atas. Taruh di folder yg bener.

**2. Database**  
Import `db_hukum.sql` ke phpMyAdmin.  
DB name: `db_hukum`

**3. Setting Koneksi**  
Copy `database.example.php` → `database.php`  
Isi host, user, pass, nama DB kamu

**4. Folder Upload**  
Bikin `/uploads/perkara/` dan `/uploads/pembayaran/` → permission 777

**5. Jalanin**  
Buka: `http://localhost/SIM-Kantor-advokat/auth`

---

### 📝 Catatan Developer
1. **Folder `system/` + `tcpdf/` tidak diupload** karena size >20MB. HRD clone jadi cepet.
2. **Password MD5** = khusus demo tugas akhir. Produksi wajib `password_hash()`.
3. **Timezone** udah diset `Asia/Jakarta` di controller.
4. **File .gitkeep** dipake biar folder `uploads/` muncul di GitHub walau kosong.

---

Dibuat untuk Tugas Akhir Sistem Informasi.  
Implementasi CI3 real-world: multi-role, workflow, upload file, role-based dashboard, generate PDF.
