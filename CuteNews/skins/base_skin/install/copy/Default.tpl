<?PHP
///////////////////// TEMPLATE Default /////////////////////
$template_active = <<<HTML
<div style="width:420px; margin-bottom:30px;">
<div><strong>{title}</strong> {star-rate}</div>

<div style="text-align:justify; padding:3px; margin-top:3px; margin-bottom:5px; border-top:1px solid #D3D3D3;">{short-story}</div>

<div style="float: right;">[full-link]Read more[/full-link] | [com-link]{comments-num} Comments[/com-link]</div>

<div><em>Posted on {date} by {author}</em></div>
</div>
HTML;


$template_full = <<<HTML
<div style="width:420px; margin-bottom:15px;">
<div><strong>{title}</strong> {star-rate}</div>

{avatar}

<div style="text-align:justify; padding:3px; margin-top:3px; margin-bottom:5px; border-top:1px solid #D3D3D3;">{full-story}</div>

<div style="float: right;">{comments-num} Comments</div>

<div><em>Posted on {date} by {author}</em></div>
</div>
HTML;


$template_comment = <<<HTML
<div style="width: 400px; margin-bottom:20px;">

<div style="border-bottom:1px solid black;"> by <strong>{author}</strong> @ {date}</div>

<div style="padding:2px; background-color:#F9F9F9">{comment}</div>

</div>
HTML;


$template_form = <<<HTML
  <table border="0" width="370" cellspacing="0" cellpadding="0">
    <tr>
      <td width="60">Name:</td>
      <td><input type="text" name="name"></td>
    </tr>
    <tr>
      <td>E-mail:</td>
      <td><input type="text" name="mail"> (optional)</td>
    </tr>
    <tr>
      <td>Smile:</td>
      <td>{smilies}</td>
    </tr>
    <tr>
      <td colspan="2">
      <textarea cols="40" rows="6" id=commentsbox name="comments"></textarea><br />
      <input type="submit" name="submit" value="Add My Comment">
      <input type=checkbox name=CNremember  id=CNremember value=1><label for=CNremember> Remember Me</label> |
  <a href="javascript:CNforget();">Forget Me</a>
      </td>
    </tr>
  </table>
HTML;


$template_prev_next = <<<HTML
<p align="center">[prev-link]<< Previous[/prev-link] {pages} [next-link]Next >>[/next-link]</p>
HTML;
$template_comments_prev_next = <<<HTML
<p align="center">[prev-link]<< Older[/prev-link] ({pages}) [next-link]Newest >>[/next-link]</p>
HTML;
$template_search = <<<HTML

<script type="text/javascript">
function mySelect(form) { form.select(); }

function ShowOrHide(d1, d2)
{
    var i;
    if (d1 != '') for(i = 1; i < d2; i++) DoDiv(d1+'_'+i);
}

function DoDiv(id)
{
    var item = null;
    if (document.getElementById) item = document.getElementById(id);
    else if (document.all) item = document.all[id];
    else if (document.layers) item = document.layers[id];

    if (item.style) {
        if (item.style.display == "none") item.style.display = "";
        else item.style.display = "none";
    } else item.visibility = "show";
}
</script>
<form method=get action="{PHP_SELF}?subaction=search">

    <input type=hidden name=dosearch value=yes>
    {user_post_query}

    <table>
        <tr><td align="right">News</td><td><input type=text value="{story}" name=story size="24"></td></tr>

        <tr id="advance_1" style='display:none; z-index:1;'><td align="right">Title</td><td><input type=text value="{title}" name=title size="24"></td></tr>
        <tr id="advance_2" style='display:none; z-index:1;'><td align="right">Author</td><td><input type=text value="{user}" name=user size="24"></td></tr>
        <tr id="advance_3" style='display:none; z-index:1;'>
            <td align="right">From date</td>
            <td>
                <select name=from_date_day> <option value=""></option> {day_f} </select>
                <select name=from_date_month> <option value=""></option> {month_f} </select>
                <select name=from_date_year> <option value=""></option> {year_f} </select>
            </td>
        </tr>
        <tr id="advance_4" style='display:none; z-index:1;'>
            <td align="right">To date</td>
            <td>
                <select name=to_date_day> <option value=""></option> {day_t} </select>
                <select name=to_date_month> <option value=""></option> {month_t}  </select>
                <select name=to_date_year> <option value=""></option> {year_t} </select>
            </td>
        </tr>

        <tr id="advance_5" style='display:none; z-index:1;'>
            <td align="right">Search and archives</td><td><input type=checkbox {selected_search_arch} name="search_in_archives" value="TRUE"> </td>
        </tr>

        <tr>
            <td><a href="javascript:ShowOrHide('advance',6)">advanced</a></td>
            <td><input type=submit value=Search></td>
        </tr>
    </table>

</form>

HTML;
?>
