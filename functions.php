<?php
require_once __DIR__.'/config.php';

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rupiah($n): string { return 'Rp'.number_format((float)$n, 0, ',', '.'); }
function cart_count(): int {
    $n = 0; foreach ($_SESSION['cart'] ?? [] as $qty) $n += (int)$qty;
    return $n;
}
function get_cart_items(): array {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return [];
    $ids = array_values(array_filter(array_map('intval', array_keys($cart))));
    if (!$ids) return [];
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT * FROM products WHERE id IN ($marks) AND active=1");
    $st->execute($ids);
    $items = [];
    foreach ($st->fetchAll() as $p) {
        $qty = max(1, min(99, (int)($cart[$p['id']] ?? 1)));
        $p['qty'] = $qty; $p['line_total'] = $qty * (int)$p['price'];
        $items[] = $p;
    }
    return $items;
}
function subtotal(array $items): int {
    return array_sum(array_map(fn($p) => (int)$p['line_total'], $items));
}
function service_regions(bool $activeOnly = false): array {
    $sql = 'SELECT id, name, area, city, shipping_type, shipping_cost, is_active, notes
            FROM service_regions';
    if ($activeOnly) $sql .= ' WHERE is_active = 1';
    $sql .= ' ORDER BY name ASC, id ASC';
    return db()->query($sql)->fetchAll();
}
function shipping_quote(array $address): ?array {
    $values = [
        $address['village'] ?? '',
        $address['suburb'] ?? '',
        $address['hamlet'] ?? '',
        $address['display_name'] ?? '',
        $address['subdistrict'] ?? '',
        $address['state_district'] ?? '',
        $address['county'] ?? '',
        $address['city'] ?? '',
    ];
    $searchText = strtolower(implode(' ', array_filter(array_map('strval', $values))));
    $best = null;
    $bestScore = 0;
    foreach (service_regions(true) as $region) {
        $name = strtolower(trim((string)$region['name']));
        $area = strtolower(trim((string)$region['area']));
        $city = strtolower(trim((string)$region['city']));
        $score = 0;
        if ($name !== '' && str_contains($searchText, $name)) $score = 3;
        elseif ($area !== '' && str_contains($searchText, $area)) $score = 2;
        elseif ($city !== '' && str_contains($searchText, $city)) $score = 1;
        if ($score > $bestScore) {
            $best = $region;
            $bestScore = $score;
        }
    }
    if (!$best) return null;
    if ($best['shipping_type'] === 'paid' && (int)$best['shipping_cost'] <= 0) return null;
    return [
        'region' => $best,
        'cost' => $best['shipping_type'] === 'free' ? 0 : max(0, (int)$best['shipping_cost']),
    ];
}
function bangkalan_address(array $a): bool {
    $county = strtolower((string)($a['county'] ?? ''));
    $city = strtolower((string)($a['city'] ?? ''));
    $district = strtolower((string)($a['state_district'] ?? ''));
    $combined = "$county $city $district";
    return str_contains($combined, 'bangkalan');
}
function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method tidak diizinkan'); }
}
