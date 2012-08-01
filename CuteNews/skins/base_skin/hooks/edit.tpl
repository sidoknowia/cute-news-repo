<p><b>Note:</b> use <b style="color: green;">global $var</b> to access external data</p>
<form action="{$PHP_SELF}" method="POST">

    <input type="hidden" name="mod" value="hooks">
    <input type="hidden" name="action" value="do-edit">
    <input type="hidden" name="hook" value="{$F}">
    <input type="hidden" name="order" value="{$O}">

    <div style="height: 20px"><div style="float: left; width: 100px;">Name</div> <b>{$F}</b></div>
    <div style="height: 20px"><div style="clear: left; float: left; width: 100px; padding: 4px 0 0 0">Order</div> <input type="text" name="ord_new" style="width: 40px;" value="{$O}"></div>
    <div style="height: 20px"><div style="clear: left; float: left; width: 100px; padding: 4px 0 0 0">Description</div> <input type="text" name="desc" style="width: 350px;" value="{$D}"></div>
    <br/> <div><textarea name="function" style="width: 640px; height: 480px;">{$T}</textarea></div> <br/>
    <input type="submit" value="Edit hook" />
</form>
