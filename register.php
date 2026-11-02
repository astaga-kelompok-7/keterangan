<?php
require 'koneksi.php';
require 'fungsi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi_password) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $cek = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param($cek, "s", $email);
        mysqli_stmt_execute($cek);
        $hasil = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasil) > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (nama, email, password, role)
                 VALUES (?, ?, ?, 'pelanggan')"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $nama,
                $email,
                $password_hash
            );

            if (mysqli_stmt_execute($stmt)) {
                header('Location: login.php?pesan=daftar');
                exit;
            } else {
                $error = 'Pendaftaran gagal. Silakan coba lagi.';
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($cek);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <h1>🥡 Tahu Kriuk</h1>
    <h2>Buat Akun Baru</h2>

    <?php if ($error !== ''): ?>
        <p class="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <label>Nama Lengkap</label>
        <input
            type="text"
            name="nama"
            value="<?= htmlspecialchars($_POST['nama'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <label>Email</label>
        <input
            type="email"
            name="email"
            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            minlength="6"
            required
        >

        <label>Konfirmasi Password</label>
        <input
            type="password"
            name="konfirmasi_password"
            minlength="6"
            required
        >

        <button type="submit">Daftar</button>
    </form>

    <p>
        Sudah punya akun?
        <a href="login.php">Login di sini</a>
    </p>
</div>

</body>
</html>