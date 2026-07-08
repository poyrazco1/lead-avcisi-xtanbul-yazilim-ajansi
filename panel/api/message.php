<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$type = preg_replace('/[^a-z_]/', '', (string)($_GET['type'] ?? 'first'));
if (!$id) json_response(['ok'=>false,'error'=>'Geçersiz kayıt.'], 422);
$lead = get_lead($id);
if (!$lead) json_response(['ok'=>false,'error'=>'Lead bulunamadı.'], 404);
$msg = ($type === 'tracking') ? tracking_message_for_lead($lead) : message_for_lead($lead, $type);
$phone = (string)($lead['phone'] ?? '');
json_response(['ok'=>true,'message'=>$msg,'phone'=>$phone,'tracking_url'=>tracking_url_for_lead($lead),'call_url'=>'tel:' . normalize_phone($phone),'whatsapp_url'=>whatsapp_url($phone, $msg)]);
