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
 * File Name: fckdialog.js
 * 	Dialog windows operations.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKDialog = new Object() ;

// This method opens a dialog window using the standard dialog template.
FCKDialog.OpenDialog = function( dialogName, dialogTitle, dialogPage, width, height, customValue, parentWindow, resizable )
{
	// Setup the dialog info.
	var oDialogInfo = new Object() ;
	oDialogInfo.Title = dialogTitle ;
	oDialogInfo.Page = dialogPage ;
	oDialogInfo.Editor = window ;
	oDialogInfo.CustomValue = customValue ;		// Optional
	
	var sUrl = FCKConfig.BasePath + 'fckdialog.html' ;
	this.Show( oDialogInfo, dialogName, sUrl, width, height, parentWindow, resizable ) ;
}
