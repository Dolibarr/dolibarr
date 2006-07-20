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
 * File Name: fck_2_gecko.js
 * 	This is the second part of the "FCK" object creation. This is the main
 * 	object that represents an editor instance.
 * 	(Gecko specific implementations)
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

// GetNamedCommandState overload for Gecko.
FCK._BaseGetNamedCommandState = FCK.GetNamedCommandState ;
FCK.GetNamedCommandState = function( commandName )
{
	switch ( commandName )
	{
		case 'Unlink' :
			return FCKSelection.HasAncestorNode('A') ? FCK_TRISTATE_OFF : FCK_TRISTATE_DISABLED ;
		default :
			return FCK._BaseGetNamedCommandState( commandName ) ;
	}
}

// Named commands to be handled by this browsers specific implementation.
FCK.RedirectNamedCommands = 
{
	Print	: true,
	Paste	: true,
	Cut		: true,
	Copy	: true
	// START iCM MODIFICATIONS
	// Include list functions so we can ensure content is wrapped
	// with P tags if not using BRs on carriage return, etc
	/*
	InsertOrderedList	: true,
	InsertUnorderedList	: true
	*/
	// END iCM MODIFICATIONS
}

// ExecuteNamedCommand overload for Gecko.
FCK.ExecuteRedirectedNamedCommand = function( commandName, commandParameter )
{
	switch ( commandName )
	{
		case 'Print' :
			FCK.EditorWindow.print() ;
			break ;
		case 'Paste' :
			try			{ if ( FCK.Paste() ) FCK.ExecuteNamedCommand( 'Paste', null, true ) ; }
			catch (e)	{ alert(FCKLang.PasteErrorPaste) ; }
			break ;
		case 'Cut' :
			try			{ FCK.ExecuteNamedCommand( 'Cut', null, true ) ; }
			catch (e)	{ alert(FCKLang.PasteErrorCut) ; }
			break ;
		case 'Copy' :
			try			{ FCK.ExecuteNamedCommand( 'Copy', null, true ) ; }
			catch (e)	{ alert(FCKLang.PasteErrorCopy) ; }
			break ;
			
		// START iCM MODIFICATIONS
		/*
		case 'InsertOrderedList'   :
		case 'InsertUnorderedList' :
		
			if ( !FCKConfig.UseBROnCarriageReturn && FCK.EditorDocument.queryCommandState( commandName ) )
			{
				// We're in a list item which is in the same type of list as the toolbar button clicked
				// Therefore, move the selected list item out of the list as is done on an ENTER key within
				// an empty list item.
				var oSel = FCK.EditorWindow.getSelection() ;
				var oSelNode = oSel.focusNode ;
				var oLINode = FCKTools.GetElementAscensor( oSelNode, "LI" ) ;
				FCK.ToggleListItem( oLINode, oSelNode ) ;
			}
			else
			{
				// Let the default handler do its stuff
				FCK.Focus() ;
				FCK.EditorDocument.execCommand( commandName, false, commandParameter ) ; 
			}
			
			FCK.Events.FireEvent( 'OnSelectionChange' ) ;
			break ;
		*/
		// END iCM MODIFICATIONS
			
		default :
			FCK.ExecuteNamedCommand( commandName, commandParameter ) ;
	}
}

FCK.AttachToOnSelectionChange = function( functionPointer )
{
	this.Events.AttachEvent( 'OnSelectionChange', functionPointer ) ;
}

FCK.Paste = function()
{
	if ( FCKConfig.ForcePasteAsPlainText )
	{
		FCK.PasteAsPlainText() ;	
		return false ;
	}
/* For now, the AutoDetectPasteFromWord feature is IE only.
	else if ( FCKConfig.AutoDetectPasteFromWord )
	{
		var sHTML = FCK.GetClipboardHTML() ;
		var re = /<\w[^>]* class="?MsoNormal"?/gi ;
		if ( re.test( sHTML ) )
		{
			if ( confirm( FCKLang["PasteWordConfirm"] ) )
			{
				FCK.PasteFromWord() ;
				return false ;
			}
		}
	}
*/
	else
		return true ;
}

//**
// FCK.InsertHtml: Inserts HTML at the current cursor location. Deletes the
// selected content if any.
FCK.InsertHtml = function( html )
{
	html = FCKConfig.ProtectedSource.Protect( html ) ;
	html = FCK.ProtectUrls( html ) ;

	// Delete the actual selection.
	var oSel = FCKSelection.Delete() ;
	
	// Get the first available range.
	var oRange = oSel.getRangeAt(0) ;
	
	// Create a fragment with the input HTML.
	var oFragment = oRange.createContextualFragment( html ) ;
	
	// Get the last available node.
	var oLastNode = oFragment.lastChild ;

	// Insert the fragment in the range.
	oRange.insertNode(oFragment) ;
	
	// Set the cursor after the inserted fragment.
	FCKSelection.SelectNode( oLastNode ) ;
	FCKSelection.Collapse( false ) ;
	
	this.Focus() ;
}

FCK.InsertElement = function( element )
{
	// Deletes the actual selection.
	var oSel = FCKSelection.Delete() ;
	
	// Gets the first available range.
	var oRange = oSel.getRangeAt(0) ;
	
	// Inserts the element in the range.
	oRange.insertNode( element ) ;
	
	// Set the cursor after the inserted fragment.
	FCKSelection.SelectNode( element ) ;
	FCKSelection.Collapse( false ) ;

	this.Focus() ;
}

FCK.PasteAsPlainText = function()
{
	// TODO: Implement the "Paste as Plain Text" code.
	
	FCKDialog.OpenDialog( 'FCKDialog_Paste', FCKLang.PasteAsText, 'dialog/fck_paste.html', 400, 330, 'PlainText' ) ;
	
/*
	var sText = FCKTools.HTMLEncode( clipboardData.getData("Text") ) ;
	sText = sText.replace( /\n/g, '<BR>' ) ;
	this.InsertHtml( sText ) ;	
*/
}
/*
FCK.PasteFromWord = function()
{
	// TODO: Implement the "Paste as Plain Text" code.
	
	FCKDialog.OpenDialog( 'FCKDialog_Paste', FCKLang.PasteFromWord, 'dialog/fck_paste.html', 400, 330, 'Word' ) ;

//	FCK.CleanAndPaste( FCK.GetClipboardHTML() ) ;
}
*/
FCK.GetClipboardHTML = function()
{
	return '' ;
}

FCK.CreateLink = function( url )
{	
	FCK.ExecuteNamedCommand( 'Unlink' ) ;
	
	if ( url.length > 0 )
	{
		// Generate a temporary name for the link.
		var sTempUrl = 'javascript:void(0);/*' + ( new Date().getTime() ) + '*/' ;
		
		// Use the internal "CreateLink" command to create the link.
		FCK.ExecuteNamedCommand( 'CreateLink', sTempUrl ) ;

		// Retrieve the just created link using XPath.
		var oLink = document.evaluate("//a[@href='" + sTempUrl + "']", this.EditorDocument.body, null, 9, null).singleNodeValue ;
		
		if ( oLink )
		{
			oLink.href = url ;
			return oLink ;
		}
	}
}

// START iCM Modifications
/*
// Ensure that behaviour of the ENTER key or the list toolbar button works correctly for a list item.
// ENTER in empty list item at top of list should result in the empty list item being
// removed and selection being moved out of the list into a P tag above it.
// ENTER in empty list item at bottom of list should result in the empty list item being
// removed and selection being moved out of the list into a P tag below it.
// ENTER in empty list item in middle of the list should result in the list being split
// into two and the selection being moved into a P tag between the two resulting lists.
// Clicking the list toolbar button in a list item at top of list should result in the list item's contents being
// moved out of the list into a P tag above it.
// Clicking the list toolbar button in a list item at the bottom of list should result in the list item's contents being
// moved out of the list into a P tag below it.
// Clicking the list toolbar button in a list item in the middle of the list should result in the list being split
// into two and the list item's contents being moved into a P tag between the two resulting lists.
FCK.ToggleListItem = function( oLINode, oSelNode )
{
	var oListNode = FCKTools.GetElementAscensor( oLINode, "UL,OL" ) ;
	var oRange = FCK.EditorDocument.createRange() ;	

	// Create a new block element
	var oBlockNode = FCK.EditorDocument.createElement( "P" ) ;
	oBlockNode.innerHTML = oLINode.innerHTML ; // Transfer any list item contents
	if ( FCKTools.NodeIsEmpty( oBlockNode ) )
		oBlockNode.innerHTML = GECKO_BOGUS ; 			// Ensure it has some content, required for Gecko
	if ( oLINode.className && oLINode.className != '' )
		oBlockNode.className = oLINode.className ; 	// Transfer across any class attribute
	
	var oCursorNode = oBlockNode ;

	// Then, perform list processing and locate the point at which the new P tag is to be inserted
	if ( oListNode.childNodes[0] == oLINode )
	{
		// First LI was empty so want to leave list and insert P above it
		oListNode.removeChild( oLINode );
		// Need to insert a new P tag (or other suitable block element) just before the list
		oRange.setStartBefore( oListNode ) ;
		oRange.setEndBefore( oListNode ) ;
	}
	else if ( oListNode.childNodes[oListNode.childNodes.length-1] == oLINode )
	{
		// Last LI was empty so want to leave list and insert new block element in the parent
		oListNode.removeChild( oLINode );
		// Need to insert a new P tag (or other suitable block element) just after the list
		oRange.setEndAfter( oListNode ) ;
		oRange.setStartAfter( oListNode ) ;
	}
	else
	{
		// A middle LI was empty so want to break list into two and insert the new block/text node in between them
		oListNode = FCKTools.SplitNode( oListNode, oSelNode, 0 ) ;				
		oListNode.removeChild( oListNode.childNodes[0] ) ;
		oRange.setStartBefore( oListNode ) ;
		oRange.setEndBefore( oListNode ) ;
	}

	// Insert new block/text node
	oRange.insertNode( oBlockNode ) ;
	
	// Ensure that we don't leave empty UL/OL tags behind
	if ( oListNode.childNodes.length == 0 ) 
		oListNode.parentNode.removeChild( oListNode ) ;
	
	// Reset cursor position to start of the new P tag's contents ready for typing
	FCK.Selection.SetCursorPosition( oCursorNode ) ;
}

FCK.ListItemEnter = function( oLINode, oSelNode, nSelOffset )
{
	// Ensure that behaviour of ENTER key within an empty list element works correctly.
	// ENTER in empty list item at top of list should result in the empty list item being
	// removed and selection being moved out of the list into a P tag above it.
	// ENTER in empty list item at bottom of list should result in the empty list item being
	// removed and selection being moved out of the list into a P tag below it.
	// ENTER in empty list item in middle of the list should result in the list being split
	// into two and the selection being moved into a P tag between the two resulting lists.
	if ( FCKTools.NodeIsEmpty( oLINode ) )
	{
		FCK.ToggleListItem( oLINode, oSelNode ) ;
		return false ; // Job done, perform no default handling
	}
	
	return true ; // If non-empty list item, let default handler do its stuff
}

FCK.ListItemBackSpace = function( oSelNode, nSelOffset )
{
	// Ensure that behaviour of BACKSPACE key within an empty list element works correctly.
	// BACKSPACE in empty list item at top of list should result in the empty list item being
	// removed and selection being moved out of the list into a P tag above it.
	// Allow the default handler to do its stuff for backspace in other list elements.
	var oListNode = oSelNode.parentNode ;
	
	if ( FCKTools.NodeIsEmpty( oSelNode ) && ( oListNode.childNodes[0] == oSelNode ) )
	{
		var oRange = FCK.EditorDocument.createRange() ;	
	
		// Create a new P element
		var oBlockNode = FCK.EditorDocument.createElement( "P" ) ;
		if ( FCKTools.NodeIsEmpty( oBlockNode ) ) 
			oBlockNode.innerHTML = GECKO_BOGUS ; 			// Ensure it has some content, required for Gecko
	
		// First LI was empty so want to leave list and insert P above it
		oListNode.removeChild( oSelNode );
		oRange.setStartBefore( oListNode ) ;
		oRange.setEndBefore( oListNode ) ;

		// Insert new P tag
		oRange.insertNode( oBlockNode ) ;
		
		// Ensure that we don't leave empty UL/OL tags behind
		if ( oListNode.childNodes.length == 0 ) 
			oListNode.parentNode.removeChild( oListNode ) ;
		
		// Reset cursor position to start of the new P tag's contents ready for typing
		FCK.Selection.SetCursorPosition( oBlockNode ) ;
		
		return false ; // Job done, perform no default handling
	}
	
	return true ; // Let default handler do its stuff if not backspacing in an empty first LI
}

FCK.Enter = function()
{
	// Remove any selected content before we begin so we end up with a single selection point
	FCK.Selection.Delete() ;
	
	// Determine the current cursor (selection) point, the node it's within and the offset
	// position of the cursor within that node
	var oSel = FCK.EditorWindow.getSelection() ;
	var nSelOffset = oSel.focusOffset;
	var oSelNode = oSel.focusNode ;

	// Guard against a null focus node.
	if ( !oSelNode )
		return false ;
	
	var oLINode = FCKTools.GetElementAscensor( oSelNode, "LI" ) ;
	
	if ( oLINode ) // An LI element is selected
	{
		// Handle list items separately as need to handle termination of the list, etc
		return FCK.ListItemEnter( oLINode, oSelNode, nSelOffset ) ;
	}
	else if ( oSelNode.nodeType == 3 ) // A TEXT node is selected
	{
		// Split it at the selection point and ensure both halves have a suitable enclosing block element
		var oParentBlockNode = FCKTools.GetParentBlockNode( oSelNode ) ;
		var oBlockNode2 = FCKTools.SplitNode( oParentBlockNode ? oParentBlockNode : FCK.EditorDocument.body, oSelNode, nSelOffset ) ;
			
		FCK.Selection.SetCursorPosition( oBlockNode2 );		
		
		return false ;
	} 
	else // An ELEMENT node is selected
	{
		// Cater for ENTER being pressed after very last element in the editor e.g. pressing ENTER after table element at very end of the editor's content
		if ( nSelOffset >= oSelNode.childNodes.length )	
		{
			var oBlockNode = FCK.EditorDocument.createElement( "P" ) ;
			if ( FCKTools.NodeIsEmpty( oBlockNode ) )
				oBlockNode.innerHTML = GECKO_BOGUS ;		// Ensure it has some content, required for Gecko			
			oSelNode.appendChild( oBlockNode ) ;
			FCK.Selection.SetCursorPosition( oBlockNode ) ;
			return false ;
		}
		
		var oBlockNode2 = FCKTools.SplitNode( oSelNode, oSelNode.childNodes[nSelOffset] ) ;
			
		FCK.Selection.SetCursorPosition( oBlockNode2 );		
		
		return false ;
	}
	
	return true ;
}

FCK.BackSpace = function()
{
	var oSel = FCK.EditorWindow.getSelection() ;
	var oSelNode = oSel.focusNode ;
	var nSelOffset = oSel.focusOffset;
	var oParentNode = null ;

	// Guard against a null focus node.
	if ( !oSelNode )
		return false ;
	
	if ( oSelNode.nodeName.toUpperCase() == "LI" ) // An LI element is selected
	{
		// Handle list items separately as need to handle termination of the list, etc
		return FCK.ListItemBackSpace( oSelNode, nSelOffset ) ;
	}
	else	
	{
		// If we are anything other than a TEXT node, move to the child indicated by the selection offset
		if ( oSelNode.nodeType != 3 )
		{
			oSelNode = oSelNode.childNodes[nSelOffset] ;
			nSelOffset = 0 ;
		}
		
		// If we are the first child and the previous sibling of the parent is an empty block element (containing nothing or just the filler element)
		// want the backspace to completely remove the empty block element
		if ( !oSelNode.previousSibling && nSelOffset <= 0 )
		{
			oParentNode = oSelNode.parentNode ;
			
			if ( oParentNode && oParentNode.previousSibling && FCKRegexLib.BlockElements.test( oParentNode.previousSibling.nodeName ) )
			{
				if ( FCKTools.NodeIsEmpty( oParentNode.previousSibling ) )
				{
					var oRange = FCK.EditorDocument.createRange() ;
					oRange.selectNode ( oParentNode.previousSibling );
					oRange.deleteContents() ;
					
					// Don't do any default processing
					return false ; 
				}
			}
		}
	}	
	return true ; // Let default processing do its stuff
}
*/
// END iCM Modifications

