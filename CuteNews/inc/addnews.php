<?php

if ($member_db[UDB_ACL] > 3)
    msg("error", lang("Access Denied"), lang("You don't have permission to add news"));

extract(filter_request('full_story,title,short_story,category,postpone_draft,from_date_hour,from_date_minutes,from_date_month,from_date_day,from_date_year,if_convert_new_lines,if_use_html'), EXTR_OVERWRITE);

$orig_cat_lines = file(SERVDIR."/cdata/category.db.php");

//only show allowed categories
$allowed_cats = array();
$cat_lines = array();
foreach($orig_cat_lines as $single_line)
{
    $ocat_arr = explode("|", $single_line);
    if ($member_db[UDB_ACL] <= $ocat_arr[3] or ($ocat_arr[3] == '0' || $ocat_arr[3] == ''))
    {
        $cat_lines[]    = $single_line;
        $allowed_cats[] = $ocat_arr[0];
    }
}

if ($action == "addnews")
{
    $CSRF = CSRFMake();
    echoheader("addnews", lang("Add News"));
    
    $short_story_id = 'short_story';
    $full_story_id = 'full_story';

    $_cat_html = false;
    $_multi_cat_html = false;
    $_dateD = false;
    $_dateM = false;
    $_dateY = false;
    $xfields = array();

    // init x-fields
    if ( !isset($cfg['more_fields']) )
    {
        $cfg['more_fields'] = array();
        fv_serialize('conf', $cfg);
    }

    foreach ($cfg['more_fields'] as $i => $v)
    {
        if ( substr($v, 0, 1) == '&' )
             $xfields[] = array( $i, substr($v,1), '(optional)' );
        else $xfields[] = array( $i, $v, '' );
    }

    if (count($cat_lines) > 0)
    {
        // old style
        foreach ($cat_lines as $single_line)
        {
            $cat_arr = explode("|", $single_line);
            $_cat_html .= '<option '.($category == $cat_arr[0]? ' selected ':'').' value="'.$cat_arr[0].'">'.$cat_arr[1].'</option>';
        }

        // new style
        $i = 0;
        foreach ($cat_lines as $single_line)
        {
            $i++;
            $cat_arr  = explode("|", $single_line);
            $cat_id   = $cat_arr[0];
            $cat_name = $cat_arr[1];
            $_multi_cat_html .= "<td style='font-size:10px;' valign=top><label for='cat".$cat_id."'><input ".($category == $cat_id? " checked ":'')." style='background-color:transparent;border:0px;' type=checkbox name='category[]' id='cat".$cat_id."' value='".$cat_id."'>".$cat_name."</label></td>";
            if ($i%4 == 0) $_multi_cat_html .= '<tr>';
        }
    }

    for ($i=1; $i < 32; $i++)
    {
        if(date("j") == $i) $_dateD .= "<option selected value=$i>$i</option>";
        else                $_dateD .= "<option value=$i>$i</option>";
    }

    for ($i=1; $i < 13; $i++)
    {
        $timestamp = mktime(0, 0, 0, $i, 1, 2003);
        if(date("n") == $i) $_dateM .= "<option selected value=$i>". date("M", $timestamp) ."</option>";
        else                $_dateM .= "<option value=$i>". date("M", $timestamp) ."</option>"; 
    }

    for ($i=2005; $i < (date('Y')+3); $i++)
    {
        if(date("Y") == $i) $_dateY .= "<option selected value=$i>$i</option>";
        else                $_dateY .= "<option value=$i>$i</option>";
    }

    $use_wysiwyg = ($config_use_wysiwyg == 'yes') ? 1 : 0;
    if ( $config_use_wysiwyg == 'ckeditor' && is_dir(SERVDIR.'/core/ckeditor') ) $tpl = 'index_cke'; else $tpl = 'index';

    echo proc_tpl
    (
            'addnews/'.$tpl,
            array
            (
                'PHP_SELF'               => PHP_SELF,
                'member_db8'             => $member_db[UDB_AVATAR],
                'use_wysiwyg'            => $use_wysiwyg,
                'short_story_id'         => $short_story_id,
                'full_story_id'          => $full_story_id,
                'cat_html'               => $_cat_html,
                'multi_cat_html'         => $_multi_cat_html,
                'insertsmiles'           => insertSmilies($short_story_id, 4, true, $use_wysiwyg),
                'insertsmiles_full'      => insertSmilies($full_story_id,  4, true, $use_wysiwyg),
                'dated'                  => $_dateD,
                'datem'                  => $_dateM,
                'datey'                  => $_dateY,
                'date_hour'              => date("H"),
                'date_minutes'           => date("i"),
                'xfields'                => $xfields,
                'CSRF'                   => $CSRF
            ),
            array
            (
                'WYSIWYG'                => $use_wysiwyg,
                'USE_AVATAR'             => ($config_use_avatar == 'yes') ? 1 : 0,
            )
    );

    echofooter();
}
// ********************************************************************************
// Do add News to news.txt
// ********************************************************************************
elseif($action == "doaddnews")
{

    /// Format our categories variable
    if( is_array($category) )
    {
        // User has selected multiple categories
        $nice_category = array();
        $ccount = 0;

        foreach ($category as $ckey => $cvalue)
        {
            if ( !in_array($cvalue, $allowed_cats) ) die_stat(false, 'Not allowed category');
            $nice_category[] = $cvalue;
        }
        $nice_category = implode(',', $nice_category);
    }
    else
    {
        //Single or Not category
        //don't format $nice_cats because we have not selected any.
        if ( $category && !in_array($category, $allowed_cats) ) die_stat(false, 'Not allowed category');
        $nice_category = $category;
    }

    // --------------------------------------------------------------
    if($member_db[UDB_ACL] == ACL_LEVEL_JOURNALIST or $postpone_draft == "draft")
    {
        // if the user is Journalist, add the article as unapproved
        $decide_news_file       = SERVDIR."/cdata/unapproved_news.txt";
        $added_time             = time() + $config_date_adjust*60;
        $postpone               = false;
        $unapproved_status_msg  = lang("The article was marked as Unapproved!");
    }
    elseif($postpone_draft == "postpone")
    {
         if( !preg_match("~^[0-9]{1,}$~", $from_date_hour) or !preg_match("~^[0-9]{1,}$~", $from_date_minutes) )
             msg("error", LANG_ERROR_TITLE, lang("You want to add a postponed article, but the hour format is invalid."), "javascript:history.go(-1)");

         $postpone          = true;
         $added_time        = mktime($from_date_hour, $from_date_minutes, 0, $from_date_month, $from_date_day, $from_date_year) + $config_date_adjust*60;
         $decide_news_file  = SERVDIR."/cdata/postponed_news.txt";
    }
    else
    {
         $postpone          = false;
         $added_time        = time() + $config_date_adjust*60;
         $decide_news_file  = SERVDIR."/cdata/news.txt";
    }

    if ($if_convert_new_lines == "yes") $n_to_br = TRUE;
    if ($if_use_html          == "yes") $use_html = TRUE;

    // Replace code
    $full_story  = replace_news("add", $full_story, $n_to_br, $use_html);
    $short_story = replace_news("add", $short_story, $n_to_br, $use_html);
    $title       = replace_news("add", $title, TRUE, FALSE); // HTML in title is not allowed

    // Check optional fields
    $optfields = array();
    foreach ($cfg['more_fields'] as $i => $v)
    {
        if (substr($v, 0, 1) != '&' && $_REQUEST[$i] == false)
            $optfields[] = $v;
    }

    if (count($optfields))
        msg('error', LANG_ERROR_TITLE, lang('Some fields can not be blank').': '.implode(', ', $optfields));

    if (trim($title) == false)
        msg("error", LANG_ERROR_TITLE, lang("The title can not be blank"), "javascript:history.go(-1)");

    if (trim($short_story) == false)
        msg("error", LANG_ERROR_TITLE, lang("The story can not be blank"), "javascript:history.go(-1)");

    if ( $member_db[UDB_CBYEMAIL] == 1)
         $added_by_email = $member_db[UDB_EMAIL];
    else $added_by_email = "none";

    // avatar check
    if ($manual_avatar)
    {
        $editavatar = check_avatar($manual_avatar);
        if ($editavatar == false)
            msg('error','Error!', lang('Avatar not uploaded!'));
    }

    // Make unique time
    $added_time = time();
    if ( file_exists (SERVDIR.'/cdata/newsid.txt') )
         $added_time = join('', file(SERVDIR.'/cdata/newsid.txt'));

    if (time() <= $added_time) $added_time++;
    else $added_time = time();

    $w = fopen(SERVDIR.'/cdata/newsid.txt', 'w');
    fwrite($w, $added_time);
    fclose($w);

    // Additional fields
    $pack = array();
    foreach ($cfg['more_fields'] as $i => $v) $pack[] = spack($i)."=".spack($_REQUEST[$i]);

    // Save The News Article In Active_News_File
    $all_db         = file($decide_news_file);
    $news_file      = fopen($decide_news_file, "w");
    flock($news_file, LOCK_EX);
    fwrite($news_file, "$added_time|$member_db[2]|$title|$short_story|$full_story|$manual_avatar|$nice_category||".join(';', $pack)."\n");
    foreach ($all_db as $line) fwrite($news_file, $line);
    flock($news_file, LOCK_UN);
    fclose($news_file);

    // Add Blank Comment In The Active_Comments_File
    $old_com_db = file(SERVDIR."/cdata/comments.txt");
    $new_com_db = fopen(SERVDIR."/cdata/comments.txt", "w");
    flock($new_com_db, LOCK_EX);
    fwrite($new_com_db, "$added_time|>|\n");
    foreach ($old_com_db as $line) fwrite($new_com_db, $line);
    flock($new_com_db, LOCK_UN);
    fclose($new_com_db);

    // Incrase By 1 The Number of Written News for Current User
    $member_db[UDB_COUNT]++;
    edit_key($username, $member_db, DB_USERS);

    if ($config_backup_news == 'yes')
    {

        $news_file = fopen($decide_news_file, "r");
        $news_backup = fopen($decide_news_file.'.bak', "w");
        while (!feof($news_file)) fwrite($news_backup, fgets($news_file));
        fclose($news_file);
        fclose($news_backup);
    }

    // Notifications
    if ($member_db[UDB_ACL] == ACL_LEVEL_JOURNALIST)
    {
        //user is journalist and the article needs to be approved, Notify !!!
        if ($config_notify_unapproved == "yes" and $config_notify_status == "active")
        {
            send_mail
            (
                $config_notify_email,
                lang("CuteNews - Unapproved article was Added"),
                str_replace( array('%1','%2'), array($member_db[UDB_NAME], $title), 'The user %1 (journalist) posted article %2 which needs first to be Approved')
            );
        }
    }

    if  ($postpone)
         msg("info", lang("News added (Postponed)"), lang("The news item was successfully added to the database as postponed. It will be activated at").date(" r",$added_time));
    else msg("info", lang("News added"), lang("The news item was successfully added").'. '.$unapproved_status_msg);

}