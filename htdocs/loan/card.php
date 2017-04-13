<?php
/* Copyright (C) 2014-2016	Alexandre Spangaro   <aspangaro@zendsi.com>
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
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
 *      \file       htdocs/loan/card.php
 *		\ingroup    loan
 *		\brief      Loan card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/html.formventilation.class.php';

$langs->load("compta");
$langs->load("bills");
$langs->load("loan");

$id=GETPOST('id','int');
$action=GETPOST('action');
$confirm=GETPOST('confirm');
$cancel=GETPOST('cancel','alpha');

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'loan', $id, '','');

$object = new Loan($db);

/*
 * Actions
 */

// Classify paid
if ($action == 'confirm_paid' && $confirm == 'yes')
{
	$object->fetch($id);
	$result = $object->set_paid($user);
    if ($result > 0)
    {
        setEventMessages($langs->trans('LoanPaid'), null, 'mesgs');
    }
    else
    {
        setEventMessages($loan->error, null, 'errors');
    }
}

// Delete loan
if ($action == 'confirm_delete' && $confirm == 'yes')
{
	$object->fetch($id);
	$result=$object->delete($user);
	if ($result > 0)
	{
		setEventMessages($langs->trans('LoanDeleted'), null, 'mesgs');
		header("Location: index.php");
		exit;
	}
	else
	{
		setEventMessages($loan->error, null, 'errors');
	}
}

// Add loan
if ($action == 'add' && $user->rights->loan->write)
{
	if (! $cancel)
	{
		$datestart	= dol_mktime(12, 0, 0, GETPOST('startmonth','int'), GETPOST('startday','int'), GETPOST('startyear','int'));
		$dateend	= dol_mktime(12, 0, 0, GETPOST('endmonth','int'), GETPOST('endday','int'), GETPOST('endyear','int'));
		$capital 	= price2num(GETPOST('capital'));

		if (! $datestart)
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("DateStart")), null, 'errors');
			$action = 'create';
		}
		elseif (! $dateend)
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("DateEnd")), null, 'errors');
			$action = 'create';
		}
		elseif (! $capital)
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("LoanCapital")), null, 'errors');
			$action = 'create';
		}
		else
		{
			$object->label					= GETPOST('label');
			$object->fk_bank				= GETPOST('accountid');
			$object->capital				= $capital;
			$object->datestart				= $datestart;
			$object->dateend				= $dateend;
			$object->nbterm					= GETPOST('nbterm');
			$object->rate					= GETPOST('rate');
			$object->note_private 			= GETPOST('note_private');
			$object->note_public 			= GETPOST('note_public');

			$accountancy_account_capital	= GETPOST('accountancy_account_capital');
			$accountancy_account_insurance	= GETPOST('accountancy_account_insurance');
			$accountancy_account_interest	= GETPOST('accountancy_account_interest');

			if ($accountancy_account_capital <= 0) { $object->account_capital = ''; } else { $object->account_capital = $accountancy_account_capital; }
			if ($accountancy_account_insurance <= 0) { $object->account_insurance = ''; } else { $object->account_insurance = $accountancy_account_insurance; }
			if ($accountancy_account_interest <= 0) { $object->account_interest = ''; } else { $object->account_interest = $accountancy_account_interest; }

			$id=$object->create($user);
			if ($id <= 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
	else
	{
		header("Location: index.php");
		exit();
	}
}

// Update record
else if ($action == 'update' && $user->rights->loan->write)
{
	if (! $cancel)
	{
		$result = $object->fetch($id);

		if ($object->fetch($id))
		{
			$object->datestart	= dol_mktime(12, 0, 0, GETPOST('startmonth','int'), GETPOST('startday','int'), GETPOST('startyear','int'));
			$object->dateend	= dol_mktime(12, 0, 0, GETPOST('endmonth','int'), GETPOST('endday','int'), GETPOST('endyear','int'));
			$object->capital	= price2num(GETPOST("capital"));
			$object->nbterm		= GETPOST("nbterm");
			$object->rate		= GETPOST("rate");
		}

        $result = $object->update($user);

        if ($result > 0)
        {
            header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
            exit;
        }
        else
        {
	        setEventMessages($object->error, $object->errors, 'errors');
        }
    }
    else
    {
        header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
        exit;
    }
}

if ($action == 'setlabel' && $user->rights->loan->write)
{
	$object->fetch($id);
	$result = $object->setValueFrom('label', GETPOST('label'), '', '', 'text', '', $user, 'LOAN_MODIFY');
	if ($result < 0)
	setEventMessages($object->error, $object->errors, 'errors');
}

/*
 * View
 */

$form = new Form($db);
if (! empty($conf->accounting->enabled)) $formaccountancy = New FormVentilation($db);

$title = $langs->trans("Loan") . ' - ' . $langs->trans("Card");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';
llxHeader("",$title,$help_url);


// Create mode
if ($action == 'create')
{
	//WYSIWYG Editor
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

    print load_fiche_titre($langs->trans("NewLoan"), '', 'title_accountancy.png');

    $datec = dol_mktime(12, 0, 0, GETPOST('remonth','int'), GETPOST('reday','int'), GETPOST('reyear','int'));

    print '<form name="loan" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

	dol_fiche_head();

    print '<table class="border" width="100%">';

	// Label
	print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("Label").'</td><td><input name="label" size="40" maxlength="255" value="'.dol_escape_htmltag(GETPOST('label')).'"></td></tr>';

	// Bank account
	if (! empty($conf->banque->enabled))
	{
		print '<tr><td class="fieldrequired">'.$langs->trans("Account").'</td><td>';
		$form->select_comptes(GETPOST("accountid"),"accountid",0,"courant=1",1);  // Show list of bank account with courant
		print '</td></tr>';
	}
	else
	{
		print '<tr><td>'.$langs->trans("Account").'</td><td>';
		print $langs->trans("NoBankAccountDefined");
		print '</td></tr>';
	}

    // Capital
    print '<tr><td class="fieldrequired">'.$langs->trans("LoanCapital").'</td><td><input name="capital" size="10" value="' . dol_escape_htmltag(GETPOST("capital")) . '"></td></tr>';

	// Date Start
	print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("DateStart").'</td><td>';
    print $form->select_date($datestart?$datestart:-1,'start','','','','add',1,1,1);
    print '</td></tr>';

	// Date End
	print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("DateEnd").'</td><td>';
    print $form->select_date($dateend?$dateend:-1,'end','','','','add',1,1,1);
    print '</td></tr>';

	// Number of terms
	print '<tr><td class="fieldrequired">'.$langs->trans("Nbterms").'</td><td><input name="nbterm" size="5" value="' . dol_escape_htmltag(GETPOST('nbterm')) . '"></td></tr>';

	// Rate
    print '<tr><td class="fieldrequired">'.$langs->trans("Rate").'</td><td><input name="rate" size="5" value="' . dol_escape_htmltag(GETPOST("rate")) . '"> %</td></tr>';

    // Note Private
    print '<tr>';
    print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
    print '<td class="tdtop">';

    $doleditor = new DolEditor('note_private', GETPOST('note_private', 'alpha'), '', 160, 'dolibarr_notes', 'In', false, true, true, ROWS_6, '90%');
    print $doleditor->Create(1);

    print '</td></tr>';

    // Note Public
    print '<tr>';
    print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
    print '<td class="tdtop">';
    $doleditor = new DolEditor('note_public', GETPOST('note_public', 'alpha'), '', 160, 'dolibarr_notes', 'In', false, true, true, ROWS_6, '90%');
    print $doleditor->Create(1);
    print '</td></tr>';

    // Accountancy
	if (! empty($conf->accounting->enabled))
	{
		// Accountancy_account_capital
        print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("LoanAccountancyCapitalCode").'</td>';
        print '<td>';
		print $formaccountancy->select_account($object->accountancy_account_capital, 'accountancy_account_capital', 1, '', 0, 1);
        print '</td></tr>';

		// Accountancy_account_insurance
        print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("LoanAccountancyInsuranceCode").'</td>';
        print '<td>';
		print $formaccountancy->select_account($object->accountancy_account_insurance, 'accountancy_account_insurance', 1, '', 0, 1);
        print '</td></tr>';

		// Accountancy_account_interest
        print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("LoanAccountancyInterestCode").'</td>';
        print '<td>';
		print $formaccountancy->select_account($object->accountancy_account_interest, 'accountancy_account_interest', 1, '', 0, 1);
        print '</td></tr>';
	}
	else // For external software 
	{
        // Accountancy_account_capital
        print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("LoanAccountancyCapitalCode").'</td>';
        print '<td><input name="accountancy_account_capital" size="16" value="'.$object->accountancy_account_capital.'">';
        print '</td></tr>';

		// Accountancy_account_insurance
        print '<tr><td class="fieldrequired">'.$langs->trans("LoanAccountancyInsuranceCode").'</td>';
        print '<td><input name="accountancy_account_insurance" size="16" value="'.$object->accountancy_account_insurance.'">';
        print '</td></tr>';

		// Accountancy_account_interest
        print '<tr><td class="fieldrequired">'.$langs->trans("LoanAccountancyInterestCode").'</td>';
        print '<td><input name="accountancy_account_interest" size="16" value="'.$object->accountancy_account_interest.'">';
        print '</td></tr>';
	}
	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

    print '</form>';
}

// View
if ($id > 0)
{
	$object = new Loan($db);
    $result = $object->fetch($id);

	if ($result > 0)
	{
		$head=loan_prepare_head($object);

		$totalpaid = $object->getSumPayment();

		// Confirm for loan
		if ($action == 'paid')
		{
			$text=$langs->trans('ConfirmPayLoan');
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans('PayLoan'),$text,"confirm_paid",'','',2);
		}

		if ($action == 'delete')
		{
			$text=$langs->trans('ConfirmDeleteLoan');
			print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('DeleteLoan'),$text,'confirm_delete','','',2);
		}

		if ($action == 'edit')
		{
			print '<form name="update" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="'.$id.'">';
		}

		dol_fiche_head($head, 'card', $langs->trans("Loan"), 0, 'bill');

		$morehtmlref='<div class="refidno">';
		// Ref loan
		$morehtmlref.=$form->editfieldkey("Label", 'label', $object->label, $object, $user->rights->loan->write, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval("Label", 'label', $object->label, $object, $user->rights->loan->write, 'string', '', null, null, '', 1);
		$morehtmlref.='</div>';

		$linkback = '<a href="' . DOL_URL_ROOT . '/loan/index.php">' . $langs->trans("BackToList") . '</a>';

		$object->totalpaid = $totalpaid;   // To give a chance to dol_banner_tab to use already paid amount to show correct status

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		/*
		// Ref
		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>';
		print $form->showrefnav($object,'id');
		print "</td></tr>";

		// Label
		if ($action == 'edit')
		{
			print '<tr><td>'.$langs->trans("Label").'</td><td>';
			print '<input type="text" name="label" size="40" value="'.$object->label.'">';
			print '</td></tr>';
		}
		else
		{
			print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';
		}
		*/

		// Capital
		if ($action == 'edit')
		{
			print '<tr><td class="titlefield">'.$langs->trans("LoanCapital").'</td><td>';
			print '<input name="capital" size="10" value="' . $object->capital . '"></td></tr>';
			print '</td></tr>';
		}
		else
		{
			print '<tr><td class="titlefield">'.$langs->trans("LoanCapital").'</td><td>'.price($object->capital,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
		}

    	// Date start
		print "<tr><td>".$langs->trans("DateStart")."</td>";
		print "<td>";
		if ($action == 'edit')
		{
			print $form->select_date($object->datestart, 'start', 0, 0, 0, 'update', 1, 0, 1);
		}
		else
		{
			print dol_print_date($object->datestart,"day");
		}
		print "</td></tr>";

		// Date end
		print "<tr><td>".$langs->trans("DateEnd")."</td>";
		print "<td>";
		if ($action == 'edit')
		{
			print $form->select_date($object->dateend, 'end', 0, 0, 0, 'update', 1, 0, 1);
		}
		else
		{
			print dol_print_date($object->dateend,"day");
		}
		print "</td></tr>";

		// Nbterms
		print '<tr><td>'.$langs->trans("Nbterms").'</td><td>'.$object->nbterm.'</td></tr>';

		// Rate
		print '<tr><td>'.$langs->trans("Rate").'</td><td>'.$object->rate.' %</td></tr>';

        // Accountancy account capital
		print '<tr><td class="nowrap">';
        print $langs->trans("LoanAccountancyCapitalCode");
        print '</td><td>';
		if (! empty($conf->accounting->enabled)) {
			print length_accountg($object->account_capital);
        } else {
			print $object->account_capital;
		}
		print '</td></tr>';

        // Accountancy account insurance
		print '<tr><td class="nowrap">';
        print $langs->trans("LoanAccountancyInsuranceCode");
        print '</td><td>';
		if (! empty($conf->accounting->enabled)) {
			print length_accountg($object->account_insurance);
        } else {
			print $object->account_insurance;
		}
		print '</td></tr>';

		// Accountancy account interest
		print '<tr><td class="nowrap">';
        print $langs->trans("LoanAccountancyInterestCode");
        print '</td><td>';
		if (! empty($conf->accounting->enabled)) {
			print length_accountg($object->account_interest);
        } else {
			print $object->account_interest;
		}
		print '</td></tr>';

		// Status
		// print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4, $totalpaye).'</td></tr>';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';

		/*
		 * Payments
		 */
		$sql = "SELECT p.rowid, p.num_payment, datep as dp,";
		$sql.= " p.amount_capital, p.amount_insurance, p.amount_interest,";
		$sql.= " c.libelle as paiement_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."payment_loan as p";
		$sql.= ", ".MAIN_DB_PREFIX."c_paiement as c ";
		$sql.= ", ".MAIN_DB_PREFIX."loan as l";
		$sql.= " WHERE p.fk_loan = ".$id;
		$sql.= " AND p.fk_loan = l.rowid";
		$sql.= " AND l.entity = ".$conf->entity;
		$sql.= " AND p.fk_typepayment = c.id";
		$sql.= " ORDER BY dp DESC";

		//print $sql;
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
            $total_insurance = 0;
            $total_interest = 0;
            $total_capital = 0;
			print '<table class="noborder paymenttable">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("RefPayment").'</td>';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("Type").'</td>';
			print '<td align="right">'.$langs->trans("Insurance").'</td>';
			print '<td align="right">'.$langs->trans("Interest").'</td>';
      		print '<td align="right">'.$langs->trans("LoanCapital").'</td>';
      		print '</tr>';

			$var=True;
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td><a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"),"payment").' '.$objp->rowid.'</a></td>';
				print '<td>'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
				print "<td>".$objp->paiement_type.' '.$objp->num_payment."</td>\n";
                print '<td align="right">'.price($objp->amount_insurance, 0, $langs, 0, 0, -1, $conf->currency)."</td>\n";
                print '<td align="right">'.price($objp->amount_interest, 0, $langs, 0, 0, -1, $conf->currency)."</td>\n";
                print '<td align="right">'.price($objp->amount_capital, 0, $langs, 0, 0, -1, $conf->currency)."</td>\n";
				print "</tr>";
                $total_capital += $objp->amount_capital;
				$i++;
			}

			$totalpaid = $total_capital;

			if ($object->paid == 0)
			{
				print '<tr><td colspan="5" align="right">'.$langs->trans("AlreadyPaid").' :</td><td align="right">'.price($totalpaid, 0, $langs, 0, 0, -1, $conf->currency).'</td></tr>';
				print '<tr><td colspan="5" align="right">'.$langs->trans("AmountExpected").' :</td><td align="right">'.price($object->capital,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

				$staytopay = $object->capital - $totalpaid;

				print '<tr><td colspan="5" align="right">'.$langs->trans("RemainderToPay").' :</td>';
				print '<td align="right"><b>'.price($staytopay, 0, $langs, 0, 0, -1, $conf->currency).'</b></td></tr>';
			}
			print "</table>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		dol_fiche_end();

		if ($action == 'edit')
		{
			print '<div class="center">';
			print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';
		}

		if ($action == 'edit') print "</form>\n";

		/*
		 *   Buttons actions
		 */
		if ($action != 'edit')
		{
			print '<div class="tabsAction">';

			// Edit
			if ($user->rights->loan->write)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
			}

			// Emit payment
			if ($object->paid == 0 && ((price2num($object->capital) > 0 && round($staytopay) < 0) || (price2num($object->capital) > 0 && round($staytopay) > 0)) && $user->rights->loan->write)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/loan/payment/payment.php?id='.$object->id.'&amp;action=create">'.$langs->trans("DoPayment").'</a>';
			}

			// Classify 'paid'
			if ($object->paid == 0 && round($staytopay) <=0 && $user->rights->loan->write)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&amp;action=paid">'.$langs->trans("ClassifyPaid").'</a>';
			}

			// Delete
			if ($user->rights->loan->delete)
			{
				print '<a class="butActionDelete" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
			}

			print "</div>";
		}
	}
	else
	{
		// Loan not found
		dol_print_error('',$object->error);
	}
}

llxFooter();

$db->close();
