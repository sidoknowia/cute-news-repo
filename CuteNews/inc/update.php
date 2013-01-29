<?php

if (!defined('INIT_INSTANCE')) die('Access restricted');

// Only admin there
if ($member_db[UDB_ACL] != ACL_LEVEL_ADMIN)
    msg("error", lang("Access Denied"), lang("You don't have permission for this section"));

if (ini_get('allow_url_fopen') == 0)
    msg("error", lang("Access Denied"), lang("Please check 'allow_url_fopen' option in php.ini file."));

// --------------------- STAT ---------------------
$update_file = SERVDIR.'/cdata/log/revision.file.log';

if ($action == 'update' )
{
    $try_get = 0;

    // Every 3 hour reloading
    if (file_exists($update_file) && ((time() - filemtime($update_file)) > 3*3600)) $try_get = 1;
    if (!file_exists($update_file)) $try_get = 1;

    // local cache update file too old or not exists - try to get new file
    if ($try_get)
    {
        $statext = cwget('http://cutephp.com/latest/.revision.log');
        $stat    = strlen($statext);

        if ($stat)
        {
            $w = fopen($update_file, 'w');
            fwrite($w, $statext);
            fclose($w);
            chmod($update_file, 0664);
        }
    }
    else
    {
        $r = fopen($update_file, 'r');
        ob_start();
        fpassthru($r);
        $statext = ob_get_clean();
        $stat    = strlen($statext);
    }

    if ($stat)
    {
        $rev = explode("\n", $statext);
        list(,$revid) = explode('=', array_shift($rev));

        // check files
        if (!function_exists('md5_file'))
            msg('error', lang('Update Info'), lang('Function `md5_file` not found: update php version'));

        $files_to_update = array();
        foreach ($rev as $files)
        {
            list($hash_rec, $file) = explode('|', $files, 2);
            if (empty($file)) continue;

            $dest = SERVDIR.'/'.$file;
            if (is_dir($dest))
            {
                $hash_rec = $hash_my = true;
            }
            else
            {
                if (file_exists($dest))
                     $hash_my = md5_file($dest);
                else $hash_my = false;
            }

            if ($hash_my != $hash_rec)
            {
                $stat = '<span style="color:red;">'.lang('not writable or not exists!').' <a target="_blank" href="https://raw.github.com/CuteNews/cute-news-repo/master/CuteNews/'.$file.'">get file</a></span>';
                if ( is_writable(SERVDIR.'/'.$file) ) $stat = '<span style="color:green;">'.lang('writable').'</span>';
                if ( !file_exists(SERVDIR.'/'.$file) ) $stat = '<span style="color:red;">'.lang('not exists').'</span>';
                $files_to_update[] = array($file, $stat);
            }
        }

        if (count($files_to_update) == 0)
            msg('info', lang('Update status'), lang('No update: your revision is the latest one'));

        echoheader('info', lang("Files to update"), make_breadcrumbs('main/options=options/Update Status'));
        echo proc_tpl('update/status');
        echofooter();
    }
    else msg('error', lang('Error!'), lang('No update: Error while receiving update file'));

}
elseif ($action == 'do_update' )
{
    $upfail = 0;
    $start = time();
    foreach ($_POST['files'] as $i => $file)
    {
        $dest = SERVDIR.'/'.$file;
        if (is_writable(SERVDIR.'/'.$file) || !file_exists(SERVDIR.'/'.$file))
        {
            // Make Folder
            $path = explode('/', $file);
            $upth = SERVDIR;
            foreach ($path as $f => $d)
            {
                $upth .= '/'.$d;
                if (strpos($d, '.') !== false) continue;
                if (!is_dir($upth)) $upfail += mkdir($upth);
            }

            // Write File
            $dn = cwget('https://raw.github.com/CuteNews/cute-news-repo/master/CuteNews/'.$file);
            if ($dn)
            {
                $w = fopen($dest, 'w');
                fwrite($w, $dn);
                fclose($w);
            }
            else $upfail++;
        }
        else $upfail++;
    }
    $end = time();

    if (file_exists($update_file))
        unlink($update_file);

    if ($upfail)
        msg('error', lang('Update Status'), lang('Update broken!').' '.($end-$start).' sec.');
    else
        msg('info', lang('Update Status'), lang('Update success').' '.($end-$start).' sec.');

}