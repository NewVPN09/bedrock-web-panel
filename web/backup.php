<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) exit;
csrf_check();

$backup_dir = '/home/minecraft/backups';
$server_dir = '/home/minecraft/Server';
$time = date('Y-m-d_H-i-s');
$backup_file = "$backup_dir/backup_$time.zip";

if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

$zip = new ZipArchive();
if ($zip->open($backup_file, ZipArchive::CREATE) === TRUE) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($server_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($server_dir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
    echo "<p>Backup created: <a href='backup.php?download=$backup_file'>Download</a></p>";
} else {
    echo "<p>Failed to create backup!</p>";
}

// Download the backup if requested
if (isset($_GET['download']) && file_exists($_GET['download'])) {
    $file = $_GET['download'];
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=' . basename($file));
    readfile($file);
    exit;
}
?>
<a href="index.php">Back to panel</a>
