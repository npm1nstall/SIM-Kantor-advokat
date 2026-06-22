</div>
</div>

<!-- 

FOOTER TEMPLATE + JS GLOBAL
File: v_footer.php
Fungsi: Load jQuery + Bootstrap + Modal dinamis + JS sidebar toggle
Dipanggil di: Semua view via $this->_render()

-->

<!-- Load jQuery + Bootstrap JS dari folder assets -->
<!-- JQuery wajib duluan baru Bootstrap, urutan ga boleh kebalik -->
<script src="<?php echo base_url('assets/js/jquery.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/bootstrap.bundle.min.js'); ?>"></script>

<?php 
/**
 * GENERATE MODAL EDIT SIDANG DINAMIS
 * Logic: Loop data $perkara dari controller Perkara.php
 * Tiap perkara bikin 1 modal dengan ID unik berdasarkan NO_PERKARA
 * ID modal di-clean: hapus '/' dan '.' biar valid buat HTML id
 * Dipake di: View perkara/v_daftar.php tombol "Update Sidang"
 */
if (isset($perkara) && is_array($perkara)): 
    foreach($perkara as $p): 
        // Validasi biar ga error kalo $p bukan array
        if(is_array($p)): 
?>
            <!-- Modal Update Sidang - ID unik per perkara -->
            <div class="modal fade" id="editModal<?= str_replace(['/', '.'], '', $p['NO_PERKARA']) ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <!-- Form submit ke Perkara/update_sidang/NO-PERKARA -->
                    <form action="<?= base_url('perkara/update_sidang/'.$p['NO_PERKARA']) ?>" method="post">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Sidang: <?= $p['NO_PERKARA'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Input datetime-local: value harus format Y-m-d\TH:i -->
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Sidang</label>
                                    <input type="datetime-local" name="tgl_sidang" class="form-control" 
                                           value="<?= !empty($p['TGL_SIDANG']) ? date('Y-m-d\TH:i', strtotime($p['TGL_SIDANG'])) : '' ?>">
                                </div>
                                <!-- Input agenda sidang -->
                                <div class="mb-3">
                                    <label class="form-label">Agenda Sidang</label>
                                    <input type="text" name="agenda" class="form-control" value="<?= $p['AGENDA_SIDANG'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
<?php 
        endif; 
    endforeach; 
endif; 
?>

<!-- JS Toggle Sidebar Menu -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const btn = document.getElementById("menu-toggle");
        const wrapper = document.getElementById("wrapper");

        // Cek dulu element ada apa ga biar ga error JS
        if(btn && wrapper) {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                wrapper.classList.toggle("toggled"); // Class 'toggled' ngatur CSS sidebar
            });
        }
    });
</script>

</body>
</html>
<!-- End of file v_footer.php -->
