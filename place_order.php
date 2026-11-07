<?php
require_once __DIR__.'/functions.php'; require_post();
$items=get_cart_items(); $a=$_SESSION['shipping_address']??null;
if(!$items||!$a){header('Location: checkout.php?error=Keranjang atau alamat belum lengkap');exit;}
$geo=reverse_order_check((float)$a['lat'],(float)$a['lon']);
if(!$geo || !bangkalan_address($geo)){header('Location: address.php?error=Lokasi tidak valid');exit;}
$quote=shipping_quote(array_merge($a,$geo));
if(!$quote){header('Location: checkout.php?error=Pengiriman ke wilayah ini belum tersedia');exit;}
$ship=(int)$quote['cost']; $sub=subtotal($items); $total=$sub+$ship;
try {
 db()->beginTransaction();
 $st=db()->prepare("INSERT INTO orders(order_code,recipient,phone,address_detail,full_address,latitude,longitude,village,subdistrict,note,subtotal,shipping_cost,total_amount,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,'Menunggu diproses',NOW())");
 $code='TK'.date('ymdHis').random_int(10,99);
 $st->execute([$code,$a['recipient'],$a['phone'],$a['detail'],$a['full_address'],$a['lat'],$a['lon'],$a['village'],$a['subdistrict'],trim($_POST['note']??''),$sub,$ship,$total]);
 $orderId=(int)db()->lastInsertId();
 $ins=db()->prepare("INSERT INTO order_items(order_id,product_id,product_name,price,quantity,line_total) VALUES(?,?,?,?,?,?)");
 foreach($items as $p)$ins->execute([$orderId,$p['id'],$p['name'],$p['price'],$p['qty'],$p['line_total']]);
 db()->commit(); $_SESSION['last_order_id']=$orderId; $_SESSION['last_order_code']=$code; unset($_SESSION['cart']);
 header('Location: success.php');exit;
} catch(Throwable $e){if(db()->inTransaction())db()->rollBack(); error_log($e->getMessage());header('Location: checkout.php?error=Pesanan gagal disimpan. Periksa konfigurasi database.');exit;}
function reverse_order_check(float $lat,float $lon): ?array {
 $url='https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.rawurlencode((string)$lat).'&lon='.rawurlencode((string)$lon).'&zoom=18&addressdetails=1';
 $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_USERAGENT=>'TokoTahuKrispy/1.0 (local checkout demo)']);
 $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
 if(!$raw||$code!==200)return null;$j=json_decode($raw,true);return is_array($j['address']??null)?$j['address']:null;
}
