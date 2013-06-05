<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/opensurvey/admin/index.php
 *	\ingroup    opensurvey
 *	\brief      Setup page of opensurvey
 */

require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/fonctions.php");

// Security check
if (!$user->admin) accessforbidden();

$langs->load("admin");
$langs->load("other");


/*
 * View
 */

$langs->load("opensurvey");

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("OpenSurveyArea"),$linkback,'setup');
print '<br>';

echo $langs->trans("OpenSurveyNothingToSetup").'<br><br>'."\n";

// Link
print img_picto('','object_globe.png').' '.$langs->trans("PublicLinkToCreateSurvey").':<br>';

// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

$url=$urlwithouturlroot.dol_buildpath('/opensurvey/public/index.php',1);
$urllink='<a href="'.$url.'" target="_blank">'.$url.'</a>';
print $urllink;


llxFooter();

$db->close();
?>