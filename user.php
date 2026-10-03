<?php
require 'koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
$role = $_SESSION['role'];

if (isset($_POST['tambah_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_baru = $_POST['role'];
    
    $cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $cek->execute([$username]);
    if ($cek->rowCount() > 0) {
        $error = "Username sudah terdaftar di sistem!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if($stmt->execute([$username, $password, $role_baru])) {
            $sukses = "User baru berhasil dibuat!";
        }
    }
}

// Hapus User
if (isset($_GET['hapus'])) {
    $id_user = (int)$_GET['hapus'];
    $cek_user = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $cek_user->execute([$id_user]);
    $u_data = $cek_user->fetch();

    if ($u_data && $u_data['username'] != 'admindboy') {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id_user]);
        $sukses = "Akun pengguna berhasil dihapus!";
    } else {
        $error = "Akun admin utama tidak boleh dihapus!";
    }
}

$users = $pdo->query("SELECT id, username, role FROM users ORDER BY role ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola User - D-Boy Speedshop</title>
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
        <h4 class="fw-bold text-primary mb-4">Kelola Pengguna Sistem</h4>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-custom p-4 mb-4">
                    <h6 class="text-primary fw-bold mb-3">Tambah User Baru</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" name="username" class="form-control" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Hak Akses</label>
                            <select name="role" class="form-select" required>
                                <option value="kasir">Kasir</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" name="tambah_user" class="btn btn-primary w-100 py-2">Buat Akun</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-custom p-4">
                    <h6 class="text-primary fw-bold mb-3">Daftar Akun</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Hak Akses</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $u): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($u['username']) ?></td>
                                    <td>
                                        <?php if($u['role'] == 'admin'): ?>
                                            <span class="badge bg-primary">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Kasir</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($u['username'] != 'admindboy'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                onclick="konfirmasiHapus('user.php?hapus=<?= $u['id'] ?>', 'Akun <?= htmlspecialchars(addslashes($u['username'])) ?>')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                        <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'notifikasi.php'; ?>
</body>
</html>