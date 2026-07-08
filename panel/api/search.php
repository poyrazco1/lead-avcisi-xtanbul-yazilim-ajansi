<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
$data = input_json();
if (!csrf_check($data['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF doğrulaması başarısız.'], 403);

$category = trim((string)($data['category'] ?? ''));
$sub = trim((string)($data['sub_sector'] ?? ''));
$custom = trim((string)($data['custom_keyword'] ?? ''));
$searchTerm = trim((string)($data['search_term'] ?? ''));
$city = trim((string)($data['city'] ?? ''));
$district = trim((string)($data['district'] ?? ''));
$limit = max(1, min((int)cfg('max_limit_per_run'), (int)($data['limit'] ?? 60)));
$price = trim((string)($data['price'] ?? ''));
$payment = trim((string)($data['payment'] ?? ''));
$package = trim((string)($data['package'] ?? 'onepage'));
$assignedTo = trim((string)($data['assigned_to'] ?? ''));
$packagePrices = $data['package_prices'] ?? [];
if (!is_array($packagePrices)) $packagePrices = [];
$consulting = $data['consulting'] ?? [];
if (!is_array($consulting)) $consulting = [];
$multiLang = !empty($data['multi_lang']);
// v5 kalite filtreleri + satış ayarı (geriye uyumlu — varsayılanlar eski davranış)
$source = trim((string)($data['source'] ?? 'Google Places')) ?: 'Google Places';
$priority = trim((string)($data['priority'] ?? ''));
$leadNote = trim((string)($data['note'] ?? ''));
$minRating = (float)($data['min_rating'] ?? 0);
$minReviews = (int)($data['min_reviews'] ?? 0);
$onlyNoWebsite = array_key_exists('only_no_website', $data) ? !empty($data['only_no_website']) : true;
$requirePhone = array_key_exists('require_phone', $data) ? !empty($data['require_phone']) : true;
$dedupeName = !empty($data['dedupe_name']);
$searchTerm = $searchTerm ?: ($custom ?: ($sub ?: $category));
$category = $category ?: 'Serbest Arama';
$sub = $sub ?: $searchTerm;
if (!$searchTerm || !$city) json_response(['ok'=>false,'error'=>'Arama kelimesi ve şehir zorunlu. Kategori seçmeden arayacaksan serbest arama kelimesi yaz.'], 422);
$key = effective_google_api_key();
if (!$key) json_response(['ok'=>false,'error'=>'Google Places API key sunucuda tanımlı değil.'], 500);

$query = trim($searchTerm . ' ' . $district . ' ' . $city . ' Türkiye');
$endpoint = 'https://places.googleapis.com/v1/places:searchText';
$fieldMask = 'places.id,places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,places.googleMapsUri,places.rating,places.userRatingCount,places.businessStatus,nextPageToken';
$saved = 0; $duplicates = 0; $checked = 0; $skippedWebsite = 0; $skippedPhone = 0; $skippedFilter = 0; $blacklisted = 0; $errors = []; $found = [];
$pageToken = null; $pages = 0;

if ($price === '') $price = package_price($package, $packagePrices);

do {
    $pages++;
    $body = ['textQuery'=>$query, 'languageCode'=>'tr', 'regionCode'=>'TR', 'pageSize'=>20];
    if ($pageToken) { $body['pageToken'] = $pageToken; usleep(1200000); }
    $resp = google_post($endpoint, $body, $key, $fieldMask);
    if (!$resp['ok']) json_response(['ok'=>false,'error'=>$resp['error'] ?: 'Google Places isteği başarısız.', 'debug'=>$resp['debug'] ?? null], 502);
    $json = $resp['json'];
    foreach (($json['places'] ?? []) as $p) {
        $checked++;
        $phone = $p['nationalPhoneNumber'] ?? $p['internationalPhoneNumber'] ?? '';
        $website = trim((string)($p['websiteUri'] ?? ''));
        $rating = (float)($p['rating'] ?? 0);
        $reviews = (int)($p['userRatingCount'] ?? 0);
        if ($requirePhone && !$phone) { $skippedPhone++; continue; }
        if ($onlyNoWebsite && $website !== '') { $skippedWebsite++; continue; }
        if ($minRating > 0 && $rating < $minRating) { $skippedFilter++; continue; }
        if ($minReviews > 0 && $reviews < $minReviews) { $skippedFilter++; continue; }
        if (is_blacklisted_phone((string)$phone)) { $blacklisted++; continue; }
        $name = $p['displayName']['text'] ?? 'İşletme';
        if ($dedupeName && lead_name_exists((string)$name)) { $duplicates++; continue; }
        $lead = [
            'place_id' => $p['id'] ?? '',
            'name' => $name,
            'sector' => $category,
            'sub_sector' => $sub,
            'city' => $city,
            'district' => $district,
            'address' => $p['formattedAddress'] ?? '',
            'phone' => $phone,
            'website' => $website,
            'maps_url' => $p['googleMapsUri'] ?? '',
            'rating' => $p['rating'] ?? null,
            'review_count' => $p['userRatingCount'] ?? null,
            'source' => $source,
            'assigned_to' => $assignedTo,
            'package_type' => $package,
            'package_price' => $price,
            'raw' => $p,
        ];
        $lead['whatsapp_message'] = sector_message($name, $category, $sub, $price, $payment, $package, $consulting, $multiLang, $packagePrices);
        $r = save_lead($lead);
        if (!empty($r['saved'])) {
            $saved++;
            $found[] = $name;
            $newId = (int)($r['id'] ?? 0);
            $extra = [];
            if ($priority !== '') $extra['priority'] = $priority;
            if ($leadNote !== '') $extra['note'] = $leadNote;
            if ($newId && $extra) update_lead_fields($newId, $extra);
        }
        else if (!empty($r['duplicate'])) $duplicates++;
        else if (!empty($r['blacklisted'])) $blacklisted++;
        else if (!empty($r['error'])) $errors[] = $r['error'];
        if ($saved >= $limit) break 2;
    }
    $pageToken = $json['nextPageToken'] ?? null;
} while ($pageToken && $pages < 3 && $saved < $limit);

json_response(['ok'=>true,'query'=>$query,'saved'=>$saved,'duplicates'=>$duplicates,'checked'=>$checked,'skippedWebsite'=>$skippedWebsite,'skippedPhone'=>$skippedPhone,'skippedFilter'=>$skippedFilter,'blacklisted'=>$blacklisted,'pages'=>$pages,'errors'=>$errors,'found'=>array_slice($found,0,20)]);

function google_post(string $url, array $body, string $key, string $fieldMask): array {
    $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (extension_loaded('curl')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Goog-Api-Key: ' . $key,
                'X-Goog-FieldMask: ' . $fieldMask,
            ],
            CURLOPT_TIMEOUT => 35,
        ]);
        $out = curl_exec($ch); $err = curl_error($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    } else {
        $ctx = stream_context_create(['http'=>[
            'method'=>'POST',
            'header'=>"Content-Type: application/json\r\nX-Goog-Api-Key: {$key}\r\nX-Goog-FieldMask: {$fieldMask}\r\n",
            'content'=>$payload,
            'timeout'=>35,
            'ignore_errors'=>true,
        ]]);
        $out = @file_get_contents($url, false, $ctx); $err = $out === false ? 'HTTP stream failed' : ''; $code = 200;
        if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
    }
    if ($out === false || trim((string)$out) === '') return ['ok'=>false,'error'=>$err ?: 'Google Places API bağlantısından boş cevap geldi. Sunucuda cURL/allow_url_fopen veya dış bağlantı engeli olabilir.','debug'=>['code'=>$code ?: 0]];
    $json = json_decode((string)$out, true);
    if ($code < 200 || $code >= 300) return ['ok'=>false,'error'=>$json['error']['message'] ?? $err ?: 'HTTP ' . $code,'debug'=>['code'=>$code]];
    if (!is_array($json)) return ['ok'=>false,'error'=>'Google cevabı JSON değil.','debug'=>['code'=>$code,'raw'=>substr((string)$out,0,300)]];
    return ['ok'=>true,'json'=>$json];
}
