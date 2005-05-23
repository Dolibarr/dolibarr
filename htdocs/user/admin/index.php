<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!
	    \file       htdocs/admin/compta.php
        \ingroup    comptabilite
        \brief      Page de configuration du module comptabilité
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("users");
$langs->load("admin");

if (!$user->admin) accessforbidden();

if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
  if (! dolibarr_set_const($db, $_POST["constname"],$_POST["constvalue"],$typeconst[$_POST["consttype"]],0,isset($_POST["constnote"])?$_POST["constnote"]:''))
    {
      print $db->error();
    }
  else
    {
      Header("Location: index.php");
    }
}


llxHeader();

$form = new Form($db);
$typeconst=array('yesno','texte','chaine');

print_titre($langs->trans("UserSetup"));

print "<br>";

print '<table class="noborder" cellpadding="3" cellspacing="0" width=\"100%\">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("OtherOptions").'</td><td>&nbsp;</td><td>&nbsp;</td><td>'.$langs->trans("Description").'</td>';
print '<td>&nbsp;</td>';
print "</tr>\n";

print '<form action="index.php" method="POST">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="USER_PASSWORD_GENERATED">';
print '<input type="hidden" name="consttype" value="yesno">';

print "<tr $bc[$var] class=value><td>USER_PASSWORD_GENERATED</td><td>".USER_PASSWORD_GENERATED."</td>\n";

print '<td>';
$form->selectyesnonum('constvalue',USER_PASSWORD_GENERATED);
print '</td><td>';

print '<input type="text" size="40" name="constnote" value="'.stripslashes(nl2br($obj->note)).'">';
print '</td><td>';
print '<input type="submit" value="'.$langs->trans("Modify").'" name="button"> &nbsp; ';
print "</td></tr>\n";

print '</form>';


print "</table>\n";




	














llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
