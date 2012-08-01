{-ENTRIES_SHOWED}
    <table border=0 cellpading=0 cellspacing=0 width=100% >
        <tr>
            <td colspan=6>
                <p style="border: solid black 1px;  margin: 22px 22px 22px 22px; padding: 4px;" align=center>- No news were found matching your criteria -<br><a href="#" onclick="getElementById('options').style.display='';">[options]</a></p>
            </td>
        </tr>
    </table>
{/-ENTRIES_SHOWED}
{ENTRIES_SHOWED}
    <script type="text/javascript">
    <!--
    function ckeck_uncheck_all()
    {
        var frm = document.editnews;
        for (var i=0;i<frm.elements.length;i++)
        {
            var elmnt = frm.elements[i];
            if (elmnt.type=='checkbox')
            {
                if(frm.master_box.checked == true) elmnt.checked=false;
                else elmnt.checked=true;
            }
        }
        if (frm.master_box.checked == true) frm.master_box.checked = false;
        else frm.master_box.checked = true;
    }
    -->
    </script>
    <form method="post" name="editnews">
    <table border=0 cellpading=0 cellspacing=0 width=100%>
        <tr>
            <td width="347">Title</td>
            <td width="65">Comments</td>
            <td width="65">Category</td>
            <td width="58">Date</td>
            <td width="78">Author</td>
            <td width="21" align="center"><input style="border: 0px; background:transparent;" type=checkbox name=master_box title="Check All" onclick="javascript:ckeck_uncheck_all();"> </td>
        </tr>
        {$entries}
        <tr> <td colspan="7" align="right">&nbsp; </tr>
        <tr>
            <td>{$npp_nav}</td>
            <td colspan="7" align="right"> With selected:
                <select name="action">
                    <option value="">-- Choose Action --</option>
                    <option title="delete all selected news" value="mass_delete">Delete</option>
                    {$do_action}
                </select>
                <input type=hidden name=source value="{$source}">
                <input type=hidden name=mod value="massactions">
                <input type=submit value=Go>
            </td>
            </tr>
        </tr>
    </table>
    </form>
{/ENTRIES_SHOWED}