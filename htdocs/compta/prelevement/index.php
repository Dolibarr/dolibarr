<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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

if ($user->societe_id > 0)
{
  $socidp = $user->societe_id;
}

llxHeader();

print_titre($langs->trans("StandingOrders"));

print '<br>';

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';
/*
 * Bon de prélèvement
 *
 */
$sql = "SELECT p.rowid, p.ref, p.amount,".$db->pdate("p.datec")." as datec";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement as p";
$sql .= " ORDER BY datec DESC LIMIT 5";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;  
  $var=True;

  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td>Bon</td><td>Date</td>';
  print '<td align="right">'.$langs->trans("Amount").'</td>';
  print '</tr>';

  while ($i < $num)
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]><td>";

      print '<a href="fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";

      print '<td>'.strftime("%d/%m/%Y %H:%M",$obj->datec)."</td>\n";

      print '<td align="right">'.price($obj->amount)." ".$conf->monnaie."</td>\n";

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
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

print '</td></tr></table>';

llxFooter();
?>
