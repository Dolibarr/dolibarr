<?php
/* Copyright (C) 2025 Laurent Destailleur <eldy@destailleur.fr>
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
 * @var Conf $conf
 */
'
@phan-var-force Conf $conf
';

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

// Define $nbmodulesnotautoenabled - TODO This code is at different places
$nbmodulesnotautoenabled = count($conf->modules);
$listofmodulesautoenabled = array('user', 'agenda', 'fckeditor', 'export', 'import');
foreach ($listofmodulesautoenabled as $moduleautoenable) {
	if (in_array($moduleautoenable, $conf->modules)) {
		$nbmodulesnotautoenabled--;
	}
}

// Check if company name is defined (first install)
if (!getDolGlobalString('MAIN_INFO_SOCIETE_NOM') || !getDolGlobalString('MAIN_INFO_SOCIETE_COUNTRY')) {
	header("Location: ".DOL_URL_ROOT."/admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete");
	exit;
}
if ($nbmodulesnotautoenabled < getDolGlobalInt('MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING', 1)) {	// If only autoenabled modules (property ->enabled_bydefault in modules) are activated
	header("Location: ".DOL_URL_ROOT."/admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete");
	exit;
}
