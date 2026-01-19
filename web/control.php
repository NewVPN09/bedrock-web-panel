<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Unauthorized");
}

// CSRF check
csrf_check();

// Allowed actions
$allowed = ['start','stop','restart'];
$action = $_POST['action'] ?? '';

if (!in_array($action, $allowed)) {
    $_SESSION['message'] = "Invalid action: " . htmlspecialchars($action);
    header("Location: index.php");
    exit;
}

// Run the command synchronously and capture output/errors
$cmd = escapeshellcmd("sudo systemctl $action bedrock");
$output = shell_exec("$cmd 2>&1");

// Save message in session to show in index.php
$_SESSION['message'] = htmlspecialchars($output ?: "Command executed: $action");

// Redirect back to panel
header("Location: index.php");
exit;
