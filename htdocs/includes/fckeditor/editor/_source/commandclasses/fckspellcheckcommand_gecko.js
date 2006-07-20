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
 * File Name: fckspellcheckcommand_gecko.js
 * 	FCKStyleCommand Class: represents the "Spell Check" command.
 * 	(Gecko specific implementation)
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKSpellCheckCommand = function()
{
	this.Name = 'SpellCheck' ;
	this.IsEnabled = ( FCKConfig.SpellChecker == 'SpellerPages' ) ;
}

FCKSpellCheckCommand.prototype.Execute = function()
{
	FCKDialog.OpenDialog( 'FCKDialog_SpellCheck', 'Spell Check', 'dialog/fck_spellerpages.html', 440, 480 ) ;
}

FCKSpellCheckCommand.prototype.GetState = function()
{
	return this.IsEnabled ? FCK_TRISTATE_OFF : FCK_TRISTATE_DISABLED ;
}