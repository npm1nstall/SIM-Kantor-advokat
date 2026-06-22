<?php 
/**
 * ==============================================
 * VIEW: Dashboard Admin
 * File: admin/admin_index.php
 * Fungsi: Halaman utama Admin setelah login
 * Data: $jml_perkara, $jml_surat, $jml_staff, $recent_perkara, $recent_keuangan
 * Controller: Dashboard.php -> case 'admin'
 * ==============================================
 */
?>

<div class="container-fluid mt-2">

    <!-- ROW KPI: 3 CARD RINGKASAN -->
    <div class="row">

        <!-- CARD 1: TOTAL PERKARA -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted small mb-1">Total Perkara</h6>
                    <!-- Data dari Controller Dashboard.php -> $data['jml_perkara'] -->
                    <h3 class="fw-bold mb-0"><?= isset($jml_perkara) ? $jml_perkara : 0 ?></h3>
                </div>
            </div>
        </div>

        <!-- CARD 2: TOTAL SURAT -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted small mb-1">Total Surat</h6>
                    <h3 class="fw-bold mb-0"><?= isset($jml_surat) ? $jml_surat : 0 ?></h3>
                </div>
            </div>
        </div>

        <!-- CARD 3: TOTAL STAFF -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted small mb-1">Total Staff</h6>
                    <h3 class="fw-bold mb-0"><?= isset($jml_staff) ? $jml_staff : 0 ?></h3>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- ROW LIST TERBARU -->
    <div class="row">

        <!-- KOLOM KIRI: PERKARA TERBARU -->
        <div class="col-md-6">
            <h5 class="small fw-bold text-secondary mb-2"><i class="fas fa-folder-open me-1"></i> Perkara Terbaru</h5>
            <ul class="list-group">
                <?php if (!empty($recent_perkara)): ?>
                    <?php foreach ($recent_perkara as $p): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-truncate"><?= $p->JUDUL_PERKARA; ?></span>
                            <small class="text-muted"><?= $p->NO_PERKARA ?? ''; ?></small>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- State kosong -->
                    <li class="list-group-item text-center text-muted small py-3">
                        Belum ada data perkara
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- KOLOM KAN: KEUANGAN TERBARU -->
        <div class="col-md-6">
            <h5 class="small fw-bold text-secondary mb-2"><i class="fas fa-wallet me-1"></i> Keuangan Terbaru</h5>
            <ul class="list-group">
                <?php if (!empty($recent_keuangan)): ?>
                    <?php foreach ($recent_keuangan as $k): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center p-2 small">
                            <div class="text-truncate me-2" style="max-width: 70%;">
                                <!-- Tampilkan keperluan dana, fallback kalo kosong -->
                                <?= !empty($k->KEPERLUAN_DANA_OPS) ? $k->KEPERLUAN_DANA_OPS : '<span class="text-muted italic">Tidak ada keterangan</span>'; ?>
                            </div>
                            
                            <!-- BADGE STATUS VERIFIKASI: Warna dinamis sesuai status -->
                            <?php 
                            $status = $k->STATUS_VERIFIKASI_OPS;
                            if ($status == 'Pending Pimpinan') {
                                echo '<span class="badge bg-warning text-dark px-2 py-1">Pending Pimpinan</span>';
                            } elseif ($status == 'Pending Keuangan') {
                                echo '<span class="badge bg-info text-dark px-2 py-1">Pending Keuangan</span>';
                            } elseif ($status == 'Validasi Selesai') {
                                echo '<span class="badge bg-success px-2 py-1">Selesai</span>';
                            } else {
                                echo '<span class="badge bg-secondary px-2 py-1">'.($status ?? 'Pending').'</span>';
                            }
                            ?>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- State kosong -->
                    <li class="list-group-item text-center text-muted small py-3">
                        Belum ada riwayat keuangan terbaru.
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
