<?php
/* Copyright (C) 2005      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2006      Laurent Destailleur   <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/fourn/paiement/fiche.php
 *	\ingroup    facture, fournisseur
 *	\brief      Onglet paiement d'un paiement fournisseur
 *	\remarks	Fichier presque identique a compta/paiement/fiche.php
 *	\version    $Id$
 */


require('./pre.inc.php');

require(DOL_DOCUMENT_ROOT.'/fourn/facture/paiementfourn.class.php');


$langs->load('bills');
$langs->load('banks');
$langs->load('companies');
$langs->load("suppliers");

$mesg='';


/*
 * Actions
 */

if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes' && $user->rights->fournisseur->facture->supprimer)
{
	$db->begin();

	$paiement = new PaiementFourn($db);
	$paiement->fetch($_GET['id']);
	$result = $paiement->delete();
	if ($result > 0)
	{
		$db->commit();
		Header('Location: '.DOL_URL_ROOT.'/fourn/facture/paiement.php');
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
		$db->rollback();
	}
}

if ($_POST['action'] == 'confirm_valide' && $_POST['confirm'] == 'yes' && $user->rights->fournisseur->facture->valider)
{
	$db->begin();

	$paiement = new PaiementFourn($db);
	$paiement->id = $_GET['id'];
	if ($paiement->valide() >= 0)
	{
		$db->commit();
		Header('Location: fiche.php?id='.$paiement->id);
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

$paiement = new PaiementFourn($db);
$paiement->fetch($_GET['id']);

$html = new Form($db);

$h=0;

$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$_GET['id'];
$head[$h][1] = $langs->trans('Card');
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/info.php?id='.$_GET['id'];
$head[$h][1] = $langs->trans('Info');
$h++;


dol_fiche_head($head, $hselected, $langs->trans('SupplierPayment'), 0, 'payment');

/*
 * Confirmation de la suppression du paiement
 */
if ($_GET['action'] == 'delete')
{
	$ret=$html->form_confirm('fiche.php?id='.$paiement->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete');
	if ($ret == 'html') print '<br>';
}

/*
 * Confirmation de la validation du paiement
 */
if ($_GET['action'] == 'valide')
{
	$ret=$html->form_confirm('fiche.php?id='.$paiement->id, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide');
	if ($ret == 'html') print '<br>';
}

if (!empty($_POST['action']) && $_POST['action'] == 'update_num' && !empty($_POST['new_num']))
{
	$res = $paiement->update_num($_POST['new_num']);
	if ($res === 0) {
		$mesg = '<div class="ok">'.$langs->trans('PaymentNumberUpdateSucceeded').'</div>';
	} else {
		$mesg = '<div class="error">'.$langs->trans('PaymentNumberUpdateFailed').'</div>';
	}
}

if (!empty($_POST['action']) && $_POST['action'] == 'update_date' && !empty($_POST['reday']))
{
	$datepaye = dol_mktime(12, 0 , 0,
	$_POST['remonth'],
	$_POST['reday'],
	$_POST['reyear']);
	$res = $paiement->update_date($datepaye);
	if ($res === 0) {
		$mesg = '<div class="ok">'.$langs->trans('PaymentDateUpdateSucceeded').'</div>';
	} else {
		$mesg = '<div class="error">'.$langs->trans('PaymentDateUpdateFailed').'</div>';
	}
}


print '<table class="border" width="100%">';

print '<tr>';
print '<td valign="top" width="140" colspan="2">'.$langs->trans('Ref').'</td><td colspan="3">'.$paiement->id.'</td></tr>';
if ($conf->banque->enabled)
{
	if ($paiement->bank_account)
	{
		// Si compte renseign�, on affiche libelle
		$bank=new Account($db);
		$bank->fetch($paiement->bank_account);

		$bankline=new AccountLine($db);
		$bankline->fetch($paiement->bank_line);

		print '<tr>';
		print '<td valign="top" width="140" colspan="2">'.$langs->trans('BankAccount').'</td>';
		print '<td><a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$bank->id.'">'.img_object($langs->trans('ShowAccount'),'account').' '.$bank->label.'</a></td>';
		print '<td>'.$langs->trans('BankLineConciliated').'</td><td>'.yn($bankline->rappro).'</td>';
		print '</tr>';
	}
}

//switch through edition options for date (only available when statut is -not 1- (=validated))
if (empty($_GET['action']) || $_GET['action']!='edit_date')
{
	print '<tr><td colspan="2">';
	print '<table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
	print $langs->trans('Date');
	print '</td>';
	if ($paiement->statut == 0 && $_GET['action'] != 'edit_date') print '<td align="right"><a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$paiement->id.'&action=edit_date">'.img_edit($langs->trans('Modify')).'</a></td>';
	print '</tr></table>';
	print '</td>';
	print '<td colspan="3">'.dol_print_date($paiement->date,'day').'</td></tr>';
}
else
{
	print '<tr>';
	print '<td valign="top" width="140" colspan="2">'.$langs->trans('Date').'</td>';
	print '<td colspan="3">';
	print '<form name="formsoc" method="post" action="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$paiement->id.'"><input type="hidden" name="action" value="update_date" />';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	if (!empty($_POST['remonth']) && !empty($_POST['reday']) && !empty($_POST['reyear']))
	$sel_date=dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
	else
	$sel_date=$paiement->date;
	$html->select_date($sel_date,'','','','',"addpaiement");
	print '<input type="submit" class="button" name="submit" value="'.$langs->trans('Validate').'" />';
	print '</form>';
	print '</td>';
	print '</tr>';
}

print '<tr><td valign="top" colspan="2">'.$langs->trans('Type').'</td><td colspan="3">'.$paiement->type_libelle.'</td></tr>';

//switch through edition options for number (only available when statut is -not 1- (=validated))
if (empty($_GET['action']) || $_GET['action'] != 'edit_num')
{
	print '<tr><td colspan="2">';
	print '<table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
	print $langs->trans('Numero');
	print '</td>';
	if ($paiement->statut == 0 && $_GET['action'] != 'edit_num') print '<td align="right"><a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$paiement->id.'&action=edit_num">'.img_edit($langs->trans('Modify')).'</a></td>';
	print '</tr></table>';
	print '</td>';
	print '<td colspan="3">'.$paiement->numero.'</td></tr>';
}
else
{
	print '<tr>';
	print '<td valign="top" width="140" colspan="2">'.$langs->trans('Numero').'</td>';
	print '<td colspan="3">';
	print '<form name="formsoc" method="post" action="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$paiement->id.'"><input type="hidden" name="action" value="update_num" />';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	if (!empty($_POST['new_num']))
	$num = $this->db->escape($_POST['new_num']);
	else
	$num = $paiement->numero;
	print '<input type="text" name="new_num" value="'.$num.'"/>';
	print '<input type="submit" class="button" name="submit" value="'.$langs->trans('Validate').'" />';
	print '</form></td>';
	print '</tr>';
}
print '<tr><td valign="top" colspan="2">'.$langs->trans('Amount').'</td><td colspan="3">'.price($paiement->montant).'&nbsp;'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

if ($conf->global->BILL_ADD_PAYMENT_VALIDATION)
{
	print '<tr><td valign="top" colspan="2">'.$langs->trans('Status').'</td><td colspan="3">'.$paiement->getLibStatut(4).'</td></tr>';
}

print '<tr><td valign="top" colspan="2">'.$langs->trans('Note').'</td><td colspan="3">'.nl2br($paiement->note).'</td></tr>';

print '</table>';

if ($mesg) print '<br>'.$mesg;

print '<br>';

/**
 *	Liste des factures
 */
$allow_delete = 1 ;
$sql = 'SELECT f.rowid as ref, f.facnumber as ref_supplier, f.total_ttc, pf.amount, f.rowid as facid, f.paye, f.fk_statut, s.nom, s.rowid as socid';
$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf,'.MAIN_DB_PREFIX.'facture_fourn as f,'.MAIN_DB_PREFIX.'societe as s';
$sql .= ' WHERE pf.fk_facturefourn = f.rowid AND f.fk_soc = s.rowid';
$sql .= ' AND pf.fk_paiementfourn = '.$paiement->id;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<b>'.$langs->trans("Invoices").'</b><br>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Ref').'</td>';
	print '<td>'.$langs->trans('RefSupplier').'</td>';
	print '<td align="center">'.$langs->trans('Status').'</td>';
	print '<td>'.$langs->trans('Company').'</td>';
	print '<td align="right">'.$langs->trans('AmountTTC').'</td>';
	print "</tr>\n";

	if ($num > 0)
	{
		$var=True;

		$facturestatic=new FactureFournisseur($db);

		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td><a href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$objp->facid.'">'.img_object($langs->trans('ShowBill'),'bill').' ';
			print $objp->ref;
			print "</a></td>\n";
			print '<td>'.$objp->ref_supplier."</td>\n";
			print '<td align="center">'.$facturestatic->LibStatut($objp->paye,$objp->fk_statut,2,1).'</td>';
			print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans('ShowCompany'),'company').' '.$objp->nom.'</a></td>';
			print '<td align="right">'.price($objp->amount).'</td>';
			print "</tr>\n";
			if ($objp->paye == 1)
			{
				$allow_delete = 0;
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
		if ($user->rights->fournisseur->facture->valider)
		{
			print '<a class="butAction" href="fiche.php?id='.$_GET['id'].'&amp;action=valide">'.$langs->trans('Valid').'</a>';

		}
	}
}
if ($user->societe_id == 0 && $allow_delete && $paiement->statut == 0 && $_GET['action'] == '')
{
	if ($user->rights->fournisseur->facture->supprimer)
	{
		print '<a class="butActionDelete" href="fiche.php?id='.$_GET['id'].'&amp;action=delete">'.$langs->trans('Delete').'</a>';

	}
}
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
