<?php
require_once 'config.php';
require_once 'debug_log.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !$_SESSION['user']['is_admin']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (isset($_GET['post_id'])) {
            $post_id = (int)$_GET['post_id'];
            $query = "SELECT p.id, p.plant_name AS plantName, p.description, p.date_created AS dateCreated,
                     COALESCE(GROUP_CONCAT(pi.image_url ORDER BY pi.display_order), '') AS imageUrls
                     FROM posts p 
                     LEFT JOIN post_images pi ON p.id = pi.post_id 
                     WHERE p.id = ? 
                     GROUP BY p.id, p.plant_name, p.description, p.date_created";
            $stmt = $db->prepare($query);
            $stmt->execute([$post_id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($post) {
                $post['images'] = $post['imageUrls'] 
                    ? array_filter(explode(',', $post['imageUrls'])) 
                    : [];
                unset($post['imageUrls']);
                echo json_encode(['success' => true, 'post' => $post]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Post not found']);
            }
        } else {
            $query = "SELECT 
                        p.id, 
                        p.plant_name AS plantName, 
                        p.description, 
                        p.date_created AS dateCreated,
                        COALESCE(GROUP_CONCAT(pi.image_url ORDER BY pi.display_order), '') AS imageUrls
                      FROM posts p
                      LEFT JOIN post_images pi ON p.id = pi.post_id
                      GROUP BY p.id, p.plant_name, p.description, p.date_created
                      ORDER BY p.date_created DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($posts as &$post) {
                $post['images'] = $post['imageUrls'] 
                    ? array_filter(explode(',', $post['imageUrls'])) 
                    : [];
                unset($post['imageUrls']);
            }
            echo json_encode(['success' => true, 'posts' => $posts]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao carregar posts: ' . $e->getMessage()]);
    }
    exit;
}


// ========== POST ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $plantName = $_POST['plantName'] ?? '';
        $description = $_POST['description'] ?? '';
        $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;
        $imagesToRemove = isset($_POST['imagesToRemove']) ? json_decode($_POST['imagesToRemove'], true) : [];

        // Editar post existente
        if ($post_id) {
            $stmt = $db->prepare("UPDATE posts SET plant_name = ?, description = ? WHERE id = ?");
            $stmt->execute([$plantName, $description, $post_id]);

            // Remover imagens marcadas
            if ($imagesToRemove && is_array($imagesToRemove)) {
                foreach ($imagesToRemove as $imgUrl) {
                    // Excluir arquivo físico se for dentro de uploads/
                    if (strpos($imgUrl, 'uploads/') === 0 && file_exists($imgUrl)) {
                        @unlink($imgUrl);
                    }
                    $stmt = $db->prepare("DELETE FROM post_images WHERE post_id = ? AND image_url = ?");
                    $stmt->execute([$post_id, $imgUrl]);
                }
            }

            // Upload de novas imagens
            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
                    $filename = uniqid('img_') . '_' . basename($_FILES['images']['name'][$i]);
                    $filepath = 'uploads/' . $filename;
                    move_uploaded_file($tmpName, $filepath);
                    $stmt = $db->prepare("INSERT INTO post_images (post_id, image_url, display_order) VALUES (?, ?, ?)");
                    $stmt->execute([$post_id, $filepath, $i]);
                }
            }
            echo json_encode(['success' => true]);
            exit;
        }

        // Criar novo post (corrigido para incluir user_id)
        $userId = $_SESSION['user']['id'];
        $stmt = $db->prepare("INSERT INTO posts (plant_name, description, date_created, user_id) VALUES (?, ?, NOW(), ?)");
        $stmt->execute([$plantName, $description, $userId]);
        $newPostId = $db->lastInsertId();

        // Upload de imagens novas
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
                $filename = uniqid('img_') . '_' . basename($_FILES['images']['name'][$i]);
                $filepath = 'uploads/' . $filename;
                move_uploaded_file($tmpName, $filepath);
                $stmt = $db->prepare("INSERT INTO post_images (post_id, image_url, display_order) VALUES (?, ?, ?)");
                $stmt->execute([$newPostId, $filepath, $i]);
            }
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar/editar post: ' . $e->getMessage()]);
    }
    exit;
}

// ========== DELETE ==========
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $post_id = isset($_DELETE['post_id']) ? (int)$_DELETE['post_id'] : null;
    if ($post_id) {
        // Remover imagens do post
        $db->prepare("DELETE FROM post_images WHERE post_id = ?")->execute([$post_id]);
        // Remover post
        $db->prepare("DELETE FROM posts WHERE id = ?")->execute([$post_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID do post não informado']);
    }
    exit;
}

// ========== MÉTODO NÃO PERMITIDO ==========
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
exit;
?>
