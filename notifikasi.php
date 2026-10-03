<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Konfigurasi Toast Modern Melayang di Kanan Atas
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

// Dialog Konfirmasi Hapus Data Modern
function konfirmasiHapus(url, namaItem) {
    Swal.fire({
        title: 'Hapus ' + namaItem + '?',
        text: "Data yang dihapus tidak dapat dipulihkan kembali!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#0d47a1',
        confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-4 shadow-lg'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>

<?php if(isset($sukses)): ?>
<script>
    Toast.fire({
        icon: 'success',
        title: '<?= addslashes($sukses) ?>'
    });
</script>
<?php endif; ?>

<?php if(isset($error)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Perhatian!',
        text: '<?= addslashes($error) ?>',
        confirmButtonColor: '#0d47a1',
        customClass: {
            popup: 'rounded-4 shadow-lg'
        }
    });
</script>
<?php endif; ?>