<?php
/* Copyright (C) 2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2003-2010 Frederico Caldeira Knabben
 *
 * Source modified from part of fckeditor (http://www.fckeditor.net)
 * retrieved as GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

define('NOTOKENRENEWAL', 1); // Disables token renewal

// Load Dolibarr environment
require '../../../../main.inc.php';
/**
 * @var Conf $conf
 */

top_httphead();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<!--
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2010 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    https://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    https://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * This page shows the actual folder path.
-->
<html>
	<head>
		<title>Folder path</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
print '<!-- Includes CSS for Dolibarr theme -->'."\n";
// Output style sheets (optioncss='print' or ''). Note: $conf->css looks like '/theme/eldy/style.css.php'
$themepath = dol_buildpath($conf->css, 1);
$themesubdir = '';
if (!empty($conf->modules_parts['theme'])) {	// This slow down
	foreach ($conf->modules_parts['theme'] as $reldir) {
		if (file_exists(dol_buildpath($reldir.$conf->css, 0))) {
			$themepath = dol_buildpath($reldir.$conf->css, 1);
			$themesubdir = $reldir;
			break;
		}
	}
}

//print 'themepath='.$themepath.' themeparam='.$themeparam;exit;
print '<link rel="stylesheet" type="text/css" href="'.$themepath.'">'."\n";
?>
		<link href="browser.css" type="text/css" rel="stylesheet">
		<script type="text/javascript">
// Automatically detect the correct document.domain (#1919).
(function()
{
	var d = document.domain ;

	while ( true )
	{
		// Test if we can access a parent property.
		try
		{
			var test = window.top.opener.document.domain ;
			break ;
		}
		catch( e )
		{}

		// Remove a domain part: www.mytest.example.com => mytest.example.com => example.com ...
		d = d.replace( /.*?(?:\.|$)/, '' );

		if ( d.length == 0 )
			break ;		// It was not able to detect the domain.

		try
		{
			document.domain = d ;
		}
		catch (e)
		{
			break ;
		}
	}
})();

function SetCurrentFolder( resourceType, folderPath )
{
	document.getElementById('tdName').innerHTML = folderPath ;
}

window.onload = function()
{
	window.top.IsLoadedActualFolder = true ;
}

		</script>
	</head>
	<body>
		<table class="fullHeight" cellSpacing="0" cellPadding="0" width="100%" border="0">
			<tr>
				<td>
					<button style="WIDTH: 100%" type="button">
						<table cellSpacing="0" cellPadding="0" width="100%" border="0">
							<tr>
								<td><?php echo img_picto_common('', 'treemenu/folder.gif', 'width="16" height="16"'); ?></td>
								<td>&nbsp;</td>
								<td id="tdName" width="100%" class="ActualFolder nowrap">/</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
						</table>
					</button>
				</td>
			</tr>
		</table>
	</body>
</html>
