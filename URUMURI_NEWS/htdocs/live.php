<?php $title = 'Live'; require 'includes/header.php'; $yt = setting('youtube_channel_id'); ?>
<main class="max-w-4xl mx-auto px-4 mt-8">
  <h1 class="font-display font-extrabold text-3xl mb-4 border-l-8 border-sun pl-3"><?= t('URUMURI NEWS Live') ?></h1>
  <?php if ($yt): ?>
    <div class="aspect-video bg-ink rounded-2xl overflow-hidden">
      <iframe class="w-full h-full" src="https://www.youtube.com/embed/live_stream?channel=<?= e($yt) ?>" title="URUMURI NEWS Live" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
    </div>
    <a href="https://www.youtube.com/channel/<?= e($yt) ?>" target="_blank" rel="noopener" class="inline-block mt-4 bg-brand text-white font-bold px-5 py-2 rounded-lg active:scale-95 transition hover:bg-ink"><?= t('Open our YouTube channel') ?></a>
    <p class="text-sm text-stone-600 mt-3"><?= t('If the player shows no video, we are not live right now. Check the channel for the next broadcast.') ?></p>
  <?php else: ?>
    <p><?= t('The live stream is not set up yet. The admin can add the YouTube channel ID in the admin panel.') ?></p>
  <?php endif; ?>
</main>
<?php require 'includes/footer.php'; ?>
