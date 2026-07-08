<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
$data = input_json();
if (!csrf_check($data['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF doğrulaması başarısız.'], 403);
$id = (int)($data['id'] ?? 0);
if (!$id) json_response(['ok'=>false,'error'=>'Geçersiz kayıt.'], 422);
$allowedStatus = ['Aranmadı','WhatsApp gönderildi','Arandı','Cevap bekleniyor','Teklif istedi','Ödeme linki gönderildi','Ödeme bekleniyor','Kapora alındı','Müşteri oldu','İlgilenmedi','Tekrar aranmasın','Sipariş oluşturuldu','Tasarım hazırlanıyor','Yayında','Tamamlandı'];
$fields = [];
if (isset($data['status'])) {
    $status = trim((string)$data['status']);
    if (!in_array($status, $allowedStatus, true)) json_response(['ok'=>false,'error'=>'Geçersiz durum.'], 422);
    $fields['status'] = $status;
}
foreach (['note','assigned_to','next_followup_at','package_type','package_price','whatsapp_message','customer_name','customer_title','customer_tax_info','customer_address','order_amount','amount_paid','payment_status','order_status','contract_status','written_approval','signed_by_company','signed_by_customer','extra_items_json'] as $k) {
    if (array_key_exists($k, $data)) $fields[$k] = trim((string)$data[$k]);
}
if (!$fields) json_response(['ok'=>false,'error'=>'Güncellenecek alan yok.'], 422);
$ok = update_lead_fields($id, $fields);
json_response(['ok'=>$ok]);
