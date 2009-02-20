<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C)      2005 Marc Barilley / Ocebo <marc@ocebo.com>
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
	    \file       htdocs/compta/paiement/fiche.php
		\ingroup    facture
		\brief      Onglet paiement d'un paiement client
		\remarks	Fichier presque identique a fournisseur/paiement/fiche.php
		\version    $Id$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');
require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");
if ($conf->banque->enabled) require_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');

$langs->load('bills');
$langs->load('banks');
$langs->load('companies');

$mesg='';


/*
 * Actions
 */

if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes' && $user->rights->facture->paiement)
{
	$db->begin();
	
	$paiement = new Paiement($db);
	$paiement->fetch($_GET['id']);
	$result = $paiement->delete();
	if ($result > 0)
	{
        $db->commit();
        Header("Location: liste.php");
        exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
        $db->rollback();
	}
}

if ($_POST['action'] == 'confirm_valide' && $_POST['confirm'] == 'yes' && $user->rights->facture->paiement)
{
	$db->begin();

	$paiement = new Paiement($db);
	$paiement->id = $_GET['id'];
	if ($paiement->valide() > 0)
	{
		$db->commit();
		
		// \TODO Boucler sur les facture liées à ce paiement et régénèrer le pdf
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
			facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
		}

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

$paiement = new Paiement($db);
$result=$paiement->fetch($_GET['id']);
if ($result <= 0)
{
	dol_print_error($db,'Payement '.$_GET['id'].' not found in database');
	exit;
}

$html = new Form($db);

$h=0;

$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Card");
$hselected = $h;
$h++;      

$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/info.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Info");
$h++;      


dol_fiche_head($head, $hselected, $langs->trans("PaymentCustomerInvoice"));

/*
 * Confirmation de la suppression du paiement
 */
if ($_GET['action'] == 'delete')
{
	$html->form_confirm('fiche.php?id='.$paiement->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete');
	print '<br>';
}

/*
 * Confirmation de la validation du paiement
 */
if ($_GET['action'] == 'valide')
{
	$facid = $_GET['facid'];
	$html->form_confirm('fiche.php?id='.$paiement->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide');
	print '<br>';
}


if ($mesg) print $mesg.'<br>';


print '<table class="border" width="100%">';

// Ref
print '<tr><td valign="top" width="140">'.$langs->trans('Ref').'</td><td colspan="3">'.$paiement->id.'</td></tr>';

// Date
print '<tr><td valign="top" width="120">'.$langs->trans('Date').'</td><td colspan="3">'.dol_print_date($paiement->date,'day').'</td></tr>';

// Mode
print '<tr><td valign="top">'.$langs->trans('Mode').'</td><td colspan="3">'.$langs->trans("PaymentType".$paiement->type_code).'</td></tr>';

// Numero
//if ($paiement->montant)
//{
	print '<tr><td valign="top">'.$langs->trans('Numero').'</td><td colspan="3">'.$paiement->numero.'</td></tr>';
//}

// Montant
print '<tr><td valign="top">'.$langs->trans('Amount').'</td><td colspan="3">'.price($paiement->montant).'&nbsp;'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';


// Note
print '<tr><td valign="top">'.$langs->trans('Note').'</td><td colspan="3">'.nl2br($paiement->note).'</td></tr>';

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
 * Liste des factures
 */
$allow_delete = 1 ;
$sql = 'SELECT f.facnumber, f.total_ttc, pf.amount, f.rowid as facid, f.paye, f.fk_statut, s.nom, s.rowid as socid';
$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
$sql .= ' WHERE pf.fk_facture = f.rowid AND f.fk_soc = s.rowid';
$sql .= ' AND pf.fk_paiement = '.$paiement->id;
$resql=$db->query($sql); 			
if ($resql)
{
	$num = $db->num_rows($resql);
  
	$i = 0;
	$total = 0;
	print '<br><table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Bill').'</td>';
	print '<td align="center">'.$langs->trans('Status').'</td>';
	print '<td>'.$langs->trans('Company').'</td>';
	print '<td align="right">'.$langs->trans('AmountTTC').'</td>';
	print "</tr>\n";
  
	if ($num > 0) 
	{
		$var=True;
      
		$facturestatic=new Facture($db);

		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">'.img_object($langs->trans('ShowBill'),'bill').' ';
			print $objp->facnumber;
			print "</a></td>\n";
			print '<td align="center">'.$facturestatic->LibStatut($objp->paye,$objp->fk_statut,2,1).'</td>';
			print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans('ShowCompany'),'company').' '.$objp->nom.'</a></td>';
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
		if ($user->rights->facture->paiement)
		{
			print '<a class="butAction" href="fiche.php?id='.$_GET['id'].'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
		}
	}
}
if ($user->societe_id == 0 && $allow_delete && $_GET['action'] == '')
{
	if ($user->rights->facture->paiement)
	{
		print '<a class="butActionDelete" href="fiche.php?id='.$_GET['id'].'&amp;action=delete">'.$langs->trans('Delete').'</a>';
	}
}

print '</div>';      

$db->close();

llxFooter('$Date$ - $Revision$');
?>
