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
 * File Name: fckiecleanup.js
 * 	FCKIECleanup Class: a generic class used as a tool to remove IE leaks.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */


var	FCKIECleanup = function( attachWindow )
{

	this.Items = new Array() ;

	attachWindow._FCKCleanupObj = this ;
	attachWindow.attachEvent( 'onunload', FCKIECleanup_Cleanup ) ;	
}
	
FCKIECleanup.prototype.AddItem = function( dirtyItem, cleanupFunction )
{
	this.Items.push( [ dirtyItem, cleanupFunction ] ) ;
}
	
function FCKIECleanup_Cleanup()
{
	var aItems = this._FCKCleanupObj.Items ;
	var iLenght = aItems.length ;

	for ( var i = 0 ; i < iLenght ; i++ )
	{
		var oItem = aItems[i] ;
		oItem[1].call( oItem[0] ) ;
		aItems[i] = null ;
	}
	
	this._FCKCleanupObj = null ;
	
	if ( CollectGarbage )
		CollectGarbage() ;
}