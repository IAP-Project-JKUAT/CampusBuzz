<?php
// posts/delete.php — Delete a post (supports both AJAX/JSON and classic form POST)
require_once '../includes/db.php';

session_start();

$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if ($isAjax) {
    header('Content-Type: application/json');
}

if (!isset($_SESSION['user_id'])) {
    if ($isAjax) {
        echo json_encode(['error' => 'Unauthorized']);
        http_response_code(401);
    } else {
        header("Location: ../public/login.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        echo json_encode(['error' => 'Method not allowed']);
        http_response_code(405);
    } else {
        header("Location: ../public/index.php");
    }
    exit;
}

// Support both JSON body (AJAX) and form POST
if ($isAjax) {
    $data = json_decode(file_get_contents('php://input'), true);
    $postId = isset($data['post_id']) ? (int) $data['post_id'] : 0;
} else {
    $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
}

if ($postId <= 0) {
    if ($isAjax) {
        echo json_encode(['error' => 'Invalid post_id']);
        http_response_code(400);
    } else {
        header("Location: ../public/index.php");
    }
    exit;
}

// Verify ownership
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if ($post && $post['user_id'] == $_SESSION['user_id']) {
    $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
    $stmt->execute([$postId]);

    if ($isAjax) {
        echo json_encode(['success' => true]);
    } else {
        $_SESSION['flash_message'] = "Post deleted successfully.";
        $_SESSION['flash_type'] = "success";
        header("Location: ../public/index.php");
    }
} else {
    if ($isAjax) {
        echo json_encode(['error' => 'Access denied or post not found']);
        http_response_code(403);
    } else {
        $_SESSION['flash_message'] = "Access denied or post not found.";
        $_SESSION['flash_type'] = "danger";
        header("Location: ../public/index.php");
    }
}
exit;