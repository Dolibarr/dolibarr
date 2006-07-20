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
 * File Name: fck_1_ie.js
 * 	This is the first part of the "FCK" object creation. This is the main
 * 	object that represents an editor instance.
 * 	(IE specific implementations)
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

FCK.Description = "FCKeditor for Internet Explorer 5.5+" ;

FCK._GetBehaviorsStyle = function()
{
	if ( !FCK._BehaviorsStyle )
	{
		var sBasePath = FCKConfig.FullBasePath ;
		var sStyle ;
		
		// The behaviors should be pointed using the FullBasePath to avoid security
		// errors when using a differente BaseHref.
		sStyle =
			'<style type="text/css" _fcktemp="true">' +
			'INPUT { behavior: url(' + sBasePath + 'css/behaviors/hiddenfield.htc) ; }' ;

		if ( FCKConfig.ShowBorders )
			sStyle += 'TABLE { behavior: url(' + sBasePath + 'css/behaviors/showtableborders.htc) ; }' ;

		// Disable resize handlers.
		sStyle += 'INPUT,TEXTAREA,SELECT,.FCK__Anchor,.FCK__PageBreak' ;

		if ( FCKConfig.DisableObjectResizing )
			sStyle += ',IMG,TABLE' ;

		sStyle += ' { behavior: url(' + sBasePath + 'css/behaviors/disablehandles.htc) ; }' ;

		sStyle += '</style>' ;
		FCK._BehaviorsStyle = sStyle ;
	}
	
	return FCK._BehaviorsStyle ;
}

function Doc_OnMouseUp()
{
	if ( FCK.EditorWindow.event.srcElement.tagName == 'HTML' )
	{
		FCK.Focus() ;
		FCK.EditorWindow.event.cancelBubble	= true ;
		FCK.EditorWindow.event.returnValue	= false ;
	}
}

function Doc_OnPaste()
{
	if ( FCK.Status == FCK_STATUS_COMPLETE )
		return FCK.Events.FireEvent( "OnPaste" ) ;
	else
		return false ;
}

/*
function Doc_OnContextMenu()
{
	var e = FCK.EditorWindow.event ;
	
	FCK.ShowContextMenu( e.screenX, e.screenY ) ;
	return false ;
}
*/

function Doc_OnKeyDown()
{
	var e = FCK.EditorWindow.event ;


	switch ( e.keyCode )
	{
		case 13 :	// ENTER
			if ( FCKConfig.UseBROnCarriageReturn && !(e.ctrlKey || e.altKey || e.shiftKey) )
			{
				Doc_OnKeyDownUndo() ;
				
				// We must ignore it if we are inside a List.
				if ( FCK.EditorDocument.queryCommandState( 'InsertOrderedList' ) || FCK.EditorDocument.queryCommandState( 'InsertUnorderedList' ) )
					return true ;

				// Insert the <BR> (The &nbsp; must be also inserted to make it work)
				FCK.InsertHtml( '<br>&nbsp;' ) ;

				// Remove the &nbsp;
				var oRange = FCK.EditorDocument.selection.createRange() ;
				oRange.moveStart( 'character', -1 ) ;
				oRange.select() ;
				FCK.EditorDocument.selection.clear() ;

				return false ;
			}
			break ;
		
		case 8 :	// BACKSPACE
			// We must delete a control selection by code and cancels the 
			// keystroke, otherwise IE will execute the browser's "back" button.
			if ( FCKSelection.GetType() == 'Control' )
			{
				FCKSelection.Delete() ;
				return false ;
			}
			break ;
		
		case 9 :	// TAB
			if ( FCKConfig.TabSpaces > 0 && !(e.ctrlKey || e.altKey || e.shiftKey) )
			{
				Doc_OnKeyDownUndo() ;
				
				FCK.InsertHtml( window.FCKTabHTML ) ;
				return false ;
			}
			break ;
		case 90 :	// Z
			if ( e.ctrlKey && !(e.altKey || e.shiftKey) )
			{
				FCKUndo.Undo() ;
				return false ;
			}
			break ;
		case 89 :	// Y
			if ( e.ctrlKey && !(e.altKey || e.shiftKey) )
			{
				FCKUndo.Redo() ;
				return false ;
			}
			break ;
	}
	
	if ( !( e.keyCode >=16 && e.keyCode <= 18 ) )
		Doc_OnKeyDownUndo() ;
	return true ;
}

function Doc_OnKeyDownUndo()
{
	if ( !FCKUndo.Typing )
	{
		FCKUndo.SaveUndoStep() ;
		FCKUndo.Typing = true ;
		FCK.Events.FireEvent( "OnSelectionChange" ) ;
	}
	
	FCKUndo.TypesCount++ ;

	if ( FCKUndo.TypesCount > FCKUndo.MaxTypes )
	{
		FCKUndo.TypesCount = 0 ;
		FCKUndo.SaveUndoStep() ;
	}
}

function Doc_OnDblClick()
{
	FCK.OnDoubleClick( FCK.EditorWindow.event.srcElement ) ;
	FCK.EditorWindow.event.cancelBubble = true ;
}

function Doc_OnSelectionChange()
{
	FCK.Events.FireEvent( "OnSelectionChange" ) ;
}

FCK.InitializeBehaviors = function( dontReturn )
{
	// Set the focus to the editable area when clicking in the document area.
	// TODO: The cursor must be positioned at the end.
	this.EditorDocument.attachEvent( 'onmouseup', Doc_OnMouseUp ) ;

	// Intercept pasting operations
	this.EditorDocument.body.attachEvent( 'onpaste', Doc_OnPaste ) ;

	// Reset the context menu.
	FCK.ContextMenu._InnerContextMenu.AttachToElement( FCK.EditorDocument.body ) ;

	// Build the "TAB" key replacement (if necessary).
	if ( FCKConfig.TabSpaces > 0 )
	{
		window.FCKTabHTML = '' ;
		for ( i = 0 ; i < FCKConfig.TabSpaces ; i++ )
			window.FCKTabHTML += "&nbsp;" ;
	}
	this.EditorDocument.attachEvent("onkeydown", Doc_OnKeyDown ) ;

	this.EditorDocument.attachEvent("ondblclick", Doc_OnDblClick ) ;

	// Catch cursor movements
	this.EditorDocument.attachEvent("onselectionchange", Doc_OnSelectionChange ) ;

	//Enable editing
//	this.EditorDocument.body.contentEditable = true ;
}

FCK.InsertHtml = function( html )
{
	html = FCKConfig.ProtectedSource.Protect( html ) ;
	html = FCK.ProtectUrls( html ) ;

	FCK.Focus() ;

	FCKUndo.SaveUndoStep() ;

	// Gets the actual selection.
	var oSel = FCK.EditorDocument.selection ;

	// Deletes the actual selection contents.
	if ( oSel.type.toLowerCase() == 'control' )
		oSel.clear() ;

	// Insert the HTML.
	oSel.createRange().pasteHTML( html ) ;
}

FCK.SetInnerHtml = function( html )		// IE Only
{
	var oDoc = FCK.EditorDocument ;
	// Using the following trick, any comment in the begining of the HTML will
	// be preserved.
	oDoc.body.innerHTML = '<div id="__fakeFCKRemove__">&nbsp;</div>' + html ;
	oDoc.getElementById('__fakeFCKRemove__').removeNode( true ) ;
}

var FCK_PreloadImages_Count = 0 ;
var FCK_PreloadImages_Images = new Array() ;

function FCK_PreloadImages()
{
	// Get the images to preload.
	var aImages = FCKConfig.PreloadImages || [] ;
	
	if ( typeof( aImages ) == 'string' )
		aImages = aImages.split( ';' ) ;

	// Add the skin icons strip.
	aImages.push( FCKConfig.SkinPath + 'fck_strip.gif' ) ;
	
	FCK_PreloadImages_Count = aImages.length ;

	var aImageElements = new Array() ;
	
	for ( var i = 0 ; i < aImages.length ; i++ )
	{
		var eImg = document.createElement( 'img' ) ;
		eImg.onload = eImg.onerror = FCK_PreloadImages_OnImage ;
		eImg.src = aImages[i] ;
		
		FCK_PreloadImages_Images[i] = eImg ;
	}
}

function FCK_PreloadImages_OnImage()
{
	if ( (--FCK_PreloadImages_Count) == 0 )
		FCKTools.RunFunction( LoadToolbarSetup ) ;
}

// Disable the context menu in the editor (outside the editing area).
function Document_OnContextMenu()
{
	return ( event.srcElement._FCKShowContextMenu == true ) ;
}
document.oncontextmenu = Document_OnContextMenu ;

function FCK_Cleanup()
{
	this.EditorWindow = null ;
	this.EditorDocument = null ;
}