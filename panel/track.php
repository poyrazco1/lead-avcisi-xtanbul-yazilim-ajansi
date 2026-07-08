<?php
require __DIR__ . '/app/core.php';
$token = preg_replace('/[^a-f0-9]/', '', (string)($_GET['t'] ?? ''));
$lead = null;
if ($token && db()) {
    $m = db();
    $stmt = $m->prepare('SELECT name, phone, package_type, package_price, order_amount, amount_paid, payment_status, order_status, contract_status, updated_at, created_at, start_date, estimated_delivery_date, actual_delivery_date, revision_limit, revision_used, domain_name, domain_expiry_date, hosting_expiry_date FROM leads WHERE tracking_token=? LIMIT 1');
    if ($stmt) { $stmt->bind_param('s', $token); $stmt->execute(); $stmt->bind_result($name,$phone,$package_type,$package_price,$order_amount,$amount_paid,$payment_status,$order_status,$contract_status,$updated_at,$created_at,$start_date,$estimated_delivery_date,$actual_delivery_date,$revision_limit,$revision_used,$domain_name,$domain_expiry_date,$hosting_expiry_date); if($stmt->fetch()) $lead=compact('name','phone','package_type','package_price','order_amount','amount_paid','payment_status','order_status','contract_status','updated_at','created_at','start_date','estimated_delivery_date','actual_delivery_date','revision_limit','revision_used','domain_name','domain_expiry_date','hosting_expiry_date'); $stmt->close(); }
}
$steps=['Sipariş oluşturuldu','Ödeme bekleniyor','Tasarım hazırlanıyor','Müşteri onayı bekliyor','Yayına hazırlanıyor','Yayında'];
$status=(string)($lead['order_status'] ?? 'Sipariş oluşturuldu');
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sipariş Takip</title><link rel="stylesheet" href="assets/style.css?v=44"></head><body class="public-track"><main class="track-card pro-track">
<?php if(!$lead): ?><h1>Takip kaydı bulunamadı</h1><p>Link hatalı veya sipariş kaydı henüz oluşturulmamış.</p><?php else: ?>
<h1>Sipariş Takip</h1><p class="muted"><?=e($lead['name'])?></p>
<div class="track-status"><strong><?=e($lead['order_status'] ?: 'Sipariş oluşturuldu')?></strong><span><?=e($lead['payment_status'] ?: 'Ödeme alınacak')?></span></div>
<div class="timeline"><?php foreach($steps as $i=>$st): $done = array_search($status,$steps,true) !== false ? $i <= array_search($status,$steps,true) : $i===0; ?><div class="timeline-step <?=$done?'done':''?>"><span><?=$done?'✓':$i+1?></span><b><?=e($st)?></b></div><?php endforeach; ?></div>
<ul class="track-list"><li><b>Paket:</b> <?=e(package_label($lead['package_type'] ?: 'onepage'))?></li><li><b>Toplam:</b> <?=number_format((float)$lead['order_amount'],2,',','.')?> TL</li><li><b>Ödenen:</b> <?=number_format((float)$lead['amount_paid'],2,',','.')?> TL</li><li><b>Kalan:</b> <?=number_format(max(0,(float)$lead['order_amount']-(float)$lead['amount_paid']),2,',','.')?> TL</li><li><b>Tahmini teslim:</b> <?=e($lead['estimated_delivery_date'] ?: '-')?></li><li><b>Revize:</b> <?=e(($lead['revision_used'] ?? 0).' / '.($lead['revision_limit'] ?? 0))?></li><li><b>Domain:</b> <?=e($lead['domain_name'] ?: '-')?></li><li><b>Domain bitiş:</b> <?=e($lead['domain_expiry_date'] ?: '-')?></li><li><b>Hosting bitiş:</b> <?=e($lead['hosting_expiry_date'] ?: '-')?></li><li><b>Sözleşme:</b> <?=e($lead['contract_status'] ?: 'Sözleşme yok')?></li><li><b>Son güncelleme:</b> <?=e($lead['updated_at'] ?: $lead['created_at'])?></li></ul>
<a class="btn primary" href="https://wa.me/905334131093" target="_blank">WhatsApp Destek</a>
<p class="muted">Bu sayfa müşteriye gönderilen takip linkidir. Durum panelden güncellendikçe burada görünür.</p><?php endif; ?>
</main></body></html>
