<?php
echo "<pre>";
echo htmlspecialchars(shell_exec("uptime"));
echo "\n";
echo htmlspecialchars(shell_exec("free -h"));
echo "</pre>";
