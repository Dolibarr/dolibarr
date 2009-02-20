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

if ($user->distributeur_id)
{
  $_GET["id"] = $user->distributeur_id;
}

llxHeader();


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];
if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="p.datepo";

/*
 *
 *
 */

if ($_GET["id"])
{
  $h = 0;
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
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/remav.php?id='.$distri->id;
  $head[$h][1] = "Rém. avance";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/remconso.php?id='.$distri->id;
  $head[$h][1] = "Rém. conso";
  $hselected = $h;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/po.php?id='.$distri->id;
  $head[$h][1] = "Prises d'ordre";
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/stats.php?id='.$distri->id;
  $head[$h][1] = "Statistiques";
  $h++;

  dol_fiche_head($head, $hselected, "Distributeur");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr><td width="70%" valign="top">';  
  
  if ($page == -1) { $page = 0 ; }

  $offset = $conf->liste_limit * $page ;
  $pageprev = $page - 1;
  $pagenext = $page + 1;
  
  /*
   * Mode Liste
   *
   */

  if ($_GET["month"] > 0)
    {
      $datetime = mktime(12,12,12,substr($_GET["month"], -2), 1 , substr($_GET["month"],0,4));
    }
  else
    {
      $datetime = time();
    }

  $month = substr("00".strftime("%m", $datetime), -2);
  $year = strftime("%Y", $datetime);      
  $mois = strftime("%B %Y", $datetime);

  $sql = "SELECT s.rowid as socid, s.nom, a.fk_contrat, sum(a.montant) as montant";

  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_conso as a";
  $sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
  $sql .= " , ".MAIN_DB_PREFIX."societe as s";
  
  $sql .= " WHERE a.fk_distributeur =".$distri->id;
  $sql .= " AND a.fk_contrat = c.rowid"; 
  $sql .= " AND c.fk_soc = s.rowid";
  $sql .= " AND a.date ='".$year.$month."'";
  $sql .= " AND a.avance = 0";
  $sql .= " GROUP BY s.rowid";
  $sql .= " ORDER BY s.nom ASC";
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      print_barre_liste("Rémunération sur consommations pour $mois", $page, "po.php", "", $sortfield, $sortorder, '', $num);
      
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre"><td>Client</td>';
      print '<td align="right">Montant</td>';
      print "</tr>\n";
      
      $var=True;
      
      while ($i < min($num,$conf->liste_limit))
	{
	  $obj = $db->fetch_object($resql);
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";	  
	  print '<td><a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socid.'">';
	  print img_file();
	  print '</a>&nbsp;';      
	  print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socid.'">'.$obj->nom."</a></td>\n";
	  print '<td align="right">'.sprintf("%01.2f",$obj->montant)."</td>\n";	  
	  print "</tr>\n";
	  $i++;
	}
      print "</table>";
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }


  print '</td><td width="30%" valign="top">';


  $sql = "SELECT distinct(a.date)";

  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_conso as a";
  $sql .= " WHERE a.avance = 0";
  $sql .= " ORDER BY a.date DESC";
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre"><td>Mois</td>';
      print "</tr>\n";
      
      $var=True;
      
      while ( $obj = $db->fetch_object($resql))
	{
	  $var=!$var;
	  
	  print "<tr $bc[$var]><td>";
	  print '<a href="'.DOL_URL_ROOT.'/telephonie/distributeurs/remconso.php?id='.$_GET["id"];
	  print '&amp;month='.$obj->date.'">'.$obj->date."</a></td>\n";
	  print "</tr>\n";
	}
      print "</table>";
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }


  print '</td></tr></table>';  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
