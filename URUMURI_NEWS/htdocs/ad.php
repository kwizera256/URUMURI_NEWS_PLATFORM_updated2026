<?php require 'config.php'; // counts the click, then sends the visitor to the advertiser
$q = $pdo->prepare('SELECT id,url FROM ads WHERE id=?'); $q->execute([(int)($_GET['id'] ?? 0)]); $a = $q->fetch();
if (!$a || !preg_match('~^https?://~i', $a['url'])) { header('Location: index.php'); exit; }
$pdo->prepare('UPDATE ads SET clicks=clicks+1 WHERE id=?')->execute([$a['id']]);
header('Location: ' . $a['url']);
