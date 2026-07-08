<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    $leadId = (int)($_GET['lead_id'] ?? 0);
    if ($id) json_response(['ok'=>true,'contract'=>get_contract($id)]);
    if ($leadId) json_response(['ok'=>true,'contract'=>get_contract_by_lead($leadId)]);
    json_response(['ok'=>true,'contracts'=>list_contracts(500)]);
}
$data = input_json();
if (!csrf_check($data['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF doğrulaması başarısız.'], 403);
$action = (string)($data['action'] ?? 'save');
if ($action === 'delete') {
    $id = (int)($data['id'] ?? 0);
    if (!$id) json_response(['ok'=>false,'error'=>'Geçersiz sözleşme.'], 422);
    json_response(['ok'=>delete_contract($id)]);
}
$r = save_contract($data);
json_response($r, !empty($r['ok']) ? 200 : 422);
