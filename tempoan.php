<?php
require 'koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
$role = $_SESSION['role'];

// Tambah / Gabung Hutang Tempoan
if (isset($_POST['tambah_tempoan'])) {
    $nama_supplier = trim($_POST['nama_supplier']);
    $nominal_baru = (float)$_POST['nominal'];
    $jatuh_tempo = $_POST['jatuh_tempo'];

    $cek_sup = $pdo->prepare("SELECT id FROM supplier WHERE LOWER(TRIM(nama_supplier)) = LOWER(TRIM(?)) LIMIT 1");
    $cek_sup->execute([$nama_supplier]);
    $sup = $cek_sup->fetch();

    if ($sup) {
        $supplier_id = $sup['id'];
    } else {
        $ins_sup = $pdo->prepare("INSERT INTO supplier (nama_supplier) VALUES (?)");
        $ins_sup->execute([$nama_supplier]);
        $supplier_id = $pdo->lastInsertId();
    }

    $cek_tempo = $pdo->prepare("SELECT * FROM tempoan WHERE supplier_id = ? AND status = 'Belum Lunas' LIMIT 1");
    $cek_tempo->execute([$supplier_id]);
    $ada = $cek_tempo->fetch();

    if ($ada) {
        $total_update = $ada['total_hutang'] + $nominal_baru;
        $sisa_update = $ada['sisa_hutang'] + $nominal_baru;
        
        $stmt = $pdo->prepare("UPDATE tempoan SET total_hutang = ?, sisa_hutang = ?, jatuh_tempo = ? WHERE id = ?");
        $stmt->execute([$total_update, $sisa_update, $jatuh_tempo, $ada['id']]);
        $sukses = "Tempoan baru Rp " . number_format($nominal_baru, 0, ',', '.') . " berhasil digabung ke hutang " . htmlspecialchars($nama_supplier) . "!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO tempoan (supplier_id, total_hutang, sisa_hutang, jatuh_tempo, status) VALUES (?, ?, ?, ?, 'Belum Lunas')");
        $stmt->execute([$supplier_id, $nominal_baru, $nominal_baru, $jatuh_tempo]);
        $sukses = "Tempoan baru sebesar Rp " . number_format($nominal_baru, 0, ',', '.') . " berhasil dicatat!";
    }
}

// Pembayaran Cicilan
if (isset($_POST['bayar_cicilan'])) {
    $tempoan_id = $_POST['tempoan_id'];
    $nominal_cicil = (float)$_POST['nominal'];
    $tanggal = date('Y-m-d');

    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO cicilan_tempoan (tempoan_id, tanggal_bayar, nominal) VALUES (?, ?, ?)")
            ->execute([$tempoan_id, $tanggal, $nominal_cicil]);
            
        $pdo->prepare("UPDATE tempoan SET sisa_hutang = sisa_hutang - ? WHERE id = ?")
            ->execute([$nominal_cicil, $tempoan_id]);

        $cek_sisa = $pdo->prepare("SELECT sisa_hutang FROM tempoan WHERE id = ?");
        $cek_sisa->execute([$tempoan_id]);
        if ($cek_sisa->fetchColumn() <= 0) {
            $pdo->prepare("UPDATE tempoan SET status = 'Lunas', sisa_hutang = 0 WHERE id = ?")->execute([$tempoan_id]);
        }
        $pdo->commit();
        $sukses = "Cicilan Rp " . number_format($nominal_cicil, 0, ',', '.') . " berhasil dicatat!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Gagal memproses cicilan.";
    }
}

$tempoan = $pdo->query("SELECT t.*, s.nama_supplier FROM tempoan t JOIN supplier s ON t.supplier_id = s.id ORDER BY t.status ASC, t.jatuh_tempo ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tempoan Supplier - D-Boy Speedshop</title>
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
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-primary mb-0">Manajemen Tempoan / Hutang Supplier</h5>
                <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalTambahTempoan">
                    + Tambah Tempoan Baru
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Supplier</th>
                            <th>Total Hutang</th>
                            <th>Sisa Hutang</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Bayar Cicilan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($tempoan)): ?><tr><td colspan="6" class="text-center py-4 text-muted">Belum ada tempoan tercatat.</td></tr><?php endif; ?>
                        <?php foreach($tempoan as $t): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($t['nama_supplier']) ?></td>
                            <td>Rp <?= number_format($t['total_hutang'], 0, ',', '.') ?></td>
                            <td class="text-danger fw-bold">Rp <?= number_format($t['sisa_hutang'], 0, ',', '.') ?></td>
                            <td><?= date('d M Y', strtotime($t['jatuh_tempo'])) ?></td>
                            <td>
                                <?php if($t['status'] == 'Lunas'): ?>
                                    <span class="badge bg-success">Lunas</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Belum Lunas</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 250px;">
                                <?php if($t['status'] != 'Lunas'): ?>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="tempoan_id" value="<?= $t['id'] ?>">
                                    <input type="number" name="nominal" class="form-control form-control-sm" placeholder="Nominal Rp" required>
                                    <button type="submit" name="bayar_cicilan" class="btn btn-sm btn-success">Cicil</button>
                                </form>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-check-circle-fill text-success"></i> Lunas</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Tempoan -->
    <div class="modal fade" id="modalTambahTempoan" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Catat Tempoan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Supplier</label>
                        <input type="text" name="nama_supplier" class="form-control" placeholder="Ketik nama supplier (Contoh: Arumi Motoparts)" required autocomplete="off">
                        <small class="text-muted">Jika nama supplier ini sudah pernah dicatat & masih ada hutang aktif, sisa hutang otomatis digabungkan.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nominal Hutang (Rp)</label>
                        <input type="number" name="nominal" class="form-control" placeholder="Contoh: 15000000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Jatuh Tempo</label>
                        <input type="date" name="jatuh_tempo" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_tempoan" class="btn btn-primary w-100">Simpan Tempoan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'notifikasi.php'; ?>
</body>
</html>