<?php
require 'koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: index.php"); 
    exit; 
}
$role = $_SESSION['role'];

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$sql = "SELECT t.*, u.username FROM transaksi t JOIN users u ON t.kasir_id = u.id WHERE 1=1";
$params = [];

if ($start_date != '' && $end_date != '') {
    $sql .= " AND DATE(t.tanggal) BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
}

$sql .= " ORDER BY t.tanggal DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$riwayat = $stmt->fetchAll();

// Akumulasi total omset
$total_omset_tampil = 0;
foreach ($riwayat as $item) {
    $total_omset_tampil += (float)$item['total_harga'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Penjualan - D-Boy Speedshop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-1">Riwayat Transaksi Penjualan</h4>
                <p class="text-muted small mb-0">Kelola dan ekspor data transaksi ke laporan Excel maupun PDF resmi.</p>
            </div>
            <div class="text-end">
                <span class="badge bg-primary px-3 py-2 fs-6">
                    Total: Rp <?= number_format($total_omset_tampil, 0, ',', '.') ?> (<?= count($riwayat) ?> Transaksi)
                </span>
            </div>
        </div>

        <!-- Filter Kalender -->
        <div class="card card-custom p-3 mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" required>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-1"></i> Filter Tanggal</button>
                    <a href="riwayat.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                </div>
            </form>
        </div>

        <div class="card card-custom p-4">
            <div class="table-responsive">
                <table id="tabelRiwayat" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 18%;">No Nota</th>
                            <th class="text-center" style="width: 25%;">Tanggal & Waktu</th>
                            <th class="text-center" style="width: 18%;">Metode Bayar</th>
                            <th class="text-end" style="width: 22%;">Total Pembayaran</th>
                            <th class="text-center" style="width: 17%;">Kasir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($riwayat as $r): ?>
                        <tr>
                            <td class="text-center"><span class="badge bg-light text-dark border">#TRX-<?= sprintf('%05d', $r['id']) ?></span></td>
                            <td class="text-center"><?= date('d-m-Y H:i', strtotime($r['tanggal'])) ?></td>
                            <td class="text-center"><span class="badge bg-info-subtle text-primary border border-info-subtle px-2 py-1"><?= htmlspecialchars($r['metode_pembayaran'] ?? 'Tunai') ?></span></td>
                            <td class="text-end fw-bold text-success">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                            <td class="text-center fw-semibold"><?= ucfirst(htmlspecialchars($r['username'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Script DataTables & Ekspor PDF/Excel -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script>
        $(document).ready(function() {
            var periodeTeks = "<?= (!empty($start_date) && !empty($end_date)) ? 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' s/d ' . date('d/m/Y', strtotime($end_date)) : 'Semua Riwayat Transaksi' ?>";
            var grandTotalText = "Rp <?= number_format($total_omset_tampil, 0, ',', '.') ?>";

            $('#tabelRiwayat').DataTable({
                dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
                buttons: [
                    { 
                        extend: 'excelHtml5', 
                        className: 'btn btn-success btn-sm', 
                        text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                        title: 'Laporan_Penjualan_D-Boy_Speedshop'
                    },
                    { 
                        extend: 'pdfHtml5', 
                        className: 'btn btn-danger btn-sm', 
                        text: '<i class="bi bi-file-earmark-pdf"></i> Export PDF',
                        title: '', 
                        filename: 'Laporan_Penjualan_D-Boy_Speedshop',
                        pageSize: 'A4',
                        orientation: 'portrait',
                        customize: function(doc) {
                            // Margin kertas [kiri: 42, atas: 35, kanan: 42, bawah: 35]
                            // Lebar area cetak bersih A4 = 595.28 - 84 = 511 pt
                            doc.pageMargins = [42, 35, 42, 35];

                            // Kop Surat Resmi
                            doc.content.splice(0, 0, {
                                stack: [
                                    { text: 'D-BOY SPEEDSHOP', fontSize: 18, bold: true, color: '#0d47a1', alignment: 'center', margin: [0, 0, 0, 3] },
                                    { text: 'Spesialis Aksesoris & Perlengkapan Motor Premium', fontSize: 9, color: '#666666', alignment: 'center', margin: [0, 0, 0, 4] },
                                    { text: 'LAPORAN RIWAYAT TRANSAKSI PENJUALAN', fontSize: 11, bold: true, color: '#222222', alignment: 'center', margin: [0, 6, 0, 3] },
                                    { text: periodeTeks + ' | Dicetak: ' + new Date().toLocaleString('id-ID'), fontSize: 8, italics: true, color: '#777777', alignment: 'center' },
                                    {
                                        canvas: [{ type: 'line', x1: 0, y1: 8, x2: 511, y2: 8, lineWidth: 1.5, lineColor: '#0d47a1' }],
                                        margin: [0, 0, 0, 15]
                                    }
                                ]
                            });

                            // Konfigurasi Tabel Proporsional
                            for (var i = 0; i < doc.content.length; i++) {
                                if (doc.content[i].table !== undefined) {
                                    var tableObj = doc.content[i];

                                    // Lebar kolom diatur presisi total 495 pt (aman di dalam area 511 pt)
                                    // Kolom: [No Nota, Tanggal, Metode, Total, Kasir]
                                    tableObj.table.widths = [80, 125, 85, 115, 90];

                                    // Format Header Tabel
                                    var headerCols = tableObj.table.body[0];
                                    for (var h = 0; h < headerCols.length; h++) {
                                        headerCols[h].fillColor = '#0d47a1';
                                        headerCols[h].color = '#ffffff';
                                        headerCols[h].bold = true;
                                        headerCols[h].fontSize = 9;
                                    }
                                    headerCols[0].alignment = 'center'; // No Nota
                                    headerCols[1].alignment = 'center'; // Tanggal & Waktu
                                    headerCols[2].alignment = 'center'; // Metode Bayar
                                    headerCols[3].alignment = 'right';  // Total Pembayaran
                                    headerCols[4].alignment = 'center'; // Kasir (Tengah seimbang)

                                    // Format Baris Data
                                    for (var r = 1; r < tableObj.table.body.length; r++) {
                                        var row = tableObj.table.body[r];
                                        var bgColor = (r % 2 === 0) ? '#f8fafd' : '#ffffff';

                                        if (row[0]) row[0].alignment = 'center'; // No Nota
                                        if (row[1]) row[1].alignment = 'center'; // Tanggal
                                        if (row[2]) row[2].alignment = 'center'; // Metode Bayar
                                        if (row[3]) row[3].alignment = 'right';  // Total Rp
                                        if (row[4]) row[4].alignment = 'center'; // Kasir

                                        for (var c = 0; c < row.length; c++) {
                                            row[c].fillColor = bgColor;
                                            row[c].fontSize = 8.5;
                                        }
                                    }

                                    // Baris Total Penjualan
                                    tableObj.table.body.push([
                                        { text: 'TOTAL PENJUALAN', colSpan: 3, alignment: 'center', bold: true, fillColor: '#e3f2fd', color: '#0d47a1', fontSize: 9 },
                                        {},
                                        {},
                                        { text: grandTotalText, alignment: 'right', bold: true, fillColor: '#e3f2fd', color: '#0d47a1', fontSize: 9 },
                                        { text: '', fillColor: '#e3f2fd' }
                                    ]);

                                    // Border dan Padding Bersih
                                    tableObj.layout = {
                                        hLineWidth: function() { return 0.5; },
                                        vLineWidth: function() { return 0.5; },
                                        hLineColor: function() { return '#dcdcdc'; },
                                        vLineColor: function() { return '#dcdcdc'; },
                                        paddingLeft: function() { return 6; },
                                        paddingRight: function() { return 6; },
                                        paddingTop: function() { return 6; },
                                        paddingBottom: function() { return 6; }
                                    };
                                    break;
                                }
                            }
                        }
                    }
                ],
                order: [[1, 'desc']],
                language: {
                    search: "Cari Riwayat:",
                    lengthMenu: "Tampilkan _MENU_ transaksi",
                    paginate: { next: "Lanjut", previous: "Kembali" },
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ transaksi"
                }
            });
        });
    </script>
</body>
</html>