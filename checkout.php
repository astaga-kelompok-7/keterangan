<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

$keranjang = $_SESSION['keranjang'] ?? [];

if (empty($keranjang)) {
    echo '<h1>Checkout Pesanan</h1>';
    echo '<p>Keranjang kamu masih kosong.</p>';
    echo '<a href="produk.php">Pilih Produk</a>';
    exit;
}

$items = [];
$total = 0;

foreach ($keranjang as $id => $jumlah) {
    $id = (int)$id;
    $jumlah = (int)$jumlah;

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, nama_produk, harga, stok
         FROM produk
         WHERE id = ? AND aktif = 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $p = mysqli_fetch_assoc($result);

    if (!$p || $jumlah < 1 || $jumlah > (int)$p['stok']) {
        continue;
    }

    $p['jumlah_pilih'] = $jumlah;
    $p['subtotal'] = $p['harga'] * $jumlah;
    $total += $p['subtotal'];
    $items[] = $p;
}

if (empty($items)) {
    echo '<h1>Checkout Pesanan</h1>';
    echo '<p>Produk tidak ditemukan atau stok tidak mencukupi.</p>';
    echo '<a href="keranjang.php">Kembali ke Keranjang</a>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - Tahu Kriuk</title>
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

    <?php if (empty($items)): ?>
        <p>Keranjang kamu masih kosong.</p>
        <a class="button" href="produk.php">Pilih Produk</a>
    <?php else: ?>

        <h3>Ringkasan Pesanan</h3>

        <?php foreach ($items as $item): ?>
            <p>
                <?= h($item['nama_produk']) ?> ×
                <?= (int)$item['jumlah_pilih'] ?> —
                <?= rupiah($item['subtotal']) ?>
            </p>
        <?php endforeach; ?>

        <h3>Total: <?= rupiah($total) ?></h3>

        <form action="proses_pesanan.php" method="POST">
            <label for="jenis">Jenis Pesanan</label>
            <select id="jenis" name="jenis" required>
                <option value="">-- Pilih jenis pesanan --</option>
                <option value="Reguler">Reguler</option>
                <option value="Preorder">Preorder</option>
            </select>


            <label>Alamat</label>
            <textarea name="alamat" required></textarea>

            <label for="metode_pembayaran">Metode Pembayaran</label>
            <select id="metode_pembayaran" name="metode_pembayaran" required>
                <option value="">-- Pilih metode pembayaran --</option>
                <option value="COD">COD</option>
                <option value="Transfer">Transfer Bank</option>
                <option value="QRIS">QRIS</option>
            </select>
 
            <label>Catatan Pesanan</label>
            <textarea name="catatan"></textarea>

            <button type="submit">Buat Pesanan dan Lanjut Pembayaran</button>
        </form>

    <?php endif; ?>
</main>

</body>
</html>
