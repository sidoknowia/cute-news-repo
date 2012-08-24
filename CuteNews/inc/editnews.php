<?PHP

if ($member_db[UDB_ACL] > ACL_LEVEL_JOURNALIST)
    msg("error", "Access Denied", "You don't have permission to edit news");

$orig_cat_lines = file(SERVDIR."/cdata/category.db.php");

//only show allowed categories
$allowed_cats = array();
$cat_lines = array();

foreach ($orig_cat_lines as $single_line)
{
    $ocat_arr = explode("|", $single_line);
    $cat[$ocat_arr[0]] = $ocat_arr[1];
    if ($member_db[UDB_ACL] <= $ocat_arr[3] or ($ocat_arr[3] == '0' || $ocat_arr[3] == ''))
    {
        $cat_lines[] = $single_line;
        $allowed_cats[] = $ocat_arr[0];
    }
}

$source = preg_replace('~[^a-z0-9_\.]~i', '' , $source);

// ********************************************************************************
// List all news available for editing
// ********************************************************************************
if ($action == "list")
{
    $CSRF = CSRFMake();
    echoheader("editnews", lang("Edit News"));

    // How Many News to show on one page
    if ($news_per_page == "") $news_per_page = 21;

    $all_db = array();
    if ($source == "")
    {
        $all_db = file(SERVDIR."/cdata/news.txt");
    }
    elseif ($source == "postponed")
    {
        $all_db = file(SERVDIR."/cdata/postponed_news.txt");
        ResynchronizePostponed();
    }
    elseif ($source == "unapproved")
    {
        $all_db = file(SERVDIR."/cdata/unapproved_news.txt");
    }
    else
    {
        $db = SERVDIR."/cdata/archives/".$source.".news.arch";
        $all_db = file_exists($db) ? file($db) : file(SERVDIR."/cdata/news.txt");
    }

    // choose only needed news items
    if ($category != '' or $author != '' or $member_db[UDB_ACL] == ACL_LEVEL_JOURNALIST)
    {
        foreach ($all_db as $raw_line)
        {
            $raw_arr = explode("|", $raw_line);
            if ( ($category == '' or in_array($category, explode(',',$raw_arr[6]))) &&
                 ($author == "" or $raw_arr[1] == $author) &&
                 ($member_db[UDB_ACL] != ACL_LEVEL_JOURNALIST or $raw_arr[1] == $member_db[UDB_NAME] ) ) $all_db_tmp[] = $raw_line;
                
        }
        $all_db = $all_db_tmp;
    }

    // Prelist Entries
    $flag = 1;
    if ($start_from == "0") $start_from = "";
    $i = $start_from;
    $entries_showed = 0;

    if (!empty($all_db))
    {
        foreach ($all_db as $line)
        {
            if ($j < $start_from)
            {
                $j++;
                continue;
            }
            
            $i++;
            $item_db    = explode("|", $line);
            $itemdate   = date("d/m/y", $item_db[0]);
            $bg         = $flag ? "#F7F6F4" : "#FFFFFF";
            $flag       = 1 - $flag;

            if (strlen($item_db[2]) > 74)
                $title = substr($item_db[2], 0, 70)." ...";

            // Safety
            $title = $item_db[2];
            $title = stripslashes( preg_replace(array("'\|'", "'\"'", "'\''"), array("I", "&quot;", "&#039;"), $title) );
            $title = preg_replace("/<[^>]*>/", "", $title) ;
            
            $entries .= proc_tpl
            (
                'editnews/list/line',
                array(  'item_db0'  => $item_db[0],
                        'title'     => $title,
                        'bg'        => $bg,
                        'source'    => $source)
            );

            $count_comments = countComments($item_db[0], $source);
            if ($count_comments == 0)
                 $entries .= "<span style='color:gray;'>".$count_comments."</span>";
            else $entries .= $count_comments;

            $entries .= "&nbsp;&nbsp;&nbsp;&nbsp;<td height=18 bgcolor=$bg nowrap>&nbsp;&nbsp;&nbsp;";

            if ($item_db[NEW_CAT] == "") $my_cat = "<span style='color:gray;'>---</span>";
            elseif (strstr($item_db[NEW_CAT], ','))
            {
                $all_this_cats_arr      = explode(',',$item_db[6]);
                $my_multy_cat_labels    = '';
                foreach ($all_this_cats_arr as $this_single_cat) $my_multy_cat_labels .= $cat[$this_single_cat].", ";
                $my_cat = "<span onmouseover=\" window.status='categories: $my_multy_cat_labels'; return true\" onmouseout=\"window.status=''; return true\"><span style='color:#7979FF;' title='$my_multy_cat_labels'>(".lang('multiple')."</span></span>";
            }
            else $my_cat = $cat[ $item_db[NEW_CAT] ];

            $entries .= $my_cat."&nbsp;
                        <td height=18 bgcolor=$bg>".$itemdate."</td>
                        <td height=18 bgcolor=$bg>".$item_db[1]."</td>
                        <td align=center bgcolor=$bg><input name=\"selected_news[]\" value=\"{$item_db[0]}\" style=\"border:0; background-color:$bg\" type='checkbox'></td>
                        </tr>";

            $entries_showed++;

            if ($i >= $news_per_page + $start_from) break;

        //foreach news line
        }
        
    // End prelisting
    }

    $all_count_news = count($all_db);
    $unapproved_selected = $postponed_selected = false;

    if ($category != "") $cat_msg = lang("Category").": <b>".htmlspecialchars($cat[$category])."</b>;";

    if ($source == "postponed")
    {
        $source_msg = "<span style='background-color:yellow;'>".lang("Postponed News").", <a title='".lang('Refresh the postponed news file')."' href=\"$PHP_SELF?mod=editnews&action=list&source=postponed\">[".lang('Resynchronize')."]</a></span>";
        $postponed_selected = " selected ";
    }
    elseif ($source == "unapproved")
    {
        $source_msg = "<span style='background-color:yellow;'>".lang('Unapproved News')."</span>";
        $unapproved_selected = " selected ";
    }
    elseif ($source != "" )
    {
        $news_lines         = file(SERVDIR."/cdata/archives/$source.news.arch");
        $count              = count($news_lines);
        $last               = $count-1;
        $first_news_arr     = explode("|", $news_lines[$last]);
        $last_news_arr      = explode("|", $news_lines[0]);
        $first_timestamp    = $first_news_arr[0];
        $last_timestamp     = $last_news_arr[0];
        $source_msg         = lang("Archive").": <b>". date("d M Y", intval($first_timestamp)) ." - ". date("d M Y", intval($last_timestamp)) ."</b>;";
    }

    if (!$handle = opendir(SERVDIR."/cdata/archives"))
        die_stat(false, lang("Can not open directory cdata/archives"));

    // Source: archives
    $opt_source = false;
    while ( false !== ($file = readdir($handle)) )
    {
        if($file != "." and $file != ".." and !is_dir(SERVDIR."/cdata/archives/$file") and substr($file, -9) == 'news.arch')
        {
            $src                = explode('.', $file);
            $info_file          = SERVDIR."/cdata/archives/" . substr($file, 0, -9) . 'count.arch';

            if ( !file_exists($info_file) )
            {
                $data  = file(SERVDIR."/cdata/archives/$file");
                $count = count( $data );

                $fx = fopen($info_file, 'w');
                fwrite($fx, $count."\n");               
                $ex = explode('|', $data[0]); fwrite($fx, $ex[0]."\n");
                $ex = explode('|', $data[$count-1]); fwrite($fx, $ex[0]."\n");
                fclose($fx);
            }

            $arch_info          = file( $info_file );
            $count              = (int)$arch_info[0];
            $first_timestamp    = (int)$arch_info[1];
            $last_timestamp     = (int)$arch_info[2];
            $arch_date          = date("d M Y", $first_timestamp) ." - ". date("d M Y",$last_timestamp);
            $opt_source        .= "<option ".(($source == $src[0]) ? "selected" : "").' value="'.htmlspecialchars($src[0]).'">'.lang('Archive').': '.$arch_date.' ('.$count.')</option>';
        }
    }
    closedir($handle);

    // Category list
    $opt_catlist = false;
    foreach ($cat_lines as $single_line)
    {
        $cat_arr = explode("|", $single_line);
        $ifselected = "";
        $opt_catlist .= "<option ".(($category == $cat_arr[0])? 'selected' : '').' value="'.htmlspecialchars($cat_arr[0]).'">'.htmlspecialchars($cat_arr[1]).'</option>';
    }

    // If user is not journalist, show author
    $opt_author = false;
    if ($member_db[UDB_ACL] != ACL_LEVEL_JOURNALIST)
    {
        $user_lines = file(SERVDIR."/cdata/db.users.php");
        unset($user_lines[0]);
        foreach ($user_lines as $single_line)
        {
            $user_arr = explode("|", $single_line, 2);
            $user_arr = unserialize($user_arr[1]);
            if ($user_arr[UDB_ACL] != ACL_LEVEL_COMMENTER)
                $opt_author .= "<option ".(($author == $user_arr[UDB_NAME])? 'selected':'').' value="'.htmlspecialchars($user_arr[UDB_NAME]).'">'.htmlspecialchars($user_arr[UDB_NAME]).'</option>';
        }
    }

    // SHOW OPTION BAR -----------------
    echo proc_tpl('editnews/list/optbar',
                  array('all_count_news'        => $all_count_news,
                        'entries_showed'        => intval($entries_showed),
                        'cat_msg'               => $cat_msg,
                        'source_msg'            => $source_msg,
                        'postponed_selected'    => $postponed_selected,
                        'unapproved_selected'   => $unapproved_selected,
                        'opt_source'            => $opt_source,
                        'opt_catlist'           => $opt_catlist,
                        'opt_author'            => $opt_author,
                        'news_per_page'         => intval($news_per_page),
                        'CSRF'                  => $CSRF
                  ),
                  array('OPT_AUTHOR' => $opt_author)
    );

    // show entries -----------------

    $npp_nav = $tmp = false;
    if ($start_from > 0)
    {
        $previous = $start_from - $news_per_page;
        $npp_nav .= '<a href="'.$PHP_SELF.'?mod=editnews&action=list&start_from='.$previous.'&category='.$category.'&author='.$author.'&source='.$source.'&news_per_page='.$news_per_page.'">&lt;&lt; '.lang('Previous').'</a>';
        $tmp = true;
    }

    if (count($all_db) > $i)
    {
        if ($tmp) $npp_nav .= "&nbsp;&nbsp;||&nbsp;&nbsp;";
        $how_next = count($all_db) - $i;

        if ($how_next > $news_per_page) $how_next = $news_per_page;
        $URL = build_uri('mod,action,start_from,category,author,source,news_per_page',
                   array('editnews','list',$i,$category,$author,$source,$news_per_page));

        $npp_nav .= '<a href="'.$PHP_SELF.$URL.'">'.lang('Next').' '.$how_next.' &gt;&gt;</a>';
    }

    // choose action
    $do_action = false;
    if ($entries_showed != 0)
    {
        if ($member_db[UDB_ACL] == ACL_LEVEL_ADMIN)
            $do_action .= '<option title="'.lang('make new archive with all selected news').'" value="mass_archive">'.lang('Send to Archive').'</option>';

        if ($source == "unapproved" and ($member_db[UDB_ACL] == ACL_LEVEL_ADMIN or $member_db[UDB_ACL] == ACL_LEVEL_EDITOR))
            $do_action .= '<option '.(( $source == "unapproved" )?  'selected' : '').' title="'.lang('approve selected news').'" value="mass_approve">'.lang('Approve News').'</option>';

        if($member_db[UDB_ACL] == ACL_LEVEL_ADMIN)
            $do_action .= '<option title="'.lang('move all selected news to one category').'" value="mass_move_to_cat">'.lang('Change Category').'</option>';
    }

    echo proc_tpl('editnews/list/entries',
                  array('entries_showed'    => $entries_showed,
                        'entries'           => $entries,
                        'npp_nav'           => $npp_nav,
                        'source'            => $source,
                        'do_action'         => $do_action,
                        'CSRF'              => $CSRF
                  ),
                  array('ENTRIES_SHOWED' => $entries_showed)
                  );
    
    echofooter();
    
}
// *********************************************************************************************************************
// Edit News Article
// *********************************************************************************************************************
elseif ($action == "editnews")
{
    // Show The Article for Editing
    if ($source == "")
        $all_db = file(SERVDIR."/cdata/news.txt");

    elseif($source == "postponed")
        $all_db = file(SERVDIR."/cdata/postponed_news.txt");

    elseif($source == "unapproved")
        $all_db = file(SERVDIR."/cdata/unapproved_news.txt");

    else
    {
        $db = SERVDIR."/cdata/archives/".$source.".news.arch";
        if ( file_exists($db) )
             $all_db = file($db);
        else $all_db = file(SERVDIR."/cdata/news.txt");
    }

    $found = FALSE;
    foreach ($all_db as $line)
    {
        $item_db=explode("|",$line);
        if ($id == $item_db[0])
        {
            $found = TRUE;
            break;
        }
        // foreach news line
    }

    $have_perm = 0;
    if(($member_db[UDB_ACL] == ACL_LEVEL_ADMIN) or ($member_db[UDB_ACL] == ACL_LEVEL_EDITOR))
        $have_perm = 1;

    elseif($member_db[UDB_ACL] == ACL_LEVEL_JOURNALIST and $item_db[NEW_USER] == $member_db[UDB_NAME])
        $have_perm = 1;

    if(!$have_perm)
        msg("error", lang("No Access"), lang("You don't have access for this action"), $PHP_SELF."?mod=editnews&action=list");

    // Check access user for category
    if (strstr($item_db[6], ','))
    {
        $all_these_cats = explode(',', $item_db[6]);
        foreach($all_these_cats as $all_this_cat)
        {
            if ($member_db[UDB_ACL] != ACL_LEVEL_ADMIN and !in_array($all_this_cat, $allowed_cats) )
                msg("error", lang("Access Denied"), lang("This article is posted under category which you are not allowed to access."));
        }
    }
    else
    {
        if($member_db[UDB_ACL] != ACL_LEVEL_ADMIN and !in_array($item_db[6], $allowed_cats) )
            msg("error", lang("Access Denied"), lang("This article is posted under category which you are not allowed to access."));
    }

    if (!$found)
        msg("error", LANG_ERROR_TITLE, "The selected news item can <b>not</b> be found.");

    $newstime   = date("D, d F Y h:i:s", $item_db[0]);
    $item_db[2] = stripslashes( preg_replace(array("'\|'", "'\"'", "'\''"), array("I", "&quot;", "&#039;"), $item_db[2]) );

    $short_story_id = 'short_story';
    $full_story_id = 'full_story';

    // Are we using the WYSIWYG ?
    $use_wysiwyg = ($config_use_wysiwyg == "yes") ? 1 : 0;
    $item_db[3] = replace_news("admin", $item_db[3], $use_wysiwyg);
    $item_db[4] = replace_news("admin", $item_db[4], $use_wysiwyg);

    $CSRF = CSRFMake();
    echoheader("editnews", lang("Edit News"));

    // make category lines
    if ( count($cat_lines) > 0)
    {
        $lines_html = false;
        foreach($cat_lines as $single_line)
        {
            $cat_arr = explode("|", $single_line);

            $lines_html .= "<td style='font-size:10px;' valign=top><label for='cat{$cat_arr[0]}'>";
            if ( in_array($cat_arr[0], explode(',',$item_db[6])) )
                 $lines_html .= "<input checked style='background-color:transparent; border:0px;' type='checkbox' name='category[]' id='cat{$cat_arr[0]}' value='{$cat_arr[0]}'>$cat_arr[1]</label>";
            else $lines_html .= "<input style='background-color:transparent; border:0px;' type='checkbox' name='category[]' id='cat{$cat_arr[0]}' value='{$cat_arr[0]}'>$cat_arr[1]</label>";

            $i++;
            if ($i%4 == 0) $lines_html .= '<tr>';
        }
        $lines_html .= "</tr>";
    }

    // Show the Comments for Editing
    $Comments_HTML = false;

    if ( $source == "" or $source == "postponed" or $source == "unapproved")
         $all_comments_db = file(SERVDIR."/cdata/comments.txt");
    else $all_comments_db = file(SERVDIR."/cdata/archives/{$source}.comments.arch");

    $found_newsid = false;
    foreach($all_comments_db as $comment_line)
    {
        $comment_line = trim($comment_line);
        $comments_arr = explode("|>|",$comment_line);
        if($comments_arr[0] == $id)
        {
            //if these are comments for our story
            $found_newsid = TRUE;
            if ($comments_arr[1] != "")
            {
                $flag = 1;
                $different_posters = explode("||", $comments_arr[1]);
                foreach ($different_posters as $individual_comment)
                {
                    if($flag == 1)
                    {
                        $bg = "bgcolor=#F7F6F4";
                        $flag = 0;
                    }
                    else
                    {
                        $bg = "";
                        $flag = 1;
                    }

                    $comment_arr            = explode("|", $individual_comment);
                    $comtime                = date("d/m/y h:i:s", (int)$comment_arr[0]);
                    $comm_value             = stripslashes(strip_tags($comment_arr[4]));
                    $comm_excerpt_lenght    = 43 - strlen($comment_arr[1]);

                    // except comment if length over length
                    if ( $comm_excerpt_lenght < strlen($comm_value))
                         $comm_excerpt = substr($comm_value, 0, $comm_excerpt_lenght) . '...';
                    else $comm_excerpt = $comm_value;

                    if ($comment_arr[1])
                    {
                        if (strlen($comment_arr[1]) > 25) $comment_arr[1] = substr($comment_arr[1], 0, 22) . "...";

                        $Comments_HTML .= proc_tpl('editnews/editnews/comment_line',
                            array
                            (
                                'comment_arr0'  => $comment_arr[0],
                                'comment_arr1'  => $comment_arr[1],
                                'comment_arr3'  => $comment_arr[3],
                                'id'            => $id,
                                'bg'            => $bg,
                                'source'        => $source,
                                'comm_excerpt'  => my_strip_tags($comm_excerpt),
                                'comtime'       => $comtime,
                            )
                        );

                    }//if not blank
                }//foreach comment

                $Comments_HTML .= proc_tpl('editnews/editnews/comment_actions', array('id' => $id, 'source' => $source));

                //foreach comment line
                break;

            }//if there are any comments
            else
            {
                $Comments_HTML = proc_tpl('editnews/editnews/nocomments');
                $found_newsid  = false;
            }

        }//if these are comments for our story

    }//foreach comments line

    if ($found_newsid == false)
        $Comments_HTML = proc_tpl('editnews/editnews/nocomments');

    // init x-fields
    $article = array();
    $pack = explode(';', $item_db[8]);
    foreach ($pack as $i => $v)
    {
        list ($a, $b) = explode('=', $v);
        $a = str_replace(array('{~',"{I}","{kv}","{eq}","{eol}"), array('{','|',';','=',"\n"), $a);
        $b = str_replace(array('{~',"{I}","{kv}","{eq}","{eol}"), array('{','|',';','=',"\n"), $b);
        $article[$a] = $b;
    }

    foreach ($cfg['more_fields'] as $i => $v)
    {
        $af = isset($article[$i]) ? $article[$i] : false;
        if ( substr($v, 0, 1) == '&' )
             $xfields[] = array( $i, substr($v,1), '(optional)', $af );
        else $xfields[] = array( $i, $v, '', $af );
    }

    // show template -------------------------------------------------------------------
    if ( $config_use_wysiwyg == 'ckeditor' && is_dir(SERVDIR.'/core/ckeditor')) $tpl = 'index_cke'; else $tpl = 'index';

    echo proc_tpl(
        'editnews/editnews/'.$tpl,
        array
        (
            'id'                    => $id,
            'source'                => $source,
            'newstime'              => $newstime,
            'item_db1'              => $item_db[1],
            'item_db2'              => my_strip_tags( $item_db[2] ),
            'item_db3'              => $item_db[3],
            'item_db4'              => $item_db[4],
            'item_db5'              => $item_db[5],
            'lines_html'            => $lines_html,
            'rtes'                  => rteSafe($item_db[3]),
            'rtes2'                 => rteSafe($item_db[4]),
            'short_story_id'        => $short_story_id,
            'short_story_smiles'    => insertSmilies($short_story_id, 4, true, $use_wysiwyg),
            'full_story_id'         => $full_story_id,
            'full_story_smiles'     => insertSmilies($full_story_id, 4, true, $use_wysiwyg),
            'use_wysiwyg'           => $use_wysiwyg,
            'Comments_HTML'         => $Comments_HTML,
            'xfields'               => $xfields,
            'CSRF'                  => $CSRF
        ),
        array
        (
            'CATEGORY'              => $cat_lines? 1 : 0,
            'SAVED'                 => $saved,
            'USE_AVATAR'            => ($config_use_avatar == 'yes') ? 1 : 0,
            'WYSIWYG'               => $use_wysiwyg,
            'UNAPPROVED'            => ($source == 'unapproved')? 1 : 0,
            'HASCOMMENTS'           => $found_newsid? 1 : 0,
        )
    );

    echofooter();
}
// ********************************************************************************
// Do Edit News
// ********************************************************************************
elseif ($action == "doeditnews")
{
    // Format our categories variable
    if (is_array($category))
    {
        //User has selected multiple categories
        $ccount = 0;
        $nice_category = '';

        foreach ($category as $ckey => $cvalue)
        {
            if ( !in_array($cvalue, $allowed_cats) ) die(lang('Not allowed category'));
            if ( $ccount == 0 ) $nice_category = $cvalue;
            else $nice_category = $nice_category.','.$cvalue;
            $ccount++;
        }
    }
    else
    {
        // Not in a category: don't format $nice_cats because we have not selected any.
        if ( $category != "" and isset($category) and !in_array($category, $allowed_cats) ) die(lang('not allowed category'));
    }

    // Check optional fields
    $more = $optfields = array();
    foreach ($cfg['more_fields'] as $i => $v)
    {
        if ($v[0] != '&' && $_REQUEST[$i] == false)
        {
            $optfields[] = $v;
        }
        else
        {
            if (!empty($_REQUEST[$i]))
                $more[] = spack($i).'='.spack($_REQUEST[$i]);
        }
    }

    if (count($optfields))
        msg('error', LANG_ERROR_TITLE, lang('Some fields can not be blank').': '.implode(', ', $optfields));

    if (trim($title) == "" and $ifdelete != "yes")
        msg("error", LANG_ERROR_TITLE, lang("The title can not be blank"), "javascript:history.go(-1)");

    if ($short_story == "" and $ifdelete != "yes")
        msg("error", LANG_ERROR_TITLE, lang("The story can not be blank"), "javascript:history.go(-1)");

    $n_to_br        = ($if_convert_new_lines == "yes")? 1 : 0;
    $use_html       = ($if_use_html == "yes")? 1 : 0;

    $short_story    = replace_news("add", $short_story, $n_to_br, $use_html);
    $full_story     = replace_news("add", $full_story,  $n_to_br, $use_html);

    $title          = stripslashes( preg_replace(array("'\|'", "'\n'", "''"), array("I", "<br />", ""), $title) );
    $avatar         = stripslashes( preg_replace(array("'\|'", "'\n'", "''"), array("I", "<br />", ""), $avatar) );

    // Check avatar
    if ($editavatar)
    {
        $editavatar = check_avatar($editavatar);
        if ($editavatar == false)
            msg('error', LANG_ERROR_TITLE, lang('Avatar not uploaded'));
    }

    // select news and comment files
    if ($source == "")
    {
        $news_file = SERVDIR."/cdata/news.txt";
        $com_file = SERVDIR."/cdata/comments.txt";
    }
    elseif ($source == "postponed")
    {
        $news_file = SERVDIR."/cdata/postponed_news.txt";
        $com_file = SERVDIR."/cdata/comments.txt";
    }
    elseif ($source == "unapproved")
    {
        $news_file = SERVDIR."/cdata/unapproved_news.txt";
        $com_file = SERVDIR."/cdata/comments.txt";
    }
    else
    {
        $news_file = SERVDIR."/cdata/archives/$source.news.arch";
        $com_file = SERVDIR."/cdata/archives/$source.comments.arch";
    }

    // write
    $old_db = file( $news_file );
    $new_db = fopen( $news_file, "w");
    foreach ($old_db as $old_db_line)
    {
        $old_db_arr = explode("|", $old_db_line);
        if ($id != $old_db_arr[0])
        {
            fwrite($new_db, $old_db_line);
        }
        else
        {
            $have_perm = 0;
            if (($member_db[UDB_ACL] == ACL_LEVEL_ADMIN) or ($member_db[UDB_ACL] == ACL_LEVEL_EDITOR)) $have_perm = 1;

            // Journalist can't edit other pages (with other name)
            elseif ($member_db[UDB_ACL] == ACL_LEVEL_JOURNALIST and $old_db_arr[NEW_USER] == $member_db[UDB_NAME]) $have_perm = 1;

            if ($have_perm)
            {
                if ($ifdelete != "yes")
                {
                    $okchanges = true;
                    $more_fields = join(';', $more);
                    fwrite ($new_db, "$old_db_arr[0]|$old_db_arr[1]|$title|$short_story|$full_story|$editavatar|$nice_category|$old_db_arr[7]|$more_fields\n");
                }
                else
                {
                    $okdeleted  = true;
                    $all_file   = file($com_file);
                    $new_com    = fopen($com_file,"w");

                    foreach ($all_file as $line)
                    {
                        $line_arr = explode("|>|", $line);
                        if ( $line_arr[0] == $id)
                             $okdelcom = true;
                        else fwrite($new_com, $line);
                    }
                    fclose($new_com);
                }
            }
            else
            {
                fwrite($new_db, $old_db_line);
                $no_permission = true;
            }
        }
    }
    fclose($new_db);

    // Show messages
    if ($no_permission)
        msg("error", lang("No Access"), lang("You don't have access for this action"), "$PHP_SELF?mod=editnews&action=list");

    if ($okdeleted)
    {
        if ( $okdelcom )
             msg("info", lang("News Deleted"), lang("The news item successfully was deleted").'.<br />'.lang("If there were comments for this article they are also deleted."));
        else msg("info", lang("News Deleted"), lang("The news item successfully was deleted").'.<br />'.
                                               lang("If there were comments for this article they are also deleted.").'<br /><span style="color:red;">'.
                                               lang("But can not delete comments of this article!")."</span>");
    }
    elseif ($okchanges)
    {
        if ($config_backup_news == 'yes')
        {
            $from = fopen($news_file, "r");
            $news_backup = fopen($news_file.'.bak', "w");
            while (!feof($from)) fwrite($news_backup, fgets($from));
            fclose($from);
            fclose($news_backup);
        }

        header("Location: $PHP_SELF?mod=editnews&action=editnews&id=$id&source=$source&saved=yes");
    }

    else msg("error", LANG_ERROR_TITLE, lang("The news item can not be found or there is an error with the news database file."));
}
elseif ($action == 'move')
{
    $id = intval($id);

    if (preg_match('~^[0-9]*$~', trim($source))) $src = "archives/$source.news.arch";
    elseif ($source) $src = $source.'_news.txt';
    else $src = 'news.txt';

    // Only for present file
    if (!file_exists(SERVDIR . '/cdata/' . $src)) $src = 'news.txt';
    $dbpath = SERVDIR . '/cdata/' . $src;
    $all_db = "\n" . join('', file($dbpath));

    $ps = strpos( $all_db, "\n$id|" );
    if ($ps === false) msg('error', LANG_ERROR_TITLE, lang('ID not found'));

    $ps++;
    $pe = strpos( $all_db, "\n", $ps );

    if ($direct == 'up')
    {
        $i = $ps-2;
        for ($i = $ps-2; $i>0; $i--) if ($all_db[$i] == "\n") break;

        $item0 = trim( substr($all_db, $i+1, $ps-$i-2) );
        $item1 = trim( substr($all_db, $ps, $pe-$ps) );

        $start = $i+1;
        $end   = $pe;

    }
    else
    {
        $i = strpos($all_db, "\n", $pe+1);
        if ($i === false) $i = strlen($all_db);

        $item0 = trim( substr($all_db, $ps, $pe-$ps-1) );
        $item1 = trim( substr($all_db, $pe+1, $i-$pe-1) );

        $start = $ps;
        $end   = $i;
    }

    // Swap lines
    if (trim($item0) && trim($item1))
    {
        $w = fopen($dbpath, 'w');
        fwrite($w, substr($all_db, 1, $start));
        fwrite($w, "$item1\n$item0");
        fwrite($w, substr($all_db, $end));

    }

    $tourl = PHP_SELF.build_uri('mod,action,source', array('editnews','list',$source), false);
    header('Location: '.$tourl);
    die();


}