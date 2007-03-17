<!--
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2007 Frederico Caldeira Knabben
 * 
 * == BEGIN LICENSE ==
 * 
 * Licensed under the terms of any of the following licenses at your
 * choice:
 * 
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 * 
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 * 
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 * 
 * == END LICENSE ==
 * 
 * File Name: io.asp
 * 	This file include IO specific functions used by the ASP Connector.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (www.fckeditor.net)
-->
<%
Function GetUrlFromPath( resourceType, folderPath )
	If resourceType = "" Then
		GetUrlFromPath = RemoveFromEnd( sUserFilesPath, "/" ) & folderPath
	Else
		GetUrlFromPath = sUserFilesPath & LCase( resourceType ) & folderPath
	End If
End Function

Function RemoveExtension( fileName )
	RemoveExtension = Left( fileName, InStrRev( fileName, "." ) - 1 )
End Function

Function ServerMapFolder( resourceType, folderPath )
	' Get the resource type directory.
	Dim sResourceTypePath
	sResourceTypePath = sUserFilesDirectory & LCase( resourceType ) & "\"
	
	' Ensure that the directory exists.
	CreateServerFolder sResourceTypePath

	' Return the resource type directory combined with the required path.
	ServerMapFolder = sResourceTypePath & RemoveFromStart( folderPath, "/" )
End Function

Sub CreateServerFolder( folderPath )
	Dim oFSO
	Set oFSO = Server.CreateObject( "Scripting.FileSystemObject" )
	
	Dim sParent
	sParent = oFSO.GetParentFolderName( folderPath )
	
	' Check if the parent exists, or create it.
	If ( NOT oFSO.FolderExists( sParent ) ) Then CreateServerFolder( sParent )
	
	If ( oFSO.FolderExists( folderPath ) = False ) Then 
		oFSO.CreateFolder( folderPath )
	End If
	
	Set oFSO = Nothing
End Sub

Function IsAllowedExt( extension, resourceType )
	Dim oRE
	Set oRE	= New RegExp
	oRE.IgnoreCase	= True
	oRE.Global		= True
	
	Dim sAllowed, sDenied
	sAllowed	= ConfigAllowedExtensions.Item( resourceType )
	sDenied		= ConfigDeniedExtensions.Item( resourceType )
	
	IsAllowedExt = True
	
	If sDenied <> "" Then
		oRE.Pattern	= sDenied
		IsAllowedExt	= Not oRE.Test( extension )
	End If 
	
	If IsAllowedExt And sAllowed <> "" Then
		oRE.Pattern		= sAllowed
		IsAllowedExt	= oRE.Test( extension )
	End If
	
	Set oRE	= Nothing
End Function
%>