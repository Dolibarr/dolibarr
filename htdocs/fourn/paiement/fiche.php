<?php
/* Copyright (C) 2005      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2006-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 *	\file       htdocs/fourn/paiement/fiche.php
 *	\ingroup    facture, fournisseur
 *	\brief      Tab to show a payment of a supplier invoice
 *	\remarks	Fichier presque identique a compta/paiement/fiche.php
 */

require '../../main.inc.php';
require DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

$langs->load('bills');
$langs->load('banks');
$langs->load('companies');
$langs->load("suppliers");

$mesg='';

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');

$object = new PaiementFourn($db);

/*
 * Actions
 */

if ($action == 'setnote' && $user->rights->fournisseur->facture->creer)
{
    $db->begin();

    $object->fetch($id);
    $result = $object->update_note(GETPOST('note'));
    if ($result > 0)
    {
        $db->commit();
        $action='';
    }
    else
    {
        $mesg='<div class="error">'.$object->error.'</div>';
        $db->rollback();
    }
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->fournisseur->facture->supprimer)
{
	$db->begin();

	$object->fetch($id);
	$result = $object->delete();
	if ($result > 0)
	{
		$db->commit();
		header('Location: '.DOL_URL_ROOT.'/fourn/facture/paiement.php');
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
		$db->rollback();
	}
}

if ($action == 'confirm_valide' && $confirm == 'yes' && $user->rights->fournisseur->facture->valider)
{
	$db->begin();

	$object->fetch($id);
	if ($object->valide() >= 0)
	{
		$db->commit();
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
		$db->rollback();
	}
}

if ($action == 'setnum' && ! empty($_POST['num_paiement']))
{
	$object->fetch($id);
    $res = $object->update_num($_POST['num_paiement']);
	if ($res === 0)
	{
		$mesg = '<div class="ok">'.$langs->trans('PaymentNumberUpdateSucceeded').'</div>';
	}
	else
	{
		$mesg = '<div class="error">'.$langs->trans('PaymentNumberUpdateFailed').'</div>';
	}
}

if ($action == 'setdatep' && ! empty($_POST['datepday']))
{
	$object->fetch($id);
    $datepaye = dol_mktime(12, 0, 0, $_POST['datepmonth'], $_POST['datepday'], $_POST['datepyear']);
	$res = $object->update_date($datepaye);
	if ($res === 0)
	{
		$mesg = '<div class="ok">'.$langs->trans('PaymentDateUpdateSucceeded').'</div>';
	}
	else
	{
		$mesg = '<div class="error">'.$langs->trans('PaymentDateUpdateFailed').'</div>';
	}
}


/*
 * View
 */

llxHeader();

$form = new Form($db);

$h=0;

$head[$h][0] = $_SERVER['PHP_SELF'].'?id='.$id;
$head[$h][1] = $langs->trans('Card');
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/info.php?id='.$id;
$head[$h][1] = $langs->trans('Info');
$h++;


dol_fiche_head($head, $hselected, $langs->trans('SupplierPayment'), 0, 'payment');

$result=$object->fetch($id);
if ($result > 0)
{
	/*
	 * Confirmation de la suppression du paiement
	 */
	if ($action == 'delete')
	{
		print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete');

	}

	/*
	 * Confirmation de la validation du paiement
	 */
	if ($action == 'valide')
	{
		print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide');

	}

	print '<table class="border" width="100%">';

	print '<tr>';
	print '<td valign="top" width="20%" colspan="2">'.$langs->trans('Ref').'</td><td colspan="3">';
    print $form->showrefnav($object,'id','',1,'rowid','ref');
	print '</td></tr>';

	// Date payment
    print '<tr><td valign="top" colspan="2">'.$form->editfieldkey("Date",'datep',$object->date,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td colspan="3">';
    print $form->editfieldval("Date",'datep',$object->date,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'datepicker','',null,$langs->trans('PaymentDateUpdateSucceeded'));
    print '</td></tr>';

	// Payment mode
	print '<tr><td valign="top" colspan="2">'.$langs->trans('PaymentMode').'</td><td colspan="3">'.$object->type_libelle.'</td></tr>';

	// Payment numero
    print '<tr><td valign="top" colspan="2">'.$form->editfieldkey("Numero",'num_paiement',$object->numero,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td colspan="3">';
    print $form->editfieldval("Numero",'num_paiement',$object->numero,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('PaymentNumberUpdateSucceeded'));
    print '</td></tr>';

	// Amount
	print '<tr><td valign="top" colspan="2">'.$langs->trans('Amount').'</td><td colspan="3">'.price($object->montant,'',$langs,0,0,-1,$conf->currency).'</td></tr>';

	if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
	{
		print '<tr><td valign="top" colspan="2">'.$langs->trans('Status').'</td><td colspan="3">'.$object->getLibStatut(4).'</td></tr>';
	}

	// Note
    print '<tr><td valign="top"" colspan="2">'.$form->editfieldkey("Note",'note',$object->note,$object,$user->rights->fournisseur->facture->creer).'</td><td colspan="3">';
    print $form->editfieldval("Note",'note',$object->note,$object,$user->rights->fournisseur->facture->creer,'textarea');
    print '</td></tr>';

    // Bank account
	if (! empty($conf->banque->enabled))
	{
		if ($object->bank_account)
		{
            $bankline=new AccountLine($db);
            $bankline->fetch($object->bank_line);

            print '<tr>';
            print '<td colspan="2">'.$langs->trans('BankTransactionLine').'</td>';
            print '<td colspan="3">';
            print $bankline->getNomUrl(1,0,'showconciliated');
            print '</td>';
            print '</tr>';

	    	print '<tr>';
	    	print '<td colspan="2">'.$langs->trans('BankAccount').'</td>';
			print '<td colspan="3">';
			$accountstatic=new Account($db);
	        $accountstatic->id=$bankline->fk_account;
	        $accountstatic->label=$bankline->bank_account_ref.' - '.$bankline->bank_account_label;
	        print $accountstatic->getNomUrl(0);
	    	print '</td>';
	    	print '</tr>';
        }
    }

	print '</table>';

	dol_htmloutput_mesg($mesg);

	print '<br>';

	/**
	 *	Liste des factures
	 */
	$allow_delete = 1 ;
	$sql = 'SELECT f.rowid, f.ref, f.ref_supplier, f.total_ttc, pf.amount, f.rowid as facid, f.paye, f.fk_statut, s.nom, s.rowid as socid';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf,'.MAIN_DB_PREFIX.'facture_fourn as f,'.MAIN_DB_PREFIX.'societe as s';
	$sql .= ' WHERE pf.fk_facturefourn = f.rowid AND f.fk_soc = s.rowid';
	$sql .= ' AND pf.fk_paiementfourn = '.$object->id;
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
		print '<td>'.$langs->trans('Company').'</td>';
		print '<td align="right">'.$langs->trans('ExpectedToPay').'</td>';
		print '<td align="center">'.$langs->trans('Status').'</td>';
		print '<td align="right">'.$langs->trans('PayedByThisPayment').'</td>';
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
				// Ref
				print '<td><a href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$objp->facid.'">'.img_object($langs->trans('ShowBill'),'bill').' ';
				print ($objp->ref?$objp->ref:$objp->rowid);
				print "</a></td>\n";
				// Ref supplier
				print '<td>'.$objp->ref_supplier."</td>\n";
				// Third party
				print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans('ShowCompany'),'company').' '.$objp->nom.'</a></td>';
				// Expected to pay
				print '<td align="right">'.price($objp->total_ttc).'</td>';
				// Status
				print '<td align="center">'.$facturestatic->LibStatut($objp->paye,$objp->fk_statut,2,1).'</td>';
				// Payed
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
	if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
	{
		if ($user->societe_id == 0 && $object->statut == 0 && $action == '')
		{
			if ($user->rights->fournisseur->facture->valider)
			{
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=valide">'.$langs->trans('Valid').'</a>';

			}
		}
	}
	if ($user->societe_id == 0 && $allow_delete && $object->statut == 0 && $action == '')
	{
		if ($user->rights->fournisseur->facture->supprimer)
		{
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';

		}
	}
	print '</div>';

}
else
{
	$langs->load("errors");
	print $langs->trans("ErrorRecordNotFound");
}

dol_fiche_end();

llxFooter();

$db->close();
?>
