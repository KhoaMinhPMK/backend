<?php
header('Content-Type: text/plain');

$logFile = __DIR__ . '/debug_log.txt';

if (file_exists($logFile)) {
    echo "Log file found at: " . $logFile . "\n";
    echo "Last modified: " . date("F d Y H:i:s.", filemtime($logFile)) . "\n";
    echo "--------------------------------------------------\n";
    echo file_get_contents($logFile);
} else {
    echo "Log file not found at: " . $logFile . "\n";
    echo "Please try registering a user first to generate logs.";
}
?>
