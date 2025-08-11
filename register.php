<?php
ob_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        echo json_encode(["success" => false, "message" => "Todos os campos são obrigatórios!"]);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(["success" => false, "message" => "As senhas não coincidem!"]);
        exit;
    }

    try {
        // Verificar se o email já existe
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => false, "message" => "Email já cadastrado!"]);
            exit;
        }

        // Criar hash da senha com BCRYPT
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Inserir usuário no banco
        $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashed_password
        ]);

        echo json_encode(["success" => true, "message" => "Registro bem-sucedido! Faça login.", "redirect" => "login.html"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erro no banco: " . $e->getMessage()]);
    }
}
ob_end_flush();
?>
