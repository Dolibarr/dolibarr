/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2006 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * "Support Open Source software. What about a donation today?"
 * 
 * File Name: fckcommands.js
 * 	Define all commands available in the editor.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKCommands = FCK.Commands = new Object() ;
FCKCommands.LoadedCommands = new Object() ;

FCKCommands.RegisterCommand = function( commandName, command )
{
	this.LoadedCommands[ commandName ] = command ;
}

FCKCommands.GetCommand = function( commandName )
{
	var oCommand = FCKCommands.LoadedCommands[ commandName ] ;
	
	if ( oCommand )
		return oCommand ;

	switch ( commandName )
	{
		case 'DocProps'		: oCommand = new FCKDialogCommand( 'DocProps'	, FCKLang.DocProps				, 'dialog/fck_docprops.html'	, 400, 390, FCKCommands.GetFullPageState ) ; break ;
		case 'Templates'	: oCommand = new FCKDialogCommand( 'Templates'	, FCKLang.DlgTemplatesTitle		, 'dialog/fck_template.html'	, 380, 450 ) ; break ;
		case 'Link'			: oCommand = new FCKDialogCommand( 'Link'		, FCKLang.DlgLnkWindowTitle		, 'dialog/fck_link.html'		, 400, 330, FCK.GetNamedCommandState, 'CreateLink' ) ; break ;
		case 'Unlink'		: oCommand = new FCKUnlinkCommand() ; break ;
		case 'Anchor'		: oCommand = new FCKDialogCommand( 'Anchor'		, FCKLang.DlgAnchorTitle		, 'dialog/fck_anchor.html'		, 370, 170 ) ; break ;
		case 'BulletedList'	: oCommand = new FCKDialogCommand( 'BulletedList', FCKLang.BulletedListProp		, 'dialog/fck_listprop.html'	, 370, 170 ) ; break ;
		case 'NumberedList'	: oCommand = new FCKDialogCommand( 'NumberedList', FCKLang.NumberedListProp		, 'dialog/fck_listprop.html'	, 370, 170 ) ; break ;
		case 'About'		: oCommand = new FCKDialogCommand( 'About'		, FCKLang.About					, 'dialog/fck_about.html'		, 400, 330 ) ; break ;

		case 'Find'			: oCommand = new FCKDialogCommand( 'Find'		, FCKLang.DlgFindTitle			, 'dialog/fck_find.html'		, 340, 170 ) ; break ;
		case 'Replace'		: oCommand = new FCKDialogCommand( 'Replace'	, FCKLang.DlgReplaceTitle		, 'dialog/fck_replace.html'		, 340, 200 ) ; break ;

		case 'Image'		: oCommand = new FCKDialogCommand( 'Image'		, FCKLang.DlgImgTitle			, 'dialog/fck_image.html'		, 450, 400 ) ; break ;
		case 'Flash'		: oCommand = new FCKDialogCommand( 'Flash'		, FCKLang.DlgFlashTitle			, 'dialog/fck_flash.html'		, 450, 400 ) ; break ;
		case 'SpecialChar'	: oCommand = new FCKDialogCommand( 'SpecialChar', FCKLang.DlgSpecialCharTitle	, 'dialog/fck_specialchar.html'	, 400, 320 ) ; break ;
		case 'Smiley'		: oCommand = new FCKDialogCommand( 'Smiley'		, FCKLang.DlgSmileyTitle		, 'dialog/fck_smiley.html'		, FCKConfig.SmileyWindowWidth, FCKConfig.SmileyWindowHeight ) ; break ;
		case 'Table'		: oCommand = new FCKDialogCommand( 'Table'		, FCKLang.DlgTableTitle			, 'dialog/fck_table.html'		, 450, 250 ) ; break ;
		case 'TableProp'	: oCommand = new FCKDialogCommand( 'Table'		, FCKLang.DlgTableTitle			, 'dialog/fck_table.html?Parent', 400, 250 ) ; break ;
		case 'TableCellProp': oCommand = new FCKDialogCommand( 'TableCell'	, FCKLang.DlgCellTitle			, 'dialog/fck_tablecell.html'	, 500, 250 ) ; break ;
		case 'UniversalKey'	: oCommand = new FCKDialogCommand( 'UniversalKey', FCKLang.UniversalKeyboard	, 'dialog/fck_universalkey.html', 415, 300 ) ; break ;

		case 'Style'		: oCommand = new FCKStyleCommand() ; break ;

		case 'FontName'		: oCommand = new FCKFontNameCommand() ; break ;
		case 'FontSize'		: oCommand = new FCKFontSizeCommand() ; break ;
		case 'FontFormat'	: oCommand = new FCKFormatBlockCommand() ; break ;

		case 'Source'		: oCommand = new FCKSourceCommand() ; break ;
		case 'Preview'		: oCommand = new FCKPreviewCommand() ; break ;
		case 'Save'			: oCommand = new FCKSaveCommand() ; break ;
		case 'NewPage'		: oCommand = new FCKNewPageCommand() ; break ;
		case 'PageBreak'	: oCommand = new FCKPageBreakCommand() ; break ;

		case 'TextColor'	: oCommand = new FCKTextColorCommand('ForeColor') ; break ;
		case 'BGColor'		: oCommand = new FCKTextColorCommand('BackColor') ; break ;

		case 'PasteText'	: oCommand = new FCKPastePlainTextCommand() ; break ;
		case 'PasteWord'	: oCommand = new FCKPasteWordCommand() ; break ;

		case 'TableInsertRow'		: oCommand = new FCKTableCommand('TableInsertRow') ; break ;
		case 'TableDeleteRows'		: oCommand = new FCKTableCommand('TableDeleteRows') ; break ;
		case 'TableInsertColumn'	: oCommand = new FCKTableCommand('TableInsertColumn') ; break ;
		case 'TableDeleteColumns'	: oCommand = new FCKTableCommand('TableDeleteColumns') ; break ;
		case 'TableInsertCell'		: oCommand = new FCKTableCommand('TableInsertCell') ; break ;
		case 'TableDeleteCells'		: oCommand = new FCKTableCommand('TableDeleteCells') ; break ;
		case 'TableMergeCells'		: oCommand = new FCKTableCommand('TableMergeCells') ; break ;
		case 'TableSplitCell'		: oCommand = new FCKTableCommand('TableSplitCell') ; break ;
		case 'TableDelete'			: oCommand = new FCKTableCommand('TableDelete') ; break ;

		case 'Form'			: oCommand = new FCKDialogCommand( 'Form'		, FCKLang.Form			, 'dialog/fck_form.html'		, 380, 230 ) ; break ;
		case 'Checkbox'		: oCommand = new FCKDialogCommand( 'Checkbox'	, FCKLang.Checkbox		, 'dialog/fck_checkbox.html'	, 380, 230 ) ; break ;
		case 'Radio'		: oCommand = new FCKDialogCommand( 'Radio'		, FCKLang.RadioButton	, 'dialog/fck_radiobutton.html'	, 380, 230 ) ; break ;
		case 'TextField'	: oCommand = new FCKDialogCommand( 'TextField'	, FCKLang.TextField		, 'dialog/fck_textfield.html'	, 380, 230 ) ; break ;
		case 'Textarea'		: oCommand = new FCKDialogCommand( 'Textarea'	, FCKLang.Textarea		, 'dialog/fck_textarea.html'	, 380, 230 ) ; break ;
		case 'HiddenField'	: oCommand = new FCKDialogCommand( 'HiddenField', FCKLang.HiddenField	, 'dialog/fck_hiddenfield.html'	, 380, 230 ) ; break ;
		case 'Button'		: oCommand = new FCKDialogCommand( 'Button'		, FCKLang.Button		, 'dialog/fck_button.html'		, 380, 230 ) ; break ;
		case 'Select'		: oCommand = new FCKDialogCommand( 'Select'		, FCKLang.SelectionField, 'dialog/fck_select.html'		, 400, 380 ) ; break ;
		case 'ImageButton'	: oCommand = new FCKDialogCommand( 'ImageButton', FCKLang.ImageButton	, 'dialog/fck_image.html?ImageButton', 450, 400 ) ; break ;

		case 'SpellCheck'	: oCommand = new FCKSpellCheckCommand() ; break ;
		case 'FitWindow'	: oCommand = new FCKFitWindow() ; break ;

		case 'Undo'	: oCommand = new FCKUndoCommand() ; break ;
		case 'Redo'	: oCommand = new FCKRedoCommand() ; break ;

		// Generic Undefined command (usually used when a command is under development).
		case 'Undefined'	: oCommand = new FCKUndefinedCommand() ; break ;
		
		// By default we assume that it is a named command.
		default:
			if ( FCKRegexLib.NamedCommands.test( commandName ) )
				oCommand = new FCKNamedCommand( commandName ) ;
			else
			{
				alert( FCKLang.UnknownCommand.replace( /%1/g, commandName ) ) ;
				return null ;
			}
	}
	
	FCKCommands.LoadedCommands[ commandName ] = oCommand ;
	
	return oCommand ;
}

// Gets the state of the "Document Properties" button. It must be enabled only
// when "Full Page" editing is available.
FCKCommands.GetFullPageState = function()
{
	return FCKConfig.FullPage ? FCK_TRISTATE_OFF : FCK_TRISTATE_DISABLED ;
}
