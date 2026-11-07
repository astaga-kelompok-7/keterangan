let map, marker, chosen=null;
const bangkalanCenter=[-7.0453,112.7351];
function switchTab(name){
 document.querySelectorAll('.tab').forEach(b=>b.classList.toggle('active',b.dataset.tab===name));
 document.querySelectorAll('.tab-pane').forEach(p=>p.classList.add('hidden'));
 document.getElementById(name).classList.remove('hidden');
 if(name==='map'){if(!map){initMap();}setTimeout(()=>map.invalidateSize(),150);}
}
document.querySelectorAll('.tab').forEach(b=>b.addEventListener('click',()=>switchTab(b.dataset.tab)));
function initMap(){
 map=L.map('mapCanvas').setView(bangkalanCenter,13);
 L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap'}).addTo(map);
 map.on('click',e=>selectPoint(e.latlng.lat,e.latlng.lng));
}
async function selectPoint(lat,lon){
 chosen={lat,lon}; if(marker)map.removeLayer(marker);
 marker=L.marker([lat,lon]).addTo(map);
 document.getElementById('locationStatus').textContent='Sedang mencari alamat dari titik peta...';
 document.getElementById('mapAddress').textContent='Memuat alamat...';
 try{
  const r=await fetch(`api/reverse.php?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}`);
  const d=await r.json();
  if(!d.valid){document.getElementById('locationStatus').textContent=d.message||'Lokasi di luar Bangkalan';document.getElementById('mapAddress').textContent=d.display_name||'Lokasi tidak dapat digunakan';chosen.valid=false;return;}
  chosen={lat,lon,...d,valid:true};
  document.getElementById('locationStatus').textContent='✓ Lokasi berada di Kabupaten Bangkalan';
  document.getElementById('mapAddress').textContent=d.display_name;
 }catch(e){document.getElementById('locationStatus').textContent='Gagal mengambil alamat. Coba lagi.';}
}
document.getElementById('myLocation').addEventListener('click',()=>{
 if(!navigator.geolocation){alert('Browser tidak mendukung lokasi otomatis.');return;}
 navigator.geolocation.getCurrentPosition(pos=>{const {latitude,longitude}=pos.coords;map.setView([latitude,longitude],17);selectPoint(latitude,longitude);},()=>alert('Izin lokasi ditolak atau lokasi tidak tersedia.'),{enableHighAccuracy:true,timeout:15000});
});
async function searchPlace(){
 const q=document.getElementById('searchLocation').value.trim();if(q.length<3){alert('Masukkan minimal 3 karakter.');return;}
 const r=await fetch('api/search.php?q='+encodeURIComponent(q));const data=await r.json();
 if(!data.length){alert('Alamat tidak ditemukan di Kabupaten Bangkalan.');return;}
 const p=data[0];map.setView([+p.lat,+p.lon],17);selectPoint(+p.lat,+p.lon);
}
document.getElementById('searchBtn').addEventListener('click',searchPlace);
document.getElementById('searchLocation').addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();searchPlace();}});
document.getElementById('confirmLocation').addEventListener('click',()=>{
 if(!chosen||!chosen.valid){alert('Pilih titik yang valid di Kabupaten Bangkalan terlebih dahulu.');return;}
 document.getElementById('lat').value=chosen.lat;document.getElementById('lon').value=chosen.lon;
 document.getElementById('full_address').value=chosen.display_name;
 document.getElementById('village').value=chosen.village||'';
 document.getElementById('subdistrict').value=chosen.subdistrict||'';
 document.getElementById('selectedAddress').textContent=chosen.display_name;
 switchTab('saved');
});
const err=new URLSearchParams(location.search).get('error');if(err)alert(err);
