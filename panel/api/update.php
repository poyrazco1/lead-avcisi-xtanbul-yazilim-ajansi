<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
$data = input_json();
if (!csrf_check($data['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF doğrulaması başarısız.'], 403);
$id = (int)($data['id'] ?? 0);
if (!$id) json_response(['ok'=>false,'error'=>'Geçersiz kayıt.'], 422);
$allowedStatus = lead_pipeline_statuses();
$fields = [];
if (isset($data['status'])) {
    $status = trim((string)$data['status']);
    if (!in_array($status, $allowedStatus, true)) json_response(['ok'=>false,'error'=>'Geçersiz durum.'], 422);
    $fields['status'] = $status;
}
// Düzenlenebilir tüm CRM alanları
$editable = [
    'note','assigned_to','next_followup_at','last_contact_at','package_type','package_price','whatsapp_message',
    'customer_name','customer_title','customer_tax_info','customer_address','order_amount','amount_paid','payment_status','order_status',
    'contract_status','written_approval','signed_by_company','signed_by_customer','extra_items_json',
    'name','sector','sub_sector','city','district','address','phone','website','maps_url','rating','review_count',
    'contact_name','contact_position','whatsapp_phone','email','neighborhood','instagram','facebook','website_quality',
    'competitor_density','priority','close_probability','requested_service',
    'has_domain','has_hosting','has_logo','has_photos','has_content','need_multilang','need_appointment','need_online_payment','need_blog','need_gallery',
    'estimated_amount','net_amount','discount_amount','deposit_amount','offer_sent','offer_sent_at',
    'start_date','estimated_delivery_date','actual_delivery_date','revision_limit','revision_used',
    'domain_owner','domain_provider','domain_name','domain_expiry_date','hosting_provider','hosting_expiry_date','renewal_fee','payment_receipt_file',
];
foreach ($editable as $k) {
    if (array_key_exists($k, $data)) {
        $v = $data[$k];
        if (is_bool($v)) $v = $v ? 1 : 0;
        $fields[$k] = is_scalar($v) ? trim((string)$v) : '';
    }
}
if (!$fields) json_response(['ok'=>false,'error'=>'Güncellenecek alan yok.'], 422);
$ok = update_lead_fields($id, $fields, current_actor());
json_response(['ok'=>$ok,'lead'=>$ok ? get_lead($id) : null]);
