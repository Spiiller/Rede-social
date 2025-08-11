<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['user'])) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user']['id'],
                'name' => $_SESSION['user']['name'],
                'email' => $_SESSION['user']['email'],
                // Sempre envie is_admin como inteiro (0 ou 1)
                'is_admin' => (int)$_SESSION['user']['is_admin']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Não logado']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Não logado']);
        exit;
    }
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';

    $stmt = $db->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
    $success = $stmt->execute([$name, $email, $_SESSION['user']['id']]);
    if ($success) {
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
?>
