<?php 
/**
 * ==============================================
 * VIEW: Dashboard Keuangan
 * File: keuangan/v_index.php
 * Fungsi: KPI Keuangan + Tabel pengajuan OPS internal
 * Data dari: Dashboard.php case 'keuangan'
 * Variabel: $jml_pending, $jml_disetujui, $pengajuan
 * Query inline: Hitung 'Pending Keuangan' & 'Selesai' langsung
 * ==============================================
 */
?>

<div class="container-fluid px-4">

    <!-- HEADER + TANGGAL DINAMIS -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <h3 class="fw-bold mb-0">Dashboard Keuangan</h3>
        <span class="text-muted small">
            <?= date('d F Y'); ?> <!-- Output: 22 Juni 2026 -->
        </span>
    </div>

    <!-- SEKSI 1: KPI INVOICE KLIEN - ALUR BARU -->
    <h5 class="fw-semibold text-secondary mb-3"><i class="fas fa-file-invoice-dollar me-2"></i>Status Berkas & Invoice Klien</h5>
    <div class="row g-4 mb-5">

        <!-- Card 1: Siap Buat Invoice -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Siap Buat Invoice</div>
                        <!-- Query inline CI3: Hitung KEUANGAN status Pending Keuangan -->
                        <h3 class="fw-bold mb-0 text-warning">
                            <?= $this->db->where('STATUS_VERIFIKASI_OPS', 'Pending Keuangan')->count_all_results('KEUANGAN'); ?>
                        </h3>
                    </div>
                    <div class="rounded-circle border p-3 bg-light">
                       <i class="fas fa-file-invoice text-warning fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Invoice Terbit -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Invoice Terbit</div>
                        <!-- Query inline: Hitung KEUANGAN status Selesai -->
                        <h3 class="fw-bold mb-0 text-success">
                            <?= $this->db->where('STATUS_VERIFIKASI_OPS', 'Selesai')->count_all_results('KEUANGAN'); ?>
                        </h3>
                    </div>
                    <div class="rounded-circle border p-3 bg-light">
                       <i class="fas fa-check-double text-success fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEKSI 2: KPI BIAYA OPERASIONAL INTERNAL - ALUR LAMA -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Ops. Pending Pimpinan</div>
                        <!-- Data dari Controller Dashboard.php -> $data['jml_pending'] -->
                        <h3 class="fw-bold mb-0 text-danger"><?= $jml_pending; ?></h3>
                    </div>
                    <div class="rounded-circle border p-3 bg-light">
                       <i class="fas fa-clock text-danger fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Ops. Dana Disetujui</div>
                        <h3 class="fw-bold mb-0 text-primary"><?= $jml_disetujui; ?></h3>
                    </div>
                    <div class="rounded-circle border p-3 bg-light">
                        <i class="fas fa-wallet text-primary fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL MONITORING BIAYA OPS INTERNAL -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-0 pt-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-exchange-alt me-2 text-primary"></i> Pengajuan Biaya Operasional Internal Terbaru
            </h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No Perkara</th>
                            <th>Keperluan</th>
                            <th>Jumlah Pengajuan</th>
                            <th>Status Pengajuan</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pengajuan)): ?>
                            <?php foreach($pengajuan as $row): ?>
                                
                                <!-- Filter: Tampilkan status yg relevan buat Keuangan -->
                                <?php if(in_array($row->STATUS_VERIFIKASI_OPS, ['Pending Pimpinan', 'Pending Keuangan', 'Validasi Selesai', 'Ditolak'])): ?>
                                    <tr>
                                        <td><span class="fw-semibold"><?= $row->NO_PERKARA; ?></span></td>
                                        <td><small class="text-muted"><?= $row->KEPERLUAN_DANA_OPS ?? '-'; ?></small></td>
                                        
                                        <!-- Format rupiah Indonesia -->
                                        <td>
                                            <span class="text-dark fw-bold">
                                                Rp <?= number_format($row->JMLH_PENGAJUAN_OPS ?? 0, 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Badge status warna beda -->
                                        <td>
                                            <?php if($row->STATUS_VERIFIKASI_OPS == 'Pending Pimpinan'): ?>
                                                <span class="badge bg-warning text-dark px-2 py-1">Menunggu ACC Pimpinan</span>
                                            <?php elseif($row->STATUS_VERIFIKASI_OPS == 'Pending Keuangan'): ?>
                                                <span class="badge bg-info text-dark px-2 py-1">Disetujui (Siap Cair)</span>
                                            <?php elseif($row->STATUS_VERIFIKASI_OPS == 'Validasi Selesai'): ?>
                                                <span class="badge bg-success px-2 py-1">Dana Cair (Selesai)</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger px-2 py-1"><?= $row->STATUS_VERIFIKASI_OPS; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- AKSI: Form input No Nota kalo status Pending Keuangan -->
                                        <td>
                                            <?php if($row->STATUS_VERIFIKASI_OPS == 'Pending Keuangan'): ?>
                                                <!-- Form cairkan dana + input nomor nota -->
                                                <form action="<?= base_url('dashboard/keuangan/cairkan_ops'); ?>" method="POST" class="d-flex gap-1" style="min-width: 240px;">
                                                    <input type="hidden" name="no_transaksi" value="<?= $row->NO_TRANSAKSI; ?>">
                                                    <input type="text" name="no_nota" class="form-control form-control-sm" placeholder="No. Nota Kas" required style="width: 120px;">
                                                    <button type="submit" class="btn btn-sm btn-success text-nowrap py-1">
                                                        <i class="fas fa-check"></i> Cairkan
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Tampilkan nomor nota kalo udah dicairkan -->
                                                <span class="text-muted small italic">
                                                    <?= !empty($row->BUKTI_NOTA_KAS_KELUAR) ? 'Nota: '.$row->BUKTI_NOTA_KAS_KELUAR : '-'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                
                            <?php endforeach; ?> 
                        <?php else: ?>
                            <!-- Empty state -->
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Belum ada data pengajuan operasional internal saat ini.
                                </td>
                            </tr>
                        <?php endif; ?> 
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
