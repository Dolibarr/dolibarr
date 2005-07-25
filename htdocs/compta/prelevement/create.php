<?php
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
 */

/**
   \file       htdocs/compta/prelevement/create.php
   \brief      Prelevement
   \version    $Revision$
*/

require("./pre.inc.php");
require_once DOL_DOCUMENT_ROOT."/includes/modules/modPrelevement.class.php";

$langs->load("withdrawals");

if (!$user->rights->prelevement->bons->creer)
  accessforbidden();

if ($_GET["action"] == 'create')
{
  $bprev = new BonPrelevement($db);
  $bprev->create($_GET["banque"],$_GET["guichet"]);
}


llxHeader();

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/create.php';
$head[$h][1] = $langs->trans("NewStandingOrder");
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("StandingOrders"));

$bprev = new BonPrelevement($db);

print '<table>';
print '<tr><td>Nb de facture à prélever :</td>';
print '<td align="right">';
print $bprev->NbFactureAPrelever();
print '</td><td>Notre banque :</td><td align="right">';
print $bprev->NbFactureAPrelever(1);
print '</td><td>Notre agence :</td><td align="right">';
print $bprev->NbFactureAPrelever(1,1);
print '</td></tr>';
print '<tr><td>Somme à prélever</td>';
print '<td align="right">';
print price($bprev->SommeAPrelever());
print '</td></tr></table>';

print '</div>';


print "<div class=\"tabsAction\">\n";


print '<a class="tabAction" href="create.php?action=create&amp;banque=1&amp;guichet=1">'.$langs->trans("CreateGuichet")."</a>\n";
print '<a class="tabAction" href="create.php?action=create&amp;banque=1">'.$langs->trans("CreateBanque")."</a>\n";
print '<a class="tabAction" href="create.php?action=create">'.$langs->trans("Create")."</a>\n";

print "</div><br>\n";

/*
 * Factures
 *
 */
$sql = "SELECT f.facnumber, f.rowid, s.nom, s.idp";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
$sql .= " WHERE s.idp = f.fk_soc";
$sql .= " AND pfd.traite = 0 AND pfd.fk_facture = f.rowid";

if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  
  if ($num)
    {
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">Factures en attente de prélèvement ('.$num.')</td></tr>';
      $var = True;
      while ($i < $num && $i < 20)
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  print '<tr '.$bc[$var].'><td>';
	  print '<a href="'.DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$obj->rowid.'">'.img_file().'</a>&nbsp;';
	  print '<a href="'.DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$obj->rowid.'">'.$obj->facnumber.'</a></td>';
	  print '<td>'.$obj->nom.'</td></tr>';
	  $i++;
	}
      
      print "</table><br>";

    }
}
else
{
  dolibarr_print_error($db);
}  


llxFooter();
?>
