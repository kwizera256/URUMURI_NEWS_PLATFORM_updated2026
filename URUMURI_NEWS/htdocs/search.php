<?php require 'config.php'; $q = trim($_GET['q'] ?? ''); $title = 'Search'; $res = [];
if ($q !== '') { $like = '%' . addcslashes($q, '%_\\') . '%';
  $res = articles('(a.title LIKE ? OR a.summary LIKE ? OR a.body LIKE ?)', [$like, $like, $like], 30); }
require 'includes/header.php'; ?>
<main class="max-w-6xl mx-auto px-4 mt-8">
  <h1 class="font-display font-extrabold text-3xl mb-5 border-l-8 border-sun pl-3"><?= t('Search results for') ?> "<?= e($q) ?>"</h1>
  <?php if (!$res): ?><p><?= t('No stories match your search.') ?></p><?php endif; ?>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5"><?php foreach ($res as $a) card($a); ?></div>
</main>
<?php require 'includes/footer.php'; ?>
