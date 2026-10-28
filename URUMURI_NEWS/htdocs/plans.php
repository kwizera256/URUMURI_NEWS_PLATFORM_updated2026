<?php require 'config.php'; need('admin'); $msg = ''; // Admin: subscription plans/prices and the MTN / Airtel payment details
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
  $fields = fn() => [trim($_POST['name'] ?? ''), (int)($_POST['days'] ?? 0), (int)($_POST['price'] ?? 0)];
  if (isset($_POST['addplan'])) { [$n, $d, $p] = $fields();
    if ($n === '' || mb_strlen($n) > 80 || $d < 1 || $d > 3650 || $p < 1) $msg = 'invalid';
    else { $pdo->prepare('INSERT INTO plans(name,days,price) VALUES(?,?,?)')->execute([$n, $d, $p]); $msg = 'created'; } }
  if (isset($_POST['saveplan'])) { [$n, $d, $p] = $fields(); $s = ($_POST['status'] ?? '') === 'paused' ? 'paused' : 'active';
    if ($n === '' || mb_strlen($n) > 80 || $d < 1 || $d > 3650 || $p < 1) $msg = 'invalid';
    else { $pdo->prepare('UPDATE plans SET name=?, days=?, price=?, status=? WHERE id=?')->execute([$n, $d, $p, $s, (int)$_POST['saveplan']]); $msg = 'saved'; } }
  if (isset($_POST['delplan'])) { $pdo->prepare('DELETE FROM plans WHERE id=?')->execute([(int)$_POST['delplan']]); $msg = 'deleted'; }
  if (isset($_POST['paysave'])) {
    foreach (array_keys(PAY_METHODS) as $k) {
      set_setting("pay_{$k}_on", isset($_POST["on_$k"]) ? '1' : '0');
      set_setting("pay_{$k}_number", mb_substr(trim($_POST["num_$k"] ?? ''), 0, 40));
      set_setting("pay_{$k}_name", mb_substr(trim($_POST["nm_$k"] ?? ''), 0, 80)); }
    set_setting('pay_note', mb_substr(trim($_POST['pay_note'] ?? ''), 0, 250)); $msg = 'saved'; }
  header('Location: plans.php?m=' . $msg); exit; }
$msg = $_GET['m'] ?? '';
$plans = $pdo->query('SELECT p.*, (SELECT COUNT(*) FROM payments WHERE plan_id=p.id AND status=\'approved\') sold FROM plans p ORDER BY price, days')->fetchAll();
$flash = ['created' => ['ok', 'Plan created.'], 'saved' => ['ok', 'Changes saved.'], 'deleted' => ['ok', 'Plan deleted.'], 'invalid' => ['err', 'Enter a name, a number of days (1-3650) and a price above 0.']][$msg] ?? null;
$title = 'Hot news plans'; require 'includes/header.php'; $tok = '<input type="hidden" name="t" value="' . csrf() . '">';
$in = 'w-full border border-stone-300 rounded-lg p-2 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun'; $btn = 'bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink active:scale-95 transition'; ?>
<main class="max-w-5xl mx-auto px-4 mt-8 space-y-8">
  <div class="flex flex-wrap items-center justify-between gap-3"><h1 class="font-display font-extrabold text-3xl"><?= t('Hot news plans') ?></h1>
    <span class="flex gap-3"><a href="payments.php" class="bg-white border border-stone-300 font-bold px-4 py-2 rounded-lg hover:bg-sun"><?= t('Payments') ?></a><a href="admin.php" class="text-brand underline self-center"><?= t('Admin panel') ?></a></span></div>
  <?php if ($flash): ?><p class="<?= $flash[0] === 'ok' ? 'bg-sun' : 'bg-red-100 text-red-800' ?> p-3 rounded-lg"><?= t($flash[1]) ?></p><?php endif; ?>

  <section><h2 class="font-display font-bold text-2xl mb-1"><?= t('Subscription prices') ?></h2>
    <p class="text-sm text-stone-600 mb-3"><?= t('Readers pay the price of a plan to read hot news for the number of days you set. Prices are in RWF.') ?></p>
    <form method="post" class="bg-white border border-stone-200 rounded-2xl p-5 grid sm:grid-cols-4 gap-3 items-end mb-4"><?= $tok ?><input type="hidden" name="addplan" value="1">
      <label class="text-sm sm:col-span-2"><?= t('Plan name') ?><input name="name" required maxlength="80" placeholder="Monthly" class="<?= $in ?>"></label>
      <label class="text-sm"><?= t('Days of access') ?><input type="number" name="days" min="1" max="3650" required placeholder="30" class="<?= $in ?>"></label>
      <label class="text-sm"><?= t('Price (RWF)') ?><input type="number" name="price" min="1" required placeholder="3000" class="<?= $in ?>"></label>
      <button class="<?= $btn ?> sm:col-span-4 sm:w-48"><?= t('Add plan') ?></button></form>
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden divide-y">
    <?php foreach ($plans as $p): ?><form method="post" class="p-3 grid sm:grid-cols-12 gap-2 items-end"><?= $tok ?><input type="hidden" name="saveplan" value="<?= $p['id'] ?>">
      <label class="text-xs sm:col-span-3"><?= t('Plan name') ?><input name="name" value="<?= e($p['name']) ?>" required maxlength="80" class="<?= $in ?>"></label>
      <label class="text-xs sm:col-span-2"><?= t('Days of access') ?><input type="number" name="days" value="<?= (int)$p['days'] ?>" min="1" max="3650" required class="<?= $in ?>"></label>
      <label class="text-xs sm:col-span-2"><?= t('Price (RWF)') ?><input type="number" name="price" value="<?= (int)$p['price'] ?>" min="1" required class="<?= $in ?>"></label>
      <label class="text-xs sm:col-span-2"><?= t('Status') ?><select name="status" class="<?= $in ?>"><option value="active" <?= $p['status'] === 'active' ? 'selected' : '' ?>><?= t('Active') ?></option><option value="paused" <?= $p['status'] === 'paused' ? 'selected' : '' ?>><?= t('Paused') ?></option></select></label>
      <div class="sm:col-span-3 flex items-center gap-4"><button class="text-brand font-bold"><?= t('Save') ?></button>
        <button name="delplan" value="<?= $p['id'] ?>" formnovalidate onclick="return confirm(<?= e(json_encode(t('Delete this plan?'))) ?>)" class="text-red-700 font-bold"><?= t('Delete') ?></button>
        <span class="text-xs text-stone-500"><?= (int)$p['sold'] ?> <?= t('sold') ?></span></div></form>
    <?php endforeach; if (!$plans) echo '<p class="p-3">' . t('No plans yet.') . '</p>'; ?></div></section>

  <section><h2 class="font-display font-bold text-2xl mb-1"><?= t('Payment methods') ?></h2>
    <p class="text-sm text-stone-600 mb-3"><?= t('Readers send the money to these numbers, then enter the transaction ID. You confirm each payment on the Payments page.') ?></p>
    <form method="post" class="bg-white border border-stone-200 rounded-2xl p-5 space-y-5"><?= $tok ?><input type="hidden" name="paysave" value="1">
      <?php foreach (PAY_METHODS as $k => $label): ?><div class="grid sm:grid-cols-12 gap-3 items-end">
        <label class="sm:col-span-3 flex items-center gap-2 font-bold pb-2"><input type="checkbox" name="on_<?= $k ?>" <?= setting("pay_{$k}_on") === '1' ? 'checked' : '' ?> class="w-4 h-4 accent-[#0054bb]"><?= e($label) ?></label>
        <label class="text-sm sm:col-span-4"><?= t('Number or pay code') ?><input name="num_<?= $k ?>" value="<?= e(setting("pay_{$k}_number")) ?>" maxlength="40" placeholder="07XXXXXXXX" class="<?= $in ?>"></label>
        <label class="text-sm sm:col-span-5"><?= t('Account name') ?><input name="nm_<?= $k ?>" value="<?= e(setting("pay_{$k}_name")) ?>" maxlength="80" class="<?= $in ?>"></label></div><?php endforeach; ?>
      <label class="block text-sm"><?= t('Extra instructions for readers (optional)') ?><textarea name="pay_note" rows="2" maxlength="250" class="<?= $in ?>"><?= e(setting('pay_note')) ?></textarea></label>
      <p class="text-xs text-stone-500"><?= t('A method only appears for readers when it is ticked and has a number.') ?></p>
      <button class="<?= $btn ?>"><?= t('Save payment methods') ?></button></form></section>
</main>
<?php require 'includes/footer.php'; ?>
