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
  <title>Lead Avcısı Panel v4.8</title>
  <link rel="stylesheet" href="assets/style.css?v=48">
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-mark">LA</div>
        <div><strong>Lead Avcısı</strong><span>Ajans CRM v4.8</span></div>
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

    <main class="main">
      <header class="topbar">
        <div>
          <span class="eyebrow">Xtanbul Ajans Operasyon Paneli</span>
          <h1>Lead Toplama + Satış CRM</h1>
          <p>Lead toplama, arama, WhatsApp, teklif, sözleşme, ödeme ve teslim süreci artık modül modül yönetilir.</p>
        </div>
        <div class="topbar-actions"><div class="user-pill">admin@poyraztoner.com</div><a class="btn ghost mini" href="../" target="_blank">Siteyi Aç</a></div>
      </header>

      <section class="quick-actions">
        <button class="quick-card" type="button" data-jump="#searchPanel"><b>Yeni Lead Tara</b><span>Sektör + şehir + ilçe seç</span></button>
        <button class="quick-card" type="button" data-jump="#leadPanel"><b>Leadleri Yönet</b><span>Ara, WhatsApp at, not al</span></button>
        <button class="quick-card" type="button" data-jump="#contractPanel"><b>Teklif / Sözleşme</b><span>A4 çıktı ve takip linki</span></button>
        <button class="quick-card" type="button" data-jump="#operationPanel"><b>Operasyon</b><span>Ödeme, revize, teslim</span></button>
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

      <section id="searchPanel" class="card search-card panel-section lead-studio">
        <div class="studio-hero">
          <div>
            <span class="eyebrow">Lead Toplama Stüdyosu</span>
            <h2>Dağınık seçim yok: sektör, bölge ve teklif akışı tek ekranda.</h2>
            <p>Önce ne arayacağını seç, sonra nerede arayacağını seç, son adımda teklif paketini belirle. Sağdaki hızlı kontrolde aramaya gidecek tüm seçimleri net görürsün.</p>
          </div>
          <div class="studio-badges">
            <span>✓ API key gizli</span>
            <span>✓ Web sitesi olan elenir</span>
            <span>✓ Telefonu olan kaydolur</span>
          </div>
        </div>

        <div class="lead-studio-grid">
          <div class="studio-main">
            <article class="studio-step">
              <div class="step-title"><span>1</span><div><h3>Sektör ve hizmet seç</h3><p>Ana kategori, alt kategori veya direkt meslek seç. Hiç kategori seçmeden sadece kelimeyle de arayabilirsin.</p></div></div>
              <div class="selector-grid three">
                <label class="select-card">
                  <b>Ana kategori</b><small>çoklu seçim</small>
                  <select id="mainCategorySelect" multiple size="9"></select>
                </label>
                <label class="select-card">
                  <b>Alt kategori</b><small>çoklu seçim</small>
                  <select id="subCategorySelect" multiple size="9"></select>
                </label>
                <label class="select-card">
                  <b>Alt alt kategori / meslek</b><small>en temiz sonuç</small>
                  <select id="microSectorSelect" multiple size="9"></select>
                </label>
              </div>
              <label class="text-card">
                <b>Serbest arama kelimeleri</b>
                <textarea id="customKeywords" rows="4" placeholder="Kategori seçmeden buraya yazabilirsin. Örn:&#10;kombi servisi&#10;halı yıkama&#10;CNC torna&#10;güzellik salonu"></textarea>
                <small>Virgül, noktalı virgül veya satır satır yaz. Kategori seçimiyle birlikte de çalışır.</small>
              </label>
              <div class="studio-actions small-actions"><button id="clearSectors" class="btn ghost mini" type="button">Sektörleri Temizle</button></div>
            </article>

            <article class="studio-step">
              <div class="step-title"><span>2</span><div><h3>Şehir ve ilçe seç</h3><p>Birden fazla şehir ve birden fazla ilçe seçebilirsin. İlçe seçmezsen şehir geneli aranır.</p></div></div>
              <div class="selector-grid region-grid">
                <label class="select-card">
                  <b>Şehirler</b><small>çoklu seçim</small>
                  <select id="citySelect" multiple size="10"></select>
                </label>
                <label class="select-card">
                  <b>İlçeler</b><small>tıkla seç / tıkla kaldır</small>
                  <select id="districtSelect" multiple size="10"></select>
                </label>
                <label class="text-card manual-region">
                  <b>Manuel ilçe / mahalle</b>
                  <textarea id="manualDistricts" rows="6" placeholder="Örn:&#10;Bağcılar, Esenler&#10;Ankara/Çankaya&#10;İzmir/Bornova"></textarea>
                  <small>Şehir/İlçe formatı daha net sonuç verir.</small>
                </label>
              </div>
              <div class="studio-actions"><button id="selectAllDistricts" class="btn secondary mini" type="button">Listelenen Tüm İlçeleri Seç</button><button id="clearDistricts" class="btn ghost mini" type="button">İlçeleri Temizle</button></div>
            </article>

            <article class="studio-step">
              <div class="step-title"><span>3</span><div><h3>Teklif ve mesaj ayarı</h3><p>Toplanan lead için WhatsApp mesajı, arama scripti ve teklif/sözleşme akışı bu bilgilerle oluşur.</p></div></div>
              <div class="selector-grid offer-grid-new">
                <label class="input-card"><b>Hedef kayıt limiti</b><input id="limitInput" type="number" min="10" max="5000" value="250"><small>Limit dolana kadar kombinasyonlar sırayla gezilir.</small></label>
                <label class="input-card"><b>Teklif paketi</b><select id="packageSelect"><option value="onepage" selected>Tek sayfalık HTML web sitesi</option><option value="onepage_appointment">Tek sayfalı + randevu alabilen web sitesi</option><option value="multipage">Çok sayfalı web sitesi</option><option value="multipage_appointment">Çok sayfalı + randevu alabilen web sitesi</option><option value="multipage_panel">Çok sayfalı + yönetim panelli web sitesi</option><option value="full_panel_appointment">Yönetim paneli + randevu sistemi olan web sitesi</option></select></label>
                <label class="input-card"><b>Ödeme adımı</b><input id="paymentInput" type="text" value="%50 kapora ile başlıyoruz"></label>
              </div>
              <div class="offer-box compact-offer">
                <div><h3>Mesajda gösterilecek ek hizmetler</h3><p>Mesaj kısa kalır ama müşteriye üst paket yolu açar.</p></div>
                <div class="checkbox-list">
                  <label><input class="consulting-check" type="checkbox" value="ads" checked> Reklam danışmanlığı</label>
                  <label><input class="consulting-check" type="checkbox" value="ecommerce" checked> E-ticaret danışmanlığı</label>
                  <label><input class="consulting-check" type="checkbox" value="social" checked> Sosyal medya danışmanlığı</label>
                  <label><input id="multiLangCheck" type="checkbox" checked> Çok dilli web sitesi</label>
                </div>
              </div>
            </article>
          </div>

          <aside class="studio-review">
            <div class="review-card sticky-review">
              <div class="review-head"><b>Hızlı kontrol</b><span>Aramadan önce son bakış</span></div>
              <p id="selectionSummary">Seçimler hazırlanıyor...</p>
              <div class="review-tip">Seçili öğeye tıklayınca seçim kalkar. ✓ seçili, □ seçili değil.</div>
              <div class="actions-row vertical-actions">
                <button id="startSearch" class="btn primary" type="button">Lead Toplamaya Başla</button>
                <button id="stopSearch" class="btn danger hidden" type="button">Durdur</button>
                <button id="refreshLeads" class="btn ghost" type="button">Listeyi Yenile</button>
                <a class="btn secondary" href="api/export.php">Excel / CSV İndir</a>
              </div>
              <div id="progressBox" class="progress-box hidden"><div class="bar"><span id="progressBar"></span></div><p id="progressText">Hazırlanıyor...</p></div>
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

      <section id="leadPanel" class="card panel-section">
        <div class="section-head">
          <div>
            <h2>Lead Listesi</h2><div class="module-hint">Liste artık sadece burada görünür; diğer modüller ekranı kirletmez.</div>
            <p>Sıcak lead’ler skoruna göre yukarı çıkar. İstemeyenleri “Tekrar aranmasın” yap; kara listeye düşer.</p>
          </div>
          <div class="filter-row">
            <input id="quickSearch" type="search" placeholder="İşletme / telefon / not ara">
            <select id="statusFilter">
              <option value="">Tüm durumlar</option>
              <option>Aranmadı</option><option>WhatsApp gönderildi</option><option>Arandı</option><option>Cevap bekleniyor</option><option>Teklif istedi</option><option>Ödeme linki gönderildi</option><option>Ödeme bekleniyor</option><option>Kapora alındı</option><option>Müşteri oldu</option><option>İlgilenmedi</option><option>Tekrar aranmasın</option>
            </select>
            <select id="scoreFilter"><option value="">Tüm skorlar</option><option value="80">80+ sıcak</option><option value="60">60+ orta</option></select>
          </div>
        </div>
        <div class="table-wrap">
          <table class="lead-table">
            <thead><tr><th>Skor</th><th>İşletme</th><th>Sektör</th><th>Bölge</th><th>Telefon</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody id="leadRows"></tbody>
          </table>
        </div>
        <div id="mobileCards" class="mobile-cards"></div>
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

  <div id="toast" class="toast hidden"></div>
  <script>window.LEAD_APP={csrf:<?=json_encode($csrf)?>, packagePrices:<?=json_encode($packagePrices, JSON_UNESCAPED_UNICODE)?>, teamMembers:<?=json_encode($teamMembers, JSON_UNESCAPED_UNICODE)?>};</script>
  <script src="assets/data.js?v=46"></script>
  <script src="assets/app.js?v=46"></script>
</body>
</html>
