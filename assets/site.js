/* Xtanbul Yazılım — ön yüz etkileşimleri */
(function () {
  'use strict';
  var body = document.body;

  // Header gölge (scroll)
  var header = document.getElementById('siteHeader');
  function onScroll() { if (header) header.classList.toggle('scrolled', window.scrollY > 8); }
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  // Mobil menü
  var toggle = document.getElementById('navToggle');
  var overlay = document.getElementById('navOverlay');
  function closeNav() { body.classList.remove('nav-open'); if (toggle) toggle.setAttribute('aria-expanded', 'false'); if (overlay) overlay.hidden = true; }
  function openNav() { body.classList.add('nav-open'); if (toggle) toggle.setAttribute('aria-expanded', 'true'); if (overlay) overlay.hidden = false; }
  if (toggle) toggle.addEventListener('click', function () { body.classList.contains('nav-open') ? closeNav() : openNav(); });
  if (overlay) overlay.addEventListener('click', closeNav);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeNav(); });

  // Mega menü
  var mega = document.querySelector('.has-mega');
  var trigger = mega ? mega.querySelector('.mega-trigger') : null;
  var isMobile = function () { return window.matchMedia('(max-width:900px)').matches; };
  if (mega && trigger) {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      var open = mega.classList.toggle('open');
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    // Masaüstünde hover ile aç
    mega.addEventListener('mouseenter', function () { if (!isMobile()) { mega.classList.add('open'); trigger.setAttribute('aria-expanded', 'true'); } });
    mega.addEventListener('mouseleave', function () { if (!isMobile()) { mega.classList.remove('open'); trigger.setAttribute('aria-expanded', 'false'); } });
    document.addEventListener('click', function (e) { if (!isMobile() && !mega.contains(e.target)) mega.classList.remove('open'); });
  }

  // Teklif formları (hero + iletişim) → panel/api/public-lead.php
  function bindLeadForm(form) {
    var status = form.dataset.status ? document.querySelector(form.dataset.status) : form.querySelector('.form-status');
    var btn = form.querySelector('button[type="submit"]');
    var btnLabel = btn ? btn.textContent : 'Gönder';
    function setStatus(msg, type) { if (status) { status.textContent = msg; status.className = 'form-status' + (type ? ' ' + type : ''); } }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var data = {};
      new FormData(form).forEach(function (v, k) { data[k] = v; });
      var kv = form.querySelector('[name="kvkk"]');
      data.kvkk = kv ? kv.checked : false;
      if (!data.person || !data.person.trim()) { setStatus('Lütfen ad soyad girin.', 'bad'); return; }
      if (!data.phone || !data.phone.trim()) { setStatus('Lütfen telefon numaranızı girin.', 'bad'); return; }
      if (!data.kvkk) { setStatus('Devam etmek için KVKK onayı gerekli.', 'bad'); return; }
      if (btn) { btn.disabled = true; btn.textContent = 'Gönderiliyor...'; }
      setStatus('Gönderiliyor...', '');
      fetch('/panel/api/public-lead.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(data)
      }).then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Sunucu hatası.' }; }); })
        .then(function (d) {
          if (d && d.ok) { form.reset(); setStatus('Talebiniz alındı. En kısa sürede sizi arayacağız. Teşekkürler!', 'ok'); }
          else { setStatus((d && d.error) || 'Gönderilemedi. Lütfen WhatsApp’tan yazın.', 'bad'); }
        }).catch(function () { setStatus('Bağlantı hatası. Lütfen WhatsApp’tan yazın.', 'bad'); })
        .finally(function () { if (btn) { btn.disabled = false; btn.textContent = btnLabel; } });
    });
  }
  Array.prototype.forEach.call(document.querySelectorAll('.js-lead-form'), bindLeadForm);

  // SSS akordeon (button + aria-expanded ile erişilebilir aç/kapa)
  Array.prototype.forEach.call(document.querySelectorAll('.faq-item .faq-q'), function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.parentNode;
      var open = item.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
})();
