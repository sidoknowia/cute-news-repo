<form method=post action="{$PHP_SELF}">
    <input type=hidden name="csrf_code" value={$CSRF}>
    <table border=0 cellpadding=0 cellspacing=0 width="645" >
    <tr>
        <td width=321 height="33">
            <h3>Add Category</h3>
            <table border=0 cellpadding=0 cellspacing=0 width=300  class="panel" >
                <tr>
                    <td width=130 height="25">&nbsp;Name</td>
                    <td  height="25"><input type=text name=cat_name></td>
                </tr>
                <tr>
                    <td height="22">&nbsp;Icon URL</td>
                    <td height="22"><input onFocus="this.select()" value="(optional)" type=text name=cat_icon></td>
                </tr>

                <tr>
                    <td height="22">&nbsp;Category Access</td>
                    <td height="22">
                        <select name="cat_access">
                            <option value="0" selected>Everyone Can Write</option>
                            <option value="2">Only Editors and Admin</option>
                            <option value="1">Only Admin</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td width=98 height="32">&nbsp;</td>
                    <td width=206 height="32">
                        <input type=submit value="  Add Category  ">
                        <input type=hidden name=mod value=categories>
                        <input type=hidden name=action value=add>
                    </td>
                </tr>
            </table>
        </td>
        <td width="25" align=middle><img border="0" src="skins/images/help_small.gif"></td>
        <td>&nbsp;<a onclick="Help('categories')" href="#">What are categories and<br>&nbsp;How to use them</a></td></td>
    </tr>
    </table>
</form>

<table border=0 cellpadding=3 cellspacing=0 width="645" >
    <tr>
        <td width=654 colspan=2 height=14>
            <b>Categories</b>
    </tr>
    <tr>
        <td width=654 colspan=2 height=1>
            <table width=100%% cellspacing=0 cellpadding=2>
                <tr>
                    <td width=6% bgcolor=#F7F6F4>&nbsp;<b>ID</b></td>
                    <td width=30% bgcolor=#F7F6F4><b>name</b></td>
                    <td width=14% bgcolor=#F7F6F4><b>icon</b></td>
                    <td width=20% bgcolor=#F7F6F4><b>restriction</b></td>
                    <td width=20% bgcolor=#F7F6F4><b>action</b></td>
                </tr>
                {$result}
            </table>
        </td>
    </tr>
</table>