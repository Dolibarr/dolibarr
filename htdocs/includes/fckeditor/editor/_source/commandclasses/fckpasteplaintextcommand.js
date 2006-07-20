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
 * File Name: fckpasteplaintextcommand.js
 * 	FCKPastePlainTextCommand Class: represents the 
 * 	"Paste as Plain Text" command.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKPastePlainTextCommand = function() 
{
	this.Name = 'PasteText' ;
}

FCKPastePlainTextCommand.prototype.Execute = function()
{
	FCK.PasteAsPlainText() ;
}

FCKPastePlainTextCommand.prototype.GetState = function()
{
	return FCK.GetNamedCommandState( 'Paste' ) ;
}
