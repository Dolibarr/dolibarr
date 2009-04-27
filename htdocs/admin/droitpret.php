<?php
/* Copyright (C) 2007 Patrick Raguin <patrick.raguin@gmail.com>
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
    	\file       htdocs/admin/droitpret.php
		\ingroup    pret
		\brief      Page d'administration/configuration du module DroitPret
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

$langs->load("admin");
$langs->load("categories");

if (!$user->admin)
  accessforbidden();


// positionne la variable pour le test d'affichage de l'icone

$var=True;


// Action mise a jour ou ajout d'une constante
if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{

	if (! dolibarr_set_const($db, $_POST["constname"],$_POST["constvalue"],$_POST["consttype"],0,isset($_POST["constnote"])?$_POST["constnote"]:'',$conf->entity))
	{
		print $db->error();
	}
	else
	{
        Header("Location: droitpret.php");
		exit;
	}
}


llxHeader();

/*
 * Interface de configuration de certaines variables de la partie adherent
 */


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("PretSetup"),$linkback,'setup');
print "<br>";


print_fiche_titre($langs->trans("MemberMainOptions"));
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";
$var=true;
$form = new Form($db);

// Categorie
$var=!$var;
print '<form action="droitpret.php" method="POST">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="DROITPRET_CAT">';
print '<input type="hidden" name="consttype" value="chaine">';
print "<tr $bc[$var] class=value><td>".$langs->trans("catActive").'</td><td>';

print $form->select_all_categories(2,$conf->global->DROITPRET_CAT,"constvalue");

print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Adresse destination
$var=!$var;
print '<form action="droitpret.php" method="POST">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="DROITPRET_MAIL">';
print '<input type="hidden" name="consttype" value="chaine">';
print "<tr $bc[$var] class=value><td>".$langs->trans("mailDroitPret").'</td>';

print '<td><input type="text" name="constvalue" value="'.$conf->global->DROITPRET_MAIL.'"></td>';

print '<td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

print '</table>';
print '<br>';







?>
