<?php require 'config.php'; $m = need_member(); // Reader account: subscription status, buy a plan, payment history
$plans = active_plans(); $methods = pay_methods(); $err = ''; $sent = ($_GET['m'] ?? '') === 'sent';
$v = ['plan' => (int)($_POST['plan'] ?? 0), 'method' => $_POST['method'] ?? '', 'phone' => trim($_POST['phone'] ?? $m['phone'] ?? ''), 'ref' => trim($_POST['ref'] ?? '')];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) { check();
  $q = $pdo->prepare("SELECT * FROM plans WHERE id=? AND status='active'"); $q->execute([$v['plan']]); $plan = $q->fetch();
  $phone = norm_phone($v['phone']); $ref = strtoupper(preg_replace('/\s+/', '', $v['ref']));
  $pend = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE member_id=? AND status='pending'"); $pend->execute([$m['id']]);
  if (!$plan) $err = t('This plan is not available.');
  elseif (!isset($methods[$v['method']])) $err = t('This payment method is not available.');
  elseif (!$phone) $err = t('Enter a valid phone number.');
  elseif (!preg_match('/^[A-Z0-9._-]{6,40}$/', $ref)) $err = t('Enter the transaction ID from the confirmation SMS.');
  elseif ((int)$pend->fetchColumn() >= 3) $err = t('You already have payments waiting for confirmation.');
  else { try {
      $pdo->prepare('INSERT INTO payments(member_id,plan_id,plan_name,days,amount,method,payer_phone,txn_ref) VALUES(?,?,?,?,?,?,?,?)')
          ->execute([$m['id'], $plan['id'], $plan['name'], $plan['days'], $plan['price'], $v['method'], $phone, $ref]);
      header('Location: account.php?m=sent'); exit;
    } catch (PDOException $x) { $err = t('This transaction ID was already used.'); } }
}
$hist = $pdo->prepare('SELECT * FROM payments WHERE member_id=? ORDER BY id DESC LIMIT 30'); $hist->execute([$m['id']]); $hist = $hist->fetchAll();
$active = member_active($m); $title = 'My account'; require 'includes/header.php';
$in = 'w-full border border-stone-300 rounded-lg p-2 mt-1 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun';
$st = ['pending' => ['Waiting for confirmation', 'bg-sun'], 'approved' => ['Approved', 'bg-green-200'], 'rejected' => ['Rejected', 'bg-red-100 text-red-800']]; ?>
<main class="max-w-4xl mx-auto px-4 mt-8 space-y-8">
  <div class="bg-white border border-stone-200 rounded-2xl p-5 flex flex-wrap items-center gap-4">
    <?= avatar($m['name'], null, 56) ?>
    <div class="flex-1 min-w-0"><h1 class="font-display font-bold text-2xl"><?= e($m['name']) ?></h1><p class="text-stone-600 text-sm"><?= e($m['email']) ?></p></div>
    <a href="member_logout.php" class="border border-stone-300 font-bold px-4 py-2 rounded-lg hover:bg-sun transition"><?= t('Log out') ?></a>
  </div>

  <?php if ($sent): ?><p class="bg-sun p-3 rounded-lg" role="status"><?= t('Payment sent. The admin will confirm it shortly and your access will start.') ?></p><?php endif; ?>
  <section class="rounded-2xl p-5 <?= $active ? 'bg-green-100 border border-green-300' : 'bg-white border border-stone-200' ?>">
    <?php if ($active): ?><p class="text-lg"><?= t('Your subscription is active until') ?> <b><?= e(date('j M Y, H:i', strtotime($m['sub_until']))) ?></b></p>
      <a href="hot.php" class="inline-block mt-3 bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink transition"><?= t('Read hot news') ?></a>
    <?php else: ?><p class="text-lg font-bold"><?= t('No active subscription') ?></p><p class="text-stone-600"><?= t('Choose a plan below to read hot news.') ?></p><?php endif; ?>
  </section>

  <section id="plans">
    <h2 class="font-display font-bold text-2xl mb-3"><?= t($active ? 'Extend your subscription' : 'Subscription plans') ?></h2>
    <?php if ($err): ?><p class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg" role="alert"><?= e($err) ?></p><?php endif; ?>
    <?php if (!$plans): ?><p><?= t('No subscription plans are available yet. Please check back soon.') ?></p>
    <?php elseif (!$methods): ?><p><?= t('Payments are not open yet. Please check back soon.') ?></p>
    <?php else: ?>
    <form method="post" class="bg-white border border-stone-200 rounded-2xl p-5 space-y-5" id="payform">
      <input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="pay" value="1">
      <fieldset><legend class="font-bold mb-2">1. <?= t('Choose a plan') ?></legend>
        <div class="grid sm:grid-cols-2 gap-3">
        <?php foreach ($plans as $p): ?><label class="flex items-center gap-3 border border-stone-300 rounded-xl p-3 cursor-pointer has-[:checked]:border-brand has-[:checked]:bg-sun/20">
          <input type="radio" name="plan" value="<?= $p['id'] ?>" data-price="<?= (int)$p['price'] ?>" required <?= $v['plan'] == $p['id'] ? 'checked' : '' ?> class="w-4 h-4 accent-[#0054bb]">
          <span><b><?= e($p['name']) ?></b> &middot; <?= e(days_label((int)$p['days'])) ?><br><span class="text-brand font-bold"><?= e(money($p['price'])) ?></span></span></label><?php endforeach; ?>
        </div></fieldset>
      <fieldset><legend class="font-bold mb-2">2. <?= t('Pay with') ?></legend>
        <div class="flex flex-wrap gap-3">
        <?php foreach ($methods as $k => $mm): ?><label class="flex items-center gap-2 border border-stone-300 rounded-xl px-4 py-2 cursor-pointer has-[:checked]:border-brand has-[:checked]:bg-sun/20">
          <input type="radio" name="method" value="<?= $k ?>" required <?= $v['method'] === $k ? 'checked' : '' ?> class="w-4 h-4 accent-[#0054bb]"><b><?= e($mm['label']) ?></b></label><?php endforeach; ?>
        </div>
        <?php foreach ($methods as $k => $mm): ?><div class="mt-3 bg-paper border border-stone-200 rounded-xl p-4" data-pay="<?= $k ?>" hidden>
          <p><?= t('Amount to pay') ?>: <b class="text-xl text-brand" data-amount>-</b></p>
          <p><?= t('Send the amount to') ?>: <b class="text-xl"><?= e($mm['number']) ?></b><?= $mm['name'] !== '' ? ' &middot; ' . t('Account name') . ': <b>' . e($mm['name']) . '</b>' : '' ?></p>
          <p class="text-sm text-stone-600 mt-1"><?= t('You will receive an SMS with a transaction ID. Enter it below.') ?></p></div><?php endforeach; ?>
        <?php if (setting('pay_note') !== ''): ?><p class="text-sm mt-3 bg-sun/30 p-3 rounded-lg"><?= nl2br(e(setting('pay_note'))) ?></p><?php endif; ?>
      </fieldset>
      <fieldset class="grid sm:grid-cols-2 gap-4"><legend class="font-bold mb-2 sm:col-span-2">3. <?= t('Confirm your payment') ?></legend>
        <label class="block text-sm"><?= t('Your phone number (used to pay)') ?><input name="phone" required inputmode="tel" placeholder="07XXXXXXXX" value="<?= e($v['phone']) ?>" class="<?= $in ?>"></label>
        <label class="block text-sm"><?= t('Transaction ID from the confirmation SMS') ?><input name="ref" required maxlength="40" value="<?= e($v['ref']) ?>" autocomplete="off" class="<?= $in ?> uppercase"></label>
      </fieldset>
      <button class="bg-brand text-white font-bold px-6 py-2.5 rounded-lg hover:bg-ink active:scale-95 transition"><?= t('I have paid') ?></button>
    </form>
    <script>
    (function(){var f=document.getElementById('payform');if(!f)return;
    function upd(){var p=f.querySelector('input[name=plan]:checked'),m=f.querySelector('input[name=method]:checked'),a=p?Number(p.dataset.price).toLocaleString('en-US')+' RWF':'-';
      f.querySelectorAll('[data-pay]').forEach(function(d){d.hidden=!m||d.dataset.pay!==m.value;var s=d.querySelector('[data-amount]');if(s)s.textContent=a})}
    f.addEventListener('change',upd);upd()})();
    </script>
    <?php endif; ?>
  </section>

  <section>
    <h2 class="font-display font-bold text-2xl mb-3"><?= t('My payments') ?></h2>
    <div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
      <table class="w-full text-sm"><thead class="bg-paper text-left"><tr><th class="p-3"><?= t('Date') ?></th><th class="p-3"><?= t('Plan') ?></th><th class="p-3"><?= t('Amount') ?></th><th class="p-3"><?= t('Pay with') ?></th><th class="p-3"><?= t('Status') ?></th></tr></thead><tbody class="divide-y">
      <?php foreach ($hist as $h): [$sl, $sc] = $st[$h['status']]; ?><tr><td class="p-3 whitespace-nowrap"><?= e(date('j M Y, H:i', strtotime($h['created_at']))) ?></td><td class="p-3"><?= e($h['plan_name']) ?> (<?= e(days_label((int)$h['days'])) ?>)</td><td class="p-3"><?= e(money($h['amount'])) ?></td>
        <td class="p-3"><?= e(PAY_METHODS[$h['method']]) ?><br><span class="text-stone-500"><?= e($h['txn_ref']) ?></span></td><td class="p-3"><span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?= $sc ?>"><?= e(t($sl)) ?></span><?= $h['note'] ? '<br><span class="text-stone-600">' . e($h['note']) . '</span>' : '' ?></td></tr><?php endforeach; ?>
      <?php if (!$hist): ?><tr><td colspan="5" class="p-3"><?= t('No payments yet.') ?></td></tr><?php endif; ?></tbody></table>
    </div>
  </section>
</main>
<?php require 'includes/footer.php'; ?>
