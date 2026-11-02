<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

if (isset($_POST['tambah'])) {
    $id = (int)($_POST['produk_id'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 1);

    $stmt = mysqli_prepare($conn, "SELECT stok FROM produk WHERE id = ? AND aktif = 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($p && $jumlah > 0 && $jumlah <= (int)$p['stok']) {
        $baru = ($_SESSION['keranjang'][$id] ?? 0) + $jumlah;

        if ($baru <= (int)$p['stok']) {
            $_SESSION['keranjang'][$id] = $baru;
        }
    }

    header("Location: keranjang.php");
    exit;
}

if (isset($_POST['hapus'])) {
    $id = (int)$_POST['hapus'];
    unset($_SESSION['keranjang'][$id]);
    header("Location: keranjang.php");
    exit;
}

$items = [];
$total = 0;

foreach ($_SESSION['keranjang'] as $id => $jumlah) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM produk WHERE id = ? AND aktif = 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$p || $jumlah > (int)$p['stok']) {
        unset($_SESSION['keranjang'][$id]);
        continue;
    }

    $p['jumlah_pilih'] = $jumlah;
    $p['subtotal'] = $p['harga'] * $jumlah;
    $total += $p['subtotal'];
    $items[] = $p;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keranjang - Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><a class="logo" href="dashboard.php">🥡 Tahu Kriuk</a>
<nav><a href="dashboard.php">Beranda</a><a href="produk.php">Produk</a><a href="preorder.php">Preorder</a><a href="keranjang.php">Keranjang</a><a href="riwayat_pesanan.php">Riwayat</a></nav>
</header>

<main class="container">
    <h1>Keranjang Belanja</h1>

    <?php if (!$items): ?>
        <p>Keranjangmu masih kosong.</p>
        <a class="button" href="produk.php">Belanja Sekarang</a>
    <?php else: ?>
        <table>
            <tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th>Aksi</th></tr>
            <?php foreach ($items as $p): ?>
            <tr>
                <td><?= h($p['nama_produk']) ?></td>
                <td><?= rupiah($p['harga']) ?></td>
                <td><?= (int)$p['jumlah_pilih'] ?></td>
                <td><?= rupiah($p['subtotal']) ?></td>
                <td>
                    <form method="POST">
                        <button name="hapus" value="<?= (int)$p['id'] ?>">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>Total: <?= rupiah($total) ?></h2>
        <a class="button" href="checkout.php">Lanjut Checkout</a>
    <?php endif; ?>
</main>
</body>
</html>
