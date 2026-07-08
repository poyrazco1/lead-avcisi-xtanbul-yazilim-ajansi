<?php require __DIR__.'/app/core.php';
$page=$_GET['page']??'home';
$valid=['home','hizmetler','paketler','referanslar','blog','haberler','kurumsal','iletisim','kvkk','gizlilik','cerez'];
if(!in_array($page,$valid,true)){$page='home';}
$s=site_settings();
header_html($page);
?>
<?php if($page==='home'): $posts=array_slice(posts_all(),0,3); $refs=array_values(array_filter(references_all(),fn($r)=>!empty($r['active']))); ?>

<section class="hero hero-pro">
  <div class="hero-orbs" aria-hidden="true"><span class="orb o1"></span><span class="orb o2"></span><span class="orb o3"></span><span class="hero-grid-lines"></span></div>
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <span class="eyebrow"><span class="eb-dot"></span><?=e($s['hero_badge'])?></span>
      <h1>İşletmenizi internette <span class="hl">güven veren</span>, müşteri kazandıran web siteyle büyütün.</h1>
      <p>Tek sayfa siteden yönetim panelli kurumsal siteye kadar; mobil uyumlu, SEO temelli ve <b>WhatsApp’a dönüşüm</b> taşıyan profesyonel web çözümleri kuruyoruz.</p>
      <div class="hero-actions">
        <a class="btn primary lg" href="#teklif-hero">Ücretsiz Teklif Al</a>
        <a class="btn ghost lg" href="/paketler">Paketleri İncele</a>
      </div>
      <ul class="trust-badges">
        <?php foreach(trust_badges() as $b): ?><li><span class="tick">✓</span><?=e($b)?></li><?php endforeach; ?>
      </ul>
      <div class="hero-stats">
        <div><b>250<span>+</span></b><span>tamamlanan proje</span></div>
        <div><b>8<span> yıl</span></b><span>dijital tecrübe</span></div>
        <div><b>4.9<span>/5</span></b><span>müşteri memnuniyeti</span></div>
      </div>
    </div>
    <aside class="hero-form-card" id="teklif-hero">
      <div class="hff-head"><span class="hff-badge">Ücretsiz</span><h2>Hemen Teklif Alın</h2><p>Bilgilerinizi bırakın, aynı gün size dönelim.</p></div>
      <form class="hero-form js-lead-form" data-status="#heroStatus" novalidate>
        <div class="hp" aria-hidden="true"><input type="text" name="company_site" tabindex="-1" autocomplete="off"></div>
        <input name="person" required placeholder="Ad Soyad *">
        <input name="phone" required placeholder="Telefon *" inputmode="tel">
        <input name="company" placeholder="Firma adı (opsiyonel)">
        <select name="service">
          <option value="">İstediğiniz hizmet</option>
          <?php foreach(['Tek sayfa web sitesi','Randevulu web sitesi','Çok sayfalı kurumsal site','Yönetim panelli site','E-ticaret','SEO','Reklam / sosyal medya'] as $o): ?><option><?=e($o)?></option><?php endforeach; ?>
        </select>
        <label class="kvkk-line"><input type="checkbox" name="kvkk" value="1" required> <a href="/kvkk" target="_blank">KVKK metni</a>ni okudum, iletişim onaylıyorum.</label>
        <button class="btn primary full lg" type="submit">Teklif İste →</button>
        <p class="form-status" id="heroStatus" role="status" aria-live="polite"></p>
      </form>
      <div class="hff-foot"><a href="tel:<?=e(preg_replace('/\s+/','',$s['contact_phone']))?>">📞 <?=e($s['contact_phone'])?></a><a href="https://wa.me/<?=normalize_whatsapp($s['contact_whatsapp'])?>" target="_blank" rel="noopener">WhatsApp</a></div>
    </aside>
  </div>
</section>

<section class="section services">
  <div class="wrap">
    <div class="section-head center"><span class="eyebrow">Hizmetlerimiz</span><h2>İşinizi büyütecek dijital hizmetler</h2><p>İhtiyacınıza göre doğru paketi kuruyor, gereksiz masraf çıkarmadan sonuç odaklı ilerliyoruz.</p></div>
    <div class="card-grid cols-4">
      <?php foreach(front_services() as $sv): ?>
      <article class="feature-card"><div class="ico"><?=e($sv['icon'])?></div><h3><?=e($sv['title'])?></h3><p><?=e($sv['desc'])?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section why">
  <div class="wrap">
    <div class="section-head"><span class="eyebrow">Neden Xtanbul?</span><h2>Sadece güzel bir site değil, satan bir sistem</h2></div>
    <div class="card-grid cols-3">
      <?php foreach(why_us_cards() as $w): ?>
      <article class="why-card"><div class="ico"><?=e($w['icon'])?></div><h3><?=e($w['title'])?></h3><p><?=e($w['desc'])?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section process">
  <div class="wrap">
    <div class="section-head center"><span class="eyebrow">Süreçlerimiz</span><h2>Fikirden yayına, net 8 adım</h2><p>Her aşamada ne olduğunu bilirsiniz; sürprizsiz, şeffaf ve planlı ilerleriz.</p></div>
    <div class="steps-grid">
      <?php foreach(process_steps() as $st): ?>
      <article class="step-card"><b class="step-no"><?=e($st['no'])?></b><h3><?=e($st['title'])?></h3><p><?=e($st['desc'])?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section packages" id="paketler">
  <div class="wrap">
    <div class="section-head center"><span class="eyebrow">Paketler</span><h2>İşletmenize uygun paketi seçin</h2><p>Başlangıç fiyatı tek sayfalık site içindir; kapsam büyüdükçe ihtiyaca göre tekliflendirilir.</p></div>
    <div class="pkg-grid">
      <?php foreach(package_cards() as $p): ?>
      <article class="pkg-card<?=!empty($p['featured'])?' featured':''?>">
        <?php if(!empty($p['featured'])): ?><span class="pkg-tag">En çok tercih edilen</span><?php endif; ?>
        <h3><?=e($p['name'])?></h3>
        <p class="pkg-who"><?=e($p['who'])?></p>
        <div class="pkg-price"><?=e($p['price'])?></div>
        <ul class="pkg-list"><?php foreach($p['features'] as $f): ?><li><?=e($f)?></li><?php endforeach; ?></ul>
        <a class="btn primary full" href="https://wa.me/<?=normalize_whatsapp($s['contact_whatsapp'])?>?text=<?=rawurlencode('Merhaba, '.$p['name'].' paketi için teklif almak istiyorum.')?>" target="_blank" rel="noopener">WhatsApp’tan Teklif Al</a>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section refs">
  <div class="wrap">
    <div class="section-head"><span class="eyebrow">Referanslar</span><h2>Yaptığımız işler</h2><a class="head-link" href="/referanslar">Tümünü gör →</a></div>
    <div class="card-grid cols-3">
      <?php foreach(array_slice($refs,0,6) as $r): ?>
      <article class="ref-card">
        <div class="ref-logo"><?php if(!empty($r['logo'])): ?><img src="<?=e($r['logo'])?>" alt="<?=e($r['name'])?> logo"><?php else: ?><?=e(first_letter($r['name']))?><?php endif; ?></div>
        <h3><?=e($r['name'])?></h3>
        <p><?=e($r['note']??'Web çalışması')?></p>
        <small class="ref-cat"><?=e($r['category']??'Web Tasarım')?></small>
        <?php if(!empty($r['website'])): ?><a class="ref-visit" href="<?=e($r['website'])?>" target="_blank" rel="noopener">Web sitesi →</a><?php endif; ?>
      </article>
      <?php endforeach; ?>
      <?php if(!$refs): ?><p class="muted">Aktif referans henüz yayınlanmadı.</p><?php endif; ?>
    </div>
  </div>
</section>

<section class="section journal">
  <div class="wrap">
    <div class="section-head"><span class="eyebrow">Blog / Rehber</span><h2>Web ve SEO rehberleri</h2><a class="head-link" href="/blog">Blog’a git →</a></div>
    <div class="card-grid cols-3">
      <?php foreach($posts as $p): ?>
      <article class="post-card"><small><?=e($p['created_at']??'')?></small><h3><a href="/<?=($p['type']??'blog')==='haber'?'haber':'blog'?>/<?=e($p['slug'])?>"><?=e($p['title'])?></a></h3><p><?=e($p['summary'])?></p><a class="post-more" href="/<?=($p['type']??'blog')==='haber'?'haber':'blog'?>/<?=e($p['slug'])?>">Devamını oku →</a></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="wrap cta-inner">
    <div><h2><?=e($s['cta_title'])?></h2><p><?=e($s['cta_desc'])?></p></div>
    <div class="cta-actions"><a class="btn light lg" href="/iletisim#teklif">Ücretsiz Teklif Al</a><a class="btn outline-light lg" href="https://wa.me/<?=normalize_whatsapp($s['contact_whatsapp'])?>?text=Merhaba%2C%20web%20sitesi%20teklifi%20almak%20istiyorum." target="_blank" rel="noopener">WhatsApp’tan Yaz</a></div>
  </div>
</section>

<?php elseif($page==='hizmetler'): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">Hizmetlerimiz</span><h1>Web tasarım, yazılım, SEO ve dijital pazarlama</h1><p>İşletmenizin ihtiyacına göre doğru dijital paketi kuruyor, ölçülebilir sonuçlar üretiyoruz.</p></div></section>
<section class="section"><div class="wrap"><div class="card-grid cols-4">
  <?php foreach(front_services() as $sv): ?><article class="feature-card"><div class="ico"><?=e($sv['icon'])?></div><h3><?=e($sv['title'])?></h3><p><?=e($sv['desc'])?></p></article><?php endforeach; ?>
</div></div></section>
<section class="section process alt"><div class="wrap"><div class="section-head center"><span class="eyebrow">Süreçlerimiz</span><h2>Nasıl ilerliyoruz?</h2></div><div class="steps-grid">
  <?php foreach(process_steps() as $st): ?><article class="step-card"><b class="step-no"><?=e($st['no'])?></b><h3><?=e($st['title'])?></h3><p><?=e($st['desc'])?></p></article><?php endforeach; ?>
</div><div class="center mt"><a class="btn primary lg" href="/iletisim#teklif">Teklif Al</a></div></div></section>

<?php elseif($page==='paketler'): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">Paketler</span><h1>Başlangıçtan özel yazılıma kadar paketler</h1><p>Başlangıç fiyatı tek sayfalık HTML site içindir. Randevu, çok sayfa, panel ve çok dil ihtiyaca göre tekliflendirilir.</p></div></section>
<section class="section"><div class="wrap"><div class="pkg-grid">
  <?php foreach(package_cards() as $p): ?>
  <article class="pkg-card<?=!empty($p['featured'])?' featured':''?>"><?php if(!empty($p['featured'])): ?><span class="pkg-tag">En çok tercih edilen</span><?php endif; ?><h3><?=e($p['name'])?></h3><p class="pkg-who"><?=e($p['who'])?></p><div class="pkg-price"><?=e($p['price'])?></div><ul class="pkg-list"><?php foreach($p['features'] as $f): ?><li><?=e($f)?></li><?php endforeach; ?></ul><a class="btn primary full" href="https://wa.me/<?=normalize_whatsapp($s['contact_whatsapp'])?>?text=<?=rawurlencode('Merhaba, '.$p['name'].' paketi için teklif almak istiyorum.')?>" target="_blank" rel="noopener">WhatsApp’tan Teklif Al</a></article>
  <?php endforeach; ?>
</div></div></section>

<?php elseif($page==='referanslar'): $refs=array_values(array_filter(references_all(),fn($r)=>!empty($r['active']))); ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">Referanslar</span><h1>Referanslarımız ve çalışma alanlarımız</h1><p>Farklı sektörlerden işletmeler için hazırladığımız web çalışmaları.</p></div></section>
<section class="section"><div class="wrap"><div class="card-grid cols-3 big">
  <?php foreach($refs as $r): ?>
  <article class="ref-card"><div class="ref-logo"><?php if(!empty($r['logo'])): ?><img src="<?=e($r['logo'])?>" alt="<?=e($r['name'])?> logo"><?php else: ?><?=e(first_letter($r['name']))?><?php endif; ?></div><h3><?=e($r['name'])?></h3><p><?=e($r['note']??'Web sitesi çalışması')?></p><small class="ref-cat"><?=e($r['category']??'Web Tasarım')?></small><?php if(!empty($r['website'])): ?><a class="ref-visit" href="<?=e($r['website'])?>" target="_blank" rel="noopener">Web sitesini ziyaret et →</a><?php endif; ?></article>
  <?php endforeach; ?>
  <?php if(!$refs): ?><p class="muted">Aktif referans henüz yayınlanmadı.</p><?php endif; ?>
</div></div></section>

<?php elseif($page==='blog'||$page==='haberler'): $type=$page==='blog'?'blog':'haber'; $posts=array_values(array_filter(posts_all(),fn($p)=>($p['type']??'blog')===$type)); ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow"><?= $page==='blog'?'Blog / Rehber':'Haberler' ?></span><h1><?= $page==='blog'?'Web sitesi, SEO ve dijital büyüme rehberleri':'Xtanbul duyuruları ve gelişmeler' ?></h1></div></section>
<section class="section"><div class="wrap"><div class="card-grid cols-3">
  <?php foreach($posts as $p): ?><article class="post-card"><small><?=e($p['created_at']??'')?></small><h3><a href="/<?= $type==='blog'?'blog':'haber' ?>/<?=e($p['slug'])?>"><?=e($p['title'])?></a></h3><p><?=e($p['summary'])?></p><a class="post-more" href="/<?= $type==='blog'?'blog':'haber' ?>/<?=e($p['slug'])?>">Devamını oku →</a></article><?php endforeach; ?>
  <?php if(!$posts): ?><p class="muted">Henüz içerik yok.</p><?php endif; ?>
</div></div></section>

<?php elseif($page==='kurumsal'): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">Kurumsal</span><h1>Xtanbul Yazılım Agent</h1><p>Küçük ve orta ölçekli işletmeleri hızlı, güvenilir ve satış odaklı web siteleriyle dijitale taşıyan bir yazılım ajansıyız.</p></div></section>
<section class="section"><div class="wrap split-2">
  <div><h2>Biz kimiz?</h2><p>Web sitesi olmayan ya da mevcut sitesi yeterince müşteri kazandırmayan işletmeler için hızlı açılan, mobilde kusursuz görünen ve WhatsApp’a dönüşüm taşıyan siteler geliştiriyoruz. Gerektiğinde randevu, yönetim paneli, çok dil, e-ticaret ve reklam danışmanlığı ekliyoruz.</p><p>İşimizi teklif, sözleşme ve teslim sürecine kadar şeffaf yönetiyoruz; yayına aldıktan sonra da destek veriyoruz.</p></div>
  <div class="stat-cards"><article><b>4.999 TL</b><span>başlangıç web sitesi</span></article><article><b>8 adım</b><span>şeffaf süreç</span></article><article><b>1 yıl</b><span>sözleşmeli hizmet</span></article><article><b>7/24</b><span>WhatsApp iletişim</span></article></div>
</div></section>
<section class="section why alt"><div class="wrap"><div class="section-head center"><span class="eyebrow">Değerlerimiz</span><h2>Neden bizimle çalışılıyor?</h2></div><div class="card-grid cols-3"><?php foreach(why_us_cards() as $w): ?><article class="why-card"><div class="ico"><?=e($w['icon'])?></div><h3><?=e($w['title'])?></h3><p><?=e($w['desc'])?></p></article><?php endforeach; ?></div></div></section>

<?php elseif($page==='iletisim'): $wa=normalize_whatsapp($s['contact_whatsapp']); ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">İletişim & Teklif</span><h1>Biz mi arayalım?</h1><p>Formu doldurun; sektörünüze uygun 2 örnek tasarım ve net paket teklifiyle sizi biz arayalım.</p></div></section>
<section class="section contact-section"><div class="wrap contact-grid">
  <aside class="contact-info">
    <article><h3>WhatsApp / Telefon</h3><p><a href="tel:<?=e($s['contact_phone'])?>"><?=e($s['contact_phone'])?></a></p><a class="btn primary" href="https://wa.me/<?=$wa?>?text=Merhaba%2C%20web%20sitesi%20teklifi%20almak%20istiyorum." target="_blank" rel="noopener">WhatsApp’tan Yaz</a></article>
    <article><h3>E-posta</h3><p><a href="mailto:<?=e($s['contact_email'])?>"><?=e($s['contact_email'])?></a></p></article>
    <article><h3>Adres</h3><p><?=e($s['contact_address'])?></p><a class="btn ghost sm" href="<?=e($s['maps_url'])?>" target="_blank" rel="noopener">Haritada Aç</a></article>
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
      <div class="fg fg-2"><label>Şehir</label><input name="city" placeholder="Şehir"></div>
      <div class="fg fg-2"><label>İlçe</label><input name="district" placeholder="İlçe"></div>
      <div class="fg"><label>Mevcut web siteniz var mı?</label><select name="has_website"><option value="Yok">Yok</option><option value="Var ama zayıf">Var ama zayıf</option><option value="Var, iyi">Var, iyi</option></select></div>
      <div class="fg"><label>İstenen hizmet</label><select name="service"><option value="">Seçiniz</option><?php foreach(['Tek sayfa web sitesi','Randevulu web sitesi','Çok sayfalı kurumsal site','Yönetim panelli site','E-ticaret','SEO','Reklam danışmanlığı','Sosyal medya danışmanlığı','Özel yazılım'] as $o): ?><option><?=e($o)?></option><?php endforeach; ?></select></div>
      <div class="fg"><label>Bütçe aralığı</label><select name="budget"><option value="">Seçiniz</option><option>5.000 TL altı</option><option>5.000 - 10.000 TL</option><option>10.000 - 25.000 TL</option><option>25.000 TL üzeri</option></select></div>
      <div class="fg"><label>Ne kadar acil?</label><select name="urgency"><option value="">Seçiniz</option><option>Bu hafta</option><option>Bu ay</option><option>1-2 ay içinde</option><option>Sadece bilgi alıyorum</option></select></div>
      <div class="fg fg-full"><label>Açıklama</label><textarea name="message" rows="4" placeholder="İhtiyacınızı kısaca yazın"></textarea></div>
      <label class="kvkk-line fg-full"><input type="checkbox" name="kvkk" value="1" required> <a href="/kvkk" target="_blank">KVKK Aydınlatma Metni</a>’ni okudum, iletişim kurulmasını onaylıyorum. *</label>
      <div class="fg-full quote-submit"><button class="btn primary lg full" type="submit">Teklif İste</button></div>
      <p class="form-status" id="quoteStatus" role="status" aria-live="polite"></p>
    </form>
  </div>
</div></section>

<?php elseif($page==='kvkk'): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">KVKK</span><h1>KVKK Aydınlatma Metni</h1></div></section>
<section class="section"><div class="wrap article"><p>Bu metin, Xtanbul Yazılım Agent ile iletişim kuran ziyaretçilerin ve müşterilerin kişisel verilerinin hangi amaçlarla işlendiğini açıklamak için hazırlanmıştır.</p><h2>İşlenen Veriler</h2><p>Ad soyad, telefon, e-posta, firma adı, web sitesi ihtiyacı, teklif ve sözleşme süreçlerinde paylaşılan bilgiler işlenebilir.</p><h2>İşleme Amaçları</h2><p>Teklif hazırlama, iletişim kurma, hizmet sunumu, ödeme ve sözleşme süreçlerini yürütme, müşteri ilişkilerini takip etme ve yasal yükümlülükleri yerine getirme amaçlarıyla veri işlenebilir.</p><h2>Aktarım</h2><p>Veriler; hizmetin yürütülmesi için zorunlu olduğu ölçüde hosting, alan adı, ödeme, muhasebe ve teknik altyapı hizmet sağlayıcılarıyla paylaşılabilir.</p><h2>Haklarınız</h2><p>KVKK kapsamındaki başvuru, düzeltme, silme, itiraz ve bilgi talebi haklarınız için iletişim sayfasındaki kanallardan bize ulaşabilirsiniz.</p><p><b>Not:</b> Bu metin teknik taslaktır; nihai hukuki metin şirket unvanı ve veri işleme süreçlerine göre özelleştirilmelidir.</p></div></section>

<?php elseif($page==='gizlilik'): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">Gizlilik</span><h1>Gizlilik Politikası</h1></div></section>
<section class="section"><div class="wrap article"><p>Ziyaretçi ve müşteri bilgileri, yalnızca teklif, iletişim, hizmet sunumu ve yasal yükümlülüklerin yerine getirilmesi amacıyla kullanılır. Bilgiler yetkisiz üçüncü kişilerle paylaşılmaz.</p></div></section>

<?php elseif($page==='cerez'): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">Çerez</span><h1>Çerez Politikası</h1></div></section>
<section class="section"><div class="wrap article"><p>Site deneyimini iyileştirmek, güvenlik ve performans sağlamak amacıyla zorunlu çerezler kullanılabilir. Reklam/analitik çerezleri eklenirse kullanıcı bilgilendirmesi ayrıca güncellenir.</p></div></section>

<?php endif; ?>
<?php footer_html(); ?>
