<div style="position: absolute; margin: -48px 0 0 250px;">
    <iframe id="checkversion" style='float: left; margin: 4px; border: none; padding: 0; height: 28px; width: 300px; overflow: hidden;' src="about:blank"></iframe>
    <button style="float: left; border: 1px solid #c0c0e0; border-radius: 3px; cursor: pointer; background: #f8f8f0; padding: 4px;" onclick="document.getElementById('checkversion').src = '{$PHP_SELF}?mod=update&amp;action=check'">Check updates</button>
</div>

<table border=0 cellpadding=0 cellspacing=0 width=654>
    <tr>
        <td width=650 colspan=5 height=1>&nbsp;
            <script type="text/javascript">
                datetoday = new Date();
                timenow=datetoday.getTime();
                datetoday.setTime(timenow);
                thehour = datetoday.getHours();
                if (thehour < 9 )      display = "Morning";
                else if (thehour < 12) display = "Day";
                else if (thehour < 17) display = "Afternoon";
                else if (thehour < 20) display = "Evening";
                else display = "Night";

                var greeting = ("Good " + display);
                document.write(greeting);
            </script> {member}{greet}
            <br /><br />
        </td>
    </tr>
    {warn}
</table>