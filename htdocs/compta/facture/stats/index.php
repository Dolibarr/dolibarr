<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
   \file       htdocs/compta/facture/stats/index.php
   \ingroup    facture
   \brief      Page des stats factures
   \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/dolgraph.class.php");

$WIDTH=500;
$HEIGHT=200;

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

llxHeader();

print_fiche_titre($langs->trans("BillsStatistics"), $mesg);

$stats = new FactureStats($db, $socid);
$year = strftime("%Y", time());
$startyear=$year-2;
$endyear=$year;
$data = $stats->getNbByMonthWithPrevYear($endyear,$startyear);

create_exdir($conf->facture->dir_temp);

$filename = $conf->facture->dir_temp."/nbfacture2year-".$year.".png";
$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&amp;file=nbfacture2year-'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetPrecisionY(0);
	$i=$startyear;
	while ($i <= $endyear)
	{
		$legend[]=$i;
		$i++;
	}
    $px->SetLegend($legend);
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetYLabel($langs->trans("NbOfInvoices"));
	$px->SetShading(3);
    $px->SetHorizTickIncrement(1);
    $px->SetPrecisionY(0);
    $px->mode='depth';
	$px->draw($filename);
}
      
$sql = "SELECT count(*) as nb, date_format(datef,'%Y') as dm, sum(total) as total FROM ".MAIN_DB_PREFIX."facture WHERE fk_statut > 0 ";
if ($socid)
{
  $sql .= " AND fk_soc=$socid";
}
$sql.= " GROUP BY dm DESC;";
$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  
  print '<table class="border" width="100%">';
  print '<tr><td align="center">'.$langs->trans("Year").'</td><td width="10%" align="center">'.$langs->trans("NumberOfBills").'</td><td align="center">'.$langs->trans("AmountTotal").'</td>';
  print '<td align="center" valign="top" rowspan="'.($num + 1).'">';
  if ($mesg) { print $mesg; }
  else { print '<img src="'.$fileurl.'" alt="Nombre de factures par mois">'; }
  print '</td></tr>';

  while ($obj = $db->fetch_object($resql))
    {
      $nbproduct = $obj->nb;
      $year = $obj->dm;
      $total = price($obj->total);
      print "<tr>";
      print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td><td align="center">'.$nbproduct.'</td><td align="center">'.$total.'</td></tr>';

    }
  
  print '</table>';
  $db->free($resql);
}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
