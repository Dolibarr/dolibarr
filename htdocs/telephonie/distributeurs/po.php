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

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/remuneration.php?id='.$distri->id;
  $head[$h][1] = "Rémunérations";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/po.php?id='.$distri->id;
  $head[$h][1] = "Prises d'ordre";
  $hselected = $h;
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/stats.php?id='.$distri->id;
  $head[$h][1] = "Statistiques";
  $h++;

  dol_fiche_head($head, $hselected, "Distributeur");
  
  if ($page == -1) { $page = 0 ; }

  $offset = $conf->liste_limit * $page ;
  $pageprev = $page - 1;
  $pagenext = $page + 1;
  
  /*
   * Mode Liste
   *
   */
  
  $sql = "SELECT s.rowid as socid, s.nom, p.fk_contrat, p.montant, p.avance_duree, p.avance_pourcent";
  $sql .= ", p.rem_pour_prev, p.rem_pour_autr, p.mode_paiement";
  $sql .= " , ".$db->pdate("p.datepo") . " as datepo";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
  $sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
  $sql .= " , ".MAIN_DB_PREFIX."societe as s";
  
  $sql .= " WHERE p.fk_distributeur =".$distri->id;
  $sql .= " AND c.fk_soc = s.rowid";
  $sql .= " AND p.fk_contrat = c.rowid";
  $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      $url_opt = "&amp;id=".$_GET["id"];      
      print_barre_liste("Prises d'ordre", $page, "po.php", $url_opt, $sortfield, $sortorder, '', $num);
      
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre">';
      print_liste_field_titre("Client","po.php","s.nom","","&amp;id=".$_GET["id"]);
      print_liste_field_titre("Contrat","po.php","p.fk_contrat","","&amp;id=".$_GET["id"]);
      print '<td align="center">Date</td>';
      print '<td align="right">Montant</td>';
      print '<td align="center">Avance Durée</td><td align="center">Avance %</td>';
      print '<td align="center">Rem %</td><td align="center">MdP</td>';
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
	  print '<td><a href="'.DOL_URL_ROOT.'/telephonie/contrat/fiche.php?id='.$obj->fk_contrat.'">'.$obj->fk_contrat."</a></td>\n";
	  print '<td align="center">'.strftime("%e %b %Y",$obj->datepo)."</td>\n";

	  print '<td align="right">'.sprintf("%01.2f",$obj->montant)."</td>\n";
	  
	  print '<td align="center">'.$obj->avance_duree."</td>\n";
	  print '<td align="center">'.$obj->avance_pourcent." %</td>\n";
	  if ($obj->mode_paiement == 'pre')
	    {
	      print '<td align="center">'.$obj->rem_pour_prev." %</td>\n";
	      print '<td align="center">Prelev</td>';
	    }
	  else
	    {
	      print '<td align="center">'.$obj->rem_pour_autr." %</td>\n";
	      print '<td align="center">Autre</td>';
	    }


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
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
