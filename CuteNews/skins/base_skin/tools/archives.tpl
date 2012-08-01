<script language="javascript">
    function confirmdelete(id, news)
    {
        var agree=confirm("Do you really want to permanently delete this archive ?\\nAll ("+news+") news and comments in it will be deleted.");
        if (agree)
            document.location="{$PHP_SELF}?mod=tools&action=dodeletearchive&archive="+id;
    }
</script>

<form action="{$PHP_SELF}" method="post">
<table border=0 cellpading=0 cellspacing=0 width="645" >
    <tr>
        <td width=450 height="33">
            <h3>Send news to archive</h3>
            <table border=0 cellpading=0 cellspacing=0 width=300  class="panel" cellpadding="10" >
                <tr>
                    <td width=304 height="25"><p align="center"><input type=submit value="Proceed with archiving ..."></p></td>
                </tr>
            </table>
            <input type="hidden" name="action" value="doarchive">
            <input type="hidden" name="mod" value="tools">
        </td>
        <td width="25" align="middle"><img border="0" src="skins/images/help_small.gif"></td>
        <td>&nbsp;<a onclick="Help('archives')" href="#">Explaining archives and<br> &nbsp;Their usage</a></td>
    </tr>
</form>
</table>
<br/>
<table border=0 cellpadding=0 cellspacing=1 width="645">
    <tr> <td width=654 colspan=2 height=14><b>Available archives</b></td> </tr>
    <tr>
        <td width=654 colspan=2 height=1>
            <table width=641 height=100% cellspacing=0 cellpadding=0>
                <tr>
                    <td width=8 bgcolor=#F7F6F4>&nbsp;</td>
                    <td width=160 bgcolor=#F7F6F4>archivation date</td>
                    <td width=222 bgcolor=#F7F6F4>duration</td>
                    <td width=81 bgcolor=#F7F6F4>news</td>
                    <td width=110 bgcolor=#F7F6F4><!--action--></td>
                </tr>
                {$inclusion}
            </table>
        </td>
    </tr>
</table>