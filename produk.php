<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

$produk = mysqli_query($conn, "SELECT * FROM produk WHERE aktif = 1 ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk - Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <a class="logo" href="dashboard.php">🥡 Tahu Kriuk</a>
    <nav>
        <a href="dashboard.php">Beranda</a>
        <a href="produk.php">Produk</a>
        <a href="preorder.php">Preorder</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="riwayat_pesanan.php">Riwayat</a>
        <a href="detail_akun.php">Akun</a>
    </nav>
</header>

<main class="container">
    <h1>Semua Produk</h1>
    <?php if (isset($_GET['pesan'])): ?>
        <p class="success">Produk ditambahkan ke keranjang.</p>
    <?php endif; ?>

    <div class="grid">
    <?php while ($p = mysqli_fetch_assoc($produk)): ?>
        <article class="card">
            <div class="food">🥡</div>
            <h2><?= h($p['nama_produk']) ?></h2>
            <p><?= h($p['deskripsi']) ?></p>
            <strong><?= rupiah($p['harga']) ?></strong>
            <p>Stok: <?= (int)$p['stok'] ?></p>

            <?php if ($p['stok'] > 0): ?>
            <form action="keranjang.php" method="POST">
                <input type="hidden" name="produk_id" value="<?= (int)$p['id'] ?>">
                <label>Jumlah</label>
                <input type="number" name="jumlah" min="1"
                    max="<?= (int)$p['stok'] ?>" value="1" required>
                <button type="submit" name="tambah">Tambah ke Keranjang</button>
            </form>
            <?php else: ?>
                <p>Stok habis.</p>
            <?php endif; ?>
        </article>
    <?php endwhile; ?>
    </div>
</main>
</body>
</html>
