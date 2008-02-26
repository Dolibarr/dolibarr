<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/comm/action/indexactions.php
        \ingroup    agenda
		\brief      Actions area
		\version    $Id$
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/client.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("agenda");

$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="a.datea";

// Sécurité accés client
if ($user->societe_id > 0) 
{
	$action = '';
	$socid = $user->societe_id;
}



/*
 * Actions
 */
if ($_GET["action"] == 'builddoc')
{
	$cat = new CommActionRapport($db, $_GET["month"], $_GET["year"]);
	$result=$cat->generate($_GET["id"]);
}

if ($action=='delete_action')
{
	$actioncomm = new ActionComm($db);
	$actioncomm->fetch($actionid);
	$result=$actioncomm->delete();
}



/*
 * Affichage liste
 */

llxHeader();

print_fiche_titre($langs->trans("ActionsArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

// Search actions
$var=false;
print '<table class="noborder" width="100%">';
print '<form method="post" action="'.DOL_URL_ROOT.'/comm/action/listactions.php">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAnAction").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Keyword").':</td><td><input type="text" class="flat" name="sf_ref" size="18"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print '</tr>';
print "</form></table><br>\n";


print '</td><td valign="top" width="70%" class="notopnoleftnoright">';

show_array_actions_to_do(0);

show_array_last_actions_done(5);

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
