-- ============================================================
-- Xtanbul Lead Suite — TEK KURULUM DOSYASI (install.sql)
-- Bu tek dosyayı içe aktarmak temiz bir kurulum için yeterlidir.
-- Tüm kolonlar CREATE içinde tanımlıdır; ayrı ALTER'a gerek yoktur.
-- Panel ilk açılışta eksik kolon/tabloları otomatik tamamlar (ensure_column).
-- MySQL 5.7+/8.0 / MariaDB 10.3+ uyumlu.
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- Kullanıcılar ----------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Lead'ler (tüm kolonlar) ----------
CREATE TABLE IF NOT EXISTS leads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    place_id VARCHAR(190) NULL,
    name VARCHAR(255) NOT NULL,
    sector VARCHAR(120) NULL,
    sub_sector VARCHAR(190) NULL,
    city VARCHAR(100) NULL,
    district VARCHAR(120) NULL,
    neighborhood VARCHAR(160) NULL,
    address TEXT NULL,
    phone VARCHAR(80) NULL,
    phone_normalized VARCHAR(50) NULL,
    whatsapp_phone VARCHAR(80) NULL,
    email VARCHAR(190) NULL,
    website VARCHAR(255) NULL,
    website_quality VARCHAR(60) NULL,
    maps_url VARCHAR(500) NULL,
    instagram VARCHAR(190) NULL,
    facebook VARCHAR(190) NULL,
    rating DECIMAL(3,2) NULL,
    review_count INT NULL,
    competitor_density VARCHAR(40) NULL,
    -- Durum / öncelik / satış
    status VARCHAR(80) NOT NULL DEFAULT 'Aranmadı',
    priority VARCHAR(20) NOT NULL DEFAULT 'Ilık',
    close_probability INT NOT NULL DEFAULT 0,
    note TEXT NULL,
    source VARCHAR(80) NOT NULL DEFAULT 'Google Places',
    whatsapp_message TEXT NULL,
    raw_json MEDIUMTEXT NULL,
    lead_score INT NOT NULL DEFAULT 0,
    score_reason TEXT NULL,
    assigned_to VARCHAR(120) NULL,
    requested_service VARCHAR(120) NULL,
    contact_name VARCHAR(190) NULL,
    contact_position VARCHAR(120) NULL,
    package_type VARCHAR(80) NULL,
    package_price VARCHAR(80) NULL,
    -- İhtiyaç bilgileri
    has_domain TINYINT(1) NOT NULL DEFAULT 0,
    has_hosting TINYINT(1) NOT NULL DEFAULT 0,
    has_logo TINYINT(1) NOT NULL DEFAULT 0,
    has_photos TINYINT(1) NOT NULL DEFAULT 0,
    has_content TINYINT(1) NOT NULL DEFAULT 0,
    need_multilang TINYINT(1) NOT NULL DEFAULT 0,
    need_appointment TINYINT(1) NOT NULL DEFAULT 0,
    need_online_payment TINYINT(1) NOT NULL DEFAULT 0,
    need_blog TINYINT(1) NOT NULL DEFAULT 0,
    need_gallery TINYINT(1) NOT NULL DEFAULT 0,
    -- İletişim / WhatsApp takibi
    next_followup_at DATETIME NULL,
    last_contact_at DATETIME NULL,
    whatsapp_sent_at DATETIME NULL,
    message_count INT NOT NULL DEFAULT 0,
    last_message_type VARCHAR(50) NULL,
    last_message_text TEXT NULL,
    -- Teklif / ciro
    estimated_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    net_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    deposit_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    order_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_status VARCHAR(80) NOT NULL DEFAULT 'Ödeme alınacak',
    order_status VARCHAR(100) NOT NULL DEFAULT 'Sipariş oluşturulmadı',
    offer_sent TINYINT(1) NOT NULL DEFAULT 0,
    offer_sent_at DATETIME NULL,
    -- Müşteri / sözleşme
    customer_name VARCHAR(190) NULL,
    customer_title VARCHAR(190) NULL,
    customer_tax_info VARCHAR(255) NULL,
    customer_address TEXT NULL,
    tracking_token VARCHAR(64) NULL,
    contract_status VARCHAR(80) NOT NULL DEFAULT 'Sözleşme yok',
    written_approval TINYINT(1) NOT NULL DEFAULT 0,
    signed_by_company TINYINT(1) NOT NULL DEFAULT 0,
    signed_by_customer TINYINT(1) NOT NULL DEFAULT 0,
    extra_items_json MEDIUMTEXT NULL,
    contract_no VARCHAR(80) NULL,
    start_date DATE NULL,
    estimated_delivery_date DATE NULL,
    actual_delivery_date DATE NULL,
    revision_limit INT NOT NULL DEFAULT 2,
    revision_used INT NOT NULL DEFAULT 0,
    domain_owner VARCHAR(80) NOT NULL DEFAULT 'Müşteri',
    domain_provider VARCHAR(120) NULL,
    domain_name VARCHAR(190) NULL,
    domain_expiry_date DATE NULL,
    hosting_provider VARCHAR(120) NOT NULL DEFAULT 'Hostinger / Xtanbul',
    hosting_expiry_date DATE NULL,
    renewal_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_receipt_file VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_place_id (place_id),
    KEY idx_phone_norm (phone_normalized),
    KEY idx_city_district (city, district),
    KEY idx_status (status),
    KEY idx_priority (priority),
    KEY idx_score (lead_score),
    KEY idx_assigned (assigned_to),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Lead iletişim geçmişi ----------
CREATE TABLE IF NOT EXISTS lead_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id INT UNSIGNED NOT NULL,
    type VARCHAR(40) NOT NULL DEFAULT 'note',
    title VARCHAR(190) NULL,
    message TEXT NULL,
    old_status VARCHAR(80) NULL,
    new_status VARCHAR(80) NULL,
    created_by VARCHAR(190) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lead (lead_id),
    KEY idx_type (type),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Sözleşmeler ----------
CREATE TABLE IF NOT EXISTS contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id INT UNSIGNED NOT NULL,
    contract_no VARCHAR(60) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL DEFAULT 'Web Site Hizmet Sözleşmesi ve Teklif Formu',
    customer_name VARCHAR(190) NULL,
    customer_phone VARCHAR(80) NULL,
    customer_title VARCHAR(190) NULL,
    customer_tax_info VARCHAR(255) NULL,
    customer_address TEXT NULL,
    package_type VARCHAR(80) NULL,
    package_price VARCHAR(80) NULL,
    order_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_status VARCHAR(80) NOT NULL DEFAULT 'Ödeme alınacak',
    order_status VARCHAR(100) NOT NULL DEFAULT 'Sipariş oluşturuldu',
    extra_items_json MEDIUMTEXT NULL,
    terms MEDIUMTEXT NULL,
    written_approval TINYINT(1) NOT NULL DEFAULT 0,
    signed_by_company TINYINT(1) NOT NULL DEFAULT 0,
    signed_by_customer TINYINT(1) NOT NULL DEFAULT 0,
    start_date DATE NULL,
    estimated_delivery_date DATE NULL,
    actual_delivery_date DATE NULL,
    revision_limit INT NOT NULL DEFAULT 2,
    revision_used INT NOT NULL DEFAULT 0,
    domain_owner VARCHAR(80) NOT NULL DEFAULT 'Müşteri',
    domain_provider VARCHAR(120) NULL,
    domain_name VARCHAR(190) NULL,
    domain_expiry_date DATE NULL,
    hosting_provider VARCHAR(120) NOT NULL DEFAULT 'Hostinger / Xtanbul',
    hosting_expiry_date DATE NULL,
    renewal_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_receipt_file VARCHAR(255) NULL,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_lead_id (lead_id),
    KEY idx_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Ödemeler ----------
CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id INT UNSIGNED NOT NULL,
    contract_id INT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    method VARCHAR(80) NULL,
    note TEXT NULL,
    receipt_file VARCHAR(255) NULL,
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lead_id (lead_id),
    KEY idx_contract_id (contract_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Demo siteler ----------
CREATE TABLE IF NOT EXISTS demo_sites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    sector VARCHAR(160) NULL,
    url VARCHAR(500) NOT NULL,
    image VARCHAR(255) NULL,
    note TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active (is_active),
    KEY idx_sector (sector)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Giriş logları ----------
CREATE TABLE IF NOT EXISTS login_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NULL,
    ip VARCHAR(80) NULL,
    user_agent VARCHAR(255) NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_email (email),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Kara liste ----------
CREATE TABLE IF NOT EXISTS lead_blacklist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_normalized VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(80) NULL,
    reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Uygulama ayarları (JSON) ----------
-- package_prices, team_members, contract_terms, payment_settings,
-- operation_settings, message_templates, google_api_key panel ilk açılışta seed edilir.
CREATE TABLE IF NOT EXISTS app_settings (
    key_name VARCHAR(120) NOT NULL PRIMARY KEY,
    value_json MEDIUMTEXT NULL,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Varsayılan admin (şifre: Admin12345! — YAYINDA DEĞİŞTİRİN) ----------
INSERT INTO users (email, password_hash)
VALUES ('admin@poyraztoner.com', '$2y$12$Zc7JOeUSQvWlbpoVXxbo0e8Ql.2BFz9pYe7sUr0GQczF.kY/wTYqS')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- ---------- Örnek lead (opsiyonel) ----------
INSERT IGNORE INTO leads
    (place_id, name, sector, sub_sector, city, district, address, phone, phone_normalized, website,
     rating, review_count, status, priority, source, lead_score, score_reason, package_type, package_price,
     order_amount, amount_paid, payment_status, order_status)
VALUES
    ('sample-poyraz-demo-001','Örnek Güzellik Salonu','Güzellik & Bakım','Güzellik salonu','İstanbul','Bağcılar',
     'Bağcılar / İstanbul','+90 555 000 00 00','905550000000','',4.7,86,'Aranmadı','Sıcak','Örnek Data',92,
     'Telefon var, web sitesi yok, yorum sayısı yüksek, sıcak sektör','onepage','4.999 TL',4999,0,
     'Ödeme alınacak','Sipariş oluşturulmadı');

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- KURULUM BİTTİ. Panel: /panel/login.php  ·  Site admin: /admin.php
-- ============================================================
