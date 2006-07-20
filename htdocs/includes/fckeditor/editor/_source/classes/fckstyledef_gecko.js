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
 * File Name: fckstyledef_gecko.js
 * 	FCKStyleDef Class: represents a single stylke definition. (Gecko specific)
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

FCKStyleDef.prototype.ApplyToSelection = function()
{
	if ( FCKSelection.GetType() == 'Text' && !this.IsObjectElement )
	{
		var oSelection = FCK.ToolbarSet.CurrentInstance.EditorWindow.getSelection() ;
		
		// Create the main element.
		var e = FCK.ToolbarSet.CurrentInstance.EditorDocument.createElement( this.Element ) ;
		
		for ( var i = 0 ; i < oSelection.rangeCount ; i++ )
		{
			e.appendChild( oSelection.getRangeAt(i).extractContents() ) ;
		}
		
		// Set the attributes.
		this._AddAttributes( e ) ;
		
		// Remove the duplicated elements.
		this._RemoveDuplicates( e ) ;

		var oRange = oSelection.getRangeAt(0) ;		
		oRange.insertNode( e ) ;
	}
	else
	{
		var oControl = FCK.ToolbarSet.CurrentInstance.Selection.GetSelectedElement() ;
		if ( oControl.tagName == this.Element )
			this._AddAttributes( oControl ) ;
	}
}

FCKStyleDef.prototype._AddAttributes = function( targetElement )
{
	for ( var a in this.Attributes )
	{
		switch ( a.toLowerCase() )
		{
			case 'src' :
				targetElement.setAttribute( '_fcksavedurl', this.Attributes[a], 0 ) ;
			
			default :
				targetElement.setAttribute( a, this.Attributes[a], 0 ) ;
		}
	}
}

FCKStyleDef.prototype._RemoveDuplicates = function( parent )
{
	for ( var i = 0 ; i < parent.childNodes.length ; i++ )
	{
		var oChild = parent.childNodes[i] ;
		
		if ( oChild.nodeType != 1 )
			continue ;
		
		this._RemoveDuplicates( oChild ) ;
		
		if ( this.IsEqual( oChild ) )
			FCKTools.RemoveOuterTags( oChild ) ;
	}
}

FCKStyleDef.prototype.IsEqual = function( e )
{
	if ( e.tagName != this.Element )
		return false ;
	
	for ( var a in this.Attributes )
	{
		if ( e.getAttribute( a ) != this.Attributes[a] )
			return false ;
	}
	
	return true ;
}

FCKStyleDef.prototype._RemoveMe = function( elementToCheck )
{
	if ( ! elementToCheck )
		return ;

	var oParent = elementToCheck.parentNode ;

	if ( elementToCheck.nodeType == 1 && this.IsEqual( elementToCheck ) )
	{
		if ( this.IsObjectElement )
		{
			for ( var a in this.Attributes )
				elementToCheck.removeAttribute( a, 0 ) ;
			return ;
		}
		else
			FCKTools.RemoveOuterTags( elementToCheck ) ;
	}
	
	this._RemoveMe( oParent ) ;
}