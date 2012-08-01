CKEDITOR.editorConfig = function( config )
{
    // Define changes to default configuration here. For example:
    config.skin = 'v2';
    config.resize_enabled = false;
    config.language = 'en';

    config.enterMode = CKEDITOR.ENTER_P;
    config.forceEnterMode = false;

    //config.toolbar = 'Basic';
    CKEDITOR.config.toolbar = [
        ['Save','Styles','Format'], ['Print','Source'],
        '/',
        ['Bold','Italic','Underline','StrikeThrough','-','Undo','Redo','-','Find','Replace','-','Outdent','Indent','-','FontSize','Font'],
        '/',
        ['NumberedList','BulletedList','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
        ['Image','Table','-','Link','Smiley','TextColor','BGColor']
    ] ;

};
