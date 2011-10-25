<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/adherents/stats/index.php
 *      \ingroup    member
 *		\brief      Page of subscription members statistics
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherentstats.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/member.lib.php");

$WIDTH=500;
$HEIGHT=200;

$userid=GETPOST('userid'); if ($userid < 0) $userid=0;
$socid=GETPOST('socid'); if ($socid < 0) $socid=0;
// Security check
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$year = strftime("%Y", time());
$startyear=$year-2;
$endyear=$year;


/*
 * View
 */

$form=new Form($db);

$langs->load("propal");

llxHeader();

print_fiche_titre($langs->trans("SubscriptionsStatistics"), $mesg);

$dir=$conf->adherent->dir_temp;

create_exdir($dir);

$stats = new AdherentStats($db, $socid, $userid);

// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)


$filenamenb = $dir.'/subscriptionsnbinyear-'.$year.'.png';
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=memberstats&file=subscriptionsnbinyear-'.$year.'.png';


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
    $px->SetMinValue(min(0,$px->GetFloorMinValue()));
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetYLabel($langs->trans("NbOfSubscriptions"));
    $px->SetShading(3);
    $px->SetHorizTickIncrement(1);
    $px->SetPrecisionY(0);
    $px->mode='depth';
    $px->SetTitle($langs->trans("NbOfSubscriptions"));

    $px->draw($filenamenb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenameamount = $dir.'/subscriptionsamountinyear-'.$year.'.png';
$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=memberstats&file=subscriptionsamountinyear-'.$year.'.png';

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
    $px->SetMinValue(min(0,$px->GetFloorMinValue()));
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetYLabel($langs->trans("AmountOfSubscriptions"));
    $px->SetShading(3);
    $px->SetHorizTickIncrement(1);
    $px->SetPrecisionY(0);
    $px->mode='depth';
    $px->SetTitle($langs->trans("AmountOfSubscriptions"));

    $px->draw($filenameamount);
}


$head = member_stats_prepare_head($adh);

dol_fiche_head($head, 'statssubscription', $langs->trans("Statistics"), 0, 'user');


print '<table class="notopnoleftnopadd" width="100%"><tr>';
print '<td align="center" valign="top">';

// Show filter box
/*print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<table class="border" width="100%">';
print '<tr><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
print '<tr><td>'.$langs->trans("Member").'</td><td>';
$filter='s.client in (1,2,3)';
print $form->select_company($id,'memberid',$filter,1);
print '</td></tr>';
print '<tr><td>'.$langs->trans("User").'</td><td>';
print $form->select_users($userid,'userid',1);
print '</td></tr>';
print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="button" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';
*/

// Show array
$data = $stats->getAllByYear();

print '<table class="border" width="100%">';
print '<tr height="24">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="center">'.$langs->trans("NbOfSubscriptions").'</td>';
print '<td align="center">'.$langs->trans("AmountTotal").'</td>';
print '<td align="center">'.$langs->trans("AmountAverage").'</td>';
print '</tr>';

$oldyear=0;
foreach ($data as $val)
{
    $year = $val['year'];
    print $avg;
    while ($oldyear > $year+1)
    {	// If we have empty year
        $oldyear--;
        print '<tr height="24">';
        print '<td align="center">';
        print '<a href="month.php?year='.$oldyear.'&amp;mode='.$mode.'">';
        print $oldyear;
        print '</a>';
        print '</td>';
        print '<td align="right">0</td>';
        print '<td align="right">0</td>';
        print '<td align="right">0</td>';
        print '</tr>';
    }
    print '<tr height="24">';
    print '<td align="center">';
    //print '<a href="month.php?year='.$year.'">';
    print $year;
    //print '</a>';
    print '</td>';
    print '<td align="right">'.$val['nb'].'</td>';
    print '<td align="right">'.price(price2num($val['total'],'MT'),1).'</td>';
    print '<td align="right">'.price(price2num($val['avg'],'MT'),1).'</td>';
    print '</tr>';
    $oldyear=$year;
}

print '</table>';


print '</td>';
print '<td align="center" valign="top">';

// Show graphs
print '<table class="border" width="100%"><tr valign="top"><td align="center">';
if ($mesg) { print $mesg; }
else {
    print '<img src="'.$fileurlnb.'" title="'.$langs->trans("NbOfSubscriptions").'" alt="'.$langs->trans("NbOfSubscriptions").'">';
    print "<br>\n";
    print '<img src="'.$fileurlamount.'" title="'.$langs->trans("AmountTotal").'" alt="'.$langs->trans("AmountTotal").'">';
}
print '</td></tr></table>';

print '</td></tr></table>';

dol_fiche_end();

$db->close();

llxFooter();
?>
