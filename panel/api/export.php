<?php
require __DIR__ . '/../app/core.php';
require_login();
$rows = list_leads([], 5000);
$filename = 'lead-avcisi-v42-' . date('Y-m-d-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['İşletme Adı','Sektör','Alt Sektör','Şehir','İlçe','Adres','Telefon','Google Maps','Puan','Yorum','Lead Skoru','Skor Sebebi','Durum','Takip Tarihi','Paket','Paket Fiyatı','Not','Toplam Ciro','Alınan Ödeme','Alınacak','Ödeme Durumu','Sipariş Durumu','Sözleşme Durumu','Takip Linki','İlk WhatsApp Mesajı','İlk WhatsApp Linki','Detay Mesaj Linki','Ödeme Mesaj Linki','Takip Mesaj Linki','Sipariş Takip WhatsApp Linki','Kaynak','Kayıt Tarihi'], ';');
foreach ($rows as $r) {
    $msg = $r['whatsapp_message'] ?? message_for_lead($r, 'first');
    fputcsv($out, [
        $r['name'] ?? '', $r['sector'] ?? '', $r['sub_sector'] ?? '', $r['city'] ?? '', $r['district'] ?? '', $r['address'] ?? '', $r['phone'] ?? '', $r['maps_url'] ?? '', $r['rating'] ?? '', $r['review_count'] ?? '', $r['lead_score'] ?? '', $r['score_reason'] ?? '', $r['status'] ?? '', $r['next_followup_at'] ?? '', $r['package_type'] ?? '', $r['package_price'] ?? '', $r['note'] ?? '', $r['order_amount'] ?? '', $r['amount_paid'] ?? '', max(0,(float)($r['order_amount'] ?? 0)-(float)($r['amount_paid'] ?? 0)), $r['payment_status'] ?? '', $r['order_status'] ?? '', $r['contract_status'] ?? '', tracking_url_for_lead($r),
        $msg, whatsapp_url($r['phone'] ?? '', $msg), whatsapp_url($r['phone'] ?? '', message_for_lead($r,'detail')), whatsapp_url($r['phone'] ?? '', message_for_lead($r,'payment')), whatsapp_url($r['phone'] ?? '', message_for_lead($r,'followup')), whatsapp_url($r['phone'] ?? '', tracking_message_for_lead($r)), $r['source'] ?? '', $r['created_at'] ?? ''
    ], ';');
}
fclose($out);
exit;
