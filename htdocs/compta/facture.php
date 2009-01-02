<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2007 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
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
 *	\file       htdocs/compta/facture.php
 *	\ingroup    facture
 *	\brief      Page de création/visu facture
 *	\version    $Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT.'/includes/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');
require_once(DOL_DOCUMENT_ROOT.'/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/invoice.lib.php');
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/project.class.php');
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/lib/project.lib.php');
if ($conf->propal->enabled)   require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');
if ($conf->contrat->enabled)  require_once(DOL_DOCUMENT_ROOT.'/contrat/contrat.class.php');
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');

if (! $user->rights->facture->lire)
accessforbidden();

$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('main');

$sall=isset($_GET['sall'])?trim($_GET['sall']):trim($_POST['sall']);
$mesg=isset($_GET['mesg'])?urldecode($_GET['mesg']):'';
$projetid=isset($_GET['projetid'])?$_GET['projetid']:0;

// Security check
$socid=isset($_GET['socid'])?$_GET['socid']:$_POST['socid'];
$facid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $facid,'');

// Nombre de ligne pour choix de produit/service prédéfinis
$NBLINES=4;

$usehm=$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

// Action clone object
if ($_POST["action"] == 'confirm_clone' && $_POST['confirm'] == 'yes')
{
	if (1==0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
	{
		$mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	}
	else
	{
		$object=new Facture($db);
		$result=$object->createFromClone($_REQUEST['facid']);
		if ($result > 0)
		{
			header("Location: ".$_SERVER['PHP_SELF'].'?facid='.$result);
			exit;
		}
		else
		{
			$mesg=$object->error;
			$_GET['action']='';
			$_GET['id']=$_REQUEST['id'];
		}
	}
}

if ($_GET['action'] == 'reopen' && $user->rights->facture->creer)
{
	$fac = new Facture($db);
	$result = $fac->fetch($_GET['facid']);
	if ($fac->statut == 3 && ($fac->close_code == 'badcustomer' || $fac->close_code == 'abandon'))
	{
		$result = $fac->set_unpayed($user);
		if ($result > 0)
		{
			Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$_GET['facid']);
			exit;
		}
		else
		{
			$mesg='<div class="error">'.$fac->error.'</div>';
		}
	}
}

// Suppression de la facture
if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes' && $user->rights->facture->supprimer)
{
	if ($user->rights->facture->supprimer)
	{
		$fac = new Facture($db);
		$result = $fac->fetch($_GET['facid']);
		$result = $fac->delete();
		if ($result > 0)
		{
			Header('Location: '.$_SERVER["PHP_SELF"]);
			exit;
		}
		else
		{
			$mesg='<div class="error">'.$fac->error.'</div>';
		}
	}
}

/*
 *  Supprime une ligne produit AVEC ou SANS confirmation
 */
if (($_POST['action'] == 'confirm_deleteproductline' && $_POST['confirm'] == 'yes' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
|| ($_GET['action'] == 'deleteline' && !$conf->global->PRODUIT_CONFIRM_DELETE_LINE))
{
	if ($user->rights->facture->creer)
	{
		$fac = new Facture($db);
		$fac->fetch($_GET['facid']);
		$result = $fac->deleteline($_GET['rowid'], $user);
		if ($result > 0)
		{
			$outputlangs = $langs;
			if (! empty($_REQUEST['lang_id']))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($_REQUEST['lang_id']);
			}
			$result=facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
			if ($result > 0)
			{
				Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$_GET['facid']);
				exit;
			}
		}
		else
		{
			$mesg='<div clas="error">'.$fac->error.'</div>';
			$_GET['action']='';
		}
	}
}

// Supprime affectation d'un avoir a la facture
if ($_GET['action'] == 'unlinkdiscount')
{
	if ($user->rights->facture->creer)
	{
		$discount=new DiscountAbsolute($db);
		$result=$discount->fetch($_GET["discountid"]);
		$discount->unlink_invoice();
	}
}

// Validation
if ($_GET['action'] == 'valid')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);

	// On verifie signe facture
	if ($facture->type == 2)
	{
		// Si avoir, le signe doit etre négatif
		if ($facture->total_ht >= 0)
		{
			$mesg='<div class="error">'.$langs->trans("ErrorInvoiceAvoirMustBeNegative").'</div>';
			$_GET['action']='';
		}
	}
	else
	{
		// Si non avoir, le signe doit etre positif
		if ($facture->total_ht < 0)
		{
			$mesg='<div class="error">'.$langs->trans("ErrorInvoiceOfThisTypeMustBePositive").'</div>';
			$_GET['action']='';
		}
	}
}

if ($_POST['action'] == 'classin')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$facture->setProject($_POST['projetid']);
}

if ($_POST['action'] == 'setmode')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$result=$facture->mode_reglement($_POST['mode_reglement_id']);
	if ($result < 0) dolibarr_print_error($facture->db,$facture->error);
}

if ($_POST['action'] == 'setpaymentterm')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$date_lim_reglement=dolibarr_mktime(12,0,0,$_POST['paymenttermmonth'],$_POST['paymenttermday'],$_POST['paymenttermyear']);
	$result=$facture->cond_reglement($facture->cond_reglement_id,$date_lim_reglement);
	if ($result < 0) dolibarr_print_error($facture->db,$facture->error);
}

if ($_POST['action'] == 'setinvoicedate')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$facture->date=dolibarr_mktime(12,0,0,$_POST['invoicedatemonth'],$_POST['invoicedateday'],$_POST['invoicedateyear']);
	$result=$facture->update($user);
	if ($result < 0) dolibarr_print_error($facture->db,$facture->error);
}

if ($_POST['action'] == 'setconditions')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$result=$facture->cond_reglement($_POST['cond_reglement_id']);
	if ($result < 0) dolibarr_print_error($facture->db,$facture->error);
}

if ($_REQUEST['action'] == 'setremisepercent' && $user->rights->facture->creer)
{
	$fac = new Facture($db);
	$fac->fetch($_REQUEST['facid']);
	$result = $fac->set_remise($user, $_POST['remise_percent']);
	$_GET['facid']=$_REQUEST['facid'];
}

if ($_POST['action'] == "setabsolutediscount" && $user->rights->facture->creer)
{
	// POST[remise_id] ou POST[remise_id_for_payment]
	if (! empty($_POST["remise_id"]))
	{
		$fac = new Facture($db);
		$fac->id=$_GET['facid'];
		$ret=$fac->fetch($_GET['facid']);
		if ($ret > 0)
		{
			$result=$fac->insert_discount($_POST["remise_id"]);
			if ($result < 0)
			{
				$mesg='<div class="error">'.$fac->error.'</div>';
			}
		}
		else
		{
			dolibarr_print_error($db,$fac->error);
		}
	}
	if (! empty($_POST["remise_id_for_payment"]))
	{
		require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');
		$discount = new DiscountAbsolute($db);
		$discount->fetch($_POST["remise_id_for_payment"]);

		$result=$discount->link_to_invoice(0,$_GET['facid']);
		if ($result < 0)
		{
			$mesg='<div class="error">'.$discount->error.'</div>';
		}
	}
}

if ($_POST['action'] == 'set_ref_client')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$facture->set_ref_client($_POST['ref_client']);
}

// Classe à "validée"
if ($_REQUEST['action'] == 'confirm_valid' && $_REQUEST['confirm'] == 'yes' && $user->rights->facture->valider)
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$fac->fetch_client();

	$result = $fac->set_valid($user);
	if ($result >= 0)
	{
		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	}
	else
	{
		$mesg='<div class="error">'.$fac->error.'</div>';
	}
}

// Repasse la facture en mode brouillon
if ($_GET['action'] == 'modif' && $user->rights->facture->modifier && $conf->global->FACTURE_ENABLE_EDITDELETE)
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);

	// On vérifie si la facture a des paiements
	$sql = 'SELECT pf.amount';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf';
	$sql.= ' WHERE pf.fk_facture = '.$fac->id;

	$result = $db->query($sql);

	if ($result)
	{
		$i = 0;
		$num = $db->num_rows($result);

		while ($i < $num)
		{
	  $objp = $db->fetch_object($result);
	  $totalpaye += $objp->amount;
	  $i++;
		}
	}

	$resteapayer = $fac->total_ttc - $totalpaye;

	// On vérifie si les lignes de factures ont été exportées en compta et/ou ventilées
	$ventilExportCompta = $fac->getVentilExportCompta();

	// On vérifie si aucun paiement n'a été effectué
	if ($resteapayer == $fac->total_ttc	&& $fac->paye == 0 && $ventilExportCompta == 0)
	{
		$fac->set_draft($user);

		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	}
}

// Classe à "payée"
if ($_POST['action'] == 'confirm_payed' && $_POST['confirm'] == 'yes' && $user->rights->facture->paiement)
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$result = $fac->set_payed($user);
}
// Classe à "payée partiellement"
if ($_POST['action'] == 'confirm_payed_partially' && $_POST['confirm'] == 'yes' && $user->rights->facture->paiement)
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$close_code=$_POST["close_code"];
	$close_note=$_POST["close_note"];
	if ($close_code)
	{
		$result = $fac->set_payed($user,$close_code,$close_note);
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Reason")).'</div>';
	}
}
// Classe à "abandonnée"
if ($_POST['action'] == 'confirm_canceled' && $_POST['confirm'] == 'yes')
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$close_code=$_POST["close_code"];
	$close_note=$_POST["close_note"];
	if ($close_code)
	{
		$result = $fac->set_canceled($user,$close_code,$close_note);
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Reason")).'</div>';
	}
}

// Convertir en reduc
if ($_POST['action'] == 'confirm_converttoreduc' && $_POST['confirm'] == 'yes' && $user->rights->facture->creer)
{
	$db->begin();

	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$fac->fetch_client();
	$fac->fetch_lines();

	if (! $fac->paye)	// protection against multiple submit
	{
		// Boucle sur chaque taux de tva
		$i=0;
		foreach($fac->lignes as $ligne)
		{
			$amount_ht[$ligne->tva_tx]+=$ligne->total_ht;
			$amount_tva[$ligne->tva_tx]+=$ligne->total_tva;
			$amount_ttc[$ligne->tva_tx]+=$ligne->total_ttc;
			$i++;
		}

		// Insère une remise par famille de taux tva
		$discount = new DiscountAbsolute($db);
		$discount->desc='(CREDIT_NOTE)';
		$discount->tva_tx=abs($fac->total_ttc);
		$discount->fk_soc=$fac->socid;
		$discount->fk_facture_source=$fac->id;

		$error=0;
		foreach($amount_ht as $tva_tx => $xxx)
		{
			$discount->amount_ht=abs($amount_ht[$tva_tx]);
			$discount->amount_tva=abs($amount_tva[$tva_tx]);
			$discount->amount_ttc=abs($amount_ttc[$tva_tx]);
			$discount->tva_tx=abs($tva_tx);

			$result=$discount->create($user);
			if ($result < 0)
			{
				$error++;
				break;
			}
		}

		if (! $error)
		{
			// Classe facture
			$result=$fac->set_payed($user);
			if ($result > 0)
			{
				//$mesg='OK'.$discount->id;
				$db->commit();
			}
			else
			{
				$mesg='<div class="error">'.$fac->error.'</div>';
				$db->rollback();
			}
		}
		else
		{
			$mesg='<div class="error">'.$discount->error.'</div>';
			$db->rollback();
		}
	}
}



/*
 * Insert invoice
 */
if ($_POST['action'] == 'add' && $user->rights->facture->creer)
{
	$facture = new Facture($db);
	$facture->socid=$_POST['socid'];
	$facture->fetch_client();

	$db->begin();

	// Replacement invoice
	if ($_POST['type'] == 1)
	{
		$datefacture = dolibarr_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture))
		{
			$error=1;
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Date")).'</div>';
		}

		if (! ($_POST['fac_replacement'] > 0))
		{
			$error=1;
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("ReplaceInvoice")).'</div>';
		}

		if (! $error)
		{
			// This is a replacement invoice
			$result=$facture->fetch($_POST['fac_replacement']);

			$facture->date           = $datefacture;
			$facture->note_public    = trim($_POST['note_public']);
			$facture->note           = trim($_POST['note']);
			$facture->ref_client     = $_POST['ref_client'];
			$facture->modelpdf       = $_POST['model'];
			$facture->projetid          = $_POST['projetid'];
			$facture->cond_reglement_id = $_POST['cond_reglement_id'];
			$facture->mode_reglement_id = $_POST['mode_reglement_id'];
			$facture->remise_absolue    = $_POST['remise_absolue'];
			$facture->remise_percent    = $_POST['remise_percent'];

			// Propriétés particulieres a facture de remplacement
			$facture->fk_facture_source = $_POST['fac_replacement'];
			$facture->type              = 1;

			$facid=$facture->createFromCurrent($user);
		}
	}

	// Facture avoir
	if ($_POST['type'] == 2)
	{
		if (! $_POST['fac_avoir'] > 0)
		{
			$error=1;
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("CorrectInvoice")).'</div>';
		}
			
		$datefacture = dolibarr_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture))
		{
			$error=1;
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Date")).'</div>';
		}

		if (! $error)
		{
			// Si facture avoir
			$datefacture = dolibarr_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

			//$result=$facture->fetch($_POST['fac_avoir']);

			$facture->socid 		 = $_POST['socid'];
			$facture->number         = $_POST['facnumber'];
			$facture->date           = $datefacture;
			$facture->note_public    = trim($_POST['note_public']);
			$facture->note           = trim($_POST['note']);
			$facture->ref_client     = $_POST['ref_client'];
			$facture->modelpdf       = $_POST['model'];
			$facture->projetid          = $_POST['projetid'];
			$facture->cond_reglement_id = 0;
			$facture->mode_reglement_id = $_POST['mode_reglement_id'];
			$facture->remise_absolue    = $_POST['remise_absolue'];
			$facture->remise_percent    = $_POST['remise_percent'];

			// Propriétés particulieres a facture avoir
			$facture->fk_facture_source = $_POST['fac_avoir'];
			$facture->type              = 2;

			$facid = $facture->create($user);
		}
	}

	if ($_POST['type'] == 0 && $_POST['fac_rec'] > 0)
	{
		// Si facture récurrente
		$datefacture = dolibarr_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

		$facture->socid 		 = $_POST['socid'];
		$facture->type           = $_POST['type'];
		$facture->number         = $_POST['facnumber'];
		$facture->date           = $datefacture;
		$facture->note_public    = trim($_POST['note_public']);
		$facture->note           = trim($_POST['note']);
		$facture->ref_client     = $_POST['ref_client'];
		$facture->modelpdf       = $_POST['model'];

		// Propriétés particulieres a facture recurrente
		$facture->fac_rec        = $_POST['fac_rec'];
		$facture->type           = 0;

		$facid = $facture->create($user);
	}

	if ($_POST['type'] == 0 && $_POST['fac_rec'] <= 0)
	{
		$datefacture = dolibarr_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture))
		{
			$error=1;
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Date")).'</div>';
		}

		if (! $error)
		{
			// Si facture standard

			$facture->socid 		 = $_POST['socid'];
			$facture->type           = $_POST['type'];
			$facture->number         = $_POST['facnumber'];
			$facture->date           = $datefacture;
			$facture->note_public    = trim($_POST['note_public']);
			$facture->note           = trim($_POST['note']);
			$facture->ref_client     = $_POST['ref_client'];
			$facture->modelpdf       = $_POST['model'];
			$facture->projetid          = $_POST['projetid'];
			$facture->cond_reglement_id = $_POST['cond_reglement_id'];
			$facture->mode_reglement_id = $_POST['mode_reglement_id'];
			$facture->amount            = $_POST['amount'];
			$facture->remise_absolue    = $_POST['remise_absolue'];
			$facture->remise_percent    = $_POST['remise_percent'];

			if (! $_POST['propalid'] && ! $_POST['commandeid'] && ! $_POST['contratid'])
			{
				for ($i = 1; $i <= $NBLINES; $i++)
				{
					if ($_POST['idprod'.$i])
					{
						$startday='';
						$endday='';
						if ($_POST['date_start'.$i.'year'] && $_POST['date_start'.$i.'month'] && $_POST['date_start'.$i.'day']) {
							$startday=$_POST['date_start'.$i.'year'].'-'.$_POST['date_start'.$i.'month'].'-'.$_POST['date_start'.$i.'day'];
						}
						if ($_POST['date_end'.$i.'year'] && $_POST['date_end'.$i.'month'] && $_POST['date_end'.$i.'day']) {
							$endday=$_POST['date_end'.$i.'year'].'-'.$_POST['date_end'.$i.'month'].'-'.$_POST['date_end'.$i.'day'];
						}
						$facture->add_product($_POST['idprod'.$i],$_POST['qty'.$i],$_POST['remise_percent'.$i],$startday,$endday);
					}
				}

				$facid = $facture->create($user);
			}
			else
			{
				/*
				 * Si creation depuis propale
				 */
				if ($_POST['propalid'])
				{
					$facture->propalid = $_POST['propalid'];
					$facid = $facture->create($user);

					if ($facid > 0)
					{
						$prop = New Propal($db);
						if ( $prop->fetch($_POST['propalid']) )
						{
							for ($i = 0 ; $i < sizeof($prop->lignes) ; $i++)
							{
								$desc=($prop->lignes[$i]->desc?$prop->lignes[$i]->desc:$prop->lignes[$i]->libelle);

								$result = $facture->addline(
								$facid,
								$desc,
								$prop->lignes[$i]->subprice,
								$prop->lignes[$i]->qty,
								$prop->lignes[$i]->tva_tx,
								$prop->lignes[$i]->fk_product,
								$prop->lignes[$i]->remise_percent,
									'',
									'',
								0,
								$prop->lignes[$i]->info_bits,
								$prop->lignes[$i]->fk_remise_except
								);

								if ($result < 0)
								{
									$error++;
									break;
								}
							}
						}
						else
						{
							$error++;
						}
					}
					else
					{
						$error++;
					}
				}

				/*
				 * Si création depuis commande
				 */
				if ($_POST['commandeid'])
				{
					$facture->commandeid = $_POST['commandeid'];
					$facid = $facture->create($user);

					if ($facid > 0)
					{
						$comm = new Commande($db);
						if ( $comm->fetch($_POST['commandeid']) )
						{
							$comm->fetch_lines();
							$lines = $comm->lignes;
							for ($i = 0 ; $i < sizeof($lines) ; $i++)
							{
								$desc=($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->libelle);

								// Should use a function using total_ht, total_ttc and total_vat
								$result = $facture->addline(
								$facid,
								$desc,
								$lines[$i]->subprice,
								$lines[$i]->qty,
								$lines[$i]->tva_tx,
								$lines[$i]->fk_product,
								$lines[$i]->remise_percent,
									'',
									'',
								0,
								$lines[$i]->info_bits,
								$lines[$i]->fk_remise_except
								);

								if ($result < 0)
								{
									$error++;
									break;
								}
							}
						}
						else
						{
							$error++;
						}
					}
					else
					{
						$error++;
					}
				}

				/*
				 * Si création depuis contrat
				 */
				if ($_POST['contratid'])
				{
					$facture->contratid = $_POST['contratid'];
					$facid = $facture->create($user);

					if ($facid > 0)
					{
						$contrat = New Contrat($db);
						if ($contrat->fetch($_POST['contratid']) > 0)
						{
							$lines = $contrat->fetch_lignes();

							for ($i = 0 ; $i < sizeof($lines) ; $i++)
							{
								$desc=($contrat->lignes[$i]->desc?$contrat->lignes[$i]->desc:$contrat->lignes[$i]->libelle);

								// Plage de dates
								$date_start=$contrat->lignes[$i]->date_debut_prevue;
								if ($contrat->lignes[$i]->date_debut_reel) $date_start=$contrat->lignes[$i]->date_debut_reel;
								$date_end=$contrat->lignes[$i]->date_fin_prevue;
								if ($contrat->lignes[$i]->date_fin_reel) $date_end=$contrat->lignes[$i]->date_fin_reel;

								$result = $facture->addline(
								$facid,
								$desc,
								$lines[$i]->subprice,
								$lines[$i]->qty,
								$lines[$i]->tva_tx,
								$lines[$i]->fk_product,
								$lines[$i]->remise_percent,
								$date_start,
								$date_end,
								0,
								$lines[$i]->info_bits,
								$lines[$i]->fk_remise_except
								);

								if ($result < 0)
								{
									$error++;
									break;
								}
							}
						}
						else
						{
							$error++;
						}
					}
					else
					{
						$error++;
					}
				}
			}
		}
	}

	// Fin création facture, on l'affiche
	if ($facid > 0 && ! $error)
	{
		$db->commit();
		Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$facid);
		exit;
	}
	else
	{
		$db->rollback();
		$_GET["action"]='create';
		$_GET["propalid"]=$_POST["propalid"];
		$_GET["commandeid"]=$_POST["commandeid"];
		$_GET["contratid"]=$_POST["contratid"];
		if (! $mesg) $mesg='<div class="error">'.$facture->error.'</div>';
	}
}

/*
 *  Ajout d'une ligne produit dans la facture
 */
if (($_POST['action'] == 'addligne' || $_POST['action'] == 'addligne_predef') && $user->rights->facture->creer)
{
	if ($_POST['qty'] && (($_POST['pu']!='' && ($_POST['np_desc'] || $_POST['dp_desc'])) || $_POST['idprod']))
	{
		$fac = new Facture($db);
		$ret=$fac->fetch($_POST['facid']);
		if ($ret < 0)
		{
			dolibarr_print_error($db,$fac->error);
			exit;
		}
		$ret=$fac->fetch_client();

		$suffixe = $_POST['idprod'] ? '_predef' : '';
		$date_start='';
		$date_end='';
		if ($_POST['date_start'.$suffixe.'year'] && $_POST['date_start'.$suffixe.'month'] && $_POST['date_start'.$suffixe.'day'])
		{
			$date_start=$_POST['date_start'.$suffixe.'year'].'-'.$_POST['date_start'.$suffixe.'month'].'-'.$_POST['date_start'.$suffixe.'day'];
			// If hour/minute are specified, append them
			if (($_POST['date_start'.$suffixe.'hour']) && ($_POST['date_start'.$suffixe.'min']))
			$date_start.=' '.$_POST['date_start'.$suffixe.'hour'].':'.$_POST['date_start'.$suffixe.'min'];
		}
		if ($_POST['date_end'.$suffixe.'year'] && $_POST['date_end'.$suffixe.'month'] && $_POST['date_end'.$suffixe.'day'])
		{
			$date_end=$_POST['date_end'.$suffixe.'year'].'-'.$_POST['date_end'.$suffixe.'month'].'-'.$_POST['date_end'.$suffixe.'day'];
			// If hour/minute are specified, append them
			if (($_POST['date_end'.$suffixe.'hour']) && ($_POST['date_end'.$suffixe.'min']))
			$date_end.=' '.$_POST['date_end'.$suffixe.'hour'].':'.$_POST['date_end'.$suffixe.'min'];
		}

		$price_base_type = 'HT';

		// Ecrase $pu par celui du produit
		// Ecrase $desc par celui du produit
		// Ecrase $txtva par celui du produit
		// Ecrase $base_price_type par celui du produit
		if ($_POST['idprod'])
		{
			$prod = new Product($db, $_POST['idprod']);
			$prod->fetch($_POST['idprod']);

			$tva_tx = get_default_tva($mysoc,$fac->client,$prod->tva_tx);
			$tva_npr = get_default_npr($mysoc,$fac->client,$prod->tva_tx);

			// On defini prix unitaire
			if ($conf->global->PRODUIT_MULTIPRICES == 1)
			{
				$pu_ht = $prod->multiprices[$fac->client->price_level];
				$pu_ttc = $prod->multiprices_ttc[$fac->client->price_level];
				$price_base_type = $prod->multiprices_base_type[$fac->client->price_level];
			}
			else
			{
				$pu_ht = $prod->price;
				$pu_ttc = $prod->price_ttc;
				$price_base_type = $prod->price_base_type;
			}

			// On reevalue prix selon taux tva car taux tva transaction peut etre different
			// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
			if ($tva_tx != $prod->tva_tx)
			{
				if ($price_base_type != 'HT')
				{
					$pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
				}
				else
				{
					$pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
				}
			}

			$desc = $prod->description;
			$desc.= $prod->description && $_POST['np_desc'] ? "\n" : "";
			$desc.= $_POST['np_desc'];
		}
		else
		{
			$pu_ht=$_POST['pu'];
			$tva_tx=eregi_replace('\*','',$_POST['tva_tx']);
			$tva_npr=eregi('\*',$_POST['tva_tx'])?1:0;
			$desc=$_POST['dp_desc'];
		}

		$info_bits=0;
		if ($tva_npr) $info_bits |= 0x01;


		if($prod->price_min && (price2num($pu_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($prod->price_min)))
		{
			$fac->error = $langs->trans("CantBeLessThanMinPrice",price2num($prod->price_min,'MU').' '.$langs->trans("Currency".$conf->monnaie)) ;
			$result = -1 ;
		}
		else
		{
			// Insert line
			$result = $fac->addline(
			$_POST['facid'],
			$desc,
			$pu_ht,
			$_POST['qty'],
			$tva_tx,
			$_POST['idprod'],
			$_POST['remise_percent'],
			$date_start,
			$date_end,
			0,
			$info_bits,
					'',
			$price_base_type,
			$pu_ttc
			);
		}
	}

	if ($result > 0)
	{
		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	}
	else
	{
		$mesg='<div class="error">'.$fac->error.'</div>';
	}

	$_GET['facid']=$_POST['facid'];   // Pour réaffichage de la fiche en cours d'édition
}

if ($_POST['action'] == 'updateligne' && $user->rights->facture->creer && $_POST['save'] == $langs->trans('Save'))
{
	$fac = new Facture($db,'',$_POST['facid']);
	if (! $fac->fetch($_POST['facid']) > 0) dolibarr_print_error($db);

	$date_start='';
	$date_end='';
	// Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
	// Retrieve start and end date (for product/service lines or customizable lines)
	if ($_POST['date_startyear'] && $_POST['date_startmonth'] && $_POST['date_startday'])
	{
		$date_start=$_POST['date_startyear'].'-'.$_POST['date_startmonth'].'-'.$_POST['date_startday'];
		// If hour/minute are specified, append them
		if (($_POST['date_starthour']) && ($_POST['date_startmin']))
		$date_start.=' '.$_POST['date_starthour'].':'.$_POST['date_startmin'];
	}
	if ($_POST['date_endyear'] && $_POST['date_endmonth'] && $_POST['date_endday'])
	{
		$date_end=$_POST['date_endyear'].'-'.$_POST['date_endmonth'].'-'.$_POST['date_endday'];
		// If hour/minute are specified, append them
		if (($_POST['date_endhour']) && ($_POST['date_endmin']))
		$date_end.=' '.$_POST['date_endhour'].':'.$_POST['date_endmin'];
	}

	// Define info_bits
	$info_bits=0;
	if (eregi('\*',$_POST['tva_tx'])) $info_bits |= 0x01;

	// Define vat_rate
	$vat_rate=$_POST['tva_tx'];
	$vat_rate=eregi_replace('\*','',$vat_rate);

	// On vérifie que le prix minimum est respecté
	if($_POST['productid']!=''){
		$productid = $_POST['productid'] ;
		$pruduct = new Product($db) ;
		$pruduct->fetch($productid) ;
	}
	if($pruduct->price_min && ($_POST['productid']!='') && (price2num($_POST['price'])*(1-price2num($_POST['remise_percent'])/100) < price2num($pruduct->price_min)))
	{
		$mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($pruduct->price_min,'MU').' '.$langs->trans("Currency".$conf->monnaie)).'</div>' ;
	}
	else
	{
		$result = $fac->updateline($_POST['rowid'],
		$_POST['desc'],
		$_POST['price'],
		$_POST['qty'],
		$_POST['remise_percent'],
		$date_start,
		$date_end,
		$vat_rate,
			'HT',
		$info_bits
		);

		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	}

	$_GET['facid']=$_POST['facid'];   // Pour réaffichage de la fiche en cours d'édition
}

if ($_POST['action'] == 'updateligne' && $user->rights->facture->creer && $_POST['cancel'] == $langs->trans('Cancel'))
{
	Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$_POST['facid']);   // Pour réaffichage de la fiche en cours d'édition
	exit;
}


/*
 * Ordonnancement des lignes
 */

if ($_GET['action'] == 'up' && $user->rights->facture->creer)
{
	$fac = new Facture($db,'',$_GET['facid']);
	$fac->fetch($_GET['facid']);
	$fac->line_up($_GET['rowid']);

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?facid='.$_GET["facid"].'#'.$_GET['rowid']);
	exit;
}

if ($_GET['action'] == 'down' && $user->rights->facture->creer)
{
	$fac = new Facture($db,'',$_GET['facid']);
	$fac->fetch($_GET['facid']);
	$fac->line_down($_GET['rowid']);

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?facid='.$_GET["facid"].'#'.$_GET['rowid']);
	exit;
}

/*
 * Add file
 */
if ($_POST['addfile'])
{
	// Set tmp user directory
	$conf->users->dir_tmp=DOL_DATA_ROOT."/users/".$user->id;
	$upload_dir = $conf->users->dir_tmp.'/temp/';

	if (! empty($_FILES['addedfile']['tmp_name']))
	{
		if (! is_dir($upload_dir)) create_exdir($upload_dir);

		if (is_dir($upload_dir))
		{
			if (dol_move_uploaded_file($_FILES['addedfile']['tmp_name'], $upload_dir . "/" . $_FILES['addedfile']['name'],0) > 0)
			{
				$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
				//print_r($_FILES);

				include_once(DOL_DOCUMENT_ROOT.'/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->add_attached_files($upload_dir . "/" . $_FILES['addedfile']['name'],$_FILES['addedfile']['name'],$_FILES['addedfile']['type']);
			}
			else
			{
				// Echec transfert (fichier dépassant la limite ?)
				$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
				// print_r($_FILES);
			}
		}
	}
	$_GET["action"]='presend';
}

/*
 * Send mail
 */
if (($_POST['action'] == 'send' || $_POST['action'] == 'relance') && ! $_POST['addfile'] && ! $_POST['cancel'])
{
	$langs->load('mails');

	$fac = new Facture($db,'',$_POST['facid']);
	$result=$fac->fetch($_POST['facid']);
	if ($result)
	{
		$ref = sanitizeFileName($fac->ref);
		$file = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';

		if (is_readable($file))
		{
			$fac->fetch_client();

			if ($_POST['sendto'])
			{
				// Le destinataire a été fourni via le champ libre
				$sendto = $_POST['sendto'];
				$sendtoid = 0;
			}
			elseif ($_POST['receiver'])
			{
				// Le destinataire a été fourni via la liste déroulante
				if ($_POST['receiver'] < 0)	// Id du tiers
				{
					$sendto = $fac->client->email;
					$sendtoid = 0;
				}
				else	// Id du contact
				{
					$sendto = $fac->client->contact_get_email($_POST['receiver']);
					$sendtoid = $_POST['receiver'];
				}
			}

			if (strlen($sendto))
			{
				$langs->load("commercial");

				$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
				$replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
				$message = $_POST['message'];
				$sendtocc = $_POST['sendtocc'];
				$deliveryreceipt = $_POST['deliveryreceipt'];

				if ($_POST['action'] == 'send')
				{
					if (strlen($_POST['subject'])) $subject = $_POST['subject'];
					else $subject = $langs->transnoentities('Bill').' '.$fac->ref;
					$actiontypecode='AC_FAC';
					$actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
					if ($message)
					{
						$actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
						$actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
						$actionmsg.=$message;
					}
					$actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
				}
				if ($_POST['action'] == 'relance')
				{
					if (strlen($_POST['subject'])) $subject = $_POST['subject'];
					else $subject = $langs->transnoentities('Relance facture '.$fac->ref);
					$actiontypecode='AC_FAC';
					$actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
					if ($message) {
						$actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
						$actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
						$actionmsg.=$message;
					}
					$actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
				}

				// Create form object
				include_once('../html.formmail.class.php');
				$formmail = new FormMail($db);

				$attachedfiles=$formmail->get_attached_files();
				$filepath = $attachedfiles['paths'];
				$filename = $attachedfiles['names'];
				$mimetype = $attachedfiles['mimes'];

				// Send mail
				require_once(DOL_DOCUMENT_ROOT.'/lib/CMailFile.class.php');
				$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt);
				if ($mailfile->error)
				{
					$mesg='<div class="error">'.$mailfile->error.'</div>';
				}
				else
				{
					$result=$mailfile->sendfile();
					if ($result)
					{
						$mesg='<div class="ok">'.$langs->trans('MailSuccessfulySent',$from,$sendto).'.</div>';

						$error=0;

						// Initialisation donnees
						$fac->sendtoid=$sendtoid;
						$fac->actiontypecode=$actiontypecode;
						$fac->actionmsg = $actionmsg;
						$fac->actionmsg2= $actionmsg2;
						$fac->facid=$fac->id;

						// Appel des triggers
						include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
						$interface=new Interfaces($db);
						$result=$interface->run_triggers('BILL_SENTBYMAIL',$fac,$user,$langs,$conf);
						if ($result < 0) { $error++; $this->errors=$interface->errors; }
						// Fin appel triggers

						if ($error)
						{
							dolibarr_print_error($db);
						}
						else
						{
							// Redirect here
							// This avoid sending mail twice if going out and then back to page
							Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&mesg='.urlencode($mesg));
							exit;
						}
					}
					else
					{
						$langs->load("other");
						$mesg='<div class="error">';
						if ($mailfile->error)
						{
							$mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
							$mesg.='<br>'.$mailfile->error;
						}
						else
						{
							$mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
						}
						$mesg.='</div>';
					}
				}
			}
			else
			{
				$langs->load("other");
				$mesg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').'</div>';
				dolibarr_syslog('Recipient email is empty');
			}
		}
		else
		{
			$langs->load("other");
			$mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
			dolibarr_syslog('Failed to read file: '.$file);
		}
	}
	else
	{
		$langs->load("other");
		$mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")).'</div>';
		dolibarr_syslog('Impossible de lire les données de la facture. Le fichier facture n\'a peut-être pas été généré.');
	}
}

/*
 * Générer ou regénérer le document PDF
 */
if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
{
	$fac = new Facture($db, 0, $_GET['facid']);
	$fac->fetch($_GET['facid']);

	if ($_REQUEST['model'])
	{
		$fac->setDocModel($user, $_REQUEST['model']);
	}

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$result);
		exit;
	}
	else
	{
		Header ('Location: '.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'#builddoc');
		exit;
	}
}



/*
 * View
 */

llxHeader('',$langs->trans('Bill'),'HelpInvoice');

$html = new Form($db);
$formfile = new FormFile($db);


/*********************************************************************
 *
 * Mode creation
 *
 **********************************************************************/
if ($_GET['action'] == 'create')
{
	$facturestatic=new Facture($db);

	print_titre($langs->trans('NewBill'));

	if ($mesg) print $mesg;

	$soc = new Societe($db);

	if ($_GET['propalid'])
	{
		$propal = New Propal($db);
		$propal->fetch($_GET['propalid']);
		$propal->fetch_client();

		$projetid=$propal->projetidp;
		$ref_client=$propal->ref_client;

		$soc=$propal->client;
		$cond_reglement_id = $propal->cond_reglement_id;
		$mode_reglement_id = $propal->mode_reglement_id;
		$remise_percent = $propal->remise_percent;
		$remise_absolue = $propal->remise_absolue;
	}
	elseif ($_GET['commandeid'])
	{
		$commande = New Commande($db);
		$commande->fetch($_GET['commandeid']);
		$commande->fetch_client();

		$projetid=$commande->projet_id;
		$ref_client=$commande->ref_client;

		$soc=$commande->client;
		$cond_reglement_id = $commande->cond_reglement_id;
		$mode_reglement_id = $commande->mode_reglement_id;
		$remise_percent = $commande->remise_percent;
		$remise_absolue = $commande->remise_absolue;
	}
	elseif ($_GET['contratid'])
	{
		$contrat = New Contrat($db);
		$contrat->fetch($_GET['contratid']);
		$contrat->fetch_client();

		$projetid=$contrat->fk_projet;

		$soc=$contrat->client;
		$cond_reglement_id = $soc->cond_reglement;
		$mode_reglement_id = $soc->mod_reglement;
		$remise_percent = $soc->remise_client;
		$remise_absolue = 0;
		$dateinvoice=empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
	}
	else
	{
		$res=$soc->fetch($socid);
		$cond_reglement_id = $soc->cond_reglement;
		$mode_reglement_id = $soc->mode_reglement;
		$remise_percent = $soc->remise_client;
		$remise_absolue = 0;
		$dateinvoice=empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
	}
	$absolute_discount=$soc->getAvailableDiscounts();


	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
	print '<input name="facnumber" type="hidden" value="provisoire">';

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans('Draft').'</td></tr>';

	/*
	 \todo
	 L'info "Reference commande client" est une carac de la commande et non de la facture.
	 Elle devrait donc etre stockée sur l'objet commande liée à la facture et non sur la facture.
	 Pour ceux qui veulent l'utiliser au niveau de la facture, positionner la
	 constante FAC_USE_CUSTOMER_ORDER_REF à 1.
	 */
	if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF)
	{
		print '<tr><td>'.$langs->trans('RefCustomerOrder').'</td><td>';
		print '<input type="text" name="ref_client" value="'.$ref_client.'">';
		print '</td></tr>';
	}

	// Factures prédéfinnies
	// TODO Use instead invoice in llx_facture table with a particular status
	if ($conf->global->FACTURE_ENABLE_RECUR)
	{
		if ($_GET['propalid'] == 0 && $_GET['commandeid'] == 0 && $_GET['contratid'] == 0)
		{
			$sql = 'SELECT r.rowid, r.titre, r.amount FROM '.MAIN_DB_PREFIX.'facture_rec as r';
			$sql.= ' WHERE r.fk_soc = '.$soc->id;
			$resql=$db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0;

				if ($num > 0)
				{
					print '<tr><td>'.$langs->trans('CreateFromRepeatableInvoice').'</td><td><select class="flat" name="fac_rec">';
					print '<option value="0" selected="true"></option>';
					while ($i < $num)
					{
						$objp = $db->fetch_object($resql);
						print '<option value="'.$objp->rowid.'">'.$objp->titre.' : '.$objp->amount.'</option>';
						$i++;
					}
					print '</select></td></tr>';
				}
				$db->free();
			}
			else
			{
				dolibarr_print_error($db);
			}
		}
	}

	// Tiers
	print '<tr><td>'.$langs->trans('Company').'</td><td colspan="2">';
	print $soc->getNomUrl(1);
	print '<input type="hidden" name="socid" value="'.$soc->id.'">';
	print '</td>';
	print '</tr>'."\n";

	// Type de facture
	$facids=$facturestatic->list_replacable_invoices($soc->id);
	if ($facids < 0)
	{
		dolibarr_print_error($db,$facturestatic);
		exit;
	}
	$options="";
	foreach ($facids as $facparam)
	{
		$options.='<option value="'.$facparam['id'].'"';
		if ($facparam['id'] == $_POST['fac_replacement']) $options.=' selected="true"';
		$options.='>'.$facparam['ref'];
		$options.=' ('.$facturestatic->LibStatut(0,$facparam['status']).')';
		$options.='</option>';
	}

	$facids=$facturestatic->list_qualified_avoir_invoices($soc->id);
	if ($facids < 0)
	{
		dolibarr_print_error($db,$facturestatic);
		exit;
	}
	$optionsav="";
	foreach ($facids as $key => $value)
	{
		$newinvoice=new Facture($db);
		$newinvoice->fetch($key);
		$optionsav.='<option value="'.$key.'"';
		if ($key == $_POST['fac_avoir']) $optionsav.=' selected="true"';
		$optionsav.='>';
		$optionsav.=$newinvoice->ref;
		$optionsav.=' ('.$newinvoice->getLibStatut(1,$value).')';
		$optionsav.='</option>';
	}

	// Type
	print '<tr><td valign="top">'.$langs->trans('Type').'</td><td colspan="2">';
	print '<table class="nobordernopadding">'."\n";

	print '<tr><td width="16px" valign="middle">';
	print '<input type="radio" name="type" value="0"'.($_POST['type']==0?' checked="true"':'').'>';
	print '</td><td valign="middle">';
	$desc=$html->textwithhelp($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
	print $desc;
	print '</td></tr>'."\n";

	print '<tr><td valign="middle">';
	print '<input type="radio" name="type" value="1"'.($_POST['type']==1?' checked=true':'');
	if (! $options) print ' disabled="true"';
	print '>';
	print '</td><td valign="middle">';
	$text=$langs->trans("InvoiceReplacementAsk").' ';
	$text.='<select class="flat" name="fac_replacement"';
	if (! $options) $text.=' disabled="true"';
	$text.='>';
	if ($options)
	{
		$text.='<option value="-1">&nbsp;</option>';
		$text.=$options;
	}
	else
	{
		$text.='<option value="-1">'.$langs->trans("NoReplacableInvoice").'</option>';
	}
	$text.='</select>';
	$desc=$html->textwithhelp($text,$langs->transnoentities("InvoiceReplacementDesc"),1);
	print $desc;
	print '</td></tr>'."\n";

	print '<tr><td valign="middle">';
	print '<input type="radio" name="type" value="2"'.($_POST['type']==2?' checked=true':'');
	if (! $optionsav) print ' disabled="true"';
	print '>';
	print '</td><td valign="middle">';
	$text=$langs->transnoentities("InvoiceAvoirAsk").' ';
	//	$text.='<input type="text" value="">';
	$text.='<select class="flat" name="fac_avoir"';
	if (! $optionsav) $text.=' disabled="true"';
	$text.='>';
	if ($optionsav)
	{
		$text.='<option value="-1">&nbsp;</option>';
		$text.=$optionsav;
	}
	else
	{
		$text.='<option value="-1">'.$langs->trans("NoInvoiceToCorrect").'</option>';
	}
	$text.='</select>';
	$desc=$html->textwithhelp($text,$langs->transnoentities("InvoiceAvoirDesc"),1);
	//.' ('.$langs->trans("FeatureNotYetAvailable").')',$langs->transnoentities("InvoiceAvoirDesc"),1);
	print $desc;
	print '</td></tr>'."\n";

	print '</table>';
	print '</td></tr>';

	// Discounts for third party
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
	if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	print '. ';
	if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->monnaie));
	else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	print '.';
	print '</td></tr>';

	// Date invoice
	print '<tr><td>'.$langs->trans('Date').'</td><td colspan="2">';
	$html->select_date($dateinvoice,'','','','',"add");
	print '</td></tr>';

	// Conditions de règlement
	print '<tr><td nowrap>'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$html->select_conditions_paiements($cond_reglement_id,'cond_reglement_id');
	print '</td></tr>';

	// Mode de règlement
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$html->select_types_paiements($mode_reglement_id,'mode_reglement_id');
	print '</td></tr>';

	// Project
	if ($conf->projet->enabled)
	{
		$langs->load('projects');
		print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
		select_projects($soc->id, $projetid, 'projetid');
		print '</td></tr>';
	}

	// Modele PDF
	print '<tr><td>'.$langs->trans('Model').'</td>';
	print '<td>';
	include_once(DOL_DOCUMENT_ROOT.'/includes/modules/facture/modules_facture.php');
	$model=new ModelePDFFactures();
	$liste=$model->liste_modeles($db);
	$html->select_array('model',$liste,$conf->global->FACTURE_ADDON_PDF);
	print "</td></tr>";

	// Note publique
	print '<tr>';
	print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
	print '<td valign="top" colspan="2">';
	print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';
	if (is_object($propal))
	{
		print $propal->note_public;
	}
	if (is_object($commande))
	{
		print $commande->note_public;
	}
	if (is_object($contrat))
	{
		print $contrat->note_public;
	}
	print '</textarea></td></tr>';

	// Note privée
	if (! $user->societe_id)
	{
		print '<tr>';
		print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
		print '<td valign="top" colspan="2">';
		print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">';
		if (is_object($propal))
		{
			print $propal->note;
		}
		if (is_object($commande))
		{
			print $commande->note;
		}
		if (is_object($contrat))
		{
			print $contrat->note;
		}
		print '</textarea></td></tr>';
	}

	if ($_GET['propalid'] > 0)
	{
		print "\n<!-- Propal info -->";
		print '<input type="hidden" name="amount"         value="'.$propal->price.'">'."\n";
		print '<input type="hidden" name="total"          value="'.$propal->total.'">'."\n";
		print '<input type="hidden" name="tva"            value="'.$propal->tva.'">'."\n";
		//print '<input type="hidden" name="remise_absolue" value="'.$propal->remise_absolue.'">'."\n";
		//print '<input type="hidden" name="remise_percent" value="'.$propal->remise_percent.'">'."\n";
		print '<input type="hidden" name="propalid"       value="'.$propal->id.'">';
			
		print '<tr><td>'.$langs->trans('Proposal').'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.img_object($langs->trans("ShowPropal"),'propal').' '.$propal->ref.'</a></td></tr>';
		print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($propal->price).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($propal->total_tva)."</td></tr>";
		print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($propal->total)."</td></tr>";
	}
	elseif ($_GET['commandeid'] > 0)
	{
		print "\n<!-- Order info -->";
		print "\n";
		print '<input type="hidden" name="amount"         value="'.$commande->total_ht.'">'."\n";
		print '<input type="hidden" name="total"          value="'.$commande->total_ttc.'">'."\n";
		print '<input type="hidden" name="tva"            value="'.$commande->total_tva.'">'."\n";
		//print '<input type="hidden" name="remise_absolue" value="'.$commande->remise_absolue.'">'."\n";
		//print '<input type="hidden" name="remise_percent" value="'.$commande->remise_percent.'">'."\n";
		print '<input type="hidden" name="commandeid"     value="'.$commande->id.'">';
			
		print '<tr><td>'.$langs->trans('Order').'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$commande->ref.'</a></td></tr>';
		print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($commande->total_ht).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($commande->total_tva)."</td></tr>";
		print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($commande->total_ttc)."</td></tr>";
	}
	elseif ($_GET['contratid'] > 0)
	{
		// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
		$contrat->remise_absolue=$remise_absolue;
		$contrat->remise_percent=$remise_percent;
		$contrat->update_price();

		print "\n<!-- Contract info -->";
		print '<input type="hidden" name="amount"         value="'.$contrat->total_ht.'">'."\n";
		print '<input type="hidden" name="total"          value="'.$contrat->total_ttc.'">'."\n";
		print '<input type="hidden" name="tva"            value="'.$contrat->total_tva.'">'."\n";
		//		print '<input type="hidden" name="remise_absolue" value="'.$contrat->remise_absolue.'">'."\n";
		//		print '<input type="hidden" name="remise_percent" value="'.$contrat->remise_percent.'">'."\n";
		print '<input type="hidden" name="contratid"      value="'.$contrat->id.'">';

		print '<tr><td>'.$langs->trans('Contract').'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$contrat->id.'">'.img_object($langs->trans("ShowContract"),'contract').' '.$contrat->ref.'</a></td></tr>';
		print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($contrat->total_ht).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($contrat->total_tva)."</td></tr>";
		print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($contrat->total_ttc)."</td></tr>";
	}
	else
	{
		if ($conf->global->PRODUCT_SHOW_WHEN_CREATE)
		{
			print '<tr><td colspan="3">';

			// Zone de choix des produits prédéfinis à la création
			print '<table class="noborder">';
			print '<tr><td>'.$langs->trans('ProductsAndServices').'</td>';
			print '<td>'.$langs->trans('Qty').'</td>';
			print '<td>'.$langs->trans('ReductionShort').'</td>';
			print '<td> &nbsp; &nbsp; </td>';
			if ($conf->service->enabled)
			{
				print '<td>'.$langs->trans('ServiceLimitedDuration').'</td>';
			}
			print '</tr>';
			for ($i = 1 ; $i <= $NBLINES ; $i++)
			{
				print '<tr>';
				print '<td>';
				// multiprix
				if($conf->global->PRODUIT_MULTIPRICES == 1)
				$html->select_produits('','idprod'.$i,'',$conf->produit->limit_size,$soc->price_level);
				else
				$html->select_produits('','idprod'.$i,'',$conf->produit->limit_size);
				print '</td>';
				print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td>';
				print '<td nowrap="nowrap"><input type="text" size="1" name="remise_percent'.$i.'" value="'.$soc->remise_client.'">%</td>';
				print '<td>&nbsp;</td>';
				// Si le module service est actif, on propose des dates de début et fin à la ligne
				if ($conf->service->enabled)
				{
					print '<td nowrap="nowrap">';
					print '<table class="nobordernopadding"><tr class="nocellnopadd">';
					print '<td class="nobordernopadding" nowrap="nowrap">';
					print $langs->trans('From').' ';
					print '</td><td class="nobordernopadding" nowrap="nowrap">';
					print $html->select_date('','date_start'.$i,$usehm,$usehm,1,"add");
					print '</td></tr>';
					print '<td class="nobordernopadding" nowrap="nowrap">';
					print $langs->trans('to').' ';
					print '</td><td class="nobordernopadding" nowrap="nowrap">';
					print $html->select_date('','date_end'.$i,$usehm,$usehm,1,"add");
					print '</td></tr></table>';
					print '</td>';
				}
				print "</tr>\n";
			}

			print '</table>';
			print '</td></tr>';
		}
	}

	// Bouton "Create Draft"
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></td></tr>';
	print "</table>\n";

	print "</form>\n";

	// Si creation depuis un propal
	if ($_GET['propalid'])
	{
		$title=$langs->trans('ProductsAndServices');

		$sql = 'SELECT pt.rowid, pt.description, pt.price, pt.fk_product, pt.fk_remise_except,';
		$sql.= ' pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice, pt.info_bits,';
		$sql.= ' p.label as product, p.ref, p.fk_product_type, p.rowid as prodid,';
		$sql.= ' p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product=p.rowid';
		$sql.= ' WHERE pt.fk_propal = '.$_GET['propalid'];
		$sql.= ' ORDER BY pt.rang ASC, pt.rowid';
	}
	if ($_GET['commandeid'])
	{
		$title=$langs->trans('Products');

		$sql = 'SELECT pt.rowid, pt.subprice, pt.tva_tx, pt.qty, pt.fk_remise_except, pt.remise_percent, pt.description, pt.info_bits,';
		$sql.= ' p.label as product, p.ref, p.rowid as prodid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as pt';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product = p.rowid';
		$sql.= ' WHERE pt.fk_commande = '.$commande->id;
		$sql.= ' ORDER BY pt.rowid ASC';
	}
	if ($_GET['contratid'])
	{
		$title=$langs->trans('Services');

		$sql = 'SELECT pt.rowid, pt.subprice, pt.tva_tx, pt.qty, pt.remise_percent, pt.description, pt.info_bits,';
		$sql.= ' pt.date_ouverture_prevue as date_debut_prevue, pt.date_ouverture as date_debut_reel,';
		$sql.= ' pt.date_fin_validite as date_fin_prevue, pt.date_cloture as date_fin_reel,';
		$sql.= ' p.label as product, p.ref, p.rowid as prodid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'contratdet as pt';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product = p.rowid';
		$sql.= ' WHERE pt.fk_contrat = '.$contrat->id;
		$sql.= ' ORDER BY pt.rowid ASC';
	}
	if ($_GET['propalid'] || $_GET['commandeid'] || $_GET['contratid'])
	{
		print '<br>';
		print_titre($title);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Ref').'</td>';
		print '<td>'.$langs->trans('Description').'</td>';
		print '<td align="right">'.$langs->trans('VAT').'</td>';
		print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
		print '<td align="right">'.$langs->trans('Qty').'</td>';
		print '<td align="right">'.$langs->trans('ReductionShort').'</td></tr>';

		// Lignes
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$var=True;
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print '<tr '.$bc[$var].'><td>';
				if (($objp->info_bits & 2) == 2)
				{
					print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$propal->socid.'">';
					print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
					print '</a>';
				}
				else if ($objp->prodid)
				{
					print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->prodid.'">'.img_object($langs->trans(''),'service').' '.$objp->ref.'</a>';
					print $objp->product?' - '.$objp->product:'';
					// Plage de dates si contrat
					if ($_GET['contratid'])
					{
						$date_start=$objp->date_debut_prevue;
						if ($objp->date_debut_reel) $date_start=$objp->date_debut_reel;
						$date_end=$objp->date_fin_prevue;
						if ($objp->date_fin_reel) $date_end=$objp->date_fin_reel;
						print_date_range($date_start,$date_end);
					}
				}
				else
				{
					print '&nbsp;';
				}
				print "</td>\n";
				print '<td>';
				if ($objp->description)
				{
					if ($objp->description == '(CREDIT_NOTE)')
					{
						$discount=new DiscountAbsolute($db);
						$discount->fetch($objp->fk_remise_except);
						print $langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
					}
					else
					{
						print dolibarr_trunc($objp->description,60);
					}
				}
				print '</td>';
				print '<td align="right">'.vatrate($objp->tva_tx).'%</td>';
				print '<td align="right">'.price($objp->subprice).'</td>';
				print '<td align="right">';
				print (($objp->info_bits & 2) != 2) ? $objp->qty : '&nbsp;';
				print '</td>';
				print '<td align="right">';
				print (($objp->info_bits & 2) != 2) ? $objp->remise_percent.'%' : '&nbsp;';
				print '</td>';
				print '</tr>';
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($db);
		}

		print '</table>';
	}

}
else
{
	$id = $_GET['facid'];
	$ref= $_GET['ref'];
	if ($id > 0 || ! empty($ref))
	{
		/* *************************************************************************** */
		/*                                                                             */
		/* Fiche en mode visu / edition                                                */
		/*                                                                             */
		/* *************************************************************************** */
		if ($mesg) print $mesg.'<br>';

		$facstatic = new Facture($db);

		$fac = new Facture($db);
		$result=$fac->fetch($_GET['facid'],$_GET['ref']);
		if ($result > 0)
		{
			if ($user->societe_id>0 && $user->societe_id!=$fac->socid)  accessforbidden('',0);

			$result=$fac->fetch_client();

			$soc = new Societe($db, $fac->socid);
			$soc->fetch($fac->socid);

			$totalpaye  = $fac->getSommePaiement();
			$totalavoir = $fac->getSommeCreditNote();

			// We cal also use bcadd to avoid pb with floating points
			// For example print 239.2 - 229.3 - 9.9; does not return 0.
			//$resteapayer=bcadd($fac->total_ttc,$totalpaye,$conf->global->MAIN_MAX_DECIMALS_TOT);
			//$resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
			$resteapayer = price2num($fac->total_ttc - $totalpaye - $totalavoir,'MT');
				
			if ($fac->paye) $resteapayer=0;
			$resteapayeraffiche=$resteapayer;

			$absolute_discount=$soc->getAvailableDiscounts('','fk_facture_source IS NULL');
			$absolute_creditnote=$soc->getAvailableDiscounts('','fk_facture_source IS NOT NULL');
				
			$author = new User($db);
			if ($fac->user_author)
			{
				$author->id = $fac->user_author;
				$author->fetch();
			}

			$facidnext=$fac->getIdReplacingInvoice();


			$head = facture_prepare_head($fac);

			dolibarr_fiche_head($head, 'compta', $langs->trans('InvoiceCustomer'));

			// Confirmation de la conversion de l'avoir en reduc
			if ($_GET['action'] == 'converttoreduc')
			{
				$text=$langs->trans('ConfirmConvertToReduc');
				$html->form_confirm($_SERVER['PHP_SELF'].'?facid='.$fac->id,$langs->trans('ConvertToReduc'),$text,'confirm_converttoreduc');
				print '<br />';
			}

			// Confirmation de la suppression de la facture
			if ($_GET['action'] == 'delete')
			{
				$text=$langs->trans('ConfirmDeleteBill');
				$html->form_confirm($_SERVER['PHP_SELF'].'?facid='.$fac->id,$langs->trans('DeleteBill'),$text,'confirm_delete');
				print '<br />';
			}

			// Confirmation de la validation
			if ($_GET['action'] == 'valid')
			{
				// on vérifie si la facture est en numérotation provisoire
				$facref = substr($fac->ref, 1, 4);
				if ($facref == 'PROV')
				{
					$numfa = $fac->getNextNumRef($soc);
				}
				else
				{
					$numfa = $fac->ref;
				}

				$text=$langs->trans('ConfirmValidateBill',$numfa);
				if ($conf->notification->enabled)
				{
					require_once(DOL_DOCUMENT_ROOT ."/notify.class.php");
					$notify=new Notify($db);
					$text.='<br>';
					$text.=$notify->confirmMessage(2,$fac->socid);
				}

				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id,$langs->trans('ValidateBill'),$text,'confirm_valid');
				print '<br />';
			}

			// Confirmation du classement payé
			if ($_GET['action'] == 'payed' && $resteapayer <= 0)
			{
				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id,$langs->trans('ClassifyPayed'),$langs->trans('ConfirmClassifyPayedBill',$fac->ref),'confirm_payed');
				print '<br />';
			}
			if ($_GET['action'] == 'payed' && $resteapayer > 0)
			{
				// Code
				$i=0;
				$close[$i]['code']='discount_vat';$i++;
				$close[$i]['code']='badcustomer';$i++;
				// Help
				$i=0;
				$close[$i]['label']=$langs->trans("HelpEscompte").'<br><br>'.$langs->trans("ConfirmClassifyPayedPartiallyReasonDiscountVatDesc");$i++;
				$close[$i]['label']=$langs->trans("ConfirmClassifyPayedPartiallyReasonBadCustomerDesc");$i++;
				// Texte
				$i=0;
				$close[$i]['reason']=$html->textwithhelp($langs->transnoentities("ConfirmClassifyPayedPartiallyReasonDiscountVat",$resteapayer,$langs->trans("Currency".$conf->monnaie)),$close[$i]['label'],1);$i++;
				$close[$i]['reason']=$html->textwithhelp($langs->transnoentities("ConfirmClassifyPayedPartiallyReasonBadCustomer",$resteapayer,$langs->trans("Currency".$conf->monnaie)),$close[$i]['label'],1);$i++;
				// arrayreasons[code]=reason
				foreach($close as $key => $val)
				{
					$arrayreasons[$close[$key]['code']]=$close[$key]['reason'];
				}

				// Crée un tableau formulaire
				$formquestion=array(
				'text' => $langs->trans("ConfirmClassifyPayedPartiallyQuestion"),
				array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"),  'values' => $arrayreasons),
				array('type' => 'text',  'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'size' => '100')
				);
				// Paiement incomplet. On demande si motif = escompte ou autre
				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id,$langs->trans('ClassifyPayed'),$langs->trans('ConfirmClassifyPayedPartially',$fac->ref),'confirm_payed_partially',$formquestion);
				print '<br>';
			}

			// Confirmation du classement abandonne
			if ($_GET['action'] == 'canceled')
			{
				// S'il y a une facture de remplacement pas encore validée (etat brouillon),
				// on ne permet pas de classer abandonner la facture.
				if ($facidnext)
				{
					$facturereplacement=new Facture($db);
					$facturereplacement->fetch($facidnext);
					$statusreplacement=$facturereplacement->statut;
				}
				if ($facidnext && $statusreplacement == 0)
				{
					print '<div class="error">'.$langs->trans("ErrorCantCancelIfReplacementInvoiceNotValidated").'</div>';
				}
				else
				{
					// Code
					$close[1]['code']='badcustomer';
					$close[2]['code']='abandon';
					// Help
					$close[1]['label']=$langs->trans("ConfirmClassifyPayedPartiallyReasonBadCustomerDesc");
					$close[2]['label']=$langs->trans("ConfirmClassifyAbandonReasonOtherDesc");
					// Texte
					$close[1]['reason']=$html->textwithhelp($langs->transnoentities("ConfirmClassifyPayedPartiallyReasonBadCustomer",$fac->ref),$close[1]['label'],1);
					$close[2]['reason']=$html->textwithhelp($langs->transnoentities("ConfirmClassifyAbandonReasonOther"),$close[2]['label'],1);
					// arrayreasons
					$arrayreasons[$close[1]['code']]=$close[1]['reason'];
					$arrayreasons[$close[2]['code']]=$close[2]['reason'];

					// Crée un tableau formulaire
					$formquestion=array(
					'text' => $langs->trans("ConfirmCancelBillQuestion"),
					array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"),  'values' => $arrayreasons),
					array('type' => 'text',  'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'size' => '100')
					);

					$html->form_confirm($_SERVER['PHP_SELF'].'?facid='.$fac->id,$langs->trans('CancelBill'),$langs->trans('ConfirmCancelBill',$fac->ref),'confirm_canceled',$formquestion);
					print '<br>';
				}
			}

			// Confirmation de la suppression d'une ligne produit
			if ($_GET['action'] == 'delete_product_line' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
			{
				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;rowid='.$_GET["rowid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteproductline', '', 'no');
				print '<br>';
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
				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id,$langs->trans('CloneInvoice'),$langs->trans('ConfirmCloneInvoice',$fac->ref),'confirm_clone',$formquestion,'yes');
				print '<br>';
			}
				
				
			// Invoice content

			print '<table class="border" width="100%">';

			// Reference
			print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
			print '<td colspan="5">';
			$morehtmlref='';
			$discount=new DiscountAbsolute($db);
			$result=$discount->fetch(0,$fac->id);
			if ($result > 0)
			{
				$morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
			}
			if ($result < 0)
			{
				dolibarr_print_error('',$discount->error);
			}
			print $html->showrefnav($fac,'ref','',1,'facnumber','ref',$morehtmlref);
			print '</td></tr>';

			// Ref client
			/*
			\todo
			L'info "Reference commande client" est une carac de la commande et non de la facture.
			Elle devrait donc etre stockée sur l'objet commande lié à la facture et non sur la facture.
			Pour ceux qui utilisent ainsi, positionner la constante FAC_USE_CUSTOMER_ORDER_REF à 1.
			*/
			if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF)
			{
				print '<tr><td>';
				print '<table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
				print $langs->trans('RefCustomerOrder').'</td><td align="left">';
				print '</td>';
				if ($_GET['action'] != 'RefCustomerOrder' && $fac->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=RefCustomerOrder&amp;facid='.$fac->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="5">';
				if ($user->rights->facture->creer && $_GET['action'] == 'RefCustomerOrder')
				{
					print '<form action="facture.php?facid='.$id.'" method="post">';
					print '<input type="hidden" name="action" value="set_ref_client">';
					print '<input type="text" class="flat" size="20" name="ref_client" value="'.$fac->ref_client.'">';
					print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
					print '</form>';
				}
				else
				{
					print $fac->ref_client;
				}
				print '</td>';
				print '</tr>';
			}

			// Tiers Société
			print '<tr><td>'.$langs->trans('Company').'</td>';
			print '<td colspan="5">'.$soc->getNomUrl(1,'compta').'</td>';
			print '</tr>';

			// Type
			print '<tr><td>'.$langs->trans('Type').'</td><td colspan="5">';
			print $fac->getLibType();
			if ($fac->type == 1)
			{
				$facreplaced=new Facture($db);
				$facreplaced->fetch($fac->fk_facture_source);
				print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
			}
			if ($fac->type == 2)
			{
				$facreplaced=new Facture($db);
				$facreplaced->fetch($fac->fk_facture_source);
				print ' ('.$langs->transnoentities("CorrectInvoice",$facreplaced->getNomUrl(1)).')';
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
					$facavoir=new Facture($db);
					$facavoir->fetch($id);
					print $facavoir->getNomUrl(1);
				}
				print ')';
			}
			if ($facidnext > 0)
			{
				$facthatreplace=new Facture($db);
				$facthatreplace->fetch($facidnext);
				print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
			}
			print '</td></tr>';

			// Ligne info remises tiers
			print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
			if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
			else print $langs->trans("CompanyHasNoRelativeDiscount");
			print '. ';
			if ($absolute_discount > 0)
			{
				if ($fac->statut > 0 || $fac->type == 2)
				{
					print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->monnaie)).'. ';
				}
				else
				{
					// Remise dispo de type non avoir
					$filter='fk_facture_source IS NULL';
					print '<br>';
					$html->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$fac->id,0,'remise_id',$soc->id,$absolute_discount,$filter,$resteapayer);
				}
			}
			if ($absolute_creditnote > 0)
			{
				// If validated, we show link "add credit note to payment"
				if ($fac->statut != 1 || $fac->type == 2)
				{
					print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->monnaie)).'. ';
				}
				else
				{
					// Remise dispo de type avoir
					$filter='fk_facture_source IS NOT NULL';
					if (! $absolute_discount) print '<br>';
					$html->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$fac->id,0,'remise_id_for_payment',$soc->id,$absolute_creditnote,$filter,$resteapayer);
				}
			}
			if (! $absolute_discount && ! $absolute_creditnote) print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
			print '</td></tr>';

			// Date invoice
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('Date');
			print '</td>';
			if ($fac->type != 2 && $_GET['action'] != 'editinvoicedate' && $fac->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editinvoicedate&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($fac->type != 2)
			{
				if ($_GET['action'] == 'editinvoicedate')
				{
					$html->form_date($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->date,'invoicedate');
				}
				else
				{
					print dolibarr_print_date($fac->date,'daytext');
				}
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';
			
			
			// Payments
			$nbrows=8;
			if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF) $nbrows++;
			if ($conf->projet->enabled) $nbrows++;

			print '<td rowspan="'.$nbrows.'" colspan="2" valign="top">';

			/*
			 * List of payments
			 */
			$sql = 'SELECT '.$db->pdate('datep').' as dp, pf.amount,';
			$sql.= ' c.libelle as paiement_type, p.num_paiement, p.rowid';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement_facture as pf';
			$sql.= ' WHERE pf.fk_facture = '.$fac->id.' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
			$sql.= ' ORDER BY dp, tms';

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$i = 0;
				print '<table class="noborder" width="100%">';

				// List of payments already done
				print '<tr class="liste_titre">';
				print '<td>'.($fac->type == 2 ? $langs->trans("PaymentsBack") : $langs->trans('Payments')).'</td>';
				print '<td>'.$langs->trans('Type').'</td>';
				print '<td align="right">'.$langs->trans('Amount').'</td>';
				print '<td width="18">&nbsp;</td>';
				print '</tr>';
					
				if ($fac->type != 2)
				{
					$var=True;
					while ($i < $num)
					{
						$objp = $db->fetch_object($result);
						$var=!$var;
						print '<tr '.$bc[$var].'><td>';
						print '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans('ShowPayment'),'payment').' ';
						print dolibarr_print_date($objp->dp,'day').'</a></td>';
						print '<td>'.$objp->paiement_type.' '.$objp->num_paiement.'</td>';
						print '<td align="right">'.price($objp->amount).'</td>';
						print '<td>&nbsp;</td>';
						print '</tr>';
						$i++;
					}

					// Already payed
					print '<tr><td colspan="2" align="right">'.$langs->trans('AlreadyPayed').' :</td><td align="right"><b>'.price($totalpaye).'</b></td><td>&nbsp;</td></tr>';

					// Billed
					print '<tr><td colspan="2" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($fac->total_ttc).'</td><td>&nbsp;</td></tr>';
					$resteapayeraffiche=$resteapayer;

					$creditnoteamount=0;
					
					// Loop on each credit note applied
					$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
					$sql.= " re.description, re.fk_facture_source, re.fk_facture_source";
					$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re";
					$sql.= " WHERE fk_facture = ".$fac->id;
					$resql=$db->query($sql);
					if ($resql)
					{
						$num = $db->num_rows($resql);
						$i = 0;
						$invoice=new Facture($db);
						while ($i < $num)
						{
							$obj = $db->fetch_object($resql);
							$invoice->fetch($obj->fk_facture_source);
							print '<tr><td colspan="2" align="right">'.$langs->trans("CreditNote").' ';
							print $invoice->getNomUrl(0);
							print ' :</td>';
							print '<td align="right" style="border: 1px solid;">'.price($obj->amount_ttc).'</td>';
							print '<td align="right">';
							print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&action=unlinkdiscount&discountid='.$obj->rowid.'">'.img_delete().'</a>';
							print '</td></tr>';
							$i++;
							$creditnoteamount += $obj->amount_ttc;
						}
					}
					else
					{
						dolibarr_print_error($db);
					}

					// Payé partiellement 'escompte'
					if (($fac->statut == 2 || $fac->statut == 3) && $fac->close_code == 'discount_vat')
					{
						print '<tr><td colspan="2" align="right" nowrap="1">';
						print $html->textwithhelp($langs->trans("Escompte").':',$langs->trans("HelpEscompte"),-1);
						print '</td><td align="right">'.price($fac->total_ttc - $creditnoteamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
						$resteapayeraffiche=0;
					}
					// Payé partiellement ou Abandon 'badcustomer'
					if (($fac->statut == 2 || $fac->statut == 3) && $fac->close_code == 'badcustomer')
					{
						print '<tr><td colspan="2" align="right" nowrap="1">';
						print $html->textwithhelp($langs->trans("Abandoned").':',$langs->trans("HelpAbandonBadCustomer"),-1);
						print '</td><td align="right">'.price($fac->total_ttc - $creditnoteamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
						//$resteapayeraffiche=0;
					}
					// Payé partiellement ou Abandon 'product_returned'
					if (($fac->statut == 2 || $fac->statut == 3) && $fac->close_code == 'product_returned')
					{
						print '<tr><td colspan="2" align="right" nowrap="1">';
						print $html->textwithhelp($langs->trans("ProductReturned").':',$langs->trans("HelpAbandonProductReturned"),-1);
						print '</td><td align="right">'.price($fac->total_ttc - $creditnoteamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
						$resteapayeraffiche=0;
					}
					// Payé partiellement ou Abandon 'abandon'
					if (($fac->statut == 2 || $fac->statut == 3) && $fac->close_code == 'abandon')
					{
						print '<tr><td colspan="2" align="right" nowrap="1">';
						$text=$langs->trans("HelpAbandonOther");
						if ($fac->close_note) $text.='<br><br><b>'.$langs->trans("Reason").'</b>:'.$fac->close_note;
						print $html->textwithhelp($langs->trans("Abandoned").':',$text,-1);
						print '</td><td align="right">'.price($fac->total_ttc - $creditnoteamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
						$resteapayeraffiche=0;
					}
					print '<tr><td colspan="2" align="right">';
					if ($resteapayeraffiche >= 0) print $langs->trans('RemainderToPay');
					else print $langs->trans('ExcessReceived');
					print ' :</td>';
					print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayeraffiche).'</b></td>';
					print '<td wrap="nowrap">&nbsp;</td></tr>';
				}
				else
				{
					// Solde avoir
					print '<tr><td colspan="2" align="right">'.$langs->trans('TotalTTCToYourCredit').' :</td>';
					print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price(abs($fac->total_ttc)).'</b></td><td>&nbsp;</td></tr>';
				}
				print '</table>';
				$db->free($result);
			}
			else
			{
				dolibarr_print_error($db);
			}

			print '</td></tr>';

			// Date payment term
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DateMaxPayment');
			print '</td>';
			if ($fac->type != 2 && $_GET['action'] != 'editpaymentterm' && $fac->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editpaymentterm&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($fac->type != 2)
			{
				if ($_GET['action'] == 'editpaymentterm')
				{
					$html->form_date($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->date_lim_reglement,'paymentterm');
				}
				else
				{
					print dolibarr_print_date($fac->date_lim_reglement,'daytext');
					if ($fac->date_lim_reglement < (time() - $conf->facture->client->warning_delay) && ! $fac->paye && $fac->statut == 1 && ! $fac->am) print img_warning($langs->trans('Late'));
				}
			}
			else
			{
				print '&nbsp;';
			}
			print '</td></tr>';

			// Conditions de règlement
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentConditionsShort');
			print '</td>';
			if ($fac->type != 2 && $_GET['action'] != 'editconditions' && $fac->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($fac->type != 2)
			{
				if ($_GET['action'] == 'editconditions')
				{
					$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->cond_reglement_id,'cond_reglement_id');
				}
				else
				{
					$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->cond_reglement_id,'none');
				}
			}
			else
			{
				print '&nbsp;';
			}
			print '</td></tr>';

			// Mode de reglement
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentMode');
			print '</td>';
			if ($_GET['action'] != 'editmode' && $fac->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($_GET['action'] == 'editmode')
			{
				$html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->mode_reglement_id,'mode_reglement_id');
			}
			else
			{
				$html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->mode_reglement_id,'none');
			}
			print '</td></tr>';

			// Lit lignes de facture pour déterminer montant
			// On s'en sert pas mais ca sert pour debuggage
			/*
			$sql  = 'SELECT l.price as price, l.qty, l.rowid, l.tva_taux,';
			$sql .= ' l.remise_percent, l.subprice';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'facturedet as l ';
			$sql .= ' WHERE l.fk_facture = '.$fac->id;
			$resql = $db->query($sql);
			if ($resql)
			{
			$num_lignes = $db->num_rows($resql);
			$i=0;
			$total_lignes_ht=0;
			$total_lignes_vat=0;
			$total_lignes_ttc=0;
			while ($i < $num_lignes)
			{
			$obj=$db->fetch_object($resql);
			$ligne_ht=($obj->price*$obj->qty);
			$ligne_vat=($ligne_ht*$obj->tva_taux/100);
			$ligne_ttc=($ligne_ht+$ligne_vat);
			$total_lignes_ht+=$ligne_ht;
			$total_lignes_vat+=$ligne_vat;
			$total_lignes_ttc+=$ligne_ttc;
			$i++;
			}
			}
			*/

			// Montants
			print '<tr><td>'.$langs->trans('AmountHT').'</td>';
			print '<td align="right" colspan="2" nowrap>'.price($fac->total_ht).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2" nowrap>'.price($fac->total_tva).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($fac->total_ttc).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans('Status').'</td>';
			print '<td align="left" colspan="3">'.($fac->getLibStatut(4,$totalpaye)).'</td></tr>';

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
					$html->form_project($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->socid,$fac->projetid,'projetid');
				}
				else
				{
					$html->form_project($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->socid,$fac->projetid,'none');
				}
				print '</td>';
				print '</tr>';
			}

			print '</table><br>';


			/*
			 * Lignes de factures
			 */
			$sql = 'SELECT l.fk_product, l.product_type, l.description, l.qty, l.rowid, l.tva_taux,';
			$sql.= ' l.fk_remise_except,';
			$sql.= ' l.remise_percent, l.subprice, l.info_bits,';
			$sql.= ' l.total_ht, l.total_tva, l.total_ttc,';
			$sql.= ' '.$db->pdate('l.date_start').' as date_start,';
			$sql.= ' '.$db->pdate('l.date_end').' as date_end,';
			$sql.= ' p.ref, p.fk_product_type, p.label as product,';
			$sql.= ' p.description as product_desc';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet as l';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON l.fk_product=p.rowid';
			$sql.= ' WHERE l.fk_facture = '.$fac->id;
			$sql.= ' ORDER BY l.rang ASC, l.rowid';

			$resql = $db->query($sql);
			if ($resql)
			{
				$num_lignes = $db->num_rows($resql);
				$i = 0; $total = 0;

				print '<table class="noborder" width="100%">';

				if ($num_lignes)
				{

					print '<tr class="liste_titre">';
					print '<td>'.$langs->trans('Description').'</td>';
					print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
					print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
					print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
					print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';
					print '<td align="right" width="50">'.$langs->trans('TotalHT').'</td>';
					print '<td width="48" colspan="3">&nbsp;</td>';
					print "</tr>\n";

				}
				$var=true;
				while ($i < $num_lignes)
				{
					$objp = $db->fetch_object($resql);
					$var=!$var;

					// Ligne en mode visu
					if ($_GET['action'] != 'editline' || $_GET['rowid'] != $objp->rowid)
					{
						print '<tr '.$bc[$var].'>';
						if ($objp->fk_product > 0)
						{
							print '<td>';
							print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

							// Affiche ligne produit
							$text = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
							if ($objp->fk_product_type==1) $text.= img_object($langs->trans('ShowService'),'service');
							else $text.= img_object($langs->trans('ShowProduct'),'product');
							$text.= ' '.$objp->ref.'</a>';
							$text.= ' - '.$objp->product;
							$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($objp->description));
							//print $description;
							print $html->textwithtooltip($text,$description,3,'','',$i);
							print_date_range($objp->date_start,$objp->date_end);
							if ($conf->global->PRODUIT_DESC_IN_FORM)
							{
								print ($objp->description && $objp->description!=$objp->product)?'<br>'.dol_htmlentitiesbr($objp->description):'';
							}

							print '</td>';
						}
						else
						{
							print '<td>';
							print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

							if (($objp->info_bits & 2) == 2)
							{
								print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$fac->socid.'">';
								print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
								print '</a>';
								if ($objp->description)
								{
									if ($objp->description == '(CREDIT_NOTE)')
									{
										$discount=new DiscountAbsolute($db);
										$discount->fetch($objp->fk_remise_except);
										print ' - '.$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
									}
									else
									{
										print ' - '.nl2br($objp->description);
									}
								}
							}
							else
							{
								if ($objp->product_type==1) $text = img_object($langs->trans('Service'),'service');
								else $text = img_object($langs->trans('Product'),'product');
								print $text.' '.nl2br($objp->description);
								print_date_range($objp->date_start,$objp->date_end);
							}
							print "</td>\n";
						}
						print '<td align="right">'.vatrate($objp->tva_taux,'%',$objp->info_bits).'</td>';
						print '<td align="right">'.price($objp->subprice)."</td>\n";
						print '<td align="right">';
						if (($objp->info_bits & 2) != 2)
						{
							print $objp->qty;
						}
						else print '&nbsp;';
						print '</td>';
						if ($objp->remise_percent > 0)
						{
							print '<td align="right">'.dolibarr_print_reduction($objp->remise_percent)."</td>\n";
						}
						else
						{
							print '<td>&nbsp;</td>';
						}
						print '<td align="right">'.price($objp->total_ht)."</td>\n";

						// Icone d'edition et suppression
						if ($fac->statut == 0  && $user->rights->facture->creer)
						{
							print '<td align="center">';
							if (($objp->info_bits & 2) == 2)
							{
								// Ligne remise prédéfinie, on permet pas modif
							}
							else
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'#'.$objp->rowid.'">';
								print img_edit();
								print '</a>';
							}
							print '</td>';
							print '<td align="center">';
							if ($conf->global->PRODUIT_CONFIRM_DELETE_LINE)
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=delete_product_line&amp;rowid='.$objp->rowid.'">';
							}
							else
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=deleteline&amp;rowid='.$objp->rowid.'">';
							}
							print img_delete();
							print '</a></td>';
							if ($num_lignes > 1)
							{
								print '<td align="center">';
								if ($i > 0)
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=up&amp;rowid='.$objp->rowid.'">';
									print img_up();
									print '</a>';
								}
								if ($i < $num_lignes-1)
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=down&amp;rowid='.$objp->rowid.'">';
									print img_down();
									print '</a>';
								}
								print '</td>';
							}
						}
						else
						{
							print '<td colspan="3">&nbsp;</td>';
						}
						print '</tr>';
					}

					// Ligne en mode update
					if ($_GET['action'] == 'editline' && $user->rights->facture->creer && $_GET['rowid'] == $objp->rowid)
					{
						print '<form name="updateligne" action="'.$_SERVER["PHP_SELF"].'#'.$objp->rowid.'" method="post">';
						print '<input type="hidden" name="action" value="updateligne">';
						print '<input type="hidden" name="facid" value="'.$fac->id.'">';
						print '<input type="hidden" name="rowid" value="'.$_GET['rowid'].'">';
						print '<tr '.$bc[$var].'>';
						print '<td>';
						print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
						if ($objp->fk_product > 0)
						{
							print '<input type="hidden" name="productid" value="'.$objp->fk_product.'">';
							print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
							if ($objp->fk_product_type==1) print img_object($langs->trans('ShowService'),'service');
							else print img_object($langs->trans('ShowProduct'),'product');
							print ' '.$objp->ref.'</a>';
							print ' - '.nl2br($objp->product);
							print '<br>';
						}
						// éditeur wysiwyg
						if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
						{
							require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
							$doleditor=new DolEditor('desc',$objp->description,164,'dolibarr_details');
							$doleditor->Create();
						}
						else
						{
							print '<textarea name="desc" class="flat" cols="70" rows="'.ROWS_2.'">';
							//print $objp->description;
							print dol_htmlentitiesbr_decode($objp->description);
							print '</textarea>';
						}
						print '</td>';
						print '<td align="right">';
						print $html->select_tva('tva_tx',$objp->tva_taux,$mysoc,$soc,'',$objp->info_bits);
						print '</td>';
						print '<td align="right"><input size="6" type="text" class="flat" name="price" value="'.price($objp->subprice,0,'',0).'"></td>';
						print '<td align="right">';
						if (($objp->info_bits & 2) != 2)
						{
							print '<input size="2" type="text" class="flat" name="qty" value="'.$objp->qty.'">';
						}
						else print '&nbsp;';
						print '</td>';
						print '<td align="right" nowrap>';
						if (($objp->info_bits & 2) != 2)
						{
							print '<input size="2" type="text" class="flat" name="remise_percent" value="'.$objp->remise_percent.'">%';
						}
						else print '&nbsp;';
						print '</td>';
						print '<td align="center" rowspan="1" colspan="5" valign="middle"><input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
						print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';
						print '</tr>' . "\n";
						if ($conf->service->enabled)
						{
							print '<tr '.$bc[$var].'>';
							print '<td colspan="9">'.$langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
							print $html->select_date($objp->date_start,'date_start',$usehm,$usehm,$objp->date_start?0:1,"updateligne");
							print ' '.$langs->trans('to').' ';
							print $html->select_date($objp->date_end,'date_end',$usehm,$usehm,$objp->date_end?0:1,"updateligne");
							print '</td>';
							print '</tr>';
						}
						print "</form>\n";
					}

					$total = $total + ($objp->qty * $objp->price);
					$i++;
				}

				$db->free($resql);
			}
			else
			{
				dolibarr_print_error($db);
			}

			/*
			 * Ajouter une ligne
			 */
			if ($fac->statut == 0 && $user->rights->facture->creer && $_GET['action'] <> 'valid' && $_GET['action'] <> 'editline')
			{
				print '<tr class="liste_titre">';
				print '<td>';
				print '<a name="add"></a>'; // ancre
				print $langs->trans('Description').'</td>';
				print '<td align="right">'.$langs->trans('VAT').'</td>';
				print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
				print '<td align="right">'.$langs->trans('Qty').'</td>';
				print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
				print '<td colspan="4">&nbsp;</td>';
				print "</tr>\n";

				// Ajout produit produits/services personalisés
				print '<form name="addligne" action="'.$_SERVER['PHP_SELF'].'#add" method="post">';
				print '<input type="hidden" name="facid" value="'.$fac->id.'">';
				print '<input type="hidden" name="action" value="addligne">';

				$var=true;
				print '<tr '.$bc[$var].'>';
				print '<td>';
				// éditeur wysiwyg
				if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
				{
					require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
					$doleditor=new DolEditor('dp_desc','',100,'dolibarr_details');
					$doleditor->Create();
				}
				else
				{
					print '<textarea class="flat" cols="70" name="dp_desc" rows="'.ROWS_2.'"></textarea>';
				}
				print '</td>';
				print '<td align="right">';

				$html->select_tva('tva_tx',$conf->defaulttx,$mysoc,$soc);
				print '</td>';
				print '<td align="right"><input type="text" name="pu" size="6"></td>';
				print '<td align="right"><input type="text" name="qty" value="'.($fac->type==2?'-1':'1').'" size="2"></td>';
				print '<td align="right" nowrap><input type="text" name="remise_percent" size="1" value="'.$soc->remise_client.'">%</td>';
				print '<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
				print '</tr>';
				if ($conf->service->enabled)
				{
					print '<tr '.$bc[$var].'>';
					print '<td colspan="9">'.$langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
					print $html->select_date('','date_start',$usehm,$usehm,1,"addligne");
					print ' '.$langs->trans('to').' ';
					print $html->select_date('','date_end',$usehm,$usehm,1,"addligne");
					print '</td>';
					print '</tr>';
				}
				print '</form>';

				// Ajout de produits/services prédéfinis
				if ($conf->produit->enabled)
				{
					print '<tr class="liste_titre">';
					print '<td colspan="3">';
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
					print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
					print '<td colspan="4">&nbsp;</td>';
					print '</tr>';

					print '<form id="addpredefinedproduct" action="'.$_SERVER['PHP_SELF'].'#add" method="post">';
					print '<input type="hidden" name="facid" value="'.$fac->id.'">';
					print '<input type="hidden" name="action" value="addligne_predef">';

					$var=! $var;
					print '<tr '.$bc[$var].'>';
					print '<td colspan="3">';
					// multiprix
					if($conf->global->PRODUIT_MULTIPRICES == 1)
					{
						$html->select_produits('','idprod','',$conf->produit->limit_size,$soc->price_level);
					}
					else
					{
						$html->select_produits('','idprod','',$conf->produit->limit_size);
					}

					if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) print '<br>';

					// éditeur wysiwyg
					if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
					{
						require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
						$doleditor=new DolEditor('np_desc','',100,'dolibarr_details');
						$doleditor->Create();
					}
					else
					{
						print '<textarea cols="70" name="np_desc" rows="'.ROWS_2.'" class="flat"></textarea>';
					}

					print '</td>';
					print '<td align="right"><input type="text" name="qty" value="'.($fac->type==2?'-1':'1').'" size="2"></td>';
					print '<td align="right" nowrap><input type="text" name="remise_percent" size="1" value="'.$soc->remise_client.'">%</td>';
					print '<td align="center" valign="middle" colspan="5"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
					print '</tr>';
					if ($conf->service->enabled)
					{
						print '<tr '.$bc[$var].'>';
						print '<td colspan="9">'.$langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
						print $html->select_date('','date_start_predef',$usehm,$usehm,1,"addligne_predef");
						print ' '.$langs->trans('to').' ';
						print $html->select_date('','date_end_predef',$usehm,$usehm,1,"addligne_predef");
						print '</td>';
						print '</tr>';
					}
					print '</form>';
				}

			}
			print "</table>\n";

			print "</div>\n";


			/*
			 * Boutons actions
			 */
			if ($_GET['action'] != 'prerelance' && $_GET['action'] != 'presend')
			{
				if ($user->societe_id == 0 && $_GET['action'] <> 'valid' && $_GET['action'] <> 'editline')
				{
					print '<div class="tabsAction">';

					// Editer une facture déjà validée, sans paiement effectué et pas exporté en compta
					if ($fac->statut == 1)
					{
						// On vérifie si les lignes de factures ont été exportées en compta et/ou ventilées
						$ventilExportCompta = $fac->getVentilExportCompta();

						if ($conf->global->FACTURE_ENABLE_EDITDELETE && $user->rights->facture->modifier
						&& ($resteapayer == $fac->total_ttc	&& $fac->paye == 0 && $ventilExportCompta == 0))
						{
							if (! $facidnext)
							{
								print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
							}
							else
							{
								print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('Modify').'</span>';
							}
						}
					}

					// Reopen a classified invoice
					if ($fac->statut == 3 &&				// A abandonned invoice
					$fac->getIdReplacingInvoice() == 0 &&	// Not replaced by another invoice
					($fac->close_code == 'badcustomer' || $fac->close_code == 'abandon'))
					{
						if (! $facidnext)
						{
							print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=reopen">'.$langs->trans('ReOpen').'</a>';
						}
						else
						{
							print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('ReOpen').'</span>';
						}
					}

					// Valider
					if ($fac->statut == 0 && $num_lignes > 0 && (($fac->type < 2 && $fac->total_ttc >= 0) || ($fac->type == 2 && $fac->total_ttc <= 0)))
					{
						if ($user->rights->facture->valider)
						{
							print '<a class="butAction" ';
							if ($conf->use_javascript_ajax && $conf->global->MAIN_CONFIRM_AJAX)
							{
								// We check if number is temporary number
								if (eregi('^\(PROV',$fac->ref)) $num = $fac->getNextNumRef($soc);
								else $num = $fac->ref;
								$url = $_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=confirm_valid&confirm=yes';
								print 'href="#" onClick="dialogConfirm(\''.$url.'\',\''.dol_escape_js($langs->trans('ConfirmValidateBill',$num)).'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'validate\')"';
							}
							else
							{
								print 'href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=valid"';
							}
							print '>'.$langs->trans('Validate').'</a>';
						}
					}

					// Envoyer
					if (($fac->statut == 1 || $fac->statut == 2) && $user->rights->facture->envoyer)
					{
						if ($facidnext)
						{
							print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('SendByMail').'</span>';
						}
						else
						{
							print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
						}
					}

					// Envoyer une relance
					if (($fac->statut == 1 || $fac->statut == 2) && $resteapayer > 0 && $user->rights->facture->envoyer)
					{
						if ($facidnext)
						{
							print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('SendRemindByMail').'</span>';
						}
						else
						{
							print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=prerelance&amp;mode=init">'.$langs->trans('SendRemindByMail').'</a>';
						}
					}

					// Emettre paiement
					if ($fac->type != 2 && $fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->paiement)
					{
						if ($facidnext)
						{
							print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('DoPayment').'</span>';
						}
						else
						{
							if ($resteapayer == 0)
							{
								print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseRemainderToPayIsZero").'">'.$langs->trans('DoPayment').'</span>';
							}
							else
							{
								print '<a class="butAction" href="paiement.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans('DoPayment').'</a>';
							}
						}
					}

					// Emettre remboursement ou Convertir en reduc
					if ($fac->type == 2)
					{
						if ($fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->paiement)
						{
							print '<a class="butAction" href="paiement.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans('DoPaymentBack').'</a>';
						}

						if ($fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->creer && $fac->getSommePaiement() == 0)
						{
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=converttoreduc">'.$langs->trans('ConvertToReduc').'</a>';
						}
					}

					// Classer 'payé'
					if ($fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->paiement &&
					(($fac->type != 2 && $resteapayer <= 0) || ($fac->type == 2 && $resteapayer >= 0)) )
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=payed">'.$langs->trans('ClassifyPayed').'</a>';
					}

					// Classer 'fermée' (possible si validée et pas encore classée payée)
					if ($fac->statut == 1 && $fac->paye == 0 && $resteapayer > 0
					&& $user->rights->facture->paiement)
					{
						if ($totalpaye > 0 || $totalavoir > 0)
						{
							// If one payment or one credit note was linked to this invoice
							print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=payed">'.$langs->trans('ClassifyPayedPartially').'</a>';
						}
						else
						{
							if ($facidnext)
							{
								print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('ClassifyCanceled').'</span>';
							}
							else
							{
								print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=canceled">'.$langs->trans('ClassifyCanceled').'</a>';
							}
						}
					}

					// Clone
					if ($fac->type == 0 && $user->rights->facture->creer)
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=clone&amp;object=invoice">'.$langs->trans("ToClone").'</a>';
					}
						
					// Clone as predefined
					if ($conf->global->FACTURE_ENABLE_RECUR && $fac->type == 0 && $fac->statut == 0 && $user->rights->facture->creer)
					{
						if (! $facidnext)
						{
							print '<a class="butAction" href="facture/fiche-rec.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans("ChangeIntoRepeatableInvoice").'</a>';
						}
					}

					// Supprimer
					if ($fac->is_erasable() && $user->rights->facture->supprimer && $_GET['action'] != 'delete')
					{
						if ($facidnext)
						{
							print '<a class="butActionRefused" href="#" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('Delete').'</a>';
						}
						else
						{
							print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
						}
					}

					print '</div>';
				}
			}


			if ($_GET['action'] != 'prerelance' && $_GET['action'] != 'presend')
			{
				print '<table width="100%"><tr><td width="50%" valign="top">';
				print '<a name="builddoc"></a>'; // ancre

				/*
				 * Documents générés
				 */
				$filename=sanitizeFileName($fac->ref);
				$filedir=$conf->facture->dir_output . '/' . sanitizeFileName($fac->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?facid='.$fac->id;
				$genallowed=$user->rights->facture->creer;
				$delallowed=$user->rights->facture->supprimer;

				$var=true;

				print '<br>';
				$somethingshown=$formfile->show_documents('facture',$filename,$filedir,$urlsource,$genallowed,$delallowed,$fac->modelpdf);

				/*
				 *   Propales rattachées
				 */
				$sql = 'SELECT '.$db->pdate('p.datep').' as dp, p.total_ht, p.ref, p.ref_client, p.rowid as propalid';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'propal as p';
				$sql .= ", ".MAIN_DB_PREFIX."fa_pr as fp";
				$sql .= " WHERE fp.fk_propal = p.rowid AND fp.fk_facture = ".$fac->id;

				dolibarr_syslog("facture.php: sql=".$sql);
				$resql = $db->query($sql);
				if ($resql)
				{
					$num = $db->num_rows($resql);
					if ($num)
					{
						$i = 0; $total = 0;
						if ($somethingshown) print '<br>';
						$somethingshown=1;
						print_titre($langs->trans('RelatedCommercialProposals'));
						print '<table class="noborder" width="100%">';
						print '<tr class="liste_titre">';
						print '<td width="150">'.$langs->trans('Ref').'</td>';
						print '<td>'.$langs->trans('RefCustomer').'</td>';
						print '<td align="center">'.$langs->trans('Date').'</td>';
						print '<td align="right">'.$langs->trans('AmountHT').'</td>';
						print '</tr>';

						$var=True;
						while ($i < $num)
						{
							$objp = $db->fetch_object($resql);
							$var=!$var;
							print '<tr '.$bc[$var].'>';
							print '<td><a href="propal.php?propalid='.$objp->propalid.'">'.img_object($langs->trans('ShowPropal'),'propal').' '.$objp->ref.'</a></td>';
							print '<td>'.$objp->ref_client.'</td>';
							print '<td align="center">'.dolibarr_print_date($objp->dp,'day').'</td>';
							print '<td align="right">'.price($objp->total_ht).'</td>';
							print '</tr>';
							$total = $total + $objp->total_ht;
							$i++;
						}
						print '<tr class="liste_total">';
						print '<td align="left">'.$langs->trans('TotalHT').'</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td align="right">'.price($total).'</td></tr>';
						print '</table>';
					}
				}
				else
				{
					dolibarr_print_error($db);
				}

				/*
				 * Commandes rattachées
				 */
				if($conf->commande->enabled)
				{
					$sql = 'SELECT '.$db->pdate('c.date_commande').' as date_commande, c.total_ht, c.ref, c.ref_client, c.rowid as id';
					$sql .= ' FROM '.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'co_fa as co_fa WHERE co_fa.fk_commande = c.rowid AND co_fa.fk_facture = '.$fac->id;
					$resql = $db->query($sql);
					if ($resql)
					{
						$num = $db->num_rows($resql);
						if ($num)
						{
							$langs->load("orders");

							$i = 0; $total = 0;
							if ($somethingshown) print '<br>';
							$somethingshown=1;
							print_titre($langs->trans('RelatedOrders'));
							print '<table class="noborder" width="100%">';
							print '<tr class="liste_titre">';
							print '<td width="150">'.$langs->trans('Ref').'</td>';
							print '<td>'.$langs->trans('RefCustomerOrderShort').'</td>';
							print '<td align="center">'.$langs->trans('Date').'</td>';
							print '<td align="right">'.$langs->trans('AmountHT').'</td>';
							print '</tr>';
							$var=true;
							while ($i < $num)
							{
								$objp = $db->fetch_object($resql);
								$var=!$var;
								print '<tr '.$bc[$var].'><td>';
								print '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$objp->id.'">'.img_object($langs->trans('ShowOrder'), 'order').' '.$objp->ref."</a></td>\n";
								print '<td>'.$objp->ref_client.'</td>';
								print '<td align="center">'.dolibarr_print_date($objp->date_commande,'day').'</td>';
								print '<td align="right">'.price($objp->total_ht).'</td>';
								print "</tr>\n";
								$total = $total + $objp->total_ht;
								$i++;
							}
							print '<tr class="liste_total">';
							print '<td align="left">'.$langs->trans('TotalHT').'</td>';
							print '<td>&nbsp;</td>';
							print '<td>&nbsp;</td>';
							print '<td align="right">'.price($total).'</td></tr>';
							print '</table>';
						}
					}
					else
					{
						dolibarr_print_error($db);
					}
				}

				print '</td><td valign="top" width="50%">';

				print '<br>';

				// List of actions on element
				include_once(DOL_DOCUMENT_ROOT.'/html.formactions.class.php');
				$formactions=new FormActions($db);
				$somethingshown=$formactions->showactions($fac,'invoice',$socid);

				print '</td></tr></table>';
			}

			/*
			 * Affiche formulaire mail
			 */
			if ($_GET['action'] == 'presend')
			{
				$ref = sanitizeFileName($fac->ref);
				$file = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';

				// Construit PDF si non existant
				if (! is_readable($file))
				{
					$outputlangs = $langs;
					if (! empty($_REQUEST['lang_id']))
					{
						$outputlangs = new Translate("",$conf);
						$outputlangs->setDefaultLang($_REQUEST['lang_id']);
					}
					$result=facture_pdf_create($db, $fac->id, '', $_REQUEST['model'], $outputlangs);
					if ($result <= 0)
					{
						dolibarr_print_error($db,$result);
						exit;
					}
				}

				print '<br>';
				print_titre($langs->trans('SendBillByMail'));

				$liste[0]='&nbsp;';
				foreach ($soc->thirdparty_and_contact_email_array() as $key=>$value)
				{
					$liste[$key]=$value;
				}

				// Créé l'objet formulaire mail
				include_once(DOL_DOCUMENT_ROOT.'/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->fromtype = 'user';
				$formmail->fromid   = $user->id;
				$formmail->fromname = $user->fullname;
				$formmail->frommail = $user->email;
				$formmail->withfrom=1;
				$formmail->withto=$liste;
				$formmail->withtocc=1;
				$formmail->withtoccc=$conf->global->FACTURE_EMAIL_USECCC;
				$formmail->withtopic=$langs->transnoentities('SendBillRef','__FACREF__');
				$formmail->withfile=2;
				$formmail->withbody=1;
				$formmail->withdeliveryreceipt=1;
				$formmail->withcancel=1;
				// Tableau des substitutions
				$formmail->substit['__FACREF__']=$fac->ref;
				// Tableau des paramètres complémentaires du post
				$formmail->param['action']='send';
				$formmail->param['models']='facture_send';
				$formmail->param['facid']=$fac->id;
				$formmail->param['returnurl']=DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id;

				// Init list of files
				if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
				{
					$formmail->clear_attached_files();
					$formmail->add_attached_files($file,$ref.'.pdf','application/pdf');
				}

				$formmail->show_form();

				print '<br>';
			}

			if ($_GET['action'] == 'prerelance')
			{
				$ref = sanitizeFileName($fac->ref);
				$file = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';

				// Construit PDF si non existant
				if (! is_readable($file))
				{
					$outputlangs = $langs;
					if (! empty($_REQUEST['lang_id']))
					{
						$outputlangs = new Translate("",$conf);
						$outputlangs->setDefaultLang($_REQUEST['lang_id']);
					}
					$result=facture_pdf_create($db, $fac->id, '', $_REQUEST['model'], $outputlangs);
					if ($result <= 0)
					{
						dolibarr_print_error($db,$result);
						exit;
					}
				}

				print '<br>';
				print_titre($langs->trans('SendReminderBillByMail'));

				$liste[0]='&nbsp;';
				foreach ($soc->thirdparty_and_contact_email_array() as $key=>$value)
				{
					$liste[$key]=$value;
				}

				// Créé l'objet formulaire mail
				include_once(DOL_DOCUMENT_ROOT.'/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->fromname = $user->fullname;
				$formmail->frommail = $user->email;
				$formmail->withfrom=1;
				$formmail->withto=$liste;
				$formmail->withtocc=1;
				$formmail->withtopic=$langs->transnoentities('SendReminderBillRef','__FACREF__');
				$formmail->withfile=2;
				$formmail->withbody=1;
				$formmail->withdeliveryreceipt=1;
				$formmail->withcancel=1;
				// Tableau des substitutions
				$formmail->substit['__FACREF__']=$fac->ref;
				// Tableau des paramètres complémentaires
				$formmail->param['action']='relance';
				$formmail->param['models']='facture_relance';
				$formmail->param['facid']=$fac->id;
				$formmail->param['returnurl']=DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id;

				// Init list of files
				if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
				{
					$formmail->clear_attached_files();
					$formmail->add_attached_files($file,$ref.'.pdf','application/pdf');
				}

				$formmail->show_form();

				print '<br>';
			}
		}
		else
		{
			dolibarr_print_error($db,$fac->error);
		}
	}
	else
	{
		/***************************************************************************
			*                                                                         *
			*                      Mode Liste                                         *
			*                                                                         *
			***************************************************************************/
		$page     =$_GET['page'];
		$sortorder=$_GET['sortorder'];
		$sortfield=$_GET['sortfield'];
		$month    =$_GET['month'];
		$year     =$_GET['year'];
		$limit = $conf->liste_limit;
		$offset = $limit * $page ;

		if (! $sortorder) $sortorder='DESC';
		if (! $sortfield) $sortfield='f.datef';

		$facturestatic=new Facture($db);

		if ($page == -1) $page = 0 ;

		$sql = 'SELECT ';
		$sql.= ' f.rowid as facid, f.facnumber, f.type, f.increment, f.total, f.total_ttc,';
		$sql.= ' '.$db->pdate('f.datef').' as df, '.$db->pdate('f.date_lim_reglement').' as datelimite,';
		$sql.= ' f.paye as paye, f.fk_statut,';
		$sql.= ' s.nom, s.rowid as socid';
		if (! $sall) $sql.= ' ,sum(pf.amount) as am';
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= ', '.MAIN_DB_PREFIX.'facture as f';
		if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
		if ($sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid';
		$sql.= ' WHERE f.fk_soc = s.rowid';
		if (!$user->rights->societe->client->voir && !$socid) //restriction
		{
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		}
		if ($socid) $sql .= ' AND s.rowid = '.$socid;
		if ($_GET['filtre'])
		{
			$filtrearr = split(',', $_GET['filtre']);
			foreach ($filtrearr as $fil)
			{
				$filt = split(':', $fil);
				$sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
			}
		}
		if ($_GET['search_ref'])
		{
			$sql .= ' AND f.facnumber like \'%'.addslashes(trim($_GET['search_ref'])).'%\'';
		}
		if ($_GET['search_societe'])
		{
			$sql .= ' AND s.nom like \'%'.addslashes(trim($_GET['search_societe'])).'%\'';
		}
		if ($_GET['search_montant_ht'])
		{
			$sql .= ' AND f.total = \''.addslashes(trim($_GET['search_montant_ht'])).'\'';
		}
		if ($_GET['search_montant_ttc'])
		{
			$sql .= ' AND f.total_ttc = \''.addslashes(trim($_GET['search_montant_ttc'])).'\'';
		}
		if ($month > 0)
		{
			if ($year > 0)
			$sql .= " AND date_format(f.datef, '%Y-%m') = '$year-$month'";
			else
			$sql .= " AND date_format(f.datef, '%m') = '$month'";
		}
		if ($year > 0)
		{
			$sql .= ' AND date_format(f.datef, \'%Y\') = '.$year;
		}
		if ($_POST['sf_ref'])
		{
			$sql .= ' AND f.facnumber like \'%'.addslashes(trim($_POST['sf_ref'])) . '%\'';
		}
		if ($sall)
		{
			$sql .= ' AND (s.nom like \'%'.addslashes($sall).'%\' OR f.facnumber like \'%'.addslashes($sall).'%\' OR f.note like \'%'.addslashes($sall).'%\' OR fd.description like \'%'.addslashes($sall).'%\')';
		}

		$sql .= ' GROUP BY f.rowid';

		$sql .= ' ORDER BY ';
		$listfield=split(',',$sortfield);
		foreach ($listfield as $key => $value)
		$sql.= $listfield[$key].' '.$sortorder.',';
		$sql .= ' f.rowid DESC ';

		$sql .= $db->plimit($limit+1,$offset);

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);

			if ($socid)
			{
				$soc = new Societe($db);
				$soc->fetch($socid);
			}

			print_barre_liste($langs->trans('BillsCustomers').' '.($socid?' '.$soc->nom:''),$page,'facture.php','&amp;socid='.$socid,$sortfield,$sortorder,'',$num);

			$i = 0;
			print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
			print '<table class="liste" width="100%">';
			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'f.facnumber','','&amp;socid='.$socid.'&amp;month='.$month.'&amp;year=' . $year,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'f.datef','','&amp;socid='.$socid.'&amp;month='.$month.'&amp;year=' . $year,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('Company'),$_SERVER['PHP_SELF'],'s.nom','','&amp;socid='.$socid.'&amp;month='.$month.'&amp;year=' . $year,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'f.total','','&amp;socid='.$socid.'&amp;month='.$month.'&amp;year=' . $year,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'f.total_ttc','','&amp;socid='.$socid.'&amp;month='.$month.'&amp;year=' . $year,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'am','','&amp;socid='.$socid.'&amp;month='.$month.'&amp;year=' . $year,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye,am','','&amp;socid='.$socid.'&amp;month='.$month.'&amp;year=' . $year,'align="right"',$sortfield,$sortorder);
			//print '<td class="liste_titre">&nbsp;</td>';
			print '</tr>';

			// Lignes des champs de filtre

			print '<tr class="liste_titre">';
			print '<td class="liste_titre" align="left">';
			print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET['search_ref'].'">';
			print '<td class="liste_titre" colspan="1" align="center">';
			print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
			//print '&nbsp;'.$langs->trans('Year').': ';
			$max_year = date("Y");
			$syear = $year;
			//if ($syear == '') $syear = date("Y");
			$html->select_year($syear,'year',1, '', $max_year);
			print '</td>';
			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_societe" value="'.$_GET['search_societe'].'">';
			print '</td><td class="liste_titre" align="right">';
			print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET['search_montant_ht'].'">';
			print '</td><td class="liste_titre" align="right">';
			print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$_GET['search_montant_ttc'].'">';
			print '</td>';
			print '<td align="right">';
			print '&nbsp;';
			print '</td>';
			print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans('Search').'">';
			print "</td></tr>\n";

			if ($num > 0)
			{
				$var=True;
				$total=0;
				$totalrecu=0;

				while ($i < min($num,$limit))
				{
					$objp = $db->fetch_object($resql);
					$var=!$var;

					print '<tr '.$bc[$var].'>';
					print '<td nowrap="nowrap">';

					$facturestatic->id=$objp->facid;
					$facturestatic->ref=$objp->facnumber;
					$facturestatic->type=$objp->type;

					print '<table class="nobordernopadding"><tr class="nocellnopadd">';
					print '<td width="90" class="nobordernopadding" nowrap="nowrap">';
					print $facturestatic->getNomUrl(1);
					print $objp->increment;
					print '</td>';
					if ($objp->datelimite < (time() - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1 && ! $objp->am)
					{
						print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
						print img_warning($langs->trans('Late'));
						print '</td>';
					}
					print '<td width="16" align="right" class="nobordernopadding">';
					$filename=sanitizeFileName($objp->facnumber);
					$filedir=$conf->facture->dir_output . '/' . sanitizeFileName($objp->facnumber);
					$urlsource=$_SERVER['PHP_SELF'].'?facid='.$objp->facid;
					$formfile->show_documents('facture',$filename,$filedir,$urlsource,'','','','','',1);
					print '</td>';
					print '</tr></table>';

					print "</td>\n";

					if ($objp->df > 0)
					{
						$y = dolibarr_print_date($objp->df,'%Y');
						$m = dolibarr_print_date($objp->df,'%m');
						$mt = dolibarr_print_date($objp->df,'%b');
						$d = dolibarr_print_date($objp->df,'%d');
						print '<td align="center" nowrap>';
						print $d;
						print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'&amp;month='.$m.'">';
						print $mt.'</a>';
						print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'">';
						print $y.'</a></td>';
					}
					else
					{
						print '<td align="center"><b>!!!</b></td>';
					}
					print '<td><a href="fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans('ShowCompany'),'company').' '.dolibarr_trunc($objp->nom,48).'</a></td>';
					print '<td align="right">'.price($objp->total).'</td>';
					print '<td align="right">'.price($objp->total_ttc).'</td>';
					print '<td align="right">'.price($objp->am).'</td>';

					// Affiche statut de la facture
					print '<td align="right" nowrap="nowrap">';
					print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$objp->am,$objp->type);
					print "</td>";
					//print "<td>&nbsp;</td>";
					print "</tr>\n";
					$total+=$objp->total;
					$total_ttc+=$objp->total_ttc;
					$totalrecu+=$objp->am;
					$i++;
				}

				if (($offset + $num) <= $limit)
				{
					// Print total
					print '<tr class="liste_total">';
					print '<td class="liste_total" colspan="3" align="left">'.$langs->trans('Total').'</td>';
					print '<td class="liste_total" align="right">'.price($total).'</td>';
					print '<td class="liste_total" align="right">'.price($total_ttc).'</td>';
					print '<td class="liste_total" align="right">'.price($totalrecu).'</td>';
					print '<td class="liste_total" align="center">&nbsp;</td>';
					print '</tr>';
				}
			}

			print "</table>\n";
			print "</form>\n";
			$db->free($resql);
		}
		else
		{
			dolibarr_print_error($db);
		}
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
