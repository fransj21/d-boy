<?php
require 'koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
$role = $_SESSION['role'];

// Tambah Supplier
if (isset($_POST['tambah_supplier'])) {
    $nama = trim($_POST['nama_supplier']);
    $kontak = trim($_POST['kontak']);
    if (!empty($nama)) {
        $stmt = $pdo->prepare("INSERT INTO supplier (nama_supplier, kontak) VALUES (?, ?)");
        $stmt->execute([$nama, $kontak]);
        $sukses = "Supplier berhasil ditambahkan!";
    }
}

// Hapus Supplier dengan Validasi Relasi Data
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];

    $cek_tempoan = $pdo->prepare("SELECT COUNT(*) FROM tempoan WHERE supplier_id = ?");
    $cek_tempoan->execute([$id_hapus]);
    $ada_tempoan = $cek_tempoan->fetchColumn();

    if ($ada_tempoan > 0) {
        $error = "Supplier tidak bisa dihapus karena masih ada catatan tempoan/hutang yang terhubung!";
    } else {
        $delete = $pdo->prepare("DELETE FROM supplier WHERE id = ?");
        $delete->execute([$id_hapus]);
        $sukses = "Supplier berhasil dihapus dari sistem!";
    }
}

$suppliers = $pdo->query("SELECT * FROM supplier ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier - D-Boy Speedshop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        :root { --primary-blue: #0d47a1; --bg-body: #f4f7fb; }
        body { background-color: var(--bg-body); font-family: 'Segoe UI', Tahoma, sans-serif; overflow-x: hidden; }
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: linear-gradient(180deg, var(--primary-blue) 0%, #001f54 100%); transition: all 0.3s; }
        #sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 4px 15px; font-weight: 500; }
        #sidebar .nav-link:hover, #sidebar .nav-link.active { background: rgba(255,255,255,0.18); color: #fff; font-weight: 600; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); background: #fff; }
    </style>
</head>
<body class="d-flex">
    
    <?php include 'sidebar.php'; ?>

    <div id="content-wrapper" class="flex-grow-1 p-4" style="min-height: 100vh; overflow-y: auto;">
        <h4 class="fw-bold text-primary mb-4">Manajemen Supplier</h4>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-custom p-4">
                    <h6 class="fw-bold text-primary mb-3">Tambah Supplier Baru</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Supplier</label>
                            <input type="text" name="nama_supplier" class="form-control" required placeholder="Contoh: Arumi Motoparts">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">No. Kontak / WA</label>
                            <input type="text" name="kontak" class="form-control" placeholder="08xxxxxxxxxx">
                        </div>
                        <button type="submit" name="tambah_supplier" class="btn btn-primary w-100 py-2">Simpan Supplier</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-custom p-4">
                    <h6 class="fw-bold text-primary mb-3">Daftar Supplier</h6>
                    <div class="table-responsive">
                        <table id="tabelSupplier" class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 10%;">No</th>
                                    <th>Nama Supplier</th>
                                    <th>Kontak</th>
                                    <th style="width: 15%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach($suppliers as $s): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($s['nama_supplier']) ?></td>
                                    <td><?= htmlspecialchars($s['kontak'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                onclick="konfirmasiHapus('supplier.php?hapus=<?= $s['id'] ?>', 'Supplier <?= htmlspecialchars(addslashes($s['nama_supplier'])) ?>')">
                                            <i class="bi bi-trash-fill"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tabelSupplier').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                language: {
                    search: "Cari Supplier:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    paginate: { next: "Lanjut", previous: "Kembali" },
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ supplier"
                }
            });
        });
    </script>
    <?php include 'notifikasi.php'; ?>
</body>
</html>