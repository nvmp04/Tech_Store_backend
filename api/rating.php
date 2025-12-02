<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

$user_id = $input['user_id'] ?? null;
$order_id = $input['order_id'] ?? null;
$rating = isset($input['rating']) ? (float)$input['rating'] : null;
$content = isset($input['content']) ? trim($input['content']) : null;
$verified = isset($input['verified']) ? (bool)$input['verified'] : false;
$parent_id = isset($input['parent_id']) && $input['parent_id'] !== null ? trim($input['parent_id']) : null;

$errors = [];
if (!$user_id) $errors[] = 'user_id is required';
if (!$order_id) $errors[] = 'order_id is required';
if ($rating === null) $errors[] = 'rating is required';
if ($rating !== null && (!is_numeric($rating) || $rating < 0 || $rating > 5)) $errors[] = 'rating must be a number between 0 and 5';

if (count($errors) > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$db = new Database();
$pdo = $db->connect();

try {
    // Validate user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Validate order exists and belongs to user
    $stmt = $pdo->prepare('SELECT id, user_id, rate FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    if ($order['user_id'] !== $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Order does not belong to the user']);
        exit;
    }
    // Check if order already rated
    if (!empty($order['rate']) && (int)$order['rate'] === 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order already rated']);
        exit;
    }

    // Get products in order
    $stmt = $pdo->prepare('SELECT product_id FROM order_items WHERE order_id = ?');
    $stmt->execute([$order_id]);
        $productRows = $stmt->fetchAll();
    if (!$productRows || count($productRows) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order has no products']);
        exit;
    }

    // Optional: if parent_id present, validate parent exists and belongs to the same product(s)
    if ($parent_id) {
        $stmt = $pdo->prepare('SELECT id, product_id FROM comments WHERE id = ? LIMIT 1');
        $stmt->execute([$parent_id]);
        $parent = $stmt->fetch();
        if (!$parent) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parent comment not found']);
            exit;
        }
        // parent product may or may not match; it's ok if comment is for same product.
    }

    $pdo->beginTransaction();

    $updatedProducts = [];

    // Optional: allow rating for a specific product in the order
    $product_id_param = isset($input['product_id']) ? (int)$input['product_id'] : null;
    $orderProductIds = array_map(function($r){ return (int)$r['product_id']; }, $productRows);
    if ($product_id_param !== null) {
        if (!in_array($product_id_param, $orderProductIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'product_id not found in order']);
            exit;
        }
        $productIdsToProcess = [$product_id_param];
    } else {
        $productIdsToProcess = $orderProductIds;
    }

    foreach ($productIdsToProcess as $productId) {

        // Insert comment if content provided
        if (!is_null($content) && $content !== '') {
            // rating may be provided in the request; fallback to 0.0 if not
            $commentRating = is_numeric($rating) ? (float)$rating : 0.0;
            $insertComment = $pdo->prepare('INSERT INTO comments (product_id, user_id, content, rating, parent_id, verified, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $insertComment->execute([$productId, $user_id, $content, $commentRating, $parent_id, $verified ? 1 : 0]);
        }

        // Update product stats: recalc rating and increment reviews
        $selectProduct = $pdo->prepare('SELECT rating, reviews FROM products WHERE id = ? FOR UPDATE');
        $selectProduct->execute([$productId]);
        $productRow = $selectProduct->fetch();
        if (!$productRow) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Product not found during update']);
            exit;
        }

        $oldReviews = (int)($productRow['reviews'] ?? 0);
        $oldRating = (float)($productRow['rating'] ?? 0);
        $newReviews = $oldReviews + 1;
        $newRating = ($oldRating * $oldReviews + $rating) / $newReviews;
        $newRatingRounded = round($newRating, 1);

        $updateProduct = $pdo->prepare('UPDATE products SET rating = ?, reviews = ? WHERE id = ?');
        $updateProduct->execute([$newRatingRounded, $newReviews, $productId]);

        $updatedProducts[] = [
            'product_id' => $productId,
            'rating' => (float)$newRatingRounded,
            'reviews' => $newReviews
        ];
    }

    // Update order.rate to true
    $updateOrder = $pdo->prepare('UPDATE orders SET rate = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $updateOrder->execute([$order_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Rating saved and product(s) updated', 'products' => $updatedProducts], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
    exit;
}
?>