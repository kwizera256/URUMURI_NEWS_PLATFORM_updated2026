<?php // Shared by dashboard.php (new story) and edit.php. Needs: $a (array), $cats, $submit, $statusField
$in = 'w-full border border-stone-300 rounded-lg p-2 mt-1 focus:outline-none focus:border-brand focus:ring-2 focus:ring-sun'; $v = fn($k) => e($a[$k] ?? ''); ?>
<form method="post" enctype="multipart/form-data" class="space-y-4 bg-white border border-stone-200 rounded-2xl p-6">
  <input type="hidden" name="t" value="<?= csrf() ?>">
  <label class="block"><?= t('Headline') ?><input name="title" required maxlength="255" value="<?= $v('title') ?>" class="<?= $in ?>"></label>
  <div class="grid sm:grid-cols-2 gap-4">
    <?php $sl0 = isset(LANGS[$a['lang'] ?? '']) ? $a['lang'] : LANG; // category names follow the language chosen for the story ?>
    <label class="block"><?= t('Category') ?><select name="category" id="f-cat" required class="<?= $in ?>">
      <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" data-en="<?= e($c['name']) ?>" data-rw="<?= e(tl($c['name'], 'rw')) ?>" data-fr="<?= e(tl($c['name'], 'fr')) ?>" <?= ($a['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e(tl($c['name'], $sl0)) ?></option><?php endforeach; ?></select></label>
    <label class="block"><?= t('Language of the story') ?><select name="lang" id="f-lang" required class="<?= $in ?>">
      <?php foreach (LANGS as $k => $n): ?><option value="<?= $k ?>" <?= $sl0 === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
  </div>
  <label class="block"><?= t('Short summary') ?><input name="summary" maxlength="300" value="<?= $v('summary') ?>" class="<?= $in ?>"></label>
  <label class="block"><?= t('Story text') ?><textarea name="body" rows="10" required class="<?= $in ?>"><?= $v('body') ?></textarea></label>
  <label class="block"><?= t('Photo (JPG, PNG or WEBP, max 3 MB)') ?>
    <?php if (!empty($a['image'])): ?><img src="uploads/<?= e($a['image']) ?>" alt="" class="h-24 rounded-lg my-2"><?php endif; ?>
    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="block mt-1"></label>
  <?php $hv = $a['video_type'] ?? 'none'; $vm = $_POST['video_mode'] ?? ($hv === 'none' ? 'none' : 'keep'); ?>
  <fieldset id="vbox" class="border border-stone-200 rounded-xl p-4">
    <legend class="px-2 font-medium"><?= t('Video (optional)') ?></legend>
    <div class="flex flex-wrap gap-x-6 gap-y-2">
      <?php foreach (array_filter(['keep' => $hv !== 'none' ? 'Keep current video' : null, 'none' => 'No video', 'upload' => 'Upload a video file', 'youtube' => 'YouTube link']) as $k => $lbl): ?>
      <label class="flex items-center gap-2"><input type="radio" name="video_mode" value="<?= $k ?>" <?= $vm === $k ? 'checked' : '' ?> class="w-4 h-4 accent-[#0054bb]"><?= t($lbl) ?></label>
      <?php endforeach; ?>
    </div>
    <?php if ($hv !== 'none'): ?><p class="text-sm text-stone-600 mt-2"><?= $hv === 'upload' ? t('Current video: uploaded file') : t('Current video: YouTube') . ' (youtu.be/' . e($a['video']) . ')' ?></p><?php endif; ?>
    <div id="v-upload" hidden class="mt-3"><label class="block text-sm"><?= t('Video file (MP4 or WEBM, max 10 MB)') ?>
      <input type="file" name="video" accept="video/mp4,video/webm" class="block mt-1"></label></div>
    <div id="v-youtube" hidden class="mt-3"><label class="block text-sm"><?= t('YouTube video link') ?>
      <input name="video_url" value="<?= e($_POST['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..." class="<?= $in ?>"></label></div>
  </fieldset>
  <script>
  (function(){var f=document.getElementById('vbox'),u=document.getElementById('v-upload'),y=document.getElementById('v-youtube');
  function s(){var c=f.querySelector('input[name=video_mode]:checked'),v=c?c.value:'';u.hidden=v!=='upload';y.hidden=v!=='youtube'}
  f.addEventListener('change',s);s()})();
  </script>
  <?php if (can('can_publish')): ?>
  <label class="flex items-start gap-3 bg-sun/20 border border-sun rounded-xl p-3"><input type="checkbox" name="is_hot" value="1" <?= !empty($a['is_hot']) ? 'checked' : '' ?> class="w-5 h-5 mt-0.5 accent-[#0054bb]">
    <span><b><?= t('Hot news (paid subscribers only)') ?></b><br><span class="text-sm text-stone-600"><?= t('Only readers with a paid subscription can read the full text. Everyone sees the title, photo and summary.') ?></span></span></label>
  <?php endif; ?>
  <?php if (!empty($statusField)): ?>
  <label class="block"><?= t('Status') ?><select name="status" class="<?= $in ?>">
    <option value="pending" <?= ($a['status'] ?? '') === 'pending' ? 'selected' : '' ?>><?= t('Waiting for approval') ?></option>
    <option value="published" <?= ($a['status'] ?? '') === 'published' ? 'selected' : '' ?>><?= t('Published') ?></option></select></label>
  <?php endif; ?>
  <button class="bg-brand text-white font-bold px-6 py-2 rounded-lg hover:bg-ink active:scale-95 transition"><?= $submit ?></button>
</form>
<script>
(function(){var l=document.getElementById('f-lang'),c=document.getElementById('f-cat');if(!l||!c)return;
function relabel(){var k=l.value;Array.prototype.forEach.call(c.options,function(o){o.textContent=o.getAttribute('data-'+k)||o.getAttribute('data-en')})}
l.addEventListener('change',relabel);relabel()})();
</script>
