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

        <p> Files that are marked as 'not writable' should be downloaded and replaced manually.
            In case of fatal update errors please download the latest version with github and rewrite all files by FTP.
        </p>
        <p><input type="submit" value="Try to update" /></p>
    </form>
</div>