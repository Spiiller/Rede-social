<?php
// ajax_test.php
session_start();
require_once 'config.php';
require_once 'debug_log.php';

// Registra log apenas no arquivo
debug_log("AJAX test accessed", $_SERVER);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Conexão AJAX bem-sucedida',
    'timestamp' => date('Y-m-d H:i:s'),
    'session_active' => isset($_SESSION['user']),
    'user_id' => isset($_SESSION['user']) ? $_SESSION['user']['id'] : null
]);
