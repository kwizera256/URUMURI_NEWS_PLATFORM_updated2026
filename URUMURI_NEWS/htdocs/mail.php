<?php // Email sending: SMTP settings (admin panel), automatic news emails to subscribers, unsubscribe links.
// SMTP values saved in the admin panel win; config.php constants are the fallback.
function mail_cfg() {
  static $c = null; if ($c !== null) return $c;
  $g = fn($k, $d) => ($v = setting($k)) !== '' ? $v : $d;
  return $c = ['host' => $g('smtp_host', SMTP_HOST), 'port' => (int)$g('smtp_port', SMTP_PORT), 'secure' => setting('smtp_secure') !== '' ? (setting('smtp_secure') === 'none' ? '' : setting('smtp_secure')) : SMTP_SECURE,
    'user' => $g('smtp_user', SMTP_USER), 'pass' => $g('smtp_pass', SMTP_PASS), 'from' => $g('smtp_from_email', SITE_EMAIL), 'name' => $g('smtp_from_name', 'URUMURI NEWS'),
    'auto' => setting('mail_auto') !== 'off', 'cap' => max(1, (int)$g('mail_daily_cap', 400))];
}
function set_setting($k, $v) { global $pdo, $SETTINGS; $pdo->prepare('INSERT INTO settings(skey,sval) VALUES(?,?) ON DUPLICATE KEY UPDATE sval=VALUES(sval)')->execute([$k, $v]); if (is_array($SETTINGS)) $SETTINGS[$k] = (string)$v; }
function mail_secret() { $s = setting('mail_secret'); if ($s === '') { $s = bin2hex(random_bytes(16)); set_setting('mail_secret', $s); } return $s; }
function unsub_token($email) { return hash_hmac('sha256', strtolower($email), mail_secret()); }
// Absolute address of the site, remembered so emails sent from cron or the background worker still have correct links.
function base_url() { $b = setting('site_base_url'); return $b !== '' ? rtrim($b, '/') . '/' : site_url(''); }
function remember_base_url() { if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) return; $u = rtrim(site_url(''), '/'); if (setting('site_base_url') !== $u) set_setting('site_base_url', $u); }

function mail_new() {
  foreach (['Exception', 'PHPMailer', 'SMTP'] as $f) require_once __DIR__ . '/lib/PHPMailer/' . $f . '.php';
  $c = mail_cfg(); $m = new PHPMailer\PHPMailer\PHPMailer(true); $m->CharSet = 'UTF-8'; $m->Timeout = 12;
  if ($c['pass'] !== '') { $m->isSMTP(); $m->Host = $c['host']; $m->Port = $c['port']; $m->SMTPAuth = true; $m->Username = $c['user']; $m->Password = $c['pass'];
    $m->SMTPSecure = $c['secure']; $m->SMTPAutoTLS = $c['secure'] !== ''; } else $m->isMail();
  $m->setFrom($c['from'], $c['name']); return $m;
}
// Sends through the configured SMTP account (or PHP mail() when no password is set). Returns [ok, error text].
function send_mail($to, $subject, $html, $replyTo = null) {
  try { $m = mail_new(); $m->addAddress($to); if ($replyTo) $m->addReplyTo($replyTo);
    $m->Subject = $subject; $m->isHTML(true); $m->Body = $html; $m->AltBody = trim(strip_tags($html)); $m->send(); return [true, ''];
  } catch (Throwable $x) { $err = isset($m) ? ($m->ErrorInfo ?: $x->getMessage()) : $x->getMessage(); error_log('URUMURI mail: ' . $err); return [false, $err]; }
}

// ---- Newsletter queue ----
function queue_story($id) { // call when a story becomes published; queues it once for every subscriber
  global $pdo; if (!mail_cfg()['auto']) return 0;
  try {
    remember_base_url();
    $u = $pdo->prepare("UPDATE articles SET notified_at=NOW() WHERE id=? AND status='published' AND notified_at IS NULL"); $u->execute([$id]);
    if (!$u->rowCount()) return 0;
    $n = $pdo->prepare('INSERT IGNORE INTO mail_queue(article_id,subscriber_id) SELECT ?, id FROM subscribers'); $n->execute([$id]);
    if ($n->rowCount()) mail_run(4, 20); // first few go out at once, the rest by the background worker
    return $n->rowCount();
  } catch (Throwable $x) { error_log('URUMURI queue: ' . $x->getMessage()); return 0; }
}
function mail_stats() {
  global $pdo; $r = $pdo->query("SELECT SUM(status='queued') q, SUM(status='failed') f, SUM(status='sent' AND DATE(sent_at)=CURDATE()) today, SUM(status='sent') total FROM mail_queue")->fetch();
  return array_map('intval', $r);
}
function mail_pending() { global $pdo; try { return (bool)$pdo->query("SELECT 1 FROM mail_queue WHERE status='queued' LIMIT 1")->fetchColumn(); } catch (Throwable $x) { return false; } }
function story_email($a) {
  $base = base_url(); $url = $base . 'article.php?s=' . rawurlencode($a['slug']); $img = ''; $vid = '';
  if ($a['image']) $img = $base . 'uploads/' . rawurlencode($a['image']);
  elseif ($a['video_type'] === 'youtube' && $a['video']) $img = 'https://img.youtube.com/vi/' . rawurlencode($a['video']) . '/hqdefault.jpg';
  if ($a['video_type'] !== 'none') $vid = '<p style="margin:0 0 14px"><a href="' . e($url) . '" style="color:#0054bb;font-weight:bold;text-decoration:none">&#9654; ' . e(t('Watch the video')) . '</a></p>';
  $sum = $a['summary'] !== '' && $a['summary'] !== null ? $a['summary'] : (!empty($a['is_hot']) ? t('This story is for subscribers only.') : mb_substr(trim(strip_tags($a['body'])), 0, 220) . '…');
  $logo = $base . 'assets/logo.png';
  return '<div style="background:#f1f5f9;padding:20px 0;font-family:Arial,Helvetica,sans-serif"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0">'
    . '<div style="padding:16px 22px;border-bottom:4px solid #ffc400"><a href="' . e($base) . '"><img src="' . e($logo) . '" alt="URUMURI NEWS" height="44" style="display:block;border:0"></a></div>'
    . ($img ? '<a href="' . e($url) . '"><img src="' . e($img) . '" alt="" width="600" style="display:block;width:100%;height:auto;border:0"></a>' : '')
    . '<div style="padding:22px"><p style="margin:0 0 6px;font-size:12px;font-weight:bold;color:#0054bb;text-transform:uppercase">' . e(tl($a['cat'], $a['lang'])) . '</p>'
    . '<h1 style="margin:0 0 10px;font-size:24px;line-height:1.25;color:#06285f"><a href="' . e($url) . '" style="color:#06285f;text-decoration:none">' . e($a['title']) . '</a></h1>'
    . '<p style="margin:0 0 14px;font-size:15px;line-height:1.5;color:#334155">' . e($sum) . '</p>' . $vid
    . '<a href="' . e($url) . '" style="display:inline-block;background:#0054bb;color:#fff;font-weight:bold;padding:11px 22px;border-radius:8px;text-decoration:none">' . e(t('Read the full story')) . '</a></div>'
    . '<div style="padding:14px 22px;background:#f8fafc;font-size:12px;color:#64748b">' . e(t('You receive this because you subscribed to URUMURI NEWS.')) . ' <a href="{UNSUB}" style="color:#64748b">' . e(t('Unsubscribe')) . '</a></div>'
    . '</div></div>';
}
// Sends queued emails until the time budget or batch size runs out. Returns ['sent','failed','left','stopped'=>reason].
function mail_run($seconds = 20, $max = 50) {
  global $pdo; $out = ['sent' => 0, 'failed' => 0, 'left' => 0, 'stopped' => ''];
  if (!$pdo->query("SELECT GET_LOCK('urumuri_mail',0)")->fetchColumn()) { $out['stopped'] = 'busy'; return $out; }
  try {
    set_setting('mail_last_run', (string)time()); $cap = mail_cfg()['cap']; $end = microtime(true) + $seconds; $m = null; $html = [];
    while (microtime(true) < $end && $out['sent'] + $out['failed'] < $max) {
      $left = $cap - mail_stats()['today']; if ($left <= 0) { $out['stopped'] = 'cap'; break; }
      $rows = $pdo->query("SELECT q.id, q.tries, s.email, a.id aid FROM mail_queue q JOIN subscribers s ON s.id=q.subscriber_id JOIN articles a ON a.id=q.article_id
        WHERE q.status='queued' AND a.status='published' ORDER BY q.id LIMIT " . (int)min(10, $left, $max - $out['sent'] - $out['failed']))->fetchAll();
      if (!$rows) break;
      if (!$m) { $m = mail_new(); $m->SMTPKeepAlive = true; }
      foreach ($rows as $r) {
        if (microtime(true) >= $end) break 2;
        if (!isset($html[$r['aid']])) { $s = $pdo->prepare('SELECT a.*, c.name cat FROM articles a JOIN categories c ON c.id=a.category_id WHERE a.id=?'); $s->execute([$r['aid']]); $a = $s->fetch();
          $html[$r['aid']] = [$a['title'], story_email($a)]; }
        [$subj, $body] = $html[$r['aid']]; $un = base_url() . 'unsubscribe.php?e=' . rawurlencode($r['email']) . '&k=' . unsub_token($r['email']);
        try {
          $m->clearAddresses(); $m->clearCustomHeaders(); $m->addAddress($r['email']); $m->Subject = $subj; $m->isHTML(true);
          $m->Body = str_replace('{UNSUB}', e($un), $body); $m->AltBody = $subj . "\n" . base_url() . "\n\n" . $un;
          $m->addCustomHeader('List-Unsubscribe', '<' . $un . '>'); $m->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click'); $m->send();
          $pdo->prepare("UPDATE mail_queue SET status='sent', sent_at=NOW(), error=NULL WHERE id=?")->execute([$r['id']]); $out['sent']++;
        } catch (Throwable $x) {
          $err = mb_substr($m->ErrorInfo ?: $x->getMessage(), 0, 250); $tries = $r['tries'] + 1;
          $pdo->prepare('UPDATE mail_queue SET tries=?, error=?, status=? WHERE id=?')->execute([$tries, $err, $tries >= 3 ? 'failed' : 'queued', $r['id']]);
          if ($tries >= 3) $out['failed']++;
          if (preg_match('/authenticate|connect|credentials|SMTP Error: Could not/i', $err)) { $out['stopped'] = 'error: ' . $err; break 2; }
          try { $m->smtpClose(); } catch (Throwable $y) {}
        }
      }
    }
    if ($m) try { $m->smtpClose(); } catch (Throwable $y) {}
    $out['left'] = (int)$pdo->query("SELECT COUNT(*) FROM mail_queue WHERE status='queued'")->fetchColumn();
  } finally { $pdo->query("SELECT RELEASE_LOCK('urumuri_mail')"); }
  return $out;
}
