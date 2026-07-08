<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
$data = input_json();
if (!csrf_check($data['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF doğrulaması başarısız.'], 403);
$id = (int)($data['id'] ?? 0);
$type = preg_replace('/[^a-z_]/', '', (string)($data['type'] ?? 'first')) ?: 'first';
if (!$id) json_response(['ok'=>false,'error'=>'Geçersiz kayıt.'], 422);
$res = mark_whatsapp_sent($id, $type);
json_response($res, !empty($res['ok']) ? 200 : 422);
