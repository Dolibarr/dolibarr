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
 * File Name: fckeditingarea.js
 * 	FCKEditingArea Class: renders an editable area.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

/**
 * @constructor
 * @param {String} targetElement The element that will hold the editing area. Any child element present in the target will be deleted.
 */
var FCKEditingArea = function( targetElement )
{
	this.TargetElement = targetElement ;
	this.Mode = FCK_EDITMODE_WYSIWYG ;

	if ( FCK.IECleanup )
		FCK.IECleanup.AddItem( this, FCKEditingArea_Cleanup ) ;
}


/**
 * @param {String} html The complete HTML for the page, including DOCTYPE and the <html> tag.
 */
FCKEditingArea.prototype.Start = function( html )
{
	var eTargetElement	= this.TargetElement ;
	var oTargetDocument	= eTargetElement.ownerDocument ;
	
	// Remove all child nodes from the target.
	while( eTargetElement.childNodes.length > 0 )
		eTargetElement.removeChild( eTargetElement.childNodes[0] ) ;

	if ( this.Mode == FCK_EDITMODE_WYSIWYG )
	{
		if ( FCKBrowserInfo.IsGecko )
		{
			html = html.replace( /(<body[^>]*>)\s*(<\/body>)/i, '$1' + GECKO_BOGUS + '$2' ) ;
		}
	
		// Create the editing area IFRAME.
		var oIFrame = this.IFrame = oTargetDocument.createElement( 'iframe' ) ;
		oIFrame.src = 'javascript:void(0)' ;
		oIFrame.frameBorder = 0 ;
		oIFrame.width = oIFrame.height = '100%' ;
		
		// Append the new IFRAME to the target.
		eTargetElement.appendChild( oIFrame ) ;
		
		// Get the window and document objects used to interact with the newly created IFRAME.
		this.Window = oIFrame.contentWindow ;
		
		// IE: Avoid JavaScript errors thrown by the editing are source (like tags events).
		// TODO: This error handler is not being fired.
		// this.Window.onerror = function() { alert( 'Error!' ) ; return true ; }

		var oDoc = this.Document = this.Window.document ;
		
		oDoc.open() ;
		oDoc.write( html ) ;
		oDoc.close() ;

		this.Window._FCKEditingArea = this ;

		// FF 1.0.x is buggy... we must wait a lot to enable editing because
		// sometimes the content simply disappears, for example when pasting
		// "bla1!<img src='some_url'>!bla2" in the source and then switching
		// back to design.
		if ( FCKBrowserInfo.IsGecko10 )
			this.Window.setTimeout( FCKEditingArea_CompleteStart, 500 ) ;
		else
			FCKEditingArea_CompleteStart.call( this.Window ) ;
	}
	else
	{
		var eTextarea = this.Textarea = oTargetDocument.createElement( 'textarea' ) ; 
		eTextarea.className = 'SourceField' ;
		eTextarea.dir = 'ltr' ;
		eTextarea.style.width = eTextarea.style.height = '100%' ;
		eTextarea.style.border = 'none' ;
		eTargetElement.appendChild( eTextarea ) ;

		eTextarea.value = html  ;

		// Fire the "OnLoad" event.
		FCKTools.RunFunction( this.OnLoad ) ;
	}
}

// "this" here is FCKEditingArea.Window 
function FCKEditingArea_CompleteStart()
{
	// Of Firefox, the DOM takes a little to become available. So we must wait for it in a loop.
	if ( !this.document.body )
	{
		this.setTimeout( FCKEditingArea_CompleteStart, 50 ) ;
		return ;
	}
	
	var oEditorArea = this._FCKEditingArea ;
	oEditorArea.MakeEditable() ;
	
	// Fire the "OnLoad" event.
	FCKTools.RunFunction( oEditorArea.OnLoad ) ;
}

FCKEditingArea.prototype.MakeEditable = function()
{
	var oDoc = this.Document ;

	if ( FCKBrowserInfo.IsIE )
		oDoc.body.contentEditable = true ;
	else
	{
		try
		{
			oDoc.designMode = 'on' ;

			// Tell Gecko to use or not the <SPAN> tag for the bold, italic and underline.
			oDoc.execCommand( 'useCSS', false, !FCKConfig.GeckoUseSPAN ) ;

			// Analysing Firefox 1.5 source code, it seams that there is support for a 
			// "insertBrOnReturn" command. Applying it gives no error, but it doesn't 
			// gives the same behavior that you have with IE. It works only if you are
			// already inside a paragraph and it doesn't render correctly in the first enter.
			// oDoc.execCommand( 'insertBrOnReturn', false, false ) ;
			
			// Tell Gecko (Firefox 1.5+) to enable or not live resizing of objects (by Alfonso Martinez)
			oDoc.execCommand( 'enableObjectResizing', false, !FCKConfig.DisableObjectResizing ) ;
			
			// Disable the standard table editing features of Firefox.
			oDoc.execCommand( 'enableInlineTableEditing', false, !FCKConfig.DisableFFTableHandles ) ;
		}
		catch (e) {}
	}
}

FCKEditingArea.prototype.Focus = function()
{
	try
	{
		if ( this.Mode == FCK_EDITMODE_WYSIWYG )
		{
			if ( FCKBrowserInfo.IsSafari )
				this.IFrame.focus() ;
			else
				this.Window.focus() ;
		}
		else
			this.Textarea.focus() ;
	}
	catch(e) {}
}

function FCKEditingArea_Cleanup()
{
	this.TargetElement = null ;
	this.IFrame = null ;
	this.Document = null ;
	this.Textarea = null ;
	
	if ( this.Window )
	{
		this.Window._FCKEditingArea = null ;
		this.Window = null ;
	}
}