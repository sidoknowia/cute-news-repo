<script language="javascript">
function confirmdelete(id){
    var agree=confirm("Do you really want to permanently delete this backup ?");
    if (agree)
    document.location="index.php?mod=tools&action=dodeletebackup&backup="+id;
    }
function confirmrestore(id){
    var agree=confirm("Do you really want to restore your news from this backup ?\nAll current news and archives will be overwritten.");
    if (agree)
    document.location="index.php?mod=tools&action=dorestorebackup&backup="+id;
    }
</script>

<h3>Create BackUp</h3>
<form method=post action="%1">
    <input type=hidden name=action value=dobackup>
    <input type=hidden name=mod value=tools>
    <table border=0 cellpading=0 cellspacing=0 class="panel" cellpadding="10" width="390" >
    <tr> <td height="25" width="366">Name of the BackUp: <input type=text name=back_name>&nbsp; <input type=submit value=" Proceed "></td> </tr>
    </table>
</form>

<table border=0 cellpading=0 cellspacing=0 cellpadding="10" width="390" >
<tr>
    <td width=654 height=1>
        <h3>Available BackUps</h3>
        <table width=641 height=100% cellspacing=0 cellpadding=0>
        <tr>
            <td width=2% bgcolor=#F7F6F4>&nbsp;</td>
            <td width=40% bgcolor=#F7F6F4>name</td>
            <td width=22% bgcolor=#F7F6F4>active news</td>
            <td width=16% bgcolor=#F7F6F4>archives</td>
            <td width=20% bgcolor=#F7F6F4>action</td>
        </tr>
        {$inclusion}
        </table>
    </td>
</tr>
</table>
