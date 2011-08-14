<?php
/* Copyright (C) 2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2003-2010 Frederico Caldeira Knabben
 *
 * Source modified from part of fckeditor (http://www.fckeditor.net)
 * retreived as GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

require('../../../../main.inc.php'); ?>
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
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * This page shows the list of available resource types.
-->
<html>
	<head>
		<title>Available types</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link href="browser.css" type="text/css" rel="stylesheet">
		<script type="text/javascript" src="js/common.js"></script>
		<script type="text/javascript">

function SetResourceType( type )
{
	window.parent.frames["frmFolders"].SetResourceType( type ) ;
}

var aTypes = [
	['File','File'],
	['Image','Image'],
	['Flash','Flash'],
	['Media','Media']
] ;

window.onload = function()
{
	var oCombo = document.getElementById('cmbType') ;
	oCombo.innerHTML = '' ;
	for ( var i = 0 ; i < aTypes.length ; i++ )
	{
		if ( oConnector.ShowAllTypes || aTypes[i][0] == oConnector.ResourceType )
			AddSelectOption( oCombo, aTypes[i][1], aTypes[i][0] ) ;
	}
}

		</script>
	</head>
	<body>
		<table class="fullHeight" cellSpacing="0" cellPadding="0" width="100%" border="0">
			<tr>
				<td nowrap>
					Resource Type<BR>
					<select id="cmbType" style="WIDTH: 100%" onchange="SetResourceType(this.value);">
						<option>&nbsp;
					</select>
				</td>
			</tr>
		</table>
	</body>
</html>
