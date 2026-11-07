<?php
header('Content-Type: application/json; charset=utf-8');
$lat=filter_input(INPUT_GET,'lat',FILTER_VALIDATE_FLOAT);$lon=filter_input(INPUT_GET,'lon',FILTER_VALIDATE_FLOAT);
if($lat===false||$lon===false||$lat===null||$lon===null){http_response_code(400);echo json_encode(['error'=>'Koordinat tidak valid']);exit;}
$url='https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.rawurlencode((string)$lat).'&lon='.rawurlencode((string)$lon).'&zoom=18&addressdetails=1';
$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_USERAGENT=>'TokoTahuKrispy/1.0 (local checkout demo)']);
$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
if(!$raw||$code!==200){http_response_code(502);echo json_encode(['error'=>'Layanan alamat tidak merespons']);exit;}
$j=json_decode($raw,true);$a=$j['address']??[];
$county=$a['county']??'';$city=$a['city']??'';$district=$a['state_district']??'';
$ok=str_contains(strtolower("$county $city $district"),'bangkalan');
if(!$ok){echo json_encode(['valid'=>false,'message'=>'Lokasi harus berada di Kabupaten Bangkalan.','display_name'=>$j['display_name']??'']);exit;}
echo json_encode(['valid'=>true,'display_name'=>$j['display_name']??'','village'=>$a['village']??$a['suburb']??$a['hamlet']??'','subdistrict'=>$county,'address'=>$a],JSON_UNESCAPED_UNICODE);
