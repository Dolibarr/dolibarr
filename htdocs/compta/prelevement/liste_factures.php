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

require_once DOL_DOCUMENT_ROOT."/compta/prelevement/rejet-prelevement.class.php";
require_once DOL_DOCUMENT_ROOT."/paiement.class.php";
require_once DOL_DOCUMENT_ROOT."/lib/dolibarrmail.class.php";

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) accessforbidden();

llxHeader('','Bon de prélèvement');

$page = $_GET["page"];
$sortorder = (empty($_GET["sortorder"])) ? "DESC" : $_GET["sortorder"];
$sortfield = (empty($_GET["sortfield"])) ? "p.datec" : $_GET["sortfield"];
$offset = $conf->liste_limit * $page ;

/*
 * Liste des factures
 *
 *
 */
$sql = "SELECT p.rowid, p.ref, p.statut";
$sql .= " ,f.rowid as facid, f.facnumber, f.total_ttc";
$sql .= " , s.idp, s.nom";
$sql .= " , pl.statut as statut_ligne, pl.rowid as rowid_ligne";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_facture as pf";
$sql .= " , ".MAIN_DB_PREFIX."facture as f";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE pf.fk_prelevement_lignes = pl.rowid";
$sql .= " AND pl.fk_prelevement_bons = p.rowid";
$sql .= " AND f.fk_soc = s.idp";
$sql .= " AND pf.fk_facture = f.rowid";

if ($_GET["socid"])
{
  $sql .= " AND s.idp = ".$_GET["socid"];
}

if ($_GET["search_fac"])
{
  $sql .= " AND f.facnumber like '%".$_GET["search_fac"]."%'";
}

if ($_GET["search_nom"])
{
  $sql .= " AND s.nom like '%".$_GET["search_nom"]."%'";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);

if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  $urladd = "&amp;socid=".$_GET["socid"];

  print_barre_liste("Factures prélevées", $page, "liste_factures.php", $urladd, $sortfield, $sortorder, '', $num);
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Ligne</td>';
  print_liste_field_titre("Facture","liste_factures.php","f.facnumber",'',$urladd);
  print_liste_field_titre("Société","liste_factures.php","s.nom",'',$urladd);
  print_liste_field_titre("Montant","liste_factures.php","f.total_ttc","",$urladd,'align="right"');
  print_liste_field_titre("Bon","liste_factures.php","p.rowid","",$urladd,'align="center"');
  print '</tr>';

  print '<form method="get" action="liste_factures.php">';
  print '<tr class="liste_titre"><td>&nbsp;</td>';
  print '<td>';
  print '<input size="8" class="fat" type="text" name="search_fac" value="'.$_GET["search_fac"].'">';
  print '</td><td>';
  print '<input size="20" class="fat" type="text" name="search_nom" value="'.stripslashes($_GET["search_nom"]).'">';

  print '</td><td colspan="2" align="center">';
  print '<input type="submit" class="button" name="button_search" value="'.$langs->trans("Search").'">';
  print '</td>';
  print "</tr>\n";
  print '</form>';

  $var=True;

  $total = 0;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	

      print "<tr $bc[$var]><td>";


      print '<img border="0" src="./statut'.$obj->statut_ligne.'.png"></a>&nbsp;';

      print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$obj->rowid_ligne.'">';
      print substr('000000'.$obj->rowid_ligne, -6).'</a></td>';


      print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">';
      print img_object($langs->trans("ShowBill"),"bill");
      print '</a>&nbsp;';

      print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">'.$obj->facnumber."</a></td>\n";

      print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$obj->idp.'">';
      print img_object($langs->trans("ShowCompany"),"company"). ' '.stripslashes($obj->nom)."</a></td>\n";

      print '<td align="right">'.price($obj->total_ttc)."</td>\n";

      print '<td align="center">';
      print '<img border="0" src="./statut'.$obj->statut.'.png"></a>&nbsp;';
      print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$obj->rowid.'">';
      print $obj->ref."</a></td>\n";

      print '</tr>';

      $total += $obj->total_ttc;
      $var=!$var;
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
