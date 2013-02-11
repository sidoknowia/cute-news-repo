<?php

    global $DB_CONNECTION, $dba_username;

    $phpself = cn_path('acp.php').'?q=db_admin';

    // Connect to selected engine
    db_cn_connect(REQ('engine', 'COOKIE'));

?><html>
<head>
    <title>DBA Cutenews</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="<?php echo $CN_SKIN_WEB; ?>/css/dba.css">
    <link rel="shortcut icon" type="image/ico" href="<?php echo cn_path(); ?>/skins/images/favicon.ico"/>
    <meta name="robots" content="noindex,nofollow">
</head>

<body>

    <p>Hello, <b><?php echo $dba_username; ?></b></p>

    <ul id="top-float-panel">
        <li><a href="<?php echo cn_path('acp.php'); ?>">ACP</a></li>
        <li><strong>Engine</strong>:
            <a <?php echo trigger_cookie_key('engine=plain', 'class="selected"'); ?> href="<?php echo $phpself; ?>&do=setcookie&key=engine&data=plain">plain</a> |
            <a <?php echo trigger_cookie_key('engine=textsql', 'class="selected"'); ?>href="<?php echo $phpself; ?>&do=setcookie&key=engine&data=textsql">textsql</a> |
            <a <?php echo trigger_cookie_key('engine=mysql', 'class="selected"'); ?> href="<?php echo $phpself; ?>&do=setcookie&key=engine&data=mysql">mysql</a>
        </li>
        <li <?php echo trigger_cookie_key('dba_do=view', "class='selected'"); ?>><a href="<?php echo $phpself; ?>&do=setcookie&key=dba_do&data=view">View tables</a></li>
        <li <?php echo trigger_cookie_key('dba_do=select', "class='selected'"); ?>><a href="<?php echo $phpself; ?>&do=setcookie&key=dba_do&data=select">Table contents</a></li>
    </ul>

    <div class="clear"></div>
    <?php

    // Show tables
    if (trigger_cookie_key('dba_do=view', true))
    {
        $q = db_query('SHOW TABLES()');
        $fields = array('table', 'secured / path', 'view');

        foreach ($q as $key => $item)
        {
            $q[$key]['href'] = '<a href="'.$phpself.'&do=select&table='.$item['table'].'">'.$item['table'].'</a>';
        }
    }

    $MyAction = '';

    // User query
    if (REQ('do') == 'do_query')
    {
        // Make Cond / Variables
        $RQ     = explode("\n", REQ('query'));

        $Query  = array_shift($RQ);
        $Args   = array_map('trim', $RQ);

        $q = db_query($Query, $Args);

        // Disallow Re-Select all
        if (preg_match('/^SELECT\s/i', $Query))
            $MyAction = 'SELECT';

        // Calculation requested fields (plaindb)
        if ($DB_CONNECTION == 'plaintxt')
        {
            $max = 0;
            foreach($q as $vx) if ($max < count($vx)) $max = count($vx);

            $fields = array();
            for($i = 0; $i < $max; $i++) $fields[] = $i;
        }
        // Get processed
        elseif ($DB_CONNECTION == 'textsql')
        {
            $fields = db_query("SELECT FIELDS()");
        }

    }

    // Show table contents
    if (trigger_get_key('do', 'select') && !in_array($MyAction, array('SELECT')))
    {
        $vt = REQ('table', 'GET');
        $q = db_query("SELECT FROM $vt");

        // Default fields
        if ($DB_CONNECTION == 'plaintxt')
        {
            if ($vt == 'users') $fields = explode(',', 'ID,ACL,Name,Password,Nick,E-mail,Count News,Hide email,Avatar,Last login');
            if ($vt == 'news')  $fields = explode(',', 'ID,User,Title,Short,Full,Avatar,Category,Rate,More fields,Options,Other');
        }
        elseif ($DB_CONNECTION == 'textsql')
        {
            $fields = db_query("SELECT FIELDS()");
        }
    }

    // Common table out
    if (!empty($q) && is_array($q))
    {
        if (!empty($vt))
            echo "<div>Table: ($vt)</div><br/>";

        echo '<table class="dba_table">';
        if (!empty($fields)) dba_writeline( $fields, 'th' );
        foreach ($q as $item) dba_writeline($item);
        echo '</table>';
    }

    include ($CN_SKIN . '/html/block.dba_add.php');

?>
</body>
</html>