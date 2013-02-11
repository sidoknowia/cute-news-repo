<script type="text/javascript" src="<?php echo cn_path().'/core/ckeditor/ckeditor.js'; ?>"></script>

<form action="<?php cn_path('acp.php'); ?>?q=add" method="post">

    <input type="hidden" name="csrf_token" value="<?php echo cn_csrf(); ?>">

    <div class="right"><a href="#" class="dashed">Article options</a></div>

    <h2 class="h2">Add news</h2>

    <h3 class="h3">News title</h3>
    <div><input type="text" name="title" style="width: 1000px;"/></div>

    <div class="cnsep"></div>
    <h3 class="h3">Short story</h3>
    <div><textarea id="short_story"></textarea></div>

    <div  class="cnsep"></div>

    <div class="right"><a href="#" class="dashed" onclick="cn_toggle('fld-fullstory')">Toggle</a></div>
    <h3 class="h3">Full story</h3>
    <div id="fld-fullstory" style="display: none;"><textarea id="full_story"></textarea></div>

    <div  class="cnsep"></div>
    <span class="submit primary"><input type="submit" name="addnews" value="Add news"></span>
    <span class="submit"><input type="submit" name="addnews" value="Preview"></span>

</form>

<script type="text/javascript">
(function()
{
    var settings =
    {
        skin: 'v2',
        width: '100%',
        height: 350,
        customConfig: '',
        language: 'en',
        entities_latin: false,
        entities_greek: false,
        toolbar: [
            ['Source','Maximize','Scayt','PasteText','Undo','Redo','Find','Replace','-','SelectAll','RemoveFormat','NumberedList','BulletedList','Outdent','Indent'],
            ['Image','Table','HorizontalRule','Smiley'],
            ['Link','Unlink','Anchor','Format','FontSize','TextColor','BGColor'],
            ['Bold','Italic','Underline','Strike','Blockquote','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],


        ],
        filebrowserBrowseUrl: '<?php echo cn_path('acp.php'); ?>?q=images&a=quick',
        filebrowserImageBrowseUrl: '<?php echo cn_path('acp.php'); ?>?q=images&a=quick',
    };

    CKEDITOR.replace( 'short_story', settings );
    CKEDITOR.replace( 'full_story',  settings );
})();
</script>

