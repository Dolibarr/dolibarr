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
 * File Name: io.asp
 * 	This file include IO specific functions used by the ASP Connector.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
-->
<%
Function GetUrlFromPath( resourceType, folderPath )
	If resourceType = "" Then
		GetUrlFromPath = RemoveFromEnd( sUserFilesPath, "/" ) & folderPath
	Else
		GetUrlFromPath = sUserFilesPath & resourceType & folderPath
	End If
End Function

Function RemoveExtension( fileName )
	RemoveExtension = Left( fileName, InStrRev( fileName, "." ) - 1 )
End Function

Function ServerMapFolder( resourceType, folderPath )
	' Get the resource type directory.
	Dim sResourceTypePath
	sResourceTypePath = sUserFilesDirectory & resourceType & "\"
	
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