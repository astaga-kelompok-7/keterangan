CREATE TABLE IF NOT EXISTS service_regions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    area VARCHAR(120) NOT NULL,
    city VARCHAR(120) NOT NULL,
    shipping_type ENUM('free', 'paid') NOT NULL DEFAULT 'paid',
    shipping_cost INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_service_region (name, area, city),
    KEY idx_service_region_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO service_regions
    (name, area, city, shipping_type, shipping_cost, is_active, notes)
VALUES
    ('Telang', 'Telang', 'Bangkalan', 'free', 0, 1, 'Gratis ongkir untuk wilayah Telang.'),
    ('Area sekitar Telang', 'Telang', 'Bangkalan', 'paid', 0, 1, 'Atur nominal tarif sesuai keputusan pemilik UMKM.'),
    ('Surabaya', 'Surabaya', 'Surabaya', 'paid', 0, 0, 'Sementara tidak dilayani.');
