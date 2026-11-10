<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

$keranjang = $_SESSION['keranjang'] ?? [];

if (empty($keranjang)) {
    header("Location: keranjang.php");
    exit;
}

$items = [];
$total = 0;

foreach ($keranjang as $id => $jumlah) {
    $id = (int)$id;
    $jumlah = (int)$jumlah;

    $stmt = mysqli_prepare($conn,
        "SELECT id, nama_produk, harga, stok
         FROM produk WHERE id = ? AND aktif = 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$p || $jumlah < 1 || $jumlah > (int)$p['stok']) {
        continue;
    }

    $p['jumlah_pilih'] = $jumlah;
    $p['subtotal'] = (float)$p['harga'] * $jumlah;
    $total += $p['subtotal'];
    $items[] = $p;
}

if (!$items) {
    exit("Produk kosong atau stok tidak mencukupi.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <a class="logo" href="dashboard.php">🥡 Tahu Kriuk</a>
    <nav>
        <a href="dashboard.php">Beranda</a>
        <a href="produk.php">Produk</a>
        <a href="keranjang.php">Keranjang</a>
    </nav>
</header>

<main class="container">
    <h1>Checkout Pesanan</h1>

    <h3>Ringkasan Pesanan</h3>

    <?php foreach ($items as $item): ?>
        <p>
            <?= h($item['nama_produk']) ?>
            × <?= (int)$item['jumlah_pilih'] ?>
            = <?= rupiah($item['subtotal']) ?>
        </p>
    <?php endforeach; ?>

    <h2>Total: <?= rupiah($total) ?></h2>

    <form action="proses_pesanan.php" method="POST">

        <label>Jenis Pesanan</label>
        <select name="jenis" required>
            <option value="">Pilih jenis pesanan</option>
            <option value="Reguler">Reguler</option>
            <option value="Preorder">Preorder</option>
        </select>

        <label>Alamat</label>
        <textarea name="alamat" required></textarea>

        <label>Metode Pembayaran</label>
        <select name="metode_pembayaran" required>
            <option value="">Pilih pembayaran</option>
            <option value="TUNAI">Tunai</option>
            <option value="QRIS">QRIS</option>
            <option value="TRANSFER">Transfer Bank</option>
        </select>

        <label>Catatan Pesanan</label>
        <textarea name="catatan"></textarea>

        <button type="submit">Buat Pesanan</button>
    </form>
</main>
</body>
</html>
