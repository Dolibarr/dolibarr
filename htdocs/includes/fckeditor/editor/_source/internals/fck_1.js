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
 * File Name: fck_1.js
 * 	This is the first part of the "FCK" object creation. This is the main
 * 	object that represents an editor instance.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCK_StartupValue ;

FCK.Events	= new FCKEvents( FCK ) ;
FCK.Toolbar	= null ;
FCK.HasFocus = false ;

FCK.StartEditor = function()
{
	FCK.TempBaseTag = FCKConfig.BaseHref.length > 0 ? '<base href="' + FCKConfig.BaseHref + '" _fcktemp="true"></base>' : '' ;

	FCK.EditingArea = new FCKEditingArea( document.getElementById( 'xEditingArea' ) ) ;

	// Set the editor's startup contents
	this.SetHTML( FCKTools.GetLinkedFieldValue() ) ;
}

FCK.Focus = function()
{
	FCK.EditingArea.Focus() ;
}

FCK.SetStatus = function( newStatus )
{
	this.Status = newStatus ;

	if ( newStatus == FCK_STATUS_ACTIVE )
	{
		FCKFocusManager.AddWindow( window, true ) ;
		
		if ( FCKBrowserInfo.IsIE )
			FCKFocusManager.AddWindow( window.frameElement, true ) ;

		// Force the focus in the editor.
		if ( FCKConfig.StartupFocus )
			FCK.Focus() ;
	}

	this.Events.FireEvent( 'OnStatusChange', newStatus ) ;
}

// GetHTML is Deprecated : returns the same value as GetXHTML.
FCK.GetHTML = FCK.GetXHTML = function( format )
{
	// We assume that if the user is in source editing, the editor value must
	// represent the exact contents of the source, as the user wanted it to be.
	if ( FCK.EditMode == FCK_EDITMODE_SOURCE )
			return FCK.EditingArea.Textarea.value ;

	var sXHTML ;
	
	if ( FCKConfig.FullPage )
		sXHTML = FCKXHtml.GetXHTML( this.EditorDocument.getElementsByTagName( 'html' )[0], true, format ) ;
	else
	{
		if ( FCKConfig.IgnoreEmptyParagraphValue && this.EditorDocument.body.innerHTML == '<P>&nbsp;</P>' )
			sXHTML = '' ;
		else
			sXHTML = FCKXHtml.GetXHTML( this.EditorDocument.body, false, format ) ;
	}

	if ( FCKBrowserInfo.IsIE )
		sXHTML = sXHTML.replace( FCKRegexLib.ToReplace, '$1' ) ;

	if ( FCK.DocTypeDeclaration && FCK.DocTypeDeclaration.length > 0 )
		sXHTML = FCK.DocTypeDeclaration + '\n' + sXHTML ;

	if ( FCK.XmlDeclaration && FCK.XmlDeclaration.length > 0 )
		sXHTML = FCK.XmlDeclaration + '\n' + sXHTML ;

	return FCKConfig.ProtectedSource.Revert( sXHTML ) ;
}

FCK.UpdateLinkedField = function()
{
	FCK.LinkedField.value = FCK.GetXHTML( FCKConfig.FormatOutput ) ;
	FCK.Events.FireEvent( 'OnAfterLinkedFieldUpdate' ) ;
}

FCK.RegisteredDoubleClickHandlers = new Object() ;

FCK.OnDoubleClick = function( element )
{
	var oHandler = FCK.RegisteredDoubleClickHandlers[ element.tagName ] ;
	if ( oHandler )
		oHandler( element ) ;
}

// Register objects that can handle double click operations.
FCK.RegisterDoubleClickHandler = function( handlerFunction, tag )
{
	FCK.RegisteredDoubleClickHandlers[ tag.toUpperCase() ] = handlerFunction ;
}

FCK.OnAfterSetHTML = function()
{
	FCKDocumentProcessor.Process( FCK.EditorDocument ) ;
	FCK.Events.FireEvent( 'OnAfterSetHTML' ) ;
}

// Saves URLs on links and images on special attributes, so they don't change when 
// moving around.
FCK.ProtectUrls = function( html )
{
	// <A> href
	html = html.replace( FCKRegexLib.ProtectUrlsAApo	, '$1$2$3$2 _fcksavedurl=$2$3$2' ) ;
	html = html.replace( FCKRegexLib.ProtectUrlsANoApo	, '$1$2 _fcksavedurl="$2"' ) ;

	// <IMG> src
	html = html.replace( FCKRegexLib.ProtectUrlsImgApo	, '$1$2$3$2 _fcksavedurl=$2$3$2' ) ;
	html = html.replace( FCKRegexLib.ProtectUrlsImgNoApo, '$1$2 _fcksavedurl="$2"' ) ;
	
	return html ;
}

FCK.IsDirty = function()
{
	return ( FCK_StartupValue != FCK.EditorDocument.body.innerHTML ) ;
}

FCK.ResetIsDirty = function()
{
	if ( FCK.EditorDocument.body )
		FCK_StartupValue = FCK.EditorDocument.body.innerHTML ;
}

FCK.SetHTML = function( html )
{
	this.EditingArea.Mode = FCK.EditMode ;

	if ( FCK.EditMode == FCK_EDITMODE_WYSIWYG )
	{
		// Firefox can't handle correctly the editing of the STRONG and EM tags. 
		// We must replace them with B and I.
		if ( FCKBrowserInfo.IsGecko )
		{
			html = html.replace( FCKRegexLib.StrongOpener, '<b$1' ) ;
			html = html.replace( FCKRegexLib.StrongCloser, '<\/b>' ) ;
			html = html.replace( FCKRegexLib.EmOpener, '<i$1' ) ;
			html = html.replace( FCKRegexLib.EmCloser, '<\/i>' ) ;
		}
	
		html = FCKConfig.ProtectedSource.Protect( html ) ;
		html = FCK.ProtectUrls( html ) ;

		var sHtml ;

		if ( FCKConfig.FullPage )
		{
			var sHtml ;

			if ( FCKBrowserInfo.IsIE )
				sHtml = FCK._GetBehaviorsStyle() ;
			else if ( FCKConfig.ShowBorders ) 
				sHtml = '<link href="' + FCKConfig.FullBasePath + 'css/fck_showtableborders_gecko.css" rel="stylesheet" type="text/css" _fcktemp="true" />' ;

			sHtml += '<link href="' + FCKConfig.FullBasePath + 'css/fck_internal.css' + '" rel="stylesheet" type="text/css" _fcktemp="true" />' ;

			sHtml = html.replace( FCKRegexLib.HeadCloser, sHtml + '$&' ) ;

			// Insert the base tag (FCKConfig.BaseHref), if not exists in the source.
			if ( FCK.TempBaseTag.length > 0 && !FCKRegexLib.HasBaseTag.test( html ) )
				sHtml = sHtml.replace( FCKRegexLib.HeadOpener, '$&' + FCK.TempBaseTag ) ;
		}
		else
		{
			sHtml =
				FCKConfig.DocType +
				'<html dir="' + FCKConfig.ContentLangDirection + '"' ;
			
			// On IE, if you are use a DOCTYPE differenft of HTML 4 (like
			// XHTML), you must force the vertical scroll to show, otherwise
			// the horizontal one may appear when the page needs vertical scrolling.
			if ( FCKBrowserInfo.IsIE && !FCKRegexLib.Html4DocType.test( FCKConfig.DocType ) )
				sHtml += ' style="overflow-y: scroll"' ;
			
			sHtml +=
				'><head><title></title>' +
				this._GetEditorAreaStyleTags() +
				'<link href="css/fck_internal.css' + '" rel="stylesheet" type="text/css" _fcktemp="true" />' ;

			if ( FCKBrowserInfo.IsIE )
				sHtml += FCK._GetBehaviorsStyle() ;
			else if ( FCKConfig.ShowBorders ) 
				sHtml += '<link href="css/fck_showtableborders_gecko.css" rel="stylesheet" type="text/css" _fcktemp="true" />' ;

			sHtml += FCK.TempBaseTag ;
			sHtml += '</head><body>' ;
			
			if ( FCKBrowserInfo.IsGecko && ( html.length == 0 || FCKRegexLib.EmptyParagraph.test( html ) ) )
				sHtml += GECKO_BOGUS ;
			else
				sHtml += html ;
			
			sHtml += '</body></html>' ;
		}

		this.EditingArea.OnLoad = FCK_EditingArea_OnLoad ;
		this.EditingArea.Start( sHtml ) ;
	}
	else
	{
		this.EditingArea.OnLoad = null ;
		this.EditingArea.Start( html ) ;
		
		// Enables the context menu in the textarea.
		this.EditingArea.Textarea._FCKShowContextMenu = true ;
	}
}

function FCK_EditingArea_OnLoad()
{
	// Get the editor's window and document (DOM)
	FCK.EditorWindow	= FCK.EditingArea.Window ;
	FCK.EditorDocument	= FCK.EditingArea.Document ;

	FCK.InitializeBehaviors() ;

	FCK.OnAfterSetHTML() ;

	// Check if it is not a startup call, otherwise complete the startup.
	if ( FCK.Status != FCK_STATUS_NOTLOADED )
		return ;

	// Save the startup value for the "IsDirty()" check.
	FCK.ResetIsDirty() ;

	// Attach the editor to the form onsubmit event
	FCKTools.AttachToLinkedFieldFormSubmit( FCK.UpdateLinkedField ) ;

	FCKUndo.SaveUndoStep() ;

	FCK.SetStatus( FCK_STATUS_ACTIVE ) ;
}

FCK._GetEditorAreaStyleTags = function()
{
	var sTags = '' ;
	var aCSSs = FCKConfig.EditorAreaCSS ;
	
	for ( var i = 0 ; i < aCSSs.length ; i++ )
		sTags += '<link href="' + aCSSs[i] + '" rel="stylesheet" type="text/css" />' ;
	
	return sTags ;
}

// # Focus Manager: Manages the focus in the editor.
var FCKFocusManager = FCK.FocusManager = new Object() ;
FCKFocusManager.IsLocked = false ;
FCK.HasFocus = false ;

FCKFocusManager.AddWindow = function( win, sendToEditingArea )
{
	var oTarget ;
	
	if ( FCKBrowserInfo.IsIE )
		oTarget = win.nodeType == 1 ? win : win.frameElement ? win.frameElement : win.document ;
	else
		oTarget = win.document ;
	
	FCKTools.AddEventListener( oTarget, 'blur', FCKFocusManager_Win_OnBlur ) ;
	FCKTools.AddEventListener( oTarget, 'focus', sendToEditingArea ? FCKFocusManager_Win_OnFocus_Area : FCKFocusManager_Win_OnFocus ) ;
}

FCKFocusManager.RemoveWindow = function( win )
{
	if ( FCKBrowserInfo.IsIE )
		oTarget = win.nodeType == 1 ? win : win.frameElement ? win.frameElement : win.document ;
	else
		oTarget = win.document ;

	FCKTools.RemoveEventListener( oTarget, 'blur', FCKFocusManager_Win_OnBlur ) ;
	FCKTools.RemoveEventListener( oTarget, 'focus', FCKFocusManager_Win_OnFocus_Area ) ;
	FCKTools.RemoveEventListener( oTarget, 'focus', FCKFocusManager_Win_OnFocus ) ;
}

FCKFocusManager.Lock = function()
{
	this.IsLocked = true ;
}

FCKFocusManager.Unlock = function()
{
	if ( this._HasPendingBlur )
		FCKFocusManager._Timer = window.setTimeout( FCKFocusManager_FireOnBlur, 100 ) ;
		
	this.IsLocked = false ;
}

FCKFocusManager._ResetTimer = function()
{
	this._HasPendingBlur = false ;

	if ( this._Timer )
	{
		window.clearTimeout( this._Timer ) ;
		delete this._Timer ; 
	}
}

function FCKFocusManager_Win_OnBlur()
{
	if ( FCK && FCK.HasFocus )
	{
		FCKFocusManager._ResetTimer() ;
		FCKFocusManager._Timer = window.setTimeout( FCKFocusManager_FireOnBlur, 100 ) ;
	}
}

function FCKFocusManager_FireOnBlur()
{
	if ( FCKFocusManager.IsLocked )
		FCKFocusManager._HasPendingBlur = true ;
	else
	{
		FCK.HasFocus = false ;
		FCK.Events.FireEvent( "OnBlur" ) ;
	}
}

function FCKFocusManager_Win_OnFocus_Area()
{
	FCKFocusManager_Win_OnFocus() ;
	FCK.Focus() ;
}

function FCKFocusManager_Win_OnFocus()
{
	FCKFocusManager._ResetTimer() ;

	if ( !FCK.HasFocus && !FCKFocusManager.IsLocked )
	{
		FCK.HasFocus = true ;
		FCK.Events.FireEvent( "OnFocus" ) ;
	}
}