<div class="cn-main-head"> <span>Login to ACP Cutenews</span> </div>

<div class="main-content main-frm">

    <div class="content-head"><p class="hn">Enter login and password</p></div>
    <form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo cn_path('acp.php'); ?>">

        <div class="hidden">
            <input type="hidden" name="form_sent" value="1">
            <input type="hidden" name="action" value="do_login">
            <input type="hidden" name="csrf_token" value="<?php echo cn_csrf(); ?>">
        </div>
        <div class="frm-group group1">
            <div class="sf-set set1">
                <div class="sf-box text required">
                    <label for="fld1"><span>Username</span></label><br>
                    <span class="fld-input"><input type="text" id="fld1" name="req_username" value="" size="35" maxlength="25" required="" spellcheck="false"></span>
                </div>
            </div>
            <div class="sf-set set2">
                <div class="sf-box text required">
                    <label for="fld2"><span>Password</span></label><br>
                    <span class="fld-input"><input type="password" id="fld2" name="req_password" value="" size="35" required=""></span>
                </div>
            </div>
            <div class="sf-set set3">
                <div class="sf-box checkbox">
                    <span class="fld-input"><input type="checkbox" id="fld3" name="save_pass" value="1"></span>
                    <label for="fld3">Remember me</label>
                </div>
            </div>
        </div>

        <div class="frm-buttons"> <span class="submit primary"><input type="submit" name="login" value="Login"></span> </div>
    </form>
</div>