<?php 
/**
 * ==============================================
 * VIEW: Verifikasi & Validasi Berkas Perkara
 * File: keuangan/v_verifikasi.php
 * Fungsi: Meja kerja Admin, Kuasa Hukum, Keuangan
 * Data dari: Dashboard.php case 'verifikasi' -> $data['berkas']
 * Logic: Filter data sesuai jabatan login. 1 view 3 otoritas
 * Alur: Admin Teruskan → Kuasa Hukum Validasi → Keuangan Tagih
 * ==============================================
 */
?>

<div class="container-fluid px-4">

    <!-- HEADER + JABATAN DINAMIS -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Verifikasi & Validasi Berkas Perkara</h3>
            <p class="text-muted small mb-0">Antrean dokumen masuk yang memerlukan persetujuan Anda otoritas sebagai <strong><?= $this->session->userdata('jabatan'); ?></strong>.</p>
        </div>
        <span class="text-muted small bg-white border px-3 py-2 rounded shadow-sm">
            <i class="fas fa-calendar-alt me-2 text-primary"></i><?= date('d F Y'); ?>
        </span>
    </div>

    <!-- FLASHDATA SUCCESS -->
    <?php if($this->session->flashdata('pesan')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $this->session->flashdata('pesan'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No Perkara</th>
                            <th>Nama Klien</th>
                            <th>Judul Perkara</th>
                            <th>Berkas Lampiran</th>
                            <th>Status Posisi</th>
                            <th width="200" class="text-center">Tindakan Otoritas</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if(!empty($berkas)): ?>
                            <?php foreach($berkas as $b): ?>
                                
                                <?php 
                                $jabatan_sekarang = $this->session->userdata('jabatan');
                                
                                // NORMALISASI STATUS: strtolower biar pengecekan 100% akurat
                                $status_berkas = strtolower($b['STATUS_VERIFIKASI_OPS'] ?? '');
                                
                                /**
                                 * FILTER KEAMANAN RBAC MEJA KERJA
                                 * Admin hanya liat Pending Admin
                                 * Kuasa Hukum hanya liat Pending Kuasa Hukum  
                                 * Keuangan hanya liat Pending Keuangan
                                 * Ini cegah user liat data yg bukan porsinya
                                 */
                                $boleh_tampil = false;
                                if ($jabatan_sekarang == 'Admin' && $status_berkas == 'pending admin') {
                                    $boleh_tampil = true;
                                } elseif ($jabatan_sekarang == 'Kuasa Hukum' && $status_berkas == 'pending kuasa hukum') {
                                    $boleh_tampil = true;
                                } elseif ($jabatan_sekarang == 'Keuangan' && $status_berkas == 'pending keuangan') {
                                    $boleh_tampil = true;
                                }
                                ?>

                                <!-- Render baris hanya kalo sesuai otoritas -->
                                <?php if ($boleh_tampil): ?>
                                <tr>
                                    <td><span class="fw-bold text-dark"><?= $b['NO_PERKARA']; ?></span></td>
                                    <td><?= $b['NAMA_KLIEN'] ?? '-'; ?></td>
                                    <td><?= $b['JUDUL_PERKARA'] ?? '-'; ?></td>
                                    
                                    <!-- LINK BERKAS PDF -->
                                    <td>
                                        <?php if(!empty($b['BERKAS_PERKARA'])): ?>
                                            <a href="<?= base_url('uploads/perkara/'.$b['BERKAS_PERKARA']); ?>"
                                               target="_blank"
                                               class="btn btn-xs btn-outline-danger px-3 py-1">
                                                <i class="fas fa-file-pdf me-1"></i> Lihat Dokumen
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small italic">Tidak ada berkas</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- BADGE STATUS SESUAI MEJA KERJA -->
                                    <td>
                                        <?php if($status_berkas == 'pending admin'): ?>
                                            <span class="badge bg-info text-dark px-3 py-2"><i class="fas fa-user-shield me-1"></i> Antrean Admin</span>
                                        <?php elseif($status_berkas == 'pending kuasa hukum'): ?>
                                            <span class="badge bg-primary px-3 py-2"><i class="fas fa-gavel me-1"></i> Antrean Kuasa Hukum</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-file-invoice-dollar me-2"></i> Siap Buat Invoice</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- TOMBOL AKSI DINAMIS SESUAI JABATAN -->
                                    <td class="text-center">
                                        <?php if($jabatan_sekarang == 'Admin' && $status_berkas == 'pending admin'): ?>
                                            <!-- Admin: Teruskan ke Kuasa Hukum -->
                                            <a href="<?= base_url('keuangan/admin_setujui_berkas?id=' . urlencode($b['NO_TRANSAKSI'])); ?>"
                                               class="btn btn-sm btn-success fw-medium px-3"
                                               onclick="return confirm('Apakah Anda ingin meneruskannya ke Kuasa Hukum?');">
                                                <i class="fas fa-share me-1"></i> Teruskan ke Pengacara
                                            </a>
                                        <?php elseif($jabatan_sekarang == 'Kuasa Hukum' && $status_berkas == 'pending kuasa hukum'): ?>
                                            <!-- Kuasa Hukum: Validasi + TTD -->
                                            <a href="<?= base_url('keuangan/kuasahukum_setujui_berkas?id=' . urlencode($b['NO_TRANSAKSI'])); ?>"
                                               class="btn btn-sm btn-primary fw-medium px-3"
                                               onclick="return confirm('Apakah validasi hukum berkas ini sudah sah?');">
                                                <i class="fas fa-check-double me-1"></i> Validasi & Oper
                                            </a>
                                        <?php else: ?>
                                            <!-- Keuangan: Langsung ke form buat invoice -->
                                            <a href="<?= base_url('dashboard/keuangan/pembayaran'); ?>" class="btn btn-sm btn-success fw-bold px-3">
                                                <i class="fas fa-calculator me-1"></i> Proses Tagihan Klien
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- EMPTY STATE -->
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-check-circle fs-3 text-success mb-2 d-block"></i>
                                    Bersih! Tidak ada antrean berkas perkara yang perlu diverifikasi saat ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
