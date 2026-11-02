<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $status_valid = [
        'Menunggu Konfirmasi',
        'Diproses',
        'Selesai',
        'Dibatalkan'
    ];

    if ($id > 0 && in_array($status, $status_valid, true)) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE pesanan SET status = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        mysqli_stmt_execute($stmt);
    }

    header("Location: admin.php");
    exit;
}

$sql = "SELECT p.*, u.nama
        FROM pesanan p
        JOIN users u ON u.id = p.user_id
        ORDER BY p.id DESC";
$data = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <a class="logo" href="admin.php">🥡 Admin Tahu Kriuk</a>
    <nav>
        <a href="admin.php">Pesanan</a>
        <a href="produk.php">Lihat Produk</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">
    <h1>Kelola Pesanan</h1>
    <p>Login sebagai: <?= h($_SESSION['nama']) ?></p>

    <table>
        <tr>
            <th>ID</th><th>Pelanggan</th><th>Tanggal</th>
            <th>Total</th><th>Pembayaran</th><th>Status</th><th>Aksi</th>
        </tr>
        <?php while ($p = mysqli_fetch_assoc($data)): ?>
        <tr>
            <td><?= (int)$p['id'] ?></td>
            <td><?= h($p['nama']) ?></td>
            <td><?= h($p['tanggal']) ?></td>
            <td><?= rupiah($p['total']) ?></td>
            <td><?= h($p['metode_pembayaran']) ?></td>
            <td><?= h($p['status']) ?></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <select name="status" required>
                        <option value="Menunggu Konfirmasi">Menunggu Konfirmasi</option>
                        <option value="Diproses">Diproses</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Dibatalkan">Dibatalkan</option>
                    </select>
                    <button type="submit">Ubah Status</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</main>
</body>
</html>
