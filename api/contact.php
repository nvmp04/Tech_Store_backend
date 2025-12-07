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

/*
|--------------------------------------------------------------------------
| GET REQUEST
|--------------------------------------------------------------------------
| Admin:
|   GET /contact                → Lấy contact chưa có reply
|
| User:
|   GET /contact?user_id=123   → Lấy tất cả contact thuộc về user đó
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

    try {
        if ($userId) {
            // USER: Lấy toàn bộ contact của user này
            $stmt = $pdo->prepare(
                'SELECT id, user_id, user_name, email, title, message, reply, created_at
                 FROM contact
                 WHERE user_id = ?
                 ORDER BY created_at DESC'
            );
            $stmt->execute([$userId]);
            $contacts = $stmt->fetchAll();

            echo json_encode(['success' => true, 'contacts' => $contacts], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ADMIN: Lấy contact chưa có phản hồi
        $stmt = $pdo->prepare(
            'SELECT id, user_id, user_name, email, title, message, reply, created_at
             FROM contact
             WHERE reply IS NULL OR reply = ""
             ORDER BY created_at DESC'
        );
        $stmt->execute();
        $contacts = $stmt->fetchAll();

        echo json_encode(['success' => true, 'contacts' => $contacts], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| POST REQUEST — User gửi liên hệ
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }

    $user_id = $input['user_id'] ?? null;
    $user_name = trim($input['user_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $title = trim($input['title'] ?? '');
    $message = trim($input['message'] ?? '');

    $errors = [];
    if (!$user_id) $errors[] = 'user_id is required';
    if (!$user_name) $errors[] = 'user_name is required';
    if (!$email) $errors[] = 'email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email is invalid';
    if (!$title) $errors[] = 'title is required';
    if (!$message) $errors[] = 'message is required';

    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO contact (user_id, user_name, email, title, message)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user_id, $user_name, $email, $title, $message]);

        echo json_encode([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'message' => 'Contact created'
        ]);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| PUT REQUEST — Admin reply contact
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }

    $id = $input['id'] ?? null;
    $reply = trim($input['reply'] ?? '');

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'id is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('UPDATE contact SET reply = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$reply, $id]);

        echo json_encode(['success' => true, 'message' => 'Reply updated']);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| DELETE REQUEST — Xóa contact
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'id is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM contact WHERE id = ?');
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Contact deleted']);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
exit;
