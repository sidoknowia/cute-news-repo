<html xmlns="http://www.w3.org/1999/html">
<head>
    <title>Migrate from 1.4.7 to 1.5.0 version</title>
    <style>body { margin: 16px; padding: 0; font: 14px/1.5em Arial, Serif; } a { color: #000080; }</style>
</head>
<body>
<h1>Migration script</h1>
<br/>

<ul>
<?php

    // Migration script
    include ('../core/init.php');

    function iconv_self($text, $cp) // to UTF8
    {
        $cp = strtolower($cp);
        if ( function_exists('iconv') ) $text = iconv($cp, 'utf-8', $text);
        return $text;
    }

    list($do, $cp, $acts) = filter_request('do,cp,acts', true);

    if (empty($cp))
        if ($config_default_charset) $cp = $config_default_charset; else $cp = 'utf-8';

    if ($do == false)
    {
        ?>
            <form action="<?php echo $PHP_SELF; ?>" method="POST">

                <input type="hidden" name="do" value="start" />

                <h3>Current Frontend Charset &rarr; <?php echo $cp; ?></h3>
                <?php if ($config_default_charst != 'utf-8') { ?>
                    <p>Wrong charset displayed? Enter your charset here <input type="text" id="cpid" name="cp" value="" /></p>
                    <p><b>iconv:</b> <?php if (!function_exists('iconv')) echo '<b style="color: #800000;">not</b> '; ?> supported</p>

                    <?php
                        if ( !function_exists('iconv') )
                        {
                            echo '<h3 style="color: #800000;">Warning: upgrade PHP to 4.0.5 to perform charset convert procedure</h3>';
                        }
                    ?>
                <?php } ?>
                <?php if ( $cfg['migrate'] ) { ?><p style="color: #800000;"><b>Notice:</b> You have run the migration process</p><?php } ?>
                <p>Perform next actions:</p>
                <p>
                    <div><input type="checkbox" checked="checked" name="acts[users]" value="Y" /> Copy user database</div>
                    <div><input type="checkbox" checked="checked" name="acts[active]" value="Y" /> Convert active dbs</div>
                    <div><input type="checkbox" checked="checked" name="acts[archives]" value="Y" /> Convert archives</div>
                    <div><input type="checkbox" checked="checked" name="acts[backups]" value="Y" /> Convert backups</div>
                </p>
                <p><input type="submit" value="Start migration script" /></p>
            </form>

        <?php
    }
    else
    {
        echo '<li>Use <b>'.$cp.'</b> codepage, convert to UTF-8</li>';

        echo '<li>Create files...';
        if (!file_exists(DB_USERS))     fclose( fopen(DB_USERS, 'w') );
        if (!file_exists(DB_BAN))       fclose( fopen(DB_BAN, 'w') );
        echo ' done</li>';

        // Copy reg.php
        if (file_exists(SERVDIR.'/data/reg.php'))
        {
            echo '<li>Copy registration data...';
            $reg_key = implode('', file(SERVDIR.'/data/reg.php'));
            $fx = fopen(SERVDIR.'/cdata/reg.php', 'w');
            fwrite($fx, $reg_key);
            fclose($fx);
            echo ' done</li>';
        }

        echo '<li>Make log dir... ';
        mkdir ( SERVDIR.'/cdata/log' );
        chmod ( SERVDIR.'/cdata/log', 0775 );
        echo ' done</li>';

        echo '<li>Make cache dir... ';
        mkdir ( SERVDIR.CACHE );
        chmod ( SERVDIR.CACHE, 0775 );
        echo ' done</li>';

        // all users
        if ($acts['users'] == 'Y')
        {
            echo '<li>Copy users in fast file database... ';
            $users = file(SERVDIR.'/data/users.db.php');
            unset($users[0]);

            foreach ($users as $v)
            {
                $data = explode('|', iconv_self($v, $cp));
                $pack = array
                (
                    UDB_ID        => $data[UDB_ID],
                    UDB_ACL       => $data[UDB_ACL],
                    UDB_NAME      => $data[UDB_NAME],
                    UDB_PASS      => $data[UDB_PASS],
                    UDB_NICK      => $data[UDB_NICK],
                    UDB_EMAIL     => $data[UDB_EMAIL],
                    UDB_COUNT     => $data[UDB_COUNT],
                    UDB_CBYEMAIL  => $data[UDB_CBYEMAIL],
                    UDB_AVATAR    => $data[UDB_AVATAR],
                    UDB_LAST      => $data[UDB_LAST],
                );

                // username is key
                add_key( $pack[UDB_NAME], $pack, DB_USERS );
            }
            echo ' done</li>';
        }

        // ipban
        echo '<li>Copy ip bans in fast file database... ';
        $ipban = file(SERVDIR.'/data/ipban.db.php');

        foreach ($ipban as $v)
        {
            $data = explode('|', iconv_self(trim($v), $cp));
            add_ip_to_ban($data[0]);
        }
        echo ' done</li>';

        // -----------------------------------------------------------------------------------------------
        echo '<li>Make folders and files... ';
        $copy = array('Default', 'Headlines', 'rss');
        $dirs = array('archives', 'backup', 'cache', 'log', 'upimages', 'plugins', 'template');
        $files = array
        (
            'auto_archive.db.php', 'cat.num.php', 'category.db.php', 'comments.txt', 'config.php',
            'db.ban.php', 'db.hooks.php', 'db.users.php', 'flood.db.php',
            'hooks.php', 'news.txt', 'postponed_news.txt', 'replaces.php',  'rss_config.php',
            'unapproved_news.txt',
        );

        // Make dirs
        foreach ($dirs as $v)
        {
            $dir = SERVDIR.'/cdata/'.$v;
            if (!file_exists($dir))
            {
                mkdir($dir, 0775);
                $x = fopen($dir.'/index.html', 'w');
                fwrite($x, 'Access denied');
                fclose($x);
                chmod ( $dir.'/index.html', 0664 );
            }
        }

        // Make files
        foreach ($files as $v)
        {
            $file = SERVDIR.'/cdata/'.$v;
            if (!file_exists($file))
            {
                fclose( fopen($file, 'w') );
                chmod ($file, 0664);
            }
        }

        // Copy files
        foreach ($copy as $v)
        {
            $file = SERVDIR.'/cdata/'.$v.'.tpl';
            if (!file_exists($file))
            {
                $rw = read_tpl('install/copy/'.$v);
                $cx = fopen($file, 'w');
                fwrite ($cx, $rw);
                fclose($cx);
                chmod ($file, 0664);
            }
        }
        echo 'done</li>';

        // Translate codepage in all files
        if ($cp != 'utf-8')
        {
            // Copy Active News and data
            if ($acts['news'] == 'Y')
            {
                echo '<li>Convert active news... ';
                $translate = array('category.db.php', 'comments.txt', 'news.txt', 'postponed_news.txt', 'unapproved_news.txt');
                $backupid = time();

                foreach ($translate as $v)
                {
                    // from old database
                    $box = file(SERVDIR.'/data/'.$v);

                    // to new database
                    $co  = fopen(SERVDIR.'/cdata/'.$v, 'w');
                    foreach ($box as $vs) fwrite($co, iconv_self($vs, $cp));
                    fclose($co);
                }
                echo ' done</li>';
            }

            // Converting archives
            if ($acts['archives'] == 'Y')
            {
                echo '<li>Convert archives... ';
                $archives = read_dir(SERVDIR.'/data/archives');
                foreach ($archives as $vc)
                {
                    $box = file(SERVDIR.$vc);
                    $co  = fopen( SERVDIR . str_replace('/data/archives/', '/cdata/archives/', $vc), 'w');
                    foreach ($box as $vs) fwrite($co, iconv_self($vs, $cp));
                    fclose($co);
                }
                echo ' done</li>';
            }

            // Convering backups
            if ($acts['backups'] == 'Y')
            {
                echo '<li>Convert backups... ';
                $backups = read_dir(SERVDIR.'/data/backup');
                foreach ($backups as $vc)
                {
                    $box = file(SERVDIR.$vc);

                    list (,,$b,$c,$d) = explode ('/', dirname( $vc ));
                    if (is_dir(SERVDIR.'/cdata/'.$b) == false) mkdir(SERVDIR.'/cdata/'.$b);
                    if (is_dir(SERVDIR.'/cdata/'.$b.'/'.$c) == false) mkdir(SERVDIR.'/cdata/'.$b.'/'.$c);
                    if (is_dir(SERVDIR.'/cdata/'.$b.'/'.$c.'/'.$d) == false) mkdir(SERVDIR.'/cdata/'.$b.'/'.$c.'/'.$d);

                    $bse = str_replace('/data/', '/cdata/', $vc);
                    $co  = fopen( SERVDIR . $bse, 'w');
                    foreach ($box as $vs) fwrite($co, iconv_self($vs, $cp));
                    fclose($co);
                }
                echo ' done</li>';
            }
        }

        $cfg['migrate'] = 1;
        fv_serialize('conf', $cfg);


?>
</ul>
<h2>Migration successful</h2>
<?php } ?>

<br/>
<p><a href="<?php echo $config_http_script_dir; ?>">Go to Admin Panel</a></p>

</body></html>