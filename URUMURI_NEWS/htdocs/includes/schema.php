<?php
// Creates any missing tables/columns the first time the site runs (and after each upgrade),
// so you do not have to import database.sql by hand. Safe to run many times.
const SCHEMA_VERSION = '6';

function ensure_schema() {
  global $pdo;
  try {
    if (setting('schema_v') === SCHEMA_VERSION) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL,
      avatar VARCHAR(100) NULL, role ENUM('admin','writer') NOT NULL DEFAULT 'writer', status ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
      can_publish TINYINT(1) NOT NULL DEFAULT 0, can_edit_all TINYINT(1) NOT NULL DEFAULT 0, can_grant TINYINT(1) NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
      id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(60) NOT NULL, slug VARCHAR(60) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS articles (
      id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(280) NOT NULL UNIQUE, summary VARCHAR(300), body MEDIUMTEXT NOT NULL,
      lang ENUM('en','rw','fr') NOT NULL DEFAULT 'en', image VARCHAR(100), video_type ENUM('none','upload','youtube') NOT NULL DEFAULT 'none', video VARCHAR(255) NULL,
      category_id INT NOT NULL, author_id INT NOT NULL, status ENUM('pending','published') NOT NULL DEFAULT 'pending', views INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, published_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, updated_by INT NULL, notified_at TIMESTAMP NULL,
      FOREIGN KEY (category_id) REFERENCES categories(id), FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(50) PRIMARY KEY, sval VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscribers (
      id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(150) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS ads (
      id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(120) NOT NULL, body VARCHAR(200) NULL, url VARCHAR(500) NOT NULL, image VARCHAR(100) NULL,
      placement ENUM('top','inline','bottom') NOT NULL DEFAULT 'inline', status ENUM('active','paused') NOT NULL DEFAULT 'active',
      starts_at DATE NULL, ends_at DATE NULL, views INT NOT NULL DEFAULT 0, clicks INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS mail_queue (
      id INT AUTO_INCREMENT PRIMARY KEY, article_id INT NOT NULL, subscriber_id INT NOT NULL,
      status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued', tries TINYINT NOT NULL DEFAULT 0, sent_at TIMESTAMP NULL, error VARCHAR(255) NULL,
      UNIQUE KEY u (article_id, subscriber_id), KEY st (status),
      FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE, FOREIGN KEY (subscriber_id) REFERENCES subscribers(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Hot news: reader accounts, subscription plans, payments (confirmed by the admin)
    $pdo->exec("CREATE TABLE IF NOT EXISTS members (
      id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL UNIQUE, phone VARCHAR(20) NULL, password VARCHAR(255) NOT NULL,
      sub_until DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS plans (
      id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL, days INT NOT NULL, price INT NOT NULL, status ENUM('active','paused') NOT NULL DEFAULT 'active',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
      id INT AUTO_INCREMENT PRIMARY KEY, member_id INT NOT NULL, plan_id INT NULL, plan_name VARCHAR(80) NOT NULL, days INT NOT NULL, amount INT NOT NULL,
      method ENUM('mtn','airtel') NOT NULL, payer_phone VARCHAR(20) NOT NULL, txn_ref VARCHAR(40) NOT NULL,
      status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', note VARCHAR(255) NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, decided_at TIMESTAMP NULL, decided_by INT NULL,
      UNIQUE KEY txn (method, txn_ref), KEY st (status), FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Columns that older versions of the site did not have (adds only what is missing)
    $add = function ($table, $col, $def) use ($pdo) {
      $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
      $q->execute([$table, $col]);
      if (!$q->fetchColumn()) $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
    };
    $pdo->exec("ALTER TABLE users MODIFY status ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending'"); // very old installs lack 'suspended'
    $add('users', 'avatar', 'VARCHAR(100) NULL');
    $add('users', 'can_publish', 'TINYINT(1) NOT NULL DEFAULT 0');
    $add('users', 'can_edit_all', 'TINYINT(1) NOT NULL DEFAULT 0');
    $add('users', 'can_grant', 'TINYINT(1) NOT NULL DEFAULT 0');
    $add('articles', 'lang', "ENUM('en','rw','fr') NOT NULL DEFAULT 'en'");
    $add('articles', 'video_type', "ENUM('none','upload','youtube') NOT NULL DEFAULT 'none'");
    $add('articles', 'video', 'VARCHAR(255) NULL');
    $add('articles', 'published_at', 'TIMESTAMP NULL');
    $add('articles', 'updated_at', 'TIMESTAMP NULL');
    $add('articles', 'updated_by', 'INT NULL');
    $add('articles', 'notified_at', 'TIMESTAMP NULL');
    $add('articles', 'is_hot', 'TINYINT(1) NOT NULL DEFAULT 0');

    // Default categories (only on an empty site) and default settings
    if (!(int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn())
      $pdo->exec("INSERT INTO categories (name,slug) VALUES ('Rwanda','rwanda'),('Africa','africa'),('World','world'),('Politics','politics'),('Business','business'),
        ('Sports','sports'),('Entertainment','entertainment'),('Technology','technology'),('Health','health'),('Education','education')");
    $pdo->exec("INSERT IGNORE INTO settings VALUES ('youtube_channel_id',''),('contact_email','" . SITE_EMAIL . "'),('contact_phone','+250 000 000 000'),
      ('whatsapp','+250 000 000 000'),('contact_address','Kigali, Rwanda'),('x_url','https://x.com/'),('instagram_url','https://instagram.com/'),('facebook_url','https://facebook.com/'),
      ('pay_mtn_on','1'),('pay_mtn_number',''),('pay_mtn_name',''),('pay_airtel_on','1'),('pay_airtel_number',''),('pay_airtel_name',''),('pay_note','')");
    $pdo->exec("UPDATE users SET can_publish=1, can_edit_all=1, can_grant=1 WHERE role='admin'");

    set_setting('schema_v', SCHEMA_VERSION);
  } catch (Throwable $x) {
    error_log('URUMURI schema: ' . $x->getMessage());
    if (APP_DEBUG) echo '<p style="background:#fff4f4;border:1px solid #f1b4b4;padding:10px;margin:0;font:14px Arial">Database setup notice: ' . htmlspecialchars($x->getMessage()) . '</p>';
  }
}
