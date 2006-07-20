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
 * File Name: sample01.lasso
 * 	Sample page.
 * 
 * File Authors:
 * 		Jason Huck (jason.huck@corefive.com)
 */
]
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>FCKeditor - Sample</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="robots" content="noindex, nofollow">
		<link href="../sample.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<h1>FCKeditor - Lasso - Sample 1</h1>
		This sample displays a normal HTML form with an FCKeditor with full features 
		enabled.
		<hr>
		<form action="sampleposteddata.lasso" method="post" target="_blank">
[//lasso
	include('../../fckeditor.lasso');
	var('basepath') = response_filepath->split('_samples')->get(1);

	var('myeditor') = fck_editor(
		-instancename='FCKeditor1',
		-basepath=$basepath,
		-initialvalue='This is some <strong>sample text</strong>. You are using <a href="http://www.fckeditor.net/">FCKeditor</a>.'
	);
	
	$myeditor->create;
]
			<br>
			<input type="submit" value="Submit">
		</form>
	</body>
</html>
