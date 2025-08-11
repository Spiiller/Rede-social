<?php
// Garantir que não haja saída antes do JSON
ob_start();
require_once 'config.php';
require_once 'debug_log.php';

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

debug_log("Upload request received", $_SERVER);
debug_log("Session data", $_SESSION);

// Verificar se o usuário está logado
if (!isset($_SESSION['user'])) {
    debug_log("Authentication failed - no user in session");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debug_log("FILES data", $_FILES);
    
    // Verificar se há arquivos enviados
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        debug_log("No images found in request");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No images uploaded']);
        ob_end_flush();
        exit;
    }
    
    // Criar diretório de upload se não existir
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        debug_log("Creating upload directory: $uploadDir");
        mkdir($uploadDir, 0777, true);
    }
    
    $imageUrls = [];
    $errors = [];
    
    // Processar cada arquivo
    foreach ($_FILES['images']['name'] as $key => $name) {
        debug_log("Processing image: $name");
        debug_log("Detalhes do arquivo", [
            'name' => $name,
            'tmp_name' => $_FILES['images']['tmp_name'][$key],
            'size' => $_FILES['images']['size'][$key],
            'error' => $_FILES['images']['error'][$key]
        ]);
        
        // Verificar se é uma imagem válida
        $check = getimagesize($_FILES['images']['tmp_name'][$key]);
        if ($check === false) {
            $error_msg = "File $name is not a valid image.";
            debug_log($error_msg);
            $errors[] = $error_msg;
            continue;
        }
        
        // Gerar um nome de arquivo único
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $newFilename = uniqid() . '_' . time() . '.' . $extension;
        $targetFile = $uploadDir . $newFilename;
        
        debug_log("Attempting to move uploaded file to: $targetFile");
        
        // Verificar permissões de escrita
        if (!is_writable($uploadDir)) {
            $error_msg = "Upload directory is not writable: $uploadDir";
            debug_log($error_msg);
            $errors[] = $error_msg;
            continue;
        }
        
        // Tentar mover o arquivo enviado
        if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $targetFile)) {
            debug_log("File uploaded successfully: $targetFile");
            $imageUrls[] = $targetFile;
        } else {
            $error_msg = "Failed to upload file $name. PHP Error: " . error_get_last()['message'];
            debug_log($error_msg);
            $errors[] = $error_msg;
        }
    }
    
    // Retornar resposta
    if (empty($imageUrls)) {
        $response = [
            'success' => false, 
            'message' => 'Failed to upload images', 
            'errors' => $errors
        ];
        debug_log("Upload failed", $response);
    } else {
        $response = [
            'success' => true, 
            'message' => 'Images uploaded successfully', 
            'imageUrls' => $imageUrls, 
            'errors' => $errors
        ];
        debug_log("Upload successful", $response);
    }
    
    // Limpar buffer e enviar JSON
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>