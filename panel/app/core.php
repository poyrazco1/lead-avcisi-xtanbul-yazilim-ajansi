<?php
declare(strict_types=1);

if (defined('LEAD_API')) {
    @ini_set('display_errors', '0');
    @error_reporting(E_ALL);
    if (!ob_get_level()) { ob_start(); }
    set_exception_handler(function(Throwable $e): void {
        while (ob_get_level()) { @ob_end_clean(); }
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => 'Sunucu tarafında PHP hatası oluştu: ' . $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    });
    register_shutdown_function(function(): void {
        $e = error_get_last();
        $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if ($e && in_array((int)$e['type'], $fatal, true)) {
            while (ob_get_level()) { @ob_end_clean(); }
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'ok' => false,
                'error' => 'Sunucu fatal PHP hatası: ' . ($e['message'] ?? 'Bilinmeyen hata'),
                'file' => basename((string)($e['file'] ?? '')),
                'line' => $e['line'] ?? null,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    });
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

$config = require __DIR__ . '/config.php';
@date_default_timezone_set($config['timezone'] ?? 'Europe/Istanbul');
$GLOBALS['lead_db_error'] = '';
$GLOBALS['lead_bootstrap_done'] = false;

function cfg(?string $key = null) {
    global $config;
    if ($key === null) return $config;
    $parts = explode('.', $key);
    $value = $config;
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) return null;
        $value = $value[$part];
    }
    return $value;
}

function lead_lower(string $v): string {
    return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
}

function start_app_session(): void {
    $name = cfg('session_name') ?: 'lead_avcisi_session';
    if (session_status() === PHP_SESSION_NONE) {
        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function csrf_token(): string {
    start_app_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(?string $token): bool {
    start_app_session();
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function db(): ?mysqli {
    static $mysqli = false;
    if ($mysqli !== false) return $mysqli;
    if (!extension_loaded('mysqli')) {
        $GLOBALS['lead_db_error'] = 'mysqli extension kapalı';
        $mysqli = null;
        return null;
    }
    $d = cfg('db');
    mysqli_report(MYSQLI_REPORT_OFF);
    $m = @new mysqli($d['host'], $d['user'], $d['pass'], $d['name'], (int)($d['port'] ?? 3306));
    if ($m->connect_errno) {
        $GLOBALS['lead_db_error'] = 'DB bağlantı hatası: ' . $m->connect_error;
        $mysqli = null;
        return null;
    }
    $m->set_charset($d['charset'] ?? 'utf8mb4');
    $mysqli = $m;
    return $mysqli;
}
function db_last_error(): string { return (string)($GLOBALS['lead_db_error'] ?? ''); }
function has_db(): bool { return db() instanceof mysqli; }

function ensure_column(mysqli $m, string $table, string $column, string $definition): void {
    $tableEsc = $m->real_escape_string($table);
    $colEsc = $m->real_escape_string($column);
    $res = $m->query("SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$colEsc}'");
    if ($res && $res->num_rows > 0) return;
    @$m->query("ALTER TABLE `{$tableEsc}` ADD COLUMN `{$colEsc}` {$definition}");
}

function bootstrap_app(): void {
    if (!empty($GLOBALS['lead_bootstrap_done'])) return;
    $GLOBALS['lead_bootstrap_done'] = true;
    $m = db();
    if (!$m) { ensure_data_files(); return; }

    $m->query("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $m->query("CREATE TABLE IF NOT EXISTS leads (
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
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_place_id (place_id),
        KEY idx_phone_norm (phone_normalized),
        KEY idx_city_district (city, district),
        KEY idx_status (status),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    ensure_column($m, 'leads', 'lead_score', 'INT NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'score_reason', 'TEXT NULL');
    ensure_column($m, 'leads', 'assigned_to', 'VARCHAR(120) NULL');
    ensure_column($m, 'leads', 'package_type', 'VARCHAR(80) NULL');
    ensure_column($m, 'leads', 'package_price', 'VARCHAR(80) NULL');
    ensure_column($m, 'leads', 'next_followup_at', 'DATETIME NULL');
    ensure_column($m, 'leads', 'last_contact_at', 'DATETIME NULL');
    ensure_column($m, 'leads', 'message_count', 'INT NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'customer_name', 'VARCHAR(190) NULL');
    ensure_column($m, 'leads', 'customer_title', 'VARCHAR(190) NULL');
    ensure_column($m, 'leads', 'customer_tax_info', 'VARCHAR(255) NULL');
    ensure_column($m, 'leads', 'customer_address', 'TEXT NULL');
    ensure_column($m, 'leads', 'order_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'amount_paid', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'payment_status', "VARCHAR(80) NOT NULL DEFAULT 'Ödeme alınacak'");
    ensure_column($m, 'leads', 'order_status', "VARCHAR(100) NOT NULL DEFAULT 'Sipariş oluşturulmadı'");
    ensure_column($m, 'leads', 'tracking_token', 'VARCHAR(64) NULL');
    ensure_column($m, 'leads', 'contract_status', "VARCHAR(80) NOT NULL DEFAULT 'Sözleşme yok'");
    ensure_column($m, 'leads', 'written_approval', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'signed_by_company', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'signed_by_customer', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'extra_items_json', 'MEDIUMTEXT NULL');
    ensure_column($m, 'leads', 'contract_no', 'VARCHAR(80) NULL');
    ensure_column($m, 'leads', 'start_date', 'DATE NULL');
    ensure_column($m, 'leads', 'estimated_delivery_date', 'DATE NULL');
    ensure_column($m, 'leads', 'actual_delivery_date', 'DATE NULL');
    ensure_column($m, 'leads', 'revision_limit', 'INT NOT NULL DEFAULT 2');
    ensure_column($m, 'leads', 'revision_used', 'INT NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'domain_owner', "VARCHAR(80) NOT NULL DEFAULT 'Müşteri'");
    ensure_column($m, 'leads', 'domain_provider', 'VARCHAR(120) NULL');
    ensure_column($m, 'leads', 'domain_name', 'VARCHAR(190) NULL');
    ensure_column($m, 'leads', 'domain_expiry_date', 'DATE NULL');
    ensure_column($m, 'leads', 'hosting_provider', "VARCHAR(120) NOT NULL DEFAULT 'Hostinger / Xtanbul'");
    ensure_column($m, 'leads', 'hosting_expiry_date', 'DATE NULL');
    ensure_column($m, 'leads', 'renewal_fee', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'payment_receipt_file', 'VARCHAR(255) NULL');

    // --- v5 CRM derinleştirme kolonları (güvenli migration, eski veri korunur) ---
    ensure_column($m, 'leads', 'contact_name', 'VARCHAR(190) NULL');
    ensure_column($m, 'leads', 'contact_position', 'VARCHAR(120) NULL');
    ensure_column($m, 'leads', 'whatsapp_phone', 'VARCHAR(80) NULL');
    ensure_column($m, 'leads', 'email', 'VARCHAR(190) NULL');
    ensure_column($m, 'leads', 'neighborhood', 'VARCHAR(160) NULL');
    ensure_column($m, 'leads', 'instagram', 'VARCHAR(190) NULL');
    ensure_column($m, 'leads', 'facebook', 'VARCHAR(190) NULL');
    ensure_column($m, 'leads', 'website_quality', 'VARCHAR(60) NULL');
    ensure_column($m, 'leads', 'competitor_density', 'VARCHAR(40) NULL');
    ensure_column($m, 'leads', 'priority', "VARCHAR(20) NOT NULL DEFAULT 'Ilık'");
    ensure_column($m, 'leads', 'close_probability', 'INT NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'requested_service', 'VARCHAR(120) NULL');
    ensure_column($m, 'leads', 'has_domain', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'has_hosting', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'has_logo', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'has_photos', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'has_content', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'need_multilang', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'need_appointment', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'need_online_payment', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'need_blog', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'need_gallery', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'estimated_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'net_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'discount_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'deposit_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'offer_sent', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($m, 'leads', 'offer_sent_at', 'DATETIME NULL');
    ensure_column($m, 'leads', 'whatsapp_sent_at', 'DATETIME NULL');
    ensure_column($m, 'leads', 'last_message_type', 'VARCHAR(50) NULL');
    ensure_column($m, 'leads', 'last_message_text', 'TEXT NULL');

    $m->query("CREATE TABLE IF NOT EXISTS lead_activities (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $m->query("CREATE TABLE IF NOT EXISTS contracts (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");


    ensure_column($m, 'contracts', 'start_date', 'DATE NULL');
    ensure_column($m, 'contracts', 'estimated_delivery_date', 'DATE NULL');
    ensure_column($m, 'contracts', 'actual_delivery_date', 'DATE NULL');
    ensure_column($m, 'contracts', 'revision_limit', 'INT NOT NULL DEFAULT 2');
    ensure_column($m, 'contracts', 'revision_used', 'INT NOT NULL DEFAULT 0');
    ensure_column($m, 'contracts', 'domain_owner', "VARCHAR(80) NOT NULL DEFAULT 'Müşteri'");
    ensure_column($m, 'contracts', 'domain_provider', 'VARCHAR(120) NULL');
    ensure_column($m, 'contracts', 'domain_name', 'VARCHAR(190) NULL');
    ensure_column($m, 'contracts', 'domain_expiry_date', 'DATE NULL');
    ensure_column($m, 'contracts', 'hosting_provider', "VARCHAR(120) NOT NULL DEFAULT 'Hostinger / Xtanbul'");
    ensure_column($m, 'contracts', 'hosting_expiry_date', 'DATE NULL');
    ensure_column($m, 'contracts', 'renewal_fee', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
    ensure_column($m, 'contracts', 'payment_receipt_file', 'VARCHAR(255) NULL');

    $m->query("CREATE TABLE IF NOT EXISTS payments (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $m->query("CREATE TABLE IF NOT EXISTS demo_sites (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(190) NOT NULL,
        sector VARCHAR(160) NULL,
        url VARCHAR(500) NOT NULL,
        image VARCHAR(255) NULL,
        note TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_active (is_active), KEY idx_sector (sector)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $m->query("CREATE TABLE IF NOT EXISTS login_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NULL,
        ip VARCHAR(80) NULL,
        user_agent VARCHAR(255) NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_email (email), KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $m->query("CREATE TABLE IF NOT EXISTS lead_blacklist (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        phone_normalized VARCHAR(50) NOT NULL UNIQUE,
        phone VARCHAR(80) NULL,
        reason VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $m->query("CREATE TABLE IF NOT EXISTS app_settings (
        key_name VARCHAR(120) NOT NULL PRIMARY KEY,
        value_json MEDIUMTEXT NULL,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $email = (string)cfg('admin_email');
    $res = $m->query("SELECT id FROM users WHERE email='" . $m->real_escape_string($email) . "' LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        $hash = password_hash((string)cfg('admin_password'), PASSWORD_DEFAULT);
        $stmt = $m->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $email, $hash); $stmt->execute(); $stmt->close(); }
    }
    seed_default_settings();
    seed_sample_lead_once();
}


function seed_sample_lead_once(): void {
    $m = db();
    if (!$m) return;
    $res = $m->query('SELECT COUNT(*) c FROM leads');
    if ($res && (int)$res->fetch_assoc()['c'] > 0) return;
    save_lead([
        'place_id'=>'sample-poyraz-demo-001',
        'name'=>'Örnek Güzellik Salonu',
        'sector'=>'Güzellik & Bakım',
        'sub_sector'=>'Güzellik salonu',
        'city'=>'İstanbul',
        'district'=>'Bağcılar',
        'address'=>'Bağcılar / İstanbul',
        'phone'=>'+90 555 000 00 00',
        'website'=>'',
        'maps_url'=>'',
        'rating'=>4.7,
        'review_count'=>86,
        'source'=>'Örnek Data',
        'assigned_to'=>'',
        'package_type'=>'onepage',
        'package_price'=>package_price('onepage'),
        'status'=>'Aranmadı',
        'order_amount'=>4999,
        'amount_paid'=>0,
        'payment_status'=>'Ödeme alınacak',
        'order_status'=>'Sipariş oluşturulmadı',
        'raw'=>['sample'=>true],
    ]);
}

function seed_default_settings(): void {
    $m = db();
    if (!$m) return;
    $defaults = [
        'package_prices' => package_default_prices(),
        'team_members' => team_members_default(),
        'contract_terms' => default_contract_terms(),
        'payment_settings' => default_payment_settings(),
        'operation_settings' => default_operation_settings(),
        'message_templates' => default_message_templates(),
    ];
    foreach ($defaults as $k => $v) {
        $exists = $m->query("SELECT key_name FROM app_settings WHERE key_name='".$m->real_escape_string($k)."' LIMIT 1");
        if ($exists && $exists->num_rows > 0) continue;
        $json = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmt = $m->prepare('INSERT INTO app_settings (key_name, value_json) VALUES (?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $k, $json); $stmt->execute(); $stmt->close(); }
    }
}

function ensure_data_files(): void {
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    foreach (['leads.json' => '[]', 'blacklist.json' => '[]', 'settings.json' => '{}'] as $file => $content) {
        $path = $dir . '/' . $file;
        if (!file_exists($path)) @file_put_contents($path, $content);
    }
}

function require_login(): void {
    start_app_session();
    if (empty($_SESSION['user_email'])) {
        if (defined('LEAD_API')) json_response(['ok' => false, 'error' => 'Oturum bulunamadı. Lütfen tekrar giriş yap.'], 401);
        header('Location: login.php'); exit;
    }
}
function is_logged_in(): bool { start_app_session(); return !empty($_SESSION['user_email']); }

function login_log(string $email, bool $success): void {
    $m=db(); if(!$m) return;
    $ip=(string)($_SERVER['REMOTE_ADDR'] ?? ''); $ua=substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''),0,250); $ok=$success?1:0;
    $stmt=$m->prepare('INSERT INTO login_logs (email, ip, user_agent, success) VALUES (?,?,?,?)');
    if($stmt){$stmt->bind_param('sssi',$email,$ip,$ua,$ok);$stmt->execute();$stmt->close();}
}
function too_many_failed_logins(string $email): bool {
    $m=db(); if(!$m) return false;
    $stmt=$m->prepare("SELECT COUNT(*) c FROM login_logs WHERE email=? AND success=0 AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    if(!$stmt) return false; $stmt->bind_param('s',$email); $stmt->execute(); $stmt->bind_result($c); $stmt->fetch(); $stmt->close(); return (int)$c>=8;
}
function change_admin_password(string $current, string $newEmail, string $newPassword): array {
    start_app_session(); $email=(string)($_SESSION['user_email'] ?? cfg('admin_email'));
    if(strlen($newPassword)<8) return ['ok'=>false,'error'=>'Yeni şifre en az 8 karakter olmalı.'];
    if(!login_user($email,$current)) return ['ok'=>false,'error'=>'Mevcut şifre hatalı.'];
    $newEmail=trim(lead_lower($newEmail ?: $email)); $hash=password_hash($newPassword,PASSWORD_DEFAULT); $m=db();
    if($m){$stmt=$m->prepare('UPDATE users SET email=?, password_hash=? WHERE email=? LIMIT 1'); if(!$stmt)return ['ok'=>false,'error'=>$m->error]; $stmt->bind_param('sss',$newEmail,$hash,$email); $ok=$stmt->execute(); $stmt->close(); if($ok){$_SESSION['user_email']=$newEmail;} return ['ok'=>$ok,'email'=>$newEmail];}
    return ['ok'=>false,'error'=>'Şifre değiştirme için MySQL gerekli.'];
}
function login_user(string $email, string $password): bool {
    bootstrap_app();
    $email = trim(lead_lower($email));
    if(too_many_failed_logins($email)) { login_log($email,false); return false; }
    $m = db();
    if ($m) {
        $stmt = $m->prepare('SELECT email, password_hash FROM users WHERE email=? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($dbEmail, $hash);
        $found = $stmt->fetch();
        $stmt->close();
        if ($found && password_verify($password, (string)$hash)) {
            login_log($email,true); start_app_session(); session_regenerate_id(true); $_SESSION['user_email'] = $dbEmail; return true;
        }
        login_log($email,false); return false;
    }
    if ($email === lead_lower((string)cfg('admin_email')) && hash_equals((string)cfg('admin_password'), $password)) {
        start_app_session(); session_regenerate_id(true); $_SESSION['user_email'] = cfg('admin_email'); return true;
    }
    return false;
}

function logout_user(): void {
    start_app_session(); $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function json_response($data, int $status = 200): void {
    if (defined('LEAD_API')) { while (ob_get_level()) { @ob_end_clean(); } }
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}
function input_json(): array { $raw = file_get_contents('php://input'); $data = json_decode($raw ?: '', true); return is_array($data) ? $data : []; }
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function normalize_phone(?string $phone): string {
    $digits = preg_replace('/\D+/', '', (string)$phone);
    if (str_starts_with($digits, '0090')) $digits = substr($digits, 2);
    if (str_starts_with($digits, '90')) return $digits;
    if (str_starts_with($digits, '0')) return '90' . substr($digits, 1);
    if (strlen($digits) === 10) return '90' . $digits;
    return $digits;
}
function whatsapp_url(string $phone, string $message): string { return 'https://wa.me/' . rawurlencode(normalize_phone($phone)) . '?text=' . rawurlencode($message); }

function package_default_prices(): array {
    return [
        'onepage' => '4.999 TL',
        'onepage_appointment' => '6.999 TL',
        'multipage' => 'Teklif alınır',
        'multipage_appointment' => 'Teklif alınır',
        'multipage_panel' => 'Teklif alınır',
        'full_panel_appointment' => 'Teklif alınır',
    ];
}
function team_members_default(): array { return []; }
function default_payment_settings(): array { return ['iban'=>'','account_holder'=>'Xtanbul Yazılım Agent','bank_name'=>'','payment_note'=>'Açıklama kısmına sözleşme numarası ve işletme adını yazınız.','deposit_rate'=>'50']; }
function default_operation_settings(): array { return ['default_revision_limit'=>2,'default_delivery_days'=>7,'renewal_warning_days'=>60,'default_renewal_fee'=>'Teklif alınır']; }
function contract_sequence_no(): string {
    $m=db(); $year=date('Y');
    if($m){ $r=$m->query("SELECT COUNT(*) c FROM contracts WHERE YEAR(created_at)=".(int)$year); $n=1; if($r){$n=(int)$r->fetch_assoc()['c']+1;} return 'XTN-'.$year.'-'.str_pad((string)$n,4,'0',STR_PAD_LEFT); }
    return 'XTN-'.$year.'-'.strtoupper(substr(bin2hex(random_bytes(2)),0,4));
}


function setting_get(string $key, $fallback = null) {
    bootstrap_app();
    $m = db();
    if ($m) {
        $stmt = $m->prepare('SELECT value_json FROM app_settings WHERE key_name=? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $key); $stmt->execute(); $stmt->bind_result($json);
            $found = $stmt->fetch(); $stmt->close();
            if ($found) { $data = json_decode((string)$json, true); return $data === null ? $fallback : $data; }
        }
        return $fallback;
    }
    ensure_data_files();
    $all = json_decode((string)@file_get_contents(__DIR__ . '/../data/settings.json'), true);
    return is_array($all) && array_key_exists($key, $all) ? $all[$key] : $fallback;
}
function setting_set(string $key, $value): bool {
    bootstrap_app();
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $m = db();
    if ($m) {
        $stmt = $m->prepare('INSERT INTO app_settings (key_name, value_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json)');
        if (!$stmt) return false;
        $stmt->bind_param('ss', $key, $json); $ok = $stmt->execute(); $stmt->close(); return $ok;
    }
    ensure_data_files();
    $path = __DIR__ . '/../data/settings.json';
    $all = json_decode((string)@file_get_contents($path), true); if (!is_array($all)) $all = [];
    $all[$key] = $value; return (bool)@file_put_contents($path, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function package_catalog(array $prices = []): array {
    $stored = setting_get('package_prices', package_default_prices());
    if (!is_array($stored)) $stored = package_default_prices();
    $prices = array_merge(package_default_prices(), $stored, $prices);
    return [
        'onepage' => ['label'=>'Tek sayfalık HTML web sitesi','price'=>$prices['onepage'] ?? '4.999 TL','desc'=>'Hızlı açılan tanıtım sayfası, hizmet özeti, konum ve WhatsApp butonu.'],
        'onepage_appointment' => ['label'=>'Tek sayfalı + randevu alabilen web sitesi','price'=>$prices['onepage_appointment'] ?? '6.999 TL','desc'=>'Tek sayfa yapı + randevu/talep formu.'],
        'multipage' => ['label'=>'Çok sayfalı web sitesi','price'=>$prices['multipage'] ?? 'Teklif alınır','desc'=>'Hakkımızda, hizmetler, galeri, iletişim gibi sayfalar.'],
        'multipage_appointment' => ['label'=>'Çok sayfalı + randevu alabilen web sitesi','price'=>$prices['multipage_appointment'] ?? 'Teklif alınır','desc'=>'Kurumsal sayfalar + randevu/talep akışı.'],
        'multipage_panel' => ['label'=>'Çok sayfalı + yönetim panelli web sitesi','price'=>$prices['multipage_panel'] ?? 'Teklif alınır','desc'=>'İçerikleri panelden düzenlenebilen web sitesi.'],
        'full_panel_appointment' => ['label'=>'Yönetim paneli + randevu sistemi olan web sitesi','price'=>$prices['full_panel_appointment'] ?? 'Teklif alınır','desc'=>'En kapsamlı paket: içerik yönetimi + randevu/talep yönetimi.'],
    ];
}
function package_label(string $package): string { $c = package_catalog(); return $c[$package]['label'] ?? $c['onepage']['label']; }
function package_price(string $package, array $prices = []): string { $c = package_catalog($prices); return $c[$package]['price'] ?? $c['onepage']['price']; }
function package_options_text(array $prices = []): string {
    $lines = ['Paket seçeneklerimiz:'];
    foreach (package_catalog($prices) as $p) $lines[] = '• ' . $p['label'] . ' — ' . $p['price'];
    return implode("\n", $lines);
}

function sector_competitor_features(string $sector, string $sub): array {
    $h = lead_lower($sector . ' ' . $sub);
    if (preg_match('/kombi|tesisat|tamir|servis|elektrik|klima|beyaz|telefon|tv/u', $h)) return ['arıza türleri','servis bölgeleri','acil arama/WhatsApp butonu','hızlı teklif formu','sık sorulan sorular'];
    if (preg_match('/güzellik|kuaför|berber|spa|estetik|lazer|tırnak|epilasyon|cilt/u', $h)) return ['hizmet listesi','öncesi/sonrası galeri','randevu butonu','kampanya alanı','Instagram bağlantısı'];
    if (preg_match('/halı|koltuk|temizlik|yıkama/u', $h)) return ['hizmet bölgeleri','fiyat alma formu','önce-sonra görselleri','hijyen/güven vurgusu','WhatsApp talep butonu'];
    if (preg_match('/sanayi|metal|cnc|imalat|üretim|plastik|medikal|tekstil|makine|pimapen|alüminyum/u', $h)) return ['üretim kabiliyeti','makine parkuru','sektörel referanslar','teklif formu','çok dilli sayfalar'];
    if (preg_match('/oto|lastik|kaporta|boya|ekspertiz|galeri|oto elektrik/u', $h)) return ['hizmet listesi','konum/yol tarifi','yapılan işler galerisi','yorum vurgusu','hızlı arama butonu'];
    if (preg_match('/doktor|klinik|diş|veteriner|sağlık|fizyoterapi/u', $h)) return ['uzmanlık alanları','randevu yönlendirmesi','ekip/doktor bilgisi','konum','sık sorulan sorular'];
    return ['hizmet açıklamaları','güven veren görseller','Google Harita/konum','tek tık WhatsApp','hızlı teklif/randevu alanı'];
}
function sector_competitor_angle(string $sector, string $sub): string {
    $features = sector_competitor_features($sector, $sub);
    return 'Rakiplerinizin güçlü sitelerinde genelde ' . implode(', ', $features) . ' bulunuyor. Bunlar müşterinin güvenini ve iletişime geçme kararını hızlandırıyor.';
}
function sector_problem_line(string $sector, string $sub): string {
    $h = lead_lower($sector . ' ' . $sub);
    if (preg_match('/kombi|tesisat|tamir|servis|elektrik|klima|beyaz|telefon|tv/u', $h)) return 'Acil servis arayan müşteriler Google’da hızlı ulaşılan, güven veren işletmeyi seçiyor. Web sitesi olmayan servisler fiyat soran müşteriyi rakibe kaptırıyor.';
    if (preg_match('/güzellik|kuaför|berber|spa|estetik|lazer|tırnak|epilasyon|cilt/u', $h)) return 'Güzellik sektöründe karar görsel kalite, hizmet listesi ve randevu kolaylığıyla veriliyor. Site salonunuzu daha güvenilir ve premium gösterir.';
    if (preg_match('/halı|koltuk|temizlik|yıkama/u', $h)) return 'Temizlik/yıkama hizmetlerinde müşteri fiyat, güven ve hızlı randevu ister. Web sitesi yoksa müşteri kararını rakipten yana verebilir.';
    if (preg_match('/sanayi|metal|cnc|imalat|üretim|plastik|medikal|tekstil|makine|pimapen|alüminyum/u', $h)) return 'Üretim ve sanayi tarafında web sitesi dijital katalog gibi çalışır. Web sitesi olmayan firma ciddi alıcı gözünde küçük görünür.';
    if (preg_match('/oto|lastik|kaporta|boya|ekspertiz|galeri|oto elektrik/u', $h)) return 'Otomotiv tarafında müşteri önce Google’da güven, hizmet listesi, konum ve hızlı iletişim arıyor.';
    if (preg_match('/doktor|klinik|diş|veteriner|sağlık|fizyoterapi/u', $h)) return 'Sağlık ve klinik tarafında güven her şeydir. Hizmetleri, konumu ve randevu yolunu net gösteren site karar sürecini etkiler.';
    return 'Müşteriler Google’da sizi bulduğunda güven veren bir web sitesi, hizmet açıklaması ve tek tık WhatsApp bağlantısı görmek istiyor.';
}
function consulting_text(array $consulting, bool $multiLang): string {
    $labels = ['ads'=>'profesyonel reklam danışmanlığı','ecommerce'=>'e-ticaret danışmanlığı','social'=>'sosyal medya danışmanlığı'];
    $items = [];
    foreach ($consulting as $c) if (isset($labels[(string)$c])) $items[] = $labels[(string)$c];
    if ($multiLang) $items[] = 'çok dilli web site kurulumu';
    return $items ? 'Ek olarak ' . implode(', ', $items) . ' hizmetleri de veriyoruz.' : '';
}
function sector_message(string $name, string $sector, string $sub, string $price = '', string $payment = '', string $package = 'onepage', array $consulting = [], bool $multiLang = false, array $packagePrices = []): string {
    $selectedPackage = package_label($package);
    $selectedPrice = $price ?: package_price($package, $packagePrices);
    if ($selectedPrice === 'Teklif alınır') $selectedPrice = package_price('onepage', $packagePrices) . ' başlangıç';
    $payment = $payment ?: (string)cfg('default_payment_step');
    $extras = consulting_text($consulting, $multiLang);
    $extrasBlock = $extras ? "\n\n{$extras}" : '';
    return "Merhaba, {$name} için yazıyorum. Google’da işletmenizi gördüm; telefon numaranız var ama web siteniz görünmüyor.\n\n" .
        sector_problem_line($sector, $sub) . "\n\n" . sector_competitor_angle($sector, $sub) . "\n\n" .
        "Şimdilik verdiğimiz başlangıç teklifinin {$selectedPackage} için olduğunu özellikle belirteyim. En düşük paketimiz {$selectedPrice}’den başlıyor. Süreç: {$payment}.\n\n" .
        package_options_text($packagePrices) . $extrasBlock . "\n\n" .
        "Uygun görürseniz bugün size sektörünüze uygun 2 örnek tasarım, paket karşılaştırması ve net ödeme adımını göndereyim. Onaylarsanız aynı gün çalışmaya başlayabiliriz.\n\n" .
        "İstemiyorsanız “istemiyorum” yazmanız yeterli, tekrar rahatsız etmeyiz.";
}

function lead_quality_score(array $lead): array {
    $score = 0; $reasons = [];
    if (!empty($lead['phone'])) { $score += 25; $reasons[] = 'telefon var'; }
    if (empty($lead['website'])) { $score += 25; $reasons[] = 'web sitesi yok'; }
    $rating = isset($lead['rating']) ? (float)$lead['rating'] : 0;
    $reviews = isset($lead['review_count']) ? (int)$lead['review_count'] : 0;
    if ($rating >= 4.5) { $score += 15; $reasons[] = 'yüksek puan'; }
    elseif ($rating >= 4.0) { $score += 10; $reasons[] = 'iyi puan'; }
    if ($reviews >= 50) { $score += 15; $reasons[] = 'yorum sayısı güçlü'; }
    elseif ($reviews >= 10) { $score += 10; $reasons[] = 'yorum var'; }
    if (!empty($lead['maps_url'])) { $score += 5; $reasons[] = 'harita linki var'; }
    if (!empty($lead['address'])) { $score += 5; $reasons[] = 'adres var'; }
    $h = lead_lower(($lead['sector'] ?? '') . ' ' . ($lead['sub_sector'] ?? ''));
    if (preg_match('/kombi|tesisat|güzellik|halı|koltuk|oto|medikal|sanayi|cnc|klinik|pimapen|telefon|beyaz/u', $h)) { $score += 10; $reasons[] = 'sıcak sektör'; }
    $score = max(0, min(100, $score));
    if ($score >= 80) $reasons[] = 'öncelikli aranmalı';
    return ['score'=>$score, 'reason'=>implode(', ', array_unique($reasons))];
}

function is_blacklisted_phone(string $phone): bool {
    $norm = normalize_phone($phone); if ($norm === '') return false;
    $m = db();
    if ($m) {
        $stmt = $m->prepare('SELECT id FROM lead_blacklist WHERE phone_normalized=? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('s', $norm); $stmt->execute(); $stmt->bind_result($id); $found = $stmt->fetch(); $stmt->close(); return (bool)$found;
    }
    ensure_data_files();
    $list = json_decode((string)@file_get_contents(__DIR__ . '/../data/blacklist.json'), true) ?: [];
    foreach ($list as $r) if (($r['phone_normalized'] ?? '') === $norm) return true;
    return false;
}
function add_blacklist_phone(string $phone, string $reason = 'Tekrar aranmasın'): void {
    $norm = normalize_phone($phone); if ($norm === '') return;
    $m = db();
    if ($m) {
        $stmt = $m->prepare('INSERT INTO lead_blacklist (phone_normalized, phone, reason) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reason=VALUES(reason)');
        if ($stmt) { $stmt->bind_param('sss', $norm, $phone, $reason); $stmt->execute(); $stmt->close(); }
        return;
    }
    ensure_data_files(); $path = __DIR__ . '/../data/blacklist.json'; $list = json_decode((string)@file_get_contents($path), true) ?: [];
    foreach ($list as &$r) if (($r['phone_normalized'] ?? '') === $norm) { $r['reason'] = $reason; @file_put_contents($path, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); return; }
    $list[] = ['phone_normalized'=>$norm,'phone'=>$phone,'reason'=>$reason,'created_at'=>date('Y-m-d H:i:s')];
    @file_put_contents($path, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function select_existing_lead_id(mysqli $m, string $field, string $value): ?int {
    if ($value === '') return null;
    if (!in_array($field, ['place_id','phone_normalized'], true)) return null;
    $stmt = $m->prepare("SELECT id FROM leads WHERE {$field}=? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $value); $stmt->execute(); $stmt->bind_result($id); $found = $stmt->fetch(); $stmt->close();
    return $found ? (int)$id : null;
}

function save_lead(array $lead): array {
    $lead['phone_normalized'] = normalize_phone($lead['phone'] ?? '');
    if (is_blacklisted_phone($lead['phone'] ?? '')) return ['saved'=>false,'duplicate'=>false,'blacklisted'=>true];
    $score = lead_quality_score($lead); $lead['lead_score'] = $score['score']; $lead['score_reason'] = $score['reason'];
    if (empty($lead['whatsapp_message'])) $lead['whatsapp_message'] = sector_message($lead['name'] ?? 'İşletmeniz', $lead['sector'] ?? '', $lead['sub_sector'] ?? '');
    $lead['package_price'] = $lead['package_price'] ?? '';
    $lead['package_type'] = $lead['package_type'] ?? 'onepage';
    $lead['assigned_to'] = $lead['assigned_to'] ?? '';

    $m = db();
    if ($m) {
        $placeIdVal = (string)($lead['place_id'] ?? ''); $phoneNormVal = (string)$lead['phone_normalized'];
        $existingId = select_existing_lead_id($m, 'place_id', $placeIdVal) ?: select_existing_lead_id($m, 'phone_normalized', $phoneNormVal);
        if ($existingId) return ['saved'=>false,'duplicate'=>true,'id'=>$existingId];
        $stmt = $m->prepare('INSERT INTO leads (place_id,name,sector,sub_sector,city,district,address,phone,phone_normalized,website,maps_url,rating,review_count,status,note,source,whatsapp_message,raw_json,lead_score,score_reason,assigned_to,package_type,package_price) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        if (!$stmt) return ['saved'=>false,'duplicate'=>false,'error'=>$m->error];
        $placeIdVal = (string)($lead['place_id'] ?? ''); $nameVal = (string)($lead['name'] ?? 'İşletme');
        $sectorVal = (string)($lead['sector'] ?? ''); $subVal = (string)($lead['sub_sector'] ?? ''); $cityVal = (string)($lead['city'] ?? ''); $districtVal = (string)($lead['district'] ?? '');
        $addressVal = (string)($lead['address'] ?? ''); $phoneVal = (string)($lead['phone'] ?? ''); $websiteVal = (string)($lead['website'] ?? ''); $mapsVal = (string)($lead['maps_url'] ?? '');
        $rating = isset($lead['rating']) && $lead['rating'] !== '' ? (float)$lead['rating'] : 0.0; $reviews = isset($lead['review_count']) && $lead['review_count'] !== '' ? (int)$lead['review_count'] : 0;
        $status = (string)($lead['status'] ?? 'Aranmadı'); $note = (string)($lead['note'] ?? ''); $source = (string)($lead['source'] ?? 'Google Places'); $waMessage = (string)($lead['whatsapp_message'] ?? '');
        $raw = json_encode($lead['raw'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); $leadScore = (int)($lead['lead_score'] ?? 0); $scoreReason = (string)($lead['score_reason'] ?? '');
        $assigned = (string)($lead['assigned_to'] ?? ''); $packageType = (string)($lead['package_type'] ?? 'onepage'); $packagePrice = (string)($lead['package_price'] ?? '');
        $stmt->bind_param('sssssssssssdisssssissss', $placeIdVal,$nameVal,$sectorVal,$subVal,$cityVal,$districtVal,$addressVal,$phoneVal,$phoneNormVal,$websiteVal,$mapsVal,$rating,$reviews,$status,$note,$source,$waMessage,$raw,$leadScore,$scoreReason,$assigned,$packageType,$packagePrice);
        $ok = $stmt->execute(); $id = $stmt->insert_id; $err = $stmt->error; $stmt->close();
        return ['saved'=>$ok,'duplicate'=>false,'id'=>$id,'error'=>$ok?null:$err];
    }
    ensure_data_files(); $path = __DIR__ . '/../data/leads.json'; $list = json_decode((string)@file_get_contents($path), true); if (!is_array($list)) $list = [];
    foreach ($list as $row) {
        if (($lead['place_id'] ?? '') && ($row['place_id'] ?? '') === $lead['place_id']) return ['saved'=>false,'duplicate'=>true,'id'=>$row['id'] ?? null];
        if (($lead['phone_normalized'] ?? '') && ($row['phone_normalized'] ?? '') === $lead['phone_normalized']) return ['saved'=>false,'duplicate'=>true,'id'=>$row['id'] ?? null];
    }
    $lead['id'] = count($list) + 1; $lead['status'] = 'Aranmadı'; $lead['note'] = ''; $lead['created_at'] = date('Y-m-d H:i:s'); $list[] = $lead;
    @file_put_contents($path, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); return ['saved'=>true,'duplicate'=>false,'id'=>$lead['id']];
}

function list_leads(array $filters = [], int $limit = 500): array {
    $m = db();
    if ($m) {
        $where = [];
        foreach (['city','district','status','sector','assigned_to'] as $f) if (!empty($filters[$f])) $where[] = "$f='" . $m->real_escape_string((string)$filters[$f]) . "'";
        if (!empty($filters['q'])) { $q = '%' . $m->real_escape_string((string)$filters['q']) . '%'; $where[] = "(name LIKE '{$q}' OR phone LIKE '{$q}' OR address LIKE '{$q}' OR note LIKE '{$q}')"; }
        if (!empty($filters['min_score'])) $where[] = 'lead_score >= ' . max(0, min(100, (int)$filters['min_score']));
        $sql = 'SELECT * FROM leads' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY lead_score DESC, id DESC LIMIT ' . max(1, min(5000, $limit));
        $res = $m->query($sql); if (!$res) return [];
        $rows = []; while ($row = $res->fetch_assoc()) { if (empty($row['lead_score'])) { $sc = lead_quality_score($row); $row['lead_score'] = $sc['score']; $row['score_reason'] = $row['score_reason'] ?: $sc['reason']; } $rows[] = $row; }
        return $rows;
    }
    ensure_data_files(); $list = json_decode((string)@file_get_contents(__DIR__ . '/../data/leads.json'), true); if (!is_array($list)) $list = [];
    return array_reverse($list);
}

function lead_name_exists(string $name): bool {
    $name = trim($name); if ($name === '') return false;
    $m = db();
    if ($m) {
        $stmt = $m->prepare('SELECT id FROM leads WHERE name=? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('s', $name); $stmt->execute(); $stmt->bind_result($id); $found = $stmt->fetch(); $stmt->close();
        return (bool)$found;
    }
    ensure_data_files();
    $list = json_decode((string)@file_get_contents(__DIR__ . '/../data/leads.json'), true) ?: [];
    foreach ($list as $r) if (trim((string)($r['name'] ?? '')) === $name) return true;
    return false;
}
function get_lead(int $id): ?array {
    $m = db();
    if ($m) {
        $id = max(1, $id);
        $res = $m->query('SELECT * FROM leads WHERE id=' . $id . ' LIMIT 1');
        if ($res) { $row = $res->fetch_assoc(); return $row ?: null; }
        return null;
    }
    foreach (list_leads([],5000) as $r) if ((int)($r['id'] ?? 0) === $id) return $r;
    return null;
}

function lead_editable_columns(): array {
    return ['status','note','assigned_to','next_followup_at','last_contact_at','package_type','package_price','whatsapp_message','customer_name','customer_title','customer_tax_info','customer_address','order_amount','amount_paid','payment_status','order_status','tracking_token','contract_status','written_approval','signed_by_company','signed_by_customer','extra_items_json','contract_no','start_date','estimated_delivery_date','actual_delivery_date','revision_limit','revision_used','domain_owner','domain_provider','domain_name','domain_expiry_date','hosting_provider','hosting_expiry_date','renewal_fee','payment_receipt_file',
        // v5 CRM alanları
        'name','sector','sub_sector','city','district','address','phone','website','maps_url','rating','review_count','lead_score','score_reason',
        'contact_name','contact_position','whatsapp_phone','email','neighborhood','instagram','facebook','website_quality','competitor_density','priority','close_probability','requested_service',
        'has_domain','has_hosting','has_logo','has_photos','has_content','need_multilang','need_appointment','need_online_payment','need_blog','need_gallery',
        'estimated_amount','net_amount','discount_amount','deposit_amount','offer_sent','offer_sent_at',
        'whatsapp_sent_at','last_message_type','last_message_text'];
}
function update_lead_fields(int $id, array $fields, ?string $actor = null): bool {
    $allowed = lead_editable_columns();
    // Durum değişimini activity log'a düşürmek için eski durumu al
    $logStatus = array_key_exists('status', $fields);
    $oldStatus = '';
    if ($logStatus) { $prev = get_lead($id); $oldStatus = (string)($prev['status'] ?? ''); }
    $m = db();
    if ($m) {
        $sets=[]; $vals=[]; $types='';
        foreach ($fields as $k=>$v) if (in_array($k,$allowed,true)) { $sets[]="{$k}=?"; $vals[]=(string)$v; $types.='s'; }
        if (!$sets) return false;
        $sets[] = 'updated_at=NOW()';
        $sql='UPDATE leads SET '.implode(',', $sets).' WHERE id=?'; $types.='i'; $vals[]=$id;
        $stmt=$m->prepare($sql); if(!$stmt) return false; $stmt->bind_param($types, ...$vals); $ok=$stmt->execute(); $stmt->close();
        if (($fields['status'] ?? '') === 'Tekrar aranmasın') { $lead=get_lead($id); if ($lead) add_blacklist_phone((string)($lead['phone'] ?? ''), 'Kullanıcı tekrar aranmasın yaptı'); }
        if ($ok && $logStatus && (string)$fields['status'] !== $oldStatus) {
            log_activity($id, 'status_change', 'Durum güncellendi', ($oldStatus?:'—').' → '.(string)$fields['status'], $oldStatus, (string)$fields['status'], $actor);
        }
        return $ok;
    }
    ensure_data_files(); $path=__DIR__.'/../data/leads.json'; $list=json_decode((string)@file_get_contents($path), true) ?: []; $ok=false;
    foreach ($list as &$r) if ((int)($r['id'] ?? 0)===$id) { foreach($fields as $k=>$v) if(in_array($k,$allowed,true)) $r[$k]=$v; $r['updated_at']=date('Y-m-d H:i:s'); $ok=true; if (($fields['status'] ?? '')==='Tekrar aranmasın') add_blacklist_phone((string)($r['phone']??''),'Kullanıcı tekrar aranmasın yaptı'); }
    @file_put_contents($path, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    if ($ok && $logStatus && (string)$fields['status'] !== $oldStatus) log_activity($id, 'status_change', 'Durum güncellendi', ($oldStatus?:'—').' → '.(string)$fields['status'], $oldStatus, (string)$fields['status'], $actor);
    return $ok;
}

function bump_message_count(int $id, string $type = '', string $text = ''): void {
    $now = date('Y-m-d H:i:s');
    $m = db();
    if ($m) {
        $stmt = $m->prepare('UPDATE leads SET message_count=message_count+1, last_contact_at=NOW(), whatsapp_sent_at=NOW(), last_message_type=?, last_message_text=? WHERE id=?');
        if ($stmt) { $t=$type; $x=$text; $iid=max(1,$id); $stmt->bind_param('ssi',$t,$x,$iid); $stmt->execute(); $stmt->close(); }
        return;
    }
    ensure_data_files(); $path=__DIR__.'/../data/leads.json'; $list=json_decode((string)@file_get_contents($path), true) ?: [];
    foreach ($list as &$r) if ((int)($r['id'] ?? 0)===$id) { $r['message_count']=(int)($r['message_count']??0)+1; $r['last_contact_at']=$now; $r['whatsapp_sent_at']=$now; $r['last_message_type']=$type; $r['last_message_text']=$text; }
    @file_put_contents($path, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
/**
 * WhatsApp mesaj türlerini kanonik hale getir (yeni + eski adlar desteklenir).
 */
function whatsapp_type_canonical(string $type): string {
    $t = strtolower(trim($type));
    $map = [
        'first_contact'=>'first','first'=>'first','ilk'=>'first','manual'=>'manual',
        'follow_up'=>'followup','followup'=>'followup','takip'=>'followup',
        'offer'=>'offer','detail'=>'offer','teklif'=>'offer',
        'payment'=>'payment','odeme'=>'payment',
        'contract'=>'contract','sozlesme'=>'contract',
        'delivery'=>'delivery','teslim'=>'delivery',
        'renewal'=>'renewal','yenileme'=>'renewal',
        'tracking'=>'tracking',
    ];
    return $map[$t] ?? 'first';
}
/**
 * WhatsApp mesajı gönderildi olarak işaretle:
 *  - message_count++, last_contact_at, whatsapp_sent_at, last_message_type/text
 *  - net durum kurallarıyla durumu ileri taşır (asla geri düşürmez)
 *  - lead_activities'e kayıt düşürür
 */
function mark_whatsapp_sent(int $id, string $type = 'first_contact'): array {
    $lead = get_lead($id);
    if (!$lead) return ['ok'=>false,'error'=>'Lead bulunamadı.'];
    $canon = whatsapp_type_canonical($type);
    // Kanonik tür → mesaj üretici tipi (message_for_lead)
    $msgTypeMap = ['first'=>'first','manual'=>'first','followup'=>'followup','offer'=>'detail','payment'=>'payment','contract'=>'contract','delivery'=>'delivery','renewal'=>'renewal','tracking'=>'tracking'];
    $msgText = message_for_lead($lead, $msgTypeMap[$canon] ?? 'first');
    $titles = [
        'first'=>'WhatsApp mesajı gönderildi','manual'=>'WhatsApp mesajı gönderildi olarak işaretlendi',
        'followup'=>'Takip mesajı gönderildi','offer'=>'Teklif mesajı gönderildi','payment'=>'Ödeme mesajı gönderildi',
        'contract'=>'Sözleşme mesajı gönderildi','delivery'=>'Teslim / yayın mesajı gönderildi','renewal'=>'Yenileme mesajı gönderildi','tracking'=>'Takip linki gönderildi',
    ];
    $current = (string)($lead['status'] ?? '');
    $order = lead_pipeline_statuses();
    $advanceTo = function(string $target) use ($current, $order) : ?string {
        if ($target === '') return null;
        $ci = array_search($current, $order, true); $ti = array_search($target, $order, true);
        return ($ti !== false && ($ci === false || $ti > $ci)) ? $target : null;
    };
    // Durum güncelleme kuralları (net)
    $newStatus = $current; $target = null;
    switch ($canon) {
        case 'first':
        case 'manual':
            // Yalnızca Yeni Lead / Uygunluk kontrolü ise ilerlet
            if (in_array($current, ['Aranmadı','Uygunluk kontrolü'], true)) $target = 'WhatsApp gönderildi';
            break;
        case 'offer':   $target = $advanceTo('Teklif gönderildi'); break;
        case 'payment': $target = $advanceTo('Ödeme bekleniyor'); break;
        case 'contract':$target = $advanceTo('Sözleşme gönderildi'); break;
        case 'delivery':$target = $advanceTo('Yayına hazır'); break;
        case 'followup':
        case 'renewal':
        case 'tracking': $target = null; break;
    }
    // Alanları güncelle (message_count, last_contact_at, whatsapp_sent_at, last_message_type/text)
    bump_message_count($id, $canon, $msgText);
    if ($target && $target !== $current) { update_lead_fields($id, ['status'=>$target]); $newStatus = $target; }
    if ($canon === 'offer') update_lead_fields($id, ['offer_sent'=>'1','offer_sent_at'=>date('Y-m-d H:i:s')]);
    $shortMsg = mb_substr(trim(preg_replace('/\s+/', ' ', $msgText)), 0, 120);
    log_activity($id, 'whatsapp', ($titles[$canon] ?? 'WhatsApp mesajı gönderildi'), '['.$canon.'] '.$shortMsg, $current, $newStatus);
    return ['ok'=>true,'status'=>$newStatus,'message_count'=>(int)($lead['message_count'] ?? 0)+1,'lead'=>get_lead($id),'message'=>$msgText];
}

/* ============ Aktivite / iletişim geçmişi ============ */
function current_actor(): string {
    start_app_session();
    return (string)($_SESSION['user_email'] ?? 'sistem');
}
function log_activity(int $leadId, string $type, string $title, string $message = '', string $oldStatus = '', string $newStatus = '', ?string $actor = null): bool {
    if ($leadId <= 0) return false;
    $actor = $actor ?? current_actor();
    $type = preg_replace('/[^a-z_]/', '', lead_lower($type)) ?: 'note';
    $m = db();
    if ($m) {
        $stmt = $m->prepare('INSERT INTO lead_activities (lead_id, type, title, message, old_status, new_status, created_by) VALUES (?,?,?,?,?,?,?)');
        if (!$stmt) return false;
        $stmt->bind_param('issssss', $leadId, $type, $title, $message, $oldStatus, $newStatus, $actor);
        $ok = $stmt->execute(); $stmt->close(); return $ok;
    }
    ensure_data_files();
    $path = __DIR__ . '/../data/activities.json';
    $list = json_decode((string)@file_get_contents($path), true); if (!is_array($list)) $list = [];
    $list[] = ['id'=>count($list)+1,'lead_id'=>$leadId,'type'=>$type,'title'=>$title,'message'=>$message,'old_status'=>$oldStatus,'new_status'=>$newStatus,'created_by'=>$actor,'created_at'=>date('Y-m-d H:i:s')];
    return (bool)@file_put_contents($path, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
function get_lead_activities(int $leadId, int $limit = 200): array {
    $m = db();
    if ($m) {
        $leadId = max(1, $leadId);
        $res = $m->query('SELECT * FROM lead_activities WHERE lead_id=' . $leadId . ' ORDER BY id DESC LIMIT ' . max(1, min(500, $limit)));
        $rows = []; if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r; return $rows;
    }
    ensure_data_files();
    $list = json_decode((string)@file_get_contents(__DIR__ . '/../data/activities.json'), true); if (!is_array($list)) $list = [];
    $list = array_values(array_filter($list, fn($r) => (int)($r['lead_id'] ?? 0) === $leadId));
    usort($list, fn($a,$b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return array_slice($list, 0, $limit);
}

function default_message_templates(): array {
    return [
        'first'   => "Merhaba {firma_adi}, işletmenizi Google'da gördüm. Web siteniz yoksa ya da mevcut siteniz yeterince müşteri kazandırmıyorsa; {sektor} sektörüne uygun, mobil uyumlu ve WhatsApp dönüşümlü bir web sitesi hazırlıyoruz.\n\nDilerseniz size 2 örnek tasarım ve {paket} paketi bilgisini gönderebilirim. Uygun mudur?",
        'detail'  => "Merhaba {firma_adi}, size uygun paketi netleştireyim:\n\n{paket} — başlangıç {teklif_tutari}\n\nRandevu, çok sayfa, yönetim paneli ve çok dil ihtiyaca göre eklenir. İsterseniz sektörünüze uygun örnek tasarımı da göndereyim.",
        'payment' => "Merhaba {firma_adi}, {paket} çalışmanız için süreci başlatabiliriz.\n\nToplam: {teklif_tutari}\nBaşlangıç kaporası: {kapora}\nKalan: {kalan_odeme}\n\nOnay verdiğinizde aynı gün tasarıma başlıyoruz.",
        'followup'=> "Merhaba {firma_adi}, önceki mesajımı hatırlatmak istedim. Sektörünüze uygun kısa bir örnek tasarım ve net paket teklifini bugün gönderebilirim. İstemiyorsanız yazmanız yeterli, tekrar rahatsız etmeyiz.",
        'tracking'=> "Merhaba {yetkili}, siparişiniz oluşturuldu. Süreci şu linkten takip edebilirsiniz:\n{takip_linki}",
        'contract'=> "Merhaba {firma_adi}, hizmet sözleşmenizi ve teklif formunuzu hazırladık. Onayınız sonrası çalışmaya başlıyoruz. Sorularınız için buradan yazabilirsiniz.",
        'delivery'=> "Merhaba {firma_adi}, web siteniz yayına hazır. Kontrol edip görüşlerinizi iletebilirsiniz. Teslim sonrası desteğimiz devam ediyor.",
        'renewal' => "Merhaba {firma_adi}, web sitenizin domain/hosting yenileme dönemi yaklaşıyor. Kesintisiz devam için yenileme işlemini birlikte planlayalım.",
    ];
}
function message_templates(): array {
    $stored = setting_get('message_templates', []);
    if (!is_array($stored)) $stored = [];
    return array_merge(default_message_templates(), array_filter($stored, fn($v) => is_string($v) && trim($v) !== ''));
}
function money_tr($v): string { return number_format((float)$v, 0, ',', '.') . ' TL'; }
function template_vars_for_lead(array $lead, array $extra = []): array {
    $package = (string)($lead['package_type'] ?? 'onepage');
    $order = (float)($lead['order_amount'] ?? 0);
    $paid = (float)($lead['amount_paid'] ?? 0);
    $deposit = (float)($lead['deposit_amount'] ?? 0);
    $vars = [
        '{firma_adi}' => (string)($lead['name'] ?? 'İşletmeniz'),
        '{yetkili}' => (string)(($lead['contact_name'] ?? '') ?: ($lead['customer_name'] ?? '') ?: 'yetkili'),
        '{sektor}' => (string)(($lead['sub_sector'] ?? '') ?: ($lead['sector'] ?? 'işletmeniz')),
        '{ilce}' => (string)($lead['district'] ?? ''),
        '{sehir}' => (string)($lead['city'] ?? ''),
        '{paket}' => package_label($package),
        '{teklif_tutari}' => $order > 0 ? money_tr($order) : (string)(($lead['package_price'] ?? '') ?: package_price($package)),
        '{kapora}' => $deposit > 0 ? money_tr($deposit) : '%50 kapora',
        '{kalan_odeme}' => money_tr(max(0, $order - $paid)),
        '{satis_temsilcisi}' => (string)($lead['assigned_to'] ?? ''),
        '{telefon}' => (string)($lead['phone'] ?? ''),
        '{takip_linki}' => $extra['{takip_linki}'] ?? '',
    ];
    return array_merge($vars, $extra);
}
function render_template(string $tpl, array $vars): string {
    return strtr($tpl, $vars);
}
function message_for_lead(array $lead, string $type = 'first'): string {
    $templates = message_templates();
    if (isset($templates[$type]) && trim((string)$templates[$type]) !== '') {
        $extra = [];
        if ($type === 'tracking') $extra['{takip_linki}'] = tracking_url_for_lead($lead);
        return render_template((string)$templates[$type], template_vars_for_lead($lead, $extra));
    }
    $name = (string)($lead['name'] ?? 'İşletmeniz'); $sector=(string)($lead['sector'] ?? ''); $sub=(string)($lead['sub_sector'] ?? '');
    $package = (string)($lead['package_type'] ?? 'onepage'); $price = (string)($lead['package_price'] ?? package_price($package));
    $packageLabel = package_label($package);
    if ($type === 'detail') return "Merhaba {$name},\n\nSize uygun web site paketlerini netleştireyim:\n\n" . package_options_text() . "\n\nŞu an başlangıç fiyatımız tek sayfalık HTML web sitesi için " . package_price('onepage') . ". Randevu sistemi, çok sayfalı yapı, yönetim paneli, reklam/e-ticaret/sosyal medya danışmanlığı ve çok dilli site ihtiyaca göre tekliflendirilir.\n\nİsterseniz sektörünüze uygun örnek tasarımı ve paket karşılaştırmasını göndereyim.";
    if ($type === 'payment') { $ps=setting_get('payment_settings', default_payment_settings()); $iban=trim((string)($ps['iban'] ?? '')); $bank=trim((string)($ps['bank_name'] ?? '')); $holder=trim((string)($ps['account_holder'] ?? '')); $note=trim((string)($ps['payment_note'] ?? '')); $payLine=$iban ? "

Ödeme bilgisi:
IBAN: {$iban}
Alıcı: {$holder}" . ($bank?"
Banka: {$bank}":"") . ($note?"
Not: {$note}":"") : "

Uygunsa ödeme bilgisini / kapora adımını paylaşayım."; return "Merhaba {$name},

{$price} başlangıçlı {$packageLabel} çalışması için süreci başlatabiliriz. Çalışmaya başlamak için ödeme adımı: " . (string)cfg('default_payment_step') . ".

Onay verdiğinizde aynı gün tasarım hazırlığına geçiyoruz.".$payLine; }
    if ($type === 'followup') return "Merhaba {$name}, dün/önceki görüşmemiz için yazıyorum. Web sitesi olmayan işletmeler Google’dan gelen müşteriyi çoğu zaman daha kurumsal görünen rakibe bırakıyor.\n\nUygun görürseniz bugün size sektörünüze uygun kısa örnek tasarım ve net paket teklifini göndereyim. İstemiyorsanız yazmanız yeterli, tekrar rahatsız etmeyiz.";
    if ($type === 'call') return call_script_for_lead($lead);
    return (string)($lead['whatsapp_message'] ?? sector_message($name,$sector,$sub));
}

function call_script_for_lead(array $lead): string {
    $name = (string)($lead['name'] ?? 'İşletmeniz');
    $sector = (string)($lead['sector'] ?? '');
    $sub = (string)($lead['sub_sector'] ?? '');
    $package = (string)($lead['package_type'] ?? 'onepage');
    $price = (string)($lead['package_price'] ?? package_price($package));
    if ($price === '' || $price === 'Teklif alınır') $price = package_price('onepage') . ' başlangıç';
    $problem = sector_problem_line($sector, $sub);
    $angle = sector_competitor_angle($sector, $sub);
    $packageLabel = package_label($package);
    return "AÇILIŞ
" .
        "Merhaba, {$name} yetkilisiyle mi görüşüyorum? Ben PoyrazTech tarafından arıyorum. Google’da işletmenizi gördüm; telefon numaranız var ama web siteniz görünmüyor. 30 saniye içinde neden aradığımı anlatayım, uygun değilse kapatırım.

" .
        "PROBLEMİ GÖSTER
" .
        $problem . "

" .
        "RAKİP AVANTAJI
" .
        $angle . " Web sitesi olmayan işletme Google’da görünse bile müşteriyi WhatsApp’a, randevuya veya teklif formuna yönlendirmekte zayıf kalıyor.

" .
        "TEKLİF
" .
        "Biz size hızlı açılan, mobil uyumlu, WhatsApp butonlu profesyonel web sitesi hazırlıyoruz. Başlangıç teklifimiz {$packageLabel} için {$price}. En düşük paket tek sayfalık HTML site olarak " . package_price('onepage') . "’den başlıyor. İsterseniz randevulu, çok sayfalı, yönetim panelli ve çok dilli paketler de hazırlıyoruz.

" .
        "KAPANIŞ / ÖDEME ADIMI
" .
        "Size bugün sektörünüze uygun 2 örnek tasarım ve paket karşılaştırması göndereyim. Beğenirseniz " . (string)cfg('default_payment_step') . " ile aynı gün çalışmaya başlayabiliriz. Uygun görürseniz WhatsApp’tan örnekleri ve ödeme adımını göndereyim mi?

" .
        "İTİRAZ CEVAPLARI
" .
        "• 'Şu an gerek yok' derse: Haklısınız, hemen karar vermenizi istemiyorum. Sadece rakiplerde olan örnekleri atayım; uygun görürseniz sonra konuşuruz.
" .
        "• 'Pahalı' derse: Bu sadece başlangıç paketidir. Tek sayfa siteyle hızlı başlayıp sonra randevu/panel ekleyebiliriz.
" .
        "• 'Instagram var' derse: Instagram iyi ama Google’dan gelen müşteri önce güven veren site, konum, hizmet listesi ve tek tık iletişim görmek istiyor.
" .
        "• 'Düşüneceğim' derse: Tamamdır, size paket karşılaştırmasını ve örnek tasarımı WhatsApp’tan göndereyim; karar verirseniz başlatırız.";
}

function stats(): array {
    $m = db(); $base = ['total'=>0,'today'=>0,'new'=>0,'offer'=>0,'payment'=>0,'customer'=>0,'blacklist'=>0,'db'=>has_db()?'MySQL':'Local JSON','by_status'=>[],'top_sectors'=>[],'conversion'=>0,'revenue_total'=>0,'revenue_paid'=>0,'revenue_pending'=>0,'contracts'=>0,'renewal_due'=>0,'overdue_delivery'=>0,'active_orders'=>0];
    if ($m) {
        $queries=['total'=>"SELECT COUNT(*) c FROM leads",'today'=>"SELECT COUNT(*) c FROM leads WHERE DATE(created_at)=CURDATE()",'new'=>"SELECT COUNT(*) c FROM leads WHERE status='Aranmadı'",'offer'=>"SELECT COUNT(*) c FROM leads WHERE status='Teklif istedi'",'payment'=>"SELECT COUNT(*) c FROM leads WHERE status='Ödeme bekleniyor'",'customer'=>"SELECT COUNT(*) c FROM leads WHERE status='Müşteri oldu'",'blacklist'=>"SELECT COUNT(*) c FROM lead_blacklist",'contracts'=>"SELECT COUNT(*) c FROM contracts WHERE is_deleted=0",'renewal_due'=>"SELECT COUNT(*) c FROM leads WHERE (domain_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)) OR (hosting_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY))",'overdue_delivery'=>"SELECT COUNT(*) c FROM leads WHERE estimated_delivery_date IS NOT NULL AND estimated_delivery_date<CURDATE() AND order_status NOT IN ('Yayında','Tamamlandı','İptal')",'active_orders'=>"SELECT COUNT(*) c FROM leads WHERE order_status NOT IN ('Sipariş oluşturulmadı','Yayında','Tamamlandı','İptal')"];
        foreach($queries as $k=>$sql){$r=$m->query($sql); if($r) $base[$k]=(int)$r->fetch_assoc()['c'];}
        $r=$m->query("SELECT status, COUNT(*) c FROM leads GROUP BY status ORDER BY c DESC"); if($r) while($row=$r->fetch_assoc()) $base['by_status'][]=$row;
        $r=$m->query("SELECT COALESCE(NULLIF(sub_sector,''), sector) sector, COUNT(*) c, AVG(lead_score) avg_score FROM leads GROUP BY COALESCE(NULLIF(sub_sector,''), sector) ORDER BY c DESC LIMIT 8"); if($r) while($row=$r->fetch_assoc()) $base['top_sectors'][]=$row;
        $rr=$m->query("SELECT COALESCE(SUM(order_amount),0) total, COALESCE(SUM(amount_paid),0) paid FROM leads"); if($rr){$x=$rr->fetch_assoc(); $base['revenue_total']=(float)$x['total']; $base['revenue_paid']=(float)$x['paid']; $base['revenue_pending']=max(0,$base['revenue_total']-$base['revenue_paid']);}
        $base['conversion'] = $base['total'] ? round(($base['customer'] / $base['total']) * 100, 1) : 0;
        return $base;
    }
    $rows=list_leads(); $today=date('Y-m-d'); $status=[]; $sectors=[];
    foreach($rows as $r){$base['total']++; if(str_starts_with((string)($r['created_at']??''),$today))$base['today']++; $st=(string)($r['status']??'Aranmadı'); $status[$st]=($status[$st]??0)+1; if($st==='Aranmadı')$base['new']++; if($st==='Teklif istedi')$base['offer']++; if($st==='Ödeme bekleniyor')$base['payment']++; if($st==='Müşteri oldu')$base['customer']++; $sec=(string)(($r['sub_sector']??'') ?: ($r['sector']??'')); if($sec)$sectors[$sec]=($sectors[$sec]??0)+1;}
    foreach($status as $k=>$c)$base['by_status'][]=['status'=>$k,'c'=>$c]; arsort($sectors); foreach(array_slice($sectors,0,8,true) as $k=>$c)$base['top_sectors'][]=['sector'=>$k,'c'=>$c,'avg_score'=>''];
    $base['conversion']=$base['total']?round(($base['customer']/$base['total'])*100,1):0; return $base;
}


function effective_google_api_key(): string {
    $stored = setting_get('google_api_key', null);
    if (is_string($stored) && trim($stored) !== '') return trim($stored);
    return (string)cfg('google_places_api_key');
}
function public_runtime_settings(): array {
    $d = cfg('db') ?: [];
    return [
        'db_host' => (string)($d['host'] ?? 'localhost'),
        'db_name' => (string)($d['name'] ?? ''),
        'db_user' => (string)($d['user'] ?? ''),
        'db_pass_set' => (string)($d['pass'] ?? '') !== '',
        'google_key_set' => effective_google_api_key() !== '',
        'google_key_masked' => mask_secret(effective_google_api_key()),
    ];
}
function mask_secret(string $v): string {
    if ($v === '') return '';
    if (strlen($v) <= 10) return str_repeat('•', strlen($v));
    return substr($v, 0, 6) . str_repeat('•', max(4, strlen($v)-10)) . substr($v, -4);
}
function parse_money($v): float {
    if (is_numeric($v)) return (float)$v;
    $s = preg_replace('/[^0-9,\.]/', '', (string)$v);
    if (strpos($s, ',') !== false && strpos($s, '.') !== false) $s = str_replace('.', '', $s);
    $s = str_replace(',', '.', $s);
    return max(0, (float)$s);
}
function ensure_tracking_token(array $lead): string {
    $token = (string)($lead['tracking_token'] ?? '');
    if ($token !== '') return $token;
    $token = bin2hex(random_bytes(16));
    update_lead_fields((int)$lead['id'], ['tracking_token'=>$token]);
    return $token;
}
function tracking_url_for_lead(array $lead): string {
    $token = ensure_tracking_token($lead);
    $base = rtrim(dirname((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $base = preg_replace('~/api$~', '', $base);
    return $base . '/track.php?t=' . rawurlencode($token);
}
function order_statuses(): array { return ['Sipariş oluşturulmadı','Sipariş oluşturuldu','Ödeme bekleniyor','Tasarım hazırlanıyor','Müşteri onayı bekleniyor','Yayına hazırlanıyor','Yayında','Tamamlandı','İptal']; }
function payment_statuses(): array { return ['Ödeme alınacak','Kapora bekleniyor','Kapora alındı','Kısmi ödeme alındı','Ödendi','İade edildi']; }
function default_contract_terms(): string {
    return "1. Bu teklif ve sözleşme web sitesi tasarım/geliştirme, kurulum ve dijital danışmanlık hizmetleri için hazırlanmıştır.\n"
        . "2. Sözleşme süresi 1 yıldır. Hizmet kapsamı, seçilen paket ve sözleşmedeki ek hizmet satırlarıyla sınırlıdır.\n"
        . "3. Başlangıç fiyatı 4.999 TL olup tek sayfalık HTML web sitesi için geçerlidir. Çok sayfalı site, yönetim paneli, randevu sistemi, çok dilli yapı, profesyonel reklam danışmanlığı, e-ticaret danışmanlığı ve sosyal medya danışmanlığı ayrıca tekliflendirilir.\n"
        . "4. Domain alan adı ücreti, alan adı seçimi ve alan adı mülkiyeti müşteriye aittir. Müşteri isterse domaini kendisi alabilir. Müşteri isterse domain alımı hizmet veren tarafından müşteri adına yürütülebilir.\n"
        . "5. Domain hizmet veren tarafından alınırsa, alım Hostinger üzerindeki şirket hesabımızdan yapılır. Talep edilmesi halinde uygun teknik şartlar sağlandığında domain transferi yapılabilir. Transfer, kayıt firması kuralları, transfer kilidi ve ICANN/kayıt operatörü süreçlerine tabidir.\n"
        . "6. Hosting hizmeti ilk yıl hizmet veren tarafından karşılanır. İkinci yıl ve sonraki yıllarda hosting/domain yenileme, bakım veya devam hizmeti için yenileme ücreti alınır. Yenileme ücreti ilgili yılın maliyetlerine ve hizmet kapsamına göre bildirilir.\n"
        . "7. İşe başlama, yazılı WhatsApp onayı ve belirlenen ödeme/kapora adımı sonrası yapılır. Yazılı WhatsApp onayı varsa taraflar dijital olarak onay vermiş kabul edilir; yazdırılan sözleşmede onay kutuları görünür.\n"
        . "8. Müşteri; logo, metin, görsel, ürün/hizmet bilgileri, KVKK/mesafeli satış/çerez/aydınlatma metinleri için gerekli şirket bilgileri ve yasal içerikleri sağlamakla yükümlüdür. Hukuki metinlerin nihai doğruluğu müşterinin sorumluluğundadır.\n"
        . "9. KVKK, çerez bildirimi, aydınlatma metni, iletişim izinleri, mesafeli satış ve benzeri yasal metinler teknik olarak siteye eklenebilir. Ancak bu metinler hukuki danışmanlık yerine geçmez; gerekirse müşteri kendi hukuk/mali danışmanından onay almalıdır.\n"
        . "10. Teslim edilen web sitesi kaynakları, ödeme tamamlandıktan ve taraflarca mutabakat sağlandıktan sonra müşteriye teslim edilir. Ödenmemiş işler için yayına alma, devir veya erişim kısıtlanabilir.\n"
        . "11. Ek istekler sözleşmeye satır satır eklenir, ayrıca ücretlendirilir ve toplam ciroya dahil edilir.\n"
        . "12. Hosting, domain, üçüncü taraf lisanslar, ücretli tema/eklenti, reklam bütçesi, ödeme kuruluşu komisyonu ve dış servis ücretleri aksi yazılmadıkça müşteri sorumluluğundadır.";
}
function contract_no(): string { return contract_sequence_no(); }
function list_contracts(int $limit = 500): array {
    $m=db(); if(!$m) return [];
    $res=$m->query('SELECT c.*, l.name lead_name, l.phone lead_phone FROM contracts c LEFT JOIN leads l ON l.id=c.lead_id WHERE c.is_deleted=0 ORDER BY c.id DESC LIMIT '.max(1,min(1000,$limit)));
    $rows=[]; if($res) while($r=$res->fetch_assoc()) $rows[]=$r; return $rows;
}
function get_contract(int $id): ?array {
    $m=db(); if(!$m) return null; $res=$m->query('SELECT * FROM contracts WHERE id='.max(1,$id).' AND is_deleted=0 LIMIT 1'); if($res){$r=$res->fetch_assoc(); return $r?:null;} return null;
}
function get_contract_by_lead(int $leadId): ?array {
    $m=db(); if(!$m) return null; $res=$m->query('SELECT * FROM contracts WHERE lead_id='.max(1,$leadId).' AND is_deleted=0 ORDER BY id DESC LIMIT 1'); if($res){$r=$res->fetch_assoc(); return $r?:null;} return null;
}
function save_contract(array $data): array {
    $m=db(); if(!$m) return ['ok'=>false,'error'=>'Sözleşme için MySQL bağlantısı gerekli.'];
    $leadId=(int)($data['lead_id'] ?? 0); $lead=get_lead($leadId); if(!$lead) return ['ok'=>false,'error'=>'Lead bulunamadı.'];
    $id=(int)($data['id'] ?? 0); $items=$data['extra_items'] ?? []; if(!is_array($items)) $items=[];
    $itemsJson=json_encode(array_values($items), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $orderAmount=parse_money($data['order_amount'] ?? ($lead['order_amount'] ?? 0));
    $amountPaid=parse_money($data['amount_paid'] ?? ($lead['amount_paid'] ?? 0));
    foreach($items as $it){ $orderAmount += parse_money($it['amount'] ?? 0); }
    $vals=[
        'customer_name'=>(string)($data['customer_name'] ?? ($lead['customer_name'] ?: $lead['name'])),
        'customer_phone'=>(string)($data['customer_phone'] ?? $lead['phone']),
        'customer_title'=>(string)($data['customer_title'] ?? ($lead['customer_title'] ?? '')),
        'customer_tax_info'=>(string)($data['customer_tax_info'] ?? ($lead['customer_tax_info'] ?? '')),
        'customer_address'=>(string)($data['customer_address'] ?? ($lead['customer_address'] ?: $lead['address'])),
        'package_type'=>(string)($data['package_type'] ?? ($lead['package_type'] ?: 'onepage')),
        'package_price'=>(string)($data['package_price'] ?? ($lead['package_price'] ?: package_price('onepage'))),
        'order_amount'=>$orderAmount,
        'amount_paid'=>$amountPaid,
        'payment_status'=>(string)($data['payment_status'] ?? 'Ödeme alınacak'),
        'order_status'=>(string)($data['order_status'] ?? 'Sipariş oluşturuldu'),
        'extra_items_json'=>$itemsJson,
        'terms'=>(string)($data['terms'] ?? default_contract_terms()),
        'written_approval'=>!empty($data['written_approval']) ? 1 : 0,
        'signed_by_company'=>!empty($data['signed_by_company']) ? 1 : 0,
        'signed_by_customer'=>!empty($data['signed_by_customer']) ? 1 : 0,
        'start_date'=>(string)($data['start_date'] ?? date('Y-m-d')),
        'estimated_delivery_date'=>(string)($data['estimated_delivery_date'] ?? date('Y-m-d', strtotime('+7 days'))),
        'actual_delivery_date'=>(string)($data['actual_delivery_date'] ?? ''),
        'revision_limit'=>(int)($data['revision_limit'] ?? 2),
        'revision_used'=>(int)($data['revision_used'] ?? 0),
        'domain_owner'=>(string)($data['domain_owner'] ?? 'Müşteri'),
        'domain_provider'=>(string)($data['domain_provider'] ?? ''),
        'domain_name'=>(string)($data['domain_name'] ?? ''),
        'domain_expiry_date'=>(string)($data['domain_expiry_date'] ?? ''),
        'hosting_provider'=>(string)($data['hosting_provider'] ?? 'Hostinger / Xtanbul'),
        'hosting_expiry_date'=>(string)($data['hosting_expiry_date'] ?? ''),
        'renewal_fee'=>parse_money($data['renewal_fee'] ?? 0),
        'payment_receipt_file'=>(string)($data['payment_receipt_file'] ?? ''),
    ];
    if($id>0){
        $stmt=$m->prepare('UPDATE contracts SET customer_name=?,customer_phone=?,customer_title=?,customer_tax_info=?,customer_address=?,package_type=?,package_price=?,order_amount=?,amount_paid=?,payment_status=?,order_status=?,extra_items_json=?,terms=?,written_approval=?,signed_by_company=?,signed_by_customer=? WHERE id=?');
        if(!$stmt) return ['ok'=>false,'error'=>$m->error];
        $stmt->bind_param('sssssssddssssiiii',$vals['customer_name'],$vals['customer_phone'],$vals['customer_title'],$vals['customer_tax_info'],$vals['customer_address'],$vals['package_type'],$vals['package_price'],$vals['order_amount'],$vals['amount_paid'],$vals['payment_status'],$vals['order_status'],$vals['extra_items_json'],$vals['terms'],$vals['written_approval'],$vals['signed_by_company'],$vals['signed_by_customer'],$id);
        $ok=$stmt->execute(); $err=$stmt->error; $stmt->close();
    } else {
        $no=contract_no(); $title='Web Site Hizmet Sözleşmesi ve Teklif Formu';
        $stmt=$m->prepare('INSERT INTO contracts (lead_id,contract_no,title,customer_name,customer_phone,customer_title,customer_tax_info,customer_address,package_type,package_price,order_amount,amount_paid,payment_status,order_status,extra_items_json,terms,written_approval,signed_by_company,signed_by_customer) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        if(!$stmt) return ['ok'=>false,'error'=>$m->error];
        $stmt->bind_param('isssssssssddssssiii',$leadId,$no,$title,$vals['customer_name'],$vals['customer_phone'],$vals['customer_title'],$vals['customer_tax_info'],$vals['customer_address'],$vals['package_type'],$vals['package_price'],$vals['order_amount'],$vals['amount_paid'],$vals['payment_status'],$vals['order_status'],$vals['extra_items_json'],$vals['terms'],$vals['written_approval'],$vals['signed_by_company'],$vals['signed_by_customer']);
        $ok=$stmt->execute(); $id=$stmt->insert_id; $err=$stmt->error; $stmt->close();
    }
    if(!$ok) return ['ok'=>false,'error'=>$err ?? 'Sözleşme kaydedilemedi.'];
    $extraUpdate = [
        'start_date'=>$vals['start_date'], 'estimated_delivery_date'=>$vals['estimated_delivery_date'], 'actual_delivery_date'=>$vals['actual_delivery_date'],
        'revision_limit'=>(string)$vals['revision_limit'], 'revision_used'=>(string)$vals['revision_used'], 'domain_owner'=>$vals['domain_owner'], 'domain_provider'=>$vals['domain_provider'],
        'domain_name'=>$vals['domain_name'], 'domain_expiry_date'=>$vals['domain_expiry_date'], 'hosting_provider'=>$vals['hosting_provider'], 'hosting_expiry_date'=>$vals['hosting_expiry_date'],
        'renewal_fee'=>(string)$vals['renewal_fee'], 'payment_receipt_file'=>$vals['payment_receipt_file']
    ];
    $parts=[]; foreach($extraUpdate as $k=>$v){ $parts[]="`$k`='".$m->real_escape_string((string)$v)."'"; }
    if($parts) $m->query('UPDATE contracts SET '.implode(',', $parts).' WHERE id='.(int)$id);
    update_lead_fields($leadId, ['customer_name'=>$vals['customer_name'],'customer_title'=>$vals['customer_title'],'customer_tax_info'=>$vals['customer_tax_info'],'customer_address'=>$vals['customer_address'],'order_amount'=>(string)$vals['order_amount'],'amount_paid'=>(string)$vals['amount_paid'],'payment_status'=>$vals['payment_status'],'order_status'=>$vals['order_status'],'contract_status'=>'Sözleşme oluşturuldu','written_approval'=>(string)$vals['written_approval'],'signed_by_company'=>(string)$vals['signed_by_company'],'signed_by_customer'=>(string)$vals['signed_by_customer'],'extra_items_json'=>$vals['extra_items_json']] + $extraUpdate);
    return ['ok'=>true,'id'=>$id,'contract'=>get_contract((int)$id)];
}
function delete_contract(int $id): bool { $m=db(); if(!$m) return false; return (bool)$m->query('UPDATE contracts SET is_deleted=1 WHERE id='.max(1,$id)); }
function tracking_message_for_lead(array $lead): string {
    $url = tracking_url_for_lead($lead);
    return "Merhaba " . (($lead['customer_name'] ?? '') ?: ($lead['name'] ?? '')) . ",\n\nSiparişiniz oluşturuldu. Süreci bu linkten takip edebilirsiniz:\n" . $url . "\n\nDurum güncellendikçe aynı link üzerinden görebilirsiniz.";
}


function list_demos(bool $activeOnly=false): array {
    $m=db(); if(!$m) return [];
    $sql='SELECT * FROM demo_sites'.($activeOnly?' WHERE is_active=1':'').' ORDER BY sector ASC, id DESC';
    $res=$m->query($sql); $rows=[]; if($res) while($r=$res->fetch_assoc()) $rows[]=$r; return $rows;
}
function save_demo(array $d): array {
    $m=db(); if(!$m) return ['ok'=>false,'error'=>'Demo yönetimi için MySQL gerekli.'];
    $id=(int)($d['id'] ?? 0); $title=trim((string)($d['title'] ?? '')); $sector=trim((string)($d['sector'] ?? '')); $url=trim((string)($d['url'] ?? '')); $image=trim((string)($d['image'] ?? '')); $note=trim((string)($d['note'] ?? '')); $active=!empty($d['is_active'])?1:0;
    if($title==='' || $url==='') return ['ok'=>false,'error'=>'Demo adı ve link zorunlu.'];
    if($id>0){$stmt=$m->prepare('UPDATE demo_sites SET title=?,sector=?,url=?,image=?,note=?,is_active=? WHERE id=?'); if(!$stmt)return ['ok'=>false,'error'=>$m->error]; $stmt->bind_param('sssssii',$title,$sector,$url,$image,$note,$active,$id);}
    else {$stmt=$m->prepare('INSERT INTO demo_sites (title,sector,url,image,note,is_active) VALUES (?,?,?,?,?,?)'); if(!$stmt)return ['ok'=>false,'error'=>$m->error]; $stmt->bind_param('sssssi',$title,$sector,$url,$image,$note,$active);} $ok=$stmt->execute(); $err=$stmt->error; $newId=$id?:$stmt->insert_id; $stmt->close(); return ['ok'=>$ok,'id'=>$newId,'error'=>$ok?null:$err];
}
function delete_demo(int $id): bool { $m=db(); if(!$m) return false; return (bool)$m->query('DELETE FROM demo_sites WHERE id='.max(1,$id)); }
function record_payment(array $d): array {
    $m=db(); if(!$m) return ['ok'=>false,'error'=>'Ödeme kaydı için MySQL gerekli.'];
    $leadId=(int)($d['lead_id'] ?? 0); $lead=get_lead($leadId); if(!$lead) return ['ok'=>false,'error'=>'Lead bulunamadı.'];
    $contractId=(int)($d['contract_id'] ?? 0) ?: null; $amount=parse_money($d['amount'] ?? 0); $method=(string)($d['method'] ?? 'Havale/EFT'); $note=(string)($d['note'] ?? ''); $file=(string)($d['receipt_file'] ?? '');
    $stmt=$m->prepare('INSERT INTO payments (lead_id,contract_id,amount,method,note,receipt_file) VALUES (?,?,?,?,?,?)'); if(!$stmt)return ['ok'=>false,'error'=>$m->error]; $stmt->bind_param('iidsss',$leadId,$contractId,$amount,$method,$note,$file); $ok=$stmt->execute(); $stmt->close();
    if($ok){ $newPaid=(float)($lead['amount_paid'] ?? 0)+$amount; $status=$newPaid >= (float)($lead['order_amount'] ?? 0) && (float)($lead['order_amount'] ?? 0)>0 ? 'Ödendi' : 'Kısmi ödeme alındı'; update_lead_fields($leadId,['amount_paid'=>(string)$newPaid,'payment_status'=>$status,'payment_receipt_file'=>$file]); }
    return ['ok'=>$ok];
}

/* ============ Pipeline / seçenek listeleri ============ */
function lead_pipeline_statuses(): array {
    return ['Aranmadı','Uygunluk kontrolü','WhatsApp gönderildi','Cevap bekleniyor','Arandı','Teklif istedi','Teklif hazırlanıyor','Teklif gönderildi','Pazarlıkta','Ödeme linki gönderildi','Ödeme bekleniyor','Kapora alındı','Sözleşme gönderildi','Sözleşme onaylandı','Proje başladı','Tasarım hazırlanıyor','Revizede','Yayına hazır','Yayında','Tamamlandı','Müşteri oldu','İlgilenmedi','Tekrar aranmasın','Kara liste'];
}
function priority_options(): array { return ['Soğuk','Ilık','Sıcak','Çok sıcak']; }
function website_quality_options(): array { return ['Yok','Var ama zayıf','Mobil kötü','Yavaş','SEO zayıf','Güncel değil','İyi']; }
function competitor_density_options(): array { return ['Düşük','Orta','Yüksek','Çok yüksek']; }
function requested_service_options(): array {
    return ['Tek sayfa web sitesi','Randevulu web sitesi','Çok sayfalı kurumsal site','Yönetim panelli site','E-ticaret','SEO','Reklam danışmanlığı','Sosyal medya danışmanlığı','Özel yazılım'];
}

/* ============ Ön yüz teklif formu → lead ============ */
function create_public_lead(array $in): array {
    // Bot koruması: honeypot dolu ise sessizce başarı dön
    if (trim((string)($in['company_site'] ?? '')) !== '') return ['ok'=>true,'skipped'=>true];
    $name = trim((string)($in['company'] ?? $in['name'] ?? ''));
    $person = trim((string)($in['person'] ?? ''));
    $phone = trim((string)($in['phone'] ?? ''));
    $email = trim((string)($in['email'] ?? ''));
    if ($name === '' && $person === '') return ['ok'=>false,'error'=>'Lütfen ad soyad veya firma adı girin.'];
    if ($phone === '' && $email === '') return ['ok'=>false,'error'=>'Telefon veya e-posta girmelisiniz.'];
    if (empty($in['kvkk'])) return ['ok'=>false,'error'=>'Devam etmek için KVKK onayı gerekli.'];
    $displayName = $name !== '' ? $name : $person;
    $lead = [
        'place_id' => 'web-'.substr(bin2hex(random_bytes(6)),0,12),
        'name' => $displayName,
        'sector' => trim((string)($in['sector'] ?? 'Web Formu')),
        'sub_sector' => trim((string)($in['service'] ?? '')),
        'city' => trim((string)($in['city'] ?? '')),
        'district' => trim((string)($in['district'] ?? '')),
        'address' => '',
        'phone' => $phone,
        'website' => trim((string)($in['website'] ?? '')),
        'maps_url' => '',
        'rating' => null,
        'review_count' => null,
        'source' => 'Web Formu',
        'assigned_to' => '',
        'package_type' => 'onepage',
        'package_price' => package_price('onepage'),
        'status' => 'Aranmadı',
        'raw' => ['web_form'=>true,'budget'=>$in['budget'] ?? '','urgency'=>$in['urgency'] ?? ''],
    ];
    $res = save_lead($lead);
    $id = (int)($res['id'] ?? 0);
    if (empty($res['saved']) && !$id) {
        return ['ok'=>false,'error'=>'Kayıt oluşturulamadı. Lütfen telefonla iletişime geçin.'];
    }
    if ($id) {
        $note = trim((string)($in['message'] ?? ''));
        $hasSite = trim((string)($in['has_website'] ?? ''));
        $extraNote = 'Web formu talebi. Bütçe: '.trim((string)($in['budget'] ?? '-')).' · Aciliyet: '.trim((string)($in['urgency'] ?? '-')).($person? ' · Yetkili: '.$person:'').($hasSite? ' · Mevcut site: '.$hasSite:'');
        update_lead_fields($id, [
            'contact_name' => $person,
            'email' => $email,
            'whatsapp_phone' => $phone,
            'requested_service' => trim((string)($in['service'] ?? '')),
            'neighborhood' => trim((string)($in['district'] ?? '')),
            'priority' => 'Sıcak',
            'note' => $note ? ($note."\n".$extraNote) : $extraNote,
        ], 'web-form');
        log_activity($id, 'note', 'Web formundan geldi', $extraNote.($note? "\nMesaj: ".$note : ''), '', '', 'web-form');
    }
    return ['ok'=>true,'id'=>$id,'duplicate'=>!empty($res['duplicate'])];
}

bootstrap_app();
