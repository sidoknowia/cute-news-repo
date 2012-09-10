<?PHP

if ( $member_db[UDB_ACL] != ACL_LEVEL_ADMIN )
     msg("error", lang("Access Denied"), lang("You don't have permission to edit users"));

// ********************************************************************************
// List All Available Users + Show Add User Form
// ********************************************************************************
if ($action == "list")
{
    $CSRF = CSRFMake();
    echoheader ("users", lang("Manage Users"), make_breadcrumbs('main/options=options/Manage Users'));

    $i = 0;
    $userlist  = array();
    $all_users = file(SERVDIR."/cdata/db.users.php");
    unset ($all_users[0]);

    foreach ($all_users as $user_line)
    {
        list(,$user_arr) = explode("|", $user_line, 2);
        $user_arr        = unserialize($user_arr);

        $bg = ($i++%2 == 1) ? 'bgcolor="#f7f6f4"' : false;
        $last_login = !empty($user_arr[UDB_LAST]) ? date('r', $user_arr[UDB_LAST]) : 'never';

        switch ($user_arr[1])
        {
            case 1: $user_level = "administrator"; break;
            case 2: $user_level = "editor"; break;
            case 3: $user_level = "journalist"; break;
            case 4: $user_level = "commenter"; break;
        }

        $userlist[] = array
        (
            'bg'            => $bg,
            'title'         => htmlspecialchars($user_arr[UDB_NAME]),
            'date'          => date("F, d Y @ H:i a", $user_arr[UDB_ID]),
            'user_level'    => $user_level,
            'last_login'    => $last_login,
            'count'         => intval( $user_arr[UDB_COUNT] ),
        );
    }

    echo proc_tpl('editusers');
    echofooter();
}
// ********************************************************************************
// Add User
// ********************************************************************************
elseif ($action == "adduser")
{
    CSRFCheck();

    if (!empty($userdel))
    {
        foreach ($userdel as $uid => $perm)
        {
            // Except myself
            if ($member_db[UDB_NAME] != $uid)
                delete_key($uid, DB_USERS);
        }
        msg('info', lang('User(s) deleted'), lang('The user(s) was successfully deleted'), "#GOBACK");
    }

    if (!$regusername)
        msg("error", lang('Error!'), lang("Username can not be blank"), "#GOBACK");

    if (!$regpassword)
        msg("error", lang('Error!'), lang("Password can not be blank"), "#GOBACK");

    if (!preg_match('/^[\.A-z0-9_\-]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{1,4}$/', $regemail))
        msg("error", lang('Error!'), lang("Not valid Email"), "#GOBACK");

    $all_users = file(SERVDIR."/cdata/db.users.php");
    unset ($all_users[0]);
    foreach ($all_users as $user_line)
    {
        list(,$user_arr) = explode("|", $user_line, 2);
        $user_arr        = unserialize($user_arr);
        if ($user_arr[UDB_NAME]  == $regusername) msg("error", lang('Error!'), lang("Sorry but user with this username already exist"), "#GOBACK");
    }

    $add_time = time() + ($config_date_adjust*60);

    // Generate best password
    $ht = hash_generate($regpassword);
    $regpassword = $ht[ count($ht)-1 ];

    switch ($reglevel)
    {
        case "1": $level = "administrator"; break;
        case "2": $level = "editor"; break;
        case "3": $level = "journalist"; break;
        case "4": $level = "commenter"; break;
    }

    // Replica in db.users
    add_key($regusername, array(UDB_ID => $add_time, $reglevel, $regusername, $regpassword, $regnickname, $regemail, 0, 0), DB_USERS);

    msg("info", lang("User Added"),
                str_replace(array('%1', '%2'), array($regusername, $level), lang("The user <b>%1</b> was successfully added as <b>%2</b>")),
                '#GOBACK');
}
// ********************************************************************************
// Edit User Details
// ********************************************************************************
elseif ($action == "edituser")
{
    $CSRF = CSRFMake();
    if ( false === ($user_arr = bsearch_key($id, DB_USERS)) )
         die( lang('User not exist') );

    $edit_level = array
    (
        array('id' => ACL_LEVEL_COMMENTER,  's' => false, 'type' => 'commenter'),
        array('id' => ACL_LEVEL_JOURNALIST, 's' => false, 'type' => 'journalist'),
        array('id' => ACL_LEVEL_EDITOR,     's' => false, 'type' => 'editor'),
        array('id' => ACL_LEVEL_ADMIN,      's' => false, 'type' => 'administrator'),
    );

    if ( isset($edit_level[ 4 - $user_arr[UDB_ACL] ]['id']) )
         $edit_level[ 4 - $user_arr[UDB_ACL] ]['s'] = 'selected';

    echo proc_tpl
    (
        'editusers.user',
        array
        (
            'CSRF'          => $CSRF,
            'user_arr[2]'   => $user_arr[2],
            'user_arr[4]'   => $user_arr[4],
            'user_arr[5]'   => $user_arr[4],
            'user_arr[6]'   => $user_arr[6],
            'user_date'     => date("r", $user_arr[0]),
            'edit_level'    => $edit_level,
            'last_login'    => empty($user_arr[UDB_LAST]) ? lang('never') : date('r', $user_arr[UDB_LAST]),
            'id'            => $id,
        )
    );

}
// ********************************************************************************
// Do Edit User
// ********************************************************************************
elseif ($action == "doedituser")
{
    CSRFCheck();

    if (empty($id))
         msg('error', lang("This is not a valid user"), '#GOBACK');

    if ( false === ($the_user = bsearch_key($id, DB_USERS)) )
         msg('error', lang("This is not a valid user"), '#GOBACK');

    // Change password if present
    if (!empty($editpassword))
    {
        $hmet = hash_generate($editpassword);
        $the_user[UDB_PASS] = $hmet[ count($hmet)-1 ];
        if ($id == $_SESS['user']) $_SESS['pwd'] = $editpassword;
        send_cookie();
    }

    // Change user level anywhere
    $the_user[UDB_ACL] = $editlevel;
    edit_key($id, $the_user, DB_USERS);

    echo proc_tpl('editusers/doedituser/saved');

}

?>
