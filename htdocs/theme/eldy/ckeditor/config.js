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
	//config.forceSimpleAmpersand = true;	// When you put a <img src="x?a=a&b=b"> into the textarea, and go into "source", then ckeditor change the & into &amp;. We don't want this. But this option does not fix this.
	//config.entities = false;			// When you put a <img src="x?a=a&b=b"> into the textarea, and go into "source", then ckeditor change the & into &amp;. We don't want this. But this option does not fix this.
	//config.entities_greek = false;
	config.resize_enabled = false;
	//config.resize_maxHeight = 3000;
	//config.resize_maxWidth = 3000;
	//config.height = '300px';
	//config.resize_dir = 'vertical';	// horizontal, vertical, both
	config.removePlugins = 'elementspath,save'; // config.removePlugins = 'elementspath,save,font';
	//config.extraPlugins = 'docprops,scayt,showprotected';
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
	//config.autoParagraph = false;
	//config.removeFormatTags = 'b,big,code,del,dfn,em,font,i,ins,kbd';		// See also rules on this.dataProcessor.writer.setRules
	//config.forcePasteAsPlainText = true;

	config.toolbar_Full =
	[
	    ['Templates','NewPage'],
	    ['Save'],
	    ['Maximize','Preview'],
	    ['PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],	// 'Cut','Copy','Paste','-', are useless, can be done with right click, even on smarpthone
	    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	    ['CreateDiv','ShowBlocks'],
	    ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
	    ['Bold','Italic','Underline','Strike','Superscript'],				// 'Subscript'
	    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['BidiLtr', 'BidiRtl'],
	    ['Link','Unlink','Anchor'],
	    ['Image','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','Iframe'],
	    ['Styles','Format','Font','FontSize'],
	    ['TextColor','BGColor'],
	 	['Source']
	];

	// Used for mailing fields
	config.toolbar_dolibarr_mailings =
	[
	 	['Maximize','Preview'],
	 	['SpellChecker', 'Scayt'],
	 	['Undo','Redo','-','Find','Replace'],
	 	['CreateDiv','ShowBlocks'],
	    ['Format','Font','FontSize'],
	 	['Bold','Italic','Underline','Strike','Superscript','-','TextColor','RemoveFormat'],
	 	['NumberedList','BulletedList','Outdent','Indent'],
	 	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	 	['Link','Unlink','Anchor','Image','Table','HorizontalRule','SpecialChar'],
	 	['Source']
	 ];

	// Used for notes fields
	config.toolbar_dolibarr_notes =
	[
	 	['Maximize'],
	 	['SpellChecker', 'Scayt'],		// 'Cut','Copy','Paste','-', are useless, can be done with right click, even on smarpthone
	 	['Undo','Redo','-','Find','Replace'],
	    ['Font','FontSize'],
	 	['Bold','Italic','Underline','Strike','Superscript','-','TextColor','RemoveFormat'],
	 	['NumberedList','BulletedList','Outdent','Indent'],
	 	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Link','Unlink','Image','Table','HorizontalRule','SpecialChar'],
	 	['Source']
	];

	// Used for details lines
	config.toolbar_dolibarr_details =
	[
	 	['Maximize'],
	 	['SpellChecker', 'Scayt'],		// 'Cut','Copy','Paste','-', are useless, can be done with right click, even on smarpthone
	    ['Format','FontSize'],
	    ['Bold','Italic','Underline','Strike','-','TextColor','RemoveFormat'],	// ,'Subscript','Superscript' useless
	 	['NumberedList','BulletedList','Outdent','Indent'],
	 	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Link','Unlink','SpecialChar'],
	 	['Source']
	];

	// Used for mailing fields
	config.toolbar_dolibarr_readonly =
	[
	 	['Maximize'],
	 	['Find'],
	 	['Source']
	];
};
