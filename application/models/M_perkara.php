<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ==============================================
 * MODEL: M_perkara 
 * Fungsi: Query database tabel PERKARA + JOIN KEUANGAN
 * Fitur: Filter antrean beda role sesuai workflow 4 tahap
 * Dipake di: Dashboard.php, Perkara.php, Keuangan.php
 * ==============================================
 */
class M_perkara extends CI_Model {

    /**
     * Ambil semua data perkara - UNTUK ADMIN/PIMPINAN
     * Dipake di: Dashboard.php role default
     * 
     * @return array Semua data tabel PERKARA
     */
    public function get_all() {
        return $this->db->get('PERKARA')->result_array();
    }

    /**
     * ANTRIAN ADMIN: Ambil berkas status 'Pending Admin'
     * Logic: Admin cuma liat berkas baru masuk dari klien/staff
     * Dipake di: Dashboard.php case 'Admin' + Keuangan.php verifikasi
     * 
     * @return array Data PERKARA + NO_TRANSAKSI + STATUS_VERIFIKASI_OPS
     */
    public function get_antrean_admin() {
        return $this->db
            ->select('PERKARA.*, KEUANGAN.NO_TRANSAKSI, KEUANGAN.STATUS_VERIFIKASI_OPS')
            ->from('PERKARA')
            ->join('KEUANGAN', 'PERKARA.NO_PERKARA = KEUANGAN.NO_PERKARA')
            ->where('KEUANGAN.STATUS_VERIFIKASI_OPS', 'Pending Admin')
            ->get()
            ->result_array();
    }

    /**
     * ANTRIAN KUASA HUKUM: Ambil berkas status 'Pending Kuasa Hukum'
     * Logic: Setelah admin verif, berkas lempar ke kuasa hukum buat validasi hukum
     * Dipake di: Dashboard.php case 'Kuasa Hukum' + Keuangan.php verifikasi
     * 
     * @return array Data PERKARA + NO_TRANSAKSI + STATUS_VERIFIKASI_OPS
     */
    public function get_antrean_kuasa_hukum() {
        return $this->db
            ->select('PERKARA.*, KEUANGAN.NO_TRANSAKSI, KEUANGAN.STATUS_VERIFIKASI_OPS')
            ->from('PERKARA')
            ->join('KEUANGAN', 'PERKARA.NO_PERKARA = KEUANGAN.NO_PERKARA')
            ->where('KEUANGAN.STATUS_VERIFIKASI_OPS', 'Pending Kuasa Hukum')
            ->get()
            ->result_array();
    }

    /**
     * ANTRIAN KEUANGAN: Ambil berkas status 'Pending Keuangan'
     * Logic: Setelah kuasa hukum validasi, berkas lempar ke keuangan buat terbit invoice
     * Dipake di: Dashboard.php case 'Keuangan' + Keuangan.php verifikasi
     * 
     * @return array Data PERKARA + NO_TRANSAKSI + STATUS_VERIFIKASI_OPS
     */
    public function get_antrean_keuangan() {
        return $this->db
            ->select('PERKARA.*, KEUANGAN.NO_TRANSAKSI, KEUANGAN.STATUS_VERIFIKASI_OPS')
            ->from('PERKARA')
            ->join('KEUANGAN', 'PERKARA.NO_PERKARA = KEUANGAN.NO_PERKARA')
            ->where('KEUANGAN.STATUS_VERIFIKASI_OPS', 'Pending Keuangan')
            ->get()
            ->result_array();
    }
    
    /**
     * Ambil semua perkara milik staff/kuasa hukum yg login
     * Logic: Filter berdasarkan TELP_STAFF + LEFT JOIN biar data perkara ga hilang 
     *        walau status keuangannya udah berubah
     * Dipake di: Dashboard.php case 'Kuasa Hukum' + Perkara.php index
     * 
     * @param string $telp_staff Nomor telp staff dari session
     * @return array Data perkara + info keuangan + status bayar
     */
    public function get_perkara_kuasa_hukum($telp_staff) {
        return $this->db
            ->select('PERKARA.*, KEUANGAN.NO_TRANSAKSI, KEUANGAN.STATUS_VERIFIKASI_OPS, KEUANGAN.STATUS_BAYAR_KLIEN')
            ->from('PERKARA')
            // LEFT JOIN biar perkara tetep muncul walau KEUANGAN belum ada datanya
            ->join('KEUANGAN', 'PERKARA.NO_PERKARA = KEUANGAN.NO_PERKARA', 'left')
            ->where('PERKARA.TELP_STAFF', $telp_staff)
            ->get()
            ->result_array();
    }
}
/* End of file M_perkara.php */
/* Location: ./application/models/M_perkara.php */
