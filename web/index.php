<?php
session_start();
require 'csrf.php';
require 'config.php';

if (!isset($_SESSION['auth'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Minecraft Bedrock Panel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<h1>Minecraft Bedrock Control Panel</h1>

<div class="grid">

    <!-- Server Control -->
    <div class="card">
        <h2>Server Control</h2>
        <form method="post" action="control.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <button name="action" value="start">Start</button>
            <button name="action" value="stop">Stop</button>
            <button name="action" value="restart">Restart</button>
        </form>
        <h3>Status</h3>
        <pre><?php echo htmlspecialchars(trim(shell_exec("systemctl is-active bedrock"))); ?></pre>
    </div>

    <!-- System Info -->
    <div class="card">
        <h2>System Info</h2>
        <?php include 'sysinfo.php'; ?>
    </div>

    <!-- RCON Console -->
    <div class="card">
        <h2>Console (RCON)</h2>
        <form method="post" action="rcon.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input name="cmd" placeholder="Command">
            <button type="submit">Send</button>
        </form>
        <pre id="rcon-output">
            <?php
            if (isset($_SESSION['rcon_output'])) {
                echo htmlspecialchars($_SESSION['rcon_output']);
                unset($_SESSION['rcon_output']);
            }
            ?>
        </pre>
    </div>

    <!-- Player List -->
    <div class="card">
        <h2>Players</h2>
        <?php include 'players.php'; ?>
    </div>

    <!-- Backups -->
    <div class="card">
        <h2>Backups</h2>
        <form method="post" action="backup.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <button type="submit">Create Backup</button>
        </form>
        <form method="post" action="restore.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <select name="backup_file">
                <?php
                foreach (glob('/home/minecraft/backups/*.zip') as $file) {
                    $base = basename($file);
                    echo "<option value=\"$base\">$base</option>";
                }
                ?>
            </select>
            <button type="submit">Restore Backup</button>
        </form>
    </div>

    <!-- File Manager -->
    <div class="card">
        <h2>Files</h2>
        <a href="files.php">Open File Manager</a>
    </div>

    <!-- Logs -->
    <div class="card">
        <h2>Logs</h2>
        <pre><?php include 'logs.php'; ?></pre>
    </div>

</div>

<div class="logout">
    <a href="logout.php">Logout</a>
</div>

</body>
</html>
