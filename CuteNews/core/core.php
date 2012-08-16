<?php

// Cache-functions
function fv_serialize($file, $data)
{
    $fn = SERVDIR.CACHE.'/'.$file.'.php';
    $fx = fopen($fn, 'w');
    fwrite($fx, "<?php die(); ?>\n" . serialize( (array)$data) );
    fclose($fx);
}

// ------------------------------------- DB on files -------------------------------------------------------------------
// Key insert
function add_key($id, $value, $file)
{
    // init
    $ms         = array();
    $lock       = false;
    $xs         = file($file);
                  unset($xs[0]);
    
    // sort keys
    if ( !is_array($id) ) $id = array( $id => $value );
    foreach ($id as $i => $v) $ms[ md5($i) ] = $v;

    ksort($ms);
    reset($ms);

    $fp = fopen($file, "w") or ($lock = true);
    if ($lock) return false;
    flock($fp, LOCK_EX);
    fwrite($fp, '<?php die(); ?>'."\n");

    foreach ($xs as $v)
    {
        list($a) = explode('|', $v, 2);
        while ($ms && $a >= key($ms))
        {
            // without duplicates
            if ($a > key($ms)) fwrite($fp, key($ms).'|'.serialize(current($ms))."\n");
            unset( $ms[ key($ms) ] );
            reset( $ms );
        }
        fwrite($fp, $v);
    }

    // passthru content
    foreach ($ms as $i => $v) fwrite($fp, $i.'|'.serialize($v)."\n");

    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}

function edit_key($id, $value, $file)
{
    // don't edit empty array or key
    if (is_array($id) && count($id) == 0 || !$id) return false;

    // array makes
    if (is_array($id))
    {
         $ms = array();
         foreach ($id as $i => $v) $ms[ md5($i) ] = $v;
    }
    else $ms = array( md5($id) => $value );

    // open file for editing
    $xs     = file($file);
    $lock   = false;
    $fp     = fopen($file, "w") or ($lock = true);
    if ($lock) return false;
    flock($fp, LOCK_EX);
    fwrite($fp, '<?php die(); ?>'."\n");

    // replace all keys to value
    unset($xs[0]);
    foreach ($xs as $v)
    {
        list ($a) = explode('|', $v, 2);
        if ( isset($ms[$a]) )
             fwrite($fp, $a."|".serialize( $ms[$a] )."\n");
        else fwrite($fp, $v);
    }

    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}

function delete_key($id, $file)
{
    $lock = false;
    $id = md5($id);

    $xs = file($file);
    unset($xs[0]);

    $fp = fopen($file, "w") or ($lock = true);
    if ($lock) return false;
    flock($fp, LOCK_EX);
    fwrite($fp, '<?php die(); ?>'."\n");

    // delete all keys
    foreach ($xs as $v)
    {
        list ($a) = explode('|', $v, 2);
        if ($a != $id) fwrite($fp, $v);
    }
    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}

// $method = BSEARCH_LINE | BSEARCH_FLAT | BSEARCH_STAT
function bsearch_key($id, $file, $method = false)
{
    // get searched file
    $st  = 0;
    $ret = false;
    $fx  = file ($file);
    unset($fx[0]);

    // detect multisearch
    if (is_array($id))
    {
        $founds = $id;
        $method |= BSEARCH_MULTI;
    }
    else $founds = array($id);

    // do search (one or many)
    foreach ($founds as $kx => $id)
    {
        // MD5($id)? Set option BSEARCH_FLAT - and use any key
        if ( ($method & BSEARCH_FLAT) == 0) $id = md5($id);

        // Start
        $pv  = 0;
        $b   = 1;
        $e   = count($fx);

        do
        {
            $st++;
            $p = (int)(($b + $e) / 2);

            // try current value
            list($v, $un) = explode('|', $fx[$p], 2);

            // correction bounds
            if ($v < $id)     $b = $p;
            elseif ($v > $id) $e = $p;
            else break;

            // don't continue in this case -> check high value
            if ($pv == $p && ($e - $b) == 1)
            {
                list($v, $un) = explode('|', $fx[++$p], 2);
                break;
            }
            else $pv = $p;
        }
        while ($e - $b > 0);

        // if breaks out and found, get $ret variable
        $val = ($method & BSEARCH_LINE)? $p : unserialize($un);
        if ( $method & BSEARCH_MULTI)
        {
            if ($v == $id) $ret[$kx] = $val; else $ret[$kx] = false;
        }
        else if ($v == $id) $ret = $val;
    }
    
    // statistic mode for benchmark & check performance
    if ($method & BSEARCH_STAT) return $st;

    // return array or value
    return $ret;
}

// DEBUG functions -----------------------------------------------------------------------------------------------------

// error_dump.log always 0600 for deny of all
// User-defined error handler for catch errors
function user_error_handler($errno, $errmsg, $filename, $linenum, $vars)
{

    $errtypes = array
    (
        E_ERROR             => "Error",
        E_WARNING           => "Warning",
        E_PARSE             => "Parsing Error",
        E_NOTICE            => "Notice",
        E_CORE_ERROR        => "Core Error",
        E_CORE_WARNING      => "Core Warning",
        E_COMPILE_ERROR     => "Compile Error",
        E_COMPILE_WARNING   => "Compile Warning",
        E_USER_ERROR        => "User Error",
        E_USER_WARNING      => "User Warning",
        E_USER_NOTICE       => "User Notice",
        E_STRICT            => "Runtime Notice",
        E_DEPRECATED        => "Deprecated"
    );

    // E_NOTICE skip
    if ($errno == E_NOTICE) return;
    
    $out = $errtypes[$errno].': '.$errmsg.'; '.trim($filename).':'.$linenum.";";
    $out = str_replace(array("\n", "\r", "\t"), ' ', $out);

    // Store data
    if (defined('STORE_ERRORS') && STORE_ERRORS)
    {
        if (is_writable(SERVDIR.CACHE))
        {
            $log = fopen(SERVDIR.CACHE.'/error_dump.log', 'a');
            fwrite($log, time().'|'.date('Y-m-d H:i:s').'|'.trim($out)."\n");
            fclose($log);
        }
    }

}

function die_stat($No, $Reason = false)
{
    $HTTP = array
    (
        0   => '',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        503 => '503 Service Unavailable',
    );

    ob_get_clean();
    ob_start();
    
    $Response = isset($HTTP[$No])? $HTTP[$No] : $HTTP[503];

    if ($No)
    {
        header('HTTP/1.1 '.$Response, true);
        echo $Reason? '<h2>'.$Response.'</h2>'.$Reason : '<h2>'.$Response.'</h2>';
    }
    else echo $Reason;

    // Log stat
    if (defined('STORE_ERRORS') && STORE_ERRORS)
    {
        if (is_writable(SERVDIR.CACHE))
        {
            $log = fopen(SERVDIR.CACHE.'/error_dump.log', 'a');
            fwrite($log, time().'|'.date('Y-m-d H:i:s').'|DIE_STAT: '.$No.'; '.str_replace(array("\n","\r"), " ", $Reason)."\n");
            fclose($log);
        }
    }
    
    exit_cookie();
}

// Modified from http://en.wikibooks.org/wiki/Algorithm_implementation/Sorting/Quicksort#PHP for quicksort cutenews
// $order = A(ascending), D(escending)
// Usage: 0-7/A or 0-7/D

function quicksort($array, $by = 0)
{
    list ($by, $ord) = explode('/', $by);
    if (count($array) < 2) return $array;

    $left = $right = array();

    reset($array);
    $pivot_key  = key($array);
    $pivot      = array_shift($array);
    $pivox      = explode('|', $pivot);

    foreach ($array as $k => $v)
    {
        $vx = explode('|', $v);
        if ($ord == 'A')
             { if ($vx[$by] < $pivox[$by]) $left[$k] = $v; else $right[$k] = $v; }
        else { if ($vx[$by] > $pivox[$by]) $left[$k] = $v; else $right[$k] = $v; }
    }

    return array_merge(quicksort($left), array($pivot_key => $pivot), quicksort($right));
}

// SKINS functions -----------------------------------------------------------------------------------------------------

// Simply read template file
function read_tpl($tpl = 'index')
{
    global $_CACHE;

    // get from cache
    if (isset($_CACHE['tpl_'.$tpl]))
        return $_CACHE['tpl_'.$tpl];

    $open = SERVDIR.SKIN.'/'.($tpl?$tpl:'default').'.tpl';
    $r = fopen($open, 'r') or die_stat(404, 'Template in &lt;'.$open.'&gt; not found');
    ob_start();
    fpassthru($r);
    $ob = ob_get_clean();
    fclose($r);

    // cache file
    $_CACHE['tpl_'.$tpl] = $ob;
    return $ob;
}

// More process for template {$args}, {$ifs}
function proc_tpl($tpl, $args = array(), $ifs = array())
{
    // predefined arguments
    $args['PHP_SELF'] = PHP_SELF;

    // retrieve options
    list ($tpl, $opts) = explode(':', $tpl, 2);

    // reading template 
    $d = read_tpl($tpl);

    // Replace if constructions {VAR}....{/VAR} if set $ifs['VAR'] : {-VAR}...{/-VAR} if no isset $ifs['VAR']
    foreach ($ifs as $i => $v)
    {
        $r = isset($v) && $v ? $v : false;
        $d = preg_replace('~{'.$i.'}(.*?){/'.$i.'}~s', ($r?"\\1":''), $d);
        $d = preg_replace('~{\-'.$i.'}(.*?){/\-'.$i.'}~s', ($r?'':"\\1"), $d);
    }

    // Replace variables in $args
    $keys = $vals = array();
    foreach ($args as $i => $v)
    {
        $keys[] = '{$'.$i.'}';
        $vals[] = $v;
    }
    $d = str_replace($keys, $vals, $d);

    // Catch Foreach Cycles
    if ( preg_match_all('~{foreach from\=([^}]+)}(.*?){/foreach}~is', $d, $rep, PREG_SET_ORDER) )
    {
        foreach ($rep as $v)
        {
            $rpl = false;
            foreach ((array)$args[ $v[1] ] as $x)
            {
                $bulk = $v[2];
                foreach ($x as $ik => $iv) $bulk = str_replace('{$'.$v[1].".$ik}", $iv, $bulk);
                $rpl .= $bulk;
            }
            $d = str_replace($v[0], $rpl, $d);
        }
    }

    // override process template (filter)
    list($d) = hook('func_proc_tpl', array($d, $tpl, $args, $ifs));

    // replace all
    return ( $d );
}

// Get template file and replace %N{1...n} to same items [0...n-1] in $vars
function over_tpl($tpl, $over = array())
{
    $d = read_tpl($tpl);

    $keys = $vals = array();
    foreach ($over as $i => $v)
    {
        $keys[] = '%'.($i+1);
        $vals[] = $v;
    }

    return (str_replace($keys, $vals, $d));
}

// Return say value of lang if present
function lang($say)
{
    global $lang;
    $say = hook('lang_say_before', $say);
    return hook('lang_say_after', empty($lang[strtolower($say)]) ? $say : $lang[strtolower($say)]);
}

function utf8_strtolower($utf8)
{
    global $HTML_SPECIAL_CHARS;

    // European languages to lower
    $utf8 = strtolower( str_replace( array_keys($HTML_SPECIAL_CHARS), array_values($HTML_SPECIAL_CHARS), $utf8) );

    // Rus Language translation
    $SPEC_TRANSLATE = explode('|', "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЫЪЭЮЯ|абвгдеёжзийклмнопрстуфхцчшщьыъэюя");
    $utf8 =  str_replace( explode(' ', trim(preg_replace('~([\xD0][\x00-\xFF])~', '\\1 ', $SPEC_TRANSLATE[0]))),
                          explode(' ', trim(preg_replace('~([\xD0-\xD1][\x00-\xFF])~', '\\1 ', $SPEC_TRANSLATE[1]))),
                          $utf8);

    return $utf8;
}

// @url http://www.php.net/manual/de/function.utf8-decode.php#100478
function UTF8ToEntities ($string)
{
    /* note: apply htmlspecialchars if desired /before/ applying this function
    /* Only do the slow convert if there are 8-bit characters */
    /* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
    if (! preg_match("~[\200-\237]~", $string) and ! preg_match("~[\241-\377]~", $string))
        return $string;

    // reject too-short sequences
    $string = preg_replace("/[\302-\375]([\001-\177])/", "&#65533;\\1", $string);
    $string = preg_replace("/[\340-\375].([\001-\177])/", "&#65533;\\1", $string);
    $string = preg_replace("/[\360-\375]..([\001-\177])/", "&#65533;\\1", $string);
    $string = preg_replace("/[\370-\375]...([\001-\177])/", "&#65533;\\1", $string);
    $string = preg_replace("/[\374-\375]....([\001-\177])/", "&#65533;\\1", $string);
    $string = preg_replace("/[\300-\301]./", "&#65533;", $string);
    $string = preg_replace("/\364[\220-\277]../", "&#65533;", $string);
    $string = preg_replace("/[\365-\367].../", "&#65533;", $string);
    $string = preg_replace("/[\370-\373]..../", "&#65533;", $string);
    $string = preg_replace("/[\374-\375]...../", "&#65533;", $string);
    $string = preg_replace("/[\376-\377]/", "&#65533;", $string);
    $string = preg_replace("/[\302-\364]{2,}/", "&#65533;", $string);

    // decode four byte unicode characters
    $string = preg_replace(
        "/([\360-\364])([\200-\277])([\200-\277])([\200-\277])/e",
        "'&#'.((ord('\\1')&7)<<18 | (ord('\\2')&63)<<12 |" .
            " (ord('\\3')&63)<<6 | (ord('\\4')&63)).';'",
        $string);

    // decode three byte unicode characters
    $string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
        "'&#'.((ord('\\1')&15)<<12 | (ord('\\2')&63)<<6 | (ord('\\3')&63)).';'",
        $string);

    // decode two byte unicode characters
    $string = preg_replace("/([\300-\337])([\200-\277])/e",
        "'&#'.((ord('\\1')&31)<<6 | (ord('\\2')&63)).';'",
        $string);

    // reject leftover continuation bytes
    $string = preg_replace("/[\200-\277]/", "&#65533;", $string);

    return $string;
}

// XXTEA ---------------------------------------------------------------------------------------------------------------

function long2str($v, $w)
{
    $len = count($v);
    $n   = ($len - 1) << 2;
    if ($w)
    {
        $m = $v[$len - 1];
        if (($m < $n - 3) || ($m > $n)) return false;
        $n = $m;
    }

    $s = array();
    for ($i = 0; $i < $len; $i++) $s[$i] = pack("V", $v[$i]);
    if ($w) return substr(join('', $s), 0, $n);
    else    return join('', $s);

}

function str2long($s, $w)
{
    $v = unpack("V*", $s.str_repeat("\0", (4 - strlen($s) % 4) & 3));
    $v = array_values($v);
    if ($w) $v[count($v)] = strlen($s);
    return $v;
}

function int32($n)
{
    while ($n >= 2147483648)  $n -= 4294967296;
    while ($n <= -2147483649) $n += 4294967296;
    return (int)$n;
}

function xxtea_encrypt($str, $key)
{
    if ($str == "") return "";

    $v = str2long($str, true);
    $k = str2long($key, false);
    if (count($k) < 4) for ($i = count($k); $i < 4; $i++) $k[$i] = 0;

    $n      = count($v) - 1;
    $z      = $v[$n];
    $y      = $v[0];
    $delta  = 0x9E3779B9;
    $q      = floor(6 + 52 / ($n + 1));
    $sum    = 0;

    while (0 < $q--)
    {
        $sum = int32($sum + $delta);
        $e = $sum >> 2 & 3;
        for ($p = 0; $p < $n; $p++)
        {
            $y = $v[$p + 1];
            $mx = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z = $v[$p] = int32($v[$p] + $mx);
        }
        $y = $v[0];
        $mx = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
        $z = $v[$n] = int32($v[$n] + $mx);
    }
    return long2str($v, false);
}

function xxtea_decrypt($str, $key)
{
    if ($str == "") return "";

    $v = str2long($str, false);
    $k = str2long($key, false);
    if (count($k) < 4) for ($i = count($k); $i < 4; $i++) $k[$i] = 0;

    $n      = count($v) - 1;
    $z      = $v[$n];
    $y      = $v[0];
    $delta  = 0x9E3779B9;
    $q      = floor(6 + 52 / ($n + 1));
    $sum    = int32($q * $delta);

    while ($sum != 0)
    {
        $e = $sum >> 2 & 3;
        for ($p = $n; $p > 0; $p--)
        {
            $z = $v[$p - 1];
            $mx = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $y = $v[$p] = int32($v[$p] - $mx);
        }
        $z      = $v[$n];
        $mx     = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
        $y      = $v[0] = int32($v[0] - $mx);
        $sum    = int32($sum - $delta);
    }
    return long2str($v, true);
}

// Mail function -------------------------------------------------------------------------------------------------------
function send_mail($to, $subject, $message, $hdr = false)
{

    if (!isset($to)) return false;
    if (!$to) return false;

    $tos = explode(',', $to);
    $from = 'CuteNews@' . $_SERVER['SERVER_NAME'];

    $headers = '';
    $headers .= 'From: '.$from."\n";
    $headers .= 'Reply-to: '.$from."\n";
    $headers .= 'Return-Path: '.$from."\n";
    $headers .= 'Message-ID: <' . md5(uniqid(time())) . '@' . $_SERVER['SERVER_NAME'] . ">\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-type: text/plain;\n";
    $headers .= "Date: " . date('r', time()) . "\n";
    $headers .= "X-Mailer: PHP/" . phpversion()."\n";
    $headers .= $hdr;

    foreach ($tos as $v)
        if ($v)
        {
            $mx = false;
            $pt = SERVDIR.CACHE.'/mail.log';
            $ms = "-------------\n".$headers."Subject: $subject\n\n".$message."\n\n";
            mail($v, $subject, $message, $headers) or $mx = true;
            if ($mx) { $log = fopen($pt, 'a'); fwrite($log, $ms); fclose($log); }
        }
}

// Filtrate necessary data from REQUEST
function filter_request($allow = '', $order = false)
{
    $extr = array();
    foreach( explode(',', $allow) as $v)
    {
        $v = trim($v);
        if ( empty($_REQUEST[$v]) )
             $vx = false;
        else $vx = get_magic_quotes_gpc()? stripslashes($_REQUEST[$v]) : $_REQUEST[$v];

        if ($order) $extr[]   = $vx;
               else $extr[$v] = $vx;
    }
    return $extr;
}

function exec_time()
{
    echo "<!-- execution time: ".round(microtime(true) - EXEC_TIME, 3)." -->";
}

function send_cookie()
{
    global $_SESS;

    $cookie = base64_encode( xxtea_encrypt(serialize($_SESS), CRYPT_SALT) );

    // if remember flag exists
    if ( isset($_SESS['@']) && $_SESS['@'])
         setcookie('session', $cookie, time() + 60*60*24*30, '/');
    else setcookie('session', $cookie, 0, '/');
}

function exit_cookie($url = false)
{
    send_cookie();

    if ($url) header('Location: '.$url);
    $echo = ob_get_clean();
    echo $echo;
    exit();
}

// hash type MD5 and SHA256
function hash_generate($password)
{
    global $cfg;

    $try = array
    (
        0 => md5($password),
        1 => SHA256_hash($password),
    );

    return $try;
}

// $rec = recursive scan
function read_dir($dir_name, $cdir = array(), $rec = true)
{
    $dir = opendir($dir_name);
    if (is_resource($dir))
    {
        while (false !== ($file = readdir($dir)))
        if ($file != "." and $file != "..")
        {
            $path = $dir_name.'/'.$file;
            if ( is_readable($path) )
            {
                if ( is_dir($path) && $rec) $cdir = read_dir($path, $cdir);
                elseif (is_file($path)) $cdir[] = str_replace(SERVDIR, '', $path);
            }
        }
        closedir($dir);
    }
    return $cdir;
}

// Add hook to system
function add_hook($hook, $func)
{
    global $_HOOKS;
    $_HOOKS[$hook][] = $func;
}

// Cascade Hooks
function hook($hook, $args = null)
{
    global $_HOOKS;

    $id = 0;

    // Direct hooks
    while (function_exists('hook_'.$hook.'_'.$id))
    {
        $args = call_user_func('hook_'.$hook.'_'.$id++, $args);
        $id++;
    }

    // Plugin hooks
    if (!empty($_HOOKS[$hook]))
        foreach($_HOOKS[$hook] as $hookfunc)
            $args = call_user_func($hookfunc, $args);

    return $args;
}

// Do breadcrumbs as mod:action=Name/mod=Name/mod/=Name ($lbl = true -> last bc is link)
function make_breadcrumbs($bc, $lbl = false)
{
    $ex = explode('/', $bc);
    $bc = array();
    $cn = count($ex);

    foreach ($ex as $i => $v)
    {
        // simply bc
        if (preg_match('~^[\w\=\: ]*$~', $v))
        {
            list($link, $desc) = explode('=', $v, 2);
            list($link, $action) = explode(':', $link);

            // detect whitespaces
            if (!$desc) $desc = $link;
            if ($action) $link .= '&amp;action='.$action;

            if ( $i < $cn - 1 || $lbl)
                 $bc[] = '<a style="font-size: 15px;" href="'.PHP_SELF.'?mod='.$link.'">'.$desc.'</a>';
            else $bc[] = $desc;

        }
    }

    return '<div style="margin: 16px 64px 12px 0; padding: 0 0 4px 0; font-size: 15px; border-bottom: 1px solid #cccccc;">'.implode(' / ', $bc).'</div>';
}

// ---------------------------------------------------------------------------------------------------------------------

// Category ID to Name [convert to category name from ID]
function catid2name($thecat)
{
    global $cat;

    $nice = array();
    $cats = explode(',', $thecat);
    foreach ($cats as $cn) $nice[] = $cat[ trim($cn) ];
    return (implode (', ', $nice));
}

function my_strip_tags($d) { return preg_replace('/<[^>]*>/', '', $d); }

// Only Allowed Tags There....
function hesc($html)
{
    global $config_xss_strict;

    // XSS Strict off
    if ($config_xss_strict == 0)
        return $html;

    if ( preg_match_all('~<\s*?/?\s*?([^>]+)>~s', $html, $sets, PREG_SET_ORDER) )
    {
        $allowed_tags = explode(',', 'a,i,b,u,p,h1,h2,h3,h4,h5,h6,hr,ul,ol,br,li,tr,th,td,tt,sub,sup,img,big,div,code,span,abbr,code,acronym,address,blockquote,center,strike,strong,table,thead,object,iframe,param,embed');
        $events       = explode(',', 'onblur,onchange,onclick,ondblclick,onfocus,onkeydown,onkeypress,onkeyup,onload,onmousedown,onmousemove,onmouseout,onmouseover,onmouseup,onreset,onselect,onsubmit,onunload');

        foreach ($sets as $vs)
        {
            $disable  = false;
            list($tag) = explode(' ', strtolower($vs[1]), 2);

            if (in_array($tag, $allowed_tags) == false) $disable = 2;
            elseif (preg_match_all('~on\w+~i', $vs[0], $evt, PREG_SET_ORDER))
                foreach ($evt as $ie) if (in_array($ie[0], $events)) { $disable = 1; break; }

            if ($disable == 1) $html = str_replace($vs[0], '<'.$tag.'>', $html);
            if ($disable == 2) $html = str_replace($vs[0], false, $html);
        }
    }
    return $html;
}

// Short Story or fullstory replacer -----------------------------------------------------------------------------------
function template_replacer_news($news_arr, $output, $type = 'full')
{
    // Predefined Globals
    global $config_timestamp_active, $config_http_script_dir, $config_comments_popup, $config_comments_popup_string,
           $config_auto_wrap, $config_full_popup, $config_full_popup_string, $rss_news_include_url, $my_names,
           $my_start_from, $cat, $action, $cat_icon, $archive, $name_to_nick, $PHP_SELF, $template, $user_query;

    // Short Story not exists
    if (empty($news_arr[NEW_FULL]) and (strpos($output, '{short-story}') === false) ) $news_arr[NEW_FULL] = $news_arr[NEW_SHORT];
    $output      = more_fields($news_arr[NEW_ID], $output);

    // Date Formatting [year, month, day, hour, minute, date=$config_timestamp_active]
    $output      = embedateformat($news_arr[0], $output);
    $output      = hook('template_replacer_news_before', $output);

    // Replace news content
    $output      = str_replace("{title}",           hesc($news_arr[NEW_TITLE]), $output);
    $output      = str_replace("{author}",          $my_names[$news_arr[NEW_USER]] ? $my_names[$news_arr[NEW_USER]] : $news_arr[NEW_USER], $output);
    $output      = str_replace("{avatar-url}",      $news_arr[NEW_AVATAR], $output);
    $output      = str_replace("{category}",        hesc(catid2name($news_arr[NEW_CAT])), $output);
    $output      = str_replace("{category-url}",    linkedcat($news_arr[NEW_CAT]), $output);
    $output      = str_replace("{author-name}",     hesc($name_to_nick[$news_arr[NEW_USER]]), $output);
    $output      = str_replace("{short-story}",     hesc($news_arr[NEW_SHORT]), $output);
    $output      = str_replace("{full-story}",      hesc($news_arr[NEW_FULL]), $output);

    // Article parameters
    $caticon     = $cat[ $news_arr[NEW_CAT] ];

    // @TODO Page Views parameter
    $output      = str_replace("{page-views}",      false, $output);
    $output      = str_replace("{php-self}",        $PHP_SELF, $output);
    $output      = str_replace("{index-link}",      '<a href="'.$PHP_SELF.'">'.lang('Go back').'</a>', $output);
    $output      = str_replace("{back-previous}",   '<a href="javascript:history.go(-1)">Go back</a>', $output);
    $output      = str_replace("{cute-http-path}",  $config_http_script_dir, $output);
    $output      = str_replace("{news-id}",         $news_arr[NEW_ID], $output);
    $output      = str_replace("{category-id}",     $news_arr[NEW_CAT], $output);
    $output      = str_replace("{comments-num}",    countComments($news_arr[NEW_ID], $archive), $output);
    $output      = str_replace("{archive-id}",      $archive, $output);
    $output      = str_replace("{category-icon}",   $cat_icon[ $news_arr[NEW_CAT] ] ? '<img style="border: none;" alt="'.$caticon.' icon" src="'.$caticon.'" />' : '', $output);
    $output      = str_replace("{avatar}",          $news_arr[NEW_AVATAR]? '<img alt="" src="'.$news_arr[NEW_AVATAR].'" style="border: none;" />' : '', $output);

    // Mail Exist in mailist
    if ( !empty($my_mails[ $news_arr[NEW_USER] ]) )
         $output = str_replace( array("[mail]", '[/mail]'), array('<a href="mailto:'.$my_mails[ $news_arr[NEW_USER] ].'">', ''), $output);
    else $output = str_replace( array("[mail]", '[/mail]'), '', $output);

    // By click to comments - popup window
    if ( $config_comments_popup == "yes" )
         $output = str_replace(array("[com-link]", '[/com-link]'),
                               array('<a href="#" onclick="window.open(\''.$config_http_script_dir.'/router.php?subaction=showcomments&amp;template='.$template.'&amp;id='.$news_arr[NEW_ID].'&amp;archive='.$archive.'&amp;start_from='.$my_start_from.'&amp;ucat='.$news_arr[NEW_CAT].'\', \'_News\', \''.$config_comments_popup_string.'\'); return false;">', '</a>'), $output);
    else $output = str_replace(array("[com-link]", '[/com-link]'),
                               array("<a href=\"$PHP_SELF?subaction=showcomments&amp;id=".$news_arr[0]."&amp;archive=$archive&amp;start_from=$my_start_from&amp;ucat=$news_arr[NEW_CAT]&amp;$user_query\">", '</a>'), $output);

    $output      = str_replace(array("[link]", "[/link]"),
                               array('<a href="'.$PHP_SELF."?subaction=showfull&amp;id=$news_arr[0]&amp;archive=$archive&amp;start_from=$my_start_from&amp;ucat=$news_arr[6]&amp;$user_query\">", "</a>"), $output);

    // @TODO Only in active news, not archives
    $output     = empty($archive) ? str_replace("{star-rate}", rating_bar($news_arr[NEW_ID], $news_arr[NEW_RATE]), $output) : str_replace("{star-rate}", false, $output);

    // With Action = showheadlines
    if ($news_arr[NEW_FULL] or $action == "showheadlines")
    {
        if ( $config_full_popup == "yes" )
             $output = str_replace('[full-link]', "<a href=\"#\" onclick=\"window.open('$config_http_script_dir/router.php?subaction=showfull&amp;id=$news_arr[0]&amp;archive=$archive&amp;template=$template', '_News', '$config_full_popup_string');return false;\">", $output);
        else $output = str_replace("[full-link]", "<a href=\"$PHP_SELF?subaction=showfull&amp;id=$news_arr[0]&amp;archive=$archive&amp;start_from=$my_start_from&amp;ucat=$news_arr[6]&amp;$user_query\">", $output);
        $output      = str_replace("[/full-link]","</a>", $output);
    }
    else
    {
        $output = preg_replace('~\[full-link\].*?\[/full-link\]~si', '<!-- no full story-->', $output);
    }

    // in RSS we need the date in specific format
    if ($template == 'rss')
    {
        $output = str_replace("{date}", date("r", $news_arr[0]), $output);
        $output = str_replace("{rss-news-include-url}", $rss_news_include_url ? $rss_news_include_url : $config_http_script_dir.'/router.php', $output);
    }
    else
    {
        $output = str_replace("{date}", date($config_timestamp_active, $news_arr[NEW_ID]), $output);
    }

    // Star Rating
    if ( empty($archive) )
         $output = str_replace("{star-rate}", rating_bar($news_arr[NEW_ID], $news_arr[NEW_RATE]), $output);
    else $output = str_replace("{star-rate}", false, $output);

    // Auto Wrapper
    if ($config_auto_wrap > 40) $auto_wrap = $config_auto_wrap; else $auto_wrap = 40;
    $output = preg_replace('~(\w{'.$auto_wrap.'})~', "\\1 ", $output);

    $output = hook('template_replacer_news_middle', $output);
    $output = replace_news("show", $output);
    $output = hook('template_replacer_news_after', $output);

    return $output;
}

// Extra Articles Fields
function more_fields($artid, $output)
{
    global $cfg;

    // if use more fields
    if ( !empty($cfg['more_fields']) && is_array($cfg['more_fields']) )
    {
        $article = bsearch_key($artid, DB_NEWS);
        foreach ($cfg['more_fields'] as $i => $v)
            $output = str_replace('{'.$i.'}', hesc($article[$i]), $output );
    }

    return $output;
}


/*
 * Log, base on multifiles and md5 tells about day & hour for user login
 * Array search slice
 */
function add_to_log($username, $action, $try = 3)
{
    // authorization stat
    $locked = false;
    $flog = SERVDIR.'/cdata/log/log_'.date('Y_m').'.php';

    // create log file if not exists
    if ( !file_exists($flog) )
    {
        fclose(fopen($flog,'w'));
        chmod ($flog, 0666);
    }

    // add to log
    $log = fopen(SERVDIR.'/cdata/log/log_'.date('Y_m').'.php', 'a') or ($locked = true);
    if ($locked)
    {
        if ($try > 0)
        {
            sleep(1);
            add_to_log($username, $action, $try - 1);
        }
        else return false;
    }
    else
    {
        flock($log, LOCK_EX);
        fwrite($log, time().'|'.serialize(array('user' => $username, 'action' => $action, 'time' => time(), 'ip' => $_SERVER['REMOTE_ADDR']))."\n");
        flock($log, LOCK_UN);
        fclose($log);
    }
    return true;
}

// User-defined for date formatting
function format_date($time, $type = false)
{
    global $cfg;

    // type format - since current time
    if ($type == 'since' || $type == 'since-short')
    {
        $dists = array(
            ' year(s) ' => 3600*24*365,
            ' month(s) ' => 3600*24*31,
            ' d. ' => 3600*24,
            ' h. ' => 3600,
            ' m. ' => 60,
        );

        $rd = false;
        $dist = time() - $time;

        foreach ($dists as $i => $v)
        {
            if ($dist > $v)
            {
                $X     = floor( $dist / $v );
                $rd   .= $X.$i;
                $dist -= $X * $v;
            }
        }

        $rd .= ($rd? '' : '0 m' ).' ago'. (($type == 'since') ? ' at '.date('Y-m-d H:i') : '');
        return $rd;
    }

    if (!isset($cfg['format_date'])) return date('r', $time);

    return $time;
}

/*
 * id=0...n-1
 * pt=1 (is digit), =0 (is ...)
 * cr=1 (current)
 */
function pagination($count, $per = 25, $current = 0, $spread = 5)
{

    $lists = array();
    $pages = (floor($count / $per) + (($count % $per) ?  1 : 0)) - 1;

    // check bounds
    $_ps = (($current - $spread) >= 0)? ($current - $spread) : 0;
    $_pe = (($current + $spread) <= $pages)? ($current + $spread) : $pages;

    if ($_ps)
    {
        $lists[] = array( 'id' => 0, 'pt' => 1, 'cr' => 0 );
        $lists[] = array( 'id' => $_ps - 1, 'pt' => 0, 'cr' => 0 );
    }

    for ($i = $_ps; $i <= $_pe; $i++)
    {
        $lists[] = array( 'id' => $i,
                          'pt' => 1,
                          'cr' => ($i == $current)? 1 : 0,
        );
    }
    if ($_pe < $pages)
    {
        $lists[] = array( 'id' => $_pe + 1, 'pt' => 0, 'cr' => 0 );
        $lists[] = array( 'id' => $pages, 'pt' => 1, 'cr' => 0 );
    }

    return $lists;
}

// make full URI (left & right parts)
function build_uri($left, $right)
{
    $URI = array();
    foreach ((array)explode(',', $left) as $i => $v) $URI[] = urlencode($v).'='.urlencode($right[$i]);
    return '?'.implode('&', $URI);
}


// Fast masked ban searcher (return found mask and dest mask for IP as ID)
function ip_mask_check($key, $AIP)
{
    if (is_array($key))
    foreach ( $key as $mask => $v)
    {
        $c = $d = 0;
        $ipx  = explode('.', $mask);
        foreach ($ipx as $p) if ($AIP[$c++] == $p || $p == '*') $d++;
        if ($d == 4) return $mask;
    }
    return false;
}

function check_for_ban($IP = '127.0.0.1', $nick = false)
{
    // check for nickname
    if ($nick)
    {
        // first nickname in database
        $nick = strtolower( preg_replace('~[^0-9a-z]~i', '', $nick) );
        if ( bsearch_key('>'.$nick, DB_BAN) )
             return ('>'.$nick);
    }

    $AIP = explode('.', $IP);
    list ($A, $B, $C, $D) = $AIP;

    // Prepare ips for search
    $ips[ 0 ] = "$A.$B.$C.$D";
    $ips[ 1 ] = "$A.$B.$C";
    $ips[ 2 ] = "$A.$B";
    $ips[ 3 ] = "$A";

    // search multiply ips
    $keys = bsearch_key($ips, DB_BAN);

    // real exists IP in keytable?
    if ( isset($keys[0]) && isset($keys[0][ $ips[0] ])) return $ips[0].':'.$ips[0];

    // masks check
    if (isset($keys[1])) { if ( $mask = ip_mask_check($keys[1], $AIP) ) return $ips[1].":".$mask; }
    if (isset($keys[2])) { if ( $mask = ip_mask_check($keys[2], $AIP) ) return $ips[2].":".$mask; }
    if (isset($keys[3])) { if ( $mask = ip_mask_check($keys[3], $AIP) ) return $ips[3].":".$mask; }

    // all ok
    return false;
}

function add_ip_to_ban($add_ip, $expire = 0)
{
    if (preg_match('~^([0-9\*]+)\.([0-9\*]+)\.([0-9\*]+)\.([0-9\*]+)$~', $add_ip, $ip))
    {

        // unique first tail
        $uniq = array();
        for ($i = 1; $i < 5; $i++)
        {
            if ($ip[$i] == '*') break;
            $uniq[] = $ip[$i];
        }

        // without * is unique
        $uniq = implode('.', $uniq);
        if ($uniq == $ip[0])
        {
            add_key($uniq, false, DB_BAN);
            edit_key($uniq, array($uniq => array('T' => 0, 'E' => $expire)), DB_BAN); // if key exist, replace
        }
        else
        {
            // if key not exists, create
            if ( $bans = bsearch_key($uniq, DB_BAN) )
            {
                $bans[$ip[0]] = array('T' => 0, 'E' => $expire);
                edit_key($uniq, $bans, DB_BAN);
            }
            else
            {
                // add first key
                add_key($uniq, false, DB_BAN);
                edit_key($uniq, array($ip[0] => array('T' => 0, 'E' => $expire)), DB_BAN);  // if key exist, replace
            }
        }
    }
}

// count times for login ban
function add_to_ban($REMOTE_ADDR)
{

    // check for ban on 6 hour
    $banid = bsearch_key($REMOTE_ADDR, DB_BAN);
    if ( !isset($banid[$REMOTE_ADDR]) )
    {
        $banid[$REMOTE_ADDR] = array('T' => 0, 'E' => 0);
        add_key($REMOTE_ADDR, array($REMOTE_ADDR => $banid[$REMOTE_ADDR]), DB_BAN);
    }

    // if more than 6 times, add ban time
    if ($banid[$REMOTE_ADDR]['T'] > 6) $banid[$REMOTE_ADDR]['E'] = time() + 3600*6;
    edit_key($REMOTE_ADDR, array($REMOTE_ADDR => array('T' => $banid[$REMOTE_ADDR]['T'] + 1,
                                                       'E' => $banid[$REMOTE_ADDR]['E'])), DB_BAN);

    if ( $banid[$REMOTE_ADDR]['T'] > 0 ) return ' <b>Ban attempt</b> '.$banid[$REMOTE_ADDR]['T'].'!';
    
}

function embedateformat($timestamp, $output)
{
    // Months
    if ( preg_match_all('~{month(\|.*?)?}~i', $output, $monthd, PREG_SET_ORDER) )
    {
        foreach ($monthd as $v)
            if (empty($v[1])) $output = str_replace($v[0], date('F', $timestamp), $output);
            else
            {
                $monthlist = explode(',', substr($v[1], 1));
                $output = str_replace($v[0], $monthlist[date('n', $timestamp)-1], $output);
            }
    }

    // Others parameters
    $output     = str_replace('{weekday}', date('l', $timestamp), $output);
    $output     = str_replace("{year}",    date("Y", $timestamp), $output);
    $output     = str_replace("{day}",     date("d", $timestamp), $output);
    $output     = str_replace("{hours}",   date("H", $timestamp), $output);
    $output     = str_replace("{minite}",  date("i", $timestamp), $output);

    $output     = str_replace("{since}",   format_date($timestamp, 'since-short'), $output);

    return $output;
}

// SHA256::hash --------------------------------------------------------------------------------------------------------
/*
 *  Based on http://csrc.nist.gov/cryptval/shs/sha256-384-512.pdf
 *
 *  © Copyright 2005 Developer's Network. All rights reserved.
 *  This is licensed under the Lesser General Public License (LGPL)
 *  This library is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Lesser General Public
 *  License as published by the Free Software Foundation; either
 *  version 2.1 of the License, or (at your option) any later version.
 */

function SHA256_sum()
{
    $T = 0;
    for($x = 0, $y = func_num_args(); $x < $y; $x++)
    {
        $a = func_get_arg($x);
        $c = 0;
        for($i = 0; $i < 32; $i++)
        {
            //    sum of the bits at $i
            $j = (($T >> $i) & 1) + (($a >> $i) & 1) + $c;
            //    carry of the bits at $i
            $c = ($j >> 1) & 1;
            //    strip the carry
            $j &= 1;
            //    clear the bit
            $T &= ~(1 << $i);
            //    set the bit
            $T |= $j << $i;
        }
    }
    return $T;
}

function SHA256_hash($str)
{
    $chunks = null;
    $M = strlen($str);                //    number of bytes
    $L1 = ($M >> 28) & 0x0000000F;    //    top order bits
    $L2 = $M << 3;                    //    number of bits
    $l = pack('N*', $L1, $L2);
    $k = $L2 + 64 + 1 + 511;
    $k -= $k % 512 + $L2 + 64 + 1;
    $k >>= 3;                           //    convert to byte count
    $str .= chr(0x80) . str_repeat(chr(0), $k) . $l;
    preg_match_all( '#.{64}#', $str, $chunks );
    $chunks = $chunks[0];

    // H(0)
    $hash = array
    (
        (int)0x6A09E667, (int)0xBB67AE85,
        (int)0x3C6EF372, (int)0xA54FF53A,
        (int)0x510E527F, (int)0x9B05688C,
        (int)0x1F83D9AB, (int)0x5BE0CD19,
    );


    // Compute
    $vars = 'abcdefgh';
    $K = null;

    $a = $b = $c = $d = $e = $f = $h = $g = false;
    if($K === null)
    {
        $K = array(
            (int)0x428A2F98, (int)0x71374491, (int)0xB5C0FBCF, (int)0xE9B5DBA5,
            (int)0x3956C25B, (int)0x59F111F1, (int)0x923F82A4, (int)0xAB1C5ED5,
            (int)0xD807AA98, (int)0x12835B01, (int)0x243185BE, (int)0x550C7DC3,
            (int)0x72BE5D74, (int)0x80DEB1FE, (int)0x9BDC06A7, (int)0xC19BF174,
            (int)0xE49B69C1, (int)0xEFBE4786, (int)0x0FC19DC6, (int)0x240CA1CC,
            (int)0x2DE92C6F, (int)0x4A7484AA, (int)0x5CB0A9DC, (int)0x76F988DA,
            (int)0x983E5152, (int)0xA831C66D, (int)0xB00327C8, (int)0xBF597FC7,
            (int)0xC6E00BF3, (int)0xD5A79147, (int)0x06CA6351, (int)0x14292967,
            (int)0x27B70A85, (int)0x2E1B2138, (int)0x4D2C6DFC, (int)0x53380D13,
            (int)0x650A7354, (int)0x766A0ABB, (int)0x81C2C92E, (int)0x92722C85,
            (int)0xA2BFE8A1, (int)0xA81A664B, (int)0xC24B8B70, (int)0xC76C51A3,
            (int)0xD192E819, (int)0xD6990624, (int)0xF40E3585, (int)0x106AA070,
            (int)0x19A4C116, (int)0x1E376C08, (int)0x2748774C, (int)0x34B0BCB5,
            (int)0x391C0CB3, (int)0x4ED8AA4A, (int)0x5B9CCA4F, (int)0x682E6FF3,
            (int)0x748F82EE, (int)0x78A5636F, (int)0x84C87814, (int)0x8CC70208,
            (int)0x90BEFFFA, (int)0xA4506CEB, (int)0xBEF9A3F7, (int)0xC67178F2
        );
    }

    $W = array();
    for($i = 0, $numChunks = sizeof($chunks); $i < $numChunks; $i++)
    {
        //    initialize the registers
        for($j = 0; $j < 8; $j++)
            ${$vars{$j}} = $hash[$j];

        //    the SHA-256 compression function
        for($j = 0; $j < 64; $j++)
        {
            if($j < 16)
            {
                $T1  = ord($chunks[$i][$j*4]) & 0xFF; $T1 <<= 8;
                $T1 |= ord($chunks[$i][$j*4+1]) & 0xFF; $T1 <<= 8;
                $T1 |= ord($chunks[$i][$j*4+2]) & 0xFF; $T1 <<= 8;
                $T1 |= ord($chunks[$i][$j*4+3]) & 0xFF;
                $W[$j] = $T1;
            }
            else
            {
                $W[$j] = SHA256_sum(((($W[$j-2] >> 17) & 0x00007FFF) | ($W[$j-2] << 15)) ^ ((($W[$j-2] >> 19) & 0x00001FFF) | ($W[$j-2] << 13)) ^ (($W[$j-2] >> 10) & 0x003FFFFF), $W[$j-7], ((($W[$j-15] >> 7) & 0x01FFFFFF) | ($W[$j-15] << 25)) ^ ((($W[$j-15] >> 18) & 0x00003FFF) | ($W[$j-15] << 14)) ^ (($W[$j-15] >> 3) & 0x1FFFFFFF), $W[$j-16]);
            }

            $T1 = SHA256_sum($h, ((($e >> 6) & 0x03FFFFFF) | ($e << 26)) ^ ((($e >> 11) & 0x001FFFFF) | ($e << 21)) ^ ((($e >> 25) & 0x0000007F) | ($e << 7)), ($e & $f) ^ (~$e & $g), $K[$j], $W[$j]);
            $T2 = SHA256_sum(((($a >> 2) & 0x3FFFFFFF) | ($a << 30)) ^ ((($a >> 13) & 0x0007FFFF) | ($a << 19)) ^ ((($a >> 22) & 0x000003FF) | ($a << 10)), ($a & $b) ^ ($a & $c) ^ ($b & $c));
            $h = $g;
            $g = $f;
            $f = $e;
            $e = SHA256_sum($d, $T1);
            $d = $c;
            $c = $b;
            $b = $a;
            $a = SHA256_sum($T1, $T2);
        }

        //    compute the next hash set
        for($j = 0; $j < 8; $j++)
            $hash[$j] = SHA256_sum(${$vars{$j}}, $hash[$j]);
    }

    // HASH HEX
    $str = '';
    reset($hash);
    do { $str .= sprintf('%08x', current($hash)); } while(next($hash));

    return $str;
}

// Auto-Archives News
function ResynchronizeAutoArchive()
{
    global $config_auto_archive, $config_notify_email,$config_notify_archive,$config_notify_status;

    $count_news = count(file(SERVDIR."/cdata/news.txt"));
    if($count_news > 1)
    {
        if($config_auto_archive == "yes")
        {

            $now['year'] = date("Y");
            $now['month'] = date("n");

            $db_content = file(SERVDIR."/cdata/auto_archive.db.php");
            list($last_archived['year'], $last_archived['month']) = explode("|", $db_content[0] );

            $tmp_now_sum = $now['year'] . sprintf("%02d", $now['month']) ;
            $tmp_last_sum = (int)$last_archived['year'] . sprintf("%02d", (int)$last_archived['month']) ;

            if($tmp_now_sum > $tmp_last_sum)
            {
                $error = FALSE;
                $arch_name = time();

                if (!copy(SERVDIR."/cdata/news.txt", SERVDIR."/cdata/archives/$arch_name.news.arch"))          { $error = lang("Can not copy news.txt from cdata/ to cdata/archives"); }
                if (!copy(SERVDIR."/cdata/comments.txt", SERVDIR."/cdata/archives/$arch_name.comments.arch"))  { $error = lang("Can not copy comments.txt from cdata/ to cdata/archives"); }

                $handle = fopen(SERVDIR."/cdata/news.txt","w") or $error = lang("Can not open news.txt");
                fclose($handle);

                $handle = fopen(SERVDIR."/cdata/comments.txt","w") or $error = lang("Can not open comments.txt");
                fclose($handle);

                $fp = fopen(SERVDIR."/cdata/auto_archive.db.php", "w");
                if ($fp)
                {
                    flock ($fp, LOCK_EX);

                    if  (!$error )
                         fwrite($fp, $now['year']."|".$now['month']."\n");
                    else fwrite($fp, "0|0|$error\n");

                    foreach($db_content as $line) fwrite($fp, $line);

                    flock ($fp, LOCK_UN);
                    fclose($fp);

                    if ($config_notify_archive == "yes" and $config_notify_status == "active")
                        send_mail($config_notify_email, lang("CuteNews - AutoArchive was Performed"), lang("CuteNews has performed the AutoArchive function.")."\n$count_news ".lang("News Articles were archived.")."\n$error");

                }
            }
        }
    }
}

// Refreshes the Postponed News file.
function ResynchronizePostponed()
{
    global $config_notify_postponed,$config_notify_status,$config_notify_email;

    $all_postponed_db = file(SERVDIR."/cdata/postponed_news.txt");
    if (!empty($all_postponed_db))
    {
        $new_postponed_db = fopen(SERVDIR."/cdata/postponed_news.txt", w);
        if ($new_postponed_db)
        {
            $now_date = time();
            flock ($new_postponed_db, LOCK_EX);

            foreach ($all_postponed_db as $p_line)
            {
                $p_item_db = explode("|", $p_line);
                if ($p_item_db[0] <= $now_date)
                {
                    // Item is old and must be Activated, add it to news.txt
                    $all_active_db      = file(SERVDIR."/cdata/news.txt");
                    $active_news_file   = fopen(SERVDIR."/cdata/news.txt", "w");

                    if ($active_news_file)
                    {
                        flock ($active_news_file, LOCK_EX);
                        fwrite($active_news_file, $p_line);
                        foreach ($all_active_db as $active_line) fwrite($active_news_file, $active_line);
                        flock ($active_news_file, LOCK_UN);
                        fclose($active_news_file);

                        if($config_notify_postponed == "yes" and $config_notify_status == "active")
                            send_mail( $config_notify_email, lang("CuteNews - Postponed article was Activated"), lang("CuteNews has activated the article").' '.$p_item_db[2]);

                    }
                }
                else
                {
                    // Item is still postponed
                    fwrite($new_postponed_db,"$p_line");
                }
            }

            flock ($new_postponed_db, LOCK_UN);
            fclose($new_postponed_db);
        }
    }
}

// Format the size of given file
function formatsize($file_size)
{
    if($file_size >= 1073741824)    $file_size = round($file_size / 1073741824 * 100) / 100 . " Gb";
    elseif($file_size >= 1048576)   $file_size = round($file_size / 1048576 * 100) / 100 . " Mb";
    elseif($file_size >= 1024)      $file_size = round($file_size / 1024 * 100) / 100 . " Kb";
    else                            $file_size = $file_size . " B";
    return $file_size;
}

// Check login information
function check_login($username, $password)
{
    global $member_db;
    if ( $member_db = bsearch_key($username, DB_USERS) )
    {
        $result = 0;
        foreach ($password as $ks => $n) if ($n == $member_db[UDB_PASS]) $result = 1 + $ks;
        return $result;
    }
    else return false;
}

// Format the Query_String for CuteNews purpuses index.php?
function cute_query_string($q_string, $strips, $type="get")
{
    foreach($strips as $key) $strips[$key] = true;

    $my_q = false;
    $var_value = explode("&", $q_string);

    foreach($var_value as $var_peace)
    {
        $parts = explode("=", $var_peace);
        if($strips[$parts[0]] != true and $parts[0] != "")
        {
            if( $type == "post" )
                 $my_q .= "<input type=\"hidden\" name=\"".htmlspecialchars($parts[0])."\" value=\"".htmlspecialchars($parts[1])."\" />\n";
            else $my_q .= "$var_peace&amp;";
        }
    }

    if( substr($my_q, -5) == "&amp;" ) $my_q = substr($my_q, 0, -5);
    return $my_q;
}

// Flood Protection Function
function flooder($ip, $comid)
{
    global $config_flood_time;

    $result = false;
    $old_db = file(SERVDIR."/cdata/flood.db.php");
    $new_db = fopen(SERVDIR."/cdata/flood.db.php", 'w');

    if ($new_db)
    {
        flock($new_db, LOCK_EX);
        $result = false;
        foreach ($old_db as $old_db_line)
        {
            $old_db_arr = explode("|", $old_db_line);
            if (($old_db_arr[0] + $config_flood_time) > time() )
            {
                fwrite($new_db, $old_db_line);
                if($old_db_arr[1] == $ip and $old_db_arr[2] == $comid) $result = true;
            }
        }
        flock($new_db, LOCK_UN);
        fclose($new_db);
    }
    return $result;
}

// Displays message to user
function msg($type, $title, $text, $back = false, $bc = false)
{
    echoheader($type, $title, $bc);
    if ($back) $back = '<a href="'.$back.'">go back</a>';
    echo over_tpl('msg', array($text, '<br /><br>' . $back) );
    echofooter();
    exit_cookie();
}

// Displays header skin
function echoheader($image, $header_text, $bread_crumbs = false)
{
    global $is_loged_in, $skin_header, $lang_content_type, $skin_menu, $skin_prefix, $config_version_name;

    if ($is_loged_in == true )
         $skin_header = preg_replace("/{menu}/", $skin_menu, $skin_header);
    else $skin_header = preg_replace("/{menu}/", " &nbsp; ".$config_version_name, $skin_header);

    $skin_header = get_skin($skin_header);
    $skin_header = str_replace('{title}', ($header_text? $header_text.' / ' : ''). 'CuteNews', $skin_header);
    $skin_header = str_replace("{image-name}", $skin_prefix.$image, $skin_header);
    $skin_header = str_replace("{header-text}", $header_text, $skin_header);
    $skin_header = str_replace("{content-type}", $lang_content_type, $skin_header);
    $skin_header = str_replace("{breadcrumbs}", $bread_crumbs, $skin_header);

    echo $skin_header;
}

// Displays footer skin
function echofooter()
{
    global $is_loged_in, $skin_footer, $lang_content_type, $skin_menu, $config_version_name;

    if ($is_loged_in == TRUE)
         $skin_footer = str_replace("{menu}", $skin_menu, $skin_footer);
    else $skin_footer = str_replace("{menu}", " &nbsp; ".$config_version_name, $skin_footer);

    $skin_footer = get_skin($skin_footer);
    $skin_footer = str_replace("{content-type}", $lang_content_type, $skin_footer);

    echo $skin_footer;
}

// And the duck fly away.
function b64dck()
{
    $cr = bd_config('e2NvcHlyaWdodHN9');
    $shder = bd_config('c2tpbl9oZWFkZXI=');
    $sfter = bd_config('c2tpbl9mb290ZXI=');

    global $$shder,$$sfter;
    $HDpnlty = bd_config('PGNlbnRlcj48aDE+Q3V0ZU5ld3M8L2gxPjxhIGhyZWY9Imh0dHA6Ly9jdXRlcGhwLmNvbSI+Q3V0ZVBIUC5jb208L2E+PC9jZW50ZXI+PGJyPg==');
    $FTpnlty = bd_config('PGNlbnRlcj48ZGl2IGRpc3BsYXk9aW5saW5lIHN0eWxlPVwnZm9udC1zaXplOiAxMXB4XCc+UG93ZXJlZCBieSA8YSBzdHlsZT1cJ2ZvbnQtc2l6ZTogMTFweFwnIGhyZWY9XCJodHRwOi8vY3V0ZXBocC5jb20vY3V0ZW5ld3MvXCIgdGFyZ2V0PV9ibGFuaz5DdXRlTmV3czwvYT4gqSAyMDA1ICA8YSBzdHlsZT1cJ2ZvbnQtc2l6ZTogMTFweFwnIGhyZWY9XCJodHRwOi8vY3V0ZXBocC5jb20vXCIgdGFyZ2V0PV9ibGFuaz5DdXRlUEhQPC9hPi48L2Rpdj48L2NlbnRlcj4=');
    if(!stristr($$shder,$cr) and !stristr($$sfter,$cr))
    {
        $$shder = $HDpnlty.$$shder;
        $$sfter = $$sfter.$FTpnlty;
    }
}
// Count How Many Comments Have a Specific Article
function CountComments($id, $archive = FALSE)
{

    $result = "0";
    if  ($archive and ($archive != "postponed" and $archive != "unapproved"))
         $all_comments = file(SERVDIR."/cdata/archives/${archive}.comments.arch");
    else $all_comments = file(SERVDIR."/cdata/comments.txt");

    foreach ($all_comments as $comment_line)
    {
        $comment_arr_1 = explode("|>|", $comment_line);
        if($comment_arr_1[0] == $id)
        {
            $comment_arr_2 = explode("||", $comment_arr_1[1]);
            $result = count($comment_arr_2)-1;
        }
    }
    return $result;
}

// insert smilies for adding into news/comments
function insertSmilies($insert_location, $break_location = FALSE, $admincp = FALSE, $wysiwyg = FALSE)
{
    global $config_http_script_dir, $config_smilies;

    $i          = 0;
    $output     = false;
    $smilies    = explode(",", $config_smilies);

    foreach($smilies as $smile)
    {
        $i++;
        $smile = trim($smile);
        if ($admincp)
        {
            if ( $wysiwyg )
                 $output .= "<a href=# onclick=\"document.getElementById('$insert_location').contentWindow.document.execCommand('InsertImage', false, '$config_http_script_dir/skins/emoticons/$smile.gif'); return false;\"><img style=\"border: none;\" alt=\"$smile\" src=\"$config_http_script_dir/skins/emoticons/$smile.gif\" /></a>";
            else $output .= "<a href=# onclick=\"javascript:document.getElementById('$insert_location').value += ' :$smile:'; return false;\"><img style=\"border: none;\" alt=\"$smile\" src=\"$config_http_script_dir/skins/emoticons/$smile.gif\" /></a>";
        }
        else
        {
            $output .= "<a href=\"javascript:insertext(':$smile:','$insert_location')\"><img style=\"border: none;\" alt=\"$smile\" src=\"$config_http_script_dir/skins/emoticons/$smile.gif\" /></a>";
        };

        if ( isset($break_location) && (int)$break_location > 0 && $i%$break_location == 0 )
             $output .= "<br />";
        else $output .= "&nbsp;";
    }

    return $output;
}

// Replaces comments charactars
function replace_comment($way, $sourse)
{
    global $HTML_SPECIAL_CHARS, $config_http_script_dir, $config_smilies;

    $sourse = stripslashes(trim($sourse));

    if($way == "add")
    {
        $find = array( "'\"'", "'\''", "'<'", "'>'", "'\|'", "'\n'", "'\r'", );
        $replace = array( "&quot;", "&#039;", "&lt;", "&gt;", "&#124;", " <br />", "", );
    }
    elseif($way == "show")
    {

        $find = array
        (
            '~\[b\](.*?)\[/b\]~i',
            '~\[i\](.*?)\[/i\]~i',
            '~\[u\](.*?)\[/u\]~i',
            '~\[link\](.*?)\[/link\]~i',
            '~\[link=(.*?)\](.*?)\[/link\]~i',
            '~\[quote=(.*?)\](.*?)\[/quote\]~',
            '~\[quote\](.*?)\[/quote\]~',
        );

        $replace = array
        (
            "<strong>\\1</strong>",
            "<em>\\1</em>",
            "<span style=\"text-decoration: underline;\">\\1</span>",
            "<a href=\"\\1\">\\1</a>",
            "<a href=\"\\1\">\\2</a>",
            "<blockquote><div style=\"font-size: 13px;\">quote (\\1):</div><hr style=\"border: 1px solid #ACA899;\" /><div>\\2</div><hr style=\"border: 1px solid #ACA899;\" /></blockquote>",
            "<blockquote><div style=\"font-size: 13px;\">quote:</div><hr style=\"border: 1px solid #ACA899;\" /><div>\\1</div><hr style=\"border: 1px solid #ACA899;\" /></blockquote>",
        );

        $smilies_arr = explode(",", $config_smilies);
        foreach($smilies_arr as $smile)
        {
            $smile      = trim($smile);
            $find[]     = "':$smile:'";
            $replace[]  = "<img style=\"border: none;\" alt=\"$smile\" src=\"$config_http_script_dir/skins/emoticons/$smile.gif\" />";
        }

    }

    $sourse  = preg_replace($find, $replace, $sourse);

    foreach ($HTML_SPECIAL_CHARS as $key => $value)
        $sourse = str_replace($key,$value,$sourse);

    return $sourse;
}

// safe data for the RTE
function rteSafe($strText)
{
    //returns safe code for preloading in the RTE
    $tmpString = $strText;

    //convert all types of single quotes
    $tmpString = str_replace(chr(145), chr(39), $tmpString);
    $tmpString = str_replace(chr(146), chr(39), $tmpString);
    $tmpString = str_replace("'", "&#39;", $tmpString);

    //convert all types of double quotes
    $tmpString = str_replace(chr(147), chr(34), $tmpString);
    $tmpString = str_replace(chr(148), chr(34), $tmpString);

    //replace carriage returns & line feeds
    $tmpString = str_replace(chr(10), " ", $tmpString);
    $tmpString = str_replace(chr(13), " ", $tmpString);

    return $tmpString;
}

// Hello skin!
function get_skin($skin)
{
    $licensed = false;
    if (!file_exists(SERVDIR.'/cdata/reg.php')) $stts = base64_decode('KHVucmVnaXN0ZXJlZCk=');
    else
    {
        include (SERVDIR.'/cdata/reg.php');
        if (isset($reg_site_key) == false) $reg_site_key = false;

        if (preg_match('/\\A(\\w{6})-\\w{6}-\\w{6}\\z/', $reg_site_key, $mmbrid))
        {
            if ( !isset($reg_display_name) or !$reg_display_name or $reg_display_name == '')
                 $stts = "<!-- (-$mmbrid[1]-) -->";
            else $stts = "<label title='(-$mmbrid[1]-)'>". base64_decode('TGljZW5zZWQgdG86IA==').$reg_display_name.'</label>';
            $licensed = true;
        }
        else $stts = '!'.base64_decode('KHVucmVnaXN0ZXJlZCk=').'!';
    }

    $msn  = bd_config('c2tpbg==');
    $cr   = bd_config('e2NvcHlyaWdodHN9');
    $lct  = bd_config('PGRpdiBzdHlsZT0iZm9udC1zaXplOiA5cHgiPlBvd2VyZWQgYnkgPGEgc3R5bGU9ImZvbnQtc2l6ZTogOXB4IiBocmVmPSJodHRwOi8vY3V0ZXBocC5jb20vY3V0ZW5ld3MvIiB0YXJnZXQ9Il9ibGFuayI+Q3V0ZU5ld3MgMS41LjA8L2E+ICZjb3B5OyAyMDEyIDxhIHN0eWxlPSJmb250LXNpemU6IDlweCIgaHJlZj0iaHR0cDovL2N1dGVwaHAuY29tLyIgdGFyZ2V0PSJfYmxhbmsiPkN1dGVQSFA8L2E+Ljxicj57bC1zdGF0dXN9PC9kaXY+');
    $lct  = preg_replace("/{l-status}/", $stts, $lct);

    if ($licensed == true) $lct = false;
    $$msn = preg_replace("/$cr/", $lct, $$msn);

    return $$msn;
}

/* === HELPERS === */
// Helper for truncation any text
function clbTruncate($match)
{
    if (strlen($match[2]) > $match[1])
         return substr($match[2], 0, $match[1] - 3) . '...';
    else return $match[2];
}

function linkedcat($catids)
{
    $cat_url        = array();
    $art_cat_arr    = explode(",", $catids);
    if (count($art_cat_arr) == 1)
    {
        return "<a href='".PHP_SELF."?cid=".$catids."'>".catid2name($catids)."</a>";
    }
    else
    {
        foreach($art_cat_arr as $thiscat)
            $cat_url[] = "<a href='".PHP_SELF."?cid=".$thiscat."'>".catid2name($thiscat)."</a>&nbsp;";

        return implode(", ", $cat_url);
    }
}

// Replaces news charactars
function replace_news($way, $sourse, $replce_n_to_br=TRUE, $use_html=TRUE)
{
    global $HTML_SPECIAL_CHARS, $config_allow_html_in_news, $config_allow_html_in_comments, $config_http_script_dir, $config_smilies, $config_use_wysiwyg;

    $sourse = trim(stripslashes($sourse));

    if ($way == "show")
    {
        $find = array
        (
            /* 1 */  '~\[upimage=([^\]]*?) ([^\]]*?)\]~i',
            /* 2 */  '~\[upimage=(.*?)\]~i',
            /* 3 */  '~\[b\](.*?)\[/b\]~i',
            /* 4 */  '~\[i\](.*?)\[/i\]~i',
            /* 5 */  '~\[u\](.*?)\[/u\]~i',
            /* 6 */  '~\[link\](.*?)\[/link\]~i',
            /* 7 */  '~\[color=(.*?)\](.*?)\[/color\]~i',
            /* 8 */  '~\[size=(.*?)\](.*?)\[/size\]~i',
            /* 9 */  '~\[font=(.*?)\](.*?)\[/font\]~i',
            /* 10 */ '~\[align=(.*?)\](.*?)\[/align\]~i',
            /* 12 */ '~\[image=(.*?)\]~i',
            /* 13 */ '~\[link=(.*?)\](.*?)\[/link\]~i',
            /* 14 */ '~\[quote=(.*?)\](.*?)\[/quote\]~i',
            /* 15 */ '~\[quote\](.*?)\[/quote\]~i',
            /* 16 */ '~\[list\]~i',
            /* 17 */ '~\[/list\]~i',
            /* 18 */ '~\[\*\]~i',
            /* 19 */ '~{nl}~',
        );

        $replace = array
        (
            /* 1 */  "<img \\2 src=\"${config_http_script_dir}/skins/images/upskins/images/\\1\" style=\"border: none;\" alt=\"\" />",
            /* 2 */  "<img src=\"${config_http_script_dir}/skins/images/upskins/images/\\1\" style=\"border: none;\" alt=\"\" />",
            /* 3 */  "<strong>\\1</strong>",
            /* 4 */  "<em>\\1</em>",
            /* 5 */  "<span style=\"text-decoration: underline;\">\\1</span>",
            /* 6 */  "<a href=\"\\1\">\\1</a>",
            /* 7 */  "<span style=\"color: \\1;\">\\2</span>",
            /* 8 */  "<span style=\"font-size: \\1pt;\">\\2</span>",
            /* 9 */  "<span style=\"font-family: \\1;\">\\2</span>",
            /* 10 */ "<div style=\"text-align: \\1;\">\\2</div>",
            /* 12 */ "<img src=\"\\1\" style=\"border: none;\" alt=\"\" />",
            /* 13 */ "<a href=\"\\1\">\\2</a>",
            /* 14 */ "<blockquote><div style=\"font-size: 13px;\">quote (\\1):</div><hr style=\"border: 1px solid #ACA899;\" /><div>\\2</div><hr style=\"border: 1px solid #ACA899;\" /></blockquote>",
            /* 15 */ "<blockquote><div style=\"font-size: 13px;\">quote:</div><hr style=\"border: 1px solid #ACA899;\" /><div>\\1</div><hr style=\"border: 1px solid #ACA899;\" /></blockquote>",
            /* 16 */ "<ul>",
            /* 17 */ "</ul>",
            /* 18 */ "<li>",
            /* 19 */ "\n",
        );

        $smilies_arr = explode(",", $config_smilies);
        foreach ($smilies_arr as $smile)
        {
            $smile = trim($smile);
            $find[] = "~:$smile:~";
            $replace[] = '<img style="border: none;" alt="'.$smile.'" src="'.$config_http_script_dir.'/skins/emoticons/'.$smile.'.gif" />';
        }

        // word replacement additional
        $replaces = file(SERVDIR.'/cdata/replaces.php');
        unset($replaces[0]);
        foreach ($replaces as $v)
        {
            list ($f, $t) = explode('=', $v, 2);
            $find[] = '~'.str_replace('~','\x7E', $f).'~is';
            $replace[] = $t;
        }

    }
    elseif ($way == "add")
    {

        $find       = array("~\|~", "~\r~", );
        $replace    = array("&#124;", "", );

        if ($use_html != TRUE)
        {
            $find[]    = "'<'";
            $find[]    = "'>'";
            $replace[] = "&lt;";
            $replace[] = "&gt;";
        }

        // if wysywig in ckeditor, replace to <BR> not allowed
        if ($config_use_wysiwyg == 'ckeditor') $replce_n_to_br = false;

        $find[]     = "~\n~";
        $replace[]  = ($replce_n_to_br == true)? "<br />" : "{nl}";


    }
    elseif ($way == "admin")
    {

        $find = array("''", "'{nl}'", "'<'", "'>'");
        $replace = array("", "\n", "&lt;", "&gt;");

        //this is for 'edit news' section when we use WYSIWYG
        if($replce_n_to_br == false)
        {
            $find[] = "~<br />~";
            $replace[] = "\n";
        }
    }

    // Replace all
    $sourse  = preg_replace($find, $replace, $sourse);
    foreach ( $HTML_SPECIAL_CHARS as $key => $value) $sourse = str_replace($key,$value,$sourse);

    // Truncate text
    $sourse = preg_replace_callback('~\[truncate=(.*?)\](.*?)\[/truncate\]~i', 'clbTruncate', $sourse);

    return $sourse;
}

function rating_bar($id, $value = '1/1', $from = 1, $to = 5)
{
    global $_CACHE, $config_http_script_dir, $config_use_rater;
    if ( $config_use_rater == 0 ) return false;

    // only 1 times
    if ( empty($_CACHE['use_script_rater']) )
         $rate = proc_tpl('rater', array('cutepath' => $config_http_script_dir));
    else $rate = false;

    // increase rater
    $_CACHE['use_script_rater']++;

    // average ratings
    list ($cr, $ur) = explode('/', $value);
    if ($ur == 0) $ur = 1;
    $value = $cr / $ur;

    for ($i = $from; $i <= $to; $i++)
        if ($value < $i) $rate .= '<a href="#" id="'.$id.'_'.$i.'" onclick="rateIt('.$id.', '.$i.');">'.RATEN_SYMBOL.'</a>';
                    else $rate .= '<a href="#" id="'.$id.'_'.$i.'" onclick="rateIt('.$id.', '.$i.');">'.RATEY_SYMBOL.'</a>';

    return $rate;
}

// Upload avatar to server
function check_avatar($editavatar)
{
    global $config_http_script_dir;

    // avatar not uploaded?
    if ( strpos($editavatar, $config_http_script_dir) === false)
    {
        // check if avatar always exists
        $Px = SERVDIR.'/cdata/upimages/'.md5($editavatar).'.jpeg';

        if ( !file_exists($Px) )
        {
            $fp = fopen($editavatar, 'r') or ($editavatar = false);

            // may load file?
            if ($editavatar)
            {
                ob_start();
                fpassthru($fp);
                $img = ob_get_clean();
                fclose($fp);

                // save image
                $fp = fopen($Px, 'w');
                fwrite($fp, $img);
                fclose($fp);

                // check attributes of image
                $attrs = getimagesize($Px);
                if ( !isset($attrs[0]) || !isset($attrs[1]) || !$attrs[0] || !$attrs[1])
                {
                    unlink($Px);
                    $editavatar = false;
                }
                else
                {
                    chmod($Px, 0644); // set no execution
                }
            }
        }

        // replace for absolute path
        if ($editavatar)
            $editavatar = str_replace(SERVDIR, $config_http_script_dir, $Px);
    }
    else
    {
        // check - available at server?
        $Px = str_replace($config_http_script_dir, SERVDIR, $editavatar);
        if (!file_exists($Px)) $editavatar = false;
    }

    return $editavatar;
}

// duck flying
function bd_config($str)
{
    return base64_decode($str);
}

?>