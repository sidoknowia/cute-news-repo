{SAVED}<h2>The changes were successfully saved</h2>{/SAVED}

<form method="POST" name="addnews" action="{$PHP_SELF}">

    <input type=hidden name="csrf_code" value="{$CSRF}" />
    <table border=0 cellpadding=0 cellspacing=0 width="720" height="100%" >
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
            <td width="575" colspan="2"><input tabindex=2 type=text size="42" value="{$xfields.3}" name="{$xfields.0}" >&nbsp;&nbsp;&nbsp;<span style="font-size:7pt">{$xfields.2}</span></td>
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
        <td width="400" colspan="3"> <table width="400" border="0" cellspacing="0" cellpadding="0" class="panel"> {$lines_html} </table>
        <td>&nbsp;</td>
    </tr>

    <tr>
        <td valign="top"> <br />Short Story </td>
        <td width="472" colspan="2">
            <textarea style="resize: none; width: 460px; height: 240px;" rows="12" cols="74" id="short_story" name="short_story" tabindex=3>{$item_db3}</textarea>
        </td>

        <td width="140" valign="top" style='background: url(skins/images/baloon.gif) no-repeat top left' align="center">
            <p><a href=# onclick="window.open('{$PHP_SELF}?&mod=images&action=quick&area={$short_story_id}', '_Addimage', 'HEIGHT=500,resizable=yes,scrollbars=yes,WIDTH=360');return false;" target="_Addimage"><br />[insert image]</a></p>
            <p>{$short_story_smiles}</p>
        </td>
    </tr>

    <tr>
        <td valign="top"> <br />Full Story<br /><span style="font-size:7pt">(optional)</span> </td>
        <td width="472" colspan="2">
            <textarea style="resize: none; width: 460px; height: 320px;" rows="12" cols="74" id="full_story" name="full_story" tabindex=4>{$item_db4}</textarea>
        </td>

        <td width="140" valign="top" style='background: url(skins/images/baloon.gif) no-repeat top left' align="center">
            <p><a href=# onclick="window.open('{$PHP_SELF}?&mod=images&action=quick&area={$short_story_id}', '_Addimage', 'HEIGHT=500,resizable=yes,scrollbars=yes,WIDTH=360');return false;" target="_Addimage"><br />[insert image]</a></p>
            <p>{$full_story_smiles}</p>
        </td>
    </tr>

    <tr>

        <td>&nbsp;</td>
        <td colspan="2">
            <input type=hidden name=id value={$id}>
            <input type=hidden name=action value=doeditnews>
            <input type=hidden name=mod value=editnews>
            <input type=hidden name=source value={$source}>
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
            {WYSIWYG}
                <label for='convert'><input id='convert' style="border:0; background-color:transparent;" type=checkbox value="yes" name="if_convert_new_lines" disabled > Convert new lines to &lt;br /&gt;</label><br/>
                <label for='html'><input id='html' style="border:0; background-color:transparent" type=checkbox value="yes" name="dummi" checked disabled> Use HTML in this article</label> <input type=hidden name="if_use_html" value="yes"><br/>
            {/WYSIWYG}
            {-WYSIWYG}
                <label for='convert'><input id='convert' style="border:0; background-color:transparent" type=checkbox value="yes" name="if_convert_new_lines" checked > Convert new lines to &lt;br /&gt;</label><br/>
                <label for='html'><input id='html' style="border:0; background-color:transparent" type=checkbox value="yes" name="if_use_html" checked> Use HTML in this article</label><br/>
            {/-WYSIWYG}
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