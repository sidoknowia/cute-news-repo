<div class="cn-main-head"><span>Cutenews has some errors</span></div>

<div class="main-content main-frm">

    <div class="ct-box error-box">
        <h2 class="warn hn"></h2>
        <ul class="error-list">
            <?php foreach ($cn_error_storage['global'] as $error) { ?>
            <li class="warn"><span><strong><?php echo $error['title']; ?></strong> <?php echo $error['msg']; ?></span></li>
            <? } ?>
        </ul>
    </div>

</div>


