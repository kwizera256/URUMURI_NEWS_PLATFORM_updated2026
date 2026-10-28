<?php
// HOT NEWS: reader accounts, subscription plans and payments (MTN Mobile Money / Airtel Money).
// Payments are confirmed by the admin (Admin > Payments); access then starts automatically.

const PAY_METHODS = ['mtn' => 'MTN Mobile Money', 'airtel' => 'Airtel Money'];

function member() { // the logged-in reader (fresh from the database) or null
  static $m = false; if ($m !== false) return $m;
  global $pdo; $m = null; $id = (int)($_SESSION['member']['id'] ?? 0);
  if ($id) { $q = $pdo->prepare('SELECT * FROM members WHERE id=?'); $q->execute([$id]); $m = $q->fetch() ?: null; if (!$m) unset($_SESSION['member']); }
  return $m;
}
function member_active($m = null) { $m = $m ?? member(); return $m && !empty($m['sub_until']) && strtotime($m['sub_until']) > time(); }
function can_read_hot() { return me() || member_active(); } // staff and paid readers
function safe_next($n) { // only relative page addresses are allowed after login
  $n = (string)$n; return ($n !== '' && preg_match('~^[A-Za-z0-9_./?=&%-]+$~', $n) && $n[0] !== '/' && strpos($n, '//') === false) ? $n : 'account.php';
}
function need_member() {
  $m = member(); if ($m) return $m;
  header('Location: member_login.php?next=' . urlencode(basename($_SERVER['SCRIPT_NAME']))); exit;
}
function money($n) { return number_format((int)$n) . ' RWF'; }
function norm_phone($p) { // Rwanda mobile number -> 07XXXXXXXX or null
  $d = preg_replace('/\D+/', '', (string)$p); if (strpos($d, '250') === 0) $d = '0' . substr($d, 3);
  return preg_match('/^07[2389]\d{7}$/', $d) ? $d : null;
}
function active_plans() { global $pdo; try { return $pdo->query("SELECT * FROM plans WHERE status='active' ORDER BY price, days")->fetchAll(); } catch (Throwable $x) { return []; } }
function pay_methods() { // enabled payment methods that have a number: ['mtn' => [label, number, name]]
  $o = []; foreach (PAY_METHODS as $k => $label)
    if (setting("pay_{$k}_on") === '1' && trim(setting("pay_{$k}_number")) !== '') $o[$k] = ['label' => $label, 'number' => trim(setting("pay_{$k}_number")), 'name' => trim(setting("pay_{$k}_name"))];
  return $o;
}
function days_label($d) { return $d % 365 === 0 && $d >= 365 ? ($d / 365) . ' ' . t($d / 365 == 1 ? 'year' : 'years') : $d . ' ' . t('days'); }

// Plan cards (price list)
function plans_grid($plans, $link = null) {
  if (!$plans) { echo '<p class="text-stone-600">' . e(t('No subscription plans are available yet. Please check back soon.')) . '</p>'; return; }
  echo '<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">';
  foreach ($plans as $p) echo '<div class="bg-white border border-stone-200 rounded-2xl p-5"><p class="font-display font-bold text-xl">' . e($p['name']) . '</p>'
    . '<p class="text-3xl font-extrabold text-brand mt-2">' . e(money($p['price'])) . '</p><p class="text-stone-600">' . e(days_label((int)$p['days'])) . '</p>'
    . ($link ? '<a href="' . e($link) . '" class="inline-block mt-3 bg-brand text-white font-bold px-4 py-2 rounded-lg hover:bg-ink transition">' . e(t('Choose a plan')) . '</a>' : '') . '</div>';
  echo '</div>';
}

// Box shown instead of a hot story's text to people without access
function paywall_box($a) {
  $next = 'article.php?s=' . rawurlencode($a['slug']); $m = member(); $btn = 'inline-block font-bold px-5 py-2 rounded-lg transition ';
  echo '<section class="mt-6 bg-white border-2 border-sun rounded-2xl p-6" aria-labelledby="pw-h"><p class="inline-block bg-red-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full">' . e(t('Premium story')) . '</p>'
    . '<h2 id="pw-h" class="font-display font-extrabold text-2xl mt-2">' . e(t('This story is for subscribers only.')) . '</h2>'
    . '<p class="text-stone-600 mt-1 mb-4">' . e($m ? t('Choose a plan to read it. Your access starts as soon as the admin confirms your payment.') : t('Create an account and subscribe to read hot news.')) . '</p>';
  plans_grid(active_plans());
  echo '<div class="flex flex-wrap gap-3 mt-5">';
  if ($m) echo '<a href="account.php#plans" class="' . $btn . 'bg-brand text-white hover:bg-ink">' . e(t('Subscribe to read')) . '</a>';
  else echo '<a href="member_register.php?next=' . urlencode($next) . '" class="' . $btn . 'bg-brand text-white hover:bg-ink">' . e(t('Create an account')) . '</a>'
    . '<a href="member_login.php?next=' . urlencode($next) . '" class="' . $btn . 'border border-stone-300 hover:bg-sun">' . e(t('Member log in')) . '</a>';
  echo '</div></section>';
}
