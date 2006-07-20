<cfsetting enablecfoutputonly="true" showdebugoutput="false">
<!---
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
 * File Name: sample01.cfm
 * 	Sample page for ColdFusion.
 * 
 * File Authors:
 * 		Hendrik Kramer (hk@lwd.de)
 * 		Mark Woods (mark@thickpaddy.com)
 * 		Wim Lemmens (didgiman@gmail.com)
--->

<cfoutput>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title>FCKeditor - Sample</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="noindex, nofollow">
	<link href="../sample.css" rel="stylesheet" type="text/css" />
</head>

<body>

<h1>FCKeditor - ColdFusion - Sample 1</h1>
	
This sample displays a normal HTML form with a FCKeditor with full features enabled; invoked by a ColdFusion Custom Tag / Module.<br>
ColdFusion is a registered trademark and product of <a href="http://www.macromedia.com/software/coldfusion/" target="_blank">Macromedia, Inc</a>.
<hr>
<form method="POST" action="#cgi.script_name#">
</cfoutput>

<cfmodule 
	template="../../fckeditor.cfm"
	basePath="#Left(cgi.script_name, FindNoCase('_samples', cgi.script_name)-1)#"
	instanceName="myEditor"
	value='This is some sample text. You are using <a href="http://fckeditor.net/" target="_blank">FCKeditor</a>.'
	width="100%"
	height="200"
>
<cfoutput>
<br />
<input type="submit" value="Submit">
<br />
</form>
</cfoutput>

<cfif isDefined( 'FORM.fieldnames' )>
	<cfoutput>
	<style>
	<!--
		td, th { font: 11px Verdana, Arial, Helv, Helvetica, sans-serif; }
	-->
	</style>
	<table border="1" cellspacing="0" cellpadding="2" bordercolor="darkblue" bordercolordark="darkblue" bordercolorlight="darkblue">
	<tr>
		<th colspan="2" bgcolor="darkblue"><font color="white"><strong>Dump of FORM Variables</strong></font></th>
	</tr>
	<tr>
		<td bgcolor="lightskyblue">FieldNames</td>
		<td>#FORM.fieldNames#</td>
	</tr>
	<cfloop list="#FORM.fieldnames#" index="key">
	<tr>
		<td valign="top" bgcolor="lightskyblue">#key#</td>
		<td>#HTMLEditFormat(evaluate("FORM.#key#"))#</td>
	</tr>
	</cfloop>
	</table>
	</cfoutput>
</cfif>

</body>
</html>
<cfsetting enablecfoutputonly="false">