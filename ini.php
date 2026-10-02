<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // matiin buffering di Nginx

set_time_limit(0);
ignore_user_abort(false);

// matiin output buffering PHP
while (ob_get_level() > 0) ob_end_flush();
ob_implicit_flush(true);

$id = 0;

while (true) {
    if (connection_aborted()) break;

    $data = json_encode([
        'waktu' => date('H:i:s'),
        'pesan' => 'Halo dari server',
    ]);

    echo "id: " . (++$id) . "\n";
    echo "event: update\n";          // opsional, nama event custom
    echo "data: {$data}\n\n";        // wajib diakhiri 2x newline

    flush();
    sleep(2);
}