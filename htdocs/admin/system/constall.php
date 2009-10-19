<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file 		htdocs/admin/system/constall.php
 *		\brief      Page d'info de toutes les constantes
 *		\version    $Id$
 */

require("./pre.inc.php");

$langs->load("admin");


if (!$user->admin)
  accessforbidden();


/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("SummaryConst"),'','setup');

print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Entity").'</td>';
print "</tr>\n";

$sql = "SELECT";
$sql.= " rowid";
$sql.= ", ".$db->decrypt('name',$conf->db->dolibarr_main_db_encryption,$conf->db->dolibarr_main_db_cryptkey)." as name";
$sql.= ", ".$db->decrypt('value',$conf->db->dolibarr_main_db_encryption,$conf->db->dolibarr_main_db_cryptkey)." as value";
$sql.= ", type";
$sql.= ", note";
$sql.= ", entity";
$sql.= " FROM ".MAIN_DB_PREFIX."const";
$sql.= " WHERE entity IN (0,".$conf->entity.")";
$sql.= " ORDER BY entity, name ASC";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($result);
      $var=!$var;

      print '<tr '.$bc[$var].'>';
      print '<td>'.$obj->name.'</td>'."\n";
      print '<td>'.$obj->value.'</td>'."\n";
      print '<td>'.$obj->entity.'</td>'."\n";
      print "</tr>\n";

      $i++;
    }
}
$var=!$var;


print '</table>';

$db->close();

llxFooter();
?>
