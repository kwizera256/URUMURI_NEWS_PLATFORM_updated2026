<?php require 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
check(); $m = strtolower(trim($_POST['email'] ?? '')); $ok = filter_var($m, FILTER_VALIDATE_EMAIL) && strlen($m) <= 150;
if ($ok) { $st = $pdo->prepare('INSERT IGNORE INTO subscribers(email) VALUES(?)'); $st->execute([$m]);
  if ($st->rowCount() === 1) { // new subscriber: alert the site email and welcome the subscriber
    send_mail(SITE_EMAIL, 'New subscriber: ' . $m, '<p>New subscriber on URUMURI NEWS: <b>' . e($m) . '</b></p>', $m);
    send_mail($m, t('Welcome to URUMURI NEWS'), '<p>' . e(t('Thank you for subscribing. You will receive our latest news updates.')) . '</p><p><b>URUMURI NEWS</b></p>'); } }
$b = $_POST['back'] ?? 'index.php';
if (!preg_match('~^/?[A-Za-z0-9_./?=&%-]*$~', $b) || strpos($b, '//') !== false) $b = 'index.php';
$b = rtrim(preg_replace('/([?&])sub=[^&]*&?/', '$1', $b), '?&');
header('Location: ' . $b . (strpos($b, '?') !== false ? '&' : '?') . 'sub=' . ($ok ? 'ok' : 'bad') . '#subscribe');
