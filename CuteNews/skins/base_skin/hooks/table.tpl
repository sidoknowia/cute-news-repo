{foreach from=table}
<tr>
    <td style="padding: 0 0 0 16px;"><a href="{$PHP_SELF}?mod=hooks&amp;action=edit&amp;hook={$table.func}&amp;order={$table.order}">{$table.func}_{$table.order}</a> (<b><a onclick="return(confirm('Delete?'));" href="{$PHP_SELF}?mod=hooks&amp;action=remove&amp;hook={$table.func}&amp;order={$table.order}">x</a></b>)</td>
    <td>{$table.order}</td>
    <td>{$table.desc}</td>
</tr>
{/foreach}