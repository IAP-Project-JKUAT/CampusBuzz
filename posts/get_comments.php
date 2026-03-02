<?php
// posts/get_comments.php — Fetch comments for a post (AJAX / JSON)
require_once '../includes/db.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

$postId = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;

if ($postId <= 0) {
    echo json_encode(['error' => 'Invalid post_id']);
    http_response_code(400);
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.comment_text, c.created_at, u.username
    FROM post_comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$postId]);
$comments = $stmt->fetchAll();

// Format time for display
foreach ($comments as &$c) {
    $c['time'] = date('g:i a', strtotime($c['created_at']));
}
unset($c);

echo json_encode(['comments' => $comments]);
