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
 * File Name: sample04.lasso
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
		//-->
		</script>
	</head>
	<body>
		<h1>FCKeditor - Lasso - Sample 4</h1>
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
		<form action="sampleposteddata.lasso" method="post" target="_blank">
[//lasso
	include('../../fckeditor.lasso');
	var('basepath') = response_filepath->split('_samples')->get(1);

	var('myeditor') = fck_editor(
		-instancename='FCKeditor1',
		-basepath=$basepath,
		-initialvalue='This is some <strong>sample text</strong>. You are using <a href="http://www.fckeditor.net/">FCKeditor</a>.'
	);
	
	if(action_param('Skin'));
		$myeditor->config = array('SkinPath' = $basepath + 'editor/skins/' + action_param('Skin') + '/');
	/if;
	
	$myeditor->create;
]
			<br>
			<input type="submit" value="Submit">
		</form>
	</body>
</html>
