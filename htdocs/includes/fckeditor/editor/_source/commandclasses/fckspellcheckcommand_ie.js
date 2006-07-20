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
 * File Name: fckspellcheckcommand_ie.js
 * 	FCKStyleCommand Class: represents the "Spell Check" command.
 * 	(IE specific implementation)
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKSpellCheckCommand = function()
{
	this.Name = 'SpellCheck' ;
	this.IsEnabled = ( FCKConfig.SpellChecker == 'ieSpell' || FCKConfig.SpellChecker == 'SpellerPages' ) ;
}

FCKSpellCheckCommand.prototype.Execute = function()
{
	switch ( FCKConfig.SpellChecker )
	{
		case 'ieSpell' :
			this._RunIeSpell() ;
			break ;
		
		case 'SpellerPages' :
			FCKDialog.OpenDialog( 'FCKDialog_SpellCheck', 'Spell Check', 'dialog/fck_spellerpages.html', 440, 480 ) ;
			break ;
	}
}

FCKSpellCheckCommand.prototype._RunIeSpell = function()
{
	try
	{
		var oIeSpell = new ActiveXObject( "ieSpell.ieSpellExtension" ) ;
		oIeSpell.CheckAllLinkedDocuments( FCK.EditorDocument ) ;
	}
	catch( e )
	{
		if( e.number == -2146827859 )
		{
			if ( confirm( FCKLang.IeSpellDownload ) )
				window.open( FCKConfig.IeSpellDownloadUrl , 'IeSpellDownload' ) ;
		}
		else
			alert( 'Error Loading ieSpell: ' + e.message + ' (' + e.number + ')' ) ;
	}
}

FCKSpellCheckCommand.prototype.GetState = function()
{
	return this.IsEnabled ? FCK_TRISTATE_OFF : FCK_TRISTATE_DISABLED ;
}