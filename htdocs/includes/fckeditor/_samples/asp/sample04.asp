<%@ CodePage=65001 Language="VBScript"%>
<% Option Explicit %>
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
 * File Name: sample04.asp
 * 	Sample page.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
-->
<% ' You must set "Enable Parent Paths" on your web site in order this relative include to work. %>
<!-- #INCLUDE file="../../fckeditor.asp" -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>FCKeditor - Sample</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="robots" content="noindex, nofollow">
		<link href="../sample.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript">

function FCKeditor_OnComplete( editorInstance )
{
	var oCombo = document.getElementById( 'cmbSkins' ) ;
	
	// Get the active skin.
	var sSkin = editorInstance.Config['SkinPath'] ;
	sSkin = sSkin.match( /[^\/]+(?=\/$)/g ) ;
	
	oCombo.value = sSkin ;
	oCombo.style.visibility = '' ;
}

function ChangeSkin( skinName )
{
	window.location.href = window.location.pathname + "?Skin=" + skinName ;
}

		</script>
	</head>
	<body>
		<h1>FCKeditor - ASP - Sample 4</h1>
		This sample shows how to change the editor skin.
		<hr>
		<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td>
					Select the skin to load:&nbsp;
				</td>
				<td>
					<select id="cmbSkins" onchange="ChangeSkin(this.value);" style="VISIBILITY: hidden">
						<option value="default" selected>Default</option>
						<option value="office2003">Office 2003</option>
						<option value="silver">Silver</option>
					</select>
				</td>
			</tr>
		</table>
		<br>
		<form action="sampleposteddata.asp" method="post" target="_blank">
<%
' Automatically calculates the editor base path based on the _samples directory.
' This is usefull only for these samples. A real application should use something like this:
' oFCKeditor.BasePath = '/fckeditor/' ;	// '/fckeditor/' is the default value.
Dim sBasePath
sBasePath = Request.ServerVariables("PATH_INFO")
sBasePath = Left( sBasePath, InStrRev( sBasePath, "/_samples" ) )

Dim oFCKeditor
Set oFCKeditor = New FCKeditor
oFCKeditor.BasePath = sBasePath

If Request.QueryString("Skin") <> "" Then
	oFCKeditor.Config("SkinPath") = sBasePath + "editor/skins/" & Server.HTMLEncode( Request.QueryString("Skin") ) + "/"
End If

oFCKeditor.Value = "This is some <strong>sample text</strong>. You are using <a href=""http://www.fckeditor.net/"">FCKeditor</a>."
oFCKeditor.Create "FCKeditor1"
%>
			<br>
			<input type="submit" value="Submit">
		</form>
	</body>
</html>