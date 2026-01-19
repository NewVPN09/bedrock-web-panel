<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) exit;
csrf_check();

$log_file = "/home/minecraft/minecraft.log";

if(isset($_GET['live'])){
    if(file_exists($log_file)){
        // Show last 100 lines
        $lines = shell_exec("tail -n 100 " . escapeshellarg($log_file));
        echo $lines;
    } else {
        echo "Log file not found.";
    }
    exit;
}
?>
