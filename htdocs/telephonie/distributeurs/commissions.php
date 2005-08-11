<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */
require("./pre.inc.php");


if (!$user->rights->telephonie->lire) accessforbidden();

llxHeader('','Telephonie - Statistiques - Distributeur');

/*
 *
 */
$h = 0;

if ($_GET["id"])
{
  $distri = new DistributeurTelephonie($db);
  $distri->fetch($_GET["id"]);

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/distributeur.php?id='.$distri->id;
  $head[$h][1] = $distri->nom;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/commissions.php?id='.$distri->id;
  $head[$h][1] = "Commissions";
  $hselected = $h;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/po.php?id='.$distri->id;
  $head[$h][1] = "Prises d'ordre";
  $h++;

  dolibarr_fiche_head($head, $hselected, "Distributeur");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="50%" valign="top">';
  

 

  
  /* Commissions */

  $consos = array();

  $sql = "SELECT c.date, sum(c.montant)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_conso as c";
  
  $sql .= " WHERE c.fk_distributeur = ".$_GET["id"];

  $sql .= " GROUP BY c.date DESC";

  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows();
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($i);	
	  $consos[$row[0]] = $row[1];	  
	  $i++;
	}
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }


  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>Date</td><td>Versé</td><td>Consos</td><td>Solde</td></tr>';

  $sql = "SELECT c.date, c.montant";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission as c";
  
  $sql .= " WHERE c.fk_distributeur = ".$_GET["id"];

  $sql .= " ORDER BY c.date DESC";
    
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows();
      $i = 0;
      $total = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($i);	
	  
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  
	  print '<td>'.substr($row[0], -2).'/'.substr($row[0],0,4).'</td>';
	  print '<td>'.price($row[1]).' HT</td>';
	  print '<td>'.price($consos[$row[0]]).' HT</td>';
	  print '<td>'.price($consos[$row[0]] - $row[1]).' HT</td>';
	  
	  $i++;
	}
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table><br />';


  print '</td><td valign="top" width="50%">';

  print '</td></tr>';
  print '</table>';
 
 $db->close();
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
