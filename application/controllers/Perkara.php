<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ==============================================
 * CONTROLLER: Perkara 
 * Fungsi: CRUD Data Perkara + Upload Berkas + Cetak PDF + Jadwal Sidang
 * Fitur: Filter klien, Hide "Pendaftaran", Upload, Timezone Asia/Jakarta
 * Role: Klien, Admin, Kuasa Hukum, Keuangan
 * ==============================================
 */
class Perkara extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Cek login: Staff atau Klien
        if (!$this->session->userdata('jabatan') && !$this->session->userdata('klien_logged_in')) {
            redirect('auth');
            return;
        }
        
        $this->load->model('M_perkara'); 
    }

    /**
     * Helper render template perkara
     * $data diterusin ke sidebar biar notif bayar kebaca
     */
    private function _render($view, $data = []) {
        $this->load->view('auth/v_header');
        $this->load->view('v_sidebar', $data);
        $this->load->view($view, $data); 
        $this->load->view('auth/v_footer', $data); 
    }

    /**
     * Data Perkara Utama
     * Logic: Klien cuma liat perkara dia + hide "Pendaftaran"
     * Staff liat sesuai role masing2
     * URL: /perkara
     */
    public function index() {
        $data['title'] = 'Data Perkara';
        $jabatan = $this->session->userdata('jabatan');

        // === FILTERING KHUS KLIEN ===
        if ($this->session->userdata('klien_logged_in')) {
            $telp = $this->session->userdata('telp_klien');
            
            // Hitung total perkara asli milik klien, bukan "Pendaftaran Akun"
            $this->db->where('TELP_KLIEN', $telp);
            $this->db->not_like('JUDUL_PERKARA', 'Pendaftaran');
            $jumlah_perkara_asli = $this->db->count_all_results('PERKARA');

            // Ambil data perkara klien ini
            $this->db->where('TELP_KLIEN', $telp);
            $this->db->order_by('TGL_MASUK', 'DESC');
            
            // Kalo udah punya perkara asli, hide semua yg judulnya "Pendaftaran"
            if ($jumlah_perkara_asli > 0) {
                $this->db->not_like('JUDUL_PERKARA', 'Pendaftaran');
            }

            $data['perkara'] = $this->db->get('PERKARA')->result_array();

            // Render khusus klien, stop biar ga kebaca switch bawah
            $this->_render('perkara/v_daftar', $data);
            return;
        }

        // === FILTERING STAFF INTERNAL ===
        if ($jabatan == 'Admin') {
            // Admin liat antrean yg pending admin
            $data['perkara'] = $this->M_perkara->get_antrean_admin();
        } 
        else if ($jabatan == 'Kuasa Hukum') {
            // Kuasa hukum liat perkara yg ditugasin ke dia
            $telp_staff = $this->session->userdata('telp'); 
            $data['perkara'] = $this->M_perkara->get_perkara_kuasa_hukum($telp_staff);
        } 
        else if ($jabatan == 'Keuangan') {
            // Keuangan liat perkara yg pending keuangan
            $data['perkara'] = $this->M_perkara->get_antrean_keuangan();
        } 
        else {
            // Role lain liat semua
            $data['perkara'] = $this->M_perkara->get_all();
        }

        $this->_render('perkara/v_daftar', $data);
    }

    /**
     * Simpan perkara baru - KLIEN
     * Upload berkas + auto ambil alamat dari data pendaftaran pertama
     * URL: /perkara/simpan
     */
    public function simpan() {
        // Config upload file
        $config['upload_path']   = FCPATH . 'uploads/perkara/';
        $config['allowed_types'] = 'pdf|jpg|jpeg|png|doc|docx';
        $config['max_size']      = 2048; // 2MB

        $this->load->library('upload', $config);

        // Kalo upload gagal
        if (!$this->upload->do_upload('berkas_perkara')) {
            echo $this->upload->display_errors();
            die();
        } else {
            $file_data = $this->upload->data();
            $no_perkara = $this->input->post('no_perkara');
            $telp_klien = $this->session->userdata('telp_klien');

            // Ambil alamat asli dari data "Pendaftaran Akun Baru" pertama kali
            $data_pendaftaran = $this->db->get_where('PERKARA', [
                'TELP_KLIEN' => $telp_klien,
                'JUDUL_PERKARA' => 'Pendaftaran Akun Baru'
            ])->row_array();

            // Kalo ga ketemu, pake input form atau kasih default "-"
            $alamat_klien = $data_pendaftaran['ALAMAT_KLIEN'] ?? ($this->input->post('alamat_klien') ?? '-');

            // Simpan data perkara
            $data_perkara = [
                'NO_PERKARA'     => $no_perkara, 
                'JUDUL_PERKARA'  => $this->input->post('judul'),
                'NAMA_KLIEN'     => $this->session->userdata('nama_klien') ?? $this->input->post('nama_klien'),
                'TELP_KLIEN'     => $telp_klien,
                'ALAMAT_KLIEN'   => $alamat_klien, // Auto isi alamat
                'BERKAS_PERKARA' => $file_data['file_name'],
                'TGL_MASUK'      => date('Y-m-d H:i:s'),
                'STATUS_PERKARA' => 'Baru'
            ];
            $this->db->insert('PERKARA', $data_perkara);

            // Auto bikin data alur keuangan biar masuk meja admin
            $data_alur_ops = [
                'NO_TRANSAKSI'          => 'TRX-' . date('YmdHis') . '-' . rand(100, 999),
                'NO_PERKARA'            => $no_perkara,
                'STATUS_VERIFIKASI_OPS' => 'Pending Admin',
                'STATUS_BAYAR_KLIEN'    => 'Belum Bayar'
            ];
            $this->db->insert('KEUANGAN', $data_alur_ops);

            $this->session->set_flashdata('pesan', 'Data perkara berhasil disimpan & berkas dikirim ke Admin!');
            redirect('perkara');
        }
    }

    /**
     * Update biodata perkara - Admin
     * URL: /perkara/update_biodata
     */
    public function update_biodata() {
        $no_perkara = $this->input->post('no_perkara');

        $data_update = [
            'JUDUL_PERKARA' => $this->input->post('judul_perkara'),
            'NAMA_KLIEN'    => $this->input->post('nama_klien'),
            'TELP_KLIEN'    => $this->input->post('telp_klien'),
            'ALAMAT_KLIEN'  => $this->input->post('alamat_klien')
        ];

        $this->db->where('NO_PERKARA', $no_perkara);
        $this->db->update('PERKARA', $data_update);

        $this->session->set_flashdata('sukses', 'Biodata perkara berhasil diubah!');
        redirect('perkara');
    }

    /**
     * Hapus perkara + relasi keuangan
     * Hapus keuangan dulu biar ga error foreign key
     * URL: /perkara/hapus/NO-PERKARA-001
     */
    public function hapus($no_perkara) {
        $id = urldecode($no_perkara);
        
        // Hapus relasi di tabel keuangan dulu
        $this->db->where('NO_PERKARA', $id);
        $this->db->delete('KEUANGAN');

        // Baru hapus perkara
        $this->db->where('NO_PERKARA', $id);
        $this->db->delete('PERKARA'); 
        
        $this->session->set_flashdata('pesan', 'Data berhasil dihapus!');
        redirect('perkara');
    }
    
    /**
     * Cetak PDF Laporan Perkara
     * Pake library TCPDF
     * URL: /perkara/cetak/NO-PERKARA-001
     */
    public function cetak($no_perkara) {
        $this->load->library('pdf');
        $id = urldecode($no_perkara);
        $data['p'] = $this->db->get_where('PERKARA', ['NO_PERKARA' => $id])->row_array();
        
        if (!$data['p']) {
            $this->session->set_flashdata('pesan', 'Data tidak ditemukan!');
            redirect('perkara');
            return;
        }

        $pdf = new Pdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetTitle('Laporan Perkara - ' . $id);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        
        // Template HTML buat PDF
        $html = '
        <h2 style="text-align:center;">LAPORAN DATA PERKARA</h2>
        <table border="1" cellpadding="5">
            <tr><th width="30%">No Perkara</th><td>'.$data['p']['NO_PERKARA'].'</td></tr>
            <tr><th>Judul Perkara</th><td>'.$data['p']['JUDUL_PERKARA'].'</td></tr>
            <tr><th>Tanggal Masuk</th><td>'.date('d-m-Y H:i', strtotime($data['p']['TGL_MASUK'])).'</td></tr>
            <tr><th>Berkas Perkara</th><td>'.($data['p']['BERKAS_PERKARA'] ?? '-').'</td></tr>
            <tr><th>Status Perkara</th><td>'.($data['p']['STATUS_PERKARA'] ?? '-').'</td></tr>
            <tr><th>Nama Klien</th><td>'.($data['p']['NAMA_KLIEN']).'</td></tr>
            <tr><th>Telp Klien</th><td>'.$data['p']['TELP_KLIEN'].'</td></tr>
            <tr><th>Alamat Klien</th><td>'.$data['p']['ALAMAT_KLIEN'].'</td></tr>
            <tr><th>Agenda Sidang</th><td>'.($data['p']['AGENDA_SIDANG'] ?? '-').'</td></tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Perkara_'.$id.'.pdf', 'I');
    }
    
    /**
     * Jadwal Sidang - KLIEN ONLY
     * Logic sama kayak index: hide "Pendaftaran" kalo udah punya perkara asli
     * URL: /perkara/jadwal_sidang
     */
    public function jadwal_sidang() {
        if ($this->session->userdata('klien_logged_in')) {
            $telp = $this->session->userdata('telp_klien'); 
            
            // Hitung perkara asli dulu
            $this->db->where('TELP_KLIEN', $telp);
            $this->db->not_like('JUDUL_PERKARA', 'Pendaftaran');
            $jumlah_perkara_asli = $this->db->count_all_results('PERKARA');

            // Ambil data buat tabel jadwal sidang
            $this->db->where('TELP_KLIEN', $telp);
            $this->db->order_by('TGL_MASUK', 'DESC');
            
            if ($jumlah_perkara_asli > 0) {
                $this->db->not_like('JUDUL_PERKARA', 'Pendaftaran');
            }

            $data['perkara'] = $this->db->get('PERKARA')->result_array();
            $data['title'] = 'Jadwal Sidang';
            
            $this->_render('perkara/v_daftar', $data);
        } else {
            redirect('auth');
        }
    }
    
    /**
     * Form proses sidang - Admin/Kuasa Hukum
     * URL: /perkara/proses_sidang/NO-PERKARA-001
     */
    public function proses_sidang($no_perkara) {
        $data['title'] = 'Proses Data Persidangan';
        $data['perkara'] = $this->db->get_where('PERKARA', ['NO_PERKARA' => urldecode($no_perkara)])->row_array();
        $this->_render('perkara/v_proses_sidang', $data);
    }

    /**
     * Simpan hasil proses sidang
     * Trik: ganti 'T' jadi spasi biar format DATETIME MySQL bener
     * URL: /perkara/simpan_proses_sidang
     */
    public function simpan_proses_sidang() {
        $no_perkara = $this->input->post('no_perkara');

        $tgl_penugasan_raw = $this->input->post('tgl_penugasan');
        $tgl_sidang_raw    = $this->input->post('tgl_sidang');

        // Trik emas: ganti T jadi spasi biar MySQL DATETIME nerima
        $tgl_penugasan = !empty($tgl_penugasan_raw) ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $tgl_penugasan_raw))) : NULL;
        $tgl_sidang    = !empty($tgl_sidang_raw) ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $tgl_sidang_raw))) : NULL;

        // Ambil file lama kalo ga upload baru
        $perkara_lama = $this->db->get_where('PERKARA', ['NO_PERKARA' => $no_perkara])->row_array();
        $nama_file = $perkara_lama['BERKAS_PERKARA']; 

        // Upload berkas baru kalo ada
        if (!empty($_FILES['berkas_baru']['name'])) {
            $config['upload_path']   = FCPATH . 'uploads/perkara/';
            $config['allowed_types'] = 'pdf|jpg|jpeg|png|doc|docx';
            $config['max_size']      = 5120; // 5MB
            $config['encrypt_name']  = TRUE;
            $this->load->library('upload', $config);
            if ($this->upload->do_upload('berkas_baru')) {
                $upload_data = $this->upload->data();
                $nama_file = $upload_data['file_name'];
            }
        }

        // Update database
        $data_update = [
            'BERKAS_PERKARA'    => $nama_file,
            'TGL_PENUGASAN_TIM' => $tgl_penugasan, 
            'TGL_SIDANG'        => $tgl_sidang,    
            'CATATAN_DISPOSISI' => $this->input->post('catatan_disposisi'),
            'AGENDA_SIDANG'     => $this->input->post('agenda_sidang'),
            'HASIL_SIDANG'      => $this->input->post('hasil_sidang')
        ];

        $this->db->where('NO_PERKARA', $no_perkara);
        $this->db->update('PERKARA', $data_update);

        redirect('perkara');
    }

    /**
     * Tambah perkara internal - STAFF
     * Staff daftarin perkara klien dari backend
     * URL: /perkara/tambah_perkara_internal
     */
    public function tambah_perkara_internal() {
        $config['upload_path']   = FCPATH . 'uploads/perkara/';
        $config['allowed_types'] = 'pdf|jpg|jpeg|png|doc|docx';
        $config['max_size']      = 2048;
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('berkas_perkara')) {
            echo $this->upload->display_errors();
            die();
        } else {
            // Kunci timezone biar jam masuk akurat
            date_default_timezone_set('Asia/Jakarta');

            $file_data = $this->upload->data();
            $no_perkara = $this->input->post('no_perkara');
            $telp_staff = $this->session->userdata('telp');

            $data_perkara = [
                'NO_PERKARA'     => $no_perkara,
                'TELP_STAFF'     => $telp_staff,
                'JUDUL_PERKARA'  => $this->input->post('judul'),
                'TGL_MASUK'      => date('Y-m-d H:i:s'), // Real-time jam klik daftar
                'BERKAS_PERKARA' => $file_data['file_name'],
                'STATUS_PERKARA' => 'Baru',
                'NAMA_KLIEN'     => $this->input->post('nama_klien'),
                'TELP_KLIEN'     => $this->input->post('telp_klien'),
                'ALAMAT_KLIEN'   => $this->input->post('alamat_klien')
            ];
            $this->db->insert('PERKARA', $data_perkara);

            // Auto bikin data keuangan
            $data_alur_keuangan = [
                'NO_TRANSAKSI'          => 'TRX-' . date('YmdHis') . '-' . rand(100, 999),
                'NO_PERKARA'            => $no_perkara,
                'STATUS_VERIFIKASI_OPS' => 'Pending Admin',
                'STATUS_BAYAR_KLIEN'    => 'Belum Bayar'
            ];
            $this->db->insert('KEUANGAN', $data_alur_keuangan);

            $this->session->set_flashdata('pesan', 'Perkara baru berhasil didaftarkan!');
            redirect('perkara');
        }
    }
}
/* End of file Perkara.php */
/* Location: ./application/controllers/Perkara.php */
