<?php

    $NotHeaders = true;
    require_once ('core/init.php');

    // plugin tells us: he is fork, stop
    if ( hook('fork_search', false) ) return;

    // Check including
    $Uri = '//'.dirname( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
    if (strpos($config_http_script_dir, $Uri) !== false && strpos($PHP_SELF, 'search.php') !== false)
       die_stat(403, 'Wrong including search.php! Check manual to get more information about this issue.');

    // Autodate
    if ( empty($from_date_day) ) $from_date_day = date('d', 0);
    if ( empty($from_date_month) ) $from_date_month = date('m', 0);
    if ( empty($from_date_year) ) $from_date_year = 2003;

    if ( empty($to_date_day) ) $to_date_day = date('d', time() + 3600*24);
    if ( empty($to_date_month) ) $to_date_month = date('m', time());
    if ( empty($to_date_year) ) $to_date_year = date('Y', time());

    $files_arch = array();

    // check for bad _GET and _POST
    $user_query         = cute_query_string($QUERY_STRING, array("search_in_archives", "start_from", "archive", "subaction", "id", "cnshow", "ucat","dosearch", "story", "title", "user", "from_date_day", "from_date_month", "from_date_year", "to_date_day", "to_date_month", "to_date_year"));
    $user_post_query    = cute_query_string($QUERY_STRING, array("search_in_archives", "start_from", "archive", "subaction", "id", "cnshow", "ucat","dosearch", "story", "title", "user", "from_date_day", "from_date_month", "from_date_year", "to_date_day", "to_date_month", "to_date_year"), "post");

    // Define Users
    $all_users = file(SERVDIR."/cdata/db.users.php");
    unset($all_users[0]);
    $my_names = array();
    foreach ($all_users as $my_user)
    {
        $user_arr = explode("|", $my_user, 2);
        $user_arr = unserialize($user_arr[1]);
        if ($user_arr[UDB_NICK] != "")
             $my_names[$user_arr[UDB_NAME]] = $user_arr[UDB_NICK];
        else $my_names[$user_arr[UDB_NAME]] = $user_arr[UDB_NAME];
    }

    if ( empty($search_form_hide) || isset($search_form_hide) && empty($dosearch) )
    {

        // Make parameters -----------------------------------------------------------------------------------------------------
        $day_f = $month_f = $year_f = false;
        $day_t = $month_t = $year_t = false;

        for ($i=1; $i<32; $i++) $day_f .= "<option ".(($from_date_day == $i)?'selected':'')." value=$i>$i</option>";
        for ($i=1; $i<13; $i++) { $timestamp = mktime(0,0,0,intval($i),1,2003); $month_f .= "<option ".(($from_date_month == $i)?'selected':'')." value=$i>".date("M", $timestamp)."</option>"; }
        for ($i=2003; $i<(date('Y')+3); $i++) $year_f .= "<option ".(($from_date_year == $i)?'selected':'')." value=$i>$i</option>";

        for ($i=1; $i<32; $i++) $day_t .= "<option ".(($to_date_day == $i)?'selected':'')." value=$i>$i</option>";
        for ($i=1; $i<13; $i++) { $timestamp = mktime(0,0,0,intval($i),1,2003); $month_t .= "<option ".(($to_date_month == $i)?'selected':'')." value=$i>".date("M", $timestamp)."</option>"; }
        for ($i=2003; $i<(date('Y')+3); $i++) $year_t .= "<option ".(($to_date_year == $i)?'selected':'')." value=$i>$i</option>";

        // libastral.so?
        $selected_search_arch = $search_in_archives ? "checked='checked'" : false;
        $template_search = read_tpl('search');
        echo str_replace
        (
            array('{PHP_SELF}', '{user_post_query}', '{story}', '{title}', '{user}', '{selected_search_arch}',
                  '{day_f}', '{month_f}', '{year_f}', '{day_t}', '{month_t}', '{year_t}'),
            array( $PHP_SELF,
                   $user_post_query,
                   htmlspecialchars($story),
                   htmlspecialchars($title),
                   htmlspecialchars($user),
                   $selected_search_arch,
                   $day_f,
                   $month_f,
                   $year_f,
                   $day_t,
                   $month_t,
                   $year_t),

            $template_search
        );
    }

    // fulltext search [find for BM25]
    if ($dosearch == "yes")
    {
        $mc_start = microtime(true);

        $exstory = array();
        $optimas = array();
        $countdc = 0;
        $countfd = 0;
        $szi = preg_split('~\s~', utf8_strtolower(preg_replace('~([\x00-\x1F]|[\x21-\x2F]|[\x3A-\x40]|[\x5C-\x60]|[\x7B-\x7E])~', '', strip_tags($story))));

        foreach ($szi as $v) if ($v)
        {
            $word = strtolower(preg_replace('~(ies|ing|y|s|es|er|ed)$~','', $v));
            $exstory[$word]++;
        }

        $date_from  = mktime(0, 0, 0, $from_date_month, $from_date_day, $from_date_year);
        $date_to    = mktime(0, 0, 0, $to_date_month, $to_date_day, $to_date_year);

        $story = trim($story);
        $files_arch = array();

        if ($search_in_archives)
        {
            if(!$handle = opendir(SERVDIR."/cdata/archives")){ die("Can not open directory ".SERVDIR."/cdata/archives "); }
            while (false !== ($file = readdir($handle)))
            {
                if ($file != "." and $file != ".." and strpos($file, 'news') !== false)
                    $files_arch[] = SERVDIR."/cdata/archives/$file";

            }
        }

        $files_arch[] = SERVDIR."/cdata/news.txt";

        foreach ($files_arch as $file)
        {
            $archive = 0;
            if (preg_match('/([[:digit:]]{0,})\.news\.arch/', $file, $regs)) $archive = $regs[1];

            $all_news_db = file($file);
            $countdc    += count($all_news_db);
            $findout     = array();

            // Garbare all information
            foreach ($all_news_db as $news_line)
            {
                $news_db_arr = explode("|", $news_line);
                $found  = 0;

                // skip data
                if ( $news_db_arr[NEW_ID] < $date_from || $date_to < $news_db_arr[NEW_ID] ) continue;
                if ( $user && preg_match("/\b$user\b/i", $news_db_arr[NEW_USER]) == false ) continue;
                if ( $title && preg_match("/$title/i", $news_db_arr[NEW_TITLE]) == false ) continue;

                $grab = array();
                foreach (preg_split('~\s~', strip_tags($news_db_arr[NEW_FULL])) as $v) if ($v)
                {

                    $word = preg_replace('~(ies|ing|y|s|es|er|ed)$~','', $v);
                    $word = preg_replace('~([\x00-\x1F]|[\x21-\x2F]|[\x3A-\x40]|[\x5C-\x60]|[\x7B-\x7E])~', '', $word);
                    $word = utf8_strtolower($word);
                    if ($word) $findout[$word][ $news_db_arr[NEW_ID] ]++;
                }

                foreach (preg_split('~\s~', strip_tags($news_db_arr[NEW_SHORT])) as $v) if ($v)
                {
                    $word = preg_replace('~(ies|ing|y|s|es|er|ed)$~','', $v);
                    $word = preg_replace('~([\x00-\x1F]|[\x21-\x2F]|[\x3A-\x40]|[\x5C-\x60]|[\x7B-\x7E])~', '', $word);
                    $word = utf8_strtolower($word);
                    if ($word) $findout[$word][ $news_db_arr[NEW_ID] ]++;
                }
            }

            foreach ($exstory as $word => $is)
            {
                if ( isset($findout[$word]) and is_array($findout[$word]) )
                     foreach ($findout[$word] as $newsid => $cnt)
                         $optimas[$archive][$newsid] += $cnt;
            }

        }

        // sort output results
        ksort($optimas);
        foreach ($optimas as $i => $wrds)
        {
            natsort($wrds);
            $wx = array();
            foreach ($wrds as $k => $x) $wx[] = $k;
            $optimas[$i] = $wx;
        }

        echo "<br /><b>".lang('Founded News articles')." [". count($optimas)."]</b> ";
        echo str_replace(array('%1','%2'), array(date("d F Y", $date_from), date("d F Y", $date_to)), lang("from <b>%1</b> to <b>%2</b><br/>"));

        // Display Search Results
        if ($optimas)
        {
            foreach ($optimas as $archive => $garbare)
            {
                // for archives slower than active news
                if  ($archive)
                     $all_news = file(SERVDIR."/cdata/archives/$archive.news.arch");
                else $all_news = file(SERVDIR."/cdata/news.txt");

                foreach ($all_news as $single_line)
                {
                    $item_arr = explode("|", $single_line);
                    $local_id = $item_arr[0];

                    if ( in_array($local_id, $garbare) )
                    {
                        if  ($archive)
                             echo "<br/><b><a href=\"$PHP_SELF?misc=search&subaction=showfull&id=$local_id&archive=$archive&cnshow=news&ucat=$item_arr[6]&start_from=&$user_query\">$item_arr[2]</a></b> (". date("d F, Y", $item_arr[0]) .")";
                        else echo "<br/><b><a href=\"$PHP_SELF?misc=search&subaction=showfull&id=$item_arr[0]&cnshow=news&ucat=$item_arr[6]&start_from=&$user_query\">$item_arr[2]</a></b> (". date("d F, Y", $item_arr[0]) .")";
                    }
                }
            }
        }
        else echo "<p>".lang('There are no news matching your search criteria')."</p>";

        echo '<p class="search_results"><i>'.lang('Search performed for').' '.round(microtime(true) - $mc_start, 4).' s.</i></p>';
    }

    // if user wants to search
    elseif ( ($misc == "search") and ($subaction == "showfull" or $subaction == "showcomments" or $_POST["subaction"] == "addcomment" or $subaction == "addcomment"))
    {
        require_once(SERVDIR."/show_news.php");
        unset($action, $subaction);
    }

    unset($search_form_hide, $dosearch);
