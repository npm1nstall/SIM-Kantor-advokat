<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Perkara extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // GATEKEEPER: Cek login dulu. Staff pake 'jabatan', Klien pake 'klien_logged_in'
        if (!$this->session->userdata('jabatan') && !$this->session->userdata('klien_logged_in')) {
            redirect('auth');
        }
        $this->load->model('M_perkara'); 
    }

    // HELPER: Render view + header + sidebar + footer biar rapi
    private function _render($view, $data = []) {
        $this->load->view('auth/v_header');
        $this->load->view('v_sidebar', $data); // $data dilempar biar notif sidebar kebaca
        $this->load->view($view, $data); 
        $this->load->view('auth/v_footer', $data); 
    }

    // HALAMAN UTAMA: List semua perkara sesuai role
    public function index()
    {
        $data['title'] = 'Data Perkara';
        $jabatan = $this->session->userdata('jabatan');

        // FILTER KHUS KLIEN: Hanya lihat perkara milik dia + hide "Pendaftaran Akun Baru" kalo udah punya kasus asli
        if ($this->session->userdata('klien_logged_in')) {
            $telp = $this->session->userdata('telp_klien');
            
            // 1. Cek dulu klien ini udah punya perkara beneran apa belum
            $this->db->where('TELP_KLIEN', $telp);
            $this->db->not_like('JUDUL_PERKARA', 'Pendaftaran');
            $jumlah_perkara_asli = $this->db->count_all_results('PERKARA');

            // 2. Ambil data perkara milik klien ini
            $this->db->where('TELP_KLIEN', $telp);
            $this->db->order_by('TGL_MASUK', 'DESC');
            
            // LOGIKA HIDDEN: Kalo udah ada kasus asli, buang semua judul "Pendaftaran"
            if ($jumlah_perkara_asli > 0) {
                $this->db->not_like('JUDUL_PERKARA', 'Pendaftaran');
            }

            $data['perkara'] = $this->db->get('PERKARA')->result_array();

            // RETURN DINI: Klien langsung render, biar ga kebaca logika staff di bawah
            $this->_render('perkara/v_daftar', $data);
            return;
        }

        // LOGIKA UNTUK STAFF INTERNAL
        if ($jabatan == 'Admin') {
            $data['perkara'] = $this->M_perkara->get_antrean_admin(); // Admin liat semua antrian
        } 
        else if ($jabatan == 'Kuasa Hukum') {
            $telp_staff = $this->session->userdata('telp'); 
            $data['perkara'] = $this->M_perkara->get_perkara_kuasa_hukum($telp_staff); // KH liat kasus yg ditugasin ke dia
        } 
        else if ($jabatan == 'Keuangan') {
            $data['perkara'] = $this->M_perkara->get_antrean_keuangan(); // Keuangan liat yg pending bayar
        } 
        else {
            $data['perkara'] = $this->M_perkara->get_all(); // Default: ambil semua
        }

        $this->_render('perkara/v_daftar', $data);
    }

    // SIMPAN PERKARA BARU DARI FORM KLIEN
    public function simpan()
    {
        $config['upload_path']   = FCPATH . 'uploads/perkara/';
        $config['allowed_types'] = 'pdf|jpg|jpeg|png|doc|docx';
        $config['max_size']      = 2048;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('berkas_perkara')) {
            echo $this->upload->display_errors();
            die();
        } else {
            $file_data = $this->upload->data();
			 // 1. NO PERKARA OTOMATIS - backup kalo post kosong
			$no_perkara = $this->input->post('no_perkara');
			if(empty($no_perkara)) {
				$no_perkara = 'PRK-' . date('Ymd') . '-' . rand(100,999);
			}

			// 2. Ambil data klien dari session
			$telp_klien = $this->session->userdata('telp_klien');
			$nama_klien = $this->session->userdata('nama_klien');

			// 3. Ambil alamat dari data pendaftaran pertama
			$data_pendaftaran = $this->db->get_where('PERKARA', [
				'TELP_KLIEN' => $telp_klien,
				'JUDUL_PERKARA' => 'Pendaftaran Akun Baru'
			])->row_array();
			$alamat_klien = $data_pendaftaran['ALAMAT_KLIEN'] ?? '-';

			$data_perkara = [
				'NO_PERKARA'     => $no_perkara, // sekarang pasti keisi
				'JUDUL_PERKARA'  => $this->input->post('judul'),
				'NAMA_KLIEN'     => $nama_klien,
				'TELP_KLIEN'     => $telp_klien,
				'ALAMAT_KLIEN'   => $alamat_klien,
				'BERKAS_PERKARA' => $file_data['file_name'],
				'TGL_MASUK'      => date('Y-m-d H:i:s'),
				'STATUS_PERKARA' => 'Baru'
			];
            $this->db->insert('PERKARA', $data_perkara);

            // BIKIN TRACKING KEUANGAN: Buat alur ops biar Admin bisa verifikasi
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

    // UPDATE BIODATA KLIEN: Dipake pas edit nama/telp/alamat doang
    public function update_biodata()
    {
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

    // HAPUS PERKARA: Hapus dari KEUANGAN dulu biar ga kena foreign key
    public function hapus($no_perkara) 
    {
        $id = urldecode($no_perkara);
        
        $this->db->where('NO_PERKARA', $id);
        $this->db->delete('KEUANGAN');

        $this->db->where('NO_PERKARA', $id);
        $this->db->delete('PERKARA'); 
        
        $this->session->set_flashdata('pesan', 'Data berhasil dihapus!');
        redirect('perkara');
    }
    
    // CETAK PDF: Generate laporan perkara ke PDF pake library TCPDF
    public function cetak($no_perkara) {
        $this->load->library('pdf');
        $id = urldecode($no_perkara);
        $data['p'] = $this->db->get_where('PERKARA', ['NO_PERKARA' => $id])->row_array();
        
        if (!$data['p']) {
            $this->session->set_flashdata('pesan', 'Data tidak ditemukan!');
            redirect('perkara');
        }

        $pdf = new Pdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetTitle('Laporan Perkara - ' . $id);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        
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
    
    // HALAMAN JADWAL SIDANG KHUS KLIEN: Filter + kasih penanda status_halaman
    public function jadwal_sidang() 
    {
        if ($this->session->userdata('klien_logged_in')) {
            $telp = $this->session->userdata('telp_klien'); 
            
            $this->db->where('TELP_KLIEN', $telp);
            $this->db->not_like('JUDUL_PERKARA', 'Pendaftaran');
            $jumlah_perkara_asli = $this->db->count_all_results('PERKARA');

            $this->db->where('TELP_KLIEN', $telp);
            $this->db->order_by('TGL_MASUK', 'DESC');
            
            if ($jumlah_perkara_asli > 0) {
                $this->db->not_like('JUDUL_PERKARA', 'Pendaftaran');
            }

            $data['perkara'] = $this->db->get('PERKARA')->result_array();
            $data['title'] = 'Jadwal Sidang';
            $data['status_halaman'] = 'jadwal_sidang'; // PENANDA: buat hide card "Belum Dijadwalkan" di view
            
            $this->_render('perkara/v_daftar', $data);
        } else {
            redirect('auth');
        }
    }

    // FORM PROSES SIDANG: Halaman khusus Admin/KH buat input hasil sidang
    public function proses_sidang($no_perkara)
    {
        $data['title'] = 'Proses Data Persidangan';
        $data['perkara'] = $this->db->get_where('PERKARA', ['NO_PERKARA' => urldecode($no_perkara)])->row_array();
        $this->_render('perkara/v_proses_sidang', $data);
    }

    // SIMPAN HASIL PROSES SIDANG: Update tgl sidang, agenda, disposisi, hasil, + upload berkas baru
    public function simpan_proses_sidang()
    {
        $no_perkara = $this->input->post('no_perkara');

        $tgl_penugasan_raw = $this->input->post('tgl_penugasan');
        $tgl_sidang_raw    = $this->input->post('tgl_sidang');

        // TRIK: datetime-local format "2026-06-22T08:00" harus ganti T jadi spasi biar MySQL mau
        $tgl_penugasan = !empty($tgl_penugasan_raw) ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $tgl_penugasan_raw))) : NULL;
        $tgl_sidang    = !empty($tgl_sidang_raw) ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $tgl_sidang_raw))) : NULL;

        // Ambil file lama dulu, kalo ga upload baru pake yg lama
        $perkara_lama = $this->db->get_where('PERKARA', ['NO_PERKARA' => $no_perkara])->row_array();
        $nama_file = $perkara_lama['BERKAS_PERKARA']; 

        // Upload berkas sidang baru kalo ada
        if (!empty($_FILES['berkas_baru']['name'])) {
            $config['upload_path']   = FCPATH . 'uploads/perkara/';
            $config['allowed_types'] = 'pdf|jpg|jpeg|png|doc|docx';
            $config['max_size']      = 5120;
            $config['encrypt_name']  = TRUE;
            $this->load->library('upload', $config);
            if ($this->upload->do_upload('berkas_baru')) {
                $upload_data = $this->upload->data();
                $nama_file = $upload_data['file_name'];
            }
        }

        // UPDATE SEMUA FIELD PERSIDANGAN
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

    // TAMBAH PERKARA INTERNAL: Dipake Staff/Admin input manual tanpa lewat klien
    public function tambah_perkara_internal()
    {
        $config['upload_path']   = FCPATH . 'uploads/perkara/';
        $config['allowed_types'] = 'pdf|jpg|jpeg|png|doc|docx';
        $config['max_size']      = 2048;
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('berkas_perkara')) {
            echo $this->upload->display_errors();
            die();
        } else {
            date_default_timezone_set('Asia/Jakarta');

            $file_data = $this->upload->data();
            $no_perkara = $this->input->post('no_perkara');
            $telp_staff = $this->session->userdata('telp');

            $data_perkara = [
                'NO_PERKARA'     => $no_perkara,
                'TELP_STAFF'     => $telp_staff,
                'JUDUL_PERKARA'  => $this->input->post('judul'),
                'TGL_MASUK'      => date('Y-m-d H:i:s'), // Lock jam masuk real-time
                'BERKAS_PERKARA' => $file_data['file_name'],
                'STATUS_PERKARA' => 'Baru',
                'NAMA_KLIEN'     => $this->input->post('nama_klien'),
                'TELP_KLIEN'     => $this->input->post('telp_klien'),
                'ALAMAT_KLIEN'   => $this->input->post('alamat_klien')
            ];
            $this->db->insert('PERKARA', $data_perkara);

            // Bikin tracking keuangan juga
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
    
    // UPDATE CEPAT DARI MODAL EDIT: Dipake tombol Edit di card. Handle semua field + convert datetime
    public function update($no_perkara)
    {
        // SECURITY: Cek admin/staff login
        if(!$this->session->userdata('admin_logged_in') && !$this->session->userdata('jabatan')) redirect('auth');
            
        // CONVERT: datetime-local "2026-06-22T18:30" → MySQL "2026-06-22 18:30:00"
        $tgl_penugasan_raw = $this->input->post('TGL_PENUGASAN_TIM');
        $tgl_sidang_raw = $this->input->post('TGL_SIDANG');
            
        $tgl_penugasan = !empty($tgl_penugasan_raw) ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $tgl_penugasan_raw))) : NULL;
        $tgl_sidang = !empty($tgl_sidang_raw) ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $tgl_sidang_raw))) : NULL;

        $data = [
            'JUDUL_PERKARA'     => $this->input->post('JUDUL_PERKARA'),
            'STATUS_PERKARA'    => $this->input->post('STATUS_PERKARA'),
            'TGL_PENUGASAN_TIM' => $tgl_penugasan,
            'TGL_SIDANG'        => $tgl_sidang,
            'AGENDA_SIDANG'     => $this->input->post('AGENDA_SIDANG'),
            'CATATAN_DISPOSISI' => $this->input->post('CATATAN_DISPOSISI'),
            'HASIL_SIDANG'      => $this->input->post('HASIL_SIDANG')
        ];
            
        $this->db->where('NO_PERKARA', $no_perkara);
        $this->db->update('PERKARA', $data);
            
        $this->session->set_flashdata('pesan', 'Data perkara berhasil diupdate');
        redirect('perkara');
    }
}
