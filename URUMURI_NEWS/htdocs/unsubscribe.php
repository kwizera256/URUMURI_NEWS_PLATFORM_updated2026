<?php require 'config.php'; $title = 'Unsubscribe'; $uem = trim($_REQUEST['e'] ?? ''); $ukey = (string)($_REQUEST['k'] ?? ''); // header.php reuses $k and $e, so own names here
$ok = $uem !== '' && hash_equals(unsub_token($uem), $ukey);
if ($ok && $_SERVER['REQUEST_METHOD'] === 'POST') { $pdo->prepare('DELETE FROM subscribers WHERE email=?')->execute([$uem]); $done = true; if (isset($_POST['List_Unsubscribe'])) exit('ok'); }
require 'includes/header.php'; ?>
<main class="max-w-xl mx-auto px-4 mt-10"><div class="bg-white border border-stone-200 rounded-2xl p-6">
  <h1 class="font-display font-extrabold text-3xl mb-3"><?= t('Unsubscribe') ?></h1>
  <?php if (!$ok): ?><p><?= t('This unsubscribe link is not valid.') ?></p>
  <?php elseif (!empty($done)): ?><p class="bg-sun p-3 rounded-lg"><?= t('You have been unsubscribed. You will not receive our news emails any more.') ?></p>
  <?php else: ?><p class="mb-4"><?= t('Stop receiving news emails at') ?> <b><?= e($uem) ?></b>?</p>
    <form method="post"><input type="hidden" name="e" value="<?= e($uem) ?>"><input type="hidden" name="k" value="<?= e($ukey) ?>">
    <button class="bg-brand text-white font-bold px-6 py-2 rounded-lg hover:bg-ink active:scale-95 transition"><?= t('Unsubscribe') ?></button></form><?php endif; ?>
  <a href="index.php" class="inline-block mt-4 text-brand underline"><?= t('Back to home') ?></a>
</div></main>
<?php require 'includes/footer.php'; ?>
