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
 * File Name: sample02.lasso
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
	var oCombo = document.getElementById( 'cmbLanguages' ) ;
	for ( code in editorInstance.Language.AvailableLanguages )
	{
		AddComboOption( oCombo, editorInstance.Language.AvailableLanguages[code] + ' (' + code + ')', code ) ;
	}
	oCombo.value = editorInstance.Language.ActiveLanguage.Code ;
}	

function AddComboOption(combo, optionText, optionValue)
{
	var oOption = document.createElement("OPTION") ;

	combo.options.add(oOption) ;

	oOption.innerHTML = optionText ;
	oOption.value     = optionValue ;
	
	return oOption ;
}

function ChangeLanguage( languageCode )
{
	window.location.href = window.location.pathname + "?Lang=" + languageCode ;
}
		//-->
		</script>
	</head>
	<body>
		<h1>FCKeditor - Lasso - Sample 2</h1>
		This sample shows the editor in all its available languages.
		<hr>
		<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td>
					Select a language:&nbsp;
				</td>
				<td>
					<select id="cmbLanguages" onchange="ChangeLanguage(this.value);">
					</select>
				</td>
			</tr>
		</table>
		<br>
		<form action="sampleposteddata.lasso" method="post" target="_blank">
[//lasso
	include('../../fckeditor.lasso');
	var('basepath') = response_filepath->split('_samples')->get(1);

	if(action_param('Lang'));
		var('config') = array(
			'AutoDetectLanguage' = 'false',
			'DefaultLanguage' = action_param('Lang')
		);
	else;
		var('config') = array(
			'AutoDetectLanguage' = 'true',
			'DefaultLanguage' = 'en'
		);
	/if;

	var('myeditor') = fck_editor(
		-instancename='FCKeditor1',
		-basepath=$basepath,
		-config=$config,
		-initialvalue='This is some <strong>sample text</strong>. You are using <a href="http://www.fckeditor.net/">FCKeditor</a>.'
	);
	
	$myeditor->create;
]
			<br>
			<input type="submit" value="Submit">
		</form>
	</body>
</html>
