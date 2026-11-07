<?php
header('Content-Type: application/json; charset=utf-8');
$q=trim($_GET['q']??'');if(strlen($q)<3){echo '[]';exit;}
$url='https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=6&countrycodes=id&q='.rawurlencode($q.', Bangkalan, Jawa Timur');
$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_USERAGENT=>'TokoTahuKrispy/1.0 (local checkout demo)']);
$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
if(!$raw||$code!==200){http_response_code(502);echo '[]';exit;}
$data=json_decode($raw,true)??[];$out=[];
foreach($data as $r){$a=$r['address']??[];$region=strtolower(($a['county']??'').' '.($a['city']??'').' '.($a['state_district']??''));if(str_contains($region,'bangkalan'))$out[]=['lat'=>$r['lat'],'lon'=>$r['lon'],'name'=>$r['display_name']];}
echo json_encode($out,JSON_UNESCAPED_UNICODE);
