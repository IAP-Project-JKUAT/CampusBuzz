<?php
// posts/create.php
require_once '../includes/db.php';

// Auth check
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$pageTitle = 'Create Post';
$basePath = '../'; // For header links
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $charCount = mb_strlen($content);

    if (empty($content)) {
        $errors[] = "Post content cannot be empty.";
    }
    if ($charCount > 280) {
        $errors[] = "Post exceeds 280 characters.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, character_count) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$_SESSION['user_id'], $content, $charCount]);
            $_SESSION['flash_message'] = "Post created successfully!";
            $_SESSION['flash_type'] = "success";
            header("Location: ../public/index.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-4"> <!-- mt-4 utility class we'll need in CSS -->
    <h1>Create New Post</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li>
                        <?php echo htmlspecialchars($error); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="create.php" method="POST" class="post-form">
        <div class="form-group">
            <label for="content">What's happening? (Max 280 chars)</label>
            <textarea id="content" name="content" rows="5" required maxlength="280"
                oninput="document.getElementById('char-count').textContent = this.value.length + '/280';"><?php echo isset($content) ? htmlspecialchars($content) : ''; ?></textarea>
            <small id="char-count" class="text-muted">0/280</small>
        </div>
        <button type="submit" class="btn btn-primary">Post</button>
        <a href="../public/index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</main>

<!-- Bottom Navigation -->
<nav class="bottom-nav">
    <a href="<?php echo $basePath; ?>public/index.php" class="nav-item">
        <span class="material-symbols-outlined">home</span>
        Home
    </a>
    <a href="<?php echo $basePath; ?>posts/create.php" class="fab active" aria-label="Create Post">
        <span class="material-symbols-outlined" style="font-size: 32px;">add</span>
    </a>
    <a href="<?php echo $basePath; ?>public/profile.php" class="nav-item">
        <span class="material-symbols-outlined">person</span>
        Profile
    </a>
</nav>

</body>

</html>