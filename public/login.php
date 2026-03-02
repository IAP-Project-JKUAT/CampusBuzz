<?php
// public/login.php
require_once '../includes/db.php';

$pageTitle = 'Login';
$basePath = '../';
$errors = [];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_email = trim($_POST['username_email']);
    $password = $_POST['password'];

    if (empty($username_email) || empty($password)) {
        $errors[] = "Please enter both username/email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username_email, $username_email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['flash_message'] = "Welcome back!";
            $_SESSION['flash_type'] = "success";
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Invalid credentials.";
        }
    }
}

// We don't include standard header here because login page has unique layout
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CampusBuzz</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/theme.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
</head>

<body style="justify-content: center;">

    <div class="auth-container">
        <div class="auth-logo">
            <span class="material-symbols-outlined" style="font-size: 48px;">campaign</span>
        </div>
        <h1 class="auth-title">Campus Buzz</h1>
        <p class="auth-subtitle">Your university community, unfiltered.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?>">
                <?php
                echo $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
                ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" style="width: 100%;">
            <div class="form-group">
                <label for="username_email">University Email</label>
                <input type="text" id="username_email" name="username_email" placeholder="yourname@university.edu"
                    required value="<?php echo isset($username_email) ? htmlspecialchars($username_email) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500; cursor: pointer;">
                    <input type="checkbox" style="width: auto; height: auto;"> Remember me
                </label>
                <a href="#"
                    style="color: var(--primary-color); text-decoration: none; font-weight: 600; font-size: 0.875rem;">Forgot
                    password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Login <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </form>

        <div style="margin-top: 2rem; text-align: center;">
            <p style="color: var(--text-muted);">Don't have an account? <a href="register.php"
                    style="color: var(--primary-color); font-weight: 700; text-decoration: none;">Sign up</a></p>
        </div>

        <button id="theme-toggle" class="btn btn-ghost" style="position: absolute; top: 1rem; right: 1rem;">
            <span class="material-symbols-outlined">dark_mode</span>
        </button>
    </div>

</body>

</html>