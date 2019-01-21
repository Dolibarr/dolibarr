<?php
/* Copyright (C) 2004      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Marcos García		 <marcosgdf@gmail.com>
 * Copyright (C) 2015	   Juanjo Menent		 <jmenent@2byte.es>
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
 *	    \file       htdocs/compta/paiement/card.php
 *		\ingroup    facture
 *		\brief      Page of a customer payment
 *		\remarks	Nearly same file than fournisseur/paiement/card.php
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
if (! empty($conf->banque->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills','banks','companies'));

$id=GETPOST('id','int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$backtopage=GETPOST('backtopage','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
// TODO ajouter regle pour restreindre acces paiement
//$result = restrictedArea($user, 'facture', $id,'');

$object = new Paiement($db);


/*
 * Actions
 */

if ($action == 'setnote' && $user->rights->facture->paiement)
{
    $db->begin();

    $object->fetch($id);
    $result = $object->update_note(GETPOST('note','none'));
    if ($result > 0)
    {
        $db->commit();
        $action='';
    }
    else
    {
	    setEventMessages($object->error, $object->errors, 'errors');
        $db->rollback();
    }
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->facture->paiement)
{
	$db->begin();

	$object->fetch($id);
	$result = $object->delete();
	if ($result > 0)
	{
        $db->commit();

        if ($backtopage)
        {
        	header("Location: ".$backtopage);
        	exit;
        }
        else
        {
        	header("Location: list.php");
        	exit;
        }
	}
	else
	{
	    $langs->load("errors");
		setEventMessages($object->error, $object->errors, 'errors');
        $db->rollback();
	}
}

if ($action == 'confirm_valide' && $confirm == 'yes' && $user->rights->facture->paiement)
{
	$db->begin();

    $object->fetch($id);
	if ($object->valide() > 0)
	{
		$db->commit();

		// Loop on each invoice linked to this payment to rebuild PDF
		$factures=array();
		foreach($factures as $id)
		{
			$fac = new Facture($db);
			$fac->fetch($id);

			$outputlangs = $langs;
			if (! empty($_REQUEST['lang_id']))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($_REQUEST['lang_id']);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$fac->generateDocument($fac->modelpdf, $outputlangs);
			}
		}

		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	}
	else
	{
	    $langs->load("errors");
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}

if ($action == 'setnum_paiement' && ! empty($_POST['num_paiement']))
{
	$object->fetch($id);
    $res = $object->update_num($_POST['num_paiement']);
	if ($res === 0)
	{
		setEventMessages($langs->trans('PaymentNumberUpdateSucceeded'), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans('PaymentNumberUpdateFailed'), null, 'errors');
	}
}

if ($action == 'setdatep' && ! empty($_POST['datepday']))
{
	$object->fetch($id);
    $datepaye = dol_mktime(12, 0, 0, $_POST['datepmonth'], $_POST['datepday'], $_POST['datepyear']);
	$res = $object->update_date($datepaye);
	if ($res === 0)
	{
		setEventMessages($langs->trans('PaymentDateUpdateSucceeded'), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans('PaymentDateUpdateFailed'), null, 'errors');
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("Payment"));

$thirdpartystatic=new Societe($db);

$result=$object->fetch($id, $ref);
if ($result <= 0)
{
	dol_print_error($db,'Payement '.$id.' not found in database');
	exit;
}

$form = new Form($db);

$head = payment_prepare_head($object);

dol_fiche_head($head, 'payment', $langs->trans("PaymentCustomerInvoice"), -1, 'payment');

/*
 * Confirmation de la suppression du paiement
 */
if ($action == 'delete')
{
	print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete','',0,2);

}

/*
 * Confirmation de la validation du paiement
 */
if ($action == 'valide')
{
	$facid = $_GET['facid'];
	print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide','',0,2);

}

$linkback = '<a href="' . DOL_URL_ROOT . '/compta/paiement/list.php">' . $langs->trans("BackToList") . '</a>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', '');


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">'."\n";

// Date payment
print '<tr><td class="titlefield">'.$form->editfieldkey("Date",'datep',$object->date,$object,$user->rights->facture->paiement).'</td><td>';
print $form->editfieldval("Date",'datep',$object->date,$object,$user->rights->facture->paiement,'datepicker','',null,$langs->trans('PaymentDateUpdateSucceeded'));
print '</td></tr>';

// Payment type (VIR, LIQ, ...)
$labeltype=$langs->trans("PaymentType".$object->type_code)!=("PaymentType".$object->type_code)?$langs->trans("PaymentType".$object->type_code):$object->type_libelle;
print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>'.$labeltype.'</td></tr>';

$disable_delete = 0;
// Bank account
if (! empty($conf->banque->enabled))
{
	if ($object->fk_account > 0)
	{
		$bankline=new AccountLine($db);
		$bankline->fetch($object->bank_line);
		if ($bankline->rappro)
		{
			$disable_delete = 1;
			$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
		}

		print '<tr>';
		print '<td>'.$langs->trans('BankAccount').'</td>';
		print '<td>';
		$accountstatic=new Account($db);
		$accountstatic->fetch($bankline->fk_account);
		print $accountstatic->getNomUrl(1);
		print '</td>';
		print '</tr>';
	}
}

// Payment numero
/*
$titlefield=$langs->trans('Numero').' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
print '<tr><td>'.$form->editfieldkey($titlefield,'num_paiement',$object->num_paiement,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td>';
print $form->editfieldval($titlefield,'num_paiement',$object->num_paiement,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('PaymentNumberUpdateSucceeded'));
print '</td></tr>';

// Check transmitter
$titlefield=$langs->trans('CheckTransmitter').' <em>('.$langs->trans("ChequeMaker").')</em>';
print '<tr><td>'.$form->editfieldkey($titlefield,'chqemetteur',$object->,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td>';
print $form->editfieldval($titlefield,'chqemetteur',$object->aaa,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('ChequeMakeUpdateSucceeded'));
print '</td></tr>';

// Bank name
$titlefield=$langs->trans('Bank').' <em>('.$langs->trans("ChequeBank").')</em>';
print '<tr><td>'.$form->editfieldkey($titlefield,'chqbank',$object->aaa,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td>';
print $form->editfieldval($titlefield,'chqbank',$object->aaa,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('ChequeBankUpdateSucceeded'));
print '</td></tr>';
*/

// Bank account
if (! empty($conf->banque->enabled))
{
	if ($object->fk_account > 0)
	{
		if ($object->type_code == 'CHQ' && $bankline->fk_bordereau > 0)
		{
			dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
			$bordereau = new RemiseCheque($db);
			$bordereau->fetch($bankline->fk_bordereau);

			print '<tr>';
			print '<td>'.$langs->trans('CheckReceipt').'</td>';
			print '<td>';
			print $bordereau->getNomUrl(1);
			print '</td>';
			print '</tr>';
		}
	}

	print '<tr>';
	print '<td>'.$langs->trans('BankTransactionLine').'</td>';
	print '<td>';
	print $bankline->getNomUrl(1,0,'showconciliated');
	print '</td>';
	print '</tr>';
}

// Comments
print '<tr><td class="tdtop">'.$form->editfieldkey("Comments",'note',$object->note,$object,$user->rights->facture->paiement).'</td><td>';
print $form->editfieldval("Note",'note',$object->note,$object,$user->rights->facture->paiement,'textarea:'.ROWS_3.':90%');
print '</td></tr>';

// Amount
print '<tr><td>'.$langs->trans('Amount').'</td><td>'.price($object->amount,'',$langs,0,-1,-1,$conf->currency).'</td></tr>';

print '</table>';

print '</div>';

dol_fiche_end();


/*
 * List of invoices
 */

$sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.total_ttc, f.paye, f.fk_statut, pf.amount, s.nom as name, s.rowid as socid';
$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
$sql.= ' WHERE pf.fk_facture = f.rowid';
$sql.= ' AND f.fk_soc = s.rowid';
$sql.= ' AND f.entity = '.$conf->entity;
$sql.= ' AND pf.fk_paiement = '.$object->id;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;

	$moreforfilter='';

	print '<br>';

	print '<div class="div-table-responsive">';
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Bill').'</td>';
	print '<td>'.$langs->trans('Company').'</td>';
	print '<td align="right">'.$langs->trans('ExpectedToPay').'</td>';
    print '<td align="right">'.$langs->trans('PayedByThisPayment').'</td>';
    print '<td align="right">'.$langs->trans('RemainderToPay').'</td>';
    print '<td align="right">'.$langs->trans('Status').'</td>';
	print "</tr>\n";

	if ($num > 0)
	{
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			$thirdpartystatic->fetch($objp->socid);

			$invoice=new Facture($db);
			$invoice->fetch($objp->facid);

			$paiement = $invoice->getSommePaiement();
			$creditnotes=$invoice->getSumCreditNotesUsed();
			$deposits=$invoice->getSumDepositsUsed();
			$alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
			$remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');

			print '<tr class="oddeven">';

            // Invoice
			print '<td>';
			print $invoice->getNomUrl(1);
			print "</td>\n";

			// Third party
			print '<td>';
			print $thirdpartystatic->getNomUrl(1);
			print '</td>';

			// Expected to pay
			print '<td align="right">'.price($objp->total_ttc).'</td>';

            // Amount payed
            print '<td align="right">'.price($objp->amount).'</td>';

            // Remain to pay
            print '<td align="right">'.price($remaintopay).'</td>';

			// Status
			print '<td align="right">'.$invoice->getLibStatut(5, $alreadypayed).'</td>';

			print "</tr>\n";
			if ($objp->paye == 1)	// If at least one invoice is paid, disable delete
			{
				$disable_delete = 1;
				$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemovePaymentWithOneInvoicePaid"));
			}
			$total = $total + $objp->amount;
			$i++;
		}
	}


	print "</table>\n";
	print '</div>';

	$db->free($resql);
}
else
{
	dol_print_error($db);
}



/*
 * Boutons Actions
 */

print '<div class="tabsAction">';

if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
{
	if ($user->societe_id == 0 && $object->statut == 0 && $_GET['action'] == '')
	{
		if ($user->rights->facture->paiement)
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
		}
	}
}

if ($user->societe_id == 0 && $action == '')
{
	if ($user->rights->facture->paiement)
	{
		if (! $disable_delete)
		{
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$title_button.'">'.$langs->trans('Delete').'</a>';
		}
	}
}

print '</div>';

llxFooter();

$db->close();
