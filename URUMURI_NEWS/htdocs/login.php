<?php require 'config.php'; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
  $q = $pdo->prepare('SELECT * FROM users WHERE email=?'); $q->execute([strtolower(trim($_POST['email']))]); $r = $q->fetch();
  if (!$r || !password_verify($_POST['password'], $r['password'])) $err = t('Email or password is incorrect.');
  elseif ($r['status'] === 'pending') $err = t('Your account is waiting for admin activation.');
  elseif ($r['status'] !== 'active') $err = t('This account is suspended. Contact the admin.');
  else { session_regenerate_id(true); $_SESSION['user'] = ['id' => $r['id'], 'name' => $r['name'], 'role' => $r['role']];
    header('Location: ' . ($r['role'] === 'admin' ? 'admin.php' : 'dashboard.php')); exit; } }
$title = 'Log in'; require 'includes/header.php'; ?>
<main class="max-w-md mx-auto px-4 mt-10">
  <h1 class="font-display font-extrabold text-3xl mb-1"><?= t('Log in') ?></h1>
  <p class="text-stone-600 mb-4"><?= t('Accounts are created by the admin.') ?></p>
  <?php if (isset($_GET['new'])): ?><p class="bg-sun p-3 mb-4 rounded-lg"><?= t('Account created. The admin must activate it before you can log in.') ?></p><?php endif; ?>
  <?php if ($err): ?><p class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg"><?= e($err) ?></p><?php endif; ?>
  <form method="post" class="space-y-4 bg-white border border-stone-200 rounded-2xl p-6">
    <input type="hidden" name="t" value="<?= csrf() ?>">
    <label class="block"><?= t('Email') ?><input type="email" name="email" required class="w-full border border-stone-300 rounded-lg p-2 mt-1 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun"></label>
    <label class="block"><?= t('Password') ?><?= pw('password', ['required' => 1, 'autocomplete' => 'current-password']) ?></label>
    <button class="bg-brand text-white font-bold w-full py-2 rounded-lg hover:bg-ink active:scale-95 transition"><?= t('Log in') ?></button>
  </form>
</main>
<?php require 'includes/footer.php'; ?>
