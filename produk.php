<?php
require 'koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: index.php"); 
    exit; 
}
$role = $_SESSION['role'];

// Tambah Produk
if (isset($_POST['tambah_produk']) && $role == 'admin') {
    $nama = trim($_POST['nama_produk']);
    $kategori = trim($_POST['kategori']);
    $modal = (float)$_POST['harga_modal'];
    $jual = (float)$_POST['harga_jual'];
    $stok = (int)$_POST['stok'];
    
    $stmt = $pdo->prepare("INSERT INTO produk (nama_produk, kategori, harga_modal, harga_jual, stok) VALUES (?, ?, ?, ?, ?)");
    if($stmt->execute([$nama, $kategori, $modal, $jual, $stok])) {
        $sukses = "Produk berhasil ditambahkan ke sistem!";
    }
}

// Hapus Produk
if (isset($_GET['hapus']) && $role == 'admin') {
    $pdo->prepare("DELETE FROM produk WHERE id = ?")->execute([$_GET['hapus']]);
    $sukses = "Produk berhasil dihapus!";
}

// Lorisan / Pembukuan
$stats = $pdo->query("SELECT SUM(stok * harga_modal) as total_modal, SUM(stok * harga_jual) as estimasi_omset, SUM(stok) as total_barang FROM produk")->fetch();
$produk = $pdo->query("SELECT * FROM produk ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Produk & Stok - D-Boy Speedshop</title>
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
        <?php if($role == 'admin'): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card card-custom p-3 border-start border-primary border-4">
                    <p class="text-muted mb-1 fw-bold">Total Stok Fisik</p>
                    <h3 class="fw-bold text-primary mb-0"><?= number_format($stats['total_barang'] ?? 0, 0, ',', '.') ?> Pcs</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom p-3 border-start border-info border-4">
                    <p class="text-muted mb-1 fw-bold">Total Modal Tertanam</p>
                    <h3 class="fw-bold text-info mb-0">Rp <?= number_format($stats['total_modal'] ?? 0, 0, ',', '.') ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom p-3 border-start border-success border-4">
                    <p class="text-muted mb-1 fw-bold">Estimasi Omset Total</p>
                    <h3 class="fw-bold text-success mb-0">Rp <?= number_format($stats['estimasi_omset'] ?? 0, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-primary mb-0">Daftar Inventaris Aksesoris</h5>
                <?php if($role == 'admin'): ?>
                <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalTambahProduk">+ Tambah Produk</button>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table id="tabelProduk" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Aksesoris</th>
                            <th>Kategori</th>
                            <?php if($role == 'admin'): ?><th>Harga Modal</th><?php endif; ?>
                            <th>Harga Jual</th>
                            <th>Stok</th>
                            <?php if($role == 'admin'): ?><th class="text-center">Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($produk as $p): 
                            $is_low = ($p['stok'] < 5);
                        ?>
                        <tr class="<?= $is_low ? 'table-danger text-danger fw-bold' : '' ?>">
                            <td><?= htmlspecialchars($p['nama_produk']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['kategori'] ?? 'Umum') ?></span></td>
                            <?php if($role == 'admin'): ?>
                                <td>Rp <?= number_format($p['harga_modal'], 0, ',', '.') ?></td>
                            <?php endif; ?>
                            <td>Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?></td>
                            <td>
                                <?= number_format($p['stok'], 0, ',', '.') ?> Pcs
                                <?php if($is_low): ?><span class="badge bg-danger ms-2">Menipis!</span><?php endif; ?>
                            </td>
                            <?php if($role == 'admin'): ?>
                            <td class="text-center">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger" 
                                        onclick="konfirmasiHapus('produk.php?hapus=<?= $p['id'] ?>', 'Produk <?= htmlspecialchars(addslashes($p['nama_produk'])) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Produk -->
    <div class="modal fade" id="modalTambahProduk" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Produk Aksesoris</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Produk</label>
                        <input type="text" name="nama_produk" class="form-control" required placeholder="Contoh: Knalpot Rob1 Beat">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <input type="text" name="kategori" class="form-control" required placeholder="Contoh: Knalpot, Shock, Velg, Spion">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Harga Modal (Rp)</label>
                            <input type="number" name="harga_modal" class="form-control" required placeholder="0">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Harga Jual (Rp)</label>
                            <input type="number" name="harga_jual" class="form-control" required placeholder="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Awal</label>
                        <input type="number" name="stok" class="form-control" required min="1" placeholder="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_produk" class="btn btn-primary w-100">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tabelProduk').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    search: "Cari Produk:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    paginate: { next: "Lanjut", previous: "Kembali" },
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ produk"
                }
            });
        });
    </script>
    <?php include 'notifikasi.php'; ?>
</body>
</html>