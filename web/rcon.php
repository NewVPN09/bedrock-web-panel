<?php
session_start();
require 'csrf.php';
require 'config.php';
if (!isset($_SESSION['auth'])) exit;
csrf_check();

$cmd = trim($_POST['cmd'] ?? '');
if (!$cmd) exit;

$s = fsockopen("udp://".RCON_HOST, RCON_PORT);
fwrite($s, $cmd."\n");
fclose($s);

header("Location: index.php");
