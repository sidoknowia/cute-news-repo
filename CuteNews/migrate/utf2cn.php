<?php
/**
 Migrate script from UTF8 http://www.korn19.ch/coding/utf8-cutenews/ to http://cutephp.com
 * (data/ to cdata/)
 */

if (!isset($_POST['oldpath']))
{
?>
    <form method="POST">
        Enter path to old project (ex: /var/www/cutenews):<br>
        <input name='oldpath' type='text'>
        <input type='submit' value='Migrate'>
    </form>
<?
}
else
{
    include '../core/init.php';
    include '../core/loadenv.php';

    define('SERVDIR', dirname(dirname(__FILE__).'.html'));
    define('OLDDIR', trim(REQ('oldpath')));

    function migrate_news($path)
    {
        // change fields in news include archive, backup, postponed, unapproved
        $news_old = file(OLDDIR.$path);
        $new_path = preg_replace('#^/data/#i', '', $path);
        $nf = fopen(SERVDIR.'/cdata/'.$new_path, 'w');

        foreach($news_old as $news_line)
        {
            $news_part = explode('|', $news_line);
            $news_id = $news_part[0];
            $news_user = $news_part[1];
            $news_title = $news_part[2];
            $news_short = $news_part[3];
            $news_full = $news_part[4];
            $news_avatar = $news_part[5];
            $news_category = $news_part[6];
            $news_rate = '';
            $news_mf = '';
            $news_opt = '';

            fwrite($nf, "$news_id|$news_user|$news_title|$news_short|$news_full|$news_avatar|$news_category|$news_rate|$news_mf|$news_opt|\n");
        }
        fclose($nf);
    }

    function make_config_arr_from_file($configfile)
    {
        $config_arr = array();
        $config_temp = file($configfile);

        foreach($config_temp as $option)
        {
            $option = trim($option);
            if (!empty($option) && strpos($option, "\$config") !== false)
            {
                $opart = explode(' = ', $option);
                $config_arr[$opart[0]] = $opart[1];
            }
        }
        return $config_arr;
    }

    function migrate_config()
    {
        $config_old = make_config_arr_from_file(OLDDIR.'/data/config.php');
        $config_new = make_config_arr_from_file(SERVDIR.'/cdata/config.php');

        foreach($config_new as $opt => $val)
        {
            // config_login_ban => config_ban_attempts
            if (strpos($opt, 'config_ban_attempts') !== false)
                $config_new[$opt] = (isset($config_old['$config_login_ban'])) ? $config_old['$config_login_ban'] : '"";';

            if (array_key_exists($opt, $config_old))
                $config_new[$opt] = $config_old[$opt];
        }

        $nf = fopen(SERVDIR.'/cdata/config.php', 'w+');
        fwrite($nf, "<?php \r\n\r\n//System Configurations (Auto Generated file)\r\n");
        foreach($config_new as $opt => $val) fwrite($nf, $opt.' = '.$val."\r\n");

        fwrite($nf, '?>');
    }

    function migrate_templates($path)
    {
        $template = file_get_contents(OLDDIR.$path);
        $rep_pos = strpos($template, '$template_comment');

        preg_match('|\$template_comment[^$]+|', $template, $replaceable);
        $rep_len = strlen($replaceable[0]);
        $replaced = str_replace('{author-name}', '{author}', $replaceable[0]);
        $new_template = substr($template, 0, $rep_pos).$replaced.substr($template, $rep_pos + $rep_len);

        file_put_contents(SERVDIR.'/cdata/'.pathinfo($path, PATHINFO_BASENAME), $new_template);
    }

    if (!is_dir(SERVDIR.'/cdata'))
        die("Not found `cdata` folder <a href='../index.php'>Back</a>");

    $data_dir = array();
    if (is_dir(OLDDIR.'/data'))
        $data_dir = read_dir(OLDDIR.'/data', array(), true, OLDDIR);
    else
        $fail[] = array('Not found', OLDDIR.'/data');

    foreach ($data_dir as $fn)
    {
        if (preg_match('#news\.(?:txt|arch)$#i', $fn) > 0)
        {
            migrate_news($fn);
            continue;
        }

        if (stripos($fn, '/data/upimages') !== false)
        {
            if (!is_dir(SERVDIR.'/uploads'))
                if (!mkdir(SERVDIR.'/uploads', 0777))
                    $fail[] = array('mkdir', SERVDIR.'/uploads');

            $dest = SERVDIR.str_replace('/data/upimages/', '/uploads/', $fn);
            if (!copy(OLDDIR.$fn, $dest))
                $fail[] = array('copy', OLDDIR.$fn, $dest);
            continue;
        }

        if (stripos($fn, '/data/emoticons') !== false)
        {
            if (!is_dir(SERVDIR.'/skins'))
            {
                if (!mkdir(SERVDIR.'/skins', 0777))
                {
                    $fail[] = array('mkdir', SERVDIR.'/skins');
                }
                else
                {
                    if (!mkdir(SERVDIR.'/skins/emoticons', 0777))
                        $fail[] = array('mkdir', SERVDIR.'/skins/emoticons');
                }
            }

            $dest = SERVDIR.str_replace('/data/emoticons/', '/skins/emoticons/', $fn);
            if (!copy(OLDDIR.$fn, $dest))
                $fail[] = array('copy', OLDDIR.$fn, $dest);

            continue;
        }

        if (stripos($fn, '/data/config.php') !== false)
        {
            migrate_config();
            continue;
        }

        if (pathinfo($fn, PATHINFO_EXTENSION) === 'tpl')
        {
            migrate_templates($fn);
            continue;
        }

        $path = SERVDIR.'/cdata';
        foreach (explode('/',  str_ireplace('/data/', '', $fn)) as $dc)
        {
            $path .= '/'.$dc;
            if (strpos($dc, '.') === false)
            {
                if (!is_dir($path) && !mkdir($path, 0777))
                    $fail[] = array('mkdir', $path);
                else
                    chmod($path, 0777);
            }
            else
            {
                if (!copy(OLDDIR.$fn, $path))
                    $fail[] = array('copy', OLDDIR.$fn, $path);

                if (!chmod($path, 0666))
                    $fail[] = array('chmod', $path);
            }
        }
    }

    // migrate skins
    $skins_dir = array();
    if (is_dir(OLDDIR.'/skins'))
        $skins_dir = read_dir(OLDDIR.'/skins', array(), true, OLDDIR);
    else
        $fail[] = array('Not found', OLDDIR.'/skins');

    foreach ($skins_dir as $resourse)
    {
        if (stripos($resourse, '/skins/images/') !== false
            || preg_match('/(?<!default|compact|simple)\.skin\.php$/i', $resourse) > 0)
        {
            if (!copy(OLDDIR.$resourse, SERVDIR.$resourse))
                $fail[] = array('copy', OLDDIR.$resourse, SERVDIR.$resourse);
        }
    }

    $found_problems = proc_tpl('install/problemlist');
    msg('info', lang('Migration success'), lang("Congrats! You migrated to Cutenews ".VERSION). " | <a href='../index.php'>Login</a> ".$found_problems);
}