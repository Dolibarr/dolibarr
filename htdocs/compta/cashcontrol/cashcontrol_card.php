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
 *      \file       htdocs/compta/cashcontrol/cashcontrol_card.php
 *      \ingroup    cashdesk|takepos
 *      \brief      Page to show a cash fence
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';

$langs->loadLangs(array("install","cashdesk","admin","banks"));

$id=GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action=GETPOST('action', 'aZ09');
$categid = GETPOST('categid');
$label = GETPOST("label");

$now=dol_now();
$syear = (GETPOSTISSET('closeyear')?GETPOST('closeyear', 'int'):dol_print_date($now, "%Y"));
$smonth = (GETPOSTISSET('closemonth')?GETPOST('closemonth', 'int'):dol_print_date($now, "%m"));
$sday = (GETPOSTISSET('closeday')?GETPOST('closeday', 'int'):dol_print_date($now, "%d"));

$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='rowid';
if (! $sortorder) $sortorder='ASC';

// Security check
if (! $user->rights->cashdesk->use && ! $user->rights->takepos->use)
{
	accessforbidden();
}

$arrayofpaymentmode=array('cash'=>'Cash', 'cheque'=>'Cheque', 'card'=>'CreditCard');

$arrayofposavailable=array();
if (! empty($conf->cashdesk->enabled)) $arrayofposavailable['cashdesk']=$langs->trans('CashDesk').' (cashdesk)';
if (! empty($conf->takepos->enabled))  $arrayofposavailable['takepos']=$langs->trans('TakePOS').' (takepos)';
// TODO Add hook here to allow other POS to add themself

$object= new CashControl($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('cashcontrolcard','globalcard'));


/*
 * Actions
 */

$permissiontoadd = ($user->rights->cashdesk->use || $user->rights->takepos->use);
$permissiontodelete = ($user->rights->cashdesk->use || $user->rights->takepos->use) || ($permissiontoadd && $object->status == 0);
if (empty($backtopage)) $backtopage = dol_buildpath('/compta/cashcontrol/cashcontrol_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
$backurlforlist = dol_buildpath('/compta/cashcontrol/cashcontrol_list.php', 1);
$triggermodname = 'CACHCONTROL_MODIFY';	// Name of trigger action code to execute when we modify record

if (empty($conf->global->CASHDESK_ID_BANKACCOUNT_CASH) && empty($conf->global->CASHDESK_ID_BANKACCOUNT_CASH1))
{
	setEventMessages($langs->trans("CashDesk")." - ".$langs->trans("NotConfigured"), null, 'errors');
}


if (GETPOST('cancel', 'alpha'))
{
	$action = 'create';
}

if ($action=="start")
{
	$error=0;
	if (! GETPOST('posmodule', 'alpha') || GETPOST('posmodule', 'alpha') == '-1')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Module")), null, 'errors');
		$action='create';
		$error++;
	}
	if (GETPOST('posnumber', 'alpha') == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CashDesk")), null, 'errors');
		$action='create';
		$error++;
	}
	if (! GETPOST('closeyear', 'alpha') || GETPOST('closeyear', 'alpha') == '-1')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Year")), null, 'errors');
		$action='create';
		$error++;
	}
}
elseif ($action=="add")
{
	if (GETPOST('opening', 'alpha') == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InitialBankBalance")), null, 'errors');
		$action='start';
		$error++;
	}
	$error=0;
	foreach($arrayofpaymentmode as $key=>$val)
	{
		if (GETPOST($key.'_amount', 'alpha') == '')
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val)), null, 'errors');
			$action='start';
			$error++;
		}
		else
		{
			$object->$key = price2num(GETPOST($key.'_amount', 'alpha'));
		}
	}

	if (! $error)
	{
		$object->day_close = GETPOST('closeday', 'int');
		$object->month_close = GETPOST('closemonth', 'int');
		$object->year_close = GETPOST('closeyear', 'int');

	    $object->opening=price2num(GETPOST('opening', 'alpha'));
	    $object->posmodule=GETPOST('posmodule', 'alpha');
		$object->posnumber=GETPOST('posnumber', 'alpha');

		$db->begin();

		$id=$object->create($user);

		if ($id > 0)
		{
			$db->commit();
			$action="view";
		}
		else
		{
			$db->rollback;
			$action="view";
		}
	}
}

if ($action=="close")
{
	$object->fetch($id);

    $result = $object->valid($user);
	if ($result <= 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
	else
	{
		setEventMessages($langs->trans("CashFenceDone"), null);
	}

    $action="view";
}

// Action to delete
if ($action == 'confirm_delete' && ! empty($permissiontodelete))
{
    $object->fetch($id);

    if (! ($object->id > 0))
    {
        dol_print_error('', 'Error, object must be fetched before being deleted');
        exit;
    }

    $result=$object->delete($user);
    //var_dump($result);
    if ($result > 0)
    {
        // Delete OK
        setEventMessages("RecordDeleted", null, 'mesgs');
        header("Location: ".$backurlforlist);
        exit;
    }
    else
    {
        if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
        else setEventMessages($object->error, null, 'errors');
    }
}


/*
 * View
 */

$form=new Form($db);

if ($action=="create" || $action=="start")
{
	llxHeader();

	$initialbalanceforterminal=array();
	$theoricalamountforterminal=array();
	$theoricalnbofinvoiceforterminal=array();

	if (GETPOST('posnumber', 'alpha') != '' && GETPOST('posnumber', 'alpha') != '' && GETPOST('posnumber', 'alpha') != '-1')
	{
		$posmodule = GETPOST('posmodule', 'alpha');
		$terminalid = GETPOST('posnumber', 'alpha');
		$terminaltouse = $terminalid;

		if ($terminaltouse == '1' && $posmodule=='cashdesk') $terminaltouse = '';

		if ($posmodule=='cashdesk' && $terminaltouse != '' && $terminaltouse != '1') {
			$terminaltouse = '';
			setEventMessages($langs->trans("OnlyTerminal1IsAvailableForCashDeskModule"), null, 'errors');
			$error++;
		}

		// Calculate $initialbalanceforterminal for terminal 0
		foreach($arrayofpaymentmode as $key => $val)
		{
			if ($key != 'cash')
			{
				$initialbalanceforterminal[$terminalid][$key] = 0;
				continue;
			}

			// Get the bank account dedicated to this point of sale module/terminal
			$vartouse='CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse;
			$bankid = $conf->global->$vartouse;			// This value is ok for 'Terminal 0' for module 'CashDesk' and 'TakePos' (they manage only 1 terminal)
			// Hook to get the good bank id according to posmodule and posnumber.
			// @TODO add hook here

			if ($bankid > 0)
			{
    			$sql = "SELECT SUM(amount) as total FROM ".MAIN_DB_PREFIX."bank";
    			$sql.= " WHERE fk_account = ".$bankid;
    			if ($syear && ! $smonth)              $sql.= " AND dateo < '".$db->idate(dol_get_first_day($syear, 1))."'";
    			elseif ($syear && $smonth && ! $sday) $sql.= " AND dateo < '".$db->idate(dol_get_first_day($syear, $smonth))."'";
    			elseif ($syear && $smonth && $sday)   $sql.= " AND dateo < '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."'";
    			else dol_print_error('', 'Year not defined');

    			$resql = $db->query($sql);
    			if ($resql)
    			{
    				$obj = $db->fetch_object($resql);
    				if ($obj) $initialbalanceforterminal[$terminalid][$key] = $obj->total;
    			}
    			else dol_print_error($db);
			}
			else
			{
				setEventMessages($langs->trans("SetupOfTerminalNotComplete", $terminaltouse), null, 'errors');
			    $error++;
			}
		}

		// Calculate $theoricalamountforterminal for terminal 0
		foreach($arrayofpaymentmode as $key => $val)
		{
			/*$sql = "SELECT SUM(amount) as total FROM ".MAIN_DB_PREFIX."bank";
			$sql.= " WHERE fk_account = ".$bankid;*/
			$sql = "SELECT SUM(pf.amount) as total, COUNT(*) as nb";
			$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as cp";
			$sql.= " WHERE pf.fk_facture = f.rowid AND p.rowid = pf.fk_paiement AND cp.id = p.fk_paiement";
			$sql.= " AND f.module_source = '".$db->escape($posmodule)."'";
			$sql.= " AND f.pos_source = '".$db->escape($terminalid)."'";
			$sql.= " AND f.paye = 1";
			$sql.= " AND p.entity IN (".getEntity('facture').")";
			if ($key == 'cash')       $sql.=" AND cp.code = 'LIQ'";
			elseif ($key == 'cheque') $sql.=" AND cp.code = 'CHQ'";
			elseif ($key == 'card')   $sql.=" AND cp.code = 'CB'";
			else
			{
				dol_print_error('Value for key = '.$key.' not supported');
				exit;
			}
			if ($syear && ! $smonth)              $sql.= " AND datef BETWEEN '".$db->idate(dol_get_first_day($syear, 1))."' AND '".$db->idate(dol_get_last_day($syear, 12))."'";
			elseif ($syear && $smonth && ! $sday) $sql.= " AND datef BETWEEN '".$db->idate(dol_get_first_day($syear, $smonth))."' AND '".$db->idate(dol_get_last_day($syear, $smonth))."'";
			elseif ($syear && $smonth && $sday)   $sql.= " AND datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
			else dol_print_error('', 'Year not defined');

			$resql = $db->query($sql);
			if ($resql)
			{
				$theoricalamountforterminal[$terminalid][$key] = $initialbalanceforterminal[$terminalid][$key];

				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					$theoricalamountforterminal[$terminalid][$key] = price2num($theoricalamountforterminal[$terminalid][$key] + $obj->total);
					$theoricalnbofinvoiceforterminal[$terminalid][$key] = $obj->nb;
				}
			}
			else dol_print_error($db);
		}
	}

	print load_fiche_titre($langs->trans("CashControl")." - ".$langs->trans("New"), '', 'title_bank.png');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    if ($action == 'start' && GETPOST('posnumber', 'int') != '' && GETPOST('posnumber', 'int') != '' && GETPOST('posnumber', 'int') != '-1')
    {
	    print '<input type="hidden" name="action" value="add">';
    }
    else
    {
    	print '<input type="hidden" name="action" value="start">';
    }
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Module").'</td>';
    print '<td>'.$langs->trans("CashDesk").' ID</td>';
    print '<td>'.$langs->trans("Year").'</td>';
    print '<td>'.$langs->trans("Month").'</td>';
    print '<td>'.$langs->trans("Day").'</td>';
    print '<td></td>';
    print "</tr>\n";

	$disabled=0;
	$prefix='close';

    print '<tr class="oddeven">';
    print '<td>'.$form->selectarray('posmodule', $arrayofposavailable, GETPOST('posmodule', 'alpha'), (count($arrayofposavailable)>1?1:0)).'</td>';
    print '<td>';
    $array=array(1=>"1", 2=>"2", 3=>"3", 4=>"4", 5=>"5", 6=>"6", 7=>"7", 8=>"8", 9=>"9");
    $selectedposnumber=0; $showempty=1;
    if ($conf->global->TAKEPOS_NUM_TERMINALS == '1')
    {
        $selectedposnumber=1; $showempty=0;
    }
    print $form->selectarray('posnumber', $array, GETPOSTISSET('posnumber')?GETPOST('posnumber', 'int'):$selectedposnumber, $showempty);
    //print '<input name="posnumber" type="text" class="maxwidth50" value="'.(GETPOSTISSET('posnumber')?GETPOST('posnumber', 'alpha'):'0').'">';
    print '</td>';
	// Year
	print '<td>';
	$retstring='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth75imp" id="'.$prefix.'year" name="'.$prefix.'year">';
	for ($year = $syear - 10; $year < $syear + 10 ; $year++)
	{
		$retstring.='<option value="'.$year.'"'.($year == $syear ? ' selected':'').'>'.$year.'</option>';
	}
	$retstring.="</select>\n";
	print $retstring;
	print '</td>';
	// Month
	print '<td>';
	$retstring='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth75imp" id="'.$prefix.'month" name="'.$prefix.'month">';
	$retstring.='<option value="0"></option>';
	for ($month = 1 ; $month <= 12 ; $month++)
	{
		$retstring.='<option value="'.$month.'"'.($month == $smonth?' selected':'').'>';
		$retstring.=dol_print_date(mktime(12, 0, 0, $month, 1, 2000), "%b");
		$retstring.="</option>";
	}
	$retstring.="</select>";
	print $retstring;
	print '</td>';
	// Day
	print '<td>';
	$retstring='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth50imp" id="'.$prefix.'day" name="'.$prefix.'day">';
	$retstring.='<option value="0" selected>&nbsp;</option>';
	for ($day = 1 ; $day <= 31; $day++)
	{
		$retstring.='<option value="'.$day.'"'.($day == $sday ? ' selected':'').'>'.$day.'</option>';
	}
	$retstring.="</select>";
	print $retstring;
	print '</td>';
	// Button Start
	print '<td>';
	if ($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1')
	{
		print '';
	}
	else
	{
		print '<input type="submit" name="add" class="button" value="'.$langs->trans("Start").'">';
	}
	print '</td>';
	print '</table>';

	// Table to see/enter balance
	if ($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1')
	{
		$posmodule = GETPOST('posmodule', 'alpha');
		$terminalid = GETPOST('posnumber', 'alpha');

		print '<br>';

		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td></td>';
		print '<td align="center">'.$langs->trans("InitialBankBalance");
		//print '<br>'.$langs->trans("TheoricalAmount").'<br>'.$langs->trans("RealAmount");
		print '</td>';
		print '<td align="center" class="hide0" colspan="'.count($arrayofpaymentmode).'">';
		print $langs->trans("AmountAtEndOfPeriod");
		print '</td>';
		print '<td></td>';
		print '</tr>';

		print '<tr class="liste_titre">';
		print '<td></td>';
		print '<td align="center">'.$langs->trans("Cash");
		//print '<br>'.$langs->trans("TheoricalAmount").'<br>'.$langs->trans("RealAmount");
		print '</td>';
		$i=0;
		foreach($arrayofpaymentmode as $key => $val)
		{
			print '<td align="center"'.($i == 0 ? ' class="hide0"':'').'>'.$langs->trans($val);
			//print '<br>'.$langs->trans("TheoricalAmount").'<br>'.$langs->trans("RealAmount");
			print '</td>';
			$i++;
		}
		print '<td></td>';
		print '</tr>';

		print '<tr>';
		// Initial amount
		print '<td>'.$langs->trans("NbOfInvoices").'</td>';
		print '<td align="center">';
		print '</td>';
		// Amount per payment type
		$i=0;
		foreach($arrayofpaymentmode as $key => $val)
		{
		    print '<td align="center"'.($i == 0 ? ' class="hide0"':'').'>';
		    print $theoricalnbofinvoiceforterminal[$terminalid][$key];
		    print '</td>';
		    $i++;
		}
		// Save
		print '<td align="center"></td>';
		print '</tr>';

		print '<tr>';
		// Initial amount
		print '<td>'.$langs->trans("TheoricalAmount").'</td>';
		print '<td align="center">';
		print price($initialbalanceforterminal[$terminalid]['cash']).'<br>';
		print '</td>';
		// Amount per payment type
		$i=0;
		foreach($arrayofpaymentmode as $key => $val)
		{
			print '<td align="center"'.($i == 0 ? ' class="hide0"':'').'>';
			print price($theoricalamountforterminal[$terminalid][$key]).'<br>';
			print '</td>';
			$i++;
		}
		// Save
		print '<td align="center"></td>';
		print '</tr>';

		print '<tr>';
		print '<td>'.$langs->trans("RealAmount").'</td>';
		// Initial amount
		print '<td align="center">';
		print '<input name="opening" type="text" class="maxwidth100 center" value="'.(GETPOSTISSET('opening')?price2num(GETPOST('opening', 'alpha')):price($initialbalanceforterminal[$terminalid]['cash'])).'">';
		print '</td>';
		// Amount per payment type
		$i=0;
		foreach($arrayofpaymentmode as $key => $val)
		{
			print '<td align="center"'.($i == 0 ? ' class="hide0"':'').'>';
			print '<input name="'.$key.'_amount" type="text"'.($key == 'cash'?' autofocus':'').' class="maxwidth100 center" value="'.GETPOST($key.'_amount', 'alpha').'">';
			print '</td>';
			$i++;
		}
		// Save
		print '<td align="center">';
		print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
		print '<input type="submit" name="add" class="button" value="'.$langs->trans("Save").'">';
		print '</td>';
		print '</tr>';

		print '</form>';
	}
    print '</form>';
}

if (empty($action) || $action=="view")
{
    $object->fetch($id);

    llxHeader('', $langs->trans("CashControl"));

    $head=array();
    $head[0][0] = DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_card.php?id='.$object->id;
    $head[0][1] = $langs->trans("Card");
    $head[0][2] = 'cashcontrol';

    dol_fiche_head($head, 'cashcontrol', $langs->trans("CashControl"), -1, 'cashcontrol');

    $linkback = '<a href="' . DOL_URL_ROOT . '/compta/cashcontrol/cashcontrol_list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref='<div class="refidno">';
    $morehtmlref.='</div>';


    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'rowid', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';

	print '<tr><td class="titlefield nowrap">';
	print $langs->trans("Ref");
	print '</td><td>';
	print $id;
	print '</td></tr>';

	print '<tr><td valign="middle">'.$langs->trans("Module").'</td><td>';
	print $object->posmodule;
	print "</td></tr>";

	print '<tr><td valign="middle">'.$langs->trans("CashDesk").' ID</td><td>';
	print $object->posnumber;
	print "</td></tr>";

	print '<tr><td class="nowrap">';
	print $langs->trans("Period");
	print '</td><td>';
	print $object->year_close."-".$object->month_close."-".$object->day_close;
	print '</td></tr>';

	print '</table>';
    print '</div>';

    print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';

    print '<tr><td class="titlefield nowrap">';
    print $langs->trans("DateCreationShort");
    print '</td><td>';
    print dol_print_date($object->date_creation, 'dayhour');
    print '</td></tr>';

    print '<tr><td valign="middle">'.$langs->trans("InitialBankBalance").' - '.$langs->trans("Cash").'</td><td>';
    print price($object->opening, 0, $langs, 1, -1, -1, $conf->currency);
    print "</td></tr>";

    foreach($arrayofpaymentmode as $key => $val)
    {
        print '<tr><td valign="middle">'.$langs->trans($val).'</td><td>';
    	print price($object->$key, 0, $langs, 1, -1, -1, $conf->currency);
    	print "</td></tr>";
    }

	print "</table>\n";
    print '</div>';
    print '</div></div>';
    print '<div style="clear:both"></div>';

    dol_fiche_end();

	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction"><a target="_blank" class="butAction" href="report.php?id='.$id.'">' . $langs->trans('PrintTicket') . '</a></div>';
	if ($object->status == CashControl::STATUS_DRAFT)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=close">' . $langs->trans('ValidateAndClose') . '</a></div>';

		print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=confirm_delete">' . $langs->trans('Delete') . '</a></div>';
	}
	print '</div>';

	print '<center><iframe src="report.php?id='.$id.'" width="60%" height="800"></iframe></center>';
}

// End of page
llxFooter();
$db->close();
