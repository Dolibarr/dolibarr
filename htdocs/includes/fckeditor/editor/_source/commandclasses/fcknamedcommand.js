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
 * File Name: fcknamedcommand.js
 * 	FCKNamedCommand Class: represents an internal browser command.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKNamedCommand = function( commandName )
{
	this.Name = commandName ;
}

FCKNamedCommand.prototype.Execute = function()
{
	FCK.ExecuteNamedCommand( this.Name ) ;
}

FCKNamedCommand.prototype.GetState = function()
{
	return FCK.GetNamedCommandState( this.Name ) ;
}
