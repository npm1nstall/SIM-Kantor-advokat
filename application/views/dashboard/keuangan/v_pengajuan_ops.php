<?php 
/**
 * ==============================================
 * VIEW: Form Pengajuan Biaya Operasional - Kuasa Hukum
 * File: keuangan/v_pengajuan_ops.php
 * Fungsi: Form ajukan dana OPS + tabel riwayat pengajuan
 * Data dari: Dashboard.php case 'pengajuan_ops' -> $data['perkara_ops']
 * Submit ke: Dashboard.php -> simpan_pengajuan_ops()
 * Alur: Pending Pimpinan → Pending Keuangan → Verified → Approved
 * ==============================================
 */
?>

<div class="card shadow border-0 m-2">
    <!-- Header card biru -->
    <div class="card-header bg-primary text-white py-2">
        <h6 class="m-0"><i class="fas fa-file-invoice-dollar me-2"></i> Form Pengajuan Biaya Operasional Perkara</h6>
    </div>
    
    <div class="card-body p-3">
        
        <!-- ALERT SUCCESS SETELAH SUBMIT -->
        <?php if($this->session->flashdata('pesan_sukses')): ?>
            <div class="alert alert-success py-2 px-3 small mb-3">
                <i class="fas fa-check-circle me-1"></i> <?= $this->session->flashdata('pesan_sukses'); ?>
            </div>
        <?php endif; ?>

        <!-- FORM PENGAJUAN BARU -->
        <form action="<?= base_url('dashboard/keuangan/simpan_pengajuan_ops'); ?>" method="POST">
            <div class="row">
                <!-- KOLOM KIRI: Pilih perkara + nominal -->
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">Pilih Perkara Aktif Anda</label>
                        <select name="no_transaksi" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Nomor Perkara / Kasus --</option>
                            <?php if(!empty($perkara_ops)): ?>
                                <?php foreach($perkara_ops as $p): ?>
                                    <!-- 
                                    FILTER DROPDOWN: 
                                    1. Perkara yg belum pernah ajukan dana = JMLH_PENGAJUAN_OPS kosong
                                    2. Perkara yg ditolak = boleh ajukan ulang
                                    -->
                                    <?php if(empty($p['JMLH_PENGAJUAN_OPS']) || $p['STATUS_VERIFIKASI_OPS'] == 'Ditolak'): ?>
                                        <option value="<?= $p['NO_TRANSAKSI']; ?>">
                                            <?= $p['NO_PERKARA']; ?> - <?= $p['JUDUL_PERKARA']; ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">Jumlah Pengajuan Dana (Rp)</label>
                        <input type="number" name="jmlh_pengajuan" class="form-control form-control-sm" placeholder="Contoh: 500000" min="1" required>
                    </div>
                </div>

                <!-- KOLOM KAN: Keperluan dana -->
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">Keperluan Dana / Deskripsi Penggunaan</label>
                        <textarea name="keperluan_dana" class="form-control form-control-sm" rows="4" placeholder="Contoh: Biaya akomodasi tim ke PN..." required></textarea>
                    </div>
                </div>
            </div>

            <!-- BUTTON SUBMIT -->
            <div class="text-end border-top pt-2 mt-2">
                <button type="submit" class="btn btn-sm btn-success px-4">
                    <i class="fas fa-paper-plane me-1"></i> Kirim Pengajuan ke Pimpinan
                </button>
            </div>
        </form>

        <!-- ================= TABEL RIWAYAT PENGAJUAN ================= -->
        <h6 class="mt-4 mb-2 small fw-bold text-secondary"><i class="fas fa-history me-1"></i> Status Monitor Pengajuan Anda:</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle small bg-white text-center">
                <thead class="table-light">
                    <tr>
                        <th>No Perkara</th>
                        <th>Judul Kasus</th>
                        <th>Nominal Dana</th>
                        <th>Status Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $ada_data = false;
                    if(!empty($perkara_ops)): 
                        foreach($perkara_ops as $p): 
                            // Tampilkan hanya yg sudah pernah ajukan dana
                            if(!empty($p['JMLH_PENGAJUAN_OPS'])): 
                                $ada_data = true;
                    ?>
                            <tr>
                                <td><?= $p['NO_PERKARA']; ?></td>
                                <td class="text-start"><?= $p['JUDUL_PERKARA']; ?></td>
                                
                                <!-- Format rupiah -->
                                <td class="text-success fw-bold">Rp <?= number_format($p['JMLH_PENGAJUAN_OPS'], 0, ',', '.'); ?></td>
                                
                                <!-- Badge status workflow 4 tahap -->
                                <td>
                                    <?php if($p['STATUS_VERIFIKASI_OPS'] == 'Pending Pimpinan'): ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Menunggu ACC Pimpinan</span>
                                    <?php elseif($p['STATUS_VERIFIKASI_OPS'] == 'Pending Keuangan'): ?>
                                        <span class="badge bg-info text-dark"><i class="fas fa-hourglass-half"></i> Disetujui Pimpinan (Proses Kasir)</span>
                                    <?php elseif($p['STATUS_VERIFIKASI_OPS'] == 'Ditolak'): ?>
                                        <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Ditolak</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="fas fa-check-double"></i> Dana Cair (Selesai)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                    <?php 
                            endif;
                        endforeach; 
                    endif; 
                    
                    // Empty state kalo belum ada pengajuan sama sekali
                    if(!$ada_data):
                    ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-2 small">
                                <i class="fas fa-inbox me-1"></i> Belum ada riwayat pengajuan biaya operasional.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
