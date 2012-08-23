<form action="{$PHP_SELF}" method="POST">
    <input type="hidden" name="mod" value="wizards" />
    <input type="hidden" name="action" value="rewrite" />
    <div>
        <textarea name="mod_rewrite" rows="20" cols="90">{$mod_rewrite}</textarea>
    </div>
    <div><button name="do" value="submit">Save</button> <button name="do" value="build">Make basic news rules in htaccess</button> Current .htaccess place is <b>{$htpath}/.htaccess</b></div>
</form>