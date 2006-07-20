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
 * File Name: fcktoolbarbreak_gecko.js
 * 	FCKToolbarBreak Class: breaks the toolbars.
 * 	It makes it possible to force the toolbar to break to a new line.
 * 	This is the Gecko specific implementation.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKToolbarBreak = function()
{}

FCKToolbarBreak.prototype.Create = function( targetElement )
{
	var oBreakDiv = targetElement.ownerDocument.createElement( 'div' ) ;
	
	oBreakDiv.style.clear = oBreakDiv.style.cssFloat = FCKLang.Dir == 'rtl' ? 'right' : 'left' ;
	
	targetElement.appendChild( oBreakDiv ) ;
}