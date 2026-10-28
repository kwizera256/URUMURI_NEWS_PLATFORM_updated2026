<?php require 'config.php';
$cat = $pdo->prepare('SELECT * FROM categories WHERE slug=?'); $cat->execute([$_GET['s'] ?? '']); $cat = $cat->fetch();
if (!$cat) { http_response_code(404); die(t('Category not found')); }
[$lw, $la] = lf(); $w = 'a.category_id=? AND ' . $lw; $args = array_merge([$cat['id']], $la);
$per = 12; $page = max(1, (int)($_GET['p'] ?? 1)); $total = count_articles($w, $args); $list = articles($w, $args, $per, ($page - 1) * $per);
$title = $cat['name']; require 'includes/header.php'; ?>
<main class="max-w-6xl mx-auto px-4 mt-8">
  <h1 class="font-display font-extrabold text-3xl border-l-8 border-sun pl-3"><?= e(t($cat['name'])) ?> <span class="text-stone-500 text-xl">(<?= $total ?>)</span></h1>
  <?php lang_chips('category.php?s=' . urlencode($cat['slug'])); ?>
  <?php if (!$list): no_stories_here(t('No stories in this category yet.')); endif; ?>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5"><?php foreach ($list as $a) card($a); ?></div>
  <?php pager($total, $per, $page); ?>
  <?php show_ad('inline'); ?>
</main>
<?php require 'includes/footer.php'; ?>
