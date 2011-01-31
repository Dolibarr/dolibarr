/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here.
	// http://docs.cksource.com/CKEditor_3.x/Developers_Guide
	
	config.toolbar_Full =
	[
	    ['Source','-','Save','NewPage','Preview','-','Templates'],
	    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
	    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	    ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
	    '/',
	    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
	    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['BidiLtr', 'BidiRtl'],
	    ['Link','Unlink','Anchor'],
	    ['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','Iframe'],
	    '/',
	    ['Styles','Format','Font','FontSize'],
	    ['TextColor','BGColor'],
	    ['Maximize', 'ShowBlocks','-','About']
	];

	config.toolbar_dolibarr_mailings = 
	[
	 	['FitWindow','Source'],
	 	['Cut','Copy','Paste','PasteText','PasteWord','-','SpellCheck','-','Preview','Print'],
	 	['Undo','Redo','-','Find','Replace','-','SelectAll'],
	 	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript','-','TextColor','BGColor','-','RemoveFormat'],
	 	['OrderedList','UnorderedList','-','Outdent','Indent'],
	 	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	 	['Link','Unlink','Anchor','Image','Table','Rule','SpecialChar'],
	 	['FontName','FontSize']
	 ];
	
	config.toolbar_dolibarr_notes =
	[
	 	['FitWindow','Source'],
	 	['Cut','Copy','Paste','PasteText','PasteWord','-','SpellCheck','-','Preview','Print'],
	 	['Undo','Redo','-','Find','Replace','-','SelectAll'],
	 	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript','-','TextColor','BGColor','-','RemoveFormat'],
	    ['OrderedList','UnorderedList','-','Outdent','Indent'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	    ['Link','Unlink','Anchor','Image','Table','Rule','SpecialChar'],
	    ['FontName','FontSize']
	];
	
	config.toolbar_dolibarr_details =
	[
	 	['FitWindow','Source'],
	 	['Cut','Copy','Paste','-','Preview'],
	    ['Undo','Redo'],
	    ['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript','-','TextColor','BGColor','-','RemoveFormat'],
	    ['OrderedList','UnorderedList','-','Outdent','Indent'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	    ['SpecialChar'],
	    ['FontName','FontSize']
	];
};
