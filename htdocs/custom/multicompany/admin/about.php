<?php
/* Copyright (C) 2011-2018 Regis Houssin  <regis.houssin@inodbox.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		/multicompany/admin/about.php
 * 	\ingroup		multicompany
 * 	\brief		About Page
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory


// Libraries
dol_include_once('/multicompany/lib/multicompany.lib.php');
dol_include_once('/multicompany/lib/PHP_Markdown/markdown.php');

// Translations
$langs->loadLangs(array('admin', 'multicompany@multicompany'));

// Security check
if (empty($user->admin) || ! empty($user->entity)) {
	accessforbidden();
}

/*
 * View
 */

$help_url='EN:Module_MultiCompany|FR:Module_MultiSoci&eacute;t&eacute;';
llxHeader('', $langs->trans("Module5000Name"), $help_url);

// Subheader
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MultiCompanySetup"), $linkback, 'multicompany@multicompany',0,'multicompany_title');

// Configuration header
$head = multicompany_prepare_head();
dol_fiche_head($head, 'about', $langs->trans("Module5000Name"));

// About page goes here

$buffer = file_get_contents(dol_buildpath('/multicompany/README.md',0));
print Markdown($buffer);

print '<br>';

$url = 'https://www.inodbox.com/';
$link = '<a href="'.$url.'" target="_blank">iNodbox</a>';
print $langs->trans("MulticompanyMoreModules", $link).'<br><br>';
print '<a href="'.$url.'" target="_blank"><img border="0" width="180" src="'.dol_buildpath('/multicompany/img/inodbox.png',1).'"></a>';
print '<br><br><br>';

print '<a target="_blank" href="'.dol_buildpath('/multicompany/COPYING',1).'"><img src="'.dol_buildpath('/multicompany/img/gplv3.png',1).'"/></a>';

dol_fiche_end();

llxFooter();

$db->close();
