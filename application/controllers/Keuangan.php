<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ==============================================
 * CONTROLLER: Keuangan 
 * Fungsi: Handle alur operasional + pembayaran + invoice
 * Alur: Klien Upload → Admin Verif → Kuasa Hukum → Keuangan Invoice → Klien Bayar
 * Role: Klien, Admin, Kuasa Hukum, Keuangan
 * ==============================================
 */
class Keuangan extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Cek login: kalo belum login tendang ke auth
        if (!$this->session->userdata('jabatan') && !$this->session->userdata('klien_logged_in')) {
            redirect('auth');
            return;
        }
        
        $this->load->model('M_keuangan');
    }

    /**
     * Helper render template keuangan
     */
    private function _render($view, $data = []) {
        $this->load->view('auth/v_header');
        $this->load->view('v_sidebar', $data);
        $this->load->view($view, $data);
        $this->load->view('auth/v_footer', $data);
    }

    /**
     * Dashboard Keuangan - Staff Keuangan
     * URL: /keuangan
     */
    public function index() {
        $data['title'] = 'Dashboard Keuangan';

        // Hitung jumlah pengajuan per status
        $data['jml_pending']   = $this->M_keuangan->count_pengajuan_by_status('Pending');
        $data['jml_disetujui'] = $this->M_keuangan->count_pengajuan_by_status('Disetujui');
        $data['jml_total']     = $this->db->count_all('KEUANGAN');

        // Ambil 10 pengajuan terbaru
        $data['pengajuan'] = $this->db
            ->order_by('TGL_PENGAJUAN_OPS', 'DESC')
            ->limit(10)
            ->get('KEUANGAN')
            ->result();

        $this->_render('dashboard/keuangan/v_index', $data);
    }

    /**
     * Halaman pembayaran klien
     * Klien bisa liat tagihan + upload bukti bayar
     * URL: /keuangan/pembayaran_klien
     */
    public function pembayaran_klien() {
        $telp = $this->session->userdata('telp_klien');
        if (empty($telp)) { 
            redirect('auth'); 
            return;
        }
        
        // Ambil data pembayaran milik klien ini
        $data['pembayaran'] = $this->M_keuangan->get_data_pembayaran_klien($telp);
        $data['title'] = 'Pembayaran Perkara';
        
        $this->_render('klien/v_pembayaran_klien', $data);
    }

    /**
     * Staff ajukan dana operasional
     * Auto generate NO_TRANSAKSI biar ga bentrok PK
     * URL: /keuangan/proses_pengajuan
     */
    public function proses_pengajuan() {
        // Generate nomor transaksi unik
        $no_transaksi = 'OPS-' . date('Ymd') . '-' . rand(100, 999);

        $data = [
            'NO_TRANSAKSI'          => $no_transaksi, // PK wajib diisi
            'NO_PERKARA'            => $this->input->post('no_perkara'),
            'KEPERLUAN_DANA_OPS'    => $this->input->post('keperluan'),
            'JMLH_PENGAJUAN_OPS'    => $this->input->post('jumlah'),
            'TGL_PENGAJUAN_OPS'     => date('Y-m-d H:i:s'),
            'STATUS_VERIFIKASI_OPS' => 'Pending',
            'TELP_STAFF'            => $this->session->userdata('telp_staff')
        ];
        
        $this->M_keuangan->simpan_pengajuan($data);
        $this->session->set_flashdata('pesan', 'Pengajuan dana berhasil dikirim!');
        redirect('dashboard/keuangan/data_pengajuan'); 
    }
    
    // ================= ALUR OPERASIONAL 4 TAHAP =================
    // KLIEN -> ADMIN -> KUASA HUKUM -> KEUANGAN

    /**
     * TAHAP 1: KLIEN UPLOAD BERKAS
     * Status awal: Pending Admin
     * URL: /keuangan/klien_upload_berkas
     */
    public function klien_upload_berkas() {
        $no_perkara = $this->input->post('no_perkara');
        // Generate NO_TRANSAKSI unik karena PK varchar
        $no_transaksi = 'TRX-' . date('YmdHis') . '-' . rand(100, 999);
        
        $data = [
            'NO_TRANSAKSI'          => $no_transaksi,
            'NO_PERKARA'            => $no_perkara,
            'STATUS_VERIFIKASI_OPS' => 'Pending Admin', 
            'STATUS_BAYAR_KLIEN'    => 'Belum Bayar'
        ];
        
        $this->db->insert('KEUANGAN', $data);
        $this->session->set_flashdata('pesan', 'Berkas berhasil diunggah, menunggu verifikasi Admin.');
        redirect('dashboard');
    }

    /**
     * TAHAP 2: ADMIN VALIDASI BERKAS
     * Status: Pending Admin → Pending Kuasa Hukum
     * URL: /keuangan/admin_setujui_berkas?id=TRX-xxx
     */
    public function admin_setujui_berkas() {
        // Cek akses admin only
        if ($this->session->userdata('jabatan') != 'Admin') {
            redirect('dashboard');
            return;
        }

        // Ambil NO_TRANSAKSI dari URL ?id=
        $no_transaksi = $this->input->get('id');

        if (empty($no_transaksi)) {
            $this->session->set_flashdata('pesan_error', 'Gagal memproses: ID Transaksi tidak terkirim.');
            redirect('dashboard/keuangan/verifikasi');
            return;
        }

        $id = urldecode($no_transaksi);

        // Update status ke tahap selanjutnya
        $this->db->where('NO_TRANSAKSI', $id);
        $this->db->update('KEUANGAN', ['STATUS_VERIFIKASI_OPS' => 'Pending Kuasa Hukum']);

        $this->session->set_flashdata('pesan', 'Berkas diverifikasi dan diteruskan ke Kuasa Hukum.');
        redirect('dashboard/keuangan/verifikasi'); 
    }

    /**
     * TAHAP 3: KUASA HUKUM VALIDASI BERKAS
     * Status: Pending Kuasa Hukum → Pending Keuangan
     * URL: /keuangan/kuasahukum_setujui_berkas?id=TRX-xxx
     */
    public function kuasahukum_setujui_berkas() {
        // Cek akses kuasa hukum only
        if ($this->session->userdata('jabatan') != 'Kuasa Hukum') {
            redirect('dashboard');
            return;
        }

        $no_transaksi = $this->input->get('id');

        if (empty($no_transaksi)) {
            $this->session->set_flashdata('pesan_error', 'Gagal memproses: ID Transaksi tidak terkirim.');
            redirect('dashboard/keuangan/verifikasi');
            return;
        }

        $id = urldecode($no_transaksi);

        // Update status ke keuangan
        $this->db->where('NO_TRANSAKSI', $id);
        $this->db->update('KEUANGAN', ['STATUS_VERIFIKASI_OPS' => 'Pending Keuangan']);

        $this->session->set_flashdata('pesan', 'Berkas divalidasi hukum dan diteruskan ke Keuangan.');
        redirect('dashboard/keuangan/verifikasi');
    }

    /**
     * TAHAP 4: KEUANGAN TERBITKAN INVOICE
     * Status: Pending Keuangan → Selesai, Belum Bayar
     * URL: /keuangan/simpan_tagihan_final/TRX-xxx
     */
    public function simpan_tagihan_final($no_transaksi) {
        // Cek akses keuangan only
        if ($this->session->userdata('jabatan') != 'Keuangan') {
            redirect('dashboard');
            return;
        }

        $id = urldecode($no_transaksi);
        $data_update = [
            'NO_INVOICE'            => $this->input->post('no_invoice'),
            'TTL_TAGIHAN_KLIEN'     => $this->input->post('ttl_tagihan'),
            'STATUS_VERIFIKASI_OPS' => 'Selesai', 
            'STATUS_BAYAR_KLIEN'    => 'Belum Bayar' 
        ];

        $this->db->where('NO_TRANSAKSI', $id); // PK = NO_TRANSAKSI
        $this->db->update('KEUANGAN', $data_update);

        $this->session->set_flashdata('pesan', 'Tagihan Invoice berhasil diterbitkan. Klien menerima notifikasi.');
        redirect('dashboard/keuangan/pembayaran');
    }
    
    /**
     * KLIEN UPLOAD BUKTI TRANSFER
     * Status bayar: Belum Bayar → Menunggu Verifikasi
     * Upload ke folder /uploads/pembayaran/
     * URL: /keuangan/klien_kirim_bukti
     */
    public function klien_kirim_bukti() {
        // Ambil ID dari POST atau GET biar fleksibel
        $no_transaksi = $this->input->post('no_transaksi') ?? $this->input->get('id');

        if (empty($no_transaksi)) {
            $this->session->set_flashdata('pesan_error', 'Gagal memproses: ID Transaksi tidak ditemukan.');
            redirect('keuangan/pembayaran_klien');
            return;
        }

        // Konfigurasi upload file
        $config['upload_path']   = FCPATH . 'uploads/pembayaran/';
        $config['allowed_types'] = 'pdf|jpg|jpeg|png';
        $config['max_size']      = 2048; // 2MB
        $config['encrypt_name']  = TRUE; // Nama file diacak biar aman

        // Bikin folder kalo belum ada
        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, TRUE);
        }

        $this->load->library('upload', $config);

        // Proses upload
        if (!$this->upload->do_upload('bukti_bayar')) {
            $this->session->set_flashdata('pesan_error', $this->upload->display_errors());
            redirect('keuangan/pembayaran_klien');
        } else {
            $file_data = $this->upload->data();

            // Update DB: simpan nama file + ubah status
            $data_update = [
                'BUKTI_BAYAR_KLIEN'  => $file_data['file_name'],
                'STATUS_BAYAR_KLIEN' => 'Menunggu Verifikasi' 
            ];

            $this->db->where('NO_TRANSAKSI', urldecode($no_transaksi));
            $this->db->update('KEUANGAN', $data_update);

            $this->session->set_flashdata('pesan', 'Bukti transfer berhasil dikirim! Menunggu verifikasi dari bagian Keuangan.');
            redirect('keuangan/pembayaran_klien');
        }
    }
}
/* End of file Keuangan.php */
/* Location: ./application/controllers/Keuangan.php */
