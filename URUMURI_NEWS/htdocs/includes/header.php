<?php
// Page layout (top of every page). Pages set $title (and optionally $og) before including this file.
require_once __DIR__ . '/../config.php';

$cats = $pdo->query('SELECT * FROM categories ORDER BY id')->fetchAll();
$h_script = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
$adpage = !in_array($h_script, ['admin', 'ads', 'articles', 'dashboard', 'edit', 'profile', 'team', 'team_report', 'mail_settings', 'login', 'install', 'unsubscribe', 'plans', 'payments', 'account', 'member_login', 'member_register'], true);
$h_user = me(); $h_member = member();
$h_title = (!isset($title) || $title === 'Home') ? 'URUMURI NEWS - ' . t('News that brings light.') : (string)$title . ' | URUMURI NEWS';
$h_og = $og ?? ['title' => 'URUMURI NEWS', 'desc' => t('News that brings light.'), 'url' => site_url(basename($_SERVER['SCRIPT_NAME'] ?? 'index.php')), 'image' => site_url('assets/logo.png')];
$h_curcat = ($h_script === 'category') ? ($_GET['s'] ?? '') : null;
$h_qs = fn($extra) => '?' . http_build_query(array_merge(array_diff_key($_GET, ['l' => 1, 'p' => 1]), $extra));
?><!doctype html>
<html lang="<?= e(LANG) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($h_title) ?></title>
<meta name="description" content="<?= e($h_og['desc']) ?>">
<meta property="og:site_name" content="URUMURI NEWS">
<meta property="og:type" content="<?= isset($og) ? 'article' : 'website' ?>">
<meta property="og:title" content="<?= e($h_og['title']) ?>">
<meta property="og:description" content="<?= e($h_og['desc']) ?>">
<meta property="og:url" content="<?= e($h_og['url']) ?>">
<meta property="og:image" content="<?= e($h_og['image']) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/png" href="assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
tailwind.config = { theme: { extend: {
  colors: { brand: '#0054bb', sun: '#ffc400', ink: '#0b1b3a', navy: '#06285f', paper: '#f5f7fb' },
  fontFamily: { display: ['"Playfair Display"', 'Georgia', 'serif'], ui: ['"Source Sans 3"', 'system-ui', 'sans-serif'], sans: ['"Source Sans 3"', 'system-ui', 'sans-serif'] }
} } };
</script>
<style>
  html { scroll-behavior: smooth; }
  body { font-family: "Source Sans 3", system-ui, sans-serif; }
  .tick { display: inline-block; white-space: nowrap; padding-left: 100%; animation: tick 60s linear infinite; }
  .tick:hover { animation-play-state: paused; }
  @keyframes tick { to { transform: translateX(-100%); } }
  @media (prefers-reduced-motion: reduce) { .tick { animation: none; padding-left: 0; } html { scroll-behavior: auto; } }
  .yt { position: relative; aspect-ratio: 16/9; background: #000; }
  .scroll-x { scrollbar-width: thin; }
  [hidden] { display: none !important; }
</style>
</head>
<body class="bg-paper text-ink antialiased min-h-screen flex flex-col">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-sun focus:text-ink focus:px-4 focus:py-2"><?= e(t('Skip to content')) ?></a>

<div class="bg-navy text-stone-200 text-sm font-ui">
  <div class="max-w-6xl mx-auto px-4 py-1.5 flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
    <p class="flex items-center gap-3"><span id="kd"><?= e(ldate(time())) ?></span><span id="kt" class="font-bold text-sun"></span><span id="kc" title="Kigali"></span></p>
    <div class="flex items-center gap-1" role="group" aria-label="Language">
      <?php foreach (LANGS as $h_k => $h_n): ?>
        <a href="<?= e($h_qs(['lang' => $h_k])) ?>" hreflang="<?= $h_k ?>" class="px-2.5 py-0.5 rounded-full <?= LANG === $h_k ? 'bg-sun text-ink font-bold' : 'hover:bg-white/10' ?>"><?= e($h_n) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<header class="bg-white border-b border-stone-200 sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-4 py-2.5 flex flex-wrap items-center gap-x-5 gap-y-2">
    <a href="index.php" class="shrink-0" aria-label="URUMURI NEWS - <?= e(t('Home')) ?>"><img src="assets/logo.png" alt="URUMURI NEWS" class="h-12 w-auto"></a>
    <form action="search.php" method="get" role="search" class="flex-1 min-w-[12rem] max-w-md flex order-last sm:order-none w-full sm:w-auto">
      <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" required placeholder="<?= e(t('Search news')) ?>" aria-label="<?= e(t('Search news')) ?>" class="w-full min-w-0 border border-stone-300 rounded-l-full px-4 py-2 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun">
      <button class="bg-brand text-white font-bold px-4 rounded-r-full hover:bg-ink transition-colors"><?= e(t('Search')) ?></button>
    </form>
    <nav class="ml-auto flex items-center gap-4 font-ui text-sm font-medium" aria-label="Account">
      <?php if (setting('youtube_channel_id') !== ''): ?><a href="live.php" class="inline-flex items-center gap-1.5 bg-red-600 text-white px-3 py-1 rounded-full font-bold hover:bg-red-700"><span class="w-2 h-2 rounded-full bg-white animate-pulse"></span><?= e(t('Live')) ?></a><?php endif; ?>
      <?php if ($h_member): ?><a href="account.php" class="hover:text-brand"><?= e(t('My account')) ?></a><a href="member_logout.php" class="hover:text-brand"><?= e(t('Log out')) ?></a><?php endif; ?>
      <?php if ($h_user): ?>
        <?php if ($h_user['role'] === 'admin'): ?><a href="admin.php" class="hover:text-brand"><?= e(t('Admin panel')) ?></a><?php endif; ?>
        <a href="dashboard.php" class="hover:text-brand"><?= e(t('Dashboard')) ?></a>
        <a href="logout.php" class="hover:text-brand"><?= e(t('Log out')) ?></a>
      <?php endif; // visitors get no Log in link here: staff log in from the footer link or /login.php ?>
    </nav>
  </div>
  <nav class="border-t border-stone-100 bg-white" aria-label="Categories">
    <div class="max-w-6xl mx-auto px-4 flex gap-1 overflow-x-auto scroll-x font-ui font-medium text-[0.95rem] whitespace-nowrap">
      <a href="index.php" class="px-3 py-2.5 border-b-4 <?= $h_script === 'index' ? 'border-sun text-brand font-bold' : 'border-transparent hover:text-brand' ?>"><?= e(t('Home')) ?></a>
      <a href="hot.php" class="px-3 py-2.5 border-b-4 inline-flex items-center gap-1.5 <?= $h_script === 'hot' ? 'border-red-600 text-red-700 font-bold' : 'border-transparent text-red-600 font-bold hover:text-red-700' ?>"><span class="w-2 h-2 rounded-full bg-red-600"></span><?= e(t('Hot news')) ?></a>
      <?php foreach ($cats as $h_c): ?>
        <a href="category.php?s=<?= e($h_c['slug']) ?>" class="px-3 py-2.5 border-b-4 <?= $h_curcat === $h_c['slug'] ? 'border-sun text-brand font-bold' : 'border-transparent hover:text-brand' ?>"><?= e(t($h_c['name'])) ?></a>
      <?php endforeach; ?>
    </div>
  </nav>
</header>
<?php if ($adpage) show_ad('top'); ?>
<div id="main" class="flex-1">
