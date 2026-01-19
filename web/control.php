<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) exit;
csrf_check();

$allowed = ['start','stop','restart'];
$action = $_POST['action'] ?? '';

if (!in_array($action, $allowed)) exit("Invalid action");

// Run synchronously and capture output/errors
$output = shell_exec("sudo systemctl $action bedrock 2>&1");

// Return result and redirect back
$_SESSION['message'] = htmlspecialchars($output);
header("Location: index.php");
exit;
