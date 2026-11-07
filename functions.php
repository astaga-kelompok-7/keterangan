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
function shipping_for(array $address): int {
    $village = strtolower((string)($address['village'] ?? ''));
    $suburb = strtolower((string)($address['suburb'] ?? ''));
    $hamlet = strtolower((string)($address['hamlet'] ?? ''));
    $display = strtolower((string)($address['display_name'] ?? ''));
    $all = "$village $suburb $hamlet $display";
    if (str_contains($all, 'telang')) return SHIPPING_TELANG;
    return SHIPPING_BANGKALAN_OTHER;
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
