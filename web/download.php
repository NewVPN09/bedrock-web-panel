<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) exit;

// Check CSRF token
if (!isset($_GET['csrf']) || $_GET['csrf'] !== $_SESSION['csrf']) {
    http_response_code(403);
    exit('CSRF validation failed');
}

if (!isset($_GET['file'])) exit;

$file = realpath($_GET['file']);
if (!$file || !file_exists($file)) exit;

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . basename($file));
readfile($file);
exit;
?>
