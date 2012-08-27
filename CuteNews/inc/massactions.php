<?PHP

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  Mass Delete
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

list($allowed_cats, $cat_lines) = get_allowed_cats($member_db);
$source         = preg_replace('~[^a-z0-9_\.]~i', '' , $source);

if ($action == "mass_delete")
{
    if (!$selected_news)
        msg("error", LANG_ERROR_TITLE, lang("You have not specified any articles"), "#GOBACK");

    // Check permissions
    $have_perm = 0;
    if     (($member_db[UDB_ACL] == ACL_LEVEL_ADMIN) or ($member_db[UDB_ACL] == ACL_LEVEL_EDITOR)) $have_perm = 1;
    elseif ($member_db[UDB_ACL]  == ACL_LEVEL_JOURNALIST and $item_db[1] == $member_db[UDB_NAME]) $have_perm = 1;

    if (!$have_perm)
    {
        msg("error", lang("No Access"), lang("You dont have access for this action"), "#GOBACK");
    }

    // Check access user for category
    if ( !empty($item_db[NEW_CAT]) )
        foreach (explode(',', $item_db[NEW_CAT]) as $all_this_cat)
            if ( !in_array($all_this_cat, $allowed_cats) )
                msg("error", lang("Access Denied"), lang("This article is posted under category which you are not allowed to access."), "#GOBACK");

    $CSRF = CSRFMake();
    echoheader("options", "Delete News");

    echo "<form method=post action=\"$PHP_SELF\">
    <table border=0 cellpadding=0 cellspacing=0 width=100% height=100%>
    <tr><td>".lang('Are you sure you want to delete all selected news')." (<b>".count($selected_news)."</b>)?<br><br>
    <input type=button value=\" No \" onclick=\"javascript:document.location='$PHP_SELF?mod=editnews&action=list&source=$source'\"> &nbsp; <input type=submit value=\"   ".lang('Yes')."   \">
    <input type=hidden name=action value=\"do_mass_delete\">
    <input type=hidden name=mod value=\"massactions\">
    <input type=hidden name=source value=\"$source\">
    <input type=hidden name=csrf_code value=\"$CSRF\">";
    foreach ($selected_news as $newsid)
    {
        echo "<input type=hidden name=selected_news[] value=\"$newsid\">\n";
    }
    echo "</td></tr></table></form>";
    echofooter();
}
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  Do Mass Delete
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
elseif($action == "do_mass_delete")
{
    CSRFCheck();
    if(!$selected_news)
        msg("error", LANG_ERROR_TITLE, lang("You have not specified any articles to be deleted"), "#GOBACK");

    if ($source == "")
    {
        $news_file = "cdata/news.txt";
        $comm_file = "cdata/comments.txt";
    }
    elseif($source == "postponed")
    {
        $news_file = "cdata/postponed_news.txt";
        $comm_file = "cdata/comments.txt";
    }
    elseif($source == "unapproved")
    {
        $news_file = "cdata/unapproved_news.txt";
        $comm_file = "cdata/comments.txt";
    }
    else
    {
        $news_file = SERVDIR."/cdata/archives/$source.news.arch";
        $comm_file = SERVDIR."/cdata/archives/$source.comments.arch";
    }

    $deleted_articles = 0;

    // Delete News
    $old_db = file($news_file);
    $new_db = fopen($news_file, 'w');
    foreach($old_db as $old_db_line)
    {
        $old_db_arr = explode("|", $old_db_line);
        if ( !in_array($old_db_arr[0], (array)$selected_news) )
        {
            fwrite($new_db, $old_db_line);
        }
        else
        {
            $have_perm = 0;
            if (($member_db[UDB_ACL] == ACL_LEVEL_ADMIN) or ($member_db[UDB_ACL] == ACL_LEVEL_EDITOR)) $have_perm = 1;
            elseif($member_db[UDB_ACL] == ACL_LEVEL_JOURNALIST and $old_db_arr[1] == $member_db[UDB_NAME]) $have_perm = 1;

            if (!$have_perm) fwrite($new_db, $old_db_line);
            else $deleted_articles++;
        }
    }
    fclose($new_db);

    // Delete Comments
    $old_db = file($comm_file);
    $new_db = fopen($comm_file, 'w');
    foreach($old_db as $old_db_line)
    {
        $old_db_arr = explode("|", $old_db_line);
        if ( !in_array($old_db_arr[0], (array)$selected_news) )
        {
            fwrite($new_db, $old_db_line);
        }
        else
        {
            $have_perm = 0;
            if (($member_db[UDB_ACL] == ACL_LEVEL_ADMIN) or ($member_db[UDB_ACL] == ACL_LEVEL_EDITOR)) $have_perm = 1;
            elseif($member_db[UDB_ACL] == ACL_LEVEL_JOURNALIST and $old_db_arr[UDB_ACL] == $member_db[UDB_NAME]) $have_perm = 1;

            if(!$have_perm) fwrite($new_db, $old_db_line);
        }
    }
    fclose($new_db);

    if ( count($selected_news) == $deleted_articles)
         msg("info",  lang("Deleted News"), str_replace('%1', $deleted_articles, lang("All articles that you selected (<b>%1</b>) were deleted")), "#GOBACK");
    else msg("error", lang("Deleted News (some errors occured)"), str_replace(array('%1','%2'), array($deleted_articles, count($selected_news)), lang("%1 of %2 articles that you selected were deleted")), "#GOBACK");
}
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   Mass Approve
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

elseif ($action == "mass_approve")
{
    CSRFCheck();

    if ($member_db[UDB_ACL] != ACL_LEVEL_ADMIN and $member_db[UDB_ACL] != ACL_LEVEL_EDITOR)
        msg("error", LANG_ERROR_TITLE, lang("You do not have permissions for this action"), "#GOBACK");

    if (!$selected_news)
        msg("error", LANG_ERROR_TITLE, lang("You have not specified any articles"), "#GOBACK");

    $news_file = SERVDIR."/cdata/unapproved_news.txt";

    $approved_articles = 0;
    $old_db = file( $news_file);
    $new_db = fopen( $news_file, 'w');
    flock($new_db, LOCK_EX);

    foreach($old_db as $old_db_line)
    {
        $old_db_arr = explode("|", $old_db_line);
        if (!in_array($old_db_arr[0], (array)$selected_news))
        {
            fwrite($new_db, $old_db_line);
        }
        else
        {
            //Move the article to Active News
            $all_active_db = file(SERVDIR."/cdata/news.txt");
            $active_news_file = fopen(SERVDIR."/cdata/news.txt", "w");
            flock ($active_news_file, LOCK_EX);
            fwrite($active_news_file, $old_db_line );
            foreach ($all_active_db as $active_line) fwrite($active_news_file, $active_line);
            flock ($active_news_file, LOCK_UN);
            fclose($active_news_file);
            $approved_articles++;
        }
    }

    flock($new_db, LOCK_UN);
    fclose($new_db);

    if ( count($selected_news) == $approved_articles)
         msg("info",  lang("News Approved"), str_replace('%1', $approved_articles, "All articles that you selected (%1) were approved and are now active"), "#GOBACK");
    else msg("error", lang("News Approved (with errors)"), str_replace(array('%1','%2'), array($approved_articles, count($selected_news)), lang("%1 of %2 articles that you selected were approved")), "#GOBACK");

}
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  Mass Move to Cat
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
elseif ($action == "mass_move_to_cat")
{
    if (!$selected_news)
        msg("error", LANG_ERROR_TITLE, lang("You have not specified any articles"), "#GOBACK");

    $CSRF = CSRFMake();
    echoheader("options", lang("Move Articles to Category"));

    echo "<form action=\"$PHP_SELF\" method=post><table border=0 cellpadding=0 cellspacing=0 width=100% height=100%><tr><td >Move selected articles (<b>".count($selected_news)."</b>) to category:";
    echo'<table width="80%" border="0" cellspacing="0" cellpadding="0" class="panel">';

    foreach ($cat_lines as $single_line)
    {
        $i++;
        $cat_arr = explode("|", $single_line);
        echo "<td style='font-size:10px;' valign=top>
        <label for='cat{$cat_arr[0]}'>
        <input $if_is_selected style='background-color:transparent;border:0px;' type=checkbox name='category[]' id='cat{$cat_arr[0]}' value='{$cat_arr[0]}'>$cat_arr[1]</label>";
        if ($i%4 == 0) echo'<tr>';
    }
    echo "</tr></table>";

    foreach($selected_news as $newsid)
    {
        echo "<input type=hidden name=selected_news[] value=\"$newsid\">";
    }

    echo "<br><input type=hidden name=action value=\"do_mass_move_to_cat\">
              <input type=hidden name=source value=\"$source\">
              <input type=hidden name=mod value=\"massactions\">&nbsp;
              <input type=hidden name=csrf_code value=\"$CSRF\">
              <input type=submit value=\"".lang('Move')."\"></td></tr></table></form>";

    echofooter();
}
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  DO Mass Move to One Category
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
elseif ($action == "do_mass_move_to_cat")
{
    CSRFCheck();

    if ($member_db[UDB_ACL] != ACL_LEVEL_ADMIN)
        msg("error", LANG_ERROR_TITLE, lang("You do not have permissions for this action"), "#GOBACK");

    if( is_array($category) )
    {
        // User has selected multiple categories
        $nice_category = implode(',', $category);
        $ccount = count($category);

    }
    else
    {
        // Single or Not category
        // don't format $nice_cats because we have not selected any.
        $nice_category = $category;
    }

    if (!$selected_news)
        msg("error", LANG_ERROR_TITLE, lang("You have not specified any articles"), "#GOBACK");

    if ($source == "")
        $news_file = SERVDIR."/cdata/news.txt";

    elseif ($source == "postponed")
        $news_file = SERVDIR."/cdata/postponed_news.txt";

    elseif ($source == "unapproved")
        $news_file = SERVDIR."/cdata/unapproved_news.txt";

    else $news_file = SERVDIR."/cdata/archives/$source.news.arch";

    $moved_articles = 0;
    $old_db = file($news_file);
    $new_db = fopen($news_file, 'w');
    flock($new_db, LOCK_EX);
    foreach ($old_db as $old_db_line)
    {
        $old_db_arr = explode("|", $old_db_line);
        if (!in_array($old_db_arr[0], (array)$selected_news))
        {
            fwrite($new_db,"$old_db_line");
        }
        else
        {
            $have_perm = 0;
            if (($member_db[UDB_ACL] == ACL_LEVEL_ADMIN) or ($member_db[UDB_ACL] == ACL_LEVEL_EDITOR)) $have_perm = 1;
            elseif ($member_db[UDB_ACL] == ACL_LEVEL_JOURNALIST and $old_db_arr[1] == $member_db[UDB_NAME]) $have_perm = 1;

            if(!$have_perm)
            {
                fwrite($new_db, $old_db_line);
            }
            else
            {
                fwrite($new_db, "$old_db_arr[0]|$old_db_arr[1]|$old_db_arr[2]|$old_db_arr[3]|$old_db_arr[4]|$old_db_arr[5]|$nice_category|||\n");
                $moved_articles ++;
            }
        }
    }
    flock($new_db, LOCK_UN);
    fclose($new_db);

    if ( count($selected_news) == $moved_articles)
         msg("info",  lang("News Moved"), str_replace('%1', $moved_articles, lang("All articles that you selected (%1) were moved to the specified category")), "#GOBACK");
    else msg("error", lang("News Moved (with errors)"), str_replace(array('%1','%2'), array($moved_articles, count($selected_news)), lang("%1 of %2 articles that you selected were moved to the specified category")), "#GOBACK");
}
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  Mass Archive
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
elseif($action == "mass_archive")
{
    if (!$selected_news)
        msg("error", LANG_ERROR_TITLE, lang("You have not specified any articles"), "#GOBACK");

    if ($source != "")
        msg("error", LANG_ERROR_TITLE, lang("These news are already archived or are in postpone queue"), "#GOBACK");

    $CSRF = CSRFMake();
    echoheader("options", lang("Send News To Archive"));

    echo "<form method=post action=\"$PHP_SELF\">
    <table border=0 cellpadding=0 cellspacing=0 width=100% height=100%><tr><td >".
    lang('Are you sure you want to send all selected news to the archive')." (<b>".count($selected_news)."</b>)?<br><br>
    <input type=button value=\" No \" onclick=\"javascript:document.location='$PHP_SELF?mod=editnews&action=list&source=$source'\"> &nbsp; <input type=submit value=\"   ".lang('Yes')."   \">
    <input type=hidden name=action value=\"do_mass_archive\">
    <input type=hidden name=csrf_code value=\"$CSRF\">
    <input type=hidden name=mod value=\"massactions\">";
    
    foreach ($selected_news as $newsid)
    {
        echo "<input type=hidden name=selected_news[] value=\"$newsid\">\n";
    }
    echo "</td></tr></table></form>";

    echofooter();
}
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  DO Mass Send To Archive
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
elseif ($action == "do_mass_archive")
{
    CSRFCheck();

    if ( $member_db[UDB_ACL] != ACL_LEVEL_ADMIN)
         msg("error", lang("Access Denied"), lang("You can not perform this action if you are not admin"), "#GOBACK");

    if ( !$selected_news )
         msg("error", LANG_ERROR_TITLE, lang("You have not specified any articles"), "#GOBACK");

    if (!is_writable(SERVDIR."/cdata/archives/"))
         msg("error", LANG_ERROR_TITLE, lang("The ./cdata/archives/ directory is not writable, CHMOD it to 775"), "#GOBACK");
    
    $news_file = SERVDIR."/cdata/news.txt";
    $comm_file = SERVDIR."/cdata/comments.txt";

    $prepeared_news_for_archive = array();
    $prepeared_comments_for_archive = array();
    $archived_news = 0;

    // Prepare the news for Archiving
    $old_db = file( $news_file);
    $new_db = fopen( $news_file, 'w');
    flock($new_db, LOCK_EX);
    foreach($old_db as $old_db_line)
    {
        $old_db_arr = explode("|", $old_db_line);
        if (!in_array($old_db_arr[0], (array)$selected_news))
        {
            fwrite($new_db, $old_db_line);
        }
        else
        {
            $have_perm = 0;
            if (($member_db[UDB_ACL] == ACL_LEVEL_ADMIN) or ($member_db[UDB_ACL] == ACL_LEVEL_EDITOR)) $have_perm = 1;
            elseif($member_db[UDB_ACL] == ACL_LEVEL_JOURNALIST and $old_db_arr[1] == $member_db[UDB_NAME]) $have_perm = 1;

            if(!$have_perm)
            {
                fwrite($new_db, $old_db_line);
            }
            else
            {
                $prepeared_news_for_archive[] = $old_db_line;
                $archived_news++;
            }
        }
    }
    flock($new_db, LOCK_UN);
    fclose($new_db);

    if ($archived_news == 0)
        msg("error", LANG_ERROR_TITLE, lang("No news were found for archiving"), "#GOBACK");

    // Prepare the comments for Archiving
    $old_db = file($comm_file);
    $new_db = fopen($comm_file, 'w');
    flock($new_db, LOCK_EX);
    foreach ($old_db as $old_db_line)
    {
        $old_db_arr = explode("|", $old_db_line);
        if ( !in_array($old_db_arr[0], (array)$selected_news))
        {
            fwrite($new_db,"$old_db_line");
        }
        else
        {
            $have_perm = 0;
            if (($member_db[UDB_ACL] == ACL_LEVEL_ADMIN) or ($member_db[UDB_ACL] == ACL_LEVEL_EDITOR)) $have_perm = 1;
            elseif($member_db[UDB_ACL] == ACL_LEVEL_JOURNALIST and $old_db_arr[1] == $member_db[UDB_NAME]) $have_perm = 1;

            if(!$have_perm)
            {
                fwrite($new_db, $old_db_line);
            }
            else
            {
                $prepeared_comments_for_archive[] = $old_db_line;
            }
        }
    }
    flock($new_db, LOCK_UN);
    fclose($new_db);

    // Start Archiving
    $arch_name = time() + ($config_date_adjust*60);

    $arch_news = fopen(SERVDIR."/cdata/archives/$arch_name.news.arch", 'w');
    foreach ($prepeared_news_for_archive as $item)
    {
        fwrite($arch_news, $item);
    }
    fclose($arch_news);

    $arch_comm = fopen(SERVDIR."/cdata/archives/$arch_name.comments.arch", 'w');
    foreach ($prepeared_comments_for_archive as $item)
    {
        fwrite($arch_comm, "$item");
    }
    fclose($arch_comm);

    msg("info", lang("News Archived"), str_replace('%1', $archived_news, lang("All articles that you selected (%1) are now archived under"))." ./cdata/archives/<b>$arch_name</b>.news.arch", "#GOBACK");
}
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  If No Action Is Choosed
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
else
{
    msg("info", lang("Choose Action"), lang("Please choose action from the drop-down menu"), "#GOBACK");
}

?>
