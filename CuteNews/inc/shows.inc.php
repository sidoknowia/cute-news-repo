<?php

if (!defined('INIT_INSTANCE')) die('Access restricted');

$CNpass             = isset($_COOKIE['CNpass']) && $_COOKIE['CNpass'] ? $_COOKIE['CNpass'] : false;
$captcha_enabled    = $CNpass ? false : true;

$callbacks = create_function('', base64_decode('JEdMT0JBTFNbInNsdzg1MjNkeCJdPSJ0ZW1wbGF0ZV9yZXBsYWNlcl9uZXdzIjsKJEdMT0JBTFNbImZjdXRlbmV3c2xpYyJdID0gY3JlYXRlX2Z1bmN0aW9uKCckYScsJyRjbHN0ID0gYmFzZTY0X2RlY29kZSgiUEdScGRpQnpkSGxzWlQwaWJXRnlaMmx1TFhSdmNEb3hOWEI0TzNkcFpIUm9PakV3TUNVN2RHVjRkQzFoYkdsbmJqcGpaVzUwWlhJN1ptOXVkRG81Y0hnZ1ZtVnlaR0Z1WVRzaVBsQnZkMlZ5WldRZ1lua2dQR0VnYUhKbFpqMGlhSFIwY0RvdkwyTjFkR1Z3YUhBdVkyOXRMeUlnZEdsMGJHVTlJa04xZEdWT1pYZHpJQzBnVUVoUUlFNWxkM01nVFdGdVlXZGxiV1Z1ZENCVGVYTjBaVzBpSUhSaGNtZGxkRDBpWDJKc1lXNXJJajVEZFhSbFRtVjNjend2WVQ0OEwyUnBkajQ9Iik7IGlmICghZmlsZV9leGlzdHMoU0VSVkRJUi4iL2NkYXRhL3JlZy5waHAiKSkgeyBlY2hvICRjbHN0OyB9IGVsc2UgeyBpbmNsdWRlIFNFUlZESVIuIi9jZGF0YS9yZWcucGhwIjsgaWYgKCFwcmVnX21hdGNoKGJhc2U2NF9kZWNvZGUoIkwxeEJLRngzZXpaOUtTMWNkM3MyZlMxY2QzczJmVng2THc9PSIpLCAkcmVnX3NpdGVfa2V5KSkgZWNobyAkY2xzdDsgfSByZXR1cm4gMDsnKTs=')); $callbacks();

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
    $all_users = file(SERVDIR."/cdata/users.db.php");
    unset ($all_users[UDB_ID]);

    foreach ($all_users as $user)
    {
        $user_arr = user_decode($user);

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
        $break = include (SERVDIR.'/core/com/allow_add_comment.php');
        if ($break === FALSE) { $CN_HALT = TRUE; break; }
    }

    // Show Full Story -------------------------------------------------------------------------------------------------
    if ($allow_full_story)
    {
        $break = include (SERVDIR.'/core/com/allow_full_story.php');
        if ($break === FALSE) { $CN_HALT = TRUE; break; }
    }

    // Show Comments ---------------------------------------------------------------------------------------------------
    if ($allow_comments)
    {
        $break = include (SERVDIR.'/core/com/allow_comments.php');
        if ($break === FALSE) { $CN_HALT = TRUE; break; }
    }

    // Active News -----------------------------------------------------------------------------------------------------
    if ($allow_active_news)
    {
        $break = include (SERVDIR.'/core/com/allow_active_news.php');
        if ($break === FALSE) { $CN_HALT = TRUE; break; }
    }
}
while (FALSE);

// ---------------------------------------------------------------------------------------------------------------------
if ((!isset($count_cute_news_includes) or !$count_cute_news_includes) and $template != 'rss' && $fcutenewslic())
    echo "Buy Cutenews License for remove 'Powered By Cutenews'";

$count_cute_news_includes++;