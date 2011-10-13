<?php
/* Copyright (C) 2004      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
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
 *	    \file       htdocs/compta/paiement/fiche.php
 *		\ingroup    facture
 *		\brief      Page of a customer payment
 *		\remarks	Nearly same file than fournisseur/paiement/fiche.php
 *		\version    $Id: fiche.php,v 1.77 2011/08/05 21:06:55 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");
if ($conf->banque->enabled) require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');

$langs->load('bills');
$langs->load('banks');
$langs->load('companies');

// Security check
$id=GETPOST("id");
$action=GETPOST("action");
if ($user->societe_id) $socid=$user->societe_id;
// TODO ajouter regle pour restreindre acces paiement
//$result = restrictedArea($user, 'facture', $id,'');

$mesg='';


/*
 * Actions
 */

if ($action == 'setnote' && $user->rights->facture->paiement)
{
    $db->begin();

    $paiement = new Paiement($db);
    $paiement->fetch($id);
    $result = $paiement->update_note(GETPOST('note'));
    if ($result > 0)
    {
        $db->commit();
        $action='';
    }
    else
    {
        $mesg='<div class="error">'.$paiement->error.'</div>';
        $db->rollback();
    }
}

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' && $user->rights->facture->paiement)
{
	$db->begin();

	$paiement = new Paiement($db);
	$paiement->fetch($id);
	$result = $paiement->delete();
	if ($result > 0)
	{
        $db->commit();
        Header("Location: liste.php");
        exit;
	}
	else
	{
	    $langs->load("errors");
		$mesg='<div class="error">'.$langs->trans($paiement->error).'</div>';
        $db->rollback();
	}
}

if ($action == 'confirm_valide' && GETPOST('confirm') == 'yes' && $user->rights->facture->paiement)
{
	$db->begin();

	$paiement = new Paiement($db);
    $paiement->fetch($id);
	if ($paiement->valide() > 0)
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
			if (! empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) facture_pdf_create($db, $fac, '', $fac->modelpdf, $outputlangs);
		}

		Header('Location: fiche.php?id='.$paiement->id);
		exit;
	}
	else
	{
	    $langs->load("errors");
		$mesg='<div class="error">'.$langs->trans($paiement->error).'</div>';
		$db->rollback();
	}
}


/*
 * View
 */

llxHeader();

$thirdpartystatic=new Societe($db);

$paiement = new Paiement($db);
$result=$paiement->fetch($id);
if ($result <= 0)
{
	dol_print_error($db,'Payement '.$id.' not found in database');
	exit;
}

$html = new Form($db);

$h=0;

$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$id;
$head[$h][1] = $langs->trans("Card");
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/info.php?id='.$id;
$head[$h][1] = $langs->trans("Info");
$h++;


dol_fiche_head($head, $hselected, $langs->trans("PaymentCustomerInvoice"), 0, 'payment');

/*
 * Confirmation de la suppression du paiement
 */
if ($action == 'delete')
{
	$ret=$html->form_confirm('fiche.php?id='.$paiement->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete','',0,2);
	if ($ret == 'html') print '<br>';
}

/*
 * Confirmation de la validation du paiement
 */
if ($action == 'valide')
{
	$facid = $_GET['facid'];
	$ret=$html->form_confirm('fiche.php?id='.$paiement->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide','',0,2);
	if ($ret == 'html') print '<br>';
}


dol_htmloutput_mesg($mesg);


print '<table class="border" width="100%">';

// Ref
print '<tr><td valign="top" width="140">'.$langs->trans('Ref').'</td><td colspan="3">'.$paiement->id.'</td></tr>';

// Date
print '<tr><td valign="top" width="120">'.$langs->trans('Date').'</td><td colspan="3">'.dol_print_date($paiement->date,'day').'</td></tr>';

// Payment type (VIR, LIQ, ...)
$labeltype=$langs->trans("PaymentType".$paiement->type_code)!=("PaymentType".$paiement->type_code)?$langs->trans("PaymentType".$paiement->type_code):$paiement->type_libelle;
print '<tr><td valign="top">'.$langs->trans('Mode').'</td><td colspan="3">'.$labeltype.'</td></tr>';

// Numero
//if ($paiement->montant)
//{
	print '<tr><td valign="top">'.$langs->trans('Numero').'</td><td colspan="3">'.$paiement->numero.'</td></tr>';
//}

// Amount
print '<tr><td valign="top">'.$langs->trans('Amount').'</td><td colspan="3">'.price($paiement->montant).'&nbsp;'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';


// Note
print '<tr><td valign="top">'.$html->editfieldkey("Note",'note',$paiement->note,'id',$paiement->id,$user->rights->facture->paiement).'</td><td colspan="3">';
print $html->editfieldval("Note",'note',$paiement->note,'id',$paiement->id,$user->rights->facture->paiement,'text');
print '</td></tr>';

// Bank account
if ($conf->banque->enabled)
{
    if ($paiement->bank_account)
    {
    	$bankline=new AccountLine($db);
    	$bankline->fetch($paiement->bank_line);

    	print '<tr>';
    	print '<td>'.$langs->trans('BankTransactionLine').'</td>';
		print '<td colspan="3">';
		print $bankline->getNomUrl(1,0,'showall');
    	print '</td>';
    	print '</tr>';
    }
}

print '</table>';


/*
 * List of invoices
 */

$disable_delete = 0;
$sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.total_ttc, f.paye, f.fk_statut, pf.amount, s.nom, s.rowid as socid';
$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
$sql.= ' WHERE pf.fk_facture = f.rowid';
$sql.= ' AND f.fk_soc = s.rowid';
$sql.= ' AND s.entity = '.$conf->entity;
$sql.= ' AND pf.fk_paiement = '.$paiement->id;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<br><table class="noborder" width="100%">';
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
		$var=True;

		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';

            $invoice=new Facture($db);
            $invoice->fetch($objp->facid);
            $paiement = $invoice->getSommePaiement();
            $creditnotes=$invoice->getSumCreditNotesUsed();
            $deposits=$invoice->getSumDepositsUsed();
            $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
            $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');

            // Invoice
			print '<td>';
			print $invoice->getNomUrl(1);
			print "</td>\n";

			// Third party
			print '<td>';
			$thirdpartystatic->id=$objp->socid;
			$thirdpartystatic->nom=$objp->nom;
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
			}
			$total = $total + $objp->amount;
			$i++;
		}
	}
	$var=!$var;

	print "</table>\n";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print '</div>';


/*
 * Boutons Actions
 */

print '<div class="tabsAction">';

if ($conf->global->BILL_ADD_PAYMENT_VALIDATION)
{
	if ($user->societe_id == 0 && $paiement->statut == 0 && $_GET['action'] == '')
	{
		if ($user->rights->facture->paiement)
		{
			print '<a class="butAction" href="fiche.php?id='.$id.'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
		}
	}
}

if ($user->societe_id == 0 && $action == '')
{
	if ($user->rights->facture->paiement)
	{
		if (! $disable_delete)
		{
			print '<a class="butActionDelete" href="fiche.php?id='.$id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemovePaymentWithOneInvoicePaid")).'">'.$langs->trans('Delete').'</a>';
		}
	}
}

print '</div>';

$db->close();

llxFooter('$Date: 2011/08/05 21:06:55 $ - $Revision: 1.77 $');
?>
