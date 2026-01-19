<?php
session_start();
require 'csrf.php';
require 'config.php';

if (!isset($_SESSION['auth'])) {
    header('Location: login.php');
    exit;
}

if(isset($_SESSION['message'])): ?>
    <div class="message"><?= $_SESSION['message'] ?></div>
    <?php unset($_SESSION['message']); ?>
<?php endif; 

    
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Minecraft Bedrock Panel</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* General */
body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg,#1f1c2c,#928dab);
    color: #fff;
}
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 22px;
    font-size: 22px;
    font-weight: bold;
    background: #5d3a9b;
}
main {
    max-width: 1000px;
    margin: 20px auto;
    padding: 20px;
    background: rgba(0,0,0,0.7);
    border-radius: 14px;
}
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}
.card {
    background: rgba(255,255,255,0.05);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.5);
}
h2 {
    color: #00bcd4;
    margin-top: 0;
}
button {
    padding: 10px 15px;
    margin: 5px 2px 0 0;
    background: #5d3a9b;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}
button:hover { background: #7c53c0; }
pre {
    background: #111;
    padding: 10px;
    border-radius: 8px;
    max-height: 250px;
    overflow: auto;
}
a {
    color: #00e5ff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
.logout {
    text-align: center;
    margin: 20px;
}
</style>
</head>
<body>

<header>
    Minecraft Bedrock Control Panel
    <a href="logout.php">Logout</a>
</header>

<main>
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
            <button>Send</button>
        </form>
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
    <div class="section">
    <h2>Server Logs</h2>
    <pre id="terminal-log">Loading logs...</pre>
</div>

<style>
#terminal-log {
    background: #000;
    color: #0f0;
    font-family: "Courier New", Courier, monospace;
    font-size: 14px;
    line-height: 1.4em;
    padding: 10px;
    border-radius: 8px;
    max-height: 400px;
    overflow-y: scroll;
    white-space: pre-wrap;
}
</style>

<script>
// Auto-refresh logs every 2 seconds
function refreshTerminal() {
    fetch('logs.php?live=1')
        .then(res => res.text())
        .then(txt => {
            const logEl = document.getElementById('terminal-log');
            logEl.textContent = txt;
            logEl.scrollTop = logEl.scrollHeight; // auto-scroll
        });
}
setInterval(refreshTerminal, 2000);
refreshTerminal();
</script>


</div>
</main>

<div class="logout">
    <a href="logout.php">Logout</a>
</div>

</body>
</html>
