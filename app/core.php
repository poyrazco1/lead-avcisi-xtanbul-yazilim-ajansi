<?php
$config = require __DIR__.'/config.php';
function e($v){return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');}
function cfg($k){global $config; return $config[$k] ?? null;}
function base_url(){ $b=cfg('base_url'); if($b) return rtrim($b,'/'); $https=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'; return ($https?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost');}
function data_path($file){return __DIR__.'/../data/'.$file;}
function posts_path(){return data_path('posts.json');}
function refs_path(){return data_path('references.json');}
function site_settings_path(){return data_path('site_settings.json');}
function json_read($path,$default=[]){ if(!file_exists($path)) return $default; $d=json_decode((string)file_get_contents($path), true); return is_array($d)?$d:$default; }
function json_write($path,$arr){ if(!is_dir(dirname($path))) mkdir(dirname($path),0755,true); return file_put_contents($path, json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); }
function posts_all(){ $p=posts_path(); if(!file_exists($p)) seed_posts(); return json_read($p,[]);} 
function posts_save($arr){return json_write(posts_path(), array_values($arr));}
function references_all(){ $p=refs_path(); if(!file_exists($p)) seed_references(); return json_read($p,[]);} 
function references_save($arr){return json_write(refs_path(), array_values($arr));}
function site_settings(){ $p=site_settings_path(); if(!file_exists($p)) seed_site_settings(); $s=json_read($p,[]); return array_merge(default_site_settings(), $s); }
function site_settings_save($s){ return json_write(site_settings_path(), array_merge(site_settings(), $s)); }
function default_site_settings(){return [
 'company_name'=>'Xtanbul Yazılım Agent',
 'logo_path'=>'/assets/xtanbul-logo.svg',
 'favicon_path'=>'/assets/favicon.svg',
 'contact_phone'=>'+905334131093',
 'contact_whatsapp'=>'+905334131093',
 'contact_email'=>'info@xtanbulyazilim.com',
 'contact_address'=>'NEF 11 Offices, Merkez Mah. Ayazma Cad. No:21 Kat:4 Daire:63, Kağıthane / İstanbul',
 'maps_url'=>'https://www.google.com/maps/search/?api=1&query=NEF%2011%20Offices%20Ka%C4%9F%C4%B1thane%20%C4%B0stanbul',
 'instagram'=>'',
 'linkedin'=>'',
 'hero_badge'=>'Web sitesi olmayan işletmelere hızlı dijital çıkış',
 'hero_title'=>'Uygun fiyata SEO uyumlu web sitesi yaptırmak isteyen işletmeler için dijital atölye.',
 'hero_desc'=>'Tek sayfa HTML site, randevulu web sitesi, çok sayfalı kurumsal site, yönetim paneli, çok dil, reklam danışmanlığı ve e-ticaret danışmanlığı.',
 'cta_title'=>'Siteniz yoksa müşteriniz rakibin sitesine gider.',
 'cta_desc'=>'İşletmenize uygun paketi seçelim, aynı gün teklif çıkaralım, onayınızla hızlıca yayına hazırlayalım.'
];}
function seed_site_settings(){ json_write(site_settings_path(), default_site_settings()); }
function slugify($s){$map=['ı'=>'i','ğ'=>'g','ü'=>'u','ş'=>'s','ö'=>'o','ç'=>'c','İ'=>'i','Ğ'=>'g','Ü'=>'u','Ş'=>'s','Ö'=>'o','Ç'=>'c']; $s=strtr($s,$map); $s=strtolower($s); $s=preg_replace('/[^a-z0-9]+/','-',$s); return trim($s,'-')?:'yazi';}
function seed_posts(){ $sample=[
 ['type'=>'blog','title'=>'Uygun Fiyata Web Sitesi Yaptırırken Nelere Dikkat Edilmeli?','slug'=>'uygun-fiyata-web-sitesi-yaptirirken','summary'=>'Ucuz site ile satış getiren uygun fiyatlı site aynı şey değildir. Küçük işletmeler için doğru kontrol listesi.','content'=>'Uygun fiyatlı web sitesi sadece ucuz sayfa değildir. Hız, mobil uyum, net hizmet anlatımı, WhatsApp yönlendirmesi, Google Harita bağlantısı, KVKK ve güven öğeleri birlikte düşünülmelidir. Xtanbul Yazılım Agent olarak tek sayfa HTML siteden yönetim panelli ve randevulu yapılara kadar ihtiyaca göre paket sunarız. En düşük paket hızlı yayına alınır; daha fazla sayfa, çok dil, randevu, blog ve panel ihtiyacı olduğunda proje paketlenir.','created_at'=>date('Y-m-d')],
 ['type'=>'blog','title'=>'Tek Sayfa Web Sitesi Kimler İçin Yeterlidir?','slug'=>'tek-sayfa-web-sitesi-kimler-icin-yeterli','summary'=>'Kombici, halı yıkama, güzellik salonu ve teknik servis gibi işletmeler için tek sayfa web sitesi ne zaman doğru seçimdir?','content'=>'Tek sayfa web sitesi; hizmetlerini hızlı anlatmak, telefon ve WhatsApp üzerinden müşteri almak isteyen yerel işletmeler için iyi başlangıçtır. Daha fazla hizmet, blog, galeri veya randevu gerekiyorsa çok sayfalı yapıya geçmek gerekir. Tek sayfa site özellikle hızlı güven vermek, mobilde iyi görünmek ve Google kaydından gelen müşteriyi kaybetmemek için kullanılır.','created_at'=>date('Y-m-d')],
 ['type'=>'blog','title'=>'Küçük İşletmeler İçin Google’da Güven Veren Site Nasıl Olmalı?','slug'=>'kucuk-isletmeler-icin-google-da-guven-veren-site','summary'=>'Yerel işletmeler için telefon, WhatsApp, harita, yorum ve hizmet sayfası düzeni nasıl kurulmalı?','content'=>'Küçük işletmelerin web sitesinde en büyük amaç müşteriyi hızlıca güvene ve iletişime taşımaktır. Ana sayfada hizmet, bölge, telefon, WhatsApp, harita, çalışma saatleri, sık sorulan sorular ve gerekirse fiyat başlangıcı görünmelidir. Rakibin sitesi varken sizin yalnızca Google kaydınız varsa karar anında zayıf kalırsınız.','created_at'=>date('Y-m-d')],
 ['type'=>'haber','title'=>'Xtanbul Yazılım Agent Paketli Web Site Hizmetlerine Başladı','slug'=>'xtanbul-yazilim-agent-paketli-web-site-hizmetleri','summary'=>'Tek sayfa, randevulu, çok sayfalı ve yönetim panelli web sitesi paketleri işletmeler için hazırlandı.','content'=>'Xtanbul Yazılım Agent; küçük işletmelerin dijitalde görünmesi için uygun fiyatlı web sitesi, SEO uyumlu içerik, reklam danışmanlığı ve e-ticaret danışmanlığı hizmetlerini paketli olarak sunmaya başladı. Paketler tek sayfa HTML site, randevulu site, çok sayfalı site, yönetim panelli site ve çok dilli site seçenekleriyle ilerler.','created_at'=>date('Y-m-d')]
 ]; json_write(posts_path(), $sample); }
function seed_references(){ $sample=[
 ['name'=>'Örnek Güzellik Salonu','website'=>'https://ornek-guzellik.com','logo'=>'','active'=>true,'note'=>'Randevu odaklı web sitesi','category'=>'Web Tasarım / Randevu'],
 ['name'=>'Örnek Teknik Servis','website'=>'https://ornek-servis.com','logo'=>'','active'=>true,'note'=>'WhatsApp dönüşümlü servis sitesi','category'=>'Web Tasarım / SEO'],
 ['name'=>'Örnek Medikal Firma','website'=>'https://ornek-medikal.com','logo'=>'','active'=>false,'note'=>'Kurumsal ürün tanıtım sitesi','category'=>'Kurumsal Site']
 ]; json_write(refs_path(), $sample); }
function upload_image($field,$prefix='img'){
 if(empty($_FILES[$field]) || ($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) return '';
 $name=$_FILES[$field]['name'] ?? 'image'; $ext=strtolower(pathinfo($name, PATHINFO_EXTENSION));
 $allowed=['png','jpg','jpeg','webp','gif','svg','ico']; if(!in_array($ext,$allowed,true)) return '';
 $dir=__DIR__.'/../uploads'; if(!is_dir($dir)) mkdir($dir,0755,true);
 $safe=$prefix.'-'.date('YmdHis').'-'.bin2hex(random_bytes(3)).'.'.$ext; $dest=$dir.'/'.$safe;
 if(move_uploaded_file($_FILES[$field]['tmp_name'],$dest)) return '/uploads/'.$safe; return '';
}
function normalize_whatsapp($n){ return preg_replace('/\D+/','',(string)$n); }
function first_letter($s){ $s=trim((string)$s); if($s==='') return '?'; if(function_exists('mb_substr')) return mb_substr($s,0,1,'UTF-8'); return substr($s,0,1); }
function service_groups(){ return [
 ['title'=>'Web Projeleri','icon'=>'⌁','items'=>['Web Sitesi Tasarımı','Tek Sayfa HTML Site','E-Ticaret Sitesi','Özel Web Yazılım','Randevu Sistemi','Yönetim Paneli']],
 ['title'=>'Dijital Pazarlama','icon'=>'◎','items'=>['SEO Danışmanlığı','Google Ads Danışmanlığı','Sosyal Medya Reklamları','Sosyal Medya Yönetimi','Dönüşüm Odaklı Landing Page']],
 ['title'=>'Kurumsal Kimlik','icon'=>'◇','items'=>['Logo Yönlendirmesi','Katalog Sayfası','Marka Dili','Görsel Düzen','Referans Sunumu']],
 ['title'=>'E-Ticaret Danışmanlığı','icon'=>'▣','items'=>['Kategori Yapısı','Ürün Sayfası Düzeni','Kampanya Akışı','Pazaryeri Hazırlığı','Dönüşüm Danışmanlığı']]
 ]; }
function sector_targets(){return ['Kombici','Halı yıkama','Güzellik salonu','Medikal firma','Sanayi üreticisi','Oto servis','Telefon tamiri','Pimapen','Tekstil üreticisi','Diş kliniği','Veteriner','Emlakçı','Kuyumcu','Restoran','Klinik','Maden ve imalat'];}
function meta_for($page){$m=[
 'home'=>['title'=>'Xtanbul Yazılım Agent | Uygun Fiyata SEO Uyumlu Web Sitesi','desc'=>'Tek sayfa, randevulu, çok sayfalı ve yönetim panelli SEO uyumlu web sitesi. Küçük işletmeler için hızlı, modern ve uygun fiyatlı dijital çözümler.'],
 'hizmetler'=>['title'=>'Web Tasarım, SEO, Reklam ve E-Ticaret Danışmanlığı | Xtanbul','desc'=>'Web site tasarımı, SEO, Google Ads, sosyal medya, e-ticaret danışmanlığı, randevulu web sitesi ve yönetim paneli hizmetleri.'],
 'paketler'=>['title'=>'Web Sitesi Paketleri | 4.999 TL’den Başlayan Fiyatlar','desc'=>'Tek sayfa HTML site, randevulu web sitesi, çok sayfalı site ve yönetim panelli web sitesi paketleri.'],
 'referanslar'=>['title'=>'Referanslarımız | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent referansları, web sitesi çalışmaları, aktif proje ve müşteri portföyü.'],
 'blog'=>['title'=>'Web Sitesi ve SEO Blog | Xtanbul Yazılım Agent','desc'=>'Uygun fiyatlı web sitesi, SEO, randevu sistemi, Google görünürlüğü ve dijital pazarlama yazıları.'],
 'haberler'=>['title'=>'Haberler | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent duyuruları, kampanyaları ve dijital hizmet haberleri.'],
 'iletisim'=>['title'=>'İletişim | Xtanbul Yazılım Agent','desc'=>'Uygun fiyatlı web sitesi yaptırmak için Xtanbul Yazılım Agent ile iletişime geçin.'],
 'kvkk'=>['title'=>'KVKK Aydınlatma Metni | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent KVKK aydınlatma metni ve kişisel verilerin işlenmesi hakkında bilgilendirme.'],
 'gizlilik'=>['title'=>'Gizlilik Politikası | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent gizlilik politikası.'],
 'cerez'=>['title'=>'Çerez Politikası | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent çerez politikası.'],
]; return $m[$page]??$m['home'];}
function nav_active($page,$key){return $page===$key?' class="active"':'';}
function header_html($page='home',$custom=null){$s=site_settings(); $meta=$custom?:meta_for($page); $url=base_url().($_SERVER['REQUEST_URI']??'/'); $wa=normalize_whatsapp($s['contact_whatsapp']); echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($meta['title']).'</title><meta name="description" content="'.e($meta['desc']).'"><link rel="canonical" href="'.e($url).'"><meta property="og:title" content="'.e($meta['title']).'"><meta property="og:description" content="'.e($meta['desc']).'"><meta property="og:type" content="website"><meta name="theme-color" content="#080a14"><link rel="icon" href="'.e($s['favicon_path']).'"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="stylesheet" href="/assets/style.css"><script type="application/ld+json">'.json_encode(['@context'=>'https://schema.org','@type'=>'ProfessionalService','name'=>$s['company_name'],'url'=>base_url(),'telephone'=>$s['contact_phone'],'email'=>$s['contact_email'],'address'=>$s['contact_address'],'areaServed'=>'Türkiye','priceRange'=>'₺₺','serviceType'=>['Web sitesi tasarımı','SEO','Google Ads danışmanlığı','Sosyal medya danışmanlığı','E-ticaret danışmanlığı']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script></head><body><div class="site-bg"></div><header class="topbar"><a class="brand" href="/"><img src="'.e($s['logo_path']).'" alt="'.e($s['company_name']).' logo"><span>Xtanbul<em>Agent</em></span></a><button class="menu-btn" onclick="document.body.classList.toggle(\'menu-open\')">Menü</button><nav class="mainnav"><a'.nav_active($page,'home').' href="/">Merhaba</a><a'.nav_active($page,'hizmetler').' href="/hizmetler">Neler Yapıyoruz?</a><a'.nav_active($page,'referanslar').' href="/referanslar">Neler Yaptık?</a><a'.nav_active($page,'blog').' href="/blog">Neler Yazdık?</a><a'.nav_active($page,'iletisim').' href="/iletisim">Bize Ulaşın</a><a class="nav-cta" href="/paketler">Paketler</a></nav></header><a class="whatsapp-float" aria-label="WhatsApp\'tan teklif al" href="https://wa.me/'.$wa.'?text=Merhaba%2C%20web%20sitesi%20teklifi%20almak%20istiyorum." target="_blank" rel="noopener"><svg class="wa-icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path class="wa-bubble" d="M16.03 3.2C9.1 3.2 3.47 8.78 3.47 15.64c0 2.34.66 4.54 1.8 6.42L3.2 28.8l6.95-2.02a12.7 12.7 0 0 0 5.88 1.47c6.94 0 12.57-5.58 12.57-12.45S22.97 3.2 16.03 3.2Z"/><path class="wa-phone" d="M22.98 19.24c-.38 1.08-1.9 1.98-2.74 2.1-.73.1-1.68.15-2.7-.17-.62-.2-1.42-.46-2.44-.9-4.3-1.85-7.1-6.12-7.31-6.4-.21-.28-1.75-2.32-1.75-4.43s1.1-3.15 1.48-3.58c.38-.43.84-.54 1.12-.54h.8c.25.01.6-.09.93.7.36.86 1.22 2.97 1.33 3.18.1.22.17.48.03.76-.13.28-.2.45-.4.7-.2.25-.43.56-.62.75-.2.2-.4.42-.17.85.23.43 1.03 1.7 2.22 2.76 1.53 1.36 2.81 1.78 3.24 1.99.43.21.68.18.93-.11.25-.3 1.07-1.25 1.36-1.68.29-.43.58-.36.98-.21.4.14 2.53 1.2 2.96 1.41.43.22.72.33.83.51.1.18.1 1.07-.28 2.15Z"/></svg><span>WhatsApp</span></a>';}
function footer_html(){ $s=site_settings(); echo '<footer class="footer"><div><b>'.e($s['company_name']).'</b><p>Uygun fiyatlı, hızlı, SEO uyumlu web sitesi ve dijital danışmanlık.</p><small>'.e($s['contact_address']).'</small></div><div class="footer-links"><a href="/hizmetler">Hizmetler</a><a href="/paketler">Paketler</a><a href="/referanslar">Referanslar</a><a href="/blog">Blog</a><a href="/iletisim">İletişim</a><a href="/kvkk">KVKK</a><a href="/gizlilik">Gizlilik</a><a href="/cerez">Çerez</a></div></footer><script>document.addEventListener("keydown",e=>{if(e.key==="Escape")document.body.classList.remove("menu-open")});</script></body></html>';}
function require_admin(){session_start(); if(empty($_SESSION['admin'])){header('Location: admin.php'); exit;}}
?>
