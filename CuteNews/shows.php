<?php

require_once 'core/init.php';

$smod = isset($smod) && $smod ? $smod : false;
$allowed_modules = hook('expand_allowed_modules', array
(
    'userlist'
));

if (in_array($smod, $allowed_modules))
    include ("core/features/$smod.php");

?>