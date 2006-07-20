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
 * File Name: fckpastewordcommand.js
 * 	FCKPasteWordCommand Class: represents the "Paste from Word" command.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKPasteWordCommand = function() 
{
	this.Name = 'PasteWord' ;
}

FCKPasteWordCommand.prototype.Execute = function()
{
	FCK.PasteFromWord() ;
}

FCKPasteWordCommand.prototype.GetState = function()
{
	if ( FCKConfig.ForcePasteAsPlainText )
		return FCK_TRISTATE_DISABLED ;
	else
		return FCK.GetNamedCommandState( 'Paste' ) ;
}
