<?php
$lines = file('storage/logs/laravel.log');
for ($i = count($lines) - 1; $i >= 0; $i--) {
    if (strpos($lines[$i], 'Class name must be a valid object') !== false) {
        $line = $lines[$i];
        $pos = strpos($line, '{');
        $payload = substr($line, $pos);
        $payload = stripcslashes($payload);
        preg_match_all('/#[0-9]+ [^\n]*/', $payload, $matches);
        echo implode("\n", array_slice($matches[0], 0, 18)) . "\n";
        break;
    }
}
