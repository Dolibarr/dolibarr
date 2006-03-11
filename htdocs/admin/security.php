<?php
/* Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/admin/security.php
        \ingroup    setup
        \brief      Page de configuration du module sécurité
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("users");
$langs->load("admin");

if (!$user->admin) accessforbidden();


/*
 * Actions
 */
if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
  if (! dolibarr_set_const($db, $_POST["constname"],$_POST["constvalue"],$typeconst[$_POST["consttype"]],0,isset($_POST["constnote"])?$_POST["constnote"]:''))
    {
      dolibarr_print_error($db);
    }
  else
    {
      Header("Location: index.php");
      exit;
    }
}




llxHeader();


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/perms.php";
$head[$h][1] = $langs->trans("DefaultRights");
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/security.php";
$head[$h][1] = $langs->trans("Passwords");
$hselected=$h;
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("Security"));


$var=false;

$form = new Form($db);
$typeconst=array('yesno','texte','chaine');

print '<table class="noborder" width=\"100%\">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<form action="index.php" method="POST">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="USER_PASSWORD_GENERATED">';
print '<input type="hidden" name="consttype" value="yesno">';

print '<tr '.$bc[$var].'><td>'.$langs->trans("GeneratePassword").'</td>';

print '<td>';
$form->selectyesnonum('constvalue',USER_PASSWORD_GENERATED);
print "</td></tr>\n";

print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

print '</form>';


print "</table>\n";

print '</div>';

llxFooter('$Date$ - $Revision$');

?>
