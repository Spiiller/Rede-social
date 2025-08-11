<?php
ob_start(); // Inicia o buffer de saída
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Preencha todos os campos!"]);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT id, name, password FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(["success" => false, "message" => "Usuário não encontrado!"]);
            exit;
        }

        if (!password_verify($password, $user["password"])) {
            echo json_encode(["success" => false, "message" => "Senha incorreta!"]);
            exit;
        }

        session_start();
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];

        echo json_encode(["success" => true, "message" => "Login bem-sucedido!", "redirect" => "index.html"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erro no banco: " . $e->getMessage()]);
    }
}
file_put_contents('debug.log', "Email: $email\n", FILE_APPEND);
ob_end_flush(); // Libera o buffer
?>