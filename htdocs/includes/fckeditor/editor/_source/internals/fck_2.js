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
 * File Name: fck_2.js
 * 	This is the second part of the "FCK" object creation. This is the main
 * 	object that represents an editor instance.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

// This collection is used by the browser specific implementations to tell
// wich named commands must be handled separately.
FCK.RedirectNamedCommands = new Object() ;

FCK.ExecuteNamedCommand = function( commandName, commandParameter, noRedirect )
{
	FCKUndo.SaveUndoStep() ;

	if ( !noRedirect && FCK.RedirectNamedCommands[ commandName ] != null )
		FCK.ExecuteRedirectedNamedCommand( commandName, commandParameter ) ;
	else
	{
		FCK.Focus() ;
		FCK.EditorDocument.execCommand( commandName, false, commandParameter ) ; 
		FCK.Events.FireEvent( 'OnSelectionChange' ) ;
	}
	
	FCKUndo.SaveUndoStep() ;
}

FCK.GetNamedCommandState = function( commandName )
{
	try
	{
		if ( !FCK.EditorDocument.queryCommandEnabled( commandName ) )
			return FCK_TRISTATE_DISABLED ;
		else
			return FCK.EditorDocument.queryCommandState( commandName ) ? FCK_TRISTATE_ON : FCK_TRISTATE_OFF ;
	}
	catch ( e )
	{
		return FCK_TRISTATE_OFF ;
	}
}

FCK.GetNamedCommandValue = function( commandName )
{
	var sValue = '' ;
	var eState = FCK.GetNamedCommandState( commandName ) ;
	
	if ( eState == FCK_TRISTATE_DISABLED ) 
		return null ;
	
	try
	{
		sValue = this.EditorDocument.queryCommandValue( commandName ) ;
	}
	catch(e) {}
	
	return sValue ? sValue : '' ;
}

FCK.PasteFromWord = function()
{
	FCKDialog.OpenDialog( 'FCKDialog_Paste', FCKLang.PasteFromWord, 'dialog/fck_paste.html', 400, 330, 'Word' ) ;
}

FCK.Preview = function()
{
	var iWidth	= FCKConfig.ScreenWidth * 0.8 ;
	var iHeight	= FCKConfig.ScreenHeight * 0.7 ;
	var iLeft	= ( FCKConfig.ScreenWidth - iWidth ) / 2 ;
	var oWindow = window.open( '', null, 'toolbar=yes,location=no,status=yes,menubar=yes,scrollbars=yes,resizable=yes,width=' + iWidth + ',height=' + iHeight + ',left=' + iLeft ) ;
	
	var sHTML ;
	
	if ( FCKConfig.FullPage )
	{
		if ( FCK.TempBaseTag.length > 0 )
			sHTML = FCK.GetXHTML().replace( FCKRegexLib.HeadOpener, '$&' + FCK.TempBaseTag ) ;
		else
			sHTML = FCK.GetXHTML() ;
	}
	else
	{
		sHTML = 
			FCKConfig.DocType +
			'<html dir="' + FCKConfig.ContentLangDirection + '">' +
			'<head>' +
			FCK.TempBaseTag +
			'<title>' + FCKLang.Preview + '</title>' +
			FCK._GetEditorAreaStyleTags() +
			'</head><body>' + 
			FCK.GetXHTML() + 
			'</body></html>' ;
	}
	
	oWindow.document.write( sHTML );
	oWindow.document.close();
}

FCK.SwitchEditMode = function( noUndo )
{
	var bIsWysiwyg = ( FCK.EditMode == FCK_EDITMODE_WYSIWYG ) ;
	var sHtml ;
	
	// Update the HTML in the view output to show.
	if ( bIsWysiwyg )
	{
		if ( !noUndo && FCKBrowserInfo.IsIE )
			FCKUndo.SaveUndoStep() ;

		sHtml = FCK.GetXHTML( FCKConfig.FormatSource ) ;
	}
	else
		sHtml = this.EditingArea.Textarea.value ;

	FCK.EditMode = bIsWysiwyg ? FCK_EDITMODE_SOURCE : FCK_EDITMODE_WYSIWYG ;

	FCK.SetHTML( sHtml ) ;

	if ( FCKBrowserInfo.IsGecko )
		window.onresize() ;

	// Set the Focus.
	FCK.Focus() ;

	// Update the toolbar (Running it directly causes IE to fail).
	FCKTools.RunFunction( FCK.ToolbarSet.RefreshModeState, FCK.ToolbarSet ) ;
}

FCK.CreateElement = function( tag )
{
	var e = FCK.EditorDocument.createElement( tag ) ;
	return FCK.InsertElementAndGetIt( e ) ;
}

FCK.InsertElementAndGetIt = function( e )
{
	e.setAttribute( 'FCKTempLabel', 'true' ) ;
	
	this.InsertElement( e ) ;
	
	var aEls = FCK.EditorDocument.getElementsByTagName( e.tagName ) ;
	
	for ( var i = 0 ; i < aEls.length ; i++ )
	{
		if ( aEls[i].getAttribute( 'FCKTempLabel' ) )
		{
			aEls[i].removeAttribute( 'FCKTempLabel' ) ;
			return aEls[i] ;
		}
	}
	return null ;
}
