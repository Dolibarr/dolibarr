<?php 
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
 * File Name: sample03.php
 * 	Sample page.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

include("../../fckeditor.php") ;
?>
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
	var oCombo = document.getElementById( 'cmbToolbars' ) ;
	oCombo.value = editorInstance.ToolbarSet.Name ;
	oCombo.style.visibility = '' ;
}

function ChangeToolbar( toolbarName )
{
	window.location.href = window.location.pathname + "?Toolbar=" + toolbarName ;
}

		</script>
	</head>
	<body>
		<h1>FCKeditor - PHP - Sample 3</h1>
		This sample shows how to change the editor toolbar.
		<hr>
		<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td>
					Select the toolbar to load:&nbsp;
				</td>
				<td>
					<select id="cmbToolbars" onchange="ChangeToolbar(this.value);" style="VISIBILITY: hidden">
						<option value="Default" selected>Default</option>
						<option value="Basic">Basic</option>
						<option value="Dolibarr">Dolibarr</option>
					</select>
				</td>
			</tr>
		</table>
		<br>
		<form action="sampleposteddata.php" method="post" target="_blank">
<?php
// Automatically calculates the editor base path based on the _samples directory.
// This is usefull only for these samples. A real application should use something like this:
// $oFCKeditor->BasePath = '/fckeditor/' ;	// '/fckeditor/' is the default value.
$sBasePath = $_SERVER['PHP_SELF'] ;
$sBasePath = substr( $sBasePath, 0, strpos( $sBasePath, "_samples" ) ) ;

$oFCKeditor = new FCKeditor('FCKeditor1') ;
$oFCKeditor->BasePath = $sBasePath ;

if ( isset($_GET['Toolbar']) )
	$oFCKeditor->ToolbarSet = htmlspecialchars($_GET['Toolbar']);

$oFCKeditor->Value = 'This is some <strong>sample text</strong>. You are using <a href="http://www.fckeditor.net/">FCKeditor</a>.' ;
$oFCKeditor->Create() ;
?>
			<br>
			<input type="submit" value="Submit">
		</form>
	</body>
</html>