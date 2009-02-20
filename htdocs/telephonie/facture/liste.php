<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if (!$user->rights->telephonie->facture->lire) accessforbidden();

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

llxHeader();

/*
 * S�curit� acc�s client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="";
}
if ($sortfield == "") {
  $sortfield="f.date DESC, f.gain ASC";
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

$sql = "SELECT f.rowid, f.date, f.ligne, f.fourn_montant, f.cout_vente, f.gain, f.fk_facture";
$sql .= " ,s.nom, s.rowid as socid";
$sql .= " , fac.facnumber as ref";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."facture as fac";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= ",".MAIN_DB_PREFIX."societe_perms as sp";

$sql .= " WHERE s.rowid = l.fk_soc_facture AND l.rowid = f.fk_ligne";
$sql .= " AND l.fk_soc_facture = s.rowid";
$sql .= " AND l.fk_client_comm = sp.fk_soc";
$sql .= " AND sp.fk_user = ".$user->id." AND sp.pread = 1";

$sql .= " AND f.fk_facture = fac.rowid";

if ($_GET["search_ligne"])
{
  $sql .= " AND f.ligne LIKE '%".$_GET["search_ligne"]."%'";
}

if ($_GET["search_client"])
{
  $sql .= " AND s.nom LIKE '%".$_GET["search_client"]."%'";
}

if ($_GET["search_facture"])
{
  $sql .= " AND fac.facnumber LIKE '%".$_GET["search_facture"]."%'";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Factures", $page, "liste.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Client","liste.php","s.nom");

  print_liste_field_titre("Ligne","liste.php","l.ligne");
  print '<td align="center">Date</td><td align="right">Montant HT</td>';

  if ($user->rights->telephonie->ligne->gain)
    {
      print '<td align="right">Co�t fournisseur HT</td>';
      print_liste_field_titre("Marge","liste.php","f.gain",'','','align="right"');
    }
  print '<td align="center">Facture</td>';
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="liste.php" method="GET">';
  print '<td><input type="text" name="search_client" size="20" value="'.$_GET["search_client"].'"></td>';
  print '<td><input type="text" name="search_ligne" size="20" value="'.$_GET["search_ligne"].'"></td>';

  print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';

  print '<td>&nbsp;</td>';

  if ($user->rights->telephonie->ligne->gain)
    {
      print '<td>&nbsp;</td>';
      print '<td>&nbsp;</td>';
    }

  print '<td align="center" ><input type="text" name="search_facture" size="8" maxlength="10" value="'.$_GET["search_facture"].'"></td>';
  print '</form>';
  print '</tr>';


  $var=True;

  $ligne = new LigneTel($db);

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socid.'">';
      print img_file();      
      print '</a>&nbsp;';

      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socid.'">'.$obj->nom."</a></td>\n";
      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?numero='.$obj->ligne.'">'.dol_print_phone($obj->ligne,0,0,true)."</a></td>\n";
      print '<td align="center">'.$obj->date."</td>\n";
      print '<td align="right">'.sprintf("%01.4f",$obj->cout_vente)."</td>\n";

      if ($user->rights->telephonie->ligne->gain)
	{
	  print '<td align="right">'.sprintf("%01.4f",$obj->fourn_montant)."</td>\n";

	  print '<td align="right">';
	  if ($obj->gain < 0 && $obj->cout_vente)
	    {
	      print '<font color="red"><b>';
	      print sprintf("%01.4f",$obj->gain);
	      print "</b></font>";
	    }
	  else
	    {
	      print sprintf("%01.4f",$obj->gain);
	    }
	  print "</td>\n";
	}
      print '<td align="center"><a href="'.DOL_URL_ROOT.'/telephonie/client/facture.php?facid='.$obj->fk_facture.'">'.$obj->ref."</a></td>\n";
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

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
