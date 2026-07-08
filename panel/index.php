<?php
require __DIR__ . '/app/core.php';
require_login();
$csrf = csrf_token();
$s = stats();
$teamMembers = setting_get('team_members', team_members_default());
$packagePrices = setting_get('package_prices', package_default_prices());
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lead Avcısı Panel v5</title>
  <link rel="stylesheet" href="assets/style.css?v=53">
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <div class="brand-mark">LA</div>
        <div><strong>Lead Avcısı</strong><span>Ajans CRM v5</span></div>
      </div>
      <nav class="nav-menu">
        <div class="nav-group"><span>Kontrol</span><a class="active" href="#dashboardPanel" data-panel-nav>Dashboard</a></div>
        <div class="nav-group"><span>Lead</span><a href="#searchPanel" data-panel-nav>Lead Topla</a><a href="#leadPanel" data-panel-nav>Lead Listesi</a><a href="#importPanel" data-panel-nav>CSV İçe Aktar</a></div>
        <div class="nav-group"><span>Satış</span><a href="#contractPanel" data-panel-nav>Sözleşme & Teklif</a><a href="#operationPanel" data-panel-nav>Operasyon</a><a href="demos.php">Demo Siteler</a></div>
        <div class="nav-group"><span>Sistem</span><a href="#settingsPanel" data-panel-nav>Ayarlar</a><a href="#accountPanel" data-panel-nav>Güvenlik</a><a href="api/export.php">CSV İndir</a><a href="logout.php">Çıkış</a></div>
      </nav>
      <div class="privacy-box">
        <b>Gizli Mod</b>
        <span>API key frontend’de görünmez. Aramalar sunucu üzerinden geçer. “Tekrar aranmasın” numaraları kara listeye alınır.</span>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main">
      <header class="topbar">
        <div style="display:flex;align-items:center;gap:14px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menü"><span></span><span></span><span></span></button>
          <div>
            <span class="eyebrow">Xtanbul Ajans Operasyon Paneli</span>
            <h1>Lead Toplama + Satış CRM</h1>
            <p>Lead toplama, arama, WhatsApp, teklif, sözleşme, ödeme ve teslim tek panelde.</p>
          </div>
        </div>
        <div class="topbar-actions">
          <div class="topbar-search"><input id="topSearch" type="search" placeholder="Hızlı lead ara..."></div>
          <div class="user-pill">admin@poyraztoner.com</div>
          <a class="btn ghost mini" href="../" target="_blank">Siteyi Aç</a>
        </div>
      </header>

      <section class="quick-actions">
        <button class="quick-card" type="button" data-jump="#searchPanel"><span class="qc-ico">🎯</span><span class="qc-txt"><b>Yeni Lead Tara</b><span>Sektör + şehir seç</span></span></button>
        <button class="quick-card" type="button" data-jump="#leadPanel"><span class="qc-ico">📋</span><span class="qc-txt"><b>Leadleri Yönet</b><span>Ara, WhatsApp, not</span></span></button>
        <button class="quick-card" type="button" data-jump="#contractPanel"><span class="qc-ico">📄</span><span class="qc-txt"><b>Teklif / Sözleşme</b><span>A4 çıktı, takip</span></span></button>
        <button class="quick-card" type="button" data-jump="#operationPanel"><span class="qc-ico">🚀</span><span class="qc-txt"><b>Operasyon</b><span>Ödeme, teslim</span></span></button>
      </section>

      <section id="dashboardPanel" class="panel-section active">
        <div class="module-title"><div><h2>Dashboard</h2><p>Bugünün durumunu gör, sonra sadece ihtiyacın olan modüle geç.</p></div><button id="refreshStatsTop" class="btn ghost mini" type="button">Yenile</button></div>
        <div class="stats-grid">
        <article><span>Toplam Lead</span><strong id="statTotal"><?=e($s['total'])?></strong></article>
        <article><span>Bugün Gelen</span><strong id="statToday"><?=e($s['today'])?></strong></article>
        <article><span>Aranmadı</span><strong id="statNew"><?=e($s['new'])?></strong></article>
        <article><span>Teklif İstedi</span><strong id="statOffer"><?=e($s['offer'])?></strong></article>
        <article><span>Ödeme Bekliyor</span><strong id="statPayment"><?=e($s['payment'] ?? 0)?></strong></article>
        <article><span>Müşteri</span><strong id="statCustomer"><?=e($s['customer'] ?? 0)?></strong></article>
        <article><span>Dönüşüm</span><strong id="statConversion"><?=e($s['conversion'] ?? 0)?>%</strong></article>
        <article><span>Veri</span><strong id="statDb"><?=e($s['db'])?></strong></article>
        <article><span>Toplam Ciro</span><strong id="statRevenue"><?=number_format((float)($s['revenue_total'] ?? 0),0,',','.')?> TL</strong></article>
        <article><span>Alınan Ödeme</span><strong id="statPaid"><?=number_format((float)($s['revenue_paid'] ?? 0),0,',','.')?> TL</strong></article>
        <article><span>Alınacak</span><strong id="statPendingRevenue"><?=number_format((float)($s['revenue_pending'] ?? 0),0,',','.')?> TL</strong></article>
        <article><span>Sözleşme</span><strong id="statContracts"><?=e($s['contracts'] ?? 0)?></strong></article>
        <article><span>Aktif İş</span><strong id="statActiveOrders"><?=e($s['active_orders'] ?? 0)?></strong></article>
        <article><span>Teslim Geciken</span><strong id="statOverdueDelivery"><?=e($s['overdue_delivery'] ?? 0)?></strong></article>
        <article><span>Yenileme Yaklaşan</span><strong id="statRenewalDue"><?=e($s['renewal_due'] ?? 0)?></strong></article>
        </div>

      <section class="dashboard-grid">
        <article class="card mini-dashboard">
          <div class="section-head compact"><h2>Durum Özeti</h2><button id="refreshStats" class="btn ghost mini" type="button">Yenile</button></div>
          <div id="statusBreakdown" class="pill-list"></div>
        </article>
        <article class="card mini-dashboard">
          <div class="section-head compact"><h2>En Çok Lead Veren Sektörler</h2></div>
          <div id="topSectors" class="rank-list"></div>
        </article>
      </section>
      </section>

      <section id="searchPanel" class="panel-section lead-wizard">
        <div class="wizard-head">
          <div><h2>Yeni Lead Tara</h2><p>Sektör, bölge ve filtreleri seç; uygun işletmeleri CRM’e aktar.</p></div>
          <div class="wizard-mini">
            <div><b id="miniSectors">0</b><span>sektör/kelime</span></div>
            <div><b id="miniRegions">0</b><span>bölge</span></div>
            <div><b id="miniCombos">0</b><span>arama</span></div>
            <div><b id="miniLimit">250</b><span>limit</span></div>
          </div>
        </div>

        <div class="wizard-grid">
          <div class="wizard-main">
            <article class="step-card">
              <div class="step-head"><span class="step-number">1</span><div><h3>Sektör ve hizmet seç</h3><p>Hazır sektör, meslek veya serbest kelime ile hedefle.</p></div></div>
              <div class="sel-search"><input id="sekSearch" type="search" placeholder="Sektör, meslek veya anahtar kelime ara"></div>
              <div class="wiz-tabs" id="sekTabs">
                <button type="button" class="wiz-tab active" data-sektab="hazir">Hazır Sektörler</button>
                <button type="button" class="wiz-tab" data-sektab="meslek">Meslekler</button>
                <button type="button" class="wiz-tab" data-sektab="kelime">Serbest Kelimeler</button>
              </div>
              <div class="wiz-pane active" data-sekpane="hazir">
                <div class="cat-browser">
                  <div class="cat-main-list" id="catMainList"></div>
                  <div class="cat-sub-area" id="catSubArea"></div>
                </div>
              </div>
              <div class="wiz-pane" data-sekpane="meslek">
                <div class="meslek-tools"><span class="mini-label" id="meslekCount"></span><button type="button" class="btn ghost sm" id="meslekSelectAll">Görünenleri seç</button><button type="button" class="btn ghost sm" id="meslekClear">Seçimi temizle</button></div>
                <div class="chip-grid" id="meslekGrid"></div>
              </div>
              <div class="wiz-pane" data-sekpane="kelime">
                <div class="kw-input"><input id="kwInput" type="text" placeholder="Kelime yaz ve Enter’a bas"><button type="button" class="btn secondary sm" id="kwAdd">Ekle</button></div>
                <div class="kw-suggest" id="kwSuggest"></div>
              </div>
              <div class="step-selected"><b>Seçilenler</b><div class="selected-chips" id="sekSelected"><em>Henüz seçim yok</em></div><button type="button" class="btn ghost sm" id="clearSectors">Tümünü temizle</button></div>
            </article>

            <article class="step-card">
              <div class="step-head"><span class="step-number">2</span><div><h3>Bölge seç</h3><p>Şehir, ilçe ve semt; çoklu seçim yapabilirsin.</p></div></div>
              <div class="region-grid">
                <div class="region-col">
                  <label class="mini-label">Şehir</label>
                  <input id="citySearch" type="search" placeholder="Şehir ara">
                  <div class="scroll-list city-list" id="cityList"></div>
                </div>
                <div class="region-col">
                  <label class="mini-label">İlçeler <span class="inline-tools"><button type="button" class="btn ghost sm" id="selAllDistricts">Tümü</button><button type="button" class="btn ghost sm" id="clrDistricts">Temizle</button></span></label>
                  <div class="popular-row" id="popularDistricts"></div>
                  <div class="scroll-list district-area" id="districtArea"><p class="hint-empty">Önce şehir seç.</p></div>
                  <input id="manualRegion" type="text" placeholder="Manuel bölge / semt (Enter ile ekle)">
                </div>
              </div>
              <div class="step-selected"><b>Seçili bölgeler</b><div class="selected-chips" id="regionSelected"><em>Henüz seçim yok</em></div></div>
            </article>

            <article class="step-card">
              <div class="step-head"><span class="step-number">3</span><div><h3>Lead kalite filtreleri</h3><p>Doğru işletmeleri hedeflemek için filtrele.</p></div></div>
              <div class="quality-filter-grid">
                <label><input type="checkbox" id="fPhone" checked><span>Telefonu olanları al</span></label>
                <label><input type="checkbox" id="fWhatsapp" checked><span>WhatsApp uygun telefonları önceliklendir</span></label>
                <label><input type="checkbox" id="fNoWebsite" checked><span>Web sitesi olmayanları al</span></label>
                <label><input type="checkbox" id="fWeakWebsite"><span>Zayıf siteleri de işaretle</span></label>
                <label><input type="checkbox" id="fRated"><span>Sadece puanı olanları al</span></label>
                <label><input type="checkbox" id="fDedupePhone" checked><span>Aynı telefon tekrar kaydolmasın</span></label>
                <label><input type="checkbox" id="fDedupeName" checked><span>Aynı firma tekrar kaydolmasın</span></label>
                <label><input type="checkbox" id="fBlacklist" checked><span>Kara listedekileri alma</span></label>
                <label><input type="checkbox" id="fNoRecall" checked><span>“Tekrar aranmasın” olanları alma</span></label>
              </div>
              <div class="quality-nums">
                <label>Min. Google puanı<select id="minRating"><option value="0">Farketmez</option><option value="3.5">3.5+</option><option value="4">4.0+</option><option value="4.5">4.5+</option></select></label>
                <label>Min. yorum sayısı<input id="minReviews" type="number" min="0" value="0"></label>
              </div>
            </article>

            <article class="step-card">
              <div class="step-head"><span class="step-number">4</span><div><h3>Satış ayarı</h3><p>Toplanan lead bu bilgilerle CRM’e düşer.</p></div></div>
              <div class="sales-grid">
                <label>Satış temsilcisi<select id="assignSelect"><option value="">— Atanmadı —</option></select></label>
                <label>İlgili paket<select id="packageSelect">
                  <option value="onepage" selected>Tek Sayfa Web Sitesi</option>
                  <option value="onepage_appointment">Tek Sayfa + Randevu</option>
                  <option value="multipage">Kurumsal (Çok Sayfa) Web Sitesi</option>
                  <option value="multipage_appointment">Kurumsal + Randevu</option>
                  <option value="multipage_panel">Yönetim Panelli Site</option>
                  <option value="full_panel_appointment">E-Ticaret / Özel Yazılım</option>
                </select></label>
                <label>Öncelik başlangıcı<select id="prioritySelect"><option>Soğuk</option><option selected>Ilık</option><option>Sıcak</option><option>Çok sıcak</option></select></label>
                <label>Hedef kayıt limiti<input id="limitInput" type="number" min="10" max="5000" value="250"></label>
                <label>Lead kaynağı<input id="sourceInput" type="text" value="Google Places"></label>
                <label>Ödeme adımı<input id="paymentInput" type="text" value="%50 kapora ile başlıyoruz"></label>
                <label class="wide-2">İç not (opsiyonel)<input id="noteInput" type="text" placeholder="Bu taramadaki leadlere düşecek not"></label>
              </div>
              <details class="msg-extras"><summary>Mesajda gösterilecek ek hizmetler</summary>
                <div class="checkbox-list">
                  <label><input class="consulting-check" type="checkbox" value="ads" checked> Reklam danışmanlığı</label>
                  <label><input class="consulting-check" type="checkbox" value="ecommerce" checked> E-ticaret danışmanlığı</label>
                  <label><input class="consulting-check" type="checkbox" value="social" checked> Sosyal medya danışmanlığı</label>
                  <label><input id="multiLangCheck" type="checkbox" checked> Çok dilli site</label>
                </div>
              </details>
            </article>

            <article class="step-card progress-panel hidden" id="resultCard">
              <div class="step-head"><span class="step-number">5</span><div><h3>Tarama sonucu</h3><p>Canlı ilerleme ve bulunan işletmeler.</p></div></div>
              <div class="result-counters">
                <div><b id="cChecked">0</b><span>Kontrol</span></div>
                <div class="ok"><b id="cSaved">0</b><span>Kaydedildi</span></div>
                <div><b id="cDup">0</b><span>Zaten vardı</span></div>
                <div><b id="cSkip">0</b><span>Uygun değil</span></div>
                <div class="bad"><b id="cErr">0</b><span>Hata</span></div>
              </div>
              <div class="progress-box" id="progressBox"><div class="bar"><span id="progressBar"></span></div><p id="progressText">Hazırlanıyor...</p></div>
              <div class="found-list" id="foundList"></div>
            </article>
          </div>

          <aside class="wizard-summary">
            <div class="sticky-summary">
              <div class="ss-head"><b>Tarama Özeti</b><span>Başlatmadan önce kontrol et</span></div>
              <div class="ss-metrics">
                <div><b id="ssSectors">0</b><span>sektör/kelime</span></div>
                <div><b id="ssRegions">0</b><span>bölge</span></div>
                <div><b id="ssCombos">0</b><span>arama</span></div>
              </div>
              <div class="ss-block"><span class="ss-title">Sektörler</span><div class="selected-chips" id="ssSectorChips"><em>Seçim yok</em></div></div>
              <div class="ss-block"><span class="ss-title">Bölgeler</span><div class="selected-chips" id="ssRegionChips"><em>Seçim yok</em></div></div>
              <div class="ss-block"><span class="ss-title">Ayar</span><div class="ss-facts" id="ssFacts"></div></div>
              <div class="ss-missing" id="ssMissing"></div>
              <div class="ss-actions">
                <button id="startSearch" class="btn primary full" type="button" disabled>Lead Taramayı Başlat</button>
                <button id="stopSearch" class="btn danger full hidden" type="button">Durdur</button>
                <div class="ss-sub"><button id="saveSelection" class="btn ghost sm" type="button">Seçimi Kaydet</button><button id="resetWizard" class="btn ghost sm" type="button">Temizle</button><button id="refreshLeads" class="btn ghost sm" type="button">Listeyi Yenile</button></div>
                <a class="btn secondary sm full" href="api/export.php">Excel / CSV İndir</a>
              </div>
            </div>
          </aside>
        </div>
      </section>

      <section id="settingsPanel" class="card panel-section">
        <div class="section-head">
          <div><h2>Ayarlar</h2><p>Paket fiyatları, Google API key ve veritabanı durumunu buradan yönet. DB şifresi güvenlik için ekranda düz gösterilmez.</p></div>
          <button id="saveSettings" class="btn primary" type="button">Ayarları Kaydet</button>
        </div>
        <div class="package-grid">
          <?php foreach (package_catalog($packagePrices) as $key => $p): ?>
          <label class="package-card">
            <b><?=e($p['label'])?></b>
            <span><?=e($p['desc'])?></span>
            <input class="package-price" data-package="<?=e($key)?>" value="<?=e($p['price'])?>">
          </label>
          <?php endforeach; ?>
        </div>
        <div class="settings-grid">
          <label><b>Google Places API Key</b><input id="googleKeyInput" type="password" placeholder="Yeni key yazarsan kaydedilir"><small>Mevcut key gizlidir; sadece değiştirmen gerekirse yeni key yaz.</small></label>
          <label><b>IBAN</b><input id="ibanInput" placeholder="TR... ödeme IBAN"></label>
          <label><b>Alıcı Ünvanı</b><input id="holderInput" value="Xtanbul Yazılım Agent"></label>
          <label><b>Banka</b><input id="bankInput" placeholder="Banka adı"></label>
          <label class="wide-2"><b>Ödeme Açıklama Notu</b><input id="paymentNoteInput" value="Açıklama kısmına sözleşme numarası ve işletme adını yazınız."></label>
          <label><b>Varsayılan Revize Hakkı</b><input id="revisionLimitInput" type="number" value="2"></label>
          <label><b>Teslim Süresi (gün)</b><input id="deliveryDaysInput" type="number" value="7"></label>
          <label><b>Yenileme Uyarısı (gün)</b><input id="renewalWarnInput" type="number" value="60"></label>
          <label><b>DB Host</b><input readonly value="<?=e(cfg('db.host'))?>"></label>
          <label><b>DB Adı</b><input readonly value="<?=e(cfg('db.name'))?>"></label>
          <label><b>DB Kullanıcı</b><input readonly value="<?=e(cfg('db.user'))?>"></label>
          <label><b>DB Şifre</b><input readonly value="••••••••••••"></label>
        </div>

        <div class="section-head" style="margin-top:26px"><div><h2>WhatsApp Mesaj Şablonları</h2><p>Değişkenler: <code>{firma_adi}</code> <code>{yetkili}</code> <code>{sektor}</code> <code>{ilce}</code> <code>{sehir}</code> <code>{paket}</code> <code>{teklif_tutari}</code> <code>{kapora}</code> <code>{kalan_odeme}</code> <code>{takip_linki}</code> <code>{satis_temsilcisi}</code> <code>{telefon}</code></p></div><button id="saveTemplates" class="btn primary" type="button">Şablonları Kaydet</button></div>
        <div class="settings-grid" id="templateGrid">
          <?php
          $tplLabels = ['first'=>'İlk WhatsApp mesajı','detail'=>'Detaylı teklif mesajı','payment'=>'Ödeme / kapora mesajı','followup'=>'Takip mesajı','contract'=>'Sözleşme mesajı','delivery'=>'Teslim / yayın mesajı','renewal'=>'Yenileme hatırlatma','tracking'=>'Takip linki mesajı'];
          $tpls = message_templates();
          foreach ($tplLabels as $k=>$lbl): ?>
          <label class="wide-full"><b><?=e($lbl)?></b><textarea data-template="<?=e($k)?>" rows="4"><?=e($tpls[$k] ?? '')?></textarea></label>
          <?php endforeach; ?>
        </div>
      </section>

      <section id="accountPanel" class="card panel-section">
        <div class="section-head"><div><h2>Güvenlik</h2><p>Admin giriş bilgilerini değiştir. Yayında varsayılan şifreyle kalmak açık kapı.</p></div><button id="changePasswordBtn" class="btn primary" type="button">Şifreyi Değiştir</button></div>
        <div class="settings-grid">
          <label><b>Yeni Admin E-posta</b><input id="newAdminEmail" value="admin@poyraztoner.com"></label>
          <label><b>Mevcut Şifre</b><input id="currentPassword" type="password" placeholder="Mevcut şifre"></label>
          <label><b>Yeni Şifre</b><input id="newPassword" type="password" placeholder="Yeni güçlü şifre"></label>
        </div>
      </section>

      <section id="contractPanel" class="card panel-section">
        <div class="section-head">
          <div><h2>Sözleşme / Teklif / Sipariş</h2><p>Lead satırındaki “Sözleşme” butonundan müşteri bilgisi, ek istekler, ödeme, ciro ve A4 yazdırılabilir sözleşme oluşturulur.</p></div>
          <a class="btn secondary" href="api/export.php">CSV İndir</a>
        </div>
        <div class="info-grid">
          <article><b>Takip Linki</b><span>Müşteriye WhatsApp’tan gönderilir, sipariş durumunu dışarıdan görür.</span></article>
          <article><b>Dijital Onay Tikleri</b><span>WhatsApp yazılı onayı + iki taraf dijital imza işaretleri sözleşmede yazdırılır.</span></article>
          <article><b>Ek İstek Satırları</b><span>Müşterinin ek talepleri satır satır eklenir ve ciroya dahil edilir.</span></article>
        </div>
      </section>

      <section id="operationPanel" class="card panel-section">
        <div class="section-head"><div><h2>Operasyon Takibi</h2><p>Ödeme, dekont, teslim tarihi, revize hakkı, domain/hosting ve yenileme takibi için müşteri kartında alanlar kullanılır.</p></div><a class="btn secondary" href="demos.php">Demo Siteleri Yönet</a></div>
        <div class="info-grid">
          <article><b>Ödeme & Dekont</b><span>Lead/sözleşmede alınan ödeme, kalan tutar ve dekont dosya yolu tutulur.</span></article>
          <article><b>Teslim & Revize</b><span>Başlangıç, tahmini teslim, gerçek teslim ve kullanılan revize hakkı takip edilir.</span></article>
          <article><b>Domain & Hosting</b><span>Domain sahibi, sağlayıcı, bitiş tarihi, hosting bitiş tarihi ve yenileme ücreti takip edilir.</span></article>
          <article><b>Yenileme Para Kaynağı</b><span>Yenilemesi yaklaşan işler dashboard’da görünür.</span></article>
        </div>
      </section>

      <section id="importPanel" class="card panel-section import-card">
        <div class="section-head">
          <div>
            <h2>CSV ile Data İçe Aktar</h2>
            <p>Elindeki Excel/CSV listesini panele al. Aynı telefon veya Google place ID varsa tekrar kaydetmez; kara listedeki numaraları atlar.</p>
          </div>
          <a class="btn ghost" href="api/import-template.php">Örnek CSV İndir</a>
        </div>
        <form id="csvImportForm" class="import-grid" enctype="multipart/form-data">
          <label>
            CSV dosyası
            <input id="csvFile" name="csv" type="file" accept=".csv,text/csv">
            <small>Excel’den CSV UTF-8 olarak dışa aktarabilirsin. Noktalı virgül ve virgül desteklenir.</small>
          </label>
          <label>
            Varsayılan sektör
            <input id="importSector" name="default_sector" type="text" placeholder="Örn: Kombi Servisi">
          </label>
          <label>
            Varsayılan şehir
            <input id="importCity" name="default_city" type="text" placeholder="Örn: İstanbul">
          </label>
          <div class="import-help wide-full">
            <b>Kabul edilen kolonlar:</b>
            <span>İşletme Adı, Telefon, Sektör, Alt Sektör, Şehir, İlçe, Adres, Not, Durum, Website, Google Maps Linki, Puan, Yorum Sayısı</span>
          </div>
          <div class="actions-row wide-full">
            <button class="btn primary" type="submit">CSV İçeri Al</button>
            <button class="btn ghost" type="button" id="clearImportResult">Sonucu Temizle</button>
          </div>
        </form>
        <div id="importResult" class="import-result hidden"></div>
      </section>

      <section id="leadPanel" class="panel-section">
        <div class="panel-card">
          <div class="page-header">
            <div><h2>Lead Listesi</h2><div class="module-hint">Sıcak lead’ler yukarıda. İstemeyenleri “Tekrar aranmasın” yapın; kara listeye düşer.</div></div>
            <span class="chip count-chip" id="leadCount">0 kayıt</span>
          </div>
          <div class="filter-bar">
            <div class="filter-primary">
              <input id="quickSearch" type="search" placeholder="İşletme / telefon / not ara">
              <select id="statusFilter">
                <option value="">Tüm durumlar</option>
                <?php foreach (lead_pipeline_statuses() as $st): ?><option><?=e($st)?></option><?php endforeach; ?>
              </select>
              <select id="scoreFilter"><option value="">Tüm skorlar</option><option value="80">80+ sıcak</option><option value="60">60+ orta</option></select>
              <button id="toggleAdvanced" class="btn ghost sm" type="button">Gelişmiş Filtreler ▾</button>
              <button id="clearFilters" class="btn ghost sm" type="button">Temizle</button>
            </div>
            <div class="filter-advanced hidden" id="advancedFilters">
              <input id="cityFilter" type="text" placeholder="Şehir">
              <input id="sectorFilter" type="text" placeholder="Sektör">
              <select id="priorityFilter"><option value="">Tüm öncelikler</option><?php foreach(priority_options() as $p): ?><option><?=e($p)?></option><?php endforeach; ?></select>
              <select id="assignedFilter"><option value="">Tüm temsilciler</option><?php foreach($teamMembers as $tm): ?><option><?=e($tm)?></option><?php endforeach; ?></select>
              <select id="websiteFilter"><option value="">Web sitesi: hepsi</option><option value="no">Web sitesi yok</option><option value="yes">Web sitesi var</option></select>
              <select id="waFilter"><option value="">WhatsApp: hepsi</option><option value="sent">Gönderildi</option><option value="not">Gönderilmedi</option></select>
              <select id="offerFilter"><option value="">Teklif: hepsi</option><option value="sent">Teklif gönderildi</option><option value="not">Teklif gönderilmedi</option></select>
              <select id="followFilter"><option value="">Takip: hepsi</option><option value="today">Bugün aranacak</option><option value="overdue">Gecikmiş takip</option></select>
            </div>
            <div class="filter-chips" id="filterChips"></div>
          </div>
          <div class="table-wrap">
            <table class="data-table lead-table">
              <colgroup><col class="c-score"><col class="c-firma"><col class="c-iletisim"><col class="c-sektor"><col class="c-dijital"><col class="c-durum"><col class="c-takip"><col class="c-aksiyon"></colgroup>
              <thead><tr><th>Skor</th><th>Firma / Yetkili</th><th>İletişim</th><th>Sektör / Konum</th><th>Dijital</th><th>Durum</th><th>Takip</th><th>Aksiyon</th></tr></thead>
              <tbody id="leadRows"></tbody>
            </table>
          </div>
          <div id="mobileCards" class="mobile-cards"></div>
        </div>
      </section>
    </main>
  </div>


  <div id="callModal" class="modal-backdrop hidden">
    <div class="call-modal">
      <div class="modal-head">
        <div>
          <h2 id="callTitle">Arama Scripti</h2>
          <p id="callSubtitle">Müşteriyi ödeme/teklif adımına götürecek konuşma metni.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeCallModal()">×</button>
      </div>
      <div class="call-meta" id="callMeta"></div>
      <textarea id="callScriptText" class="call-script" readonly></textarea>
      <div class="modal-actions">
        <a id="callPhoneLink" class="btn primary" href="#">Telefonla Ara</a>
        <button id="copyCallScript" class="btn secondary" type="button">Metni Kopyala</button>
        <button id="markCalledBtn" class="btn ghost" type="button">Arandı İşaretle</button>
        <button id="markOfferBtn" class="btn ghost" type="button">Teklif İstedi</button>
        <button id="markNoCallBtn" class="btn danger" type="button">Tekrar Aranmasın</button>
      </div>
    </div>
  </div>

  <div id="detailModal" class="modal-backdrop hidden">
    <div class="lead-modal">
      <div class="lead-modal-head">
        <div><h2 id="detailTitle">Lead Detayı</h2><div class="sub" id="detailSub"></div></div>
        <button class="modal-close" type="button" onclick="closeDetail()">×</button>
      </div>
      <div class="tab-bar" id="detailTabs"></div>
      <div id="detailBody"></div>
      <div class="modal-save-bar">
        <button class="btn ghost" type="button" onclick="closeDetail()">Kapat</button>
        <button class="btn primary" type="button" id="detailSave">Değişiklikleri Kaydet</button>
      </div>
    </div>
  </div>

  <div id="toast" class="toast hidden"></div>
  <script>window.LEAD_APP={
    csrf:<?=json_encode($csrf)?>,
    packagePrices:<?=json_encode($packagePrices, JSON_UNESCAPED_UNICODE)?>,
    teamMembers:<?=json_encode($teamMembers, JSON_UNESCAPED_UNICODE)?>,
    statuses:<?=json_encode(lead_pipeline_statuses(), JSON_UNESCAPED_UNICODE)?>,
    priorities:<?=json_encode(priority_options(), JSON_UNESCAPED_UNICODE)?>,
    websiteQualities:<?=json_encode(website_quality_options(), JSON_UNESCAPED_UNICODE)?>,
    competitorDensities:<?=json_encode(competitor_density_options(), JSON_UNESCAPED_UNICODE)?>,
    services:<?=json_encode(requested_service_options(), JSON_UNESCAPED_UNICODE)?>,
    paymentStatuses:<?=json_encode(payment_statuses(), JSON_UNESCAPED_UNICODE)?>,
    orderStatuses:<?=json_encode(order_statuses(), JSON_UNESCAPED_UNICODE)?>,
    packages:<?=json_encode(array_map(fn($p)=>$p['label'], package_catalog($packagePrices)), JSON_UNESCAPED_UNICODE)?>
  };</script>
  <script src="assets/data.js?v=46"></script>
  <script src="assets/app.js?v=53"></script>
</body>
</html>
