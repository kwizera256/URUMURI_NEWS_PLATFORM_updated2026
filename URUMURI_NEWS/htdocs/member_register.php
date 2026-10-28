<?php require 'config.php'; // Reader sign-up (for Hot news). Staff accounts are separate and created by the admin.
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? ''); $err = ''; $v = ['name' => '', 'email' => '', 'phone' => ''];
if (member()) { header('Location: ' . $next); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
  $v = ['name' => trim($_POST['name'] ?? ''), 'email' => strtolower(trim($_POST['email'] ?? '')), 'phone' => trim($_POST['phone'] ?? '')];
  $pw = $_POST['password'] ?? ''; $phone = $v['phone'] === '' ? null : norm_phone($v['phone']);
  if ($v['name'] === '' || mb_strlen($v['name']) > 100 || !filter_var($v['email'], FILTER_VALIDATE_EMAIL) || strlen($v['email']) > 150) $err = t('Enter your name and a valid email address.');
  elseif ($v['phone'] !== '' && !$phone) $err = t('Enter a valid phone number.');
  elseif (strlen($pw) < 8) $err = t('Password must be at least 8 characters.');
  elseif ($pw !== ($_POST['password_confirm'] ?? '')) $err = t('Passwords do not match.');
  else { $x = $pdo->prepare('SELECT 1 FROM members WHERE email=?'); $x->execute([$v['email']]);
    if ($x->fetch()) $err = t('This email already has an account.');
    else { $pdo->prepare('INSERT INTO members(name,email,phone,password) VALUES(?,?,?,?)')->execute([$v['name'], $v['email'], $phone, password_hash($pw, PASSWORD_DEFAULT)]);
      session_regenerate_id(true); $_SESSION['member'] = ['id' => (int)$pdo->lastInsertId()]; header('Location: ' . $next); exit; } } }
$title = 'Create an account'; require 'includes/header.php'; $in = 'w-full border border-stone-300 rounded-lg p-2 mt-1 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun'; ?>
<main class="max-w-md mx-auto px-4 mt-10">
  <h1 class="font-display font-extrabold text-3xl mb-1"><?= t('Create an account') ?></h1>
  <p class="text-stone-600 mb-4"><?= t('An account lets you subscribe and read hot news.') ?></p>
  <?php if ($err): ?><p class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg" role="alert"><?= e($err) ?></p><?php endif; ?>
  <form method="post" class="space-y-4 bg-white border border-stone-200 rounded-2xl p-6">
    <input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="next" value="<?= e($next) ?>">
    <label class="block"><?= t('Full name') ?><input name="name" required maxlength="100" value="<?= e($v['name']) ?>" autocomplete="name" class="<?= $in ?>"></label>
    <label class="block"><?= t('Email') ?><input type="email" name="email" required maxlength="150" value="<?= e($v['email']) ?>" autocomplete="email" class="<?= $in ?>"></label>
    <label class="block"><?= t('Phone number') ?> <span class="text-stone-500 text-sm">(<?= t('optional') ?>)</span><input name="phone" inputmode="tel" value="<?= e($v['phone']) ?>" placeholder="07XXXXXXXX" autocomplete="tel" class="<?= $in ?>"></label>
    <label class="block"><?= t('Password') ?><?= pw('password', ['required' => 1, 'minlength' => 8, 'autocomplete' => 'new-password']) ?></label>
    <label class="block"><?= t('Confirm password') ?><?= pw('password_confirm', ['required' => 1, 'autocomplete' => 'new-password', 'confirm' => 'password']) ?></label>
    <button class="bg-brand text-white font-bold w-full py-2 rounded-lg hover:bg-ink active:scale-95 transition"><?= t('Create an account') ?></button>
  </form>
  <p class="mt-4 text-center"><?= t('Already have an account?') ?> <a class="text-brand underline" href="member_login.php?next=<?= urlencode($next) ?>"><?= t('Member log in') ?></a></p>
</main>
<?php require 'includes/footer.php'; ?>
