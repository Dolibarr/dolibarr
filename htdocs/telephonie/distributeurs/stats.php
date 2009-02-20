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

if ($user->distributeur_id && $user->responsable_distributeur_id == 0)
{
  accessforbidden();
}

if (!$user->rights->telephonie->lire) accessforbidden();

llxHeader('','Telephonie - Statistiques - Distributeur');

if ($user->distributeur_id)
{
  $_GET["id"] = $user->distributeur_id;
}

/*
 *
 *
 *
 */

if ($_GET["id"])
{
  $distri = new DistributeurTelephonie($db);
  $distri->fetch($_GET["id"]);

  $h = 0;
  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/distributeur.php?id='.$distri->id;
  $head[$h][1] = $distri->nom;
  $h++;

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
  $hselected = $h;
  $h++;

  dol_fiche_head($head, $hselected, "Distributeur");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="30%" valign="top">';
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>Mois</td><td align="right">Prise d\'ordre</td></tr>';
  
  $sql = "SELECT sum(p.montant), date_format(datepo, '%m-%Y')";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
  
  $sql .= " WHERE p.fk_distributeur = ".$_GET["id"];
  $sql .= " GROUP BY date_format(p.datepo, '%Y%m') DESC";
  
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
	  
	  print '<td>'.$row[1].'</td>';
	  
	  print '<td align="right">'.price($row[0]).'</td></tr>';
	  $i++;
	}
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table><br />';

 
  print '</td><td valign="top" width="70%">';

  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/'.$_GET["id"].'/po.month.png" alt="Prise d\'ordre" title="Prise d\'ordre"><br /><br />'."\n";

  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/'.$_GET["id"].'/clients.hebdomadaire.png" alt="Nouveaux clients" title="Nouveaux clients"><br /><br />'."\n";
  
  
  print '</td></tr>';
  print '</table>';
 
 $db->close();
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
