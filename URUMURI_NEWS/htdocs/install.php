<?php // Run once after importing database.sql, then DELETE this file.
require 'config.php';
if ($pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()) die('Admin already exists. Delete install.php.');
$pdo->prepare("INSERT INTO users(name,email,password,role,status,can_publish,can_edit_all,can_grant) VALUES('Admin','admin@urumuri.news',?,'admin','active',1,1,1)")->execute([password_hash('Admin@123', PASSWORD_DEFAULT)]);
echo 'Admin created: admin@urumuri.news / Admin@123. Log in, change the password in Profile, and delete install.php.';
