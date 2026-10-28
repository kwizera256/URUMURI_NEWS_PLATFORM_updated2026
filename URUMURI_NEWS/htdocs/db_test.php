<?php
// Connection check. Open yoursite/db_test.php once to see if the database works, then DELETE this file.
// If the connection fails, config.php/db.php stop with a message that says exactly what is wrong.
require __DIR__ . '/config.php';
$ok = fn($b) => $b ? '<b style="color:#0a7d33">OK</b>' : '<b style="color:#b00020">PROBLEM</b>';
$rows = [];
foreach (['users', 'categories', 'articles', 'settings', 'subscribers', 'ads', 'mail_queue'] as $t) {
  try { $rows[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); } catch (Throwable $x) { $rows[$t] = null; }
}
$admins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
?><!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Database check</title>
<body style="font-family:Arial,sans-serif;max-width:640px;margin:6vh auto;padding:0 18px;color:#0b1b3a;line-height:1.6">
<h1>URUMURI NEWS - database check</h1>
<p>Connected to <code><?= htmlspecialchars(DB_NAME) ?></code> on <code><?= htmlspecialchars(DB_HOST) ?></code> <?= $ok(true) ?></p>
<p>PHP version: <?= htmlspecialchars(PHP_VERSION) ?> &middot; uploads folder writable: <?= $ok(is_writable(__DIR__ . '/uploads')) ?></p>
<table cellpadding="6" style="border-collapse:collapse"><tr><th align="left">Table</th><th align="left">Rows</th></tr>
<?php foreach ($rows as $t => $n): ?><tr style="border-top:1px solid #ddd"><td><?= $t ?></td><td><?= $n === null ? $ok(false) . ' missing' : $n ?></td></tr><?php endforeach; ?></table>
<p>Admin account: <?= $admins ? $ok(true) . ' exists' : '<b style="color:#b45f00">none yet</b> - open <a href="install.php">install.php</a> once to create it' ?></p>
<p style="background:#fff8d6;padding:10px;border-radius:8px"><b>Security:</b> delete <code>db_test.php</code> (and <code>install.php</code> after use) from File Manager when you are done.</p>
</body>
