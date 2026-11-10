<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($conn,
    "SELECT
        p.id,
        p.tanggal,
        p.jenis,
        p.total,
        p.status,
        b.metode,
        b.status AS status_bayar
     FROM pesanan p
     LEFT JOIN pembayaran b ON b.pesanan_id = p.id
     WHERE p.user_id = ?
     ORDER BY p.id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$data = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Pesanan Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <a class="logo" href="dashboard.php">🥡 Tahu Kriuk</a>
</header>

<main class="container">

    <h1>Riwayat Pesanan Saya</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Total</th>
                <th>Metode Pembayaran</th>
                <th>Status Pembayaran</th>
                <th>Status Pesanan</th>
                <th>Detail</th>
            </tr>
        </thead>

        <tbody>

        <?php while ($p = mysqli_fetch_assoc($data)): ?>

            <tr>
                <td><?= (int)$p['id'] ?></td>
                <td><?= h($p['tanggal']) ?></td>
                <td><?= h($p['jenis']) ?></td>
                <td><?= rupiah($p['total']) ?></td>
                <td><?= h($p['metode'] ?? '-') ?></td>
                <td><?= h($p['status_bayar'] ?? '-') ?></td>
                <td><?= h($p['status']) ?></td>

                <td>
                    <a href="pembayaran.php?id=<?= (int)$p['id'] ?>">
                        Lihat Detail
                    </a>
                </td>
            </tr>

        <?php endwhile; ?>

        </tbody>
    </table>

    <p>
        <a class="button" href="produk.php">Pesan Lagi</a>
    </p>

</main>
</body>
</html>
