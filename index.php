<?php require __DIR__.'/app/core.php';
$page=$_GET['page']??'home';
$valid=['home','hizmetler','paketler','referanslar','blog','haberler','kurumsal','iletisim','kvkk','gizlilik','cerez'];
$notFound=!in_array($page,$valid,true);
$s=site_settings();
$wa=normalize_whatsapp($s['contact_whatsapp']);
if($notFound){ http_response_code(404); header_html('home',['title'=>'Sayfa bulunamadı | '.$s['company_name'],'desc'=>'Aradığınız sayfa bulunamadı. Anasayfaya dönebilir veya bizimle iletişime geçebilirsiniz.']); }
elseif($page==='hizmetler' && ($hq=$_GET['h']??'') && ($svcMeta=front_service_find($hq))){
  $long=$svcMeta['long']; $mdesc=$long;
  if(mb_strlen($long)>158){ $cut=mb_substr($long,0,155); $sp=mb_strrpos($cut,' '); $mdesc=($sp?mb_substr($cut,0,$sp):$cut).'…'; }
  header_html('hizmetler',['title'=>$svcMeta['title'].' | '.$s['company_name'],'desc'=>$mdesc]);
}
else { header_html($page); }
?>
<?php if($notFound): ?>
<section class="section error-section"><div class="wrap error-wrap">
  <span class="eyebrow center">Hata 404</span>
  <h1 class="error-code">Sayfa bulunamadı</h1>
  <p class="error-lead">Aradığınız sayfa taşınmış veya kaldırılmış olabilir. Aşağıdaki bağlantılardan devam edebilirsiniz.</p>
  <div class="hero-actions" style="justify-content:center">
    <a class="btn btn-primary btn-lg" href="/">Anasayfaya Dön</a>
    <a class="btn btn-secondary btn-lg" href="/iletisim#teklif">İletişime Geç</a>
  </div>
</div></section>

<?php elseif($page==='home'):
  /* Anasayfaya özel içerik (yalnızca ön yüz sunumu) */
  $hero_cards=[
    ['m'=>'⚡','t'=>'Hızlı dönüş','d'=>'Talebinize aynı gün içinde net teklifle geri dönüyoruz.'],
    ['m'=>'◆','t'=>'Profesyonel hizmet','d'=>'Tasarımdan yayına kadar tek elden, deneyimli ekiple.'],
    ['m'=>'⌂','t'=>'Yerinde / online destek','d'=>'İster yüz yüze ister uzaktan; süreç boyunca yanınızdayız.'],
    ['m'=>'✓','t'=>'Şeffaf süreç','d'=>'Kapsam, takvim ve fiyat baştan yazılı ve nettir.'],
  ];
  $home_process=[
    ['no'=>'01','title'=>'Keşif','desc'=>'İhtiyacınızı, hedef kitlenizi ve rakiplerinizi dinleyip kapsamı netleştiriyoruz.'],
    ['no'=>'02','title'=>'Planlama','desc'=>'Sayfa yapısı, içerik akışı ve tasarım yönünü birlikte planlıyoruz.'],
    ['no'=>'03','title'=>'Uygulama','desc'=>'Mobil uyumlu, hızlı ve SEO temelli olarak tasarlayıp geliştiriyoruz.'],
    ['no'=>'04','title'=>'Teslim / Takip','desc'=>'Yayına alıp teslim ediyor, sonrasında performansı takip ediyoruz.'],
  ];
  $home_works=[
    ['cat'=>'Güzellik & Bakım','title'=>'Randevu odaklı salon sitesi','img'=>''],
    ['cat'=>'Teknik Servis','title'=>'WhatsApp dönüşümlü servis sitesi','img'=>''],
    ['cat'=>'Kurumsal','title'=>'Çok sayfalı tanıtım sitesi','img'=>''],
    ['cat'=>'E-Ticaret','title'=>'Ürün ve kampanya vitrini','img'=>''],
    ['cat'=>'Sağlık & Klinik','title'=>'Randevulu klinik sitesi','img'=>''],
    ['cat'=>'Yeme & İçme','title'=>'Menü ve rezervasyon sitesi','img'=>''],
  ];
  $home_packages=[
    ['name'=>'Başlangıç','who'=>'Hızlı bir dijital vitrin isteyen yerel işletmeler için.','features'=>['Mobil uyumlu tek sayfa','WhatsApp & arama butonu','Temel SEO kurulumu','Harita ve iletişim'],'featured'=>false],
    ['name'=>'Standart','who'=>'Kurumsal duruş ve daha fazla içerik isteyenler için.','features'=>['Çok sayfalı yapı','Hizmet & referans bölümleri','Blog / haber altyapısı','Gelişmiş SEO düzeni'],'featured'=>true],
    ['name'=>'Premium','who'=>'Kendi içeriğini yöneten ve büyümek isteyen markalar için.','features'=>['Yönetim paneli','Randevu / talep sistemi','Çok dil opsiyonu','Özel yazılım & entegrasyon'],'featured'=>false],
  ];
?>

<!-- 1) HERO -->
<section class="hero">
  <div class="wrap">
    <div class="hero-copy">
      <span class="eyebrow"><span class="eb-dot" aria-hidden="true"></span>Web Tasarım &amp; Dijital Çözümler</span>
      <h1 class="hero-title">Markanızı büyüten,<br><span class="hl">satışa çeviren</span> web siteleri.</h1>
      <p class="hero-lead">Tek sayfa siteden yönetim panelli kurumsal siteye kadar; mobil uyumlu, SEO temelli ve <b>WhatsApp’a dönüşüm</b> taşıyan profesyonel web çözümleri kuruyoruz.</p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="/iletisim#teklif">Ücretsiz Teklif Al</a>
        <a class="btn btn-secondary btn-lg" href="/hizmetler">Hizmetleri İncele</a>
      </div>
    </div>
    <div class="hero-cards">
      <?php foreach($hero_cards as $c): ?>
      <article class="trust-card"><span class="tc-mark" aria-hidden="true"><?=e($c['m'])?></span><h3><?=e($c['t'])?></h3><p><?=e($c['d'])?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- MARQUEE -->
<section class="marquee" aria-hidden="true">
  <div class="marquee-track">
    <span>WEB</span><i class="dot"></i><span class="o">SEO</span><i class="dot"></i><span>PANEL</span><i class="dot"></i><span class="o">E-TİCARET</span><i class="dot"></i><span>RANDEVU</span><i class="dot"></i><span class="o">KURUMSAL</span><i class="dot"></i><span>TASARIM</span><i class="dot"></i>
    <span>WEB</span><i class="dot"></i><span class="o">SEO</span><i class="dot"></i><span>PANEL</span><i class="dot"></i><span class="o">E-TİCARET</span><i class="dot"></i><span>RANDEVU</span><i class="dot"></i><span class="o">KURUMSAL</span><i class="dot"></i><span>TASARIM</span><i class="dot"></i>
  </div>
</section>

<!-- 2) HİZMETLER -->
<section class="section">
  <div class="wrap">
    <div class="section-heading"><div><span class="eyebrow">Hizmetler</span><h2 class="section-title">İşinizi büyütecek dijital hizmetler</h2><p class="section-lead">İhtiyacınıza göre doğru paketi kuruyor, gereksiz masraf çıkarmadan sonuç odaklı ilerliyoruz.</p></div><a class="head-link" href="/hizmetler">Tüm hizmetler →</a></div>
    <div class="card-grid cols-3">
      <?php foreach(array_slice(front_services(),0,6) as $i=>$sv): ?>
      <article class="service-card"><span class="card-index"><?=sprintf('%02d',$i+1)?></span><h3 class="card-title"><?=e($sv['title'])?></h3><p><?=e($sv['desc'])?></p><a class="card-link" href="/hizmetler?h=<?=e($sv['slug'])?>">Detay →</a></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 3) SÜREÇ -->
<section class="section section-alt">
  <div class="wrap">
    <div class="section-heading center"><span class="eyebrow center">Nasıl Çalışıyoruz?</span><h2 class="section-title">Fikirden yayına, net 4 adım</h2><p class="section-lead">Her aşamada ne olduğunu bilirsiniz; sürprizsiz, şeffaf ve planlı ilerleriz.</p></div>
    <div class="process-grid">
      <?php foreach($home_process as $st): ?>
      <article class="process-card"><span class="card-index"><?=e($st['no'])?></span><h3 class="card-title"><?=e($st['title'])?></h3><p><?=e($st['desc'])?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 4) ÖNE ÇIKAN İŞLER / GALERİ -->
<section class="section">
  <div class="wrap">
    <div class="section-heading"><div><span class="eyebrow">Öne Çıkan İşler</span><h2 class="section-title">Farklı sektörlerden çalışmalar</h2><p class="section-lead">Yerel işletmelerden kurumsal markalara kadar hazırladığımız web çalışmalarından bir seçki.</p></div><a class="head-link" href="/referanslar">Tümünü gör →</a></div>
    <div class="work-grid">
      <?php foreach($home_works as $w): ?>
      <article class="work-card">
        <div class="work-media"><?php if(!empty($w['img'])): ?><img src="<?=e($w['img'])?>" alt="<?=e($w['title'])?>" loading="lazy"><?php else: ?><span class="work-ph" aria-hidden="true"><span><?=e(first_letter($w['cat']))?></span></span><?php endif; ?></div>
        <div class="work-body"><span class="work-cat"><?=e($w['cat'])?></span><h3><?=e($w['title'])?></h3></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 5) PAKETLER -->
<section class="section section-alt" id="paketler">
  <div class="wrap">
    <div class="section-heading center"><span class="eyebrow center">Paketler</span><h2 class="section-title">İşletmenize uygun paketi seçin</h2><p class="section-lead">İhtiyacınıza göre kapsamı birlikte belirliyor, net bir teklif çıkarıyoruz.</p></div>
    <div class="pkg-grid">
      <?php foreach($home_packages as $p): ?>
      <article class="pkg-card<?=!empty($p['featured'])?' featured':''?>">
        <?php if(!empty($p['featured'])): ?><span class="pkg-tag">Öne çıkan</span><?php endif; ?>
        <h3 class="pkg-name"><?=e($p['name'])?></h3>
        <p class="pkg-who"><?=e($p['who'])?></p>
        <ul class="pkg-list"><?php foreach($p['features'] as $f): ?><li><?=e($f)?></li><?php endforeach; ?></ul>
        <a class="btn <?=!empty($p['featured'])?'btn-light':'btn-primary'?> btn-block" href="https://wa.me/<?=$wa?>?text=<?=rawurlencode('Merhaba, '.$p['name'].' paketi için teklif almak istiyorum.')?>" target="_blank" rel="noopener">Teklif Al</a>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 6) SSS -->
<section class="section">
  <div class="wrap">
    <div class="section-heading center"><span class="eyebrow center">Sık Sorulan Sorular</span><h2 class="section-title">Aklınıza takılanlar</h2></div>
    <div class="faq">
      <?php foreach(faq_items() as $i=>$fq): $fid='faq'.$i; ?>
      <div class="faq-item<?=$i===0?' open':''?>">
        <button type="button" class="faq-q" id="<?=$fid?>-q" aria-expanded="<?=$i===0?'true':'false'?>" aria-controls="<?=$fid?>-a"><?=e($fq['q'])?><span class="faq-ico" aria-hidden="true">+</span></button>
        <div class="faq-a" id="<?=$fid?>-a" role="region" aria-labelledby="<?=$fid?>-q"><div class="faq-a-inner"><p><?=e($fq['a'])?></p></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 7) FİNAL CTA -->
<section class="cta">
  <div class="wrap cta-inner">
    <div><h2 class="cta-title"><?=e($s['cta_title'])?></h2><p class="cta-text"><?=e($s['cta_desc'])?></p></div>
    <div class="cta-actions"><a class="btn btn-light btn-lg" href="https://wa.me/<?=$wa?>?text=Merhaba%2C%20web%20sitesi%20teklifi%20almak%20istiyorum." target="_blank" rel="noopener">WhatsApp’tan Yaz</a><a class="btn btn-line btn-lg" href="/iletisim#teklif">İletişime Geç</a></div>
  </div>
</section>

<?php elseif($page==='hizmetler'):
  $hslug=$_GET['h']??''; $svc=$hslug?front_service_find($hslug):null;
  if($svc): $waMsg=rawurlencode('Merhaba, '.$svc['title'].' hizmeti için bilgi almak istiyorum.');
?>
<!-- 1) SERVICE HERO -->
<section class="page-hero service-hero">
  <div class="wrap">
    <span class="eyebrow">Hizmet</span>
    <h1><?=e($svc['title'])?></h1>
    <p><?=e($svc['long'])?></p>
    <div class="page-hero-actions">
      <a class="btn btn-primary" href="/iletisim#teklif">Ücretsiz Teklif Al</a>
      <a class="btn btn-secondary" href="https://wa.me/<?=$wa?>?text=<?=$waMsg?>" target="_blank" rel="noopener">WhatsApp’tan Yaz</a>
      <a class="btn btn-secondary" href="#surec">Süreci İncele</a>
    </div>
    <?=breadcrumb_html([['label'=>'Anasayfa','url'=>'/'],['label'=>'Hizmetler','url'=>'/hizmetler'],['label'=>$svc['title']]])?>
    <div class="hero-cards">
      <?php foreach(service_hero_cards() as $c): ?>
      <article class="trust-card"><span class="tc-mark" aria-hidden="true"><?=e($c['m'])?></span><h3><?=e($c['t'])?></h3><p><?=e($c['d'])?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 2) HİZMET KAPSAMI -->
<section class="section"><div class="wrap">
  <div class="section-heading"><div><span class="eyebrow">Kapsam</span><h2 class="section-title">Bu hizmette neler var?</h2><p class="section-lead"><?=e($svc['desc'])?></p></div></div>
  <div class="card-grid cols-3">
    <?php foreach($svc['scope'] as $i=>$sc): ?>
    <article class="service-card"><span class="card-index"><?=sprintf('%02d',$i+1)?></span><h3 class="card-title"><?=e($sc['t'])?></h3><p><?=e($sc['d'])?></p></article>
    <?php endforeach; ?>
  </div>
</div></section>

<!-- 3) KİMLER İÇİN UYGUN -->
<section class="section section-alt"><div class="wrap">
  <div class="section-heading"><div><span class="eyebrow">Kimler İçin?</span><h2 class="section-title">Bu hizmet kimler için uygun?</h2></div></div>
  <div class="card-grid cols-3">
    <?php foreach($svc['audience'] as $a): ?>
    <article class="feature-card"><h3 class="card-title"><?=e($a['t'])?></h3><p><?=e($a['d'])?></p></article>
    <?php endforeach; ?>
  </div>
</div></section>

<!-- 4) ÇALIŞMA SÜRECİ -->
<section class="section" id="surec"><div class="wrap">
  <div class="section-heading center"><span class="eyebrow center">Çalışma Süreci</span><h2 class="section-title">Bu iş nasıl ilerleyecek?</h2></div>
  <div class="process-grid">
    <?php foreach(service_process() as $st): ?>
    <article class="process-card"><span class="card-index"><?=e($st['no'])?></span><h3 class="card-title"><?=e($st['title'])?></h3><p><?=e($st['desc'])?></p></article>
    <?php endforeach; ?>
  </div>
</div></section>

<!-- 5) AVANTAJLAR / FARKLAR -->
<section class="section section-alt"><div class="wrap">
  <div class="section-heading center"><span class="eyebrow center">Avantajlar</span><h2 class="section-title">Neden bu hizmeti bizden almalısınız?</h2></div>
  <div class="card-grid cols-3">
    <?php foreach(why_us_cards() as $w): ?><article class="feature-card"><h3 class="card-title"><?=e($w['title'])?></h3><p><?=e($w['desc'])?></p></article><?php endforeach; ?>
  </div>
</div></section>

<!-- 6) GÖRSEL / ÇALIŞMA ÖRNEĞİ -->
<section class="section"><div class="wrap">
  <div class="section-heading"><div><span class="eyebrow">Çalışma Örneği</span><h2 class="section-title">Teslim ettiğimiz yapı</h2><p class="section-lead">Hazırladığımız çalışmaların masaüstü, mobil ve yönetim görünümünden örnek düzenler.</p></div><a class="head-link" href="/referanslar">Referanslar →</a></div>
  <div class="work-grid">
    <?php foreach(service_gallery() as $g): ?>
    <article class="work-card"><div class="work-media"><span class="work-ph" aria-hidden="true"><span><?=e(first_letter($g['cat']))?></span></span></div><div class="work-body"><span class="work-cat"><?=e($g['cat'])?></span><h3><?=e($g['title'])?></h3></div></article>
    <?php endforeach; ?>
  </div>
</div></section>

<!-- 7) PAKET / TEKLİF SEÇENEKLERİ -->
<section class="section section-alt"><div class="wrap">
  <div class="section-heading center"><span class="eyebrow center">Paketler</span><h2 class="section-title">Size uygun paketi seçin</h2><p class="section-lead">İhtiyacınıza göre kapsamı birlikte belirliyor, net bir teklif çıkarıyoruz.</p></div>
  <div class="pkg-grid">
    <?php foreach(service_packages() as $p): ?>
    <article class="pkg-card<?=!empty($p['featured'])?' featured':''?>"><?php if(!empty($p['featured'])): ?><span class="pkg-tag">Öne çıkan</span><?php endif; ?><h3 class="pkg-name"><?=e($p['name'])?></h3><p class="pkg-who"><?=e($p['who'])?></p><ul class="pkg-list"><?php foreach($p['features'] as $f): ?><li><?=e($f)?></li><?php endforeach; ?></ul><a class="btn <?=!empty($p['featured'])?'btn-light':'btn-primary'?> btn-block" href="https://wa.me/<?=$wa?>?text=<?=rawurlencode('Merhaba, '.$svc['title'].' - '.$p['name'].' paketi için teklif almak istiyorum.')?>" target="_blank" rel="noopener">Teklif Al</a></article>
    <?php endforeach; ?>
  </div>
</div></section>

<!-- 8) SSS -->
<section class="section"><div class="wrap">
  <div class="section-heading center"><span class="eyebrow center">SSS</span><h2 class="section-title">Sık sorulan sorular</h2></div>
  <div class="faq">
    <?php $sfaq=!empty($svc['faq'])?$svc['faq']:faq_items(); foreach($sfaq as $i=>$fq): $fid='svcfaq'.$i; ?>
    <div class="faq-item<?=$i===0?' open':''?>">
      <button type="button" class="faq-q" id="<?=$fid?>-q" aria-expanded="<?=$i===0?'true':'false'?>" aria-controls="<?=$fid?>-a"><?=e($fq['q'])?><span class="faq-ico" aria-hidden="true">+</span></button>
      <div class="faq-a" id="<?=$fid?>-a" role="region" aria-labelledby="<?=$fid?>-q"><div class="faq-a-inner"><p><?=e($fq['a'])?></p></div></div>
    </div>
    <?php endforeach; ?>
  </div>
</div></section>

<!-- 9) FİNAL CTA -->
<?php final_cta_html(['title'=>$svc['title'].' için bugün teklif alın','desc'=>'Bilgilerinizi bırakın; ihtiyacınıza uygun net bir teklifle aynı gün size dönelim.']); ?>
<?php else:
    page_hero(['eyebrow'=>'Hizmetler','title'=>'Web tasarım, yazılım, SEO ve dijital pazarlama','desc'=>'İşletmenizin ihtiyacına göre doğru dijital paketi kuruyor, ölçülebilir sonuçlar üretiyoruz.','actions'=>[['label'=>'Ücretsiz Teklif Al','href'=>'/iletisim#teklif']],'crumbs'=>[['label'=>'Anasayfa','url'=>'/'],['label'=>'Hizmetler']]]);
?>
<section class="section"><div class="wrap"><div class="card-grid cols-3">
  <?php foreach(front_services() as $i=>$sv): ?><article class="service-card"><span class="card-index"><?=sprintf('%02d',$i+1)?></span><h3 class="card-title"><?=e($sv['title'])?></h3><p><?=e($sv['desc'])?></p><a class="card-link" href="/hizmetler?h=<?=e($sv['slug'])?>">Detay →</a></article><?php endforeach; ?>
</div></div></section>
<section class="section section-alt"><div class="wrap"><div class="section-heading center"><span class="eyebrow center">Süreçlerimiz</span><h2 class="section-title">Nasıl ilerliyoruz?</h2></div><div class="process-grid">
  <?php foreach(array_slice(process_steps(),0,4) as $st): ?><article class="process-card"><span class="card-index"><?=e($st['no'])?></span><h3 class="card-title"><?=e($st['title'])?></h3><p><?=e($st['desc'])?></p></article><?php endforeach; ?>
</div></div></section>
<?php final_cta_html(); endif; ?>

<?php elseif($page==='paketler'):
  page_hero(['eyebrow'=>'Paketler','title'=>'Başlangıçtan özel yazılıma kadar paketler','desc'=>'Başlangıç fiyatı tek sayfalık HTML site içindir. Randevu, çok sayfa, panel ve çok dil ihtiyaca göre tekliflendirilir.','actions'=>[['label'=>'Ücretsiz Teklif Al','href'=>'/iletisim#teklif']],'crumbs'=>[['label'=>'Anasayfa','url'=>'/'],['label'=>'Paketler']]]);
?>
<section class="section"><div class="wrap"><div class="pkg-grid">
  <?php foreach(package_cards() as $p): ?>
  <article class="pkg-card<?=!empty($p['featured'])?' featured':''?>"><?php if(!empty($p['featured'])): ?><span class="pkg-tag">En çok tercih edilen</span><?php endif; ?><h3 class="pkg-name"><?=e($p['name'])?></h3><p class="pkg-who"><?=e($p['who'])?></p><div class="pkg-price"><?=e($p['price'])?></div><ul class="pkg-list"><?php foreach($p['features'] as $f): ?><li><?=e($f)?></li><?php endforeach; ?></ul><a class="btn <?=!empty($p['featured'])?'btn-light':'btn-primary'?> btn-block" href="https://wa.me/<?=$wa?>?text=<?=rawurlencode('Merhaba, '.$p['name'].' paketi için teklif almak istiyorum.')?>" target="_blank" rel="noopener">WhatsApp’tan Teklif Al</a></article>
  <?php endforeach; ?>
</div></div></section>
<?php final_cta_html(); ?>

<?php elseif($page==='referanslar'): $refs=array_values(array_filter(references_all(),fn($r)=>!empty($r['active'])));
  page_hero(['eyebrow'=>'Çalışmalar','title'=>'Referanslarımız ve çalışma alanlarımız','desc'=>'Farklı sektörlerden işletmeler için hazırladığımız web çalışmalarından bir seçki.','actions'=>[['label'=>'Benzer bir proje başlatın','href'=>'/iletisim#teklif']],'crumbs'=>[['label'=>'Anasayfa','url'=>'/'],['label'=>'Referanslar']]]);
?>
<section class="section"><div class="wrap">
  <?php if($refs): ?>
  <div class="work-grid">
    <?php foreach($refs as $r): ?>
    <article class="work-card">
      <div class="work-media"><?php if(!empty($r['logo'])): ?><img src="<?=e($r['logo'])?>" alt="<?=e($r['name'])?>" loading="lazy"><?php else: ?><span class="work-ph" aria-hidden="true"><span><?=e(first_letter($r['name']))?></span></span><?php endif; ?></div>
      <div class="work-body"><span class="work-cat"><?=e($r['category']??'Web Tasarım')?></span><h3><?=e($r['name'])?></h3><?php if(!empty($r['note'])): ?><p><?=e($r['note'])?></p><?php endif; ?><?php if(!empty($r['website'])): ?><a class="work-visit" href="<?=e($r['website'])?>" target="_blank" rel="noopener">Web sitesini gör →</a><?php endif; ?></div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?><p class="muted">Aktif referans henüz yayınlanmadı.</p><?php endif; ?>
</div></section>
<?php final_cta_html(); ?>

<?php elseif($page==='blog'||$page==='haberler'): $type=$page==='blog'?'blog':'haber'; $posts=array_values(array_filter(posts_all(),fn($p)=>($p['type']??'blog')===$type));
  $ptitle = $page==='blog'?'Web sitesi, SEO ve dijital büyüme rehberleri':'Xtanbul duyuruları ve gelişmeler';
  page_hero(['eyebrow'=>$page==='blog'?'Blog / Rehber':'Haberler','title'=>$ptitle,'desc'=>$page==='blog'?'Uygun fiyatlı web sitesi, SEO, randevu sistemi ve dijital büyüme üzerine kısa rehberler.':'Xtanbul Yazılım Agent’tan duyurular ve gelişmeler.','crumbs'=>[['label'=>'Anasayfa','url'=>'/'],['label'=>$page==='blog'?'Blog / Rehber':'Haberler']]]);
?>
<section class="section"><div class="wrap"><div class="card-grid cols-3">
  <?php foreach($posts as $p): ?><article class="post-card"><div class="post-head"><span class="post-cat"><?= $type==='blog'?'Rehber':'Haber' ?></span><?php if(!empty($p['created_at'])): ?><small><?=e($p['created_at'])?></small><?php endif; ?></div><h3><a href="/<?= $type==='blog'?'blog':'haber' ?>/<?=e($p['slug'])?>"><?=e($p['title'])?></a></h3><p><?=e($p['summary'])?></p><a class="post-more" href="/<?= $type==='blog'?'blog':'haber' ?>/<?=e($p['slug'])?>">Devamını oku →</a></article><?php endforeach; ?>
  <?php if(!$posts): ?><p class="muted">Henüz içerik yok.</p><?php endif; ?>
</div></div></section>
<?php final_cta_html(); ?>

<?php elseif($page==='kurumsal'):
  page_hero(['eyebrow'=>'Kurumsal','title'=>'Xtanbul Yazılım Agent','desc'=>'Küçük ve orta ölçekli işletmeleri hızlı, güvenilir ve satış odaklı web siteleriyle dijitale taşıyan bir yazılım ajansıyız.','actions'=>[['label'=>'İletişime Geç','href'=>'/iletisim#teklif'],['label'=>'Çalışmaları Gör','href'=>'/referanslar','style'=>'btn-secondary']],'crumbs'=>[['label'=>'Anasayfa','url'=>'/'],['label'=>'Kurumsal']]]);
?>
<section class="section"><div class="wrap split-2">
  <div><span class="eyebrow">Biz kimiz?</span><h2 class="section-title">Satış odaklı, şeffaf bir dijital atölye</h2><p>Web sitesi olmayan ya da mevcut sitesi yeterince müşteri kazandırmayan işletmeler için hızlı açılan, mobilde kusursuz görünen ve WhatsApp’a dönüşüm taşıyan siteler geliştiriyoruz. Gerektiğinde randevu, yönetim paneli, çok dil, e-ticaret ve reklam danışmanlığı ekliyoruz.</p><p>İşimizi teklif, sözleşme ve teslim sürecine kadar şeffaf yönetiyoruz; yayına aldıktan sonra da destek veriyoruz.</p></div>
  <div class="stat-cards"><article><b>4.999 TL</b><span>başlangıç web sitesi</span></article><article><b>8 adım</b><span>şeffaf süreç</span></article><article><b>1 yıl</b><span>sözleşmeli hizmet</span></article><article><b>7/24</b><span>WhatsApp iletişim</span></article></div>
</div></section>
<section class="section section-alt"><div class="wrap"><div class="section-heading center"><span class="eyebrow center">Değerlerimiz</span><h2 class="section-title">Nasıl bir yaklaşımla çalışıyoruz?</h2></div><div class="card-grid cols-3"><?php foreach(why_us_cards() as $w): ?><article class="feature-card"><h3 class="card-title"><?=e($w['title'])?></h3><p><?=e($w['desc'])?></p></article><?php endforeach; ?></div></div></section>
<section class="section"><div class="wrap"><div class="section-heading center"><span class="eyebrow center">Çalışma Prensibimiz</span><h2 class="section-title">Fikirden yayına, net adımlarla</h2></div><div class="process-grid"><?php foreach(array_slice(process_steps(),0,4) as $st): ?><article class="process-card"><span class="card-index"><?=e($st['no'])?></span><h3 class="card-title"><?=e($st['title'])?></h3><p><?=e($st['desc'])?></p></article><?php endforeach; ?></div></div></section>
<?php final_cta_html(); ?>

<?php elseif($page==='iletisim'):
  page_hero(['eyebrow'=>'İletişim & Teklif','title'=>'Biz mi arayalım?','desc'=>'Formu doldurun; sektörünüze uygun 2 örnek tasarım ve net paket teklifiyle sizi biz arayalım.','crumbs'=>[['label'=>'Anasayfa','url'=>'/'],['label'=>'İletişim']]]);
?>
<section class="section"><div class="wrap contact-grid">
  <aside class="contact-info">
    <article><h3>Telefon</h3><p><a href="tel:<?=e(preg_replace('/\s+/','',$s['contact_phone']))?>"><?=e($s['contact_phone'])?></a></p><a class="btn btn-primary btn-sm" href="tel:<?=e(preg_replace('/\s+/','',$s['contact_phone']))?>">Hemen Ara</a></article>
    <article><h3>WhatsApp</h3><p>Aynı gün içinde dönüş yapıyoruz.</p><a class="btn btn-secondary btn-sm" href="https://wa.me/<?=$wa?>?text=Merhaba%2C%20web%20sitesi%20teklifi%20almak%20istiyorum." target="_blank" rel="noopener">WhatsApp’tan Yaz</a></article>
    <article><h3>E-posta</h3><p><a href="mailto:<?=e($s['contact_email'])?>"><?=e($s['contact_email'])?></a></p></article>
    <article><h3>Adres</h3><p><?=e($s['contact_address'])?></p><a class="btn btn-secondary btn-sm" href="<?=e($s['maps_url'])?>" target="_blank" rel="noopener">Haritada Aç</a></article>
    <article><h3>Çalışma Saatleri</h3><p>Hafta içi 09:00 – 18:00<br>Cumartesi 10:00 – 15:00</p></article>
  </aside>
  <div class="quote-card" id="teklif">
    <h2>Teklif Formu</h2>
    <p class="quote-sub">Bilgilerinizi bırakın, en kısa sürede dönüş yapalım. Zorunlu alanlar <span>*</span></p>
    <form id="quoteForm" class="quote-form js-lead-form" data-status="#quoteStatus" novalidate>
      <div class="hp" aria-hidden="true"><label>Firma web sitesi<input type="text" name="company_site" tabindex="-1" autocomplete="off"></label></div>
      <div class="fg"><label>Ad Soyad *</label><input name="person" required placeholder="Adınız Soyadınız"></div>
      <div class="fg"><label>Firma Adı</label><input name="company" placeholder="İşletme / firma adı"></div>
      <div class="fg"><label>Telefon *</label><input name="phone" required placeholder="05xx xxx xx xx" inputmode="tel"></div>
      <div class="fg"><label>E-posta</label><input name="email" type="email" placeholder="ornek@eposta.com"></div>
      <div class="fg"><label>Sektör</label><input name="sector" placeholder="Örn: Güzellik salonu"></div>
      <div class="fg"><label>Şehir</label><input name="city" placeholder="Şehir"></div>
      <div class="fg"><label>İlçe</label><input name="district" placeholder="İlçe"></div>
      <div class="fg"><label>Mevcut web siteniz var mı?</label><select name="has_website"><option value="Yok">Yok</option><option value="Var ama zayıf">Var ama zayıf</option><option value="Var, iyi">Var, iyi</option></select></div>
      <div class="fg"><label>İstenen hizmet</label><select name="service"><option value="">Seçiniz</option><?php foreach(['Tek sayfa web sitesi','Randevulu web sitesi','Çok sayfalı kurumsal site','Yönetim panelli site','E-ticaret','SEO','Reklam danışmanlığı','Sosyal medya danışmanlığı','Özel yazılım'] as $o): ?><option><?=e($o)?></option><?php endforeach; ?></select></div>
      <div class="fg"><label>Bütçe aralığı</label><select name="budget"><option value="">Seçiniz</option><option>5.000 TL altı</option><option>5.000 - 10.000 TL</option><option>10.000 - 25.000 TL</option><option>25.000 TL üzeri</option></select></div>
      <div class="fg"><label>Ne kadar acil?</label><select name="urgency"><option value="">Seçiniz</option><option>Bu hafta</option><option>Bu ay</option><option>1-2 ay içinde</option><option>Sadece bilgi alıyorum</option></select></div>
      <div class="fg fg-full"><label>Açıklama</label><textarea name="message" rows="4" placeholder="İhtiyacınızı kısaca yazın"></textarea></div>
      <label class="kvkk-line fg-full"><input type="checkbox" name="kvkk" value="1" required> <a href="/kvkk" target="_blank">KVKK Aydınlatma Metni</a>’ni okudum, iletişim kurulmasını onaylıyorum. *</label>
      <div class="fg-full quote-submit"><button class="btn btn-primary btn-lg btn-block" type="submit">Teklif İste</button></div>
      <p class="form-status" id="quoteStatus" role="status" aria-live="polite"></p>
    </form>
  </div>
</div></section>

<?php elseif($page==='kvkk'):
  page_hero(['eyebrow'=>'KVKK','title'=>'KVKK Aydınlatma Metni','crumbs'=>[['label'=>'Anasayfa','url'=>'/'],['label'=>'KVKK']]]);
?>
<section class="section"><div class="wrap article"><p>Bu metin, Xtanbul Yazılım Agent ile iletişim kuran ziyaretçilerin ve müşterilerin kişisel verilerinin hangi amaçlarla işlendiğini açıklamak için hazırlanmıştır.</p><h2>İşlenen Veriler</h2><p>Ad soyad, telefon, e-posta, firma adı, web sitesi ihtiyacı, teklif ve sözleşme süreçlerinde paylaşılan bilgiler işlenebilir.</p><h2>İşleme Amaçları</h2><p>Teklif hazırlama, iletişim kurma, hizmet sunumu, ödeme ve sözleşme süreçlerini yürütme, müşteri ilişkilerini takip etme ve yasal yükümlülükleri yerine getirme amaçlarıyla veri işlenebilir.</p><h2>Aktarım</h2><p>Veriler; hizmetin yürütülmesi için zorunlu olduğu ölçüde hosting, alan adı, ödeme, muhasebe ve teknik altyapı hizmet sağlayıcılarıyla paylaşılabilir.</p><h2>Haklarınız</h2><p>KVKK kapsamındaki başvuru, düzeltme, silme, itiraz ve bilgi talebi haklarınız için iletişim sayfasındaki kanallardan bize ulaşabilirsiniz.</p><p><b>Not:</b> Bu metin teknik taslaktır; nihai hukuki metin şirket unvanı ve veri işleme süreçlerine göre özelleştirilmelidir.</p></div></section>

<?php elseif($page==='gizlilik'):
  page_hero(['eyebrow'=>'Gizlilik','title'=>'Gizlilik Politikası','crumbs'=>[['label'=>'Anasayfa','url'=>'/'],['label'=>'Gizlilik']]]);
?>
<section class="section"><div class="wrap article"><p>Ziyaretçi ve müşteri bilgileri, yalnızca teklif, iletişim, hizmet sunumu ve yasal yükümlülüklerin yerine getirilmesi amacıyla kullanılır. Bilgiler yetkisiz üçüncü kişilerle paylaşılmaz.</p></div></section>

<?php elseif($page==='cerez'):
  page_hero(['eyebrow'=>'Çerez','title'=>'Çerez Politikası','crumbs'=>[['label'=>'Anasayfa','url'=>'/'],['label'=>'Çerez']]]);
?>
<section class="section"><div class="wrap article"><p>Site deneyimini iyileştirmek, güvenlik ve performans sağlamak amacıyla zorunlu çerezler kullanılabilir. Reklam/analitik çerezleri eklenirse kullanıcı bilgilendirmesi ayrıca güncellenir.</p></div></section>

<?php endif; ?>
<?php footer_html(); ?>
