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
 * File Name: fckxhtml_gecko.js
 * 	Defines the FCKXHtml object, responsible for the XHTML operations.
 * 	Gecko specific.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

FCKXHtml._GetMainXmlString = function()
{
	// Create the XMLSerializer.
	var oSerializer = new XMLSerializer() ;

	// Return the serialized XML as a string.
	// Due to a BUG on Gecko, the special chars sequence "#?-:" must be replaced with "&"
	// for the XHTML entities.
	return oSerializer.serializeToString( this.MainNode ).replace( FCKRegexLib.GeckoEntitiesMarker, '&' ) ;
}

// There is a BUG on Gecko... createEntityReference returns null.
// So we use a trick to append entities on it.
FCKXHtml._AppendEntity = function( xmlNode, entity )
{
	xmlNode.appendChild( this.XML.createTextNode( '#?-:' + entity + ';' ) ) ;
}

FCKXHtml._AppendAttributes = function( xmlNode, htmlNode, node )
{
	var aAttributes = htmlNode.attributes ;
	
	for ( var n = 0 ; n < aAttributes.length ; n++ )
	{
		var oAttribute = aAttributes[n] ;
		
		if ( oAttribute.specified )
		{
			var sAttName = oAttribute.nodeName.toLowerCase() ;
			var sAttValue ;

			// Ignore any attribute starting with "_fck".
			if ( sAttName.startsWith( '_fck' ) )
				continue ;
			// There is a bug in Mozilla that returns '_moz_xxx' attributes as specified.
			else if ( sAttName.indexOf( '_moz' ) == 0 )
				continue ;
			// There are one cases (on Gecko) when the oAttribute.nodeValue must be used:
			//		- for the "class" attribute
			else if ( sAttName == 'class' )
				sAttValue = oAttribute.nodeValue ;
			// XHTML doens't support attribute minimization like "CHECKED". It must be trasformed to cheched="checked".
			else if ( oAttribute.nodeValue === true )
				sAttValue = sAttName ;
			else
				sAttValue = htmlNode.getAttribute( sAttName, 2 ) ;	// We must use getAttribute to get it exactly as it is defined.

			if ( FCKConfig.ForceSimpleAmpersand && sAttValue.replace )
				sAttValue = sAttValue.replace( /&/g, '___FCKAmp___' ) ;
			
			this._AppendAttribute( node, sAttName, sAttValue ) ;
		}
	}
}