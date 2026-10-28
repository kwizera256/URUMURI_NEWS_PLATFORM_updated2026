<?php require 'config.php'; need('admin'); $title = 'Email settings'; $flash = null; $btn = 'bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink active:scale-95 transition';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
  if (isset($_POST['save'])) {
    $em = fn($v) => filter_var(trim($v), FILTER_VALIDATE_EMAIL) ? trim($v) : '';
    $port = (int)($_POST['smtp_port'] ?? 587);
    if (!$em($_POST['smtp_from_email'] ?? '') || trim($_POST['smtp_host'] ?? '') === '' || $port < 1 || $port > 65535) $flash = ['err', t('Please enter a valid host, port and sender email.')];
    else {
      set_setting('smtp_host', trim($_POST['smtp_host'])); set_setting('smtp_port', (string)$port);
      set_setting('smtp_secure', in_array($_POST['smtp_secure'] ?? '', ['tls', 'ssl', 'none']) ? $_POST['smtp_secure'] : 'tls');
      set_setting('smtp_user', trim($_POST['smtp_user'] ?? '')); set_setting('smtp_from_email', $em($_POST['smtp_from_email']));
      set_setting('smtp_from_name', trim($_POST['smtp_from_name'] ?? '') ?: 'URUMURI NEWS');
      if (($_POST['smtp_pass'] ?? '') !== '') set_setting('smtp_pass', $_POST['smtp_pass']); // blank = keep the saved password
      if (isset($_POST['clear_pass'])) set_setting('smtp_pass', '');
      set_setting('mail_auto', isset($_POST['mail_auto']) ? 'on' : 'off'); set_setting('mail_daily_cap', (string)max(1, min(100000, (int)($_POST['mail_daily_cap'] ?? 400))));
      remember_base_url(); $flash = ['ok', t('Email settings saved.')]; } }
  if (isset($_POST['test'])) { $to = trim($_POST['to'] ?? '');
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) $flash = ['err', t('Please enter a valid email address.')];
    else { [$ok, $err] = send_mail($to, 'URUMURI NEWS test email', '<p>This is a test email from your URUMURI NEWS website. Sending works.</p>');
      $flash = $ok ? ['ok', t('Test email sent to') . ' ' . $to] : ['err', t('Email could not be sent.') . ' ' . $err]; } }
  if (isset($_POST['retry'])) { $pdo->exec("UPDATE mail_queue SET status='queued', tries=0, error=NULL WHERE status='failed'"); $flash = ['ok', t('Failed emails were queued again.')]; }
  if (isset($_POST['clear'])) { $pdo->exec("DELETE FROM mail_queue WHERE status='queued'"); $flash = ['ok', t('Waiting emails were cleared.')]; }
  $c = null; }
remember_base_url(); $c = mail_cfg(); $st = mail_stats(); $secretKey = mail_secret();
$err1 = $pdo->query("SELECT error FROM mail_queue WHERE status<>'sent' AND error IS NOT NULL ORDER BY id DESC LIMIT 1")->fetchColumn();
require 'includes/header.php'; $in = 'w-full border border-stone-300 rounded-lg p-2 mt-1 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun'; $tok = '<input type="hidden" name="t" value="' . csrf() . '">';
$subs = (int)$pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn(); ?>
<main class="max-w-3xl mx-auto px-4 mt-8 space-y-8">
  <div><a href="admin.php" class="text-brand underline text-sm"><?= t('Back to admin panel') ?></a>
  <h1 class="font-display font-extrabold text-3xl mt-2"><?= t('Email settings') ?></h1>
  <p class="text-stone-600"><?= t('Connect the platform email so every newly published story is emailed automatically to your subscribers.') ?></p></div>
  <?php if ($flash): ?><p class="p-3 rounded-lg <?= $flash[0] === 'ok' ? 'bg-sun' : 'bg-red-100 text-red-900' ?>"><?= e($flash[1]) ?></p><?php endif; ?>

  <section><h2 class="font-display font-bold text-2xl mb-3"><?= t('SMTP settings') ?></h2>
  <form method="post" class="bg-white border border-stone-200 rounded-2xl p-5 space-y-4"><?= $tok ?>
    <div class="grid sm:grid-cols-3 gap-4">
      <label class="block sm:col-span-2"><?= t('SMTP host') ?><input name="smtp_host" required value="<?= e($c['host']) ?>" class="<?= $in ?>" placeholder="smtp.gmail.com"></label>
      <label class="block"><?= t('Port') ?><input name="smtp_port" type="number" required value="<?= (int)$c['port'] ?>" class="<?= $in ?>"></label>
      <label class="block"><?= t('Security') ?><select name="smtp_secure" class="<?= $in ?>"><?php foreach (['tls' => 'TLS (STARTTLS, port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'None'] as $k => $l): ?><option value="<?= $k ?>" <?= ($c['secure'] === '' ? 'none' : $c['secure']) === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label class="block sm:col-span-2"><?= t('SMTP username') ?><input name="smtp_user" value="<?= e($c['user']) ?>" class="<?= $in ?>" autocomplete="off"></label>
    </div>
    <label class="block"><?= t('SMTP password') ?> <span class="text-sm text-stone-600">(<?= $c['pass'] !== '' ? t('saved, leave blank to keep it') : t('not set yet') ?>)</span>
      <?= pw('smtp_pass', ['autocomplete' => 'new-password']) ?>
      <span class="block text-sm text-stone-600 mt-1"><?= t('For Gmail use a 16-letter App Password (Google Account > Security > 2-Step Verification > App passwords), not your normal password.') ?></span></label>
    <?php if ($c['pass'] !== '' && setting('smtp_pass') !== ''): ?><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="clear_pass" class="w-4 h-4 accent-[#0054bb]"> <?= t('Remove the saved password') ?></label><?php endif; ?>
    <div class="grid sm:grid-cols-2 gap-4">
      <label class="block"><?= t('Sender name') ?><input name="smtp_from_name" value="<?= e($c['name']) ?>" class="<?= $in ?>"></label>
      <label class="block"><?= t('Sender email') ?><input name="smtp_from_email" type="email" required value="<?= e($c['from']) ?>" class="<?= $in ?>"></label>
    </div>
    <div class="grid sm:grid-cols-2 gap-4 items-end">
      <label class="flex items-center gap-2"><input type="checkbox" name="mail_auto" <?= $c['auto'] ? 'checked' : '' ?> class="w-5 h-5 accent-[#0054bb]"> <?= t('Email new stories to subscribers automatically') ?></label>
      <label class="block"><?= t('Maximum emails per day') ?><input name="mail_daily_cap" type="number" min="1" value="<?= (int)$c['cap'] ?>" class="<?= $in ?>"></label>
    </div>
    <p class="text-sm text-stone-600"><?= t('Free Gmail allows about 500 emails per day. Emails over the daily limit wait and go out the next day.') ?></p>
    <button name="save" value="1" class="<?= $btn ?>"><?= t('Save settings') ?></button>
  </form></section>

  <section><h2 class="font-display font-bold text-2xl mb-3"><?= t('Send a test email') ?></h2>
  <form method="post" class="bg-white border border-stone-200 rounded-2xl p-5 flex flex-wrap items-end gap-3"><?= $tok ?>
    <label class="block flex-1 min-w-48"><?= t('Send to') ?><input name="to" type="email" required value="<?= e($c['from']) ?>" class="<?= $in ?>"></label>
    <button name="test" value="1" class="<?= $btn ?>"><?= t('Send test email') ?></button></form></section>

  <section><h2 class="font-display font-bold text-2xl mb-3"><?= t('Subscriber emails') ?></h2>
  <div class="bg-white border border-stone-200 rounded-2xl p-5 space-y-4">
    <div class="flex flex-wrap gap-3 text-sm font-ui">
      <?php foreach ([[$subs, 'Subscribers'], [$st['q'], 'Waiting to be sent'], [$st['today'], 'Sent today'], [$st['total'], 'Sent in total'], [$st['f'], 'Failed']] as [$n, $l]): ?>
      <span class="border border-stone-300 rounded-full px-3 py-1"><b><?= (int)$n ?></b> <?= t($l) ?></span><?php endforeach; ?></div>
    <?php if ($err1): ?><p class="text-sm text-red-800 bg-red-50 rounded-lg p-2"><?= t('Last error') ?>: <?= e($err1) ?></p><?php endif; ?>
    <form method="post" id="sendnow" class="flex flex-wrap gap-3 items-center"><?= $tok ?>
      <button type="button" id="go" class="<?= $btn ?>" <?= $st['q'] ? '' : 'disabled style="opacity:.5"' ?>><?= t('Send waiting emails now') ?></button>
      <button name="retry" value="1" class="border-2 border-brand text-brand font-bold px-5 py-2 rounded-lg hover:bg-paper"><?= t('Retry failed') ?></button>
      <button name="clear" value="1" onclick="return confirm(<?= e(json_encode(t('Delete all waiting emails?'))) ?>)" class="border border-stone-300 px-5 py-2 rounded-lg hover:bg-sun"><?= t('Clear waiting emails') ?></button>
      <span id="prog" class="text-sm text-stone-600"></span></form>
    <p class="text-sm text-stone-600"><?= t('Emails are sent in small batches in the background while people visit the site. For steady sending, you can also run this address every 5 minutes with a cron job:') ?></p>
    <code class="block text-xs bg-paper border border-stone-200 rounded-lg p-2 break-all"><?= e(base_url() . 'mail_worker.php?key=' . $secretKey) ?></code>
  </div></section>
</main>
<script>
(function(){var b=document.getElementById('go'),p=document.getElementById('prog'),f=document.getElementById('sendnow'),T=f.querySelector('[name=t]').value,sent=0,N=<?= json_encode(['sent' => t('sent'), 'left' => t('waiting'), 'done' => t('All waiting emails were processed.'), 'cap' => t('Daily limit reached. The rest will go out tomorrow.'), 'err' => t('Stopped:')], JSON_UNESCAPED_UNICODE) ?>;
b.onclick=function(){b.disabled=true;sent=0;step()};
function step(){p.textContent='…';var d=new FormData();d.append('t',T);fetch('mail_worker.php',{method:'POST',body:d,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){
  sent+=j.sent||0;var s=j.stopped||'';p.textContent=sent+' '+N.sent+', '+(j.left||0)+' '+N.left;
  if(s==='cap'){p.textContent+=' - '+N.cap;return}if(s.indexOf('error')===0){p.textContent+=' - '+N.err+' '+s;return}
  if((j.left||0)>0&&(j.sent||j.failed||s==='busy'))setTimeout(step,s==='busy'?1500:200);else{p.textContent+=' - '+N.done;setTimeout(function(){location.reload()},1200)}
}).catch(function(){p.textContent='Error'})}})();
</script>
<?php require 'includes/footer.php'; ?>
