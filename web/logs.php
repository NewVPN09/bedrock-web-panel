<?php
if (!isset($_SESSION['auth'])) exit;
exec("tail -n 200 ".escapeshellarg(LOG_FILE), $o);
echo "<pre>".htmlspecialchars(implode("\n",$o))."</pre>";
