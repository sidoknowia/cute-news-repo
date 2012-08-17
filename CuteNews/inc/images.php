<?PHP

if ($member_db[UDB_ACL] > ACL_LEVEL_JOURNALIST or ($member_db[UDB_ACL] != ACL_LEVEL_ADMIN and $action == "doimagedelete"))
    msg("error", "Access Denied", "You don't have permission to manage images");

$allowed_extensions = array("gif", "jpg", "png", "bmp", "jpe", "jpeg");

// ********************************************************************************
// Show Preview of Image
// ********************************************************************************
if($action == "preview")
{ ?>
<HTML>
    <HEAD>
        <TITLE>Image Preview</TITLE>
        <script type='text/javascript'>
            var NS = (navigator.appName=="Netscape")?true:false;
            function fitPic()
            {
                iWidth = (NS)?window.innerWidth:document.body.clientWidth;
                iHeight = (NS)?window.innerHeight:document.body.clientHeight;
                iWidth = document.images[0].width - iWidth;
                iHeight = document.images[0].height - iHeight;
                window.resizeBy(iWidth, iHeight-1);
                self.focus();
            }
        </script>
    </HEAD>
    <BODY bgcolor="#FFFFFF" onload='fitPic();' topmargin="0" marginheight="0" leftmargin="0" marginwidth="0">
         <script type='text/javascript'>
             document.write( "<img src='<?php echo $config_http_script_dir; ?>/cdata/upimages/<?php echo $image; ?>' border=0>" );
         </script>
    </BODY>
</HTML>
<?php

}
// ********************************************************************************
// Show Images List
// ********************************************************************************
elseif ($action != "doimagedelete")
{
    if ($subaction != 'upload') $CSRF = CSRFMake();
    if ($action == "quick")
    {
        ?><html><head>
<title>Insert Image</title>
<style type="text/css">
<!--
    select, option, textarea, input
    {
        border: #808080 1px solid;
        color: #000000;
        font-size: 11px;
        font-family: Verdana;
        background-color: #ffffff
    }
    BODY, TD {text-decoration: none; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 8pt;}
    a:active,a:visited,a:link {font-size : 10px; color: #808080; font-family: verdana; text-decoration: none;}
    a:hover {font-size : 10px; color: darkblue; font-weight:bold; text-decoration: none; }
    .panel  { border: 1px dotted silver; background-color: #F7F6F4;}
-->
</style>
</head>

<body bgcolor="white">
<script language="javascript" type="text/javascript">

    function insertimage(selectedImage)
    {
        var area = '<?php echo $area; ?>';

        alternativeText = document.forms['properties'].alternativeText.value;
        imageAlign = document.forms['properties'].imageAlign.value;
        imageBorder = document.forms['properties'].imageBorder.value;

        var appends = '';
        var imageWidth = document.forms['properties'].imageWidth.value;
        var imageHeight = document.forms['properties'].imageHeight.value;
        if (imageWidth) appends += ' width=' + imageWidth;
        if (imageHeight) appends += ' height=' + imageWidth;

        finalImage = " <img " + appends + " border='" + imageBorder + "' align='" + imageAlign +"' alt='" + alternativeText + "' src='<?php echo $config_http_script_dir; ?>/cdata/upimages/"+ selectedImage +"'>";
        <?php

            if ($wysiwyg && $_REQUEST['CKEditorFuncNum'])
            {
                $CKEditorFuncNum = $_REQUEST['CKEditorFuncNum'];
                echo "window.opener.CKEDITOR.tools.callFunction(".$CKEditorFuncNum.", '".$config_http_script_dir."/cdata/upimages/'+ selectedImage);";
                echo "window.close();";
            }
            else
            {
                echo 'opener.document.getElementById(area).value += finalImage; window.close();';
            }
        ?>
    }

    function PopupPic(sPicURL)
    {
        window.open('<?php echo $PHP_SELF; ?>?mod=images&action=preview&image='+sPicURL, '', 'resizable=1,HEIGHT=200,WIDTH=200');
    }

    window.resizeTo(410, 550);
    self.focus();

</script>
<?php

    }
    else
    {
        echoheader("images", "Manage Images");
    }

    // ********************************************************************************
    // Upload Image(s)
    // ********************************************************************************

    if ($subaction == "upload")
    {
        CSRFCheck();
        $CSRF = CSRFMake();
        for ($image_i = 1; $image_i < ($images_number+1); $image_i++)
        {
            $current_image  = 'image_'.$image_i;
            $image          = $_FILES[$current_image]['tmp_name'];
            $image_name     = $_FILES[$current_image]['name'];
            $image_name     = str_replace(" ", "_", $image_name);
            $img_name_arr   = explode(".",$image_name);
            $type           = end($img_name_arr);

            if (empty($image_name)) $img_result .= "<br><span style='color: red;'>$current_image -> No File Specified For Upload!</span>";
            elseif( !isset($overwrite) and file_exists(SERVDIR."/cdata/upimages/".$image_name)){ $img_result .= "<br><span style='color: red;'>$image_name -> Image already exist!</span>";}
            elseif( !(in_array($type, $allowed_extensions) or in_array(strtolower($type), $allowed_extensions)) )
            {
                $img_result .= "<br><span style='color:red;'>$image_name ->This type of file is not allowed!</span>";
            }
            else
            {
                // Image is OK, upload it
                copy($image, SERVDIR."/cdata/upimages/".$image_name) or $img_result .= "<br><span style='color: red;'>$image_name -> Couldn't copy image to server</span><br />Check if file_uploads is allowed in the php.ini file of your server";
                if (file_exists(SERVDIR."/cdata/upimages/".$image_name))
                {
                    $img_result .= "<br><span style='color: green;'>$image_name -> Image was uploaded</span>";
                    if ($action == "quick")
                        $img_result .= " <a title=\"Inser this image in the $my_area\" href=\"javascript:insertimage('$image_name');\">[insert it]</a>";

                }
                // if file is uploaded succesfully
            }
        }
    }

// Add the JS for multiply image upload.
?>

<script type='text/javascript'>

function AddRowsToTable() {
     var tbl = document.getElementById('tblSample');
     var lastRow = tbl.rows.length;

     // if there's no header row in the table, then iteration = lastRow + 1
     var iteration = lastRow+1;
     var row = tbl.insertRow(lastRow);

     var cellRight = row.insertCell(0);
     var el = document.createElement('input');
     el.setAttribute('type', 'file');
     el.setAttribute('name', 'image_' + iteration);
     el.setAttribute('size', '30');
     el.setAttribute('value', iteration);
     cellRight.appendChild(el);

     document.getElementById('images_number').value = iteration;
}
function RemoveRowFromTable() {
     var tbl = document.getElementById('tblSample');
     var lastRow = tbl.rows.length;
     if (lastRow > 1){
              tbl.deleteRow(lastRow - 1);
               document.getElementById('images_number').value =  document.getElementById('images_number').value - 1;
     }
}

</script>
<form name="form" id="form" action="<?php echo $PHP_SELF; ?>?mod=images" method="post" enctype="multipart/form-data">

    <input type="hidden" name="csrf_code" value="<?php echo $CSRF; ?>" />
    <table border=0 cellpadding=0 cellspacing=0  width=100%>

        <td height="33">
        <b>Upload Image</b>
        <table border=0 cellpadding=0 cellspacing=0 class="panel" cellpadding=8>
        <tr>
        <td height=25>

            <table  border="0" cellspacing="0" cellpadding="0" id="tblSample">
                <tr id="row">
                    <td width="1" colspan="2"><input type="file" size="30" name="image_1"></td>
                </tr>
            </table>

            <table border="0" cellspacing="0" cellpadding="0" style="margin-top:5px;">
                <tr>
                    <td>
                        <INPUT TYPE="submit" name="submit" VALUE="Upload" style="font-weight:bold;"> &nbsp;
                        <input type=button value='-' style="font-weight:bold; width:22px;" title='Remove last file input box' onClick="RemoveRowFromTable();return false;">
                        <input type=button value='+' style="font-weight:bold; width:22px;" title='Add another file input box' onClick="AddRowsToTable();return false;"> &nbsp;
                        <input style="border:0px; background-color:#F7F6F4;" type=checkbox name=overwrite id=overwrite value=1><label title='Overwrite file(s) if exist' for=overwrite> Overwrite</label>
                    </td>
                </tr>
            </table>
        <?php echo $img_result; ?>
        </table>

        <input type=hidden name=wysiwyg value='<?php echo $wysiwyg; ?>'>
        <input type=hidden name=CKEditorFuncNum value='<?php echo $CKEditorFuncNum; ?>'>
        <input type=hidden name=subaction value=upload>
        <input type=hidden name=area value='<?php echo $area; ?>'>
        <input type=hidden name=action value='<?php echo $action; ?>'>
        <input type=hidden name='images_number' id='images_number' value='1'>
</form>

<?php

    if ($action == "quick") echo proc_tpl('images/quick', array('CKEditorFuncNum' => $_REQUEST['CKEditorFuncNum']));
    echo "<tr><td><img height=1 style=\"height: 13px !important; height: 1px;\" border=0 src=\"skins/images/blank.gif\" width=1></td></tr>
        <tr><td><b>Uploaded Images</b></tr>
        <tr><td height=1>
            <form action='$PHP_SELF?mod=images' METHOD='POST'>
            <input type='hidden' name='csrf_code' value='$CSRF' />
            <table width=100% height=100% cellspacing=0 cellpadding=0>";

        $i = 0;
        $img_dir = opendir(SERVDIR."/cdata/upimages");
        while ($file = readdir($img_dir))
        {
             //Yes we'll store them in array for sorting
             $images_in_dir[] = $file;
        }

        natcasesort($images_in_dir);
        reset($images_in_dir);
        foreach ($images_in_dir as $file)
        {
            $img_name_arr = explode(".",$file);
            $img_type     = end($img_name_arr);
            if ( (in_array($img_type, $allowed_extensions) or in_array(strtolower($img_type), $allowed_extensions)) and $file != ".." and $file != "." and is_file(SERVDIR."/cdata/upimages/".$file))
            {
                $i++;
                $this_size =  filesize(SERVDIR."/cdata/upimages/".$file);
                $total_size += $this_size;
                $img_info = getimagesize(SERVDIR."/cdata/upimages/".$file);
                if ( $i%2 != 0 ) $bg = "bgcolor=#F7F6F4"; $bg = "";
                if ($action == "quick")
                {
                    $my_area = str_replace("_", " ", $area);
                    echo "
                    <tr $bg>
                        <td height=16 width=1px> <a title='Preview this image' href=\"javascript:PopupPic('".$file."')\"><img style='border:0px;' src='skins/images/view_image.gif'></a></td>
                        <td height=16 width=100%><a title=\"Insert this image in the $my_area\" href=\"javascript:insertimage('$file')\">$file</a></td>
                        <td height=16 align=right>$img_info[0]x$img_info[1]&nbsp;&nbsp;</td>
                        <td height=16 align=right>&nbsp;". formatsize($this_size) ."</td>
                    </tr>";
                }
                else
                {
                    echo "<tr $bg>
                            <td height=16>&nbsp;</td>
                            <td height=16 width=63%><a target=_blank href=\"". $config_http_script_dir ."/cdata/upimages/$file\">$file</a></td>
                            <td height=16 align=right>$img_info[0]x$img_info[1]</td>
                            <td height=16 align=right>&nbsp;". formatsize($this_size) ."</td>
                            <td width=70 height=16 align=right><input type=checkbox name=images[$file] value=\"$file\"></td>
                         </tr>";
                }
            }
        }

        if ($i > 0)
        {
            echo "<tr><td height=16>";
            if($action != "quick")
            {
                echo" <td colspan=4 align=right><br><input type=submit value='Delete Selected Images'></tr>";
            }

            echo"<tr height=1>
                    <td width=14>&nbsp;</td> <td><br/><b>Total size</b></td> <td>&nbsp;</td>
                    <td align=right><br/><b>". formatsize($total_size) .'</b></td>
                </tr>';

        }

    echo '</table><input type=hidden name=action value=doimagedelete></form></table>';

    if($action != "quick") echofooter();

}
// ********************************************************************************
// Delete Image
// ********************************************************************************
elseif ($action == "doimagedelete")
{
    CSRFCheck();

    if(!isset($images))
        msg("info","No Images selected","You must select images to be deleted.", $PHP_SELF."?mod=images");

    foreach ($images as $image)
        unlink(SERVDIR."/cdata/upimages/".$image) or print("Could not delete image <b>$file</b>");

    msg("info", "Image(s) Deleted", "The image was successfully deleted.", $PHP_SELF."?mod=images");

}
?>