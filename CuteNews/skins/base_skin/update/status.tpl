<div style="margin: 0 0 0 64px;">
    <form action="{$PHP_SELF}" method="post">
        <input type="hidden" name="mod" value="update"/>
        <input type="hidden" name="action" value="do_update"/>
        <table border="0" cellpadding="4" cellspacing="0">
            <tr bgcolor="#FFFFE0"><td>Path</td> <td>Status</td></tr>
            {foreach from=files_to_update}
                <tr>
                    <td><input type="hidden" name="files[]" value="{$files_to_update.0}"/>{$files_to_update.0}</td>
                    <td>{$files_to_update.1}</td>
                </tr>
            {/foreach}
        </table>

        <p> Files marked as "not writable", you need to download and replace the hand.
            If fatal errors occur during the upgrade, download the latest version from github and overwrite all the files on FTP.
        </p>
        <p><input type="submit" value="Try to update" /></p>
    </form>
</div>