<?php
// public/update_profile.php
require_once '../includes/db.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$bio = trim($data['bio'] ?? '');
$userId = (int) $_SESSION['user_id'];

if (empty($username)) {
    echo json_encode(['error' => 'Username cannot be empty']);
    exit;
}

// Check if username is already taken by another user
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
$stmt->execute([$username, $userId]);
if ($stmt->fetch()) {
    echo json_encode(['error' => 'Username is already taken']);
    exit;
}

// Update user in the database
$stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ? WHERE user_id = ?");
$success = $stmt->execute([$username, $bio, $userId]);

if ($success) {
    // Update session username so other parts of the site reflect it
    $_SESSION['username'] = $username;
    
    echo json_encode([
        'success' => true,
        'user' => [
            'username' => htmlspecialchars($username),
            'bio' => htmlspecialchars($bio)
        ]
    ]);
} else {
    echo json_encode(['error' => 'Failed to update profile']);
}
