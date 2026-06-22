<?php 
/**
 * ==============================================
 * VIEW: Dashboard Klien
 * File: klien/klien_index.php
 * Fungsi: Halaman utama klien setelah login
 * Data: $perkara, $notif_bayar
 * Controller: Dashboard.php -> case 'klien_index'
 * Fitur: Greeting, notif tagihan, detail perkara, status bayar
 * ==============================================
 */
?>

<!-- GREETING + NAMA KLIEN DINAMIS -->
<div class="d-flex justify-content-between align-items-center mb-4 ms-3 mt-2">
    <div>
        <h4 class="fw-bold mb-1">
            Selamat Datang,
            <!-- htmlspecialchars = cegah XSS kalo nama klien ada script -->
            <?= htmlspecialchars($this->session->userdata('nama_klien')); ?>
        </h4>
    </div>
</div>

<!-- NOTIF ALERT TAGIHAN: Muncul kalo ada tagihan belum bayar -->
<?php if (!empty($notif_bayar) && $notif_bayar > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Perhatian!</strong> Anda memiliki <?= $notif_bayar ?> tagihan yang belum diselesaikan. 
        <a href="<?= base_url('keuangan/pembayaran_klien') ?>" class="alert-link">Klik di sini untuk membayar</a>.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ROW 2 KOLOM: DETAIL PERKARA + STATUS BAYAR -->
<div class="row">
    <!-- KOLOM KIRI: DETAIL PERKARA -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3 text-success"><i class="fas fa-balance-scale me-2"></i> Detail Perkara</h5>
            <div class="row">
                <!-- NO PERKARA -->
                <div class="col-md-6 mb-3">
                    <p class="mb-1 text-muted">No. Perkara</p>
                    <h6 class="fw-bold"><?= $perkara['NO_PERKARA'] ?? 'Data belum tersedia'; ?></h6>
                </div>
                
                <!-- AGENDA SIDANG -->
                <div class="col-md-6 mb-3">
                    <p class="mb-1 text-muted">Agenda Sidang Berikutnya</p>
                    <h6 class="fw-bold text-primary"><?= $perkara['AGENDA_SIDANG'] ?? 'Tidak ada agenda'; ?></h6>
                </div>
            </div>
            
            <!-- PROGRESS BAR STATUS PERKARA -->
            <div class="mt-2">
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-bold text-muted">Progres Status Perkara</span>
                    <span class="text-success fw-bold"><?= $perkara['STATUS_PERKARA'] ?? '-'; ?></span>
                </div>
                <div class="progress" style="height: 12px; border-radius: 6px;">
                    <!-- Width 70% bisa diganti dinamis nanti pake logic status -->
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: 70%">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: CARD STATUS PEMBAYARAN -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-4 bg-primary text-white h-100">
            <h5 class="fw-bold mb-3"><i class="fas fa-wallet me-2"></i> Status Pembayaran</h5>
            <div class="text-center py-3">
                <!-- Status bayar: Lunas / Belum Bayar / Menunggu Verifikasi -->
                <h3 class="mb-0"><?= $perkara['STATUS_BAYAR_KLIEN'] ?? 'Belum ada data'; ?></h3>
                <p class="opacity-75">Lihat detail di menu Keuangan</p>
            </div>
            <!-- Tombol ke halaman upload bukti bayar -->
            <a href="<?= base_url('dashboard/keuangan_klien'); ?>" class="btn btn-light w-100 fw-bold mt-2">
                Cek Detail
            </a>
        </div>
    </div>
</div>

<!-- CARD CATATAN TERBARU DARI ADVOKAT -->
<div class="card border-0 shadow-sm p-4 mt-2">
    <h5 class="fw-bold mb-3"><i class="fas fa-history me-2"></i> Catatan Terbaru</h5>
    <p class="text-muted italic">
        "<?= $perkara['CATATAN_DISPOSISI'] ?? 'Tidak ada catatan terbaru dari tim advokat.'; ?>"
    </p>
</div>
