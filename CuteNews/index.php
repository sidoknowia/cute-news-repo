<?php

/***************************************************************************
 CuteNews CutePHP.com
 Copyright (с) 2012 Cutenews Team
****************************************************************************/

include ('core/init.php');

if ( $using_safe_skin )
     require_once(SERVDIR."/skins/base_skin/default.skin.php");
else require_once(SERVDIR."/skins/$config_skin.skin.php");

$PHP_SELF = "index.php";

// Check if CuteNews is not installed
$fp = fopen(SERVDIR."/cdata/db.users.php", 'r'); fgets($fp); $user = trim(fgets($fp)); fclose($fp);
if ($user == false)
{
    if ( !file_exists(SERVDIR."/inc/install.php"))
        die_stat(false, '<h2>Error!</h2>CuteNews detected that you do not have users in your db.users.php file and wants to run the install module.<br>
                         However, the install module (<b>./inc/install.php</b>) can not be located, please reupload this file and make sure you set
                         the proper permissions so the installation can continue.');

    require (SERVDIR."/inc/install.php");
    die();
}

hook('index_init');

// User is banned?
$REMOTE_ADDR = '__'.$_SERVER['REMOTE_ADDR'].'__';
$banid = bsearch_key($REMOTE_ADDR, DB_BAN);
if ($banid)
{
    if ( isset($banid[$REMOTE_ADDR]) )
    {
        if ( $banid[$REMOTE_ADDR]['E'] > time() ) msg('error', LANG_ERROR_TITLE, "You're banned");
        elseif ( $banid[$REMOTE_ADDR]['E'] > 0)   delete_key($REMOTE_ADDR, DB_BAN);
    }
}

b64dck();
if ($action == "logout")
{
    $_SESS['user'] = $_SESS['pwd'] = false;
    send_cookie(true);
    add_to_log($username, 'logout');
    msg("info", lang("Logout"), lang("You are now logged out").", <a href=\"$PHP_SELF\">".lang('login')."</a><br /><br>");
}

// sanitize
$is_loged_in = false;
extract(filter_request('mod'), EXTR_OVERWRITE);

// Check the User is Identified -------------------------------------------------------------------------------------
$result      = false;
$username    = empty($_REQUEST['user']) ? $_REQUEST['username'] : $_SESS['ix'];

if ( empty($_SESS['user']))
{
    /* Login Authorization using COOKIES */
    if ($action == 'dologin')
    {
        CSRFCheck();

        // Do we have correct username and password ?
        $member_db      = bsearch_key($username, DB_USERS);
        $cmd5_password  = hash_generate($password);

        if ( in_array($member_db[UDB_PASS], $cmd5_password))
        {
            $_SESS['ix']    = $username;
            $_SESS['user']  = $username;
            $_SESS['data']  = $member_db;

            if ($rememberme == 'yes') $_SESS['@'] = true;
            elseif (isset($_SESS['@'])) unset($_SESS['@']);

            add_to_log($username, 'login');
            delete_key($ip, DB_BAN);

            // Modify Last Login
            $member_db[UDB_LAST] = time();
            edit_key($username, $member_db, DB_USERS);

            $is_loged_in = true;
        }
        else
        {
            $_SESS['user'] = false;
            $result = "<span style='color:red;'>".lang('Wrong username or password')."</span>";
            $result .= add_to_ban($ip);

            add_to_log($username, lang('Wrong username/password'));
            $is_loged_in = false;
        }
    }
}
else
{
    if ($config_push_users == 'yes')
    {
        // If user has been deleted - disable session
        $detect = join('', file(SERVDIR.'/cdata/actions.txt'));
        if (strpos($detect, '%REMOVE|'.md5($_SESS['user'])."\n") !== false)
        {
            $detect = str_replace('%REMOVE|'.md5($_SESS['user'])."\n", '', $detect);
            $w = fopen(SERVDIR.'/cdata/actions.txt', 'w');
            fwrite($w, $detect);
            fclose($w);

            $_SESS       = array();
            $is_loged_in = false;
            $member_db   = false;
        }
        else
        {
            $member_db = $_SESS['data'];
            $is_loged_in = true;
        }
    }
    else
    {
        // Check existence of user
        $member_db  = bsearch_key($_SESS['user'], DB_USERS);
        if ($member_db)
        {
            $is_loged_in = true;
        }
        else
        {
            $_SESS['data'] = false;
            $_SESS['user'] = false;
            $is_loged_in = false;
        }
    }
}

/* END Login Authorization using COOKIES */
send_cookie(true);

// ---------------------------------------------------------------------------------------------------------------------
// If User is Not Logged In, Display The Login Page

if (empty($is_loged_in))
{
    $CSRF = CSRFMake();
    echoheader("user", lang("Please Login"));
    echo proc_tpl('login_window',
                  array('lastusername'  => htmlspecialchars($username),
                        'result'        => $result,
                        'CSRF'          => $CSRF),

                  array('ALLOW_REG' => ($config_allow_registration == "yes")? 1:0 )
    );

    echofooter();
}
elseif ($is_loged_in)
{
    // ********************************************************************************
    // Include System Module
    // ********************************************************************************

                            //name of mod   //access
    $system_modules = array('addnews'       => 'user',
                            'editnews'      => 'user',
                            'main'          => 'user',
                            'options'       => 'user',
                            'images'        => 'user',
                            'editusers'     => 'admin',
                            'editcomments'  => 'admin',
                            'tools'         => 'admin',
                            'ipban'         => 'admin',
                            'about'         => 'user',
                            'categories'    => 'admin',
                            'massactions'   => 'user',
                            'help'          => 'user',
                            'debug'         => 'admin',
                            'wizards'       => 'admin',
                            'update'        => 'admin',
                            'rating'        => 'user',
                            );

    list($system_modules, $mod, $stop) = hook('system_modules_expand', array($system_modules, $mod, false));

    // Plugin tells us: don't show anything, stop
    if ($stop == false)
    {
        if ($mod == false) require(SERVDIR."/inc/main.php");
        elseif( $system_modules[$mod] )
        {
            if ($mod == 'rating')
                require (SERVDIR."/inc/ratings.php");

            elseif ($member_db[UDB_ACL] == ACL_LEVEL_COMMENTER and $mod != 'options')
                msg('error', 'Error!', lang('Access Denied for your user-level (commenter)'));

            elseif( $system_modules[$mod] == "user")
                require (SERVDIR."/inc/".$mod.".php");

            elseif( $system_modules[$mod] == "admin" and $member_db[UDB_ACL] == ACL_LEVEL_ADMIN)
                require (SERVDIR."/inc/".$mod.".php");

            elseif( $system_modules[$mod] == "admin" and $member_db[UDB_ACL] != ACL_LEVEL_ADMIN)
            {
                msg("error", "Access denied", "Only admin can access this module");
            }
            else
            {
                die("Module access must be set to <b>user</b> or <b>admin</b>");
            }
        }
        else
        {
            add_to_log($username, 'Module '.htmlspecialchars($mod).' not valid');
            die_stat(false, htmlspecialchars($mod)." is NOT a valid module");
        }
    }
}

exec_time();
exit_cookie();