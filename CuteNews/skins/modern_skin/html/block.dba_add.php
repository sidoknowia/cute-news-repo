<br/><hr/>

<h3>Make query</h3>

<form action="<?php $_SERVER['REQUEST_URI']; ?>" method="post">

    <input type="hidden" name="do" value="do_query" />
    <div><textarea name="query" style="width: 100%; height: 200px; border: 1px solid #808080;"><?php echo REQ('query','*POST'); ?></textarea></div>

    <div><input type="submit" value="Submit query" /></div>

</form>

<div><strong>TextSQL Cutenews syntax:</strong></div>
<div>
    <ul>
        <li>INSERT INTO [db_table] WHERE field0,field0,....,fieldN -- $args[0]...$args[N]</li>
        <li>SELECT FROM [db_table] WHERE cond1=$0 & cond2=$1 + cond3=$2, etc -- $args[0]...$args[2]</li>
        <li>UPDATE TABLE [db_table] SET f1,f2,...,fN WHERE cond1=$0 & cond2=$1 + cond3=$2, etc -- $args[0][0]=f1, ....</li>
        <li>DELETE FROM [db_table] WHERE cond1=$0 & cond2=$1 + cond3=$2, etc -- $args[0]...$args[2]</li>
    </ul>
</div>
