<?php
require 'koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
$role = $_SESSION['role'];

if (isset($_POST['simpan_pengaturan'])) {
    $nama_toko = trim($_POST['nama_toko']);
    $alamat = trim($_POST['alamat']);
    
    $stmt = $pdo->prepare("UPDATE pengaturan SET nama_toko = ?, alamat = ? WHERE id = 1");
    if($stmt->execute([$nama_toko, $alamat])) {
        $sukses = "Pengaturan toko berhasil diperbarui!";
    }
}

$set = $pdo->query("SELECT * FROM pengaturan WHERE id = 1")->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - D-Boy Speedshop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card card-custom p-4">
                    <h4 class="text-primary fw-bold mb-4">Pengaturan Toko</h4>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Toko</label>
                            <input type="text" name="nama_toko" class="form-control" value="<?= htmlspecialchars($set['nama_toko'] ?? 'D-Boy Speedshop') ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Alamat Toko</label>
                            <textarea name="alamat" class="form-control" rows="4" required><?= htmlspecialchars($set['alamat'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="simpan_pengaturan" class="btn btn-primary w-100 py-2">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'notifikasi.php'; ?>
</body>
</html>