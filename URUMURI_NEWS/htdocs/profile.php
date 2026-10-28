<?php require 'config.php'; need(); $m = me(); $err = ''; $ok = isset($_GET['ok']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
  $n = trim($_POST['name'] ?? ''); $cur = $_POST['current'] ?? ''; $new = $_POST['new_password'] ?? '';
  if (!$n) $err = t('Enter your name.');
  if (!$err && $new !== '') { if (!password_verify($cur, $m['password'])) $err = t('Current password is incorrect.'); elseif (strlen($new) < 8) $err = t('Password must be at least 8 characters.'); elseif ($new !== ($_POST['new_password_confirm'] ?? '')) $err = t('Passwords do not match.'); }
  $av = null; if (!$err) { $av = save_upload($_FILES['avatar'] ?? []); if ($av === false) $err = t('Image must be JPG, PNG or WEBP and under 3 MB.'); }
  if (!$err) { $set = ['name=?']; $args = [$n];
    if ($av) { $set[] = 'avatar=?'; $args[] = $av; if ($m['avatar']) @unlink(__DIR__ . '/uploads/' . $m['avatar']); }
    if ($new !== '') { $set[] = 'password=?'; $args[] = password_hash($new, PASSWORD_DEFAULT); }
    $args[] = $m['id']; $pdo->prepare('UPDATE users SET ' . implode(',', $set) . ' WHERE id=?')->execute($args);
    $_SESSION['user']['name'] = $n; header('Location: profile.php?ok=1'); exit; } }
$title = 'Profile'; require 'includes/header.php'; $in = 'w-full border border-stone-300 rounded-lg p-2 mt-1 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun'; ?>
<main class="max-w-md mx-auto px-4 mt-8">
  <h1 class="font-display font-extrabold text-3xl mb-4"><?= t('Profile') ?></h1>
  <?php if ($ok): ?><p class="bg-sun p-3 mb-4 rounded-lg"><?= t('Changes saved.') ?></p><?php endif; ?>
  <?php if ($err): ?><p class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg"><?= e($err) ?></p><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="space-y-4 bg-white border border-stone-200 rounded-2xl p-6">
    <input type="hidden" name="t" value="<?= csrf() ?>">
    <div class="flex items-center gap-4"><?= avatar($m['name'], $m['avatar'], 80) ?><label class="block text-sm"><?= t('Profile picture') ?><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="block mt-1"></label></div>
    <label class="block"><?= t('Full name') ?><input name="name" required value="<?= e($m['name']) ?>" class="<?= $in ?>"></label>
    <p class="text-sm text-stone-600"><?= e($m['email']) ?></p>
    <h2 class="font-display font-bold text-lg pt-2"><?= t('Change password') ?></h2>
    <label class="block"><?= t('Current password') ?><?= pw('current', ['autocomplete' => 'current-password']) ?></label>
    <label class="block"><?= t('New password') ?><?= pw('new_password', ['minlength' => 8, 'autocomplete' => 'new-password']) ?></label>
    <label class="block"><?= t('Confirm new password') ?><?= pw('new_password_confirm', ['autocomplete' => 'new-password', 'confirm' => 'new_password']) ?></label>
    <button class="bg-brand text-white font-bold w-full py-2 rounded-lg hover:bg-ink active:scale-95 transition"><?= t('Save') ?></button>
  </form>
</main>
<?php require 'includes/footer.php'; ?>
