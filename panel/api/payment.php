<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
$data=input_json();
if(!csrf_check($data['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF hatası.'],403);
json_response(record_payment($data));
