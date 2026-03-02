<?php
// posts/edit.php
require_once '../includes/db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$pageTitle = 'Edit Post';
$basePath = '../';
$errors = [];

if (!isset($_GET['id'])) {
    header("Location: ../public/index.php");
    exit;
}

$postId = $_GET['id'];

// Fetch post and verify ownership
$stmt = $pdo->prepare("SELECT * FROM posts WHERE post_id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    die("Access denied or post not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $charCount = mb_strlen($content);

    if (empty($content)) {
        $errors[] = "Content cannot be empty.";
    }
    if ($charCount > 280) {
        $errors[] = "Post exceeds 280 characters.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE posts SET content = ?, character_count = ? WHERE post_id = ?");
        $stmt->execute([$content, $charCount, $postId]);
        $_SESSION['flash_message'] = "Post updated successfully!";
        $_SESSION['flash_type'] = "success";
        header("Location: ../public/index.php");
        exit;
    }
} else {
    $content = $post['content'];
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Edit Post</h1>

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

    <form action="edit.php?id=<?php echo $postId; ?>" method="POST" class="post-form">
        <div class="form-group">
            <label for="content">Edit your buzz (Max 280 chars)</label>
            <textarea id="content" name="content" rows="5" required maxlength="280"
                oninput="document.getElementById('char-count').textContent = this.value.length + '/280';"><?php echo htmlspecialchars($content); ?></textarea>
            <small id="char-count" class="text-muted">
                <?php echo mb_strlen($content); ?>/280
            </small>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
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
    <a href="<?php echo $basePath; ?>posts/create.php" class="fab" aria-label="Create Post">
        <span class="material-symbols-outlined" style="font-size: 32px;">add</span>
    </a>
    <a href="<?php echo $basePath; ?>public/profile.php" class="nav-item">
        <span class="material-symbols-outlined">person</span>
        Profile
    </a>
</nav>

</body>

</html>