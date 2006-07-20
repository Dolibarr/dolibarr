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
 * File Name: fckurlparams.js
 * 	Defines the FCKURLParams object that is used to get all parameters
 * 	passed by the URL QueryString (after the "?").
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

// #### URLParams: holds all URL passed parameters (like ?Param1=Value1&Param2=Value2)
var FCKURLParams = new Object() ;

var aParams = document.location.search.substr(1).split('&') ;
for ( var i = 0 ; i < aParams.length ; i++ )
{
	var aParam = aParams[i].split('=') ;
	var sParamName  = aParam[0] ;
	var sParamValue = aParam[1] ;

	FCKURLParams[ sParamName ] = sParamValue ;
}