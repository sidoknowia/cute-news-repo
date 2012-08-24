<script src="core/ckeditor/ckeditor.js"></script>
<script type="text/javascript">
function submitForm() { return true; }
function confirmDelete(url)
{
    var agree=confirm("Do you really want to permanently delete this article ?");
    if (agree) document.location = url;
}
</script>

{SAVED}<h2>The changes were successfully saved</h2>{/SAVED}

<form onSubmit= "return submitForm();" method="POST" name="addnews" action="{$PHP_SELF}">

    <input type=hidden name="csrf_code" value="{$CSRF}" />
    <table border=0 cellpadding=2 cellspacing=0 >
    <tr>
        <td width="100">Info.</td>
        <td width="571" colspan="6"> Posted on {$newstime} by {$item_db1} </td>
    </tr>
    <tr>
        <td width="100">Title</td>
        <td width="464" colspan="3"><input type=text name=title value="{$item_db2}" size=55 tabindex=1></td>
        <td width="103" valign="top">&nbsp;</td>
    </tr>

    {foreach from=xfields}
        <tr>
            <td width="75">{$xfields.1}</td>
            <td width="575" colspan="2"><input tabindex=2 type=text size="42" value="{$xfields.3}" name="{$xfields.0}">&nbsp;&nbsp;&nbsp;<span style="font-size:7pt">{$xfields.2}</span></td>
        </tr>
    {/foreach}

    {USE_AVATAR}
    <tr>
        <td>Avatar URL</td>
        <td width="464" colspan="3">
        <input type=text name=editavatar value="{$item_db5}" size=42 tabindex=2>&nbsp;&nbsp;&nbsp;<span style="font-size:7pt">(optional)</span>
        <td width="103" valign="top">
    </tr>
    {/USE_AVATAR}

    <tr>
        <td>Category</td>
        <td colspan="2">
            {CATEGORY} <table width="100%" border="0" cellspacing="0" cellpadding="0" class="panel"> {$lines_html} </table> {/CATEGORY}
            {-CATEGORY}<span style="color: gray;">{{No category}}</span>{/-CATEGORY}
        <td>&nbsp;</td>
    </tr>

    <tr>
        <td valign="top"> <br />Short Story</td>
        <td colspan="2" width="612" colspan="2">
            <textarea style="resize: none; width: 600px; height: 240px;" rows="12" cols="74" id="short_story" name="short_story" tabindex=3>{$item_db3}</textarea>
        </td>
    </tr>

    <tr><td colspan="3">&nbsp;</td> </tr>
    <tr>
        <td valign="top"> <br />Full Story<br /><span style="font-size:7pt">(optional)</span> </td>
        <td width="612" colspan="2">
            <textarea style="resize: none; width: 600px; height: 320px;" rows="12" cols="74" id="full_story" name="full_story" tabindex=4>{$item_db4}</textarea>
        </td>
    </tr>

    <tr>

        <td>&nbsp;</td>
        <td colspan="2">
            <input type=hidden name=id value={$id}>
            <input type=hidden name=action value=doeditnews>
            <input type=hidden name=mod value=editnews>
            <input type=hidden name=source value="{$source}">
            <br/>
            <table border=0 cellspacing=0 cellpadding=2 width=100%>
                <tr>
                    <td align="left"> <input type="submit" style='font-weight:bold' value="Save Changes" accesskey="s">&nbsp; </td>
                    <td align="right">
                        {UNAPPROVED}<input type=button value="Approve" onclick="javascript:document.location=('{$PHP_SELF}?mod=massactions&selected_news[]={$id}&action=mass_approve&source=unapproved');"> &nbsp;{/UNAPPROVED}
                        <input type="button" value="Delete" onClick="confirmDelete('{$PHP_SELF}?mod=editnews&action=doeditnews&source={$source}&ifdelete=yes&id={$id}')"> &nbsp;
                        <input style='width:120px;' type=button onClick="ShowOrHide('options','')" value=" Article Options ">
                    </td>
              </tr>
            </table>
        </td>
    </tr>

    <tr id='options' style='display:none;'>
        <td> <br>Options</td>
        <td width="565" colspan="4"> &nbsp;<br>
            <label for='convert'> <input id='convert' style="border:0; background-color:transparent" type=checkbox value="yes" name="if_convert_new_lines" disabled > Convert new lines to &lt;br /&gt;</label> <br/>
            <label for='html'> <input id='html' style="border:0; background-color:transparent" type=checkbox value="yes" name="dummi" checked disabled> Use HTML in this article</label> <input type=hidden name="if_use_html" value="yes"> <br/>
        </td>
    </tr>
    </table>

</form>
<br/>

<!-- COMMENT FORM -->
<form method=post name=comments action="{$PHP_SELF}">
    <input type=hidden name="csrf_code" value="{$CSRF}" />
    <table border=0 cellpadding=0 cellspacing=0 width="720" height="100%" >

    {HASCOMMENTS}
    <tr>
        <td width="75">Comments</td>
        <td><b>Poster</b>, Comment preview</td>
        <td width="120"> <b>Date</b> </td>
        <td width="1">&nbsp;</td>
    </tr>
    {/HASCOMMENTS}
    {$Comments_HTML}

<script type="text/javascript">
    (function()
    {
        var settings =
        {
            skin: 'v2',
            width: 655,
            height: 350,
            customConfig: '',
            language: 'en',
            entities_latin: false,
            entities_greek: false,
            toolbar: [
                ['Source','Maximize','Scayt','PasteText','Undo','Redo','Find','Replace','-','SelectAll','RemoveFormat','NumberedList','BulletedList','Outdent','Indent'],
                ['Image','Table','HorizontalRule','Smiley'],
                ['Link','Unlink','Anchor'],
                ['Format','FontSize','TextColor','BGColor'],
                ['Bold','Italic','Underline','Strike','Blockquote'],
                ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],

            ],
            filebrowserBrowseUrl: '{$PHP_SELF}?&mod=images&action=quick&wysiwyg=true',
            filebrowserImageBrowseUrl: '{$PHP_SELF}?&mod=images&action=quick&wysiwyg=true'
        };
        CKEDITOR.replace( 'short_story', settings );
        CKEDITOR.replace( 'full_story', settings );
    })();

</script>
