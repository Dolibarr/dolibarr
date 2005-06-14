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


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="p.datepo";
}

/*
 * Recherche
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

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/po.php?id='.$distri->id;
  $head[$h][1] = "Prises d'ordre";
  $hselected = $h;
  $h++;
  
  dolibarr_fiche_head($head, $hselected, "Distributeur");
  
  
  if ($page == -1) { $page = 0 ; }

  $offset = $conf->liste_limit * $page ;
  $pageprev = $page - 1;
  $pagenext = $page + 1;
  
  /*
   * Mode Liste
   *
   */
  
  $sql = "SELECT s.idp, s.nom, p.fk_contrat, p.montant";
  $sql .= " , ".$db->pdate("p.datepo") . " as datepo";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
  $sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
  $sql .= " , ".MAIN_DB_PREFIX."societe as s";
  
  $sql .= " WHERE p.fk_distributeur =".$distri->id;
  
  $sql .= " AND c.fk_soc = s.idp";
  $sql .= " AND p.fk_contrat = c.rowid";

  $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      print_barre_liste("Prises d'ordre", $page, "po.php", "", $sortfield, $sortorder, '', $num);
      
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre">';
      print_liste_field_titre("Client","po.php","s.nom");
      
      print_liste_field_titre("Contrat","po.php","l.ligne");
      print '<td align="center">Date</td><td align="right">Montant</td>';
      print "</tr>\n";
      
      $var=True;
      
      while ($i < min($num,$conf->liste_limit))
	{
	  $obj = $db->fetch_object($i);	
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  
	  print '<td><a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->idp.'">';
	  print img_file();
	  print '</a>&nbsp;';
      
	  print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->idp.'">'.$obj->nom."</a></td>\n";
	  print '<td><a href="'.DOL_URL_ROOT.'/telephonie/contrat/fiche.php?id='.$obj->fk_contrat.'">'.$obj->fk_contrat."</a></td>\n";
	  print '<td align="center">'.strftime("%e %b %Y",$obj->datepo)."</td>\n";

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
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
