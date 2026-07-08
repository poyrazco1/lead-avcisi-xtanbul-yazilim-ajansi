<?php
require __DIR__ . '/app/core.php';
$checks = [
    'PHP Version' => PHP_VERSION,
    'mysqli' => extension_loaded('mysqli') ? 'Açık' : 'Kapalı / local JSON moduna düşer',
    'mysqlnd' => function_exists('mysqli_fetch_all') ? 'Var / sorun yok' : 'Yok olabilir / v3.1 get_result kullanmadığı için sorun değil',
    'cURL' => extension_loaded('curl') ? 'Açık' : 'Kapalı / alternatif HTTP denenir',
    'JSON' => extension_loaded('json') ? 'Açık' : 'Kapalı',
    'Session' => function_exists('session_start') ? 'Açık' : 'Kapalı',
    'DB Bağlantı' => has_db() ? 'Başarılı' : 'Bağlanamadı / local JSON moduna düşer',
    'DB Hata Özeti' => has_db() ? 'Yok' : (db_last_error() ?: 'Bilinmiyor'),
    'Data klasörü yazılabilir' => is_writable(__DIR__ . '/data') ? 'Evet' : 'Hayır',
    'SQL dosyası' => file_exists(__DIR__ . '/database/schema.sql') ? 'Var: database/schema.sql' : 'Yok',
];
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Server Check</title><link rel="stylesheet" href="assets/style.css?v=34"></head><body><main class="main"><section class="card"><h1>Lead Avcısı Server Check v3.4</h1><p class="muted">API key ve şifreler bu ekranda gösterilmez.</p><table class="lead-table"><tbody><?php foreach($checks as $k=>$v): ?><tr><th><?=e($k)?></th><td><?=e($v)?></td></tr><?php endforeach; ?></tbody></table><p><a class="btn primary" href="login.php">Girişe Git</a></p></section></main></body></html>
