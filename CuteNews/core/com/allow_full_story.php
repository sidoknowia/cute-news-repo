<?php

    if (!defined('INIT_INSTANCE')) die('Access restricted');

    $all_active_news = file($news_file);
    foreach ($all_active_news as $active_news)
    {
        $news_arr = explode("|", $active_news);
        if ($news_arr[NEW_ID] == $id and (empty($category) or $is_in_category))
        {
            $found       = true;
            $output      = template_replacer_news($news_arr, $template_full);
            $output      = hook('replace_fullstory', $output);
            $output      = UTF8ToEntities($output);
            echo $output;
        }
    }

    if($config_use_fblike == 'yes')
    {
        $float = '';
        if($config_use_twitter == 'yes') $float = 'style="float: left;"';
        echo '<div class="fb-like" data-send="'.($config_fblike_send_btn=="yes"?"true":"false").'" data-layout="'.$config_fblike_style.'" data-width="'.$config_fblike_width.'" data-show-faces="'.($config_fblike_show_faces=="yes"?"true":"false").'" data-font="'.$config_fblike_font.'" data-colorscheme="'.$config_fblike_color.'" data-action="'.$config_fblike_verb.'" '.$float.'></div>';
    }

    if($config_use_twitter == 'yes')
        echo '<div><a href="https://twitter.com/share" class="twitter-share-button" data-url="'.trim($config_tw_url).'" data-text="'.trim($config_tw_text).'" data-via="'.trim($config_tw_via).'" data-related="'.trim($config_tw_recommended).'" data-count="'.$config_tw_show_count.'" data-hashtags="'.trim($config_tw_hashtag).'" data-lang="'.$config_tw_lang.'" data-size="'.($config_tw_size=="yes"?"large":"medium").'"></a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></div>';
    // Article ID was not found, if we have not specified an archive -> try to find the article in some archive.
    // Auto-Find ID In archives
    //----------------------------------------------------------------------
    if (!$found)
    {
        echo '<a id="com_form"></a>';

        if (!$archive or $archive == '')
        {
            // Get all archives. (if any) and fit our lost id in the most propper archive.
            $lost_id        = $id;
            $all_archives   = false;
            $hope_archive   = false;

            if (!$handle = opendir(SERVDIR."/cdata/archives")) echo ("<!-- ".lang('cannot open directory')." ".SERVDIR."/cdata/archives --> ");

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
                elseif ($all_archives[0] > $lost_id)
                {
                    $hope_archive = $all_archives[0];
                    return FALSE;
                }
            }
        }

        if ($hope_archive)
        {
            $URL = $PHP_SELF.build_uri('archive,start_from,ucat,subaction,id', array($hope_archive));
            echo '<div>'.lang('You are now being redirected to the article in our archives, if the redirection fails, please').' <a href="'.$URL.'">'.lang('click here').'</a></div>
                    <script type="text/javascript">window.location="'.str_replace('&amp;', '&', $URL).'";</script>';
        }
        else
        {
            echo '<div style="text-align: center;">'.lang('Cannot find an article with id').': <strong>'. (int)htmlspecialchars($id).'</strong></div>';
        }
        return FALSE;
    }

    return TRUE;