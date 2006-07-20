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
 * File Name: fcktoolbarbreak_ie.js
 * 	FCKToolbarBreak Class: breaks the toolbars.
 * 	It makes it possible to force the toolbar to brak to a new line.
 * 	This is the IE specific implementation.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKToolbarBreak = function()
{}

FCKToolbarBreak.prototype.Create = function( targetElement )
{
	var oBreakDiv = targetElement.ownerDocument.createElement( 'div' ) ;
	
	oBreakDiv.className = 'TB_Break' ;
	
	oBreakDiv.style.clear = FCKLang.Dir == 'rtl' ? 'left' : 'right' ;
	
	targetElement.appendChild( oBreakDiv ) ;
}