<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_admin();

// Proses tambah, edit, refill, dan nonaktifkan produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = $_POST['aksi'] ?? '';

    // Tambah produk
    if ($aksi === 'tambah') {
        $nama = trim($_POST['nama_produk'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $harga = (int)($_POST['harga'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);

        if ($nama !== '' && $harga > 0 && $stok >= 0) {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO produk
                (nama_produk, deskripsi, harga, stok, aktif)
                VALUES (?, ?, ?, ?, 1)"
            );

            mysqli_stmt_bind_param(
                $stmt, "ssii", $nama, $deskripsi, $harga, $stok
            );

            mysqli_stmt_execute($stmt);
        }
    }

    // Edit produk
    elseif ($aksi === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_produk'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $harga = (int)($_POST['harga'] ?? 0);

        if ($id > 0 && $nama !== '' && $harga > 0) {
            $stmt = mysqli_prepare($conn,
                "UPDATE produk
                 SET nama_produk = ?, deskripsi = ?, harga = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt, "ssii", $nama, $deskripsi, $harga, $id
            );

            mysqli_stmt_execute($stmt);
        }
    }

    // Refill stok
    elseif ($aksi === 'refill') {
        $id = (int)($_POST['id'] ?? 0);
        $jumlah = (int)($_POST['jumlah'] ?? 0);

        if ($id > 0 && $jumlah > 0) {
            $stmt = mysqli_prepare($conn,
                "UPDATE produk
                 SET stok = stok + ?, aktif = 1
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt, "ii", $jumlah, $id);
            mysqli_stmt_execute($stmt);
        }
    }

    // Nonaktifkan produk
    elseif ($aksi === 'nonaktif') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = mysqli_prepare($conn,
                "UPDATE produk SET aktif = 0 WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
        }
    }

    header("Location: admin_produk.php");
    exit;
}

// Mengambil semua produk
$produk = mysqli_query($conn,
    "SELECT * FROM produk ORDER BY id DESC"
);

if (!$produk) {
    die("Gagal mengambil produk: " . mysqli_error($conn));
}

// Menghitung jumlah produk
$total_produk = mysqli_fetch_assoc(mysqli_query(
    $conn, "SELECT COUNT(*) AS total FROM produk"
))['total'];

$total_tersedia = mysqli_fetch_assoc(mysqli_query(
    $conn, "SELECT COUNT(*) AS total FROM produk
            WHERE stok > 0 AND aktif = 1"
))['total'];

$total_habis = mysqli_fetch_assoc(mysqli_query(
    $conn, "SELECT COUNT(*) AS total FROM produk
            WHERE stok = 0 AND aktif = 1"
))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Produk - Tahu Kriuk</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .ringkasan {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .kotak-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .kotak-info h3 {
            margin: 0 0 10px;
        }

        .kotak-info p {
            font-size: 25px;
            font-weight: bold;
            margin: 0;
        }

        .form-produk {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .form-produk input,
        .form-produk textarea {
            width: 100%;
            margin: 7px 0 15px;
            padding: 10px;
            box-sizing: border-box;
        }

        .tabel-produk {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
        }

        .tabel-produk th,
        .tabel-produk td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .tabel-produk th {
            background: #39758d;
            color: white;
        }

        .tersedia {
            color: green;
            font-weight: bold;
        }

        .habis {
            color: red;
            font-weight: bold;
        }

        .nonaktif {
            color: gray;
            font-weight: bold;
        }

        .aksi-form {
            margin: 5px 0;
        }

        .aksi-form input {
            width: 100px;
            padding: 7px;
        }

        .btn-nonaktif {
            background: #c0392b;
        }

        .btn-edit {
            background: #ed6b16;
        }

        .btn-refill {
            background: #75ad43;
        }

        @media(max-width: 700px) {
            .tabel-produk {
                font-size: 12px;
            }

            .tabel-produk th,
            .tabel-produk td {
                padding: 7px;
            }
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

    <h1>Kelola Produk</h1>
    <p>Kelola produk dan persediaan stok Tahu Kriuk.</p>

    <div class="ringkasan">

        <div class="kotak-info">
            <h3>Total Produk</h3>
            <p><?= (int)$total_produk ?></p>
        </div>

        <div class="kotak-info">
            <h3>Produk Tersedia</h3>
            <p class="tersedia"><?= (int)$total_tersedia ?></p>
        </div>

        <div class="kotak-info">
            <h3>Produk Habis</h3>
            <p class="habis"><?= (int)$total_habis ?></p>
        </div>

    </div>

    <section class="form-produk">

        <h2>Tambah Produk Baru</h2>

        <form method="POST">

            <input type="hidden" name="aksi" value="tambah">

            <label>Nama Produk</label>
            <input type="text" name="nama_produk"
                   placeholder="Masukkan nama produk" required>

            <label>Deskripsi</label>
            <textarea name="deskripsi"
                      placeholder="Masukkan deskripsi produk"></textarea>

            <label>Harga</label>
            <input type="number" name="harga" min="1"
                   placeholder="Masukkan harga" required>

            <label>Stok Awal</label>
            <input type="number" name="stok" min="0"
                   value="0" required>

            <button type="submit">Tambah Produk</button>

        </form>

    </section>

    <h2>Daftar Semua Produk</h2>

    <table class="tabel-produk">

        <tr>
            <th>ID</th>
            <th>Nama Produk</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Status</th>
            <th>Kelola</th>
        </tr>

        <?php while ($p = mysqli_fetch_assoc($produk)): ?>

        <tr>
            <td><?= (int)$p['id'] ?></td>

            <td><?= h($p['nama_produk']) ?></td>

            <td><?= rupiah($p['harga']) ?></td>

            <td><?= (int)$p['stok'] ?></td>

            <td>
                <?php if (!(int)$p['aktif']): ?>
                    <span class="nonaktif">Nonaktif</span>
                <?php elseif ((int)$p['stok'] > 0): ?>
                    <span class="tersedia">Tersedia</span>
                <?php else: ?>
                    <span class="habis">Habis</span>
                <?php endif; ?>
            </td>

            <td>

                <!-- Form edit produk -->
                <details>
                    <summary>Edit Produk</summary>

                    <form method="POST" class="aksi-form">

                        <input type="hidden" name="aksi" value="edit">
                        <input type="hidden" name="id"
                               value="<?= (int)$p['id'] ?>">

                        <input type="text" name="nama_produk"
                               value="<?= h($p['nama_produk']) ?>" required>

                        <textarea name="deskripsi"><?= h($p['deskripsi']) ?></textarea>

                        <input type="number" name="harga"
                               value="<?= (int)$p['harga'] ?>"
                               min="1" required>

                        <button type="submit" class="btn-edit">
                            Simpan Perubahan
                        </button>

                    </form>
                </details>

                <!-- Form refill stok -->
                <form method="POST" class="aksi-form">

                    <input type="hidden" name="aksi" value="refill">
                    <input type="hidden" name="id"
                           value="<?= (int)$p['id'] ?>">

                    <input type="number" name="jumlah"
                           min="1" placeholder="Jumlah refill" required>

                    <button type="submit" class="btn-refill">
                        Refill Stok
                    </button>

                </form>

                <!-- Nonaktifkan produk -->
                <?php if ((int)$p['aktif'] === 1): ?>

                <form method="POST" class="aksi-form"
                      onsubmit="return confirm('Yakin ingin menonaktifkan produk ini?')">

                    <input type="hidden" name="aksi" value="nonaktif">
                    <input type="hidden" name="id"
                           value="<?= (int)$p['id'] ?>">

                    <button type="submit" class="btn-nonaktif">
                        Nonaktifkan
                    </button>

                </form>

                <?php endif; ?>

            </td>
        </tr>

        <?php endwhile; ?>

    </table>

</main>

</body>
</html>
