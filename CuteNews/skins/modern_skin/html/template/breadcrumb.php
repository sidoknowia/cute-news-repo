<?php

    global $CN_BREADCRUMBS;
    if (empty($CN_BREADCRUMBS))
        return null;

?>
<div class="cn-crumbs">

    <?php foreach ($CN_BREADCRUMBS as $key => $value ) { ?>
        <?php if ($key > 0) echo '<span> &rarr;&nbsp;</span>'; ?>
        <span class="crumb"><a href="<?php echo $value['href']; ?>"><?php echo $value['var']; ?></a></span>
    <?php } ?>

</div>