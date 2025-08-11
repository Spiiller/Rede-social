<?php
session_start();


// Configurações do banco de dados
$db_host = 'localhost';
$db_name = 'u933302538_jd_alegria';
$db_user = 'u933302538_jd_alegria';
$db_pass = 'TesteJardim25';

// Criar conexão PDO
try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Função para verificar sessão ativa
function check_session() {
    // Só verifica a sessão se ela já foi iniciada
    return (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id']));
}
?>