<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * $Id: prev.php,v 1.21 2011/07/31 22:23:13 eldy Exp $
 */

require('../../main.inc.php');

// Security check
if ($user->societe_id > 0)
{
  $socid = $user->societe_id;
}


function pt ($db, $sql, $title) {
  global $bc;
  global $langs,$conf;

  print '<table class="border" width="100%">';
  print '<tr class="liste_titre">';
  print "<td>$title</td>";
  print "<td align=\"right\">Montant</td>";
  print "</tr>\n";

  $result = $db->query($sql);
  if ($result)
    {
      $num = $db->num_rows($result);
      $i = 0; $total = 0 ;

      $var=True;
      while ($i < $num)
	{
	  $obj = $db->fetch_object($result);
	  $var=!$var;
	  print '<tr '.$bc[$var].'>';
	  print '<td>'.$obj->dm.'</td>';
	  print '<td align="right">'.price($obj->amount).'</td>';

	  print "</tr>\n";
	  $total = $total + $obj->amount;
	  $i++;
	}
      print "<tr class=\"total\"><td colspan=\"2\" align=\"right\"><b>".$langs->trans("TotalHT").": ".price($total)."</b> ".$langs->trans("Currency".$conf->monnaie)."</td></tr>";

      $db->free();
    }
  else
    {
      dol_print_error($db);

    }
  print "</table>";

}
/*
 *
 */

llxHeader();


if ($sortfield == "")
{
  $sortfield="lower(p.label)";
}
if ($sortorder == "")
{
  $sortorder="ASC";
}

$in = "(1,2,4)";

print_titre ("CA Pr�visionnel bas� sur les propositions <b>ouvertes</b> et <b>sign�es</b>");

print '<table width="100%">';

print '<tr><td valign="top">';

$sql = "SELECT sum(p.price) as amount, date_format(p.datep,'%Y-%m') as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."propal as p";
$sql.= " WHERE p.fk_statut in ".$in;
$sql.= " AND p.entity = ".$conf->entity;
if ($socid) $sql.= " AND p.fk_soc = ".$socid;
$sql.= " GROUP BY dm DESC";

pt($db, $sql, $langs->trans("Month"));

print '</td><td valign="top">';

$sql = "SELECT sum(p.price) as amount, year(p.datep) as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."propal as p";
$sql.= " WHERE p.fk_statut in ".$in;
$sql.= " AND p.entity = ".$conf->entity;
if ($socid) $sql.= " AND p.fk_soc = ".$socid;
$sql.= " GROUP BY dm DESC";

pt($db, $sql, "Ann�e");

print "<br>";

$sql = "SELECT sum(p.price) as amount, month(p.datep) as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."propal as p";
$sql.= " WHERE p.fk_statut in ".$in;
$sql.= " AND p.entity = ".$conf->entity;
if ($socid) $sql.= " AND p.fk_soc = ".$socid;
$sql.= " GROUP BY dm";

pt($db, $sql, "Mois cumul�s");

print "</td></tr></table>";

$db->close();


llxFooter("<em>Derni&egrave;re modification $Date: 2011/07/31 22:23:13 $ r&eacute;vision $Revision: 1.21 $</em>");
?>
