<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) exit;

if (!isset($_GET['file'])) exit;

$file = realpath($_GET['file']);
if (!$file || !file_exists($file)) exit;

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . basename($file));
readfile($file);
exit;
?>
