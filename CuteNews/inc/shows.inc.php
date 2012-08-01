<?php

$CNpass             = isset($_COOKIE['CNpass']) && $_COOKIE['CNpass'] ? $_COOKIE['CNpass'] : false;
$captcha_enabled    = $CNpass ? false : true;

// ---------------------------------------------------------------------------------------------------------------------
do
{
    // plugin tells us: he is fork, stop
    if ( hook('fork_shows_inc', false) ) break;

    // Used if we want to display some error to the user and halt the rest of the script
    $user_query      = cute_query_string($QUERY_STRING, array( "comm_start_from", "start_from", "archive", "subaction", "id", "ucat"));
    $user_post_query = cute_query_string($QUERY_STRING, array( "comm_start_from", "start_from", "archive", "subaction", "id", "ucat"), "post");

    // Define Categories
    $cat = array();
    $cat_lines = file(SERVDIR."/cdata/category.db.php");
    foreach ($cat_lines as $single_line)
    {
        $cat_arr                        = explode("|", $single_line);
        $cat[ $cat_arr[CAT_ID] ]        = $cat_arr[CAT_NAME];
        $cat_icon[ $cat_arr[CAT_ID] ]   = $cat_arr[CAT_ICON];
    }

    // Define Users
    $all_users = file(SERVDIR."/cdata/db.users.php");
    unset ($all_users[0]);

    foreach ($all_users as $user)
    {
        $user_arr = explode("|", $user, 2);
        $user_arr = unserialize($user_arr[1]);

        // nick exists?
        if ($user_arr[UDB_NICK])
        {
            $my_names[$user_arr[UDB_NAME]]     = ($user_arr[UDB_CBYEMAIL] != 1 and $user_arr[UDB_EMAIL])? '<a href="mailto:'.hesc($user_arr[UDB_EMAIL]).'">'.hesc($user_arr[UDB_NICK]).'</a>' : hesc($user_arr[UDB_NICK]);
            $name_to_nick[$user_arr[UDB_NAME]] = $user_arr[UDB_NICK];
        }
        else
        {
            $my_names[$user_arr[UDB_NAME]]     = ($user_arr[UDB_CBYEMAIL] != 1 and $user_arr[UDB_EMAIL])? '<a href="mailto:'.hesc($user_arr[UDB_EMAIL]).'">'.hesc($user_arr[UDB_NAME]).'</a>' : hesc($user_arr[UDB_NAME]);
            $name_to_nick[$user_arr[UDB_NAME]] = $user_arr[UDB_NAME];
        }

        $my_mails[ $user_arr[UDB_NAME] ]   = ($user_arr[UDB_CBYEMAIL] == 1)? "" : $user_arr[UDB_EMAIL];
        $my_passwords[$user_arr[UDB_NAME]] = $user_arr[UDB_PASS];
        $my_users[] = $user_arr[UDB_NAME];

    }

    ResynchronizePostponed();
    if ($config_auto_archive == "yes") ResynchronizeAutoArchive();
    hook('resync_routines');

    // Add Comment -----------------------------------------------------------------------------------------------------
    if ($allow_add_comment)
    {
        extract(filter_request('captcha,name,mail,id'), EXTR_OVERWRITE);
        $name    = isset($_COOKIE['CNname']) && $_COOKIE['CNname'] ? $_COOKIE['CNname'] : $name;

        $captcha = trim($captcha);
        $name    = trim($name);
        $mail    = trim($mail);
        $id      = (int)$id;
        
        //----------------------------------
        // Check the lenght of comment, include name + mail
        //----------------------------------
        $CN_HALT = false;
        if( strlen($name) > 50 )
        {
            echo '<div style="text-align: center;">'.lang('Your name is too long!').'</div>';
            $CN_HALT = true;
            break;
        }
        elseif( strlen($mail) > 50)
        {
            echo '<div style="text-align: center;">'.lang('Your e-mail is too long!').'</div>';
            $CN_HALT = true;
            break;
        }
        elseif ( strlen($comments) > $config_comment_max_long and $config_comment_max_long != "" and $config_comment_max_long != "0")
        {
            echo '<div style="text-align: center;">'.lang('Your comment is too long!').'</div>';
            $CN_HALT = true;
            break;
        }

        //----------------------------------
        // Check if IP is blocked
        //----------------------------------
        $foundip = true;

        if    (getenv("HTTP_CLIENT_IP"))        $ip = getenv("HTTP_CLIENT_IP");
        elseif(getenv("REMOTE_ADDR"))           $ip = getenv("REMOTE_ADDR");
        elseif(getenv("HTTP_X_FORWARDED_FOR"))  $ip = getenv("HTTP_X_FORWARDED_FOR");
        else                                    { $ip = "not detected"; $foundip = false; }

        if ( !preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip) ) $foundip = false;

        if ( ($ids = check_for_ban($ip, $name)) || $foundip == false )
        {

            $is_ban = false;
            list ($kk, $dest) = explode(':', $ids);
            $banuser = bsearch_key($kk, DB_BAN);

            // check IP
            if ($dest)
            {
                // if expire date out or never expired
                if ($banuser[$dest]['E'] == 0 || $banuser[$dest]['E'] >= time())
                {
                    $banuser[$dest]['T']++;
                    edit_key($kk, $banuser, DB_BAN);
                    $is_ban = true;
                }
                else
                {
                    unset($banuser[$dest]);
                    edit_key($kk, $banuser, DB_BAN);
                }
            }
            // check Nickname
            else
            {
                list ($user, $times, $expire) = explode('|', $banuser);
                if ($expire == 0 or $expire >= time() )
                {
                    edit_key($kk, substr($kk,1).'|'.($times+1).'|'.$expire, DB_BAN);
                    $is_ban = true;
                }
                else delete_key($kk, DB_BAN);;
            }

            // user really banned
            if ($is_ban)
            {
                echo '<div class="blocking_posting_comment">'.lang('Sorry but you have been blocked from posting comments').'</div>';
                $CN_HALT = TRUE;
                break;
            }
        }

        //----------------------------------
        // Flood Protection
        //----------------------------------
        if ( $config_flood_time != 0 and $config_flood_time != "" )
        {
            if (flooder($ip, $id) == true)
            {
                echo '<div class="blocking_posting_comment">'.str_replace('%1', $config_flood_time, lang('Flood protection activated! You have to wait %1 seconds after your last comment before posting again at this article')).'</div>';
                $CN_HALT = TRUE;
                break;
            }
        }
        
        //----------------------------------
        // Check if the name is protected
        //----------------------------------
        $user_member = bsearch_key(strtolower($name), DB_USERS);
        if ( $name && empty($user_member) == false )
        {
            $is_member = true;

            // Check stored password in cookies
            if ($CNpass and $user_member[UDB_PASS] == $CNpass) $password = true;

            if (empty($password))
            {
                $comments   = preg_replace( array("'\"'", "'\''", "''"), array("&quot;", "&#039;", ""), $comments);
                $name       = replace_comment("add", preg_replace("/\n/", "", $name));
                $mail       = replace_comment("add", preg_replace("/\n/", "", $mail));

                echo proc_tpl('enter_passcode',
                              array('name' => $name, 'comments' => $comments, 'mail' => $mail, 'ip' => $ip,
                                    'show' => $show, 'ucat' => $ucat, 'user_post_query'=> $user_post_query));

                $CN_HALT = true;
                break;
            }
            else
            {
                $gen = hash_generate($password);

                // password ok?
                if (in_array($user_member[UDB_PASS], $gen) || ($CNpass && $user_member[UDB_PASS] == $CNpass))
                {
                    // if check remember password -> echo this script
                    if (empty($CNrememberPass) == false)
                    {
                        $name = htmlspecialchars($name);
                        echo read_tpl('remember').'<script type="text/javascript">CNRememberPass("'.$user_member[UDB_PASS].'", "'.$name.'", "'.$mail.'")</script>';
                    }

                    // hide email
                    $mail = $user_member[UDB_CBYEMAIL] ? false : $user_member[UDB_EMAIL];
                    $captcha_enabled = false;
                }
                else
                {
                    echo '<div class="blocking_posting_comment">Wrong password! <a href="javascript:location.reload(true)">'.lang('Refresh').'</a></div>';
                    add_to_log($name, lang('Wrong password (posting comment with exist username)'));

                    $CN_HALT = true;
                    break;
                }
            }
        }
        else $is_member = false;

        // ---------------------------------
        // Converting to UTF8 [Try]
        // ---------------------------------
        if (function_exists('iconv'))
        {
            list($hac) = explode(',', $config_default_charset);
            $name      = iconv($hac, 'utf-8', $name);
            $comments  = iconv($hac, 'utf-8', $comments);
        }

        // Captcha test (if not disabled force)
        if ($captcha != $_SESS['CSW'] && $config_use_captcha && $captcha_enabled)
        {
            echo '<div class="blocking_posting_comment">'.lang('Wrong captcha').'! <a href="javascript:location.reload(true)">'.lang('Refresh').'</a></div>';
            add_to_log($ip, 'Attack to captcha');
            $CN_HALT = true;
            break;
        }

        //----------------------------------
        // Check if only members can comment
        //----------------------------------
        if ($config_only_registered_comment == "yes" and !$is_member)
        {
            echo '<div class="blocking_posting_comment">'.lang('Sorry but only registered users can post comments, and').' "'.htmlspecialchars($name).'" '.lang('is not recognized as valid member').'.</div>';
            $CN_HALT = true;
            break;
        }

        //----------------------------------
        // Wrap the long words
        //----------------------------------
        if ($config_auto_wrap > 1)
        {
            $comments_arr = explode("\n", $comments);
            foreach ($comments_arr as $line)
            {
                $wraped_comm .= preg_replace("([^ \/\/]{".$config_auto_wrap."})","\\1\n", $line) ."\n";
            }

            if(strlen($name) > $config_auto_wrap)
                $name = substr($name, 0, $config_auto_wrap)." ...";
            
            $comments = $wraped_comm;
        }

        //----------------------------------
        // Do some validation check 4 name, mail..
        //----------------------------------
        $comments   = replace_comment("add", $comments);
        $name       = replace_comment("add", preg_replace("/\n/", "",$name));
        $mail       = replace_comment("add", preg_replace("/\n/", "",$mail));

        if (trim($name) == false)
        {
            echo '<div class="blocking_posting_comment">'.lang('You must enter name').'.<br /><a href="javascript:history.go(-1)">'.lang('go back').'</a></div>';
            $CN_HALT = true;
            break;
        }

        if (trim($mail) == false) $mail = "none";
        else
        {
            $ok = false;

            if (preg_match("/^[\.A-z0-9_\-\+]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{1,4}$/", $mail))
                $ok = true;

            elseif ($config_allow_url_instead_mail == "yes" and preg_match("/((http(s?):\/\/)|(www\.))([\w\.]+)([\/\w+\.-?]+)/", $mail))
                $ok = true;

            elseif ($config_allow_url_instead_mail != "yes")
            {
                echo '<div class="blocking_posting_comment">'.lang('This is not a valid e-mail').'<br /><a href="javascript:history.go(-1)">'.lang('go back').'</a></div>';
                $CN_HALT = true;
                break;
            }
            else
            {
                echo '<div class="blocking_posting_comment">'.lang('This is not a valid e-mail or site URL').'<br /><a href="javascript:history.go(-1)">'.lang('go back').'</a></div>';
                $CN_HALT = true;
                break;
            }
        }

        if (empty($comments))
        {
            echo '<div class="blocking_posting_comment">'.lang('Sorry but the comment can not be blank').'<br /><a href="javascript:history.go(-1)">'.lang('go back').'</a></div>';
            $CN_HALT = true;
            break;
        }

        $time = time() + $config_date_adjust*60;

        //----------------------------------
        // Hook comment checker
        //----------------------------------
        hook('add_comment_checker');
        if ($CN_HALT) break;

        //----------------------------------
        // Add The Comment ... Go Go GO!
        //----------------------------------

        $old_comments = file($comm_file);
        $new_comments = fopen($comm_file, "w");
        if (!$new_comments) die_stat(503, lang('System error. Try again'));
        flock ($new_comments, LOCK_EX);

        $found = FALSE;
        foreach($old_comments as $old_comments_line)
        {
            $old_comments_arr = explode("|>|", $old_comments_line);
            if($old_comments_arr[0] == $id)
            {
                $old_comments_arr[1] = trim($old_comments_arr[1]);
                fwrite($new_comments, "$old_comments_arr[0]|>|$old_comments_arr[1]$time|$name|$mail|$ip|$comments||\n");
                $found = TRUE;
            }
            else
            {
                // if we do not have the news ID in the comments.txt we are not doing anything (see comment below) (must make sure the news ID is valid)
                fwrite($new_comments, $old_comments_line);
            }
        }

        if(!$found)
        {
            echo '<div class="blocking_posting_comment">'.lang('CuteNews did not added your comment because there is some problem with the comments database').'.<br /><a href="javascript:history.go(-1)">'.lang('go back').'</a></div>';
            $CN_HALT = TRUE;
            break;
        }

        flock ($new_comments, LOCK_UN);
        fclose($new_comments);

        //----------------------------------
        // Sign this comment in the Flood Protection
        //----------------------------------
        if ($config_flood_time != "0" and $config_flood_time != "" )
        {
            $flood_file = fopen(SERVDIR."/cdata/flood.db.php", "a");
            flock ($flood_file, LOCK_EX);
            fwrite($flood_file, time()."|$ip|$id|\n");
            flock ($flood_file, LOCK_UN);
            fclose($flood_file);
        }

        // checkout
        hook('comment_added');

        //----------------------------------
        // Notify for New Comment ?
        //----------------------------------
        if($config_notify_comment == "yes" and $config_notify_status == "active")
        {
            send_mail($config_notify_email, lang("CuteNews - New Comment Added"), lang("New Comment was added by")." $name:\n--------------------------$comments");
        }

        echo '<script type="text/javascript">window.location="'.$PHP_SELF.'?subaction=showfull&id='.$id.'&ucat='.$ucat.'&archive='.$archive.'&start_from='.$start_from.'&'.$user_query.'";</script>';
    }

    // Show Full Story -------------------------------------------------------------------------------------------------
    if ($allow_full_story)
    {
        if (!file_exists($news_file)) die_stat(false, lang("Error! News file does not exists!"));

        // @TODO Search Article [be fixed later]
        $all_active_news = file($news_file);
        foreach ($all_active_news as $active_news)
        {
            $news_arr = explode("|", $active_news);
            
            if ($news_arr[NEW_ID] == $id and (empty($category) or $is_in_category))
            {
                $found       = true;
                $output      = template_replacer_news($news_arr, $template_full, 'full');
                $output      = hook('replace_fullstory', $output);
                $output      = UTF8ToEntities($output);

                // Use php template
                if ( file_exists(SERVDIR.'/cdata/template/fullstory.php'))
                     include (SERVDIR.'/cdata/template/fullstory.php');
                else echo $output;
            }
        }

        if (!$found)
        {
            // Article ID was not found, if we have not specified an archive -> try to find the article in some archive.
            // Auto-Find ID In archives
            //----------------------------------------------------------------------
            echo '<a id="com_form"></a>';

            if (!$archive or $archive == '')
            {
                //get all archives. (if any) and fit our lost id in the most propper archive.

                $lost_id        = $id;
                $all_archives   = false;
                $hope_archive   = false;

                if (!$handle = opendir(SERVDIR."/cdata/archives")) echo ("<!-- ".lang('Can not open directory')." ".SERVDIR."/cdata/archives --> ");

                while (false !== ($file = readdir($handle)))
                {
                    if ($file != "." and $file != ".." and !is_dir(SERVDIR."/cdata/archives/$file") and substr($file, -9) == 'news.arch')
                    {
                        $file_arr = explode(".", $file);
                        $all_archives[] = $file_arr[0];
                    }
                }
                closedir($handle);

                if ($all_archives)
                {
                    sort($all_archives);
                    if (isset($all_archives[1]))
                    {
                        foreach($all_archives as $this_archive) if ($this_archive > $lost_id) { $hope_archive = $this_archive; break; }
                    }
                    elseif ($all_archives[0] > $lost_id) { $hope_archive = $all_archives[0]; break; }
                }
            }

            if ($hope_archive)
            {
                echo '<div>'.lang('You are now being redirected to the article in our archives, if the redirection fails, please').' <a href="'.$PHP_SELF.'?start_from='.$start_from.'&ucat='.$ucat.'&subaction='.$subaction.'&id='.$id.'&archive='.$hope_archive.'&'.$user_query.'">'.lang('click here').'</a></div>
                <script type="text/javascript">window.location="'.$PHP_SELF.'?start_from='.$start_from.'&ucat='.$ucat.'&subaction='.$subaction.'&id='.$id.'&archive='.$hope_archive.'&'.$user_query.'";</script>';
            }
            else
            {
                echo '<div style="text-align: center;">'.lang('Can not find an article with id').': <strong>'. (int)htmlspecialchars($id).'</strong></div>';
            }
            $CN_HALT = TRUE;
            break;
        }
    }

    // Show Comments ---------------------------------------------------------------------------------------------------
    if ($allow_comments)
    {

        $comm_per_page          = $config_comments_per_page;
        $total_comments         = 0;
        $showed_comments        = 0;
        $comment_number         = 0;
        $showed                 = 0;

        if ($config_use_fbcomments == 'yes')
        {
            echo '<div class="fb-comments" data-href="'.$config_http_script_dir.'/router.php?subaction=showfull&amp;id='.$id.'" data-num-posts="'.$config_fb_comments.'" data-width="'.$config_fb_box_width.'"></div>';
        }

        $all_comments = file( $comm_file );
        foreach($all_comments as $comment_line)
        {
            $comment_line       = trim($comment_line);
            $comment_line_arr   = explode("|>|", $comment_line);

            if($id == $comment_line_arr[0])
            {
                $individual_comments = explode("||", $comment_line_arr[1]);
                $total_comments = count($individual_comments) - 1;

                $iteration = 0;
                if ($config_reverse_comments == "yes")
                {
                    $iteration = count($individual_comments) + 1;
                    $individual_comments = array_reverse($individual_comments);
                }

                foreach ($individual_comments as $comment)
                {
                    $iteration = ($config_reverse_comments == "yes") ? $iteration-1 : $iteration + 1;

                    $comment_arr = explode("|", $comment);
                    if($comment_arr[0] != "")
                    {
                        if(isset($comm_start_from) and $comm_start_from != "")
                        {
                            if($comment_number < $comm_start_from)
                            {
                                $comment_number++;
                                continue;
                            }
                            elseif ($showed_comments == $comm_per_page) break;
                        }

                        $comment_number ++;
                        $comment_arr[4] = stripslashes(rtrim($comment_arr[4]));

                        if ($comment_arr[2] != "none")
                        {
                            $mail_or_url = false;
                            if ( preg_match("/^[\.A-z0-9_\-\+]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{1,4}$/", $comment_arr[2]))
                            {
                                $url_target = "";
                                $mail_or_url = "mailto:";
                            }
                            else
                            {
                                $url_target = 'target="_blank"';
                                $mail_or_url = "";
                                if (substr($comment_arr[2],0,3) == "www") $mail_or_url = "http://";
                            }
                            $output = str_replace("{author}", "<a $url_target href=\"$mail_or_url".stripslashes($comment_arr[2])."\">".stripslashes($comment_arr[1])."</a>", $template_comment);

                        }
                        else
                        {
                            $output = str_replace("{author}", $comment_arr[1], $template_comment);
                        }

                        $comment_arr[4] = preg_replace("/\b((http(s?):\/\/)|(www\.))([\w\.]+)([&-~\%\/\w+\.-?]+)\b/i", "<a href=\"http$3://$4$5$6\" target=\"_blank\">$2$4$5$6</a>", $comment_arr[4]);
                        $comment_arr[4] = preg_replace("/([\w\.]+)(@)([-\w\.]+)/i", "<a href=\"mailto:$0\">$0</a>", $comment_arr[4]);
                        
                        $output         = str_replace("{mail}", $comment_arr[2], $output);
                        $output         = str_replace("{date}", date($config_timestamp_comment, $comment_arr[0]),$output);
                        $output         = str_replace("{year}", date("Y", $comment_arr[0]), $output);
                        $output         = embedateformat($news_arr[0], $output);
                        $output         = str_replace("{day}", date("d", $comment_arr[0]), $output);
                        $output         = str_replace("{hours}", date("H:i", $comment_arr[0]), $output);

                        $output         = str_replace("{comment-id}", $comment_arr[0],$output);
                        $output         = str_replace("{comment}", "<a name=\"".$comment_arr[0]."\"></a>".$comment_arr[4], $output);
                        $output         = str_replace("{comment-iteration}", $iteration ,$output);
                        $output         = replace_comment("show", $output);

                        // Use php template
                        $output         = UTF8ToEntities($output);
                        if ( file_exists(SERVDIR.'/cdata/template/comment.php'))
                            include (SERVDIR.'/cdata/template/comment.php');
                        else echo $output;

                        $showed_comments++;
                        
                        if($comm_per_page != 0 and $comm_per_page == $showed_comments) break;
                    }
                }
            }
        }

        //----------------------------------
        // Prepare the Comment Pagination
        //----------------------------------

        $prev_next_msg = $template_comments_prev_next;

        // Previous link
        if ($comm_start_from)
        {
            $prev = $comm_start_from - $comm_per_page;
            $prev_next_msg = preg_replace("'\[prev-link\](.*?)\[/prev-link\]'si", "<a href=\"$PHP_SELF?comm_start_from=$prev&amp;archive=$archive&amp;subaction=showcomments&amp;id=$id&amp;ucat=$ucat&amp;$user_query\">\\1</a>", $prev_next_msg);
        }
        else
        {
            $prev_next_msg = preg_replace("'\[prev-link\](.*?)\[/prev-link\]'si", "\\1", $prev_next_msg);
            $no_prev = TRUE;
        }

        // Pages
        if ($comm_per_page)
        {
            $pages_count        = ceil($total_comments / $comm_per_page);
            $pages_start_from   = 0;
            $pages              = "";

            for ($j=1; $j<=$pages_count; $j++)
            {
                if( $pages_start_from != $comm_start_from )
                     $pages .= '<a href="'.$PHP_SELF.'?comm_start_from='.$pages_start_from.'&amp;archive='.$archive.'&amp;subaction=showcomments&amp;id='.$id.'&amp;ucat='.$ucat.'&amp;'.$user_query.'">'.$j.'</a> ';
                else $pages .= ' <strong>'.$j.'</strong> ';

                $pages_start_from += $comm_per_page;
            }

            $prev_next_msg = str_replace("{pages}", $pages, $prev_next_msg);
        }

        // Next link
        if ($comm_per_page < $total_comments and $comment_number < $total_comments)
        {
            $prev_next_msg = preg_replace("'\[next-link\](.*?)\[/next-link\]'si", "<a href=\"$PHP_SELF?comm_start_from=$comment_number&amp;archive=$archive&amp;subaction=showcomments&amp;id=$id&amp;ucat=$ucat&amp;$user_query\">\\1</a>", $prev_next_msg);
        }
        else
        {
            $prev_next_msg = preg_replace("'\[next-link\](.*?)\[/next-link\]'si", "\\1", $prev_next_msg);
            $no_next = true;
        }

        if (empty($no_prev) or empty($no_next)) echo $prev_next_msg;

        $template_form = str_replace("{config_http_script_dir}", $config_http_script_dir, $template_form);
        
        //----------------------------------
        // Check if the remember script exists
        //----------------------------------
        $gduse         = function_exists('imagecreatetruecolor')? 0 : 1;
        $captcha_form  = $config_use_captcha && $captcha_enabled ? ( proc_tpl('captcha_comments', array('cutepath' => $config_http_script_dir ), array('TEXTCAPTCHA' => $gduse) ) ) : false;

        $smilies_form  = proc_tpl('remember_js') . insertSmilies('short', FALSE) . $captcha_form;
        $template_form = str_replace("{smilies}", $smilies_form, $template_form);
        $template_form = hook('comment_template_form', $template_form);

        echo over_tpl('comment_form', array($template_form, $ucat, $show, $user_post_query,
                                      read_tpl('remember').'<script type="text/javascript">CNreadCookie();</script>'));
    }

    // Active News -----------------------------------------------------------------------------------------------------
    if ($allow_active_news)
    {

        $in_use         = 0;
        $used_archives  = array();
        $all_news       = file($news_file);

        if ($reverse == true) $all_news = array_reverse($all_news);
        if ($orderby == 'R')  shuffle($all_news);
        elseif ($orderby) $all_news = quicksort($all_news, $orderby);

        // Search last comments
        if ( !empty($sortbylastcom) )
        {
            $garnews = array();
            foreach ($all_news as $nl) { list ($id) = explode('|', $nl, 2); $garnews[$id] = $nl; }
            $all_news = array();

            $all_comments = file($comm_file);
            $all_comments = preg_replace('~^(\d+)\|>\|((\d+)\|.*?\|.*?\|.*?\|.*?\|.*?\|)*~im', '\\3.\\1', $all_comments);
            arsort($all_comments);
            foreach ($all_comments as $pm) if ( $nl = rtrim($garnews[ (int)(substr($pm, strpos($pm, '.') + 1)) ]) ) $all_news[] = $nl;
        }

        hook('news_reorder', $all_news);

        $count_all = 0;
        if (isset($category) and $category)
        {
            foreach($all_news as $news_line)
            {
                $news_arr = explode("|", $news_line);
                $is_in_cat = false;
                if (strstr($news_arr[6], ','))
                {
                    // if the article is in multiple categories
                    $this_cats_arr = explode(',',$news_arr[6]);
                    foreach($this_cats_arr as $this_single_cat)
                    {
                        if (isset($requested_cats[$this_single_cat]) && isset($requested_cats[$this_single_cat])) $is_in_cat = TRUE;
                    }
                }
                elseif (isset($requested_cats[$news_arr[6]]) && isset($requested_cats[$news_arr[6]])) $is_in_cat = TRUE;

                if ($is_in_cat) $count_all++; else continue;
            }
        }
        else $count_all = count($all_news);

        $i              = 0;
        $showed         = 0;
        $repeat         = true;
        $url_archive    = $archive;

        while ($repeat)
        {
            foreach ($all_news as $news_line)
            {
                $is_in_cat = false;
                $news_arr = explode("|", $news_line);

                if (strstr($news_arr[6], ','))
                {
                    //if the article is in multiple categories
                    $this_cats_arr = explode(',',$news_arr[6]);
                    foreach ($this_cats_arr as $this_single_cat)
                    {
                        if (isset($requested_cats[$this_single_cat]) && isset($requested_cats[$this_single_cat])) $is_in_cat = true;
                    }

                }
                elseif (isset($requested_cats[$news_arr[6]]) && isset($requested_cats[$news_arr[6]])) $is_in_cat = true;

                // if User_By, show news only for this user
                if ( !empty($user_by) && $user_by != $news_arr[NEW_USER]) { $count_all--; continue; }

                if (!$is_in_cat and isset($category) and $category) continue;
                if ($start_from)
                {
                    if ($i < $start_from)
                    {
                        $i++;
                        continue;
                    }
                    elseif ($showed == $number) break;
                }

                // show data
                $my_author   = $my_names[$news_arr[1]] ? $my_names[$news_arr[1]] : $news_arr[1];

                // Basic replacements
                $output      = template_replacer_news($news_arr, $template_active, 'short');
                $output      = hook('replace_activenews', $output);
                $output      = UTF8ToEntities($output);

                if ( file_exists(SERVDIR.'/cdata/template/activenews.php'))
                     include (SERVDIR.'/cdata/template/activenews.php');
                else echo $output;

                $i++;
                $showed++;

                // allow use fb comments
                if ($config_use_fbcomments == 'yes' && $config_fb_inactive == 'yes')
                {
                    echo '<div class="fb-comments" data-href="'.$config_http_script_dir.'/router.php?subaction=showfull&amp;id='.$news_arr[0].'" data-num-posts="'.$config_fb_comments.'" data-width="'.$config_fb_box_width.'"></div>';
                }

                if ($number and $number == $i) break;
            }

            // External archive $archive is already used
            $archives_arr = array();
            $used_archives[$archive] = true;

            // Archives Loop [IF $only_active = false]
            if ($i < $number and empty($only_active))
            {
                // get archives ids
                if (!$handle = opendir(SERVDIR . "/cdata/archives")) die_stat(false, '<div class="cutenews-warning">'.lang('Can not open directory').' '.SERVDIR.'/cdata/archives</div>');
                while (false !== ($file = readdir($handle)))
                {
                    if ($file != "." and $file != ".." and substr($file, -9) == 'news.arch')
                    {
                        list($archid) = explode(".", $file);
                        if (empty($used_archives[$archid])) $archives_arr[$archid] = $archid;
                    }
                }
                closedir($handle);

                // get max archive id to show
                $in_use = max($archives_arr);
                if ( $in_use )
                {
                    $archive                = $in_use;
                    $all_news               = file(SERVDIR."/cdata/archives/$in_use.news.arch");
                    $used_archives[$in_use] = true;
                }
                else $repeat = false;

            }
            else $repeat = false;
        }

        // << Previous & Next >>
        $prev_next_msg = $template_prev_next;

        //----------------------------------
        // Previous link
        //----------------------------------
        if ($start_from)
        {
            $prev = $start_from - $number;
            $prev_next_msg = preg_replace("'\[prev-link\](.*?)\[/prev-link\]'si", "<a href=\"$PHP_SELF?start_from=$prev&amp;ucat=$ucat&amp;archive=$url_archive&amp;subaction=$subaction&amp;id=$id&amp;$user_query\">\\1</a> ", $prev_next_msg);
        }
        else
        {
            $prev_next_msg = preg_replace("'\[prev-link\](.*?)\[/prev-link\]'si", "\\1", $prev_next_msg);
            $no_prev = true;
        }

        //----------------------------------
        // Pages
        //----------------------------------
        if ($number)
        {
            $pages_count        = ceil($count_all / $number);
            $pages_start_from   = 0;
            $pages              = "";

            for($j=1; $j<= $pages_count; $j++)
            {
                if ( $pages_start_from != $start_from)
                     $pages .= '<a href="'.$PHP_SELF.'?start_from='.$pages_start_from.'&amp;ucat='.$ucat.'&amp;archive='.$url_archive.'&amp;subaction='.$subaction.'&amp;id='.$id.'&amp;'.$user_query.'">'.$j.'</a> ';
                else $pages .= '<strong>'.$j.'</strong> ';
                $pages_start_from += $number;
            }
            
            $prev_next_msg = str_replace("{pages}", $pages, $prev_next_msg);
        }
        
        //----------------------------------
        // Next link  (typo here ... typo there... typos everywhere !)
        //----------------------------------
        if($number < $count_all and $i < $count_all)
        {
            $prev_next_msg = preg_replace("'\[next-link\](.*?)\[/next-link\]'si", "<a href=\"$PHP_SELF?start_from=$i&amp;ucat=$ucat&amp;archive=$url_archive&amp;subaction=$subaction&amp;id=$id&amp;$user_query\">\\1</a>", $prev_next_msg);
        }
        else
        {
            $prev_next_msg = preg_replace("'\[next-link\](.*?)\[/next-link\]'si", "\\1", $prev_next_msg);
            $no_next = TRUE;
        }
        if (!$no_prev or !$no_next) echo $prev_next_msg;
        
    }

}
while (false);

// ---------------------------------------------------------------------------------------------------------------------
if ((!isset($count_cute_news_includes) or !$count_cute_news_includes) and $template != 'rss')
{
    /// Removing the "Powered By..." line is NOT allowed by the CuteNews License, only registered users are alowed to do so.
    if (!file_exists(SERVDIR."/cdata/reg.php"))
    {
        echo base64_decode('PGRpdiBzdHlsZT0ibWFyZ2luLXRvcDoxNXB4O3dpZHRoOjEwMCU7dGV4dC1hbGlnbjpjZW50ZXI7Zm9udDo5cHggVmVyZGFuYTsiPkNvbnRlbnQgTWFuYWdlbWVudCBQb3dlcmVkIGJ5IDxhIGhyZWY9Imh0dHA6Ly9jdXRlcGhwLmNvbS8iIHRpdGxlPSJDdXRlTmV3cyAtIFBIUCBOZXdzIE1hbmFnZW1lbnQgU3lzdGVtIj5DdXRlTmV3czwvYT48L2Rpdj4=');
    }
    else
    {
        include(SERVDIR."/cdata/reg.php");
        if( !preg_match('/\\A(\\w{6})-\\w{6}-\\w{6}\\z/', $reg_site_key, $mmbrid))
        {
            echo base64_decode('PGRpdiBzdHlsZT0ibWFyZ2luLXRvcDoxNXB4O3dpZHRoOjEwMCU7dGV4dC1hbGlnbjpjZW50ZXI7Zm9udDo5cHggVmVyZGFuYTsiPkNvbnRlbnQgTWFuYWdlbWVudCBQb3dlcmVkIGJ5IDxhIGhyZWY9Imh0dHA6Ly9jdXRlcGhwLmNvbS8iIHRpdGxlPSJDdXRlTmV3cyAtIFBIUCBOZXdzIE1hbmFnZW1lbnQgU3lzdGVtIj5DdXRlTmV3czwvYT48L2Rpdj4=');
        }
    }
}

$count_cute_news_includes++;