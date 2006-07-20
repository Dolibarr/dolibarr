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
 * File Name: fckselection_ie.js
 * 	Active selection functions. (IE specific implementation)
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

// Get the selection type.
FCKSelection.GetType = function()
{
	return FCK.EditorDocument.selection.type ;
}

// Retrieves the selected element (if any), just in the case that a single
// element (object like and image or a table) is selected.
FCKSelection.GetSelectedElement = function()
{
	if ( this.GetType() == 'Control' )
	{
		var oRange = FCK.EditorDocument.selection.createRange() ;

		if ( oRange && oRange.item )
			return FCK.EditorDocument.selection.createRange().item(0) ;
	}
}

FCKSelection.GetParentElement = function()
{
	switch ( this.GetType() )
	{
		case 'Control' :
			return FCKSelection.GetSelectedElement().parentElement ;
		case 'None' :
			return ;
		default :
			return FCK.EditorDocument.selection.createRange().parentElement() ;
	}
}

FCKSelection.SelectNode = function( node )
{
	FCK.Focus() ;
	FCK.EditorDocument.selection.empty() ;

	try 
	{
		// Try to select the node as a control.
		var oRange = FCK.EditorDocument.body.createControlRange() ;
		oRange.addElement( node ) ;
	} 
	catch(e) 
	{
		// If failed, select it as a text range.
		var oRange = FCK.EditorDocument.selection.createRange() ;
		oRange.moveToElementText( node ) ;
	}

	oRange.select() ;
}

FCKSelection.Collapse = function( toStart )
{
	FCK.Focus() ;
	if ( this.GetType() == 'Text' )
	{
		var oRange = FCK.EditorDocument.selection.createRange() ;
		oRange.collapse( toStart == null || toStart === true ) ;
		oRange.select() ;
	}
}

// The "nodeTagName" parameter must be Upper Case.
FCKSelection.HasAncestorNode = function( nodeTagName )
{
	var oContainer ;

	if ( FCK.EditorDocument.selection.type == "Control" )
	{
		oContainer = this.GetSelectedElement() ;
	}
	else
	{
		var oRange  = FCK.EditorDocument.selection.createRange() ;
		oContainer = oRange.parentElement() ;
	}

	while ( oContainer )
	{
		if ( oContainer.tagName == nodeTagName ) return true ;
		oContainer = oContainer.parentNode ;
	}

	return false ;
}

// The "nodeTagName" parameter must be UPPER CASE.
FCKSelection.MoveToAncestorNode = function( nodeTagName )
{
	var oNode ;

	if ( FCK.EditorDocument.selection.type == "Control" )
	{
		var oRange = FCK.EditorDocument.selection.createRange() ;
		for ( i = 0 ; i < oRange.length ; i++ )
		{
			if (oRange(i).parentNode)
			{
				oNode = oRange(i).parentNode ;
				break ;
			}
		}
	}
	else
	{
		var oRange  = FCK.EditorDocument.selection.createRange() ;
		oNode = oRange.parentElement() ;
	}

	while ( oNode && oNode.nodeName != nodeTagName )
		oNode = oNode.parentNode ;

	return oNode ;
}

FCKSelection.Delete = function()
{
	// Gets the actual selection.
	var oSel = FCK.EditorDocument.selection ;

	// Deletes the actual selection contents.
	if ( oSel.type.toLowerCase() != "none" )
	{
		oSel.clear() ;
	}

	return oSel ;
}
// START iCM Modifications
/*
// Move the cursor position (the selection point) to a specific offset within a specific node
// If no offset specified, the start of the node is assumed
FCKSelection.SetCursorPosition = function ( oNode, nOffset )
{
	if ( typeof nOffset == "undefined" ) nOffset = 0 ;

	FCK.Selection.SelectNode( oNode ) ; // Doesn't handle offsets currently but offset always zero at mo
	FCK.Selection.Collapse( true ) ;
	
	oNode.scrollIntoView( false );	
}
*/
// END iCM Modifications

