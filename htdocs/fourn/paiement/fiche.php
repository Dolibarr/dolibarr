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
	    \file       htdocs/fourn/paiement/fiche.php
		\ingroup    facture, fournisseur
		\brief      Onglet paiement d'un paiement fournisseur
		\remarks	Fichier presque identique a compta/paiement/fiche.php
		\version    $Id$
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
 * Visualisation de la fiche
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


dolibarr_fiche_head($head, $hselected, $langs->trans('SupplierPayment'));

/*
 * Confirmation de la suppression du paiement
 */
if ($_GET['action'] == 'delete')
{
	$html->form_confirm('fiche.php?id='.$paiement->id, 'Supprimer le paiement', 'Etes-vous sûr de vouloir supprimer ce paiement ?', 'confirm_delete');
	print '<br>';
}

/*
 * Confirmation de la validation du paiement
 */
if ($_GET['action'] == 'valide')
{
	$html->form_confirm('fiche.php?id='.$paiement->id, 'Valider le paiement', 'Etes-vous sûr de vouloir valider ce paiment, auncune modification n\'est possible une fois le paiement validé ?', 'confirm_valide');
	print '<br>';
}


if ($mesg) print $mesg.'<br>';


print '<table class="border" width="100%">';

print '<tr>';
print '<td valign="top" width="140">'.$langs->trans('Ref').'</td><td colspan="3">'.$paiement->id.'</td></tr>';
if ($conf->banque->enabled)
{
    if ($paiement->bank_account)
    {
    	// Si compte renseigné, on affiche libelle
    	$bank=new Account($db);
    	$bank->fetch($paiement->bank_account);

    	$bankline=new AccountLine($db);
    	$bankline->fetch($paiement->bank_line);

    	print '<tr>';
    	print '<td valign="top" width="140">'.$langs->trans('BankAccount').'</td>';
    	print '<td><a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$bank->id.'">'.img_object($langs->trans('ShowAccount'),'account').' '.$bank->label.'</a></td>';
    	print '<td>'.$langs->trans('BankLineConciliated').'</td><td>'.yn($bankline->rappro).'</td>';
    	print '</tr>';
    }
}
print '<tr><td valign="top" width="140">'.$langs->trans('Date').'</td><td colspan="3">'.dolibarr_print_date($paiement->date).'</td></tr>';
print '<tr><td valign="top">'.$langs->trans('Type').'</td><td colspan="3">'.$paiement->type_libelle.'</td></tr>';
if ($paiement->numero)
{
	print '<tr><td valign="top">'.$langs->trans('Numero').'</td><td colspan="3">'.$paiement->numero.'</td></tr>';
}
print '<tr><td valign="top">'.$langs->trans('Amount').'</td><td colspan="3">'.price($paiement->montant).'&nbsp;'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
print '<tr><td valign="top">'.$langs->trans('Status').'</td><td colspan="3">'.$paiement->getLibStatut(4).'</td></tr>';
print '<tr><td valign="top">'.$langs->trans('Note').'</td><td colspan="3">'.nl2br($paiement->note).'</td></tr>';
print '</table>';


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
	print '<br>'.$langs->trans("Invoices").'<br>';
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
	dolibarr_print_error($db);
}

print '</div>';


/*
 * Boutons Actions
 */

print '<div class="tabsAction">';
if ($user->societe_id == 0 && $paiement->statut == 0 && $_GET['action'] == '')
{
	if ($user->rights->fournisseur->facture->valider)
	{
		print '<a class="butAction" href="fiche.php?id='.$_GET['id'].'&amp;action=valide">'.$langs->trans('Valid').'</a>';

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
