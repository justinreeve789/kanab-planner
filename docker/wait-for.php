<?php
/**
 * Wait until host:port accepts connections. Usage: php wait-for.php host port [seconds]
 */
[$script, $host, $port] = $argv + [null, null, null];
$deadline = time() + (int) ($argv[3] ?? 120);
while (time() < $deadline) {
    $socket = @fsockopen($host, (int) $port, $errno, $error, 2);
    if ($socket) {
        fclose($socket);
        exit(0);
    }
    sleep(2);
}
fwrite(STDERR, "Timed out waiting for {$host}:{$port}\n");
exit(1);
