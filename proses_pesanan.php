<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

$user_id = (int)$_SESSION['user_id'];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_rupiah($angka) {
    return 'Rp' . number_format((float)$angka, 0, ',', '.');
}

/* =====================================
   BAGIAN 1: MEMBUAT PESANAN
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jenis'])) {

    $jenis = $_POST['jenis'];
    $alamat = trim($_POST['alamat'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');
    $metode = strtoupper(trim($_POST['metode_pembayaran'] ?? ''));

    if (!in_array($jenis, ['Reguler', 'Preorder'], true)) {
        exit("Jenis pesanan tidak valid.");
    }

    if ($alamat === '') {
        exit("Alamat wajib diisi.");
    }

    if (!in_array($metode, ['TUNAI', 'QRIS', 'TRANSFER'], true)) {
        exit("Metode pembayaran tidak valid.");
    }

    $keranjang = $_SESSION['keranjang'] ?? [];

    if (empty($keranjang)) {
        exit("Keranjang kosong.");
    }

    mysqli_begin_transaction($conn);

    try {
        $items = [];
        $total = 0;

        foreach ($keranjang as $id => $jumlah) {

            $id = (int)$id;
            $jumlah = (int)$jumlah;

            if ($jumlah < 1) {
                throw new Exception("Jumlah produk tidak valid.");
            }

            $stmt = mysqli_prepare($conn,
                "SELECT id, nama_produk, harga, stok
                 FROM produk
                 WHERE id = ? AND aktif = 1
                 FOR UPDATE"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $produk = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );

            if (!$produk || $jumlah > (int)$produk['stok']) {
                throw new Exception(
                    "Stok produk " . ($produk['nama_produk'] ?? '') .
                    " tidak mencukupi."
                );
            }

            $subtotal = (float)$produk['harga'] * $jumlah;
            $total += $subtotal;

            $items[] = [
                'id' => $id,
                'jumlah' => $jumlah,
                'harga' => $produk['harga'],
                'subtotal' => $subtotal
            ];
        }

        // Status pesanan awal
        $status_pesanan = 'Menunggu Konfirmasi';

        $stmt = mysqli_prepare($conn,
            "INSERT INTO pesanan
            (user_id, jenis, alamat, catatan,
             metode_pembayaran, total, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "issssds",
            $user_id,
            $jenis,
            $alamat,
            $catatan,
            $metode,
            $total,
            $status_pesanan
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal membuat pesanan.");
        }

        $pesanan_id = mysqli_insert_id($conn);

        // Simpan detail produk
        foreach ($items as $item) {

            $stmt = mysqli_prepare($conn,
                "INSERT INTO detail_pesanan
                (pesanan_id, produk_id, jumlah, harga, subtotal)
                VALUES (?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iiidd",
                $pesanan_id,
                $item['id'],
                $item['jumlah'],
                $item['harga'],
                $item['subtotal']
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Gagal menyimpan detail pesanan.");
            }

            // Kurangi stok
            $stmt = mysqli_prepare($conn,
                "UPDATE produk
                 SET stok = stok - ?
                 WHERE id = ? AND stok >= ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iii",
                $item['jumlah'],
                $item['id'],
                $item['jumlah']
            );

            if (!mysqli_stmt_execute($stmt) ||
                mysqli_stmt_affected_rows($stmt) !== 1) {
                throw new Exception("Gagal memperbarui stok.");
            }
        }

        // Simpan pembayaran
        $status_bayar = ($metode === 'TUNAI')
            ? 'Belum Dibayar'
            : 'Menunggu Pembayaran';

        $stmt = mysqli_prepare($conn,
            "INSERT INTO pembayaran
            (pesanan_id, metode, status, bukti_pembayaran)
            VALUES (?, ?, ?, '')"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "iss",
            $pesanan_id,
            $metode,
            $status_bayar
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal mencatat pembayaran.");
        }

        mysqli_commit($conn);

        unset($_SESSION['keranjang']);

        header("Location: pembayaran.php?id=" . $pesanan_id);
        exit;

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        exit("Pesanan gagal dibuat: " . e($e->getMessage()));
    }
}

/* =====================================
   BAGIAN 2: UNGGAH BUKTI PEMBAYARAN
===================================== */

$pesanan_id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn,
    "SELECT p.*, b.status AS status_bayar,
            b.metode, b.bukti_pembayaran
     FROM pesanan p
     JOIN pembayaran b ON b.pesanan_id = p.id
     WHERE p.id = ? AND p.user_id = ?"
);

mysqli_stmt_bind_param($stmt, "ii", $pesanan_id, $user_id);
mysqli_stmt_execute($stmt);

$pesanan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pesanan) {
    http_response_code(404);
    exit("Pesanan tidak ditemukan.");
}

$error = '';

$metode = strtoupper($pesanan['metode']);
$status_bayar = $pesanan['status_bayar'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti'])) {

    if (!in_array($metode, ['QRIS', 'TRANSFER'], true)) {
        $error = "Metode pembayaran ini tidak memerlukan bukti transfer.";
    } elseif (!in_array($status_bayar, [
        'Menunggu Pembayaran',
        'Belum Dibayar'
    ], true)) {
        $error = "Pembayaran sudah dikirim atau sedang diperiksa.";
    } elseif ($_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
        $error = "Gagal mengunggah bukti pembayaran.";
    } elseif ($_FILES['bukti']['size'] > 5 * 1024 * 1024) {
        $error = "Ukuran gambar maksimal 5 MB.";
    } else {

        $file = $_FILES['bukti'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        $ekstensi = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($ekstensi[$mime])) {
            $error = "Format gambar harus JPG, PNG, atau WEBP.";
        } else {

            $folder = __DIR__ . '/uploads/bukti_pembayaran';

            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }

            $nama_file = bin2hex(random_bytes(16)) .
                '.' . $ekstensi[$mime];

            $tujuan = $folder . '/' . $nama_file;

            if (move_uploaded_file($file['tmp_name'], $tujuan)) {

                $path = 'uploads/bukti_pembayaran/' . $nama_file;

                mysqli_begin_transaction($conn);

                try {
                    $stmt = mysqli_prepare($conn,
                        "UPDATE pembayaran
                         SET bukti_pembayaran = ?,
                             status = 'Menunggu Konfirmasi'
                         WHERE pesanan_id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt, "si", $path, $pesanan_id
                    );

                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Gagal menyimpan bukti.");
                    }

                    $stmt = mysqli_prepare($conn,
                        "UPDATE pesanan
                         SET status = 'Menunggu Konfirmasi'
                         WHERE id = ? AND user_id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt, "ii", $pesanan_id, $user_id
                    );

                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Gagal memperbarui pesanan.");
                    }

                    mysqli_commit($conn);

                    header("Location: pembayaran.php?id=" .
                        $pesanan_id . "&terkirim=1");
                    exit;

                } catch (Throwable $e) {
                    mysqli_rollback($conn);
                    if (is_file($tujuan)) {
                        unlink($tujuan);
                    }
                    $error = $e->getMessage();
                }

            } else {
                $error = "Gagal menyimpan gambar.";
            }
        }
    }
}

$stmt = mysqli_prepare($conn,
    "SELECT d.*, pr.nama_produk
     FROM detail_pesanan d
     JOIN produk pr ON pr.id = d.produk_id
     WHERE d.pesanan_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $pesanan_id);
mysqli_stmt_execute($stmt);
$detail = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <a class="logo" href="dashboard.php">🥡 Tahu Kriuk</a>
</header>

<main class="container">

    <h1>Pembayaran Pesanan #<?= $pesanan_id ?></h1>

    <?php if ($error): ?>
        <p style="color:red"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['terkirim'])): ?>
        <p style="color:green">
            Bukti pembayaran berhasil dikirim. Tunggu konfirmasi admin.
        </p>
    <?php endif; ?>

    <p>Metode: <strong><?= e($metode) ?></strong></p>
    <p>Total: <strong><?= format_rupiah($pesanan['total']) ?></strong></p>
    <p>Status pembayaran: <strong><?= e($status_bayar) ?></strong></p>

    <h2>Rincian Produk</h2>

    <table>
        <tr>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Subtotal</th>
        </tr>

        <?php while ($d = mysqli_fetch_assoc($detail)): ?>
        <tr>
            <td><?= e($d['nama_produk']) ?></td>
            <td><?= (int)$d['jumlah'] ?></td>
            <td><?= format_rupiah($d['subtotal']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <section style="margin-top:25px">

        <?php if ($metode === 'QRIS'): ?>

            <h2>Bayar Menggunakan QRIS</h2>

            <?php if (is_file(__DIR__ . '/images/qris.png')): ?>
                <img
                    src="images/qris.png"
                    alt="QRIS Tahu Kriuk"
                    style="max-width:300px;width:100%"
                >
            <?php else: ?>
                <p>Gambar QRIS belum tersedia.</p>
            <?php endif; ?>

            <p>Silakan scan QRIS resmi toko.</p>
            <p>Nominal: <?= format_rupiah($pesanan['total']) ?></p>

        <?php elseif ($metode === 'TRANSFER'): ?>

            <h2>Transfer Bank</h2>
            <p>Silakan transfer sesuai nominal pesanan ke rekening resmi toko.</p>
            <p>Nominal: <?= format_rupiah($pesanan['total']) ?></p>

        <?php elseif (in_array($metode, ['TUNAI', 'COD'], true)): ?>

            <h2>Pembayaran Tunai</h2>
            <p>Pembayaran dilakukan secara tunai kepada petugas saat pesanan diterima.</p>
            <p>Status pembayaran akan diperbarui oleh admin setelah uang diterima.</p>

        <?php endif; ?>

        <?php if (in_array($metode, ['QRIS', 'TRANSFER'], true)): ?>

            <?php if ($status_bayar === 'Menunggu Konfirmasi'): ?>

                <p>Bukti sudah dikirim. Menunggu pemeriksaan admin.</p>

            <?php elseif (in_array($status_bayar, ['Lunas', 'Dibayar'], true)): ?>

                <p style="color:green">Pembayaran sudah dikonfirmasi.</p>

            <?php elseif (empty($pesanan['bukti_pembayaran'])): ?>

                <h3>Unggah Bukti Pembayaran</h3>

                <form method="POST" enctype="multipart/form-data">
                    <input
                        type="file"
                        name="bukti"
                        accept=".jpg,.jpeg,.png,.webp"
                        required
                    >
                    <button type="submit">Kirim Bukti Pembayaran</button>
                </form>

            <?php endif; ?>

        <?php endif; ?>

    </section>

    <p><a class="button" href="riwayat_pesanan.php">Lihat Riwayat</a></p>

</main>
</body>
</html>
