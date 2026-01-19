<?php
session_start();
if (!isset($_SESSION['auth'])) exit;

$b = glob(BACKUP_PATH."/*.tar.gz");
rsort($b);
if (!$b) exit("No backups");

shell_exec("sudo tar -xzf ".escapeshellarg($b[0])." -C ".escapeshellarg(SERVER_PATH));
header("Location: index.php");
