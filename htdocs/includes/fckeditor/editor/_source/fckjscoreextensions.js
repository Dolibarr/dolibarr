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
 * File Name: fckjscoreextensions.js
 * 	Extensions to the JavaScript Core.
 * 
 * 	All custom extentions functions are PascalCased to differ from the standard
 * 	camelCased ones.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

String.prototype.Contains = function( textToCheck )
{
	return ( this.indexOf( textToCheck ) > -1 ) ;
}

String.prototype.Equals = function()
{
	for ( var i = 0 ; i < arguments.length ; i++ )
		if ( this == arguments[i] )
			return true ;
	
	return false ;
}

Array.prototype.AddItem = function( item )
{
	var i = this.length ;
	this[ i ] = item ;
	return i ;
}

Array.prototype.indexOf = function( value )
{
	for ( var i = 0 ; i < this.length ; i++ )
	{
		if ( this[i] == value )
			return i ;
	}
	return -1 ;
}

String.prototype.startsWith = function( value )
{
	return ( this.substr( 0, value.length ) == value ) ;
}

// Extends the String object, creating a "endsWith" method on it.
String.prototype.endsWith = function( value, ignoreCase )
{
	var L1 = this.length ;
	var L2 = value.length ;
	
	if ( L2 > L1 )
		return false ;

	if ( ignoreCase )
	{
		var oRegex = new RegExp( value + '$' , 'i' ) ;
		return oRegex.test( this ) ;
	}
	else
		return ( L2 == 0 || this.substr( L1 - L2, L2 ) == value ) ;
}

String.prototype.remove = function( start, length )
{
	var s = '' ;
	
	if ( start > 0 )
		s = this.substring( 0, start ) ;
	
	if ( start + length < this.length )
		s += this.substring( start + length , this.length ) ;
		
	return s ;
}

String.prototype.trim = function()
{
	return this.replace( /(^\s*)|(\s*$)/g, '' ) ;
}

String.prototype.ltrim = function()
{
	return this.replace( /^\s*/g, '' ) ;
}

String.prototype.rtrim = function()
{
	return this.replace( /\s*$/g, '' ) ;
}

String.prototype.replaceNewLineChars = function( replacement )
{
	return this.replace( /\n/g, replacement ) ;
}