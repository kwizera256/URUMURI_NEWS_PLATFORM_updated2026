<?php require 'config.php'; need(); needcan('can_grant'); $m = me(); $adm = $m['role'] === 'admin'; $msg = '';
$perms = ['can_publish' => 'Can publish articles', 'can_edit_all' => 'Can update all articles', 'can_grant' => 'Can grant access to others'];
if ($adm && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) { check();
  $n = trim($_POST['name'] ?? ''); $em = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
  if (!$n || !filter_var($em, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8) $msg = 'invalid';
  elseif ($pw !== ($_POST['password_confirm'] ?? '')) $msg = 'mismatch';
  else { $x = $pdo->prepare('SELECT 1 FROM users WHERE email=?'); $x->execute([$em]);
    if ($x->fetch()) $msg = 'exists';
    else { $av = save_upload($_FILES['avatar'] ?? []);
      if ($av === false) $msg = 'image';
      else { $pdo->prepare("INSERT INTO users(name,email,password,avatar,status,can_publish,can_edit_all,can_grant) VALUES(?,?,?,?,'active',?,?,?)")
        ->execute([$n, $em, password_hash($pw, PASSWORD_DEFAULT), $av, isset($_POST['can_publish']) ? 1 : 0, isset($_POST['can_edit_all']) ? 1 : 0, isset($_POST['can_grant']) ? 1 : 0]); $msg = 'created'; } } } }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check(); $uid = (int)($_POST['uid'] ?? 0);
  $q = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='writer'"); $q->execute([$uid]); $tg = $q->fetch();
  if ($tg && $uid !== (int)$m['id']) {
    if (isset($_POST['delete']) && $adm) { $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]); $msg = 'deleted'; }
    else { $set = []; $args = [];
      foreach ($perms as $k => $_) if ($adm || !empty($m[$k])) { $set[] = "$k=?"; $args[] = isset($_POST[$k]) ? 1 : 0; } // you can only hand out what you hold
      if ($adm) {
        if (in_array($_POST['status'] ?? '', ['pending', 'active', 'suspended'])) { $set[] = 'status=?'; $args[] = $_POST['status']; }
        $np = $_POST['new_password'] ?? ''; if ($np !== '') { if (strlen($np) < 8) $msg = 'short'; elseif ($np !== ($_POST['new_password_confirm'] ?? '')) $msg = 'mismatch'; else { $set[] = 'password=?'; $args[] = password_hash($np, PASSWORD_DEFAULT); } }
      }
      if ($set && !in_array($msg, ['short', 'mismatch'])) { $args[] = $uid; $pdo->prepare('UPDATE users SET ' . implode(',', $set) . ' WHERE id=?')->execute($args); $msg = 'saved'; } } } }
$users = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM articles WHERE author_id=u.id) n FROM users u WHERE role='writer' ORDER BY FIELD(status,'pending','active','suspended'), created_at DESC")->fetchAll();
$title = 'Team'; require 'includes/header.php'; $tok = '<input type="hidden" name="t" value="' . csrf() . '">';
$in = 'border border-stone-300 rounded-lg p-2 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun'; ?>
<main class="max-w-5xl mx-auto px-4 mt-8">
  <h1 class="font-display font-extrabold text-3xl mb-1"><?= t('Team') ?></h1>
  <p class="text-stone-600 mb-4"><?= t($adm ? 'Activate writers, reset passwords and choose what each writer may do.' : 'Choose what each writer may do. You can only give access you hold yourself.') ?></p>
  <?php $flash = ['saved' => ['ok', 'Changes saved.'], 'created' => ['ok', 'Writer account created.'], 'short' => ['err', 'Password must be at least 8 characters.'], 'mismatch' => ['err', 'Passwords do not match.'],
    'exists' => ['err', 'This email already has an account.'], 'image' => ['err', 'Image must be JPG, PNG or WEBP and under 3 MB.'], 'invalid' => ['err', 'Enter a name, a valid email and a password of at least 8 characters.']][$msg] ?? null;
  if ($flash): ?><p class="<?= $flash[0] === 'ok' ? 'bg-sun' : 'bg-red-100 text-red-800' ?> p-3 mb-4 rounded-lg"><?= t($flash[1]) ?></p><?php endif; ?>
  <?php if ($adm): ?>
  <form method="post" enctype="multipart/form-data" class="bg-white border border-stone-200 rounded-2xl p-5 mb-6 grid md:grid-cols-2 gap-3"><?= $tok ?><input type="hidden" name="create" value="1">
    <h2 class="font-display font-bold text-xl md:col-span-2"><?= t('Create writer account') ?></h2>
    <input name="name" required placeholder="<?= e(t('Full name')) ?>" class="<?= $in ?>">
    <input type="email" name="email" required placeholder="<?= e(t('Email')) ?>" class="<?= $in ?>">
    <?= pw('password', ['required' => 1, 'minlength' => 8, 'autocomplete' => 'new-password', 'placeholder' => t('Temporary password (at least 8 characters)'), 'wrap' => 'block']) ?>
    <?= pw('password_confirm', ['required' => 1, 'autocomplete' => 'new-password', 'placeholder' => t('Confirm password'), 'confirm' => 'password', 'wrap' => 'block']) ?>
    <label class="text-sm md:col-span-2"><?= t('Profile picture (optional)') ?><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="block mt-1"></label>
    <div class="md:col-span-2 flex flex-wrap gap-x-6 gap-y-2"><?php foreach ($perms as $k => $lbl): ?><label class="flex items-center gap-2"><input type="checkbox" name="<?= $k ?>" class="w-4 h-4 accent-[#0054bb]"><?= t($lbl) ?></label><?php endforeach; ?></div>
    <button class="bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink active:scale-95 transition md:col-span-2 md:w-56"><?= t('Create account') ?></button>
  </form>
  <?php endif; ?>
  <div class="space-y-4">
  <?php foreach ($users as $u): ?>
    <form method="post" class="bg-white border <?= $u['status'] === 'pending' ? 'border-sun border-2' : 'border-stone-200' ?> rounded-2xl p-4"><?= $tok ?><input type="hidden" name="uid" value="<?= $u['id'] ?>">
      <div class="flex flex-wrap items-center gap-3">
        <?= avatar($u['name'], $u['avatar'], 52) ?>
        <div class="flex-1 min-w-0"><p class="font-display font-bold text-xl"><?= e($u['name']) ?></p><p class="text-sm text-stone-600"><?= e($u['email']) ?> &middot; <?= (int)$u['n'] ?> <?= t('stories') ?> &middot; <?= e(ldate(strtotime($u['created_at']), true)) ?></p></div>
        <?php if ($adm): ?><select name="status" class="<?= $in ?>"><?php foreach (['pending' => 'Waiting for activation', 'active' => 'Active', 'suspended' => 'Suspended'] as $k => $lbl): ?><option value="<?= $k ?>" <?= $u['status'] === $k ? 'selected' : '' ?>><?= t($lbl) ?></option><?php endforeach; ?></select>
        <?php else: ?><span class="bg-stone-100 text-sm px-3 py-1 rounded-full"><?= t(['pending' => 'Waiting for activation', 'active' => 'Active', 'suspended' => 'Suspended'][$u['status']]) ?></span><?php endif; ?>
      </div>
      <div class="flex flex-wrap gap-x-6 gap-y-2 mt-3">
        <?php foreach ($perms as $k => $lbl): $ok = $adm || !empty($m[$k]); ?>
        <label class="flex items-center gap-2 <?= $ok ? '' : 'opacity-50' ?>"><input type="checkbox" name="<?= $k ?>" <?= $u[$k] ? 'checked' : '' ?> <?= $ok ? '' : 'disabled' ?> class="w-4 h-4 accent-[#0054bb]"><?= t($lbl) ?></label>
        <?php endforeach; ?>
      </div>
      <div class="flex flex-wrap items-center gap-3 mt-3">
        <?php if ($adm): ?><?= pw('new_password', ['placeholder' => t('New password (leave empty to keep)'), 'autocomplete' => 'new-password', 'wrap' => 'block flex-1 min-w-48']) ?>
        <?= pw('new_password_confirm', ['placeholder' => t('Confirm new password'), 'autocomplete' => 'new-password', 'confirm' => 'new_password', 'wrap' => 'block flex-1 min-w-48']) ?><?php endif; ?>
        <button class="bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink active:scale-95 transition"><?= t('Save') ?></button>
        <?php if ($adm): ?><button name="delete" value="1" onclick="return confirm(<?= e(json_encode(t('Delete this account and all its stories?'))) ?>)" class="text-red-700 font-bold"><?= t('Delete') ?></button><?php endif; ?>
      </div>
    </form>
  <?php endforeach; if (!$users) echo '<p>' . t('No writers have registered yet.') . '</p>'; ?>
  </div>
</main>
<?php require 'includes/footer.php'; ?>
