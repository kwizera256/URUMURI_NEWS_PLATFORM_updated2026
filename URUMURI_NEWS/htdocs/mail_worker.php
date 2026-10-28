<?php require 'config.php'; // Sends waiting news emails in small batches. Three ways to run it:
// 1) visitors' pages poke it quietly (POST, at most every 30 s)   2) admin "Send now" button   3) cron: mail_worker.php?key=<cron key shown in the admin panel>
header('Content-Type: application/json');
$admin = (me()['role'] ?? '') === 'admin'; $key = $_GET['key'] ?? '';
if ($key !== '') { if (!hash_equals(mail_secret(), $key)) { http_response_code(403); exit('{"error":"key"}'); } $mode = 'cron'; }
elseif ($admin && $_SERVER['REQUEST_METHOD'] === 'POST') { check(); $mode = 'admin'; }
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') { $mode = 'poke'; if (!mail_cfg()['auto'] || time() - (int)setting('mail_last_run') <= 30) exit('{"skipped":true}'); }
else { http_response_code(405); exit('{"error":"method"}'); }
ignore_user_abort(true); @set_time_limit(60); remember_base_url();
echo json_encode(mail_run($mode === 'poke' ? 10 : 25, $mode === 'poke' ? 25 : 60) + ['stats' => mail_stats()]);
