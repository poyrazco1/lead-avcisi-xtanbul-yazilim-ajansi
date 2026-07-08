-- Lead Avcısı Panel v3.4 Satış CRM Schema
-- Panel ilk açılışta tabloları otomatik oluşturur. Bu dosya manuel kurulum/yedek içindir.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    place_id VARCHAR(190) NULL,
    name VARCHAR(255) NOT NULL,
    sector VARCHAR(120) NULL,
    sub_sector VARCHAR(190) NULL,
    city VARCHAR(100) NULL,
    district VARCHAR(120) NULL,
    address TEXT NULL,
    phone VARCHAR(80) NULL,
    phone_normalized VARCHAR(50) NULL,
    website VARCHAR(255) NULL,
    maps_url VARCHAR(500) NULL,
    rating DECIMAL(3,2) NULL,
    review_count INT NULL,
    status VARCHAR(80) NOT NULL DEFAULT 'Aranmadı',
    note TEXT NULL,
    source VARCHAR(80) NOT NULL DEFAULT 'Google Places',
    whatsapp_message TEXT NULL,
    raw_json MEDIUMTEXT NULL,
    lead_score INT NOT NULL DEFAULT 0,
    score_reason TEXT NULL,
    assigned_to VARCHAR(120) NULL,
    package_type VARCHAR(80) NULL,
    package_price VARCHAR(80) NULL,
    next_followup_at DATETIME NULL,
    last_contact_at DATETIME NULL,
    message_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_place_id (place_id),
    KEY idx_phone_norm (phone_normalized),
    KEY idx_city_district (city, district),
    KEY idx_status (status),
    KEY idx_score (lead_score),
    KEY idx_assigned (assigned_to),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_blacklist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_normalized VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(80) NULL,
    reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
    key_name VARCHAR(120) NOT NULL PRIMARY KEY,
    value_json MEDIUMTEXT NULL,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (email, password_hash)
VALUES ('admin@poyraztoner.com', '$2y$12$Zc7JOeUSQvWlbpoVXxbo0e8Ql.2BFz9pYe7sUr0GQczF.kY/wTYqS')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- v3.6 sözleşme / ciro / takip alanları
ALTER TABLE leads ADD COLUMN customer_name VARCHAR(190) NULL;
ALTER TABLE leads ADD COLUMN customer_title VARCHAR(190) NULL;
ALTER TABLE leads ADD COLUMN customer_tax_info VARCHAR(255) NULL;
ALTER TABLE leads ADD COLUMN customer_address TEXT NULL;
ALTER TABLE leads ADD COLUMN order_amount DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN payment_status VARCHAR(80) NOT NULL DEFAULT 'Ödeme alınacak';
ALTER TABLE leads ADD COLUMN order_status VARCHAR(100) NOT NULL DEFAULT 'Sipariş oluşturulmadı';
ALTER TABLE leads ADD COLUMN tracking_token VARCHAR(64) NULL;
ALTER TABLE leads ADD COLUMN contract_status VARCHAR(80) NOT NULL DEFAULT 'Sözleşme yok';
ALTER TABLE leads ADD COLUMN written_approval TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN signed_by_company TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN signed_by_customer TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN extra_items_json MEDIUMTEXT NULL;

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
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_lead_id (lead_id),
  KEY idx_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO leads (place_id, name, sector, sub_sector, city, district, address, phone, phone_normalized, website, rating, review_count, status, source, lead_score, score_reason, assigned_to, package_type, package_price, order_amount, amount_paid, payment_status, order_status)
VALUES ('sample-poyraz-demo-001','Örnek Güzellik Salonu','Güzellik & Bakım','Güzellik salonu','İstanbul','Bağcılar','Bağcılar / İstanbul','+90 555 000 00 00','905550000000','',4.7,86,'Aranmadı','Örnek Data',92,'Telefon var, web sitesi yok, yorum sayısı yüksek, sıcak sektör','','onepage','4.999 TL',4999,0,'Ödeme alınacak','Sipariş oluşturulmadı');

-- v4.4 güvenlik / ödeme / teslim / yenileme / demo site yönetimi
ALTER TABLE leads ADD COLUMN contract_no VARCHAR(80) NULL;
ALTER TABLE leads ADD COLUMN start_date DATE NULL;
ALTER TABLE leads ADD COLUMN estimated_delivery_date DATE NULL;
ALTER TABLE leads ADD COLUMN actual_delivery_date DATE NULL;
ALTER TABLE leads ADD COLUMN revision_limit INT NOT NULL DEFAULT 2;
ALTER TABLE leads ADD COLUMN revision_used INT NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN domain_owner VARCHAR(80) NOT NULL DEFAULT 'Müşteri';
ALTER TABLE leads ADD COLUMN domain_provider VARCHAR(120) NULL;
ALTER TABLE leads ADD COLUMN domain_name VARCHAR(190) NULL;
ALTER TABLE leads ADD COLUMN domain_expiry_date DATE NULL;
ALTER TABLE leads ADD COLUMN hosting_provider VARCHAR(120) NOT NULL DEFAULT 'Hostinger / Xtanbul';
ALTER TABLE leads ADD COLUMN hosting_expiry_date DATE NULL;
ALTER TABLE leads ADD COLUMN renewal_fee DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN payment_receipt_file VARCHAR(255) NULL;

ALTER TABLE contracts ADD COLUMN start_date DATE NULL;
ALTER TABLE contracts ADD COLUMN estimated_delivery_date DATE NULL;
ALTER TABLE contracts ADD COLUMN actual_delivery_date DATE NULL;
ALTER TABLE contracts ADD COLUMN revision_limit INT NOT NULL DEFAULT 2;
ALTER TABLE contracts ADD COLUMN revision_used INT NOT NULL DEFAULT 0;
ALTER TABLE contracts ADD COLUMN domain_owner VARCHAR(80) NOT NULL DEFAULT 'Müşteri';
ALTER TABLE contracts ADD COLUMN domain_provider VARCHAR(120) NULL;
ALTER TABLE contracts ADD COLUMN domain_name VARCHAR(190) NULL;
ALTER TABLE contracts ADD COLUMN domain_expiry_date DATE NULL;
ALTER TABLE contracts ADD COLUMN hosting_provider VARCHAR(120) NOT NULL DEFAULT 'Hostinger / Xtanbul';
ALTER TABLE contracts ADD COLUMN hosting_expiry_date DATE NULL;
ALTER TABLE contracts ADD COLUMN renewal_fee DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE contracts ADD COLUMN payment_receipt_file VARCHAR(255) NULL;

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

-- ============================================================
-- v5 CRM derinleştirme (güvenli migration — panel açılışta ensure_column ile de eklenir)
-- MySQL 8+/MariaDB 10.4+ IF NOT EXISTS destekler; eski sürümde mevcut kolonlarda hatayı yoksayın.
-- ============================================================
ALTER TABLE leads ADD COLUMN IF NOT EXISTS contact_name VARCHAR(190) NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS contact_position VARCHAR(120) NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS whatsapp_phone VARCHAR(80) NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS email VARCHAR(190) NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS neighborhood VARCHAR(160) NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS instagram VARCHAR(190) NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS facebook VARCHAR(190) NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS website_quality VARCHAR(60) NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS competitor_density VARCHAR(40) NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS priority VARCHAR(20) NOT NULL DEFAULT 'Ilık';
ALTER TABLE leads ADD COLUMN IF NOT EXISTS close_probability INT NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS requested_service VARCHAR(120) NULL;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS has_domain TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS has_hosting TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS has_logo TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS has_photos TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS has_content TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS need_multilang TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS need_appointment TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS need_online_payment TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS need_blog TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS need_gallery TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS estimated_amount DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS net_amount DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS deposit_amount DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS offer_sent TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS offer_sent_at DATETIME NULL;

-- Lead iletişim geçmişi / aktivite kaydı
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
