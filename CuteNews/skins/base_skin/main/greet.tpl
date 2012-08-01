<table border=0 cellpading=0 cellspacing=0 width=654>
    <tr>
        <td width=650 colspan=5 height=1>&nbsp;
            <script type="text/javascript">
                datetoday = new Date();
                timenow=datetoday.getTime();
                datetoday.setTime(timenow);
                thehour = datetoday.getHours();
                if                 (thehour < 9 )         display = "Morning";
                else if (thehour < 12)         display = "Day";
                else if (thehour < 17)         display = "Afternoon";
                else if (thehour < 20)         display = "Evening";
                else display = "Night";
                var greeting = ("Good " + display);
                document.write(greeting);
            </script> {member}{greet}
            <br /><br />
        </td>
    </tr>
    {warn}
</table>