<form method=post action="{$PHP_SELF}">
    <input type=hidden name="csrf_code" value="{$CSRF}" />
    <input type=hidden name=action value=add>
    <input type=hidden name=mod value=ipban>
    <table border=0 cellpadding=0 cellspacing=0 width="645">
        <tr>
            <td width=321 height="33">
                <p><b>Block IP or Nickname</b></p>
                <table border=0 cellpadding=0 cellspacing=0 width=500  class="panel" cellpadding="7" >
                    <tr>
                        <td width=79 height="25">&nbsp;IP Address:</td>
                        <td width=274 height="25"> <input type=text name="add_ip"> example: <i>129.32.31.44</i> or <i>129.32.*.*</i> </td>
                    </tr>
                    <tr>
                        <td width=79 height="25">&nbsp;Nick name:</td>
                        <td width=274 height="25"> <input type=text name="add_nick"> <input type=submit value="Block IP or nick / Refresh"></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</form>

<table border=0 cellpadding=0 cellspacing=0 width="645">
    <tr>
        <td height="28"><b>Blocked IP Addresses</b></td>
    </tr>

    <tr>
        <td height=1>

            <table cellspacing=0 cellpadding=0>

                <tr>
                    <td width=15 bgcolor=#F7F6F4></td>
                    <td width=260 bgcolor=#F7F6F4><b>IP</b></td>
                    <td width=200 bgcolor=#F7F6F4><b>times been blocked</b></td>
                    <td width=120 bgcolor=#F7F6F4><b>expire</b></td>
                    <td width=140 bgcolor=#F7F6F4><b>unblock</td>
                </tr>

                {foreach from=iplist}
                <tr height="18" {$iplist.bg}>
                    <td> &nbsp; </td>
                    <td> <a href="http://www.ripe.net/perl/whois?searchtext={$iplist.ip}" target=_blank title="Get more information about this ip">{$iplist.ip}</a> </td>
                    <td> {$iplist.times} </td>
                    <td> {$iplist.expire} </td>
                    <td> <a href="{$PHP_SELF}?mod=ipban&action=remove&remove_ip={$iplist.ip}">[unblock]</a></td>
                </tr>
                {/foreach}

            </table>
        </td>
    </tr>
</table>