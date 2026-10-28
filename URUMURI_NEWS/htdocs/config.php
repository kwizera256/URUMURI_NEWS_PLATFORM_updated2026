<?php
/* ==========================================================================
   URUMURI NEWS - main configuration + shared helper functions.
   Every page loads this file first. EDIT YOUR SETTINGS ONLY IN THIS SECTION.
   ========================================================================== */

// ---- 1. DATABASE (InfinityFree > MySQL Databases) ------------------------
const DB_HOST = 'sql313.infinityfree.com';        // MySQL hostname (NOT localhost, NOT a URL)
const DB_NAME = 'if0_43264006_urumuri_news';      // full database name from the "List of MySQL Databases"
const DB_USER = 'if0_43264006';                   // MySQL username
const DB_PASS = '0782061792k';                    // MySQL password (the one in your InfinityFree panel)
const DB_PORT = 3306;

// ---- 2. SITE SETTINGS -----------------------------------------------------
const GA_ID      = 'G-XXXXXXXXXX';                // your Google Analytics 4 ID (leave as is to disable)
const SITE_EMAIL = 'kwizerasamuel6644@gmail.com';

// Gmail SMTP (paste a 16-letter Gmail App Password in SMTP_PASS to enable SMTP; empty = PHP mail())
const SMTP_HOST = 'smtp.gmail.com', SMTP_PORT = 587, SMTP_SECURE = 'tls',
      SMTP_USER = 'kwizerasamuel6644@gmail.com', SMTP_PASS = '';

// true = show detailed errors on screen (handy while setting up). Set to false once everything works.
const APP_DEBUG = true;
/* ========================== END OF SETTINGS ============================== */

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Africa/Kigali');

const LANGS = ['en' => 'English', 'rw' => 'Kinyarwanda', 'fr' => 'Français'];

if (isset($_GET['lang']) && isset(LANGS[$_GET['lang']])) {
    setcookie('lang', $_GET['lang'], time() + 31536000, '/');
    $_COOKIE['lang'] = $_GET['lang'];
}
define('LANG', isset(LANGS[$_COOKIE['lang'] ?? '']) ? $_COOKIE['lang'] : 'en');
require_once __DIR__ . '/lang.php';

// ---- Database connection ($pdo) -------------------------------------------
require_once __DIR__ . '/db.php';

/* ------------------------------ basic helpers ----------------------------- */
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Absolute URL of the site root
function site_url($path = '') {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
  return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/' . ltrim($path, '/');
}

// Site settings stored in the `settings` table (contact info, social links, mail options ...)
function setting($k, $default = '') {
  global $pdo, $SETTINGS;
  if (!is_array($SETTINGS)) {
    $SETTINGS = [];
    try { foreach ($pdo->query('SELECT skey, sval FROM settings') as $r) $SETTINGS[$r['skey']] = (string)$r['sval']; } catch (Throwable $x) {}
  }
  return $SETTINGS[$k] ?? $default;
}

/* ------------------------------ security / login -------------------------- */
function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function check() { // call at the top of every POST handler
  if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['t'] ?? ''))) { http_response_code(400); die(e(t('Invalid request.')) . ' <a href="javascript:history.back()">&larr;</a>'); }
}
function me() { // the logged-in, active user (fresh from the database) or null
  static $u = false;
  if ($u !== false) return $u;
  global $pdo; $u = null; $id = (int)($_SESSION['user']['id'] ?? 0);
  if ($id) { $q = $pdo->prepare('SELECT * FROM users WHERE id=?'); $q->execute([$id]); $r = $q->fetch();
    if ($r && $r['status'] === 'active') $u = $r; else unset($_SESSION['user']); }
  return $u;
}
function can($right) { $m = me(); return $m && ($m['role'] === 'admin' || !empty($m[$right])); }
function need($role = null) { // require login (and optionally the admin role)
  $m = me(); if (!$m) { header('Location: login.php'); exit; }
  if ($role === 'admin' && $m['role'] !== 'admin') { header('Location: dashboard.php'); exit; }
  return $m;
}
function needcan(...$rights) { // require at least one of these rights
  foreach ($rights as $r) if (can($r)) return;
  header('Location: dashboard.php'); exit;
}

// Password input with a show/hide eye (the click handler lives in includes/footer.php)
function pw($name, $o = []) {
  $at = 'type="password" name="' . e($name) . '" class="w-full border border-stone-300 rounded-lg p-2 pr-11 bg-white focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun"';
  foreach (['required', 'minlength', 'autocomplete', 'placeholder'] as $k)
    if (isset($o[$k])) $at .= $k === 'required' ? ' required' : ' ' . $k . '="' . e($o[$k]) . '"';
  if (!empty($o['confirm'])) $at .= ' data-confirm="' . e($o['confirm']) . '" data-mismatch="' . e(t('Passwords do not match.')) . '"';
  $wrap = $o['wrap'] ?? 'block mt-1';
  return '<span class="relative ' . e($wrap) . '"><input ' . $at . '>'
    . '<button type="button" class="pw-eye absolute right-2 top-1/2 -translate-y-1/2 p-1 text-stone-500 hover:text-brand" aria-pressed="false" aria-label="' . e(t('Show password')) . '" title="' . e(t('Show password')) . '" data-show="' . e(t('Show password')) . '" data-hide="' . e(t('Hide password')) . '">'
    . '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/><path class="slash" d="M3 3l18 18" style="display:none"/></svg></button></span>';
}

/* ------------------------------ files / text ------------------------------ */
// Saves an uploaded JPG/PNG/WEBP (max 3 MB). Returns file name, null (nothing uploaded) or false (invalid).
function save_upload($f) {
  if (empty($f['name']) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
  if (($f['error'] ?? 1) !== UPLOAD_ERR_OK || $f['size'] > 3 * 1024 * 1024 || !is_uploaded_file($f['tmp_name'])) return false;
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
  $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
  if (!$ext || !@getimagesize($f['tmp_name'])) return false;
  $n = bin2hex(random_bytes(8)) . '.' . $ext;
  return move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $n) ? $n : false;
}

function slugify($s) {
  $s = trim((string)$s);
  if (function_exists('transliterator_transliterate')) $s = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s) ?: $s;
  else $s = strtr(mb_strtolower($s, 'UTF-8'), ['à'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','œ'=>'oe','ÿ'=>'y']);
  $s = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');
  return substr($s, 0, 200);
}

// Date + time that the browser converts to the visitor's own clock (see footer.php)
function tm($ts, $long = false) {
  return '<time class="lt" datetime="' . date('c', $ts) . '" data-f="' . ($long ? 'l' : 's') . '">' . e(ldate($ts, !$long) . ', ' . date('H:i', $ts)) . '</time>';
}

function avatar($name, $file, $px = 40) {
  $st = 'width:' . (int)$px . 'px;height:' . (int)$px . 'px;min-width:' . (int)$px . 'px';
  if ($file && is_file(__DIR__ . '/uploads/' . $file))
    return '<img src="uploads/' . e($file) . '" alt="" class="rounded-full object-cover shrink-0 bg-stone-200" style="' . $st . '">';
  $i = mb_strtoupper(mb_substr(trim((string)$name) ?: '?', 0, 1));
  return '<span class="rounded-full bg-brand text-white font-bold inline-flex items-center justify-center shrink-0" style="' . $st . ';font-size:' . round($px * 0.42) . 'px" aria-hidden="true">' . e($i) . '</span>';
}

/* ------------------------------ story queries ----------------------------- */
// Language filter. By default stories follow the language the visitor chose (LANG);
// ?l=en|rw|fr picks one language and ?l=all shows every language. Returns [sql, args].
function lf() {
  $l = $_GET['l'] ?? '';
  if ($l === 'all') return ['1', []];
  return ['a.lang=?', [isset(LANGS[$l]) ? $l : LANG]];
}
function lf_current() { $l = $_GET['l'] ?? ''; return $l === 'all' ? 'all' : (isset(LANGS[$l]) ? $l : LANG); }

// Name of a category/word in a given language (stories keep the language their writer chose)
function tl($s, $lang) { global $TR; return $lang === 'en' ? $s : ($TR[$lang][$s] ?? $s); }

const STORY_FROM = " FROM articles a JOIN categories c ON c.id=a.category_id JOIN users u ON u.id=a.author_id WHERE a.status='published' AND ";
function articles($where = '1', $args = [], $limit = 12, $offset = 0, $hot = 0) { // published stories, newest first ($hot: 0 normal, 1 hot only, null both)
  global $pdo;
  $q = $pdo->prepare('SELECT a.*, c.name cat, c.slug cslug, u.name author, u.avatar author_avatar, COALESCE(a.published_at, a.created_at) pub'
    . STORY_FROM . '(' . $where . ')' . ($hot === null ? '' : ' AND a.is_hot=' . ((int)$hot ? 1 : 0)) . ' ORDER BY pub DESC, a.id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset);
  $q->execute($args); return $q->fetchAll();
}
function count_articles($where = '1', $args = [], $hot = 0) {
  global $pdo; $q = $pdo->prepare('SELECT COUNT(*)' . STORY_FROM . '(' . $where . ')' . ($hot === null ? '' : ' AND a.is_hot=' . ((int)$hot ? 1 : 0))); $q->execute($args); return (int)$q->fetchColumn();
}

function lang_chips($base) { // language filter chips under a heading
  $cur = lf_current(); $sep = strpos($base, '?') !== false ? '&' : '?';
  echo '<div class="flex flex-wrap gap-2 my-4 font-ui text-sm">';
  foreach (LANGS + ['all' => t('All languages')] as $k => $n) {
    echo '<a href="' . e($base . $sep . 'l=' . $k) . '" class="px-4 py-1.5 rounded-full ' . ($cur === $k ? 'bg-brand text-white' : 'bg-white border border-stone-300 hover:bg-sun') . '">' . e($n) . '</a>';
  }
  echo '</div>';
}

// Shown when the chosen language has no stories yet
function no_stories_here($msg = null) {
  echo '<p class="text-lg">' . e($msg ?? t('No stories in this language yet.')) . ' <a href="?l=all" class="text-brand underline">' . e(t('Show all languages')) . '</a></p>';
}

function pager($total, $per, $page) {
  $pages = (int)ceil($total / max(1, $per)); if ($pages <= 1) return;
  $url = fn($p) => '?' . http_build_query(array_merge($_GET, ['p' => $p]));
  $from = max(1, $page - 2); $to = min($pages, $page + 2); $b = 'px-3.5 py-1.5 rounded-lg border border-stone-300 bg-white hover:bg-sun';
  echo '<nav class="flex flex-wrap justify-center gap-2 mt-8 font-ui text-sm" aria-label="Pages">';
  if ($page > 1) echo '<a href="' . e($url($page - 1)) . '" class="' . $b . '" rel="prev">&lsaquo;</a>';
  if ($from > 1) echo '<a href="' . e($url(1)) . '" class="' . $b . '">1</a>' . ($from > 2 ? '<span class="px-1 py-1.5">…</span>' : '');
  for ($i = $from; $i <= $to; $i++) echo '<a href="' . e($url($i)) . '" class="px-3.5 py-1.5 rounded-lg border ' . ($i == $page ? 'bg-brand border-brand text-white font-bold' : 'border-stone-300 bg-white hover:bg-sun') . '"' . ($i == $page ? ' aria-current="page"' : '') . '>' . $i . '</a>';
  if ($to < $pages) echo ($to < $pages - 1 ? '<span class="px-1 py-1.5">…</span>' : '') . '<a href="' . e($url($pages)) . '" class="' . $b . '">' . $pages . '</a>';
  if ($page < $pages) echo '<a href="' . e($url($page + 1)) . '" class="' . $b . '" rel="next">&rsaquo;</a>';
  echo '</nav>';
}

// One story card (home, category, search, related)
function card($a) {
  $hot = !empty($a['is_hot']);
  $ex = $a['summary'] !== null && $a['summary'] !== '' ? $a['summary'] : ($hot ? t('This story is for subscribers only.') : mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($a['body']))), 0, 140) . '…');
  $href = 'article.php?s=' . e($a['slug']); $vt = $a['video_type'] ?? 'none';
  $thumb = $a['image'] ? 'uploads/' . e($a['image']) : ($vt === 'youtube' && $a['video'] ? 'https://img.youtube.com/vi/' . e($a['video']) . '/hqdefault.jpg' : '');
  echo '<article class="bg-white border border-stone-200 rounded-2xl overflow-hidden flex flex-col hover:shadow-lg transition-shadow">'
    . '<a href="' . $href . '" class="block relative aspect-[16/10] bg-stone-100" tabindex="-1" aria-hidden="true">'
    . ($thumb ? '<img src="' . $thumb . '" alt="" loading="lazy" class="w-full h-full object-cover">' : '<img src="assets/logo.png" alt="" class="w-full h-full object-contain p-10 opacity-60">')
    . ($vt !== 'none' ? '<span class="absolute top-2 left-2 bg-ink/85 text-white text-xs font-bold px-2.5 py-1 rounded-full">&#9654; ' . e(t('Video')) . '</span>' : '')
    . '</a><div class="p-4 flex-1 flex flex-col">'
    . '<div class="flex flex-wrap gap-2 items-center"><a href="category.php?s=' . e($a['cslug']) . '" class="bg-sun text-xs font-bold px-2.5 py-0.5 rounded-full hover:bg-ink hover:text-white transition-colors">' . e(tl($a['cat'], $a['lang'])) . '</a>'
    . ($hot ? '<span class="bg-red-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full">' . e(t('Hot')) . '</span>' : '')
    . '<span class="border border-brand text-brand text-xs font-bold px-2 py-0.5 rounded-full">' . strtoupper(e($a['lang'])) . '</span></div>'
    . '<h3 class="font-display font-bold text-xl leading-snug mt-2"><a href="' . $href . '" class="hover:text-brand">' . e($a['title']) . '</a></h3>'
    . '<p class="text-stone-600 mt-1.5 text-[0.95rem] leading-6">' . e($ex) . '</p>'
    . '<div class="mt-auto pt-3 flex items-center gap-2 text-sm text-stone-600">' . avatar($a['author'], $a['author_avatar'], 24) . '<span class="truncate">' . e($a['author']) . ' &middot; ' . tm(strtotime($a['pub'])) . '</span></div>'
    . '<div class="mt-3">' . share_bar($a, true) . '</div></div></article>';
}

// Adverts for a placement ('top', 'inline' or 'bottom'). The top banner shows up to 4 active ads side by side
// (random ones when more exist, so every advertiser gets turns); other placements show 1. Does nothing if the ads table is missing.
function show_ad($place, $limit = null) {
  global $pdo; $limit = max(1, min(6, (int)($limit ?? ($place === 'top' ? 4 : 1))));
  try {
    $q = $pdo->prepare("SELECT * FROM ads WHERE status='active' AND placement=? AND (starts_at IS NULL OR starts_at<=CURDATE()) AND (ends_at IS NULL OR ends_at>=CURDATE()) ORDER BY RAND() LIMIT $limit");
    $q->execute([$place]); $ads = $q->fetchAll(); if (!$ads) return;
    $pdo->prepare('UPDATE ads SET views=views+1 WHERE id IN (' . implode(',', array_map('intval', array_column($ads, 'id'))) . ')')->execute();
  } catch (Throwable $x) { return; }
  $n = count($ads);
  $cols = $n === 1 ? '' : ($n === 2 ? 'sm:grid-cols-2' : ($n === 3 ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2 lg:grid-cols-4'));
  echo '<aside class="max-w-6xl mx-auto px-4 my-6" aria-label="' . e(t('Advertisement')) . '"><p class="text-[11px] uppercase tracking-wide text-stone-500 mb-1">' . e(t('Advertisement')) . '</p><div class="grid gap-3 ' . $cols . '">';
  foreach ($ads as $ad) {
    $href = 'ad.php?id=' . (int)$ad['id'];
    if ($ad['image']) echo '<a href="' . $href . '" target="_blank" rel="sponsored noopener" class="block"><img src="uploads/' . e($ad['image']) . '" alt="' . e($ad['title']) . '" class="w-full ' . ($n === 1 ? 'max-h-64' : 'h-28 sm:h-32') . ' object-cover rounded-xl border border-stone-200 bg-white"></a>';
    else echo '<a href="' . $href . '" target="_blank" rel="sponsored noopener" class="block bg-white border border-stone-200 rounded-xl p-4 hover:bg-sun/30 transition-colors"><b class="font-display text-lg">' . e($ad['title']) . '</b>'
      . ($ad['body'] ? '<span class="block text-stone-600 mt-1">' . e($ad['body']) . '</span>' : '') . '</a>';
  }
  echo '</div></aside>';
}

/* ------------------------------ social sharing ---------------------------- */
function social_icon($k, $px = 18) {
  static $I = [
    'whatsapp' => '<path fill="currentColor" d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.4A10 10 0 1 0 12 2zm5.2 13.9c-.2.6-1.2 1.2-1.7 1.2-.5.1-1 .2-3.3-.7-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .6l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.1 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.4.1.2.1.8-.1 1.4z"/>',
    'x' => '<path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
    'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/>',
    'facebook' => '<path fill="currentColor" d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12z"/>'
  ];
  return '<svg viewBox="0 0 24 24" width="' . $px . '" height="' . $px . '" aria-hidden="true">' . $I[$k] . '</svg>';
}

function share_bar($a, $compact = false) {
  $url = site_url('article.php?s=' . rawurlencode($a['slug'])); $ti = $a['title']; $u = rawurlencode($url);
  $base = 'text-white font-medium font-ui hover:opacity-85 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand ';
  $cls = $base . ($compact ? 'inline-flex items-center justify-center w-9 h-9 rounded-full' : 'inline-flex items-center gap-2 text-sm px-3.5 py-2 rounded-full');
  $nets = [['facebook', 'Facebook', 'bg-[#1877f2]', 'https://www.facebook.com/sharer/sharer.php?u=' . $u],
           ['x', 'X', 'bg-black', 'https://twitter.com/intent/tweet?url=' . $u . '&text=' . rawurlencode($ti)],
           ['whatsapp', 'WhatsApp', 'bg-[#25d366]', 'https://wa.me/?text=' . rawurlencode($ti . ' ' . $url)]];
  $o = '<div class="flex flex-wrap items-center gap-x-2 gap-y-1" role="group" aria-label="' . e(t('Share this story')) . '">';
  $o .= '<span class="text-sm font-bold text-ink mr-1">' . e(t('Share')) . '</span>';
  foreach ($nets as [$k, $name, $bg, $href]) { $lbl = t('Share on ' . $name);
    $o .= '<a class="' . $cls . ' ' . $bg . '" target="_blank" rel="noopener noreferrer" href="' . e($href) . '" aria-label="' . e($lbl) . '" title="' . e($lbl) . '">' . social_icon($k) . ($compact ? '' : e($name)) . '</a>'; }
  $lbl = t('Share on Instagram');
  $o .= '<button type="button" class="' . $cls . ' bg-[#c13584]" data-ig data-url="' . e($url) . '" data-title="' . e($ti) . '" data-copied="' . e(t('Link copied. Paste it in your Instagram story, post or message.')) . '" aria-label="' . e($lbl) . '" title="' . e($lbl) . '">' . social_icon('instagram') . ($compact ? '' : 'Instagram') . '</button>';
  return $o . '<span class="text-xs text-brand w-full mt-1 empty:hidden" role="status" aria-live="polite" data-share-msg></span></div>';
}

/* ------------------------------ video news -------------------------------- */
function youtube_id($s) {
  $s = trim((string)$s); if (preg_match('/^[A-Za-z0-9_-]{11}$/', $s)) return $s;
  if (preg_match('~^https?://(?:www\.|m\.|music\.)?(?:youtube\.com|youtube-nocookie\.com)/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/|v/)([A-Za-z0-9_-]{11})~i', $s, $m)) return $m[1];
  if (preg_match('~^https?://youtu\.be/([A-Za-z0-9_-]{11})~i', $s, $m)) return $m[1];
  return null;
}

function save_video($f) { // InfinityFree allows uploads up to 10 MB
  if (empty($f['name']) || ($f['error'] ?? 0) === UPLOAD_ERR_NO_FILE) return null;
  if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) return 'big';
  if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 10 * 1024 * 1024) return false;
  $ext = ['video/mp4' => 'mp4', 'video/webm' => 'webm'][mime_content_type($f['tmp_name'])] ?? null; if (!$ext) return false;
  $n = bin2hex(random_bytes(8)) . '.' . $ext;
  return move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $n) ? $n : false;
}

function drop_video($type, $file) {
  if ($type === 'upload' && preg_match('/^[a-f0-9]{16}\.(mp4|webm)$/', (string)$file)) @unlink(__DIR__ . '/uploads/' . $file);
}

function post_too_big() {
  return $_SERVER['REQUEST_METHOD'] === 'POST' && !$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

function video_input($old = []) {
  $ot = $old['video_type'] ?? 'none'; $ov = $old['video'] ?? null; $mode = $_POST['video_mode'] ?? ($ot === 'none' ? 'none' : 'keep');
  if ($mode === 'none') return ['none', null, ''];
  if ($mode === 'youtube') { $id = youtube_id($_POST['video_url'] ?? ''); return $id ? ['youtube', $id, ''] : [$ot, $ov, t('Enter a valid YouTube video link.')]; }
  if ($mode === 'upload') { $n = save_video($_FILES['video'] ?? []);
    if ($n === null) return $ot === 'upload' ? ['upload', $ov, ''] : [$ot, $ov, t('Choose a video file to upload.')];
    if ($n === 'big') return [$ot, $ov, t('The upload is too large for this server. Max upload size on InfinityFree is 10 MB.')];
    if ($n === false) return [$ot, $ov, t('Video must be MP4 or WEBM and under 10 MB.')];
    return ['upload', $n, '']; }
  return [$ot, $ov, ''];
}

function video_player($a) {
  $vt = $a['video_type'] ?? 'none'; if ($vt === 'none' || empty($a['video'])) return '';
  $poster = !empty($a['image']) ? 'uploads/' . e($a['image']) : '';
  if ($vt === 'upload') { $ext = strtolower(pathinfo($a['video'], PATHINFO_EXTENSION));
    return '<div class="mt-5 rounded-2xl overflow-hidden bg-black"><video controls playsinline preload="metadata" class="w-full max-h-[75vh]"' . ($poster ? ' poster="' . $poster . '"' : '') . '><source src="uploads/' . e($a['video']) . '" type="video/' . ($ext === 'webm' ? 'webm' : 'mp4') . '">' . e(t('Your browser does not support video playback.')) . '</video></div>'; }
  return '<div class="mt-5 rounded-2xl overflow-hidden aspect-video bg-black"><iframe class="w-full h-full" src="https://www.youtube-nocookie.com/embed/' . e(rawurlencode($a['video'])) . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
}

/* ------------------------------ email + database upgrades ----------------- */
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/includes/members.php';
require_once __DIR__ . '/includes/schema.php';
ensure_schema();
