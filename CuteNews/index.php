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
        if ( $banid[$REMOTE_ADDR]['E'] > time() )
            msg('error','Error!', "You're banned");
        elseif ( $banid[$REMOTE_ADDR]['E'] > 0)
            delete_key($REMOTE_ADDR, DB_BAN);
    }
}

b64dck();
if ($action == "logout")
{
    $_SESS['user'] = $_SESS['pwd'] = false;
    add_to_log($username, 'logout');
    msg("info", lang("Logout"), lang("You are now logged out").", <a href=\"$PHP_SELF\">".lang('login')."</a><br /><br>");
}

// sanitize
extract(filter_request('mod,csrf'), EXTR_OVERWRITE);

$is_loged_in        = false;
$cookie_logged      = false;
$session_logged     = false;
$temp_arr           = explode("?", $HTTP_REFERER);
$HTTP_REFERER       = $temp_arr[0];

if(substr($HTTP_REFERER, -1) == "/") $HTTP_REFERER.= "index.php";

// Check the User is Identified -------------------------------------------------------------------------------------

if (isset($HTTP_X_FORWARDED_FOR)) $ip = $HTTP_X_FORWARDED_FOR;
elseif (isset($HTTP_CLIENT_IP))   $ip = $HTTP_CLIENT_IP;
if (empty($ip))                   $ip = $_SERVER['REMOTE_ADDR'];
if (empty($ip))                   $ip = false;

$username    = empty($_SESS['user']) ? $username : $_SESS['user'];
$password    = $password? $password : (empty($_SESS['pwd'])  ? $password : $_SESS['pwd']);

/* Login Authorization using COOKIES */
if (isset($username))
{
    // Do we have correct username and password ?
    $cmd5_password = hash_generate($password);
    $use_ln = check_login($username, $cmd5_password);
    if ($use_ln)
    {
        if ($action == 'dologin')
        {
            $_SESS['ix'] = $username;
            if (!empty($csrf) && $csrf == $_SESS['csrf'])
            {
                $_SESS['user'] = $username;
                $_SESS['pwd']  = $password;
                if ($rememberme == 'yes') $_SESS['@'] = true;
                elseif (isset($_SESS['@'])) unset($_SESS['@']);

                add_to_log($username, 'login');
                delete_key($REMOTE_ADDR, DB_BAN);

                // Modify Last Login
                $member_db[UDB_LAST] = time();
                edit_key($username, $member_db, DB_USERS);

                $is_loged_in = true;
            }
            else
            {
                $_SESS['user'] = false;
                $_SESS['pwd'] = false;
                $result = "<span style='color:red;'>CSRF missed</span>";
                add_to_log($username, 'Missed CSRF (cookies)');

                $is_loged_in = false;
            }
        }
        else $is_loged_in = true;
    }
    else
    {
        $_SESS['user'] = false;
        $_SESS['pwd'] = false;
        $result = "<span style='color:red;'>".lang('Wrong username or password')."</span>";
        $result .= add_to_ban($REMOTE_ADDR);

        add_to_log($username, lang('Wrong username/password'));
        $is_loged_in = false;
    }
}

/* END Login Authorization using COOKIES */
send_cookie(true);

// ---------------------------------------------------------------------------------------------------------------------
// If User is Not Logged In, Display The Login Page

if (empty($is_loged_in))
{

    $_SESS['csrf'] = md5( mt_rand().mt_rand().mt_rand() );

    echoheader("user", lang("Please Login"));
    echo proc_tpl('login_window',
                  array('lastusername' => $_SESS['ix'], 'result' => $result, 'csrf' => $_SESS['csrf']),
                  array('ALLOW_REG' => ($config_allow_registration == "yes")? 1:0 ));
    echofooter();
}
elseif ($is_loged_in)
{

    // xxtea algoritm is better, than HTTP_REFERER
    $csrf = isset($_SESS['csrf']) ? $_SESS['csrf'] : false;

    // is valid md5 hash?
    if ( !preg_match('/^[0-9a-f]{32}/', $csrf))
    {
        add_to_log($username, 'Permanent crypt CSRF missing!');
        die_stat(false, lang("<h2>Sorry but your access to this page was denied !</h2><br>try to <a href=\"?action=logout\">logout</a> and then login again"));
    }

    // strong CSRF
    list (,$addr) = explode('@', $_SESS['csrf']);
    if ( $action != 'dologin' && $addr != $_SERVER['REMOTE_ADDR'])
    {
        header('Location: '.PHP_SELF);
        $_SESS['user'] = false;
        exit_cookie();
    }

    // save csrf
    $_SESS['csrf'] = md5(mt_rand()).'@'.$_SERVER['REMOTE_ADDR'];

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
                            'hooks'         => 'admin',
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
            add_to_log($username, 'Module '.$mod.' not valid');
            die_stat(false, $mod." is NOT a valid module");
        }
    }
}

exec_time();
exit_cookie();