<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_admin();

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';

    if ($id > 0 && $aksi === 'konfirmasi_pembayaran') {

        mysqli_begin_transaction($conn);

        try {
            $stmt = mysqli_prepare($conn,
                "SELECT b.metode, b.status
                 FROM pembayaran b
                 JOIN pesanan p ON p.id = b.pesanan_id
                 WHERE b.pesanan_id = ?
                 FOR UPDATE"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $bayar = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );

            if (!$bayar) {
                throw new Exception("Pembayaran tidak ditemukan.");
            }

            if (!in_array($bayar['status'], [
                'Belum Dibayar',
                'Menunggu Pembayaran',
                'Menunggu Konfirmasi'
            ], true)) {
                throw new Exception("Pembayaran sudah diproses.");
            }

            if (
                in_array($bayar['metode'], ['QRIS', 'TRANSFER'], true)
                && $bayar['status'] !== 'Menunggu Konfirmasi'
            ) {
                throw new Exception("Bukti pembayaran belum dikirim.");
            }

            $stmt = mysqli_prepare($conn,
                "UPDATE pembayaran
                 SET status = 'Lunas'
                 WHERE pesanan_id = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Gagal mengonfirmasi pembayaran.");
            }

            mysqli_commit($conn);

            header("Location: admin.php?berhasil=1");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $pesan = $e->getMessage();
        }

    } elseif ($id > 0 && $aksi === 'ubah_pesanan') {

        $status = $_POST['status'] ?? '';

        $status_valid = [
            'Menunggu Konfirmasi',
            'Diproses',
            'Selesai',
            'Dibatalkan'
        ];

        if (in_array($status, $status_valid, true)) {

            $stmt = mysqli_prepare($conn,
                "UPDATE pesanan SET status = ? WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt, "si", $status, $id);
            mysqli_stmt_execute($stmt);

            header("Location: admin.php?berhasil=1");
            exit;
        }
    }
}

$sql = "
    SELECT
        p.*,
        u.nama,
        b.metode,
        b.status AS status_bayar,
        b.bukti_pembayaran
    FROM pesanan p
    JOIN users u ON u.id = p.user_id
    LEFT JOIN pembayaran b ON b.pesanan_id = p.id
    ORDER BY p.id DESC
";

$data = mysqli_query($conn, $sql);

if (!$data) {
    die("Gagal mengambil data: " . h(mysqli_error($conn)));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .payment-status {
            font-weight: bold;
        }

        .proof-image {
            width: 90px;
            border-radius: 8px;
            display: block;
            margin: 5px 0;
        }

        .confirm-button {
            background: #16834a;
            color: white;
            padding: 8px 12px;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }

        .table-wrapper {
            overflow-x: auto;
        }
    </style>
</head>
<body>

<header>
    <a class="logo" href="admin.php">🥡 Admin Tahu Kriuk</a>

    <nav>
        <a href="admin.php">Pesanan</a>
        <a href="admin_produk.php">Kelola Produk</a>
        <a href="produk.php">Lihat Produk</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>Pengelolaan Pesanan dan Pembayaran</h1>

    <p>Login sebagai: <?= h($_SESSION['nama']) ?></p>

    <?php if (isset($_GET['berhasil'])): ?>
        <p style="color:green">
            Data berhasil diperbarui.
        </p>
    <?php endif; ?>

    <?php if ($pesan): ?>
        <p style="color:red"><?= h($pesan) ?></p>
    <?php endif; ?>

    <div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Pelanggan</th>
                <th>Tanggal</th>
                <th>Total</th>
                <th>Metode</th>
                <th>Bukti</th>
                <th>Status Pembayaran</th>
                <th>Konfirmasi</th>
                <th>Status Pesanan</th>
            </tr>
        </thead>

        <tbody>

        <?php while ($p = mysqli_fetch_assoc($data)): ?>

            <tr>
                <td><?= (int)$p['id'] ?></td>

                <td><?= h($p['nama']) ?></td>

                <td><?= h($p['tanggal']) ?></td>

                <td><?= rupiah($p['total']) ?></td>

                <td><?= h($p['metode'] ?? '-') ?></td>

                <td>
                    <?php if (!empty($p['bukti_pembayaran'])): ?>
                        <a
                            href="<?= h($p['bukti_pembayaran']) ?>"
                            target="_blank"
                        >
                            Lihat Bukti
                        </a>
                    <?php else: ?>
                        Belum ada
                    <?php endif; ?>
                </td>

                <td class="payment-status">
                    <?= h($p['status_bayar'] ?? '-') ?>
                </td>

                <td>
                    <?php
                    $metode = strtoupper($p['metode'] ?? '');
                    $statusBayar = $p['status_bayar'] ?? '';

                    $siapKonfirmasi =
                        in_array($statusBayar, [
                            'Belum Dibayar',
                            'Menunggu Pembayaran',
                            'Menunggu Konfirmasi'
                        ], true)
                        &&
                        (
                            in_array($metode, ['TUNAI', 'COD'], true)
                            ||
                            (
                                in_array($metode, ['QRIS', 'TRANSFER'], true)
                                && $statusBayar === 'Menunggu Konfirmasi'
                                && !empty($p['bukti_pembayaran'])
                            )
                        );
                    ?>

                    <?php if ($siapKonfirmasi): ?>

                        <form method="POST"
                              onsubmit="return confirm('Konfirmasi pembayaran ini?')">

                            <input type="hidden" name="id"
                                   value="<?= (int)$p['id'] ?>">

                            <input type="hidden" name="aksi"
                                   value="konfirmasi_pembayaran">

                            <button class="confirm-button" type="submit">
                                Konfirmasi Lunas
                            </button>
                        </form>

                    <?php elseif ($statusBayar === 'Lunas'): ?>

                        <span style="color:green">Sudah Lunas</span>

                    <?php else: ?>

                        <span>Menunggu pembayaran</span>

                    <?php endif; ?>
                </td>

                <td>
                    <form method="POST">
                        <input type="hidden" name="id"
                               value="<?= (int)$p['id'] ?>">

                        <input type="hidden" name="aksi"
                               value="ubah_pesanan">

                        <select name="status">
                            <?php
                            $statuses = [
                                'Menunggu Konfirmasi',
                                'Diproses',
                                'Selesai',
                                'Dibatalkan'
                            ];

                            foreach ($statuses as $status):
                            ?>
                                <option value="<?= h($status) ?>"
                                    <?= $p['status'] === $status ? 'selected' : '' ?>>
                                    <?= h($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit">Simpan</button>
                    </form>
                </td>

            </tr>

        <?php endwhile; ?>

        </tbody>
    </table>
    </div>

</main>
</body>
</html>
