<%@ CodePage=65001 Language="VBScript"%>
<%
Option Explicit
Response.Buffer = True
%>
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
 * File Name: upload.asp
 * 	This is the "File Uploader" for ASP.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (www.fckeditor.net)
-->
<!--#include file="config.asp"-->
<!--#include file="io.asp"-->
<!--#include file="class_upload.asp"-->
<%

' This is the function that sends the results of the uploading process.
Function SendResults( errorNumber, fileUrl, fileName, customMsg )
	Response.Write "<script type=""text/javascript"">"
	Response.Write "window.parent.OnUploadCompleted(" & errorNumber & ",""" & Replace( fileUrl, """", "\""" ) & """,""" & Replace( fileName, """", "\""" ) & """,""" & Replace( customMsg , """", "\""" ) & """) ;"
	Response.Write "</script>"
	Response.End
End Function

%>
<%

' Check if this uploader has been enabled.
If ( ConfigIsEnabled = False ) Then
	SendResults "1", "", "", "This file uploader is disabled. Please check the ""editor/filemanager/upload/asp/config.asp"" file"
End If

' The the file type (from the QueryString, by default 'File').
Dim resourceType
If ( Request.QueryString("Type") <> "" ) Then
	resourceType = Request.QueryString("Type")
Else
	resourceType = "File"
End If

' Create the Uploader object.
Dim oUploader
Set oUploader = New NetRube_Upload
oUploader.MaxSize	= 0
oUploader.Allowed	= ConfigAllowedExtensions.Item( resourceType )
oUploader.Denied	= ConfigDeniedExtensions.Item( resourceType )
oUploader.GetData

If oUploader.ErrNum > 1 Then
	SendResults "202", "", "", ""
Else
	Dim sFileName, sFileUrl, sErrorNumber, sOriginalFileName, sExtension
	sFileName		= ""
	sFileUrl		= ""
	sErrorNumber	= "0"

	' Map the virtual path to the local server path.
	Dim sServerDir
	sServerDir = Server.MapPath( ConfigUserFilesPath )
	If ( Right( sServerDir, 1 ) <> "\" ) Then
		sServerDir = sServerDir & "\"
	End If
	
	If ( ConfigUseFileType = True ) Then
		sServerDir = sServerDir & resourceType & "\"
	End If 

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
			If oUploader.ErrNum > 0 Then SendResults "202", "", "", ""
			Exit Do
		End If
	Loop

	If ( ConfigUseFileType = True ) Then
		sFileUrl = ConfigUserFilesPath & resourceType & "/" & sFileName
	Else
		sFileUrl = ConfigUserFilesPath & sFileName
	End If

	SendResults sErrorNumber, sFileUrl, sFileName, ""
	
End If

Set oUploader = Nothing
%>