<?php
/* Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
   \file       htdocs/admin/editeur.php
   \ingroup    editeur
   \brief      Page d'administration/configuration du module Editeur
   \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


// Action activation d'un sous module du module adherent
if ($_POST["action"] == 'set')
{
	$name = "EDITEUR_LIVRE_FORMAT_".time();
	$result=dolibarr_set_const($db, $name, $_POST["format"],'chaine',0,'',$conf->entity);
	if ($result < 0)
	{
		print $db->error();
	}
}

// Action desactivation d'un sous module
if ($_GET["action"] == 'unset')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name LIKE 'EDITEUR_LIVRE_FORMAT_%'";
	$sql.= " AND rowid='".$_GET["id"]."';";
	if ($db->query($sql))
	{
		Header("Location: editeur.php");
		exit;
	}
}


/*
 * View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Configuration du module Editeur"),$linkback,'setup');
print '<br>';

print '<form action="editeur.php" method="POST">';
print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';

print '<input type="hidden" name="action" value="set">';
print "<tr $bc[$var] class=value><td>".$langs->trans("Nouveau format").'</td><td>';
print '<input type="texte" name="format">';
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Add").'" name="Button">';
print "</td></tr>\n";

print '</table>';
print '</form>';
print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Formats").'</td>';
print '<td align="center" width="80">'.$langs->trans("Action").'</td>';
print "</tr>\n";
$var=true;


$sql = "SELECT rowid,value FROM ".MAIN_DB_PREFIX."const WHERE name LIKE 'EDITEUR_LIVRE_FORMAT_%'";
$result = $db->query($sql);

while ($obj = $db->fetch_object($result) )
{
  $var=!$var;

  print "<tr $bc[$var]><td>".nl2br($obj->value)."</td>\n";

  print '<td>';
  print '<a href="editeur.php?action=unset&id='.$obj->rowid.'">'.$langs->trans('Delete').'</a>';
  print "</td></tr>\n";
}

print '</table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
