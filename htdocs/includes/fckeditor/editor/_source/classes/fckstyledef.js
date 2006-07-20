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
 * File Name: fckstyledef.js
 * 	FCKStyleDef Class: represents a single style definition.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKStyleDef = function( name, element )
{
	this.Name = name ;
	this.Element = element.toUpperCase() ;
	this.IsObjectElement = FCKRegexLib.ObjectElements.test( this.Element ) ;
	this.Attributes = new Object() ;
}

FCKStyleDef.prototype.AddAttribute = function( name, value )
{
	this.Attributes[ name ] = value ;
}

FCKStyleDef.prototype.GetOpenerTag = function()
{
	var s = '<' + this.Element ;
	
	for ( var a in this.Attributes )
		s += ' ' + a + '="' + this.Attributes[a] + '"' ;
	
	return s + '>' ;
}

FCKStyleDef.prototype.GetCloserTag = function()
{
	return '</' + this.Element + '>' ;
}


FCKStyleDef.prototype.RemoveFromSelection = function()
{
	if ( FCKSelection.GetType() == 'Control' )
		this._RemoveMe( FCK.ToolbarSet.CurrentInstance.Selection.GetSelectedElement() ) ;
	else
		this._RemoveMe( FCK.ToolbarSet.CurrentInstance.Selection.GetParentElement() ) ;
}