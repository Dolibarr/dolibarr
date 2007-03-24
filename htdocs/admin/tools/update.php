<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
		\file 		htdocs/admin/tools/update.php
		\brief      Page de mise a jour online de dolibarr
		\version    $Revision$
*/

require("./pre.inc.php");
include_once $dolibarr_main_document_root."/lib/databases/".$conf->db->type.".lib.php";

$langs->load("admin");

if (! $user->admin)
  accessforbidden();

if ($_GET["msg"]) $message='<div class="error">'.$_GET["msg"].'</div>';



/*
*	Actions
*/
if ($_POST["action"]=='update')
{


}


/*
* Affichage page
*/

llxHeader();

print_fiche_titre($langs->trans("Upgrade"),'','setup');
print '<br>';

print $langs->trans("Version").' : <b>'.DOL_VERSION.'</b><br>';
print '<br>';

print $langs->trans("Upgrade").'<br>';
print $langs->trans("FeatureNotYetAvailable");

print '<br>';
print '<br>';

print $langs->trans("AddExtensionThemeModuleOrOther").'<br>';
print $langs->trans("FeatureNotYetAvailable");

print '</form>';

llxFooter('$Date$ - $Revision$');
?>