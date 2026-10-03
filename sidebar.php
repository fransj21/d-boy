<?php
// sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'kasir';
?>
<nav id="sidebar" class="d-flex flex-column py-3 shadow-lg">
    <div class="px-4 mb-4 mt-2 text-center text-md-start">
        <h4 class="text-white fw-bold mb-0">🏍️ D-Boy</h4>
        <small class="text-white-50" style="letter-spacing: 1.5px;">SPEEDSHOP POS</small>
    </div>
    <ul class="nav flex-column mb-auto">
        <!-- Menu yang bisa diakses Admin dan Kasir -->
        <li class="nav-item">
            <a href="index.php" class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="transaksi.php" class="nav-link <?= ($current_page == 'transaksi.php') ? 'active' : '' ?>">
                <i class="bi bi-cart-check-fill me-2"></i> Kasir POS
            </a>
        </li>

        <!-- Menu Khusus Admin Saja -->
        <?php if ($user_role === 'admin'): ?>
        <li class="nav-item">
            <a href="produk.php" class="nav-link <?= ($current_page == 'produk.php') ? 'active' : '' ?>">
                <i class="bi bi-box-seam-fill me-2"></i> Produk & Stok
            </a>
        </li>
        <li class="nav-item">
            <a href="riwayat.php" class="nav-link <?= ($current_page == 'riwayat.php') ? 'active' : '' ?>">
                <i class="bi bi-clock-history me-2"></i> Riwayat Transaksi
            </a>
        </li>
        <li class="nav-item">
            <a href="supplier.php" class="nav-link <?= ($current_page == 'supplier.php') ? 'active' : '' ?>">
                <i class="bi bi-truck me-2"></i> Supplier
            </a>
        </li>
        <li class="nav-item">
            <a href="tempoan.php" class="nav-link <?= ($current_page == 'tempoan.php') ? 'active' : '' ?>">
                <i class="bi bi-wallet2 me-2"></i> Tempoan (Hutang)
            </a>
        </li>
        <li class="nav-item">
            <a href="user.php" class="nav-link <?= ($current_page == 'user.php') ? 'active' : '' ?>">
                <i class="bi bi-people-fill me-2"></i> Kelola User
            </a>
        </li>
        <li class="nav-item">
            <a href="pengaturan.php" class="nav-link <?= ($current_page == 'pengaturan.php') ? 'active' : '' ?>">
                <i class="bi bi-gear-fill me-2"></i> Pengaturan
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <div class="px-3 mt-auto">
        <a href="logout.php" class="btn btn-outline-light btn-sm w-100 py-2"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
    </div>
</nav>