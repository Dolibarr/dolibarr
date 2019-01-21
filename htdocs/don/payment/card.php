<?php
/* Copyright (C) 2015       Alexandre Spangaro	  	<aspangaro.dolibarr@gmail.com>
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
 *	    \file       htdocs/don/payment/card.php
 *		\ingroup    donations
 *		\brief      Tab payment of a donation
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/paymentdonation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
if (! empty($conf->banque->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load('bills');
$langs->load('banks');
$langs->load('companies');

// Security check
$id=GETPOST('rowid')?GETPOST('rowid','int'):GETPOST('id','int');
$action=GETPOST('action','aZ09');
$confirm=GETPOST('confirm');
if ($user->societe_id) $socid=$user->societe_id;
// TODO Add rule to restrict access payment
//$result = restrictedArea($user, 'facture', $id,'');

$object = new PaymentDonation($db);
if ($id > 0)
{
	$result=$object->fetch($id);
	if (! $result) dol_print_error($db,'Failed to get payment id '.$id);
}


/*
 * Actions
 */

// Delete payment
if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->don->supprimer)
{
	$db->begin();

	$result = $object->delete($user);
	if ($result > 0)
	{
        $db->commit();
        header("Location: ".DOL_URL_ROOT."/don/index.php");
        exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
        $db->rollback();
	}
}

// Create payment
if ($action == 'confirm_valide' && $confirm == 'yes' && $user->rights->don->creer)
{
	$db->begin();

	$result=$object->valide();

	if ($result > 0)
	{
		$db->commit();

		$factures=array();	// TODO Get all id of invoices linked to this payment
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

		header('Location: card.php?id='.$object->id);
		exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}


/*
 * View
 */

llxHeader();

$don = new Don($db);
$form = new Form($db);

$h=0;

$head[$h][0] = DOL_URL_ROOT.'/don/payment/card.php?id='.$id;
$head[$h][1] = $langs->trans("Card");
$hselected = $h;
$h++;

dol_fiche_head($head, $hselected, $langs->trans("DonationPayment"), -1, 'payment');

/*
 * Confirm deleting of the payment
 */
if ($action == 'delete')
{
	print $form->formconfirm('card.php?id='.$object->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete','',0,2);

}

/*
 * Confirm validation of the payment
 */
if ($action == 'valide')
{
	$facid = GETPOST('facid','int');
	print $form->formconfirm('card.php?id='.$object->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide','',0,2);

}


dol_banner_tab($object,'id','',1,'rowid','id');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border" width="100%">';

// Ref
/*print '<tr><td class=">'.$langs->trans('Ref').'</td>';
print '<td colspan="3">';
print $form->showrefnav($object,'id','',1,'rowid','id');
print '</td></tr>';
*/

// Date
print '<tr><td class="titlefield">'.$langs->trans('Date').'</td><td>'.dol_print_date($object->datep,'day').'</td></tr>';

// Mode
print '<tr><td>'.$langs->trans('Mode').'</td><td>'.$langs->trans("PaymentType".$object->type_code).'</td></tr>';

// Number
print '<tr><td>'.$langs->trans('Number').'</td><td>'.$object->num_payment.'</td></tr>';

// Amount
print '<tr><td>'.$langs->trans('Amount').'</td><td>'.price($object->amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';

// Note
print '<tr><td>'.$langs->trans('Note').'</td><td>'.nl2br($object->note).'</td></tr>';

// Bank account
if (! empty($conf->banque->enabled))
{
    if ($object->bank_account)
    {
    	$bankline=new AccountLine($db);
    	$bankline->fetch($object->bank_line);

    	print '<tr>';
    	print '<td>'.$langs->trans('BankTransactionLine').'</td>';
		print '<td>';
		print $bankline->getNomUrl(1,0,'showall');
    	print '</td>';
    	print '</tr>';
    }
}

print '</table>';


/*
 * List of donations paid
 */

$disable_delete = 0;
$sql = 'SELECT d.rowid as did, d.paid, d.amount as d_amount, pd.amount';
$sql.= ' FROM '.MAIN_DB_PREFIX.'payment_donation as pd,'.MAIN_DB_PREFIX.'don as d';
$sql.= ' WHERE pd.fk_donation = d.rowid';
$sql.= ' AND d.entity = '.$conf->entity;
$sql.= ' AND pd.rowid = '.$id;

dol_syslog("don/payment/card.php", LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<br><table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Donation').'</td>';
    print '<td align="right">'.$langs->trans('ExpectedToPay').'</td>';
	print '<td align="center">'.$langs->trans('Status').'</td>';
	print '<td align="right">'.$langs->trans('PayedByThisPayment').'</td>';
	print "</tr>\n";

	if ($num > 0)
	{
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			// Ref
			print '<td>';
			$don->fetch($objp->did);
			print $don->getNomUrl(1);
			print "</td>\n";
			// Expected to pay
			print '<td align="right">'.price($objp->d_amount).'</td>';
			// Status
			print '<td align="center">'.$don->getLibStatut(4,$objp->amount).'</td>';
			// Amount payed
			print '<td align="right">'.price($objp->amount).'</td>';
			print "</tr>\n";
			if ($objp->paid == 1)	// If at least one invoice is paid, disable delete
			{
				$disable_delete = 1;
			}
			$total = $total + $objp->amount;
			$i++;
		}
	}


	print "</table>\n";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print '</div>';

dol_fiche_end();


/*
 * Actions buttons
 */
print '<div class="tabsAction">';

/*
if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
{
	if ($user->societe_id == 0 && $object->statut == 0 && $_GET['action'] == '')
	{
		if ($user->rights->facture->paiement)
		{
			print '<a class="butAction" href="card.php?id='.$_GET['id'].'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
		}
	}
}
*/

if ($_GET['action'] == '')
{
	if ($user->rights->don->supprimer)
	{
		if (! $disable_delete)
		{
			print '<a class="butActionDelete" href="card.php?id='.$_GET['id'].'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("CantRemovePaymentWithOneInvoicePaid")).'">'.$langs->trans('Delete').'</a>';
		}
	}
}

print '</div>';



llxFooter();

$db->close();
