<?php
function debug_log($message, $data = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if (!empty($data)) {
        $logMessage .= ": " . json_encode($data, JSON_PRETTY_PRINT);
    }
    $logMessage .= PHP_EOL;
    file_put_contents(__DIR__ . '/debug.log', $logMessage, FILE_APPEND);
}
?>