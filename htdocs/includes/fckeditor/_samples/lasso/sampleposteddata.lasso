[//lasso
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
 * File Name: sampleposteddata.lasso
 * 	Sample page.
 * 
 * File Authors:
 * 		Jason Huck (jason.huck@corefive.com)
 * 		Jim Michaels (jmichae3@yahoo.com)
 */
]
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>FCKeditor - Samples - Posted Data</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="robots" content="noindex, nofollow">
		<link href="../sample.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<h1>FCKeditor - Samples - Posted Data</h1>
		This page lists all data posted by the form.
		<hr>
		<table width="100%" border="1" cellspacing="0" bordercolor="#999999">
			<tr style="FONT-WEIGHT: bold; COLOR: #dddddd; BACKGROUND-COLOR: #999999">
				<td nowrap>Field Name&nbsp;&nbsp;</td>
				<td>Value</td>
			</tr>
[iterate(client_postparams, local('this'))]
			<tr>
				<td valign="top" nowrap><b>[#this->first]</b></td>
				<td width="100%">[#this->second]</td>
			</tr>
[/iterate]
		</table>
	</body>
</html>
