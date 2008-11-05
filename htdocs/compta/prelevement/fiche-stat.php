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
 * 	\version	$Id$
 */

require("./pre.inc.php");

// Sécurité accés client
if ($user->societe_id > 0) accessforbidden();


/*
 * View
 */

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
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Statistics");
$hselected = $h;
$h++;  

$prev_id = $_GET["id"];

if ($prev_id)
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

  /*
   * Stats
   *
   */
  $sql = "SELECT sum(pl.amount), pl.statut";
  $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";

  $sql .= " WHERE pl.fk_prelevement_bons = ".$prev_id;
  $sql .= " GROUP BY pl.statut";
  
  if ($db->query($sql))
    {
      $num = $db->num_rows();
      $i = 0;
      
      print"\n<!-- debut table -->\n";
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre">';
      print '<td>Statut</td><td align="right">Montant</td><td align="right">%</td></tr>';
      
      $var=false;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row();	
	  
	  print "<tr $bc[$var]><td>";
	  
	  if ($row[1] == 2)
	    {
	      print 'Crédité';
	    }
	  elseif ($row[1] == 3)
	    {
	      print 'Rejeté';
	    }
	  elseif ($row[1] == 1)
	    {
	      print 'En attente';
	    }
	    else print 'Unknown';	  

	  print '</td><td align="right">';	  
	  print price($row[0]);	  

	  print '</td><td align="right">';	  
	  print round($row[0]/$bon->amount*100,2)." %";	  
	  print '</td>';

	  print "</tr>\n";
	  
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
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
