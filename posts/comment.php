<?php
// posts/comment.php — Add a comment to a post (AJAX / JSON)
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

$data = json_decode(file_get_contents('php://input'), true);
$postId = isset($data['post_id']) ? (int) $data['post_id'] : 0;
$commentText = isset($data['comment_text']) ? trim((string) $data['comment_text']) : '';
$userId = (int) $_SESSION['user_id'];

if ($postId <= 0) {
    echo json_encode(['error' => 'Invalid post_id']);
    http_response_code(400);
    exit;
}

if ($commentText === '') {
    echo json_encode(['error' => 'Comment cannot be empty']);
    http_response_code(400);
    exit;
}

if (mb_strlen($commentText) > 280) {
    echo json_encode(['error' => 'Comment too long (max 280 characters)']);
    http_response_code(400);
    exit;
}

// Verify post exists
$stmt = $pdo->prepare("SELECT post_id FROM posts WHERE post_id = ?");
$stmt->execute([$postId]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Post not found']);
    http_response_code(404);
    exit;
}

// Insert comment
$stmt = $pdo->prepare("INSERT INTO post_comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
$stmt->execute([$postId, $userId, $commentText]);

// Fetch username for response
$stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

echo json_encode([
    'success' => true,
    'comment' => [
        'username' => $user['username'],
        'text' => $commentText,
        'created_at' => date('g:i a'),
    ]
]);
