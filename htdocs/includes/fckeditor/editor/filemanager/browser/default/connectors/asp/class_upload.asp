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
 * File Name: class_upload.asp
 * 	These are the classes used to handle ASP upload without using third
 * 	part components (OCX/DLL).
 * 
 * File Authors:
 * 		NetRube (netrube@126.com)
-->
<%
'**********************************************
' File:		NetRube_Upload.asp
' Version:	NetRube Upload Class Version 2.1 Build 20050228
' Author:	NetRube
' Email:	NetRube@126.com
' Date:		02/28/2005
' Comments:	The code for the Upload.
'			This can free usage, but please
'			not to delete this copyright information.
'			If you have a modification version,
'			Please send out a duplicate to me.
'**********************************************
' 文件名:	NetRube_Upload.asp
' 版本:		NetRube Upload Class Version 2.1 Build 20050228
' 作者:		NetRube(网络乡巴佬)
' 电子邮件:	NetRube@126.com
' 日期:		2005年02月28日
' 声明:		文件上传类
'			本上传类可以自由使用，但请保留此版权声明信息
'			如果您对本上传类进行修改增强，
'			请发送一份给俺。
'**********************************************

Class NetRube_Upload

	Public	File, Form
	Private oSourceData
	Private nMaxSize, nErr, sAllowed, sDenied
	
	Private Sub Class_Initialize
		nErr		= 0
		nMaxSize	= 1048576
		
		Set File			= Server.CreateObject("Scripting.Dictionary")
		File.CompareMode	= 1
		Set Form			= Server.CreateObject("Scripting.Dictionary")
		Form.CompareMode	= 1
		
		Set oSourceData		= Server.CreateObject("ADODB.Stream")
		oSourceData.Type	= 1
		oSourceData.Mode	= 3
		oSourceData.Open
	End Sub
	
	Private Sub Class_Terminate
		Form.RemoveAll
		Set Form = Nothing
		File.RemoveAll
		Set File = Nothing
		
		oSourceData.Close
		Set oSourceData = Nothing
	End Sub
	
	Public Property Get Version
		Version = "NetRube Upload Class Version 1.0 Build 20041218"
	End Property

	Public Property Get ErrNum
		ErrNum	= nErr
	End Property
	
	Public Property Let MaxSize(nSize)
		nMaxSize	= nSize
	End Property
	
	Public Property Let Allowed(sExt)
		sAllowed	= sExt
	End Property
	
	Public Property Let Denied(sExt)
		sDenied	= sExt
	End Property

	Public Sub GetData
		Dim aCType
		aCType = Split(Request.ServerVariables("HTTP_CONTENT_TYPE"), ";")
		If aCType(0) <> "multipart/form-data" Then
			nErr = 1
			Exit Sub
		End If
		
		Dim nTotalSize
		nTotalSize	= Request.TotalBytes
		If nTotalSize < 1 Then
			nErr = 2
			Exit Sub
		End If
		If nMaxSize > 0 And nTotalSize > nMaxSize Then
			nErr = 3
			Exit Sub
		End If
		
		oSourceData.Write Request.BinaryRead(nTotalSize)
		oSourceData.Position = 0
		
		Dim oTotalData, oFormStream, sFormHeader, sFormName, bCrLf, nBoundLen, nFormStart, nFormEnd, nPosStart, nPosEnd, sBoundary
		
		oTotalData	= oSourceData.Read
		bCrLf		= ChrB(13) & ChrB(10)
		sBoundary	= MidB(oTotalData, 1, InStrB(1, oTotalData, bCrLf) - 1)
		nBoundLen	= LenB(sBoundary) + 2
		nFormStart	= nBoundLen
		
		Set oFormStream = Server.CreateObject("ADODB.Stream")
		
		Do While (nFormStart + 2) < nTotalSize
			nFormEnd	= InStrB(nFormStart, oTotalData, bCrLf & bCrLf) + 3
			
			With oFormStream
				.Type	= 1
				.Mode	= 3
				.Open
				oSourceData.Position = nFormStart
				oSourceData.CopyTo oFormStream, nFormEnd - nFormStart
				.Position	= 0
				.Type		= 2
				.CharSet	= "UTF-8"
				sFormHeader	= .ReadText
				.Close
			End With
			
			nFormStart	= InStrB(nFormEnd, oTotalData, sBoundary) - 1
			nPosStart	= InStr(22, sFormHeader, " name=", 1) + 7
			nPosEnd		= InStr(nPosStart, sFormHeader, """")
			sFormName	= Mid(sFormHeader, nPosStart, nPosEnd - nPosStart)
			
			If InStr(45, sFormHeader, " filename=", 1) > 0 Then
				Set File(sFormName)			= New NetRube_FileInfo
				File(sFormName).FormName	= sFormName
				File(sFormName).Start		= nFormEnd
				File(sFormName).Size		= nFormStart - nFormEnd - 2
				nPosStart					= InStr(nPosEnd, sFormHeader, " filename=", 1) + 11
				nPosEnd						= InStr(nPosStart, sFormHeader, """")
				File(sFormName).ClientPath	= Mid(sFormHeader, nPosStart, nPosEnd - nPosStart)
				File(sFormName).Name		= Mid(File(sFormName).ClientPath, InStrRev(File(sFormName).ClientPath, "\") + 1)
				File(sFormName).Ext			= LCase(Mid(File(sFormName).Name, InStrRev(File(sFormName).Name, ".") + 1))
				nPosStart					= InStr(nPosEnd, sFormHeader, "Content-Type: ", 1) + 14
				nPosEnd						= InStr(nPosStart, sFormHeader, vbCr)
				File(sFormName).MIME		= Mid(sFormHeader, nPosStart, nPosEnd - nPosStart)
			Else
				With oFormStream
					.Type	= 1
					.Mode	= 3
					.Open
					oSourceData.Position = nPosEnd
					oSourceData.CopyTo oFormStream, nFormStart - nFormEnd - 2
					.Position	= 0
					.Type		= 2
					.CharSet	= "UTF-8"
					Form(sFormName)	= .ReadText
					.Close
				End With
			End If
			
			nFormStart	= nFormStart + nBoundLen
		Loop
		
		oTotalData = ""
		Set oFormStream = Nothing
	End Sub

	Public Sub SaveAs(sItem, sFileName)
		If File(sItem).Size < 1 Then
			nErr = 2
			Exit Sub
		End If
		
		If Not IsAllowed(File(sItem).Ext) Then
			nErr = 4
			Exit Sub
		End If
		
		Dim oFileStream
		Set oFileStream = Server.CreateObject("ADODB.Stream")
		With oFileStream
			.Type		= 1
			.Mode		= 3
			.Open
			oSourceData.Position = File(sItem).Start
			oSourceData.CopyTo oFileStream, File(sItem).Size
			.Position	= 0
			.SaveToFile sFileName, 2
			.Close
		End With
		Set oFileStream = Nothing
	End Sub
	
	Private Function IsAllowed(sExt)
		Dim oRE
		Set oRE	= New RegExp
		oRE.IgnoreCase	= True
		oRE.Global		= True
		
		If sDenied = "" Then
			oRE.Pattern	= sAllowed
			IsAllowed	= (sAllowed = "") Or oRE.Test(sExt)
		Else
			oRE.Pattern	= sDenied
			IsAllowed	= Not oRE.Test(sExt)
		End If
		
		Set oRE	= Nothing
	End Function
End Class

Class NetRube_FileInfo
	Dim FormName, ClientPath, Path, Name, Ext, Content, Size, MIME, Start
End Class
%>