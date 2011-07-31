<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * $Id: facturesrejets.php,v 1.13 2011/07/31 22:23:29 eldy Exp $
 *
 */

require("../bank/pre.inc.php");
require_once DOL_DOCUMENT_ROOT."/compta/prelevement/class/rejet-prelevement.class.php";
require_once DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php";

$langs->load("categories");

// Security check
if ($user->societe_id > 0) accessforbidden();


/*
 * View
 */
llxHeader('',$langs->trans("WithdrawalReceipt"));

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Fiche");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Factures");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/rejets.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Rejets");
$hselected = $h;
$h++;


$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Statistiques");
$h++;

$prev_id = $_GET["id"];

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
  $sortfield="p.datec";
}

/*
 * Liste des factures
 *
 *
 */
$sql = "SELECT p.rowid, pf.statut, p.ref";
$sql.= " ,f.rowid as facid, f.facnumber, f.total_ttc";
$sql.= " , s.rowid as socid, s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement as p";
$sql.= " , ".MAIN_DB_PREFIX."prelevement_facture as pf";
$sql.= " , ".MAIN_DB_PREFIX."facture as f";
$sql.= " , ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE pf.fk_prelevement = p.rowid";
$sql.= " AND f.fk_soc = s.rowid";
$sql.= " AND pf.fk_facture = f.rowid";
$sql.= " AND pf.statut = 2 ";
$sql.= " AND f.entity = ".$conf->entity;
if ($_GET["socid"]) $sql.= " AND s.rowid = ".$_GET["socid"];
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;

  print_barre_liste("Pr�l�vements rejet�s", $page, "rejets.php", $urladd, $sortfield, $sortorder, '', $num);
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Bon N�","rejets.php","p.ref",'',$urladd);
  print_liste_field_titre($langs->trans("Invoice"),"rejets.php","p.facnumber",'',$urladd);
  print_liste_field_titre($langs->trans("ThirdParty"),"rejets.php","s.nom",'',$urladd);
  print_liste_field_titre($langs->trans("Amount"),"rejets.php","f.total_ttc","",$urladd,'align="center"');
  print '<td colspan="2">&nbsp;</td></tr>';

  $var=True;

  $total = 0;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($result);

      print "<tr $bc[$var]><td>";

      print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";

      print "<td>";

      print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">';
      print img_file();
      print '</a>&nbsp;';

      print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">'.$obj->facnumber."</a></td>\n";


      print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.stripslashes($obj->nom)."</a></td>\n";

      print '<td align="center">'.price($obj->total_ttc)."</td>\n";

      print '<td><b>Rejet�</b></td><td>';

      print '</td>';

      print "</tr>\n";

      $total += $obj->total_ttc;
      $var=!$var;
      $i++;
    }

  print "</table>";
  $db->free($result);
}
else
{
  dol_print_error($db);
}

$db->close();

llxFooter('$Date: 2011/07/31 22:23:29 $ - $Revision: 1.13 $');
?>
