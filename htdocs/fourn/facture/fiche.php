<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.fr>
 * Copyright (C) 2005-2007 Regis Houssin         <regis@dolibarr.fr>
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
	\file       htdocs/fourn/facture/fiche.php
	\ingroup    facture, fournisseur
	\brief      Page for supplier invoice card
	\version    $Id$
*/

require_once('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/facture/paiementfourn.class.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/fourn.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/product.class.php');
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/project.class.php');


if (!$user->rights->fournisseur->facture->lire)
	accessforbidden();

$langs->load('bills');
$langs->load('suppliers');
$langs->load('companies');

// S�curit� acc�s client
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


$html = new Form($db);
$mesg='';
$action=isset($_GET['action'])?$_GET['action']:$_POST['action'];

if ($_POST['action'] == 'confirm_valid' && $_POST['confirm'] == 'yes' && $user->rights->fournisseur->facture->valider)
{
	$facturefourn=new FactureFournisseur($db);
	$facturefourn->fetch($_GET['facid']);
    $facturefourn->set_valid($user);
	Header('Location: fiche.php?facid='.$_GET['facid']);
	exit;
}

if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes')
{
	if ($user->rights->fournisseur->facture->supprimer )
	{
		$facturefourn = new FactureFournisseur($db);
		$factfournid = $_GET['facid'];
		$facturefourn->delete($factfournid);
		Header('Location: index.php');
		exit;
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

if ($_GET['action'] == 'payed')
{
	$facturefourn=new FactureFournisseur($db);
	$facturefourn->fetch($_GET['facid']);
    $facturefourn->set_payed($user);
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
	$datefacture = $db->idate(mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']));
	$date_echeance = $db->idate(mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']));

	$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn set ';
	$sql .= " facnumber='".addslashes(trim($_POST['facnumber']))."'";
	$sql .= ", libelle='".addslashes(trim($_POST['libelle']))."'";
	$sql .= ", note='".$_POST['note']."'";
	$sql .= ", datef = '$datefacture'";
	$sql .= ", date_lim_reglement = '$date_echeance'";
	$sql .= ' WHERE rowid = '.$_GET['facid'].' ;';
	$result = $db->query( $sql);
}
/*
 * Action cr�ation
 */
if ($_POST['action'] == 'add' && $user->rights->fournisseur->facture->creer)
{
	if ($_POST['facnumber'])
	{
		$datefacture = dolibarr_mktime(12,0,0,
			$_POST['remonth'],
			$_POST['reday'],
			$_POST['reyear']);

		$db->begin();

		// Creation facture
		$facfou = new FactureFournisseur($db);

		$facfou->ref           = $_POST['facnumber'];
		$facfou->socid         = $_POST['socid'];
		$facfou->libelle       = $_POST['libelle'];
		$facfou->date          = $datefacture;
		$facfou->date_echeance = dolibarr_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);
		$facfou->note          = $_POST['note'];

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
	else
	{
		$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('RefSupplier')).'</div>';
		$_GET['action']='create';
		$_GET['socid']=$_POST['socid'];
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
		}
		else
		{
			$label = $_POST['label'];
		}

		$facfou->updateline($_GET['ligne_id'], $label, $pu, $_POST['tauxtva'], $_POST['qty'], $_POST['idprod'], $price_base_type);
	}
}

if ($_GET['action'] == 'add_ligne')
{
	$facfou = new FactureFournisseur($db, '', $_GET['facid']);

	if ($_POST['prodfournpriceid'])
	{
		$nv_prod = new Product($db);
		$idprod=$nv_prod->get_buyprice($_POST['prodfournpriceid'], $_POST['qty']);
		if ($idprod > 0)
		{
			$result=$nv_prod->fetch($idprod);
			
			// cas sp�cial pour lequel on a les meme r�f�rence que le fournisseur
			// $label = '['.$nv_prod->ref.'] - '. $nv_prod->libelle;
			$label = $nv_prod->libelle;

			$societe='';
			if ($_POST['socid'])
			{
				$societe=new Societe($db);
				$societe->fetch($_POST['socid']);
			}

			$tvatx=get_default_tva($societe,$mysoc,$nv_prod->tva_tx);

			$result=$facfou->addline($label, $nv_prod->fourn_pu, $tvatx, $_POST['qty'], $idprod);
		}
		if ($idprod == -1)
		{
			// Quantit� insuffisante
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
			if (!empty($_POST['amount']))
			{
				$ht = price2num($_POST['amount']);
				$facfou->addline($_POST['label'], $ht, $tauxtva, $_POST['qty']);
			}
			else
			{
				$ttc = price2num($_POST['amountttc']);
				$ht = $ttc / (1 + ($tauxtva / 100));
				$facfou->addline($_POST['label'], $ht, $tauxtva, $_POST['qty']);
			}
		}
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
*	Affichage page
*/
$addons='';
llxHeader('','', $addons);


// Mode creation
if ($_GET['action'] == 'create' or $_GET['action'] == 'copy')
{
	print_titre($langs->trans('NewBill'));

	if ($mesg) { print $mesg.'<br>'; }

	if ($_GET['action'] == 'copy')
	{
		$fac_ori = new FactureFournisseur($db);
		$fac_ori->fetch($_GET['facid']);
	}

	$societe='';
	if ($_GET['socid'])
	{
		$societe=new Societe($db);
		$societe->fetch($_GET['socid']);
	}

	print '<form name="add" action="fiche.php" method="post">';
	print '<input type="hidden" name="action" value="add">';
	print '<table class="border" width="100%">';
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

	if($_GET['action'] == 'copy'){
		print '<tr><td>'.$langs->trans('RefSupplier').'</td><td><input name="facnumber" value="'.$fac_ori->ref.'" type="text"></td>';
	}else{
		print '<tr><td>'.$langs->trans('RefSupplier').'</td><td><input name="facnumber" type="text"></td>';
	}

	print '<td width="50%" rowspan="4" valign="top"><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td></tr>';
	if ($_GET['action'] == 'copy')
	{
		print '<tr><td>'.$langs->trans('Label').'</td><td><input size="30" name="libelle" value="'.$fac_ori->libelle.'" type="text"></td></tr>';
	}
	else
	{
		print '<tr><td>'.$langs->trans('Label').'</td><td><input size="30" name="libelle" type="text"></td></tr>';
	}

	print '<tr><td>'.$langs->trans('DateInvoice').'</td><td>';
	$html->select_date('','','','','',"add");
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('DateEcheance').'</td><td>';
	$html->select_date('','ech','','','',"add");
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
			if ($_GET['action'] == 'copy')
			{
				$value_label = $fac_ori->lignes[$i-1]->description;
				$value_pu = $fac_ori->lignes[$i-1]->pu_ht;
				$value_tauxtva = $fac_ori->lignes[$i-1]->tva_taux;
				$value_qty = $fac_ori->lignes[$i-1]->qty;
			}
			else
			{
				$value_qty = '1';
				$value_tauxtva = '';
			}
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

		$productstatic = new Product($db);

		$fac = new FactureFournisseur($db);
		$fac->fetch($_GET['facid']);

		$societe = new Fournisseur($db);
		$societe->fetch($fac->socid);

		if ($_GET['action'] == 'edit')
		{
			print_titre($langs->trans('SupplierInvoice'));

			print '<form name="update" action="fiche.php?facid='.$fac->id.'" method="post">';
			print '<input type="hidden" name="action" value="update">';

			print '<table class="border" width="100%">';
			print '<tr><td>'.$langs->trans('Company').'</td>';

			print '<td>'.$societe->getNomUrl(1).'</td>';
			print '<td width="50%" valign="top">'.$langs->trans('NotePublic').'</td>';
			print '</tr>';

			print '<tr><td valign="top">'.$langs->trans('Ref').'</td><td valign="top">';
			print $fac->ref.'</td>';

			print '<tr><td valign="top">'.$langs->trans('RefSupplier').'</td><td valign="top">';
			print '<input name="facnumber" type="text" value="'.$fac->ref_supplier.'"></td>';

			$rownb=9;
			print '<td rowspan="'.$rownb.'" valign="top">';
			print '<textarea name="note" wrap="soft" cols="60" rows="'.ROWS_9.'">';
			print $fac->note;
			print '</textarea></td></tr>';

			print '<tr><td valign="top">'.$langs->trans('Label').'</td><td>';
			print '<input size="30" name="libelle" type="text" value="'.$fac->libelle.'"></td></tr>';

			print '<tr><td>'.$langs->trans('DateInvoice').'</td><td nowrap="nowrap">';
			$html->select_date($fac->datep,'','','','',"update");
			print '</td></tr>';

			print '<tr><td>'.$langs->trans('DateEcheance').'</td><td nowrap="nowrap">';
			$html->select_date($fac->date_echeance,'ech','','','',"update");
	        if (($fac->paye == 0) && ($fac->statut > 0) && $fac->date_echeance < (time() - $conf->facture->fournisseur->warning_delay)) print img_picto($langs->trans("Late"),"warning");
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

			/*
			 * Lignes
			 *
			 */
			print '<br>';
			$var=true;

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><td>'.$langs->trans('Label').'</td>';
			print '<td align="right">'.$langs->trans('VAT').'</td>';
			print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
			print '<td align="right">'.$langs->trans('PriceUTTC').'</td>';
			print '<td align="right">'.$langs->trans('Qty').'</td>';
			print '<td align="right">'.$langs->trans('TotalHT').'</td>';
			print '<td align="right">'.$langs->trans('TotalTTC').'</td>';
			print '<td colspan="2">&nbsp;</td></tr>';
			for ($i = 0 ; $i < sizeof($fac->lignes) ; $i++)
			{
				$var=!$var;
				// Affichage simple de la ligne
				print '<tr '.$bc[$var].'><td>'.$fac->lignes[$i]->description.'</td>';
				print '<td align="right">'.vatrate($fac->lignes[$i]->tva_taux).'%</td>';
				print '<td align="right" nowrap="nowrap">'.price($fac->lignes[$i]->pu_ht,'MU').'</td>';
				print '<td align="right" nowrap="nowrap">'.($fac->lignes[$i]->pu_ttc?price($fac->lignes[$i]->pu_ttc,'MU'):'&nbsp;').'</td>';
				print '<td align="right">'.$fac->lignes[$i]->qty.'</td>';
				print '<td align="right" nowrap="nowrap">'.price($fac->lignes[$i]->total_ht,'MT').'</td>';
				print '<td align="right" nowrap="nowrap">'.price($fac->lignes[$i]->total_ttc,'MT').'</td>';
				print '<td align="center" width="16">';
				print '<a href="fiche.php?facid='.$fac->id.'&amp;action=mod_ligne&amp;etat=0&amp;ligne_id='.$fac->lignes[$i]->rowid.'">'.img_edit().'</a>';
				print '</td>';
				print '<td align="center" width="16">';
				print '<a href="fiche.php?facid='.$fac->id.'&amp;action=confirm_delete_line&amp;ligne_id='.$fac->lignes[$i]->rowid.'">'.img_delete().'</a>';
				print '</td>';
				print '</td></tr>';
			}

            print '</table>';
		}
		else
		{
			/*
			 *
			 */
			$head = facturefourn_prepare_head($fac);
			$titre=$langs->trans('SupplierInvoice');
			dolibarr_fiche_head($head, 'card', $titre);

			if ($mesg) { print $mesg.'<br>'; }

			/*
			 * Confirmation de la suppression d'une ligne produit
			 */
			 if ($_GET['action'] == 'confirm_delete_line')
			 {
			 	$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;ligne_id='.$_GET["ligne_id"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteproductline');
			 	print '<br>';
			 }
			/*
			 * Confirmation de la validation
			 *
			 */
			if ($_GET['action'] == 'valid')
			{
				$html->form_confirm('fiche.php?facid='.$fac->id, $langs->trans('ValidateBill'), $langs->trans('ConfirmValidateBill', $fac->ref), 'confirm_valid');
				print '<br />';
			}

			/*
			* Confirmation de la suppression de la facture fournisseur
			*/
			if ($_GET['action'] == 'delete')
			{
				$html->form_confirm('fiche.php?facid='.$fac->id, $langs->trans('DeleteBill'), $langs->trans('ConfirmDeleteBill'), 'confirm_delete');
				print '<br />';
			}

			print '<table width="100%" class="notopnoleftnoright">';
			print '<tr><td width="50%" valign="top" class="notopnoleft">';

			/*
			 *   Facture
			 */
			print '<table class="border" width="100%">';

            // Ref
            print '<tr><td nowrap="nowrap">'.$langs->trans("Ref").'</td><td colspan="3">'.$fac->ref.'</td>';
            print "</tr>\n";

            // Ref supplier
            print '<tr><td nowrap="nowrap">'.$langs->trans("RefSupplier").'</td><td colspan="3">'.$fac->ref_supplier.'</td>';
            print "</tr>\n";

            // Societe
			print '<tr><td>'.$langs->trans('Company').'</td><td colspan="2">'.$societe->getNomUrl(1).'</td>';
			print '<td align="right"><a href="index.php?socid='.$fac->socid.'">'.$langs->trans('OtherBills').'</a></td>';
			print '</tr>';

			print '<tr><td>'.$langs->trans('Label').'</td><td colspan="3">';
			print $fac->libelle;
			print '</td>';
			print '</tr>';

			print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3" nowrap="nowrap">';
			print dolibarr_print_date($fac->datep,'daytext').'</td></tr>';

			print '<tr>';
			print '<td>'.$langs->trans('DateEcheance').'</td><td colspan="3">';
			print dolibarr_print_date($fac->date_echeance,'daytext');
	        if (($fac->paye == 0) && ($fac->statut > 0) && $fac->date_echeance < (time() - $conf->facture->fournisseur->warning_delay)) print img_picto($langs->trans("Late"),"warning");
			print '</td></tr>';

			// Status
			$alreadypayed=$fac->getSommePaiement();
			print '<tr><td>'.$langs->trans('Status').'</td><td colspan="3">'.$fac->getLibStatut(4,$alreadypayed).'</td></tr>';

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

			print '</td><td valign="top" class="notopnoleftnoright">';


			print '<table width="100%" class="noborder">';

			/*
			 * Liste des paiements
			 */
			print '<tr><td colspan="2">';
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
				print '<table class="noborder" width="100%">';
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
					print '<td nowrap><a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans('Payment'),'payment').'</a> '.dolibarr_print_date($objp->dp,'day')."</td>\n";
					print '<td>'.$objp->paiement_type.' '.$objp->num_paiement.'</td>';
					print '<td align="right">'.price($objp->amount).'</td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td>';

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
					print '<tr><td colspan="2" align="right">'.$langs->trans('AlreadyPayed').' :</td><td align="right"><b>'.price($totalpaye).'</b></td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
					print '<tr><td colspan="2" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($fac->total_ttc).'</td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

					$resteapayer = $fac->total_ttc - $totalpaye;

					print '<tr><td colspan="2" align="right">'.$langs->trans('RemainderToPay').' :</td>';
					print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayer).'</b></td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
				}
				print '</table>';
				$db->free($result);
			}
			else
			{
				dolibarr_print_error($db);
			}
			print '</td></tr>';
			print '</table>';



			print '</td></tr>';
			print '</table>';


			/*
			 * Lignes
			 *
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
					print '<td align="right">'.$langs->trans('TotalHT').'</td>';
					print '<td align="right">'.$langs->trans('TotalTTC').'</td>';
					print '<td>&nbsp;</td>';
					print '<td>&nbsp;</td>';
					print '</tr>';
				}
				
				$var=!$var;
				// Ligne en modification
				if ($fac->statut == 0 && $_GET['action'] == 'mod_ligne' && $_GET['etat'] == '0' && $_GET['ligne_id'] == $fac->lignes[$i]->rowid)
				{
					print '<form action="fiche.php?facid='.$fac->id.'&amp;etat=1&amp;ligne_id='.$fac->lignes[$i]->rowid.'" method="post">';
					print '<input type="hidden" name="action" value="update_line">';
					print '<tr '.$bc[$var].'>';
					print '<td>';
					if ($conf->produit->enabled && $fac->lignes[$i]->fk_product)
					{
						$product=new ProductFournisseur($db);
						$product->fetch($fac->lignes[$i]->fk_product);
						$product->ref=$product->libelle;	// Car sur facture fourn on met juste le libelle sur produits lies
						print $product->getNomUrl(1);
						print '<input type="hidden" name="idprod" value="'.$fac->lignes[$i]->fk_product.'">';
					}
					else
					{
						print '<textarea class="flat" cols="70" rows="'.ROWS_2.'" name="label">'.$fac->lignes[$i]->description.'</textarea>';
					}
					print '</td>';
					print '<td align="right">';
					$html->select_tva('tauxtva',$fac->lignes[$i]->tva_taux,$societe,$mysoc);
					print '</td>';
					print '<td align="right" nowrap="nowrap"><input size="6" name="puht" type="text" value="'.price($fac->lignes[$i]->pu_ht).'"></td>';
					print '<td align="right" nowrap="nowrap"><input size="6" name="puttc" type="text" value=""></td>';
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
					print '<td>';
					if ($fac->lignes[$i]->fk_product)
					{
						$productstatic->id=$fac->lignes[$i]->fk_product;
						$productstatic->type=1;
						//$productstatic->ref=$fac->lignes[$i]->ref;
						//print $productstatic->getNomUrl(1).' ('.$fac->lignes[$i]->ref_fourn.') - '.$fac->lignes[$i]->libelle;
						$productstatic->ref=$fac->lignes[$i]->libelle;
						print $productstatic->getNomUrl(1);
					}
					else
					{
						print nl2br($fac->lignes[$i]->description);
					}
					print '</td>';
					print '<td align="right">'.vatrate($fac->lignes[$i]->tva_taux).'%</td>';
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

			if ($fac->statut == 0 && $_GET['action'] != 'mod_ligne')
			{
				print '<tr class="liste_titre"><td>'.$langs->trans('Label').'</td>';
				print '<td align="right">'.$langs->trans('VAT').'</td>';
				print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
				print '<td align="right">'.$langs->trans('PriceUTTC').'</td>';
				print '<td align="right">'.$langs->trans('Qty').'</td>';
				print '<td align="right">'.$langs->trans('TotalHT').'</td>';
				print '<td align="right">'.$langs->trans('TotalTTC').'</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print '</tr>';

				/* Nouvelle ligne */
				$var=!$var;
				print '<form action="fiche.php?facid='.$fac->id.'&amp;action=add_ligne" method="post">';
				print '<input type="hidden" name="facid" value="'.$fac->id.'">';
				print '<input type="hidden" name="socid" value="'.$societe->id.'">';
				print '<tr '.$bc[$var].'>';
				print '<td>';
				print '<textarea class="flat" cols="70" rows="'.ROWS_2.'" name="label"></textarea>';
				print '</td>';
				print '<td align="right">';
				$html->select_tva('tauxtva','',$societe,$mysoc);
				print '</td>';
				print '<td align="right">';
				print '<input size="6" name="amount" type="text">';
				print '</td>';
				print '<td align="right">';
				print '<input size="6" name="amountttc" type="text">';
				print '</td>';
				print '<td align="right">';
				print '<input size="1" name="qty" type="text" value="1">';
				print '</td>';
				print '<td align="right">&nbsp;</td>';
				print '<td align="center">&nbsp;</td>';
				print '<td align="center" valign="middle" colspan="2"><input type="submit" class="button" value="'.$langs->trans('Add').'"></td></tr>';
				print '</form>';

	            // Ajout de produits/services pr�d�finis
	            if ($conf->produit->enabled)
	            {
	                print '<form name="addligne_predef" action="fiche.php?facid='.$fac->id.'&amp;action=add_ligne" method="post">';
	                print '<input type="hidden" name="socid" value="'. $fac->socid .'">';
	                print '<input type="hidden" name="facid" value="'.$fac->id.'">';
	                print '<input type="hidden" name="socid" value="'.$fac->socid.'">';
	                $var=! $var;
	                print '<tr '.$bc[$var].'>';
	                print '<td colspan="4">';
	                $html->select_produits_fournisseurs($fac->socid,'','prodfournpriceid',$filtre);
	                print '</td>';
	                print '<td align="right"><input type="text" name="qty" value="1" size="1"></td>';
	                print '<td>&nbsp;</td>';
	                print '<td>&nbsp;</td>';
	                print '<td align="center" valign="middle" rowspan="2" colspan="2"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
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

		if ($fac->statut <= 1 && $fac->getSommePaiement() <= 0 && $user->rights->fournisseur->facture->creer)
		{
			if ($_GET['action'] != 'edit')
			{
				print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
			}
		}

		if ($fac->statut == 1 && $fac->paye == 0  && $user->societe_id == 0)
		{
			print '<a class="butAction" href="paiement.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans('DoPayment').'</a>';
		}

		if ($fac->statut == 1 && price($resteapayer) <= 0 && $fac->paye == 0  && $user->societe_id == 0)
		{
			print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=payed">'.$langs->trans('ClassifyPayed').'</a>';
		}

		if ($fac->statut == 0 && $user->rights->fournisseur->facture->valider)
		{
			if (sizeof($fac->lignes) && $_GET['action'] <> 'edit')
			{
				print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=valid">'.$langs->trans('Valid').'</a>';
			}
		}
		else
			if ($user->rights->fournisseur->facture->creer)
			{
				print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=copy&amp;socid='.$fac->socid.'">'.$langs->trans('Copy').'</a>';
			}

		if ($_GET['action'] != 'edit' && $fac->statut == 0 && $user->rights->fournisseur->facture->supprimer)
		{
			print '<a class="butActionDelete" href="fiche.php?facid='.$fac->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		print '</div>';
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
