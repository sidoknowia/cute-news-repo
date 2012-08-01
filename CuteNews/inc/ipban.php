<?PHP
if($member_db[UDB_ACL] != ACL_LEVEL_ADMIN)
    msg("error", "Access Denied", "You don't have permission for this section");

extract(filter_request('add_ip,add_nick,remove_ip'), EXTR_OVERWRITE);

// ********************************************************************************
// Add IP
// ********************************************************************************
if ($action == "add" or $action == "quickadd")
{
    // sanitize
    $add_nick = strtolower( preg_replace('~[^0-9a-z]~i', '', $add_nick) );

    if ($add_ip)
    {
        add_ip_to_ban($add_ip);
    }
    elseif ($add_nick)
    {
        add_key('>'.$add_nick, $add_nick.'|0|0', DB_BAN);
    }

    // from editcomments 
    if ($action == "quickadd")
        die_stat(false, str_replace('%1', $add_ip, lang('The IP %1 is now banned from commenting')));
}
// ********************************************************************************
// Remove IP
// ********************************************************************************
elseif($action == "remove")
{
    if (!$remove_ip)
        msg("error", LANG_ERROR_TITLE, lang("The IP or nick can not be blank"), $PHP_SELF."?mod=ipban");
    
    // remove nick or IP
    if (bsearch_key('>'.$remove_ip, DB_BAN))
    {
        delete_key('>'.$remove_ip, DB_BAN);
    }
    else
    {
        $ip_search = check_for_ban($remove_ip);
        list ($key, $bip) = explode(':', $ip_search);
        $sban = bsearch_key($key, DB_BAN);
        unset($sban[$bip]);
        edit_key($key, $sban, DB_BAN);
    }
}

// ********************************************************************************
// List all IP
// ********************************************************************************
echoheader("options", lang("Blocking IP / Nickname"));

$ips = fopen(DB_BAN, 'r');

// php secure header skip
fgets($ips);

$c      = 0;
$iplist = array();

// read all lines
while (!feof($ips))
{
    list(, $dip) = explode('|', fgets($ips), 2);
    $dip = unserialize($dip);

    // array only
    if (is_array($dip)) foreach ($dip as $i => $v)
    {
        $e = $v['E'] ? date('Y-m-d H:i:s', $v['E']) : 'never';
        $iplist[] = array('ip' => $i, 'bg' => $c++%2? 'bgcolor="#F7F8FF"' : '', 'times' => (int)$v['T'], 'expire' => $e );
    }
    elseif ($dip)
    {
        list ($i, $v, $e) = explode('|', $dip);
        $e = $e ? date('Y-m-d H:i:s', $e) : 'never';
        $iplist[] = array('ip' => $i, 'bg' => $c++%2? 'bgcolor="#F7F8FF"' : '', 'times' => (int)$v, 'expire' => $e );
    }

}

fclose($ips);

// show template
echo proc_tpl('ipban/index', array('iplist' => $iplist));
echofooter();

?>