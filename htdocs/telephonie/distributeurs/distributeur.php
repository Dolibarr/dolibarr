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
  $hselected = $h;
  $h++;

  if ($user->distributeur_id == 0 || $user->responsable_distributeur_id > 0)
    {
      $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/contrat.php?id='.$distri->id;
      $head[$h][1] = "Contrat";
      $h++;

      $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/remuneration.php?id='.$distri->id;
      $head[$h][1] = "Rémunérations";
      $h++;

      $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/po.php?id='.$distri->id;
      $head[$h][1] = "Prises d'ordre";
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/stats.php?id='.$distri->id;
      $head[$h][1] = "Statistiques";
      $h++;
    }

  dol_fiche_head($head, $hselected, "Distributeur");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="40%" valign="top">';
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Responsables</td></tr>';

  $sql = "SELECT u.rowid, u.firstname, u.name";
  $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
  $sql .= " , ".MAIN_DB_PREFIX."telephonie_distributeur_responsable as dc";  
  $sql .= " WHERE u.rowid = dc.fk_user ";
  $sql .= " AND dc.fk_distributeur = '".$_GET["id"]."'";
  $sql .= " ORDER BY u.name ASC;";
    
  $resql = $db->query($sql);
  
  if ($resql)
    {
      while ($row = $db->fetch_row($resql))
	{
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  print '<td>'.$row[1].' '.$row[2].'</td>';
	}
      $db->free($resql);
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table><br />';



  $sql = "SELECT u.rowid, u.firstname, u.name, u.email";
  $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
  $sql .= " , ".MAIN_DB_PREFIX."telephonie_distributeur_commerciaux as dc";  
  $sql .= " WHERE u.rowid = dc.fk_user ";
  $sql .= " AND dc.fk_distributeur = '".$_GET["id"]."'";
  $sql .= " ORDER BY u.name ASC;";
    
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows($resql);

      if ($num > 0 )
	{
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr class="liste_titre"><td>Commerciaux</td></tr>';
	  while ($row = $db->fetch_row($resql))
	    {
	      $var=!$var;
	      print "<tr $bc[$var]>";
	      print '<td><a href="./comm/commercial.php?id='.$row[0].'&amp;did='.$_GET["id"].'">';;
	      print $row[1].' '.$row[2].'</a>';
	      if ($row[3] && $user->distributeur_id == 0)
		{
		  print " &lt;".$row[3]."&gt;";
		}
	      print '</td>';
	    }
	  
	  print '</table><br />';
	}
      $db->free($resql);
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }

  if ($user->distributeur_id == 0 || $user->responsable_distributeur_id > 0)
    {

      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre"><td colspan="2">Total commission</td></tr>';
      
      $sql = "SELECT sum(c.montant)";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission as c";  
      $sql .= " WHERE c.fk_distributeur = ".$_GET["id"];
      
      $resql = $db->query($sql);
      
      if ($resql)
	{
	  $num = $db->num_rows($resql);
	  $i = 0;
	  $total = 0;
	  $var = 0;
	  while ($i < $num)
	    {
	      $row = $db->fetch_row($resql);	  
	      $var=!$var;	  
	      print "<tr $bc[$var]>";	  
	      print '<td>Total</td>';
	      print '<td align="right">'.price($row[0]).' HT</td>';
	      
	      $i++;
	    }
	  $db->free($resql);
	}
      else 
	{
	  print $db->error() . ' ' . $sql;
	}
      print '</table><br />';
    }

  if ($user->distributeur_id == 0 || $user->responsable_distributeur_id > 0)
    {

  print '</td><td valign="top" width="30%">';
  
  /* comm */



  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr class="liste_titre">';
  print '<td>Date</td><td align="right">Rémunérations</td></tr>';

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
	  
	  $i++;
	}
      $db->free($resql);
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table><br />';
    

  print '</td><td valign="top" width="30%">';
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr class="liste_titre">';
  print '<td>Date</td><td align="right">'."Prise d'ordre mensuelle</td></tr>";

  $sql = "SELECT ".$db->pdate("p.datepo") . " as datepo, sum(p.montant)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
  $sql .= " WHERE p.fk_distributeur =".$distri->id;
  $sql .= " GROUP BY date_format(datepo,'%Y%m') DESC";  

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
	  
	  print '<td>'.strftime("%m/%Y",$row[0]).'</td>';
	  print '<td align="right">'.price($row[1]).' HT</td>';
	  
	  $i++;
	}
      $db->free($resql);
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table><br />';
    }
  print '</td></tr>';
  print '</table></div>';

  /* ************************************************************************** */
  /*                                                                            */ 
  /* Barre d'action                                                             */ 
  /*                                                                            */ 
  /* ************************************************************************** */
  print "\n<div class=\"tabsAction\">\n";
  
  if ($_GET["action"] == '' && $user->admin)
    {
      print "<a class=\"butAction\" href=\"fiche.php?action=create_commercial&amp;distri=".$distri->id."\">".$langs->trans("Nouveau commercial")."</a>";
    }
  
  print "</div><br>";
 
  $db->close();
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
