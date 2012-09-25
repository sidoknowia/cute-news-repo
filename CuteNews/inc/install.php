<?php

    define('CONVERT_UPIMAGES', false);

    function make_salt()
    {
        // Generate unique salt
        if (file_exists(SERVDIR . '/cdata/cache/conf.php'))
             $cfg = unserialize( str_replace("<?php die(); ?>\n", '', implode('', file ( SERVDIR . '/cdata/cache/conf.php' ))) );
        else $cfg = array();

        $salt = $cfg['crypt_salt'] = false;
        for ($j = 0; $j < 4; $j++)
        {
            for ($i = 0; $i < 64; $i++) $salt .= md5(mt_rand().uniqid(mt_rand()));
            $cfg['crypt_salt'] .= md5($salt);
        }
        $CryptSalt = $cfg['crypt_salt'];

        $fx = fopen(SERVDIR.'/cdata/cache/conf.php', 'w');
        fwrite($fx, "<?php die(); ?>\n" . serialize($cfg) );
        fclose($fx);

        return $CryptSalt;
    }

    // -----------------------------------------------------------------------------------------------------------------
    if ($action == 'make')
    {
        $copy = array('Default', 'Headlines', 'rss');
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
            'ipban.db.php'
        );

        // Make Upload folder -----------
        $success = 1;
        if (!is_dir(SERVDIR.'/uploads'))
        {
            $success *= mkdir(SERVDIR.'/uploads', 0777);
            $x = fopen(SERVDIR.'/uploads/index.html', 'w');
            fwrite($x, 'Access denied');
            fclose($x);
        }

        // COPY ALL FROM DATA FOLDER
        $data_dir = array();
        if  (is_dir(SERVDIR.'/data'))
             $data_dir = read_dir(SERVDIR.'/data');
        else $data_dir = array();

        foreach ($data_dir as $fx)
        {
            // Skip data/emoticons
            if (preg_match('~^/data/emoticons/~i', $fx)) continue;
            if (preg_match('~\.htaccess~i', $fx)) continue;

            // Migration uploads
            if (preg_match('~^/data/upimages/~i', $fx) && defined('CONVERT_UPIMAGES') && CONVERT_UPIMAGES)
            {
                $success *= copy(SERVDIR.$fx, SERVDIR.str_replace('/data/upimages/', '/uploads/', $fx));
                continue;
            }

            $path = SERVDIR.'/cdata';
            foreach (explode('/',  preg_replace('~^/data/~i', '', $fx)) as $dc)
            {
                $path .= '/'.$dc;
                if (strpos($dc, '.') === false)
                {
                    if (!is_dir($path))
                    {
                        $success *= mkdir($path, 0775);
                    }
                }
                else
                {
                    // Don't replace exist file(s)
                    if (!file_exists($path))
                    {
                        $success *= copy(SERVDIR.$fx, $path);
                        chmod($path, 0666);
                    }
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
            if (!is_dir($dir)) mkdir($dir, 0775);
        }

        // Make files
        foreach ($files as $v)
        {
            $file = SERVDIR.'/cdata/'.$v;
            if (!file_exists($file))
            {
                fclose( fopen($file, 'w') );
                $success *= chmod ($file, 0666);
            }
        }

        // Copy files anywhere
        foreach ($copy as $v)
        {
            $file = SERVDIR.'/cdata/'.$v.'.tpl';
            $rw = read_tpl('install/copy/'.$v);
            $cp = fopen($file, 'w');
            fwrite ($cp, $rw);
            fclose($cp);
            $success *= chmod ($file, 0666);
        }

        // MIGRATION SCRIPT --------------------------------------------------------------------------------------------
        $count_users = count( file(SERVDIR.'/cdata/users.db.php') );

        // Clean or migration installation
        if  ($count_users < 2)
        {
            relocation( PHP_SELF.'?action=register' );
        }
        else
        {
            $CryptSalt = make_salt();
            msg('info', lang('Migration success'), lang("Congrats! You migrated to 1.5.0 automatically"). " | <a href='$PHP_SELF'>Login</a>");
        }

    }
    // step 2
    elseif ($action == 'register')
    {
        echoheader('info', 'Cute News v'.VERSION.' Installer');
        $site = $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);
        echo str_replace('{site}', preg_replace('~[\\\|/]\s*$~', '', $site), proc_tpl('install/copy'));
    }
    // step 3
    elseif ($action == 'finish')
    {
        $site     = $_POST['site'];
        $user     = $_POST['user'];
        $email    = $_POST['email'];
        $password = $_POST['password'];
        $retype   = $_POST['retype'];
        $nick     = $_POST['nick'];

        if (empty($site) or empty($user) or empty($email))
            msg('error', lang('Error!'), lang('You must fill all required fields'), '#GOBACK');

        // error in password
        if ($password && $password != $retype or !$password)
            msg('error', lang('Error!'), lang("Invalid password(s) or don't match"), '#GOBACK');

        // add config.php
        $hac = $_SERVER['HTTP_ACCEPT_CHARSET'];
        list($cp) = spsep($hac);

        $cfg = fopen(SERVDIR.'/cdata/config.php', 'w');
        fwrite($cfg, read_tpl('install/copy/config'));
        fwrite($cfg, '$config_http_script_dir = "'.$site.'";'."\n");
        fwrite($cfg, '$config_notify_email = "'.$email.'";'."\n");
        fwrite($cfg, '$config_default_charset = "'.$cp.'";'."\n");
        fwrite($cfg, "?>");
        fclose($cfg);

        // Generate unique salt
        $CryptSalt = make_salt();

        // Make user table
        $hash = hash_generate($password);
        $pwd  = $hash[ count($hash)-1 ];

        rewritefile('/cdata/users.db.php', '<'.'?php die("You don\'t have access to open this file!"); ?>'."\n");
        user_add(array(0 => time(), ACL_LEVEL_ADMIN, $user, $pwd, $nick, $email, 0, 0));

        // auto-login
        setcookie('session', base64_encode( xxtea_encrypt(serialize( array( 'user' => $user )), "$ip@$CryptSalt")), 0, '/');
        relocation(PHP_SELF);
    }
    // step 1
    else
    {
        // Try create cdata folder or set rights
        if (is_dir(SERVDIR.'/cdata') == false) mkdir(SERVDIR.'/cdata', 0777);
        if (is_writable(SERVDIR.'/cdata') == false) chmod(SERVDIR.'/cdata', 0777);

        // Check - ok?
        if (is_writable(SERVDIR.'/cdata'))
        {
            relocation(PHP_SELF.'?action=make');
        }
        else
        {
            echoheader('info', 'Cute News v'.VERSION.' Installer');
            proc_tpl('install/welcome');
        }
    }

    echofooter();
?>