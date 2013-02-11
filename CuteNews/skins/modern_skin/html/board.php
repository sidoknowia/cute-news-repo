<?php

global $MOD_INCLUDE;

// Errors
if (trigger_cn_error())
    include ($CN_SKIN.'/html/block.errors.php');

include ($CN_SKIN.'/html/template/breadcrumb.php');
?>
<!-- Show content -->
<div class="cn-dcontent"><?php include ($CN_SKIN.'/html/'.$MOD_INCLUDE.'.php'); ?></div>