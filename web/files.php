<?php
session_start();
require 'config.php';
if (!isset($_SESSION['auth'])) exit;

$base = realpath(SERVER_PATH);
$path = realpath($_GET['p'] ?? $base);
if (!$path || strpos($path, $base) !== 0) $path = $base;

echo "<h2>Files: ".htmlspecialchars($path)."</h2>";
echo "<pre>";

foreach (scandir($path) as $f) {
    if ($f === '.') continue;
    $full = $path.'/'.$f;
    echo is_dir($full) ? "[DIR] " : "[FILE] ";
    echo "<a href='?p=".urlencode($full)."'>".htmlspecialchars($f)."</a>\n";
}

echo "</pre>";
echo "<a href='upload.php?p=".urlencode($path)."'>Upload</a>";
