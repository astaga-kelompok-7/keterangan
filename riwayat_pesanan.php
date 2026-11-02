<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

$id = (int)$_SESSION['user_id'];
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, tanggal, jenis, total, status
     FROM pesanan WHERE user_id = ? ORDER BY id DESC"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Pesanan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><a class="logo" href="dashboard.php">🥡 Tahu Kriuk</a></header>
<main class="container">
    <h1>Riwayat Pesanan Saya</h1>
    <table>
        <tr><th>No.</th><th>Tanggal</th><th>Jenis</th><th>Total</th><th>Status</th><th>Detail</th></tr>
        <?php while ($p = mysqli_fetch_assoc($data)): ?>
        <tr>
            <td><?= (int)$p['id'] ?></td>
            <td><?= h($p['tanggal']) ?></td>
            <td><?= h($p['jenis']) ?></td>
            <td><?= rupiah($p['total']) ?></td>
            <td><?= h($p['status']) ?></td>
            <td><a href="detail_pembayaran.php?id=<?= (int)$p['id'] ?>">Lihat</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <p><a class="button" href="produk.php">Pesan Lagi</a></p>
</main>
</body>
</html>
