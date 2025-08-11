<?php
// check_permissions.php
echo "<h1>PlantGram System Check</h1>";

// Verificar diretório de uploads
$uploadDir = 'uploads/';
echo "<h2>Upload Directory Check</h2>";
echo "Upload directory path: " . realpath($uploadDir) . "<br>";

if (!file_exists($uploadDir)) {
    echo "Status: Directory does not exist. Attempting to create...<br>";
    if (mkdir($uploadDir, 0777, true)) {
        echo "Result: Directory created successfully.<br>";
    } else {
        echo "Result: <strong>FAILED to create directory.</strong><br>";
        echo "Error: " . error_get_last()['message'] . "<br>";
    }
} else {
    echo "Status: Directory exists.<br>";
}

// Verificar permissões
echo "Permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "<br>";
echo "Is writable: " . (is_writable($uploadDir) ? "Yes" : "<strong>NO - FIX THIS</strong>") . "<br>";

// Verificar configuração do PHP
echo "<h2>PHP Configuration Check</h2>";
echo "file_uploads enabled: " . (ini_get('file_uploads') ? "Yes" : "<strong>NO - FIX THIS</strong>") . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";

// Verificar conexão com o banco de dados
echo "<h2>Database Connection Check</h2>";
require_once 'config.php';

echo "Connection: ";
try {
    $db->query("SELECT 1");
    echo "Successful<br>";
    
    // Verificar tabelas
    echo "<h3>Database Tables</h3>";
    $tables = [
        'users',
        'posts',
        'post_images',
        'post_likes',
        'post_saves',
        'comments'
    ];
    
    foreach ($tables as $table) {
        echo "Table '$table': ";
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "Exists ($count records)<br>";
        } catch (PDOException $e) {
            echo "<strong>ERROR: " . $e->getMessage() . "</strong><br>";
        }
    }
    
} catch (PDOException $e) {
    echo "<strong>FAILED: " . $e->getMessage() . "</strong><br>";
}

// Verificar sessão
echo "<h2>Session Check</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "User logged in: " . (isset($_SESSION['user']) ? "Yes" : "No") . "<br>";
if (isset($_SESSION['user'])) {
    echo "User details: <pre>" . print_r($_SESSION['user'], true) . "</pre>";
}

// Verificar caminhos do servidor
echo "<h2>Server Paths</h2>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Current script path: " . __FILE__ . "<br>";
?>