<!--
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
 * File Name: basexml.asp
 * 	This file include the functions that create the base XML output.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
-->
<%

Sub SetXmlHeaders()
	' Cleans the response buffer.
	Response.Clear()

	' Prevent the browser from caching the result.
	Response.CacheControl = "no-cache"

	' Set the response format.
	Response.CharSet		= "UTF-8"
	Response.ContentType	= "text/xml"
End Sub

Sub CreateXmlHeader( command, resourceType, currentFolder )
	' Create the XML document header.
	Response.Write "<?xml version=""1.0"" encoding=""utf-8"" ?>"

	' Create the main "Connector" node.
	Response.Write "<Connector command=""" & command & """ resourceType=""" & resourceType & """>"
	
	' Add the current folder node.
	Response.Write "<CurrentFolder path=""" & ConvertToXmlAttribute( currentFolder ) & """ url=""" & ConvertToXmlAttribute( GetUrlFromPath( resourceType, currentFolder) ) & """ />"
End Sub

Sub CreateXmlFooter()
	Response.Write "</Connector>"
End Sub

Sub SendError( number, text )
	SetXmlHeaders
	
	' Create the XML document header.
	Response.Write "<?xml version=""1.0"" encoding=""utf-8"" ?>"
	
	Response.Write "<Connector><Error number=""" & number & """ text=""" & Server.HTMLEncode( text ) & """ /></Connector>"
	
	Response.End
End Sub
%>