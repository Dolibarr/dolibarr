<?php
/* Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FI8TNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/theme/eldy/manifest.json.php
 *		\brief      File for The Web App
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

require_once __DIR__.'/../../main.inc.php';


top_httphead('text/json');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
	// For a text/json, we must set an Expires to avoid to have it forced to an expired value by the web server
	header('Expires: '.gmdate('D, d M Y H:i:s', dol_now('gmt') + 10800).' GMT');
} else {
	header('Cache-Control: no-cache');
}


$manifest = new stdClass();


$manifest->name = constant('DOL_APPLICATION_TITLE');
if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
	$manifest->name = $conf->global->MAIN_APPLICATION_TITLE;
}


$manifest->theme_color = !empty($conf->global->MAIN_MANIFEST_APPLI_THEME_COLOR) ? $conf->global->MAIN_MANIFEST_APPLI_THEME_COLOR : '#F05F40';
$manifest->background_color = !empty($conf->global->MAIN_MANIFEST_APPLI_BG_COLOR) ? $conf->global->MAIN_MANIFEST_APPLI_BG_COLOR : "#ffffff";
$manifest->display = "standalone";
$manifest->splash_pages = null;
$manifest->icons = array();

if (!empty($conf->global->MAIN_MANIFEST_APPLI_LOGO_URL)) {
	$icon = new stdClass();
	$icon->src = $conf->global->MAIN_MANIFEST_APPLI_LOGO_URL;
	if ($conf->global->MAIN_MANIFEST_APPLI_LOGO_URL_SIZE) {
		$icon->sizes = $conf->global->MAIN_MANIFEST_APPLI_LOGO_URL_SIZE."x".$conf->global->MAIN_MANIFEST_APPLI_LOGO_URL_SIZE;
	} else {
		$icon->sizes = "512x512";
	}
	$icon->type = "image/png";
	$manifest->icons[] = $icon;
} elseif (!empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED)) {
	if (!empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI)) {
		$iconRelativePath = 'logos/thumbs/'.$conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI;
		$iconPath = $conf->mycompany->dir_output.'/'.$iconRelativePath;
		if (is_readable($iconPath)) {
			$imgSize = getimagesize($iconPath);
			if (!empty($imgSize)) {
				$icon = new stdClass();
				$icon->src = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode($iconRelativePath);
				$icon->sizes = $imgSize[0]."x".$imgSize[1];
				$icon->type = "image/png";
				$manifest->icons[] = $icon;
			}
		}
	}

	if (!empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_SMALL)) {
		$iconRelativePath = 'logos/thumbs/'.$conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_SMALL;
		$iconPath = $conf->mycompany->dir_output.'/'.$iconRelativePath;
		if (is_readable($iconPath)) {
			$imgSize = getimagesize($iconPath);
			if ($imgSize) {
				$icon = new stdClass();
				$icon->src = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode($iconRelativePath);
				$icon->sizes = $imgSize[0]."x".$imgSize[1];
				$icon->type = "image/png";
				$manifest->icons[] = $icon;
			}
		}
	}

	if (!empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED)) {
		$iconRelativePath = 'logos/'.$conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED;
		$iconPath = $conf->mycompany->dir_output.'/'.$iconRelativePath;
		if (is_readable($iconPath)) {
			$imgSize = getimagesize($iconPath);
			if ($imgSize) {
				$icon = new stdClass();
				$icon->src = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode($iconRelativePath);
				$icon->sizes = $imgSize[0]."x".$imgSize[1];
				$icon->type = "image/png";
				$manifest->icons[] = $icon;
			}
		}
	}
}

// Add Dolibarr std icon
if (empty($manifest->icons)) {
	$icon = new stdClass();
	$icon->src = DOL_URL_ROOT.'/theme/dolibarr_256x256_color.png';
	$icon->sizes = "256x256";
	$icon->type = "image/png";
	$manifest->icons[] = $icon;
}


print json_encode($manifest);
