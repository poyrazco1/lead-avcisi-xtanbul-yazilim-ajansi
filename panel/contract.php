<?php
require __DIR__ . '/app/core.php';
require_login();
$leadId = (int)($_GET['lead_id'] ?? 0);
$contractId = (int)($_GET['id'] ?? 0);
$lead = $leadId ? get_lead($leadId) : null;
$contract = $contractId ? get_contract($contractId) : ($leadId ? get_contract_by_lead($leadId) : null);
if (!$lead && $contract) $lead = get_lead((int)$contract['lead_id']);
if (!$lead && $leadId) { http_response_code(404); echo 'Lead bulunamadı'; exit; }
$packages = package_catalog();
$items = $contract && !empty($contract['extra_items_json']) ? json_decode((string)$contract['extra_items_json'], true) : [];
if (!is_array($items) || !$items) $items = [['name'=>'Tek sayfalık HTML web sitesi','qty'=>1,'amount'=>parse_money($lead['package_price'] ?? package_price('onepage'))]];
$termsText = (string)($contract['terms'] ?? setting_get('contract_terms', default_contract_terms()));
$contractNo = $contract['contract_no'] ?? 'Kaydedince oluşur';
$today = date('d.m.Y');
$paymentSettings = setting_get('payment_settings', []);
$iban = $paymentSettings['iban'] ?? '';
$holder = $paymentSettings['account_holder'] ?? 'Xtanbul Yazılım Agent';
$bank = $paymentSettings['bank_name'] ?? '';
$packageKey = $contract['package_type'] ?? ($lead['package_type'] ?? 'onepage');
$packageLabel = $packages[$packageKey]['label'] ?? 'Tek sayfalık HTML web sitesi';
$orderAmount = (float)($contract['order_amount'] ?? ($lead['order_amount'] ?? 4999));
$amountPaid = (float)($contract['amount_paid'] ?? ($lead['amount_paid'] ?? 0));
$remaining = max(0, $orderAmount - $amountPaid);
function tl($n){ return number_format((float)$n, 2, ',', '.') . ' TL'; }
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sözleşme / Teklif</title><link rel="stylesheet" href="assets/style.css?v=48"></head><body><main class="contract-wrap elegant-contract-wrap">
<div class="no-print contract-actions"><a class="btn ghost" href="index.php#leadPanel">Panele Dön</a><button class="btn primary" onclick="saveContract()">Kaydet</button><button class="btn secondary" onclick="syncTermsPreview();window.print()">Önlü-Arkalı A4 Yazdır / PDF</button><?php if($contract): ?><button class="btn danger" onclick="deleteContract(<?= (int)$contract['id']?>)">Sil</button><?php endif; ?></div>
<input type="hidden" id="contractId" value="<?=e($contract['id'] ?? '')?>"><input type="hidden" id="leadId" value="<?=e($lead['id'] ?? $leadId)?>">

<section class="a4-contract elegant-contract contract-front" id="contractDoc">
  <header class="elegant-contract-head">
    <div class="elegant-brand"><div class="elegant-logo">X</div><div><h1>Xtanbul Yazılım Agent</h1><p>Web Site Hizmet Sözleşmesi ve Teklif Formu</p></div></div>
    <div class="elegant-meta"><span>Referans / Sözleşme No</span><b><?=e($contractNo)?></b><small><?=e($today)?></small></div>
  </header>

  <div class="elegant-line"></div>

  <div class="elegant-party-grid">
    <article><h3>Hizmet Veren</h3><p><b>Xtanbul Yazılım Agent</b></p><p>Web tasarım, yazılım, SEO, reklam ve e-ticaret danışmanlığı</p></article>
    <article><h3>Müşteri</h3><label>İşletme / Müşteri<input id="customerName" value="<?=e($contract['customer_name'] ?? ($lead['customer_name'] ?: $lead['name']))?>"></label><label>Telefon<input id="customerPhone" value="<?=e($contract['customer_phone'] ?? $lead['phone'])?>"></label></article>
  </div>

  <div class="elegant-info-grid">
    <label><span>Yetkili / Ünvan</span><input id="customerTitle" value="<?=e($contract['customer_title'] ?? ($lead['customer_title'] ?? ''))?>"></label>
    <label><span>Vergi / TC Bilgisi</span><input id="customerTax" value="<?=e($contract['customer_tax_info'] ?? ($lead['customer_tax_info'] ?? ''))?>"></label>
    <label><span>Paket</span><select id="contractPackage"><?php foreach($packages as $k=>$p): ?><option value="<?=e($k)?>" <?=($k===$packageKey?'selected':'')?>><?=e($p['label'])?></option><?php endforeach; ?></select></label>
    <label><span>Paket Fiyatı</span><input id="contractPackagePrice" value="<?=e($contract['package_price'] ?? ($lead['package_price'] ?: package_price('onepage')))?>"></label>
    <label class="elegant-wide"><span>Açık Adres / Müşteri Notu</span><textarea id="customerAddress" rows="2"><?=e($contract['customer_address'] ?? ($lead['customer_address'] ?: $lead['address']))?></textarea></label>
  </div>

  <div class="elegant-package-strip"><b><?=e($packageLabel)?></b><span>Başlangıç kapsamı: seçilen paket, sözleşmede yer alan ek hizmet satırları, ödeme ve teslim şartları ile sınırlıdır. Detaylı şartlar arka yüzde yer alır.</span></div>

  <div class="elegant-fee-table">
    <div><span>Paket Ücreti</span><strong><input id="orderAmount" type="number" step="0.01" value="<?=e($orderAmount)?>"></strong></div>
    <div><span>Alınan Ödeme</span><strong><input id="amountPaid" type="number" step="0.01" value="<?=e($amountPaid)?>"></strong></div>
    <div class="fee-strong"><span>Kalan Ödeme</span><strong id="remainingAmountPreview"><?=tl($remaining)?></strong></div>
    <div><span>Ödeme Durumu</span><strong><select id="paymentStatus"><?php foreach(payment_statuses() as $st): ?><option <?=($st===($contract['payment_status'] ?? ($lead['payment_status'] ?? 'Ödeme alınacak'))?'selected':'')?>><?=e($st)?></option><?php endforeach; ?></select></strong></div>
    <div><span>Sipariş Durumu</span><strong><select id="orderStatus"><?php foreach(order_statuses() as $st): ?><option <?=($st===($contract['order_status'] ?? ($lead['order_status'] ?? 'Sipariş oluşturuldu'))?'selected':'')?>><?=e($st)?></option><?php endforeach; ?></select></strong></div>
  </div>

  <div class="elegant-service-grid">
    <label><span>Başlangıç</span><input id="startDate" type="date" value="<?=e($contract['start_date'] ?? ($lead['start_date'] ?? date('Y-m-d')))?>"></label>
    <label><span>Tahmini Teslim</span><input id="estimatedDeliveryDate" type="date" value="<?=e($contract['estimated_delivery_date'] ?? ($lead['estimated_delivery_date'] ?? date('Y-m-d', strtotime('+7 days'))))?>"></label>
    <label><span>Gerçek Teslim</span><input id="actualDeliveryDate" type="date" value="<?=e($contract['actual_delivery_date'] ?? ($lead['actual_delivery_date'] ?? ''))?>"></label>
    <label><span>Revize Hakkı</span><input id="revisionLimit" type="number" value="<?=e($contract['revision_limit'] ?? ($lead['revision_limit'] ?? 2))?>"></label>
    <label><span>Kullanılan Revize</span><input id="revisionUsed" type="number" value="<?=e($contract['revision_used'] ?? ($lead['revision_used'] ?? 0))?>"></label>
    <label><span>Domain Sahibi</span><select id="domainOwner"><option <?=($contract['domain_owner'] ?? ($lead['domain_owner'] ?? 'Müşteri'))==='Müşteri'?'selected':''?>>Müşteri</option><option <?=($contract['domain_owner'] ?? ($lead['domain_owner'] ?? ''))==='Xtanbul / Hostinger şirket hesabı'?'selected':''?>>Xtanbul / Hostinger şirket hesabı</option></select></label>
    <label><span>Domain Adı</span><input id="domainName" value="<?=e($contract['domain_name'] ?? ($lead['domain_name'] ?? ''))?>" placeholder="ornek.com"></label>
    <label><span>Domain Sağlayıcı</span><input id="domainProvider" value="<?=e($contract['domain_provider'] ?? ($lead['domain_provider'] ?? ''))?>" placeholder="Hostinger / müşteri firması"></label>
    <label><span>Domain Bitiş</span><input id="domainExpiry" type="date" value="<?=e($contract['domain_expiry_date'] ?? ($lead['domain_expiry_date'] ?? ''))?>"></label>
    <label><span>Hosting Sağlayıcı</span><input id="hostingProvider" value="<?=e($contract['hosting_provider'] ?? ($lead['hosting_provider'] ?? 'Hostinger / Xtanbul'))?>"></label>
    <label><span>Hosting Bitiş</span><input id="hostingExpiry" type="date" value="<?=e($contract['hosting_expiry_date'] ?? ($lead['hosting_expiry_date'] ?? ''))?>"></label>
    <label><span>Yenileme Ücreti</span><input id="renewalFee" type="number" step="0.01" value="<?=e($contract['renewal_fee'] ?? ($lead['renewal_fee'] ?? 0))?>"></label>
    <label class="elegant-wide"><span>Dekont / Ödeme Kanıtı Dosya Yolu</span><input id="receiptFile" value="<?=e($contract['payment_receipt_file'] ?? ($lead['payment_receipt_file'] ?? ''))?>" placeholder="uploads/dekont-001.jpg"></label>
  </div>

  <div class="elegant-section-title"><span>Hizmet Kalemleri ve Ek İstekler</span><button class="btn secondary mini no-print" onclick="addItem()" type="button">+ Satır Ekle</button></div>
  <table class="items-table elegant-items" id="itemsTable"><thead><tr><th>Hizmet / Ek İstek</th><th>Adet</th><th>Tutar</th><th class="no-print">Sil</th></tr></thead><tbody><?php foreach($items as $it): ?><tr><td><input value="<?=e($it['name'] ?? '')?>"></td><td><input type="number" value="<?=e($it['qty'] ?? 1)?>"></td><td><input type="number" step="0.01" value="<?=e($it['amount'] ?? 0)?>"></td><td class="no-print"><button class="btn danger mini" onclick="this.closest('tr').remove();calcTotal()" type="button">Sil</button></td></tr><?php endforeach; ?></tbody></table>

  <div class="elegant-payment-box"><b>Ödeme Bilgileri</b><p>Hesap Sahibi: <?=e($holder)?><?= $bank ? ' · Banka: '.e($bank) : '' ?></p><p>IBAN: <?=e($iban ?: 'Ayarlar bölümünden IBAN girilecek')?></p><p>Ödeme Açıklaması: Xtanbul + müşteri adı + <?=e($contractNo)?></p></div>

  <label class="wide-full no-print terms-editor"><b>Arka Yüz Şartları</b><textarea id="terms" rows="9"><?=e($termsText)?></textarea><small>Bu alan yazdırmada arka sayfaya “Sözleşme Maddeleri” olarak basılır.</small></label>

  <div class="elegant-checks"><label><input id="writtenApproval" type="checkbox" <?=!empty($contract['written_approval'])?'checked':''?>> WhatsApp yazılı onayı alındı</label><label><input id="signedCompany" type="checkbox" <?=!empty($contract['signed_by_company'])?'checked':''?>> Hizmet veren dijital imzaladı</label><label><input id="signedCustomer" type="checkbox" <?=!empty($contract['signed_by_customer'])?'checked':''?>> Müşteri dijital onayladı</label></div>
  <footer class="elegant-footer">Bu ön yüz teklif, kapsam ve ödeme özetidir. Arka yüzdeki şartlar ile birlikte geçerlidir.</footer>
</section>

<section class="a4-contract elegant-contract contract-back" id="termsBackPage">
  <header class="elegant-contract-head back"><div><h1>Sözleşme Maddeleri</h1><p>Arka Yüz - Web Site Hizmet Sözleşmesi</p></div><div class="elegant-meta"><span>Sözleşme No</span><b><?=e($contractNo)?></b></div></header>
  <div class="elegant-terms" id="termsPreview"><?=nl2br(e($termsText))?></div>
  <div class="elegant-signature-row"><div><b>HİZMET VEREN</b><span>Xtanbul Yazılım Agent</span><p id="companySignPrint"><?=!empty($contract['signed_by_company'])?'☑':'☐'?> Dijital olarak imzalandı / onaylandı</p></div><div><b>MÜŞTERİ</b><span><?=e($contract['customer_name'] ?? ($lead['customer_name'] ?: $lead['name']))?></span><p id="customerSignPrint"><?=!empty($contract['signed_by_customer'])?'☑':'☐'?> Dijital olarak imzalandı / onaylandı</p><p id="waSignPrint"><?=!empty($contract['written_approval'])?'☑':'☐'?> WhatsApp yazılı onayı alındı</p></div></div>
  <div class="elegant-back-approval"><span id="waBackPrint"><?=!empty($contract['written_approval'])?'☑ WhatsApp yazılı onayı alındı':'☐ WhatsApp yazılı onayı alınmadı'?></span><span id="companyBackPrint"><?=!empty($contract['signed_by_company'])?'☑ Hizmet veren onayladı':'☐ Hizmet veren onaylamadı'?></span><span id="customerBackPrint"><?=!empty($contract['signed_by_customer'])?'☑ Müşteri onayladı':'☐ Müşteri onaylamadı'?></span></div>
  <footer class="elegant-footer">Bu form teklif, sipariş ve sözleşme amacıyla düzenlenir; referans numarasıyla arşivlenir.</footer>
</section>
</main><script>window.CSRF='<?=csrf_token()?>';</script><script>
function qs(s){return document.querySelector(s)}
function rows(){return [...document.querySelectorAll('#itemsTable tbody tr')].map(tr=>({name:tr.children[0].querySelector('input').value,qty:tr.children[1].querySelector('input').value,amount:tr.children[2].querySelector('input').value})).filter(x=>x.name)}
function addItem(){document.querySelector('#itemsTable tbody').insertAdjacentHTML('beforeend','<tr><td><input placeholder="Ek hizmet"></td><td><input type="number" value="1"></td><td><input type="number" step="0.01" value="0"></td><td class="no-print"><button class="btn danger mini" onclick="this.closest(\'tr\').remove();calcTotal()" type="button">Sil</button></td></tr>')}
function calcTotal(){let total=parseFloat(qs('#orderAmount').value||0); rows().forEach(x=>total+=parseFloat(x.amount||0)); const paid=parseFloat(qs('#amountPaid').value||0); const rem=Math.max(0,total-paid); const el=qs('#remainingAmountPreview'); if(el) el.textContent=rem.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+' TL'; return total}
function escHtml(v){return String(v??'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
function syncTermsPreview(){
  calcTotal();
  const terms=qs('#terms')?.value||''; const preview=qs('#termsPreview'); if(preview) preview.innerHTML=escHtml(terms).replace(/\n/g,'<br>');
  const wc=qs('#writtenApproval')?.checked, sc=qs('#signedCompany')?.checked, su=qs('#signedCustomer')?.checked;
  if(qs('#waSignPrint')) qs('#waSignPrint').textContent=(wc?'☑':'☐')+' WhatsApp yazılı onayı alındı';
  if(qs('#companySignPrint')) qs('#companySignPrint').textContent=(sc?'☑':'☐')+' Dijital olarak imzalandı / onaylandı';
  if(qs('#customerSignPrint')) qs('#customerSignPrint').textContent=(su?'☑':'☐')+' Dijital olarak imzalandı / onaylandı';
  if(qs('#waBackPrint')) qs('#waBackPrint').textContent=wc?'☑ WhatsApp yazılı onayı alındı':'☐ WhatsApp yazılı onayı alınmadı';
  if(qs('#companyBackPrint')) qs('#companyBackPrint').textContent=sc?'☑ Hizmet veren onayladı':'☐ Hizmet veren onaylamadı';
  if(qs('#customerBackPrint')) qs('#customerBackPrint').textContent=su?'☑ Müşteri onayladı':'☐ Müşteri onaylamadı';
}
['terms','writtenApproval','signedCompany','signedCustomer','orderAmount','amountPaid'].forEach(id=>qs('#'+id)?.addEventListener('input',syncTermsPreview));
window.addEventListener('beforeprint', syncTermsPreview);
async function saveContract(){const payload={csrf:window.CSRF,id:qs('#contractId').value,lead_id:qs('#leadId').value,customer_name:qs('#customerName').value,customer_phone:qs('#customerPhone').value,customer_title:qs('#customerTitle').value,customer_tax_info:qs('#customerTax').value,customer_address:qs('#customerAddress').value,package_type:qs('#contractPackage').value,package_price:qs('#contractPackagePrice').value,order_amount:qs('#orderAmount').value,amount_paid:qs('#amountPaid').value,payment_status:qs('#paymentStatus').value,order_status:qs('#orderStatus').value,extra_items:rows(),start_date:qs('#startDate').value,estimated_delivery_date:qs('#estimatedDeliveryDate').value,actual_delivery_date:qs('#actualDeliveryDate').value,revision_limit:qs('#revisionLimit').value,revision_used:qs('#revisionUsed').value,domain_owner:qs('#domainOwner').value,domain_name:qs('#domainName').value,domain_provider:qs('#domainProvider').value,domain_expiry_date:qs('#domainExpiry').value,hosting_provider:qs('#hostingProvider').value,hosting_expiry_date:qs('#hostingExpiry').value,renewal_fee:qs('#renewalFee').value,payment_receipt_file:qs('#receiptFile').value,terms:qs('#terms').value,written_approval:qs('#writtenApproval').checked,signed_by_company:qs('#signedCompany').checked,signed_by_customer:qs('#signedCustomer').checked}; const r=await fetch('api/contracts.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}); const d=await r.json(); if(!d.ok){alert(d.error||'Kaydedilemedi');return;} alert('Sözleşme kaydedildi'); location.href='contract.php?id='+d.id;}
async function deleteContract(id){if(!confirm('Sözleşme silinsin mi?'))return; const r=await fetch('api/contracts.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:window.CSRF,action:'delete',id})}); const d=await r.json(); if(d.ok){alert('Silindi'); location.href='index.php';} else alert('Silinemedi');}
syncTermsPreview();
</script></body></html>
