<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/compta/prelevement/index.php
        \brief      Prelevement
        \version    $Revision$
*/

require("./pre.inc.php");
require_once DOL_DOCUMENT_ROOT."/includes/modules/modPrelevement.class.php";

$langs->load("withdrawals");


if (!$user->rights->prelevement->bons->lire)
  accessforbidden();

// Sécurité accés client
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}


/*
 * Affichage page
 *
 */

llxHeader();


print_fiche_titre($langs->trans("CustomersStandingOrdersArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';


$bprev = new BonPrelevement($db);
$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td>Nb de facture à prélever</td>';
print '<td align="right">';
print $bprev->NbFactureAPrelever();
print '</td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td>Somme à prélever</td>';
print '<td align="right">';
print price($bprev->SommeAPrelever());
print '</td></tr></table><br>';


/*
 * Bon de prélèvement
 *
 */
$sql = "SELECT p.rowid, p.ref, p.amount,".$db->pdate("p.datec")." as datec";
$sql .= " ,p.statut ";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " ORDER BY datec DESC LIMIT 5";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;  
  $var=True;

  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td>'.$langs->trans("WithdrawalReceiptShort").'</td><td>'.$langs->trans("Date").'</td>';
  print '<td align="right">'.$langs->trans("Amount").'</td>';
  print '</tr>';

  while ($i < $num)
    {
      $obj = $db->fetch_object($result);	
      $var=!$var;

      print "<tr $bc[$var]><td>";

      print '<img border="0" src="./statut'.$obj->statut.'.png"></a>&nbsp;';

      print '<a href="fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";

      print '<td>'.dolibarr_print_date($obj->datec,"dayhour")."</td>\n";

      print '<td align="right">'.price($obj->amount)."</td>\n";

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($result);
}
else 
{
  dolibarr_print_error($db);
}

print '</td><td valign="top" width="70%">';

/*
 * Factures
 *
 */
$sql = "SELECT f.facnumber, f.rowid, s.nom, s.rowid as socid";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
$sql .= " WHERE s.rowid = f.fk_soc";
$sql .= " AND pfd.traite = 0 AND pfd.fk_facture = f.rowid";

if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

if ($socid)
{
  $sql .= " AND f.fk_soc = $socid";
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
	  print '<a href="'.DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$obj->rowid.'">'.img_file().' '.$obj->facnumber.'</a></td>';
      print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$obj->nom.'</a></td>';
	  print '</tr>';
	  $i++;
	}
      
      print "</table><br>";

    }
}
else
{
  dolibarr_print_error($db);
}  

print '</td></tr></table>';

llxFooter('$Date$ - $Revision$');

?>
