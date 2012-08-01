{IS}
    <p>Position - a procedure that will be executed when you call</p>
    <table width="650" cellpadding="3" cellspacing="0">
        <tr bgcolor="#FFFFF0"><td>function name</td> <td>Position</td> <td>Description</td></tr>
        {foreach from=table}<tr bgcolor="#F0FFF0"><td colspan="3">hook_<b>{$table.1}</b>_</td></tr>{$table.0}{/foreach}
    </table>
{/IS}
{-IS}
    <div>No hooks</div>
{/-IS}

<p>
<form action="{$PHP_SELF}" method="POST">

    <input type="hidden" name="mod" value="hooks" />
    <input type="hidden" name="action" value="add-hook" />
    Hook name <input type="text" style="width: 400px;" name="hook" />
    <input type="submit" value="Add new hook" />

</form>
</p>