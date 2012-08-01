<h2>Check file permissions</h2>

<p>
{WRITABLE}
    <p><span style="font-size: 14px; color: green;"><b>./cdata/</b> is writable</span></p>
    <p><a style="font-size: 12px;" href="{$PHP_SELF}?action=make">Go to next step</a></p>
{/WRITABLE}
{-WRITABLE}
    <p><span style="font-size: 14px; color: red;"><b>./cdata/</b> is not writable!</span></p>
    <p><span style="font-size: 12px;">Check "chmod 755 cdata" or "chmod 775 cdata", or "chmod 777 cdata".</span></p>{/-WRITABLE}
</p>
<br/>