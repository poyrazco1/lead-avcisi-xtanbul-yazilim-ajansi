<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="lead-import-template.csv"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['İşletme Adı','Telefon','Sektör','Alt Sektör','Şehir','İlçe','Adres','Not','Durum','Website','Google Maps Linki','Puan','Yorum Sayısı'], ';');
fputcsv($out, ['Örnek Kombi Servisi','0532 000 00 00','Teknik Servis & Tamir','Kombi Servisi','İstanbul','Bağcılar','Örnek Mah. Örnek Cad.','WhatsApp atılacak','Aranmadı','','','4.7','28'], ';');
fclose($out);
