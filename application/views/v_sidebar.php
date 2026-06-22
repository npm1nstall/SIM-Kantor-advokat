<?php 
/**
 * ==============================================
 * SIDEBAR TEMPLATE - MENU ROLE BASED
 * File: v_sidebar.php
 * Fungsi: Tampilkan menu beda2 sesuai role login
 * Logic: Klien vs Admin vs Kuasa Hukum vs Keuangan vs Pimpinan
 * Notif: Badge merah notif_bayar buat Keuangan
 * Dipanggil di: auth/v_header setelah buka wrapper
 * ==============================================
 */

$jabatan = $this->session->userdata('jabatan'); 
$is_klien = ($this->session->userdata('klien_logged_in') == TRUE);
?>

<!-- Sidebar wrapper - CSS Bootstrap flexbox -->
<div id="sidebar-wrapper"> 
    <div class="list-group list-group-flush my-3">

    <!-- ================= MENU DASHBOARD ================= -->
    <?php if ($is_klien): ?>
    <!-- KLIEN: Dashboard = halaman greeting ringkas -->
    <a href="<?= base_url('dashboard'); ?>" 
       class="list-group-item bg-transparent text-white fw-medium border-0">
        <i class="fas fa-th-large me-3"></i> Dashboard Klien
    </a>

    <?php elseif ($jabatan == 'Keuangan'): ?>
        <!-- KEUANGAN: Dashboard khusus modul keuangan biar angka summary sinkron -->
        <a href="<?= base_url('dashboard/keuangan'); ?>" 
           class="list-group-item bg-transparent text-white fw-medium border-0">
            <i class="fas fa-th-large me-3"></i> Dashboard
        </a>

    <?php else: ?>
        <!-- ADMIN/KUASA HUKUM/PIMPINAN: Dashboard umum -->
        <a href="<?= base_url('dashboard'); ?>" 
           class="list-group-item bg-transparent text-white fw-medium border-0">
            <i class="fas fa-th-large me-3"></i> Dashboard
        </a>
    <?php endif; ?>

    <!-- ================= MENU KLIEN ================= -->
    <?php if ($is_klien): ?>

        <a href="<?= base_url('perkara/jadwal_sidang'); ?>" 
           class="list-group-item bg-transparent text-white fw-medium border-0">
           <i class="fas fa-calendar-check me-3"></i> Jadwal Sidang
        </a>

        <a href="<?= base_url('keuangan/pembayaran_klien'); ?>" 
           class="list-group-item bg-transparent text-white fw-medium border-0">
           <i class="fas fa-wallet me-3"></i> Pembayaran
        </a>

        <a href="<?= base_url('perkara'); ?>" 
           class="list-group-item bg-transparent text-white fw-medium border-0">
            <i class="fas fa-folder-open me-3"></i> Data Perkara
        </a>

    <!-- ================= MENU STAFF INTERNAL ================= -->
    <?php else: ?>

        <!-- MENU PERKARA - Admin & Kuasa Hukum doang -->
        <?php if (in_array($jabatan, ['Admin', 'Kuasa Hukum'])): ?>
            <a href="<?= base_url('perkara'); ?>" 
               class="list-group-item bg-transparent text-white fw-medium border-0">
                <i class="fas fa-gavel me-3"></i> Manajemen Perkara
            </a>
        <?php endif; ?>

        <!-- MENU SURAT - Admin full CRUD, Pimpinan read-only -->
        <?php if ($jabatan == 'Admin'): ?>
            <a href="<?= base_url('dashboard/surat'); ?>" 
               class="list-group-item bg-transparent text-white fw-medium border-0">
                <i class="fas fa-envelope me-3"></i> Manajemen Surat
            </a>
        <?php elseif ($jabatan == 'Pimpinan'): ?>
            <a href="<?= base_url('dashboard/surat'); ?>" 
               class="list-group-item bg-transparent text-white fw-medium border-0">
                <i class="fas fa-envelope me-3"></i> Laporan Surat
            </a>
        <?php endif; ?>

        <!-- ================= SEPARATOR KEUANGAN ================= -->
        <div class="px-3 pt-3 text-white-50 small fw-bold">KEUANGAN & OPERASIONAL</div>

        <!-- VERIFIKASI BERKAS - Admin + Kuasa Hukum -->
        <?php if (in_array($jabatan, ['Admin', 'Kuasa Hukum'])): ?>
            <a href="<?= base_url('dashboard/keuangan/verifikasi'); ?>" 
               class="list-group-item bg-transparent text-white border-0">
                <i class="fas fa-tasks me-3"></i>
                Verifikasi Berkas
            </a>
        <?php endif; ?>

        <!-- AJUKAN BIAYA OPS - Khusus Kuasa Hukum -->
        <?php if ($jabatan == 'Kuasa Hukum'): ?>
            <a href="<?= base_url('dashboard/keuangan/pengajuan_ops'); ?>" 
               class="list-group-item bg-transparent text-white border-0">
                <i class="fas fa-file-invoice-dollar me-3"></i>
                Ajukan Biaya Ops
            </a>
        <?php endif; ?>

        <!-- MENU KEUANGAN - Khusus Staf Keuangan -->
        <?php if ($jabatan == 'Keuangan'): ?>
            <a href="<?= base_url('dashboard/keuangan/keuangan'); ?>" 
               class="list-group-item bg-transparent text-white border-0">
                <i class="fas fa-folder-open me-3"></i>
                Data Pengajuan
            </a>

            <a href="<?= base_url('dashboard/keuangan/verifikasi'); ?>" 
               class="list-group-item bg-transparent text-white border-0">
                <i class="fas fa-tasks me-3"></i>
                Verifikasi Berkas Klien
            </a>

            <!-- MENU PEMBAYARAN + BADGE NOTIF -->
            <a href="<?= base_url('dashboard/keuangan/pembayaran'); ?>"
               class="list-group-item bg-transparent text-white border-0">
                <i class="fas fa-wallet me-3"></i>
                Pembayaran & Invoice

                <!-- Badge merah muncul kalo ada tagihan belum bayar -->
                <?php if (!empty($notif_bayar) && $notif_bayar > 0): ?>
                    <span class="badge bg-danger ms-2"><?= $notif_bayar ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <!-- APPROVAL PIMPINAN - Khusus Pimpinan -->
        <?php if ($jabatan == 'Pimpinan'): ?>
            <a href="<?= base_url('dashboard/keuangan/approval'); ?>" 
               class="list-group-item bg-transparent text-white border-0">
                <i class="fas fa-check-double me-3"></i>
                Approval Pimpinan
            </a>
        <?php endif; ?>

        <!-- LAPORAN KEUANGAN - Keuangan + Pimpinan -->
        <?php if (in_array($jabatan, ['Keuangan', 'Pimpinan'])): ?>
            <a href="<?= base_url('dashboard/keuangan/laporan'); ?>" 
               class="list-group-item bg-transparent text-white border-0">
                <i class="fas fa-chart-line me-3"></i>
                Laporan Keuangan
            </a>
        <?php endif; ?>

        <!-- ================= SEPARATOR SDM ================= -->
        <?php if (in_array($jabatan, ['Admin', 'Pimpinan'])): ?>
            <div class="px-3 pt-3 text-white-50 small fw-bold">LAPORAN & SDM</div>

            <a href="<?= base_url('dashboard/laporan_perkara'); ?>" 
               class="list-group-item bg-transparent text-white border-0">
                <i class="fas fa-gavel me-3"></i>
                Laporan Perkara & Sidang
            </a>
        <?php endif; ?>

        <!-- MENU ADMIN ONLY -->
        <?php if ($jabatan == 'Admin'): ?>
            <a href="<?= base_url('dashboard/staff'); ?>" 
               class="list-group-item bg-transparent text-white border-0">
                <i class="fas fa-users me-3"></i>
                Data Staff
            </a>

            <a href="<?= base_url('dashboard/cuti'); ?>" 
               class="list-group-item bg-transparent text-white border-0">
                <i class="fas fa-umbrella-beach me-3"></i>
                Manajemen Cuti
            </a>
        <?php endif; ?>

    <?php endif; ?>

    <!-- ================= LOGOUT ================= -->
    <hr class="mx-4 text-white-50">

    <a href="<?= base_url('auth/logout'); ?>" 
       class="list-group-item bg-transparent text-white fw-medium border-0">
        <i class="fas fa-sign-out-alt me-3"></i> Logout
    </a>

</div>
</div>

<!-- KONTEN UTAMA - Navbar + Judul Halaman -->
<div id="page-content-wrapper">
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
        <div class="d-flex align-items-center">
            <!-- Tombol toggle sidebar mobile -->
            <button id="menu-toggle" class="btn btn-outline-secondary me-3">
                <i class="fas fa-bars"></i>
            </button>
            <!-- Judul dinamis dari $data['title'] di controller -->
            <h2 class="fs-2 m-0"><?= isset($title) ? $title : 'Dashboard'; ?></h2>
        </div>
    </nav>
