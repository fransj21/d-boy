<?php
require 'koneksi.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$role = $_SESSION['role'];

// Data Ringkasan
$total_stok = $pdo->query("SELECT SUM(stok) FROM produk")->fetchColumn() ?? 0;
$omset_hari_ini = $pdo->query("SELECT SUM(total_harga) FROM transaksi WHERE DATE(tanggal) = CURDATE()")->fetchColumn() ?? 0;
$transaksi_hari_ini = $pdo->query("SELECT COUNT(id) FROM transaksi WHERE DATE(tanggal) = CURDATE()")->fetchColumn() ?? 0;

// Data Widget
$stok_menipis = $pdo->query("SELECT * FROM produk WHERE stok < 5 ORDER BY stok ASC")->fetchAll();
$terlaris = $pdo->query("SELECT p.nama_produk, SUM(dt.qty) as total_terjual FROM detail_transaksi dt JOIN produk p ON dt.produk_id = p.id GROUP BY dt.produk_id ORDER BY total_terjual DESC LIMIT 5")->fetchAll();
$terbaru = $pdo->query("SELECT t.*, u.username FROM transaksi t JOIN users u ON t.kasir_id = u.id ORDER BY t.tanggal DESC LIMIT 5")->fetchAll();

// Query Realtime Grafik Omset 7 Hari Terakhir
$chart_labels = [];
$chart_data = [];
$hari_indo = [
    'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 
    'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'
];

for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $nama_hari = $hari_indo[date('D', strtotime($tgl))];
    $label = $nama_hari . ' (' . date('d/m', strtotime($tgl)) . ')';

    $stmt_chart = $pdo->prepare("SELECT SUM(total_harga) FROM transaksi WHERE DATE(tanggal) = ?");
    $stmt_chart->execute([$tgl]);
    $omset_tgl = $stmt_chart->fetchColumn() ?? 0;

    $chart_labels[] = $label;
    $chart_data[] = (float)$omset_tgl;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - D-Boy Speedshop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary-blue: #0d47a1; --bg-body: #f4f7fb; }
        body { background-color: var(--bg-body); font-family: 'Segoe UI', Tahoma, sans-serif; overflow-x: hidden; }
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: linear-gradient(180deg, var(--primary-blue) 0%, #001f54 100%); transition: all 0.3s; }
        #sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 4px 15px; font-weight: 500; }
        #sidebar .nav-link:hover, #sidebar .nav-link.active { background: rgba(255,255,255,0.18); color: #fff; font-weight: 600; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); background: #fff; }
        @media (max-width: 768px) {
            #sidebar { margin-left: -260px; position: fixed; z-index: 999; }
            #sidebar.active { margin-left: 0; }
        }
    </style>
</head>
<body class="d-flex">

    <?php include 'sidebar.php'; ?>

    <div id="content-wrapper" class="flex-grow-1 p-4" style="min-height: 100vh; overflow-y: auto;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-1">Dashboard Analitik</h4>
                <p class="text-muted small mb-0">Selamat Datang, <strong><?= ucfirst($_SESSION['username']) ?></strong> (<?= ucfirst($role) ?>)</p>
            </div>
            <button id="sidebarToggle" class="btn btn-primary d-md-none"><i class="bi bi-list"></i></button>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-custom p-4 border-start border-primary border-4">
                    <p class="text-muted mb-1 fw-bold">Omset Hari Ini</p>
                    <h3 class="fw-bold text-primary mb-0">Rp <?= number_format($omset_hari_ini, 0, ',', '.') ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom p-4 border-start border-success border-4">
                    <p class="text-muted mb-1 fw-bold">Transaksi Hari Ini</p>
                    <h3 class="fw-bold text-success mb-0"><?= number_format($transaksi_hari_ini, 0, ',', '.') ?> Nota</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom p-4 border-start border-warning border-4">
                    <p class="text-muted mb-1 fw-bold">Total Stok Barang</p>
                    <h3 class="fw-bold text-warning mb-0"><?= number_format($total_stok, 0, ',', '.') ?> Pcs</h3>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Grafik Realtime -->
            <div class="col-lg-8">
                <div class="card card-custom p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold text-secondary mb-0">Grafik Penjualan 7 Hari Terakhir (Realtime)</h6>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-circle-fill fs-6 me-1"></i> Live</span>
                    </div>
                    <canvas id="salesChart" height="110"></canvas>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-custom p-4 h-100">
                    <h6 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Stok Menipis (< 5)</h6>
                    <ul class="list-group list-group-flush">
                        <?php foreach($stok_menipis as $sm): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-truncate" style="max-width: 170px;"><?= htmlspecialchars($sm['nama_produk']) ?></span>
                            <span class="badge bg-danger rounded-pill"><?= $sm['stok'] ?> Pcs</span>
                        </li>
                        <?php endforeach; if(empty($stok_menipis)) echo "<li class='list-group-item text-muted px-0'>Semua stok dalam kondisi aman.</li>"; ?>
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom p-4">
                    <h6 class="fw-bold text-primary mb-3">🔥 5 Produk Terlaris</h6>
                    <table class="table table-borderless table-hover mb-0">
                        <tbody>
                            <?php foreach($terlaris as $tl): ?>
                            <tr>
                                <td><?= htmlspecialchars($tl['nama_produk']) ?></td>
                                <td class="text-end fw-bold text-success"><?= number_format($tl['total_terjual'], 0, ',', '.') ?> Terjual</td>
                            </tr>
                            <?php endforeach; if(empty($terlaris)) echo "<tr><td class='text-muted'>Belum ada penjualan.</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom p-4">
                    <h6 class="fw-bold text-primary mb-3">🕒 Transaksi Terbaru</h6>
                    <table class="table table-borderless table-hover mb-0">
                        <tbody>
                            <?php foreach($terbaru as $tb): ?>
                            <tr>
                                <td><small class="text-muted"><?= date('H:i, d/m/Y', strtotime($tb['tanggal'])) ?></small></td>
                                <td>Kasir: <?= htmlspecialchars($tb['username']) ?></td>
                                <td class="text-end fw-bold text-primary">Rp <?= number_format($tb['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; if(empty($terbaru)) echo "<tr><td class='text-muted'>Belum ada transaksi.</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        if(sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
            });
        }

        // Data Chart Otomatis dari PHP
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Omset',
                    data: <?= json_encode($chart_data) ?>,
                    borderColor: '#0d47a1',
                    backgroundColor: 'rgba(13, 71, 161, 0.08)',
                    borderWidth: 3,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#0d47a1',
                    pointRadius: 4
                }]
            },
            options: { 
                responsive: true,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Omset: Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>