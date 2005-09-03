<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/commande/stats/index.php
        \ingroup    commande
		\brief      Page des stats commandes
		\version    $Revision$
*/

require("./pre.inc.php");

if (!$user->rights->commande->lire) accessforbidden();

require(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require("./commandestats.class.php");

$WIDTH=500;
$HEIGHT=250;

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}


llxHeader();

print_fiche_titre($langs->trans("OrdersStatistics"), $mesg);

$stats = new CommandeStats($db, $socidp);

$year = strftime("%Y", time());
$data = $stats->getNbByMonthWithPrevYear($year);

// Création répertoire pour images générées
$dir=$conf->commande->dir_images;
if (! file_exists($dir))
{
    if (create_exdir($dir) < 0)
    {
        $mesg = $langs->trans("ErrorCanNotCreateDir",$dir);
    }
}


$filename = $conf->commande->dir_images."/nbcommande2year-".$year.".png";
$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=nbcommande2year-'.$year.'.png';

$px = new BarGraph();
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetData($data);
    $px->SetMaxValue($px->GetMaxValue());
    $px->SetLegend(array($year - 1, $year));
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetYLabel("Nombre de commande");
    $px->draw($filename);
}      
$rows = $stats->getNbByYear();
$num = sizeof($rows);

print '<table class="border" width="100%">';
print '<tr><td align="center">'.$langs->trans("Year").'</td><td width="10%" align="center">'.$langs->trans("NbOfOrders").'</td><td align="center">'.$langs->trans("AmountTotal").'</td>';
print '<td align="center" valign="top" rowspan="'.($num + 1).'">';
if ($px->isGraphKo()) { print '<font class="error">'.$px->isGraphKo().'</div>'; }
else { print '<img src="'.$fileurl.'" alt="Nombre de commande par mois">'; }
print '</td></tr>';
$i = 0;
while (list($key, $value) = each ($rows))
{
  $year = $value[0];
  $nbproduct = $value[1];
  $price = $value[2];
  print "<tr>";
  print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td><td align="center">'.$nbproduct.'</td><td align="center">'.price($price).'</td></tr>';
  $i++;
}

print '</table>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
