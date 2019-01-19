<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Charles-Fr BENKE     <charles.fr@benke.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Andreu Bisquerra		<jove@bisquerra.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *      \file       htdocs/compta/bank/categ.php
 *      \ingroup    compta
 *      \brief      Page ajout de categories bancaires
 */

$res=@include("../main.inc.php");
if (! $res) $res=@include("../../main.inc.php");
include_once 'class/cashcontrol.class.php';

$langs->loadLangs(array("cashcontrol","install","cashdesk","admin"));

$action=GETPOST('action','aZ09');
$id=GETPOST('id');

if (!$user->rights->banque->configurer)
  accessforbidden();

$categid = GETPOST('categid');
$label = GETPOST("label");

if (empty($conf->global->CASHDESK_ID_BANKACCOUNT_CASH) or empty($conf->global->CASHDESK_ID_BANKACCOUNT_CB)) setEventMessages($langs->trans("CashDesk")." - ".$langs->trans("NotConfigured"), null, 'errors');

/*
 * Add category
 */
if ($action=="start")
{
    $cashcontrol= new CashControl($db);
    $cashcontrol->opening=GETPOST('opening');
	if (GETPOST('posmodule')==0) $cashcontrol->posmodule="cashdesk";
	else if (GETPOST('posmodule')==1) $cashcontrol->posmodule="takepos";
	$cashcontrol->posnumber=GETPOST('posnumber');
    $id=$cashcontrol->create($user);
	$action="view";
}

if ($action=="close")
{
    $cashcontrol= new CashControl($db);
	$cashcontrol->id=$id;
    $cashcontrol->close($user);
	$action="view";
}


if ($action=="create")
{
llxHeader();
    print load_fiche_titre("Cashcontrol - ".$langs->trans("New"), '', 'title_bank.png');
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="start">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("InitialBankBalance").'</td><td>'.$langs->trans("Module").'</td><td>'.$langs->trans("CashDesk").' ID</td><td>,</td>';
    print "</tr>\n";
    print '<tr class="oddeven">';
    print '<td>&nbsp;</td><td><input name="opening" type="text" size="10" value="0"></td>';
	print '<td>'.$form->selectarray('posmodule', array('0'=>$langs->trans('CashDesk'),'1'=>$langs->trans('TakePOS')),1).'</td>';
	print '<td><input name="posnumber" type="text" size="10" value="0"></td>';
    print '<td align="center"><input type="submit" name="add" class="button" value="'.$langs->trans("Start").'"></td>';
    print '</tr>';
    print '</table></form>';
}


if ($action=="list")
{
llxHeader();
    print load_fiche_titre("Cashcontrol - ".$langs->trans("List"), '', 'title_bank.png');
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="start">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("DateCreationShort").'</td><td colspan="2">'.$langs->trans("DateEnd").'</td><td align=right>'.$langs->trans("Cash").'</td><td align=right>'.$langs->trans("PaymentTypeCB").'</td><td>'.$langs->trans("Status").'</td>';
    print "</tr>\n";
	
	$sql = "SELECT *";
	$sql.= " FROM ";
	$sql.= MAIN_DB_PREFIX."pos_cash_fence order by rowid DESC";
	$result = $db->query($sql);
	if ($result) {
		$i = 0;
		$num = $db->num_rows($result);

		while ($i < $num) {
			print '<tr class="oddeven">';
			$objp = $db->fetch_object($result);
			$totalpaye += $objp->amount;
			print '<td>';
			print '<a href="cashcontrol.php?action=view&id='.$objp->rowid.'">'.$objp->rowid.'</a>';
			print '</td><td>'.dol_print_date($objp->date_creation, 'dayhour').'<td><td>'.dol_print_date(strtotime($objp->year_close."-".$objp->month_close."-".$objp->day_close), 'day').'</td>';
			print '<td align=right>';
			if ($objp->status==2) print price($objp->cash);
			print '</td><td align=right>';
			if ($objp->status==2) price($objp->card);
			print '</td><td>';
			if ($objp->status==1) print $langs->trans("Opened");
			if ($objp->status==2) print $langs->trans("Closed");
			print '</td></tr>';
			$i ++;
		}
	} else {
		//no hay
	}
	
    print '</table></form>';
}



if ($action=="view")
{
	$cashcontrol= new CashControl($db);
    $cashcontrol->fetch($id);
	llxHeader();
    print load_fiche_titre($langs->trans("CashControl"), '', 'title_bank.png');
    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';
	
	print '<tr><td class="nowrap">';
	print $langs->trans("Code");
	print '</td><td colspan="2">';
	print $id;
	print '</td></tr>';
	
	print '<tr><td class="nowrap">';
	print $langs->trans("DateCreationShort");
	print '</td><td colspan="2">';
	print dol_print_date($cashcontrol->date_creation, 'dayhour');
	print '</td></tr>';
	
	print '<tr><td class="nowrap">';
	print $langs->trans("DateEnd");
	print '</td><td colspan="2">';
	print dol_print_date(strtotime($cashcontrol->year_close."-".$cashcontrol->month_close."-".$cashcontrol->day_close), 'day');
	print '</td></tr>';
	
	print '<tr><td class="nowrap">';
	print $langs->trans("Status");
	print '</td><td colspan="2">';
	if ($cashcontrol->status==1) print $langs->trans("Opened");
	if ($cashcontrol->status==2) print $langs->trans("Closed");
	print '</td></tr>';

	print '</table>';
    print '</div>';
    print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';

	print '<tr><td valign="middle">'.$langs->trans("InitialBankBalance").'</td><td colspan="3">';
	print price($cashcontrol->opening);
	print "</td></tr>";
	
	print '<tr><td valign="middle">'.$langs->trans("CashDesk").' ID</td><td colspan="3">';
	print $cashcontrol->posnumber;
	print "</td></tr>";
	
	print '<tr><td valign="middle">'.$langs->trans("Module").'</td><td colspan="3">';
	print $cashcontrol->posmodule;
	print "</td></tr>";

	print "</table>\n";
    print '</div>';
    print '</div></div>';
    print '<div style="clear:both"></div>';

    dol_fiche_end();
	
	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction"><a target="_blank" class="butAction" href="report.php?id='.$id.'">' . $langs->trans('PrintTicket') . '</a></div>';
	if ($cashcontrol->status==1) print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=close">' . $langs->trans('Close') . '</a></div>';
	print '</div>';
	
	print '<center><iframe src="report.php?id='.$id.'" width="60%" height="800"></iframe></center>';
}

llxFooter();
