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

if ($user->distributeur_id && $user->responsable_distributeur_id == 0)
{
  $_GET["id"] = $user->id;
}

if ($user->responsable_distributeur_id > 0)
{
  if (!in_array($_GET["id"], $user->responsable_distributeur_commerciaux))
    {
      accessforbidden();
    }
}

llxHeader('','Telephonie - Distributeur - Commercial');

/*
 *
 */
$h = 0;
$year = strftime("%Y",time());

if ($_GET["id"] && $_GET["did"])
{
  $commercial = new User($db, $_GET["id"]);
  $commercial->fetch();

  $distri = new DistributeurTelephonie($db);
  $distri->fetch($_GET["did"]);

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/distributeur.php?id='.$distri->id;
  $head[$h][1] = $distri->nom;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/comm/commercial.php?id='.$_GET["id"].'&amp;did='.$_GET["did"];
  $head[$h][1] = $commercial->prenom ." ". $commercial->nom;
  $hselected = $h;
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/comm/ca.php?id='.$commercial->id.'&amp;did='.$_GET["did"];
  $head[$h][1] = "Chiffre d'affaire";
  $h++;
  /*
  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/stats.php?id='.$distri->id;
  $head[$h][1] = "Statistiques";
  $h++;
  */
  dol_fiche_head($head, $hselected, "Distributeur");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="50%" valign="top">';
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr class="liste_titre">';
  print '<td>Date</td><td align="right">'."Prise d'ordre mensuelle</td></tr>";

  $sql = "SELECT ".$db->pdate("p.datepo") . " as datepo, sum(p.montant)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
  $sql .= " WHERE p.fk_commercial =".$commercial->id;
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

  print '</td><td width="50%" valign="top">';

  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$commercial->id.'/po.'.$year.'.mensuel.png" alt="Nouveaux clients (moy)" title="Nouveaux clients (moy)"><br /><br />'."\n";

  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$commercial->id.'/clients.hebdomadaire.png" alt="Nouveaux clients" title="Nouveaux clients"><br /><br />'."\n";

  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$commercial->id.'/clientsmoyenne.hebdomadaire.png" alt="Nouveaux clients (moy)" title="Nouveaux clients (moy)"><br /><br />'."\n";

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
