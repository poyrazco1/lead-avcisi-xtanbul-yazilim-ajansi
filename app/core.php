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
 ['icon'=>'🖥️','slug'=>'web-tasarim','title'=>'Web Tasarım','desc'=>'Mobil uyumlu, hızlı açılan, satış odaklı modern web sitesi tasarımı.','long'=>'İşletmenizin dijitaldeki ilk izlenimini belirleyen web sitesini; hızlı açılan, mobilde kusursuz görünen ve ziyaretçiyi müşteriye çeviren bir yapıda tasarlıyoruz. Tasarımı marka dilinize göre kurgular, gereksiz karmaşadan uzak sade ve güven veren bir arayüz oluştururuz.','points'=>['Mobil öncelikli, hızlı açılan tasarım','Marka diline uygun sade arayüz','WhatsApp ve arama odaklı dönüşüm','SEO uyumlu temiz kod yapısı'],
  'scope'=>[['t'=>'Arayüz tasarımı','d'=>'Marka diline uygun, sade ve güven veren modern bir arayüz.'],['t'=>'Mobil uyum','d'=>'Telefon ve tablette taşmayan, hızlı açılan responsive yapı.'],['t'=>'Dönüşüm kurgusu','d'=>'WhatsApp ve arama butonlarıyla ziyaretçiyi talebe yönlendirme.'],['t'=>'İçerik yerleşimi','d'=>'Hizmet, referans ve iletişim akışının net planlanması.'],['t'=>'Temel SEO','d'=>'Başlık, meta ve hız temeliyle aramaya uygun altyapı.'],['t'=>'Yayın & teslim','d'=>'Domain/hosting kurulumu ve sorunsuz yayına alma.']],
  'audience'=>[['t'=>'Yeni açılan işletmeler','d'=>'İlk profesyonel web sitesine hızlı ve güvenilir bir başlangıç.'],['t'=>'Sitesini yenileyenler','d'=>'Eski, mobil uyumsuz sitesini modern bir yapıya taşımak isteyenler.'],['t'=>'Yerel hizmet işletmeleri','d'=>'Telefon ve WhatsApp’tan müşteri almak isteyen işletmeler.']],
  'faq'=>[['q'=>'Web tasarım süreci ne kadar sürer?','a'=>'İçerik hazır olduğunda çoğu proje birkaç gün ile birkaç hafta arasında yayına alınır; süre kapsamla birlikte netleşir.'],['q'=>'Tasarımı beğenmezsem revize var mı?','a'=>'Evet. Taslak aşamasında geri bildirimlerinize göre revize eder, yön onaylanmadan koda geçmeyiz.'],['q'=>'Site mobilde de iyi görünecek mi?','a'=>'Tüm tasarımları mobil öncelikli kurgular, telefon ve tablette test ederek teslim ederiz.'],['q'=>'İçerik ve görselleri siz mi hazırlıyorsunuz?','a'=>'Metin ve görsel yönlendirmesinde destek olur, elinizdeki içerikleri de düzenleyip kullanırız.'],['q'=>'Yayın sonrası değişiklik yapabilir miyim?','a'=>'Panelli paketlerde kendiniz düzenlersiniz; diğer paketlerde bakım desteğiyle güncelleriz.']]],
 ['icon'=>'🏢','slug'=>'kurumsal-web-sitesi','title'=>'Kurumsal Web Sitesi','desc'=>'Çok sayfalı, güven veren, marka dilinize uygun kurumsal siteler.','long'=>'Kurumsal duruşunuzu yansıtan; hizmet, referans, blog ve iletişim sayfalarıyla eksiksiz çok sayfalı web siteleri geliştiriyoruz. İçerik akışını ziyaretçiyi ikna edecek şekilde planlar, marka güvenini öne çıkarırız.','points'=>['Hizmet, referans ve blog sayfaları','Kurumsal içerik ve akış planı','Çok dil opsiyonu','Yönetim paneli entegrasyonu'],
  'scope'=>[['t'=>'Çok sayfa mimarisi','d'=>'Hizmet, hakkımızda, referans ve iletişim sayfalarının kurgusu.'],['t'=>'Kurumsal içerik','d'=>'Markanızı anlatan, ikna eden içerik yerleşimi ve düzeni.'],['t'=>'Referans & blog','d'=>'Çalışmalarınızı ve rehber içerikleri yayınlayacak bölümler.'],['t'=>'Marka görünürlüğü','d'=>'Logo, renk ve tipografiyle tutarlı kurumsal görünüm.'],['t'=>'Çok dil opsiyonu','d'=>'İhtiyaç halinde birden fazla dilde yayın altyapısı.'],['t'=>'Panel entegrasyonu','d'=>'İçeriği kendiniz yönetmeniz için opsiyonel yönetim paneli.']],
  'audience'=>[['t'=>'Kurumsal markalar','d'=>'Ciddi ve güven veren bir dijital duruş isteyen firmalar.'],['t'=>'Büyüyen KOBİ’ler','d'=>'Hizmet ve referanslarını düzenli sunmak isteyen işletmeler.'],['t'=>'B2B firmalar','d'=>'Bayi, tedarikçi ve iş ortaklarına hitap eden markalar.']],
  'faq'=>[['q'=>'Kaç sayfa yapılıyor?','a'=>'Sayfa sayısı ihtiyacınıza göre belirlenir; hizmet, referans, blog ve iletişim gibi bölümler standart olarak planlanır.'],['q'=>'Blog altyapısı dahil mi?','a'=>'Evet, kurumsal pakette blog/haber bölümü kurulabilir ve panelden yönetilebilir.'],['q'=>'Çok dilli site yapabiliyor musunuz?','a'=>'Evet, ihtiyaç halinde birden fazla dilde yayın için altyapı kuruyoruz.'],['q'=>'Mevcut kurumsal kimliğime uyar mı?','a'=>'Logo, renk ve tipografinizi esas alır; kurumsal kimliğinizle tutarlı bir tasarım hazırlarız.'],['q'=>'İçeriği sonradan güncelleyebilir miyim?','a'=>'Yönetim paneli eklendiğinde içerikleri kod bilmeden kendiniz güncelleyebilirsiniz.']]],
 ['icon'=>'📄','slug'=>'tek-sayfa-web-sitesi','title'=>'Tek Sayfa Web Sitesi','desc'=>'İşletmenizi tek ekranda anlatan, WhatsApp’a dönüştüren landing page.','long'=>'Hizmetini hızlıca anlatmak ve telefon/WhatsApp üzerinden müşteri almak isteyen yerel işletmeler için tek sayfa siteler kuruyoruz. Hızlı yayına alınır, düşük maliyetlidir ve dönüşüme odaklanır.','points'=>['Tek ekranda net hizmet anlatımı','WhatsApp & arama butonu','Harita ve çalışma saatleri','Hızlı yayına alma'],
  'scope'=>[['t'=>'Tek ekran akışı','d'=>'İşletmenizi baştan sona anlatan, akıcı tek sayfa kurgusu.'],['t'=>'Hizmet özeti','d'=>'Sunduğunuz hizmetlerin net ve kısa şekilde tanıtımı.'],['t'=>'WhatsApp & arama','d'=>'Tek dokunuşla iletişim için belirgin dönüşüm butonları.'],['t'=>'Harita & saatler','d'=>'Konum, çalışma saatleri ve iletişim bilgileri.'],['t'=>'Hızlı yayın','d'=>'Kısa sürede hazırlanıp yayına alınan hafif yapı.'],['t'=>'Temel SEO','d'=>'Yerel aramalarda bulunmanız için temel SEO düzeni.']],
  'audience'=>[['t'=>'Yerel esnaf','d'=>'Hızlı bir dijital vitrin isteyen mahalle ve bölge işletmeleri.'],['t'=>'Yeni başlayanlar','d'=>'Düşük bütçeyle profesyonel bir başlangıç yapmak isteyenler.'],['t'=>'Tek hizmet sunanlar','d'=>'Tek bir hizmet veya ürünü net biçimde anlatmak isteyenler.']],
  'faq'=>[['q'=>'Tek sayfa site bana yeter mi?','a'=>'Hizmetini hızlı anlatmak ve telefon/WhatsApp’tan müşteri almak isteyen işletmeler için genellikle yeterlidir.'],['q'=>'Sonradan çok sayfalı yapıya geçebilir miyim?','a'=>'Evet, ihtiyaç büyüdüğünde çok sayfalı veya panelli yapıya taşıyabiliriz.'],['q'=>'Ne kadar sürede hazır olur?','a'=>'İçerik hazırsa tek sayfa siteler genellikle birkaç gün içinde yayına alınır.'],['q'=>'Google’da çıkar mı?','a'=>'Temel SEO kurulumuyla özellikle yerel aramalarda bulunabilir hale gelir.'],['q'=>'Harita ve WhatsApp ekleniyor mu?','a'=>'Evet, Google Harita bağlantısı, arama ve WhatsApp butonları standart olarak eklenir.']]],
 ['icon'=>'📅','slug'=>'randevulu-web-sitesi','title'=>'Randevulu Web Sitesi','desc'=>'Ziyaretçiyi randevu/talep formuyla doğrudan müşteriye çeviren yapı.','long'=>'Güzellik, sağlık, servis gibi randevu ile çalışan işletmeler için; ziyaretçiyi form üzerinden doğrudan talebe yönlendiren siteler geliştiriyoruz. Gelen talepler size anında ulaşır.','points'=>['Randevu / talep formu','Hizmet alanları ve fiyat bilgisi','KVKK uyumlu form akışı','WhatsApp ile takip'],
  'scope'=>[['t'=>'Randevu / talep formu','d'=>'Ziyaretçiyi doğrudan talebe yönlendiren net form akışı.'],['t'=>'Hizmet & fiyat','d'=>'Sunduğunuz hizmetlerin ve varsa fiyat bilgisinin sunumu.'],['t'=>'KVKK uyumlu akış','d'=>'Onay kutusu ve aydınlatma metniyle uyumlu form yapısı.'],['t'=>'Bildirim akışı','d'=>'Gelen taleplerin size hızlıca ulaşması için kurgu.'],['t'=>'WhatsApp takip','d'=>'Talep sonrası tek tıkla WhatsApp üzerinden iletişim.'],['t'=>'Mobil uyum','d'=>'Telefonda kolay doldurulan, taşmayan form tasarımı.']],
  'audience'=>[['t'=>'Güzellik & bakım','d'=>'Kuaför, güzellik salonu ve bakım merkezleri.'],['t'=>'Sağlık & klinik','d'=>'Diş, estetik ve poliklinik gibi randevulu sağlık hizmetleri.'],['t'=>'Servis & atölye','d'=>'Teknik servis, oto ve tamir gibi talep toplayan işletmeler.']],
  'faq'=>[['q'=>'Randevu talepleri bana nasıl ulaşıyor?','a'=>'Form gönderildiğinde talepler tarafınıza iletilir; WhatsApp ve telefonla da takip edebilirsiniz.'],['q'=>'Takvim entegrasyonu yapılabilir mi?','a'=>'İhtiyaca göre basit talep formundan gelişmiş randevu akışına kadar farklı seçenekler sunabiliriz.'],['q'=>'Form alanlarını özelleştirebilir miyiz?','a'=>'Evet, sektörünüze uygun alanları (hizmet, tarih, not vb.) birlikte belirleriz.'],['q'=>'KVKK onayı ekleniyor mu?','a'=>'Evet, formlara KVKK onay kutusu ve aydınlatma metni bağlantısı eklenir.'],['q'=>'Talep sonrası müşteriyle nasıl iletişim kuruyorum?','a'=>'Gelen bilgilerle telefon veya WhatsApp üzerinden hızlıca dönüş yapabilirsiniz.']]],
 ['icon'=>'⚙️','slug'=>'yonetim-panelli-site','title'=>'Yönetim Panelli Site','desc'=>'İçerik, blog, referans ve siparişleri kendiniz yönetin.','long'=>'İçeriğini kendisi güncellemek isteyen işletmeler için yönetim panelli siteler kuruyoruz. Blog, haber, referans ve talepleri kod bilmeden kendi panelinizden yönetirsiniz.','points'=>['Kolay içerik yönetim paneli','Blog / haber / referans ekleme','Talep ve sipariş takibi','Yetkili kullanıcı yönetimi'],
  'scope'=>[['t'=>'İçerik paneli','d'=>'Sayfa ve bölümleri kod bilmeden düzenleyebileceğiniz panel.'],['t'=>'Blog / haber / referans','d'=>'İçerik ekleme, düzenleme ve yayından kaldırma.'],['t'=>'Talep & sipariş takibi','d'=>'Gelen talep ve siparişleri tek yerden görüntüleme.'],['t'=>'Kullanıcı yetkisi','d'=>'Ekip için farklı yetki seviyeleriyle güvenli erişim.'],['t'=>'Medya yükleme','d'=>'Görsel ve dosyaları panelden kolayca yükleme.'],['t'=>'Yedekleme','d'=>'İçeriğin güvende kalması için yedekleme yaklaşımı.']],
  'audience'=>[['t'=>'İçerik üreten işletmeler','d'=>'Blog ve duyurularını düzenli paylaşmak isteyenler.'],['t'=>'Ekiple yönetenler','d'=>'Birden fazla kullanıcının içerik girmesi gereken markalar.'],['t'=>'Sık güncelleyenler','d'=>'Kampanya, fiyat ve içerikleri sık değiştiren işletmeler.']],
  'faq'=>[['q'=>'Paneli kullanmak için kod bilmem gerekir mi?','a'=>'Hayır. Panel; içerik ekleme ve düzenlemeyi teknik bilgi gerektirmeyecek şekilde tasarlanır.'],['q'=>'Kaç kullanıcı ekleyebilirim?','a'=>'İhtiyacınıza göre birden fazla yetkili kullanıcı tanımlanabilir.'],['q'=>'Panel kullanımı için eğitim veriyor musunuz?','a'=>'Evet, teslimde kısa bir kullanım anlatımı ve gerektiğinde destek sağlıyoruz.'],['q'=>'Panel güvenli mi?','a'=>'Girişler korumalı yapılır; yetki seviyeleri ve temel güvenlik önlemleri uygulanır.'],['q'=>'Mobilden de yönetebilir miyim?','a'=>'Panel mobil uyumlu çalışır; telefon veya tabletten de içerik güncelleyebilirsiniz.']]],
 ['icon'=>'🛒','slug'=>'e-ticaret-danismanligi','title'=>'E-Ticaret Danışmanlığı','desc'=>'Kategori, ürün ve kampanya kurgusuyla satışa hazır altyapı.','long'=>'Online satışa geçmek isteyen markalar için kategori yapısı, ürün sayfası düzeni ve kampanya kurgusuyla satışa hazır bir altyapı planlıyoruz. Dönüşümü artıracak akışı birlikte kuruyoruz.','points'=>['Kategori ve ürün yapısı','Ürün sayfası düzeni','Kampanya ve dönüşüm akışı','Pazaryeri hazırlığı'],
  'scope'=>[['t'=>'Kategori yapısı','d'=>'Ürünlerin kolay bulunmasını sağlayan düzenli kategori kurgusu.'],['t'=>'Ürün sayfası','d'=>'Görsel, açıklama ve butonlarla dönüşüm odaklı ürün düzeni.'],['t'=>'Sepet & ödeme akışı','d'=>'Ziyaretçiyi zorlamadan tamamlanan satın alma yolu.'],['t'=>'Kampanya kurgusu','d'=>'İndirim ve kampanyaların doğru sunulması.'],['t'=>'Pazaryeri hazırlığı','d'=>'Pazaryeri kanallarına uygun içerik ve yapı planı.'],['t'=>'Dönüşüm analizi','d'=>'Satışı artıracak noktaların belirlenmesi ve iyileştirilmesi.']],
  'audience'=>[['t'=>'Online satışa geçenler','d'=>'İlk kez internetten satış yapmak isteyen markalar.'],['t'=>'Mağazasını büyütenler','d'=>'Mevcut satışını artırmak isteyen e-ticaret sahipleri.'],['t'=>'Pazaryeri satıcıları','d'=>'Kendi sitesiyle pazaryeri satışını güçlendirmek isteyenler.']],
  'faq'=>[['q'=>'Hangi altyapıyı öneriyorsunuz?','a'=>'İhtiyaç, ürün sayısı ve bütçeye göre size en uygun altyapıyı birlikte belirleriz.'],['q'=>'Kaç ürün ekleyebilirim?','a'=>'Ürün sayısı seçilen altyapıya göre değişir; büyümeye uygun bir yapı planlarız.'],['q'=>'Ödeme ve kargo entegrasyonu var mı?','a'=>'Ödeme ve kargo süreçlerinin kurgusu danışmanlık kapsamında planlanır ve yönlendirilir.'],['q'=>'Pazaryerlerine de uygun mu?','a'=>'Evet, ürün içeriklerini pazaryeri kanallarına da uyumlu olacak şekilde planlarız.'],['q'=>'Satışlarımı nasıl artırırım?','a'=>'Ürün sayfası düzeni, kampanya kurgusu ve dönüşüm noktalarında iyileştirmeler öneririz.']]],
 ['icon'=>'🔍','slug'=>'seo-temel-kurulum','title'=>'SEO Temel Kurulum','desc'=>'Teknik SEO, başlık/meta düzeni ve Google görünürlük temeli.','long'=>'Sitenizin Google’da bulunabilmesi için teknik SEO temelini kuruyoruz. Başlık ve meta düzeni, site hızı, mobil uyum ve içerik yapısını arama motorlarına uygun hale getiriyoruz.','points'=>['Teknik SEO kurulumu','Başlık ve meta düzeni','Site hızı ve mobil uyum','Google araçları entegrasyonu'],
  'scope'=>[['t'=>'Teknik SEO','d'=>'Arama motorlarının siteyi doğru okumasını sağlayan altyapı.'],['t'=>'Başlık & meta','d'=>'Sayfa başlıkları ve açıklamalarının düzenlenmesi.'],['t'=>'Site hızı','d'=>'Sayfaların hızlı açılması için temel optimizasyon.'],['t'=>'Mobil uyum','d'=>'Mobil deneyimin arama sıralamasına uygun hale getirilmesi.'],['t'=>'Sitemap & robots','d'=>'Site haritası ve tarama yönergelerinin kurulması.'],['t'=>'Search Console & Analytics','d'=>'Google araçlarının bağlanması ve takip kurulumu.']],
  'audience'=>[['t'=>'Google’da görünmek isteyenler','d'=>'Aramalarda bulunmak isteyen tüm işletmeler.'],['t'=>'Yeni site sahipleri','d'=>'Yeni yayına alınan sitesini doğru temele oturtmak isteyenler.'],['t'=>'İçerik üretenler','d'=>'Blog ve rehber içerikleriyle organik trafik hedefleyenler.']],
  'faq'=>[['q'=>'SEO ile ne kadar sürede sonuç alırım?','a'=>'SEO orta-uzun vadeli bir çalışmadır; temel kurulum sonrası ilerleme genellikle haftalar içinde görülmeye başlar.'],['q'=>'İlk sırada çıkmayı garanti ediyor musunuz?','a'=>'Hiçbir kurumsal ajans sıralama garantisi vermez; biz doğru ve kalıcı temeli kurarız.'],['q'=>'İçerik yazımı dahil mi?','a'=>'Temel kurulum teknik tarafı kapsar; içerik desteği ihtiyaca göre ayrıca planlanabilir.'],['q'=>'Rapor veriyor musunuz?','a'=>'Google araçları bağlanır; ilerlemeyi takip edebilmeniz için yönlendirme yaparız.'],['q'=>'Mevcut siteme de uygulanır mı?','a'=>'Evet, yayında olan sitenizin teknik SEO temelini de gözden geçirip iyileştirebiliriz.']]],
 ['icon'=>'📣','slug'=>'reklam-sosyal-medya','title'=>'Google Ads / Sosyal Medya','desc'=>'Doğru hedefleme ile reklam ve sosyal medya danışmanlığı.','long'=>'Doğru kitleye ulaşmak için Google Ads ve sosyal medya reklam danışmanlığı veriyoruz. Bütçenizi verimli kullanacak hedefleme ve içerik yönünü birlikte belirliyoruz.','points'=>['Google Ads danışmanlığı','Sosyal medya reklam kurgusu','Hedefleme ve bütçe planı','Dönüşüm takibi'],
  'scope'=>[['t'=>'Hedef kitle','d'=>'Reklamların doğru kişilere ulaşması için kitle tanımı.'],['t'=>'Google Ads kurulumu','d'=>'Arama ve görüntülü reklam kampanyalarının kurgusu.'],['t'=>'Sosyal medya reklamı','d'=>'Instagram ve Facebook reklamlarının planlanması.'],['t'=>'Metin & görsel yönü','d'=>'Dikkat çeken reklam metni ve görsel yönlendirmesi.'],['t'=>'Bütçe planı','d'=>'Bütçenin verimli dağıtılması ve yönetimi.'],['t'=>'Dönüşüm takibi','d'=>'Reklam performansının ölçülmesi ve iyileştirilmesi.']],
  'audience'=>[['t'=>'Hızlı talep isteyenler','d'=>'Kısa sürede daha fazla arama ve mesaj almak isteyenler.'],['t'=>'Yeni tanıtım yapanlar','d'=>'Yeni ürün, hizmet veya şube tanıtan işletmeler.'],['t'=>'Bölgesel hedefleyenler','d'=>'Belirli şehir veya bölgede müşteri arayan işletmeler.']],
  'faq'=>[['q'=>'Reklam bütçesi ne kadar olmalı?','a'=>'Sektör ve hedefe göre değişir; bütçeyi verimli kullanacak bir plan birlikte belirleriz.'],['q'=>'Reklam bütçesi hizmete dahil mi?','a'=>'Hayır, reklam bütçesi platformlara ödenir; biz kurgu, yönetim ve danışmanlığı sağlarız.'],['q'=>'Hangi platformları öneriyorsunuz?','a'=>'İşletmenize göre Google Ads, Instagram ve Facebook arasında doğru kanalları öneririz.'],['q'=>'Ne zaman sonuç görürüm?','a'=>'Reklamlar yayına alındıktan sonra ilk sonuçlar genellikle kısa sürede görülmeye başlar.'],['q'=>'Performansı nasıl takip ediyoruz?','a'=>'Dönüşüm takibi kurar, hangi reklamın işe yaradığını ölçüp bütçeyi ona göre yönlendiririz.']]],
];}
function front_service_find($slug){ foreach(front_services() as $sv){ if(($sv['slug']??'')===$slug) return $sv; } return null; }
/* Hizmet detay landing — ortak (tüm hizmet sayfalarında aynı) bölümler */
function service_hero_cards(){ return [
 ['m'=>'⏱','t'=>'Teslim süreci','d'=>'Kapsam netleştikten sonra planlı ve zamanında teslim.'],
 ['m'=>'🤝','t'=>'Teslim sonrası destek','d'=>'Yayın sonrası bakım, güncelleme ve yönlendirme.'],
 ['m'=>'◎','t'=>'Uygulama alanı','d'=>'Yerel işletmeden kurumsal markaya kadar esnek çözüm.'],
 ['m'=>'✎','t'=>'Ücretsiz danışmanlık','d'=>'İhtiyacınıza uygun net teklif için aynı gün dönüş.'],
];}
function service_process(){ return [
 ['no'=>'01','title'=>'Keşif','desc'=>'İhtiyacınızı, hedefinizi ve rakiplerinizi dinleyip kapsamı netleştiriyoruz.'],
 ['no'=>'02','title'=>'Planlama','desc'=>'Yapı, içerik akışı ve tasarım yönünü birlikte planlıyoruz.'],
 ['no'=>'03','title'=>'Uygulama','desc'=>'Mobil uyumlu, hızlı ve SEO temelli olarak hayata geçiriyoruz.'],
 ['no'=>'04','title'=>'Teslim / Takip','desc'=>'Yayına alıp teslim ediyor, sonrasında performansı takip ediyoruz.'],
];}
function service_packages(){ return [
 ['name'=>'Başlangıç','who'=>'Hızlı ve net bir başlangıç isteyenler için.','features'=>['Temel kapsam ve kurulum','Mobil uyumlu yapı','WhatsApp & arama yönlendirmesi','Temel SEO','Hızlı yayına alma'],'featured'=>false],
 ['name'=>'Standart','who'=>'Daha fazla içerik ve kapsam isteyenler için.','features'=>['Genişletilmiş kapsam','Çok bölümlü yapı','İçerik ve akış planı','Gelişmiş SEO düzeni','Öncelikli destek'],'featured'=>true],
 ['name'=>'Premium','who'=>'Yönetim ve büyüme odaklı markalar için.','features'=>['Tam kapsam','Yönetim paneli','Randevu / talep sistemi','Entegrasyon opsiyonları','Sürekli geliştirme'],'featured'=>false],
];}
function service_gallery(){ return [
 ['cat'=>'Masaüstü','title'=>'Masaüstü görünüm'],
 ['cat'=>'Mobil','title'=>'Mobil görünüm'],
 ['cat'=>'Yönetim','title'=>'Yönetim paneli'],
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
function faq_items(){ return [
 ['q'=>'Bir web sitesi ne kadar sürede yayına alınır?','a'=>'Tek sayfa siteler içerik hazır olduğunda genellikle birkaç gün içinde yayına alınır. Çok sayfalı kurumsal ve yönetim panelli projelerde süre kapsamla birlikte teklif aşamasında netleştirilir.'],
 ['q'=>'En düşük paket fiyatı neyi kapsıyor?','a'=>'Başlangıç fiyatı mobil uyumlu tek sayfa site içindir; WhatsApp ve arama butonu, temel SEO kurulumu, harita ve iletişim alanlarını kapsar. Randevu, çok sayfa, panel ve çok dil ihtiyaca göre ayrıca tekliflendirilir.'],
 ['q'=>'Sitemi kendim güncelleyebilir miyim?','a'=>'Evet. Yönetim panelli paketlerde blog, haber, referans ve içeriklerinizi kod bilmeden kendi panelinizden düzenleyebilirsiniz.'],
 ['q'=>'Alan adı ve hosting dahil mi?','a'=>'Domain ve hosting kurulumunu sizin adınıza yapıyor, süreç boyunca yönlendiriyoruz. Bu kalemler paketten bağımsız olarak sağlanır ve teklifte şeffaf şekilde belirtilir.'],
 ['q'=>'Teslim sonrası destek veriyor musunuz?','a'=>'Yayına aldıktan sonra da yanınızdayız. Güncelleme, bakım ve performans takibi için sözleşmeli destek sunuyoruz.'],
]; }
/* ===== Ortak ön yüz bileşen renderer'ları ===== */
function breadcrumb_html($crumbs){
 if(!$crumbs) return '';
 $h='<nav class="breadcrumb" aria-label="Sayfa yolu"><ol>'; $n=count($crumbs);
 foreach($crumbs as $i=>$c){
  $last=$i===$n-1;
  if($last || empty($c['url'])) $h.='<li><span aria-current="page">'.e($c['label']).'</span></li>';
  else $h.='<li><a href="'.e($c['url']).'">'.e($c['label']).'</a></li>';
 }
 return $h.'</ol></nav>';
}
function page_hero($o){
 echo '<section class="page-hero"><div class="wrap">';
 if(!empty($o['eyebrow'])) echo '<span class="eyebrow">'.e($o['eyebrow']).'</span>';
 echo '<h1>'.e($o['title']).'</h1>';
 if(!empty($o['desc'])) echo '<p>'.e($o['desc']).'</p>';
 if(!empty($o['actions'])){
  echo '<div class="page-hero-actions">';
  foreach($o['actions'] as $a){
   $cls='btn '.($a['style']??'btn-primary'); $blank=!empty($a['blank'])?' target="_blank" rel="noopener"':'';
   echo '<a class="'.$cls.'" href="'.e($a['href']).'"'.$blank.'>'.e($a['label']).'</a>';
  }
  echo '</div>';
 }
 if(!empty($o['crumbs'])) echo breadcrumb_html($o['crumbs']);
 echo '</div></section>';
}
function final_cta_html($o=[]){
 $s=site_settings(); $wa=normalize_whatsapp($s['contact_whatsapp']);
 $title=$o['title'] ?? $s['cta_title']; $desc=$o['desc'] ?? $s['cta_desc'];
 echo '<section class="cta"><div class="wrap cta-inner"><div><h2 class="cta-title">'.e($title).'</h2><p class="cta-text">'.e($desc).'</p></div>';
 echo '<div class="cta-actions"><a class="btn btn-light btn-lg" href="https://wa.me/'.$wa.'?text=Merhaba%2C%20web%20sitesi%20teklifi%20almak%20istiyorum." target="_blank" rel="noopener">WhatsApp’tan Yaz</a><a class="btn btn-line btn-lg" href="/iletisim#teklif">İletişime Geç</a></div></div></section>';
}
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
    echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($meta['title']).'</title><meta name="description" content="'.e($meta['desc']).'"><link rel="canonical" href="'.e($url).'"><meta property="og:title" content="'.e($meta['title']).'"><meta property="og:description" content="'.e($meta['desc']).'"><meta property="og:type" content="website"><meta name="theme-color" content="#0b0d0a"><link rel="icon" href="'.e($s['favicon_path']).'"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"><link rel="stylesheet" href="/assets/style.css?v=10"><script defer src="/assets/site.js?v=10"></script><script type="application/ld+json">'.json_encode(['@context'=>'https://schema.org','@type'=>'ProfessionalService','name'=>$s['company_name'],'url'=>base_url(),'telephone'=>$s['contact_phone'],'email'=>$s['contact_email'],'address'=>$s['contact_address'],'areaServed'=>'Türkiye','priceRange'=>'₺₺','serviceType'=>['Web sitesi tasarımı','SEO','Google Ads danışmanlığı','Sosyal medya danışmanlığı','E-ticaret danışmanlığı']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script></head><body>';
    echo '<header class="site-header" id="siteHeader"><div class="header-inner"><a class="brand" href="/"><img src="'.e($s['logo_path']).'" alt="'.e($s['company_name']).' logo" width="34" height="34" decoding="async"><span class="brand-name">Xtanbul<em>Yazılım</em></span></a>';
    echo '<nav class="site-nav mobile-menu" id="mainNav" aria-label="Ana menü"><ul>';
    echo '<li class="has-mega"><button type="button" class="nav-link mega-trigger'.($page==='hizmetler'?' active':'').'" aria-expanded="false" aria-haspopup="true">Hizmetler<i class="caret">▾</i></button>'.mega_menu_html().'</li>';
    foreach($navItems as $it){ if($it[0]==='hizmetler') continue; echo '<li><a class="nav-link'.($page===$it[0]?' active':'').'" href="/'.$it[0].'">'.e($it[1]).'</a></li>'; }
    echo '<li class="nav-mobile-cta"><a class="btn btn-primary btn-block" href="/iletisim#teklif">Ücretsiz Teklif Al</a></li>';
    echo '</ul></nav>';
    echo '<div class="header-actions"><a class="btn btn-secondary btn-sm desk-only" href="https://wa.me/'.$wa.'?text=Merhaba%2C%20web%20sitesi%20teklifi%20almak%20istiyorum." target="_blank" rel="noopener">WhatsApp</a><a class="btn btn-primary btn-sm" href="/iletisim#teklif">Teklif Al</a><button type="button" class="hamburger" id="navToggle" aria-label="Menüyü aç/kapat" aria-expanded="false"><span></span><span></span><span></span></button></div>';
    echo '</div></header><div class="nav-overlay" id="navOverlay" hidden></div>';
    echo '<a class="whatsapp-float" aria-label="WhatsApp\'tan teklif al" href="https://wa.me/'.$wa.'?text=Merhaba%2C%20web%20sitesi%20teklifi%20almak%20istiyorum." target="_blank" rel="noopener"><svg class="wa-icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path class="wa-bubble" d="M16.03 3.2C9.1 3.2 3.47 8.78 3.47 15.64c0 2.34.66 4.54 1.8 6.42L3.2 28.8l6.95-2.02a12.7 12.7 0 0 0 5.88 1.47c6.94 0 12.57-5.58 12.57-12.45S22.97 3.2 16.03 3.2Z"/><path class="wa-phone" d="M22.98 19.24c-.38 1.08-1.9 1.98-2.74 2.1-.73.1-1.68.15-2.7-.17-.62-.2-1.42-.46-2.44-.9-4.3-1.85-7.1-6.12-7.31-6.4-.21-.28-1.75-2.32-1.75-4.43s1.1-3.15 1.48-3.58c.38-.43.84-.54 1.12-.54h.8c.25.01.6-.09.93.7.36.86 1.22 2.97 1.33 3.18.1.22.17.48.03.76-.13.28-.2.45-.4.7-.2.25-.43.56-.62.75-.2.2-.4.42-.17.85.23.43 1.03 1.7 2.22 2.76 1.53 1.36 2.81 1.78 3.24 1.99.43.21.68.18.93-.11.25-.3 1.07-1.25 1.36-1.68.29-.43.58-.36.98-.21.4.14 2.53 1.2 2.96 1.41.43.22.72.33.83.51.1.18.1 1.07-.28 2.15Z"/></svg></a><main id="main">';
}
function footer_html(){ $s=site_settings(); $wa=normalize_whatsapp($s['contact_whatsapp']); $year=date('Y');
    $social='<div class="footer-social">';
    if(!empty($s['instagram'])) $social.='<a href="'.e($s['instagram']).'" aria-label="Instagram" target="_blank" rel="noopener">Instagram</a>';
    if(!empty($s['linkedin'])) $social.='<a href="'.e($s['linkedin']).'" aria-label="LinkedIn" target="_blank" rel="noopener">LinkedIn</a>';
    $social.='<a href="https://wa.me/'.$wa.'" aria-label="WhatsApp" target="_blank" rel="noopener">WhatsApp</a></div>';
    echo '</main><footer class="site-footer"><div class="footer-top"><div class="footer-brand"><a class="brand" href="/"><img src="'.e($s['logo_path']).'" alt="'.e($s['company_name']).' logo" width="34" height="34" decoding="async"><span class="brand-name">Xtanbul<em>Yazılım</em></span></a><p>İşletmenizi internette güven veren, müşteri kazandıran ve WhatsApp’a dönüşüm taşıyan profesyonel web siteleriyle büyütüyoruz.</p>'.$social.'</div>';
    echo '<div class="footer-col"><h4>Hizmetler</h4><a href="/hizmetler">Web Tasarım</a><a href="/hizmetler">Kurumsal Site</a><a href="/hizmetler">Randevulu Site</a><a href="/hizmetler">E-Ticaret Danışmanlığı</a><a href="/hizmetler">SEO Kurulumu</a></div>';
    echo '<div class="footer-col"><h4>Kurumsal</h4><a href="/kurumsal">Hakkımızda</a><a href="/paketler">Paketler</a><a href="/referanslar">Referanslar</a><a href="/blog">Blog / Rehber</a><a href="/iletisim">İletişim</a></div>';
    echo '<div class="footer-col"><h4>İletişim</h4><a href="tel:'.e(preg_replace('/\s+/','',$s['contact_phone'])).'">'.e($s['contact_phone']).'</a><a href="mailto:'.e($s['contact_email']).'">'.e($s['contact_email']).'</a><a href="'.e($s['maps_url']).'" target="_blank" rel="noopener">Haritada Aç</a><small>'.e($s['contact_address']).'</small></div>';
    echo '</div><div class="footer-bottom"><span>© '.$year.' '.e($s['company_name']).' — Tüm hakları saklıdır.</span><div class="footer-legal"><a href="/kvkk">KVKK</a><a href="/gizlilik">Gizlilik</a><a href="/cerez">Çerez</a></div></div></footer></body></html>';
}
function require_admin(){session_start(); if(empty($_SESSION['admin'])){header('Location: admin.php'); exit;}}
?>
