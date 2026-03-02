<?php
// posts/like.php — Toggle like on a post (AJAX / JSON)
require_once '../includes/db.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    http_response_code(405);
    exit;
}

$data    = json_decode(file_get_contents('php://input'), true);
$postId  = isset($data['post_id']) ? (int) $data['post_id'] : 0;
$userId  = (int) $_SESSION['user_id'];

if ($postId <= 0) {
    echo json_encode(['error' => 'Invalid post_id']);
    http_response_code(400);
    exit;
}

// Check if already liked
$stmt = $pdo->prepare("SELECT like_id FROM post_likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$postId, $userId]);
$existing = $stmt->fetch();

if ($existing) {
    // Unlike
    $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    $liked = false;
} else {
    // Like
    $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
    $stmt->execute([$postId, $userId]);
    $liked = true;
}

// Get updated count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
$stmt->execute([$postId]);
$count = (int) $stmt->fetchColumn();

echo json_encode(['liked' => $liked, 'count' => $count]);
