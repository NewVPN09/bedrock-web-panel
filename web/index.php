<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) { header('Location: login.php'); exit; }
?>
<link rel="stylesheet" href="assets/style.css">

<h1>Minecraft Bedrock Control Panel</h1>

<div class="grid">

<div class="card">
<h2>Server Control</h2>
<form method="post" action="control.php">
<input type="hidden" name="csrf" value="<?= csrf_token() ?>">
<button name="action" value="start">Start</button>
<button name="action" value="stop">Stop</button>
<button name="action" value="restart">Restart</button>
</form>
Status: <pre><?php include 'status.php'; ?></pre>
</div>

<div class="card">
<h2>System Info</h2>
<?php include 'sysinfo.php'; ?>
</div>

<div class="card">
<h2>Console (RCON)</h2>
<form method="post" action="rcon.php">
<input type="hidden" name="csrf" value="<?= csrf_token() ?>">
<input name="cmd" placeholder="Command">
<button>Send</button>
</form>
</div>

<div class="card">
<h2>Players</h2>
<?php include 'players.php'; ?>
</div>

<div class="card">
<h2>Backups</h2>
<a href="backup.php">Create Backup</a><br>
<a href="restore.php">Restore Backup</a>
</div>

<div class="card">
<h2>Files</h2>
<a href="files.php">Open File Manager</a>
</div>

<div class="card">
<h2>Logs</h2>
<?php include 'logs.php'; ?>
</div>

</div>

<a href="logout.php">Logout</a>
