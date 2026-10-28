<?php require 'config.php'; // Reader log in (for Hot news)
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? ''); $err = '';
if (member()) { header('Location: ' . $next); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
  $f = $_SESSION['mfail'] ?? ['n' => 0, 't' => time()]; if (time() - $f['t'] > 600) $f = ['n' => 0, 't' => time()]; // 5 tries per 10 minutes
  if ($f['n'] >= 5) $err = t('Too many attempts. Please wait a few minutes and try again.');
  else { $q = $pdo->prepare('SELECT * FROM members WHERE email=?'); $q->execute([strtolower(trim($_POST['email'] ?? ''))]); $r = $q->fetch();
    if (!$r || !password_verify($_POST['password'] ?? '', $r['password'])) { $f['n']++; $_SESSION['mfail'] = $f; $err = t('Email or password is incorrect.'); }
    else { unset($_SESSION['mfail']); session_regenerate_id(true); $_SESSION['member'] = ['id' => (int)$r['id']]; header('Location: ' . $next); exit; } } }
$title = 'Member log in'; require 'includes/header.php'; ?>
<main class="max-w-md mx-auto px-4 mt-10">
  <h1 class="font-display font-extrabold text-3xl mb-1"><?= t('Member log in') ?></h1>
  <p class="text-stone-600 mb-4"><?= t('Log in to read hot news and manage your subscription.') ?></p>
  <?php if ($err): ?><p class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg" role="alert"><?= e($err) ?></p><?php endif; ?>
  <form method="post" class="space-y-4 bg-white border border-stone-200 rounded-2xl p-6">
    <input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="next" value="<?= e($next) ?>">
    <label class="block"><?= t('Email') ?><input type="email" name="email" required autocomplete="email" class="w-full border border-stone-300 rounded-lg p-2 mt-1 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun"></label>
    <label class="block"><?= t('Password') ?><?= pw('password', ['required' => 1, 'autocomplete' => 'current-password']) ?></label>
    <button class="bg-brand text-white font-bold w-full py-2 rounded-lg hover:bg-ink active:scale-95 transition"><?= t('Log in') ?></button>
  </form>
  <p class="mt-4 text-center"><?= t('No account yet?') ?> <a class="text-brand underline" href="member_register.php?next=<?= urlencode($next) ?>"><?= t('Create an account') ?></a></p>
</main>
<?php require 'includes/footer.php'; ?>
