<?php
session_start();
require 'csrf.php';
if (!isset($_SESSION['auth'])) exit;
csrf_check();

$cmd = $_POST['cmd'] ?? '';
if (!$cmd) exit;

$rcon_host = '127.0.0.1';
$rcon_port = 19132;        // UDP port from server.properties
$rcon_pass = 'minecraft123'; // Your RCON password

class RCON {
    private $socket;
    public function __construct($host, $port, $password){
        $this->socket = stream_socket_client("udp://$host:$port", $errno, $errstr, 1);
        if (!$this->socket) exit("RCON connection failed: $errstr ($errno)");
        $this->send($password); // Authenticate
    }
    public function send($cmd){
        fwrite($this->socket, $cmd);
        return stream_get_contents($this->socket);
    }
    public function __destruct(){
        fclose($this->socket);
    }
}

try {
    $rcon = new RCON($rcon_host, $rcon_port, $rcon_pass);
    echo "<pre>" . htmlspecialchars($rcon->send($cmd)) . "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
<a href="index.php">Back</a>
