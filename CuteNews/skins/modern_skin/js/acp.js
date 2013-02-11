function cn_getId(id)
{
    if (document.all) return (document.all[id]);
    else if (document.getElementById) return (document.getElementById(id));
    else if (document.layers) return (document.layers[id]);
    else return null;
}

function cn_toggle(id)
{
    var item = cn_getId(id);

    if (typeof item !== 'undefinex')
    {
        if (item.style)
        {
            if (arguments.length == 2)
            {
                if (arguments[1] == true)  item.style.display = "";
                else item.style.display = "none";
            }
            else
            {
                if (item.style.display == "none") item.style.display = "";
                else item.style.display = "none";
            }
        }
        else item.visibility = "show";
    }
}