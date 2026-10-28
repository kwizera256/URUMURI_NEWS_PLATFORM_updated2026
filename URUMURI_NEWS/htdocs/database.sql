-- =====================================================================
-- URUMURI NEWS - MAIN DATABASE (single file)
-- Replaces: database.sql, upgrade.sql, upgrade_video.sql, upgrade_ads.sql,
--           upgrade_mail.sql, update_email.sql
-- Safe to import on an empty database AND to re-run on an existing one
-- (CREATE TABLE IF NOT EXISTS / INSERT IGNORE: nothing is overwritten).
-- Older installs are upgraded automatically by includes/schema.php on first page load.
-- =====================================================================
SET NAMES utf8mb4;

-- ---------- Staff ----------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(100) NULL,
  role ENUM('admin','writer') NOT NULL DEFAULT 'writer',
  status ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
  can_publish TINYINT(1) NOT NULL DEFAULT 0,
  can_edit_all TINYINT(1) NOT NULL DEFAULT 0,
  can_grant TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Content ----------
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  slug VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(280) NOT NULL UNIQUE,
  summary VARCHAR(300),
  body MEDIUMTEXT NOT NULL,
  lang ENUM('en','rw','fr') NOT NULL DEFAULT 'en',
  image VARCHAR(100),
  video_type ENUM('none','upload','youtube') NOT NULL DEFAULT 'none',
  video VARCHAR(255) NULL,
  category_id INT NOT NULL,
  author_id INT NOT NULL,
  status ENUM('pending','published') NOT NULL DEFAULT 'pending',
  views INT NOT NULL DEFAULT 0,
  is_hot TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  published_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  updated_by INT NULL,
  notified_at TIMESTAMP NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Site settings ----------
CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(50) PRIMARY KEY,
  sval VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Subscribers + email queue ----------
CREATE TABLE IF NOT EXISTS subscribers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mail_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  article_id INT NOT NULL,
  subscriber_id INT NOT NULL,
  status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  tries TINYINT NOT NULL DEFAULT 0,
  sent_at TIMESTAMP NULL,
  error VARCHAR(255) NULL,
  UNIQUE KEY u (article_id, subscriber_id),
  KEY st (status),
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  FOREIGN KEY (subscriber_id) REFERENCES subscribers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Advertising ----------
CREATE TABLE IF NOT EXISTS ads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  body VARCHAR(200) NULL,
  url VARCHAR(500) NOT NULL,
  image VARCHAR(100) NULL,
  placement ENUM('top','inline','bottom') NOT NULL DEFAULT 'inline',
  status ENUM('active','paused') NOT NULL DEFAULT 'active',
  starts_at DATE NULL,
  ends_at DATE NULL,
  views INT NOT NULL DEFAULT 0,
  clicks INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Hot news: reader accounts, plans, payments ----------
CREATE TABLE IF NOT EXISTS members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20) NULL,
  password VARCHAR(255) NOT NULL,
  sub_until DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  days INT NOT NULL,
  price INT NOT NULL,
  status ENUM('active','paused') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  plan_id INT NULL,
  plan_name VARCHAR(80) NOT NULL,
  days INT NOT NULL,
  amount INT NOT NULL,
  method ENUM('mtn','airtel') NOT NULL,
  payer_phone VARCHAR(20) NOT NULL,
  txn_ref VARCHAR(40) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  decided_at TIMESTAMP NULL,
  decided_by INT NULL,
  UNIQUE KEY txn (method, txn_ref),
  KEY st (status),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Default data (never overwrites existing values) ----------
INSERT INTO categories (name, slug)
SELECT * FROM (
  SELECT 'Rwanda' n,'rwanda' s UNION ALL SELECT 'Africa','africa' UNION ALL SELECT 'World','world'
  UNION ALL SELECT 'Politics','politics' UNION ALL SELECT 'Business','business' UNION ALL SELECT 'Sports','sports'
  UNION ALL SELECT 'Entertainment','entertainment' UNION ALL SELECT 'Technology','technology'
  UNION ALL SELECT 'Health','health' UNION ALL SELECT 'Education','education'
) d WHERE NOT EXISTS (SELECT 1 FROM categories);

INSERT IGNORE INTO settings (skey, sval) VALUES
('youtube_channel_id',''),
('contact_email','kwizerasamuel6644@gmail.com'),
('contact_phone','+250 000 000 000'),
('whatsapp','+250 000 000 000'),
('contact_address','Kigali, Rwanda'),
('x_url','https://x.com/'),
('instagram_url','https://instagram.com/'),
('facebook_url','https://facebook.com/'),
('pay_mtn_on','1'),('pay_mtn_number',''),('pay_mtn_name',''),
('pay_airtel_on','1'),('pay_airtel_number',''),('pay_airtel_name',''),
('pay_note','');

-- Admin accounts always keep every right
UPDATE users SET can_publish=1, can_edit_all=1, can_grant=1 WHERE role='admin';
