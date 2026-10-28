<?php require 'config.php'; // Hot news: premium stories for paid subscribers
[$lw, $la] = lf(); $per = 12; $page = max(1, (int)($_GET['p'] ?? 1));
$total = count_articles($lw, $la, 1); $list = articles($lw, $la, $per, ($page - 1) * $per, 1);
$m = member(); $title = 'Hot news'; require 'includes/header.php'; ?>
<main class="max-w-6xl mx-auto px-4 mt-8">
  <h1 class="font-display font-extrabold text-3xl border-l-8 border-red-600 pl-3"><?= t('Hot news') ?></h1>
  <p class="text-stone-600 mt-2 max-w-2xl"><?= t('Hot news are premium stories. Subscribe to read them.') ?></p>

  <?php if (me()): ?><p class="mt-4 bg-sun p-3 rounded-lg"><?= t('You are logged in as staff: you can read every hot story.') ?></p>
  <?php elseif (member_active($m)): ?><p class="mt-4 bg-sun p-3 rounded-lg"><?= t('Your subscription is active until') ?> <b><?= e(date('j M Y, H:i', strtotime($m['sub_until']))) ?></b>. <a href="account.php" class="underline"><?= t('My account') ?></a></p>
  <?php else: ?>
  <section class="mt-5 bg-white border-2 border-sun rounded-2xl p-5">
    <h2 class="font-display font-bold text-2xl mb-3"><?= t('Subscription plans') ?></h2>
    <?php plans_grid(active_plans(), $m ? 'account.php#plans' : 'member_register.php?next=' . urlencode('account.php')); ?>
    <div class="flex flex-wrap gap-3 mt-4">
      <?php if ($m): ?><a href="account.php#plans" class="bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink transition"><?= t('Subscribe to read') ?></a>
      <?php else: ?><a href="member_register.php" class="bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink transition"><?= t('Create an account') ?></a>
      <a href="member_login.php" class="border border-stone-300 font-bold px-5 py-2 rounded-lg hover:bg-sun transition"><?= t('Member log in') ?></a><?php endif; ?>
    </div>
    <p class="text-sm text-stone-600 mt-3"><?= t('You can pay with') ?> <?= e(implode(' / ', array_column(pay_methods() ?: array_map(fn($l) => ['label' => $l], PAY_METHODS), 'label'))) ?>.</p>
  </section>
  <?php endif; ?>

  <?php lang_chips('hot.php'); ?>
  <?php if (!$list): if (lf_current() === 'all') echo '<p class="text-lg">' . e(t('No hot news yet.')) . '</p>'; else no_stories_here(t('No hot news yet.')); endif; ?>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5"><?php foreach ($list as $a) card($a); ?></div>
  <?php pager($total, $per, $page); ?>
</main>
<?php require 'includes/footer.php'; ?>
