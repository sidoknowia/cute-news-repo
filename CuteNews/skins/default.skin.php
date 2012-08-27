<?PHP

$skin_prefix = "";

// ********************************************************************************
// Skin MENU
// ********************************************************************************

$skin_menu = <<<HTML
        <table cellpadding=5 cellspacing=4 border=0>
        <tr>
            <td><a class="nav" href="$PHP_SELF?mod=main">Home</a></td><td>|</td>
            <td><a class="nav" href="$PHP_SELF?mod=addnews&action=addnews" accesskey="a">Add News</a></td><td>|</td>
            <td><a class="nav" href="$PHP_SELF?mod=editnews&action=list">Edit News</a></td><td>|</td>
            <td><a class="nav" href="$PHP_SELF?mod=options&action=options">Options</a></td><td>|</td>
            <td><a class="nav" href="$PHP_SELF?mod=about&action=about">Help/About</a></td><td>|</td>
            <td><a class="nav" href="$PHP_SELF?action=logout">Logout</a></td>
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
<link rel="shortcut icon" type="image/ico" href="skins/images/favicon.ico"/>

<script type="text/javascript" src="skins/cute.js"></script>
<style type="text/css">
<!--
html, body
{
    background-color: white;
}

select, textarea, input, button
{
    border: #808080 1px solid;
    color: #000000;
    font-size: 12px;
    padding: 3px;
    background-color: #ffffff;
}

input[type=submit]:hover, input[type=button]:hover, button:hover
{
    background-color: #EBEBEB !important;
}

a:active,a:visited,a:link
{
    color: #446488;
    text-decoration: none;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-size: 12px;
}
a:hover
{
    color: #00004F;
    text-decoration: none;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-size: 12px;
}

a.nav
{
    padding: 10px 10px 9px 10px;
}

a.nav:active, a.nav:visited, a.nav:link
{
    color: #000000;
    font-size: 12px;
    font-weight: bold;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    text-decoration: none;
}

a.nav:hover
{
    font-size: 12px;
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
.bborder {
    background-color: #FFFFFF;
    border: 1px #A7A6B4 solid;
    width: 800px;
    border-radius: .8em;
    -moz-border-radius: .8em;
}
.menu-border {
border-top: 1px solid black;
border-bottom: 1px solid black;
background: #ffffff; /* Old browsers */
background: -moz-linear-gradient(top,  #ffffff 0%, #f1f1f1 50%, #e1e1e1 51%, #f6f6f6 100%); /* FF3.6+ */
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffffff), color-stop(50%,#f1f1f1), color-stop(51%,#e1e1e1), color-stop(100%,#f6f6f6)); /* Chrome,Safari4+ */
background: -webkit-linear-gradient(top,  #ffffff 0%,#f1f1f1 50%,#e1e1e1 51%,#f6f6f6 100%); /* Chrome10+,Safari5.1+ */
background: -o-linear-gradient(top,  #ffffff 0%,#f1f1f1 50%,#e1e1e1 51%,#f6f6f6 100%); /* Opera 11.10+ */
background: -ms-linear-gradient(top,  #ffffff 0%,#f1f1f1 50%,#e1e1e1 51%,#f6f6f6 100%); /* IE10+ */
background: linear-gradient(to bottom,  #ffffff 0%,#f1f1f1 50%,#e1e1e1 51%,#f6f6f6 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#f6f6f6',GradientType=0 ); /* IE6-9 */
}

.panel
{
    border-radius: .3em;
    border: 1px solid silver;
    background-color: #F7F6F4;
    padding: 4px;
}

div.center { text-align: center; }
body, td, tr
{
    text-decoration: none;
    cursor: default;
}

#password_strength
{
    border:  1px solid gray;
    padding:  2px;
    background: red;
    margin: 0 0 6px 0;
}

td.top_header { padding: 8px; }

*, td, th
{
    font-size: 12px;
    font-family: Helvetica, Arial, Verdana, sans-serif;
}
-->
</style>
<title>{title}</title>
</head>

<body style="width: 800px; margin: 8px auto;">

<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td>&nbsp;</td>
        <td class="bborder">

            <table border=0 cellpadding=0 cellspacing=0 width="100%" >
            <tr> <td>&nbsp;</td> </tr>
            <tr> <td class="menu-border"> {menu} </td> </tr>
            <tr> <td>

                <!--SELF-->
                <table border=0 cellpadding=0 cellspacing=0 width="100%" >
                <tr>
                    <td width="10%"> <p align="center"><br /><img border="0" src="skins/images/{image-name}.gif" /> </td>
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
                    <br/>
                    </td>
                </tr>
                </table>
                <!--/SELF-->
            </td>
            </tr>
            </table>
        </td>
        <td>&nbsp;</td>
    </tr>
</table>
<br />
<div style="text-align: center;">{copyrights}</div>
</body></html>
HTML;

?>
