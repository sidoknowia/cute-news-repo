<form method=POST action="{$PHP_SELF}" name=personal>
    
    <input type=hidden name=mod value=options>
    <input type=hidden name=action value=dosavepersonal>
    <input type=hidden name="csrf_code" value="{$CSRF}" />
    
    <table border=0 width=617 cellspacing="4" cellpadding="0">
        <tr>
            <td height="21" width="150" bgcolor=#F7F6F4>&nbsp;Username</td>
            <td height="21" bgcolor=#F7F6F4 colspan=2>{$member_db[2]}
        </tr>

        <tr>
            <td height="21">&nbsp;New Password</td>
            <td height="21" colspan=2> <input type="password" name=editpassword> </td>
        </tr>
        <tr>
            <td height="21">&nbsp;Confirm Password</td>
            <td height="21" colspan=2> <input type="password" name=confirmpassword >&nbsp;&nbsp;&nbsp;Confirm is if you want to change</td>
        </tr>

        <tr>
            <td height="21" bgcolor=#F7F6F4>&nbsp;Nickname</td>
            <td height="21" bgcolor=#F7F6F4 colspan=2> <input type=text name=editnickname value="{$member_db[4]}"> </td>
        </tr>

        <tr>
            <td height="21">&nbsp;Email</td>
            <td height="21" colspan=2> <input type=text name=editmail value="{$member_db[5]}">&nbsp;&nbsp;&nbsp;<input type=checkbox name=edithidemail {$ifchecked}>&nbsp;Hide my e-mail from visitors
        </tr>

        {NOTCOMMENTER}
        <tr>
            <td height="21" bgcolor=#F7F6F4>&nbsp;Default Avatar URL</td>
            <td height="21" bgcolor=#F7F6F4 > <input type=text name=change_avatar value="{$member_db[8]}">&nbsp;&nbsp;&nbsp;&nbsp;will appear on 'Add News' page </td>
        </tr>
        {/NOTCOMMENTER}

        <tr>
            <td height="21" {$bg}>&nbsp;Access Level</td>
            <td height="21" {$bg} colspan=2>{$access_level}</td>

        {NOTCOMMENTER}
        </tr>
        <tr>
            <td height="21" bgcolor=#F7F6F4>&nbsp;written news</td>
            <td height="21" bgcolor=#F7F6F4 colspan=2>{$member_db[6]}</td>
        </tr>
        {/NOTCOMMENTER}

        <tr>
            <td height="21">registration date </td>
            <td height="21" colspan=2> {$registrationdate} </td>
        </tr>

        <tr>
            <td colspan=2><br /><input type=submit value="Save Changes" accesskey="s">
        </tr>

    </table>
</form>