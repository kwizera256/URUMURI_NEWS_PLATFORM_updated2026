<?php require 'config.php'; need('admin');
if (isset($_GET['csv'])) { header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=subscribers.csv'); echo "email,subscribed\n";
  foreach ($pdo->query('SELECT email,created_at FROM subscribers ORDER BY created_at DESC') as $r) echo $r['email'] . ',' . $r['created_at'] . "\n"; exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
  if (isset($_POST['testmail'])) { [$ok, $err] = send_mail(SITE_EMAIL, 'URUMURI NEWS test email', '<p>This is a test email from your URUMURI NEWS website. Sending works.</p>');
    $_SESSION['flash'] = $ok ? ['ok', t('Test email sent to') . ' ' . SITE_EMAIL] : ['err', t('Email could not be sent.') . ' ' . $err]; }
  if (isset($_POST['activate'])) $pdo->prepare("UPDATE users SET status='active' WHERE id=? AND role='writer'")->execute([$_POST['activate']]);
  if (isset($_POST['reject'])) $pdo->prepare("DELETE FROM users WHERE id=? AND role='writer' AND status='pending'")->execute([$_POST['reject']]);
  if (isset($_POST['yt'])) $pdo->prepare("UPDATE settings SET sval=? WHERE skey='youtube_channel_id'")->execute([trim($_POST['yt'])]);
  if (!empty($_POST['cat']) && slugify($_POST['cat'])) $pdo->prepare('INSERT IGNORE INTO categories(name,slug) VALUES(?,?)')->execute([trim($_POST['cat']), slugify($_POST['cat'])]);
  if (isset($_POST['delcat'])) $pdo->prepare('DELETE FROM categories WHERE id=? AND NOT EXISTS (SELECT 1 FROM articles WHERE category_id=?)')->execute([$_POST['delcat'], $_POST['delcat']]);
  if (isset($_POST['contact'])) foreach (['contact_email', 'contact_phone', 'whatsapp', 'contact_address', 'x_url', 'instagram_url', 'facebook_url'] as $k) {
    $v = trim($_POST[$k] ?? ''); if (substr($k, -4) === '_url' && $v !== '' && !preg_match('~^https?://~i', $v)) $v = '';
    $pdo->prepare('INSERT INTO settings(skey,sval) VALUES(?,?) ON DUPLICATE KEY UPDATE sval=VALUES(sval)')->execute([$k, $v]); }
  header('Location: admin.php'); exit; }
$title = 'Admin'; require 'includes/header.php';
$s = $pdo->query("SELECT (SELECT COUNT(*) FROM articles WHERE status='published') p,(SELECT COUNT(*) FROM articles WHERE status='pending') w,(SELECT COALESCE(SUM(views),0) FROM articles) v,
  (SELECT COUNT(*) FROM users WHERE role='writer' AND status='active') a,(SELECT COUNT(*) FROM subscribers) s")->fetch();
$pend = $pdo->query("SELECT * FROM users WHERE role='writer' AND status='pending' ORDER BY created_at")->fetchAll();
$cs = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM articles WHERE category_id=c.id) n FROM categories c ORDER BY c.id')->fetchAll();
$tok = '<input type="hidden" name="t" value="' . csrf() . '">'; $in = 'w-full border border-stone-300 rounded-lg p-2 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun'; $btn = 'bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink active:scale-95 transition'; ?>
<main class="max-w-5xl mx-auto px-4 mt-8 space-y-8">
  <h1 class="font-display font-extrabold text-3xl"><?= t('Admin panel') ?></h1>
  <?php if ($f = $_SESSION['flash'] ?? null): unset($_SESSION['flash']); ?><p class="<?= $f[0] === 'ok' ? 'bg-sun' : 'bg-red-100 text-red-800' ?> p-3 rounded-lg"><?= e($f[1]) ?></p><?php endif; ?>
  <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
    <?php foreach ([[$s['p'], 'Published stories'], [$s['w'], 'Waiting for approval'], [$s['v'], 'Total views'], [$s['a'], 'Writers'], [$s['s'], 'Subscribers']] as [$n, $l]): ?>
    <div class="bg-white border rounded-xl p-4"><p class="text-3xl font-bold text-brand"><?= $n ?></p><p class="text-sm"><?= t($l) ?></p></div><?php endforeach; ?>
  </div>
  <div class="flex flex-wrap gap-3"><a href="articles.php" class="<?= $btn ?>"><?= t('Manage articles') ?></a><a href="team.php" class="<?= $btn ?>"><?= t('Team') ?></a><a href="ads.php" class="<?= $btn ?>"><?= t('Ads') ?></a><a href="plans.php" class="<?= $btn ?>"><?= t('Hot news plans') ?></a><a href="payments.php" class="<?= $btn ?>"><?= t('Payments') ?><?php $pp = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn(); if ($pp): ?> <span class="bg-sun text-ink text-xs font-bold px-2 py-0.5 rounded-full"><?= $pp ?></span><?php endif; ?></a><a href="admin.php?csv=1" class="bg-white border border-stone-300 font-bold px-5 py-2 rounded-lg hover:bg-sun transition"><?= t('Export emails (CSV)') ?></a><a href="mail_settings.php" class="bg-white border border-stone-300 font-bold px-5 py-2 rounded-lg hover:bg-sun transition"><?= t('Email settings') ?></a></div>

  <section><h2 class="font-display font-bold text-2xl mb-3"><?= t('Accounts waiting for activation') ?></h2>
    <div class="bg-white border rounded-xl overflow-hidden divide-y">
    <?php foreach ($pend as $u): ?><div class="p-3 flex flex-wrap items-center justify-between gap-3"><span class="flex items-center gap-3"><?= avatar($u['name'], $u['avatar'], 40) ?><span><b><?= e($u['name']) ?></b><br><span class="text-sm text-stone-600"><?= e($u['email']) ?></span></span></span>
      <form method="post" class="flex gap-4"><?= $tok ?><button name="activate" value="<?= $u['id'] ?>" class="text-brand font-bold"><?= t('Activate') ?></button><button name="reject" value="<?= $u['id'] ?>" class="text-red-700 font-bold"><?= t('Reject') ?></button></form></div>
    <?php endforeach; if (!$pend) echo '<p class="p-3">' . t('No accounts are waiting.') . '</p>'; ?></div></section>

  <section><h2 class="font-display font-bold text-2xl mb-3"><?= t('Team report') ?></h2>
    <div class="bg-white border rounded-2xl p-5 flex flex-wrap items-center gap-3">
      <p class="flex-1 text-sm text-stone-600"><?= t('Filter the team, tick the members you want, and download a CSV or PDF report.') ?></p>
      <a href="team_report.php" class="<?= $btn ?>"><?= t('Open team report') ?></a></div></section>

  <section><h2 class="font-display font-bold text-2xl mb-3"><?= t('Categories') ?></h2>
    <div class="bg-white border rounded-xl overflow-hidden divide-y">
    <?php foreach ($cs as $c): ?><form method="post" class="p-3 flex items-center justify-between"><?= $tok ?><span><a href="category.php?s=<?= e($c['slug']) ?>" class="font-medium hover:underline"><?= e(t($c['name'])) ?></a> <span class="text-stone-500 text-sm">(<?= (int)$c['n'] ?> <?= t('stories') ?>)</span></span>
      <?php if (!$c['n']): ?><button name="delcat" value="<?= $c['id'] ?>" class="text-red-700 font-bold"><?= t('Delete') ?></button><?php endif; ?></form><?php endforeach; ?></div>
    <form method="post" class="flex gap-3 mt-3"><?= $tok ?><input name="cat" required placeholder="<?= e(t('Category name')) ?>" class="<?= $in ?>"><button class="<?= $btn ?> shrink-0"><?= t('Add category') ?></button></form></section>

  <div class="grid md:grid-cols-2 gap-6">
    <form method="post" class="bg-white border rounded-2xl p-5 space-y-3"><?= $tok ?><h2 class="font-display font-bold text-xl"><?= t('YouTube live channel') ?></h2>
      <input name="yt" value="<?= e(setting('youtube_channel_id')) ?>" placeholder="<?= e(t('Channel ID, for example UCxxxxxxxxxxxxxxxxxxxxxx')) ?>" class="<?= $in ?>"><button class="<?= $btn ?>"><?= t('Save channel') ?></button></form>
  </div>
  <form method="post" class="bg-white border rounded-2xl p-5 grid md:grid-cols-2 gap-3"><?= $tok ?><input type="hidden" name="contact" value="1">
    <h2 class="font-display font-bold text-xl md:col-span-2"><?= t('Contact and social links') ?></h2>
    <input name="contact_email" value="<?= e(setting('contact_email')) ?>" placeholder="<?= e(t('Email')) ?>" class="<?= $in ?>">
    <input name="contact_phone" value="<?= e(setting('contact_phone')) ?>" placeholder="<?= e(t('Phone')) ?>" class="<?= $in ?>">
    <input name="whatsapp" value="<?= e(setting('whatsapp')) ?>" placeholder="WhatsApp (+250...)" class="<?= $in ?>">
    <input name="contact_address" value="<?= e(setting('contact_address')) ?>" placeholder="<?= e(t('Address')) ?>" class="<?= $in ?>">
    <input name="x_url" value="<?= e(setting('x_url')) ?>" placeholder="X link (https://...)" class="<?= $in ?>">
    <input name="instagram_url" value="<?= e(setting('instagram_url')) ?>" placeholder="Instagram link (https://...)" class="<?= $in ?>">
    <input name="facebook_url" value="<?= e(setting('facebook_url')) ?>" placeholder="Facebook link (https://...)" class="<?= $in ?> md:col-span-2">
    <button class="<?= $btn ?> md:col-span-2 md:w-48"><?= t('Save links') ?></button>
  </form>
</main>
<?php require 'includes/footer.php'; ?>
