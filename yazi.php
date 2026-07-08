<?php require __DIR__.'/app/core.php';
$slug=$_GET['slug']??''; $post=null;
foreach(posts_all() as $p){ if(($p['slug']??'')===$slug){$post=$p; break;} }
if(!$post){
  http_response_code(404);
  header_html('blog',['title'=>'Yazı bulunamadı | '.site_settings()['company_name'],'desc'=>'Aradığınız yazı bulunamadı.']);
  ?>
  <section class="section error-section"><div class="wrap error-wrap">
    <span class="eyebrow center">Hata 404</span>
    <h1 class="error-code">Yazı bulunamadı</h1>
    <p class="error-lead">Aradığınız içerik taşınmış veya kaldırılmış olabilir. Tüm yazılara göz atabilir ya da anasayfaya dönebilirsiniz.</p>
    <div class="hero-actions" style="justify-content:center">
      <a class="btn btn-primary btn-lg" href="/blog">Tüm Yazılar</a>
      <a class="btn btn-secondary btn-lg" href="/">Anasayfaya Dön</a>
    </div>
  </div></section>
  <?php
  footer_html(); exit;
}
$type=$post['type']??'blog'; $isHaber=$type==='haber';
header_html('blog',['title'=>$post['title'].' | Xtanbul Yazılım Agent','desc'=>$post['summary']]);
page_hero([
  'eyebrow'=>$isHaber?'Haber':'Blog / Rehber',
  'title'=>$post['title'],
  'desc'=>$post['summary'],
  'crumbs'=>[['label'=>'Anasayfa','url'=>'/'],['label'=>$isHaber?'Haberler':'Blog / Rehber','url'=>$isHaber?'/haberler':'/blog'],['label'=>$post['title']]],
]);
?>
<section class="section"><article class="wrap article">
  <?php if(!empty($post['created_at'])): ?><small><?=e($post['created_at'])?></small><?php endif; ?>
  <div class="content"><?=nl2br(e($post['content']))?></div>
  <p class="mt"><a class="post-more" href="/<?=$isHaber?'haberler':'blog'?>">← Tüm yazılar</a></p>
</article></section>
<?php final_cta_html(['title'=>'Web siteniz için yardıma mı ihtiyacınız var?','desc'=>'Web tasarım, SEO ve dijital büyüme için ücretsiz teklif alın; aynı gün size dönelim.']); ?>
<?php footer_html(); ?>
