<?php
session_start();
require 'config.php';
if (!isset($_SESSION['auth'])) exit;

$f = realpath($_GET['f'] ?? '');
$base = realpath(SERVER_PATH);

if ($f && strpos($f, $base) === 0) {
    if (is_dir($f)) rmdir($f); else unlink($f);
}
header("Location: files.php");
