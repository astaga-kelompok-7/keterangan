<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

$result = mysqli_query(
    $conn,
    "SELECT * FROM produk WHERE aktif = 1 ORDER BY id DESC LIMIT 3"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beranda - Tahu Kriuk</title>
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
        <a href="akun.php">Akun</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <section class="hero">
        <p>SELAMAT DATANG, <?= h($_SESSION['nama']) ?>!</p>
        <h1>Gurih, Renyah, dan Bikin Nagih!</h1>
        <p>Nikmati camilan tahu kriuk favoritmu.</p>
        <a class="button" href="produk.php">Lihat Semua Produk</a>
    </section>

    <section class="container">
        <h2>Menu Favorit</h2>
        <div class="grid">
        <?php while ($p = mysqli_fetch_assoc($result)): ?>
            <article class="card">
                <div class="food">🥡</div>
                <h3><?= h($p['nama_produk']) ?></h3>
                <p><?= h($p['deskripsi']) ?></p>
                <strong><?= rupiah($p['harga']) ?></strong>
                <p><a class="button" href="produk.php">Lihat Produk</a></p>
            </article>
        <?php endwhile; ?>
        </div>
    </section>
</main>
<footer>© 2026 Tahu Kriuk</footer>
</body>
</html>
