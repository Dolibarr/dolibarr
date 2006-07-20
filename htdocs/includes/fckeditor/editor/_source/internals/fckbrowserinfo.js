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
 * File Name: fckbrowserinfo.js
 * 	Contains browser detection information.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var s = navigator.userAgent.toLowerCase() ;

var FCKBrowserInfo = 
{
	IsIE		: s.Contains('msie'),
	IsIE7		: s.Contains('msie 7'),
	IsGecko		: s.Contains('gecko/'),
	IsSafari	: s.Contains('safari'),
	IsOpera		: s.Contains('opera')
}

FCKBrowserInfo.IsGeckoLike = FCKBrowserInfo.IsGecko || FCKBrowserInfo.IsSafari || FCKBrowserInfo.IsOpera ;

if ( FCKBrowserInfo.IsGecko )
{
	var sGeckoVersion = s.match( /gecko\/(\d+)/ )[1] ;
	FCKBrowserInfo.IsGecko10 = sGeckoVersion < 20051111 ;	// Actually "10" refers to versions before Firefox 1.5, where Gecko 20051111 has been released.
}