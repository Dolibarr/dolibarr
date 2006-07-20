<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
 * File Name: sampleposteddata.asp
 * 	This page lists the data posted by a form.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
-->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>FCKeditor - Samples - Posted Data</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />
	<link href="../sample.css" rel="stylesheet" type="text/css" />
</head>
<body>
	<h1>
		FCKeditor - Samples - Posted Data</h1>
	<div>
		This page lists all data posted by the form.
	</div>
	<hr />
	<table width="100%" border="1" cellpadding="3" style="border-color: #999999; border-collapse: collapse;">
		<tr style="font-weight: bold; color: #dddddd; background-color: #999999">
			<td style="white-space: nowrap;">
				Field Name&nbsp;&nbsp;</td>
			<td>
				Value</td>
		</tr>
		<% For Each sForm in Request.Form %>
		<tr>
			<td valign="top" style="white-space: nowrap;">
				<b>
					<%=sForm%>
				</b>
			</td>
			<td style="width: 100%;">
				<pre><%=ModifyForOutput( Request.Form(sForm) )%></pre>
			</td>
		</tr>
		<% Next %>
	</table>
</body>
</html>
<%

' This function is useful only for this sample page se whe can display the
' posted data accordingly. This processing is usually not done on real
' applications, where the posted data must be saved on a DB or file. In those
' cases, no processing must be done, and the data is saved as posted.
Function ModifyForOutput( value )

	ModifyForOutput = Server.HTMLEncode( Request.Form(sForm) )

End Function

%>
