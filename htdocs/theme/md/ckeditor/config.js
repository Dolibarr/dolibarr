/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here.
	// http://docs.cksource.com/CKEditor_3.x/Developers_Guide
	// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html
	config.enterMode = CKEDITOR.ENTER_BR;
	config.resize_enabled = false;
	//config.resize_maxHeight = 3000;
	//config.resize_maxWidth = 3000;
	//config.height = '300px';
	//config.resize_dir = 'vertical';	// horizontal, vertical, both
	config.removePlugins = 'elementspath,save'; // config.removePlugins = 'elementspath,save,font';
	config.removeDialogTabs = 'flash:advanced';	// config.removeDialogTabs = 'flash:advanced;image:Link';
	config.protectedSource.push( /<\?[\s\S]*?\?>/g );   // Prevent PHP Code to be formatted
	//config.menu_groups = 'clipboard,table,anchor,link,image';	// for context menu 'clipboard,form,tablecell,tablecellproperties,tablerow,tablecolumn,table,anchor,link,image,flash,checkbox,radio,textfield,hiddenfield,imagebutton,button,select,textarea' 
	//config.language = 'de';
	//config.defaultLanguage = 'en';
	//config.contentsLanguage = 'fr';
	config.fullPage = false;	// Not a full html page string, just part of it
	config.dialog_backgroundCoverColor = 'rgb(255, 254, 253)';
	//config.contentsCss = '/css/mysitestyles.css';
	config.image_previewText=' ';	// Must no be empty
		
	config.toolbar_Full =
	[
	    ['Templates','NewPage'],
	    ['Save'],
	    ['Source','Maximize','Preview'],
	    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
	    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	    ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
	    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
	    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['BidiLtr', 'BidiRtl'],
	    ['Link','Unlink','Anchor'],
	    ['Image','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','Iframe'],
	    ['Styles','Format','Font','FontSize'],
	    ['TextColor','BGColor'],
	    ['Maximize', 'ShowBlocks']
	];

	// Used for mailing fields
	config.toolbar_dolibarr_mailings = 
	[
	 	['Source','Maximize','Preview'],
	 	['Cut','Copy','Paste','-','SpellChecker', 'Scayt'],
	 	['Undo','Redo','-','Find','Replace'],
	    ['Format','Font','FontSize'],
	 	['Bold','Italic','Underline','Strike','Subscript','Superscript','-','TextColor','RemoveFormat'],
	 	['NumberedList','BulletedList','Outdent','Indent','CreateDiv'],
	 	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	 	['Link','Unlink','Anchor','Image','Table','HorizontalRule','SpecialChar']
	 ];
	
	// Used for notes fields
	config.toolbar_dolibarr_notes =
	[
	 	['Source','Maximize'],
	 	['Cut','Copy','Paste','-','SpellChecker', 'Scayt'],
	 	['Undo','Redo','-','Find','Replace'],
	    ['Format','Font','FontSize'],
	 	['Bold','Italic','Underline','Strike','Subscript','Superscript','-','TextColor','RemoveFormat'],
	 	['NumberedList','BulletedList','Outdent','Indent'],
	 	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Link','Unlink','Image','Table','HorizontalRule','SpecialChar']
	];
	
	// Used for details lines
	config.toolbar_dolibarr_details =
	[
	 	['Source','Maximize'],
	 	['Cut','Copy','Paste','-','SpellChecker', 'Scayt'],
	    ['Format','Font','FontSize'],
	    ['Bold','Italic','Underline','Strike','Subscript','Superscript','-','TextColor','RemoveFormat'],
	 	['NumberedList','BulletedList','Outdent','Indent'],
	 	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Link','Unlink','SpecialChar']
	];
	
	// Used for mailing fields
	config.toolbar_dolibarr_readonly =
	[
	 	['Source','Maximize'],
	 	['Find']
	];	
};
