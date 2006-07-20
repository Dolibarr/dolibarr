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
 * File Name: sample01.php
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
	</head>
	<body>
		<h1>FCKeditor - PHP - Sample 1</h1>
		This sample displays a normal HTML form with an FCKeditor with full features 
		enabled.
		<hr>
		<form action="sampleposteddata.php" method="post" target="_blank">
<?php
// Automatically calculates the editor base path based on the _samples directory.
// This is usefull only for these samples. A real application should use something like this:
// $oFCKeditor->BasePath = '/fckeditor/' ;	// '/fckeditor/' is the default value.
$sBasePath = $_SERVER['PHP_SELF'] ;
$sBasePath = substr( $sBasePath, 0, strpos( $sBasePath, "_samples" ) ) ;

$oFCKeditor = new FCKeditor('FCKeditor1') ;
$oFCKeditor->BasePath	= $sBasePath ;
$oFCKeditor->Value		= 'This is some <strong>sample text</strong>. You are using <a href="http://www.fckeditor.net/">FCKeditor</a>.' ;
$oFCKeditor->Create() ;
?>
			<br>
			<input type="submit" value="Submit">
		</form>
	</body>
</html>