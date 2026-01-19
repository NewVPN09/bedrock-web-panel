<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) { header('Location: login.php'); exit; }

/* ===== CONFIG ===== */
$screen_name = "bedrock";
$server_dir  = "/home/minecraft/Server";
$backup_dir  = "/home/minecraft/BackupWorlds";
$log_file    = "/home/minecraft/minecraft.log";
$manage_script = "/home/minecraft/manage_screen.sh";

$gamerules = [
    "announceAdvancements","commandBlockOutput","disableElytraMovementCheck",
    "doDaylightCycle","doEntityDrops","doFireTick","doLimitedCrafting",
    "doMobLoot","doMobSpawning","doTileDrops","doWeatherCycle",
    "drowningDamage","fallDamage","fireDamage","keepInventory",
    "logAdminCommands","maxCommandChainLength","mobGriefing",
    "naturalRegeneration","pvp","sendCommandFeedback",
    "showCoordinates","showDeathMessages","spawnRadius",
    "tntExplodes","disableInsomnia"
];

$message = "";
$console_output = "";

/* ===== HELPERS ===== */
function run_script($script){
    $script = escapeshellcmd($script);
    exec("$script 2>&1", $output);
    return implode("\n", $output);
}

function screen_running(){
    global $screen_name;
    exec("screen -ls | grep '\\.$screen_name'", $out);
    return !empty($out);
}

function screen_cmd($cmd){
    global $screen_name;
    $cmd_escaped = str_replace(['"', '`', '$', ';', '|', '&', '>', '<', '\\'], ['&quot;','','','','','','','',''], $cmd);
    exec("screen -S $screen_name -p 0 -X stuff \"say $cmd_escaped\n\"");
}

function log_action($msg){
    global $log_file;
    file_put_contents($log_file, "[".date("Y-m-d H:i:s")."] $msg\n", FILE_APPEND);
}

function get_uptime() {
    if (file_exists("/proc/uptime")) {
        $seconds = (int)explode(' ', file_get_contents("/proc/uptime"))[0];
        $days = floor($seconds/86400);
        $hours = floor(($seconds%86400)/3600);
        $minutes = floor(($seconds%3600)/60);
        $secs = $seconds%60;
        return "{$days}d {$hours}h {$minutes}m {$secs}s";
    }
    return "N/A";
}

function get_cpu_load() { $load = sys_getloadavg(); return "1m: {$load[0]}, 5m: {$load[1]}, 15m: {$load[2]}"; }
function get_memory_usage() { $mem = shell_exec("free -m"); preg_match_all('/\d+/', $mem, $m); return "{$m[0][1]}MB / {$m[0][0]}MB used ({$m[0][2]}MB free)"; }
function get_disk_usage($path="/") { $t=disk_total_space($path);$f=disk_free_space($path);return format_bytes($t-$f)." / ".format_bytes($t)." (".round(($t-$f)/$t*100,1)."%)"; }
function format_bytes($bytes){$u=['B','KB','MB','GB','TB'];$i=0;while($bytes>=1024&&$i<count($u)-1){$bytes/=1024;$i++;}return round($bytes,2).' '.$u[$i];}
function get_ping($host="127.0.0.1") { preg_match('/time=(\d+\.\d+) ms/', shell_exec("ping -c 1 $host"), $m); return $m[1] ?? "N/A"; }

/* ===== HANDLE POST ACTIONS ===== */
if ($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST['action'])) {
    if(!csrf_check()) { $message="? CSRF validation failed"; }
    switch($_POST['action']){
        case "start": $message=run_script("$manage_script start"); log_action($message); break;
        case "stop": $message=run_script("$manage_script stop"); log_action($message); break;
        case "restart": $message=run_script("$manage_script restart"); log_action($message); break;
        case "clean": $message=run_script("/home/minecraft/bedrock-clean.sh"); log_action($message); break;
        case "backup": 
            $message=run_script("/home/minecraft/autobackup.sh");
            $message="✅ Backup completed!";
            break;
        case "clear_console":
            file_exists($log_file) ? file_put_contents($log_file,"") : $message="? Log file missing";
            $console_output="";
            $message="🧹 Console cleared";
            break;
        case "command":
            if(screen_running() && !empty($_POST['command'])){
                $cmd_safe=str_replace(['"', '`', '$', ';', '|', '&', '>', '<', '\\'],['&quot;','','','','','','','',''],$_POST['command']);
                screen_cmd($cmd_safe);
                $message="✅ Command sent: ".htmlspecialchars($cmd_safe);
            } else $message="? Server offline or command empty";
            break;
        case "gamerule":
            if(screen_running()){ screen_cmd("gamerule {$_POST['gamerule']} {$_POST['value']}"); $message="✅ Gamerule updated"; }
            else $message="? Server offline";
            break;
        case "console": $console_output=file_exists($log_file)?shell_exec("tail -n 100 $log_file"):"Log not found"; break;
    }
}

/* ===== AJAX DOWNLOAD ===== */
if(isset($_GET['download_backup']) && $_GET['download_backup']==='latest'){
    $files = glob("$backup_dir/bedrock_world_*.tar.gz");
    if(!$files){http_response_code(404); exit("No backup found");}
    rsort($files); $file=$files[0];
    header("Content-Type: application/gzip");
    header("Content-Length: ".filesize($file));
    header("Content-Disposition: attachment; filename=\"".basename($file)."\"");
    readfile($file); exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width">
<title>Bedrock Panel</title>
<link rel="stylesheet" href="assets/style.css">
<style>
body{margin:10px;font-family:Segoe UI,Arial;background:linear-gradient(135deg,#1f1c2c,#928dab);color:#fff}
header{display:flex;justify-content:space-between;align-items:center;padding:16px;background:#5d3a9b;font-weight:bold;font-size:20px}
main{max-width:900px;margin:20px auto;padding:20px;background:rgba(0,0,0,.7);border-radius:14px}
.section{margin-bottom:20px;border-bottom:1px solid #555;padding-bottom:20px}
h2{color:#00bcd4}
input,select,button{width:100%;margin:5px 0;padding:10px;border-radius:8px;border:none;font-size:15px;box-sizing:border-box}
button{background:#5d3a9b;color:#fff;cursor:pointer}
button:hover{background:#7c53c0}
pre{background:#111;padding:10px;border-radius:8px;max-height:300px;overflow:auto}
.download-btn{background:#00e676;color:#000;padding:8px 12px;border-radius:8px;text-decoration:none}
.download-btn:hover{background:#1aff88;transform:scale(1.05)}
.icon-frame{width:56px;height:56px;border:2px solid #00bcd4;border-radius:14px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:0.2s}
.icon-frame:hover{background:rgba(255,255,255,0.15);transform:scale(1.1)}
</style>
<script>
function refreshConsole(){fetch('?live=1').then(r=>r.text()).then(t=>document.getElementById('console').textContent=t);}
setInterval(refreshConsole,3000);
function downloadBackup(){
    const xhr=new XMLHttpRequest();
    xhr.open('GET','?download_backup=latest',true);
    xhr.responseType='blob';
    xhr.onload=function(){if(xhr.status==200){const link=document.createElement('a');link.href=window.URL.createObjectURL(xhr.response);link.download='backup.tar.gz';link.click();}};
    xhr.send();
}
</script>
</head>
<body>
<header>
<span>Bedrock Panel</span>
<a href="?logout=1" title="Logout" style="color:#fff;">Logout</a>
</header>
<main>

<?php if($message) echo "<div class='message'>$message</div>"; ?>

<div class="section">
<h2>Server Status</h2>
<p>Status: <?= screen_running() ? '<span style="color:#0f0">Online</span>' : '<span style="color:#f00">Offline</span>' ?></p>
</div>

<div class="section">
<h2>Server Controls</h2>
<div style="display:flex;gap:10px;">
<div class="icon-frame" onclick="document.getElementById('start').click()"><img src="https://img.icons8.com/ios-filled/36/ffffff/play.png"></div>
<div class="icon-frame" onclick="document.getElementById('stop').click()"><img src="https://img.icons8.com/ios-filled/36/ffffff/stop.png"></div>
<div class="icon-frame" onclick="document.getElementById('restart').click()"><img src="https://img.icons8.com/ios-filled/36/ffffff/refresh.png"></div>
</div>
<form method="post" style="display:none;">
<button id="start" name="action" value="start"></button>
<button id="stop" name="action" value="stop"></button>
<button id="restart" name="action" value="restart"></button>
</form>
</div>

<div class="section">
<h2>Send Server Message</h2>
<form method="post"><input name="command" placeholder="say Hello players"><button name="action" value="command">Send</button></form>
</div>

<div class="section">
<h2>Gamerule Editor</h2>
<form method="post"><select name="gamerule"><?php foreach($gamerules as $g) echo "<option>$g</option>"; ?></select>
<input name="value" placeholder="true / false / number"><button name="action" value="gamerule">Apply</button></form>
</div>

<div class="section">
<h2>Live Console</h2>
<form method="post" style="display:flex;gap:5px;">
<button name="action" value="console">Refresh</button>
<button name="action" value="clear_console">Clear</button>
</form>
<pre id="console"><?= htmlspecialchars($console_output) ?></pre>
</div>

<div class="section">
<h2>Download Backup</h2>
<a href="#" class="download-btn" onclick="downloadBackup();return false;">Download Latest Backup</a>
</div>

<div class="section">
<h2>System Status</h2>
<ul>
<li>Uptime: <?= get_uptime() ?></li>
<li>CPU Load: <?= get_cpu_load() ?></li>
<li>Memory: <?= get_memory_usage() ?></li>
<li>Disk: <?= get_disk_usage() ?></li>
<li>Ping: <?= get_ping() ?></li>
</ul>
</div>

<div class="section">
<h2>Server Management</h2>
<form method="post">
<button name="action" value="install">Install Server</button>
<button name="action" value="uninstall">Uninstall Server</button>
<button name="action" value="reinstall">Reinstall Server</button>
</form>
</div>

</main>
</body>
</html>
