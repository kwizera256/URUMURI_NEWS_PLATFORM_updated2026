<?php require 'config.php'; need('admin');
// Team report for the admin: CSV download, or a print-ready page (Print > Save as PDF) with pictures.
$st = in_array($_GET['status'] ?? '', ['active', 'suspended', 'pending']) ? $_GET['status'] : 'all';
$ro = in_array($_GET['role'] ?? '', ['admin', 'writer']) ? $_GET['role'] : '';
$rg = in_array($_GET['right'] ?? '', ['can_publish', 'can_edit_all', 'can_grant']) ? $_GET['right'] : '';
$d = fn($k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET[$k] ?? '') ? $_GET[$k] : '';
$from = $d('from'); $to = $d('to'); $sq = trim($_GET['q'] ?? '');
$ids = array_values(array_unique(array_filter(array_map('intval', (array)($_GET['ids'] ?? [])))));
$w = []; $args = [];
if ($st !== 'all') { $w[] = 'u.status=?'; $args[] = $st; }
if ($ro) { $w[] = 'u.role=?'; $args[] = $ro; }
if ($rg) $w[] = "(u.$rg=1 OR u.role='admin')";
if ($from) { $w[] = 'DATE(u.created_at)>=?'; $args[] = $from; }
if ($to) { $w[] = 'DATE(u.created_at)<=?'; $args[] = $to; }
if ($sq !== '') { $w[] = '(u.name LIKE ? OR u.email LIKE ?)'; $lk = '%' . addcslashes($sq, '%_\\') . '%'; array_push($args, $lk, $lk); }
$fmt = $_GET['format'] ?? '';
if ($ids && in_array($fmt, ['csv', 'print'])) { $w[] = 'u.id IN (' . implode(',', $ids) . ')'; }
$q = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM articles WHERE author_id=u.id AND status='published') pub FROM users u"
  . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . " ORDER BY FIELD(u.status,'active','suspended','pending'), u.role='admin' DESC, u.created_at");
$q->execute($args); $rows = $q->fetchAll();
$stl = ['pending' => 'Waiting for activation', 'active' => 'Active', 'suspended' => 'Suspended'];
$filterLabel = $st === 'all' ? 'All team members' : $stl[$st];
if ($ids && in_array($fmt, ['csv', 'print'])) $filterLabel = 'Selected members';

if (!in_array($fmt, ['csv', 'print'])) { // selection page: filter, tick members, then export
  $title = 'Team report'; require 'includes/header.php';
  $fi = 'border border-stone-300 rounded-lg p-2 mt-1 w-full bg-white focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun';
  $btn = 'bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink active:scale-95 transition';
  $flt = array_filter(['status' => $st === 'all' ? '' : $st, 'role' => $ro, 'right' => $rg, 'from' => $from, 'to' => $to, 'q' => $sq]); ?>
<main class="max-w-5xl mx-auto px-4 mt-8">
  <a href="admin.php" class="text-brand underline text-sm"><?= t('Back to admin panel') ?></a>
  <h1 class="font-display font-extrabold text-3xl mt-2 mb-1"><?= t('Team report') ?></h1>
  <p class="text-stone-600 mb-4"><?= t('Choose filters, tick the members you want, then download. If you tick nobody, everyone shown is exported.') ?></p>
  <form method="get" class="bg-white border border-stone-200 rounded-2xl p-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end text-sm font-ui">
    <label><?= t('Which members?') ?><select name="status" class="<?= $fi ?>"><?php foreach (['all' => 'All team members'] + $stl as $k => $lbl): ?><option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= t($lbl) ?></option><?php endforeach; ?></select></label>
    <label><?= t('Role') ?><select name="role" class="<?= $fi ?>"><option value=""><?= t('All roles') ?></option><option value="admin" <?= $ro === 'admin' ? 'selected' : '' ?>><?= t('Admin') ?></option><option value="writer" <?= $ro === 'writer' ? 'selected' : '' ?>><?= t('Writer') ?></option></select></label>
    <label><?= t('Rights') ?><select name="right" class="<?= $fi ?>"><option value=""><?= t('Any rights') ?></option>
      <?php foreach (['can_publish' => 'Can publish stories', 'can_edit_all' => 'Can edit all stories', 'can_grant' => 'Can give rights to others'] as $k => $lbl): ?><option value="<?= $k ?>" <?= $rg === $k ? 'selected' : '' ?>><?= t($lbl) ?></option><?php endforeach; ?></select></label>
    <label><?= t('Joined from') ?><input type="date" name="from" value="<?= e($from) ?>" class="<?= $fi ?>"></label>
    <label><?= t('Joined until') ?><input type="date" name="to" value="<?= e($to) ?>" class="<?= $fi ?>"></label>
    <label><?= t('Search name or email') ?><input name="q" value="<?= e($sq) ?>" class="<?= $fi ?>"></label>
    <div class="flex gap-2"><button class="<?= $btn ?>"><?= t('Filter') ?></button><a href="team_report.php" class="border border-stone-300 px-4 py-2 rounded-lg hover:bg-sun"><?= t('Reset') ?></a></div>
  </form>
  <form method="get" id="exp" class="mt-4">
    <?php foreach ($flt as $k => $v) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">'; ?>
    <div class="flex flex-wrap items-center gap-3 mb-3">
      <label class="flex items-center gap-2 font-ui"><input type="checkbox" id="all" class="w-4 h-4 accent-[#0054bb]"> <?= t('Select all') ?></label>
      <span class="text-sm text-stone-600"><b id="cnt">0</b> / <?= count($rows) ?> <?= t('selected') ?></span>
      <span class="flex-1"></span>
      <button name="format" value="csv" class="<?= $btn ?>"><?= t('Download CSV') ?></button>
      <button name="format" value="print" formtarget="_blank" class="<?= $btn ?>" onclick="this.form.insertAdjacentHTML('beforeend','<input type=hidden name=auto value=1>')"><?= t('Download PDF') ?></button>
    </div>
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden divide-y">
      <?php foreach ($rows as $r): ?><label class="p-3 flex items-center gap-3 hover:bg-paper cursor-pointer">
        <input type="checkbox" name="ids[]" value="<?= $r['id'] ?>" class="pick w-4 h-4 accent-[#0054bb]"><?= avatar($r['name'], $r['avatar'], 36) ?>
        <span class="min-w-0 flex-1"><b><?= e($r['name']) ?></b><br><span class="text-sm text-stone-600"><?= e($r['email']) ?> &middot; <?= $r['role'] === 'admin' ? t('Admin') : t('Writer') ?> &middot; <?= t($stl[$r['status']]) ?> &middot; <?= e(ldate(strtotime($r['created_at']), true)) ?> &middot; <?= (int)$r['pub'] ?> <?= t('Published stories') ?></span></span></label>
      <?php endforeach; if (!$rows) echo '<p class="p-3">' . t('No members match these filters.') . '</p>'; ?>
    </div>
  </form>
</main>
<script>(function(){var a=document.getElementById('all'),p=[].slice.call(document.querySelectorAll('.pick')),c=document.getElementById('cnt');
function u(){c.textContent=p.filter(function(x){return x.checked}).length}a.onchange=function(){p.forEach(function(x){x.checked=a.checked});u()};p.forEach(function(x){x.onchange=u});u()})();</script>
<?php require 'includes/footer.php'; exit; }

if (($_GET['format'] ?? '') === 'csv') {
  $safe = fn($s) => preg_match('/^[=+\-@\t\r]/', (string)$s) ? "'" . $s : (string)$s; // stops spreadsheet formula injection
  header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=urumuri-team-report-' . date('Y-m-d') . '.csv');
  $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); // BOM so Excel shows accents correctly
  fputcsv($o, [t('Name'), t('Email'), t('Role'), t('Status'), t('Date joined'), t('Published stories'), t('Picture')], ',', '"', '\\');
  foreach ($rows as $r)
    fputcsv($o, [$safe($r['name']), $safe($r['email']), $r['role'] === 'admin' ? t('Admin') : t('Writer'), t($stl[$r['status']]), date('Y-m-d', strtotime($r['created_at'])),
      (int)$r['pub'], $r['avatar'] ? site_url('uploads/' . rawurlencode($r['avatar'])) : ''], ',', '"', '\\');
  fclose($o); exit; }

$count = array_count_values(array_column($rows, 'status'));
?><!DOCTYPE html>
<html lang="<?= LANG ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(t('Team report')) ?> | URUMURI NEWS</title>
<style>
*{box-sizing:border-box}body{font-family:Arial,Helvetica,sans-serif;color:#06285f;margin:0;padding:24px;background:#fff}
.bar{display:flex;gap:12px;align-items:center;margin-bottom:20px}.bar button,.bar a{font:inherit;font-weight:700;padding:9px 18px;border-radius:8px;border:0;background:#0054bb;color:#fff;text-decoration:none;cursor:pointer}
.bar a{background:#fff;color:#0054bb;border:2px solid #0054bb}
header{display:flex;align-items:center;gap:16px;border-bottom:4px solid #0054bb;padding-bottom:12px}header img{height:56px;width:auto}
h1{margin:0;font-size:24px}header p{margin:2px 0 0;color:#555;font-size:13px}
.sum{display:flex;flex-wrap:wrap;gap:10px;margin:16px 0}.sum span{border:1px solid #cbd5e1;border-radius:999px;padding:4px 12px;font-size:13px}
table{width:100%;border-collapse:collapse;font-size:13px}th,td{border-bottom:1px solid #e2e8f0;padding:8px 6px;text-align:left;vertical-align:middle}
th{background:#f1f5f9;font-size:12px;text-transform:uppercase;letter-spacing:.03em}tr{page-break-inside:avoid}
.av{width:44px;height:44px;border-radius:50%;object-fit:cover;display:block}.ini{background:#0054bb;color:#fff;font-weight:700;font-size:18px;display:flex;align-items:center;justify-content:center}
.b{display:inline-block;border:1px solid;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:700}.active{color:#166534;border-color:#166534}.suspended{color:#991b1b;border-color:#991b1b}.pending{color:#92400e;border-color:#92400e}
@page{size:A4;margin:12mm}@media print{.bar{display:none}body{padding:0}}
</style></head><body>
<div class="bar"><button type="button" onclick="window.print()"><?= e(t('Print or save as PDF')) ?></button><a href="admin.php"><?= e(t('Back to admin panel')) ?></a></div>
<header><img src="assets/logo.png" alt="URUMURI NEWS"><div><h1><?= e(t('Team report')) ?></h1><p>URUMURI NEWS &middot; <?= e(t($filterLabel)) ?> &middot; <?= e(t('Generated on')) ?> <?= e(ldate(time(), true)) ?></p></div></header>
<div class="sum"><span><b><?= count($rows) ?></b> <?= e(t('Team members')) ?></span>
  <?php foreach ($stl as $k => $lbl) if (!empty($count[$k])): ?><span><b><?= (int)$count[$k] ?></b> <?= e(t($lbl)) ?></span><?php endif; ?></div>
<table><thead><tr><th>#</th><th><?= e(t('Picture')) ?></th><th><?= e(t('Name')) ?></th><th><?= e(t('Email')) ?></th><th><?= e(t('Role')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Date joined')) ?></th><th><?= e(t('Published stories')) ?></th></tr></thead><tbody>
<?php foreach ($rows as $i => $r): ?><tr><td><?= $i + 1 ?></td>
  <td><?php if ($r['avatar']): ?><img class="av" src="uploads/<?= e($r['avatar']) ?>" alt=""><?php else: ?><span class="av ini"><?= e(mb_strtoupper(mb_substr($r['name'], 0, 1))) ?></span><?php endif; ?></td>
  <td><b><?= e($r['name']) ?></b></td><td><?= e($r['email']) ?></td><td><?= $r['role'] === 'admin' ? e(t('Admin')) : e(t('Writer')) ?></td>
  <td><span class="b <?= e($r['status']) ?>"><?= e(t($stl[$r['status']])) ?></span></td><td><?= e(ldate(strtotime($r['created_at']), true)) ?></td><td><?= (int)$r['pub'] ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8"><?= e(t('No writers have registered yet.')) ?></td></tr><?php endif; ?>
</tbody></table>
<?php if (isset($_GET['auto'])): ?><script>window.addEventListener('load',function(){setTimeout(function(){window.print()},400)})</script><?php endif; ?>
</body></html>
