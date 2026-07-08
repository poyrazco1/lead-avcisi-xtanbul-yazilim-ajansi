<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $leadId = (int)($_GET['lead_id'] ?? 0);
    if (!$leadId) json_response(['ok'=>false,'error'=>'Geçersiz lead.'], 422);
    json_response(['ok'=>true,'activities'=>get_lead_activities($leadId, 200)]);
}

$data = input_json();
if (!csrf_check($data['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF doğrulaması başarısız.'], 403);
$leadId = (int)($data['lead_id'] ?? 0);
if (!$leadId) json_response(['ok'=>false,'error'=>'Geçersiz lead.'], 422);
$type = preg_replace('/[^a-z_]/', '', (string)($data['type'] ?? 'note')) ?: 'note';
$title = trim((string)($data['title'] ?? 'Not eklendi'));
$message = trim((string)($data['message'] ?? ''));
if ($message === '' && $title === '') json_response(['ok'=>false,'error'=>'Boş kayıt eklenemez.'], 422);
$ok = log_activity($leadId, $type, $title ?: 'Not', $message);
// Not eklendiğinde son temas tarihini de tazele
if ($ok && in_array($type, ['note','call'], true)) update_lead_fields($leadId, ['last_contact_at'=>date('Y-m-d H:i:s')]);
json_response(['ok'=>$ok,'activities'=>get_lead_activities($leadId, 200)]);
