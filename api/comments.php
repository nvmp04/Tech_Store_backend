<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db = new Database();
$pdo = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    if (!$product_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'product_id is required']);
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT c.id, c.parent_id, u.full_name as userName, DATE_FORMAT(c.created_at, "%d/%m/%Y") as date, c.verified, c.content as comment, c.rating FROM comments c JOIN users u ON u.id = c.user_id WHERE c.product_id = ? AND c.status = "active" ORDER BY c.created_at DESC'
    );
    $stmt->execute([$product_id]);
    $comments = $stmt->fetchAll();

    echo json_encode(['success' => true, 'comments' => $comments], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Only require comment id in the request body; derive user from auth token
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    $commentId = isset($input['id']) ? trim($input['id']) : null;

    if (!$commentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id is required']);
        exit;
    }

    try {
        // find comment
        $stmt = $pdo->prepare('SELECT id, user_id, status FROM comments WHERE id = ? LIMIT 1');
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch();
        if (!$comment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Comment not found']);
            exit;
        }

        if ($comment['status'] === 'deleted') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Comment already deleted']);
            exit;
        }

        // Validate caller is owner or admin via AuthMiddleware (derives user from token)
        $currentUser = AuthMiddleware::requireOwnerOrAdmin($comment['user_id']);

        // Begin transaction to atomically mark parent and its descendant comments deleted
        $pdo->beginTransaction();

        // Collect the IDs to delete: parent + all descendants (recursive)
        $idsToDelete = [$commentId];
        $cursor = 0;
        while ($cursor < count($idsToDelete)) {
            $current = $idsToDelete[$cursor];
            $childStmt = $pdo->prepare('SELECT id FROM comments WHERE parent_id = ? AND status != "deleted"');
            $childStmt->execute([$current]);
            $childRows = $childStmt->fetchAll();
            foreach ($childRows as $cr) {
                if (!in_array($cr['id'], $idsToDelete)) {
                    $idsToDelete[] = $cr['id'];
                }
            }
            $cursor++;
        }

        // Build query to update all ids in one statement
        $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
        $sql = "UPDATE comments SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)";
        $params = array_merge(['deleted'], $idsToDelete);
        $updStmt = $pdo->prepare($sql);
        $updStmt->execute($params);

        $pdo->commit();

        echo json_encode(['success' => true, 'deleted_ids' => $idsToDelete, 'message' => 'Comment and its child comments deleted']);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    $user_id = $input['user_id'] ?? null;
    $product_id = isset($input['product_id']) ? (int)$input['product_id'] : null;
    $content = isset($input['content']) ? trim($input['content']) : null;
    $parent_id = isset($input['parent_id']) && $input['parent_id'] !== null ? trim($input['parent_id']) : null;
    $verified = isset($input['verified']) ? (bool)$input['verified'] : false;
    $rating = isset($input['rating']) ? (float)$input['rating'] : 0.0;

    $errors = [];
    if (!$user_id) $errors[] = 'user_id is required';
    if (!$product_id) $errors[] = 'product_id is required';
    if (!$content || $content === '') $errors[] = 'content is required';
    if ($rating !== null && (!is_numeric($rating) || $rating < 0 || $rating > 5)) $errors[] = 'rating must be a number between 0 and 5';

    if (count($errors) > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    try {
        // ensure product exists
        $stmt = $pdo->prepare('SELECT id FROM products WHERE id = ? LIMIT 1');
        $stmt->execute([$product_id]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        // ensure user exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        // if parent_id provided, ensure it exists
        if ($parent_id) {
            $stmt = $pdo->prepare('SELECT id FROM comments WHERE id = ? LIMIT 1');
            $stmt->execute([$parent_id]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parent comment not found']);
                exit;
            }
        }

        // rating is optional and default already set to 0.0 above
        $stmt = $pdo->prepare('INSERT INTO comments (product_id, user_id, content, rating, parent_id, verified, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$product_id, $user_id, $content, $rating, $parent_id, $verified ? 1 : 0]);

        $insertedId = $pdo->lastInsertId();

        echo json_encode(['success' => true, 'id' => $insertedId, 'message' => 'Comment created']);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
exit;

?>
