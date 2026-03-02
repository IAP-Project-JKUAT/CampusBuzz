<?php
// public/logout.php
session_start();
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/'); // Delete session cookie

session_start(); // Start new session for flush message
$_SESSION['flash_message'] = "You have been logged out.";
$_SESSION['flash_type'] = "info";

header("Location: login.php");
exit;
?>