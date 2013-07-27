<?php
/* Copyright (C) 2004      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@capnetworks.com>
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
 *	    \file       htdocs/compta/payment_sc/fiche.php
 *		\ingroup    facture
 *		\brief      Onglet payment of a social contribution
 *		\remarks	Fichier presque identique a fournisseur/paiement/fiche.php
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
if (! empty($conf->banque->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load('bills');
$langs->load('banks');
$langs->load('companies');

// Security check
$id=isset($_GET["id"])?$_GET["id"]:$_POST["id"];
$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
if ($user->societe_id) $socid=$user->societe_id;
// TODO ajouter regle pour restreindre acces paiement
//$result = restrictedArea($user, 'facture', $id,'');

$mesg='';


/*
 * Actions
 */

// Delete payment
if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes' && $user->rights->tax->charges->supprimer)
{
	$db->begin();

	$paiement = new PaymentSocialContribution($db);
	$paiement->fetch($_REQUEST['id']);
	$result = $paiement->delete($user);
	if ($result > 0)
	{
        $db->commit();
        header("Location: ".DOL_URL_ROOT."/compta/charges/index.php?mode=sconly");
        exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
        $db->rollback();
	}
}

// Create payment
if ($_REQUEST['action'] == 'confirm_valide' && $_REQUEST['confirm'] == 'yes' && $user->rights->tax->charges->creer)
{
	$db->begin();

	$paiement = new PaymentSocialContribution($db);
	$paiement->id = $_REQUEST['id'];
	if ($paiement->valide() > 0)
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
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) facture_pdf_create($db, $fac, $fac->modelpdf, $outputlangs);
		}

		header('Location: fiche.php?id='.$paiement->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
		$db->rollback();
	}
}


/*
 * View
 */

llxHeader();

$socialcontrib=new ChargeSociales($db);
$paiement = new PaymentSocialContribution($db);

$result=$paiement->fetch($_GET['id']);
if ($result <= 0)
{
	dol_print_error($db,'Payment '.$_GET['id'].' not found in database');
	exit;
}

$form = new Form($db);

$h=0;

$head[$h][0] = DOL_URL_ROOT.'/compta/payment_sc/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Card");
$hselected = $h;
$h++;

/*$head[$h][0] = DOL_URL_ROOT.'/compta/payment_sc/info.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Info");
$h++;
*/


dol_fiche_head($head, $hselected, $langs->trans("PaymentSocialContribution"), 0, 'payment');

/*
 * Confirmation de la suppression du paiement
 */
if ($_GET['action'] == 'delete')
{
	$ret=$form->form_confirm('fiche.php?id='.$paiement->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete','',0,2);
	if ($ret == 'html') print '<br>';
}

/*
 * Confirmation de la validation du paiement
 */
if ($_GET['action'] == 'valide')
{
	$facid = $_GET['facid'];
	$ret=$form->form_confirm('fiche.php?id='.$paiement->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide','',0,2);
	if ($ret == 'html') print '<br>';
}


if ($mesg) print $mesg.'<br>';


print '<table class="border" width="100%">';

// Ref
print '<tr><td valign="top" width="140">'.$langs->trans('Ref').'</td>';
print '<td colspan="3">';
print $form->showrefnav($paiement,'id','',1,'rowid','id');
print '</td></tr>';

// Date
print '<tr><td valign="top" width="120">'.$langs->trans('Date').'</td><td colspan="3">'.dol_print_date($paiement->datep,'day').'</td></tr>';

// Mode
print '<tr><td valign="top">'.$langs->trans('Mode').'</td><td colspan="3">'.$langs->trans("PaymentType".$paiement->type_code).'</td></tr>';

// Numero
print '<tr><td valign="top">'.$langs->trans('Numero').'</td><td colspan="3">'.$paiement->num_paiement.'</td></tr>';

// Montant
print '<tr><td valign="top">'.$langs->trans('Amount').'</td><td colspan="3">'.price($paiement->amount).'&nbsp;'.$langs->trans('Currency'.$conf->currency).'</td></tr>';


// Note
print '<tr><td valign="top">'.$langs->trans('Note').'</td><td colspan="3">'.nl2br($paiement->note).'</td></tr>';

// Bank account
if (! empty($conf->banque->enabled))
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
 * List of social contributions payed
 */

$disable_delete = 0;
$sql = 'SELECT f.rowid as scid, f.libelle, f.paye, f.amount as sc_amount, pf.amount, pc.libelle as sc_type';
$sql.= ' FROM '.MAIN_DB_PREFIX.'paiementcharge as pf,'.MAIN_DB_PREFIX.'chargesociales as f, '.MAIN_DB_PREFIX.'c_chargesociales as pc';
$sql.= ' WHERE pf.fk_charge = f.rowid AND f.fk_type = pc.id';
$sql.= ' AND f.entity = '.$conf->entity;
$sql.= ' AND pf.rowid = '.$paiement->id;

dol_syslog("compta/payment_sc/fiche.php sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<br><table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('SocialContribution').'</td>';
    print '<td>'.$langs->trans('Type').'</td>';
	print '<td>'.$langs->trans('Label').'</td>';
	print '<td align="right">'.$langs->trans('ExpectedToPay').'</td>';
	print '<td align="center">'.$langs->trans('Status').'</td>';
	print '<td align="right">'.$langs->trans('PayedByThisPayment').'</td>';
	print "</tr>\n";

	if ($num > 0)
	{
		$var=True;

		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			$var=!$var;
			print '<tr '.$bc[$var].'>';
			// Ref
			print '<td>';
			$socialcontrib->fetch($objp->scid);
			print $socialcontrib->getNomUrl(1);
			print "</td>\n";
			// Type
            print '<td>';
            print $socialcontrib->type_libelle;
            /*print $socialcontrib->type;*/
            print "</td>\n";
			// Label
			print '<td>'.$objp->libelle.'</td>';
			// Expected to pay
			print '<td align="right">'.price($objp->sc_amount).'</td>';
			// Status
			print '<td align="center">'.$socialcontrib->getLibStatut(4).'</td>';
			// Amount payed
			print '<td align="right">'.price($objp->amount).'</td>';
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

/*
if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
{
	if ($user->societe_id == 0 && $paiement->statut == 0 && $_GET['action'] == '')
	{
		if ($user->rights->facture->paiement)
		{
			print '<a class="butAction" href="fiche.php?id='.$_GET['id'].'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
		}
	}
}
*/

if ($_GET['action'] == '')
{
	if ($user->rights->tax->charges->supprimer)
	{
		if (! $disable_delete)
		{
			print '<a class="butActionDelete" href="fiche.php?id='.$_GET['id'].'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("CantRemovePaymentWithOneInvoicePaid")).'">'.$langs->trans('Delete').'</a>';
		}
	}
}

print '</div>';


$db->close();

llxFooter();
?>
