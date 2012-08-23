<form method=post name=addnews action="{$PHP_SELF}">

    <input type=hidden name=mod value=addnews>
    <input type=hidden name=action value=doaddnews>
    <input type=hidden name="csrf_code" value="{$CSRF}" />

    <table border=0 cellpadding=0 cellspacing=0 width="654" >

    <tr>
        <td width="75">Title</td>
        <td width="575" colspan="2"><input type=text size="55" name="title" tabindex=1></td>
    </tr>

    {foreach from=xfields}
        <tr>
            <td width="75">{$xfields.1}</td>
            <td width="575" colspan="2"><input tabindex=2 type=text size="42" value="" name="{$xfields.0}" >&nbsp;&nbsp;&nbsp;<span style="font-size:7pt">{$xfields.2}</span></td>
        </tr>
    {/foreach}

    {USE_AVATAR}
    <tr>
        <td width="75">Avatar URL</td>
        <td width="575" colspan="2"><input tabindex=2 type=text size="42" value="{$member_db8}" name="manual_avatar" >&nbsp;&nbsp;&nbsp;<span style="font-size:7pt">(optional)</span></td>
    </tr>
    {/USE_AVATAR}

    <tr id='singlecat'>
        <td width="75">Category</td>
        <td width="575" colspan="2">
            <select id='selecsinglecat' name=category tabindex=3> <option value=""> --- </option> {$cat_html} </select>
            <a href="javascript:ShowOrHide('multicat','singlecat');" onClick="javascript:document.getElementById('selecsinglecat').name='';">(multiple categories)</a>
        </td>
    </tr>

    <tr style="display:none;" id='multicat'>
        <td width="75"> Category </td>
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="panel"> {$multi_cat_html} </tr> </table>
        </td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td width="75" valign="top"><br />Short Story</td>
        <td>
            <textarea style="width: 464px;" rows="12" cols="74" id="short_story" name="short_story" tabindex=4></textarea>
        </td>
        <td width="108" valign="top" style='background: url(skins/images/baloon.gif) no-repeat top left' align="center">
            <p><a href=# onclick="window.open('{$PHP_SELF}?&mod=images&action=quick&area={$short_story_id}', '_Addimage', 'HEIGHT=500,resizable=yes,scrollbars=yes,WIDTH=360');return false;" target="_Addimage"><br />[insert image]</a></p>
            <p>{$insertsmiles}</p>
        </td>
    </tr>
    <tr><td colspan="3">&nbsp;</td></tr>
    <tr id='full-story' style='display:none; z-index:1;'>

        <td width="75" valign="top"><br />Full Story<br /><span style="font-size:7pt">(optional)</span></td>
        <td>
            <textarea rows="12" cols="74" id="full_story" name="full_story" tabindex=5 style="width:464px;"></textarea>
        </td>
        <td width="108" valign="top" style='background: url(skins/images/baloon.gif) no-repeat top left' align="center">
            <p><a href=# onclick="window.open('{$PHP_SELF}?&mod=images&action=quick&area={$full_story_id}', '_Addimage', 'HEIGHT=500,resizable=yes,scrollbars=yes,WIDTH=360');return false;" target="_Addimage"><br />[insert image]</a></p>
            <p>{$insertsmiles_full}</p>
        </td>
    </tr>

    <tr>
        <td>&nbsp;</td>
        <td>
             <table border=0 cellspacing=0 cellpadding=0 width=100%>
             <tr>
               <td width=50%> <input type=submit style='font-weight:bold' title="Post the New Article" value="     Add News     " accesskey="s"> </td>
               <td width=50% align=right> <input style='width:110px;'type=button onClick="ShowOrHide('full-story',''); setTimeout('increaseTextareaBug()',310);" value="Toggle Full-Story"> <input style='width:90px;' type=button onClick="ShowOrHide('options','');" value="Article Options"> </td>
             </tr>
            </table>
        </td>
    </tr>

    <tr id='options' style='display:none;'>
        <td width="75"><br>Options</td>
        <td width="575" colspan="4">
            <br>

            <label for='convert'>
            <input id='convert' style="border:0; background-color:transparent" type=checkbox value="yes" name="if_convert_new_lines" checked > Convert new lines to &lt;br /&gt;</label>
            <br/>

            <label for='html'>
            <input id='html' style="border:0; background-color:transparent" type=checkbox value="yes" name="if_use_html" checked> Use HTML in this article</label>
            <br/>

            <label for='active'><input checked id='active' style="border:0; background-color:transparent" type=radio value="active" name="postpone_draft">
            <b>Normal</b>, add article as active</label>
            <br />

             <label for='draft'><input id='draft' style="border:0; background-color:transparent" type=radio value="draft" name="postpone_draft">
            <b>Draft</b>, add article as unapproved</label>
            <br />

            <label for='postpone'><input id='postpone' style="border:0; background-color:transparent" type=radio value="postpone" name="postpone_draft">
            <b>Postpone</b>, make article active at</label>

            <select name=from_date_day>{$dated}</select>
            <select name=from_date_month>{$datem}</select>
            <select name=from_date_year>{$datey}</select>
            @ <input value='{$date_hour}' title='24 Hour format [hh]' name=from_date_hour size=2 type=text /> : <input value='{$date_minutes}' title='Minutes [mm]' name=from_date_minutes size=2 type=text />
        </td>

    </tr>
    </table>
</form>