<?php require 'config.php'; need(); needcan('can_publish', 'can_edit_all'); $m = me();
$fl = ['st' => '', 'w' => '', 'l' => '', 'from' => '', 'to' => ''];
$src = $_SERVER['REQUEST_METHOD'] === 'POST' ? (array)($_POST['back'] ?? []) : $_GET;
$fl['st'] = in_array($src['st'] ?? '', ['pending', 'published']) ? $src['st'] : '';
$fl['w'] = (int)($src['w'] ?? 0) ?: '';
$fl['l'] = isset(LANGS[$src['l'] ?? '']) ? $src['l'] : '';
foreach (['from', 'to'] as $k) $fl[$k] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $src[$k] ?? '') ? $src[$k] : '';
$keep = array_filter($fl, fn($v) => $v !== '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check(); $id = (int)($_POST['id'] ?? 0); $do = $_POST['do'] ?? '';
  if ($do === 'publish' && can('can_publish')) { $pdo->prepare("UPDATE articles SET status='published', published_at=COALESCE(published_at,NOW()) WHERE id=?")->execute([$id]); if (function_exists('queue_story')) queue_story($id); }
  if ($do === 'unpublish' && can('can_publish')) $pdo->prepare("UPDATE articles SET status='pending', published_at=NULL WHERE id=?")->execute([$id]);
  if ($do === 'delete' && $m['role'] === 'admin') $pdo->prepare('DELETE FROM articles WHERE id=?')->execute([$id]);
  header('Location: articles.php?' . http_build_query($keep)); exit; }
$where = []; $args = [];
if ($fl['st']) { $where[] = 'a.status=?'; $args[] = $fl['st']; }
if ($fl['w']) { $where[] = 'a.author_id=?'; $args[] = $fl['w']; }
if ($fl['l']) { $where[] = 'a.lang=?'; $args[] = $fl['l']; }
if ($fl['from']) { $where[] = 'DATE(COALESCE(a.published_at,a.created_at))>=?'; $args[] = $fl['from']; }
if ($fl['to']) { $where[] = 'DATE(COALESCE(a.published_at,a.created_at))<=?'; $args[] = $fl['to']; }
$W = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$J = ' FROM articles a JOIN categories c ON c.id=a.category_id JOIN users u ON u.id=a.author_id' . $W;
$cq = $pdo->prepare('SELECT COUNT(*)' . $J); $cq->execute($args); $total = (int)$cq->fetchColumn();
$per = 25; $page = max(1, (int)($_GET['p'] ?? 1));
$q = $pdo->prepare('SELECT a.*, c.name cat, u.name author, u.avatar av' . $J . ' ORDER BY a.status="pending" DESC, COALESCE(a.published_at,a.created_at) DESC, a.id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per)); $q->execute($args);
$writers = $pdo->query("SELECT id,name FROM users WHERE role IN ('admin','writer') ORDER BY name")->fetchAll();
$title = 'Manage articles'; require 'includes/header.php'; $tok = '<input type="hidden" name="t" value="' . csrf() . '">'; ?>
<main class="max-w-5xl mx-auto px-4 mt-8">
  <h1 class="font-display font-extrabold text-3xl mb-3"><?= t('Manage articles') ?></h1>
  <?php if (isset($_GET['saved'])): ?><p class="bg-sun p-3 mb-4 rounded-lg"><?= t('Changes saved.') ?></p><?php endif; ?>
  <div class="flex gap-2 mb-3 font-ui text-sm flex-wrap"><?php foreach (['' => 'All', 'pending' => 'Waiting for approval', 'published' => 'Published'] as $k => $lbl): ?>
    <a href="?<?= e(http_build_query(array_filter(array_merge($fl, ['st' => $k])))) ?>" class="px-4 py-1.5 rounded-full <?= $fl['st'] === $k ? 'bg-brand text-white' : 'bg-white border border-stone-300 hover:bg-sun' ?>"><?= t($lbl) ?></a><?php endforeach; ?></div>
  <?php $fi = 'border border-stone-300 rounded-lg p-2 mt-1 w-full bg-white focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun'; ?>
  <form method="get" class="bg-white border border-stone-200 rounded-xl p-4 mb-4 grid sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end text-sm font-ui">
    <input type="hidden" name="st" value="<?= e($fl['st']) ?>">
    <label><?= t('Writer') ?><select name="w" class="<?= $fi ?>"><option value=""><?= t('All writers') ?></option>
      <?php foreach ($writers as $w): ?><option value="<?= $w['id'] ?>" <?= $fl['w'] == $w['id'] ? 'selected' : '' ?>><?= e($w['name']) ?></option><?php endforeach; ?></select></label>
    <label><?= t('Language') ?><select name="l" class="<?= $fi ?>"><option value=""><?= t('All languages') ?></option>
      <?php foreach (LANGS as $k => $n): ?><option value="<?= $k ?>" <?= $fl['l'] === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
    <label><?= t('From date') ?><input type="date" name="from" value="<?= e($fl['from']) ?>" class="<?= $fi ?>"></label>
    <label><?= t('To date') ?><input type="date" name="to" value="<?= e($fl['to']) ?>" class="<?= $fi ?>"></label>
    <div class="flex gap-2"><button class="bg-brand text-white font-bold px-4 py-2 rounded-lg hover:bg-ink active:scale-95 transition"><?= t('Filter') ?></button>
      <a href="articles.php" class="border border-stone-300 px-4 py-2 rounded-lg hover:bg-sun"><?= t('Reset') ?></a></div>
  </form>
  <p class="text-sm text-stone-600 mb-2"><?= $total ?> <?= t('stories found') ?></p>
  <div class="bg-white border border-stone-200 rounded-xl overflow-hidden divide-y">
    <?php foreach ($q as $a): ?><div class="p-3 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3 min-w-0"><?= avatar($a['author'], $a['av'], 36) ?>
        <div class="min-w-0"><p class="font-bold"><?= e($a['title']) ?><?php if (!empty($a['is_hot'])): ?> <span class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full align-middle"><?= e(t('Hot')) ?></span><?php endif; ?></p>
        <p class="text-sm text-stone-600"><?= e($a['author']) ?> &middot; <?= e(tl($a['cat'], $a['lang'])) ?> &middot; <?= e(LANGS[$a['lang']]) ?> &middot; <?= $a['status'] === 'published' ? t('Published') : t('Waiting for approval') ?> &middot; <?= tm(strtotime($a['published_at'] ?? $a['created_at'])) ?></p></div></div>
      <form method="post" class="flex gap-4 items-center"><?= $tok ?><?php foreach ($keep as $bk => $bv) echo '<input type="hidden" name="back[' . e($bk) . ']" value="' . e($bv) . '">'; ?><input type="hidden" name="id" value="<?= $a['id'] ?>">
        <?php if (can('can_edit_all')): ?><a href="edit.php?id=<?= $a['id'] ?>" class="text-brand font-bold"><?= t('Edit') ?></a><?php endif; ?>
        <?php if (can('can_publish')): ?><button name="do" value="<?= $a['status'] === 'published' ? 'unpublish' : 'publish' ?>" class="text-brand font-bold"><?= $a['status'] === 'published' ? t('Unpublish') : t('Publish') ?></button><?php endif; ?>
        <?php if ($m['role'] === 'admin'): ?><button name="do" value="delete" onclick="return confirm(<?= e(json_encode(t('Delete this story?'))) ?>)" class="text-red-700 font-bold"><?= t('Delete') ?></button><?php endif; ?></form>
    </div><?php endforeach; if (!$q->rowCount()) echo '<p class="p-3">' . t('Nothing to review.') . '</p>'; ?>
  </div>
  <?php pager($total, $per, $page); ?>
</main>
<?php require 'includes/footer.php'; ?>
