<?PHP

if ($member_db[UDB_ACL] != ACL_LEVEL_ADMIN)
    msg("error", lang("Access Denied"), lang("You don't have permission for this section"));

if($action == "" or !$action)
{

$wizard_options = <<<HTMLWIZARDS
<ol>
<li style="margin-bottom:5px;"><a href='$PHP_SELF?mod=wizards&action=rss'><b>Rss</b> Setup and Integration</a></li>
<li style="margin-bottom:5px;"><a href='$PHP_SELF?mod=wizards&action=news'>Integrate <b>News</b> into your site</a></li>
<li><a href='$config_http_script_dir/migrate/147_150.php'>1.4.7 &rarr; 1.5.0</a></li>
</ol>

HTMLWIZARDS;

   msg("wizard", lang("Choose Wizard"), "$wizard_options");
}


// ********************************************************************************
// Initiate the News Integration Wizard
// ********************************************************************************
if($action == "news"){

//Gather the Templates
$templates_list = array();
if(!$handle = opendir(SERVDIR."/cdata")) die("Can not open directory ".SERVDIR."/cdata ");

while (false !== ($file = readdir($handle)))
{
    if (preg_replace('/^.*\.(.*?)$/', '\\1', $file) == 'tpl')
    {
        $file_arr           = explode(".", $file);
        $templates_list[]   = $file_arr[0];
    }
}
closedir($handle);

$templates_html = "<select name=w_template>";
foreach($templates_list as $single_template)
{
    if($single_template != "rss")
    {
        if ($single_template == "Default")
             $templates_html .= "<option selected value=\"$single_template\">$single_template</option>";
        else $templates_html .= "<option value=\"$single_template\">$single_template</option>";
    }
}
$templates_html .= "</select>";

//Gather the Categories
$cat_lines = file(SERVDIR."/cdata/category.db.php");
if ($cat_lines)
{
    $cat_html = "<select style='display:;' name=w_category[] id=category multiple>";
    foreach ($cat_lines as $single_line)
    {
        $cat_arr    = explode("|", $single_line);
        $cat_html   .= "<option value=\"$cat_arr[0]\">(ID:$cat_arr[0]) $cat_arr[1]</option>\n";
    }
    $cat_html .= "</select><br><label for=allcategory><input id=allcategory onClick=\"if(this.checked){getElementById('category').style.display='none';}else{getElementById('category').style.display='';}\" type=checkbox value='yes' name='w_allcategory'> Or Show from All Categories</label>";
}
else $cat_html = "You have no categories";


echoheader("wizard", "News Integration Wizard");

echo<<<WIZARDHTML
<table border=0 cellpadding=0 cellspacing=0 width=100% height=100%><form method="post" action="$PHP_SELF"><tr><td >

<table cellspacing="0" cellpadding="3" width="645" height="100%" border="0" style="border-collapse: collapse" bordercolor="#111111"><tr>
  <td width="639" colspan="2">



Welcome to the News Integration Wizard. This tool will help you to integrate the
news that you have published using CuteNews, into your existing Webpage. <br>
&nbsp;</td></tr>
  <tr>
    <td bgcolor="#F7F6F4" style="padding:3px; border-bottom:1px solid gray;" width="639" colspan="2">
<b><font size="2">Quick Customization...</font></b></td>
  </tr>
  <tr>
    <td width="356" >



<b><br>



Number of Active News to Display:</b></td>
    <td width="277" rowspan="2"  valign="top" align="center">



<br>
<input style="text-align: center" name="w_number" size="11"></td>
  </tr>
  <tr>
    <td width="356" style="padding-left:10px;  ">



<p align="justify"><i>if the active news are less then the specified number to show, the rest of the
news will be fetched from the archives (if any)</i></td>
  </tr>
  <tr>
    <td width="356" >



<b><br>
Template to Use When Displaying News:</b></td>
    <td width="277" rowspan="2"  valign="top" align="center">



<br>
$templates_html

</td>
  </tr>
  <tr>
    <td width="356" style="padding-left:10px;  ">



<p align="justify"><i>using different templates you can customize the look of
your news, comments etc.</i></td>
  </tr>
  <tr>
    <td width="356" >



<b><br>
Categories to Show News From:</b></td>
    <td width="277" rowspan="2"  valign="top" align="center">



<br>
$cat_html
</td>
  </tr>
  <tr>
    <td width="356" style="padding-left:10px;  ">



<p align="justify"><i>you can specify only from which categories news will be
displayed, hold CTRL to select multiple categories (if any)<br>
&nbsp;</i></td>
  </tr>
  <tr>
    <td bgcolor="#F7F6F4" style="padding:3px; border-bottom:1px solid gray; " width="639" colspan="2">
<b><font size="2">Advanced Settings...</font></b></td>
  </tr>
  <tr>
    <td width="356" >



<b><br>
Start 'Displaying' From...</b></td>
    <td width="277" rowspan="2"  align="center" valign="top">



<br>
<input name="w_start_from" size="11" style="text-align: center"></td>
  </tr>
  <tr>
    <td width="356" style="padding-left:10px;  ">



<i>if Set, the displaying of the news will be started from the specified number
(eg. if set to 2 - the first 2 news will be skipped and the rest shown)</i></td>
  </tr>
  <tr>
    <td width="356" >



<b><br>
Reverse News Order:</b></td>
    <td width="277" rowspan="2"  align="center" valign="top">



<br>
&nbsp;<input type=checkbox value="yes" name="w_reverse"></td>
  </tr>
  <tr>
    <td width="356" style="padding-left:10px;  ">



<i>if Yes, the order of which the news are shown will be reversed</i></td>
  </tr>
  <tr>
    <td width="356" >



<b><br>
Show Only Active News:</b></td>
    <td width="277" rowspan="2"  align="center" valign="top">



<br>
<input type=checkbox value="yes" name="w_only_active"></td>
  </tr>
  <tr>
    <td width="356" style="padding-left:10px;  ">



<i>if Yes, even if the number of news you requested to be shown is bigger than
all active news, no news from the archives will be shown</i></td>
  </tr>
  <tr>
    <td width="356">



<b><br>
Static Include:</b></td>
    <td width="277" rowspan="2" align="center" valign="top">



<br>
<input type=checkbox value="yes" name="w_static"></td>
  </tr>
  <tr>
    <td width="356" style="padding-left:10px;">



<i>if Yes, the news will be displayed but will not show the full story and
comment pages when requested. useful for <a href=# onclick="javascript:Help('multiple_includes')">multiple includes</a>.</i></td>
  </tr>
  <tr>
    <td width="639" colspan="2">



&nbsp;</td>
  </tr>
  <tr>
    <td width="639" colspan="2" style="border-top:1px solid gray; padding-top:10px;">

<center><input type=submit style="font-weight:bold;" value="Proceed to Integration >>"></center>

&nbsp;</td>
  </tr>
</table>

<input type=hidden name=mod value=wizards>
<input type=hidden name=action value=news_step2>

</td></tr></form></table>
WIZARDHTML;

echofooter();
}
// ********************************************************************************
// Show The News Integration Code
// ********************************************************************************
if ($action == "news_step2")
{
echoheader("wizard", lang("News Integration"));

$the_code = '&lt;?php'."\n";

// Try to determine include path
$include_path = dirname(dirname(__FILE__)) .'/show_news.php';

if ($w_number and $w_number != '')
    $the_code .= '$number='.$w_number.";\n";

if ($w_template != 'Default')
    $the_code .= '$template="'.$w_template."\";\n";

// Get ready with Categories (if any)
if($w_allcategory != 'yes'  and isset($w_category) and $w_category != '')
{
    $i=0;
    foreach($w_category as $category)
    {
        $i++;
        $my_category .= $category;
        if (count($w_category) != $i){ $my_category .= ','; }
    }

    if (count($w_category) > 1)
         $the_code .= '$category="'.$my_category."\";\n";
    else $the_code .= '$category='.$my_category.";\n";
}


if ($w_reverse == 'yes')                    $the_code .= "\$reverse=TRUE;\n";
if ($w_only_active == 'yes')                $the_code .= "\$only_active=TRUE;\n";
if ($w_static == 'yes')                     $the_code .= "\$static=TRUE;\n";
if ($w_start_from and $w_start_from != '')  $the_code .= "\$start_from=$w_start_from;\n";

$the_code .= "include(\"$include_path\");\n?&gt;";
echo "CuteNews determined your full path to show_news.php to be: '<b>$include_path</b>'<br>
If for some reasons the include path is incorrect or does not work, please determine<br>
the relative path for including <i>show_news.php</i> yourself or
consult your administrator.<br>
<br>
To show your news, insert (copy & paste) the code into some of your pages (*.php) :<br><br>
<textarea style='font-weight: bold;' cols=70 rows=10>$the_code</textarea><br>";


echofooter();
}
// ********************************************************************************
// Initiate the RSS Wizard
// ********************************************************************************
if($action == "rss"){
echoheader("wizard", lang("RSS Set-Up Wizard"));

echo"Rich Site Summary (sometimes referred to as Really Simple Syndication);<br>
RSS allows a web developer to share the content on his/her site. RSS repackages the web content <br>
as a list of data items, to which you can subscribe from a directory of RSS publishers. <br>
RSS 'feeds' can be read with a web browser or special RSS reader called a content aggregator.
<br><br><input onClick=\"document.location='$PHP_SELF?mod=wizards&action=rss_step2';\" type=button value='Proceed with RSS Configuration >>'><br><br>
";

echofooter();
}
// ********************************************************************************
// Show the RSS config
// ********************************************************************************
if($action == "rss_step2")
{
    include(SERVDIR."/cdata/rss_config.php");

    if ($rss_language == '' or !$rss_language) $rss_language = 'en-us';
    if ($rss_encoding == '' or !$rss_encoding) $rss_encoding = 'UTF-8';
    echoheader("wizard", lang("RSS Configuration"));

echo <<<HTML

    <table border=0 height=1 width=617 cellspacing="0" cellpadding="0">
     <form method=POST action="index.php">
     <td height="21" width=400 bgcolor=#F7F6F4 >
         &nbsp;<b>URL of the page where you include your news</b><br>
         &nbsp;<i>example: http://mysite.com/news.php</i><br>
         &nbsp;<i>or: $config_http_script_dir/example2.php</i>
     <td height="21"  bgcolor=#F7F6F4 colspan=2>
     <input name="rss_news_include_url" value="$rss_news_include_url" type=text size=30>
     <tr>

     <td height="21" >
     <br>
         &nbsp;Title of the RSS feed
     <td height="21" colspan=2>
     <br>
     <input name="rss_title" value="$rss_title" size=30 >
     </tr>


     <tr>
     <td height="21" bgcolor=#F7F6F4 >
     <br>
         &nbsp;Character Encoding (default: <i>UTF-8</i>)
     <td height="21" colspan=2 bgcolor=#F7F6F4 >
     <br>
          <input name="rss_encoding" value="$rss_encoding" size=20 >
     </tr>



     <tr>
     <td height="21" >
     <br>
         &nbsp;Language (default: <i>en-us</i>)
     <td height="21">
     <br>
          <input name="rss_language" value="$rss_language" size=5 >
     </tr>


     <tr>
     <td height="1" colspan="2" colspan=3>
     <br /><br><input type=submit style="font-weight:bold; font-size:110%;" value="Save Configurations and Proceed >>" accesskey="s"> &nbsp;
     <input style="font-size:90%;" onClick="document.location='$PHP_SELF?mod=wizards&action=customizerss';" type=button value='Skip to Customization >>'>
     </tr>

     <input type=hidden name=mod value=wizards>
     <input type=hidden name=action value=dosaverss>
     </form>
     </table>



HTML;

echofooter();
}

// ********************************************************************************
// Save the RSS Configuration
// ********************************************************************************
if($action == "dosaverss")
{
    if (strpos($rss_news_include_url, 'http://') === false)
        msg("error",  LANG_ERROR_TITLE, lang("The URL where you include your news must start with <b>http://</b>"));

    $handler = fopen(SERVDIR."/cdata/rss_config.php", "w") or msg("error",  LANG_ERROR_TITLE, "Can not open file ./cdata/rss_config.php");
    fwrite($handler, "<?PHP \n\n//RSS Configurations (Auto Generated file)\n\n");

    fwrite($handler, "\$rss_news_include_url = \"".htmlspecialchars($rss_news_include_url)."\";\n\n");
    fwrite($handler, "\$rss_title = \"".htmlspecialchars($rss_title)."\";\n\n");
    fwrite($handler, "\$rss_encoding = \"".htmlspecialchars($rss_encoding)."\";\n\n");
    fwrite($handler, "\$rss_language = \"".htmlspecialchars($rss_language)."\";\n\n");

    fwrite($handler, "?>");
    fclose($handler);

    msg("wizard", lang("RSS Configuration Saved"), lang("The configurations were saved successfully").".<br><br><input onClick=\"document.location='$PHP_SELF?mod=wizards&action=customizerss';\" type=button value='Proceed With RSS Customization >>'>");
}
// ********************************************************************************
// Save the RSS Configuration
// ********************************************************************************
if($action == "customizerss")
{
    echoheader("wizard", "RSS Customization");

    // Detect the categories (if any)
    $cat_lines = file(SERVDIR."/cdata/category.db.php");
    if(count($cat_lines) > 0)
    {
        $cat_options .= "<select style=\"\" id=categories multiple size=5>   \n";
        foreach($cat_lines as $single_line)
        {
            $cat_arr = explode("|", $single_line);
            $cat_options .= "<option value=\"$cat_arr[0]\">(ID:$cat_arr[0]) $cat_arr[1]</option>\n";
        }
        $cat_options .= "</select> <br><label for=allcategories><input onclick=\"if(this.checked){getElementById('categories').style.display='none';}else{getElementById('categories').style.display='';}\" type=checkbox id=allcategories value=yes>".lang('Or show from all Categories')."</label>";

    }else $cat_options = lang("You do not have any categories").". <input type=hidden id=categories><input type=hidden id=allcategories>";


//
// Show the HTML
//
echo<<<HTMLECHO
<SCRIPT type="text/javascript">
function generateCode(){
sbox = document.getElementById('categories');
var categoryString = '';
var firstDone = 0;

        for (var i=0; i<sbox.length; i++) {
                        if (sbox[i].selected) {

                                if(firstDone == 1){ categoryString = categoryString + ','; }
                                categoryString = categoryString + sbox[i].value;

                                firstDone = 1;

                        }
        }

var number = document.getElementById('number').value;

var string = '$config_http_script_dir/rss.php';

if(document.getElementById('allcategories').checked || categoryString == ''){
        if(number != ''){ string += '?number='+number; }

}else{
         string += '?category=' + categoryString;
        if(number != ''){ string += '&number='+number; }
}



//alert(string);

var htmlcode = '<a title="RSS Feed" href="' + string + '">\\n<img src="$config_http_script_dir/skins/images/rss_icon.gif" border=0  />\\n</a>';

document.getElementById('result').value = htmlcode;


}

</SCRIPT>

<table cellspacing="0" cellpadding="5" width="647" height="100%" border="0" style="border-collapse: collapse" bordercolor="#111111"><tr>
  <td width="647" colspan="3">After You have configured your RSS options, the
  RSS feed is ready to be used.<br>
  <br>
  URL Address of your RSS: <b><a href="$config_http_script_dir/rss.php">
  $config_http_script_dir/rss.php</a><br>
&nbsp;</b></td></tr>
  <tr>
    <td bgcolor="#F7F6F4" style="border-bottom:1px solid gray;" width="647" colspan="3"><b><font size="2">&nbsp;Customizing
    your RSS feed:</font></b></td>
  </tr>
  <tr>
    <td width="647" colspan="3">&nbsp;</td>
  </tr>
  <tr>
    <td width="58">&nbsp;</td><td width="393">Number of articles to be shown in
    the RSS (default:10):</td><td width="196"><input id=number size=5 type="text" size="20"></td>
  </tr>
  <tr>
    <td width="58">&nbsp;</td><td width="393">Show articles only from these
    categories:</td><td width="196">






$cat_options





    </td>
  </tr>
  <tr>
    <td colspan="3" style="padding:40px;">After you have selected your preferred settings, click the
    'Generate HTML Code' button and you are ready to insert this code into your
    page. The generated code will be of a linked RSS image that will be pointing
    to your RSS feed (rss.php).

    </td>
  </tr>
  <tr>
    <td width="647" colspan="3">
    <p align="center">
    <input type=button value="Generate HTML Code" onClick="generateCode();" style="font-weight: bold; font-size:120%;" ><br>
    <br>
    <textarea id=result rows="5" cols="100"></textarea> </td>
  </tr>
  <tr>
    <td width="647" colspan="3">&nbsp;</td>
  </tr>
</table>
HTMLECHO;

echofooter();
}