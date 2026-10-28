<?php require 'config.php'; need('admin'); $msg = '';
$places = ['top' => 'Top banner (every page)', 'inline' => 'Inside content', 'bottom' => 'Above footer'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
  if (isset($_POST['create'])) {
    $t = trim($_POST['title'] ?? ''); $b = trim($_POST['body'] ?? ''); $u = trim($_POST['url'] ?? ''); $p = $_POST['placement'] ?? '';
    $s = preg_match('/^\d{4}-\d\d-\d\d$/', $_POST['starts_at'] ?? '') ? $_POST['starts_at'] : null; $en = preg_match('/^\d{4}-\d\d-\d\d$/', $_POST['ends_at'] ?? '') ? $_POST['ends_at'] : null;
    if (!$t || !preg_match('~^https?://~i', $u) || !isset($places[$p]) || ($s && $en && $en < $s)) $msg = 'invalid';
    else { $img = save_upload($_FILES['image'] ?? []);
      if ($img === false) $msg = 'image';
      elseif (!$img && !$b) $msg = 'empty';
      else { $pdo->prepare('INSERT INTO ads(title,body,url,image,placement,starts_at,ends_at) VALUES(?,?,?,?,?,?,?)')->execute([$t, $b ?: null, $u, $img, $p, $s, $en]); $msg = 'created'; } } }
  if (isset($_POST['toggle'])) $pdo->prepare("UPDATE ads SET status=IF(status='active','paused','active') WHERE id=?")->execute([$_POST['toggle']]);
  if (isset($_POST['delete'])) { $q = $pdo->prepare('SELECT image FROM ads WHERE id=?'); $q->execute([$_POST['delete']]); if ($im = $q->fetchColumn()) @unlink(__DIR__ . '/uploads/' . $im);
    $pdo->prepare('DELETE FROM ads WHERE id=?')->execute([$_POST['delete']]); } }
$title = 'Ads'; require 'includes/header.php';
try { $ads = $pdo->query('SELECT * FROM ads ORDER BY id DESC')->fetchAll(); } catch (Throwable $x) { $ads = null; }
$tok = '<input type="hidden" name="t" value="' . csrf() . '">'; $in = 'w-full border border-stone-300 rounded-lg p-2 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun';
$flash = ['created' => ['ok', 'Ad created.'], 'invalid' => ['err', 'Enter a title and a link starting with https://'], 'image' => ['err', 'Image must be JPG, PNG or WEBP and under 3 MB.'], 'empty' => ['err', 'Add a banner picture or some ad text.']][$msg] ?? null; ?>
<main class="max-w-5xl mx-auto px-4 mt-8 space-y-6">
  <h1 class="font-display font-extrabold text-3xl"><?= t('Ads') ?></h1>
  <p class="text-sm text-stone-600 -mt-3"><?= t('The top banner shows up to 4 active "Top" ads side by side. Add as many as you like: when there are more than 4 they take turns.') ?></p>
  <?php if ($flash): ?><p class="<?= $flash[0] === 'ok' ? 'bg-sun' : 'bg-red-100 text-red-800' ?> p-3 rounded-lg"><?= t($flash[1]) ?></p><?php endif; ?>
  <?php if ($ads === null): ?><p class="bg-red-100 text-red-800 p-3 rounded-lg">The ads table is missing. Import database.sql in phpMyAdmin, or reload any page so it is created automatically.</p><?php else: ?>
  <form method="post" enctype="multipart/form-data" class="bg-white border border-stone-200 rounded-2xl p-5 grid md:grid-cols-2 gap-3"><?= $tok ?><input type="hidden" name="create" value="1">
    <h2 class="font-display font-bold text-xl md:col-span-2"><?= t('Create ad') ?></h2>
    <input name="title" required maxlength="120" placeholder="<?= e(t('Ad title')) ?>" class="<?= $in ?>">
    <input name="url" required placeholder="<?= e(t('Link (https://...)')) ?>" class="<?= $in ?>">
    <input name="body" maxlength="200" placeholder="<?= e(t('Ad text (optional)')) ?>" class="<?= $in ?> md:col-span-2">
    <select name="placement" class="<?= $in ?>"><?php foreach ($places as $k => $l): ?><option value="<?= $k ?>"><?= t($l) ?></option><?php endforeach; ?></select>
    <label class="text-sm"><?= t('Banner picture (best size 970x250, JPG, PNG or WEBP)') ?><input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="block mt-1"></label>
    <label class="text-sm"><?= t('Start date (optional)') ?><input type="date" name="starts_at" class="<?= $in ?>"></label>
    <label class="text-sm"><?= t('End date (optional)') ?><input type="date" name="ends_at" class="<?= $in ?>"></label>
    <button class="bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink active:scale-95 transition md:col-span-2 md:w-48"><?= t('Create ad') ?></button>
  </form>
  <div class="bg-white border border-stone-200 rounded-xl overflow-hidden divide-y">
    <?php foreach ($ads as $a): $ctr = $a['views'] ? round($a['clicks'] * 100 / $a['views'], 1) : 0; ?>
    <form method="post" class="p-3 flex flex-wrap items-center justify-between gap-3"><?= $tok ?>
      <div class="flex items-center gap-3 min-w-0">
        <?php if ($a['image']): ?><img src="uploads/<?= e($a['image']) ?>" alt="" class="w-24 h-14 object-cover rounded-lg"><?php else: ?><div class="w-24 h-14 rounded-lg bg-sun/40 flex items-center justify-center text-xs">Text</div><?php endif; ?>
        <div class="min-w-0"><p class="font-bold"><?= e($a['title']) ?> <span class="text-xs font-normal px-2 py-0.5 rounded-full <?= $a['status'] === 'active' ? 'bg-sun' : 'bg-stone-200' ?>"><?= $a['status'] === 'active' ? t('Active') : t('Paused') ?></span></p>
          <p class="text-sm text-stone-600"><?= t($places[$a['placement']]) ?> &middot; <?= (int)$a['views'] ?> <?= t('views') ?> &middot; <?= (int)$a['clicks'] ?> <?= t('Clicks') ?> (<?= $ctr ?>%)<?= $a['starts_at'] || $a['ends_at'] ? ' &middot; ' . e(($a['starts_at'] ?: '...') . ' to ' . ($a['ends_at'] ?: '...')) : '' ?></p></div></div>
      <div class="flex gap-4"><button name="toggle" value="<?= $a['id'] ?>" class="text-brand font-bold"><?= $a['status'] === 'active' ? t('Pause') : t('Activate') ?></button>
        <button name="delete" value="<?= $a['id'] ?>" onclick="return confirm(<?= e(json_encode(t('Delete this ad?'))) ?>)" class="text-red-700 font-bold"><?= t('Delete') ?></button></div>
    </form>
    <?php endforeach; if (!$ads) echo '<p class="p-3">' . t('No ads yet.') . '</p>'; ?>
  </div>
  <?php endif; ?>
</main>
<?php require 'includes/footer.php'; ?>
