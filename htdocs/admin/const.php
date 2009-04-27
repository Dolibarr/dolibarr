<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 \file       htdocs/admin/const.php
 \ingroup    setup
 \brief      Page d'administration/configuration des constantes autres
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin)
accessforbidden();



$typeconst=array('yesno','texte','chaine');

if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
	if (! dolibarr_set_const($db, $_POST["constname"],$_POST["constvalue"],$typeconst[$_POST["consttype"]],1,isset($_POST["constnote"])?$_POST["constnote"]:'',$_POST["entity"]));
	{
		print $db->error();
	}
}

if ($_GET["action"] == 'delete')
{
	if (! dolibarr_del_const($db, $_GET["rowid"],$_GET["entity"]));
	{
		print $db->error();
	}
}


llxHeader();

print_fiche_titre($langs->trans("OtherSetup"),'','setup');

print $langs->trans("ConstDesc")."<br>\n";
print "<br>\n";


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Note").'</td>';
if ($conf->multicompany->enabled) print '<td>'.$langs->trans("Entity").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";


$form = new Form($db);


# Affiche ligne d'ajout
$var=false;
print '<form action="const.php" method="POST">';
print '<input type="hidden" name="action" value="add">';

print "<tr $bc[$var] class=value><td><input type=\"text\" class=\"flat\" size=\"24\" name=\"constname\" value=\"\"></td>\n";
print '<td>';
print '<input type="text" class="flat" size="30" name="constvalue" value="">';
print '</td><td>';
print '<input type="text" class="flat" size="40" name="constnote" value="">';
print '</td>';
if ($conf->multicompany->enabled)
{
	print '<td>';
	print '<input type="text" class="flat" size="1" name="entity" value="'.$conf->entity.'">';
	print '</td>';
}
else
{
	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
}
print '<td align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Add").'" name="Button"><br>';
print "</td>\n";
print '</tr>';

print '</form>';


# Affiche lignes des constantes
if ($all==1){
	$sql = "SELECT rowid, name, value, note, entity ";
	$sql.= "FROM llx_const ";
	$sql.= "WHERE entity IN (0,".$conf->entity.") ";
	$sql.= "ORDER BY name ASC";
}else{
	$sql = "SELECT rowid, name, value, note, entity ";
	$sql.= "FROM llx_const ";
	$sql.= "WHERE visible = 1 ";
	$sql.= "AND entity IN (0,".$conf->entity.") ";
	$sql.= "ORDER BY name ASC";
}
dol_syslog("Const::listConstant sql=".$sql,LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	$var=false;

	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		$var=!$var;

		print '<form action="const.php" method="POST">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="rowid" value="'.$rowid.'">';
		print '<input type="hidden" name="constname" value="'.$obj->name.'">';

		print "<tr $bc[$var] class=value><td>$obj->name</td>\n";

		// Value
		print '<td>';
		print '<input type="text" class="flat" size="30" name="constvalue" value="'.stripslashes($obj->value).'">';
		print '</td><td>';

		// Note
		print '<input type="text" class="flat" size="40" name="constnote" value="'.stripslashes(nl2br($obj->note)).'">';
		print '</td>';
		
		// Entity
		if ($conf->multicompany->enabled)
		{
			print '<td>';
			print '<input type="text" class="flat" size="1" name="entity" value="'.$obj->entity.'">';
			print '</td>';
		}
		else
		{
			print '<input type="hidden" name="entity" value="'.$obj->entity.'">';
		}
		
		print '<td align="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" name="button"> &nbsp; ';
		print '<a href="const.php?rowid='.$obj->rowid.'&entity='.$obj->entity.'&action=delete">'.img_delete().'</a>';
		print "</td></tr>\n";

		print '</form>';
		$i++;
	}
}


print '</table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
