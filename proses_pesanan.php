<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_login();

$user_id = (int)($_SESSION['user_id'] ?? 0);
$pesanan_id = (int)($_GET['id'] ?? 0);

if ($pesanan_id < 1) {
    exit('ID pesanan tidak valid.');
}

// Ambil pesanan milik pelanggan yang sedang login
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM pesanan WHERE id = ? AND user_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $pesanan_id, $user_id);
mysqli_stmt_execute($stmt);
$pesanan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pesanan) {
    http_response_code(404);
    exit('Pesanan tidak ditemukan.');
}

// Ambil informasi pembayaran
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM pembayaran WHERE pesanan_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $pesanan_id);
mysqli_stmt_execute($stmt);
$pembayaran = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pembayaran) {
    exit('Data pembayaran belum ditemukan.');
}

$error = '';
$metode = strtoupper(trim($pesanan['metode_pembayaran'] ?? ''));
$status_bayar = $pembayaran['status'] ?? 'Belum Dibayar';

// Proses unggah bukti pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (in_array($status_bayar, ['Menunggu Konfirmasi', 'Lunas', 'Dibayar'], true)) {
        $error = 'Bukti sudah dikirim atau pembayaran sudah dikonfirmasi.';
    } elseif ($metode === 'COD') {
        $error = 'Pesanan COD tidak memerlukan unggah bukti pembayaran.';
    } elseif (!isset($_FILES['bukti']) ||
        $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Pilih gambar bukti pembayaran terlebih dahulu.';
    } else {
        $file = $_FILES['bukti'];

        if ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Ukuran file maksimal 5 MB.';
        } else {
            $info = new finfo(FILEINFO_MIME_TYPE);
            $mime = $info->file($file['tmp_name']);

            $ekstensi = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($ekstensi[$mime])) {
                $error = 'File harus berupa gambar JPG, PNG, atau WEBP.';
            } else {
                $folder = __DIR__ . '/uploads/bukti_pembayaran';

                if (!is_dir($folder) &&
                    !mkdir($folder, 0755, true)) {
                    $error = 'Folder penyimpanan bukti gagal dibuat.';
                } else {
                    $nama_file = bin2hex(random_bytes(16)) .
                        '.' . $ekstensi[$mime];

                    $tujuan = $folder . '/' . $nama_file;

                    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
                        $error = 'Gagal menyimpan bukti pembayaran.';
                    } else {
                        $path_bukti = 'uploads/bukti_pembayaran/' . $nama_file;

                        mysqli_begin_transaction($conn);

                        try {
                            $stmt = mysqli_prepare(
                                $conn,
                                "UPDATE pembayaran
                                 SET bukti_pembayaran = ?,
                                     status = 'Menunggu Konfirmasi'
                                 WHERE pesanan_id = ?"
                            );
                            mysqli_stmt_bind_param(
                                $stmt, "si", $path_bukti, $pesanan_id
                            );

                            if (!mysqli_stmt_execute($stmt) ||
                                mysqli_stmt_affected_rows($stmt) < 0) {
                                throw new Exception('Gagal menyimpan data pembayaran.');
                            }

                            $stmt = mysqli_prepare(
                                $conn,
                                "UPDATE pesanan
                                 SET status = 'Menunggu Konfirmasi'
                                 WHERE id = ? AND user_id = ?"
                            );
                            mysqli_stmt_bind_param(
                                $stmt, "ii", $pesanan_id, $user_id
                            );

                            if (!mysqli_stmt_execute($stmt)) {
                                throw new Exception('Gagal memperbarui status pesanan.');
                            }

                            mysqli_commit($conn);

                            header(
                                'Location: detail_pembayaran.php?id=' .
                                $pesanan_id . '&terkirim=1'
                            );
                            exit;
                        } catch (Throwable $e) {
                            mysqli_rollback($conn);

                            if (is_file($tujuan)) {
                                unlink($tujuan);
                            }

                            $error = $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

// Ambil rincian produk
$stmt = mysqli_prepare(
    $conn,
    "SELECT dp.jumlah, dp.harga, dp.subtotal, p.nama_produk
     FROM detail_pesanan dp
     JOIN produk p ON p.id = dp.produk_id
     WHERE dp.pesanan_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $pesanan_id);
mysqli_stmt_execute($stmt);
$detail = mysqli_stmt_get_result($stmt);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_rupiah($angka) {
    return 'Rp' . number_format((float)$angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran - Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .payment-box {
            background: #fff;
            border: 1px solid #eadcc7;
            border-radius: 12px;
            padding: 22px;
            margin: 22px 0;
        }
        .payment-image {
            display: block;
            max-width: 300px;
            width: 100%;
            height: auto;
            margin: 20px auto;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .upload-input {
            display: block;
            width: 100%;
            margin: 12px 0 18px;
        }
        .notice {
            padding: 12px;
            background: #fff0ce;
            border-radius: 8px;
            margin: 14px 0;
        }
        .error-message {
            background: #ffe1e1;
            padding: 12px;
            border-radius: 8px;
            color: #9b1717;
        }
        .success-message {
            background: #e0f6e5;
            padding: 12px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<header>
    <a class="logo" href="dashboard.php">🥡 Tahu Kriuk</a>
</header>

<main class="container">
    <h1>Detail Pesanan #<?= (int)$pesanan['id'] ?></h1>

    <?php if (isset($_GET['terkirim'])): ?>
        <p class="success-message">
            Bukti pembayaran berhasil dikirim. Silakan tunggu konfirmasi admin.
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error-message"><?= e($error) ?></p>
    <?php endif; ?>

    <p>Jenis: <?= e($pesanan['jenis']) ?></p>
    <p>Status pesanan: <?= e($pesanan['status']) ?></p>
    <p>Metode pembayaran: <?= e($pesanan['metode_pembayaran']) ?></p>
    <p>Status pembayaran: <?= e($status_bayar) ?></p>

    <h2>Rincian Produk</h2>
    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($item = mysqli_fetch_assoc($detail)): ?>
            <tr>
                <td><?= e($item['nama_produk']) ?></td>
                <td><?= (int)$item['jumlah'] ?></td>
                <td><?= format_rupiah($item['subtotal']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Total: <?= format_rupiah($pesanan['total']) ?></h2>
    <p>Alamat: <?= e($pesanan['alamat']) ?></p>

    <section class="payment-box">
        <h2>Instruksi Pembayaran</h2>

        <?php if ($metode === 'QRIS'): ?>
            <p>Silakan scan QRIS resmi toko untuk membayar sesuai nominal pesanan.</p>

            <?php if (is_file(__DIR__ . '/images/qris.png')): ?>
                <img
                    class="payment-image"
                    src="images/qris.png"
                    alt="QRIS Tahu Kriuk"
                >
            <?php else: ?>
                <p class="notice">
                    Gambar QRIS belum dipasang. Simpan gambar QRIS resmi toko
                    di <code>images/qris.png</code>.
                </p>
            <?php endif; ?>

            <p><strong>Nominal: <?= format_rupiah($pesanan['total']) ?></strong></p>

        <?php elseif ($metode === 'TRANSFER'): ?>
            <p>Silakan transfer sesuai nominal pesanan ke rekening resmi toko.</p>
            <p><strong>Bank:</strong> [Isi nama bank]</p>
            <p><strong>Nomor rekening:</strong> [Isi nomor rekening]</p>
            <p><strong>Atas nama:</strong> [Isi nama pemilik rekening]</p>
            <p><strong>Nominal: <?= format_rupiah($pesanan['total']) ?></strong></p>

        <?php elseif ($metode === 'COD'): ?>
            <p class="notice">
                Pembayaran dilakukan saat pesanan diterima. Kamu tidak perlu
                mengunggah bukti transfer.
            </p>

        <?php else: ?>
            <p class="error-message">
                Metode pembayaran tidak dikenali. Silakan hubungi admin.
            </p>
        <?php endif; ?>

        <?php if (in_array($metode, ['QRIS', 'TRANSFER'], true)): ?>
            <?php if (!empty($pembayaran['bukti_pembayaran'])): ?>
                <p>Bukti pembayaran sudah diunggah.</p>
                <p>Status: <?= e($status_bayar) ?></p>
            <?php elseif ($status_bayar === 'Lunas' || $status_bayar === 'Dibayar'): ?>
                <p class="success-message">Pembayaran sudah dikonfirmasi.</p>
            <?php else: ?>
                <h3>Unggah Bukti Pembayaran</h3>
                <form method="POST" enctype="multipart/form-data">
                    <label for="bukti">
                        Pilih foto atau screenshot bukti pembayaran
                    </label>
                    <input
                        class="upload-input"
                        type="file"
                        id="bukti"
                        name="bukti"
                        accept=".jpg,.jpeg,.png,.webp"
                        required
                    >
                    <small>Format JPG, PNG, atau WEBP. Maksimal 5 MB.</small>
                    <p>
                        <button type="submit">Kirim Bukti Pembayaran</button>
                    </p>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <a class="button" href="riwayat_pesanan.php">Lihat Riwayat</a>
</main>
</body>
</html>
