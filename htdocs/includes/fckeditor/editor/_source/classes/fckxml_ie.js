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
 * File Name: fckxml_ie.js
 * 	FCKXml Class: class to load and manipulate XML files.
 * 	(IE specific implementation)
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKXml = function()
{
	this.Error = false ;
}

FCKXml.prototype.LoadUrl = function( urlToCall )
{
	this.Error = false ;

	var oXmlHttp = FCKTools.CreateXmlObject( 'XmlHttp' ) ;

	if ( !oXmlHttp )
	{
		this.Error = true ;
		return ;
	}

	oXmlHttp.open( "GET", urlToCall, false ) ;
	
	oXmlHttp.send( null ) ;
	
	if ( oXmlHttp.status == 200 || oXmlHttp.status == 304 )
		this.DOMDocument = oXmlHttp.responseXML ;
	else if ( oXmlHttp.status == 0 && oXmlHttp.readyState == 4 )
	{
		this.DOMDocument = FCKTools.CreateXmlObject( 'DOMDocument' ) ;
		this.DOMDocument.async = false ;
		this.DOMDocument.resolveExternals = false ;
		this.DOMDocument.loadXML( oXmlHttp.responseText ) ;
	}
	else
	{
		this.Error = true ;
		alert( 'Error loading "' + urlToCall + '"' ) ;
	}
}

FCKXml.prototype.SelectNodes = function( xpath, contextNode )
{
	if ( this.Error )
		return new Array() ;

	if ( contextNode )
		return contextNode.selectNodes( xpath ) ;
	else
		return this.DOMDocument.selectNodes( xpath ) ;
}

FCKXml.prototype.SelectSingleNode = function( xpath, contextNode ) 
{
	if ( this.Error )
		return ;
		
	if ( contextNode )
		return contextNode.selectSingleNode( xpath ) ;
	else
		return this.DOMDocument.selectSingleNode( xpath ) ;
}