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
 * File Name: fcktablecommand.js
 * 	FCKPastePlainTextCommand Class: represents the 
 * 	"Paste as Plain Text" command.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKTableCommand = function( command )
{
	this.Name = command ;
}

FCKTableCommand.prototype.Execute = function()
{
	FCKUndo.SaveUndoStep() ;
	
	switch ( this.Name )
	{
		case 'TableInsertRow' :
			FCKTableHandler.InsertRow() ;
			break ;
		case 'TableDeleteRows' :
			FCKTableHandler.DeleteRows() ;
			break ;
		case 'TableInsertColumn' :
			FCKTableHandler.InsertColumn() ;
			break ;
		case 'TableDeleteColumns' :
			FCKTableHandler.DeleteColumns() ;
			break ;
		case 'TableInsertCell' :
			FCKTableHandler.InsertCell() ;
			break ;
		case 'TableDeleteCells' :
			FCKTableHandler.DeleteCells() ;
			break ;
		case 'TableMergeCells' :
			FCKTableHandler.MergeCells() ;
			break ;
		case 'TableSplitCell' :
			FCKTableHandler.SplitCell() ;
			break ;
		case 'TableDelete' :
			FCKTableHandler.DeleteTable() ;
			break ;
		default :
			alert( FCKLang.UnknownCommand.replace( /%1/g, this.Name ) ) ;
	}
}

FCKTableCommand.prototype.GetState = function()
{
	return FCK_TRISTATE_OFF ;
}