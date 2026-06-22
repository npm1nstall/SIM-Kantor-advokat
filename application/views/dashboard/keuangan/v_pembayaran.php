<?php 
/**
 * ==============================================
 * VIEW: Pembayaran & Invoice Klien
 * File: keuangan/v_pembayaran.php
 * Fungsi: Kelola tagihan, invoice, verifikasi bukti bayar klien
 * Data dari: Dashboard.php case 'pembayaran' -> $data['tagihan']
 * Query: Join KEUANGAN + PERKARA buat ambil NO_PERKARA, TTL_TAGIHAN_KLIEN
 * Workflow: Pending Keuangan → Buat Invoice → Klien Bayar → Verifikasi
 * ==============================================
 */
?>

<div class="container-fluid px-4">

    <!-- HEADER + TOMBOL KE ANTREAN -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <h3 class="fw-bold mb-0">Pembayaran & Invoice Klien</h3>
        
        <!-- Tombol ke daftar berkas yg status Pending Keuangan -->
        <a href="<?= base_url('dashboard/keuangan/verifikasi'); ?>" class="btn btn-primary">
            <i class="fas fa-tasks"></i> Lihat Antrean Berkas Siap Tagih
        </a>
    </div>

    <!-- FLASHDATA SUCCESS -->
    <?php if($this->session->flashdata('pesan')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $this->session->flashdata('pesan'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TABEL TAGIHAN KLIEN -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No Perkara</th>
                            <th>No Invoice</th>
                            <th>Total Tagihan</th>
                            <th>Bukti Bayar</th>
                            <th>Alur Berkas</th> <!-- Posisi workflow 4 tahap -->
                            <th>Status Bayar</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($tagihan)): ?>
                            <?php foreach($tagihan as $t): ?>
                            <tr>
                                <!-- NO PERKARA -->
                                <td><span class="fw-semibold text-dark"><?= $t->NO_PERKARA; ?></span></td>
                                
                                <!-- NO INVOICE: Badge hitam kalo udah ada -->
                                <td>
                                    <?= !empty($t->NO_INVOICE)
                                        ? '<span class="badge bg-dark">'.$t->NO_INVOICE.'</span>'
                                        : '<span class="text-muted small italic">Belum Diterbitkan</span>'; ?>
                                </td>

                                <!-- TOTAL TAGIHAN: Format rupiah -->
                                <td>
                                    <?php if(!empty($t->TTL_TAGIHAN_KLIEN)): ?>
                                        <span class="fw-bold text-success">Rp <?= number_format($t->TTL_TAGIHAN_KLIEN,0,',','.'); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- BUKTI BAYAR: Link ke file upload -->
                                <td>
                                    <?php if(!empty($t->BUKTI_BAYAR_KLIEN)): ?>
                                        <a href="<?= base_url('uploads/pembayaran/'.$t->BUKTI_BAYAR_KLIEN); ?>"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Lihat Bukti
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-white-50">Belum Upload</span>
                                    <?php endif; ?>
                                </td>

                                <!-- ALUR BERKAS: Posisi workflow 4 tahap -->
                                <td>
                                    <?php 
                                    $ops = $t->STATUS_VERIFIKASI_OPS;
                                    if ($ops == 'Pending Admin') {
                                        echo '<span class="badge bg-info text-dark">Di Meja Admin</span>';
                                    } elseif ($ops == 'Pending Kuasa Hukum') {
                                        echo '<span class="badge bg-primary">Di Kuasa Hukum</span>';
                                    } elseif ($ops == 'Pending Keuangan') {
                                        echo '<span class="badge bg-warning text-dark">Siap Buat Invoice</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Validasi Selesai</span>';
                                    }
                                    ?>
                                </td>

                                <!-- STATUS BAYAR: Lunas/Menunggu/Ditolak -->
                                <td>
                                    <?php
                                    $status = $t->STATUS_BAYAR_KLIEN;
                                    if ($status == 'Lunas') {
                                        echo '<span class="badge bg-success">Lunas</span>';
                                    } elseif ($status == 'Menunggu Verifikasi') {
                                        echo '<span class="badge bg-warning text-dark">Menunggu Verifikasi</span>';
                                    } elseif ($status == 'Ditolak') {
                                        echo '<span class="badge bg-danger">Ditolak</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">Belum Bayar</span>';
                                    }
                                    ?>
                                </td>

                                <!-- AKSI DINAMIS SESUAI STATUS -->
                                <td>
                                    <!-- Kalo Pending Keuangan = tombol Buat Invoice -->
                                    <?php if($t->STATUS_VERIFIKASI_OPS == 'Pending Keuangan'): ?>
                                        <a href="<?= base_url('dashboard/keuangan/tambah_tagihan/'.$t->NO_TRANSAKSI); ?>"
                                           class="btn btn-sm btn-success fw-medium">
                                            <i class="fas fa-file-invoice"></i> Buat Invoice
                                        </a>
                                    <!-- Kalo udah ada invoice = tombol Edit -->
                                    <?php else: ?>
                                        <a href="<?= base_url('dashboard/keuangan/edit_tagihan/'.$t->NO_TRANSAKSI); ?>"
                                           class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    <?php endif; ?>

                                    <!-- Kalo klien udah upload bukti = tombol Verifikasi muncul -->
                                    <?php if($t->STATUS_BAYAR_KLIEN == 'Menunggu Verifikasi'): ?>
                                        <a href="<?= base_url('dashboard/keuangan/verifikasi_bayar/'.$t->NO_TRANSAKSI); ?>"
                                           class="btn btn-sm btn-info text-white ms-1">
                                            Verifikasi
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- EMPTY STATE -->
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-file-invoice fs-3 text-white-50 mb-2 d-block"></i>
                                    Belum ada data transaksi atau tagihan klien
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
