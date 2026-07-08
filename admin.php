<?php
require __DIR__.'/app/core.php';
session_start();
$err='';
if(isset($_POST['email'])){ if($_POST['email']===cfg('admin_email') && $_POST['password']===cfg('admin_password')){$_SESSION['admin']=true; header('Location: admin.php'); exit;} $err='Hatalı giriş'; }
if(isset($_GET['logout'])){session_destroy(); header('Location: admin.php'); exit;}
if(empty($_SESSION['admin'])){ ?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/style.css?v=8"><title>Güvenli Yönetim Girişi</title></head><body class="admin-login-page"><main class="admin-login-shell"><section class="admin-login-info"><span class="login-badge">Xtanbul Yazılım Agent</span><h1>Güvenli Yönetim Paneli</h1><p>Blog, haber, referans, logo, favicon ve site ayarlarını buradan yönet.</p><ul><li>Referans aktif/pasif kontrolü</li><li>Logo ve favicon yükleme</li><li>İçerik ve iletişim bilgisi yönetimi</li></ul></section><form class="admin-login-card" method="post" autocomplete="off"><div class="login-mark">XA</div><h2>Admin Girişi</h2><p class="muted">Bu ekran site menüsünde görünmez. Doğrudan güvenli URL üzerinden erişilir.</p><?php if($err): ?><div class="login-error"><?=e($err)?></div><?php endif; ?><label>E-posta<input name="email" type="email" placeholder="admin@..." required></label><label>Şifre<input name="password" type="password" placeholder="••••••••" required></label><button>Giriş Yap</button><small>Yayına aldıktan sonra varsayılan şifreyi mutlaka değiştir.</small></form></main></body></html><?php exit;}

$tab=$_GET['tab'] ?? 'dashboard';
$posts=posts_all(); $refs=references_all(); $settings=site_settings();

// ---- POST/GET işleyicileri (backend mantığı korunur) ----
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_post'])){
 $id=$_POST['id']!==''?(int)$_POST['id']:-1;
 $item=['type'=>$_POST['type'],'title'=>$_POST['title'],'slug'=>slugify($_POST['slug']?:$_POST['title']),'summary'=>$_POST['summary'],'content'=>$_POST['content'],'created_at'=>$_POST['created_at']?:date('Y-m-d')];
 if($id>=0 && isset($posts[$id])) $posts[$id]=$item; else $posts[]=$item; posts_save($posts); header('Location: admin.php?tab=posts&saved=1'); exit;
}
if(isset($_GET['delete_post'])){unset($posts[(int)$_GET['delete_post']]); posts_save($posts); header('Location: admin.php?tab=posts'); exit;}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_ref'])){
 $id=$_POST['id']!==''?(int)$_POST['id']:-1; $logo=upload_image('logo','referans');
 $item=['name'=>trim($_POST['name']??''),'website'=>trim($_POST['website']??''),'logo'=>$logo ?: ($_POST['old_logo']??''),'active'=>!empty($_POST['active']),'note'=>trim($_POST['note']??''),'category'=>trim($_POST['category']??'Web Tasarım')];
 if($item['name']!==''){ if($id>=0 && isset($refs[$id])) $refs[$id]=$item; else $refs[]=$item; references_save($refs); }
 header('Location: admin.php?tab=refs&saved=1'); exit;
}
if(isset($_GET['toggle_ref']) && isset($refs[(int)$_GET['toggle_ref']])){ $i=(int)$_GET['toggle_ref']; $refs[$i]['active']=empty($refs[$i]['active']); references_save($refs); header('Location: admin.php?tab=refs'); exit; }
if(isset($_GET['delete_ref'])){unset($refs[(int)$_GET['delete_ref']]); references_save($refs); header('Location: admin.php?tab=refs'); exit;}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_settings'])){
 $logo=upload_image('site_logo','site-logo'); $fav=upload_image('site_favicon','favicon');
 $new=[
  'company_name'=>trim($_POST['company_name']??$settings['company_name']),
  'contact_phone'=>trim($_POST['contact_phone']??$settings['contact_phone']),
  'contact_whatsapp'=>trim($_POST['contact_whatsapp']??$settings['contact_whatsapp']),
  'contact_email'=>trim($_POST['contact_email']??$settings['contact_email']),
  'contact_address'=>trim($_POST['contact_address']??$settings['contact_address']),
  'maps_url'=>trim($_POST['maps_url']??$settings['maps_url']),
  'instagram'=>trim($_POST['instagram']??''),
  'linkedin'=>trim($_POST['linkedin']??''),
  'hero_badge'=>trim($_POST['hero_badge']??$settings['hero_badge']),
  'hero_title'=>trim($_POST['hero_title']??$settings['hero_title']),
  'hero_desc'=>trim($_POST['hero_desc']??$settings['hero_desc']),
  'cta_title'=>trim($_POST['cta_title']??($settings['cta_title']??'')),
  'cta_desc'=>trim($_POST['cta_desc']??($settings['cta_desc']??'')),
 ];
 if($logo) $new['logo_path']=$logo; if($fav) $new['favicon_path']=$fav;
 site_settings_save($new); header('Location: admin.php?tab=settings&saved=1'); exit;
}
$edit=isset($_GET['edit'])&&isset($posts[(int)$_GET['edit']])?$posts[(int)$_GET['edit']]:null;
$editRef=isset($_GET['edit_ref'])&&isset($refs[(int)$_GET['edit_ref']])?$refs[(int)$_GET['edit_ref']]:null;

$blogCount=count(array_filter($posts,fn($p)=>($p['type']??'blog')==='blog'));
$haberCount=count(array_filter($posts,fn($p)=>($p['type']??'')==='haber'));
$activeRefs=count(array_filter($refs,fn($r)=>!empty($r['active'])));
$navItems=[['dashboard','Panel','▦'],['posts','Blog / Haber','✎'],['refs','Referanslar','★'],['settings','Site Ayarları','⚙']];
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/style.css?v=8"><title>Site Yönetimi | Xtanbul</title></head>
<body class="adm-body">
<div class="adm-overlay" id="admOverlay"></div>
<div class="adm-shell">
  <aside class="adm-sidebar" id="admSidebar">
    <div class="adm-brand"><span class="adm-mark">XA</span><div><strong>Site Yönetimi</strong><span>Xtanbul Yazılım</span></div></div>
    <nav class="adm-nav">
      <?php foreach($navItems as $it): ?><a class="<?=$tab===$it[0]?'active':''?>" href="?tab=<?=$it[0]?>"><i><?=$it[2]?></i><?=e($it[1])?></a><?php endforeach; ?>
      <div class="adm-nav-sep">Bağlantılar</div>
      <a href="/panel/login.php"><i>◆</i>Lead Panel (CRM)</a>
      <a href="/" target="_blank"><i>↗</i>Siteyi Aç</a>
      <a href="?logout=1" class="adm-logout"><i>⎋</i>Çıkış</a>
    </nav>
  </aside>

  <main class="adm-main">
    <header class="adm-topbar">
      <button class="adm-menu-btn" id="admMenuBtn" aria-label="Menü"><span></span><span></span><span></span></button>
      <div><span class="adm-eyebrow">Site İçerik Yönetimi</span><h1><?php foreach($navItems as $it) if($it[0]===$tab) echo e($it[1]); ?></h1></div>
      <a class="btn ghost sm" href="/" target="_blank">Siteyi Görüntüle ↗</a>
    </header>

    <?php if(isset($_GET['saved'])): ?><div class="adm-toast">✓ Değişiklikler kaydedildi.</div><?php endif; ?>

    <?php if($tab==='dashboard'): ?>
    <section class="adm-stats">
      <article class="adm-stat"><span>Toplam İçerik</span><b><?=count($posts)?></b><small><?=$blogCount?> blog · <?=$haberCount?> haber</small></article>
      <article class="adm-stat"><span>Referans</span><b><?=count($refs)?></b><small><?=$activeRefs?> aktif</small></article>
      <article class="adm-stat"><span>Blog Yazısı</span><b><?=$blogCount?></b><small>yayında</small></article>
      <article class="adm-stat"><span>Haber</span><b><?=$haberCount?></b><small>yayında</small></article>
    </section>
    <div class="adm-quick-grid">
      <a class="adm-quick" href="?tab=posts&edit=new"><i>✎</i><b>Yeni Blog / Haber</b><span>İçerik ekle veya düzenle</span></a>
      <a class="adm-quick" href="?tab=refs"><i>★</i><b>Referans Yönet</b><span>Logo, link, aktif/pasif</span></a>
      <a class="adm-quick" href="?tab=settings"><i>⚙</i><b>Site Ayarları</b><span>Hero, iletişim, marka</span></a>
      <a class="adm-quick" href="/panel/login.php"><i>◆</i><b>Lead Panel</b><span>CRM & satış paneli</span></a>
    </div>
    <section class="adm-card">
      <div class="adm-card-head"><h2>Son İçerikler</h2><a class="btn ghost sm" href="?tab=posts">Tümü</a></div>
      <?php if(!$posts): ?><p class="muted">Henüz içerik yok.</p><?php else: foreach(array_slice(array_reverse($posts,true),0,5,true) as $i=>$p): ?>
        <div class="adm-list-row"><span class="badge-type <?=($p['type']??'blog')==='haber'?'haber':'blog'?>"><?=e($p['type']??'blog')?></span><b class="adm-ell"><?=e($p['title'])?></b><small><?=e($p['created_at']??'')?></small><a class="btn ghost mini" href="?tab=posts&edit=<?=$i?>">Düzenle</a></div>
      <?php endforeach; endif; ?>
    </section>

    <?php elseif($tab==='posts'): ?>
    <div class="adm-two-col">
      <section class="adm-card">
        <div class="adm-card-head"><h2><?= $edit? 'İçeriği Düzenle' : 'Yeni İçerik' ?></h2><?php if($edit): ?><a class="btn ghost mini" href="?tab=posts">+ Yeni</a><?php endif; ?></div>
        <form method="post" class="adm-form">
          <input type="hidden" name="id" value="<?= isset($_GET['edit'])&&$_GET['edit']!=='new'?(int)$_GET['edit']:'' ?>">
          <label class="fld">Tür<select name="type"><option value="blog" <?=($edit['type']??'')==='blog'?'selected':''?>>Blog</option><option value="haber" <?=($edit['type']??'')==='haber'?'selected':''?>>Haber</option></select></label>
          <label class="fld">Tarih<input name="created_at" placeholder="YYYY-MM-DD" value="<?=e($edit['created_at']??date('Y-m-d'))?>"></label>
          <label class="fld full">Başlık<input name="title" placeholder="İçerik başlığı" value="<?=e($edit['title']??'')?>" required></label>
          <label class="fld full">Slug (URL)<input name="slug" placeholder="otomatik oluşur" value="<?=e($edit['slug']??'')?>"></label>
          <label class="fld full">Özet<textarea name="summary" rows="2" placeholder="Kısa özet"><?=e($edit['summary']??'')?></textarea></label>
          <label class="fld full">İçerik<textarea name="content" rows="9" placeholder="İçerik metni"><?=e($edit['content']??'')?></textarea></label>
          <div class="adm-actions full"><button class="btn primary" name="save_post" value="1">Kaydet</button></div>
        </form>
      </section>
      <section class="adm-card">
        <div class="adm-card-head"><h2>İçerikler (<?=count($posts)?>)</h2></div>
        <input class="adm-filter" id="postFilter" type="search" placeholder="İçeriklerde ara...">
        <div class="adm-list" id="postList">
          <?php if(!$posts): ?><p class="muted">Henüz içerik yok.</p><?php else: foreach(array_reverse($posts,true) as $i=>$p): ?>
          <div class="adm-list-row" data-search="<?=e(strtolower($p['title'].' '.($p['type']??'')))?>">
            <span class="badge-type <?=($p['type']??'blog')==='haber'?'haber':'blog'?>"><?=e($p['type']??'blog')?></span>
            <b class="adm-ell" title="<?=e($p['title'])?>"><?=e($p['title'])?></b>
            <small><?=e($p['created_at']??'')?></small>
            <a class="btn ghost mini" href="?tab=posts&edit=<?=$i?>">Düzenle</a>
            <a class="btn danger mini" href="?tab=posts&delete_post=<?=$i?>" onclick="return confirm('Silinsin mi?')">Sil</a>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </section>
    </div>

    <?php elseif($tab==='refs'): ?>
    <div class="adm-two-col">
      <section class="adm-card">
        <div class="adm-card-head"><h2><?= $editRef? 'Referansı Düzenle' : 'Yeni Referans' ?></h2><?php if($editRef): ?><a class="btn ghost mini" href="?tab=refs">+ Yeni</a><?php endif; ?></div>
        <form method="post" class="adm-form" enctype="multipart/form-data">
          <input type="hidden" name="id" value="<?= isset($_GET['edit_ref'])?(int)$_GET['edit_ref']:'' ?>">
          <input type="hidden" name="old_logo" value="<?=e($editRef['logo']??'')?>">
          <label class="fld full">Referans adı<input name="name" placeholder="Firma / marka adı" value="<?=e($editRef['name']??'')?>" required></label>
          <label class="fld">Web sitesi<input name="website" placeholder="https://..." value="<?=e($editRef['website']??'')?>"></label>
          <label class="fld">Kategori<input name="category" placeholder="Web Tasarım" value="<?=e($editRef['category']??'Web Tasarım')?>"></label>
          <label class="fld full">Açıklama<textarea name="note" rows="2" placeholder="Yapılan iş / kısa not"><?=e($editRef['note']??'')?></textarea></label>
          <label class="fld full file-label">Logo yükle<input type="file" name="logo" accept="image/*,.svg,.ico"></label>
          <?php if(!empty($editRef['logo'])): ?><div class="fld full"><img class="preview-logo" src="<?=e($editRef['logo'])?>" alt="logo"></div><?php endif; ?>
          <label class="fld full switch-line"><input type="checkbox" name="active" value="1" <?= !isset($editRef) || !empty($editRef['active'])?'checked':'' ?>> Aktif olarak sitede göster</label>
          <div class="adm-actions full"><button class="btn primary" name="save_ref" value="1">Referansı Kaydet</button></div>
        </form>
      </section>
      <section class="adm-card">
        <div class="adm-card-head"><h2>Referanslar (<?=count($refs)?> · <?=$activeRefs?> aktif)</h2></div>
        <div class="adm-ref-grid">
          <?php if(!$refs): ?><p class="muted">Henüz referans yok.</p><?php else: foreach($refs as $i=>$r): ?>
          <article class="adm-ref-card">
            <div class="adm-ref-logo"><?php if(!empty($r['logo'])): ?><img src="<?=e($r['logo'])?>" alt="logo"><?php else: ?><?=e(first_letter($r['name']))?><?php endif; ?></div>
            <b class="adm-ell"><?=e($r['name'])?></b>
            <small class="adm-ell"><?=e($r['category']??'Web Tasarım')?></small>
            <span class="status <?=!empty($r['active'])?'on':'off'?>"><?=!empty($r['active'])?'Aktif':'Pasif'?></span>
            <div class="adm-ref-actions">
              <a class="btn ghost mini" href="?tab=refs&toggle_ref=<?=$i?>"><?=!empty($r['active'])?'Pasifle':'Aktifle'?></a>
              <a class="btn secondary mini" href="?tab=refs&edit_ref=<?=$i?>">Düzenle</a>
              <a class="btn danger mini" href="?tab=refs&delete_ref=<?=$i?>" onclick="return confirm('Silinsin mi?')">Sil</a>
            </div>
          </article>
          <?php endforeach; endif; ?>
        </div>
      </section>
    </div>

    <?php elseif($tab==='settings'): $settings=site_settings(); ?>
    <form method="post" class="adm-settings" enctype="multipart/form-data">
      <section class="adm-card">
        <div class="adm-card-head"><h2>İletişim Bilgileri</h2></div>
        <div class="adm-form">
          <label class="fld full">Firma adı<input name="company_name" value="<?=e($settings['company_name'])?>"></label>
          <label class="fld">Telefon<input name="contact_phone" value="<?=e($settings['contact_phone'])?>"></label>
          <label class="fld">WhatsApp<input name="contact_whatsapp" value="<?=e($settings['contact_whatsapp'])?>"></label>
          <label class="fld full">E-posta<input name="contact_email" value="<?=e($settings['contact_email'])?>"></label>
          <label class="fld full">Adres<textarea name="contact_address" rows="2"><?=e($settings['contact_address'])?></textarea></label>
          <label class="fld full">Google Maps URL<input name="maps_url" value="<?=e($settings['maps_url'])?>"></label>
          <label class="fld">Instagram<input name="instagram" value="<?=e($settings['instagram']??'')?>"></label>
          <label class="fld">LinkedIn<input name="linkedin" value="<?=e($settings['linkedin']??'')?>"></label>
        </div>
      </section>
      <section class="adm-card">
        <div class="adm-card-head"><h2>Hero & CTA Metinleri</h2></div>
        <div class="adm-form">
          <label class="fld full">Hero rozet<input name="hero_badge" value="<?=e($settings['hero_badge'])?>"></label>
          <label class="fld full">Hero başlık (SEO)<textarea name="hero_title" rows="2"><?=e($settings['hero_title'])?></textarea></label>
          <label class="fld full">Hero açıklama<textarea name="hero_desc" rows="2"><?=e($settings['hero_desc'])?></textarea></label>
          <label class="fld full">CTA başlığı<textarea name="cta_title" rows="2"><?=e($settings['cta_title']??'')?></textarea></label>
          <label class="fld full">CTA açıklaması<textarea name="cta_desc" rows="2"><?=e($settings['cta_desc']??'')?></textarea></label>
        </div>
      </section>
      <section class="adm-card">
        <div class="adm-card-head"><h2>Logo & Favicon</h2></div>
        <div class="adm-form">
          <label class="fld file-label">Logo yükle<input type="file" name="site_logo" accept="image/*,.svg"></label>
          <label class="fld file-label">Favicon yükle<input type="file" name="site_favicon" accept="image/*,.svg,.ico"></label>
          <div class="fld current-assets"><span>Mevcut logo</span><img class="preview-logo" src="<?=e($settings['logo_path'])?>" alt="logo"></div>
          <div class="fld current-assets"><span>Mevcut favicon</span><img class="preview-favicon" src="<?=e($settings['favicon_path'])?>" alt="favicon"></div>
        </div>
      </section>
      <div class="adm-save-bar"><button class="btn primary" name="save_settings" value="1">Ayarları Kaydet</button></div>
    </form>
    <?php endif; ?>
  </main>
</div>
<script>
(function(){
  var b=document.getElementById('admMenuBtn'), s=document.getElementById('admSidebar'), o=document.getElementById('admOverlay');
  function close(){document.body.classList.remove('adm-open');} function open(){document.body.classList.add('adm-open');}
  if(b) b.addEventListener('click',function(){document.body.classList.contains('adm-open')?close():open();});
  if(o) o.addEventListener('click',close);
  var pf=document.getElementById('postFilter');
  if(pf) pf.addEventListener('input',function(){var q=this.value.toLowerCase();document.querySelectorAll('#postList .adm-list-row').forEach(function(r){r.style.display=(r.dataset.search||'').includes(q)?'':'none';});});
})();
</script>
</body></html>
