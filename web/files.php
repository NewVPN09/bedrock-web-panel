<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) exit;

$dir = $_GET['dir'] ?? '/home/minecraft/Server';
$dir = realpath($dir); // prevent directory traversal

if (!$dir || !is_dir($dir)) {
    echo "<p>Invalid directory!</p>";
    exit;
}

$files = scandir($dir);

echo "<h2>File Manager: $dir</h2>";
echo "<ul>";
if ($dir != '/home/minecraft/Server') {
    $parent = dirname($dir);
    echo "<li><a href='files.php?dir=$parent'>[..]</a></li>";
}
foreach ($files as $file) {
    if ($file == '.' || $file == '..') continue;
    $path = $dir . '/' . $file;
    if (is_dir($path)) {
        echo "<li>[DIR] <a href='files.php?dir=$path'>$file</a></li>";
    } else {
        echo "<li>[FILE] $file - <a href='download.php?file=$path'>Download</a></li>";
    }
}
echo "</ul>";

echo "<a href='index.php'>Back to panel</a>";
?>
