<?php

if ($member_db[UDB_ACL] != ACL_LEVEL_ADMIN)
    msg("error", lang("Access Denied"), lang("You don't have permission for this section"));

if ($action == 'update' )
{
    $r = fopen('http://cutephp.com/latest/.export.log', 'r');
    ob_start(); $stat = fpassthru($r); $statext = ob_get_clean();

    $w = fopen(SERVDIR.'/cdata/cache/.export.log', 'w');
    fwrite($w, $statext);
    fclose($w);

    if ($stat && preg_match('~Exported revision (\d+)~i', $statext, $rev))
    {
        include (SERVDIR.'/cdata/log/revision.php');
        $uselast = ($my_current_rev == $rev[1])? 1 : 0;

        echoheader('info', lang("Update Status"));
        echo proc_tpl('update',
            array('rev' => $rev[1]),
            array('ALREADYLAST' => $uselast,
                  'UPIFRAME' => (($_GET['do'] == 'do_update')? 1:0))
        );

        echofooter();
    }
    else msg('error', LANG_ERROR_TITLE, lang('No update: Error while receiving update file'));
}
elseif ($action == 'do_update' )
{
    $r = fopen(SERVDIR.'/cdata/cache/.export.log', 'r');
    ob_start(); $stat = fpassthru($r); $statext = ob_get_clean();
    $bundle = explode("\n", $statext);
    $proc   = intval( $_GET['proc'] );

    if ($proc >= count($bundle))
    {
        // Auto update to latest version
        include (SERVDIR.'/migrate/latest.revision.php');
        echo lang('OK! Success updated');
    }
    else
    {
        $mc  = time();
        $log = false;

        if ($proc == 0)
        {
            // Truncate the log
            fclose ( fopen(SERVDIR.'/cdata/log/update.log', 'w') );
        }

        while (time() - $mc < 15 && $proc < count($bundle))
        {
            list (,$name) = explode("A ", $bundle[$proc]);
            $name = trim($name);
            $proc++;

            if ($name == '.' || $name == false) continue;

            $r = fopen("http://cutephp.com/latest/?cp=".urlencode($name), 'r');
            ob_start(); fpassthru($r); $data = ob_get_clean();

            // Make paths
            $DEST_DIR = SERVDIR;
            $dirs = explode('/', $name);

            if (preg_match('~exported revision (\d+)~i', $bundle[$proc], $rev))
            {
                $w = fopen(SERVDIR.'/cdata/log/revision.php', 'w');
                fwrite($w, '<'.'? $my_current_rev = '.$rev[1].'; ?>');
                fclose($w);
            }
            elseif( preg_match('~\.[a-z0-9]+?$~i', $dirs[count($dirs)-1] ) )
            {
                // Make dirs...
                $w = fopen($DEST_DIR.'/'.$name, 'w');
                fwrite($w, $data);
                fclose($w);

                $log .= date('r')." Write file ".$DEST_DIR.'/'.$name."\n";
            }
            else
            {
                $depth = '';
                foreach ($dirs as $vd)
                {
                    $depth .= '/'.$vd;
                    if (!is_dir($DEST_DIR.$depth) && !file_exists($DEST_DIR.$depth))
                    {
                        mkdir($DEST_DIR . $depth);
                        $log .= date('r')." Make dir ".$DEST_DIR.$depth."\n";
                    }
                }
            }
        }

        $a = fopen(SERVDIR.'/cdata/log/update.log', 'a');
        fwrite($a, $log);
        fclose($a);

        $percent = intval( $proc / count($bundle) * 100 );

        // Redirect for continue loading
        echo ( '<html>
                <head><meta http-equiv="refresh" content="1; URL='.$config_http_script_dir.'?mod=update&action=do_update&proc='.$proc.'"></head>
                <body><script type="text/javascript">parent.document.getElementById("progress").style.width = "'.$percent.'%";</script></body>
                </html><head></head>');
        die();
    }
}