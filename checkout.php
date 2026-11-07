<?php
require_once __DIR__.'/functions.php';
$items=get_cart_items(); if(!$items){header('Location: cart.php');exit;}
$address=$_SESSION['shipping_address']??null;
$sub=subtotal($items); $ship=$address ? shipping_for($address) : SHIPPING_BANGKALAN_OTHER;
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Checkout</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<header class="topbar"><a class="brand" href="index.php">🛍 Toko Tahu Krispy</a><span>Checkout</span></header><main class="container narrow">
<?php if(isset($_GET['error'])): ?><div class="alert"><?=e($_GET['error'])?></div><?php endif; ?>
<form method="post" action="place_order.php" id="orderForm">
<section class="panel"><div class="panel-title">📍 Alamat Pengiriman <a href="address.php">Ubah</a></div>
<?php if($address): ?><b><?=e($address['recipient'])?> · <?=e($address['phone'])?></b><p><?=e($address['full_address'])?></p><small class="muted"><?=e($address['village']??'')?>, <?=e($address['subdistrict']??'')?>, Kabupaten Bangkalan</small>
<input type="hidden" name="lat" value="<?=e($address['lat'])?>"><input type="hidden" name="lon" value="<?=e($address['lon'])?>"><input type="hidden" name="full_address" value="<?=e($address['full_address'])?>"><input type="hidden" name="village" value="<?=e($address['village']??'')?>"><input type="hidden" name="subdistrict" value="<?=e($address['subdistrict']??'')?>"><input type="hidden" name="shipping_cost" value="<?=$ship?>">
<?php else: ?><p class="muted">Tambahkan alamat pengiriman terlebih dahulu.</p><a class="btn" href="address.php">Pilih Alamat</a><?php endif; ?></section>
<section class="panel"><div class="panel-title">🏪 Toko Tahu Krispy Bangkalan</div><?php foreach($items as $p): ?><div class="checkout-product"><div class="product-photo small"><?=e($p['emoji'])?></div><div class="grow"><b><?=e($p['name'])?></b><div class="muted">Produk pilihan</div><strong class="price"><?=rupiah($p['price'])?></strong></div><span>x <?=$p['qty']?></span><b><?=rupiah($p['line_total'])?></b></div><?php endforeach; ?>
<label class="field-label">Pesan untuk Penjual (opsional)</label><textarea name="note" rows="2" placeholder="Tinggalkan pesan..."></textarea></section>
<section class="panel"><div class="panel-title">🚚 Opsi Pengiriman</div><div class="between"><span>Pengiriman reguler</span><b class="price"><?=rupiah($ship)?></b></div><p class="success-text">✓ <?=($ship===0?'Gratis ongkir untuk area Telang':'Tarif pengiriman wilayah Bangkalan')?></p></section>
<section class="panel"><div class="panel-title">🧾 Rincian Pembayaran</div><div class="between"><span>Subtotal Produk</span><span><?=rupiah($sub)?></span></div><div class="between"><span>Ongkos Kirim</span><span><?=rupiah($ship)?></span></div><hr><div class="between total"><b>Total Pembayaran</b><strong class="price"><?=rupiah($sub+$ship)?></strong></div></section>
<div class="sticky-order"><div><small>Total Pembayaran</small><strong class="price"><?=rupiah($sub+$ship)?></strong></div><button class="btn" <?=!$address?'disabled':''?>>Buat Pesanan</button></div></form></main></body></html>