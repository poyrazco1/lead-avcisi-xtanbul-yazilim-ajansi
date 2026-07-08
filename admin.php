<?php
require __DIR__.'/app/core.php';
session_start();
$err='';
if(isset($_POST['email'])){ if($_POST['email']===cfg('admin_email') && $_POST['password']===cfg('admin_password')){$_SESSION['admin']=true; header('Location: admin.php'); exit;} $err='Hatalı giriş'; }
if(isset($_GET['logout'])){session_destroy(); header('Location: admin.php'); exit;}
if(empty($_SESSION['admin'])){ ?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/style.css"><title>Güvenli Yönetim Girişi</title></head><body class="admin-login-page"><main class="admin-login-shell"><section class="admin-login-info"><span class="login-badge">Xtanbul Yazılım Agent</span><h1>Güvenli Yönetim Paneli</h1><p>Blog, haber, referans, logo, favicon ve site ayarlarını buradan yönet.</p><ul><li>Referans aktif/pasif kontrolü</li><li>Logo ve favicon yükleme</li><li>İçerik ve iletişim bilgisi yönetimi</li></ul></section><form class="admin-login-card" method="post" autocomplete="off"><div class="login-mark">XA</div><h2>Admin Girişi</h2><p class="muted">Bu ekran site menüsünde görünmez. Doğrudan güvenli URL üzerinden erişilir.</p><?php if($err): ?><div class="login-error"><?=e($err)?></div><?php endif; ?><label>E-posta<input name="email" type="email" placeholder="admin@..." required></label><label>Şifre<input name="password" type="password" placeholder="••••••••" required></label><button>Giriş Yap</button><small>Yayına aldıktan sonra varsayılan şifreyi mutlaka değiştir.</small></form></main></body></html><?php exit;}
$tab=$_GET['tab'] ?? 'posts';
$posts=posts_all(); $refs=references_all(); $settings=site_settings();
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_post'])){
 $id=$_POST['id']!==''?(int)$_POST['id']:-1;
 $item=['type'=>$_POST['type'],'title'=>$_POST['title'],'slug'=>slugify($_POST['slug']?:$_POST['title']),'summary'=>$_POST['summary'],'content'=>$_POST['content'],'created_at'=>$_POST['created_at']?:date('Y-m-d')];
 if($id>=0 && isset($posts[$id])) $posts[$id]=$item; else $posts[]=$item; posts_save($posts); header('Location: admin.php?tab=posts'); exit;
}
if(isset($_GET['delete_post'])){unset($posts[(int)$_GET['delete_post']]); posts_save($posts); header('Location: admin.php?tab=posts'); exit;}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_ref'])){
 $id=$_POST['id']!==''?(int)$_POST['id']:-1; $logo=upload_image('logo','referans');
 $item=['name'=>trim($_POST['name']??''),'website'=>trim($_POST['website']??''),'logo'=>$logo ?: ($_POST['old_logo']??''),'active'=>!empty($_POST['active']),'note'=>trim($_POST['note']??''),'category'=>trim($_POST['category']??'Web Tasarım')];
 if($item['name']!==''){ if($id>=0 && isset($refs[$id])) $refs[$id]=$item; else $refs[]=$item; references_save($refs); }
 header('Location: admin.php?tab=refs'); exit;
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
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/style.css"><title>Admin</title></head><body><main class="admin"><div class="admin-head"><h1>Site Yönetimi</h1><a href="?logout=1">Çıkış</a></div><div class="tabs"><a class="<?= $tab==='posts'?'active':'' ?>" href="?tab=posts">Blog / Haber</a><a class="<?= $tab==='refs'?'active':'' ?>" href="?tab=refs">Referanslar</a><a class="<?= $tab==='settings'?'active':'' ?>" href="?tab=settings">Ayarlar</a><a href="/panel/login.php">Lead Panel</a><a href="/" target="_blank">Siteyi Aç</a></div>
<?php if($tab==='posts'): ?>
<h2>Blog / Haber Yönetimi</h2><form method="post" class="editor"><input type="hidden" name="id" value="<?= isset($_GET['edit'])?(int)$_GET['edit']:'' ?>"><select name="type"><option value="blog" <?=($edit['type']??'')==='blog'?'selected':''?>>Blog</option><option value="haber" <?=($edit['type']??'')==='haber'?'selected':''?>>Haber</option></select><input name="title" placeholder="Başlık" value="<?=e($edit['title']??'')?>"><input name="slug" placeholder="slug" value="<?=e($edit['slug']??'')?>"><input name="created_at" placeholder="YYYY-MM-DD" value="<?=e($edit['created_at']??date('Y-m-d'))?>"><textarea name="summary" placeholder="Özet"><?=e($edit['summary']??'')?></textarea><textarea name="content" rows="10" placeholder="İçerik"><?=e($edit['content']??'')?></textarea><button name="save_post" value="1">Kaydet</button></form><h2>İçerikler</h2><?php foreach($posts as $i=>$p): ?><div class="admin-row"><b><?=e($p['title'])?></b><span><?=e($p['type'])?></span><a href="?tab=posts&edit=<?=$i?>">Düzenle</a><a href="?tab=posts&delete_post=<?=$i?>" onclick="return confirm('Silinsin mi?')">Sil</a></div><?php endforeach; ?>
<?php elseif($tab==='refs'): ?>
<h2>Referans Yönetimi</h2><p class="muted">İsim, logo, web site adresi ve aktif/pasif durumunu buradan yönet. Aktif olmayan referans sitede görünmez.</p><form method="post" class="editor" enctype="multipart/form-data"><input type="hidden" name="id" value="<?= isset($_GET['edit_ref'])?(int)$_GET['edit_ref']:'' ?>"><input type="hidden" name="old_logo" value="<?=e($editRef['logo']??'')?>"><input name="name" placeholder="Referans adı" value="<?=e($editRef['name']??'')?>"><input name="website" placeholder="https://..." value="<?=e($editRef['website']??'')?>"><input name="category" placeholder="Kategori / hizmet türü" value="<?=e($editRef['category']??'Web Tasarım')?>"><textarea name="note" placeholder="Kısa açıklama / yapılan iş"><?=e($editRef['note']??'')?></textarea><label class="file-label">Logo yükle <input type="file" name="logo" accept="image/*,.svg,.ico"></label><?php if(!empty($editRef['logo'])): ?><img class="preview-logo" src="<?=e($editRef['logo'])?>" alt="logo"><?php endif; ?><label class="switch-line"><input type="checkbox" name="active" value="1" <?= !isset($editRef) || !empty($editRef['active'])?'checked':'' ?>> Aktif olarak sitede göster</label><button name="save_ref" value="1">Referansı Kaydet</button></form><h2>Referanslar</h2><?php foreach($refs as $i=>$r): ?><div class="admin-row ref-row"><b><?=e($r['name'])?></b><span class="status <?=!empty($r['active'])?'on':'off'?>"><?=!empty($r['active'])?'Aktif':'Pasif'?></span><a href="?tab=refs&toggle_ref=<?=$i?>"><?=!empty($r['active'])?'Pasif Yap':'Aktif Yap'?></a><a href="?tab=refs&edit_ref=<?=$i?>">Düzenle</a><a href="?tab=refs&delete_ref=<?=$i?>" onclick="return confirm('Silinsin mi?')">Sil</a></div><?php endforeach; ?>
<?php elseif($tab==='settings'): $settings=site_settings(); ?>
<h2>Site Ayarları</h2><?php if(isset($_GET['saved'])): ?><p class="ok">Ayarlar kaydedildi.</p><?php endif; ?><form method="post" class="editor" enctype="multipart/form-data"><input name="company_name" placeholder="Firma adı" value="<?=e($settings['company_name'])?>"><input name="contact_phone" placeholder="Cep telefonu" value="<?=e($settings['contact_phone'])?>"><input name="contact_whatsapp" placeholder="WhatsApp numarası" value="<?=e($settings['contact_whatsapp'])?>"><input name="contact_email" placeholder="E-posta" value="<?=e($settings['contact_email'])?>"><textarea name="contact_address" placeholder="Adres"><?=e($settings['contact_address'])?></textarea><input name="maps_url" placeholder="Google Maps URL" value="<?=e($settings['maps_url'])?>"><input name="instagram" placeholder="Instagram URL" value="<?=e($settings['instagram']??'')?>"><input name="linkedin" placeholder="LinkedIn URL" value="<?=e($settings['linkedin']??'')?>"><input name="hero_badge" placeholder="Hero rozet" value="<?=e($settings['hero_badge'])?>"><textarea name="hero_title" placeholder="Ana başlık"><?=e($settings['hero_title'])?></textarea><textarea name="hero_desc" placeholder="Ana açıklama"><?=e($settings['hero_desc'])?></textarea><textarea name="cta_title" placeholder="CTA başlığı"><?=e($settings['cta_title']??'')?></textarea><textarea name="cta_desc" placeholder="CTA açıklaması"><?=e($settings['cta_desc']??'')?></textarea><div class="upload-grid"><label class="file-label">Logo yükle <input type="file" name="site_logo" accept="image/*,.svg"></label><label class="file-label">Favicon yükle <input type="file" name="site_favicon" accept="image/*,.svg,.ico"></label></div><div class="current-assets"><span>Mevcut logo:</span><img class="preview-logo" src="<?=e($settings['logo_path'])?>" alt="logo"><span>Mevcut favicon:</span><img class="preview-favicon" src="<?=e($settings['favicon_path'])?>" alt="favicon"></div><button name="save_settings" value="1">Ayarları Kaydet</button></form>
<?php endif; ?>
</main></body></html>
