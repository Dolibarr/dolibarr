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

llxHeader('','Telephonie - Statistiques - Commerciaux');

/*
 *
 *
 *
 */


$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/index.php';
$head[$h][1] = "Global";
$h++;

if ($_GET["commid"])
{
  $comm = new User($db, $_GET["commid"]);
  $comm->fetch();

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/commercial.php?commid='.$comm->id;
  $head[$h][1] = $comm->fullname;
  $hselected = $h;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/commercialca.php?commid='.$comm->id;
  $head[$h][1] = "CA";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/lignes.php?commid='.$comm->id;
  $head[$h][1] = "Lignes";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/commercialpo.php?commid='.$comm->id;
  $head[$h][1] = "Prises d'ordres";
  $h++;

  dol_fiche_head($head, $hselected, "Commerciaux");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="30%" valign="top">';
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  
  print '<tr class="liste_titre"><td width="50%" valign="top">Nom</td><td align="center">Nb Lignes</td></tr>';
  
  $sql = "SELECT count(*) as cc,date_format(date_commande,'%m %Y')";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
  $sql .= " WHERE l.fk_commercial = ".$comm->id;
  $sql .= " AND date_commande IS NOT NULL";
  $sql .= " AND l.statut <> 7";
  $sql .= " GROUP BY date_format(date_commande,'%Y/%m') DESC";
 
 $result = $db->query($sql);
 if ($result)
   {
     $num = $db->num_rows();
     $i = 0;
     $datas = array();
     $legends = array();
     
     while ($i < $num)
       {
	 $row = $db->fetch_row($i);		 
	 $var=!$var;
	 
	 print "<tr $bc[$var]>";	 
	 print '<td width="50%" valign="top">';
	 print $row[2]." ". $row[1].'</td><td align="center">'.$row[0].'</td></tr>';	 
	 $i++;
       }
     $db->free();
   }
 else 
   {
     print $db->error() . ' ' . $sql;
   }
 
 print '</table>';
 
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
