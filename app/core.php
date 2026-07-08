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

/* ===== Kurumsal ön yüz içerikleri ===== */
function front_services(){ return [
 ['icon'=>'🖥️','title'=>'Web Tasarım','desc'=>'Mobil uyumlu, hızlı açılan, satış odaklı modern web sitesi tasarımı.'],
 ['icon'=>'🏢','title'=>'Kurumsal Web Sitesi','desc'=>'Çok sayfalı, güven veren, marka dilinize uygun kurumsal siteler.'],
 ['icon'=>'📄','title'=>'Tek Sayfa Web Sitesi','desc'=>'İşletmenizi tek ekranda anlatan, WhatsApp’a dönüştüren landing page.'],
 ['icon'=>'📅','title'=>'Randevulu Web Sitesi','desc'=>'Ziyaretçiyi randevu/talep formuyla doğrudan müşteriye çeviren yapı.'],
 ['icon'=>'⚙️','title'=>'Yönetim Panelli Site','desc'=>'İçerik, blog, referans ve siparişleri kendiniz yönetin.'],
 ['icon'=>'🛒','title'=>'E-Ticaret Danışmanlığı','desc'=>'Kategori, ürün ve kampanya kurgusuyla satışa hazır altyapı.'],
 ['icon'=>'🔍','title'=>'SEO Temel Kurulum','desc'=>'Teknik SEO, başlık/meta düzeni ve Google görünürlük temeli.'],
 ['icon'=>'📣','title'=>'Google Ads / Sosyal Medya','desc'=>'Doğru hedefleme ile reklam ve sosyal medya danışmanlığı.'],
];}
function why_us_cards(){ return [
 ['icon'=>'🎯','title'=>'Satış odaklı tasarım','desc'=>'Sadece güzel değil; ziyaretçiyi arayan ve yazan müşteriye dönüştüren yapı.'],
 ['icon'=>'💬','title'=>'WhatsApp & arama dönüşümü','desc'=>'Her sayfada tek tıkla WhatsApp ve arama ile hızlı iletişim.'],
 ['icon'=>'📱','title'=>'Mobil performans','desc'=>'Telefonda hızlı açılan, taşmayan, kusursuz görünen sayfalar.'],
 ['icon'=>'🔎','title'=>'SEO uyumlu yapı','desc'=>'Google’da bulunmanız için doğru başlık, içerik ve teknik temel.'],
 ['icon'=>'🤝','title'=>'Teslim sonrası destek','desc'=>'Yayına aldıktan sonra da yanınızdayız; yenileme ve bakım takibi.'],
 ['icon'=>'🧩','title'=>'Panel / içerik yönetimi','desc'=>'İçeriklerinizi kendiniz güncelleyebileceğiniz panel opsiyonu.'],
];}
function process_steps(){ return [
 ['no'=>'01','title'=>'İhtiyaç Analizi','desc'=>'Sektör, hedef ve rakip analizi ile doğru kapsam belirlenir.'],
 ['no'=>'02','title'=>'Teklif ve Paket Seçimi','desc'=>'Şeffaf fiyat ve kapsam yazılı hale gelir.'],
 ['no'=>'03','title'=>'Tasarım Taslağı','desc'=>'Marka dilinize uygun taslak hazırlanır ve onaylanır.'],
 ['no'=>'04','title'=>'İçerik ve Görsel','desc'=>'Metin, görsel ve SEO içerikleri hazırlanır.'],
 ['no'=>'05','title'=>'Kodlama','desc'=>'Mobil uyumlu, hızlı ve güvenli şekilde geliştirilir.'],
 ['no'=>'06','title'=>'Test','desc'=>'Mobil, tablet ve masaüstünde eksiksiz test edilir.'],
 ['no'=>'07','title'=>'Yayına Alma','desc'=>'Domain/hosting kurulumu ile siteniz yayına alınır.'],
 ['no'=>'08','title'=>'Takip ve Destek','desc'=>'Yayın sonrası performans takibi ve sürekli destek.'],
];}
function package_cards(){ return [
 ['name'=>'Başlangıç Tek Sayfa','who'=>'Hızlı dijital vitrin isteyen yerel işletmeler','price'=>'4.999 TL’den başlar','features'=>['Mobil uyumlu tek sayfa','WhatsApp & arama butonu','SEO temel kurulum','Harita ve iletişim'],'featured'=>false],
 ['name'=>'Tek Sayfa + Randevu','who'=>'Randevu/talep toplayan hizmet işletmeleri','price'=>'Teklif alınır','features'=>['Randevu / talep formu','Hizmet alanları','KVKK sayfaları','WhatsApp takip'],'featured'=>false],
 ['name'=>'Çok Sayfalı Kurumsal','who'=>'Kurumsal duruş isteyen markalar','price'=>'Teklif alınır','features'=>['Hizmet sayfaları','Blog / haber altyapısı','Referans yönetimi','Çok dil opsiyonu'],'featured'=>true],
 ['name'=>'Yönetim Panelli Site','who'=>'İçeriğini kendi yöneten işletmeler','price'=>'Teklif alınır','features'=>['İçerik yönetim paneli','Blog/haber/referans ekleme','Randevu opsiyonu','Sipariş takip'],'featured'=>false],
 ['name'=>'Özel Yazılım / CRM','who'=>'Süreçlerini dijitalleştiren firmalar','price'=>'Teklif alınır','features'=>['İhtiyaca özel yazılım','CRM / panel geliştirme','Entegrasyonlar','Sürekli geliştirme'],'featured'=>false],
];}
function mega_menu_groups(){ return [
 ['title'=>'Web Hizmetleri','items'=>['Web Tasarım','Kurumsal Web Sitesi','Tek Sayfa Web Sitesi','Randevulu Web Sitesi']],
 ['title'=>'Grafik / Marka','items'=>['Logo Yönlendirme','Kurumsal Kimlik','Katalog Tasarımı','Sosyal Medya Görselleri']],
 ['title'=>'Dijital Pazarlama','items'=>['SEO Danışmanlığı','Google Ads','Sosyal Medya Yönetimi','Dönüşüm Odaklı Landing']],
 ['title'=>'Teknik Destek / Bakım','items'=>['Site Bakımı','Hız Optimizasyonu','Güvenlik & Yedekleme','Domain / Hosting']],
 ['title'=>'E-Ticaret / Özel Yazılım','items'=>['E-Ticaret Danışmanlığı','Yönetim Paneli','CRM Çözümleri','Özel Web Yazılım']],
];}
function trust_badges(){ return ['Hızlı teslim','Mobil uyumlu','SEO temeli','WhatsApp odaklı dönüşüm','Yönetim paneli opsiyonu']; }
function meta_for($page){$m=[
 'home'=>['title'=>'Xtanbul Yazılım Agent | Uygun Fiyata SEO Uyumlu Web Sitesi','desc'=>'Tek sayfa, randevulu, çok sayfalı ve yönetim panelli SEO uyumlu web sitesi. Küçük işletmeler için hızlı, modern ve uygun fiyatlı dijital çözümler.'],
 'hizmetler'=>['title'=>'Web Tasarım, SEO, Reklam ve E-Ticaret Danışmanlığı | Xtanbul','desc'=>'Web site tasarımı, SEO, Google Ads, sosyal medya, e-ticaret danışmanlığı, randevulu web sitesi ve yönetim paneli hizmetleri.'],
 'paketler'=>['title'=>'Web Sitesi Paketleri | 4.999 TL’den Başlayan Fiyatlar','desc'=>'Tek sayfa HTML site, randevulu web sitesi, çok sayfalı site ve yönetim panelli web sitesi paketleri.'],
 'referanslar'=>['title'=>'Referanslarımız | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent referansları, web sitesi çalışmaları, aktif proje ve müşteri portföyü.'],
 'blog'=>['title'=>'Web Sitesi ve SEO Blog | Xtanbul Yazılım Agent','desc'=>'Uygun fiyatlı web sitesi, SEO, randevu sistemi, Google görünürlüğü ve dijital pazarlama yazıları.'],
 'haberler'=>['title'=>'Haberler | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent duyuruları, kampanyaları ve dijital hizmet haberleri.'],
 'kurumsal'=>['title'=>'Kurumsal | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent; küçük ve orta ölçekli işletmeleri dijitale taşıyan web tasarım ve yazılım ajansı.'],
 'iletisim'=>['title'=>'İletişim & Teklif | Xtanbul Yazılım Agent','desc'=>'Uygun fiyatlı web sitesi yaptırmak için Xtanbul Yazılım Agent ile iletişime geçin, hemen teklif alın.'],
 'kvkk'=>['title'=>'KVKK Aydınlatma Metni | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent KVKK aydınlatma metni ve kişisel verilerin işlenmesi hakkında bilgilendirme.'],
 'gizlilik'=>['title'=>'Gizlilik Politikası | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent gizlilik politikası.'],
 'cerez'=>['title'=>'Çerez Politikası | Xtanbul Yazılım Agent','desc'=>'Xtanbul Yazılım Agent çerez politikası.'],
]; return $m[$page]??$m['home'];}
function nav_active($page,$key){return $page===$key?' class="active"':'';}
function mega_menu_html(){
    $html = '<div class="mega-panel" role="menu"><div class="mega-inner">';
    foreach(mega_menu_groups() as $g){
        $html .= '<div class="mega-col"><h4>'.e($g['title']).'</h4><ul>';
        foreach($g['items'] as $it){ $html .= '<li><a href="/hizmetler">'.e($it).'</a></li>'; }
        $html .= '</ul></div>';
    }
    $html .= '<div class="mega-col mega-cta"><h4>Ne yapmak istiyorsunuz?</h4><p>Size en uygun paketi birlikte belirleyelim, aynı gün teklif çıkaralım.</p><a class="btn primary sm" href="/iletisim#teklif">Ücretsiz Teklif Al</a><a class="btn ghost sm" href="/paketler">Paketleri İncele</a></div>';
    $html .= '</div></div>';
    return $html;
}
function header_html($page='home',$custom=null){
    $s=site_settings(); $meta=$custom?:meta_for($page); $url=base_url().($_SERVER['REQUEST_URI']??'/'); $wa=normalize_whatsapp($s['contact_whatsapp']);
    $navItems=[['hizmetler','Hizmetler'],['paketler','Paketler'],['referanslar','Referanslar'],['blog','Blog / Rehber'],['kurumsal','Kurumsal'],['iletisim','İletişim']];
    echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($meta['title']).'</title><meta name="description" content="'.e($meta['desc']).'"><link rel="canonical" href="'.e($url).'"><meta property="og:title" content="'.e($meta['title']).'"><meta property="og:description" content="'.e($meta['desc']).'"><meta property="og:type" content="website"><meta name="theme-color" content="#ffffff"><link rel="icon" href="'.e($s['favicon_path']).'"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap"><link rel="stylesheet" href="/assets/style.css?v=8"><script defer src="/assets/site.js?v=8"></script><script type="application/ld+json">'.json_encode(['@context'=>'https://schema.org','@type'=>'ProfessionalService','name'=>$s['company_name'],'url'=>base_url(),'telephone'=>$s['contact_phone'],'email'=>$s['contact_email'],'address'=>$s['contact_address'],'areaServed'=>'Türkiye','priceRange'=>'₺₺','serviceType'=>['Web sitesi tasarımı','SEO','Google Ads danışmanlığı','Sosyal medya danışmanlığı','E-ticaret danışmanlığı']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script></head><body>';
    echo '<header class="site-header" id="siteHeader"><div class="header-inner"><a class="brand" href="/"><img src="'.e($s['logo_path']).'" alt="'.e($s['company_name']).' logo"><span class="brand-name">Xtanbul<em>Yazılım</em></span></a>';
    echo '<nav class="main-nav" id="mainNav" aria-label="Ana menü"><ul>';
    echo '<li class="has-mega"><button type="button" class="nav-link mega-trigger'.($page==='hizmetler'?' active':'').'" aria-expanded="false" aria-haspopup="true">Hizmetler<i class="caret">▾</i></button>'.mega_menu_html().'</li>';
    foreach($navItems as $it){ if($it[0]==='hizmetler') continue; echo '<li><a class="nav-link'.($page===$it[0]?' active':'').'" href="/'.$it[0].'">'.e($it[1]).'</a></li>'; }
    echo '<li class="nav-mobile-cta"><a class="btn primary full" href="/iletisim#teklif">Teklif Al</a></li>';
    echo '</ul></nav>';
    echo '<div class="header-cta"><a class="phone-link" href="tel:'.e(preg_replace('/\s+/','',$s['contact_phone'])).'"><span>Hemen ara</span><b>'.e($s['contact_phone']).'</b></a><a class="btn primary" href="/iletisim#teklif">Teklif Al</a><button type="button" class="hamburger" id="navToggle" aria-label="Menüyü aç/kapat" aria-expanded="false"><span></span><span></span><span></span></button></div>';
    echo '</div></header><div class="nav-overlay" id="navOverlay" hidden></div>';
    echo '<a class="whatsapp-float" aria-label="WhatsApp\'tan teklif al" href="https://wa.me/'.$wa.'?text=Merhaba%2C%20web%20sitesi%20teklifi%20almak%20istiyorum." target="_blank" rel="noopener"><svg class="wa-icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path class="wa-bubble" d="M16.03 3.2C9.1 3.2 3.47 8.78 3.47 15.64c0 2.34.66 4.54 1.8 6.42L3.2 28.8l6.95-2.02a12.7 12.7 0 0 0 5.88 1.47c6.94 0 12.57-5.58 12.57-12.45S22.97 3.2 16.03 3.2Z"/><path class="wa-phone" d="M22.98 19.24c-.38 1.08-1.9 1.98-2.74 2.1-.73.1-1.68.15-2.7-.17-.62-.2-1.42-.46-2.44-.9-4.3-1.85-7.1-6.12-7.31-6.4-.21-.28-1.75-2.32-1.75-4.43s1.1-3.15 1.48-3.58c.38-.43.84-.54 1.12-.54h.8c.25.01.6-.09.93.7.36.86 1.22 2.97 1.33 3.18.1.22.17.48.03.76-.13.28-.2.45-.4.7-.2.25-.43.56-.62.75-.2.2-.4.42-.17.85.23.43 1.03 1.7 2.22 2.76 1.53 1.36 2.81 1.78 3.24 1.99.43.21.68.18.93-.11.25-.3 1.07-1.25 1.36-1.68.29-.43.58-.36.98-.21.4.14 2.53 1.2 2.96 1.41.43.22.72.33.83.51.1.18.1 1.07-.28 2.15Z"/></svg></a><main id="main">';
}
function footer_html(){ $s=site_settings(); $wa=normalize_whatsapp($s['contact_whatsapp']); $year=date('Y');
    echo '</main><footer class="site-footer"><div class="footer-top"><div class="footer-brand"><a class="brand" href="/"><img src="'.e($s['logo_path']).'" alt="'.e($s['company_name']).' logo"><span class="brand-name">Xtanbul<em>Yazılım</em></span></a><p>İşletmenizi internette güven veren, müşteri kazandıran ve WhatsApp’a dönüşüm taşıyan profesyonel web siteleriyle büyütüyoruz.</p><div class="footer-social"><a href="'.e($s['instagram']?:'#').'" aria-label="Instagram" target="_blank" rel="noopener">Instagram</a><a href="'.e($s['linkedin']?:'#').'" aria-label="LinkedIn" target="_blank" rel="noopener">LinkedIn</a><a href="https://wa.me/'.$wa.'" aria-label="WhatsApp" target="_blank" rel="noopener">WhatsApp</a></div></div>';
    echo '<div class="footer-col"><h4>Hizmetler</h4><a href="/hizmetler">Web Tasarım</a><a href="/hizmetler">Kurumsal Site</a><a href="/hizmetler">Randevulu Site</a><a href="/hizmetler">E-Ticaret Danışmanlığı</a><a href="/hizmetler">SEO Kurulumu</a></div>';
    echo '<div class="footer-col"><h4>Kurumsal</h4><a href="/kurumsal">Hakkımızda</a><a href="/paketler">Paketler</a><a href="/referanslar">Referanslar</a><a href="/blog">Blog / Rehber</a><a href="/iletisim">İletişim</a></div>';
    echo '<div class="footer-col"><h4>İletişim</h4><a href="tel:'.e(preg_replace('/\s+/','',$s['contact_phone'])).'">'.e($s['contact_phone']).'</a><a href="mailto:'.e($s['contact_email']).'">'.e($s['contact_email']).'</a><a href="'.e($s['maps_url']).'" target="_blank" rel="noopener">Haritada Aç</a><small>'.e($s['contact_address']).'</small></div>';
    echo '</div><div class="footer-bottom"><span>© '.$year.' '.e($s['company_name']).' — Tüm hakları saklıdır.</span><div class="footer-legal"><a href="/kvkk">KVKK</a><a href="/gizlilik">Gizlilik</a><a href="/cerez">Çerez</a></div></div></footer></body></html>';
}
function require_admin(){session_start(); if(empty($_SESSION['admin'])){header('Location: admin.php'); exit;}}
?>
