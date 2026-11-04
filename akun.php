<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

$id = (int)$_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT nama, email, role, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    exit("Akun tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Akun</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><a class="logo" href="dashboard.php">🥡 Tahu Kriuk</a></header>
<main class="container">
    <h1>Detail Akun Saya</h1>
    <div class="card">
        <p>Nama: <?= h($user['nama']) ?></p>
        <p>Email: <?= h($user['email']) ?></p>
        <p>Jenis akun: <?= h($user['role']) ?></p>
        <p>Terdaftar: <?= h($user['created_at']) ?></p>
    </div>
    <a class="button" href="dashboard.php">Kembali ke Beranda</a>
</main>
</body>
</html>
