<?php

    if (!defined('INIT_INSTANCE')) die('Access restricted');

    define('CONVERT_UPIMAGES', true);

    // Create blank PHP file
    function make_php($phpfile)
    {
        $file = SERVDIR.'/cdata/'.$phpfile;
        if (file_exists($file))
        {
            $w = fopen($file, 'w');
            fwrite($w, '<'.'?php die("Access restricted"); ?>'."\n");
            fclose($w);
        }
    }

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
        $fail = array();
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
            'ipban.db.php',
            'confirmations.php'
        );

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
            if (preg_match('~^/data/upimages/~i', $fx) && defined('CONVERT_UPIMAGES') && CONVERT_UPIMAGES)
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
                    if (!is_dir($path) && !mkdir($path, 0775)) $fail[] = array('mkdir', $path);
                }
                else
                {
                    // Don't replace exist file(s)
                    if (!file_exists($path))
                    {
                        if (!copy(SERVDIR.$fx, $path)) $fail[] = array('copy', SERVDIR.$fx, $path);
                        if (!chmod($path, 0666)) $fail[] = array('chmod', $path);
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
                if (!chmod ($file, 0666)) $fail[] = array('chmod', $file);
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
            if (!chmod ($file, 0666)) $fail[] = array('chmod', $file);
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
            $found_problems = proc_tpl('install/problemlist');
            msg('info', lang('Migration success'), lang("Congrats! You migrated to ".VERSION." automatically"). " | <a href='$PHP_SELF'>Login</a> ".$found_problems);
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
        relocation(PHP_SELF.'?installed');
    }
    // step 1
    else
    {
        // Check cdata and uploads folder
        if (is_dir(SERVDIR.'/cdata') && is_dir(SERVDIR.'/uploads') &&
            is_writable(SERVDIR.'/cdata') && is_writable(SERVDIR.'/uploads'))
        {
            relocation(PHP_SELF.'?action=make');
        }
        else
        {
            echoheader('info', 'Cute News v'.VERSION.' Installer');
            echo proc_tpl('install/welcome');
        }
    }

    echofooter();
?>