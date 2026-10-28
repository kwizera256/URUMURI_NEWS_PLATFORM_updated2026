<?php $em = setting('contact_email') ?: SITE_EMAIL; $ph = setting('contact_phone'); $wa = setting('whatsapp'); $ad = setting('contact_address'); $sub = $_GET['sub'] ?? '';
$wa_url = $wa ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $wa) : '';
$I = [ // icons (24x24)
 'mail' => '<rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
 'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
 'pin' => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="2.5" fill="currentColor"/>',
 'whatsapp' => '<path fill="currentColor" d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.4A10 10 0 1 0 12 2zm5.2 13.9c-.2.6-1.2 1.2-1.7 1.2-.5.1-1 .2-3.3-.7-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .6l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.1 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.4.1.2.1.8-.1 1.4z"/>',
 'x' => '<path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
 'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/>',
 'facebook' => '<path fill="currentColor" d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12z"/>'];
$svg = fn($k, $c = 'w-5 h-5') => '<svg viewBox="0 0 24 24" class="' . $c . '" aria-hidden="true">' . $I[$k] . '</svg>';
$dot = 'w-9 h-9 rounded-full bg-white/10 text-sun flex items-center justify-center shrink-0';
$soc = [['x_url', 'X', 'x', setting('x_url')], ['instagram_url', 'Instagram', 'instagram', setting('instagram_url')], ['facebook_url', 'Facebook', 'facebook', setting('facebook_url')], ['wa', 'WhatsApp', 'whatsapp', $wa_url]]; ?>
</div><!-- /#main -->
<?php if (!empty($adpage)) show_ad('bottom'); ?>
<footer class="bg-navy text-stone-300 mt-12 border-t-4 border-sun">
  <div class="max-w-6xl mx-auto px-4 py-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
    <div><img src="assets/logo.png" alt="URUMURI NEWS" class="h-20 w-auto bg-white rounded-xl p-2"><p class="text-sm mt-3"><?= t('News that brings light.') ?></p></div>
    <div>
      <h3 class="font-display font-bold text-white text-lg mb-3"><?= t('Contact us') ?></h3>
      <ul class="space-y-3 text-sm">
        <?php if ($em): ?><li class="flex items-center gap-3"><span class="<?= $dot ?>"><?= $svg('mail') ?></span><a href="mailto:<?= e($em) ?>" class="hover:text-sun break-all"><?= e($em) ?></a></li><?php endif; ?>
        <?php if ($ph): ?><li class="flex items-center gap-3"><span class="<?= $dot ?>"><?= $svg('phone') ?></span><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $ph)) ?>" class="hover:text-sun"><?= e($ph) ?></a></li><?php endif; ?>
        <?php if ($wa_url): ?><li class="flex items-center gap-3"><span class="<?= $dot ?>"><?= $svg('whatsapp') ?></span><a href="<?= e($wa_url) ?>" target="_blank" rel="noopener" class="hover:text-sun">WhatsApp: <?= e($wa) ?></a></li><?php endif; ?>
        <?php if ($ad): ?><li class="flex items-center gap-3"><span class="<?= $dot ?>"><?= $svg('pin') ?></span><span><?= e($ad) ?></span></li><?php endif; ?>
      </ul>
    </div>
    <div>
      <h3 class="font-display font-bold text-white text-lg mb-3"><?= t('Follow us') ?></h3>
      <div class="flex gap-3">
        <?php foreach ($soc as [$k, $label, $ic, $url]): if (!$url) continue; ?>
        <a href="<?= e($url) ?>" target="_blank" rel="noopener" aria-label="<?= $label ?>" title="<?= $label ?>" class="w-10 h-10 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-sun hover:text-ink transition-colors"><?= $svg($ic) ?></a>
        <?php endforeach; ?>
      </div>
      <h3 class="font-display font-bold text-white text-lg mt-6 mb-2"><?= t('Quick links') ?></h3>
      <ul class="space-y-1 text-sm"><li><a href="index.php" class="hover:text-sun"><?= t('Home') ?></a></li><li><a href="live.php" class="hover:text-sun"><?= t('Live') ?></a></li><li><a href="hot.php" class="hover:text-sun"><?= t('Hot news') ?></a></li><li><a href="<?= member() ? 'account.php' : 'member_login.php' ?>" class="hover:text-sun"><?= member() ? t('My account') : t('Member log in') ?></a></li><li><a href="privacy.php" class="hover:text-sun"><?= t('Privacy policy') ?></a></li><?php if (me()): ?><li><a href="dashboard.php" class="hover:text-sun"><?= t('Dashboard') ?></a></li><?php else: ?><li><a href="login.php" class="hover:text-sun"><?= t('Log in') ?></a></li><?php endif; ?></ul>
    </div>
    <div id="subscribe">
      <h3 class="font-display font-bold text-white text-lg mb-2"><?= t('Subscribe') ?></h3>
      <p class="text-sm mb-3"><?= t('Get the latest updates by email.') ?></p>
      <?php if ($sub === 'ok'): ?><p class="bg-sun text-ink text-sm p-2 rounded-lg mb-2"><?= t('Thanks for subscribing.') ?></p>
      <?php elseif ($sub === 'bad'): ?><p class="bg-red-100 text-red-800 text-sm p-2 rounded-lg mb-2"><?= t('Enter a valid email address.') ?></p><?php endif; ?>
      <form method="post" action="subscribe.php" class="flex">
        <input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
        <input type="email" name="email" required placeholder="<?= e(t('Your email address')) ?>" class="w-full min-w-0 rounded-l-full px-4 py-2 text-ink focus:outline-none focus:ring-2 focus:ring-sun">
        <button class="bg-sun text-ink font-bold px-4 rounded-r-full hover:bg-white transition-colors"><?= t('Subscribe') ?></button>
      </form>
    </div>
  </div>
  <div class="border-t border-white/10"><div class="max-w-6xl mx-auto px-4 py-4 text-sm flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
    <p>&copy; <?= date('Y') ?> URUMURI NEWS. <?= t('All rights reserved.') ?></p>
    <a href="privacy.php" class="underline hover:text-sun"><?= t('Privacy policy') ?></a>
    <button type="button" id="cookie-settings" class="underline hover:text-sun"><?= t('Cookie settings') ?></button>
  </div></div>
</footer>
<script>
var DT=<?= json_encode($DT[LANG], JSON_UNESCAPED_UNICODE) ?>;
(function(){
var p=function(n){return n<10?'0'+n:n},hm=function(d){return p(d.getHours())+':'+p(d.getMinutes())};
function dstr(d,long){var m=DT.m[d.getMonth()];if(!long)m=Array.from(m).slice(0,3).join('');return d.getDate()+' '+m+' '+d.getFullYear()}
function conv(){document.querySelectorAll('time.lt').forEach(function(e){var d=new Date(e.getAttribute('datetime'));if(isNaN(d))return;var l=e.dataset.f==='l';e.textContent=(l?DT.d[d.getDay()]+', ':'')+dstr(d,l)+', '+hm(d)})}
var t=document.getElementById('kt'),c=document.getElementById('kc'),kd=document.getElementById('kd');
function tick(){var d=new Date();if(t)t.textContent=hm(d);if(kd)kd.textContent=DT.d[d.getDay()]+', '+dstr(d,true)}
function temp(){if(!c)return;fetch('https://api.open-meteo.com/v1/forecast?latitude=-1.9441&longitude=30.0619&current=temperature_2m&timezone=Africa%2FKigali').then(function(r){return r.json()}).then(function(d){c.textContent=Math.round(d.current.temperature_2m)+'\u00B0C'}).catch(function(){})}
conv();tick();temp();setInterval(tick,30000);setInterval(temp,900000);
})();
</script>
<script>
document.addEventListener('click',function(e){var b=e.target.closest('.pw-eye');if(!b)return;var i=b.parentNode.querySelector('input'),s=i.type==='password';i.type=s?'text':'password';
b.setAttribute('aria-label',s?b.dataset.hide:b.dataset.show);b.title=b.getAttribute('aria-label');b.setAttribute('aria-pressed',s);b.querySelector('.slash').style.display=s?'':'none';i.focus()});
document.querySelectorAll('input[data-confirm]').forEach(function(c){var p=c.form.querySelector('[name="'+c.dataset.confirm+'"]');
function chk(){c.setCustomValidity(c.value===p.value?'':c.dataset.mismatch)}c.addEventListener('input',chk);p.addEventListener('input',chk);chk()});
</script>
<script>
document.querySelectorAll('[data-ig]').forEach(function(b){b.addEventListener('click',function(){
  var u=b.dataset.url,t=b.dataset.title,m=b.parentNode.querySelector('[data-share-msg]');
  function copy(){var d=function(){m.textContent=b.dataset.copied;window.open('https://www.instagram.com/','_blank','noopener')};
    if(navigator.clipboard&&window.isSecureContext)navigator.clipboard.writeText(u).then(d,function(){prompt('',u)});else{var x=document.createElement('textarea');x.value=u;document.body.appendChild(x);x.select();try{document.execCommand('copy');d()}catch(e){prompt('',u)}document.body.removeChild(x)}}
  if(navigator.share){navigator.share({title:t,url:u}).catch(function(e){if(e&&e.name!=='AbortError')copy()})}else copy();
})});
</script>
<div id="cookie-banner" role="dialog" aria-labelledby="cb-t" <?= !empty($_COOKIE['urumuri_consent']) ? 'hidden' : '' ?> class="fixed inset-x-0 bottom-0 z-50 p-3 sm:p-4">
  <div class="max-w-3xl mx-auto bg-white text-ink border border-stone-300 rounded-2xl shadow-2xl p-4 sm:p-5 flex flex-col sm:flex-row gap-4 sm:items-center">
    <div class="flex-1 text-sm"><p id="cb-t" class="font-display font-bold text-lg"><?= t('We use cookies') ?></p>
      <p class="mt-1"><?= t('Some cookies are needed for the site to work. With your permission we also use analytics cookies to count visits. You can change your choice at any time.') ?> <a href="privacy.php" class="text-brand underline"><?= t('Read our privacy policy') ?></a></p></div>
    <div class="flex gap-3 shrink-0">
      <button type="button" id="cb-no" class="border-2 border-brand text-brand font-bold px-5 py-2 rounded-lg hover:bg-paper active:scale-95 transition"><?= t('Reject') ?></button>
      <button type="button" id="cb-yes" class="bg-brand text-white border-2 border-brand font-bold px-5 py-2 rounded-lg hover:bg-ink active:scale-95 transition"><?= t('Accept') ?></button>
    </div>
  </div>
</div>
<script>
(function(){
var GA=<?= json_encode(GA_ID !== 'G-XXXXXXXXXX' ? GA_ID : '') ?>,b=document.getElementById('cookie-banner');
function save(v){document.cookie='urumuri_consent='+v+';max-age=15552000;path=/;SameSite=Lax'+(location.protocol==='https:'?';Secure':'')}
function clearGA(){var p=location.hostname.split('.'),d=[''];for(var i=0;i<p.length-1;i++){d.push(';domain='+p.slice(i).join('.'));d.push(';domain=.'+p.slice(i).join('.'))}
  document.cookie.split(';').forEach(function(c){var n=c.split('=')[0].trim();if(n==='_ga'||n==='_gid'||n.indexOf('_ga_')===0||n.indexOf('_gat')===0)d.forEach(function(x){document.cookie=n+'=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/'+x})})}
function loadGA(){if(!GA||typeof window.gtag==='function')return;window.dataLayer=window.dataLayer||[];window.gtag=function(){dataLayer.push(arguments)};
  var s=document.createElement('script');s.async=true;s.src='https://www.googletagmanager.com/gtag/js?id='+encodeURIComponent(GA);document.head.appendChild(s);gtag('js',new Date());gtag('config',GA)}
document.getElementById('cb-yes').onclick=function(){save('accepted');if(GA)window['ga-disable-'+GA]=false;loadGA();b.hidden=true};
document.getElementById('cb-no').onclick=function(){save('rejected');if(GA)window['ga-disable-'+GA]=true;clearGA();b.hidden=true};
document.getElementById('cookie-settings').onclick=function(){b.hidden=false;document.getElementById('cb-yes').focus()};
})();
</script>
<script>
document.addEventListener('click',function(e){var b=e.target.closest('.yt button');if(!b)return;var w=b.parentNode,f=document.createElement('iframe');
f.src='https://www.youtube-nocookie.com/embed/'+encodeURIComponent(w.dataset.yt)+'?autoplay=1&rel=0';f.title=w.dataset.title||'Video';f.className='absolute inset-0 w-full h-full';
f.allow='autoplay; encrypted-media; picture-in-picture; fullscreen';f.allowFullscreen=true;f.referrerPolicy='strict-origin-when-cross-origin';w.innerHTML='';w.appendChild(f)});
</script>
<?php if (mail_cfg()['auto'] && mail_pending() && time() - (int)setting('mail_last_run') > 30): ?><script>fetch('mail_worker.php',{method:'POST',keepalive:true}).catch(function(){})</script><?php endif; ?>
</body></html>
