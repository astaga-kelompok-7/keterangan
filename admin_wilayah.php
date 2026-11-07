<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

if (empty($_SESSION['region_csrf'])) {
    $_SESSION['region_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['region_csrf'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }

    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($action === 'delete' && $id > 0) {
            $statement = db()->prepare('DELETE FROM service_regions WHERE id = ?');
            $statement->execute([$id]);
            header('Location: admin_wilayah.php?message=Wilayah berhasil dihapus');
            exit;
        }
        if ($action === 'toggle' && $id > 0) {
            $statement = db()->prepare('UPDATE service_regions SET is_active = NOT is_active WHERE id = ?');
            $statement->execute([$id]);
            header('Location: admin_wilayah.php?message=Status wilayah berhasil diubah');
            exit;
        }
        if ($action === 'save') {
            $name = trim((string)($_POST['name'] ?? ''));
            $area = trim((string)($_POST['area'] ?? ''));
            $city = trim((string)($_POST['city'] ?? ''));
            $type = ($_POST['shipping_type'] ?? '') === 'free' ? 'free' : 'paid';
            $rawCostInput = trim((string)($_POST['shipping_cost'] ?? ''));
            if (preg_match('/(^|[^0-9])-[0-9]+/', $rawCostInput)) {
                throw new InvalidArgumentException('Biaya pengiriman tidak boleh negatif.');
            }
            $rawCost = preg_replace('/\D+/', '', $rawCostInput);
            $cost = $rawCost === '' ? 0 : (int)$rawCost;
            $active = isset($_POST['is_active']) ? 1 : 0;
            $notes = trim((string)($_POST['notes'] ?? ''));

            if ($name === '' || $area === '' || $city === '') {
                throw new InvalidArgumentException('Nama wilayah, kecamatan/area, dan kabupaten/kota wajib diisi.');
            }
            if ($cost < 0) {
                throw new InvalidArgumentException('Biaya pengiriman tidak boleh negatif.');
            }
            if ($type === 'free') {
                $cost = 0;
            }

            if ($id > 0) {
                $statement = db()->prepare(
                    'UPDATE service_regions
                     SET name = ?, area = ?, city = ?, shipping_type = ?, shipping_cost = ?, is_active = ?, notes = ?
                     WHERE id = ?'
                );
                $statement->execute([$name, $area, $city, $type, $cost, $active, $notes, $id]);
                $message = 'Wilayah berhasil diubah.';
            } else {
                $statement = db()->prepare(
                    'INSERT INTO service_regions
                     (name, area, city, shipping_type, shipping_cost, is_active, notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $statement->execute([$name, $area, $city, $type, $cost, $active, $notes]);
                $message = 'Wilayah berhasil ditambahkan.';
            }
            header('Location: admin_wilayah.php?message=' . rawurlencode($message));
            exit;
        }
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        $error = (($exception->errorInfo[1] ?? null) === 1062)
            ? 'Wilayah dengan nama, area, dan kabupaten/kota yang sama sudah ada.'
            : 'Perubahan wilayah gagal disimpan. Periksa konfigurasi database.';
        error_log($exception->getMessage());
    }
}

$editing = null;
if ((int)($_GET['edit'] ?? 0) > 0) {
    $statement = db()->prepare('SELECT * FROM service_regions WHERE id = ?');
    $statement->execute([(int)$_GET['edit']]);
    $editing = $statement->fetch() ?: null;
}
$regions = service_regions();
$message = (string)($_GET['message'] ?? $message);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konfigurasi Wilayah Layanan dan Tarif</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <a class="logo" href="admin.php">🥡 Admin Tahu Kriuk</a>
    <nav>
        <a href="admin.php">Pesanan</a>
        <a href="admin_produk.php">Kelola Produk</a>
        <a href="admin_wilayah.php">Wilayah & Tarif</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>
<main class="container">
    <h1>Konfigurasi Wilayah Layanan dan Tarif</h1>
    <?php if ($message !== ''): ?><p class="success"><?=e($message)?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="alert"><?=e($error)?></p><?php endif; ?>

    <section class="card">
        <h2><?= $editing ? 'Edit Wilayah' : 'Tambah Wilayah' ?></h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?=e($csrf)?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
            <label>Nama wilayah</label>
            <input name="name" required value="<?=e($editing['name'] ?? '')?>">
            <label>Kecamatan atau area</label>
            <input name="area" required value="<?=e($editing['area'] ?? '')?>">
            <label>Kabupaten/kota</label>
            <input name="city" required value="<?=e($editing['city'] ?? 'Bangkalan')?>">
            <label>Jenis ongkir</label>
            <select name="shipping_type" id="shippingType">
                <option value="free" <?=($editing['shipping_type'] ?? '') === 'free' ? 'selected' : ''?>>Gratis</option>
                <option value="paid" <?=($editing['shipping_type'] ?? 'paid') === 'paid' ? 'selected' : ''?>>Berbayar</option>
            </select>
            <label>Biaya pengiriman (Rp)</label>
            <input name="shipping_cost" id="shippingCost" inputmode="numeric" min="0"
                   value="<?=e((string)($editing['shipping_cost'] ?? 0))?>">
            <label><input type="checkbox" name="is_active" value="1" <?=(!$editing || $editing['is_active']) ? 'checked' : ''?>> Wilayah aktif</label>
            <label>Keterangan tambahan</label>
            <textarea name="notes" rows="3"><?=e($editing['notes'] ?? '')?></textarea>
            <button type="submit"><?= $editing ? 'Simpan Perubahan' : 'Tambah Wilayah' ?></button>
            <?php if ($editing): ?><a class="button" href="admin_wilayah.php">Batal</a><?php endif; ?>
        </form>
    </section>

    <h2>Daftar Wilayah Layanan</h2>
    <table>
        <tr><th>Wilayah</th><th>Area</th><th>Kabupaten/kota</th><th>Status</th><th>Jenis ongkir</th><th>Tarif</th><th>Aksi</th></tr>
        <?php foreach ($regions as $region): ?>
        <tr>
            <td><?=e($region['name'])?></td>
            <td><?=e($region['area'])?></td>
            <td><?=e($region['city'])?></td>
            <td><?= $region['is_active'] ? 'Aktif' : 'Nonaktif' ?></td>
            <td><?= $region['shipping_type'] === 'free' ? 'Gratis' : 'Berbayar' ?></td>
            <td><?= $region['shipping_type'] === 'free' ? rupiah(0) : ($region['shipping_cost'] > 0 ? rupiah($region['shipping_cost']) : 'Belum diatur') ?></td>
            <td>
                <a class="button" href="?edit=<?= (int)$region['id'] ?>">Edit</a>
                <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?=e($csrf)?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$region['id'] ?>">
                    <button type="submit"><?= $region['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Hapus wilayah ini?')">
                    <input type="hidden" name="csrf" value="<?=e($csrf)?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$region['id'] ?>">
                    <button type="submit">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</main>
<script>
const type = document.getElementById('shippingType');
const cost = document.getElementById('shippingCost');
function syncCost() {
    cost.disabled = type.value === 'free';
    if (cost.disabled) cost.value = '0';
}
type.addEventListener('change', syncCost);
syncCost();
</script>
</body>
</html>
