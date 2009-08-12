<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file 		htdocs/admin/tools/update.php
 *		\brief      Page to make a Dolibarr online upgrade
 *		\version    $Id$
 */

require("./pre.inc.php");
include_once $dolibarr_main_document_root."/lib/databases/".$conf->db->type.".lib.php";

$langs->load("admin");
$langs->load("other");

if (! $user->admin)
  accessforbidden();

if ($_GET["msg"]) $message='<div class="error">'.$_GET["msg"].'</div>';


$urldolibarr='http://www.dolibarr.org/downloads/cat_view/62-stable-versions';
$urldolibarrmodules='http://www.dolibarr.org/downloads/cat_view/65-modulesaddon';
$urldolibarrthemes='http://www.dolibarr.org/';
$dolibarrroot=eregi_replace('[\\\/]+$','',DOL_DOCUMENT_ROOT);
$dolibarrroot=eregi_replace('[^\\\/]+$','',$dolibarrroot);


/*
*	Actions
*/
if ($_POST["action"]=='update')
{


}


/*
* View
*/

$wikihelp='EN:Installation_/_Upgrade|FR:Installation_/_Mise_a_jour|ES:Instalaci&omodulon_/_Actualizaci&omodulon';
llxHeader($langs->trans("Upgrade"),'',$wikihelp);

print_fiche_titre($langs->trans("Upgrade"),'','setup');

print $langs->trans("CurrentVersion").' : <b>'.DOL_VERSION.'</b><br>';
print $langs->trans("LastStableVersion").' : <b>'.$langs->trans("FeatureNotYetAvailable").'</b><br>';
print '<br>';

print $langs->trans("Upgrade").'<br>';
print '<hr>';
print $langs->trans("ThisIsProcessToFollow").'<br>';
print '<b>'.$langs->trans("StepNb",1).'</b>: ';
$fullurl='<a href="'.$urldolibarr.'" target="_blank">'.$urldolibarr.'</a>';
print $langs->trans("DownloadPackageFromWebSite",$fullurl).'<br>';
print '<b>'.$langs->trans("StepNb",2).'</b>: ';
print $langs->trans("UnpackPackageInDolibarrRoot",$dolibarrroot).'<br>';
print '<b>'.$langs->trans("StepNb",3).'</b>: ';
print $langs->trans("RemoveLock",$dolibarrroot.'install.lock').'<br>';
print '<b>'.$langs->trans("StepNb",4).'</b>: ';
$fullurl='<a href="'.DOL_URL_ROOT.'/install'.'" target="_blank">'.DOL_URL_ROOT.'/install'.'</a>';
print $langs->trans("CallUpdatePage",$fullurl).'<br>';
print '<b>'.$langs->trans("StepNb",5).'</b>: ';
print $langs->trans("RestoreLock",$dolibarrroot.'install.lock').'<br>';

print '<br>';
print '<br>';

print $langs->trans("AddExtensionThemeModuleOrOther").'<br>';
print '<hr>';
print $langs->trans("ThisIsProcessToFollow").'<br>';
print '<b>'.$langs->trans("StepNb",1).'</b>: ';
$fullurl='<a href="'.$urldolibarrmodules.'" target="_blank">'.$urldolibarrmodules.'</a>';
print $langs->trans("DownloadPackageFromWebSite",$fullurl).'<br>';
print '<b>'.$langs->trans("StepNb",2).'</b>: ';
print $langs->trans("UnpackPackageInDolibarrRoot",$dolibarrroot).'<br>';
print '<b>'.$langs->trans("StepNb",3).'</b>: ';
print $langs->trans("SetupIsReadyForUse").'<br>';

print '</form>';

llxFooter('$Date$ - $Revision$');
?>