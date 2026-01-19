<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) exit;
csrf_check();

if (!isset($_POST['backup_file'])) {
    echo "<p>No backup selected.</p><a href='index.php'>Back</a>";
    exit;
}

$backup_dir = '/home/minecraft/backups';
$server_dir = '/home/minecraft/Server';
$backup_file = $backup_dir . '/' . basename($_POST['backup_file']);

if (!file_exists($backup_file)) {
    echo "<p>Backup file not found!</p><a href='index.php'>Back</a>";
    exit;
}

// Stop server before restoring
shell_exec("sudo systemctl stop bedrock");

// Extract backup
$zip = new ZipArchive();
if ($zip->open($backup_file) === TRUE) {
    $zip->extractTo($server_dir);
    $zip->close();
    echo "<p>Backup restored successfully!</p>";
} else {
    echo "<p>Failed to restore backup!</p>";
}

// Start server again
shell_exec("sudo systemctl start bedrock");

echo "<a href='index.php'>Back to panel</a>";
?>
