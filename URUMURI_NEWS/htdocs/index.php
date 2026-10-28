<?php
// Header loads config.php (database + helper functions like lf() and articles())
require 'includes/header.php';

// 3. APPLICATION LOGIC SETUP
$title = 'Home'; 
[$lw, $la] = lf(); 
$per = 12; 
$page = max(1, (int)($_GET['p'] ?? 1));
$total = count_articles($lw, $la);

// Slider and ticker layout configs follow user language selection
$sl = isset(LANGS[$_GET['l'] ?? '']) ? $_GET['l'] : LANG; // language of the slider and the yellow Latest ticker
$slideNote = false; 
$slides = $page === 1 ? articles('a.lang=?', [$sl], 5) : [];

if ($page === 1 && !$slides) { 
    $slides = articles('1', [], 5); 
    $slideNote = (bool)$slides; 
}

$list = articles($lw, $la, $per, ($page - 1) * $per);
$ticker = articles('a.lang=?', [$sl], 12); // only the chosen language, never mixed
$yt = setting('youtube_channel_id'); 
?>

<?php if ($ticker): ?>
<div class="bg-sun text-ink overflow-hidden">
  <div class="max-w-6xl mx-auto px-4 py-2 flex gap-3 items-center">
    <span class="font-bold shrink-0 font-ui"><?= t('Latest') ?></span>
    <div class="overflow-hidden flex-1"><div class="tick">
      <?php foreach ($ticker as $a): ?><a href="article.php?s=<?= e($a['slug']) ?>" class="mr-10 hover:underline"><?= e($a['title']) ?></a><?php endforeach; ?>
    </div></div>
  </div>
</div>
<?php endif; ?>

<main class="max-w-6xl mx-auto px-4 mt-6">
  <?php if ($slides): ?>
  <section id="slider" class="relative overflow-hidden rounded-2xl bg-ink" aria-label="Top stories">
    <div id="track" class="flex transition-transform duration-700 ease-in-out">
      <?php foreach ($slides as $a): ?>
      <a href="article.php?s=<?= e($a['slug']) ?>" class="min-w-full relative block h-72 md:h-[26rem]">
        <?php if ($a['image']): ?><img src="uploads/<?= e($a['image']) ?>" alt="" class="w-full h-full object-cover opacity-70"><?php endif; ?>
        <div class="absolute left-0 bottom-0 right-0 md:right-auto md:max-w-2xl bg-ink/85 text-white p-5">
          <span class="bg-sun text-ink text-xs font-bold px-2.5 py-0.5 rounded-full"><?= e(tl($a['cat'], $a['lang'])) ?></span>
          <span class="border border-white/60 text-xs font-bold px-2 py-0.5 rounded-full ml-1"><?= strtoupper(e($a['lang'])) ?></span>
          <h2 class="font-display font-extrabold text-2xl md:text-3xl leading-tight mt-2"><?= e($a['title']) ?></h2>
          <p class="text-sm text-stone-300 mt-1 flex items-center gap-2"><?= avatar($a['author'], $a['author_avatar'], 22) ?><?= e($a['author']) ?>, <?= tm(strtotime($a['pub'])) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <button id="prev" class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 w-10 h-10 rounded-full font-bold hover:bg-sun transition" aria-label="Previous">&lsaquo;</button>
    <button id="next" class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 w-10 h-10 rounded-full font-bold hover:bg-sun transition" aria-label="Next">&rsaquo;</button>
    <div id="dots" class="absolute top-3 right-3 flex gap-2"></div>
  </section>
  <?php if ($slideNote): ?><p class="text-sm text-stone-600 mt-2"><?= t('No top stories in this language yet, showing all languages.') ?></p><?php endif; ?>
  <?php endif; ?>

  <?php if ($yt): ?>
  <a href="live.php" class="mt-6 flex items-center justify-between bg-brand text-white p-4 rounded-xl hover:bg-ink transition">
    <span class="font-display font-bold text-xl"><?= t('URUMURI NEWS Live is on YouTube') ?></span><span class="bg-sun text-ink font-bold px-3 py-1 rounded-lg"><?= t('Watch live') ?></span>
  </a><?php endif; ?>

  <a href="hot.php" class="mt-6 flex items-center justify-between gap-3 bg-red-600 text-white p-4 rounded-xl hover:bg-red-700 transition">
    <span class="font-display font-bold text-xl"><?= t('Hot news') ?> &middot; <span class="font-sans font-medium text-base"><?= t('Premium stories for subscribers') ?></span></span><span class="bg-white text-red-700 font-bold px-3 py-1 rounded-lg shrink-0"><?= t('Read hot news') ?></span>
  </a>

  <h2 class="font-display font-extrabold text-2xl mt-8 border-l-8 border-sun pl-3"><?= t('Latest news') ?></h2>
  <?php lang_chips('index.php'); ?>
  <?php if (!$list): if (lf_current() === 'all') echo '<p class="text-lg">' . e(t('No stories yet.')) . '</p>'; else no_stories_here(); endif; ?>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5"><?php foreach ($list as $a) card($a); ?></div>
  <?php pager($total, $per, $page); ?>

  <?php show_ad('inline'); ?>
  <?php if ($page === 1): foreach ($cats as $c):
    $items = articles('a.category_id=? AND ' . $lw, array_merge([$c['id']], $la), 3); if (!$items) continue; ?>
  <section class="mt-10">
    <div class="flex items-end justify-between mb-3">
      <h2 class="font-display font-extrabold text-2xl border-l-8 border-sun pl-3"><?= e(lf_current() === 'all' ? t($c['name']) : tl($c['name'], lf_current())) ?></h2>
      <a href="category.php?s=<?= e($c['slug']) ?>" class="text-brand font-medium hover:underline"><?= t('View all') ?> &rsaquo;</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5"><?php foreach ($items as $a) card($a); ?></div>
  </section>
  <?php endforeach; endif; ?>
</main>

<?php if ($slides): ?>
<script>
(function(){
  const track=document.getElementById('track'),n=track.children.length,dots=document.getElementById('dots');
  let i=0,timer;
  for(let k=0;k<n;k++){const d=document.createElement('button');d.className='w-3 h-3 rounded-full bg-white/60';d.setAttribute('aria-label','Slide '+(k+1));d.onclick=()=>{go(k);reset()};dots.appendChild(d)}
  function go(k){i=(k+n)%n;track.style.transform='translateX(-'+i*100+'%)';[...dots.children].forEach((d,j)=>d.className='w-3 h-3 rounded-full '+(j===i?'bg-sun':'bg-white/60'))}
  function reset(){clearInterval(timer);if(n>1&&!matchMedia('(prefers-reduced-motion:reduce)').matches)timer=setInterval(()=>go(i+1),4500)}
  document.getElementById('prev').onclick=()=>{go(i-1);reset()};
  document.getElementById('next').onclick=()=>{go(i+1);reset()};
  const s=document.getElementById('slider');s.onmouseenter=()=>clearInterval(timer);s.onmouseleave=reset;
  go(0);reset();
})();
</script>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
