<?php

include '../core/init.php';
include '../core/loadenv.php';

$fail = array();
$dirs = array('archives', 'backup', 'cache', 'log', 'plugins', 'template');

/*
   Added
   + replaces.php
   + idnews.db.php
*/
$files = array
(
    'auto_archive.db.php',
    'cat.num.php',
    'category.db.php',
    'comments.txt',
    'db.ban.php',
    'users.db.php',
    'flood.db.php',
    'news.txt',
    'postponed_news.txt',
    'rss_config.php',
    'unapproved_news.txt',
    'idnews.db.php',
    'replaces.php',
    'ipban.db.php',
    'confirmations.php'
);

if (!is_dir(SERVDIR.'/data'))
    msg('error', 'Error', lang("Can't migrate: no `data` folder"));

// Make Upload folder -----------
if (!is_dir(SERVDIR.'/uploads'))
{
    if (!mkdir(SERVDIR.'/uploads', 0777)) $fail[] = array('mkdir', SERVDIR.'/uploads');
    $x = fopen(SERVDIR.'/uploads/index.html', 'w');
    fwrite($x, 'Access denied');
    fclose($x);
}

// Make php-files
make_php('confirmations.php');

// COPY ALL FROM DATA FOLDER
$data_dir = array();
if  (is_dir(SERVDIR.'/data'))
     $data_dir = read_dir(SERVDIR.'/data');
else $data_dir = array();

foreach ($data_dir as $fx)
{
    // // Emoticons migration
    if (preg_match('~^/data/emoticons/~i', $fx))
    {
        $dest = SERVDIR.str_replace('/data/emoticons/', '/skins/emoticons/', $fx);
        if (!file_exists($dest) && !copy(SERVDIR.$fx, $dest)) $fail[] = array('copy', SERVDIR.$fx, $dest);
        continue;
    }

    if (preg_match('~\.htaccess~i', $fx)) continue;

    // Migration uploads
    if (preg_match('~^/data/upimages/~i', $fx))
    {
        $dest = SERVDIR.str_replace('/data/upimages/', '/uploads/', $fx);
        if (!file_exists($dest) && !copy(SERVDIR.$fx, $dest)) $fail[] = array('copy', SERVDIR.$fx, $dest);
        continue;
    }

    $path = SERVDIR.'/cdata';
    foreach (explode('/',  preg_replace('~^/data/~i', '', $fx)) as $dc)
    {
        $path .= '/'.$dc;
        if (strpos($dc, '.') === false)
        {
            if (!is_dir($path) && !mkdir($path, 0777)) $fail[] = array('mkdir', $path); else chmod($path, 0777);
        }
        else
        {
            if (!copy(SERVDIR.$fx, $path)) $fail[] = array('copy', SERVDIR.$fx, $path);
            if (!chmod($path, 0666)) $fail[] = array('chmod', $path);
        }
    }
}

// Place .htaccess to cdata section
$w = fopen(SERVDIR.'/cdata/.htaccess', 'w');
fwrite($w, "Deny From All");
chmod (SERVDIR.'/cdata/.htaccess', 0644);
fclose($w);

// Make dirs
foreach ($dirs as $v)
{
    $dir = SERVDIR.'/cdata/'.$v;
    if (!is_dir($dir))
    {
        mkdir($dir, 0777);
        chmod($dir, 0777);
    }
}

// Make files
foreach ($files as $v)
{
    $file = SERVDIR.'/cdata/'.$v;
    if (!file_exists($file))
    {
        fclose( fopen($file, 'w') );
        if (!chmod ($file, 0666)) $fail[] = array('chmod', $file);
    }
}

$found_problems = proc_tpl('install/problemlist');

make_crypt_salt();
msg('info', lang('Migration success'), lang("Congrats! You migrated to Cutenews ".VERSION). " | <a href='../index.php'>Login</a> ".$found_problems);