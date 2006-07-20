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
 * File Name: fckevents.js
 * 	FCKEvents Class: used to handle events is a advanced way.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKEvents ;

FCKEvents = function( eventsOwner )
{
	this.Owner = eventsOwner ;
	this.RegisteredEvents = new Object() ;
}

FCKEvents.prototype.AttachEvent = function( eventName, functionPointer )
{
	var aTargets ;

	if ( !( aTargets = this.RegisteredEvents[ eventName ] ) ) 
		this.RegisteredEvents[ eventName ] = [ functionPointer ] ;
	else
		aTargets.push( functionPointer ) ;
}

FCKEvents.prototype.FireEvent = function( eventName, params )
{
	var bReturnValue = true ;

	var oCalls = this.RegisteredEvents[ eventName ] ;

	if ( oCalls )
	{
		for ( var i = 0 ; i < oCalls.length ; i++ )
			bReturnValue = ( oCalls[ i ]( this.Owner, params ) && bReturnValue ) ;
	}

	return bReturnValue ;
}