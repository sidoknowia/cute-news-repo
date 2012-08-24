<?PHP

if ( $member_db[UDB_ACL] != ACL_LEVEL_ADMIN )
     msg("error", lang("Access Denied"), lang("You don't have permission to edit users"));

// ********************************************************************************
// List All Available Users + Show Add User Form
// ********************************************************************************
if ($action == "list")
{
    $CSRF = CSRFMake();

    echoheader ("users", lang("Manage Users"));
    echo proc_tpl('editusers', array('CSRF' => $CSRF));

    $i = 1;
    $all_users = file(SERVDIR."/cdata/db.users.php");
    unset($all_users[0]);
    foreach ($all_users as $user_line)
    {
        list(,$user_arr) = explode("|", $user_line, 2);
        $user_arr        = unserialize($user_arr);

        $bg = ($i++%2 == 0) ? 'bgcolor="#f7f6f4"' : false;
        $last_login         = !empty($user_arr[UDB_LAST]) ? date('r', $user_arr[UDB_LAST]) : 'never';

        switch ($user_arr[1])
        {
            case 1: $user_level = "administrator"; break;
            case 2: $user_level = "editor"; break;
            case 3: $user_level = "journalist"; break;
            case 4: $user_level = "commenter"; break;
        }

        echo "<tr $bg title='".$user_arr[UDB_NAME]."'&#039;s last login was on: $last_login'>
        <td width=143> &nbsp;".$user_arr[UDB_NAME]."</td>
        <td width=197>";

        echo( date("F, d Y @ H:i a", $user_arr[UDB_ID]) );

        echo "</td> <td>&nbsp;</td>
              <td width=83>&nbsp;&nbsp;".$user_arr[UDB_COUNT]."</td>
              <td width=122> &nbsp;$user_level</td>
              <td width=80>
                  <a onclick=\"popupedit('".$user_arr[UDB_NAME]."'); return(false)\" href=#>[edit]</a>&nbsp;
                  <a onclick=\"confirmdelete('".$user_arr[UDB_NAME]."'); return(false)\" href=\"$PHP_SELF?mod=editusers&action=dodeleteuser&id=$user_arr[0]\">[delete]</a>
              </td></tr>";

    }
    echo "</table>";
    
    echofooter();
}
// ********************************************************************************
// Add User
// ********************************************************************************
elseif ($action == "adduser")
{
    if (!$regusername)
        msg("error", LANG_ERROR_TITLE, lang("Username can not be blank"), "javascript:history.go(-1)");

    if (!$regpassword)
        msg("error", LANG_ERROR_TITLE, lang("Password can not be blank"), "javascript:history.go(-1)");

    if (!preg_match('/^[\.A-z0-9_\-]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{1,4}$/', $regemail))
        msg("error", LANG_ERROR_TITLE, lang("Not valid Email"), "javascript:history.go(-1)");

    CSRFCheck();

    $all_users = file(SERVDIR."/cdata/db.users.php");
    unset($all_users[0]);
    foreach ($all_users as $user_line)
    {
        list(,$user_arr) = explode("|", $user_line, 2);
        $user_arr        = unserialize($user_arr);
        if ($user_arr[UDB_NAME]  == $regusername) msg("error", LANG_ERROR_TITLE, lang("Sorry but user with this username already exist"), "javascript:history.go(-1)");
    }

    $add_time = time() + ($config_date_adjust*60);

    // Generate best password
    $ht = hash_generate($regpassword);
    $regpassword = $ht[ count($ht)-1 ];

    switch($reglevel)
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
                $PHP_SELF . "?mod=editusers&action=list");
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

    if (empty($id)) die_stat(false, lang("This is not a valid user."));

    if ( false === ($the_user = bsearch_key($id, DB_USERS)) )
         die_stat(false, lang("This is not a valid user."));

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
// ********************************************************************************
// Delete User
// ********************************************************************************
elseif ($action == "dodeleteuser")
{
    if ( empty($id) ) die_stat(false, lang("This is not a valid user"));

    delete_key($id, DB_USERS);

    if ($config_push_users == 'yes')
    {
        $a = fopen(SERVDIR.'/cdata/actions.txt', 'a');
        fwrite($a, "%REMOVE|".md5($id)."\n");
        fclose($a);
    }

    msg("info", lang("User Deleted"), str_replace('%1', $id, lang("The user <b>%1</b> was successfully deleted")), "$PHP_SELF?mod=editusers&action=list");
}

?>
