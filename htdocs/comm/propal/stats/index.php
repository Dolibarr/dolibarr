<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/comm/propal/stats/index.php
        \ingroup    propale
		\brief      Page des stats propositions commerciales
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/stats/propalestats.class.php");
require_once(DOL_DOCUMENT_ROOT."/dolgraph.class.php");

$WIDTH=500;
$HEIGHT=200;


llxHeader();

print_fiche_titre($langs->trans("ProposalsStatistics"), $mesg);

$stats = new PropaleStats($db);
$year = strftime("%Y", time());
$data = $stats->getNbByMonthWithPrevYear($year);

create_exdir($conf->propal->dir_temp);

if (!$user->rights->commercial->client->voir || $user->societe_id)
{
	$filename = $conf->propal->dir_temp.'/nbpropale2year-'.$user->id.'-'.$year.'.png';
	$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=nbpropale2year-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename = $conf->propal->dir_temp.'/nbpropale2year-'.$year.'.png';
	$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=nbpropale2year-'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
	$px->SetPrecisionY(0);
    $px->SetLegend(array($year - 1, $year));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($filename);
}

$sql = "SELECT count(*), date_format(p.datep,'%Y') as dm, sum(p.price)";
if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."propal as p";
if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE fk_statut > 0";
if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
if($user->societe_id)
{
   $sql .= " AND p.fk_soc = ".$user->societe_id;
}
$sql.= " GROUP BY dm DESC ";
if ($db->query($sql))
{
  $num = $db->num_rows();

  print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
  print '<tr><td align="center">'.$langs->trans("Year").'</td><td width="10%" align="center">'.$langs->trans("NbOfProposals").'</td><td align="center">'.$langs->trans("AmountTotal").'</td>';
  print '<td align="center" valign="top" rowspan="'.($num + 1).'">';
  
  if ($mesg)
  {
  	print "$mesg";
  }
  else
  {
  	print '<img src="'.$fileurl.'" alt="Nombre de proposition par mois">';
  }
  
  print '</td></tr>';
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $nbproduct = $row[0];
      $year = $row[1];
      print "<tr>";
      print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td>';
      print '<td align="center">'.$nbproduct.'</td>';
      print '<td align="center">'.price($row[2]).'</td></tr>';
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
