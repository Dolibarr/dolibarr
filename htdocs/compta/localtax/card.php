<?php
/* Copyright (C) 2011-2014      Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2015			Marcos García <marcosgdf@gmail.com>
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
 *	    \file       htdocs/compta/localtax/card.php
 *      \ingroup    tax
 *		\brief      Page of second or third tax payments (like IRPF for spain, ...)
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/localtax/class/localtax.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("compta");
$langs->load("banks");
$langs->load("bills");

$id=GETPOST("id",'int');
$action=GETPOST("action","alpha");
$refund=GETPOST("refund","int");
if (empty($refund)) $refund=0;

$lttype=GETPOST('localTaxType', 'int');

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');

$localtax = new Localtax($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('localtaxvatcard','globalcard'));


/**
 * Actions
 */

//add payment of localtax
if($_POST["cancel"] == $langs->trans("Cancel")){
	header("Location: list.php?localTaxType=".$lttype);
	exit;
}

if ($action == 'add' && $_POST["cancel"] <> $langs->trans("Cancel"))
{

    $db->begin();

    $datev=dol_mktime(12,0,0, $_POST["datevmonth"], $_POST["datevday"], $_POST["datevyear"]);
    $datep=dol_mktime(12,0,0, $_POST["datepmonth"], $_POST["datepday"], $_POST["datepyear"]);

    $localtax->accountid=GETPOST("accountid");
    $localtax->paymenttype=GETPOST("paiementtype");
    $localtax->datev=$datev;
    $localtax->datep=$datep;
    $localtax->amount=price2num(GETPOST("amount"));
	$localtax->label=GETPOST("label");
	$localtax->ltt=$lttype;

    $ret=$localtax->addPayment($user);
    if ($ret > 0)
    {
        $db->commit();
        header("Location: list.php?localTaxType=".$lttype);
        exit;
    }
    else
    {
        $db->rollback();
        setEventMessages($localtax->error, $localtax->errors, 'errors');
        $_GET["action"]="create";
    }
}

//delete payment of localtax
if ($action == 'delete')
{
    $result=$localtax->fetch($id);

	if ($localtax->rappro == 0)
	{
	    $db->begin();

	    $ret=$localtax->delete($user);
	    if ($ret > 0)
	    {
			if ($localtax->fk_bank)
			{
				$accountline=new AccountLine($db);
				$result=$accountline->fetch($localtax->fk_bank);
				if ($result > 0) $result=$accountline->delete($user);	// $result may be 0 if not found (when bank entry was deleted manually and fk_bank point to nothing)
			}

			if ($result >= 0)
			{
				$db->commit();
				header("Location: ".DOL_URL_ROOT.'/compta/localtax/list.php?localTaxType='.$localtax->ltt);
				exit;
			}
			else
			{
				$localtax->error=$accountline->error;
				$db->rollback();
				setEventMessages($localtax->error, $localtax->errors, 'errors');
			}
	    }
	    else
	    {
	        $db->rollback();
	        setEventMessages($localtax->error, $localtax->errors, 'errors');
	    }
	}
	else
	{
        $mesg='Error try do delete a line linked to a conciliated bank transaction';
        setEventMessages($mesg, null, 'errors');
	}
}


/*
*	View
*/

llxHeader();

$form = new Form($db);

if ($id)
{
    $vatpayment = new Localtax($db);
	$result = $vatpayment->fetch($id);
	if ($result <= 0)
	{
		dol_print_error($db);
		exit;
	}
}


if ($action == 'create')
{
    print load_fiche_titre($langs->transcountry($lttype==2?"newLT2Payment":"newLT1Payment",$mysoc->country_code));

    print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" name="formlocaltax" method="post">'."\n";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="localTaxType" value="'.$lttype.'">';
    print '<input type="hidden" name="action" value="add">';

    dol_fiche_head();

    print '<table class="border" width="100%">';

    print "<tr>";
    print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("DatePayment").'</td><td>';
    print $form->select_date($datep,"datep",'','','','add',1,1);
    print '</td></tr>';

    print '<tr><td class="fieldrequired">'.$langs->trans("DateValue").'</td><td>';
    print $form->select_date($datev,"datev",'','','','add',1,1);
    print '</td></tr>';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input name="label" class="minwidth200" value="'.($_POST["label"]?GETPOST("label",'',2):$langs->transcountry(($lttype==2?"LT2Payment":"LT1Payment"),$mysoc->country_code)).'"></td></tr>';

	// Amount
	print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input name="amount" size="10" value="'.GETPOST("amount").'"></td></tr>';

    if (! empty($conf->banque->enabled))
    {
		print '<tr><td class="fieldrequired">'.$langs->trans("Account").'</td><td>';
        $form->select_comptes($_POST["accountid"],"accountid",0,"courant=1",1);  // Affiche liste des comptes courant
        print '</td></tr>';

	    print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
	    $form->select_types_paiements(GETPOST("paiementtype"), "paiementtype");
	    print "</td>\n";
	    print "</tr>";

		// Number
		print '<tr><td>'.$langs->trans('Numero');
		print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
		print '<td><input name="num_payment" type="text" value="'.GETPOST("num_payment").'"></td></tr>'."\n";
    }
    // Other attributes
    $parameters=array();
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

    print '</form>';
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

if ($id)
{
	$h = 0;
	$head[$h][0] = DOL_URL_ROOT.'/compta/localtax/card.php?id='.$vatpayment->id;
	$head[$h][1] = $langs->trans('Card');
	$head[$h][2] = 'card';
	$h++;

	dol_fiche_head($head, 'card', $langs->trans("VATPayment"), 0, 'payment');


	print '<table class="border" width="100%">';

	print "<tr>";
	print '<td width="25%">'.$langs->trans("Ref").'</td><td>';
	print $vatpayment->ref;
	print '</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("DatePayment").'</td><td>';
	print dol_print_date($vatpayment->datep,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("DateValue").'</td><td>';
	print dol_print_date($vatpayment->datev,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($vatpayment->amount).'</td></tr>';

	if (! empty($conf->banque->enabled))
	{
		if ($vatpayment->fk_account > 0)
		{
 		   	$bankline=new AccountLine($db);
    		$bankline->fetch($vatpayment->fk_bank);

	    	print '<tr>';
	    	print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td>';
			print $bankline->getNomUrl(1,0,'showall');
	    	print '</td>';
	    	print '</tr>';
		}
	}

    // Other attributes
    $parameters=array();
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$vatpayment,$action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print '</table>';

	dol_fiche_end();


	/*
	* Boutons d'actions
	*/
	print "<div class=\"tabsAction\">\n";
	if ($vatpayment->rappro == 0)
		print '<a class="butActionDelete" href="card.php?id='.$vatpayment->id.'&action=delete">'.$langs->trans("Delete").'</a>';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("LinkedToAConcialitedTransaction").'">'.$langs->trans("Delete").'</a>';
	print "</div>";
}

llxFooter();
$db->close();

