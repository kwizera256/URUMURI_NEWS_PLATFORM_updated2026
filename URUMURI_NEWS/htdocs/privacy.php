<?php $title = 'Privacy policy'; require 'includes/header.php';
$em = setting('contact_email') ?: SITE_EMAIL; $mail = '<a href="mailto:' . e($em) . '" class="text-brand underline">' . e($em) . '</a>';
$r = fn($s) => str_replace('{email}', $mail, e($s)); // text is escaped, then {email} becomes a mail link
// Each section: h = heading, p = paragraphs, ul = bullet list, z = closing paragraph.
$P = [
'en' => ['intro' => 'URUMURI NEWS (“we”, “us”) publishes news in English, Kinyarwanda and French. This policy explains what personal information this website collects, why we collect it, and the choices you have. By using the site you agree to this policy. You can accept or reject optional cookies at any time.',
 'upd' => 'Last updated',
 's' => [
  ['h' => 'Who is responsible', 'p' => ['URUMURI NEWS is responsible for this website. You can reach us at {email}.']],
  ['h' => 'What we collect', 'ul' => [
    'Visitors: you can read all news without an account. Like every website, our server records technical data such as your IP address, browser type and the pages you open. Each story keeps a total view counter that does not identify you.',
    'Newsletter subscribers: your email address and the date you subscribed. We use it only to send you news updates and a welcome message.',
    'Writers and admins: name, email address, password (kept only as a one-way scrambled code, so nobody can read it), an optional profile picture, the date joined and the account status. The stories, photos and videos they submit are published under their name.',
    'Advertisers: we count how many times an advertisement is shown and clicked. We do not follow visitors across other sites.']],
  ['h' => 'Cookies and similar tools', 'p' => ['Cookies are small files saved in your browser. We use two kinds.'], 'ul' => [
    'Essential (always on): a session cookie (PHPSESSID) that keeps staff logged in and protects forms, a language cookie (lang) that remembers EN, RW or FR, and a cookie (urumuri_consent) that remembers your cookie choice for 6 months.',
    'Analytics (only if you accept): Google Analytics cookies (_ga and _ga_*) that tell us which pages are read. If you reject, Google Analytics is not loaded and its cookies are removed.'],
   'z' => 'You can change your choice at any time with “Cookie settings” at the bottom of every page.'],
  ['h' => 'Other companies’ services', 'p' => ['Some features load content from other companies, which can receive your IP address when your browser contacts them: Google Fonts and a style library (page fonts and design); Open-Meteo (the Kigali temperature at the top); YouTube (videos from YouTube load only after you press play, and the Live page loads the YouTube player); Google Analytics (only if you accept); Facebook, X, WhatsApp and Instagram (only when you press a share button). Please read their own privacy policies.']],
  ['h' => 'How we use your information', 'ul' => ['to run the website and publish stories', 'to approve and manage writer accounts', 'to send news updates to people who subscribed', 'to keep the site secure and fix problems', 'to understand our audience (only if you accept analytics cookies)'], 'z' => 'We do not sell personal information.'],
  ['h' => 'Sharing, keeping and protecting data', 'p' => ['We share data only with service providers we need (website hosting, email delivery through Gmail, and analytics if you accept) or when the law requires it.', 'Subscriber emails are kept until you ask us to remove them. Writer accounts are kept while they are in use and deleted when removed or on request. Passwords are stored in scrambled form and forms are protected with security tokens. No system is 100% secure.']],
  ['h' => 'Your rights', 'p' => ['You have the right to see the information we hold about you, correct it, have it deleted, object to its use and withdraw your consent. Write to us at {email}. Rwanda’s law on the protection of personal data and privacy (Law No 058/2021) applies, and you may also contact Rwanda’s data protection authority.']],
  ['h' => 'Children', 'p' => ['This website is not meant for children under 16 and we do not knowingly collect their information.']],
  ['h' => 'Changes to this policy', 'p' => ['If we change this policy we will post the new version on this page with a new date.']]]],
'rw' => ['intro' => 'URUMURI NEWS (“twe”) dutangaza amakuru mu Cyongereza, Ikinyarwanda n’Igifaransa. Iyi politiki isobanura amakuru bwite uru rubuga rukusanya, impamvu tuyakusanya, n’amahitamo ufite. Iyo ukoresheje uru rubuga uba wemeye iyi politiki. Ushobora kwemera cyangwa kwanga cookies zitari ngombwa igihe cyose.',
 'upd' => 'Yavuguruwe ku wa',
 's' => [
  ['h' => 'Ushinzwe uru rubuga', 'p' => ['URUMURI NEWS ni yo ishinzwe uru rubuga. Mushobora kutugeraho kuri {email}.']],
  ['h' => 'Amakuru dukusanya', 'ul' => [
    'Abasura urubuga: ushobora gusoma amakuru yose nta konti. Nk’izindi mbuga zose, seriveri yacu yandika amakuru ya tekiniki nka aderesi ya IP, ubwoko bwa porogaramu usomeramo n’impapuro ufunguye. Buri nkuru ifite umubare rusange w’abayisomye utagaragaza uwo uri we.',
    'Abiyandikishije kwakira amakuru: imeri yawe n’itariki wiyandikishijeho. Tuyikoresha gusa kugira ngo tukwoherereze amakuru mashya n’ubutumwa bukwakira.',
    'Abanditsi n’abayobozi: amazina, imeri, ijambobanga (ribikwa mu buryo busobanye ku buryo ntawe ushobora kurisoma), ifoto ya profil itari ngombwa, itariki binjiriyeho n’imiterere ya konti. Inkuru, amafoto na video batanga bitangazwa bifite amazina yabo.',
    'Abamamaza: tubara incuro itangazo ryerekanwe n’incuro ryakanzweho. Ntidukurikirana abasura izindi mbuga.']],
  ['h' => 'Cookies n’ibindi bisa na zo', 'p' => ['Cookies ni dosiye nto zibikwa muri porogaramu usomeramo. Dukoresha ubwoko bubiri.'], 'ul' => [
    'Ingenzi (zihora zikora): cookie y’iseshoni (PHPSESSID) ituma abakozi bakomeza kwinjira kandi irinda impapuro; cookie y’ururimi (lang) yibuka EN, RW cyangwa FR; na cookie (urumuri_consent) yibuka icyemezo cyawe mu mezi 6.',
    'Isesengura (niba wemeye gusa): cookies za Google Analytics (_ga na _ga_*) zitubwira impapuro zisomwa. Nuzanga, Google Analytics ntifungurwa kandi cookies zayo zirasibwa.'],
   'z' => 'Ushobora guhindura icyemezo cyawe igihe cyose ukoresheje “Igenamiterere rya cookies” munsi ya buri rupapuro.'],
  ['h' => 'Serivisi z’andi masosiyete', 'p' => ['Zimwe mu mikorere zikura ibikubiyemo ku yandi masosiyete, ashobora kubona aderesi yawe ya IP igihe porogaramu yawe iyabajije: Google Fonts n’isomero ry’imitako (inyuguti n’imiterere y’impapuro); Open-Meteo (ubushyuhe bwa Kigali bugaragara hejuru); YouTube (video ziri kuri YouTube zifungurwa ari uko ukanze kuzireba, naho urupapuro rwa Live rufungura umukinisho wa YouTube); Google Analytics (niba wemeye gusa); Facebook, X, WhatsApp na Instagram (iyo ukanze buto yo gusangiza gusa). Turagusaba gusoma politiki zabo z’ibanga.']],
  ['h' => 'Uko dukoresha amakuru yawe', 'ul' => ['gukoresha urubuga no gutangaza inkuru', 'kwemeza no gucunga konti z’abanditsi', 'kohereza amakuru mashya ku biyandikishije', 'kurinda urubuga no gukosora ibibazo', 'gusobanukirwa abasomyi (niba wemeye cookies z’isesengura gusa)'], 'z' => 'Ntitugurisha amakuru bwite.'],
  ['h' => 'Gusangira, kubika no kurinda amakuru', 'p' => ['Dusangiza amakuru gusa n’abatanga serivisi dukeneye (aho urubuga rubitse, kohereza imeri hakoreshejwe Gmail, n’isesengura niba wemeye), cyangwa igihe itegeko ribitegetse.', 'Imeri z’abiyandikishije zibikwa kugeza usabye ko zivanwaho. Konti z’abanditsi zibikwa igihe zikoreshwa, zigasibwa iyo zivanyweho cyangwa zisabwe gusibwa. Amagambobanga abikwa mu buryo busobanye, kandi impapuro zirinzwe hakoreshejwe ibimenyetso by’umutekano. Nta sisitemu irinzwe ijana ku ijana.']],
  ['h' => 'Uburenganzira bwawe', 'p' => ['Ufite uburenganzira bwo kumenya amakuru dufite kuri wowe, kuyakosoza, kuyasibisha, kuyaburanya no kuvanaho icyemezo wari watanze. Twandikire kuri {email}. Itegeko ry’u Rwanda rirengera amakuru bwite n’ubuzima bwite (Itegeko n° 058/2021) rirakurikizwa, kandi ushobora no kugana urwego rw’u Rwanda rushinzwe kurengera amakuru bwite.']],
  ['h' => 'Abana', 'p' => ['Uru rubuga ntirugenewe abana bari munsi y’imyaka 16, kandi ntidukusanya ku bushake amakuru yabo.']],
  ['h' => 'Impinduka kuri iyi politiki', 'p' => ['Nituramuka duhinduye iyi politiki, tuzashyira verisiyo nshya kuri uru rupapuro hamwe n’itariki nshya.']]]],
'fr' => ['intro' => 'URUMURI NEWS (« nous ») publie l’actualité en anglais, en kinyarwanda et en français. Cette politique explique quelles informations personnelles ce site collecte, pourquoi nous les collectons et quels choix vous avez. En utilisant le site, vous acceptez cette politique. Vous pouvez accepter ou refuser les cookies facultatifs à tout moment.',
 'upd' => 'Dernière mise à jour',
 's' => [
  ['h' => 'Responsable du site', 'p' => ['URUMURI NEWS est responsable de ce site. Vous pouvez nous écrire à {email}.']],
  ['h' => 'Ce que nous collectons', 'ul' => [
    'Visiteurs : vous pouvez lire toute l’actualité sans compte. Comme tout site, notre serveur enregistre des données techniques telles que votre adresse IP, votre navigateur et les pages ouvertes. Chaque article a un compteur total de vues qui ne vous identifie pas.',
    'Abonnés à la lettre d’information : votre adresse e-mail et la date d’abonnement. Nous l’utilisons uniquement pour vous envoyer des nouvelles et un message de bienvenue.',
    'Rédacteurs et administrateurs : nom, adresse e-mail, mot de passe (conservé uniquement sous forme de code irréversible, personne ne peut le lire), photo de profil facultative, date d’inscription et statut du compte. Les articles, photos et vidéos soumis sont publiés sous leur nom.',
    'Annonceurs : nous comptons combien de fois une publicité est affichée et cliquée. Nous ne suivons pas les visiteurs sur d’autres sites.']],
  ['h' => 'Cookies et outils similaires', 'p' => ['Les cookies sont de petits fichiers enregistrés dans votre navigateur. Nous en utilisons deux types.'], 'ul' => [
    'Essentiels (toujours actifs) : un cookie de session (PHPSESSID) qui garde le personnel connecté et protège les formulaires, un cookie de langue (lang) qui mémorise EN, RW ou FR, et un cookie (urumuri_consent) qui mémorise votre choix pendant 6 mois.',
    'Mesure d’audience (seulement si vous acceptez) : cookies Google Analytics (_ga et _ga_*) qui nous indiquent quelles pages sont lues. Si vous refusez, Google Analytics n’est pas chargé et ses cookies sont supprimés.'],
   'z' => 'Vous pouvez changer d’avis à tout moment avec « Paramètres des cookies » en bas de chaque page.'],
  ['h' => 'Services d’autres sociétés', 'p' => ['Certaines fonctions chargent du contenu provenant d’autres sociétés, qui peuvent recevoir votre adresse IP lorsque votre navigateur les contacte : Google Fonts et une bibliothèque de style (polices et design des pages) ; Open-Meteo (la température de Kigali en haut de page) ; YouTube (les vidéos YouTube ne se chargent qu’après avoir appuyé sur lecture, et la page Live charge le lecteur YouTube) ; Google Analytics (seulement si vous acceptez) ; Facebook, X, WhatsApp et Instagram (seulement quand vous appuyez sur un bouton de partage). Veuillez lire leurs propres politiques de confidentialité.']],
  ['h' => 'Comment nous utilisons vos informations', 'ul' => ['faire fonctionner le site et publier des articles', 'valider et gérer les comptes des rédacteurs', 'envoyer des nouvelles aux personnes abonnées', 'garder le site sécurisé et corriger les problèmes', 'comprendre notre audience (seulement si vous acceptez les cookies de mesure)'], 'z' => 'Nous ne vendons pas les informations personnelles.'],
  ['h' => 'Partage, conservation et protection des données', 'p' => ['Nous partageons les données uniquement avec les prestataires dont nous avons besoin (hébergement du site, envoi d’e-mails via Gmail, et mesure d’audience si vous acceptez) ou lorsque la loi l’exige.', 'Les e-mails des abonnés sont conservés jusqu’à ce que vous demandiez leur suppression. Les comptes de rédacteurs sont conservés tant qu’ils sont utilisés et supprimés lorsqu’ils sont retirés ou sur demande. Les mots de passe sont stockés sous forme chiffrée et les formulaires sont protégés par des jetons de sécurité. Aucun système n’est sûr à 100 %.']],
  ['h' => 'Vos droits', 'p' => ['Vous avez le droit de consulter les informations que nous détenons sur vous, de les corriger, de les faire supprimer, de vous opposer à leur utilisation et de retirer votre consentement. Écrivez-nous à {email}. La loi rwandaise sur la protection des données personnelles et de la vie privée (loi n° 058/2021) s’applique, et vous pouvez aussi contacter l’autorité rwandaise de protection des données.']],
  ['h' => 'Enfants', 'p' => ['Ce site n’est pas destiné aux enfants de moins de 16 ans et nous ne collectons pas sciemment leurs informations.']],
  ['h' => 'Modifications de cette politique', 'p' => ['Si nous modifions cette politique, nous publierons la nouvelle version sur cette page avec une nouvelle date.']]]]];
$L = $P[LANG]; ?>
<main class="max-w-3xl mx-auto px-4 mt-8">
  <h1 class="font-display font-extrabold text-3xl md:text-4xl border-l-8 border-sun pl-3"><?= t('Privacy policy') ?></h1>
  <p class="text-sm text-stone-600 mt-2"><?= e($L['upd']) ?>: <?= e(ldate(strtotime('2026-10-16'))) ?></p>
  <p class="mt-5 text-lg leading-8"><?= $r($L['intro']) ?></p>
  <?php foreach ($L['s'] as $i => $s): ?>
  <section class="mt-8">
    <h2 class="font-display font-bold text-2xl"><?= ($i + 1) . '. ' . e($s['h']) ?></h2>
    <?php foreach ($s['p'] ?? [] as $p): ?><p class="mt-2 leading-7"><?= $r($p) ?></p><?php endforeach; ?>
    <?php if (!empty($s['ul'])): ?><ul class="mt-2 list-disc pl-6 space-y-2 leading-7"><?php foreach ($s['ul'] as $li): ?><li><?= $r($li) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if (!empty($s['z'])): ?><p class="mt-2 leading-7"><?= $r($s['z']) ?></p><?php endif; ?>
  </section>
  <?php endforeach; ?>
  <p class="mt-8"><button type="button" onclick="document.getElementById('cookie-settings').click()" class="bg-brand text-white font-bold px-5 py-2 rounded-lg hover:bg-ink active:scale-95 transition"><?= t('Cookie settings') ?></button></p>
</main>
<?php require 'includes/footer.php'; ?>
