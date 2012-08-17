<form name=properties>
    <input type=hidden name=CKEditorFuncNum value='{$CKEditorFuncNum}'>
    <table style='margin-top:10px;' border=0 cellpadding=0 cellspacing=0  width=100%>

        <td height=33>

            <b>Image Properties</b>
            <table border=0 cellpadding=0 cellspacing=0 class="panel" style='padding:5px'width=290px; >

            <tr>
                <td width=80>Alt. Text: </td>
                <td><input tabindex=1 type=text name=alternativeText style="width:150;"></td>
            </tr>

            <tr>
                <td>Image Align</td>
                <td>
                    <select name='imageAlign' style='width:150'>
                        <option value=none>None</option>
                        <option value=left>Left</option>
                        <option value=right>Right</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Border</td>
                <td><input type=text value='0' name=imageBorder style="width:35"> pixels</td>
            </tr>

            <tr>
                <td>Width</td>
                <td><input type=text value='' name=imageWidth style="width:35"> pixels</td>
            </tr>

            <tr>
                <td>Height</td>
                <td><input type=text value='' name=imageHeight style="width:35"> pixels</td>
            </tr>

        </table>
    </table>
</form>