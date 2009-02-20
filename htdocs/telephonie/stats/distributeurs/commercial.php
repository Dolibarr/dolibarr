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

llxHeader('','Telephonie - Statistiques - Distributeurs');

/*
 *
 *
 *
 */

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/index.php';
$head[$h][1] = "Global";
$h++;

if ($_GET["id"])
{
  $comm = new User($db, $_GET["id"]);
  $comm->fetch();

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/commercial.php?id='.$comm->id;
  $head[$h][1] = $comm->fullname;
  $hselected = $h;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/commercialca.php?id='.$comm->id;
  $head[$h][1] = "CA";
  $h++;

  dol_fiche_head($head, $hselected, "Distributeurs");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="30%" valign="top">';
  
 
  /*
   *
   *
   */
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>Date</td><td align="right">Prise d\'ordre</td></tr>';
  
  $sql = "SELECT sum(p.montant), date_format(p.datepo, '%m/%Y')";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
  
  $sql .= " WHERE p.fk_commercial = ".$_GET["id"];
  $sql .= " GROUP BY date_format(p.datepo, '%y%m') DESC";
  
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
	  print '<td>'.$row[1].'</a></td>';
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
  
  
  /*
   *
   */
  print '</td><td valign="top" width="70%">';
  
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$comm->id.'/clients.hebdomadaire.png" alt="Commandes de lignes par semaine" title="Lignes Actives"><br /><br />'."\n";
  
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$comm->id.'/clientsmoyenne.hebdomadaire.png" alt="Commandes de lignes par semaine" title="Lignes Actives"><br /><br />'."\n";
  
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$comm->id.'/lignes.commandes.hebdomadaire.png" alt="Commandes de lignes par semaine" title="Lignes Actives"><br /><br />'."\n"; 
  
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$comm->id.'/lignes.commandes.mensuels.png" alt="Commandes de ligne par mois" title="Lignes Actives"><br /><br />'."\n";
      
  print '</td></tr>';
  print '</table>';
  
  $db->close();  
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
