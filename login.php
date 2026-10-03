<?php
require 'koneksi.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Untuk prototype jika hash di DB belum sesuai, gunakan verifikasi langsung (untuk produksi tetap gunakan password_verify)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Pengecekan bypass khusus sesuai request password awal
        if (($username === 'admindboy' && $password === 'Sultanlebakwangi') || 
            ($username === 'kasirdboy' && $password === 'Perintis') || 
            password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        }
    }
    $error = "Username atau Password salah!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - D-Boy Speedshop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #0d47a1, #1976d2); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #ffffff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); padding: 40px; width: 100%; max-width: 400px; }
        .login-card h2 { color: #0d47a1; font-weight: 700; }
        .btn-primary { background-color: #0d47a1; border: none; }
        .btn-primary:hover { background-color: #002171; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h2>D-Boy Speedshop</h2>
            <p class="text-muted">Aksesoris Motor Premium</p>
        </div>
        <?php if(isset($error)): ?> <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required autocomplete="off">
            </div>
            <div class="mb-4">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Masuk</button>
        </form>
    </div>
</body>
</html>