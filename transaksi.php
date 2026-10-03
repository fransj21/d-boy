<?php
require 'koneksi.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$role =$_SESSION['role'];

if (!isset($_SESSION['cart'])) {$_SESSION['cart'] = []; }

// Tambah Produk ke Keranjang
if (isset($_POST['add_to_cart'])) {
    $id = (int)$_POST['id'];
    $stmt =$pdo->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    $prod =$stmt->fetch();
    
    if ($prod &&$prod['stok'] > 0) {
        if (isset($_SESSION['cart'][$id])) {
            if ($_SESSION['cart'][$id]['qty'] <$prod['stok']) {
                $_SESSION['cart'][$id]['qty']++;
            }
        } else {
            $_SESSION['cart'][$id] = [
                'nama' => $prod['nama_produk'],
                'harga' => (float)$prod['harga_jual'],
                'qty' => 1
            ];
        }
    }
    header("Location: transaksi.php");
    exit;
}

// Tambah / Kurang Qty Keranjang
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    if (isset($_SESSION['cart'][$id])) {
        if ($_GET['action'] === 'plus') {
            $stok_real =$pdo->query("SELECT stok FROM produk WHERE id = $id")->fetchColumn();
            if ($_SESSION['cart'][$id]['qty'] <$stok_real) {
                $_SESSION['cart'][$id]['qty']++;
            }
        } elseif ($_GET['action'] === 'minus') {
            $_SESSION['cart'][$id]['qty']--;
            if ($_SESSION['cart'][$id]['qty'] <= 0) {
                unset($_SESSION['cart'][$id]);
            }
        }
    }
    header("Location: transaksi.php");
    exit;
}

// Bersihkan Keranjang
if (isset($_POST['clear_cart'])) {$_SESSION['cart'] = [];
    header("Location: transaksi.php");
    exit;
}

// Selesaikan Transaksi
if (isset($_POST['proses_bayar']) && !empty($_SESSION['cart'])) {$pdo->beginTransaction();
    try {
        $grand_total = (float)$_POST['grand_total'];
        $metode = htmlspecialchars($_POST['metode_pembayaran']);
        $uang_bayar = (float)$_POST['uang_bayar'];
        $kembali = (float)$_POST['uang_kembali'];

        if ($metode === 'Tunai' && $uang_bayar <$grand_total) {
            throw new Exception("Nominal tunai yang dibayarkan kurang dari total tagihan!");
        }

        $stmt =$pdo->prepare("INSERT INTO transaksi (total_harga, kasir_id, metode_pembayaran, bayar, kembali) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$grand_total, $_SESSION['user_id'],$metode, $uang_bayar,$kembali]);
        $trx_id =$pdo->lastInsertId();

        foreach ($_SESSION['cart'] as $id =>$item) {
            $sub =$item['harga'] * $item['qty'];$pdo->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, qty, subtotal) VALUES (?, ?, ?, ?)")
                ->execute([$trx_id, $id,$item['qty'], $sub]);$pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?")
                ->execute([$item['qty'],$id]);
        }

        $pdo->commit();
        $_SESSION['cart'] = [];$transaksi_berhasil = [
            'id'       => $trx_id,
            'metode'   => $metode,
            'total'    => $grand_total,
            'bayar'    => $uang_bayar,
            'kembali'  => $kembali
        ];
    } catch (Exception $e) {$pdo->rollBack();
        $error =$e->getMessage();
    }
}

// Filter Pencarian & Kategori
$kategori_filter =$_GET['kategori'] ?? '';
$search_query =$_GET['search'] ?? '';

$sql = "SELECT * FROM produk WHERE stok > 0";
$params = [];
if ($kategori_filter !== '' && $kategori_filter !== 'Semua') {$sql .= " AND kategori = ?";
    $params[] =$kategori_filter;
}
if ($search_query !== '') {$sql .= " AND nama_produk LIKE ?";
    $params[] = "\%$search_query%";
}
$sql .= " ORDER BY nama_produk ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produk =$stmt->fetchAll();

$kategori_list =$pdo->query("SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori != ''")->fetchAll(PDO::FETCH_COLUMN);

// Hitung Grand Total
$grand_total_keranjang = 0;
$total_item_count = 0;
foreach ($_SESSION['cart'] as $it) {$grand_total_keranjang += ($it['harga'] *$it['qty']);
    $total_item_count +=$it['qty'];
}
$is_cart_empty = empty($_SESSION['cart']) ? 'disabled' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir POS - D-Boy Speedshop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --primary-blue: #0d47a1; --light-blue: #e3f2fd; --bg-body: #f8fafc; }
        body { background-color: var(--bg-body); font-family: 'Segoe UI', Tahoma, sans-serif; overflow-x: hidden; }
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: linear-gradient(180deg, var(--primary-blue) 0%, #001f54 100%); transition: all 0.3s; }
        #sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 4px 15px; font-weight: 500; }
        #sidebar .nav-link:hover, #sidebar .nav-link.active { background: rgba(255,255,255,0.18); color: #fff; font-weight: 600; }
        
        .search-box { border-radius: 12px; border: 1px solid #e2e8f0; padding: 12px 20px; }
        .pill-cat { border-radius: 30px; padding: 8px 22px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: 1px solid #e2e8f0; color: #64748b; background: #fff; transition: all 0.2s; white-space: nowrap; }
        .pill-cat.active, .pill-cat:hover { background-color: var(--primary-blue); color: #fff; border-color: var(--primary-blue); }
        .prod-card { border: 1px solid #f1f5f9; border-radius: 16px; background: #fff; transition: transform 0.2s, box-shadow 0.2s; }
        .prod-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
        .prod-price { color: var(--primary-blue); font-weight: 700; font-size: 1.15rem; }
        .btn-add { background-color: var(--primary-blue); color: white; border-radius: 10px; font-weight: 600; border: none; padding: 8px 0; width: 100%; }
        .btn-add:hover { background-color: #002171; color: white; }
        
        .cart-panel { background: #fff; border-radius: 18px; border: 1px solid #f1f5f9; height: calc(100vh - 48px); display: flex; flex-direction: column; position: sticky; top: 24px; }
        .cart-items { overflow-y: auto; flex-grow: 1; }
    </style>
</head>
<body class="d-flex">

    <?php include 'sidebar.php'; ?>

    <div id="content-wrapper" class="flex-grow-1 p-4" style="height: 100vh; overflow-y: auto;">
        <div class="row g-4">
            <!-- Katalog Produk (Kiri) -->
            <div class="col-lg-8">
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control search-box border-start-0" placeholder="Cari produk aksesoris..." value="<?php echo htmlspecialchars($search_query); ?>" style="border-radius: 0 12px 12px 0;">
                    </div>
                </form>

                <!-- Tombol Kategori Dinamis -->
                <div class="d-flex gap-2 overflow-auto pb-3 mb-3">
                    <?php
                    $active_semua = ($kategori_filter === '' || $kategori_filter === 'Semua') ? 'pill-cat active' : 'pill-cat';
                    echo '<a href="transaksi.php" class="' . $active_semua . '">Semua</a>';

                    foreach ($kategori_list as $kat) {$active_kat = ($kategori_filter ===$kat) ? 'pill-cat active' : 'pill-cat';
                        $url_kat = 'transaksi.php?kategori=' . urlencode($kat);
                        $label_kat = htmlspecialchars($kat);
                        echo '<a href="' . $url_kat . '" class="' . $active_kat . '">' . $label_kat . '</a>';
                    }
                    ?>
                </div>

                <div class="row g-3">
                    <?php if (empty($produk)): ?>
                        <div class="col-12 text-center py-5 text-muted">Produk tidak ditemukan atau stok kosong.</div>
                    <?php else: ?>
                        <?php foreach ($produk as$p): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="card prod-card p-3 h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <span class="badge bg-light text-primary border mb-2"><?php echo htmlspecialchars($p['kategori'] ?? 'Aksesoris'); ?></span>
                                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($p['nama_produk']); ?></h6>
                                    <p class="text-muted small mb-2">Stok: <?php echo number_format($p['stok'], 0, ',', '.'); ?></p>
                                </div>
                                <div>
                                    <div class="prod-price mb-3">Rp <?php echo number_format($p['harga_jual'], 0, ',', '.'); ?></div>
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-add">Tambah</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel Keranjang (Kanan) -->
            <div class="col-lg-4">
                <div class="cart-panel p-4 shadow-sm">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-cart3 fs-4 text-primary me-2"></i>
                        <div>
                            <h5 class="fw-bold mb-0">Keranjang</h5>
                            <small class="text-muted"><?php echo number_format($total_item_count, 0, ',', '.'); ?> item \vert{} <?php echo count($_SESSION['cart']); ?> produk</small>
                        </div>
                    </div>

                    <div class="cart-items py-2">
                        <?php if (empty($_SESSION['cart'])): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-cart-x fs-1 opacity-25"></i>
                                <p class="mt-2">Keranjang masih kosong</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($_SESSION['cart'] as $id_cart =>$item): ?>
                                <?php $sub = $item['harga'] *$item['qty']; ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($item['nama']); ?></h6>
                                        <small class="text-primary fw-semibold">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="transaksi.php?action=minus&id=<?php echo (int)$id_cart; ?>" class="btn btn-sm btn-outline-secondary py-0 px-2">-</a>
                                        <span class="fw-bold"><?php echo (int)$item['qty']; ?></span>
                                        <a href="transaksi.php?action=plus&id=<?php echo (int)$id_cart; ?>" class="btn btn-sm btn-outline-secondary py-0 px-2">+</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="border-top pt-3 mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Total</h5>
                            <h4 class="fw-bold text-primary mb-0">Rp <?php echo number_format($grand_total_keranjang, 0, ',', '.'); ?></h4>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" class="w-50">
                                <button type="submit" name="clear_cart" class="btn btn-light w-100 py-2 fw-semibold text-muted" <?php echo $is_cart_empty; ?>>Bersihkan</button>
                            </form>
                            <button type="button" class="btn btn-add w-50 py-2" data-bs-toggle="modal" data-bs-target="#modalPembayaran" <?php echo $is_cart_empty; ?>>Bayar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Pembayaran -->
    <div class="modal fade" id="modalPembayaran" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 shadow-lg" method="POST">
                <input type="hidden" name="grand_total" id="modalGrandTotal" value="<?php echo (float)$grand_total_keranjang; ?>">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-wallet2 me-2"></i>Konfirmasi Pembayaran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="text-center p-3 bg-light rounded-4 mb-4">
                        <small class="text-muted fw-semibold">TOTAL TAGIHAN</small>
                        <h2 class="fw-bold text-primary mb-0">Rp <?php echo number_format($grand_total_keranjang, 0, ',', '.'); ?></h2>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Metode Pembayaran</label>
                        <select name="metode_pembayaran" id="metodePembayaran" class="form-select form-select-lg" required>
                            <option value="Tunai">💵 Tunai (Cash)</option>
                            <option value="QRIS">📱 QRIS</option>
                            <option value="Transfer Bank">🏦 Transfer Bank</option>
                            <option value="E-Wallet">💳 E-Wallet (OVO/GoPay/Dana)</option>
                        </select>
                    </div>

                    <div id="areaTunai">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Uang Diterima (Rp)</label>
                            <input type="number" name="uang_bayar" id="inputUangBayar" class="form-control form-control-lg" placeholder="Masukkan jumlah uang..." required>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNominal(<?php echo (float)$grand_total_keranjang; ?>)">Uang Pas</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNominal(50000)">50.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNominal(100000)">100.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNominal(200000)">200.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNominal(500000)">500.000</button>
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Kembalian:</span>
                                <h4 class="fw-bold text-success mb-0" id="textKembalian">Rp 0</h4>
                            </div>
                        </div>
                    </div>

                    <div id="areaNonTunai" class="alert alert-info py-3 mb-2 d-none">
                        <i class="bi bi-info-circle-fill me-2"></i>Pembayaran <strong>Non-Tunai</strong> langsung diproses pas sesuai total tagihan.
                    </div>

                    <input type="hidden" name="uang_kembali" id="hiddenKembali" value="0">
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="submit" name="proses_bayar" id="btnSubmitBayar" class="btn btn-primary w-100 py-3 fw-bold fs-5 rounded-3" disabled>
                        <i class="bi bi-check-circle me-1"></i> Selesaikan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const totalBelanja = <?php echo (float)$grand_total_keranjang; ?>;
        const metodeSelect = document.getElementById('metodePembayaran');
        const areaTunai = document.getElementById('areaTunai');
        const areaNonTunai = document.getElementById('areaNonTunai');
        const inputUangBayar = document.getElementById('inputUangBayar');
        const textKembalian = document.getElementById('textKembalian');
        const hiddenKembali = document.getElementById('hiddenKembali');
        const btnSubmitBayar = document.getElementById('btnSubmitBayar');

        function hitungKembalian() {
            const bayar = parseFloat(inputUangBayar.value) || 0;
            const kembali = bayar - totalBelanja;

            if (kembali >= 0) {
                textKembalian.className = "fw-bold text-success mb-0";
                textKembalian.innerText = "Rp " + kembali.toLocaleString('id-ID');
                hiddenKembali.value = kembali;
                btnSubmitBayar.disabled = false;
            } else {
                textKembalian.className = "fw-bold text-danger mb-0";
                textKembalian.innerText = "Kurang Rp " + Math.abs(kembali).toLocaleString('id-ID');
                hiddenKembali.value = 0;
                btnSubmitBayar.disabled = true;
            }
        }

        function setNominal(val) {
            inputUangBayar.value = val;
            hitungKembalian();
        }

        inputUangBayar.addEventListener('input', hitungKembalian);

        metodeSelect.addEventListener('change', function() {
            if (this.value === 'Tunai') {
                areaTunai.classList.remove('d-none');
                areaNonTunai.classList.add('d-none');
                inputUangBayar.value = '';
                textKembalian.innerText = "Rp 0";
                hiddenKembali.value = 0;
                btnSubmitBayar.disabled = true;
                inputUangBayar.required = true;
            } else {
                areaTunai.classList.add('d-none');
                areaNonTunai.classList.remove('d-none');
                inputUangBayar.value = totalBelanja;
                hiddenKembali.value = 0;
                btnSubmitBayar.disabled = false;
                inputUangBayar.required = false;
            }
        });
    </script>

    <?php if (isset($transaksi_berhasil)): ?>
    <script>
    Swal.fire({
        title: '<span style="color:#0d47a1; font-weight:700;">TRANSAKSI SUKSES!</span>',
        html: `
            <div class="text-center my-3">
                <span class="badge bg-light text-primary border px-3 py-2 mb-3">No. Nota #TRX-<?php echo sprintf('%05d', $transaksi_berhasil['id']); ?></span>
                <p class="text-muted mb-2">Metode: <strong><?php echo htmlspecialchars($transaksi_berhasil['metode']); ?></strong></p>
                <div class="p-3 bg-light rounded-4 border mb-3">
                    <small class="text-muted fw-bold">TOTAL KEMBALIAN</small>
                    <h1 class="fw-bold text-success mb-0">Rp <?php echo number_format($transaksi_berhasil['kembali'], 0, ',', '.'); ?></h1>
                </div>
                <div class="d-flex justify-content-between px-3 text-muted small">
                    <span>Total Tagihan: Rp <?php echo number_format($transaksi_berhasil['total'], 0, ',', '.'); ?></span>
                    <span>Uang Diterima: Rp <?php echo number_format($transaksi_berhasil['bayar'], 0, ',', '.'); ?></span>
                </div>
            </div>
        `,
        icon: 'success',
        confirmButtonText: '<i class="bi bi-check2-circle me-1"></i> Selesai',
        confirmButtonColor: '#0d47a1',
        allowOutsideClick: false,
        customClass: { popup: 'rounded-4 shadow-lg' }
    });
    </script>
    <?php endif; ?>

    <?php include 'notifikasi.php'; ?>
</body>
</html>