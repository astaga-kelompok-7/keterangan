<?php
require_once __DIR__.'/functions.php'; require_post();
$recipient=trim($_POST['recipient']??''); $phone=trim($_POST['phone']??'');
$detail=trim($_POST['detail']??''); $lat=(float)($_POST['lat']??0); $lon=(float)($_POST['lon']??0);
$full=trim($_POST['full_address']??''); $village=trim($_POST['village']??''); $sub=trim($_POST['subdistrict']??'');
if(!$recipient||!$phone||!$detail||!$full||!$lat||!$lon){header('Location: address.php?error=Lengkapi data dan pilih lokasi');exit;}
$check=reverse_geocode($lat,$lon);
if(!$check || !bangkalan_address($check)){header('Location: address.php?error=Lokasi harus berada di Kabupaten Bangkalan');exit;}
$addr=$check;
$_SESSION['shipping_address']=[
 'recipient'=>$recipient,'phone'=>$phone,'detail'=>$detail,'lat'=>$lat,'lon'=>$lon,
 'full_address'=>$full.', '.$detail,'village'=>$village ?: ($addr['village']??$addr['suburb']??''),
 'subdistrict'=>$sub ?: ($addr['county']??''),'geo'=>$addr
];
header('Location: checkout.php');exit;

function reverse_geocode(float $lat,float $lon): ?array {
 $url='https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.rawurlencode((string)$lat).'&lon='.rawurlencode((string)$lon).'&zoom=18&addressdetails=1';
 $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_USERAGENT=>'TokoTahuKrispy/1.0 (local checkout demo)']);
 $raw=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
 if(!$raw||$code!==200)return null; $j=json_decode($raw,true); return is_array($j['address']??null)?array_merge($j['address'],['display_name'=>$j['display_name']??'']):null;
}
