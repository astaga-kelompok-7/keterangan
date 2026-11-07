# keterangan

## Konfigurasi wilayah layanan

Fitur wilayah dan tarif menggunakan tabel `service_regions`. Jalankan migration
`database/migrations/001_create_service_regions.sql` pada database yang dipakai
oleh `config.php` sebelum membuka `admin_wilayah.php`.

Admin dapat menambah, mengubah, mengaktifkan/nonaktifkan, dan menghapus wilayah
beserta tarifnya. Checkout mencocokkan alamat hasil geocoding dengan wilayah
aktif dan menghitung ulang ongkir dari database saat `place_order.php` dipanggil;
nilai ongkir yang dikirim dari browser tidak dipercaya. Nilai ongkir disalin ke
`orders.shipping_cost`, sehingga perubahan konfigurasi tidak mengubah pesanan
lama.
