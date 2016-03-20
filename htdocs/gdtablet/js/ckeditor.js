CKEDITOR.editorConfig = function( config )
{
    config.toolbar_tablet =
        [
            ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
            ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
            ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
            ['Link','Unlink'],
            ['Format','FontSize'],
            ['TextColor','BGColor']
        ];
    config.removePlugins = 'elementspath';
    config.resize_enabled = false;
};