<?php
require_once 'config.php';

// Definir header JSON
header('Content-Type: application/json');

// Iniciar sessão, se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar método POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    file_put_contents('debug.log', "Método não permitido: {$_SERVER['REQUEST_METHOD']}\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "Método não permitido!"]);
    exit;
}

// Obter ação
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
file_put_contents('debug.log', "Ação recebida: '$action'\n", FILE_APPEND);

// Processar ações
if ($action === 'logout') {
    session_unset();
    session_destroy();
    file_put_contents('debug.log', "Logout executado\n", FILE_APPEND);
    echo json_encode(["success" => true, "message" => "Logout bem-sucedido!", "redirect" => "login.html"]);
    exit;
} elseif ($action === 'login') {
    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');
    file_put_contents('debug.log', "Login - Email: '$email'\n", FILE_APPEND);

    if (empty($email) || empty($password)) {
        file_put_contents('debug.log', "Campos de login vazios\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Preencha todos os campos!"]);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            file_put_contents('debug.log', "Usuário não encontrado: '$email'\n", FILE_APPEND);
            echo json_encode(["success" => false, "message" => "Usuário não encontrado!"]);
            exit;
        }

        if (!password_verify($password, $user["password"])) {
            file_put_contents('debug.log', "Senha incorreta para: '$email'\n", FILE_APPEND);
            echo json_encode(["success" => false, "message" => "Senha incorreta!"]);
            exit;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'is_admin' => $user['is_admin']
        ];
        file_put_contents('debug.log', "Login bem-sucedido para: '$email'\n", FILE_APPEND);

        echo json_encode([
            "success" => true,
            "message" => "Login bem-sucedido!",
            "redirect" => "index.html",
            "user" => [
                "id" => $user["id"],
                "name" => $user["name"],
                "email" => $user["email"],
                "is_admin" => $user["is_admin"]
            ]
        ]);
    } catch (PDOException $e) {
        file_put_contents('debug.log', "Erro no banco (login): {$e->getMessage()}\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Erro no banco: " . $e->getMessage()]);
    }
} elseif ($action === 'register') {
    $name = trim($_POST["name"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');
    $confirm_password = trim($_POST["confirm_password"] ?? '');
    file_put_contents('debug.log', "Registro - Email: '$email'\n", FILE_APPEND);

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        file_put_contents('debug.log', "Campos de registro vazios\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Todos os campos são obrigatórios!"]);
        exit;
    }

    if ($password !== $confirm_password) {
        file_put_contents('debug.log', "Senhas não coincidem para: '$email'\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "As senhas não coincidem!"]);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->rowCount() > 0) {
            file_put_contents('debug.log', "Email já cadastrado: '$email'\n", FILE_APPEND);
            echo json_encode(["success" => false, "message" => "Email já cadastrado!"]);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashed_password
        ]);
        file_put_contents('debug.log', "Registro bem-sucedido para: '$email'\n", FILE_APPEND);

        echo json_encode(["success" => true, "message" => "Registro bem-sucedido! Faça login.", "redirect" => "login.html"]);
    } catch (PDOException $e) {
        file_put_contents('debug.log', "Erro no banco (registro): {$e->getMessage()}\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Erro no banco: " . $e->getMessage()]);
    }
} else {
    file_put_contents('debug.log', "Ação inválida recebida: '$action'\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "Ação inválida!"]);
}

exit;
?>