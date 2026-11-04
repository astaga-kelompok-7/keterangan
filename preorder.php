<?php
require 'koneksi.php';
require 'fungsi.php';

wajib_login();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preorder - Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <a class="logo" href="dashboard.php">🥡 Tahu Kriuk</a>
    <nav>
        <a href="dashboard.php">Beranda</a>
        <a href="produk.php">Produk</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="riwayat_pesanan.php">Riwayat Pesanan</a>
    </nav>
</header>

<main class="container">
    <h1>Preorder Tahu Kriuk</h1>

    <p>
        Pesan Tahu Kriuk terlebih dahulu dan lakukan pembayaran
        untuk menyelesaikan preorder kamu.
    </p>

    <h3>Langkah Preorder</h3>

    <ol>
        <li>Pilih produk Tahu Kriuk yang ingin dipesan.</li>
        <li>Tentukan jumlah produk dan tambahkan ke keranjang.</li>
        <li>Buka keranjang dan lanjutkan ke checkout.</li>
        <li>Isi alamat serta catatan pesanan.</li>
        <li>Pilih metode pembayaran dan selesaikan pesanan.</li>
    </ol>

    <a class="button" href="produk.php">
        Pilih Produk dan Preorder
    </a>

    <p>
        Sudah memilih produk?
        <a href="keranjang.php">Lanjut ke Keranjang</a>
    </p>
</main>

</body>
</html>