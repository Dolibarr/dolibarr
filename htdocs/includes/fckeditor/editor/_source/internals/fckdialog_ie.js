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
 * File Name: fckdialog_ie.js
 * 	Dialog windows operations. (IE specific implementations)
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

FCKDialog.Show = function( dialogInfo, dialogName, pageUrl, dialogWidth, dialogHeight, parentWindow )
{
	if ( !parentWindow )
		parentWindow = window ;

	FCKFocusManager.Lock() ;
	
	var oReturn = parentWindow.showModalDialog( pageUrl, dialogInfo, "dialogWidth:" + dialogWidth + "px;dialogHeight:" + dialogHeight + "px;help:no;scroll:no;status:no") ;
	
	if ( !oReturn )
		alert( FCKLang.DialogBlocked ) ;	

	FCKFocusManager.Unlock() ;
}
