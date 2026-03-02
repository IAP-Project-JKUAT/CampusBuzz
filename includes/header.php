<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ensure $basePath is defined
if (!isset($basePath)) {
    $basePath = '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - CampusBuzz' : 'CampusBuzz'; ?></title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
    <script src="<?php echo $basePath; ?>assets/js/theme.js" defer></script>
</head>

<body>
    <!-- Top App Bar -->
    <header class="navbar">
        <div class="container">
            <a href="<?php echo $basePath; ?>public/index.php" class="logo">CampusBuzz</a>
            <div class="nav-actions">
                <button class="nav-icon-btn" aria-label="Search">
                    <span class="material-symbols-outlined">search</span>
                </button>
                <button class="nav-icon-btn" aria-label="Notifications" style="position: relative;">
                    <span class="material-symbols-outlined">notifications</span>
                    <span
                        style="position: absolute; top: 6px; right: 6px; width: 8px; height: 8px; background-color: var(--primary-color); border-radius: 50%; border: 2px solid var(--card-bg);"></span>
                </button>
                <button id="theme-toggle" class="nav-icon-btn" aria-label="Toggle Dark Mode">
                    <span class="material-symbols-outlined">dark_mode</span>
                </button>

                <!-- Desktop Nav (Hidden on Mobile) -->
                <div class="desktop-nav" style="display: flex; gap: 10px; margin-left: 10px;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo $basePath; ?>public/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo $basePath; ?>public/login.php" class="btn btn-primary btn-sm">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Bottom Navigation moved OUTSIDE <main> so it's not trapped by max-width: 600px container -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <nav class="bottom-nav">
            <a href="<?php echo $basePath; ?>public/index.php"
                class="nav-item <?php echo ($pageTitle === 'Feed') ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">home</span>
                Home
            </a>
            <a href="<?php echo $basePath; ?>posts/create.php" class="fab" aria-label="Create Post">
                <span class="material-symbols-outlined" style="font-size: 32px;">add</span>
            </a>
            <a href="<?php echo $basePath; ?>public/profile.php"
                class="nav-item <?php echo ($pageTitle === 'Profile') ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">person</span>
                Profile
            </a>
        </nav>
    <?php endif; ?>

    <main class="container" style="padding-bottom: 5rem;">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> mt-4">
                <?php
                echo $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
                ?>
            </div>
        <?php endif; ?>