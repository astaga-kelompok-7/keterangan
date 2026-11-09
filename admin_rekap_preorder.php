<?php
require 'koneksi.php';
require 'fungsi.php';
wajib_admin();

function tanggal_valid(string $tanggal): bool {
    $date = DateTime::createFromFormat('!Y-m-d', $tanggal);
    return $date !== false && $date->format('Y-m-d') === $tanggal;
}

$mulai = trim((string)($_GET['mulai'] ?? ''));
$selesai = trim((string)($_GET['selesai'] ?? ''));
$error = '';

if (($mulai !== '' && !tanggal_valid($mulai)) || ($selesai !== '' && !tanggal_valid($selesai))) {
    $error = 'Format tanggal harus valid.';
    $mulai = '';
    $selesai = '';
}

if ($mulai !== '' && $selesai !== '' && $mulai > $selesai) {
    $error = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.';
    $mulai = '';
    $selesai = '';
}

$conditions = [
    "LOWER(REPLACE(p.jenis, '-', '')) = 'preorder'",
    "p.status <> 'Dibatalkan'"
];
$types = '';
$values = [];

if ($mulai !== '') {
    $conditions[] = 'p.tanggal >= ?';
    $types .= 's';
    $values[] = $mulai . ' 00:00:00';
}
if ($selesai !== '') {
    $conditions[] = 'p.tanggal < DATE_ADD(?, INTERVAL 1 DAY)';
    $types .= 's';
    $values[] = $selesai . ' 00:00:00';
}

$where = implode(' AND ', $conditions);

function jalankan_rekap(mysqli $conn, string $sql, string $types, array $values): mysqli_result {
    $statement = mysqli_prepare($conn, $sql);
    if (!$statement) {
        throw new RuntimeException('Gagal menyiapkan rekap preorder.');
    }
    if ($types !== '') {
        $parameters = [$types];
        foreach ($values as $key => $value) {
            $parameters[] = &$values[$key];
        }
        call_user_func_array([$statement, 'bind_param'], $parameters);
    }
    if (!mysqli_stmt_execute($statement)) {
        throw new RuntimeException('Gagal mengambil rekap preorder.');
    }
    $result = mysqli_stmt_get_result($statement);
    if (!$result) {
        throw new RuntimeException('Gagal membaca hasil rekap preorder.');
    }
    return $result;
}

try {
    $ringkasan = jalankan_rekap(
        $conn,
        "SELECT COUNT(DISTINCT p.id) AS total_pesanan,
                COALESCE(SUM(dp.jumlah), 0) AS total_produk,
                COALESCE(SUM(dp.subtotal), 0) AS total_nilai
         FROM pesanan p
         JOIN detail_pesanan dp ON dp.pesanan_id = p.id
         WHERE $where",
        $types,
        $values
    );
    $total = mysqli_fetch_assoc($ringkasan) ?: [
        'total_pesanan' => 0,
        'total_produk' => 0,
        'total_nilai' => 0
    ];

    $produk = jalankan_rekap(
        $conn,
        "SELECT pr.nama_produk,
                SUM(dp.jumlah) AS jumlah_produksi,
                SUM(dp.subtotal) AS nilai_produk,
                COUNT(DISTINCT p.id) AS jumlah_pesanan
         FROM detail_pesanan dp
         JOIN pesanan p ON p.id = dp.pesanan_id
         JOIN produk pr ON pr.id = dp.produk_id
         WHERE $where
         GROUP BY dp.produk_id, pr.nama_produk
         ORDER BY jumlah_produksi DESC, pr.nama_produk ASC",
        $types,
        $values
    );
} catch (RuntimeException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Rekap preorder tidak dapat ditampilkan.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Pre-order - Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <a class="logo" href="admin.php">🥡 Admin Tahu Kriuk</a>
    <nav>
        <a href="admin.php">Pesanan</a>
        <a href="admin_rekap_preorder.php">Rekap Pre-order</a>
        <a href="admin_produk.php">Kelola Produk</a>
        <a href="admin_wilayah.php">Wilayah & Tarif</a>
        <a href="produk.php">Lihat Produk</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">
    <h1>Rekap Pre-order</h1>
    <p>Gunakan rekap ini untuk menentukan jumlah produksi berdasarkan pre-order yang belum dibatalkan.</p>

    <?php if ($error !== ''): ?>
        <p class="alert"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="GET" class="filter-form">
        <label for="mulai">Tanggal mulai</label>
        <input id="mulai" type="date" name="mulai" value="<?= h($mulai) ?>">
        <label for="selesai">Tanggal selesai</label>
        <input id="selesai" type="date" name="selesai" value="<?= h($selesai) ?>">
        <button type="submit">Tampilkan Rekap</button>
        <a class="button secondary-button" href="admin_rekap_preorder.php">Reset</a>
    </form>

    <div class="summary-grid">
        <article class="card">
            <h3>Total Pesanan</h3>
            <strong><?= (int)$total['total_pesanan'] ?></strong>
        </article>
        <article class="card">
            <h3>Total Produk</h3>
            <strong><?= (int)$total['total_produk'] ?> pcs</strong>
        </article>
        <article class="card">
            <h3>Nilai Pre-order</h3>
            <strong><?= rupiah($total['total_nilai']) ?></strong>
        </article>
    </div>

    <h2>Kebutuhan Produksi per Produk</h2>
    <table>
        <tr>
            <th>Produk</th>
            <th>Jumlah Produksi</th>
            <th>Jumlah Pesanan</th>
            <th>Nilai Produk</th>
        </tr>
        <?php while ($item = mysqli_fetch_assoc($produk)): ?>
        <tr>
            <td><?= h($item['nama_produk']) ?></td>
            <td><strong><?= (int)$item['jumlah_produksi'] ?> pcs</strong></td>
            <td><?= (int)$item['jumlah_pesanan'] ?></td>
            <td><?= rupiah($item['nilai_produk']) ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if (mysqli_num_rows($produk) === 0): ?>
        <tr>
            <td colspan="4">Belum ada data pre-order pada periode ini.</td>
        </tr>
        <?php endif; ?>
    </table>
</main>
</body>
</html>
