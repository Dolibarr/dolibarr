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
 * File Name: common.js
 * 	Common objects and functions shared by all pages that compose the
 * 	File Browser dialog window.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

function AddSelectOption( selectElement, optionText, optionValue )
{
	var oOption = document.createElement("OPTION") ;

	oOption.text	= optionText ;
	oOption.value	= optionValue ;	

	selectElement.options.add(oOption) ;

	return oOption ;
}

var oConnector	= window.parent.oConnector ;
var oIcons		= window.parent.oIcons ;