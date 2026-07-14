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
})();

/* Scroll reveal — framer-motion fadeUp benzeri, vanilla IntersectionObserver.
   Güvenli: JS yoksa içerik görünür kalır; ekranda olan (üstteki) öğelere dokunmaz;
   prefers-reduced-motion'da devre dışı. Yalnızca ön yüzde (body.site-front). */
(function () {
  'use strict';
  if (!document.body || document.body.className.indexOf('site-front') === -1) return;
  if (!('IntersectionObserver' in window)) return;
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var selector = '.section-head, .feature-card, .why-card, .step-card, .pkg-card, .ref-card, .post-card, .stat-cards article, .hero-form-card, .article, .cta-inner, .split-2 > *';
  var els = Array.prototype.slice.call(document.querySelectorAll(selector));
  if (!els.length) return;

  var vh = window.innerHeight || document.documentElement.clientHeight;
  var toWatch = [];
  els.forEach(function (el, i) {
    var top = el.getBoundingClientRect().top;
    if (top < vh * 0.92) return; // zaten görünürse gizleme (FOUC yok)
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity .6s ease, transform .6s ease';
    el.style.transitionDelay = ((i % 3) * 0.08) + 's';
    el.style.willChange = 'opacity, transform';
    toWatch.push(el);
  });

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      var el = e.target;
      el.style.opacity = '1';
      el.style.transform = 'none';
      io.unobserve(el);
      setTimeout(function () { el.style.willChange = 'auto'; }, 700);
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -70px 0px' });

  toWatch.forEach(function (el) { io.observe(el); });
})();
