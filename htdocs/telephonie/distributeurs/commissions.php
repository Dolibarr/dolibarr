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

if ($user->distributeur_id)
{
  $_GET["id"] = $user->distributeur_id;
}

if ($_GET["id"])
{
  $distri = new DistributeurTelephonie($db);
  $distri->fetch($_GET["id"]);

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/distributeur.php?id='.$distri->id;
  $head[$h][1] = $distri->nom;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/contrat.php?id='.$distri->id;
  $head[$h][1] = "Contrat";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/commissions.php?id='.$distri->id;
  $head[$h][1] = "Rémunérations";
  $hselected = $h;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/remav.php?id='.$distri->id;
  $head[$h][1] = "Rém. avance";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/remconso.php?id='.$distri->id;
  $head[$h][1] = "Rém. conso";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/po.php?id='.$distri->id;
  $head[$h][1] = "Prises d'ordre";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/stats.php?id='.$distri->id;
  $head[$h][1] = "Statistiques";
  $h++;

  dol_fiche_head($head, $hselected, "Distributeur");


  /* Conso */
  $conso_total = 0;
  $consos = array();

  $sql = "SELECT c.date, sum(c.montant)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_conso as c";  
  $sql .= " WHERE c.fk_distributeur = ".$_GET["id"];
  $sql .= " AND c.annul = 0";
  $sql .= " GROUP BY c.date DESC";

  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);	
	  $consos[$row[0]] = $row[1];	  
	  $conso_total += $row[1];
	  $i++;
	}
      $db->free($resql);
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }


  /* Commission */
  $comm_total = 0;
  $commissions = array();

  $sql = "SELECT c.date, c.montant";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission as c";  
  $sql .= " WHERE c.fk_distributeur = ".$_GET["id"];
  $sql .= " ORDER BY c.date DESC";
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($row = $db->fetch_row($resql))
	{
	  $commissions[$row[0]] = $row[1];	  
	  $comm_total += $row[1];
	}
      $db->free($resql);
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr><td width="50%" valign="top">';
    
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>Date</td><td align="right">Rémunération</td>';
  print '</tr>';
  $var=1;
  print '<tr class="liste_titre">';
  print '<td>Total</td><td align="right">'.price($comm_total).' HT</td></tr>';

  $sql = "SELECT c.date, c.montant";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission as c";
  $sql .= " WHERE c.fk_distributeur = ".$_GET["id"];

  $sql .= " ORDER BY c.date DESC";
    
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      $total = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  
	  print '<td>'.substr($row[0], -2).'/'.substr($row[0],0,4).'</td>';
	  print '<td align="right">'.price($row[1]).' HT</td>';
	  /*
	  print '<td align="right">'.price($consos[$row[0]]).' HT</td>';
	  print '<td align="right">'.price($consos[$row[0]] - $row[1]).' HT</td>';
	  */
	  
	  $i++;
	}
      $db->free($resql);
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
