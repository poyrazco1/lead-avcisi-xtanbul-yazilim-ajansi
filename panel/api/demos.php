<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'GET') json_response(['ok'=>true,'demos'=>list_demos(false)]);
$data = input_json();
if (!csrf_check($data['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF hatası.'], 403);
if (($data['action'] ?? '') === 'delete') json_response(['ok'=>delete_demo((int)($data['id'] ?? 0))]);
json_response(save_demo($data));
