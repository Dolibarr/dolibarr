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
 * File Name: sample03.lasso
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
		<script type="text/javascript">
		<!--
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
		//-->
		</script>
	</head>
	<body>
		<h1>FCKeditor - Lasso - Sample 3</h1>
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
					</select>
				</td>
			</tr>
		</table>
		<br>
		<form action="sampleposteddata.lasso" method="post" target="_blank">
[//lasso
	include('../../fckeditor.lasso');
	var('basepath') = response_filepath->split('_samples')->get(1);

	var('myeditor') = fck_editor(
		-instancename='FCKeditor1',
		-basepath=$basepath,
		-initialvalue='This is some <strong>sample text</strong>. You are using <a href="http://www.fckeditor.net/">FCKeditor</a>.'
	);

	if(action_param('Toolbar'));
		$myeditor->toolbarset = action_param('Toolbar');
	/if;
	
	$myeditor->create;
]
			<br>
			<input type="submit" value="Submit">
		</form>
	</body>
</html>
