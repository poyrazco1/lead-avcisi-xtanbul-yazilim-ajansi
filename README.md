# Xtanbul Yazılım — Kurumsal Site + Lead CRM v5

v5 ile ön yüz ve panel **açık, kurumsal ajans temasına** taşındı; lead paneli **detaylı bir CRM'e** dönüştürüldü ve WhatsApp gönderimi güvenilir otomatik işaretleme kazandı. PHP 8.2 uyumlu, Laravel/React/Vue yok, `.env` yok, ayarlar `config.php` + `app_settings` tablosundan yönetilir. Plesk uyumu ve mevcut login/session/CSRF/API mantığı korunur.

## v5 — Yeni tasarım
- **Ön yüz (açık kurumsal tema):** mega menülü header, güçlü hero + güven rozetleri, hizmetler, "Neden Xtanbul", 8 adımlık süreç, paketler, referanslar, blog/rehber, footer.
- **"Biz mi arayalım?" teklif formu** (`/iletisim#teklif`) → panelde otomatik lead oluşturur (`panel/api/public-lead.php`, honeypot + KVKK onayı).
- **Panel (açık CRM tema):** sol sidebar (mobilde drawer), üst bar + hızlı arama, temiz kartlar/tablo, mobilde kart görünümü, genişletilmiş dashboard.
- Responsive: mobil/tablet/masaüstünde yatay taşma yok.

## v5 — Lead CRM derinleştirme
- **Genişletilmiş lead modeli:** yetkili kişi/pozisyon, WhatsApp telefonu, e-posta, mahalle, Instagram/Facebook, web sitesi kalitesi, rakip yoğunluğu, öncelik (Soğuk/Ilık/Sıcak/Çok sıcak), müşteri olma ihtimali, ihtiyaç bilgileri (domain/hosting/logo/görsel/içerik/çok dil/randevu/online ödeme/blog/galeri), tahmini/net/indirim/kapora tutarları, teklif gönderim durumu. Eski kolonlar korunur, güvenli `ensure_column` migration.
- **`lead_activities` tablosu + `api/activities.php`:** her WhatsApp/arama/not/teklif/ödeme/durum değişikliği kayıt altına alınır (iletişim geçmişi zaman çizelgesi).
- **Sekmeli lead detay modalı:** Genel, Dijital Analiz, İhtiyaçlar, Satış & Teklif, İletişim Geçmişi, Sözleşme & Ödeme, Notlar.
- **Sağlam WhatsApp otomatiği:** "WhatsApp Gönder" → mesaj oluşur, yeni sekmede açılır, durum otomatik güncellenir, `message_count` +1, `last_contact_at` güncellenir, activity kaydı düşer, toast gösterilir. Popup engellenirse mesaj panoya kopyalanır. İleri durumdaki lead geri düşmez (ör. "Teklif istedi" iken ilk mesaj tekrar atılınca durum korunur).
- **Yönetilebilir mesaj şablonları** (Ayarlar): ilk mesaj, detaylı teklif, ödeme/kapora, takip, sözleşme, teslim, yenileme, takip linki — değişkenlerle (`{firma_adi}`, `{yetkili}`, `{paket}`, `{teklif_tutari}`, `{kapora}`, `{kalan_odeme}`, `{takip_linki}` vb.).
- **Gelişmiş lead listesi:** arama + durum + şehir + sektör + öncelik + temsilci + web sitesi var/yok + WhatsApp gönderildi/gönderilmedi + teklif + takip (bugün/gecikmiş) filtreleri; aksiyonlar "birincil / ikincil / diğer" olarak gruplu.

## Korunanlar
- Sözleşme önlü/arkalı A4 formatı, CSV import/export, Google Places lead toplama, kara liste, ödeme/sözleşme akışı, admin paneli.

## Girişler
- Site: `/`
- Site admin: `/admin.php`
- Lead panel: `/panel/login.php`

Varsayılan admin:
- E-posta: admin@poyraztoner.com
- Şifre: Admin12345!

Yayına alınca şifreyi değiştirin.
