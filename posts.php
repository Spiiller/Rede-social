<?php
require_once 'config.php';
require_once 'debug_log.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

debug_log("Request to posts.php", [
    'method' => $_SERVER['REQUEST_METHOD'],
    'query' => $_GET,
    'session' => isset($_SESSION['user']) ? [
        'user_id' => $_SESSION['user']['id'],
        'is_admin' => $_SESSION['user']['is_admin']
    ] : 'No user session'
]);

header('Content-Type: application/json');

$current_user_id = isset($_SESSION['user']) ? (string)$_SESSION['user']['id'] : null;

// ==================== GET =========================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // GET comentários de um post específico
    if (isset($_GET['comments_for'])) {
        $post_id = (int)$_GET['comments_for'];
        $stmt = $db->prepare("
            SELECT c.id, c.text, c.user_id, u.name AS user
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.date_created ASC
        ");
        $stmt->execute([$post_id]);
        $comments = [];
        $user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0;
        $is_admin = isset($_SESSION['user']['is_admin']) ? $_SESSION['user']['is_admin'] : 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
            $comment['canDelete'] = ($comment['user_id'] == $user_id || $is_admin);
            $comments[] = $comment;
        }
        echo json_encode(['success' => true, 'comments' => $comments]);
        exit;
    }

    // GET de posts salvos pelo usuário
    if (isset($_GET['saved_by'])) {
        $user_id = (int)$_GET['saved_by'];
        if (!isset($_SESSION['user']) || $_SESSION['user']['id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            exit;
        }

        $query = "
            SELECT 
                p.id, 
                p.plant_name AS plantName, 
                p.description, 
                p.date_created AS dateCreated,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS likes,
                (SELECT GROUP_CONCAT(user_id) FROM post_likes WHERE post_id = p.id) AS likedBy,
                (SELECT GROUP_CONCAT(user_id) FROM post_saves WHERE post_id = p.id) AS savedBy,
                GROUP_CONCAT(DISTINCT pi.image_url ORDER BY pi.display_order SEPARATOR '||') AS imageUrls
            FROM 
                posts p
            INNER JOIN 
                post_saves ps ON p.id = ps.post_id
            LEFT JOIN 
                post_images pi ON p.id = pi.post_id
            WHERE 
                ps.user_id = ?
            GROUP BY 
                p.id
            ORDER BY 
                p.date_created DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
    } else {
        // GET de todos os posts
        $query = "
            SELECT 
                p.id, 
                p.plant_name AS plantName, 
                p.description, 
                p.date_created AS dateCreated,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS likes,
                (SELECT GROUP_CONCAT(user_id) FROM post_likes WHERE post_id = p.id) AS likedBy,
                (SELECT GROUP_CONCAT(user_id) FROM post_saves WHERE post_id = p.id) AS savedBy,
                GROUP_CONCAT(DISTINCT pi.image_url ORDER BY pi.display_order SEPARATOR '||') AS imageUrls
            FROM 
                posts p
            LEFT JOIN 
                post_images pi ON p.id = pi.post_id
            GROUP BY 
                p.id
            ORDER BY 
                p.date_created DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
    }

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts as &$post) {
        $post['images'] = $post['imageUrls'] ? explode('||', $post['imageUrls']) : [];
        unset($post['imageUrls']);
        $post['likes'] = (int)$post['likes'];
        $post['savedBy'] = $post['savedBy'] ? explode(',', $post['savedBy']) : [];
        $post['likedBy'] = $post['likedBy'] ? explode(',', $post['likedBy']) : [];
        $post['saved'] = $current_user_id && in_array($current_user_id, $post['savedBy']);
        $post['liked'] = $current_user_id && in_array($current_user_id, $post['likedBy']);
        $post['comments'] = []; // Comentários são carregados separadamente via GET comments_for
    }

    echo json_encode(['success' => true, 'posts' => $posts]);
    exit;
}

// ==================== PATCH =========================
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    parse_str($_SERVER['QUERY_STRING'], $params);
    $post_id = isset($params['post_id']) ? (int)$params['post_id'] : null;
    $user_id = $_SESSION['user']['id'];

    if (!$post_id) {
        echo json_encode(['success' => false, 'message' => 'Parâmetro post_id ausente']);
        exit;
    }

    // Toggle salvar
    $stmt = $db->prepare('SELECT id FROM post_saves WHERE post_id = ? AND user_id = ?');
    $stmt->execute([$post_id, $user_id]);
    if ($stmt->fetch()) {
        // Já está salvo, remove
        $db->prepare('DELETE FROM post_saves WHERE post_id = ? AND user_id = ?')->execute([$post_id, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Post removido dos salvos']);
    } else {
        // Não está salvo, salva
        $db->prepare('INSERT INTO post_saves (post_id, user_id) VALUES (?, ?)')->execute([$post_id, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Post salvo']);
    }
    exit;
}

// ==================== POST =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    // Recebe dados do POST
    $action = $_POST['action'] ?? '';
    $post_id = $_POST['post_id'] ?? null;
    $user_id = $_SESSION['user']['id'];

    // Curtir/descurtir
    if ($action === 'like' && $post_id) {
        $stmt = $db->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        if ($stmt->fetch()) {
            // Já curtiu, então remove
            $db->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?")->execute([$post_id, $user_id]);
            echo json_encode(['success' => true, 'message' => 'Like removido.']);
        } else {
            // Não curtiu, então adiciona
            $db->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
            echo json_encode(['success' => true, 'message' => 'Like adicionado.']);
        }
        exit;
    }

    // Adicionar comentário
    if ($action === 'comment' && $post_id && isset($_POST['comment'])) {
        $comment = $_POST['comment'];
        $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, text, date_created) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$post_id, $user_id, $comment]);
        echo json_encode(['success' => true, 'message' => 'Comentário adicionado.']);
        exit;
    }

    // Excluir comentário
    if ($action === 'delete_comment' && isset($_POST['comment_id'])) {
        $comment_id = (int)$_POST['comment_id'];
        // Permitir apagar se for admin ou dono do comentário
        $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && ($row['user_id'] == $user_id || !empty($_SESSION['user']['is_admin']))) {
            $db->prepare("DELETE FROM comments WHERE id = ?")->execute([$comment_id]);
            echo json_encode(['success' => true, 'message' => 'Comentário excluído.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Sem permissão para excluir.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Requisição POST inválida.']);
    exit;
}

// ==================== MÉTODO NÃO PERMITIDO =========================
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
exit;
?>
