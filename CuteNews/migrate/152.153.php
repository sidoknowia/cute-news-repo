<?php

// CREATE REQUIRED DATABASES
/*
 * users (id,username,email,acl,nickname,passmd5,passwd,salt,session_id,count_news,hide_email,avatar,last_active,approved)
 * news (id,approved,published,edited,username,title,short,full,avatar,category) -- multi news
 * errors(type,error,file,line,backtrace)
 * ratings()
 * options(name,value)
 *
 */

define('INIT_INSTANCE', TRUE);
define('CNROOT', dirname( dirname(__FILE__).'html' ));
define('CNDATA', CNROOT.'/cdata');

// Require
require_once (CNROOT . '/core/core.php');
require_once (CNROOT . '/core/triggers/core.php');
require_once (CNROOT . '/core/functions.php');

// Connect via textsql to create tables
db_cn_connect('textsql');

$Tables = array
(
    'users' => 'id,username,email,acl,nickname,passwd,salt,session_id,count_news,hide_email,avatar,last_active,approved',
    'errors' => 'type,error,file,line,backtrace',
    'options' => 'name,value',
);

// Authorized only
$dba_username = 'Migration_Script';
foreach ($Tables as $table => $fields) db_query("CREATE TABLE $table WHERE $fields");

?>
<html>
<body>
    <h2>Migration from 1.5.2 to 1.5.3</h2>

    <div><a href="../acp.php">Return to ACP</a></div>
</body>
</html>