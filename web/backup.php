<?php
session_start();
if (!isset($_SESSION['auth'])) exit;

$f = BACKUP_PATH."/backup_".date("Ymd_His").".tar.gz";
shell_exec("sudo tar -czf ".escapeshellarg($f)." -C ".escapeshellarg(SERVER_PATH)." worlds");

header("Location: index.php");
