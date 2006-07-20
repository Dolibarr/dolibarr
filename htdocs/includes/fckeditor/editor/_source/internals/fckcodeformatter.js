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
 * File Name: fckcodeformatter.js
 * 	Format the HTML.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKCodeFormatter = new Object() ;

FCKCodeFormatter.Init = function()
{
	var oRegex = this.Regex = new Object() ;

	// Regex for line breaks.
	oRegex.BlocksOpener = /\<(P|DIV|H1|H2|H3|H4|H5|H6|ADDRESS|PRE|OL|UL|LI|TITLE|META|LINK|BASE|SCRIPT|LINK|TD|TH|AREA|OPTION)[^\>]*\>/gi ;
	oRegex.BlocksCloser = /\<\/(P|DIV|H1|H2|H3|H4|H5|H6|ADDRESS|PRE|OL|UL|LI|TITLE|META|LINK|BASE|SCRIPT|LINK|TD|TH|AREA|OPTION)[^\>]*\>/gi ;

	oRegex.NewLineTags	= /\<(BR|HR)[^\>]*\>/gi ;

	oRegex.MainTags = /\<\/?(HTML|HEAD|BODY|FORM|TABLE|TBODY|THEAD|TR)[^\>]*\>/gi ;

	oRegex.LineSplitter = /\s*\n+\s*/g ;

	// Regex for indentation.
	oRegex.IncreaseIndent = /^\<(HTML|HEAD|BODY|FORM|TABLE|TBODY|THEAD|TR|UL|OL)[ \/\>]/i ;
	oRegex.DecreaseIndent = /^\<\/(HTML|HEAD|BODY|FORM|TABLE|TBODY|THEAD|TR|UL|OL)[ \>]/i ;
	oRegex.FormatIndentatorRemove = new RegExp( '^' + FCKConfig.FormatIndentator ) ;

	oRegex.ProtectedTags = /(<PRE[^>]*>)([\s\S]*?)(<\/PRE>)/gi ;
}

FCKCodeFormatter._ProtectData = function( outer, opener, data, closer )
{
	return opener + '___FCKpd___' + FCKCodeFormatter.ProtectedData.AddItem( data ) + closer ;
}

FCKCodeFormatter.Format = function( html )
{
	if ( !this.Regex )
		this.Init() ;

	// Protected content that remain untouched during the
	// process go in the following array.
	FCKCodeFormatter.ProtectedData = new Array() ;
	
	var sFormatted = html.replace( this.Regex.ProtectedTags, FCKCodeFormatter._ProtectData ) ;
	
	// Line breaks.
	sFormatted		= sFormatted.replace( this.Regex.BlocksOpener, '\n$&' ) ; ;
	sFormatted		= sFormatted.replace( this.Regex.BlocksCloser, '$&\n' ) ;
	sFormatted		= sFormatted.replace( this.Regex.NewLineTags, '$&\n' ) ;
	sFormatted		= sFormatted.replace( this.Regex.MainTags, '\n$&\n' ) ;

	// Indentation.
	var sIndentation = '' ;
	
	var asLines = sFormatted.split( this.Regex.LineSplitter ) ;
	sFormatted = '' ;
	
	for ( var i = 0 ; i < asLines.length ; i++ )
	{
		var sLine = asLines[i] ;
		
		if ( sLine.length == 0 )
			continue ;
		
		if ( this.Regex.DecreaseIndent.test( sLine ) )
			sIndentation = sIndentation.replace( this.Regex.FormatIndentatorRemove, '' ) ;

		sFormatted += sIndentation + sLine + '\n' ;
		
		if ( this.Regex.IncreaseIndent.test( sLine ) )
			sIndentation += FCKConfig.FormatIndentator ;
	}
	
	// Now we put back the protected data.
	for ( var i = 0 ; i < FCKCodeFormatter.ProtectedData.length ; i++ )
	{
		var oRegex = new RegExp( '___FCKpd___' + i ) ;
		sFormatted = sFormatted.replace( oRegex, FCKCodeFormatter.ProtectedData[i].replace( /\$/g, '$$$$' ) ) ;
	}

	return sFormatted.trim() ;
}