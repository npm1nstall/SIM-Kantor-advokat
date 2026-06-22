<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ==============================================
 * CONTROLLER: Dashboard 
 * Fungsi: Halaman utama beda role + CRUD Staff + Keuangan + Pimpinan
 * Role: Klien, Admin, Keuangan, Kuasa Hukum, Pimpinan
 * Author: [Nama Kamu]
 * ==============================================
 */
class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // CEK LOGIN: Kalo belum login, tendang ke auth
        if (!$this->session->userdata('jabatan') && !$this->session->userdata('klien_logged_in')) {
            redirect('auth');
            return;
        }
        
        // Load model keuangan, dipake di semua fungsi
        $this->load->model('M_keuangan');
    }

    /**
     * Helper render template
     * Fungsi: Biar ga nulis load view header+sidebar+footer berulang
     */
    private function _render($view, $data = []) {
        $this->load->view('auth/v_header');
        $this->load->view('v_sidebar', $data);
        $this->load->view($view, $data);
        $this->load->view('auth/v_footer');
    }

    /**
     * Dashboard Utama - Auto detect role
     * URL: /dashboard
     */
    public function index() {
        // === 1. JALUR KLIEN ===
        if ($this->session->userdata('klien_logged_in')) {
            $telp = $this->session->userdata('telp_klien');

            // Ambil data perkara milik klien ini
            $data['perkara'] = $this->db->get_where('PERKARA', ['TELP_KLIEN' => $telp])->row_array();

            // Hitung notif tagihan belum bayar
            $data['notif_bayar'] = $this->M_keuangan->count_tagihan_belum_bayar($telp);

            // Kalo data perkara kosong, kasih default biar view ga error
            if (!$data['perkara']) {
                $data['perkara'] = [
                    'NO_PERKARA'      => '-',
                    'JUDUL_PERKARA'   => 'Data tidak ditemukan',
                    'STATUS_PERKARA'  => '-'
                ];
            }

            $this->_render('klien/v_dashboard', $data);
            return;
        }

        // === 2. JALUR STAFF INTERNAL ===
        $jabatan = $this->session->userdata('jabatan');
        $data['title'] = 'Dashboard';
        
        // Load model perkara buat ambil data antrean
        $this->load->model('M_perkara');

        // SWITCH BERDASARKAN JABATAN
        switch ($jabatan) {

            case 'Admin':
                // Hitung data real-time buat card summary
                $data['jml_perkara'] = $this->db->count_all('PERKARA');
                $data['jml_surat']   = $this->db->count_all('SURAT');
                $data['jml_staff']   = $this->db->count_all('KARYAWAN');
                
                // Hitung sidang mendatang
                $data['jml_sidang']  = $this->db->where('TGL_SIDANG >=', date('Y-m-d H:i:s'))->count_all_results('PERKARA');

                // Ambil antrean pendaftaran yg butuh diproses admin
                $data['pengajuan']   = $this->M_perkara->get_antrean_admin();

                $this->_render('dashboard/admin/v_index', $data);
                break;

            case 'Keuangan':
                // Hitung status keuangan
                $data['jml_pending']   = $this->db->where('STATUS_VERIFIKASI_OPS', 'Pending Pimpinan')->count_all_results('KEUANGAN');
                $data['jml_disetujui'] = $this->db->where('STATUS_VERIFIKASI_OPS', 'Pending Keuangan')->count_all_results('KEUANGAN');
                $data['jml_total']     = $this->db->count_all('KEUANGAN');
                
                $this->load->model('M_perkara');
                
                // Ambil data pengajuan ops + join ke perkara
                $data['pengajuan'] = $this->db
                    ->select('KEUANGAN.*, PERKARA.JUDUL_PERKARA')
                    ->from('KEUANGAN')
                    ->join('PERKARA', 'PERKARA.NO_PERKARA = KEUANGAN.NO_PERKARA', 'left')
                    ->where('KEUANGAN.JMLH_PENGAJUAN_OPS >', 0) 
                    ->get()
                    ->result();

                $this->_render('dashboard/keuangan/v_index', $data);
                break;

            case 'Pimpinan':
                // Pimpinan punya dashboard khusus
                redirect('dashboard/pimpinan');
                break;

            case 'Kuasa Hukum':
                // Data dinamis buat kuasa hukum
                $data['jml_perkara'] = $this->db->count_all('PERKARA');
                $data['jml_sidang']  = $this->db->where('TGL_SIDANG >=', date('Y-m-d H:i:s'))->count_all_results('PERKARA');
                $data['pengajuan']   = $this->M_perkara->get_antrean_kuasa_hukum();
                
                $this->_render('dashboard/kuasa_hukum/v_index', $data);
                break;

            default:
                show_404();
                break;
        }
    }

    // ================= MODUL SDM - ADMIN ONLY =================

    /**
     * Simpan data staff/klien baru
     * Logic: Klien ga pake password, Staff wajib password MD5
     */
    public function simpan_staff() {
        if ($this->session->userdata('jabatan') != 'Admin') redirect('dashboard');

        $role = $this->input->post('role');
        $telp = $this->input->post('telp');
        $nama = $this->input->post('nama');

        // Validasi wajib
        if (empty($role) || empty($telp) || empty($nama)) {
            $this->session->set_flashdata('pesan_error', 'Semua kolom wajib diisi!');
            redirect('dashboard/staff');
            return;
        }

        // Kalo role Klien
        if ($role == 'Klien') {
            $no_perkara = 'REG-' . date('YmdHis') . '-' . rand(10, 99);

            $data_perkara = [
                'NO_PERKARA'     => $no_perkara,
                'NAMA_KLIEN'     => $nama,
                'TELP_KLIEN'     => $telp,
                'ALAMAT_KLIEN'   => $this->input->post('alamat'),
                'JUDUL_PERKARA'  => 'Pendaftaran Akun Baru',
                'TGL_MASUK'      => date('Y-m-d H:i:s'),
                'STATUS_PERKARA' => 'Baru'
            ];
            $this->db->insert('PERKARA', $data_perkara);

            // Auto bikin data keuangan kosong
            $data_keuangan = [
                'NO_TRANSAKSI'          => 'TRX-' . date('YmdHis') . '-' . rand(100, 999),
                'NO_PERKARA'            => $no_perkara,
                'STATUS_VERIFIKASI_OPS' => 'Pending Admin',
                'STATUS_BAYAR_KLIEN'    => 'Belum Bayar'
            ];
            $this->db->insert('KEUANGAN', $data_keuangan);
            
        } else {
            // Kalo role Staff - wajib password
            $password_input = $this->input->post('password');
            if (empty($password_input)) {
                $this->session->set_flashdata('pesan_error', 'Password wajib diisi untuk staf internal!');
                redirect('dashboard/tambah_staff');
                return;
            }

            $data_karyawan = [
                'NAMA_STAFF'    => $nama,
                'TELP_STAFF'    => $telp,
                'JABATAN_STAFF' => $role, 
                'PASS_STAFF'    => md5($password_input) // MD5 buat demo
            ];
            $this->db->insert('KARYAWAN', $data_karyawan);
        }

        $this->session->set_flashdata('pesan', 'Akun baru role ' . $role . ' berhasil dibuat!');
        redirect('dashboard/staff');
    }

    /**
     * Tampilkan form tambah staff
     */
    public function tambah_staff() {
        if ($this->session->userdata('jabatan') != 'Admin') redirect('dashboard');
        $data['title'] = 'Tambah Akun Pengguna';
        $this->_render('dashboard/admin/v_tambah_staff', $data);
    }

    /**
     * Tampilkan data semua staff
     */
    public function staff() {
        if ($this->session->userdata('jabatan') != 'Admin') redirect('dashboard');
        $data['title'] = 'Data Staff';
        $data['staff'] = $this->db->get('KARYAWAN')->result_array();
        $this->_render('dashboard/admin/v_staff', $data);
    }

    /**
     * Manajemen cuti - Admin & Pimpinan
     */
    public function cuti() {
        if (!in_array($this->session->userdata('jabatan'), ['Admin', 'Pimpinan'])) redirect('dashboard');
        $data['title'] = 'Manajemen Cuti';
        $data['cuti'] = $this->db->get_where('KARYAWAN', 'status_cuti IS NOT NULL')->result_array();
        $this->_render('dashboard/admin/v_cuti', $data);
    }

    /**
     * Manajemen surat - Admin only
     */
    public function surat() {
        if ($this->session->userdata('jabatan') != 'Admin') redirect('dashboard');
        $this->_render('dashboard/admin/v_surat', ['title' => 'Manajemen Surat']);
    }

    // ================= MODUL KEUANGAN =================
    
    /**
     * Controller keuangan multi-aksi
     * Aksi: index, pengajuan, verifikasi, approval, pembayaran, dll
     */
    public function keuangan($aksi = 'index') {
        $jabatan = $this->session->userdata('jabatan');

        // Cek akses role
        if (!in_array($jabatan, ['Admin', 'Keuangan', 'Pimpinan', 'Kuasa Hukum'])) {
            redirect('dashboard');
        }

        switch ($aksi) {

            case 'pengajuan':
                $this->_render('dashboard/keuangan/v_pengajuan');
                break;
                
            case 'pengajuan_ops':
                $data['title'] = 'Ajukan Biaya Operasional';
                $telp_staff = $this->session->userdata('telp'); 

                // Ambil perkara milik kuasa hukum ini
                $data['perkara_ops'] = $this->db
                    ->select('PERKARA.NO_PERKARA, PERKARA.JUDUL_PERKARA, KEUANGAN.NO_TRANSAKSI, KEUANGAN.JMLH_PENGAJUAN_OPS, KEUANGAN.STATUS_VERIFIKASI_OPS')
                    ->from('PERKARA')
                    ->join('KEUANGAN', 'PERKARA.NO_PERKARA = KEUANGAN.NO_PERKARA')
                    ->where('PERKARA.TELP_STAFF', $telp_staff)
                    ->get()
                    ->result_array();

                $this->_render('dashboard/keuangan/v_pengajuan_ops', $data);
                break;

            case 'simpan_pengajuan_ops':
                $no_transaksi = $this->input->post('no_transaksi');
                    
                if (!empty($no_transaksi)) {
                    $data_update = [
                        'TGL_PENGAJUAN_OPS'     => date('Y-m-d H:i:s'),
                        'JMLH_PENGAJUAN_OPS'    => $this->input->post('jmlh_pengajuan'),
                        'KEPERLUAN_DANA_OPS'    => $this->input->post('keperluan_dana'),
                        'STATUS_VERIFIKASI_OPS' => 'Pending Pimpinan', 
                        'TTD_KUASA_HUKUM'       => 'TERTANDA_SISTEM_KH' 
                    ];

                    $this->db->where('NO_TRANSAKSI', $no_transaksi);
                    $this->db->update('KEUANGAN', $data_update);
                    $this->session->set_flashdata('pesan_sukses', 'Pengajuan operasional berhasil dikirim ke Pimpinan!');
                }
                redirect('dashboard/keuangan/pengajuan_ops');
                break;

            case 'verifikasi':
                $this->load->model('M_perkara');
                // Ambil antrean sesuai role yg verifikasi
                if ($jabatan == 'Admin') {
                    $data['berkas'] = $this->M_perkara->get_antrean_admin();
                } else if ($jabatan == 'Kuasa Hukum') {
                    $data['berkas'] = $this->M_perkara->get_antrean_kuasa_hukum();
                } else {
                    $data['berkas'] = $this->M_perkara->get_antrean_keuangan();
                }
                
                $data['title'] = 'Verifikasi Berkas';
                $this->_render('dashboard/keuangan/v_verifikasi', $data);
                break;

            case 'proses_verifikasi':
                $no_perkara = $this->input->post('no_perkara'); 
                $this->db->where('NO_PERKARA', $no_perkara);
                $this->db->update('KEUANGAN', ['STATUS_VERIFIKASI_OPS' => 'Validasi Selesai']);

                $this->session->set_flashdata('sukses', 'Berkas berhasil diverifikasi!');
                redirect('dashboard/keuangan/pembayaran'); 
                break;

            case 'approval':
                $data['title'] = 'Approval Pimpinan';
                $data['antrean_approval'] = $this->db
                    ->select('KEUANGAN.*, PERKARA.NO_PERKARA, PERKARA.JUDUL_PERKARA')
                    ->from('KEUANGAN')
                    ->join('PERKARA', 'PERKARA.NO_PERKARA = KEUANGAN.NO_PERKARA', 'left')
                    ->like('KEUANGAN.STATUS_VERIFIKASI_OPS', 'Pending Pimpinan', 'both')
                    ->get()
                    ->result_array();

                $this->_render('dashboard/keuangan/v_approval', $data);
                break;
                
            case 'proses_approval':
                $no_transaksi = $this->uri->segment(4);
                $status_klik  = $this->uri->segment(5);

                if (!empty($no_transaksi)) {
                    if ($status_klik == 'ACC') {
                        $data_update = [
                            'STATUS_VERIFIKASI_OPS' => 'Pending Keuangan', 
                            'TTD_PIMPINAN'          => 'APPROVED_BY_PIMPINAN' 
                        ];
                    } else {
                        $data_update = [
                            'STATUS_VERIFIKASI_OPS' => 'Ditolak', 
                            'TTD_PIMPINAN'          => 'REJECTED_BY_PIMPINAN'
                        ];
                    }

                    $this->db->where('NO_TRANSAKSI', $no_transaksi);
                    $this->db->update('KEUANGAN', $data_update);
                    $this->session->set_flashdata('sukses_approval', 'Pengajuan operasional berhasil diproses!');
                }
                redirect('dashboard/pimpinan'); 
                break;
                
            case 'cairkan_ops':
                $no_transaksi = $this->input->post('no_transaksi');
                $no_nota      = $this->input->post('no_nota');

                if (!empty($no_transaksi)) {
                    $data_update = [
                        'BUKTI_NOTA_KAS_KELUAR' => $no_nota,
                        'STATUS_VERIFIKASI_OPS' => 'Validasi Selesai'
                    ];
                    $this->db->where('NO_TRANSAKSI', $no_transaksi);
                    $this->db->update('KEUANGAN', $data_update);
                }
                redirect('dashboard/keuangan');
                break;

            case 'pembayaran':
                $data['title'] = 'Pembayaran Klien';
                $data['tagihan'] = $this->db->get('KEUANGAN')->result();
                $this->_render('dashboard/keuangan/v_pembayaran', $data);
                break;
                
            case 'verifikasi_bayar':
                $segments = $this->uri->segment_array();
                $no_transaksi = end($segments);

                if (!empty($no_transaksi) && $no_transaksi !== 'verifikasi_bayar') {
                    $no_transaksi = urldecode($no_transaksi);
                    $this->db->where('NO_TRANSAKSI', $no_transaksi);
                    $this->db->update('KEUANGAN', ['STATUS_BAYAR_KLIEN' => 'Lunas']);
                }
                redirect('dashboard/keuangan/pembayaran');
                break;

            case 'tambah_tagihan':
                $data['title'] = 'Buat Tagihan';
                $no_transaksi = $this->uri->segment(5) ?? $this->uri->segment(4);

                if (!empty($no_transaksi)) {
                    $data['perkara'] = $this->db
                        ->select('PERKARA.*, KEUANGAN.NO_TRANSAKSI, KEUANGAN.STATUS_VERIFIKASI_OPS')
                        ->from('PERKARA')
                        ->join('KEUANGAN', 'PERKARA.NO_PERKARA = KEUANGAN.NO_PERKARA')
                        ->where('KEUANGAN.NO_TRANSAKSI', urldecode($no_transaksi))
                        ->get()
                        ->row_array();
                }

                if (empty($data['perkara'])) {
                    $data['perkara'] = $this->db->get('PERKARA')->result_array();
                }

                $this->_render('dashboard/keuangan/v_tambah_tagihan', $data);
                break;

            default:
                // Dashboard utama keuangan
                $data['jml_pending']   = $this->db->where('STATUS_VERIFIKASI_OPS', 'Pending Keuangan')->count_all_results('KEUANGAN');
                $data['jml_disetujui'] = $this->db->where('STATUS_VERIFIKASI_OPS', 'Validasi Selesai')->count_all_results('KEUANGAN'); 
                $data['jml_total']     = $this->db->count_all('KEUANGAN');
                $data['pengajuan']     = $this->db->get_where('KEUANGAN', ['STATUS_VERIFIKASI_OPS' => 'Pending Keuangan'])->result();

                $this->_render('dashboard/keuangan/v_index', $data);
                break;
        }
    }

    /**
     * Laporan sistem - Admin & Pimpinan
     */
    public function laporan() {
        if (!in_array($this->session->userdata('jabatan'), ['Admin', 'Pimpinan'])) redirect('dashboard');
        $this->_render('dashboard/admin/v_laporan', ['title' => 'Laporan Sistem']);
    }
    
    // ================= DASHBOARD PIMPINAN =================
    
    /**
     * Dashboard khusus Pimpinan
     * Isi: KPI, Keuangan, Data recent
     */
    public function pimpinan() {
        if ($this->session->userdata('jabatan') != 'Pimpinan') {
            redirect('dashboard');
        }

        $data['title'] = 'Dashboard Pimpinan';
        
        // Summary card atas
        $data['jml_perkara'] = $this->db->count_all('PERKARA');
        $data['jml_surat']   = $this->db->count_all('SURAT');
        $data['jml_staff']   = $this->db->count_all('KARYAWAN');

        // KPI Perkara
        $data['perkara_proses'] = $this->db->where('STATUS_PERKARA', 'Proses')->count_all_results('PERKARA');
        $data['perkara_selesai'] = $this->db->where('STATUS_PERKARA', 'Selesai')->count_all_results('PERKARA');
        $data['surat_masuk'] = $this->db->where('JNS_SURAT', 'Masuk')->count_all_results('SURAT');

        // Status keuangan
        $data['total_pengajuan'] = $this->db->count_all('KEUANGAN');
        $data['pending_admin'] = $this->db->where('STATUS_VERIFIKASI_OPS', 'Pending Admin')->count_all_results('KEUANGAN');
        $data['pending_kuasa_hukum'] = $this->db->where('STATUS_VERIFIKASI_OPS', 'Pending Kuasa Hukum')->count_all_results('KEUANGAN');
        $data['pending_keuangan'] = $this->db->where('STATUS_VERIFIKASI_OPS', 'Pending Keuangan')->count_all_results('KEUANGAN');
        $data['pending_approval'] = $this->db->where('STATUS_VERIFIKASI_OPS', 'Verifikasi Keuangan')->count_all_results('KEUANGAN');
        $data['pending_pimpinan'] = $this->db->where('STATUS_VERIFIKASI_OPS', 'Pending Pimpinan')->count_all_results('KEUANGAN');

        // Total pembayaran lunas
        $query_bayar = $this->db->select_sum('TTL_TAGIHAN_KLIEN')->where('STATUS_BAYAR_KLIEN', 'Lunas')->get('KEUANGAN')->row();
        $data['total_pembayaran'] = $query_bayar->TTL_TAGIHAN_KLIEN ?? 0;

        // Data recent 5 terakhir
        $data['recent_perkara'] = $this->db->order_by('NO_PERKARA', 'DESC')->limit(5)->get('PERKARA')->result();
        $data['recent_keuangan'] = $this->db
            ->select('KEUANGAN.*, PERKARA.JUDUL_PERKARA')
            ->from('KEUANGAN')
            ->join('PERKARA', 'PERKARA.NO_PERKARA = KEUANGAN.NO_PERKARA', 'left')
            ->where('KEUANGAN.JMLH_PENGAJUAN_OPS >', 0)
            ->order_by('KEUANGAN.NO_TRANSAKSI', 'DESC')
            ->limit(5)
            ->get()
            ->result();

        $this->_render('dashboard/pimpinan/v_index', $data);
    }
}
/* End of file Dashboard.php */
/* Location: ./application/controllers/Dashboard.php */
