DATABASE (UPDATED): everything is now in ONE file, database.sql (all tables + default data, safe to re-run).
The old upgrade.sql, upgrade_video.sql, upgrade_ads.sql, upgrade_mail.sql and update_email.sql were merged into it and removed.
Import only database.sql in phpMyAdmin (or skip it: includes/schema.php creates/upgrades the same tables automatically).

URUMURI NEWS - INFINITYFREE SETUP (read this first)
=====================================================
1. In InfinityFree > File Manager, open the "htdocs" folder of your domain and upload EVERYTHING from this folder into it
   (config.php, db.php, index.php, includes/, lib/, uploads/, assets/ ... must be directly inside htdocs).
2. DATABASE SETTINGS live in ONE place only: config.php (top of the file). They are already filled in:
      DB_HOST = sql313.infinityfree.com   <- the "MySQL Hostname" in your panel (never https://..., never localhost)
      DB_NAME = if0_43264006_urumuri_news <- the full name under "List of MySQL Databases"
      DB_USER = if0_43264006
      DB_PASS = your MySQL password
   If you ever change the password in the panel, change DB_PASS in config.php too.
3. Open yoursite/db_test.php. It must say the database is connected. Tables are created automatically on first load,
   so you do NOT need to import database.sql (it is only kept as a backup copy of the structure).
4. Open yoursite/install.php ONCE to create the admin (admin@urumuri.news / Admin@123), log in, change the password in Profile,
   then delete install.php and db_test.php from File Manager.
5. When everything works, set APP_DEBUG to false in config.php.
6. Free hosting limits: uploads up to 10 MB; a brand-new domain can take a while to resolve (DNS caching).

HOT NEWS (paid stories) - HOW IT WORKS
======================================
1. Admin panel > "Hot news plans": add your subscription plans (name, days of access, price in RWF) and type your MTN Mobile Money
   and Airtel Money numbers / account names. A payment method only shows to readers when it is ticked and has a number.
2. Writing or editing a story: publishers (and the admin) see a "Hot news" tick box. Hot stories are NOT on the home page or in categories;
   they appear on the Hot news page. Everyone sees the title, photo and summary; the full text needs a paid subscription.
3. Readers: Hot news page > Create an account > My account > choose a plan > pay with MTN or Airtel > enter the phone number they paid
   from and the transaction ID from the confirmation SMS.
4. Admin panel > "Payments": check your MTN / Airtel messages, then press Approve. Access starts at once (a new payment adds days on top
   of the remaining time). Reject with a reason if the money did not arrive. The "Members" tab lets you add or remove days by hand.
   NOTE: payments are confirmed by you, not automatically. Automatic confirmation needs MTN MoMo / Airtel Money merchant API access.
5. Top-of-page ads: Admin panel > Ads > placement "Top banner". Up to 4 top ads show side by side; with more than 4 they take turns.

----- original notes below -----

URUMURI NEWS setup (XAMPP / any PHP 8+ and MySQL host)
NEW INSTALL
1. Copy this folder to htdocs (or your host's public_html). Make uploads/ writable.
2. Import database.sql in phpMyAdmin.
3. Edit config.php: DB credentials and GA_ID (your GA4 ID, G-XXXXXXXXXX).
4. Open /install.php once to create the admin (admin@urumuri.news / Admin@123), then delete install.php.
5. Log in, change the password under Profile, add your YouTube Channel ID and your contacts in the Admin panel.
6. EMAILS (subscribe alerts + welcome email): open config.php and paste a Gmail App Password into SMTP_PASS
   (Google Account > Security > 2-Step Verification > App passwords). Then Admin panel > "Send test email".
   Without SMTP_PASS the site tries PHP mail(), which does not work on a local XAMPP. Subscribers are always saved either way.

ADS: Admin panel > Ads. Add a banner picture (best 970x250) or a text ad with a https:// link, choose where it shows
(top banner, inside content, above footer), optional start/end dates. Views and clicks are counted. Ads show only on public pages.

UPGRADING an earlier version (keeps stories and accounts)
1. Import database.sql once (it contains every old upgrade and is safe to re-run; nothing is overwritten).
2. (The Ads table is included in database.sql.)
3. Replace all files with this version (keep your own DB settings and GA_ID in config.php).

STAFF LOGIN: there is no sign-up link on the public site. Admin and writers log in from the small "Log in" link in the footer (or open yoursite/login.php).
Passwords have a show/hide eye, and every form that sets a new password asks to confirm it.

HOW ACCESS WORKS
- There is no public sign-up. The admin creates writer accounts on the Team page (name, email, temporary password, photo, rights).
- Rights per writer: publish articles, update all articles, grant access to others. A person who may grant can only give rights they hold themselves.
- Only the admin activates/suspends accounts, resets passwords and deletes accounts or stories.

VIDEO NEWS, SHARING, TEAM REPORT, PRIVACY (this version)
- Writing/editing a story: the "Video (optional)" box lets a publisher upload an MP4/WEBM file (max 60 MB) or paste a YouTube video link
  (watch, youtu.be, shorts or live link). The story photo becomes the video's cover. Cards on the home page show a "Video" badge.
  The video columns are added to the database automatically on first load (also included in database.sql).
- Big uploads: .htaccess and .user.ini ask PHP for 64 MB. On XAMPP, if a video is refused as too large, open php.ini and set
  upload_max_filesize=64M and post_max_size=70M, then restart Apache.
- Share buttons (Facebook, X, WhatsApp, Instagram) are on every news card and on each story page.
- Admin panel > Team report: download a CSV, or a PDF (opens a print page: choose Save as PDF) with names, emails, pictures, date joined and status.
- Footer: Privacy policy page (privacy.php, EN/RW/FR) and Cookie settings. The cookie banner has Accept and Reject; Google Analytics loads only after Accept.
  Review the policy text with your own legal adviser before going live and check the contact email in the Admin panel.


NEW IN THIS VERSION
-------------------
1. Manage articles (admin/editors): filter by writer, language, date from/to and status; filters survive publish/unpublish/delete; paging.
2. Home page: the "top stories" slider and the Latest ticker follow the visitor's language (or the ?l= language chip). If a language has no
   stories yet, all languages are shown with a small note.
3. Team report (Admin > Team report): filter by status, role, rights, join dates, search; tick the members you want; download CSV or PDF.
   Tick nobody = export everyone shown.
4. Email (Admin > Email settings): SMTP host/port/security/user/password/sender, test email, automatic email of every newly published story to
   subscribers (with picture or YouTube thumbnail, "Watch the video" link, unsubscribe link). Gmail: use a 16-letter App Password, ~500 emails/day;
   the daily cap queues the rest for tomorrow. Stories already online when you upgrade are NOT emailed. Pictures in emails need a real public domain.
   Optional cron every 5 min: the address shown on the Email settings page. The SMTP password is stored in the database (never shown again).
5. Videos: a story can have an uploaded MP4/WEBM (max 60 MB) or a YouTube link; both play on the story page.
Database changes are created automatically on first visit (also included in database.sql).
