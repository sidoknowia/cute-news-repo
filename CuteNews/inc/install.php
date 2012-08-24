<?php

    echoheader('info', 'Cute News v'.VERSION.' Installer');

    if ($action == 'make')
    {
        $copy = array('Default', 'Headlines', 'rss');
        $dirs = array('archives', 'backup', 'cache', 'log', 'plugins', 'template');
        $files = array
        (
            'auto_archive.db.php', 'idnews.db.php', 'cat.num.php', 'category.db.php', 'comments.txt', 'config.php',
            'db.ban.php', 'db.users.php', 'flood.db.php', 'actions.txt',
            'news.txt', 'postponed_news.txt', 'replaces.php', 'rss_config.php',
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
                chmod ($file, 0666);
            }
        }

        // Copy files
        foreach ($copy as $v)
        {
            $file = SERVDIR.'/cdata/'.$v.'.tpl';
            if (!file_exists($file))
            {
                $rw = read_tpl('install/copy/'.$v);
                $cp = fopen($file, 'w');
                fwrite ($cp, $rw);
                fclose($cp);
                chmod ($file, 0666);
            }
        }

        // Place .htaccess
        $w = fopen(SERVDIR.'/cdata/.htaccess', 'w');
        fwrite($w, "Deny From All");
        chmod (SERVDIR.'/cdata/.htaccess', 0644);
        fclose($w);

        // Make Upload folder
        mkdir(SERVDIR.'/uploads');
        chmod(SERVDIR.'/uploads', 0777);

        $x = fopen(SERVDIR.'/uploads/index.html', 'w');
        fwrite($x, 'Access denied');
        fclose($x);

        header("Location: ".PHP_SELF.'?action=register');

    }
    // step 2
    elseif ($action == 'register')
    {
        $site = $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);
        echo str_replace('{site}', preg_replace('~/$~', '', $site), proc_tpl('install/copy'));
    }
    // step 3
    elseif ($action == 'finish')
    {
        extract( filter_request('site,email,user,password,retype,email,nick'), EXTR_OVERWRITE);

        // error in password
        if ($password && $password != $retype or !$password)
            header("Location: ".PHP_SELF.'?action=register');

        // add config.php
        $hac = $_SERVER['HTTP_ACCEPT_CHARSET'];
        list($cp) = explode(',', $hac);

        $cfg = fopen(SERVDIR.'/cdata/config.php', 'w');
        fwrite($cfg, read_tpl('install/copy/config'));
        fwrite($cfg, '$config_http_script_dir = "'.$site.'";'."\n");
        fwrite($cfg, '$config_notify_email = "'.$email.'";'."\n");
        fwrite($cfg, '$config_default_charset = "'.$cp.'";'."\n");
        fwrite($cfg, "?>");
        fclose($cfg);

        // Generate unique salt
        $cfg = unserialize( str_replace("<?php die(); ?>\n", '', implode('', file ( SERVDIR . CACHE.'/conf.php' ))) );
        $salt = $cfg['crypt_salt'] = false;
        for ($j = 0; $j < 4; $j++)
        {
            for ($i = 0; $i < 64; $i++) $salt .= md5(mt_rand().uniqid(mt_rand()));
            $cfg['crypt_salt'] .= md5($salt);
        }
        $CryptSalt = $cfg['crypt_salt'];
        fv_serialize('conf', $cfg);

        // add user
        $hash = hash_generate($password);
        $pwd  = $hash[ count($hash)-1 ];
        add_key($user, array(0 => time(), ACL_LEVEL_ADMIN, $user, $pwd, $nick, $email, 0, 0), DB_USERS);

        // auto-login
        header('Location: '.PHP_SELF);
        setcookie('session', base64_encode( xxtea_encrypt(serialize( array(
                    'user'=>$user,
                    'pwd' => $password,
                    'csrf' => md5(mt_rand()).'@'.$_SERVER['REMOTE_ADDR'])),
                    $CryptSalt)), 0, '/');

        ob_get_clean();
        die();

    }
    // step 1
    else
    {
        // Try create cdata folder or set rights
        if (is_dir(SERVDIR.'/cdata') == false) mkdir(SERVDIR.'/cdata', 0775);
        if (is_writable(SERVDIR.'/cdata') == false) chmod(SERVDIR.'/cdata', 0775);

        // Check - ok?
        if (is_writable(SERVDIR.'/cdata'))
        {
            header('Location: '.PHP_SELF.'?action=make');
        }
        echo proc_tpl('install/welcome', array(), array('WRITABLE' => is_writable(SERVDIR.'/cdata')));
    }

    echofooter();
?>