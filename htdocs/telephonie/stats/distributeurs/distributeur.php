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
require DOL_DOCUMENT_ROOT.'/telephonie/distributeurtel.class.php';

if (!$user->rights->telephonie->lire) accessforbidden();

llxHeader('','Telephonie - Statistiques - Distributeur');

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
  $year = strftime("%Y",time());
  $distri = new DistributeurTelephonie($db);
  $distri->fetch($_GET["id"]);

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/distributeur.php?id='.$distri->id;
  $head[$h][1] = $distri->nom;
  $hselected = $h;
  $h++;

  dol_fiche_head($head, $hselected, "Distributeur");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="70%" valign="top">';

  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/'.$_GET["id"].'/po.month.png" alt="Prise d\'ordre" title="Prise d\'ordre"><br /><br />'."\n";

  print '</td><td valign="top" width="30%">';
  
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
      
      while ($row = $db->fetch_row($resql))
	{
	  $var=!$var;	  
	  print "<tr $bc[$var]><td>".$row[1].'</td>';  
	  print '<td align="right">'.price($row[0]).'</td></tr>';
	}
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table>';

  print '</td></tr><tr><td valign="top" width="70%">';
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/'.$_GET["id"].'/clients.hebdomadaire.png" alt="Nouveaux clients" title="Nouveaux clients"><br /><br />'."\n";
  print '</td><td>';

  print '</td></tr><tr><td valign="top" width="70%">';
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/'.$_GET["id"].'/resultat.mensuel.png" alt="Resultat" title="Resultat"><br /><br />'."\n";
  print '</td><td valign="top" width="30%">';
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Mois</td><td align="right">Resultat</td></tr>';
  
  $sql = "SELECT valeur,legend FROM ".MAIN_DB_PREFIX."telephonie_stats";  
  $sql .= " WHERE graph = 'distributeur.resultat.mensuel.".$_GET["id"]."'";
  $sql .= " ORDER BY legend DESC";  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      while ($row = $db->fetch_row($resql))
	{
	  $var=!$var;	  
	  print "<tr $bc[$var]><td>".$row[1].'</td>';  
	  print '<td align="right">'.price($row[0]).'</td></tr>';
	}
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table>';

  print '</td></tr><tr><td valign="top" width="70%">';
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/'.$_GET["id"].'/gain.mensuel.png" alt="Gain mensuel" title="Gain mensuel"><br /><br />'."\n";
  print '</td><td valign="top" width="30%">';
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Mois</td><td align="right">Gain</td></tr>';
  
  $sql = "SELECT valeur,legend FROM ".MAIN_DB_PREFIX."telephonie_stats";  
  $sql .= " WHERE graph = 'distributeur.gain.mensuel.".$_GET["id"]."'";
  $sql .= " ORDER BY legend DESC";  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      while ($row = $db->fetch_row($resql))
	{
	  $var=!$var;	  
	  print "<tr $bc[$var]><td>".$row[1].'</td>';  
	  print '<td align="right">'.price($row[0]).'</td></tr>';
	}
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table>';

  print '</td></tr><tr><td valign="top" width="70%">';
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/'.$_GET["id"].'/commission.mensuel.png" alt="Commission mensuelle" title="Commission mensuelle"><br /><br />'."\n";

  print '</td><td valign="top" width="30%">';
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Mois</td><td align="right">Commission</td></tr>';
  
  $sql = "SELECT valeur,legend FROM ".MAIN_DB_PREFIX."telephonie_stats";  
  $sql .= " WHERE graph = 'distributeur.commission.mensuel.".$_GET["id"]."'";
  $sql .= " ORDER BY legend DESC";  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      while ($row = $db->fetch_row($resql))
	{
	  $var=!$var;	  
	  print "<tr $bc[$var]><td>".$row[1].'</td>';  
	  print '<td align="right">'.price($row[0]).'</td></tr>';
	}
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table>';

  print '</td></tr><tr><td valign="top" width="70%">';
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/'.$_GET["id"].'/ca.mensuel.png" alt="CA" title="CA"><br /><br />'."\n";

  print '</td><td valign="top" width="30%">';
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Mois</td><td align="right">CA</td></tr>';
  
  $sql = "SELECT valeur,legend FROM ".MAIN_DB_PREFIX."telephonie_stats";  
  $sql .= " WHERE graph = 'distributeur.ca.mensuel.".$_GET["id"]."'";
  $sql .= " ORDER BY legend DESC";  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      while ($row = $db->fetch_row($resql))
	{
	  $var=!$var;	  
	  print "<tr $bc[$var]><td>".$row[1].'</td>';  
	  print '<td align="right">'.price($row[0]).'</td></tr>';
	}
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table>';



  print '</td></tr>';
  print '</table>';
 
 $db->close();
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
