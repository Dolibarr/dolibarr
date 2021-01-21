<?php
/* Copyright (C) 2006-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Alexandre Spangaro	<aspangaro@open-dsi.fr>
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
 * or see https://www.gnu.org/
 */


/**
 *	    \file       htdocs/core/lib/doleditor.lib.php
 *		\brief      Ensemble de fonctions de base pour la gestion des utilisaterus et groupes
 */

/**
 * 	Show list of ckeditor's themes.
 *
 * 	@param	User|null	$fuser				User concerned or null for global theme
 * 	@param	int			$edit				1 to add edit form
 * 	@return	void
 */
function show_skin($fuser, $edit = 0)
{
	global $conf, $langs, $db;
	global $bc;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

	$formother = new FormOther($db);

	$dirskins = array('/includes/ckeditor/ckeditor/skins');
	if (!empty($conf->modules_parts['theme']))		// Using this feature slow down application
	{
		foreach ($conf->modules_parts['theme'] as $reldir)
		{
			$dirskins = array_merge($dirskins, (array) ($reldir.'theme'));
		}
	}
	$dirskins = array_unique($dirskins);
	// Now dir_themes=array('/themes') or dir_themes=array('/theme','/mymodule/theme')

	$selected_theme = '';
	if (empty($conf->global->FCKEDITOR_SKIN)) $selected_theme = 'moono-lisa';
	else $selected_theme = $conf->global->FCKEDITOR_SKIN;

	$colspan = 2;

	$thumbsbyrow = 6;
	print '<table class="noborder centpercent">';

	$var = false;

	// Title
   	print '<tr class="liste_titre"><th width="35%">'.$langs->trans("DefaultSkin").'</th>';
   	print '<th class="right">';
   	$url = 'http://ckeditor.com/addons/skins/all';
   	/*print '<a href="'.$url.'" target="_blank">';
   	print $langs->trans('DownloadMoreSkins');
   	print '</a>';*/
   	print '</th></tr>';

	print '<tr class="oddeven">';
   	print '<td>'.$langs->trans("ThemeDir").'</td>';
   	print '<td>';
   	foreach ($dirskins as $dirskin)
   	{
   		echo '"'.$dirskin.'" ';
   	}
   	print '</td>';
   	print '</tr>';

	//
	print '<tr class="oddeven"><td colspan="'.$colspan.'">';

	print '<table class="nobordernopadding" width="100%"><tr><td><div class="center">';

	$i = 0;
	foreach ($dirskins as $dir)
	{
		//print $dirroot.$dir;exit;
		$dirskin = dol_buildpath($dir, 0); // This include loop on $conf->file->dol_document_root
		$urltheme = dol_buildpath($dir, 1);

		if (is_dir($dirskin))
		{
			$handle = opendir($dirskin);
			if (is_resource($handle))
			{
				while (($subdir = readdir($handle)) !== false)
				{
					if (is_dir($dirskin."/".$subdir) && substr($subdir, 0, 1) <> '.'
							&& substr($subdir, 0, 3) <> 'CVS' && !preg_match('/common|phones/i', $subdir))
					{
						// Disable not stable themes (dir ends with _exp or _dev)
						if ($conf->global->MAIN_FEATURES_LEVEL < 2 && preg_match('/_dev$/i', $subdir)) continue;
						if ($conf->global->MAIN_FEATURES_LEVEL < 1 && preg_match('/_exp$/i', $subdir)) continue;

						print '<div class="inline-block" style="margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;">';
						if ($subdir == $selected_theme)
						{
							print '<input '.($edit ? '' : 'disabled').' type="radio" '.$bc[$var].' style="border: 0px;" checked name="fckeditor_skin" value="'.$subdir.'"> <b>'.$subdir.'</b>';
						} else {
							print '<input '.($edit ? '' : 'disabled').' type="radio" '.$bc[$var].' style="border: 0px;" name="fckeditor_skin" value="'.$subdir.'"> '.$subdir;
						}
						print '</div>';

						$i++;
					}
				}
			}
		}
	}

	print '</div></td></tr></table>';

	print '</td></tr>';

	print '</table>';
}
