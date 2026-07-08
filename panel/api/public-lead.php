<?php
// Ön yüz "Biz mi arayalım?" teklif formu → panelde lead oluşturur.
// Giriş gerektirmez; honeypot + temel doğrulama ile korunur.
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok'=>false,'error'=>'Sadece POST desteklenir.'], 405);
}
$data = input_json();
if (!$data) $data = $_POST;
// KVKK checkbox JSON'da bool gelebilir, formdan '1' gelebilir
$data['kvkk'] = !empty($data['kvkk']) && $data['kvkk'] !== '0' && $data['kvkk'] !== 'false';
$res = create_public_lead(is_array($data) ? $data : []);
json_response($res, !empty($res['ok']) ? 200 : 422);
