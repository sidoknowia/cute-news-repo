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
        fv_serialize('conf', $cfg);

        return $CryptSalt;
    }

    // -----------------------------------------------------------------------------------------------------------------
    if ($action == 'make')
    {
        $copy = array('Default', 'Headlines', 'rss');
        $dirs = array('archives', 'backup', 'cache', 'log', 'plugins', 'template');

        /* Deprecated
           - ipban.db.php
           - users.db.php

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
            'db.users.php',
            'flood.db.php',
            'news.txt',
            'postponed_news.txt',
            'rss_config.php',
            'unapproved_news.txt',
            'idnews.db.php',
            'replaces.php',
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
                    if ( !in_array($dc, array('ipban.db.php', 'users.db.php') ) )
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

        // Migrate Users
        $migrate_users_done = false;
        if (file_exists(SERVDIR.'/data/users.db.php'))
        {
            $users = file(SERVDIR.'/data/users.db.php');
            if (isset($users[0])) unset($users[0]);

            if (!empty($users))
            {
                foreach ($users as $v)
                {
                    $data = explode('|', $v);
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
                $migrate_users_done = true;;
            }
        }

        // Migrate IPBans
        if (file_exists(SERVDIR.'/data/ipban.db.php'))
        {
            $ipban = file(SERVDIR.'/data/ipban.db.php');
            foreach ($ipban as $v)
            {
                $data = explode('|', trim($v));
                add_ip_to_ban($data[0]);
            }
        }

        // Clean or migration installation
        if  ($migrate_users_done == false)
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
        echo str_replace('{site}', preg_replace('~/$~', '', $site), proc_tpl('install/copy'));
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
            msg('error', LANG_ERROR_TITLE, lang('You must fill all required fields'), '#GOBACK');

        // error in password
        if ($password && $password != $retype or !$password)
            msg('error', LANG_ERROR_TITLE, lang("Invalid password(s) or don't match"), '#GOBACK');

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
        $CryptSalt = make_salt();

        // add user
        $hash = hash_generate($password);
        $pwd  = $hash[ count($hash)-1 ];
        add_key($user, array(0 => time(), ACL_LEVEL_ADMIN, $user, $pwd, $nick, $email, 0, 0), DB_USERS);

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
            proc_tpl('install/welcome', array(), array('WRITABLE' => is_writable(SERVDIR.'/cdata')));
        }
    }

    echofooter();
?>