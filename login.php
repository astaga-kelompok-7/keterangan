<?php
require 'koneksi.php';
require 'fungsi.php';

$error = '';
$pesan = '';

if (isset($_GET['pesan']) && $_GET['pesan'] === 'daftar') {
    $pesan = 'Pendaftaran berhasil. Silakan login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, nama, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (
            $user &&
            password_verify($password, $user['password']) &&
            in_array($user['role'], ['admin', 'pelanggan'], true)
        ) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin.php");
                exit;
            }

            if ($user['role'] === 'pelanggan') {
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <h1>🥡 Tahu Kriuk</h1>
    <h2>Masuk ke Akun</h2>
    <p>Silakan login sebagai admin atau pembeli.</p>

    <?php if ($pesan !== ''): ?>
        <p class="success">
            <?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            placeholder="Masukkan email"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukkan password"
            required
        >

        <button type="submit">Masuk</button>
    </form>

    <p>Belum punya akun?
        <a href="register.php">Daftar di sini</a>
    </p>
</div>

</body>
</html>
