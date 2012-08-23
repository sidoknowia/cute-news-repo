<form method=get action="{$PHP_SELF}">
    <input type=hidden name=action value=templates>
    <input type=hidden name=mod value=options>
    <table border=0 cellpadding=0 cellspacing=0>
        <tr>
            <td width=373 height="75">
                <b>Manage Templates</b>

                <table border=0 cellpadding=0 cellspacing=0 width=347  class="panel">
                        <tr>
                            <td width=126height="23">Editing Template</td>
                            <td width=225height="23">: <b>{$do_template}</b></td>
                        </tr>
                        <tr>
                            <td width=126 height="27"> &nbsp;Switch to Template</td>
                            <td width=225 height="27">:&nbsp;<select size=1 name=do_template>{$SELECT_template}</select> <input type=submit value=Go></td>
                        </tr>
                        <tr>
                            <td width=351 height="25" colspan="2">
                                <a href="{$PHP_SELF}?mod=options&subaction=new&action=templates">[create new template]</a> {$show_delete_link}
                            </td>
                        </tr>
                </table>
            </td>
            <td width=268 height="75" align="center">
                <!-- HELP -->
                <table cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="25" align=middle><img border="0" src="skins/images/help_small.gif" /></td>
                        <td >&nbsp;<a onclick="Help('templates'); return false;" href="#">Understanding Templates</a></td>
                    </tr>
                </table>
                <!-- END HELP -->
            </td>
        </tr>
    </table>
</form>

<br>
<b>Edit Template Parts</b>
<form method=post action="{$PHP_SELF}">
    <table width="100%">
    <tr> <!- start active news -->
        <td height="7"  bgcolor=#F7F6F4 colspan="2">
        <b><a style="font-size:16px" href="javascript:ShowOrHide('active-news1','active-news2')" >Active News</a></b>
    </tr>
    <tr id='active-news1' {$tr_hidden}>
    <td height="9" width="200" valign="top">
    <b>{title}<br />
    {avatar}<br />
    {short-story}<br />
    {full-story}<br />
    {author}<br />
    {author-name}<br />
    [mail] </b>and<b> [/mail]<br />
    {date}<br />
    [link] </b>and<b> [/link]<br />
    [full-link] </b>and<b> [/full-link]<br />
    [com-link] </b>and<b> [/com-link]<br />
    {comments-num}<br />
    {category}<br />
    {category-icon}<br />
    {star-rate}<br />
    <td height="9"  valign="top" width=430>
    - Title of the article<br />
    - Show Avatar image (if any)<br />
    - Short story of news item<br />
    - The full story<br />
    - Author of the article, with link to his email (if any)<br />
    - The name of the author, without email<br />
    - Will generate a link to the author mail (if any) eg. [mail]Email[/mail]<br />
    - Date when the story is written<br />
    - Will generate a permanent link to the full story<br />
    - Link to the full story of article, only if there is full story<br />
    - Generate link to the comments of article<br />
    - This will display the number of comments posted for article<br />
    - Name of the category where article is posted (if any)<br />
    - Shows the category icon (if any)<br />
    - Rating bar<br />
    </tr>
    <tr id='active-news2' {$tr_hidden}>
        <td height="8" colspan="2"> <textarea rows="9" cols="98" name="edit_active">{$template_active}</textarea> </td>
    </tr> <!-- End active news -->

    <tr> <!-- Start full story -->
        <td height="7" bgcolor=#F7F6F4 colspan="2"><b><a style="font-size:16px" href="javascript:ShowOrHide('full-story1','full-story2')" >Full Story</a></b></td>
    </tr>
    <tr id='full-story1' {$tr_hidden}>
        <td height="9" width="200" valign="top">
            <b> {title}<br />
            {avatar}<br />
            {full-story}<br />
            {short-story}</b><b><br />
            {author}<br />
            {author-name}<br />
            [mail] </b>and<b> [/mail]<br />
            {date}<br />
            {comments-num}<br />
            {category}    <br />
            {category-icon}<br />
            {back-previous}<br />
            {index-link}<br /><br>
            {star-rate}<br />
            </b>
        </td>
        <td height="9"  valign="top">
            - Title of the article<br />
            - Show Avatar image (if any)<br />
            - The full story<br />
            - Short story of news item<br />
            - Author of the article, with link to his email (if any)<br />
            - The name of the author, without email<br />
            - Will generate a link to the author mail (if any) eg. [mail]Email[/mail]<br />
            - Date when the story is written<br />
            - This will display the number of comments posted for article<br />
            - Name of the category where article is posted (if any)<br />
            - Shows the category icon (if any)<br />
            - Insert back link</br>
            - Insert direct link to main page <br>&nbsp;&nbsp;You may use <a href="{$PHP_SELF}?mod=tools&amp;action=xfields" target="_blank">custom fields</a> there. Syntax: {your_field}</br>
            - Rating bar<br />
        </td>
    </tr>
    <tr id='full-story2' {$tr_hidden}>
        <td height="8"  colspan="2"> <textarea rows="9" cols="98" name="edit_full">{$template_full}</textarea> </td>
    </tr> <!-- End full story -->

    <tr> <!-- Start comment -->
        <td height="7"  bgcolor=#F7F6F4 colspan="2">
        <b><a style="font-size:16px" href="javascript:ShowOrHide('comment1','comment2')">Comment</a></b>
    </tr>
    <tr id='comment1' {$tr_hidden}>
        <td height="9" width="200" valign="top">
            <b>  {author}<br />
            {mail}<br />
            {date}<br />
            {comment}<br />
            {comment-iteration}</b>
        </td>
        <td height="9"  valign="top">
            - Name of the comment poster<br />
            - E-mail of the poster<br />
            - Date when the comment was posted<br />
            - The Comment<br />
            - Show the sequential number of individual comment
        </td>
    </tr>
    <tr id='comment2' {$tr_hidden}>
        <td height="8"  colspan="2"> <textarea rows="9" cols="98" name="edit_comment">{$template_comment}</textarea> </td>
    </tr> <!-- End comment -->

    <tr> <!-- Start add comment form -->
        <td height="7"  bgcolor=#F7F6F4 colspan="2">
            <b><a style="font-size: 16px" href="javascript:ShowOrHide('add-comment-form1','add-comment-form2')" >Add comment form</a></b>
        </td>
    </tr>
    <tr id='add-comment-form1' {$tr_hidden}>
        <td height="9" width="1094" valign="top" colspan="2"> Please do not edit this unless you have basic HTML knowledge!!! </td>
    </tr>
    <tr id='add-comment-form2' {$tr_hidden}>
        <td height="8"  colspan="2"> <textarea rows="9" cols="98" name="edit_form">{$template_form}</textarea> </td>
    </tr> <!-- End add comment form -->

    <tr> <!-- Start previous & next -->
        <td height="7"  bgcolor=#F7F6F4 colspan="2">
            <b><a style="font-size:16px" href="javascript:ShowOrHide('previous-next1','previous-next2')" >News Pagination</a></b>
        </td>
    </tr>

    <tr id='previous-next1' {$tr_hidden}>
        <td height="9" width="200" valign="top">
            <b> [prev-link] </b>and<b> [/prev-link]<br />
            [next-link] </b>and<b> [/next-link]<br />
            {pages}<br />
        </td>
        <td height="9"  valign="top">
            - Will generate a link to preveous page (if there is)<br />
            - Will generate a link to next page (if there is)<br />
            - Shows linked numbers of the pages; example: <a href='#'>1</a> <a href='#'>2</a> <a href='#'>3</a> <a href='#'>4</a>
        </td>
    </tr>

    <tr id='previous-next2' {$tr_hidden}>
        <td height="8"  colspan="2"> <textarea rows="3" cols="98" name="edit_prev_next">{$template_prev_next}</textarea> </td>
    </tr> <!-- End previous & next -->

    <tr> <!-- Start previous & next COMMENTS-->
        <td height="7"  bgcolor=#F7F6F4 colspan="2">
            <b><a style="font-size:16px" href="javascript:ShowOrHide('previous-next21','previous-next22')" >Comments Pagination</a></b>
        </td>
    </tr>
    <tr id='previous-next21' {$tr_hidden}>
        <td height="9" width="200" valign="top">
            <b> [prev-link] </b>and<b> [/prev-link]<br />
            [next-link] </b>and<b> [/next-link]<br />
            {pages}<br />
        </td>
        <td height="9" valign="top">
            - Will generate a link to preveous page (if there is)<br />
            - Will generate a link to next page (if there is)<br />
            - Shows linked numbers of the pages; example: <a href='#'>1</a> <a href='#'>2</a> <a href='#'>3</a> <a href='#'>4</a>
        </td>
    </tr>

    <tr id='previous-next22' {$tr_hidden}>
        <td height="8"  colspan="2"> <textarea rows="3" cols="98" name="edit_comments_prev_next">{$template_comments_prev_next}</textarea> </td>
    </tr> <!-- End previous & next COMMENTS -->

    <tr>
        <td height="8"  colspan="2">
            <input type=hidden name=mod value=options>
            <input type=hidden name=action value=dosavetemplates>
            <input type=hidden name=do_template value="{$do_template}">
            <br /><input type=submit value="   Save Changes   " accesskey="s">
        </td>
    </tr>
    </table>
</form>
