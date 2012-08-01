<?php

    // check headers information
    if (isset($_REQUEST['trace'])) { echo $_SERVER['HTTP_ACCEPT_CHARSET']; exit(); }

    require_once ('core/init.php');

    // plugin tells us: he is fork, stop
    if ( hook('fork_router', false) ) return;

    include ('show_news.php');
    hook('router_file_after');
