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

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Fiche");
$h++;      

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/lignes.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Lignes");
$hselected = $h;
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Factures");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-rejet.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Rejets");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Statistiques");
$h++;  

$prev_id = $_GET["id"];

if ($_GET["id"])
{
  $bon = new BonPrelevement($db,"");

  if ($bon->fetch($_GET["id"]) == 0)
    {

      dolibarr_fiche_head($head, $hselected, 'Prélèvement : '. $bon->ref);


      print '<table class="border" width="100%">';

      print '<tr><td width="20%">Référence</td><td>'.$bon->ref.'</td></tr>';

      print '</table><br />';
    }
  else
    {
      print "Erreur";
    }
}

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($sortorder == "") {
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="pl.fk_soc";
}

/*
 * Liste des lignes de prélèvement
 *
 *
 */
$sql = "SELECT pl.rowid, pl.statut, pl.amount";
$sql .= " , s.idp, s.nom";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE pl.fk_prelevement_bons=".$prev_id;
$sql .= " AND pl.fk_soc = s.idp";

if ($_GET["socid"])
{
  $sql .= " AND s.idp = ".$_GET["socid"];
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);

if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  $urladd = "&amp;id=".$_GET["id"];

  print_barre_liste("Lignes de prélèvement", $page, "lignes.php", $urladd, $sortfield, $sortorder, '', $num);
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Lignes","lignes.php","pl.rowid",'',$urladd);
  print_liste_field_titre("Société","lignes.php","s.nom",'',$urladd);
  print_liste_field_titre("Montant","lignes.php","f.total_ttc","",$urladd,'align="center"');
  print '<td colspan="2">&nbsp;</td></tr>';

  $var=True;

  $total = 0;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	

      print "<tr $bc[$var]><td>";

      print '<img border="0" src="./statut'.$obj->statut.'.png"></a>&nbsp;';
      print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$obj->rowid.'">';
      print substr('000000'.$obj->rowid, -6);
      print '</a></td>';

      print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$obj->idp.'">'.stripslashes($obj->nom)."</a></td>\n";

      print '<td align="center">'.price($obj->amount)."</td>\n";

      print '<td>';

      if ($obj->statut == 3)
	{
	  print '<b>Rejeté</b>';
	}
      else
	{
	  print "&nbsp;";
	}

      print '</td></tr>';

      $total += $obj->total_ttc;
      $var=!$var;
      $i++;
    }

  if($_GET["socid"])
    {
      print "<tr $bc[$var]><td>";

      print '<td>Total</td>';

      print '<td align="center">'.price($total)."</td>\n";

      print '<td>&nbsp;</td>';

      print "</tr>\n";
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
