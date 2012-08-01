<?PHP

$skin_prefix = "";

// ********************************************************************************
// Skin MENU
// ********************************************************************************

$skin_menu = <<<HTML
    <table cellpadding=5 cellspacing=4 border=0>
        <tr>
            <td> <a class="nav" href="$PHP_SELF?mod=main">Home</a></td><td>|</td>
            <td> <a class="nav" href="$PHP_SELF?mod=addnews&action=addnews" accesskey="a">Add News</a> </td> <td>|</td>
            <td> <a class="nav" href="$PHP_SELF?mod=editnews&action=list">Edit News</a> </td> <td>|</td>
            <td> <a class="nav" href="$PHP_SELF?mod=options&action=options">Options</a> </td> <td>|</td>
            <td> <a class="nav" href="$PHP_SELF?mod=about&action=about">Help/About</a> </td> <td>|</td>
            <td> <a class="nav" href="$PHP_SELF?action=logout">Logout</a> </td>
        </tr>
    </table>
HTML;

// ********************************************************************************
// Skin HEADER
// ********************************************************************************
$skin_header = <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<script type="text/javascript" src="skins/cute.js"></script>
<style type="text/css">
<!--
html, body
{
    background-color: white;
}

select, textarea, input
{
    border: #808080 1px solid;
    color: #000000;
    font-size: 11px;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    background-color: #ffffff;
}

input[type=submit]:hover, input[type=button]:hover
{
    background-color:#EBEBEB !important;
}

a:active,a:visited,a:link
{
    color: #446488;
    text-decoration: none;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-size: 8pt;
}
a:hover
{
    color: #00004F;
    text-decoration: none;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-size: 8pt;
}

a.nav
{
    padding: 10px 10px 9px 10px;
}

a.nav:active, a.nav:visited, a.nav:link
{
    color: #000000;
    font-size: 10px;
    font-weight: bold;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    text-decoration: none;
}

a.nav:hover
{
    font-size: 10px;
    font-weight: bold;
    color: black;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    text-decoration: underline;
}

.header
{
    font-size: 16px;
    font-weight: bold;
    color: #808080;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    text-decoration: none;
    height: 32px;
}
.bborder
{
    background-color: #FFFFFF;
    border: 1px #A7A6B4 solid;
}
.panel
{
    border-radius: .3em;
    border: 1px solid silver;
    background-color: #F7F6F4;
}

div.center
{
    text-align: center;
}

body, td, tr
{
    text-decoration: none;
    font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 8pt;
    cursor: default;
}

#password_strength
{
    border:  1px solid gray;
    padding:  2px;
    background: red;
    margin: 0 0 6px 0;
}

td.top_header
{
    padding: 8px;

}
-->
</style> <title>{title}</title> </head>
<body bgcolor=white>
<table border="0" cellspacing="0" cellpadding="2" style="width:800px; margin: 0 auto;">
  <tr>
        <td class="bborder" bgcolor="#FFFFFF" style="border-radius: .8em; -moz-border-radius: .8em;">
            <table border=0 cellpadding=0 cellspacing=0 bgcolor="#ffffff" width="800" >
            <tr>
                 <td bgcolor="#FFFFFF" style="padding: 8px;"><span style="background: #ffffcc; padding: 4px;">Safe skin (check skins/default.skin.php)</span></td>
            </tr>
            <tr>
                 <td bgcolor="#000000" ><img src="skins/images/blank.gif" width=1 height=1></td>
            </tr>
            <tr>
                 <td bgcolor="#F7F6F4">{menu}</td>
            </tr>
            <tr>
                 <td bgcolor="#000000" ><img src="skins/images/blank.gif" width=1 height=1></td>
            </tr>
            <tr><td bgcolor="#FFFFFF" ><img src="skins/images/blank.gif" width=1 height=5></td></tr>
            <tr>
                <td >
</center>
<!--SELF-->

<table border=0 cellpading=0 cellspacing=0 width="100%" height="100%" >
<tr>
    <td width="10%"> <p align="center"><br /><img border="0" src="skins/images/{image-name}.gif" > </td>
    <td width="87%" height="20%"> {breadcrumbs} <div class=header>{header-text}</div> </td>
</tr>
<tr>
    <td width="10%">&nbsp;</td>
    <td width="87%">
<!--MAIN area-->
HTML;

// ********************************************************************************
// Skin FOOTER
// ********************************************************************************
$skin_footer = <<<HTML
         <!--MAIN area-->
        <img border=0 height=10 src="skins/images/blank.gif"></tr>
        </table>
        <!--/SELF-->
                </td>
        </tr></table></td></tr></table>
    <br /><div style="text-align: center;">{copyrights}</div>
    </body></html>
HTML;

?>
