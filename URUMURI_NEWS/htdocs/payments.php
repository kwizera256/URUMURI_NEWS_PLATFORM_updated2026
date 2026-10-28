<?php require 'config.php'; $adm = need('admin'); // Admin: confirm/reject payments and see members
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check(); $id = (int)($_POST['id'] ?? 0); $do = $_POST['do'] ?? ''; $to = 'pending';
  if ($do === 'approve') {
    $pdo->beginTransaction();
    try { $q = $pdo->prepare("SELECT * FROM payments WHERE id=? AND status='pending' FOR UPDATE"); $q->execute([$id]); $p = $q->fetch();
      if ($p) { $pdo->prepare("UPDATE payments SET status='approved', decided_at=NOW(), decided_by=?, note=NULL WHERE id=?")->execute([$adm['id'], $id]);
        $pdo->prepare('UPDATE members SET sub_until = DATE_ADD(GREATEST(COALESCE(sub_until, NOW()), NOW()), INTERVAL ? DAY) WHERE id=?')->execute([$p['days'], $p['member_id']]); }
      $pdo->commit(); } catch (Throwable $x) { $pdo->rollBack(); throw $x; }
    $to = 'approved'; }
  if ($do === 'reject') { $pdo->prepare("UPDATE payments SET status='rejected', decided_at=NOW(), decided_by=?, note=? WHERE id=? AND status='pending'")->execute([$adm['id'], mb_substr(trim($_POST['note'] ?? ''), 0, 250) ?: null, $id]); $to = 'rejected'; }
  if ($do === 'grant') { $d = max(1, min(3650, (int)($_POST['days'] ?? 0)));
    $pdo->prepare('UPDATE members SET sub_until = DATE_ADD(GREATEST(COALESCE(sub_until, NOW()), NOW()), INTERVAL ? DAY) WHERE id=?')->execute([$d, (int)($_POST['member'] ?? 0)]); $to = 'members'; }
  if ($do === 'revoke') { $pdo->prepare('UPDATE members SET sub_until=NULL WHERE id=?')->execute([(int)($_POST['member'] ?? 0)]); $to = 'members'; }
  header('Location: payments.php?s=' . $to); exit; }
$tab = in_array($_GET['s'] ?? '', ['pending', 'approved', 'rejected', 'members']) ? $_GET['s'] : 'pending';
$counts = $pdo->query("SELECT SUM(status='pending') p, SUM(status='approved') a, SUM(status='rejected') r FROM payments")->fetch(); $mc = (int)$pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
if ($tab === 'members') $rows = $pdo->query('SELECT m.*, (SELECT COUNT(*) FROM payments WHERE member_id=m.id AND status=\'approved\') paid FROM members m ORDER BY m.created_at DESC LIMIT 300')->fetchAll();
else { $q = $pdo->prepare('SELECT p.*, m.name mname, m.email memail FROM payments p JOIN members m ON m.id=p.member_id WHERE p.status=? ORDER BY p.id DESC LIMIT 200'); $q->execute([$tab]); $rows = $q->fetchAll(); }
$title = 'Payments'; require 'includes/header.php'; $tok = '<input type="hidden" name="t" value="' . csrf() . '">';
$tabs = ['pending' => ['Waiting for confirmation', (int)$counts['p']], 'approved' => ['Approved', (int)$counts['a']], 'rejected' => ['Rejected', (int)$counts['r']], 'members' => ['Members', $mc]]; ?>
<main class="max-w-6xl mx-auto px-4 mt-8">
  <div class="flex flex-wrap items-center justify-between gap-3 mb-4"><h1 class="font-display font-extrabold text-3xl"><?= t('Payments') ?></h1>
    <span class="flex gap-3"><a href="plans.php" class="bg-white border border-stone-300 font-bold px-4 py-2 rounded-lg hover:bg-sun"><?= t('Hot news plans') ?></a><a href="admin.php" class="text-brand underline self-center"><?= t('Admin panel') ?></a></span></div>
  <div class="flex gap-2 mb-4 font-ui text-sm flex-wrap"><?php foreach ($tabs as $k => [$lbl, $n]): ?><a href="?s=<?= $k ?>" class="px-4 py-1.5 rounded-full <?= $tab === $k ? 'bg-brand text-white' : 'bg-white border border-stone-300 hover:bg-sun' ?>"><?= t($lbl) ?> (<?= $n ?>)</a><?php endforeach; ?></div>

  <?php if ($tab === 'members'): ?>
  <div class="bg-white border border-stone-200 rounded-xl overflow-x-auto"><table class="w-full text-sm"><thead class="bg-paper text-left"><tr><th class="p-3"><?= t('Full name') ?></th><th class="p-3"><?= t('Phone number') ?></th><th class="p-3"><?= t('Subscription') ?></th><th class="p-3"><?= t('Joined') ?></th><th class="p-3"></th></tr></thead><tbody class="divide-y">
    <?php foreach ($rows as $r): $on = member_active($r); ?><tr><td class="p-3"><b><?= e($r['name']) ?></b><br><span class="text-stone-500"><?= e($r['email']) ?></span></td><td class="p-3"><?= e($r['phone'] ?? '-') ?></td>
      <td class="p-3"><?= $on ? '<span class="bg-green-200 px-2.5 py-0.5 rounded-full text-xs font-bold">' . e(t('Active')) . '</span> ' . e(date('j M Y', strtotime($r['sub_until']))) : '<span class="text-stone-500">' . e(t('No active subscription')) . '</span>' ?> <span class="text-stone-400">(<?= (int)$r['paid'] ?> <?= t('payments') ?>)</span></td>
      <td class="p-3 whitespace-nowrap"><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
      <td class="p-3"><form method="post" class="flex items-center gap-2 flex-wrap"><?= $tok ?><input type="hidden" name="member" value="<?= $r['id'] ?>"><input type="number" name="days" min="1" max="3650" value="30" class="w-20 border border-stone-300 rounded-lg p-1" aria-label="<?= e(t('Days of access')) ?>">
        <button name="do" value="grant" class="text-brand font-bold"><?= t('Add days') ?></button><?php if ($on): ?><button name="do" value="revoke" onclick="return confirm(<?= e(json_encode(t('Remove this subscription?'))) ?>)" class="text-red-700 font-bold"><?= t('Remove') ?></button><?php endif; ?></form></td></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="p-3"><?= t('No members yet.') ?></td></tr><?php endif; ?></tbody></table></div>

  <?php else: ?>
  <div class="bg-white border border-stone-200 rounded-xl overflow-x-auto"><table class="w-full text-sm"><thead class="bg-paper text-left"><tr><th class="p-3"><?= t('Date') ?></th><th class="p-3"><?= t('Member') ?></th><th class="p-3"><?= t('Plan') ?></th><th class="p-3"><?= t('Amount') ?></th><th class="p-3"><?= t('Pay with') ?></th><th class="p-3"><?= t('Transaction ID') ?></th><th class="p-3"></th></tr></thead><tbody class="divide-y">
    <?php foreach ($rows as $r): ?><tr class="align-top"><td class="p-3 whitespace-nowrap"><?= e(date('j M Y, H:i', strtotime($r['created_at']))) ?></td><td class="p-3"><b><?= e($r['mname']) ?></b><br><span class="text-stone-500"><?= e($r['memail']) ?></span></td>
      <td class="p-3"><?= e($r['plan_name']) ?><br><span class="text-stone-500"><?= e(days_label((int)$r['days'])) ?></span></td><td class="p-3 font-bold"><?= e(money($r['amount'])) ?></td>
      <td class="p-3"><?= e(PAY_METHODS[$r['method']]) ?><br><span class="text-stone-500"><?= e($r['payer_phone']) ?></span></td><td class="p-3 font-mono"><?= e($r['txn_ref']) ?></td>
      <td class="p-3"><?php if ($r['status'] === 'pending'): ?><form method="post" class="space-y-2"><?= $tok ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button name="do" value="approve" onclick="return confirm(<?= e(json_encode(t('Confirm that you received this payment?'))) ?>)" class="bg-brand text-white font-bold px-3 py-1.5 rounded-lg hover:bg-ink"><?= t('Approve') ?></button>
        <div class="flex gap-1"><input name="note" maxlength="250" placeholder="<?= e(t('Reason (optional)')) ?>" class="border border-stone-300 rounded-lg p-1 w-36"><button name="do" value="reject" class="text-red-700 font-bold"><?= t('Reject') ?></button></div></form>
        <?php else: ?><span class="text-stone-500"><?= $r['decided_at'] ? e(date('j M Y, H:i', strtotime($r['decided_at']))) : '' ?><?= $r['note'] ? '<br>' . e($r['note']) : '' ?></span><?php endif; ?></td></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="p-3"><?= t('Nothing here.') ?></td></tr><?php endif; ?></tbody></table></div>
  <?php endif; ?>
</main>
<?php require 'includes/footer.php'; ?>
