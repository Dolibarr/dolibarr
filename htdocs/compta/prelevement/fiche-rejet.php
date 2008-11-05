<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \version	$Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/rejet-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");

// Sécurité accés client
if ($user->societe_id > 0) accessforbidden();

llxHeader('',$langs->trans("WithdrawalReceipt"));

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Card");
$h++;      

if ($conf->use_preview_tabs)
{
    $head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/bon.php?id='.$_GET["id"];
    $head[$h][1] = $langs->trans("Preview");
    $h++;  
}

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/lignes.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Lines");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Bills");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-rejet.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Rejects");
$hselected = $h;
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Statistics");
$h++;  

$prev_id = $_GET["id"];

if ($_GET["id"])
{
  $bon = new BonPrelevement($db,"");

  if ($bon->fetch($_GET["id"]) == 0)
    {
      dolibarr_fiche_head($head, $hselected, $langs->trans("WithdrawalReceipt"));

      print '<table class="border" width="100%">';
      print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td>'.$bon->getNomUrl(1).'</td></tr>';
      print '</table>';
      
      print '</div>';
    }
  else
    {
      print "Erreur";
    }
}

$page = $_GET["page"];
$rej = new RejetPrelevement($db, $user);
/*
 * Liste des factures
 *
 *
 */
$sql = "SELECT pl.rowid, pl.amount, pl.statut";
$sql .= " , s.rowid as socid, s.nom";
$sql .= " , pr.motif, pr.afacturer, pr.fk_facture";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_rejet as pr";
$sql .= " WHERE p.rowid=".$prev_id;
$sql .= " AND pl.fk_prelevement_bons = p.rowid";
$sql .= " AND pl.fk_soc = s.rowid";
$sql .= " AND pl.statut = 3 ";
$sql .= " AND pr.fk_prelevement_lignes = pl.rowid";

if ($_GET["socid"])
{
  $sql .= " AND s.rowid = ".$_GET["socid"];
}

$sql .= " ORDER BY pl.amount DESC";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>'.$langs->trans("Line").'</td><td>'.$langs->trans("ThirdParty").'</td><td align="right">'.$langs->trans("Amount").'</td>';
  print '<td>'.$langs->trans("Reason").'</td><td align="center">'.$langs->trans("ToBill").'</td><td align="center">'.$langs->trans("Invoice").'</td></tr>';

  $var=True;
  $total = 0;

  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);	

      print "<tr $bc[$var]><td>";
      print '<img border="0" src="./statut'.$obj->statut.'.png"></a>&nbsp;';
      print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$obj->rowid.'">';

      print substr('000000'.$obj->rowid, -6);
      print '</a></td>';
      print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$obj->socid.'">'.stripslashes($obj->nom)."</a></td>\n";

      print '<td align="right">'.price($obj->amount)."</td>\n";
      print '<td>'.$rej->motifs[$obj->motif].'</td>';

      print '<td align="center">'.yn($obj->afacturer).'</td>';
      print '<td align="center">'.$obj->fk_facture.'</td>';
      print "</tr>\n";

      $total += $obj->amount;
      $var=!$var;
      $i++;
    }

  print '<tr class="liste_total"><td>&nbsp;</td>';
  print '<td class="liste_total">'.$langs->trans("Total").'</td>';
  print '<td align="right">'.price($total)."</td>\n";
  print '<td>&nbsp;</td>';
  print "</tr>\n</table>\n";
  $db->free($resql);
}
else 
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
