<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'error'=>'Sadece POST desteklenir.'], 405);
if (!csrf_check($_POST['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF doğrulaması başarısız.'], 403);
if (empty($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) json_response(['ok'=>false,'error'=>'CSV dosyası yüklenmedi.'], 422);
if ((int)($_FILES['csv']['size'] ?? 0) > 8 * 1024 * 1024) json_response(['ok'=>false,'error'=>'CSV dosyası çok büyük. Maksimum 8 MB.'], 422);

$defaultSector = trim((string)($_POST['default_sector'] ?? 'CSV İçe Aktarım'));
$defaultCity = trim((string)($_POST['default_city'] ?? ''));
$assignedTo = trim((string)($_POST['assigned_to'] ?? ''));
$tmp = $_FILES['csv']['tmp_name'];
$first = (string)file_get_contents($tmp, false, null, 0, 4096);
$semicolon = substr_count($first, ';');
$comma = substr_count($first, ',');
$delimiter = $semicolon >= $comma ? ';' : ',';
$fh = fopen($tmp, 'r');
if (!$fh) json_response(['ok'=>false,'error'=>'CSV dosyası okunamadı.'], 422);

$header = fgetcsv($fh, 0, $delimiter);
if (!$header || count($header) < 2) json_response(['ok'=>false,'error'=>'CSV başlık satırı okunamadı.'], 422);
$header = array_map('clean_csv_header', $header);
$map = [];
foreach ($header as $i => $h) {
    $key = canonical_csv_key($h);
    if ($key) $map[$key] = $i;
}
if (!isset($map['phone']) && !isset($map['name'])) json_response(['ok'=>false,'error'=>'CSV içinde en az Telefon veya İşletme Adı kolonu olmalı.'], 422);

$saved=0; $duplicates=0; $blacklisted=0; $failed=0; $rowNo=1; $warnings=[];
while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
    $rowNo++;
    if (count(array_filter($row, fn($v)=>trim((string)$v) !== '')) === 0) continue;
    $name = csv_val($row, $map, 'name');
    $phone = csv_val($row, $map, 'phone');
    if ($name === '' && $phone === '') { $failed++; $warnings[] = "Satır {$rowNo}: isim/telefon boş"; continue; }
    $sector = csv_val($row, $map, 'sector') ?: $defaultSector;
    $sub = csv_val($row, $map, 'sub_sector') ?: $sector;
    $city = csv_val($row, $map, 'city') ?: $defaultCity;
    $district = csv_val($row, $map, 'district');
    $status = csv_val($row, $map, 'status') ?: 'Aranmadı';
    $assigned = csv_val($row, $map, 'assigned_to') ?: $assignedTo;
    $rating = csv_val($row, $map, 'rating');
    $reviews = csv_val($row, $map, 'review_count');
    $lead = [
        'place_id' => csv_val($row, $map, 'place_id'),
        'name' => $name ?: ('CSV Lead ' . $rowNo),
        'sector' => $sector,
        'sub_sector' => $sub,
        'city' => $city,
        'district' => $district,
        'address' => csv_val($row, $map, 'address'),
        'phone' => $phone,
        'website' => csv_val($row, $map, 'website'),
        'maps_url' => csv_val($row, $map, 'maps_url'),
        'rating' => $rating !== '' ? (float)str_replace(',', '.', $rating) : null,
        'review_count' => $reviews !== '' ? (int)preg_replace('/\D+/', '', $reviews) : null,
        'status' => valid_import_status($status) ? $status : 'Aranmadı',
        'note' => csv_val($row, $map, 'note'),
        'source' => 'CSV Import',
        'assigned_to' => $assigned,
        'package_type' => csv_val($row, $map, 'package_type') ?: 'onepage',
        'package_price' => csv_val($row, $map, 'package_price') ?: package_price('onepage'),
        'raw' => ['csv_row'=>$rowNo],
    ];
    $lead['whatsapp_message'] = csv_val($row, $map, 'whatsapp_message') ?: sector_message($lead['name'], $sector, $sub, $lead['package_price'], (string)cfg('default_payment_step'), $lead['package_type'], ['ads','ecommerce','social'], true);
    $r = save_lead($lead);
    if (!empty($r['saved'])) $saved++;
    elseif (!empty($r['duplicate'])) $duplicates++;
    elseif (!empty($r['blacklisted'])) $blacklisted++;
    else { $failed++; if (!empty($r['error'])) $warnings[] = "Satır {$rowNo}: " . $r['error']; }
}
fclose($fh);
json_response(['ok'=>true,'saved'=>$saved,'duplicates'=>$duplicates,'blacklisted'=>$blacklisted,'failed'=>$failed,'warnings'=>array_slice($warnings,0,20)]);

function clean_csv_header(string $v): string {
    $v = preg_replace('/^\xEF\xBB\xBF/', '', $v);
    $v = trim($v);
    return $v;
}
function normalize_key_tr(string $v): string {
    $v = lead_lower($v);
    $v = strtr($v, ['ı'=>'i','ğ'=>'g','ü'=>'u','ş'=>'s','ö'=>'o','ç'=>'c','İ'=>'i','Ğ'=>'g','Ü'=>'u','Ş'=>'s','Ö'=>'o','Ç'=>'c']);
    $v = preg_replace('/[^a-z0-9]+/u', '_', $v);
    return trim((string)$v, '_');
}
function canonical_csv_key(string $h): ?string {
    $k = normalize_key_tr($h);
    $aliases = [
        'name' => ['isletme_adi','firma_adi','firma','isletme','ad','adi','name','business_name','company','unvan'],
        'phone' => ['telefon','tel','gsm','cep','phone','mobile','numara','telefon_numarasi'],
        'sector' => ['sektor','ana_kategori','kategori','sector','category'],
        'sub_sector' => ['alt_sektor','alt_kategori','meslek','hizmet','sub_sector','sub_category'],
        'city' => ['sehir','il','city'],
        'district' => ['ilce','bolge','mahalle','district','area'],
        'address' => ['adres','address','konum'],
        'note' => ['not','note','aciklama'],
        'status' => ['durum','status'],
        'assigned_to' => ['atanan','personel','assigned','assigned_to','sorumlu'],
        'website' => ['website','web_sitesi','site','url'],
        'maps_url' => ['google_maps_linki','maps_url','harita','google_maps','map'],
        'rating' => ['puan','rating'],
        'review_count' => ['yorum_sayisi','yorum','review_count','reviews'],
        'place_id' => ['place_id','google_place_id'],
        'package_type' => ['paket','package','package_type'],
        'package_price' => ['fiyat','paket_fiyati','price','package_price'],
        'whatsapp_message' => ['whatsapp_mesaji','mesaj','whatsapp_message'],
    ];
    foreach ($aliases as $canonical => $list) if (in_array($k, $list, true)) return $canonical;
    return null;
}
function csv_val(array $row, array $map, string $key): string {
    if (!isset($map[$key])) return '';
    $i = (int)$map[$key];
    return trim((string)($row[$i] ?? ''));
}
function valid_import_status(string $status): bool {
    return in_array($status, ['Aranmadı','WhatsApp gönderildi','Arandı','Cevap bekleniyor','Teklif istedi','Ödeme linki gönderildi','Ödeme bekleniyor','Kapora alındı','Müşteri oldu','İlgilenmedi','Tekrar aranmasın'], true);
}
