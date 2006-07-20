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
 * File Name: fcktools_gecko.js
 * 	Utility functions. (Gecko version).
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

// Constant for the Gecko Bogus Node.
var GECKO_BOGUS = FCKBrowserInfo.IsGecko ? '<br _moz_editor_bogus_node="TRUE">' : '' ;

FCKTools.CancelEvent = function( e )
{
	if ( e )
		e.preventDefault() ;
}

FCKTools.DisableSelection = function( element )
{
	if ( FCKBrowserInfo.IsGecko )
		element.style.MozUserSelect	= 'none' ;	// Gecko only.	
	else
		element.style.userSelect	= 'none' ;	// CSS3 (not supported yet).
}

// Appends a CSS file to a document.
FCKTools.AppendStyleSheet = function( documentElement, cssFileUrl )
{
	var e = documentElement.createElement( 'LINK' ) ;
	e.rel	= 'stylesheet' ;
	e.type	= 'text/css' ;
	e.href	= cssFileUrl ;
	documentElement.getElementsByTagName("HEAD")[0].appendChild( e ) ;
	return e ;
}

// Removes all attributes and values from the element.
FCKTools.ClearElementAttributes = function( element )
{
	// Loop throw all attributes in the element
	for ( var i = 0 ; i < element.attributes.length ; i++ )
	{
		// Remove the element by name.
		element.removeAttribute( element.attributes[i].name, 0 ) ;	// 0 : Case Insensitive
	}
}

// Returns an Array of strings with all defined in the elements inside another element.
FCKTools.GetAllChildrenIds = function( parentElement )
{
	// Create the array that will hold all Ids.
	var aIds = new Array() ;
	
	// Define a recursive function that search for the Ids.
	var fGetIds = function( parent )
	{
		for ( var i = 0 ; i < parent.childNodes.length ; i++ )
		{
			var sId = parent.childNodes[i].id ;
			
			// Check if the Id is defined for the element.
			if ( sId && sId.length > 0 ) aIds[ aIds.length ] = sId ;
			
			// Recursive call.
			fGetIds( parent.childNodes[i] ) ;
		}
	}
	
	// Start the recursive calls.
	fGetIds( parentElement ) ;

	return aIds ;
}

FCKTools.RemoveOuterTags = function( e )
{
	var oFragment = e.ownerDocument.createDocumentFragment() ;
			
	for ( var i = 0 ; i < e.childNodes.length ; i++ )
		oFragment.appendChild( e.childNodes[i] ) ;
			
	e.parentNode.replaceChild( oFragment, e ) ;
}

FCKTools.CreateXmlObject = function( object )
{
	switch ( object )
	{
		case 'XmlHttp' :
			return new XMLHttpRequest() ;
		case 'DOMDocument' :
			return document.implementation.createDocument( '', '', null ) ;
	}
	return null ;
}

FCKTools.GetScrollPosition = function( relativeWindow )
{
	return { X : relativeWindow.pageXOffset, Y : relativeWindow.pageYOffset } ;
}

FCKTools.AddEventListener = function( sourceObject, eventName, listener )
{
	sourceObject.addEventListener( eventName, listener, false ) ;
}

FCKTools.RemoveEventListener = function( sourceObject, eventName, listener )
{
	sourceObject.removeEventListener( eventName, listener, false ) ;
}

// Listeners attached with this function cannot be detached.
FCKTools.AddEventListenerEx = function( sourceObject, eventName, listener, paramsArray )
{
	sourceObject.addEventListener( 
		eventName, 
		function( e )
		{
			listener.apply( sourceObject, [ e ].concat( paramsArray || [] ) ) ;
		},
		false 
	) ;
}

// Returns and object with the "Width" and "Height" properties.
FCKTools.GetViewPaneSize = function( win )
{
	return { Width : win.innerWidth, Height : win.innerHeight } ;
}

FCKTools.SaveStyles = function( element )
{
	var oSavedStyles = new Object() ;
	
	if ( element.className.length > 0 )
	{
		oSavedStyles.Class = element.className ;
		element.className = '' ;
	}

	var sInlineStyle = element.getAttribute( 'style' ) ;

	if ( sInlineStyle && sInlineStyle.length > 0 )
	{
		oSavedStyles.Inline = sInlineStyle ;
		element.setAttribute( 'style', '', 0 ) ;	// 0 : Case Insensitive
	}

	return oSavedStyles ;
}

FCKTools.RestoreStyles = function( element, savedStyles )
{
	element.className = savedStyles.Class || '' ;

	if ( savedStyles.Inline )
		element.setAttribute( 'style', savedStyles.Inline, 0 ) ;	// 0 : Case Insensitive
	else
		element.removeAttribute( 'style', 0 ) ;
}

FCKTools.RegisterDollarFunction = function( targetWindow )
{
	targetWindow.$ = function( id ) 
	{ 
		return this.document.getElementById( id ) ;
	} ;
}

// START iCM Modifications
/*
// Starting at the specified node, find the first inline node of the sequence
// For example, assume we have the following elements : <p>Some text <span>some more text</span> and <a href="href">some link</a> yet some more text</p>
// If the "some link" text node is the one specified, then the "Some text" text node will be the first inline node returned.
FCKTools.GetFirstInlineNode = function( oNode )
{
	if ( FCKRegexLib.BlockElements.test( oNode.nodeName ) )
		return oNode ;
	else if ( oNode.previousSibling && !FCKRegexLib.BlockElements.test( oNode.previousSibling.nodeName ) )
		return FCKTools.GetFirstInlineNode( oNode.previousSibling ) ;
	else if ( oNode.parentNode && !FCKRegexLib.BlockElements.test( oNode.parentNode.nodeName ) && oNode.parentNode.nodeName.toUpperCase() != "BODY" )
		return FCKTools.GetFirstInlineNode( oNode.parentNode ) ;
	else 
		return oNode ;
}

// Starting at the specified node, find the last inline node of the sequence
// For example, assume we have the following elements : <p>Some text <span>some more text</span> and <a href="href">some link</a> yet some more text</p>
// If the "some link" text node is the one specified, then the " yet some more text" text node will be the last inline node returned.
FCKTools.GetLastInlineNode = function( oNode )
{
	if ( FCKRegexLib.BlockElements.test( oNode.nodeName ) )
		return oNode ;
	else if ( oNode.nextSibling && !FCKRegexLib.BlockElements.test( oNode.nextSibling.nodeName ) )
		return FCKTools.GetLastInlineNode( oNode.nextSibling ) ;
	else if ( oNode.parentNode && !FCKRegexLib.BlockElements.test( oNode.parentNode.nodeName ) && oNode.parentNode.nodeName.toUpperCase() != "BODY" )
		return FCKTools.GetLastInlineNode( oNode.parentNode ) ;
	else
		return oNode ;
}


// Split the supplied parent at the specified child and (optionally) offset.
// Ensure that enclosing block elements are created where missing but that existing 
// block elements (table for example) don't get incorrectly nested. 
FCKTools.SplitNode = function( oParentBlockNode, oChildNode, nOffset )
{
	if ( typeof nOffset == "undefined" ) nOffset = 0 ;

	var oFragment = FCK.EditorDocument.createDocumentFragment() ;
	var oRange = FCK.EditorDocument.createRange() ;

	if ( FCKRegexLib.ListElements.test( oParentBlockNode.nodeName ) )
	{
		// Treat OL/UL parents differently as want to split at the specified
		// child LI node to create to OL/UL lists.
		oStartNode = oParentBlockNode.firstChild ;
		oEndNode = oParentBlockNode.lastChild ;
	}
	else
	{
		// Locate the inline nodes adjacent to the specified child node so that these can
		// be kept together.
		oStartNode = FCKTools.GetFirstInlineNode( oChildNode ) ;
		oEndNode = FCKTools.GetLastInlineNode( oChildNode ) ;
	}

	// Create a new tag which holds the content of the affected node(s) located before (but not including) the child node and offset
	if ( FCKRegexLib.BlockElements.test( oStartNode.nodeName ) && !FCKRegexLib.ListElements.test( oParentBlockNode.nodeName ) )
	{
		// First element of the bunch is already a block element so we don't want to wrap it with a new block element.
		// Just use this first node provided it is not the same as the last node (to prevent duplication), otherwise
		// create a new empty P element.
		if ( oStartNode != oEndNode )
		{
			oBlockNode1 = oStartNode.cloneNode( true ) ;
		}
		else
		{
			oBlockNode1 = FCK.EditorDocument.createElement( "P" ) ;
			oBlockNode1.innerHTML = GECKO_BOGUS ;
			
			if ( !FCKRegexLib.SpecialBlockElements.test( oParentBlockNode.nodeName ) )
				FCKTools.SetElementAttributes( oBlockNode1, oParentBlockNode.attributes ) ;  // Transfer across any class attributes, etc
		}
	}
	else
	{
		// First element of the bunch is not a block element (or it is a LI element which is a special case).
		// So ensure all of the inline nodes before the selection are wrapped with a suitable block element.
		var oBlockNode1 = FCK.EditorDocument.createElement( FCKRegexLib.SpecialBlockElements.test( oParentBlockNode.nodeName ) ? "P" : oParentBlockNode.tagName ) ;
		oRange.setStartBefore( oStartNode ) ;
		if ( nOffset == 0 )
			oRange.setEndBefore( oChildNode ) ;
		else
			oRange.setEnd( oChildNode, nOffset ) ;
		oBlockNode1.appendChild( oRange.cloneContents() ) ;
		oBlockNode1.innerHTML = oBlockNode1.innerHTML.replace(/[\x00-\x1F]/g, "") ; // Prevent any control characters returned within the innerHTML from causing problems
		if ( FCKTools.NodeIsEmpty( oBlockNode1 ) )
			oBlockNode1.innerHTML = GECKO_BOGUS ;		// Ensure it has some content, required for Gecko
		else
			oBlockNode1.innerHTML = oBlockNode1.innerHTML.replace( FCKRegexLib.EmptyElement, "" ) ; // Strip out any empty tags that may have been generated by the split
		if ( !FCKRegexLib.SpecialBlockElements.test( oParentBlockNode.nodeName ) )
			FCKTools.SetElementAttributes( oBlockNode1, oParentBlockNode.attributes ) ; 	// Transfer across any class attributes, etc
	}

	// Create a new tag which holds the content of the affected node(s) located after (and including) the child node
	if ( FCKRegexLib.BlockElements.test( oEndNode.nodeName ) && !FCKRegexLib.ListElements.test( oParentBlockNode.nodeName ) )
	{
		// Last element of the bunch is already a block element so we don't want to wrap it with a new block element.
		oBlockNode2 = oEndNode.cloneNode( true ) ;
	}
	else
	{
		// Last element of the bunch is not a block element (or it is a LI element which is a special case).
		// So ensure all of the inline nodes after and including the child/offset are wrapped with a suitable block element.
		var oBlockNode2 = FCK.EditorDocument.createElement( FCKRegexLib.SpecialBlockElements.test( oParentBlockNode.nodeName ) ? "P" : oParentBlockNode.tagName );
		oRange.setEndAfter( oEndNode );
		if ( nOffset == 0 )
			oRange.setStartBefore( oChildNode ) ;
		else
			oRange.setStart( oChildNode, nOffset );
		oBlockNode2.appendChild( oRange.cloneContents() ) ;
		oBlockNode2.innerHTML = oBlockNode2.innerHTML.replace(/[\x00-\x1F]/g, "") ;  // Prevent any control characters returned within the innerHTML from causing problems
		if ( FCKTools.NodeIsEmpty( oBlockNode2 ) ) 
			oBlockNode2.innerHTML = GECKO_BOGUS ; 			// Ensure it has some content, required for Gecko
		else
			oBlockNode2.innerHTML = oBlockNode2.innerHTML.replace( FCKRegexLib.EmptyElement, "" ) ; // Strip out any empty tags that may have been generated by the split
		if ( !FCKRegexLib.SpecialBlockElements.test( oParentBlockNode.nodeName ) )
			FCKTools.SetElementAttributes( oBlockNode2, oParentBlockNode.attributes ) ; 	// Transfer across any class attributes, etc
	}
	
	// Insert the resulting nodes into a document fragment
	oFragment.appendChild( oBlockNode1 );
	oFragment.appendChild( oBlockNode2 );
	
	// Replace the affected nodes with the new nodes (fragment)
	FCKTools.ReplaceNodes( oParentBlockNode, oStartNode, oEndNode, oFragment ) ;	
	
	// Return the second node so it can be used for setting cursor position, etc
	return oBlockNode2 ;
}

// Function that replaces the specified range of nodes (inclusive) within the supplied parent
// with the nodes stored in the supplied document fragment.
FCKTools.ReplaceNodes = function( oParentBlockNode, oStartNode, oEndNode, oFragment )
{
	var oRange = FCK.EditorDocument.createRange() ;
	
	// Delete the affected node(s)
	if ( !FCKRegexLib.SpecialBlockElements.test( oParentBlockNode.nodeName ) && (oParentBlockNode.firstChild == oStartNode) && (oParentBlockNode.lastChild == oEndNode) )
	{
		// Entire parent block node is to be replaced so insert the two new block elements before it 
		// and then remove the old node
		oRange.selectNode ( oParentBlockNode );
	}
	else
	{
		// Only part of the parent block node is to be replaced so insert the two new block elements
		// before the first inline node of the affected content and then remove the old nodes
		oRange.setEndAfter( oEndNode ) ;
		oRange.setStartBefore( oStartNode ) ;
	}
	
	// Insert the replacement nodes
	oRange.deleteContents() ;
	oRange.insertNode( oFragment ) ;
}
*/
// END iCM Modifications

