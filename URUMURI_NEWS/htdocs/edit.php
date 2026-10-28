<?php require 'config.php'; need(); $m = me();
$q = $pdo->prepare('SELECT * FROM articles WHERE id=?'); $q->execute([(int)($_GET['id'] ?? 0)]); $a = $q->fetch();
if (!$a || !(can('can_edit_all') || $a['author_id'] == $m['id'])) { header('Location: dashboard.php'); exit; }
$cats = $pdo->query('SELECT * FROM categories ORDER BY id')->fetchAll(); $catids = array_column($cats, 'id'); $err = ''; $statusField = can('can_publish');
if (post_too_big()) $err = t('The upload is too large for this server. Try a smaller video or ask the admin to raise the upload limit.');
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
  $t = trim($_POST['title'] ?? ''); $s = trim($_POST['summary'] ?? ''); $b = trim($_POST['body'] ?? ''); $c = (int)($_POST['category'] ?? 0); $l = $_POST['lang'] ?? '';
  if (!$t || !$b || !in_array($c, $catids) || !isset(LANGS[$l])) $err = t('Title, category, language and story text are required.');
  $img = $a['image']; if (!$err) { $n = save_upload($_FILES['image'] ?? []); if ($n === false) $err = t('Image must be JPG, PNG or WEBP and under 3 MB.'); elseif ($n) $img = $n; }
  if (!$err) { [$vt, $vv, $verr] = video_input($a); if ($verr) $err = $verr; }
  if (!$err) {
    // publishers choose the status; other editors send a published story back for approval
    $st = $statusField ? (($_POST['status'] ?? '') === 'published' ? 'published' : 'pending') : 'pending';
    $pubat = $st === 'published' ? ($a['status'] === 'published' && $a['published_at'] ? $a['published_at'] : date('Y-m-d H:i:s')) : null;
    $pdo->prepare('UPDATE articles SET title=?,summary=?,body=?,lang=?,image=?,category_id=?,status=?,published_at=?,video_type=?,video=?,is_hot=?,updated_at=NOW(),updated_by=? WHERE id=?')
        ->execute([$t, $s, $b, $l, $img, $c, $st, $pubat, $vt, $vv, can('can_publish') ? (isset($_POST['is_hot']) ? 1 : 0) : (int)$a['is_hot'], $m['id'], $a['id']]);
    if ($st === 'published') queue_story((int)$a['id']);
    if (($a['video_type'] ?? 'none') === 'upload' && ($a['video'] ?? '') !== $vv) drop_video('upload', $a['video']); // remove the replaced file
    header('Location: ' . (can('can_edit_all') ? 'articles.php' : 'dashboard.php') . '?saved=1'); exit; }
  $a = array_merge($a, ['is_hot' => isset($_POST['is_hot']) ? 1 : 0, 'title' => $t, 'summary' => $s, 'body' => $b, 'lang' => $l, 'category_id' => $c]); }
$title = 'Edit story'; require 'includes/header.php'; ?>
<main class="max-w-4xl mx-auto px-4 mt-8">
  <h1 class="font-display font-extrabold text-3xl mb-4"><?= t('Edit story') ?></h1>
  <?php if (!$statusField && $a['status'] === 'published'): ?><p class="bg-sun p-3 mb-4 rounded-lg"><?= t('Saving changes sends this story back for approval.') ?></p><?php endif; ?>
  <?php if ($err): ?><p class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg"><?= e($err) ?></p><?php endif; ?>
  <?php $submit = t('Save changes'); require 'includes/article_form.php'; ?>
</main>
<?php require 'includes/footer.php'; ?>
