<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if (!$user->rights->telephonie->service->lire)
  accessforbidden();

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

llxHeader('','Telephonie - Services - Liste');
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="s.nom";
}

/*
 * Recherche
 *
 *
 */

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

/*
 * Mode Liste
 *
 *
 *
 */

if ($_GET["id"])
{
  
  $service = new TelephonieService($db);
  
  if ( $service->fetch($_GET["id"]) == 0 )
    { 
      
      
      $h=0;
      $head[$h][0] = DOL_URL_ROOT."/telephonie/service/fiche.php?id=".$service->id;
      $head[$h][1] = $langs->trans("Service");
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT."/telephonie/service/contrats.php?id=".$service->id;
      $head[$h][1] = "Contrats";
      $hselected = $h;
      $h++;
      
      dol_fiche_head($head, $hselected, 'Service : '.$service->id);
      
      
      $sql = "SELECT s.rowid as socid, s.nom as nom_facture";
      $sql .= " , c.ref, cs.montant, c.rowid as crowid";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
      $sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
      $sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat_service as cs";
      $sql .= " WHERE cs.fk_service = ".$_GET["id"];
      $sql .= " AND cs.fk_contrat = c.rowid";
      $sql .= " AND s.rowid = c.fk_soc_facture";
      
      if ($_GET["search_client"])
	{
	  $sel =urldecode($_GET["search_client"]);
	  $sql .= " AND s.nom LIKE '%".$sel."%'";
	}
      
      $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
      
      $resql = $db->query($sql);
      if ($resql)
	{
	  $num = $db->num_rows($resql);
	  $i = 0;
	  
	  $urladd= "&amp;id=".$_GET["id"];
	  
	  print_barre_liste("Contrats", $page, "contrats.php", $urladd, $sortfield, $sortorder, '', $num);
	  print"\n<!-- debut table -->\n";
	  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr class="liste_titre">';
	  
	  print_liste_field_titre("Contrat","contrats.php","c.ref","",$urladd);
	  print_liste_field_titre("Client","contrats.php","s.nom","",$urladd);
	  
	  print '<td align="right">Montant</td>';
	  
	  print "</tr>\n";
	  
	  /*
	    print '<tr class="liste_titre">';
	    print '<form action="liste.php" method="GET">';
	    print '<td><input type="text" name="search_ligne" value="'. $_GET["search_ligne"].'" size="10"></td>'; 
	    print '<td><input type="text" name="search_client" value="'. $_GET["search_client"].'" size="10"></td>';
	    print '<td><input type="submit" value="Chercher"></td>';
	    print '</form>';
	    print '</tr>';
	  */
	  
	  $var=True;
	  
	  while ($i < min($num,$conf->liste_limit))
	    {
	      $obj = $db->fetch_object($resql);
	      $var=!$var;
	      
	      print "<tr $bc[$var]><td>";
	      
	      print '<a href="'.DOL_URL_ROOT.'/telephonie/contrat/fiche.php?id='.$obj->crowid.'">';
	      print img_file();      
	      print '</a>&nbsp;';
	      
	      print '<a href="'.DOL_URL_ROOT.'/telephonie/contrat/fiche.php?id='.$obj->crowid.'">'.$obj->ref."</a></td>\n";
	      
	      print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->socid.'">'.stripslashes($obj->nom_facture).'</a></td>';
	      
	      print '<td align="right">'.price($obj->montant)."</td>\n";
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
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
