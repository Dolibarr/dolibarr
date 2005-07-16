<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/comm/propal/stats/index.php
        \ingroup    propale
		\brief      Page des stats propositions commerciales
		\version    $Revision$
*/

require("./pre.inc.php");
require("./propalestats.class.php");

$WIDTH=500;
$HEIGHT=250;


llxHeader();

print_fiche_titre($langs->trans("ProposalsStatistics"), $mesg);

$stats = new PropaleStats($db);
$year = strftime("%Y", time());
$data = $stats->getNbByMonthWithPrevYear($year);

if (! is_dir($conf->propal->dir_images)) { mkdir($conf->propal->dir_images); }

$filename = $conf->propal->dir_images."/nbpropale2year-$year.png";
$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=nbpropale2year-'.$year.'.png';

$px = new BarGraph();
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetData($data);
    $px->SetLegend(array($year - 1, $year));
    $px->SetMaxValue($px->GetMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->draw($filename);
}

$sql = "SELECT count(*), date_format(datep,'%Y') as dm, sum(price)  FROM ".MAIN_DB_PREFIX."propal WHERE fk_statut > 0 GROUP BY dm DESC ";
if ($db->query($sql))
{
  $num = $db->num_rows();

  print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
  print '<tr><td align="center">'.$langs->trans("Year").'</td><td width="10%" align="center">'.$langs->trans("NbOfProposals").'</td><td align="center">'.$langs->trans("AmountTotal").'</td>';
  print '<td align="center" valign="top" rowspan="'.($num + 1).'">';
  if ($mesg) { print "$mesg"; }
  else { print '<img src="'.$fileurl.'" alt="Nombre de proposition par mois">'; }
  print '</td></tr>';
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $nbproduct = $row[0];
      $year = $row[1];
      print "<tr>";
      print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td><td align="center">'.$nbproduct.'</td><td align="center">'.price($row[2]).'</td></tr>';
      $i++;
    }

  print '</table>';
  $db->free();
}
else
{
  dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
