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