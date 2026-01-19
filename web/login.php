<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['user'] === ADMIN_USER && password_verify($_POST['pass'], ADMIN_PASS_HASH)) {
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
        header('Location: index.php');
        exit;
    }
    $error = "Invalid login";
}
?>
<link rel="stylesheet" href="assets/style.css">
<form method="post">
<h2>Login</h2>
<input name="user" placeholder="Username" required>
<input name="pass" type="password" placeholder="Password" required>
<button>Login</button>
<div><?= $error ?? '' ?></div>
</form>
