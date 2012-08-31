<?PHP

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  Load the specified section in PopUp Window
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
if (isset($section))
{
    $section = preg_replace('~[^a-z0-9_]~i', '', $section);
    $help_section = proc_tpl('help/sections/'.$section);
    echo proc_tpl('help/index');
}
else
{

    echoheader("question", "Help Documentation");
    echo "<style type=\"text/css\">
        <!--
        .code {
                font-family : Andale Mono, Courier;
                border: 1px solid #BBCDDB;
                margin:10px;
                padding:4px;
                background:#FBFFFF;
        }
    h1 {
                background-color : #EAF0F4;
                border-bottom : #000000 1px solid;
                border-top : #000000 1px solid;
                border-left : #000000 1px solid;

                color : #000000;
                font-family : Tahoma, Verdana, Arial, Helvetica, sans-serif;
                font-size : 15px;
                font-weight : bold;
                padding-bottom : 5px;
                padding-left : 10px;
                padding-right : 10px;
                padding-top : 5px;
                text-decoration : none;
        }
        -->
        </style>";
    foreach ($help_sections as $help_section){
            echo"$help_section<BR><br>";
    }

        echofooter();
}
?>