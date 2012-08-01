<?PHP

if ($member_db[UDB_ACL] == ACL_LEVEL_COMMENTER and ($action != 'personal' and $action != 'options'))
    msg('error', 'Error!', 'Access Denied for your user-level (commenter)');

// ********************************************************************************
// Options Menu
// ********************************************************************************
if ($action == "options" or $action == '')
{
    echoheader("options", "Options", make_breadcrumbs('main/=options'));

    //----------------------------------
    // Predefine Options
    //----------------------------------

    // access means the lower level of user allowed; 1:admin, 2:editor+admin, 3:editor+admin+journalist, 4:all
    $options = array
    (
        array(
               'name'               => lang("Personal Options"),
               'url'                => "$PHP_SELF?mod=options&action=personal",
               'access'             => "4",
        ),
        array(
               'name'               => lang("Block IP's from posting comments"),
               'url'                => "$PHP_SELF?mod=ipban",
               'access'             => "1",
        ),
        array(
               'name'               => lang("System Configurations"),
               'url'                => "$PHP_SELF?mod=options&action=syscon&rand=".time(),
               'access'             => "1",
        ),
        array(
               'name'               => lang("Integration and Migration Wizards"),
               'url'                => "$PHP_SELF?mod=wizards",
               'access'             => "1",
        ),
        array(
               'name'               => lang("Edit Templates"),
               'url'                => "$PHP_SELF?mod=options&action=templates",
               'access'             => "1",
        ),
        array(
               'name'               => lang("Add/Edit Users"),
               'url'                => "$PHP_SELF?mod=editusers&action=list",
               'access'             => "1",
        ),
        array(
               'name'               => lang("Archives Manager"),
               'url'                => "$PHP_SELF?mod=tools&action=archive",
               'access'             => "1",
        ),
        array(
               'name'               => lang("Manage Uploaded Images"),
               'url'                => "$PHP_SELF?mod=images",
               'access'             => "1",
        ),
        array(
               'name'               => lang("Backup Tool"),
               'url'                => "$PHP_SELF?mod=tools&action=backup",
               'access'             => "1",
        ),
        array(
               'name'               => lang("Edit Categories"),
               'url'                => "$PHP_SELF?mod=categories",
               'access'             => "1",
        ),

        array(
               'name'               => lang("Report bug/error"),
               'url'                => "$PHP_SELF?mod=tools&action=report",
               'access'             => "1",
        ),
        array(
               'name'               => lang("User logs"),
               'url'                => "$PHP_SELF?mod=tools&action=userlog",
               'access'             => "1",
        ),
        array(
               'name'               => lang("Hooks and plugins"),
               'url'                => "$PHP_SELF?mod=hooks",
               'access'             => "1",
        ),
        array(
               'name'               => lang("Word replacement"),
               'url'                => "$PHP_SELF?mod=tools&action=replaces",
               'access'             => "1",
        ),
        array(
               'name'               => lang("Additional fields"),
               'url'                => "$PHP_SELF?mod=tools&action=xfields",
               'access'             => "1",
        ),
        /* array(
               'name'               => "Language",
               'url'                => "$PHP_SELF?mod=tools&action=language",
               'access'             => "1",
        ), */
    );

    hook('more_options');

    //------------------------------------------------
    // Cut the options for wich we don't have access
    //------------------------------------------------
    $count_options = count($options);
    for($i=0; $i<$count_options; $i++)
    {
        if($member_db[1] > $options[$i]['access']) unset($options[$i]);
    }

    echo '<table border="0" width="100%"><tr>';
    
    $i = 0;
    foreach ($options as $option)
    {
        if ($i%2 == 0) echo "</tr>\n<tr>\n<td width='47%'>&nbsp;&nbsp;&nbsp;<a href='".$option['url']."'><b>".$option['name']."</b></a></td>\n";
        else echo"\n<td width='53%'><a href='".$option['url']."'><b>".$option['name']."</b></a></td>\n"; 
        $i++;
    }

    echo'</tr></table>';
    echofooter();
}
// ********************************************************************************
// Show Personal Options
// ********************************************************************************
elseif($action == "personal")
{
    echoheader("user", "Personal Options");

    foreach($member_db as $key => $value)
        $member_db[$key]  = stripslashes(preg_replace(array("'\"'", "'\''"), array("&quot;", "&#039;"), $member_db[$key]));

    // define access level
    $access_level = array(ACL_LEVEL_ADMIN       => 'administrator', ACL_LEVEL_EDITOR    => 'editor',
                          ACL_LEVEL_JOURNALIST  => 'journalist',    ACL_LEVEL_COMMENTER => 'commenter');

    echo proc_tpl('options/personal',
                  array(
                      'member_db[2]' => $member_db[UDB_NAME],
                      'member_db[4]' => $member_db[UDB_NICK],
                      'member_db[5]' => $member_db[UDB_EMAIL],
                      'member_db[6]' => $member_db[UDB_COUNT],
                      'member_db[8]' => $member_db[UDB_AVATAR],
                      'ifchecked'    => ($member_db[UDB_CBYEMAIL] == 1)? "checked" : false, // if user wants to hide his e-mail
                      'access_level' => $access_level[ $member_db[UDB_ACL] ],
                      'registrationdate' => date("D, d F Y", $member_db[0]), // registration date
                      'bg' => $member_db[UDB_ACL] < ACL_LEVEL_COMMENTER? "bgcolor=#F7F6F4" : false,
                  ),
                  array('NOTCOMMENTER' => $member_db[UDB_ACL] < ACL_LEVEL_COMMENTER)
    );

    echofooter();
}
// ********************************************************************************
// Save Personal Options
// ********************************************************************************
elseif($action == "dosavepersonal")
{

    $editnickname       = replace_comment("add", $editnickname);
    $editmail           = replace_comment("add", $editmail);
    $edithidemail       = replace_comment("add", $edithidemail);
    $change_avatar      = replace_comment("add", $change_avatar);

    if ($editpassword and !preg_match("/^[\.A-z0-9_\-]{1,31}$/i", $editpassword))
        msg("error", LANG_ERROR_TITLE, lang("Your password must contain only valid characters and numbers"));

    $edithidemail = $edithidemail? 1 : 0;
    $avatars = preg_replace(array("'\|'","'\n'","' '"), array("","","_"), $avatars);

    $pack = bsearch_key($username, DB_USERS);

    // editing password (with confirm)
    if ($editpassword)
    {
        if ($confirmpassword == $editpassword)
        {
            $hashs          = hash_generate($editpassword);
            $pack[UDB_PASS] = $hashs[ count($hashs) - 1 ];
            $_SESS['pwd']   = $editpassword;
        }
        else msg('error', LANG_ERROR_TITLE, lang('Confirm password not match'), "$PHP_SELF?mod=options&action=personal");
    }

    $pack[UDB_NICK]         = $editnickname;
    $pack[UDB_EMAIL]        = $editmail;
    $pack[UDB_CBYEMAIL]     = $edithidemail;
    $pack[UDB_AVATAR]       = $change_avatar;
    edit_key($username, $pack, DB_USERS);

    msg("info", lang("Changes Saved"), lang("Your personal information was saved"), "$PHP_SELF?mod=options&action=personal");
    
}
// ********************************************************************************
// Edit Templates
// ********************************************************************************
elseif($action == "templates")
{
    if($member_db[1] != 1){ msg("error", lang("Access Denied"), lang("You don't have permissions for this type of action")); }
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Detect all template packs we have
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    $templates_list = array();
    if (!$handle = opendir(SERVDIR."/cdata")) die("Can not open directory ".SERVDIR."/cdata ");
    while (false !== ($file = readdir($handle)))
    {
        if(preg_replace('/^.*\.(.*?)$/', '\\1', $file) == 'tpl'){
        $file_arr                 = explode(".", $file);
        $templates_list[]= $file_arr[0];
        }
    }
    closedir($handle);

    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
      If we want to create new template
     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    if ($subaction == "new")
    {
        echoheader("options", "New Template");
        echo "<form method=post action=\"$PHP_SELF\"><table border=0 cellpading=0 cellspacing=0 width=100% height=100%><tr><td >Create new template based on: <select name=base_template>";
        foreach($templates_list as $single_template)
        {
            echo "<option value=\"$single_template\">$single_template</option>";
        }
        echo '</select> with name <input type=text name=template_name> &nbsp;<input type=submit value="'.lang('Create Template').'">
        <input type=hidden name=mod value=options>
        <input type=hidden name=action value=templates>
        <input type=hidden name=subaction value=donew>
        </td></tr></table></form>';
        echofooter();
        exit_cookie();
    }
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
      Do Create the new template
     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    if ($subaction == "donew")
    {
        if (!preg_match('/^[a-z0-9_-]+$/i', $template_name))
        {
            msg("error", LANG_ERROR_TITLE, lang("The name of the template must be only with letters and numbers"), "$PHP_SELF?mod=options&subaction=new&action=templates");
        }

        if(file_exists(SERVDIR."/cdata/$template_name.tpl"))
            msg("error", LANG_ERROR_TITLE, lang("Template with this name already exists"), "$PHP_SELF?mod=options&subaction=new&action=templates");

        if ( $base_template )
             $base_file = SERVDIR."/cdata/$base_template.tpl";
        else $base_file = SERVDIR."/cdata/Default.tpl";

        if (!copy($base_file, SERVDIR."/cdata/$template_name.tpl"))
        {
            msg("error", LANG_ERROR_TITLE, str_replace('%1', $base_file, lang("Can not copy file %1 to ./cdata/ folder with name "))."$template_name.tpl");
        }

        chmod(SERVDIR."/cdata/$template_name.tpl", 0666);

        msg("info", lang("Template Created"), lang("A new template was created with name")." <b>$template_name</b><br>", "$PHP_SELF?mod=options&action=templates");
    }
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
      Deleting template, preparation
     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
        if($subaction == "delete")
        {
            if (strtolower($do_template) == "default")
                msg("Error",  LANG_ERROR_TITLE, lang("You can not delete the default template"), "$PHP_SELF?mod=options&action=templates");

            if (strtolower($do_template) == "rss")
                msg("Error", LANG_ERROR_TITLE, lang("You can not delete the RSS template, it is not even supposed you to edit it"), "$PHP_SELF?mod=options&action=templates");

        $msg = "<form method=post action=\"$PHP_SELF\">".lang('Are you sure you want to delete the template')." <b>$do_template</b> ?<br><br>
        <input type=submit value=\" ".lang('Yes, Delete This Template')."\"> &nbsp;
        <input onClick=\"document.location='$PHP_SELF?mod=options&action=templates';\" type=button value=\"".lang('Cancel')."\">
        <input type=hidden name=mod value=options>
        <input type=hidden name=action value=templates>
        <input type=hidden name=subaction value=dodelete>
        <input type=hidden name=do_template value=\"$do_template\">
        </form>";

        msg("info", lang("Deleting Template"), $msg);
    }
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
      DO Deleting template
     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    if($subaction == "dodelete")
    {
        if(strtolower($do_template) == "default")
            msg("Error", LANG_ERROR_TITLE, lang("You can not delete the default template"), "$PHP_SELF?mod=options&action=templates");

        $unlink = unlink(SERVDIR."/cdata/$do_template.tpl");
        if ( !$unlink )
             msg("error", LANG_ERROR_TITLE, "Can not delete file ./cdata/$do_template.tpl <br>maybe the is no permission from the server");
        else msg("info",  lang("Template Deleted"), str_replace('%1', $do_template, lang("The template <b>%1</b> was deleted.")), "$PHP_SELF?mod=options&action=templates");
    }

    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
      Show The Template Manager
     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    if ($do_template == '' or !$do_template)
    {
        $do_template = 'Default';
        $show_delete_link = '';
    }
    elseif (strtolower($do_template) != 'default')
    {
        $show_delete_link = "<a href=\"$PHP_SELF?action=templates&mod=options&subaction=delete&do_template=$do_template\">[".lang('delete this template')."]</a>";
    }
    
    require(SERVDIR."/cdata/$do_template.tpl");

    if (strpos($HTTP_USER_AGENT, 'opera') !== false)
         $tr_hidden = "";
    else $tr_hidden = " style='display:none'";


    $templates_names = array("template_active", "template_comment", "template_form", "template_full", "template_prev_next", "template_comments_prev_next");
    foreach($templates_names as $template)
    {
        $$template = preg_replace("/</","&lt;",$$template);
        $$template = preg_replace("/>/","&gt;",$$template);
    }
    echoheader("options","Templates");

    echo'<table border=0 cellpading=0 cellspacing=0 height="77" >
    <tr>
        <td width=373 height="75">
        <b>Manage Templates</b>

        <table border=0 cellpading=0 cellspacing=0 width=347  class="panel" height="50" >
    <form method=get action="'.$PHP_SELF.'">
    <tr>
    <td width=126height="23"> &nbsp;Editing Template <td width=225height="23"> :&nbsp; <b>'.$do_template.'</b>
    </tr>
    <tr>
        <td width=126 height="27"> &nbsp;Switch to Template
        <td width=225 height="27"> :&nbsp; <select size=1 name=do_template>';

    foreach($templates_list as $single_template)
    {
            if($single_template == $do_template){ echo"<option selected value=\"$single_template\">$single_template</option>"; }
        else{ echo"<option value=\"$single_template\">$single_template</option>"; }
    }

    echo'</select>
    <input type=submit value=Go>
    </tr>
    <tr>
        <td width=351 height="25" colspan="2">
    &nbsp;<a href="'.$PHP_SELF.'?mod=options&subaction=new&action=templates">[create new template]</a>&nbsp;
    '.$show_delete_link.'</tr>
        <input type=hidden name=action value=templates><input type=hidden name=mod value=options>
        </form>
        </table>

        <td width=268 height="75" align="center">
  <!-- HELP -->
   <table cellspacing="0" cellpadding="0">
    <tr>
      <td width="25" align=middle><img border="0" src="skins/images/help_small.gif"></td>
      <td >&nbsp;<a onClick="javascript:Help(\'templates\')" href="#">Understanding Templates</a></td>
    </tr>
   </table>
  <!-- END HELP -->

    </tr>
        </table>
    <img height=20 border=0 src="skins/images/blank.gif" width=1>
    <br>
    <b>Edit Template Parts</b><table width="100%"><form method=post action="'.$PHP_SELF.'">

<tr> <!- start active news -->
    <td height="7"  bgcolor=#F7F6F4 colspan="2">
    <b><a style="font-size:16px" href="javascript:ShowOrHide(\'active-news1\',\'active-news2\')" >Active News</a></b>
    </tr>
    <tr id=\'active-news1\' '.$tr_hidden.'>
    <td height="9" width="200" valign="top">
    <b>{title}<br />
    {avatar}<br />
    {short-story}<br />
    {full-story}<br />
    {author}<br />
    {author-name}<br />
    [mail] </b>and<b> [/mail]<br />
    {date}<br />
    [link] </b>and<b> [/link]<br />
    [full-link] </b>and<b> [/full-link]<br />
    [com-link] </b>and<b> [/com-link]<br />
    {comments-num}<br />
    {category}<br />
    {category-icon}<br />
    {star-rate}<br />
    <td height="9"  valign="top" width=430>
    - Title of the article<br />
    - Show Avatar image (if any)<br />
    - Short story of news item<br />
    - The full story<br />
    - Author of the article, with link to his email (if any)<br />
    - The name of the author, without email<br />
    - Will generate a link to the author mail (if any) eg. [mail]Email[/mail]<br />
    - Date when the story is written<br />
    - Will generate a permanent link to the full story<br />
    - Link to the full story of article, only if there is full story<br />
    - Generate link to the comments of article<br />
    - This will display the number of comments posted for article<br />
    - Name of the category where article is posted (if any)<br />
    - Shows the category icon (if any)<br />
    - Rating bar<br />
    </tr>
    <tr id=\'active-news2\' '.$tr_hidden.'>
    <td height="8"  colspan="2">
    <textarea rows="9" cols="98" name="edit_active">'.$template_active.'</textarea>
    <br />
    &nbsp;
</tr> <!-- End active news -->

<tr> <!-- Start full story -->
    <td height="7"  bgcolor=#F7F6F4 colspan="2">
    <b><a style="font-size:16px" href="javascript:ShowOrHide(\'full-story1\',\'full-story2\')" >Full Story</a></b>
    </tr>
    <tr id=\'full-story1\' '.$tr_hidden.'>
    <td height="9" width="200" valign="top">
    <b> {title}<br />
    {avatar}<br />
    {full-story}<br />
    {short-story}</b><b><br />
    {author}<br />
    {author-name}<br />
    [mail] </b>and<b> [/mail]<br />
    {date}<br />
    {comments-num}<br />
    {category}    <br />
    {category-icon}<br />
    {back-previous}<br />
    {index-link}<br /><br>
    {star-rate}<br />
    </b>
    <td height="9"  valign="top">
    - Title of the article<br />
    - Show Avatar image (if any)<br />
    - The full story<br />
    - Short story of news item<br />
    - Author of the article, with link to his email (if any)<br />
    - The name of the author, without email<br />
    - Will generate a link to the author mail (if any) eg. [mail]Email[/mail]<br />
    - Date when the story is written<br />
    - This will display the number of comments posted for article<br />
    - Name of the category where article is posted (if any)<br />
    - Shows the category icon (if any)<br />
    - Insert back link</br>
    - Insert direct link to main page <br>&nbsp;&nbsp;You may use <a href="'.PHP_SELF.'?mod=tools&amp;action=xfields" target="_blank">custom fields</a> there. Syntax: {your_field}</br>
    - Rating bar<br />
    </tr>
    <tr id=\'full-story2\' '.$tr_hidden.'>
    <td height="8"  colspan="2">
    <textarea rows="9" cols="98" name="edit_full">'.$template_full.'</textarea>
    <br />
    &nbsp;
</tr> <!-- End full story -->

<tr> <!-- Start comment -->
    <td height="7"  bgcolor=#F7F6F4 colspan="2">
    <b><a style="font-size:16px" href="javascript:ShowOrHide(\'comment1\',\'comment2\')" >Comment</a></b>
    </tr>
    <tr id=\'comment1\' '.$tr_hidden.'>
    <td height="9" width="200" valign="top">
    <b>  {author}<br />
    {mail}<br />
    {date}<br />
    {comment}<br />
    {comment-iteration}</b>
    <td height="9"  valign="top">
    - Name of the comment poster<br />
    - E-mail of the poster<br />
    - Date when the comment was posted<br />
    - The Comment<br />
    - Show the sequential number of individual comment
    </tr>
    <tr id=\'comment2\' '.$tr_hidden.'>
    <td height="8"  colspan="2">
    <textarea rows="9" cols="98" name="edit_comment">'.$template_comment.'</textarea>
    <br />
    &nbsp;
</tr> <!-- End comment -->

<tr> <!-- Start add comment form -->
    <td height="7"  bgcolor=#F7F6F4 colspan="2">
    <b><a style="font-size:16px" href="javascript:ShowOrHide(\'add-comment-form1\',\'add-comment-form2\')" >Add comment form</a></b>
    </tr>
    <tr id=\'add-comment-form1\' '.$tr_hidden.'>
    <td height="9" width="1094" valign="top" colspan="2">
    Please do not edit this unless you have basic HTML knowledge !!!
    </tr>
    <tr id=\'add-comment-form2\' '.$tr_hidden.'>
    <td height="8"  colspan="2">
    <textarea rows="9" cols="98" name="edit_form">'.$template_form.'</textarea>
    <br />
    &nbsp;
</tr> <!-- End add comment form -->

<tr> <!-- Start previous & next -->
    <td height="7"  bgcolor=#F7F6F4 colspan="2">
    <b><a style="font-size:16px" href="javascript:ShowOrHide(\'previous-next1\',\'previous-next2\')" >News Pagination</a></b>
    </tr>
    <tr id=\'previous-next1\' '.$tr_hidden.'>
    <td height="9" width="200" valign="top">
    <b> [prev-link] </b>and<b> [/prev-link]<br />
    [next-link] </b>and<b> [/next-link]<br />
        {pages}<br />
    <td height="9"  valign="top">
    - Will generate a link to preveous page (if there is)<br />
    - Will generate a link to next page (if there is)<br />
    - Shows linked numbers of the pages; example: <a href=\'#\'>1</a> <a href=\'#\'>2</a> <a href=\'#\'>3</a> <a href=\'#\'>4</a>
    </tr>

    <tr id=\'previous-next2\' '.$tr_hidden.'>
    <td height="8"  colspan="2">
    <textarea rows="3" cols="98" name="edit_prev_next">'.$template_prev_next.'</textarea>
</tr> <!-- End previous & next -->



<tr> <!-- Start previous & next COMMENTS-->
    <td height="7"  bgcolor=#F7F6F4 colspan="2">
    <b><a style="font-size:16px" href="javascript:ShowOrHide(\'previous-next21\',\'previous-next22\')" >Comments Pagination</a></b>
    </tr>
    <tr id=\'previous-next21\' '.$tr_hidden.'>
    <td height="9" width="200" valign="top">
    <b> [prev-link] </b>and<b> [/prev-link]<br />
    [next-link] </b>and<b> [/next-link]<br />
        {pages}<br />
    <td height="9"  valign="top">
    - Will generate a link to preveous page (if there is)<br />
    - Will generate a link to next page (if there is)<br />
    - Shows linked numbers of the pages; example: <a href=\'#\'>1</a> <a href=\'#\'>2</a> <a href=\'#\'>3</a> <a href=\'#\'>4</a>
    </tr>

    <tr id=\'previous-next22\' '.$tr_hidden.'>
    <td height="8"  colspan="2">
    <textarea rows="3" cols="98" name="edit_comments_prev_next">'.$template_comments_prev_next.'</textarea>
</tr> <!-- End previous & next COMMENTS -->


<tr> <!-- Customize search form -->
    <td height="7"  bgcolor=#F7F6F4 colspan="2">
    <b><a style="font-size:16px" href="javascript:ShowOrHide(\'custom-search\',\'custom-search2\')" >Customize Search</a></b>
    </tr>
    <tr id=\'custom-search\' '.$tr_hidden.'>
        
    </tr>

    <tr id=\'custom-search2\' '.$tr_hidden.'>
    <td height="8"  colspan="2">
    <textarea rows="20" cols="98" name="edit_search">'.$template_search.'</textarea>
    </tr> <!-- End customize search form -->


<tr>
    <td height="8"  colspan="2">
    <input type=hidden name=mod value=options>
    <input type=hidden name=action value=dosavetemplates>
    <input type=hidden name=do_template value="'.$do_template.'">
    <br /><input type=submit value="   Save Changes   " accesskey="s">
    </tr></form>
    </table>';

        echofooter();
}
// ********************************************************************************
// Do Save Changes to Templates
// ********************************************************************************
elseif($action == "dosavetemplates")
{
    if($member_db[1] != 1)
        msg("error", lang("Access Denied"), lang("You don't have permissions for this type of action"));

    $templates_names = array("edit_active", "edit_comment", "edit_form", "edit_full", "edit_prev_next", "edit_comments_prev_next","edit_search");
    foreach($templates_names as $template)
        $$template = stripslashes($$template);

    if($do_template == "" or !$do_template){ $do_template = "Default"; }
    $template_file = SERVDIR."/cdata/$do_template.tpl";

    if($do_template == "rss"){ $edit_active = str_replace("&", "&amp;",$edit_active); }

    $handle = fopen("$template_file","w");
    fwrite($handle, "<?PHP\n///////////////////// TEMPLATE $do_template /////////////////////\n");
    fwrite($handle, "\$template_active = <<<HTML\n$edit_active\nHTML;\n\n\n");
    fwrite($handle, "\$template_full = <<<HTML\n$edit_full\nHTML;\n\n\n");
    fwrite($handle, "\$template_comment = <<<HTML\n$edit_comment\nHTML;\n\n\n");
    fwrite($handle, "\$template_form = <<<HTML\n$edit_form\nHTML;\n\n\n");
    fwrite($handle, "\$template_prev_next = <<<HTML\n$edit_prev_next\nHTML;\n");
    fwrite($handle, "\$template_comments_prev_next = <<<HTML\n$edit_comments_prev_next\nHTML;\n");
    fwrite($handle, "\$template_search = <<<HTML\n$edit_search\nHTML;\n");
    fwrite($handle, "?>\n");

   msg("info", lang("Changes Saved"), "The changes to template <b>$do_template</b> were successfully saved.","$PHP_SELF?mod=options&action=templates&do_template=$do_template");
}

// ********************************************************************************
// System Configuration
// ********************************************************************************
elseif($action == "syscon")
{
    if ($member_db[UDB_ACL] != ACL_LEVEL_ADMIN)
        msg("error", lang("Access Denied"), lang("You don't have permissions to access this section"));

    $bc = 'main/options/options:syscon=config';
    if (isset($_REQUEST['message'])) $bc .= '/='.lang('Your Configuration Saved');

    echoheader("options", lang("System Configuration"), make_breadcrumbs($bc));

    function showRow($title="", $description="", $field="")
    {
        global $i;
        if ( $i%2 == 0 and $title != "") $bg = "bgcolor=#F7F6F4";
        echo "<tr $bg >
                <td colspan=\"2\" style=\"padding:4\">
                &nbsp;<b>$title</b>
                <td width=294 rowspan=\"2\" valign=\"middle\" align=middle>
                $field<br />&nbsp;
                </tr>
                <tr $bg >
        <td height=15 width=\"27\" style=\"padding:4\">
        &nbsp;
        <td height=15 width=\"299\" valign=top>
        <span style='color:#808080;'>$description</span>
        </tr>";
        $bg = ""; $i++;
    }
    function makeDropDown($options, $name, $selected)
    {
        $output = "<select size=1 name=\"$name\">\r\n";
        foreach($options as $value=>$description)
        {
            $output .= "<option value=\"$value\"";
            if ($selected == $value) $output .= " selected ";
            $output .= ">$description</option>\n";
        }
        $output .= "</select>";
        return $output;
    }

    echo"<table border=0 cellpading=0 cellspacing=0 width=654  ><form action=\"$PHP_SELF\" method=post>";

echo <<<HTML
<script language='JavaScript' type="text/javascript">

        function ChangeOption(selectedOption) {

                document.getElementById('general').style.display = "none";
                document.getElementById('news').style.display = "none";
                document.getElementById('comments').style.display = "none";
                document.getElementById('notifications').style.display = "none";
                document.getElementById('facebook').style.display = "none";

                document.getElementById('button1').style.backgroundColor = "";
                document.getElementById('button2').style.backgroundColor = "";
                document.getElementById('button3').style.backgroundColor = "";
                document.getElementById('button4').style.backgroundColor = "";
                document.getElementById('button5').style.backgroundColor = "";

                document.getElementById('currentid').value = selectedOption;

                var SelectedButton = 'button1';
                if(selectedOption == 'general') {document.getElementById('general').style.display = ""; SelectedButton='button1';}
                if(selectedOption == 'news') {document.getElementById('news').style.display = ""; SelectedButton='button2';}
                if(selectedOption == 'comments') {document.getElementById('comments').style.display = ""; SelectedButton='button3';}
                if(selectedOption == 'notifications') {document.getElementById('notifications').style.display = ""; SelectedButton='button4';}
                if(selectedOption == 'facebook') {document.getElementById('facebook').style.display = ""; SelectedButton='button5';}

                document.getElementById(SelectedButton).style.backgroundColor = "#EBE8E2";
       }

</script>

<tr style="position:relative" valign=top>
<td style="padding-bottom:30px;" colspan="3"  >
<table style="text-align:center;  padding:0px; margin:0px;" width="100%" height=35px cellpadding="0" cellspacing="0">
<tr style="border:1px solid black; vertical-align:middle;" >
 <td id="button1" style="background-color:#EBE8E2; border:1px solid black; border-radius: .7em .7em .0em .0em" width="20%"><a style="display:block; font-size:150%; font-weight:bold; height:100%; padding-top:10px;" href="javascript:ChangeOption('general');">General</a>
 <td id="button2" style="border:1px solid black; border-radius: .7em .7em .0em .0em" width="20%"><a style="display:block; font-size:150%; font-weight:bold; height:100%; padding-top:10px;" href="javascript:ChangeOption('news');">News</a>
 <td id="button3" style="border:1px solid black; border-radius: .7em .7em .0em .0em" width="20%"><a style="display:block; font-size:150%; font-weight:bold; height:100%; padding-top:10px;" href="javascript:ChangeOption('comments');">Comments</a>
 <td id="button4" style="border:1px solid black; border-radius: .7em .7em .0em .0em" width="20%"><a style="display:block; font-size:150%; font-weight:bold; height:100%; padding-top:10px;" href="javascript:ChangeOption('notifications');">Notifications</a>
 <td id="button5" style="border:1px solid black; border-radius: .7em .7em .0em .0em" width="20%"><a style="display:block; font-size:150%; font-weight:bold; height:100%; padding-top:10px;" href="javascript:ChangeOption('facebook');">Facebook</a>
 </tr>
</table>
</tr>
HTML;

    if (!$handle = opendir(SERVDIR."/skins"))
    {
        die_stat(false, "Can not open directory ./skins ");
    }
    while (false !== ($file = readdir($handle)))
    {
        $file_arr = explode(".",$file);
        if($file_arr[1] == "skin")
        {
            $sys_con_skins_arr[$file_arr[0]] = $file_arr[0];
        }
        elseif($file_arr[1] == "lang")
        {
            $sys_con_langs_arr[$file_arr[0]] = $file_arr[0];
        }
    }
    closedir($handle);

    // News
    if ( is_dir(SERVDIR.'/core/ckeditor') )
         $ckeditorEnabled = makeDropDown(array("no"=>"No",'ckeditor'=>'CKEditor'), "save_con[use_wysiwyg]", $config_use_wysiwyg);
    else $ckeditorEnabled = makeDropDown(array("no"=>"No"), "save_con[use_wysiwyg]", $config_use_wysiwyg);

    // General
    echo"<tr style='' id=general width=100%><td colspan=10 width=100%><table cellpading=0 cellspacing=0 colspan=10 width=100%>";

    showRow(lang("Full URL to CuteNews Directory"), lang("example: http://yoursite.com/cutenews"),                      "<input type=text style=\"text-align: center;\" name='save_con[http_script_dir]' value='$config_http_script_dir' size=40>");
    showRow(lang("Frontend default codepage"),      lang("for example: windows-1251, utf-8, koi8-r etc"),               "<input type=text style=\"text-align: center;\" name='save_con[default_charset]' value='$config_default_charset' size=40>");
    showRow(lang("CuteNews Skin"),                  lang("you can download more from our website"),                     makeDropDown($sys_con_skins_arr, "save_con[skin]", $config_skin));
    showRow(lang("Use WYSIWYG Editor"),             lang("use (or not) the advanced editor"), $ckeditorEnabled);
    showRow(lang("Time Adjustment"),                lang("in minutes; eg. : <b>180</b>=+3 hours; <b>-120</b>=-2 hours"),"<input type=text style=\"text-align: center;\" name='save_con[date_adjust]' value=\"$config_date_adjust\" size=10>");
    showRow(lang("Smilies"),                        lang("Separate them with commas (<b>,</b>)"),                       "<input type=text style=\"text-align: center;\" name='save_con[smilies]' value=\"$config_smilies\" size=40>");
    showRow(lang("Auto-Archive every Month"),       lang("if yes, evrery month the active news will be archived"),      makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[auto_archive]", "$config_auto_archive"));
    showRow(lang("Allow Self-Registration"),        lang("allow users to auto-register"),                               makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[allow_registration]", "$config_allow_registration"));
    showRow(lang("Self-Registration Level"),        lang("with what access level are users auto-registred?"),           makeDropDown(array("3"=>"Journalist","4"=>"Commenter"), "save_con[registration_level]", "$config_registration_level"));
    showRow(lang("Use captcha"),                    lang("on registration and comments"),                               makeDropDown(array("0"=>"No","1"=>"Yes"), "save_con[use_captcha]", $config_use_captcha));
    showRow(lang("Use rating"),                     lang("is internal CuteNews rating system"),                         makeDropDown(array("0"=>"No","1"=>"Yes"), "save_con[use_rater]", $config_use_rater));
    if ($config_use_rater)
    {
        showRow(lang("Rate symbol 1"), lang("rate full symbol"),   "<input type=text style=\"text-align: center;\"  name='save_con[ratey]' value=\"$config_ratey\" size=10>", "save_con[ratey]", $config_ratey);
        showRow(lang("Rate symbol 2"), lang("rate empty symbol"),  "<input type=text style=\"text-align: center;\"  name='save_con[raten]' value=\"$config_raten\" size=10>", "save_con[raten]", $config_raten);
    }

    echo"</table></td></tr>";

    echo"<tr style='display:none' id=news width=100%><td colspan=10 width=100%><table cellpading=0 cellspacing=0 colspan=10 width=100%>";
    showRow(lang("Use Avatars"),                            lang("if not, the avatar URL field wont be shown"),     makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[use_avatar]", $config_use_avatar));
    showRow(lang("Reverse News"),                           lang("if yes, older news will be shown on the top"),    makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[reverse_active]", $config_reverse_active));
    showRow(lang("Show Full Story In PopUp"),               lang("full Story will be opened in PopUp window"),      makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[full_popup]", "$config_full_popup"));
    showRow(lang("Settings for Full Story PopUp"),          lang("only if 'Show Full Story In PopUp' is enabled"),  "<input type=text style=\"text-align: center;\"  name='save_con[full_popup_string]' value=\"$config_full_popup_string\" size=40>");
    showRow(lang("Show Comments When Showing Full Story"),  lang("if yes, comments will be shown under the story"), makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[show_comments_with_full]", "$config_show_comments_with_full"));
    showRow(lang("Time Format For News"),                   lang("view help for time formatting <a href=\"http://www.php.net/manual/en/function.date.php\" target=\"_blank\">here</a>"), "<input type=text style=\"text-align: center;\"  name='save_con[timestamp_active]' value='$config_timestamp_active' size=40>");
    echo"</table></td></tr>";

    // Comments
    echo "<tr style='display:none' id=comments width=100%><td colspan=10 width=100%><table cellpading=0 cellspacing=0 colspan=10 width=100%>";
    showRow(lang("Auto Wrap Comments"),                         lang("any word that is longer than this will be wrapped"),          "<input type=text style=\"text-align: center;\"  name='save_con[auto_wrap]' value=\"$config_auto_wrap\" size=10>");
    showRow(lang("Reverse Comments"),                           lang("if yes, newest comments will be shown on the top"),           makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[reverse_comments]", "$config_reverse_comments"));
    showRow(lang("Comments Flood Protection"),                  lang("in seconds; 0 = no protection"),                              "<input type=text style=\"text-align: center;\"  name='save_con[flood_time]' value=\"$config_flood_time\" size=10>");
    showRow(lang("Max. Length of Comments in Characters"),      lang("enter <b>0</b> to disable checking"),                         "<input type=text style=\"text-align: center;\"  name='save_con[comment_max_long]' value='$config_comment_max_long' size=10>");
    showRow(lang("Comments Per Page (Pagination)"),             lang("enter <b>0</b> or leave empty to disable pagination"),        "<input type=text style=\"text-align: center;\"  name='save_con[comments_per_page]' value='$config_comments_per_page' size=10>");
    showRow(lang("Only Registered Users Can Post Comments"),    lang("if yes, only registered users can post comments"),            makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[only_registered_comment]", "$config_only_registered_comment"));
    showRow(lang("Allow Mail Field to Act and as URL Field"),   lang("visitors will be able to put their site URL insted of mail"), makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[allow_url_instead_mail]", "$config_allow_url_instead_mail"));
    showRow(lang("Show Comments In PopUp"),                     lang("comments will be opened in PopUp window"),                    makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[comments_popup]", $config_comments_popup));
    showRow(lang("Settings for Comments PopUp"),                lang("only if 'Show Comments In PopUp' is enabled"),                "<input type=text style=\"text-align: center;\"  name=\"save_con[comments_popup_string]\" value=\"$config_comments_popup_string\" size=40>");
    showRow(lang("Show Full Story When Showing Comments"),      lang("if yes, comments will be shown under the story"),             makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[show_full_with_comments]", $config_show_full_with_comments));
    showRow(lang("Time Format For Comments"),                   lang("view help for time formatting <a href=\"http://www.php.net/manual/en/function.date.php\" target=\"_blank\">here</a>"), "<input type=text style=\"text-align: center;\"  name='save_con[timestamp_comment]' value='$config_timestamp_comment' size=40>");
    echo"</table></td></tr>";

    // Notifications
    echo "<tr style='display:none' id=notifications width=100%><td colspan=10 width=100%><table cellpading=0 cellspacing=0 colspan=10 width=100%>";
    showRow(lang("Notifications - Active/Disabled"),        lang("global status of notifications"),                        makeDropDown(array("active"=>"Active","disabled"=>"Disabled"), "save_con[notify_status]", "$config_notify_status"));
    showRow(lang("Notify of New Registrations"),            lang("when new user auto-registers"),                          makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[notify_registration]", "$config_notify_registration"));
    showRow(lang("Notify of New Comments"),                 lang("when new comment is added"),                             makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[notify_comment]", "$config_notify_comment"));
    showRow(lang("Notify of Unapproved News"),              lang("when unapproved article is posted (by journalists)"),    makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[notify_unapproved]", "$config_notify_unapproved"));
    showRow(lang("Notify of Auto-Archiving"),               lang("when (if) news are auto-archived"),                      makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[notify_archive]", "$config_notify_archive"));
    showRow(lang("Notify of Activated Postponed Articles"), lang("when postponed article is activated"),                   makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[notify_postponed]", "$config_notify_postponed"));
    showRow(lang("Email(s)"),                               lang("Where the notification will be send, separate multyple emails by comma"), "<input type=text style=\"text-align: center;\"  name='save_con[notify_email]' value=\"$config_notify_email\" size=40>");
    echo"</table></td></tr>";

    // Facebook preferences
    $config_fb_comments = $config_fb_comments ? $config_fb_comments : 4;
    $config_fb_box_width = $config_fb_box_width ? $config_fb_box_width : 470;

    echo "<tr style='display:none' id='facebook' width=100%><td colspan=10 width=100%><table cellpading=0 cellspacing=0 colspan=10 width=100%>";
    showRow(lang("Use facebook comments for post"), lang("if yes, facebook comments will be shown"),    makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[use_fbcomments]", $config_use_fbcomments));
    showRow(lang("In active news"),                 lang("Show in active news list"),                   makeDropDown(array("yes"=>"Yes","no"=>"No"), "save_con[fb_inactive]", $config_fb_inactive));
    showRow(lang("Comments number"),                lang("Count comment under top box"),                "<input type=text style=\"text-align: center;\"  name=\"save_con[fb_comments]\" value=\"$config_fb_comments\" size=8>", "save_con[fb_comments]", $config_fb_comments);
    showRow(lang("Box width"),                      lang("In pixels"),                                  "<input type=text style=\"text-align: center;\"  name=\"save_con[fb_box_width]\" value=\"$config_fb_box_width\" size=8>", "save_con[fb_box_width]", $config_fb_box_width);
    showRow(lang("Facebook appID"),                 lang("Get your AppId <a href='https://developers.facebook.com/apps'>there</a>"), "<input type=text style=\"text-align: center;\"  name=\"save_con[fb_appid]\" value=\"$config_fb_appid\" size=40>", "save_con[fb_appid]", $config_fb_appid);
    echo"</table></td></tr>";

    echo"
    <input type=hidden id=currentid name=current value=general>
    <input type=hidden name=mod value=options>
    <input type=hidden name=action value=dosavesyscon>".
    showRow("", "", "<br /><input style='font-weight:bold;font-size:120%;' type=submit value=\"     Save Changes     \" accesskey=\"s\">")."
    </form></table>";

    // select tabs ----------------
echo <<<HTML
    <script type="text/javascript">
           var iof = document.location.toString();
           if (iof.indexOf('#') > 0) ChangeOption(iof.substr(iof.indexOf('#') + 1));
    </script>
HTML;

    echofooter();
}
// ********************************************************************************
// Save System Configuration
// ********************************************************************************
elseif($action == "dosavesyscon")
{
    if ($member_db[UDB_ACL] != 1)
        msg("error", lang("Access Denied"), lang("You don't have permission for this section"));

    $handler = fopen(SERVDIR."/cdata/config.php", "w");
    fwrite ($handler, "<?php \n\n//System Configurations (Auto Generated file)\n");
    foreach($save_con as $name => $value)
    {
        fwrite($handler, "\$config_$name = \"".htmlspecialchars($value)."\";\n");
    }
    fwrite($handler, "?>");
    fclose($handler);

    header('Location: '.PHP_SELF.'?mod=options&action=syscon&message=1#'.$_REQUEST['current']);
    include (SERVDIR."/skins/".$save_con["skin"].".skin.php");
}

hook('options_additional_actions');