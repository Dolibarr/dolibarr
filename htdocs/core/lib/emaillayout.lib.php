<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024      Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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

/**
 *		\file       htdocs/core/lib/emaillayout.lib.php
 *		\brief      File for getting email html models
 */

/**
 * Get empty html
 *
 * @param	string	$name	Name of template
 * @return 	string  $out  	Html content
 */
function getHtmlOfLayout($name)
{
	global $conf, $mysoc, $user, $langs;

	$commonSubstitutionArray = getCommonSubstitutionArray($langs);

	$specificSubstitutionArray = array(
		'__LOGO_URL__' => !empty($mysoc->logo) && dol_is_file($conf->mycompany->dir_output.'/logos/'.$mysoc->logo) ? DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&file='.urlencode('logos/'.$mysoc->logo) : 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC',
		'__TITLEOFMAILHOLDER__' => $langs->trans('TitleOfMailHolder'),
		'__CONTENOFMAILHOLDER__' => $langs->trans('ContentOfMailHolder'),
		'__USERSIGNATURE__' => !empty($user->signature) ? dol_htmlentities($user->signature) : '',
		'__GRAY_RECTANGLE__' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC',
		'__LAST_NEWS__'   => $langs->trans('LastNews'),
	);

	$substitutionarray = array_merge($commonSubstitutionArray, $specificSubstitutionArray);

	$templatePath = DOL_DOCUMENT_ROOT . '/install/doctemplates/maillayout/';

	$templateFile = $templatePath . $name . '.html';
	if (file_exists($templateFile)) {
		$out = file_get_contents($templateFile);
	} else {
		$out = '';
	}

	$out = make_substitutions($out, $substitutionarray);

	return $out;
}
