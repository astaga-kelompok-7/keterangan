<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

$id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT p.*, b.status AS status_bayar, b.metode
     FROM pesanan p
     JOIN pembayaran b ON b.pesanan_id = p.id
     WHERE p.id = ? AND p.user_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$pesanan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pesanan) {
    http_response_code(404);
    exit("Pesanan tidak ditemukan.");
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT d.*, pr.nama_produk
     FROM detail_pesanan d
     JOIN produk pr ON pr.id = d.produk_id
     WHERE d.pesanan_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$detail = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Pembayaran</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><a class="logo" href="dashboard.php">🥡 Tahu Kriuk</a></header>
<main class="container">
    <h1>Detail Pesanan #<?= (int)$pesanan['id'] ?></h1>
    <p>Jenis: <?= h($pesanan['jenis']) ?></p>
    <p>Status pesanan: <?= h($pesanan['status']) ?></p>
    <p>Metode pembayaran: <?= h($pesanan['metode']) ?></p>
    <p>Status pembayaran: <?= h($pesanan['status_bayar']) ?></p>

    <h2>Rincian Produk</h2>
    <table>
        <tr><th>Produk</th><th>Jumlah</th><th>Subtotal</th></tr>
        <?php while ($d = mysqli_fetch_assoc($detail)): ?>
        <tr>
            <td><?= h($d['nama_produk']) ?></td>
            <td><?= (int)$d['jumlah'] ?></td>
            <td><?= rupiah($d['subtotal']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <h2>Total: <?= rupiah($pesanan['total']) ?></h2>
    <p>Alamat: <?= nl2br(h($pesanan['alamat'])) ?></p>
    <a class="button" href="riwayat_pesanan.php">Lihat Riwayat</a>
</main>
</body>
</html>
