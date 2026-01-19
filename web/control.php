<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) exit;
csrf_check();

$allowed = ['start','stop','restart'];
$action = $_POST['action'] ?? '';

if (!in_array($action, $allowed)) exit("Invalid action");

// Map each action to a non-blocking command
$cmds = [
    'start'   => "sudo systemctl start bedrock > /dev/null 2>&1 &",
    'stop'    => "sudo systemctl stop bedrock > /dev/null 2>&1 &",
    'restart' => "sudo systemctl restart bedrock > /dev/null 2>&1 &"
];

// Execute the command in background
shell_exec($cmds[$action]);

// Redirect back immediately
header("Location: index.php");
exit;
