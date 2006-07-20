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
 * File Name: fck.js
 * 	Creation and initialization of the "FCK" object. This is the main object
 * 	that represents an editor instance.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

// FCK represents the active editor instance
var FCK = new Object() ;
FCK.Name			= FCKURLParams[ 'InstanceName' ] ;

FCK.Status			= FCK_STATUS_NOTLOADED ;
FCK.EditMode		= FCK_EDITMODE_WYSIWYG ;

FCK.LoadLinkedFile = function()
{
	// There is a bug on IE... getElementById returns any META tag that has the
	// name set to the ID you are looking for. So the best way in to get the array
	// by names and look for the correct one.
	// As ASP.Net generates a ID that is different from the Name, we must also
	// look for the field based on the ID (the first one is the ID).
	
	var oDocument = window.parent.document ;

	var eLinkedField		= oDocument.getElementById( FCK.Name ) ;
	var colElementsByName	= oDocument.getElementsByName( FCK.Name ) ;

	var i = 0;
	while ( eLinkedField || i == 0 )
	{
		if ( eLinkedField && ( eLinkedField.tagName == 'INPUT' || eLinkedField.tagName == 'TEXTAREA' ) )
		{
			FCK.LinkedField = eLinkedField ;
			break ;
		}
		eLinkedField = colElementsByName[i++] ;
	}
}
FCK.LoadLinkedFile() ;

var FCKTempBin = new Object() ;
FCKTempBin.Elements = new Array() ;

FCKTempBin.AddElement = function( element )
{
	var iIndex = this.Elements.length ;
	this.Elements[ iIndex ] = element ;
	return iIndex ;
}

FCKTempBin.RemoveElement = function( index )
{
	var e = this.Elements[ index ] ;
	this.Elements[ index ] = null ;
	return e ;
}

FCKTempBin.Reset = function()
{
	var i = 0 ;
	while ( i < this.Elements.length )
		this.Elements[ i++ ] == null ;
	this.Elements.length = 0 ;
}