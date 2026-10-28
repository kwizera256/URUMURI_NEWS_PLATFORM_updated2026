<?php require 'config.php';
$r = articles('a.slug=?', [$_GET['s'] ?? ''], 1, 0, null); // null = normal and hot stories if (!$r) { http_response_code(404); die(t('Story not found')); }
$a = $r[0]; $pdo->prepare('UPDATE articles SET views=views+1 WHERE id=?')->execute([$a['id']]);
$title = $a['title'];
$locked = !empty($a['is_hot']) && !can_read_hot();
$og = ['title' => $a['title'], 'desc' => $locked ? ($a['summary'] ?: t('This story is for subscribers only.')) : mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($a['body']))), 0, 200),
  'url' => site_url('article.php?s=' . rawurlencode($a['slug'])), 'image' => site_url($a['image'] ? 'uploads/' . rawurlencode($a['image']) : 'assets/logo.png')];
require 'includes/header.php';
$ts = strtotime($a['pub']); $edit = me() && (can('can_edit_all') || me()['id'] == $a['author_id']); ?>
<main class="max-w-3xl mx-auto px-4 mt-8">
  <div class="flex flex-wrap items-center gap-2">
    <a href="category.php?s=<?= e($a['cslug']) ?>" class="bg-sun text-xs font-bold px-2.5 py-0.5 rounded-full hover:bg-ink hover:text-white transition-colors"><?= e(tl($a['cat'], $a['lang'])) ?></a>
    <span class="border border-brand text-brand text-xs font-bold px-2 py-0.5 rounded-full"><?= e(LANGS[$a['lang']] ?? $a['lang']) ?></span>
    <?php if (!empty($a['is_hot'])): ?><span class="bg-red-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full"><?= e(t('Hot')) ?></span><?php endif; ?>
    <?php if ($edit): ?><a href="edit.php?id=<?= (int)$a['id'] ?>" class="text-sm text-brand underline ml-auto"><?= t('Edit') ?></a><?php endif; ?>
  </div>
  <h1 class="font-display font-extrabold text-3xl md:text-4xl leading-tight mt-3"><?= e($a['title']) ?></h1>
  <div class="flex items-center gap-3 mt-3 text-stone-600">
    <?= avatar($a['author'], $a['author_avatar'], 44) ?>
    <span><b class="font-medium text-ink"><?= e($a['author']) ?></b><br><?= t('Published') ?>: <?= tm($ts, true) ?>
    <?php if ($a['updated_at']): ?> &middot; <?= t('Updated') ?>: <?= tm(strtotime($a['updated_at'])) ?><?php endif; ?></span>
  </div>
  <div class="mt-4"><?= share_bar($a) ?></div>
  <?php if ($locked): if ($a['image']): ?><img src="uploads/<?= e($a['image']) ?>" alt="" class="w-full mt-5 rounded-2xl"><?php endif; ?>
  <?php if ($a['summary']): ?><p class="mt-5 text-xl leading-8 text-stone-700"><?= e($a['summary']) ?></p><?php endif; ?>
  <?php paywall_box($a); ?>
  <?php elseif ($vid = video_player($a)): echo $vid; elseif ($a['image']): ?><img src="uploads/<?= e($a['image']) ?>" alt="" class="w-full mt-5 rounded-2xl"><?php endif; ?>
  <?php if (!$locked): ?><article class="mt-6 text-lg leading-8 max-w-prose"><?= nl2br(e($a['body'])) ?></article><?php endif; ?>
  <div class="mt-8 pt-5 border-t border-stone-200"><?= share_bar($a) ?></div>
</main>
<?php show_ad('inline'); $more = articles('a.category_id=? AND a.id<>? AND a.lang=?', [$a['category_id'], $a['id'], $a['lang']], 3);
if ($more): ?><section class="max-w-6xl mx-auto px-4 mt-10"><h2 class="font-display font-bold text-xl mb-3"><?= t('Related stories') ?></h2>
<div class="grid sm:grid-cols-3 gap-5"><?php foreach ($more as $m) card($m); ?></div></section><?php endif;
require 'includes/footer.php'; ?>
