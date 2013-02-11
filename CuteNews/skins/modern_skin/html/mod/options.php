<div class="main_menu_wrapper">
    <div class="admin-menu gen-content">
        <ul>
            <li class="active first-item"><a href="?section=setup"><span>Settings</span></a></li>
            <li class="normal"><a href="/extensions.php?section=manage"><span>Plugins</span></a></li>
        </ul>
    </div>
</div>

<div class="admin-submenu gen-content">
    <ul>
        <li class="active first-item"><a href="#">General</a></li>
        <li class="normal"><a href="#">News</a></li>
        <li class="normal"><a href="#">Comments</a></li>
        <li class="normal"><a href="#">Notifications</a></li>
        <li class="normal"><a href="#">Social</a></li>
        <li class="normal"><a href="#">Censoring</a></li>
    </ul>
</div>

<div class="main-content main-frm">
    <form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo cn_path('acp.php'); ?>?q=">

        <div class="hidden">
            <input type="hidden" name="csrf_token" value="<?php echo cn_csrf(); ?>">
            <input type="hidden" name="form_sent" value="1">
        </div>

        <div class="content-head"> <h2 class="hn"><span>Common settings</span></h2> </div>
        <fieldset class="frm-group group1">

            <div class="sf-set set1">
                <div class="sf-box text">
                    <label for="fld1"><span>Full URL</span></label><br>
                    <span class="fld-input"><input type="text" id="fld1" name="form[board_title]" size="50" maxlength="255" value="My PunBB forum"></span>
                </div>
            </div>

            <div class="sf-set set2">
                <div class="sf-box text">
                    <label for="fld2"><span>Frontend default codepage</span></label><br>
                    <span class="fld-input"><input type="text" id="fld2" name="form[board_desc]" size="50" maxlength="255" value="Unfortunately no one can be told what PunBB is — you have to see it for yourself"></span>
                </div>
            </div>

            <div class="sf-set set3">
                <div class="sf-box select">
                    <label for="fld3"><span>Default style</span></label><br>
                    <span class="fld-input"><select id="fld3" name="form[default_style]">
                        <option value="Soft_Oxygen" selected="selected">Soft Oxygen</option>
                        <option value="Copper">Copper</option>
                        <option value="Hydrogen">Hydrogen</option>
                        <option value="Carbon">Carbon</option>
                        <option value="Artstyle">Artstyle</option>
                        <option value="Urban">Urban</option>
                        <option value="Oxygen">Oxygen</option>
                        <option value="Web20">Web20</option>
                    </select></span>
                </div>
            </div>
        </fieldset>
    </form>
</div>