<?php

require_once 'core/init.php';
include ('core/loadenv.php');

// Save path for htaccess
$w = fopen(SERVDIR.'/cdata/htpath.php', 'w'); fwrite($w, '<'.'?php $ht_path = "'.dirname(__FILE__).'"; ?>'); fclose($w);

$imod = isset($imod) && $imod ? $imod : false;
hook('expand_code_shows');

?>