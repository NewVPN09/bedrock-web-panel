<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) exit;
csrf_check();

$allowed = ['start','stop','restart'];
$a = $_POST['action'] ?? '';

if (!in_array($a, $allowed)) exit("Invalid");

echo shell_exec("sudo systemctl $a bedrock");
header("Location: index.php");
