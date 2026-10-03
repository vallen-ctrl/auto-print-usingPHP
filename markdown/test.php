<?php
require 'db.php';
require 'auth.php';

$device = authDevice($pdo);
$jobId  = (int)($_GET['job'] ?? 0);

// job harus milik device ini + statusnya queued + belum expired
$stmt = $pdo->prepare(
  "SELECT * FROM jobs
   WHERE id = ? AND device_id = ? AND status = 'queued' AND expires_at > NOW()"
);
$stmt->execute([$jobId, $device['id']]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) { http_response_code(404); exit('Not found'); }

$base = '/var/app-storage/';                       // DI LUAR public_html
$path = realpath($base . $job['file_path']);
if (!$path || strpos($path, $base) !== 0 || !is_file($path)) {
    http_response_code(404); exit('Not found');
}

$pdo->prepare("UPDATE jobs SET status='downloaded' WHERE id=?")->execute([$jobId]);

header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . basename($job['original_name']) . '"');
readfile($path);