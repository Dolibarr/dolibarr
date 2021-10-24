<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/fourn/facture/fiche.php
 *	\ingroup    facture, fournisseur
 *	\brief      Page for supplier invoice card
 *	\version    $Id$
 */

require_once('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/facture/paiementfourn.class.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/fourn.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/product.class.php');
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/projet/project.class.php');

// If socid provided by ajax company selector
if (! empty($_REQUEST['socid_id']))
{
	$_GET['socid'] = $_GET['socid_id'];
	$_POST['socid'] = $_POST['socid_id'];
	$_REQUEST['socid'] = $_REQUEST['socid_id'];
}

$langs->load('bills');
$langs->load('suppliers');
$langs->load('companies');

$facid = isset($_GET["facid"])?$_GET["facid"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $facid, 'facture_fourn', 'facture');

$html = new Form($db);
$mesg='';
$action=isset($_GET['action'])?$_GET['action']:$_POST['action'];


/*
 * Actions
 */

// Action clone object
if ($_REQUEST["action"] == 'confirm_clone' && $_REQUEST['confirm'] == 'yes')
{
	if (1==0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
	{
		$mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	}
	else
	{
		$object=new FactureFournisseur($db);
		$result=$object->createFromClone($_REQUEST['facid']);
		if ($result > 0)
		{
			header("Location: ".$_SERVER['PHP_SELF'].'?facid='.$result);
			exit;
		}
		else
		{
			$langs->load("errors");
			$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
			$_GET['action']='';
			$_GET['id']=$_REQUEST['id'];
		}
	}
}

if ($_REQUEST['action'] == 'confirm_valid' && $_REQUEST['confirm'] == 'yes' && $user->rights->fournisseur->facture->valider)
{
	$facturefourn=new FactureFournisseur($db);
	$facturefourn->fetch($_GET['facid']);
	$result = $facturefourn->set_valid($user);
}

if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes')
{
	if ($user->rights->fournisseur->facture->supprimer )
	{
		$facturefourn = new FactureFournisseur($db);
		$factfournid = $_GET['facid'];
		$result=$facturefourn->delete($factfournid);
		if ($result > 0)
		{
			Header('Location: index.php');
			exit;
		}
		else
		{
			$mesg='<div class="error">'.$facturefourn->error.'</div>';
		}
	}
}

if ($_POST['action'] == 'confirm_deleteproductline' && $_POST['confirm'] == 'yes')
{
	if ($user->rights->fournisseur->facture->creer)
	{
		$facturefourn = new FactureFournisseur($db);
		$facturefourn->fetch($_GET['facid']);
		$facturefourn->deleteline($_GET['ligne_id']);
		$_GET['action'] = '';
	}
}

if ($_REQUEST['action'] == 'confirm_paid' && $_REQUEST['confirm'] == 'yes' && $user->rights->fournisseur->facture->creer)
{
	$facturefourn=new FactureFournisseur($db);
	$facturefourn->fetch($_GET['facid']);
	$result=$facturefourn->set_paid($user);
}

// Set supplier ref
if ($_POST['action'] == 'set_ref_supplier' && $user->rights->fournisseur->facture->creer)
{
	$facturefourn = new FactureFournisseur($db);
	$facturefourn->fetch($_GET['facid']);
	$result=$facturefourn->set_ref_supplier($user, $_POST['ref_supplier']);
}

if($_GET['action'] == 'deletepaiement')
{
	$facfou = new FactureFournisseur($db);
	$facfou->fetch($_GET['facid']);
	if ($facfou->statut == 1 && $facfou->paye == 0 && $user->societe_id == 0)
	{
		$paiementfourn = new PaiementFourn($db);
		$paiementfourn->fetch($_GET['paiement_id']);
		$paiementfourn->delete();
	}
}

if ($_POST['action'] == 'update' && ! $_POST['cancel'])
{
	$datefacture = dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
	$date_echeance = dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);

	$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn set ';
	$sql .= " facnumber='".addslashes(trim($_POST['facnumber']))."'";
	$sql .= ", libelle='".addslashes(trim($_POST['libelle']))."'";
	$sql .= ", note='".$_POST['note']."'";
	$sql .= ", datef = '".$db->idate($datefacture)."'";
	$sql .= ", date_lim_reglement = '".$db->idate($date_echeance)."'";
	$sql .= ' WHERE rowid = '.$_GET['facid'].' ;';
	$result = $db->query( $sql);
}
/*
 * Action creation
 */
if ($_POST['action'] == 'add' && $user->rights->fournisseur->facture->creer)
{
	$datefacture=dol_mktime(12,0,0,$_POST['remonth'],$_POST['reday'],$_POST['reyear']);
	$datedue=dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);

	if ($datefacture == '')
	{
		$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('DateInvoice')).'</div>';
		$_GET['action']='create';
		$_GET['socid']=$_POST['socid'];
		$error++;
	}
	if (empty($_POST['facnumber']))
	{
		$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('RefSupplier')).'</div>';
		$_GET['action']='create';
		$_GET['socid']=$_POST['socid'];
		$error++;
	}

	if (! $error)
	{
		$db->begin();

		// Creation facture
		$facfou = new FactureFournisseur($db);

		$facfou->ref           = $_POST['facnumber'];
		$facfou->socid         = $_POST['socid'];
		$facfou->libelle       = $_POST['libelle'];
		$facfou->date          = $datefacture;
		$facfou->date_echeance = $datedue;
		$facfou->note_public   = $_POST['note'];

		$facid = $facfou->create($user);

		// Ajout des lignes de factures
		if ($facid > 0)
		{
			for ($i = 1 ; $i < 9 ; $i++)
			{
				$label = $_POST['label'.$i];
				$amountht  = price2num($_POST['amount'.$i]);
				$amountttc = price2num($_POST['amountttc'.$i]);
				$tauxtva   = price2num($_POST['tauxtva'.$i]);
				$qty = $_POST['qty'.$i];
				$fk_product = $_POST['fk_product'.$i];
				if ($label)
				{
					if ($amountht)
					{
						$price_base='HT'; $amount=$amountht;
					}
					else
					{
						$price_base='TTC'; $amount=$amountttc;
					}
					$atleastoneline=1;
					$ret=$facfou->addline($label, $amount, $tauxtva, $qty, $fk_product, $remise_percent, '', '', '', 0, $price_base);
					if ($ret < 0) $nberror++;
				}
			}
			if ($nberror)
			{
				$db->rollback();
				$mesg='<div class="error">'.$facfou->error.'</div>';
				$_GET['action']='create';
				$_GET['socid']=$_POST['socid'];
			}
			else
			{
				$db->commit();
				header('Location: fiche.php?facid='.$facid);
				exit;
			}
		}
		else
		{
			$db->rollback();
			$mesg='<div class="error">'.$facfou->error.'</div>';
			$_GET['action']='create';
			$_GET['socid']=$_POST['socid'];
		}
	}
}

if ($_GET['action'] == 'del_ligne')
{
	$facfou = new FactureFournisseur($db,'',$_GET['facid']);
	$facfou->deleteline($_GET['ligne_id']);
	$_GET['action'] = 'edit';
}

// Modification d'une ligne
if ($_REQUEST['action'] == 'update_line')
{
	if ($_REQUEST['etat'] == '1' && ! $_REQUEST['cancel']) // si on valide la modification
	{
		$facfou = new FactureFournisseur($db);
		$facfou->fetch($_GET['facid']);

		if ($_POST['puht'])
		{
			$pu=$_POST['puht'];
			$price_base_type='HT';
		}
		if ($_POST['puttc'])
		{
			$pu=$_POST['puttc'];
			$price_base_type='TTC';
		}

		if ($_POST['idprod'])
		{
			$prod = new Product($db);
			$prod->fetch($_POST['idprod']);
			$label = $prod->libelle;
			$type = $prod->type;
		}
		else
		{
			$label = $_POST['label'];
			$type = $_POST["type"]?$_POST["type"]:0;
		}

		$facfou->updateline($_GET['ligne_id'], $label, $pu, $_POST['tauxtva'], $_POST['qty'], $_POST['idprod'], $price_base_type, 0, $type);
	}
}

if ($_GET['action'] == 'addline')
{
	$facfou = new FactureFournisseur($db, '', $_GET['facid']);
	$ret=$facfou->fetch($_GET['facid']);
	if ($ret < 0)
	{
		dol_print_error($db,$facfou->error);
		exit;
	}

	if ($_POST['idprodfournprice'])	// > 0 or -1
	{
		$product = new ProductFournisseur($db);
		$idprod=$product->get_buyprice($_POST['idprodfournprice'], $_POST['qty']);
		if ($idprod > 0)
		{
			$result=$product->fetch($idprod);

			// cas special pour lequel on a les meme reference que le fournisseur
			// $label = '['.$product->ref.'] - '. $product->libelle;
			$label = $product->libelle;

			$societe='';
			if ($facfou->socid)
			{
				$societe=new Societe($db);
				$societe->fetch($facfou->socid);
			}

			$tvatx=get_default_tva($societe,$mysoc,$product->tva_tx);
			$type = $product->type;

			$result=$facfou->addline($label, $product->fourn_pu, $tvatx, $_POST['qty'], $idprod);
		}
		if ($idprod == -1)
		{
			// Quantity too low
			$mesg='<div class="error">'.$langs->trans("ErrorQtyTooLowForThisSupplier").'</div>';
		}
	}
	else
	{
		$tauxtva = price2num($_POST['tauxtva']);
		if (! $_POST['label'])
		{
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")).'</div>';
		}
		else
		{
			$type = $_POST["type"];
			if (! empty($_POST['amount']))
			{
				$ht = price2num($_POST['amount']);
				$price_base_type = 'HT';
				//$desc, $pu, $txtva, $qty, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits='', $price_base_type='HT', $type=0)
				$result=$facfou->addline($_POST['label'], $ht, $tauxtva, $_POST['qty'], 0, 0, $datestart, $dateend, 0, 0, $price_base_type, $type);
			}
			else
			{
				$ttc = price2num($_POST['amountttc']);
				$ht = $ttc / (1 + ($tauxtva / 100));
				$price_base_type = 'HT';
				$result=$facfou->addline($_POST['label'], $ht, $tauxtva, $_POST['qty'], 0, 0, $datestart, $dateend, 0, 0, $price_base_type, $type);
			}
		}
	}

	//print "xx".$tva_tx; exit;
	if ($result > 0)
	{
		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		//supplier_invoice_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);

		unset($_POST['qty']);
		unset($_POST['type']);
		unset($_POST['idprodfournprice']);
		unset($_POST['remmise_percent']);
		unset($_POST['dp_desc']);
		unset($_POST['np_desc']);
		unset($_POST['pu']);
		unset($_POST['tva_tx']);
	}
	else if (empty($mesg))
	{
		$mesg='<div class="error">'.$facfou->error.'</div>';
	}

	$_GET['action'] = '';
}

if ($_POST['action'] == 'classin')
{
	$facture = new FactureFournisseur($db,'',$_GET['facid']);
	$facture->fetch($_GET['facid']);
	$facture->setProject($_POST['projetid']);
}


/*
 *	View
 */

llxHeader('','','');

$formfile = new FormFile($db);


// Mode creation
if ($_GET['action'] == 'create')
{
	print_fiche_titre($langs->trans('NewBill'));

	if ($mesg) { print $mesg.'<br>'; }

	$societe='';
	if ($_GET['socid'])
	{
		$societe=new Societe($db);
		$societe->fetch($_GET['socid']);
	}

	$datetmp=dol_mktime(12,0,0,$_POST['remonth'],$_POST['reday'],$_POST['reyear']);
	$dateinvoice=($datetmp==''?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0):$datetmp);
	$datetmp=dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);
	$datedue=($datetmp==''?-1:$datetmp);

	print '<form name="add" action="fiche.php" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<table class="border" width="100%">';

	// Third party
	print '<tr><td>'.$langs->trans('Company').'</td>';
	print '<td>';

	if ($_GET['socid'])
	{
		print $societe->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$_GET['socid'].'">';
	}
	else
	{
		$html->select_societes((empty($_GET['socid'])?'':$_GET['socid']),'socid','s.fournisseur = 1');
	}
	print '</td>';
	print '<td width="50%">'.$langs->trans('NotePublic').'</td></tr>';

	print '<tr><td>'.$langs->trans('RefSupplier').'</td><td><input name="facnumber" value="'.(isset($_POST['facnumber'])?$_POST['facnumber']:$fac_ori->ref).'" type="text"></td>';
	print '<td width="50%" rowspan="4" valign="top"><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td></tr>';

	print '<tr><td>'.$langs->trans('Label').'</td><td><input size="30" name="libelle" value="'.(isset($_POST['libelle'])?$_POST['libelle']:$fac_ori->libelle).'" type="text"></td></tr>';

	// Date invoice
	print '<tr><td>'.$langs->trans('DateInvoice').'</td><td>';
	$html->select_date($dateinvoice,'','','','',"add",1,1);
	print '</td></tr>';

	// Due date
	print '<tr><td>'.$langs->trans('DateEcheance').'</td><td>';
	$html->select_date($datedue,'ech','','','',"add",1,1);
	print '</td></tr>';

	print '</table><br>';

	if ($conf->global->PRODUCT_SHOW_WHEN_CREATE)
	{
		print '<table class="border" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>&nbsp;</td><td>'.$langs->trans('Label').'</td>';
		print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
		print '<td align="right">'.$langs->trans('VAT').'</td>';
		print '<td align="right">'.$langs->trans('Qty').'</td>';
		print '<td align="right">'.$langs->trans('PriceUTTC').'</td>';
		print '</tr>';

		for ($i = 1 ; $i < 9 ; $i++)
		{
			$value_qty = '1';
			$value_tauxtva = '';
			print '<tr><td>'.$i.'</td>';
			print '<td><input size="50" name="label'.$i.'" value="'.$value_label.'" type="text"></td>';
			print '<td align="right"><input type="text" size="8" name="amount'.$i.'" value="'.$value_pu.'"></td>';
			print '<td align="right">';
			$html->select_tva('tauxtva'.$i,$value_tauxtva,$societe,$mysoc);
			print '</td>';
			print '<td align="right"><input type="text" size="3" name="qty'.$i.'" value="'.$value_qty.'"></td>';
			print '<td align="right"><input type="text" size="8" name="amountttc'.$i.'" value=""></td></tr>';
		}

		print '</table>';
	}

	print '<center><input type="submit" class="button" value="'.$langs->trans('Save').'"></center>';
	print '</form>';
}
else
{
	if ($_GET['facid'] > 0)
	{
		/* *************************************************************************** */
		/*                                                                             */
		/* Fiche en mode visu ou edition                                               */
		/*                                                                             */
		/* *************************************************************************** */

		$now=gmmktime();

		$productstatic = new Product($db);

		$fac = new FactureFournisseur($db);
		$fac->fetch($_GET['facid']);

		$societe = new Fournisseur($db);
		$societe->fetch($fac->socid);

		if ($_GET['action'] == 'edit')
		{
			print_fiche_titre($langs->trans('SupplierInvoice'));

			print '<form name="update" action="fiche.php?facid='.$fac->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';

			print '<table class="border" width="100%">';

			print '<tr><td>'.$langs->trans('Company').'</td>';
			print '<td>'.$societe->getNomUrl(1).'</td>';
			print '<td width="50%" valign="top">'.$langs->trans('NotePublic').'</td>';
			print '</tr>';

			// Ref
			print '<tr><td valign="top">'.$langs->trans('Ref').'</td><td valign="top">';
			print $fac->ref.'</td>';
			$rownb=10;
			print '<td rowspan="'.$rownb.'" valign="top">';
			print '<textarea name="note" wrap="soft" cols="60" rows="'.$rownb.'">';
			print $fac->note;
			print '</textarea></td></tr>';

			print '<tr><td valign="top">'.$langs->trans('RefSupplier').'</td><td valign="top">';
			print '<input name="facnumber" type="text" value="'.$fac->ref_supplier.'"></td>';
			print '</tr>';

			print '<tr><td valign="top">'.$langs->trans('Label').'</td><td>';
			print '<input size="30" name="libelle" type="text" value="'.$fac->libelle.'"></td></tr>';

			print '<tr><td class="fieldrequired">'.$langs->trans('DateInvoice').'</td><td nowrap="nowrap">';
			$html->select_date($fac->datep,'','','','',"update",1,1);
			print '</td></tr>';

			print '<tr><td class="fieldrequired">'.$langs->trans('DateEcheance').'</td><td nowrap="nowrap">';
			$html->select_date($fac->date_echeance,'ech','','','',"update",1,1);
			if (($fac->paye == 0) && ($fac->statut > 0) && $fac->date_echeance < ($now - $conf->facture->fournisseur->warning_delay)) print img_picto($langs->trans("Late"),"warning");
			print '</td></tr>';

			print '<tr><td>'.$langs->trans('AmountHT').'</td><td nowrap="nowrap"><b>'.price($fac->total_ht).'</b></td></tr>';
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td nowrap="nowrap">'.price($fac->total_tva).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td nowrap="nowrap">'.price($fac->total_ttc).'</td></tr>';

			print '<tr><td>'.$langs->trans('Status').'</td><td>'.$fac->getLibStatut(4).'</td></tr>';
			print '<tr><td colspan="2" align="center">';
			print '<input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
			print ' &nbsp; &nbsp; ';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';

			print '</td></tr>';
			print '</table>';
			print '</form>';
		}
		else
		{
			/*
			 *	View card
			 */
			$head = facturefourn_prepare_head($fac);
			$titre=$langs->trans('SupplierInvoice');
			dol_fiche_head($head, 'card', $titre, 0, 'bill');

			if ($mesg) { print $mesg.'<br>'; }

			// Confirmation de la suppression d'une ligne produit
			if ($_GET['action'] == 'confirm_delete_line')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;ligne_id='.$_GET["ligne_id"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteproductline', '', 0, 0);
				if ($ret == 'html') print '<br>';
			}

			// Clone confirmation
			if ($_GET["action"] == 'clone')
			{
				// Create an array for form
				$formquestion=array(
				//'text' => $langs->trans("ConfirmClone"),
				//array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1)
				);
				// Paiement incomplet. On demande si motif = escompte ou autre
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id,$langs->trans('CloneInvoice'),$langs->trans('ConfirmCloneInvoice',$fac->ref),'confirm_clone',$formquestion,'yes', 1);
				if ($ret == 'html') print '<br>';
			}

			// Confirmation de la validation
			if ($_GET['action'] == 'valid')
			{
				$ret=$html->form_confirm('fiche.php?facid='.$fac->id, $langs->trans('ValidateBill'), $langs->trans('ConfirmValidateBill', $fac->ref), 'confirm_valid', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			// Confirmation set paid
			if ($_GET['action'] == 'paid')
			{
				$ret=$html->form_confirm('fiche.php?facid='.$fac->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidBill', $fac->ref), 'confirm_paid', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de la suppression de la facture fournisseur
			 */
			if ($_GET['action'] == 'delete')
			{
				$ret=$html->form_confirm('fiche.php?facid='.$fac->id, $langs->trans('DeleteBill'), $langs->trans('ConfirmDeleteBill'), 'confirm_delete', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}


			/*
			 *   Facture
			 */
			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td nowrap="nowrap" width="20%">'.$langs->trans("Ref").'</td><td colspan="4">';
			print $html->showrefnav($fac,'facid','',1,'rowid','ref',$morehtmlref);
			print '</td>';
			print "</tr>\n";

			// Ref supplier
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
			print $langs->trans('RefSupplier').'</td><td align="left">';
			print '</td>';
			if ($_GET['action'] != 'refsupplier' && $fac->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refsupplier&amp;facid='.$fac->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="5">';
			if ($user->rights->fournisseur->facture->creer && $_GET['action'] == 'refsupplier')
			{
				print '<form action="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="set_ref_supplier">';
				print '<input type="text" class="flat" size="20" name="ref_supplier" value="'.$fac->ref_supplier.'">';
				print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print $fac->ref_supplier;
			}
			print '</td>';
			print '</tr>';

			// Third party
			print '<tr><td>'.$langs->trans('Supplier').'</td><td colspan="4">'.$societe->getNomUrl(1).' (<a href="index.php?socid='.$fac->socid.'">'.$langs->trans('OtherBills').'</a>)</td></tr>';

			// Type
			print '<tr><td>'.$langs->trans('Type').'</td><td colspan="4">';
			print $fac->getLibType();
			if ($fac->type == 1)
			{
				$facreplaced=new FactureFournisseur($db);
				$facreplaced->fetch($fac->fk_facture_source);
				print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
			}
			if ($fac->type == 2)
			{
				$facusing=new FactureFournisseur($db);
				$facusing->fetch($fac->fk_facture_source);
				print ' ('.$langs->transnoentities("CorrectInvoice",$facusing->getNomUrl(1)).')';
			}

			$facidavoir=$fac->getListIdAvoirFromInvoice();
			if (sizeof($facidavoir) > 0)
			{
				print ' ('.$langs->transnoentities("InvoiceHasAvoir");
				$i=0;
				foreach($facidavoir as $id)
				{
					if ($i==0) print ' ';
					else print ',';
					$facavoir=new FactureFournisseur($db);
					$facavoir->fetch($id);
					print $facavoir->getNomUrl(1);
				}
				print ')';
			}
			if ($facidnext > 0)
			{
				$facthatreplace=new FactureFournisseur($db);
				$facthatreplace->fetch($facidnext);
				print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
			}
			print '</td></tr>';


			// Label
			print '<tr><td>'.$langs->trans('Label').'</td><td colspan="3">';
			print $fac->libelle;
			print '</td>';

			/*
			 * List of payments
			 */
			$nbrows=7;
			if ($conf->projet->enabled) $nbrows++;

			print '<td rowspan="'.$nbrows.'" valign="top">';
			$sql  = 'SELECT '.$db->pdate('datep').' as dp, pf.amount,';
			$sql .= ' c.libelle as paiement_type, p.num_paiement, p.rowid';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn as p';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_paiementfourn = p.rowid';
			$sql .= ' WHERE pf.fk_facturefourn = '.$fac->id;
			$sql .= ' ORDER BY dp DESC';

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$i = 0; $totalpaye = 0;
				print '<table class="nobordernopadding" width="100%">';
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans('Payments').'</td>';
				print '<td>'.$langs->trans('Type').'</td>';

				if ($fac->statut == 1 && $fac->paye == 0 && $user->societe_id == 0)
				{
					$tdsup=' colspan="2"';
				}
				print '<td align="right">'.$langs->trans('AmountTTC').'</td><td'.$tdsup.'>&nbsp;</td></tr>';

				$var=True;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;
					print '<tr '.$bc[$var].'>';
					print '<td nowrap><a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans('Payment'),'payment').'</a> '.dol_print_date($objp->dp,'day')."</td>\n";
					print '<td>'.$objp->paiement_type.' '.$objp->num_paiement.'</td>';
					print '<td align="right">'.price($objp->amount).'</td><td>&nbsp;'.$langs->trans('Currency'.$conf->monnaie).'</td>';

					if ($fac->statut == 1 && $fac->paye == 0 && $user->societe_id == 0)
					{
						print '<td align="center">';
						print '<a href="fiche.php?facid='.$fac->id.'&amp;action=deletepaiement&amp;paiement_id='.$objp->rowid.'">';
						print img_delete();
						print '</a></td>';
					}

					print '</tr>';
					$totalpaye += $objp->amount;
					$i++;
				}

				if ($fac->paye == 0)
				{
					print '<tr><td colspan="2" align="right">'.$langs->trans('AlreadyPaid').' :</td><td align="right"><b>'.price($totalpaye).'</b></td><td>&nbsp;'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
					print '<tr><td colspan="2" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($fac->total_ttc).'</td><td>&nbsp;'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

					$resteapayer = $fac->total_ttc - $totalpaye;

					print '<tr><td colspan="2" align="right">'.$langs->trans('RemainderToPay').' :</td>';
					print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayer).'</b></td><td>&nbsp;'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
				}
				print '</table>';
				$db->free($result);
			}
			else
			{
				dol_print_error($db);
			}
			print '</td>';


			print '</tr>';

			// Date
			print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3" nowrap="nowrap">';
			print dol_print_date($fac->datep,'daytext').'</td></tr>';

			// Due date
			print '<tr>';
			print '<td>'.$langs->trans('DateEcheance').'</td><td colspan="3">';
			print dol_print_date($fac->date_echeance,'daytext');
			if (($fac->paye == 0) && ($fac->statut > 0) && $fac->date_echeance < ($now - $conf->facture->fournisseur->warning_delay)) print img_picto($langs->trans("Late"),"warning");
			print '</td></tr>';

			// Status
			$alreadypaid=$fac->getSommePaiement();
			print '<tr><td>'.$langs->trans('Status').'</td><td colspan="3">'.$fac->getLibStatut(4,$alreadypaid).'</td></tr>';

			print '<tr><td>'.$langs->trans('AmountHT').'</td><td><b>'.price($fac->total_ht).'</b></td><td colspan="2" align="left">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($fac->total_tva).'</td><td colspan="2" align="left">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($fac->total_ttc).'</td><td colspan="2" align="left">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Project
			if ($conf->projet->enabled)
			{
				$langs->load('projects');
				print '<tr>';
				print '<td>';

				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('Project');
				print '</td>';
				if ($_GET['action'] != 'classer')
				{
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classer&amp;facid='.$fac->id.'">';
					print img_edit($langs->trans('SetProject'),1);
					print '</a></td>';
				}
				print '</tr></table>';

				print '</td><td colspan="3">';
				if ($_GET['action'] == 'classer')
				{
					$html->form_project($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->socid,$fac->fk_project,'projetid');
				}
				else
				{
					$html->form_project($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->socid,$fac->fk_project,'none');
				}
				print '</td>';
				print '</tr>';
			}

			print '</table>';


			/*
			 * Lines
			 */
			print '<br>';
			print '<table class="noborder" width="100%">';
			$var=1;
			for ($i = 0 ; $i < sizeof($fac->lignes) ; $i++)
			{
				if ($i == 0)
				{
					print '<tr class="liste_titre"><td>'.$langs->trans('Label').'</td>';
					print '<td align="right">'.$langs->trans('VAT').'</td>';
					print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
					print '<td align="right">'.$langs->trans('PriceUTTC').'</td>';
					print '<td align="right">'.$langs->trans('Qty').'</td>';
					print '<td align="right">'.$langs->trans('TotalHTShort').'</td>';
					print '<td align="right">'.$langs->trans('TotalTTCShort').'</td>';
					print '<td>&nbsp;</td>';
					print '<td>&nbsp;</td>';
					print '</tr>';
				}

				// Show product and description
				$type=$fac->lignes[$i]->product_type?$fac->lignes[$i]->product_type:$fac->lignes[$i]->fk_product_type;
				// Try to enhance type detection using date_start and date_end for free lines where type
				// was not saved.
				if (! empty($fac->lignes[$i]->date_start)) $type=1;
				if (! empty($fac->lignes[$i]->date_end)) $type=1;

				$var=!$var;

				// Edit line
				if ($fac->statut == 0 && $_GET['action'] == 'mod_ligne' && $_GET['etat'] == '0' && $_GET['ligne_id'] == $fac->lignes[$i]->rowid)
				{
					print '<form action="fiche.php?facid='.$fac->id.'&amp;etat=1&amp;ligne_id='.$fac->lignes[$i]->rowid.'" method="post">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="update_line">';
					print '<tr '.$bc[$var].'>';

					// Show product and description
					print '<td>';
					if (($conf->produit->enabled || $conf->service->enabled) && $fac->lignes[$i]->fk_product)
					{
						print '<input type="hidden" name="idprod" value="'.$fac->lignes[$i]->fk_product.'">';
						$product_static=new ProductFournisseur($db);
						$product_static->fetch($fac->lignes[$i]->fk_product);
						$text=$product_static->getNomUrl(1);
						$text.= ' - '.$product_static->libelle;
						print $text;
						print '<br>';
					}
					else
					{
						print $html->select_type_of_lines($fac->lignes[$i]->product_type,'type',1);
						if ($conf->produit->enabled && $conf->service->enabled) print '<br>';
					}

					// Description - Editor wysiwyg
					if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
					{
						require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
						$doleditor=new DolEditor('label',$fac->lignes[$i]->description,200,'dolibarr_details');
						$doleditor->Create();
					}
					else
					{
						$nbrows=ROWS_2;
						if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
						print '<textarea name="label" class="flat" cols="70" rows="'.$nbrows.'">'.dol_htmlentitiesbr_decode($fac->lignes[$i]->description).'</textarea>';
					}
					print '</td>';

					// VAT
					print '<td align="right">';
					$html->select_tva('tauxtva',$fac->lignes[$i]->tva_tx,$societe,$mysoc);
					print '</td>';

					// Unit price
					print '<td align="right" nowrap="nowrap"><input size="4" name="puht" type="text" value="'.price($fac->lignes[$i]->pu_ht).'"></td>';

					print '<td align="right" nowrap="nowrap"><input size="4" name="puttc" type="text" value=""></td>';

					print '<td align="right"><input size="1" name="qty" type="text" value="'.$fac->lignes[$i]->qty.'"></td>';

					print '<td align="right" nowrap="nowrap">&nbsp;</td>';

					print '<td align="right" nowrap="nowrap">&nbsp;</td>';

					print '<td align="center" colspan="2"><input type="submit" class="button" value="'.$langs->trans('Save').'">';
					print '<br /><input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';

					print '</tr>';
					print '</form>';
				}
				else // Affichage simple de la ligne
				{
					print '<tr '.$bc[$var].'>';

					// Show product and description
					print '<td>';
					if ($fac->lignes[$i]->fk_product)
					{
						print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

						$product_static=new ProductFournisseur($db);
						$product_static->fetch($fac->lignes[$i]->fk_product);
						$text=$product_static->getNomUrl(1);
						$text.= ' - '.$product_static->libelle;
						$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($fac->lignes[$i]->description));
						print $html->textwithtooltip($text,$description,3,'','',$i);

						// Show range
						print_date_range($fac->lignes[$i]->date_start,$fac->lignes[$i]->date_end);

						// Add description in form
						if ($conf->global->PRODUIT_DESC_IN_FORM) print ($fac->lignes[$i]->description && $fac->lignes[$i]->description!=$product_static->libelle)?'<br>'.dol_htmlentitiesbr($fac->lignes[$i]->description):'';
					}

					// Description - Editor wysiwyg
					if (! $fac->lignes[$i]->fk_product)
					{
						if ($type==1) $text = img_object($langs->trans('Service'),'service');
						else $text = img_object($langs->trans('Product'),'product');
						print $text.' '.nl2br($fac->lignes[$i]->description);

						// Show range
						print_date_range($fac->lignes[$i]->date_start,$fac->lignes[$i]->date_end);
					}
					print '</td>';

					// VAT
					print '<td align="right">'.vatrate($fac->lignes[$i]->tva_tx).'%</td>';

					// Unit price
					print '<td align="right" nowrap="nowrap">'.price($fac->lignes[$i]->pu_ht,'MU').'</td>';

					print '<td align="right" nowrap="nowrap">'.($fac->lignes[$i]->pu_ttc?price($fac->lignes[$i]->pu_ttc,'MU'):'&nbsp;').'</td>';

					print '<td align="right">'.$fac->lignes[$i]->qty.'</td>';

					print '<td align="right" nowrap="nowrap">'.price($fac->lignes[$i]->total_ht).'</td>';

					print '<td align="right" nowrap="nowrap">'.price($fac->lignes[$i]->total_ttc).'</td>';

					print '<td align="center" width="16">';
					if ($fac->statut == 0) print '<a href="fiche.php?facid='.$fac->id.'&amp;action=mod_ligne&amp;etat=0&amp;ligne_id='.$fac->lignes[$i]->rowid.'">'.img_edit().'</a>';
					else print '&nbsp;';
					print '</td>';

					print '<td align="center" width="16">';
					if ($fac->statut == 0) print '<a href="fiche.php?facid='.$fac->id.'&amp;action=confirm_delete_line&amp;ligne_id='.$fac->lignes[$i]->rowid.'">'.img_delete().'</a>';
					else print '&nbsp;';
					print '</td>';

					print '</tr>';
				}

			}

			/*
			 * Form to add new line
			 */

			if ($fac->statut == 0 && $_GET['action'] != 'mod_ligne')
			{
				print '<tr class="liste_titre">';
				print '<td>';
				print '<a name="add"></a>'; // ancre
				print $langs->trans('AddNewLine').' - '.$langs->trans("FreeZone").'</td>';
				print '<td align="right">'.$langs->trans('VAT').'</td>';
				print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
				print '<td align="right">'.$langs->trans('PriceUTTC').'</td>';
				print '<td align="right">'.$langs->trans('Qty').'</td>';
				print '<td align="right">&nbsp;</td>';
				print '<td align="right">&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print '</tr>';

				// Add free products/services form
				print '<form action="fiche.php?facid='.$fac->id.'&amp;action=addline" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="facid" value="'.$fac->id.'">';
				print '<input type="hidden" name="socid" value="'.$societe->id.'">';

				$var=true;
				print '<tr '.$bc[$var].'>';
				print '<td>';

				$forceall=1;	// For suppliers, we always show all types
				print $html->select_type_of_lines(isset($_POST["type"])?$_POST["type"]:-1,'type',1,0,$forceall);
				if ($forceall || ($conf->produit->enabled && $conf->service->enabled)
				|| (empty($conf->produit->enabled) && empty($conf->service->enabled))) print '<br>';

				// Editor wysiwyg
				if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
				{
					require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
					$doleditor=new DolEditor('label',$_POST["label"],100,'dolibarr_details');
					$doleditor->Create();
				}
				else
				{
					$nbrows=ROWS_2;
					if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
					print '<textarea class="flat" cols="60" name="label" rows="'.$nbrows.'">'.$_POST["label"].'</textarea>';
				}
				print '</td>';
				print '<td align="right">';
				print $html->select_tva('tauxtva',$conf->defaulttx,$societe,$mysoc);
				print '</td>';
				print '<td align="right">';
				print '<input size="4" name="amount" type="text">';
				print '</td>';
				print '<td align="right">';
				print '<input size="4" name="amountttc" type="text">';
				print '</td>';
				print '<td align="right">';
				print '<input size="1" name="qty" type="text" value="1">';
				print '</td>';
				print '<td align="right">&nbsp;</td>';
				print '<td align="center">&nbsp;</td>';
				print '<td align="center" valign="middle" colspan="2"><input type="submit" class="button" value="'.$langs->trans('Add').'"></td></tr>';
				print '</form>';

				// Ajout de produits/services predefinis
				if ($conf->produit->enabled || $conf->service->enabled)
				{
					print '<tr class="liste_titre">';
					print '<td colspan="4">';
					print $langs->trans("AddNewLine").' - ';
					if ($conf->service->enabled)
					{
						print $langs->trans('RecordedProductsAndServices');
					}
					else
					{
						print $langs->trans('RecordedProducts');
					}
					print '</td>';
					print '<td align="right">'.$langs->trans('Qty').'</td>';
					print '<td align="right">&nbsp;</td>';
					print '<td colspan="4">&nbsp;</td>';
					print '</tr>';

					print '<form name="addligne_predef" action="fiche.php?facid='.$fac->id.'&amp;action=addline" method="post">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="socid" value="'. $fac->socid .'">';
					print '<input type="hidden" name="facid" value="'.$fac->id.'">';
					print '<input type="hidden" name="socid" value="'.$fac->socid.'">';
					$var=! $var;
					print '<tr '.$bc[$var].'>';
					print '<td colspan="4">';
					$html->select_produits_fournisseurs($fac->socid,'','idprodfournprice','',$filtre);
					print '</td>';
					print '<td align="right"><input type="text" name="qty" value="1" size="1"></td>';
					print '<td>&nbsp;</td>';
					print '<td>&nbsp;</td>';
					print '<td align="center" valign="middle" colspan="2"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
					print '</tr>';
					print '</form>';
				}
			}

			print '</table>';

			print '</div>';
		}


		/*
		 * Boutons actions
		 */

		print '<div class="tabsAction">';

		if ($_GET['action'] != 'edit' && $fac->statut <= 1 && $fac->getSommePaiement() <= 0 && $user->rights->fournisseur->facture->creer)
		{
			print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
		}

		if ($_GET['action'] != 'edit' && $fac->statut == 1 && $fac->paye == 0  && $user->societe_id == 0)
		{
			print '<a class="butAction" href="paiement.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans('DoPayment').'</a>';
		}

		if ($_GET['action'] != 'edit' && $fac->statut == 1 && $fac->paye == 0  && $user->societe_id == 0)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=paid"';
			print '>'.$langs->trans('ClassifyPaid').'</a>';

			//print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=paid">'.$langs->trans('ClassifyPaid').'</a>';
		}

		if ($_GET['action'] != 'edit' && $fac->statut == 0 && $user->rights->fournisseur->facture->valider)
		{
			if (sizeof($fac->lignes))
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=valid"';
				print '>'.$langs->trans('Validate').'</a>';
			}
		}

		if ($_GET['action'] != 'edit' && $user->rights->fournisseur->facture->creer)
		{
			print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=clone&amp;socid='.$fac->socid.'">'.$langs->trans('ToClone').'</a>';
		}

		if ($_GET['action'] != 'edit' && $user->rights->fournisseur->facture->supprimer)
		{
			print '<a class="butActionDelete" href="fiche.php?facid='.$fac->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		print '</div>';



		if ($_GET['action'] != 'presend')
		{
			print '<table width="100%"><tr><td width="50%" valign="top">';
			print '<a name="builddoc"></a>'; // ancre

			/*
			 * Documents generes
			 */
			$filename=dol_sanitizeFileName($fac->ref);
			$filedir=$conf->fournisseur->dir_output.'/facture/'.get_exdir($fac->id,2).$fac->id;
			$urlsource=$_SERVER['PHP_SELF'].'?facid='.$fac->id;
			//$genallowed=$user->rights->fournisseur->facture->creer;
			$genallowed=false;	// TODO Waiting for supplier invoice generation
			$delallowed=$user->rights->fournisseur->facture->supprimer;

			$var=true;

			$somethingshown=$formfile->show_documents('facture_fournisseur',$filename,$filedir,$urlsource,$genallowed,$delallowed,$fac->modelpdf);


			print '</td><td valign="top" width="50%">';

			print '<br>';

			// List of actions on element
			/*
			include_once(DOL_DOCUMENT_ROOT.'/html.formactions.class.php');
			$formactions=new FormActions($db);
			$somethingshown=$formactions->showactions($fac,'invoice_supplier',$socid);
			*/

			print '</td></tr></table>';
		}
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
