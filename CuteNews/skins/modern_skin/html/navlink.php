<div class="cn-admin-notify">

    <div class="cn-logged-as">
        <?php

        if ($CN_USER_AUTH == FALSE) {
            ?>
                <div><span>You are not logged in.</span></div>
                <div><span>Please <b>login or register</b>.</span></div>
            <?php
        } else {
            ?><span>You are logged as <strong><?php echo $CN_USER_AUTH; ?></strong>.</span> <?php
        } ?>
    </div>

    <div class="cn-tools">
    <?php /* ?>
        <?php if ($CN_USER_AUTH) { ?>
        <span><a <?php echo trigger_get_key('q', 'profile', 'class="isactive"'); ?> href="<?php echo cn_path(); ?>?q=profile">My profile</a></span>
        <span>&nbsp;|&nbsp;</span>
        <span><a <?php echo trigger_get_key('q', 'messages', 'class="isactive"'); ?> href="<?php echo cn_path(); ?>?q=messages">New messages <strong>(0)</strong></a></span>
        <?php } ?>
    <?php */ ?></div>

</div>

<!-- Cutenews logo -->
<div class="cn-header">
    <a href="<?php echo cn_path('acp.php'); ?>"><img height="32" src="<?php echo cn_path('skins/images/cutenews-logo.png'); ?>" /></a>
</div>

<!-- Admin menu --->
<?php if ($CN_USER_AUTH) { ?>
<ul class="general-navigation">
    <li <?php echo trigger_get_key('q', array('', 'index'), 'class="isactive"'); ?>><a href="<?php echo cn_path('acp.php'); ?>?q=index">Home</a></li>
    <li <?php echo trigger_get_key('q', 'add', 'class="isactive"'); ?>><a href="<?php echo cn_path('acp.php'); ?>?q=add">Add News</a></li>
    <li <?php echo trigger_get_key('q', 'edit', 'class="isactive"'); ?>><a href="<?php echo cn_path('acp.php'); ?>?q=edit">Edit News</a></li>
    <li <?php echo trigger_get_key('q', 'options', 'class="isactive"'); ?>><a href="<?php echo cn_path('acp.php'); ?>?q=options">Options</a></li>
    <li <?php echo trigger_get_key('q', 'help', 'class="isactive"'); ?>><a href="<?php echo cn_path('acp.php'); ?>?q=help">Help/About</a></li>
    <li><a href="<?php echo cn_path('example.php'); ?>">Example</a></li>
    <li <?php echo trigger_get_key('q', 'db_admin', 'class="isactive"'); ?>><a href="<?php echo cn_path('acp.php'); ?>?q=db_admin">DBA</a></li>
    <li <?php echo trigger_get_key('q', 'logout', 'class="isactive"'); ?>><a href="<?php echo cn_path('acp.php'); ?>?q=logout">Logout</a></li>
</ul>
<?php } else { ?>
<ul class="general-navigation">
    <li class="isactive"><a href="<?php echo cn_path('acp.php'); ?>?q=index">ACP Login</a></li>
    <li><a href="<?php echo cn_path('example1.php'); ?>?q=index">Example 1</a></li>
    <li><a href="<?php echo cn_path('example2.php'); ?>?q=index">Example 2</a></li>
    <li><a href="<?php echo cn_path('example.php'); ?>?q=index">Example 3</a></li>
    <li><a href="<?php echo cn_path('index.php'); ?>">CN APanel</a></li>
    <li><a href="<?php echo cn_path('acp.php'); ?>?q=db_admin">DBA</a></li>
</ul>
<?php } ?>

<div class="clear"></div>