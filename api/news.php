<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db = new Database();
$pdo = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare('SELECT id, title, excerpt, content, thumbnail, created_at FROM news ORDER BY created_at DESC');
        $stmt->execute();
        $news = $stmt->fetchAll();

        echo json_encode(['success' => true, 'news' => $news], JSON_UNESCAPED_UNICODE);
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

    $title = isset($input['title']) ? trim($input['title']) : null;
    $excerpt = isset($input['excerpt']) ? trim($input['excerpt']) : null;
    $content = isset($input['content']) ? trim($input['content']) : null;
    $thumbnail = isset($input['thumbnail']) ? trim($input['thumbnail']) : null;

    $errors = [];
    if (!$title) $errors[] = 'title is required';
    if (!$content) $errors[] = 'content is required';

    if (count($errors) > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO news (title, excerpt, content, thumbnail) VALUES (?, ?, ?, ?)');
        $stmt->execute([$title, $excerpt, $content, $thumbnail]);

        $newsId = $pdo->lastInsertId();

        echo json_encode(['success' => true, 'id' => $newsId, 'message' => 'News created']);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    $id = isset($input['id']) ? (int)$input['id'] : null;
    $title = isset($input['title']) ? trim($input['title']) : null;
    $excerpt = isset($input['excerpt']) ? trim($input['excerpt']) : null;
    $content = isset($input['content']) ? trim($input['content']) : null;
    $thumbnail = isset($input['thumbnail']) ? trim($input['thumbnail']) : null;

    $errors = [];
    if (!$id) $errors[] = 'id is required';
    if (!$title) $errors[] = 'title is required';
    if (!$content) $errors[] = 'content is required';

    if (count($errors) > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    try {
        // Check if news exists
        $checkStmt = $pdo->prepare('SELECT id FROM news WHERE id = ? LIMIT 1');
        $checkStmt->execute([$id]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'News not found']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE news SET title = ?, excerpt = ?, content = ?, thumbnail = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$title, $excerpt, $content, $thumbnail, $id]);

        echo json_encode(['success' => true, 'id' => $id, 'message' => 'News updated']);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    $id = isset($input['id']) ? (int)$input['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id is required']);
        exit;
    }

    try {
        // Check if news exists
        $checkStmt = $pdo->prepare('SELECT id FROM news WHERE id = ? LIMIT 1');
        $checkStmt->execute([$id]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'News not found']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM news WHERE id = ?');
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'id' => $id, 'message' => 'News deleted']);
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
