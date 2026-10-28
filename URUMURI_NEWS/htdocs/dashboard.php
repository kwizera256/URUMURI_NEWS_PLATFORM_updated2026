<?php require 'config.php'; need(); $m = me(); $err = '';
$catids = array_column($pdo->query('SELECT id FROM categories')->fetchAll(), 'id');
if (post_too_big()) $err = t('The upload is too large for this server. Try a smaller video or ask the admin to raise the upload limit.');
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
  if (isset($_POST['del'])) {
    $pdo->prepare("DELETE FROM articles WHERE id=? AND (author_id=? OR ?='admin')")->execute([$_POST['del'], $m['id'], $m['role']]);
    header('Location: dashboard.php'); exit; }
  $t = trim($_POST['title'] ?? ''); $s = trim($_POST['summary'] ?? ''); $b = trim($_POST['body'] ?? ''); $c = (int)($_POST['category'] ?? 0); $l = $_POST['lang'] ?? '';
  if (!$t || !$b || !in_array($c, $catids) || !isset(LANGS[$l])) $err = t('Title, category, language and story text are required.');
  if (!$err) { $img = save_upload($_FILES['image'] ?? []); if ($img === false) $err = t('Image must be JPG, PNG or WEBP and under 3 MB.'); }
  if (!$err) { [$vt, $vv, $verr] = video_input([]); if ($verr) { $err = $verr; if ($img) @unlink(__DIR__ . '/uploads/' . $img); } }
  if (!$err) { $pub = can('can_publish');
    $pdo->prepare('INSERT INTO articles(title,slug,summary,body,lang,image,category_id,author_id,status,published_at,video_type,video,is_hot) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$t, slugify($t) . '-' . substr(uniqid(), -5), $s, $b, $l, $img, $c, $m['id'], $pub ? 'published' : 'pending', $pub ? date('Y-m-d H:i:s') : null, $vt, $vv, ($pub && isset($_POST['is_hot'])) ? 1 : 0]);
    if ($pub) queue_story((int)$pdo->lastInsertId());
    header('Location: dashboard.php?ok=' . ($pub ? 'p' : 'w')); exit; } }
$title = 'Dashboard'; require 'includes/header.php';
$mine = $pdo->prepare('SELECT a.*, c.name cat FROM articles a JOIN categories c ON c.id=a.category_id WHERE author_id=? ORDER BY created_at DESC'); $mine->execute([$m['id']]);
$perm = ['can_publish' => 'Can publish articles', 'can_edit_all' => 'Can update all articles', 'can_grant' => 'Can grant access to others']; ?>
<main class="max-w-4xl mx-auto px-4 mt-8">
  <div class="bg-white border border-stone-200 rounded-2xl p-5 flex flex-wrap items-center gap-4">
    <?= avatar($m['name'], $m['avatar'], 64) ?>
    <div class="flex-1 min-w-0"><p class="font-display font-bold text-2xl"><?= e($m['name']) ?></p><p class="text-stone-600 text-sm"><?= e($m['email']) ?> &middot; <?= $m['role'] === 'admin' ? t('Admin') : t('Writer') ?></p>
      <div class="flex flex-wrap gap-2 mt-2"><?php foreach ($perm as $k => $lbl) if (can($k)) echo '<span class="bg-sun text-xs font-bold px-2.5 py-0.5 rounded-full">' . e(t($lbl)) . '</span>'; ?></div></div>
    <a href="profile.php" class="bg-brand text-white font-bold px-4 py-2 rounded-lg hover:bg-ink transition"><?= t('Edit profile') ?></a>
  </div>
  <h1 class="font-display font-extrabold text-3xl mt-8 mb-4"><?= t('Write a story') ?></h1>
  <?php if (isset($_GET['ok'])): ?><p class="bg-sun p-3 mb-4 rounded-lg"><?= $_GET['ok'] === 'p' ? t('Story published.') : t('Story sent. It goes live after the admin approves it.') ?></p><?php endif; ?>
  <?php if ($err): ?><p class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg"><?= e($err) ?></p><?php endif; ?>
  <?php $a = []; $submit = t('Submit story'); $statusField = false; require 'includes/article_form.php'; ?>
  <h2 class="font-display font-bold text-2xl mt-10 mb-3"><?= t('My stories') ?></h2>
  <div class="bg-white border border-stone-200 rounded-xl overflow-hidden divide-y">
    <?php foreach ($mine as $s): ?><div class="p-3 flex justify-between items-center gap-3">
      <div><p class="font-bold"><?= e($s['title']) ?></p>
        <p class="text-sm text-stone-600"><?= e(tl($s['cat'], $s['lang'])) ?> &middot; <?= e(LANGS[$s['lang']]) ?> &middot; <?= $s['status'] === 'published' ? t('Published') . ', ' . $s['views'] . ' ' . t('views') : t('Waiting for approval') ?> &middot; <?= tm(strtotime($s['published_at'] ?? $s['created_at'])) ?></p></div>
      <div class="flex gap-4 shrink-0"><a href="edit.php?id=<?= $s['id'] ?>" class="text-brand font-bold"><?= t('Edit') ?></a>
        <form method="post" onsubmit="return confirm(<?= e(json_encode(t('Delete this story?'))) ?>)"><input type="hidden" name="t" value="<?= csrf() ?>"><button name="del" value="<?= $s['id'] ?>" class="text-red-700 font-bold"><?= t('Delete') ?></button></form></div>
    </div><?php endforeach; if (!$mine->rowCount()) echo '<p class="p-3">' . t('You have not written anything yet.') . '</p>'; ?>
  </div>
</main>
<?php require 'includes/footer.php'; ?>
