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
 * File Name: commands.asp
 * 	This file include the functions that handle the Command requests
 * 	in the ASP Connector.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (www.fckeditor.net)
-->
<%
Sub GetFolders( resourceType, currentFolder )
	' Map the virtual path to the local server path.
	Dim sServerDir
	sServerDir = ServerMapFolder( resourceType, currentFolder )

	' Open the "Folders" node.
	Response.Write "<Folders>"

	Dim oFSO, oCurrentFolder, oFolders, oFolder
	Set oFSO = Server.CreateObject( "Scripting.FileSystemObject" )
	Set oCurrentFolder = oFSO.GetFolder( sServerDir )
	Set oFolders = oCurrentFolder.SubFolders

	For Each oFolder in oFolders
		Response.Write "<Folder name=""" & ConvertToXmlAttribute( oFolder.name ) & """ />"
	Next
	
	Set oFSO = Nothing
	
	' Close the "Folders" node.
	Response.Write "</Folders>"
End Sub

Sub GetFoldersAndFiles( resourceType, currentFolder )
	' Map the virtual path to the local server path.
	Dim sServerDir
	sServerDir = ServerMapFolder( resourceType, currentFolder )

	Dim oFSO, oCurrentFolder, oFolders, oFolder, oFiles, oFile
	Set oFSO = Server.CreateObject( "Scripting.FileSystemObject" )
	Set oCurrentFolder = oFSO.GetFolder( sServerDir )
	Set oFolders	= oCurrentFolder.SubFolders
	Set oFiles		= oCurrentFolder.Files
	
	' Open the "Folders" node.
	Response.Write "<Folders>"
	
	For Each oFolder in oFolders
		Response.Write "<Folder name=""" & ConvertToXmlAttribute( oFolder.name ) & """ />"
	Next
	
	' Close the "Folders" node.
	Response.Write "</Folders>"
		
	' Open the "Files" node.
	Response.Write "<Files>"
	
	For Each oFile in oFiles
		Dim iFileSize
		iFileSize = Round( oFile.size / 1024 )
		If ( iFileSize < 1 AND oFile.size <> 0 ) Then iFileSize = 1
		
		Response.Write "<File name=""" & ConvertToXmlAttribute( oFile.name ) & """ size=""" & iFileSize & """ />"
	Next
	
	' Close the "Files" node.
	Response.Write "</Files>"
End Sub

Sub CreateFolder( resourceType, currentFolder )
	Dim sErrorNumber

	Dim sNewFolderName
	sNewFolderName = Request.QueryString( "NewFolderName" )

	If ( sNewFolderName = "" OR InStr( 1, sNewFolderName, ".." ) > 0  ) Then
		sErrorNumber = "102"
	Else
		' Map the virtual path to the local server path of the current folder.
		Dim sServerDir
		sServerDir = ServerMapFolder( resourceType, currentFolder & "/" & sNewFolderName )
		
		On Error Resume Next

		CreateServerFolder sServerDir
		
		Dim iErrNumber, sErrDescription
		iErrNumber		= err.number
		sErrDescription	= err.Description
		
		On Error Goto 0
		
		Select Case iErrNumber
			Case 0
				sErrorNumber = "0"
			Case 52
				sErrorNumber = "102"	' Invalid Folder Name.
			Case 70
				sErrorNumber = "103"	' Security Error.
			Case 76
				sErrorNumber = "102"	' Path too long.
			Case Else
				sErrorNumber = "110"
		End Select
	End If

	' Create the "Error" node.
	Response.Write "<Error number=""" & sErrorNumber & """ originalNumber=""" & iErrNumber & """ originalDescription=""" & ConvertToXmlAttribute( sErrDescription ) & """ />"
End Sub

Sub FileUpload( resourceType, currentFolder )
	Dim oUploader
	Set oUploader = New NetRube_Upload
	oUploader.MaxSize	= 0
	oUploader.Allowed	= ConfigAllowedExtensions.Item( resourceType )
	oUploader.Denied	= ConfigDeniedExtensions.Item( resourceType )
	oUploader.GetData

	Dim sErrorNumber
	sErrorNumber = "0"
	
	Dim sFileName, sOriginalFileName, sExtension
	sFileName = ""

	If oUploader.ErrNum > 1 Then
		sErrorNumber = "202"
	Else
		' Map the virtual path to the local server path.
		Dim sServerDir
		sServerDir = ServerMapFolder( resourceType, currentFolder )

		Dim oFSO
		Set oFSO = Server.CreateObject( "Scripting.FileSystemObject" )
	
		' Get the uploaded file name.
		sFileName	= oUploader.File( "NewFile" ).Name
		sExtension	= oUploader.File( "NewFile" ).Ext
		sOriginalFileName = sFileName

		Dim iCounter
		iCounter = 0

		Do While ( True )
			Dim sFilePath
			sFilePath = sServerDir & sFileName

			If ( oFSO.FileExists( sFilePath ) ) Then
				iCounter = iCounter + 1
				sFileName = RemoveExtension( sOriginalFileName ) & "(" & iCounter & ")." & sExtension
				sErrorNumber = "201"
			Else
				oUploader.SaveAs "NewFile", sFilePath
				If oUploader.ErrNum > 0 Then sErrorNumber = "202"
				Exit Do
			End If
		Loop
	End If

	Set oUploader	= Nothing

	Response.Clear

	Response.Write "<script type=""text/javascript"">"
	Response.Write "window.parent.frames['frmUpload'].OnUploadCompleted(" & sErrorNumber & ",'" & Replace( sFileName, "'", "\'" ) & "') ;"
	Response.Write "</script>"

	Response.End
End Sub
%>