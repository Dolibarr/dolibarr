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
 */

/*
 * 	\version	$Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/rejet-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");

$langs->load("companies");


if (!$user->rights->prelevement->bons->lire)
  accessforbidden();

// Sécurité accés client
if ($user->societe_id > 0) accessforbidden();

/*
 * View
 */

llxHeader('','Bon de prélèvement - Rejet');

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Card");
$h++;      

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Bills");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/rejets.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Rejects");
$hselected = $h;
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Statistics");
$h++;  

$prev_id = $_GET["id"];

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="p.datec";

$rej = new RejetPrelevement($db, $user);

/*
 * Liste des factures
 *
 */
$sql = "SELECT pl.rowid, pr.motif, p.ref, pl.statut";
$sql .= " , s.rowid as socid, s.nom";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_rejet as pr";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE pr.fk_prelevement_lignes = pl.rowid";
$sql .= " AND pl.fk_prelevement_bons = p.rowid";
$sql .= " AND pl.fk_soc = s.rowid";

if ($_GET["socid"])
{
  $sql .= " AND s.rowid = ".$_GET["socid"];
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;
  
  print_barre_liste($langs->trans("WithdrawsRefused"), $page, "rejets.php", $urladd, $sortfield, $sortorder, '', $num);
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Nb"),"rejets.php","p.ref",'',$urladd);
  print_liste_field_titre($langs->trans("ThirdParty"),"rejets.php","s.nom",'',$urladd);
  print_liste_field_titre($langs->trans("Reason"),"rejets.php","pr.motif","",$urladd);
  print '</tr>';

  $var=True;

  $total = 0;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($result);	

      print "<tr $bc[$var]><td>";
      print '<img border="0" src="./statut'.$obj->statut.'.png"></a>&nbsp;';
      print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$obj->rowid.'">';

      print substr('000000'.$obj->rowid, -6)."</a></td>";

      print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$obj->socid.'">'.stripslashes($obj->nom)."</a></td>\n";

      print '<td>'.$rej->motifs[$obj->motif].'</td>';
      print "</tr>\n";
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

llxFooter('$Date$ - $Revision$');
?>
