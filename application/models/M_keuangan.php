<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ==============================================
 * MODEL: M_keuangan 
 * Fungsi: Query database tabel KEUANGAN + JOIN PERKARA
 * Dipake di: Keuangan.php, Dashboard.php, Perkara.php
 * ==============================================
 */
class M_keuangan extends CI_Model {

    /**
     * Hitung jumlah pengajuan berdasarkan status
     * Dipake di: Dashboard Keuangan buat card summary
     * Contoh: count_pengajuan_by_status('Pending Keuangan')
     * 
     * @param string $status Status_verifikasi_ops yg mau dihitung
     * @return int Jumlah data
     */
    public function count_pengajuan_by_status($status) {
        return $this->db
                    ->where('STATUS_VERIFIKASI_OPS', $status)
                    ->count_all_results('KEUANGAN');
    }

    /**
     * Hitung notifikasi tagihan belum bayar - KHUS KLIEN
     * Logic: Cuma muncul kalo STATUS_VERIFIKASI_OPS = 'Selesai' 
     *        + STATUS_BAYAR_KLIEN = 'Belum Bayar'
     * Dipake di: Dashboard.php index klien buat badge notif
     * 
     * @param string $telp Nomor telp klien dari session
     * @return int Jumlah tagihan yg harus dibayar
     */
    public function count_tagihan_belum_bayar($telp) {
        return $this->db
            ->from('KEUANGAN')
            ->join('PERKARA', 'KEUANGAN.NO_PERKARA = PERKARA.NO_PERKARA')
            ->where('PERKARA.TELP_KLIEN', $telp)
            ->where('KEUANGAN.STATUS_VERIFIKASI_OPS', 'Selesai') // Kunci: nunggu invoice terbit dulu
            ->where('KEUANGAN.STATUS_BAYAR_KLIEN', 'Belum Bayar')
            ->count_all_results();
    }

    /**
     * Simpan data pengajuan baru ke tabel KEUANGAN
     * Dipake di: Keuangan.php proses_pengajuan & klien_upload_berkas
     * 
     * @param array $data Data array NO_TRANSAKSI, NO_PERKARA, status, dll
     * @return bool True kalo berhasil insert
     */
    public function simpan_pengajuan($data) {
        return $this->db->insert('KEUANGAN', $data);
    }

    /**
     * Ambil detail data pembayaran klien + join judul perkara
     * Logic: Cuma ambil yg STATUS_VERIFIKASI_OPS = 'Selesai' 
     *        = yg udah diterbitin invoice sama keuangan
     * Dipake di: Keuangan.php pembayaran_klien
     * 
     * @param string $telp Nomor telp klien dari session
     * @return array Object hasil query
     */
    public function get_data_pembayaran_klien($telp) {
        return $this->db
            ->select('KEUANGAN.*, PERKARA.JUDUL_PERKARA, PERKARA.STATUS_PERKARA')
            ->from('KEUANGAN')
            ->join('PERKARA', 'KEUANGAN.NO_PERKARA = PERKARA.NO_PERKARA')
            ->where('PERKARA.TELP_KLIEN', $telp)
            ->where('KEUANGAN.STATUS_VERIFIKASI_OPS', 'Selesai') // Filter: berkas ber-invoice doang
            ->get()
            ->result();
    }
}
/* End of file M_keuangan.php */
/* Location: ./application/models/M_keuangan.php */
