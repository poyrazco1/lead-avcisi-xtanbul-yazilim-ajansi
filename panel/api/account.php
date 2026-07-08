<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
$data = input_json();
if (!csrf_check($data['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF hatası.'], 403);
$action = (string)($data['action'] ?? 'password');
if ($action === 'password') {
    $res = change_admin_password((string)($data['current_password'] ?? ''), (string)($data['email'] ?? ''), (string)($data['new_password'] ?? ''));
    json_response($res, $res['ok'] ? 200 : 422);
}
json_response(['ok'=>false,'error'=>'Geçersiz işlem.'], 422);
