<?php
/* Copyright (C) 2014		Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
        setEventMessage($langs->trans('LoanPaid'));
    }
    else
    {
        setEventMessage($loan->error, 'errors');
    }
}

// Delete loan
if ($action == 'confirm_delete' && $confirm == 'yes')
{
	$object->fetch($id);
	$result=$object->delete($user);
	if ($result > 0)
	{
		setEventMessage($langs->trans('LoanDeleted'));
		header("Location: index.php");
		exit;
	}
	else
	{
		setEventMessage($loan->error, 'errors');
	}
}

// Add loan
if ($action == 'add' && $user->rights->loan->write)
{
	if (! $cancel)
	{
		$datestart=@dol_mktime(12,0,0, $_POST["startmonth"], $_POST["startday"], $_POST["startyear"]);
		$dateend=@dol_mktime(12,0,0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
		
		if (! $datestart)
		{
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("DateStart")), 'errors');
			$action = 'create';
		}
		elseif (! $dateend)
		{
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("DateEnd")), 'errors');
			$action = 'create';
		}
		elseif (! $_POST["capital"])
		{
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Capital")), 'errors');
			$action = 'create';
		}
		else
		{
			$object->label		= $_POST["label"];
			$object->fk_bank	= $_POST["accountid"];
			$object->capital	= $_POST["capital"];
			$object->datestart	= $datestart;
			$object->dateend	= $dateend;
			$object->nbterm		= $_POST["nbterm"];
			$object->rate		= $_POST["rate"];
			$object->note_private = GETPOST('note_private');
			$object->note_public = GETPOST('note_public');

			$object->account_capital	= $_POST["accountancy_account_capital"];
			$object->account_insurance	= $_POST["accountancy_account_insurance"];
			$object->account_interest	= $_POST["accountancy_account_interest"];

			$id=$object->create($user);
			if ($id <= 0)
			{
				setEventMessage($object->error, 'errors');
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
			$object->label		= GETPOST("label");
			$object->datestart	= dol_mktime(12, 0, 0, GETPOST('startmonth','int'), GETPOST('startday','int'), GETPOST('startyear','int'));
			$object->dateend	= dol_mktime(12, 0, 0, GETPOST('endmonth','int'), GETPOST('endday','int'), GETPOST('endyear','int'));
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
	        setEventMessage($object->error, 'errors');
        }
    }
    else
    {
        header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
        exit;
    }
}

/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Loan|FR:Module_Emprunt';
llxHeader("",$langs->trans("Loan"),$help_url);


// Create mode
if ($action == 'create')
{
	//WYSIWYG Editor
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

    print_fiche_titre($langs->trans("NewLoan"));

    $datec = dol_mktime(12, 0, 0, GETPOST('remonth','int'), GETPOST('reday','int'), GETPOST('reyear','int'));

    print '<form name="loan" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border" width="100%">';

	// Label
	print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3"><input name="label" size="40" maxlength="255" value="'.dol_escape_htmltag(GETPOST('label')).'"></td></tr>';

	// Bank account
	if (! empty($conf->banque->enabled))
	{
		print '<tr><td class="fieldrequired">'.$langs->trans("Account").'</td><td>';
		$form->select_comptes($GETPOST["accountid"],"accountid",0,"courant=1",1);  // Show list of bank account with courant
		print '</td></tr>';
	}
	else
	{
		print '<tr><td>'.$langs->trans("Account").'</td><td>';
		print $langs->trans("NoBankAccountDefined");
		print '</td></tr>';
	}

    // Capital
    print '<tr><td class="fieldrequired">'.$langs->trans("Capital").'</td><td><input name="capital" size="10" value="' . GETPOST("capital") . '"></td></tr>';

	// Date Start
	print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("DateStart").'</td><td>';
    print $form->select_date($datestart?$datestart:-1,'start','','','','add',1,1);
    print '</td></tr>';

	// Date End
	print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("DateEnd").'</td><td>';
    print $form->select_date($dateend?$dateend:-1,'end','','','','add',1,1);
    print '</td></tr>';

	// Number of terms
	print '<tr><td class="fieldrequired">'.$langs->trans("Nbterms").'</td><td><input name="nbterm" size="5" value="' . GETPOST('nbterm') . '"></td></tr>';

	// Rate
    print '<tr><td class="fieldrequired">'.$langs->trans("Rate").'</td><td><input name="rate" size="5" value="' . GETPOST("rate") . '"> %</td></tr>';

    // Note Private
    print '<tr>';
    print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
    print '<td valign="top" colspan="2">';

    $doleditor = new DolEditor('note_private', GETPOST('note_private', 'alpha'), '', 200, 'dolibarr_notes', 'In', false, true, true, ROWS_8, 100);
    print $doleditor->Create(1);

    print '</td></tr>';

    // Note Public
    print '<tr>';
    print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
    print '<td valign="top" colspan="2">';
    $doleditor = new DolEditor('note_public', GETPOST('note_public', 'alpha'), '', 200, 'dolibarr_notes', 'In', false, true, true, ROWS_8, 100);
    print $doleditor->Create(1);
    print '</td></tr>';

	print '</table>';

	print '<br>';

	// Accountancy
	print '<table class="border" width="100%">';

	if ($conf->accounting->enabled)
	{
		print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("LoanAccountancyCapitalCode").'</td>';
		print '<td><input name="accountancy_account_capital" size="16" value="'.$object->accountancy_account_capital.'">';
		print '</td></tr>';

		print '<tr><td class="fieldrequired">'.$langs->trans("LoanAccountancyInsuranceCode").'</td>';
		print '<td><input name="accountancy_account_insurance" size="16" value="'.$object->accountancy_account_insurance.'">';
		print '</td></tr>';

		print '<tr><td class="fieldrequired">'.$langs->trans("LoanAccountancyInterestCode").'</td>';
		print '<td><input name="accountancy_account_interest" size="16" value="'.$object->accountancy_account_interest.'">';
		print '</td></tr>';
	}
	else
	{
		print '<tr><td width="25%">'.$langs->trans("LoanAccountancyCapitalCode").'</td>';
		print '<td><input name="accountancy_account_capital" size="16" value="'.$object->accountancy_account_capital.'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("LoanAccountancyInsuranceCode").'</td>';
		print '<td><input name="accountancy_account_insurance" size="16" value="'.$object->accountancy_account_insurance.'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("LoanAccountancyInterestCode").'</td>';
		print '<td><input name="accountancy_account_interest" size="16" value="'.$object->accountancy_account_interest.'">';
		print '</td></tr>';
	}

	print '</table>';

    print '<br><center><input class="button" type="submit" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; ';
    print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

    print '</form>';
}

// View
if ($id > 0)
{
    $result = $object->fetch($id);

	if ($result > 0)
	{
		$head=loan_prepare_head($object);

		dol_fiche_head($head, 'card', $langs->trans("Loan"),0,'bill');

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

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object,'id');
		print "</td></tr>";

		// Label
		if ($action == 'edit')
		{
			print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">';
			print '<input type="text" name="label" size="40" value="'.$object->label.'">';
			print '</td></tr>';
		}
		else
		{
			print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$object->label.'</td></tr>';
		}

		// Capital
		print '<tr><td>'.$langs->trans("Capital").'</td><td>'.price($object->capital,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

    	// Date start
		print "<tr><td>".$langs->trans("DateStart")."</td>";
		print "<td>";
		if ($action == 'edit')
		{
			print $form->select_date($object->datestart, 'start', 0, 0, 0, 'update', 1);
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
			print $form->select_date($object->dateend, 'end', 0, 0, 0, 'update', 1);
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

        // Note Private
        print '<tr><td>'.$langs->trans('NotePrivate').'</td><td>'.nl2br($object->note_private).'</td></tr>';

        // Note Public
        print '<tr><td>'.$langs->trans('NotePublic').'</td><td>'.nl2br($object->note_public).'</td></tr>';

		// Status
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4, $totalpaye).'</td></tr>';

		print '</table>';

		if ($action == 'edit')
		{
			print '<br><div align="center">';
			print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print ' &nbsp; ';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';
			print '</form>';
		} 

		dol_fiche_end();

		print '<table class="border" width="100%">';
		print '<tr><td>';

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
			$i = 0; $total = 0;
			echo '<table class="nobordernopadding" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("RefPayment").'</td>';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("Type").'</td>';
			print '<td align="center" colspan="2">'.$langs->trans("Insurance").'</td>';
			print '<td align="center" colspan="2">'.$langs->trans("Interest").'</td>';
      		print '<td align="center" colspan="2">'.$langs->trans("Capital").'</td>';
      		print '<td>&nbsp;</td>';
      		print '</tr>';

			$var=True;
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var]."><td>";
				print '<a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"),"payment").' '.$objp->rowid.'</a></td>';
				print '<td>'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
				print "<td>".$objp->paiement_type.' '.$objp->num_payment."</td>\n";
				print '<td align="right">'.price($objp->amount_insurance)."</td><td>&nbsp;".$langs->trans("Currency".$conf->currency)."</td>\n";
				print '<td align="right">'.price($objp->amount_interest)."</td><td>&nbsp;".$langs->trans("Currency".$conf->currency)."</td>\n";
        		print '<td align="right">'.price($objp->amount_capital)."</td><td>&nbsp;".$langs->trans("Currency".$conf->currency)."</td>\n";
				print "</tr>";
				$totalpaid += $objp->amount_capital;
				$i++;
			}

			if ($object->paid == 0)
			{
				print '<tr><td colspan="7" align="right">'.$langs->trans("AlreadyPaid").' :</td><td align="right"><b>'.price($totalpaid).'</b></td><td>&nbsp;'.$langs->trans("Currency".$conf->currency).'</td></tr>';
				print '<tr><td colspan="7" align="right">'.$langs->trans("AmountExpected").' :</td><td align="right" bgcolor="#d0d0d0">'.price($object->capital).'</td><td bgcolor="#d0d0d0">&nbsp;'.$langs->trans("Currency".$conf->currency).'</td></tr>';

				$staytopay = $object->capital - $totalpaid;

				print '<tr><td colspan="7" align="right">'.$langs->trans("RemainderToPay").' :</td>';
				print '<td align="right" bgcolor="#f0f0f0"><b>'.price($staytopay).'</b></td><td bgcolor="#f0f0f0">&nbsp;'.$langs->trans("Currency".$conf->currency).'</td></tr>';
			}
			print "</table>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
		print "</td></tr>";
		print "</table>";

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
		// Loan not find
		dol_print_error('',$object->error);
	}
}

llxFooter();

$db->close();
