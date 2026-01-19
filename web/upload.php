<?php
session_start();
require 'config.php';
if (!isset($_SESSION['auth'])) exit;

$base = realpath(SERVER_PATH);
$p = realpath($_GET['p'] ?? $base);
if (!$p || strpos($p, $base) !== 0) exit("Bad path");

if ($_FILES) {
    move_uploaded_file($_FILES['f']['tmp_name'], $p.'/'.basename($_FILES['f']['name']));
    header("Location: files.php?p=".urlencode($p));
}
?>
<form method="post" enctype="multipart/form-data">
<input type="file" name="f">
<button>Upload</button>
</form>
