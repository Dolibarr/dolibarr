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
 * File Name: fckdocumentprocessor.js
 * 	Advanced document processors.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKDocumentProcessor = new Object() ;
FCKDocumentProcessor._Items = new Array() ;

FCKDocumentProcessor.AppendNew = function()
{
	var oNewItem = new Object() ;
	this._Items.AddItem( oNewItem ) ;
	return oNewItem ;
}

FCKDocumentProcessor.Process = function( document )
{
	var oProcessor, i = 0 ;
	while( ( oProcessor = this._Items[i++] ) )
		oProcessor.ProcessDocument( document ) ;
}

var FCKDocumentProcessor_CreateFakeImage = function( fakeClass, realElement )
{
	var oImg = FCK.EditorDocument.createElement( 'IMG' ) ;
	oImg.className = fakeClass ;
	oImg.src = FCKConfig.FullBasePath + 'images/spacer.gif' ;
	oImg.setAttribute( '_fckfakelement', 'true', 0 ) ;
	oImg.setAttribute( '_fckrealelement', FCKTempBin.AddElement( realElement ), 0 ) ;
	return oImg ;
}

// Link Anchors
var FCKAnchorsProcessor = FCKDocumentProcessor.AppendNew() ;
FCKAnchorsProcessor.ProcessDocument = function( document )
{
	var aLinks = document.getElementsByTagName( 'A' ) ;

	var oLink ;
	var i = aLinks.length - 1 ;
	while ( i >= 0 && ( oLink = aLinks[i--] ) )
	{
		// If it is anchor.
		if ( oLink.name.length > 0 && ( !oLink.getAttribute('href') || oLink.getAttribute('href').length == 0 ) )
		{
			var oImg = FCKDocumentProcessor_CreateFakeImage( 'FCK__Anchor', oLink.cloneNode(true) ) ;
			oImg.setAttribute( '_fckanchor', 'true', 0 ) ;
			
			oLink.parentNode.insertBefore( oImg, oLink ) ;
			oLink.parentNode.removeChild( oLink ) ;
		}
	}
}

// Page Breaks
var FCKPageBreaksProcessor = FCKDocumentProcessor.AppendNew() ;
FCKPageBreaksProcessor.ProcessDocument = function( document )
{
	var aDIVs = document.getElementsByTagName( 'DIV' ) ;

	var eDIV ;
	var i = aDIVs.length - 1 ;
	while ( i >= 0 && ( eDIV = aDIVs[i--] ) )
	{
		if ( eDIV.style.pageBreakAfter == 'always' && eDIV.childNodes.length == 1 && eDIV.childNodes[0].style && eDIV.childNodes[0].style.display == 'none' )
		{
			var oFakeImage = FCKDocumentProcessor_CreateFakeImage( 'FCK__PageBreak', eDIV.cloneNode(true) ) ;
			
			eDIV.parentNode.insertBefore( oFakeImage, eDIV ) ;
			eDIV.parentNode.removeChild( eDIV ) ;
		}
	}
/*
	var aCenters = document.getElementsByTagName( 'CENTER' ) ;

	var oCenter ;
	var i = aCenters.length - 1 ;
	while ( i >= 0 && ( oCenter = aCenters[i--] ) )
	{
		if ( oCenter.style.pageBreakAfter == 'always' && oCenter.innerHTML.trim().length == 0 )
		{
			var oFakeImage = FCKDocumentProcessor_CreateFakeImage( 'FCK__PageBreak', oCenter.cloneNode(true) ) ;
			
			oCenter.parentNode.insertBefore( oFakeImage, oCenter ) ;
			oCenter.parentNode.removeChild( oCenter ) ;
		}
	}
*/
}

// Flash Embeds.
var FCKFlashProcessor = FCKDocumentProcessor.AppendNew() ;
FCKFlashProcessor.ProcessDocument = function( document )
{
	/*
	Sample code:
	This is some <embed src="/UserFiles/Flash/Yellow_Runners.swf"></embed><strong>sample text</strong>. You are&nbsp;<a name="fred"></a> using <a href="http://www.fckeditor.net/">FCKeditor</a>.
	*/

	var aEmbeds = document.getElementsByTagName( 'EMBED' ) ;

	var oEmbed ;
	var i = aEmbeds.length - 1 ;
	while ( i >= 0 && ( oEmbed = aEmbeds[i--] ) )
	{
		if ( oEmbed.src.endsWith( '.swf', true ) )
		{
			var oCloned = oEmbed.cloneNode( true ) ;
			
			// On IE, some properties are not getting clonned properly, so we 
			// must fix it. Thanks to Alfonso Martinez.
			if ( FCKBrowserInfo.IsIE )
			{
				var oAtt ;
				if ( oAtt = oEmbed.getAttribute( 'scale' ) ) oCloned.setAttribute( 'scale', oAtt ) ;
				if ( oAtt = oEmbed.getAttribute( 'play' ) ) oCloned.setAttribute( 'play', oAtt ) ;
				if ( oAtt = oEmbed.getAttribute( 'loop' ) ) oCloned.setAttribute( 'loop', oAtt ) ;
				if ( oAtt = oEmbed.getAttribute( 'menu' ) ) oCloned.setAttribute( 'menu', oAtt ) ;
				if ( oAtt = oEmbed.getAttribute( 'wmode' ) ) oCloned.setAttribute( 'wmode', oAtt ) ;
				if ( oAtt = oEmbed.getAttribute( 'quality' ) ) oCloned.setAttribute( 'quality', oAtt ) ;
			}
		
			var oImg = FCKDocumentProcessor_CreateFakeImage( 'FCK__Flash', oCloned ) ;
			oImg.setAttribute( '_fckflash', 'true', 0 ) ;
			
			FCKFlashProcessor.RefreshView( oImg, oEmbed ) ;

			oEmbed.parentNode.insertBefore( oImg, oEmbed ) ;
			oEmbed.parentNode.removeChild( oEmbed ) ;

//			oEmbed.setAttribute( '_fckdelete', 'true', 0) ;
//			oEmbed.style.display = 'none' ;
//			oEmbed.hidden = true ;
		}
	}
}

FCKFlashProcessor.RefreshView = function( placholderImage, originalEmbed )
{
	if ( originalEmbed.width > 0 )
		placholderImage.style.width = FCKTools.ConvertHtmlSizeToStyle( originalEmbed.width ) ;
		
	if ( originalEmbed.height > 0 )
		placholderImage.style.height = FCKTools.ConvertHtmlSizeToStyle( originalEmbed.height ) ;
}

FCK.GetRealElement = function( fakeElement )
{
	var e = FCKTempBin.Elements[ fakeElement.getAttribute('_fckrealelement') ] ;

	if ( fakeElement.getAttribute('_fckflash') )
	{
		if ( fakeElement.style.width.length > 0 )
				e.width = FCKTools.ConvertStyleSizeToHtml( fakeElement.style.width ) ;
		
		if ( fakeElement.style.height.length > 0 )
				e.height = FCKTools.ConvertStyleSizeToHtml( fakeElement.style.height ) ;
	}
	
	return e ;
}

// START iCM MODIFICATIONS
/*
var FCKTablesProcessor = FCKDocumentProcessor.AppendNew() ;
FCKTablesProcessor.ProcessDocument = function( document )
{
	FCKTablesProcessor.CheckTablesNesting( document ) ;
}

// Ensure that tables are not incorrectly nested within P, H1, H2, etc tags
FCKTablesProcessor.CheckTablesNesting = function( document )
{
	var aTables = document.getElementsByTagName( "TABLE" ) ;
	var oParentNode ;
	
	for ( var i=0; i<aTables.length; i++ )
	{
		FCKTablesProcessor.CheckTableNesting( aTables[i] ) ;
	}
}

// Corrects nesting of the supplied table as necessary.
// Also called by fck_table.html to check that a newly inserted table is correctly nested.
FCKTablesProcessor.CheckTableNesting = function( oTableNode )
{
	var oParentBlockNode = FCKTools.GetParentBlockNode( oTableNode.parentNode ) ;
	
	if ( oParentBlockNode && !FCKRegexLib.TableBlockElements.test( oParentBlockNode.nodeName ) )
	{
		// Create a new tag which holds the content of the child nodes located before the table
		var oNode1 = FCK.EditorDocument.createElement( oParentBlockNode.tagName ) ;
		var oFragment1 = FCKTools.GetDocumentFragment( oParentBlockNode, oParentBlockNode.firstChild, oTableNode, true, false, true ) ;
		oNode1.appendChild( oFragment1 ) ;
		FCKTools.SetElementAttributes( oNode1, oParentBlockNode.attributes ) ; 	// Transfer across any class attributes, etc
	
		// Create a new tag which holds the content of the child nodes located after the table
		var oNode2 = FCK.EditorDocument.createElement( oParentBlockNode.tagName );
		var oFragment2 = FCKTools.GetDocumentFragment( oParentBlockNode, oTableNode, oParentBlockNode.lastChild, false, true, true ) ;
		oNode2.appendChild( oFragment2 ) ;
		FCKTools.SetElementAttributes( oNode2, oParentBlockNode.attributes ) ; 	// Transfer across any class attributes, etc
		
		// Create a document fragment that contains the two new elements with the table element inbetween
		var oNewNode = FCK.EditorDocument.createDocumentFragment() ;
		if ( !FCKTools.NodeIsEmpty( oNode1 ) )
			oNewNode.appendChild( oNode1 ) ;
		oNewNode.appendChild( oTableNode ) ;
		if ( !FCKTools.NodeIsEmpty( oNode2 ) )
			oNewNode.appendChild( oNode2 ) ; 
		
		// Replace the existing parent node with the nodes in the fragment
		oParentBlockNode.parentNode.replaceChild( oNewNode, oParentBlockNode ) ;
	}
}		
*/
// END iCM MODIFICATIONS
