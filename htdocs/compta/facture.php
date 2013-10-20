<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2013 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2013 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012-2013 Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Jean-Francois FERRY   <jfefe@aternatik.fr>
 * Copyright (C) 2013      Florian Henry         <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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
 *	\file       htdocs/compta/facture.php
 *	\ingroup    facture
 *	\brief      Page to create/see an invoice
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
if (! empty($conf->projet->enabled))   {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('banks');
$langs->load('main');
if (! empty($conf->margin->enabled)) $langs->load('margins');

$sall=trim(GETPOST('sall'));
$projectid=(GETPOST('projectid')?GETPOST('projectid','int'):0);

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$lineid=GETPOST('lineid','int');
$userid=GETPOST('userid','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_montant_ttc=GETPOST('search_montant_ttc','alpha');
$origin=GETPOST('origin','alpha');
$originid=(GETPOST('originid','int')?GETPOST('originid','int'):GETPOST('origin_id','int')); // For backward compatibility

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Security check
$fieldid = (! empty($ref)?'facnumber':'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id,'','','fk_soc',$fieldid);

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;

$usehm=(! empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE:0);

$object=new Facture($db);
$extrafields = new ExtraFields($db);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret=$object->fetch($id, $ref);
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('invoicecard'));


/*
 * Actions
 */

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks


// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->facture->creer)
{
	if (1==0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
	{
		$mesgs[]='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	}
	else
	{
		if ($object->fetch($id) > 0)
		{
			$result=$object->createFromClone($socid);
			if ($result > 0)
			{
				header("Location: ".$_SERVER['PHP_SELF'].'?facid='.$result);
				exit;
			}
			else
			{
				$mesgs[]=$object->error;
				$action='';
			}
		}
	}
}

// Change status of invoice
else if ($action == 'reopen' && $user->rights->facture->creer)
{
	$result = $object->fetch($id);
	if ($object->statut == 2
		|| ($object->statut == 3 && $object->close_code != 'replaced'))
	{
		$result = $object->set_unpaid($user);
		if ($result > 0)
		{
			header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
			exit;
		}
		else
		{
			$mesgs[]='<div class="error">'.$object->error.'</div>';
		}
	}
}

// Delete invoice
else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->facture->supprimer)
{
	$result = $object->fetch($id);
	$object->fetch_thirdparty();

	$idwarehouse=GETPOST('idwarehouse');

	$qualified_for_stock_change=0;
	if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
	{
		$qualified_for_stock_change=$object->hasProductsOrServices(2);
	}
	else
	{
		$qualified_for_stock_change=$object->hasProductsOrServices(1);
	}

	$result = $object->delete(0,0,$idwarehouse);
	if ($result > 0)
	{
		header('Location: '.DOL_URL_ROOT.'/compta/facture/list.php');
		exit;
	}
	else
	{
		$mesgs[]='<div class="error">'.$object->error.'</div>';
	}
}

// Delete line
else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$object->fetch_thirdparty();

	$result = $object->deleteline($_GET['lineid'], $user);
	if ($result > 0)
	{
		// Define output language
		$outputlangs = $langs;
		$newlang='';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
		if (! empty($newlang))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
		{
			$ret=$object->fetch($id);    // Reload to get new records
			$result=facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
		if ($result >= 0)
		{
			header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
			exit;
		}
	}
	else
	{
		$mesgs[]='<div clas="error">'.$object->error.'</div>';
		$action='';
	}
}

// Delete link of credit note to invoice
else if ($action == 'unlinkdiscount' && $user->rights->facture->creer)
{
	$discount=new DiscountAbsolute($db);
	$result=$discount->fetch($_GET["discountid"]);
	$discount->unlink_invoice();
}

// Validation
else if ($action == 'valid' && $user->rights->facture->creer)
{
	$object->fetch($id);

	// On verifie signe facture
	if ($object->type == 2)
	{
		// Si avoir, le signe doit etre negatif
		if ($object->total_ht >= 0)
		{
			$mesgs[]='<div class="error">'.$langs->trans("ErrorInvoiceAvoirMustBeNegative").'</div>';
			$action='';
		}
	}
	else
	{
		// Si non avoir, le signe doit etre positif
		if (empty($conf->global->FACTURE_ENABLE_NEGATIVE) && $object->total_ht < 0)
		{
			$mesgs[]='<div class="error">'.$langs->trans("ErrorInvoiceOfThisTypeMustBePositive").'</div>';
			$action='';
		}
	}
}

else if ($action == 'set_thirdparty' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$object->setValueFrom('fk_soc',$socid);

	header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
	exit;
}

else if ($action == 'classin' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$object->setProject($_POST['projectid']);
}

else if ($action == 'setmode' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$result = $object->setPaymentMethods(GETPOST('mode_reglement_id','int'));
	if ($result < 0) dol_print_error($db,$object->error);
}
else if ($action == 'setinvoicedate' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$old_date_lim_reglement=$object->date_lim_reglement;
	$object->date=dol_mktime(12,0,0,$_POST['invoicedatemonth'],$_POST['invoicedateday'],$_POST['invoicedateyear']);
	$new_date_lim_reglement=$object->calculate_date_lim_reglement();
	if ($new_date_lim_reglement > $old_date_lim_reglement) $object->date_lim_reglement=$new_date_lim_reglement;
	if ($object->date_lim_reglement < $object->date) $object->date_lim_reglement=$object->date;
	$result=$object->update($user);
	if ($result < 0) dol_print_error($db,$object->error);
}
else if ($action == 'setconditions' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$object->cond_reglement_code=0;	// To clean property
	$object->cond_reglement_id=0;		// To clean property
	$result=$object->setPaymentTerms(GETPOST('cond_reglement_id','int'));
	if ($result < 0) dol_print_error($db,$object->error);

	$old_date_lim_reglement=$object->date_lim_reglement;
	$new_date_lim_reglement=$object->calculate_date_lim_reglement();
	if ($new_date_lim_reglement > $old_date_lim_reglement) $object->date_lim_reglement=$new_date_lim_reglement;
	if ($object->date_lim_reglement < $object->date) $object->date_lim_reglement=$object->date;
	$result=$object->update($user);
	if ($result < 0) dol_print_error($db,$object->error);
}
else if ($action == 'setpaymentterm' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$object->date_lim_reglement=dol_mktime(12,0,0,$_POST['paymenttermmonth'],$_POST['paymenttermday'],$_POST['paymenttermyear']);
	if ($object->date_lim_reglement < $object->date)
	{
		$object->date_lim_reglement=$object->calculate_date_lim_reglement();
		setEventMessage($langs->trans("DatePaymentTermCantBeLowerThanObjectDate"),'warnings');
	}
	$result=$object->update($user);
	if ($result < 0) dol_print_error($db,$object->error);
}
else if ($action == 'setrevenuestamp' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$object->revenuestamp=GETPOST('revenuestamp');
	$result=$object->update($user);
	$object->update_price(1);
	if ($result < 0) dol_print_error($db,$object->error);
}
else if ($action == 'setremisepercent' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$result = $object->set_remise($user, $_POST['remise_percent']);
}
else if ($action == "setabsolutediscount" && $user->rights->facture->creer)
{
	// POST[remise_id] ou POST[remise_id_for_payment]
	if (! empty($_POST["remise_id"]))
	{
		$ret=$object->fetch($id);
		if ($ret > 0)
		{
			$result=$object->insert_discount($_POST["remise_id"]);
			if ($result < 0)
			{
				$mesgs[]='<div class="error">'.$object->error.'</div>';
			}
		}
		else
		{
			dol_print_error($db,$object->error);
		}
	}
	if (! empty($_POST["remise_id_for_payment"]))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
		$discount = new DiscountAbsolute($db);
		$discount->fetch($_POST["remise_id_for_payment"]);

		$result=$discount->link_to_invoice(0,$id);
		if ($result < 0)
		{
			$mesgs[]='<div class="error">'.$discount->error.'</div>';
		}
	}
}

else if ($action == 'set_ref_client' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$object->set_ref_client($_POST['ref_client']);
}

else if ($action == 'setnote_public' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES),'_public');
	if ($result < 0) dol_print_error($db,$object->error);
}

else if ($action == 'setnote_private' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES),'_private');
	if ($result < 0) dol_print_error($db,$object->error);
}

// Classify to validated
else if ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->facture->valider)
{
	$idwarehouse=GETPOST('idwarehouse');

	$object->fetch($id);
	$object->fetch_thirdparty();

	// Check parameters

	// Check for  mandatory prof id
	for ($i = 1; $i < 5; $i++)
	{

		$idprof_mandatory ='SOCIETE_IDPROF'.($i).'_INVOICE_MANDATORY';
		$idprof='idprof'.$i;
		if (! $object->thirdparty->$idprof && ! empty($conf->global->$idprof_mandatory))
		{
			if (! $error) $langs->load("errors");
			$error++;

			setEventMessage($langs->trans('ErrorProdIdIsMandatory',$langs->transcountry('ProfId'.$i, $object->thirdparty->country_code)),'errors');
		}
	}

	$qualified_for_stock_change=0;
	if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
	{
		$qualified_for_stock_change=$object->hasProductsOrServices(2);
	}
	else
	{
		$qualified_for_stock_change=$object->hasProductsOrServices(1);
	}

	//Check for warehouse
	if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change)
	{
		if (! $idwarehouse || $idwarehouse == -1)
		{
			$error++;
			setEventMessage($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse")),'errors');
			$action='';
		}
	}

	if (! $error)
	{
		$result = $object->validate($user,'',$idwarehouse);
		if ($result >= 0)
		{
			// Define output language
			$outputlangs = $langs;
			$newlang='';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
				$ret=$object->fetch($id);    // Reload to get new records
				facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
		else
		{
			setEventMessage($object->error,'errors');
		}
	}
}

// Go back to draft status (unvalidate)
else if ($action == 'confirm_modif' && ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->valider) || $user->rights->facture->invoice_advance->unvalidate))
{
	$idwarehouse=GETPOST('idwarehouse');

	$object->fetch($id);
	$object->fetch_thirdparty();

	$qualified_for_stock_change=0;
	if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
	{
		$qualified_for_stock_change=$object->hasProductsOrServices(2);
	}
	else
	{
		$qualified_for_stock_change=$object->hasProductsOrServices(1);
	}

	// Check parameters
	if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change)
	{
		if (! $idwarehouse || $idwarehouse == -1)
		{
			$error++;
			setEventMessage($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse")),'errors');
			$action='';
		}
	}

	if (! $error)
	{
		// On verifie si la facture a des paiements
		$sql = 'SELECT pf.amount';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf';
		$sql.= ' WHERE pf.fk_facture = '.$object->id;

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
		else
		{
			dol_print_error($db,'');
		}

		$resteapayer = $object->total_ttc - $totalpaye;

		// On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
		$ventilExportCompta = $object->getVentilExportCompta();

		// On verifie si aucun paiement n'a ete effectue
		if ($resteapayer == $object->total_ttc	&& $object->paye == 0 && $ventilExportCompta == 0)
		{
			$object->set_draft($user, $idwarehouse);

			// Define output language
			$outputlangs = $langs;
			$newlang='';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
				$ret=$object->fetch($id);    // Reload to get new records
				facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
	}
}

// Classify "paid"
else if ($action == 'confirm_paid' && $confirm == 'yes' && $user->rights->facture->paiement)
{
	$object->fetch($id);
	$result = $object->set_paid($user);
}
// Classif  "paid partialy"
else if ($action == 'confirm_paid_partially' && $confirm == 'yes' && $user->rights->facture->paiement)
{
	$object->fetch($id);
	$close_code=$_POST["close_code"];
	$close_note=$_POST["close_note"];
	if ($close_code)
	{
		$result = $object->set_paid($user,$close_code,$close_note);
	}
	else
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Reason")),'errors');
	}
}
// Classify "abandoned"
else if ($action == 'confirm_canceled' && $confirm == 'yes')
{
	$object->fetch($id);
	$close_code=$_POST["close_code"];
	$close_note=$_POST["close_note"];
	if ($close_code)
	{
		$result = $object->set_canceled($user,$close_code,$close_note);
	}
	else
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Reason")),'errors');
	}
}

// Convertir en reduc
else if ($action == 'confirm_converttoreduc' && $confirm == 'yes' && $user->rights->facture->creer)
{
	$db->begin();

	$object->fetch($id);
	$object->fetch_thirdparty();
	$object->fetch_lines();

	if (! $object->paye)	// protection against multiple submit
	{
		// Boucle sur chaque taux de tva
		$i=0;
		foreach($object->lines as $line)
		{
			$amount_ht[$line->tva_tx]+=$line->total_ht;
			$amount_tva[$line->tva_tx]+=$line->total_tva;
			$amount_ttc[$line->tva_tx]+=$line->total_ttc;
			$i++;
		}

		// Insert one discount by VAT rate category
		$discount = new DiscountAbsolute($db);
		if ($object->type == 2)     $discount->description='(CREDIT_NOTE)';
		elseif ($object->type == 3) $discount->description='(DEPOSIT)';
		else {
			$this->error="CantConvertToReducAnInvoiceOfThisType";
			return -1;
		}
		$discount->tva_tx=abs($object->total_ttc);
		$discount->fk_soc=$object->socid;
		$discount->fk_facture_source=$object->id;

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
			$result=$object->set_paid($user);
			if ($result > 0)
			{
				//$mesgs[]='OK'.$discount->id;
				$db->commit();
			}
			else
			{
				$mesgs[]='<div class="error">'.$object->error.'</div>';
				$db->rollback();
			}
		}
		else
		{
			$mesgs[]='<div class="error">'.$discount->error.'</div>';
			$db->rollback();
		}
	}
}

/*
 * Insert new invoice in database
*/
else if ($action == 'add' && $user->rights->facture->creer)
{
	if ($socid>0)
		$object->socid=GETPOST('socid','int');

	$db->begin();

	$error=0;

	// Fill array 'array_options' with data from add form
	$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
	if($ret < 0)
		$error++;

	// Replacement invoice
	if ($_POST['type'] == 1)
	{
		$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture))
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")),'errors');
		}

		if (! ($_POST['fac_replacement'] > 0))
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("ReplaceInvoice")),'errors');
		}

		if (! $error)
		{
			// This is a replacement invoice
			$result=$object->fetch($_POST['fac_replacement']);
			$object->fetch_thirdparty();

			$object->date				= $datefacture;
			$object->note_public		= trim($_POST['note_public']);
			$object->note				= trim($_POST['note']);
			$object->ref_client			= $_POST['ref_client'];
			$object->ref_int			= $_POST['ref_int'];
			$object->modelpdf			= $_POST['model'];
			$object->fk_project			= $_POST['projectid'];
			$object->cond_reglement_id	= $_POST['cond_reglement_id'];
			$object->mode_reglement_id	= $_POST['mode_reglement_id'];
			$object->remise_absolue		= $_POST['remise_absolue'];
			$object->remise_percent		= $_POST['remise_percent'];

			// Proprietes particulieres a facture de remplacement
			$object->fk_facture_source	= $_POST['fac_replacement'];
			$object->type				= 1;

			$id=$object->createFromCurrent($user);
			if ($id <= 0) $mesgs[]=$object->error;
		}
	}

	// Credit note invoice
	if ($_POST['type'] == 2)
	{
		if (! $_POST['fac_avoir'] > 0)
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CorrectInvoice")),'errors');
		}

		$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture))
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->trans("Date")),'errors');
		}

		if (! $error)
		{
			// Si facture avoir
			$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

			//$result=$object->fetch($_POST['fac_avoir']);

			$object->socid				= GETPOST('socid','int');
			$object->number				= $_POST['facnumber'];
			$object->date				= $datefacture;
			$object->note_public		= trim($_POST['note_public']);
			$object->note				= trim($_POST['note']);
			$object->ref_client			= $_POST['ref_client'];
			$object->ref_int			= $_POST['ref_int'];
			$object->modelpdf			= $_POST['model'];
			$object->fk_project			= $_POST['projectid'];
			$object->cond_reglement_id	= 0;
			$object->mode_reglement_id	= $_POST['mode_reglement_id'];
			$object->remise_absolue		= $_POST['remise_absolue'];
			$object->remise_percent		= $_POST['remise_percent'];

			// Proprietes particulieres a facture avoir
			$object->fk_facture_source	= $_POST['fac_avoir'];
			$object->type				= 2;

			$id = $object->create($user);

			// Add predefined lines
			for ($i = 1; $i <= $NBLINES; $i++)
			{
				if ($_POST['idprod'.$i])
				{
					$product=new Product($db);
					$product->fetch($_POST['idprod'.$i]);
					$startday=dol_mktime(12, 0, 0, $_POST['date_start'.$i.'month'], $_POST['date_start'.$i.'day'], $_POST['date_start'.$i.'year']);
					$endday=dol_mktime(12, 0, 0, $_POST['date_end'.$i.'month'], $_POST['date_end'.$i.'day'], $_POST['date_end'.$i.'year']);
					$result=$object->addline($product->description,$product->price, $_POST['qty'.$i], $product->tva_tx, $product->localtax1_tx, $product->localtax2_tx, $_POST['idprod'.$i], $_POST['remise_percent'.$i], $startday, $endday, 0, 0, '', $product->price_base_type, $product->price_ttc, $product->type);
				}
			}
		}
	}

	// Standard invoice or Deposit invoice created from a Predefined invoice
	if (($_POST['type'] == 0 || $_POST['type'] == 3) && $_POST['fac_rec'] > 0)
	{
		$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture))
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")),'errors');
		}

		if (! $error)
		{
			$object->socid			= GETPOST('socid','int');
			$object->type           = $_POST['type'];
			$object->number         = $_POST['facnumber'];
			$object->date           = $datefacture;
			$object->note_public	= trim($_POST['note_public']);
			$object->note_private   = trim($_POST['note_private']);
			$object->ref_client     = $_POST['ref_client'];
			$object->ref_int     	= $_POST['ref_int'];
			$object->modelpdf       = $_POST['model'];

			// Source facture
			$object->fac_rec        = $_POST['fac_rec'];

			$id = $object->create($user);
		}
	}



	// Standard or deposit or proforma invoice
	if (($_POST['type'] == 0 || $_POST['type'] == 3 || $_POST['type'] == 4) && $_POST['fac_rec'] <= 0)
	{
		if (GETPOST('socid','int')<1)
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Customer")),'errors');
		}

		$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture))
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")),'errors');
		}

		if (! $error)
		{
			// Si facture standard
			$object->socid				= GETPOST('socid','int');
			$object->type				= GETPOST('type');
			$object->number				= $_POST['facnumber'];
			$object->date				= $datefacture;
			$object->note_public		= trim($_POST['note_public']);
			$object->note_private		= trim($_POST['note_private']);
			$object->ref_client			= $_POST['ref_client'];
			$object->ref_int			= $_POST['ref_int'];
			$object->modelpdf			= $_POST['model'];
			$object->fk_project			= $_POST['projectid'];
			$object->cond_reglement_id	= ($_POST['type'] == 3?1:$_POST['cond_reglement_id']);
			$object->mode_reglement_id	= $_POST['mode_reglement_id'];
			$object->amount				= $_POST['amount'];
			$object->remise_absolue		= $_POST['remise_absolue'];
			$object->remise_percent		= $_POST['remise_percent'];
			$object->fetch_thirdparty();

			// If creation from another object of another module (Example: origin=propal, originid=1)
			if ($_POST['origin'] && $_POST['originid'])
			{
				// Parse element/subelement (ex: project_task)
				$element = $subelement = $_POST['origin'];
				if (preg_match('/^([^_]+)_([^_]+)/i',$_POST['origin'],$regs))
				{
					$element = $regs[1];
					$subelement = $regs[2];
				}

				// For compatibility
				if ($element == 'order')    {
					$element = $subelement = 'commande';
				}
				if ($element == 'propal')   {
					$element = 'comm/propal'; $subelement = 'propal';
				}
				if ($element == 'contract') {
					$element = $subelement = 'contrat';
				}
				if ($element == 'inter')    {
					$element = $subelement = 'ficheinter';
				}
				if ($element == 'shipping') {
					$element = $subelement = 'expedition';
				}

				$object->origin    = $_POST['origin'];
				$object->origin_id = $_POST['originid'];

				// Possibility to add external linked objects with hooks
				$object->linked_objects[$object->origin] = $object->origin_id;
				if (is_array($_POST['other_linked_objects']) && ! empty($_POST['other_linked_objects']))
				{
					$object->linked_objects = array_merge($object->linked_objects, $_POST['other_linked_objects']);
				}

				$id = $object->create($user);

				if ($id > 0)
				{
					// If deposit invoice
					if ($_POST['type'] == 3)
					{
						$typeamount=GETPOST('typedeposit','alpha');
						$valuedeposit=GETPOST('valuedeposit','int');

						if ($typeamount=='amount')
						{
							$amountdeposit=$valuedeposit;
						}
						else
						{
							$amountdeposit=0;

							dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

							$classname = ucfirst($subelement);
							$srcobject = new $classname($db);

							dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add deposit lines");
							$result=$srcobject->fetch($object->origin_id);
							if ($result > 0)
							{
								$totalamount=0;
								$lines = $srcobject->lines;
								$numlines=count($lines);
								for ($i=0; $i<$numlines; $i++)
								{
									$totalamount += $lines[$i]->subprice;
								}

								if ($totalamount!=0)
								{
									$amountdeposit=($totalamount*$valuedeposit)/100;
								}
							}
							else
							{
								$mesgs[]=$srcobject->error;
								$error++;
							}

						}

						$result = $object->addline(
							$langs->trans('Deposit'),
							$amountdeposit, //subprice
							1, //quantity
							$lines[$i]->tva_tx,
							0, //localtax1_tx
							0, //localtax2_tx
							0, //fk_product
							0, //remise_percent
							0, //date_start
							0, //date_end
							0,
							$lines[$i]->info_bits, //info_bits
							0, //info_bits
							'HT',
							0,
							0, //product_type
							1,
							$lines[$i]->special_code,
							$object->origin,
							0,
							0,
							0,
							0,
							$langs->trans('Deposit')
						);


					}else {

						dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

						$classname = ucfirst($subelement);
						$srcobject = new $classname($db);

						dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
						$result=$srcobject->fetch($object->origin_id);
						if ($result > 0)
						{
							$lines = $srcobject->lines;
							if (empty($lines) && method_exists($srcobject,'fetch_lines'))  $lines = $srcobject->fetch_lines();

							$fk_parent_line=0;
							$num=count($lines);

							for ($i=0;$i<$num;$i++)
							{
								$label=(! empty($lines[$i]->label)?$lines[$i]->label:'');
								$desc=(! empty($lines[$i]->desc)?$lines[$i]->desc:$lines[$i]->libelle);

								if ($lines[$i]->subprice < 0)
								{
									// Negative line, we create a discount line
									$discount = new DiscountAbsolute($db);
									$discount->fk_soc=$object->socid;
									$discount->amount_ht=abs($lines[$i]->total_ht);
									$discount->amount_tva=abs($lines[$i]->total_tva);
									$discount->amount_ttc=abs($lines[$i]->total_ttc);
									$discount->tva_tx=$lines[$i]->tva_tx;
									$discount->fk_user=$user->id;
									$discount->description=$desc;
									$discountid=$discount->create($user);
									if ($discountid > 0)
									{
										$result=$object->insert_discount($discountid);    // This include link_to_invoice
									}
									else
									{
										$mesgs[]=$discount->error;
										$error++;
										break;
									}
								}
								else
								{
									// Positive line
									$product_type=($lines[$i]->product_type?$lines[$i]->product_type:0);

									// Date start
									$date_start=false;
									if ($lines[$i]->date_debut_prevue) $date_start=$lines[$i]->date_debut_prevue;
									if ($lines[$i]->date_debut_reel) $date_start=$lines[$i]->date_debut_reel;
									if ($lines[$i]->date_start) $date_start=$lines[$i]->date_start;

									//Date end
									$date_end=false;
									if ($lines[$i]->date_fin_prevue) $date_end=$lines[$i]->date_fin_prevue;
									if ($lines[$i]->date_fin_reel) $date_end=$lines[$i]->date_fin_reel;
									if ($lines[$i]->date_end) $date_end=$lines[$i]->date_end;

									// Reset fk_parent_line for no child products and special product
									if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
										$fk_parent_line = 0;
									}

									//Extrafields
									if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i],'fetch_optionals'))
									{
										$lines[$i]->fetch_optionals($lines[$i]->rowid);
										$array_option=$lines[$i]->array_options;
									}

									$result = $object->addline(
										$desc,
										$lines[$i]->subprice,
										$lines[$i]->qty,
										$lines[$i]->tva_tx,
										$lines[$i]->localtax1_tx,
										$lines[$i]->localtax2_tx,
										$lines[$i]->fk_product,
										$lines[$i]->remise_percent,
										$date_start,
										$date_end,
										0,
										$lines[$i]->info_bits,
										$lines[$i]->fk_remise_except,
										'HT',
										0,
										$product_type,
										$lines[$i]->rang,
										$lines[$i]->special_code,
										$object->origin,
										$lines[$i]->rowid,
										$fk_parent_line,
										$lines[$i]->fk_fournprice,
										$lines[$i]->pa_ht,
										$label,
										$array_option
									);

									if ($result > 0)
									{
										$lineid=$result;
									}
									else
									{
										$lineid=0;
										$error++;
										break;
									}

									// Defined the new fk_parent_line
									if ($result > 0 && $lines[$i]->product_type == 9) {
										$fk_parent_line = $result;
									}
								}
							}

							// Hooks
							$parameters=array('objFrom'=>$srcobject);
							$reshook=$hookmanager->executeHooks('createFrom',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
							if ($reshook < 0) $error++;
						}
						else
						{
							$mesgs[]=$srcobject->error;
							$error++;
						}
					}
				}
				else
				{
					$mesgs[]=$object->error;
					$error++;
				}
			}
			// If some invoice's lines already known
			else
			{
				$id = $object->create($user);

				for ($i = 1; $i <= $NBLINES; $i++)
				{
					if ($_POST['idprod'.$i])
					{
						$product=new Product($db);
						$product->fetch($_POST['idprod'.$i]);
						$startday=dol_mktime(12, 0, 0, $_POST['date_start'.$i.'month'], $_POST['date_start'.$i.'day'], $_POST['date_start'.$i.'year']);
						$endday=dol_mktime(12, 0, 0, $_POST['date_end'.$i.'month'], $_POST['date_end'.$i.'day'], $_POST['date_end'.$i.'year']);
						$result=$object->addline($product->description,$product->price, $_POST['qty'.$i], $product->tva_tx, $product->localtax1_tx, $product->localtax2_tx, $_POST['idprod'.$i], $_POST['remise_percent'.$i], $startday, $endday, 0, 0, '', $product->price_base_type, $product->price_ttc, $product->type);
					}
				}
			}
		}
	}

	// End of object creation, we show it
	if ($id > 0 && ! $error)
	{
		$db->commit();
		header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
		exit;
	}
	else
	{
		$db->rollback();
		$action='create';
		$_GET["origin"]=$_POST["origin"];
		$_GET["originid"]=$_POST["originid"];
		$mesgs[]='<div class="error">'.$object->error.'</div>';
	}
}

// Add a new line
else if (($action == 'addline' || $action == 'addline_predef') && $user->rights->facture->creer)
{
	$langs->load('errors');
	$error = 0;

	$idprod=GETPOST('idprod', 'int');
	$product_desc = (GETPOST('product_desc')?GETPOST('product_desc'):(GETPOST('np_desc')?GETPOST('np_desc'):(GETPOST('dp_desc')?GETPOST('dp_desc'):'')));
	$price_ht = GETPOST('price_ht');
	$tva_tx=(GETPOST('tva_tx')?GETPOST('tva_tx'):0);

	//Extrafields
	$extrafieldsline = new ExtraFields($db);
	$extralabelsline =$extrafieldsline->fetch_name_optionals_label($object->table_element_line);
	$array_option = $extrafieldsline->getOptionalsFromPost($extralabelsline);
	//Unset extrafield
	if (is_array($extralabelsline))
	{
		// Get extra fields
		foreach ($extralabelsline as $key => $value) {
			unset($_POST["options_".$key]);
		}
	}

	if ((empty($idprod) || GETPOST('usenewaddlineform')) && ($price_ht < 0) && (GETPOST('qty') < 0))
	{
		setEventMessage($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), 'errors');
		$error++;
	}
	if (empty($idprod) && GETPOST('type') < 0)
	{
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), 'errors');
		$error++;
	}
	if ((empty($idprod) || GETPOST('usenewaddlineform')) && (!($price_ht >= 0) || $price_ht == ''))	// Unit price can be 0 but not ''
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("UnitPriceHT")), 'errors');
		$error++;
	}
	if (! GETPOST('qty') && GETPOST('qty') == '')
	{
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), 'errors');
		$error++;
	}
	if (empty($idprod) && empty($product_desc))
	{
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), 'errors');
		$error++;
	}

	if (! $error && (GETPOST('qty') >= 0) && (! empty($product_desc) || ! empty($idprod)))
	{
		$ret=$object->fetch($id);
		if ($ret < 0)
		{
			dol_print_error($db,$object->error);
			exit;
		}
		$ret=$object->fetch_thirdparty();

		// Clean parameters
		$predef=((! empty($idprod) && $conf->global->MAIN_FEATURES_LEVEL < 2) ? '_predef' : '');
		$date_start=dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
		$date_end=dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
		$price_base_type = (GETPOST('price_base_type', 'alpha')?GETPOST('price_base_type', 'alpha'):'HT');

		// Define special_code for special lines
		$special_code=0;
		//if (empty($_POST['qty'])) $special_code=3;	// Options should not exists on invoices

		// Ecrase $pu par celui du produit
		// Ecrase $desc par celui du produit
		// Ecrase $txtva par celui du produit
		// Ecrase $base_price_type par celui du produit
		if (! empty($idprod))
		{
			$prod = new Product($db);
			$prod->fetch($idprod);

			$label = ((GETPOST('product_label') && GETPOST('product_label')!=$prod->label)?GETPOST('product_label'):'');

			// Update if prices fields are defined
			if (GETPOST('usenewaddlineform'))
			{
				$pu_ht=price2num($price_ht, 'MU');
				$pu_ttc=price2num(GETPOST('price_ttc'), 'MU');
				$tva_npr=(preg_match('/\*/', $tva_tx)?1:0);
				$tva_tx=str_replace('*','', $tva_tx);
				$desc = $product_desc;
			}
			else
			{
				$tva_tx = get_default_tva($mysoc,$object->client,$prod->id);
				$tva_npr = get_default_npr($mysoc,$object->client,$prod->id);

				// We define price for product
				if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->client->price_level))
				{
					$pu_ht = $prod->multiprices[$object->client->price_level];
					$pu_ttc = $prod->multiprices_ttc[$object->client->price_level];
					$price_min = $prod->multiprices_min[$object->client->price_level];
					$price_base_type = $prod->multiprices_base_type[$object->client->price_level];
				}
				else
				{
					$pu_ht = $prod->price;
					$pu_ttc = $prod->price_ttc;
					$price_min = $prod->price_min;
					$price_base_type = $prod->price_base_type;
				}

				// if price ht is forced (ie: calculated by margin rate and cost price)
				if (!empty($price_ht))
				{
					$pu_ht	= price2num($price_ht, 'MU');
					$pu_ttc	= price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
				}

				// On reevalue prix selon taux tva car taux tva transaction peut etre different
				// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
				elseif ($tva_tx != $prod->tva_tx)
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

				$desc='';

				// Define output language
				if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
				{
					$outputlangs = $langs;
					$newlang='';
					if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
					if (empty($newlang)) $newlang=$object->client->default_lang;
					if (! empty($newlang))
					{
						$outputlangs = new Translate("",$conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$desc = (! empty($prod->multilangs[$outputlangs->defaultlang]["description"])) ? $prod->multilangs[$outputlangs->defaultlang]["description"] : $prod->description;
				}
				else
				{
					$desc = $prod->description;
				}

				$desc=dol_concatdesc($desc,$product_desc);

				// Add custom code and origin country into description
				if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (! empty($prod->customcode) || ! empty($prod->country_code)))
				{
					$tmptxt='(';
					if (! empty($prod->customcode)) $tmptxt.=$langs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
					if (! empty($prod->customcode) && ! empty($prod->country_code)) $tmptxt.=' - ';
					if (! empty($prod->country_code)) $tmptxt.=$langs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code,0,$db,$langs,0);
					$tmptxt.=')';
					$desc= dol_concatdesc($desc, $tmptxt);
				}
			}

			$type = $prod->type;
		}
		else
		{
			$pu_ht		= price2num($price_ht, 'MU');
			$pu_ttc		= price2num(GETPOST('price_ttc'), 'MU');
			$tva_npr	= (preg_match('/\*/', $tva_tx)?1:0);
			$tva_tx		= str_replace('*', '', $tva_tx);
			$label		= (GETPOST('product_label')?GETPOST('product_label'):'');
			$desc		= $product_desc;
			$type		= GETPOST('type');
		}

		// Margin
		$fournprice=(GETPOST('fournprice')?GETPOST('fournprice'):'');
		$buyingprice=(GETPOST('buying_price')?GETPOST('buying_price'):'');

		// Local Taxes
		$localtax1_tx= get_localtax($tva_tx, 1, $object->client);
		$localtax2_tx= get_localtax($tva_tx, 2, $object->client);

		$info_bits=0;
		if ($tva_npr) $info_bits |= 0x01;

		if (! empty($price_min) && (price2num($pu_ht)*(1-price2num(GETPOST('remise_percent'))/100) < price2num($price_min)))
		{
			$mesg = $langs->trans("CantBeLessThanMinPrice",price(price2num($price_min,'MU'),0,$langs,0,0,-1,$conf->currency));
			setEventMessage($mesg, 'errors');
		}
		else
		{
			// Insert line
			$result = $object->addline(
				$desc,
				$pu_ht,
				GETPOST('qty'),
				$tva_tx,
				$localtax1_tx,
				$localtax2_tx,
				$idprod,
				GETPOST('remise_percent'),
				$date_start,
				$date_end,
				0,
				$info_bits,
				'',
				$price_base_type,
				$pu_ttc,
				$type,
				-1,
				$special_code,
				'',
				0,
				GETPOST('fk_parent_line'),
				$fournprice,
				$buyingprice,
				$label,
				$array_option
			);

			if ($result > 0)
			{
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				{
					// Define output language
					$outputlangs = $langs;
					$newlang=GETPOST('lang_id','alpha');
					if (! empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) $newlang=$object->client->default_lang;
					if (! empty($newlang))
					{
						$outputlangs = new Translate("",$conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$ret=$object->fetch($id);    // Reload to get new records
					facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}

				unset($_POST['qty']);
				unset($_POST['type']);
				unset($_POST['idprod']);
				unset($_POST['remise_percent']);
				unset($_POST['price_ht']);
				unset($_POST['price_ttc']);
				unset($_POST['tva_tx']);
				unset($_POST['product_ref']);
				unset($_POST['product_label']);
				unset($_POST['product_desc']);
				unset($_POST['fournprice']);
				unset($_POST['buying_price']);
				unset($_POST['np_marginRate']);
				unset($_POST['np_markRate']);

				// old method
				unset($_POST['np_desc']);
				unset($_POST['dp_desc']);
			}
			else
			{
				setEventMessage($object->error, 'errors');
			}

			$action='';
		}
	}
}

else if ($action == 'updateligne' && $user->rights->facture->creer && $_POST['save'] == $langs->trans('Save'))
{
	if (! $object->fetch($id) > 0) dol_print_error($db);
	$object->fetch_thirdparty();

	// Clean parameters
	$date_start='';
	$date_end='';
	$date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
	$date_end=dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
	$description=dol_htmlcleanlastbr(GETPOST('product_desc'));
	$pu_ht=GETPOST('price_ht');
	$vat_rate=(GETPOST('tva_tx')?GETPOST('tva_tx'):0);

	// Define info_bits
	$info_bits=0;
	if (preg_match('/\*/', $vat_rate)) $info_bits |= 0x01;

	// Define vat_rate
	$vat_rate=str_replace('*','',$vat_rate);
	$localtax1_rate=get_localtax($vat_rate,1,$object->client);
	$localtax2_rate=get_localtax($vat_rate,2,$object->client);

	// Add buying price
	$fournprice=(GETPOST('fournprice')?GETPOST('fournprice'):'');
	$buyingprice=(GETPOST('buying_price')?GETPOST('buying_price'):'');

	//Extrafields
	$extrafieldsline = new ExtraFields($db);
	$extralabelsline =$extrafieldsline->fetch_name_optionals_label($object->table_element_line);
	$array_option = $extrafieldsline->getOptionalsFromPost($extralabelsline);
	//Unset extrafield
	if (is_array($extralabelsline))
	{
		// Get extra fields
		foreach ($extralabelsline as $key => $value)
		{
			unset($_POST["options_".$key]);
		}
	}


	// Check minimum price
	$productid = GETPOST('productid', 'int');
	if (! empty($productid))
	{
		$product = new Product($db);
		$product->fetch($productid);

		$type=$product->type;

		$price_min = $product->price_min;
		if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->client->price_level))
			$price_min = $product->multiprices_min[$object->client->price_level];

		$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label'):'');

		if ($price_min && (price2num($pu_ht)*(1-price2num(GETPOST('remise_percent'))/100) < price2num($price_min)))
		{
			setEventMessage($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min,'MU'),0,$langs,0,0,-1,$conf->currency)), 'errors');
			$error++;
		}
	}
	else
	{
		$type = GETPOST('type');
		$label = (GETPOST('product_label') ? GETPOST('product_label'):'');

		// Check parameters
		if (GETPOST('type') < 0) {
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")), 'errors');
			$error++;
		}
	}

	// Update line
	if (! $error)
	{
		$result = $object->updateline(
			GETPOST('lineid'),
			$description,
			$pu_ht,
			GETPOST('qty'),
			GETPOST('remise_percent'),
			$date_start,
			$date_end,
			$vat_rate,
			$localtax1_rate,
			$localtax2_rate,
			'HT',
			$info_bits,
			$type,
			GETPOST('fk_parent_line'),
			0,
			$fournprice,
			$buyingprice,
			$label,
			0,
			$array_option
		);

		if ($result >= 0)
		{
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
				// Define output language
				$outputlangs = $langs;
				$newlang='';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
				if (! empty($newlang))
				{
					$outputlangs = new Translate("",$conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$ret=$object->fetch($id);    // Reload to get new records
				facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}

			unset($_POST['qty']);
			unset($_POST['type']);
			unset($_POST['productid']);
			unset($_POST['remise_percent']);
			unset($_POST['price_ht']);
			unset($_POST['price_ttc']);
			unset($_POST['tva_tx']);
			unset($_POST['product_ref']);
			unset($_POST['product_label']);
			unset($_POST['product_desc']);
			unset($_POST['fournprice']);
			unset($_POST['buying_price']);
		}
		else
		{
			setEventMessage($object->error, 'errors');
		}
	}
}

else if ($action == 'updateligne' && $user->rights->facture->creer && $_POST['cancel'] == $langs->trans('Cancel'))
{
	header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);   // Pour reaffichage de la fiche en cours d'edition
	exit;
}

// Modify line position (up)
else if ($action == 'up' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$object->fetch_thirdparty();
	$object->line_up($_GET['rowid']);

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);

	header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id.'#'.$_GET['rowid']);
	exit;
}
// Modify line position (down)
else if ($action == 'down' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$object->fetch_thirdparty();
	$object->line_down($_GET['rowid']);

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);

	header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id.'#'.$_GET['rowid']);
	exit;
}

// Link invoice to order
if (GETPOST('linkedOrder'))
{
	$object->fetch($id);
	$object->fetch_thirdparty();
	$result=$object->add_object_linked('commande',GETPOST('linkedOrder'));
}


/*
 * Add file in email form
 */
if (GETPOST('addfile'))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';

	dol_add_file_process($upload_dir_tmp,0,0);
	$action='presend';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';

	// TODO Delete only files that was uploaded from email form
	dol_remove_file_process($_POST['removedfile'],0);
	$action='presend';
}

/*
 * Send mail
 */
if (($action == 'send' || $action == 'relance') && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
	$langs->load('mails');

	$actiontypecode='';$subject='';$actionmsg='';$actionmsg2='';

	$result=$object->fetch($id);
	$result=$object->fetch_thirdparty();

	if ($result > 0)
	{
		//        $ref = dol_sanitizeFileName($object->ref);
		//        $file = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';

		//        if (is_readable($file))
		//        {
		if ($_POST['sendto'])
		{
			// Le destinataire a ete fourni via le champ libre
			$sendto = $_POST['sendto'];
			$sendtoid = 0;
		}
		elseif ($_POST['receiver'] != '-1')
		{
			// Recipient was provided from combo list
			if ($_POST['receiver'] == 'thirdparty') // Id of third party
			{
				$sendto = $object->client->email;
				$sendtoid = 0;
			}
			else	// Id du contact
			{
				$sendto = $object->client->contact_get_property($_POST['receiver'],'email');
				$sendtoid = $_POST['receiver'];
			}
		}

		if (dol_strlen($sendto))
		{
			$langs->load("commercial");

			$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
			$replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
			$message = $_POST['message'];
			$sendtocc = $_POST['sendtocc'];
			$deliveryreceipt = $_POST['deliveryreceipt'];

			if ($action == 'send')
			{
				if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
				else $subject = $langs->transnoentities('Bill').' '.$object->ref;
				$actiontypecode='AC_FAC';
				$actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
				if ($message)
				{
					$actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
					$actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
					$actionmsg.=$message;
				}
				//$actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
			}
			if ($action == 'relance')
			{
				if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
				else $subject = $langs->transnoentities('Relance facture '.$object->ref);
				$actiontypecode='AC_FAC';
				$actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
				if ($message) {
					$actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
					$actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
					$actionmsg.=$message;
				}
				//$actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
			}

			// Create form object
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);

			$attachedfiles=$formmail->get_attached_files();
			$filepath = $attachedfiles['paths'];
			$filename = $attachedfiles['names'];
			$mimetype = $attachedfiles['mimes'];

			// Send mail
			require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt,-1);
			if ($mailfile->error)
			{
				$mesgs[]='<div class="error">'.$mailfile->error.'</div>';
			}
			else
			{
				$result=$mailfile->sendfile();
				if ($result)
				{
					$error=0;

					// Initialisation donnees
					$object->sendtoid		= $sendtoid;
					$object->actiontypecode	= $actiontypecode;
					$object->actionmsg		= $actionmsg;  // Long text
					$object->actionmsg2		= $actionmsg2; // Short text
					$object->fk_element		= $object->id;
					$object->elementtype	= $object->element;

					// Appel des triggers
					include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
					$interface=new Interfaces($db);
					$result=$interface->run_triggers('BILL_SENTBYMAIL',$object,$user,$langs,$conf);
					if ($result < 0) {
						$error++; $this->errors=$interface->errors;
					}
					// Fin appel triggers

					if ($error)
					{
						dol_print_error($db);
					}
					else
					{
						// Redirect here
						// This avoid sending mail twice if going out and then back to page
						$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));
						setEventMessage($mesg);
						header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id);
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
					$mesgs[]=$mesg;
				}
			}
			/*            }
			 else
			{
			$langs->load("other");
			$mesgs[]='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').'</div>';
			dol_syslog('Recipient email is empty');
			}*/
		}
		else
		{
			$langs->load("errors");
			$mesgs[]='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
			dol_syslog('Failed to read file: '.$file);
		}
	}
	else
	{
		$langs->load("other");
		$mesgs[]='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")).'</div>';
		dol_syslog('Impossible de lire les donnees de la facture. Le fichier facture n\'a peut-etre pas ete genere.');
	}

	$action = 'presend';
}

/*
 * Generate document
 */
else if ($action == 'builddoc')	// En get ou en post
{
	$object->fetch($id);
	$object->fetch_thirdparty();

	// Save last template used to generate document
	if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));
	if (GETPOST('fk_bank')) $object->fk_bank=GETPOST('fk_bank');

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$result=facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
}

// Remove file in doc form
else if ($action == 'remove_file')
{
	if ($object->fetch($id))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$object->fetch_thirdparty();

		$langs->load("other");
		$upload_dir = $conf->facture->dir_output;
		$file = $upload_dir . '/' . GETPOST('file');
		$ret=dol_delete_file($file,0,0,0,$object);
		if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
		else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
		$action='';
	}
}

// Print file
else if ($action == 'print_file' AND $user->rights->printipp->read)
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/dolprintipp.class.php';
	$printer = new dolPrintIPP($db,$conf->global->PRINTIPP_HOST,$conf->global->PRINTIPP_PORT,$user->login,$conf->global->PRINTIPP_USER,$conf->global->PRINTIPP_PASSWORD);
	$printer->print_file(GETPOST('file','alpha'),GETPOST('printer','alpha'));
    setEventMessage($langs->trans("FileWasSentToPrinter", GETPOST('file')));
    $action='';
}

if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->facture->creer)
{
	if ($action == 'addcontact')
	{
		$result = $object->fetch($id);

		if ($result > 0 && $id > 0)
		{
			$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
			$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
		}

		if ($result >= 0)
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		else
		{
			if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$langs->load("errors");
				$mesgs[] = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
			}
			else
			{
				$mesgs[] = '<div class="error">'.$object->error.'</div>';
			}
		}
	}

	// bascule du statut d'un contact
	else if ($action == 'swapstatut')
	{
		if ($object->fetch($id))
		{
			$result=$object->swapContactStatus(GETPOST('ligne'));
		}
		else
		{
			dol_print_error($db);
		}
	}

	// Efface un contact
	else if ($action == 'deletecontact')
	{
		$object->fetch($id);
		$result = $object->delete_contact($lineid);

		if ($result >= 0)
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		else {
			dol_print_error($db);
		}
	}
}

if ($action == 'update_extras')
{
	// Fill array 'array_options' with data from add form
	$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
	if($ret < 0)
		$error++;

	if(!$error) {
		// Actions on extra fields (by external module or standard code)
		// FIXME le hook fait double emploi avec le trigger !!
		$hookmanager->initHooks(array('invoicedao'));
		$parameters=array('id'=>$object->id);
		$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$object,$action); // Note that $action and $object may have been modified by some hooks
		if (empty($reshook))
		{
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$result=$object->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}
		}
		else if ($reshook < 0) $error++;
	}
	else
	{
		$action = 'edit_extras';
	}

}


/*
 * View
*/

$form = new Form($db);
$formother=new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic=new Account($db);
$now=dol_now();

llxHeader('',$langs->trans('Bill'),'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');

print '
<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	jQuery("#linktoorder").click(function() {
		jQuery("#commande").toggle();
	});
});
</script>
';


/*********************************************************************
 *
 * Mode creation
 *
 **********************************************************************/
if ($action == 'create')
{
	$facturestatic=new Facture($db);
	$extralabels=$extrafields->fetch_name_optionals_label($facturestatic->table_element);

	print_fiche_titre($langs->trans('NewBill'));

	$soc = new Societe($db);
	if ($socid>0) $res=$soc->fetch($socid);

	if (! empty($origin) && ! empty($originid))
	{
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;
		if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs))
		{
			$element = $regs[1];
			$subelement = $regs[2];
		}

		if ($element == 'project')
		{
			$projectid=$originid;
		}
		else
		{
			// For compatibility
			if ($element == 'order' || $element == 'commande')    {
				$element = $subelement = 'commande';
			}
			if ($element == 'propal')   {
				$element = 'comm/propal'; $subelement = 'propal';
			}
			if ($element == 'contract') {
				$element = $subelement = 'contrat';
			}
			if ($element == 'shipping') {
				$element = $subelement = 'expedition';
			}

			dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

			$classname = ucfirst($subelement);
			$objectsrc = new $classname($db);
			$objectsrc->fetch($originid);
			if (empty($objectsrc->lines) && method_exists($objectsrc,'fetch_lines'))  $objectsrc->fetch_lines();
			$objectsrc->fetch_thirdparty();

			$projectid			= (! empty($objectsrc->fk_project)?$objectsrc->fk_project:'');
			$ref_client			= (! empty($objectsrc->ref_client)?$objectsrc->ref_client:'');
			$ref_int			= (! empty($objectsrc->ref_int)?$objectsrc->ref_int:'');

			$soc = $objectsrc->thirdparty;
			$cond_reglement_id 	= (! empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(! empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
			$mode_reglement_id 	= (! empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(! empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
			$remise_percent 	= (! empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(! empty($soc->remise_percent)?$soc->remise_percent:0));
			$remise_absolue 	= (! empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(! empty($soc->remise_absolue)?$soc->remise_absolue:0));
			$dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;

			//Replicate extrafields
			$objectsrc->fetch_optionals($originid);
			$object->array_options=$objectsrc->array_options;
		}
	}
	else
	{
		$cond_reglement_id 	= $soc->cond_reglement_id;
		$mode_reglement_id 	= $soc->mode_reglement_id;
		$remise_percent 	= $soc->remise_percent;
		$remise_absolue 	= 0;
		$dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
	}
	$absolute_discount=$soc->getAvailableDiscounts();


	if (! empty($conf->use_javascript_ajax))
	{
		print ajax_combobox('fac_replacement');
		print ajax_combobox('fac_avoir');
	}

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	if ($soc->id > 0)
		print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
	print '<input name="facnumber" type="hidden" value="provisoire">';
	print '<input name="ref_client" type="hidden" value="'.$ref_client.'">';
	print '<input name="ref_int" type="hidden" value="'.$ref_int.'">';
	print '<input type="hidden" name="origin" value="'.$origin.'">';
	print '<input type="hidden" name="originid" value="'.$originid.'">';

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans('Draft').'</td></tr>';

	// Thirdparty
	print '<td class="fieldrequired">'.$langs->trans('Customer').'</td>';
	if($soc->id > 0)
	{
		print '<td colspan="2">';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$soc->id.'">';
		print '</td>';
	}
	else
	{
		print '<td colspan="2">';
		print $form->select_company('','socid','s.client = 1 OR s.client = 3',1);
		print '</td>';
	}
	print '</tr>'."\n";

	// Predefined invoices
	if (empty($origin) && empty($originid) && $socid > 0)
	{
		$sql = 'SELECT r.rowid, r.titre, r.total_ttc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_rec as r';
		$sql.= ' WHERE r.fk_soc = '.$soc->id;

		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num > 0)
			{
				print '<tr><td>'.$langs->trans('CreateFromRepeatableInvoice').'</td><td>';
				print '<select class="flat" name="fac_rec">';
				print '<option value="0" selected="selected"></option>';
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					print '<option value="'.$objp->rowid.'"';
					if (GETPOST('fac_rec') == $objp->rowid) print ' selected="selected"';
					print '>'.$objp->titre.' ('.price($objp->total_ttc).' '.$langs->trans("TTC").')</option>';
					$i++;
				}
				print '</select></td></tr>';
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}

	// Type de facture
	$facids=$facturestatic->list_replacable_invoices($soc->id);
	if ($facids < 0)
	{
		dol_print_error($db,$facturestatic);
		exit;
	}
	$options="";
	foreach ($facids as $facparam)
	{
		$options.='<option value="'.$facparam['id'].'"';
		if ($facparam['id'] == $_POST['fac_replacement']) $options.=' selected="selected"';
		$options.='>'.$facparam['ref'];
		$options.=' ('.$facturestatic->LibStatut(0,$facparam['status']).')';
		$options.='</option>';
	}

	$facids=$facturestatic->list_qualified_avoir_invoices($soc->id);
	if ($facids < 0)
	{
		dol_print_error($db,$facturestatic);
		exit;
	}
	$optionsav="";
	$newinvoice_static=new Facture($db);
	foreach ($facids as $key => $valarray)
	{
		$newinvoice_static->id=$key;
		$newinvoice_static->ref=$valarray['ref'];
		$newinvoice_static->statut=$valarray['status'];
		$newinvoice_static->type=$valarray['type'];
		$newinvoice_static->paye=$valarray['paye'];

		$optionsav.='<option value="'.$key.'"';
		if ($key == $_POST['fac_avoir']) $optionsav.=' selected="selected"';
		$optionsav.='>';
		$optionsav.=$newinvoice_static->ref;
		$optionsav.=' ('.$newinvoice_static->getLibStatut(1,$valarray['paymentornot']).')';
		$optionsav.='</option>';
	}

	print '<tr><td valign="top" class="fieldrequired">'.$langs->trans('Type').'</td><td colspan="2">';
	print '<table class="nobordernopadding">'."\n";

	// Standard invoice
	print '<tr height="18"><td width="16px" valign="middle">';
	print '<input type="radio" name="type" value="0"'.(GETPOST('type')==0?' checked="checked"':'').'>';
	print '</td><td valign="middle">';
	$desc=$form->textwithpicto($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
	print $desc;
	print '</td></tr>'."\n";

	// Proforma
	if (! empty($conf->global->FACTURE_USE_PROFORMAT))
	{
		print '<tr height="18"><td width="16px" valign="middle">';
		print '<input type="radio" name="type" value="4"'.(GETPOST('type')==4?' checked="checked"':'').'>';
		print '</td><td valign="middle">';
		$desc=$form->textwithpicto($langs->trans("InvoiceProForma"),$langs->transnoentities("InvoiceProFormaDesc"),1);
		print $desc;
		print '</td></tr>'."\n";
	}

	if ((empty($origin)) || ((($origin=='propal') || ($origin=='commande')) && (!empty($originid))))
	{
		// Deposit
		print '<tr height="18"><td width="16px" valign="middle">';
		print '<input type="radio" name="type" value="3"'.(GETPOST('type')==3?' checked="checked"':'').'>';
		print '</td><td valign="middle" class="nowrap">';
		$desc=$form->textwithpicto($langs->trans("InvoiceDeposit"),$langs->transnoentities("InvoiceDepositDesc"),1);
		print '<table class="nobordernopadding"><tr><td>'.$desc.'</td>';
		if (($origin=='propal') || ($origin=='commande'))
		{
			print '<td class="nowrap" style="padding-left: 5px">';
			$arraylist=array('amount'=>'FixAmount','variable'=>'VarAmount');
			print $form->selectarray('typedeposit',$arraylist, GETPOST('typedeposit'), 0, 0, 0, '', 1);
			print '</td>';
			print '<td class="nowrap" style="padding-left: 5px">'.$langs->trans('Value').':<input type="text" name="valuedeposit" size="3" value="'.GETPOST('valuedeposit','int').'"/>';
		}
		print '</td></tr></table>';
		print '</td></tr>'."\n";
	}

	if ($socid > 0)
	{
		// Replacement
		print '<tr height="18"><td valign="middle">';
		print '<input type="radio" name="type" value="1"'.(GETPOST('type')==1?' checked="checked"':'');
		if (! $options) print ' disabled="disabled"';
		print '>';
		print '</td><td valign="middle">';
		$text=$langs->trans("InvoiceReplacementAsk").' ';
		$text.='<select class="flat" name="fac_replacement" id="fac_replacement"';
		if (! $options) $text.=' disabled="disabled"';
		$text.='>';
		if ($options)
		{
			$text.='<option value="-1"></option>';
			$text.=$options;
		}
		else
		{
			$text.='<option value="-1">'.$langs->trans("NoReplacableInvoice").'</option>';
		}
		$text.='</select>';
		$desc=$form->textwithpicto($text,$langs->transnoentities("InvoiceReplacementDesc"),1);
		print $desc;
		print '</td></tr>'."\n";
	}

	if (empty($origin) && $socid > 0)
	{
		// Credit note
		print '<tr height="18"><td valign="middle">';
		print '<input type="radio" name="type" value="2"'.(GETPOST('type')==2?' checked=true':'');
		if (! $optionsav) print ' disabled="disabled"';
		print '>';
		print '</td><td valign="middle">';
		$text=$langs->transnoentities("InvoiceAvoirAsk").' ';
		//	$text.='<input type="text" value="">';
		$text.='<select class="flat" name="fac_avoir" id="fac_avoir"';
		if (! $optionsav) $text.=' disabled="disabled"';
		$text.='>';
		if ($optionsav)
		{
			$text.='<option value="-1"></option>';
			$text.=$optionsav;
		}
		else
		{
			$text.='<option value="-1">'.$langs->trans("NoInvoiceToCorrect").'</option>';
		}
		$text.='</select>';
		$desc=$form->textwithpicto($text,$langs->transnoentities("InvoiceAvoirDesc"),1);
		print $desc;
		print '</td></tr>'."\n";
	}

	print '</table>';
	print '</td></tr>';

	if ($socid > 0)
	{
		// Discounts for third party
		print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
		if ($soc->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",'<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">'.$soc->remise_percent.'</a>');
		else print $langs->trans("CompanyHasNoRelativeDiscount");
		print ' <a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">('.$langs->trans("EditRelativeDiscount").')</a>';
		print '. ';
		print '<br>';
		if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",'<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">'.price($absolute_discount).'</a>',$langs->trans("Currency".$conf->currency));
		else print $langs->trans("CompanyHasNoAbsoluteDiscount");
		print ' <a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">('.$langs->trans("EditGlobalDiscounts").')</a>';
		print '.';
		print '</td></tr>';
	}

	// Date invoice
	print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
	$form->select_date($dateinvoice,'','','','',"add",1,1);
	print '</td></tr>';

	// Payment term
	print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$form->select_conditions_paiements(isset($_POST['cond_reglement_id'])?$_POST['cond_reglement_id']:$cond_reglement_id,'cond_reglement_id');
	print '</td></tr>';

	// Payment mode
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$form->select_types_paiements(isset($_POST['mode_reglement_id'])?$_POST['mode_reglement_id']:$mode_reglement_id,'mode_reglement_id');
	print '</td></tr>';

	// Project
	if (! empty($conf->projet->enabled) && $socid>0)
	{
		$formproject=new FormProjets($db);

		$langs->load('projects');
		print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
		$formproject->select_projects($soc->id, $projectid, 'projectid');
		print '</td></tr>';
	}

	if ($soc->outstanding_limit)
	{
		// Outstanding Bill
		print '<tr><td>';
		print $langs->trans('OutstandingBill');
		print '</td><td align=right>';
		print price($soc->get_OutstandingBill()).' / ';
		print price($soc->outstanding_limit).'</td><td colspan=2>';
		print '</td>';
		print '</tr>';
	}

	// Other attributes
	$parameters=array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		print $object->showOptionals($extrafields,'edit');
	}

	// Modele PDF
	print '<tr><td>'.$langs->trans('Model').'</td>';
	print '<td>';
	include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
	$liste=ModelePDFFactures::liste_modeles($db);
	print $form->selectarray('model',$liste,$conf->global->FACTURE_ADDON_PDF);
	print "</td></tr>";

	// Public note
	print '<tr>';
	print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
	print '<td valign="top" colspan="2">';
	$note_public='';
	if (is_object($objectsrc))    // Take value from source object
	{
		$note_public=$objectsrc->note_public;
	}
	$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
	print $doleditor->Create(1);

	//print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_public.'</textarea></td></tr>';

	// Private note
	if (empty($user->societe_id))
	{
		print '<tr>';
		print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
		print '<td valign="top" colspan="2">';
		$note_private='';
		if (! empty($origin) && ! empty($originid) && is_object($objectsrc))    // Take value from source object
		{
			$note_private=$objectsrc->note_private;
		}
		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
		print $doleditor->Create(1);
		//print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'.</textarea></td></tr>';
	}

	if (! empty($origin) && ! empty($originid) && is_object($objectsrc))
	{
		// TODO for compatibility
		if ($origin == 'contrat')
		{
			// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
			$objectsrc->remise_absolue=$remise_absolue;
			$objectsrc->remise_percent=$remise_percent;
			$objectsrc->update_price(1,-1,1);
		}

		print "\n<!-- ".$classname." info -->";
		print "\n";
		print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
		print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
		print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
		print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
		print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

		$newclassname=$classname;
        if ($newclassname == 'Propal') $newclassname = 'CommercialProposal';
        elseif ($newclassname == 'Commande') $newclassname = 'Order';
        elseif ($newclassname == 'Expedition') $newclassname = 'Sending';

		print '<tr><td>'.$langs->trans($newclassname).'</td><td colspan="2">'.$objectsrc->getNomUrl(1).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($objectsrc->total_ht).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($objectsrc->total_tva)."</td></tr>";
		if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
		{
			print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax1)."</td></tr>";
		}

		if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
		{
			print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax2)."</td></tr>";
		}
		print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($objectsrc->total_ttc)."</td></tr>";
	}
	else
	{
		// Show deprecated optional form to add product line here
		if (! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE))
		{
			print '<tr><td colspan="3">';

			// Zone de choix des produits predefinis a la creation
			print '<table class="noborder" width="100%">';
			print '<tr>';
			print '<td>'.$langs->trans('ProductsAndServices').'</td>';
			print '<td>'.$langs->trans('Qty').'</td>';
			print '<td>'.$langs->trans('ReductionShort').'</td>';
			print '<td> &nbsp; &nbsp; </td>';
			if (! empty($conf->service->enabled))
			{
				print '<td>'.$langs->trans('ServiceLimitedDuration').'</td>';
			}
			print '</tr>';
			for ($i = 1 ; $i <= $NBLINES ; $i++)
			{
				print '<tr>';
				print '<td>';
				// multiprix
				if (! empty($conf->global->PRODUIT_MULTIPRICES))
					$form->select_produits('','idprod'.$i,'',$conf->product->limit_size,$soc->price_level);
				else
					$form->select_produits('','idprod'.$i,'',$conf->product->limit_size);
				print '</td>';
				print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td>';
				print '<td class="nowrap"><input type="text" size="1" name="remise_percent'.$i.'" value="'.$soc->remise_percent.'">%</td>';
				print '<td>&nbsp;</td>';
				// Si le module service est actif, on propose des dates de debut et fin a la ligne
				if (! empty($conf->service->enabled))
				{
					print '<td class="nowrap">';
					print '<table class="nobordernopadding"><tr class="nocellnopadd">';
					print '<td class="nobordernopadding nowrap">';
					print $langs->trans('From').' ';
					print '</td><td class="nobordernopadding nowrap">';
					print $form->select_date('','date_start'.$i,$usehm,$usehm,1,"add");
					print '</td></tr>';
					print '<td class="nobordernopadding nowrap">';
					print $langs->trans('to').' ';
					print '</td><td class="nobordernopadding nowrap">';
					print $form->select_date('','date_end'.$i,$usehm,$usehm,1,"add");
					print '</td></tr></table>';
					print '</td>';
				}
				print "</tr>\n";
			}

			print '</table>';
			print '</td></tr>';
		}
	}

	print "</table>\n";

	// Button "Create Draft"
	print '<br><center><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></center>';

	print "</form>\n";

	// Show origin lines
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc))
	{
		print '<br>';

		$title=$langs->trans('ProductsAndServices');
		print_titre($title);

		print '<table class="noborder" width="100%">';

		$objectsrc->printOriginLinesList();

		print '</table>';
	}

	print '<br>';
}
else if ($id > 0 || ! empty($ref))
{
	/*
	 * Show object in view mode
	*/

	$result=$object->fetch($id,$ref);

	// fetch optionals attributes and labels
	$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

	if ($result > 0)
	{
		if ($user->societe_id>0 && $user->societe_id!=$object->socid)  accessforbidden('',0);

		$result=$object->fetch_thirdparty();

		$soc = new Societe($db);
		$soc->fetch($object->socid);
		$selleruserevenustamp=$mysoc->useRevenueStamp();

		$totalpaye  = $object->getSommePaiement();
		$totalcreditnotes = $object->getSumCreditNotesUsed();
		$totaldeposits = $object->getSumDepositsUsed();
		//print "totalpaye=".$totalpaye." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits." selleruserrevenuestamp=".$selleruserevenustamp;

		// We can also use bcadd to avoid pb with floating points
		// For example print 239.2 - 229.3 - 9.9; does not return 0.
		//$resteapayer=bcadd($object->total_ttc,$totalpaye,$conf->global->MAIN_MAX_DECIMALS_TOT);
		//$resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
		$resteapayer = price2num($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits,'MT');

		if ($object->paye) $resteapayer=0;
		$resteapayeraffiche=$resteapayer;

		if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
		{
			$filterabsolutediscount="fk_facture_source IS NULL";  // If we want deposit to be substracted to payments only and not to total of final invoice
			$filtercreditnote="fk_facture_source IS NOT NULL";    // If we want deposit to be substracted to payments only and not to total of final invoice
		}
		else
		{
			$filterabsolutediscount="fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND description='(DEPOSIT)')";
			$filtercreditnote="fk_facture_source IS NOT NULL AND description <> '(DEPOSIT)'";
		}

		$absolute_discount=$soc->getAvailableDiscounts('',$filterabsolutediscount);
		$absolute_creditnote=$soc->getAvailableDiscounts('',$filtercreditnote);
		$absolute_discount=price2num($absolute_discount,'MT');
		$absolute_creditnote=price2num($absolute_creditnote,'MT');

		$author = new User($db);
		if ($object->user_author)
		{
			$author->fetch($object->user_author);
		}

		$objectidnext=$object->getIdReplacingInvoice();

		$head = facture_prepare_head($object);

		dol_fiche_head($head, 'compta', $langs->trans('InvoiceCustomer'), 0, 'bill');

		$formconfirm='';

		// Confirmation de la conversion de l'avoir en reduc
		if ($action == 'converttoreduc')
		{
			$text=$langs->trans('ConfirmConvertToReduc');
			$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id,$langs->trans('ConvertToReduc'),$text,'confirm_converttoreduc','',"yes",2);
		}

		// Confirmation to delete invoice
		if ($action == 'delete')
		{
			$text=$langs->trans('ConfirmDeleteBill',$object->ref);
			$formquestion=array();

			$qualified_for_stock_change=0;
			if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
			{
				$qualified_for_stock_change=$object->hasProductsOrServices(2);
			}
			else
			{
				$qualified_for_stock_change=$object->hasProductsOrServices(1);
			}

			if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change && $object->statut>=1)
			{
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct=new FormProduct($db);
				$label=$object->type==2?$langs->trans("SelectWarehouseForStockDecrease"):$langs->trans("SelectWarehouseForStockIncrease");
				$formquestion=array(
				//'text' => $langs->trans("ConfirmClone"),
				//array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
				//array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
				array('type' => 'other', 'name' => 'idwarehouse',   'label' => $label,   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1,0,0,$langs->trans("NoStockAction"))));
				$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id,$langs->trans('DeleteBill'),$text,'confirm_delete',$formquestion,"yes",1);
			}else {
				$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id,$langs->trans('DeleteBill'),$text,'confirm_delete','','',1);
			}
		}

		// Confirmation de la validation
		if ($action == 'valid')
		{
			// on verifie si l'objet est en numerotation provisoire
			$objectref = substr($object->ref, 1, 4);
			if ($objectref == 'PROV')
			{
				$savdate=$object->date;
				if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION))
				{
					$object->date=dol_now();
					$object->date_lim_reglement=$object->calculate_date_lim_reglement();
				}
				$numref = $object->getNextNumRef($soc);
				//$object->date=$savdate;
			}
			else
			{
				$numref = $object->ref;
			}

			$text=$langs->trans('ConfirmValidateBill',$numref);
			if (! empty($conf->notification->enabled))
			{
				require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
				$notify=new Notify($db);
				$text.='<br>';
				$text.=$notify->confirmMessage('BILL_VALIDATE',$object->socid);
			}
			$formquestion=array();

			$qualified_for_stock_change=0;
			if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
			{
				$qualified_for_stock_change=$object->hasProductsOrServices(2);
			}
			else
			{
				$qualified_for_stock_change=$object->hasProductsOrServices(1);
			}

			if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change)
			{
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
				$formproduct=new FormProduct($db);
				$warehouse = new Entrepot($db);
				$warehouse_array = $warehouse->list_array();
				if (count($warehouse_array) == 1) {
					$label = $object->type==2?$langs->trans("WarehouseForStockIncrease", current($warehouse_array)):$langs->trans("WarehouseForStockDecrease", current($warehouse_array));
					$value = '<input type="hidden" id="idwarehouse" name="idwarehouse" value="' . key($warehouse_array) . '">';
				} else {
					$label = $object->type==2?$langs->trans("SelectWarehouseForStockIncrease"):$langs->trans("SelectWarehouseForStockDecrease");
					$value = $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1);
				}
				$formquestion=array(
				//'text' => $langs->trans("ConfirmClone"),
				//array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
				//array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
				array('type' => 'other', 'name' => 'idwarehouse',   'label' => $label,   'value' => $value));
			}
			if ($object->type != 2 && $object->total_ttc < 0)    // Can happen only if $conf->global->FACTURE_ENABLE_NEGATIVE is on
			{
				$text.='<br>'.img_warning().' '.$langs->trans("ErrorInvoiceOfThisTypeMustBePositive");
			}
			$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('ValidateBill'),$text,'confirm_valid',$formquestion,(($object->type != 2 && $object->total_ttc < 0)?"no":"yes"),($conf->notification->enabled?0:2));
		}

		// Confirm back to draft status
		if ($action == 'modif')
		{
			$text=$langs->trans('ConfirmUnvalidateBill',$object->ref);
			$formquestion=array();

			$qualified_for_stock_change=0;
			if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
			{
				$qualified_for_stock_change=$object->hasProductsOrServices(2);
			}
			else
			{
				$qualified_for_stock_change=$object->hasProductsOrServices(1);
			}
			if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change)
			{
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
				$formproduct=new FormProduct($db);
				$warehouse = new Entrepot($db);
				$warehouse_array = $warehouse->list_array();
				if (count($warehouse_array) == 1) {
					$label = $object->type==2?$langs->trans("WarehouseForStockDecrease", current($warehouse_array)):$langs->trans("WarehouseForStockIncrease", current($warehouse_array));
					$value = '<input type="hidden" id="idwarehouse" name="idwarehouse" value="' . key($warehouse_array) . '">';
				} else {
					$label=$object->type==2?$langs->trans("SelectWarehouseForStockDecrease"):$langs->trans("SelectWarehouseForStockIncrease");
					$value = $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1);
				}
				$formquestion=array(
				//'text' => $langs->trans("ConfirmClone"),
				//array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
				//array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
				array('type' => 'other', 'name' => 'idwarehouse',   'label' => $label,   'value' => $value));
			}

			$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('UnvalidateBill'),$text,'confirm_modif',$formquestion,"yes",1);
		}

		// Confirmation du classement paye
		if ($action == 'paid' && $resteapayer <= 0)
		{
			$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('ClassifyPaid'),$langs->trans('ConfirmClassifyPaidBill',$object->ref),'confirm_paid','',"yes",1);
		}
		if ($action == 'paid' && $resteapayer > 0)
		{
			// Code
			$i=0;
			$close[$i]['code']='discount_vat';$i++;
			$close[$i]['code']='badcustomer';$i++;
			// Help
			$i=0;
			$close[$i]['label']=$langs->trans("HelpEscompte").'<br><br>'.$langs->trans("ConfirmClassifyPaidPartiallyReasonDiscountVatDesc");$i++;
			$close[$i]['label']=$langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");$i++;
			// Texte
			$i=0;
			$close[$i]['reason']=$form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonDiscountVat",$resteapayer,$langs->trans("Currency".$conf->currency)),$close[$i]['label'],1);$i++;
			$close[$i]['reason']=$form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer",$resteapayer,$langs->trans("Currency".$conf->currency)),$close[$i]['label'],1);$i++;
			// arrayreasons[code]=reason
			foreach($close as $key => $val)
			{
				$arrayreasons[$close[$key]['code']]=$close[$key]['reason'];
			}

			// Cree un tableau formulaire
			$formquestion=array(
			'text' => $langs->trans("ConfirmClassifyPaidPartiallyQuestion"),
			array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"),  'values' => $arrayreasons),
			array('type' => 'text',  'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'size' => '100')
			);
			// Paiement incomplet. On demande si motif = escompte ou autre
			$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('ClassifyPaid'),$langs->trans('ConfirmClassifyPaidPartially',$object->ref),'confirm_paid_partially',$formquestion,"yes");
		}

		// Confirmation du classement abandonne
		if ($action == 'canceled')
		{
			// S'il y a une facture de remplacement pas encore validee (etat brouillon),
			// on ne permet pas de classer abandonner la facture.
			if ($objectidnext)
			{
				$facturereplacement=new Facture($db);
				$facturereplacement->fetch($objectidnext);
				$statusreplacement=$facturereplacement->statut;
			}
			if ($objectidnext && $statusreplacement == 0)
			{
				print '<div class="error">'.$langs->trans("ErrorCantCancelIfReplacementInvoiceNotValidated").'</div>';
			}
			else
			{
				// Code
				$close[1]['code']='badcustomer';
				$close[2]['code']='abandon';
				// Help
				$close[1]['label']=$langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");
				$close[2]['label']=$langs->trans("ConfirmClassifyAbandonReasonOtherDesc");
				// Texte
				$close[1]['reason']=$form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer",$object->ref),$close[1]['label'],1);
				$close[2]['reason']=$form->textwithpicto($langs->transnoentities("ConfirmClassifyAbandonReasonOther"),$close[2]['label'],1);
				// arrayreasons
				$arrayreasons[$close[1]['code']]=$close[1]['reason'];
				$arrayreasons[$close[2]['code']]=$close[2]['reason'];

				// Cree un tableau formulaire
				$formquestion=array(
				'text' => $langs->trans("ConfirmCancelBillQuestion"),
				array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"),  'values' => $arrayreasons),
				array('type' => 'text',  'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'size' => '100')
				);

				$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id,$langs->trans('CancelBill'),$langs->trans('ConfirmCancelBill',$object->ref),'confirm_canceled',$formquestion,"yes");
			}
		}

		// Confirmation de la suppression d'une ligne produit
		if ($action == 'ask_deleteline')
		{
			$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 'no', 1);
		}

		// Clone confirmation
		if ($action == 'clone')
		{
			// Create an array for form
			$formquestion=array(
			//'text' => $langs->trans("ConfirmClone"),
			//array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1)
			);
			// Paiement incomplet. On demande si motif = escompte ou autre
			$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('CloneInvoice'),$langs->trans('ConfirmCloneInvoice',$object->ref),'confirm_clone',$formquestion,'yes',1);
		}

		if (! $formconfirm)
		{
			$parameters=array('lineid'=>$lineid);
			$formconfirm=$hookmanager->executeHooks('formConfirm',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		}

		// Print form confirm
		print $formconfirm;




		// Invoice content

		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
		print '<td colspan="5">';
		$morehtmlref='';
		$discount=new DiscountAbsolute($db);
		$result=$discount->fetch(0,$object->id);
		if ($result > 0)
		{
			$morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
		}
		if ($result < 0)
		{
			dol_print_error('',$discount->error);
		}
		print $form->showrefnav($object, 'ref', $linkback, 1, 'facnumber', 'ref', $morehtmlref);
		print '</td></tr>';

		// Ref customer
		print '<tr><td width="20%">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('RefCustomer');
		print '</td>';
		if ($action != 'refclient' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refclient&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
		print '</tr></table>';
		print '</td>';
		print '<td colspan="5">';
		if ($user->rights->facture->creer && $action == 'refclient')
		{
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="set_ref_client">';
			print '<input type="text" class="flat" size="20" name="ref_client" value="'.$object->ref_client.'">';
			print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		}
		else
		{
			print $object->ref_client;
		}
		print '</td></tr>';

		// Third party
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%">';
		print '<tr><td>'.$langs->trans('Company').'</td>';
		print '</td><td colspan="5">';
		if (! empty($conf->global->FACTURE_CHANGE_THIRDPARTY) && $action != 'editthirdparty' && $object->brouillon && $user->rights->facture->creer)
			print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editthirdparty&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetLinkToThirdParty'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="5">';
		if ($action == 'editthirdparty')
		{
			$form->form_thirdparty($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->socid,'socid');
		}
		else
		{
			print ' &nbsp;'.$soc->getNomUrl(1,'compta');
			print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->socid.'">'.$langs->trans('OtherBills').'</a>)';
		}
		print '</tr>';

		// Type
		print '<tr><td>'.$langs->trans('Type').'</td><td colspan="5">';
		print $object->getLibType();
		if ($object->type == 1)
		{
			$facreplaced=new Facture($db);
			$facreplaced->fetch($object->fk_facture_source);
			print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
		}
		if ($object->type == 2)
		{
			$facusing=new Facture($db);
			$facusing->fetch($object->fk_facture_source);
			print ' ('.$langs->transnoentities("CorrectInvoice",$facusing->getNomUrl(1)).')';
		}

		$facidavoir=$object->getListIdAvoirFromInvoice();
		if (count($facidavoir) > 0)
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
		if ($objectidnext > 0)
		{
			$facthatreplace=new Facture($db);
			$facthatreplace->fetch($objectidnext);
			print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
		}
		print '</td></tr>';

		// Relative and absolute discounts
		$addrelativediscount='<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("EditRelativeDiscounts").'</a>';
		$addabsolutediscount='<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("EditGlobalDiscounts").'</a>';
		$addcreditnote='<a href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&socid='.$soc->id.'&type=2&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("AddCreditNote").'</a>';

		print '<tr><td>'.$langs->trans('Discounts');
		print '</td><td colspan="5">';
		if ($soc->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_percent);
		else print $langs->trans("CompanyHasNoRelativeDiscount");
		//print ' ('.$addrelativediscount.')';

		if ($absolute_discount > 0)
		{
			print '. ';
			if ($object->statut > 0 || $object->type == 2 || $object->type == 3)
			{
				if ($object->statut == 0)
				{
					print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
					print '. ';
				}
				else
				{
					if ($object->statut < 1 || $object->type == 2 || $object->type == 3)
					{
						$text=$langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
						print '<br>'.$text.'.<br>';
					}
					else
					{
						$text=$langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
						$text2=$langs->trans("AbsoluteDiscountUse");
						print $form->textwithpicto($text,$text2);
					}
				}
			}
			else
			{
				// Remise dispo de type remise fixe (not credit note)
				print '<br>';
				$form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, GETPOST('discountid'), 'remise_id', $soc->id, $absolute_discount, $filterabsolutediscount, $resteapayer, ' ('.$addabsolutediscount.')');
			}
		}
		else
		{
			if ($absolute_creditnote > 0)    // If not, link will be added later
			{
				if ($object->statut == 0 && $object->type != 2 && $object->type != 3) print ' ('.$addabsolutediscount.')<br>';
				else print '. ';
			}
			else print '. ';
		}
		if ($absolute_creditnote > 0)
		{
			// If validated, we show link "add credit note to payment"
			if ($object->statut != 1 || $object->type == 2 || $object->type == 3)
			{
				if ($object->statut == 0 && $object->type != 3)
				{
					$text=$langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->currency));
					print $form->textwithpicto($text,$langs->trans("CreditNoteDepositUse"));
				}
				else
				{
					print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->currency)).'.';
				}
			}
			else
			{
				// Remise dispo de type avoir
				if (! $absolute_discount) print '<br>';
				//$form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filtercreditnote, $resteapayer);
				$form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filtercreditnote, 0);    // We must allow credit not even if amount is higher
			}
		}
		if (! $absolute_discount && ! $absolute_creditnote)
		{
			print $langs->trans("CompanyHasNoAbsoluteDiscount");
			if ($object->statut == 0 && $object->type != 2 && $object->type != 3) print ' ('.$addabsolutediscount.')<br>';
			else print '. ';
		}
		//if ($object->statut == 0 && $object->type != 2 && $object->type != 3)
		// {
		//if (! $absolute_discount && ! $absolute_creditnote) print '<br>';
		//print ' &nbsp; - &nbsp; ';
		//print $addabsolutediscount;
		//print ' &nbsp; - &nbsp; '.$addcreditnote;      // We disbale link to credit note
		//}
		print '</td></tr>';

		// Date invoice
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Date');
		print '</td>';
		if ($object->type != 2 && $action != 'editinvoicedate' && ! empty($object->brouillon) && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editinvoicedate&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';

		if ($object->type != 2)
		{
			if ($action == 'editinvoicedate')
			{
				$form->form_date($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->date,'invoicedate');
			}
			else
			{
				print dol_print_date($object->date,'daytext');
			}
		}
		else
		{
			print dol_print_date($object->date,'daytext');
		}
		print '</td>';


		// List of payments

		$sign=1;
		if ($object->type == 2) $sign=-1;

		$nbrows=8; $nbcols=2;
		if (! empty($conf->projet->enabled)) $nbrows++;
		if (! empty($conf->banque->enabled)) $nbcols++;
		if (! empty($soc->outstandingbill)) $nbrows++;
		if($mysoc->localtax1_assuj=="1") $nbrows++;
		if($mysoc->localtax2_assuj=="1") $nbrows++;
		if ($selleruserevenustamp) $nbrows++;

		print '<td rowspan="'.$nbrows.'" colspan="2" valign="top">';

		print '<table class="nobordernopadding" width="100%">';

		// List of payments already done
		print '<tr class="liste_titre">';
		print '<td>'.($object->type == 2 ? $langs->trans("PaymentsBack") : $langs->trans('Payments')).'</td>';
		print '<td>'.$langs->trans('Type').'</td>';
		if (! empty($conf->banque->enabled)) print '<td align="right">'.$langs->trans('BankAccount').'</td>';
		print '<td align="right">'.$langs->trans('Amount').'</td>';
		print '<td width="18">&nbsp;</td>';
		print '</tr>';

		$var=true;

		// Payments already done (from payment on this invoice)
		$sql = 'SELECT p.datep as dp, p.num_paiement, p.rowid, p.fk_bank,';
		$sql.= ' c.code as payment_code, c.libelle as payment_label,';
		$sql.= ' pf.amount,';
		$sql.= ' ba.rowid as baid, ba.ref, ba.label';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'paiement as p';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
		$sql.= ' WHERE pf.fk_facture = '.$object->id.' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
		$sql.= ' ORDER BY p.datep, p.tms';

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;

			//if ($object->type != 2)
			//{
			if ($num > 0)
			{
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;
					print '<tr '.$bc[$var].'><td>';
					print '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans('ShowPayment'),'payment').' ';
					print dol_print_date($db->jdate($objp->dp),'day').'</a></td>';
					$label=($langs->trans("PaymentType".$objp->payment_code)!=("PaymentType".$objp->payment_code))?$langs->trans("PaymentType".$objp->payment_code):$objp->payment_label;
					print '<td>'.$label.' '.$objp->num_paiement.'</td>';
					if (! empty($conf->banque->enabled))
					{
						$bankaccountstatic->id=$objp->baid;
						$bankaccountstatic->ref=$objp->ref;
						$bankaccountstatic->label=$objp->ref;
						print '<td align="right">';
						if ($bankaccountstatic->id) print $bankaccountstatic->getNomUrl(1,'transactions');
						print '</td>';
					}
					print '<td align="right">'.price($sign * $objp->amount).'</td>';
					print '<td>&nbsp;</td>';
					print '</tr>';
					$i++;
				}
			}
			else
			{
				print '<tr '.$bc[$var].'><td colspan="'.$nbcols.'">'.$langs->trans("None").'</td><td></td><td></td></tr>';
			}
			//}
			$db->free($result);
			}
			else
			{
				dol_print_error($db);
			}

			if ($object->type != 2)
			{
				// Total already paid
				print '<tr><td colspan="'.$nbcols.'" align="right">';
				if ($object->type != 3) print $langs->trans('AlreadyPaidNoCreditNotesNoDeposits');
				else print $langs->trans('AlreadyPaid');
				print ' :</td><td align="right">'.price($totalpaye).'</td><td>&nbsp;</td></tr>';

				$resteapayeraffiche=$resteapayer;

				// Loop on each credit note or deposit amount applied
				$creditnoteamount=0;
				$depositamount=0;
				$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
				$sql.= " re.description, re.fk_facture_source";
				$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re";
				$sql.= " WHERE fk_facture = ".$object->id;
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
						print '<tr><td colspan="'.$nbcols.'" align="right">';
						if ($invoice->type == 2) print $langs->trans("CreditNote").' ';
						if ($invoice->type == 3) print $langs->trans("Deposit").' ';
						print $invoice->getNomUrl(0);
						print ' :</td>';
						print '<td align="right">'.price($obj->amount_ttc).'</td>';
						print '<td align="right">';
						print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&action=unlinkdiscount&discountid='.$obj->rowid.'">'.img_delete().'</a>';
						print '</td></tr>';
						$i++;
						if ($invoice->type == 2) $creditnoteamount += $obj->amount_ttc;
						if ($invoice->type == 3) $depositamount += $obj->amount_ttc;
					}
				}
				else
				{
					dol_print_error($db);
				}

				// Paye partiellement 'escompte'
				if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'discount_vat')
				{
					print '<tr><td colspan="'.$nbcols.'" align="right" nowrap="1">';
					print $form->textwithpicto($langs->trans("Discount").':',$langs->trans("HelpEscompte"),-1);
					print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
					$resteapayeraffiche=0;
				}
				// Paye partiellement ou Abandon 'badcustomer'
				if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'badcustomer')
				{
					print '<tr><td colspan="'.$nbcols.'" align="right" nowrap="1">';
					print $form->textwithpicto($langs->trans("Abandoned").':',$langs->trans("HelpAbandonBadCustomer"),-1);
					print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
					//$resteapayeraffiche=0;
				}
				// Paye partiellement ou Abandon 'product_returned'
				if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'product_returned')
				{
					print '<tr><td colspan="'.$nbcols.'" align="right" nowrap="1">';
					print $form->textwithpicto($langs->trans("ProductReturned").':',$langs->trans("HelpAbandonProductReturned"),-1);
					print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
					$resteapayeraffiche=0;
				}
				// Paye partiellement ou Abandon 'abandon'
				if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'abandon')
				{
					print '<tr><td colspan="'.$nbcols.'" align="right" nowrap="1">';
					$text=$langs->trans("HelpAbandonOther");
					if ($object->close_note) $text.='<br><br><b>'.$langs->trans("Reason").'</b>:'.$object->close_note;
					print $form->textwithpicto($langs->trans("Abandoned").':',$text,-1);
					print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
					$resteapayeraffiche=0;
				}

				// Billed
				print '<tr><td colspan="'.$nbcols.'" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($object->total_ttc).'</td><td>&nbsp;</td></tr>';

				// Remainder to pay
				print '<tr><td colspan="'.$nbcols.'" align="right">';
				if ($resteapayeraffiche >= 0) print $langs->trans('RemainderToPay');
				else print $langs->trans('ExcessReceived');
				print ' :</td>';
				print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayeraffiche).'</b></td>';
				print '<td class="nowrap">&nbsp;</td></tr>';
			}
			else	// Credit note
			{
				// Total already paid back
				print '<tr><td colspan="'.$nbcols.'" align="right">';
				print $langs->trans('AlreadyPaidBack');
				print ' :</td><td align="right">'.price($sign * $totalpaye).'</td><td>&nbsp;</td></tr>';

				// Billed
				print '<tr><td colspan="'.$nbcols.'" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($sign * $object->total_ttc).'</td><td>&nbsp;</td></tr>';

				// Remainder to pay back
				print '<tr><td colspan="'.$nbcols.'" align="right">';
				if ($resteapayeraffiche <= 0) print $langs->trans('RemainderToPayBack');
				else print $langs->trans('ExcessPaydBack');
				print ' :</td>';
				print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($sign * $resteapayeraffiche).'</b></td>';
				print '<td class="nowrap">&nbsp;</td></tr>';

				// Sold credit note
				//print '<tr><td colspan="'.$nbcols.'" align="right">'.$langs->trans('TotalTTC').' :</td>';
				//print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($sign * $object->total_ttc).'</b></td><td>&nbsp;</td></tr>';
			}

			print '</table>';

			// Margin Infos
			if (! empty($conf->margin->enabled))
			{
				print '<br>';
				$object->displayMarginInfos($object->statut > 0);
			}

			print '</td></tr>';

			// Conditions de reglement
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentConditionsShort');
			print '</td>';
			if ($object->type != 2 && $action != 'editconditions' && ! empty($object->brouillon) && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($object->type != 2)
			{
				if ($action == 'editconditions')
				{
					$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->cond_reglement_id,'cond_reglement_id');
				}
				else
				{
					$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->cond_reglement_id,'none');
				}
			}
			else
			{
				print '&nbsp;';
			}
			print '</td></tr>';

			// Date payment term
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DateMaxPayment');
			print '</td>';
			if ($object->type != 2 && $action != 'editpaymentterm' && ! empty($object->brouillon) && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editpaymentterm&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($object->type != 2)
			{
				if ($action == 'editpaymentterm')
				{
					$form->form_date($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->date_lim_reglement,'paymentterm');
				}
				else
				{
					print dol_print_date($object->date_lim_reglement,'daytext');
					if ($object->date_lim_reglement < ($now - $conf->facture->client->warning_delay) && ! $object->paye && $object->statut == 1 && ! isset($object->am)) print img_warning($langs->trans('Late'));
				}
			}
			else
			{
				print '&nbsp;';
			}
			print '</td></tr>';

			// Payment mode
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentMode');
			print '</td>';
			if ($action != 'editmode' && ! empty($object->brouillon) && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($action == 'editmode')
			{
				$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->mode_reglement_id,'mode_reglement_id');
			}
			else
			{
				$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->mode_reglement_id,'none');
			}
			print '</td></tr>';

			if ($soc->outstandingbill)
			{
				// Outstanding Bill
				print '<tr><td>';
				print $langs->trans('OutstandingBill');
				print '</td><td align=right>';
				print price($soc->get_OutstandingBill()).' / ';
				print price($soc->outstandingbill);
				print '</td>';
				print '</tr>';
			}

			// Amount
			print '<tr><td>'.$langs->trans('AmountHT').'</td>';
			print '<td align="right" colspan="3" nowrap>'.price($object->total_ht,1,'',1,-1,-1,$conf->currency).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right" colspan="3" nowrap>'.price($object->total_tva,1,'',1,-1,-1,$conf->currency).'</td></tr>';
			print '</tr>';

			// Amount Local Taxes
			if ($mysoc->localtax1_assuj=="1" && $mysoc->useLocalTax(1)) //Localtax1 (example RE)
			{
				print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td>';
				print '<td align="right" colspan="3" nowrap>'.price($object->total_localtax1,1,'',1,-1,-1,$conf->currency).'</td></tr>';
			}
			if ($mysoc->localtax2_assuj=="1" && $mysoc->useLocalTax(2)) //Localtax2 (example IRPF)
			{
				print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td>';
				print '<td align="right" colspan="3" nowrap>'.price($object->total_localtax2,1,'',1,-1,-1,$conf->currency).'</td></tr>';
			}

			// Revenue stamp
			if ($selleruserevenustamp)		// Test company use revenue stamp
			{
				print '<tr><td>';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('RevenueStamp');
				print '</td>';
				if ($action != 'editrevenuestamp' && ! empty($object->brouillon) && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editrevenuestamp&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetRevenuStamp'),1).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="3" align="right">';
				if ($action == 'editrevenuestamp')
				{
					print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="setrevenuestamp">';
					print $formother->select_revenue_stamp(GETPOST('revenuestamp'), 'revenuestamp', $mysoc->country_code);
					//print '<input type="text" class="flat" size="4" name="revenuestamp" value="'.price2num($object->revenuestamp).'">';
					print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
					print '</form>';
				}
				else
				{
					print price($object->revenuestamp,1,'',1,-1,-1,$conf->currency);
				}
				print '</td></tr>';
			}

			// Total with tax
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="3" nowrap>'.price($object->total_ttc,1,'',1,-1,-1,$conf->currency).'</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans('Status').'</td>';
			print '<td align="left" colspan="3">'.($object->getLibStatut(4,$totalpaye)).'</td></tr>';

			// Project
			if (! empty($conf->projet->enabled))
			{
				$langs->load('projects');
				print '<tr>';
				print '<td>';

				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('Project');
				print '</td>';
				if ($action != 'classify')
				{
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;facid='.$object->id.'">';
					print img_edit($langs->trans('SetProject'),1);
					print '</a></td>';
				}
				print '</tr></table>';

				print '</td><td colspan="3">';
				if ($action == 'classify')
				{
					$form->form_project($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->socid,$object->fk_project,'projectid');
				}
				else
				{
					$form->form_project($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->socid,$object->fk_project,'none');
				}
				print '</td>';
				print '</tr>';
			}

			// Other attributes
			$res=$object->fetch_optionals($object->id,$extralabels);
			$parameters=array('colspan' => ' colspan="2"');
			$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
			if (empty($reshook) && ! empty($extrafields->attribute_label))
			{

				if ($action == 'edit_extras')
				{
					print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formsoc">';
					print '<input type="hidden" name="action" value="update_extras">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="id" value="'.$object->id.'">';
				}

				foreach($extrafields->attribute_label as $key=>$label)
				{
					if ($action == 'edit_extras') {
						$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
					} else {
						$value=$object->array_options["options_".$key];
					}
					if ($extrafields->attribute_type[$key] == 'separate')
					{
						print $extrafields->showSeparator($key);
					}
					else
					{
						print '<tr><td';
						if (! empty($extrafields->attribute_required[$key])) print ' class="fieldrequired"';
						print '>'.$label.'</td><td colspan="5">';
						// Convert date into timestamp format
						if (in_array($extrafields->attribute_type[$key],array('date','datetime')))
						{
							$value = isset($_POST["options_".$key])?dol_mktime($_POST["options_".$key."hour"], $_POST["options_".$key."min"], 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]):$db->jdate($object->array_options['options_'.$key]);
						}

						if ($action == 'edit_extras' && $user->rights->facture->creer)
						{
							print $extrafields->showInputField($key,$value);
						}
						else
						{
							print $extrafields->showOutputField($key,$value);
						}
						print '</td></tr>'."\n";
					}
				}

				if(count($extrafields->attribute_label) > 0) {

					if ($action == 'edit_extras' && $user->rights->facture->creer)
					{
						print '<tr><td></td><td colspan="5">';
						print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
						print '</form>';
						print '</td></tr>';

					}
					else {
						if ($object->statut == 0 && $user->rights->facture->creer)
						{
							print '<tr><td></td><td><a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit_extras">'.img_picto('','edit').' '.$langs->trans('Modify').'</a></td></tr>';
						}
					}
				}
			}

			print '</table><br>';

			if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
			{
				$blocname = 'contacts';
				$title = $langs->trans('ContactsAddresses');
				include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
			}

			if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
			{
				$blocname = 'notes';
				$title = $langs->trans('Notes');
				include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
			}

			// Lines
			$result = $object->getLinesArray();

			if (! empty($conf->use_javascript_ajax) && $object->statut == 0)
			{
				include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
			}

			print '<table id="tablelines" class="noborder noshadow" width="100%">';

			// Show object lines
			if (! empty($object->lines))
				$ret=$object->printObjectLines($action,$mysoc,$soc,$lineid,1);

			// Form to add new line
			if ($object->statut == 0 && $user->rights->facture->creer && $action <> 'valid' && $action <> 'editline')
			{
				$var=true;

				if ($conf->global->MAIN_FEATURES_LEVEL > 1)
				{
					// Add free or predefined products/services
					$object->formAddObjectLine(1,$mysoc,$soc);
				}
				else
				{
					// Add free products/services
					$object->formAddFreeProduct(1,$mysoc,$soc);

					// Add predefined products/services
					if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
					{
						$var=!$var;
						$object->formAddPredefinedProduct(1,$mysoc,$soc);
					}
				}

				$parameters=array();
				$reshook=$hookmanager->executeHooks('formAddObjectLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
			}

			print "</table>\n";

			print "</div>\n";


			// Boutons actions

			if ($action != 'prerelance' && $action != 'presend')
			{
				if ($user->societe_id == 0 && $action <> 'valid' && $action <> 'editline')
				{
					print '<div class="tabsAction">';

					// Editer une facture deja validee, sans paiement effectue et pas exporte en compta
					if ($object->statut == 1)
					{
						// On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
						$ventilExportCompta = $object->getVentilExportCompta();

						if ($resteapayer == $object->total_ttc	&& $object->paye == 0 && $ventilExportCompta == 0)
						{
							if (! $objectidnext)
							{
								if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->valider) || $user->rights->facture->invoice_advance->unvalidate)
								{
									print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=modif">'.$langs->trans('Modify').'</a></div>';
								}
								else
								{
									print '<div class="inline-block divButAction"><span class="butActionRefused" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('Modify').'</span></div>';
								}
							}
							else
							{
								print '<div class="inline-block divButAction"><span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('Modify').'</span></div>';
							}
						}
					}

					// Reopen a standard paid invoice
					if (($object->type == 0 || $object->type == 1) && ($object->statut == 2 || $object->statut == 3))				// A paid invoice (partially or completely)
					{
						if (! $objectidnext && $object->close_code != 'replaced')	// Not replaced by another invoice
						{
							print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=reopen">'.$langs->trans('ReOpen').'</a></div>';
						}
						else
						{
							print '<div class="inline-block divButAction"><span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('ReOpen').'</span></div>';
						}
					}

					// Validate
					if ($object->statut == 0 && count($object->lines) > 0 &&
						(
							(($object->type == 0 || $object->type == 1 || $object->type == 3 || $object->type == 4) && (! empty($conf->global->FACTURE_ENABLE_NEGATIVE) || $object->total_ttc >= 0))
							|| ($object->type == 2 && $object->total_ttc <= 0))
					)
					{
						if ($user->rights->facture->valider)
						{
							print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=valid">'.$langs->trans('Validate').'</a></div>';
						}
					}

					// Send by mail
					if (($object->statut == 1 || $object->statut == 2))
					{
						if ($objectidnext)
						{
							print '<div class="inline-block divButAction"><span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('SendByMail').'</span></div>';
						}
						else
						{
							if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send)
							{
								print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a></div>';
							}
							else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a></div>';
						}
					}

					if (! empty($conf->global->FACTURE_SHOW_SEND_REMINDER))	// For backward compatibility
					{
						if (($object->statut == 1 || $object->statut == 2) && $resteapayer > 0)
						{
							if ($objectidnext)
							{
								print '<div class="inline-block divButAction"><span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('SendRemindByMail').'</span></div>';
							}
							else
							{
								if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send)
								{
									print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=prerelance&amp;mode=init">'.$langs->trans('SendRemindByMail').'</a></div>';
								}
								else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">'.$langs->trans('SendRemindByMail').'</a></div>';
							}
						}
					}

					// Create payment
					if ($object->type != 2 && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement)
					{
						if ($objectidnext)
						{
							print '<div class="inline-block divButAction"><span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('DoPayment').'</span></div>';
						}
						else
						{
							if ($resteapayer == 0)
							{
								print '<div class="inline-block divButAction"><span class="butActionRefused" title="'.$langs->trans("DisabledBecauseRemainderToPayIsZero").'">'.$langs->trans('DoPayment').'</span></div>';
							}
							else
							{
								print '<div class="inline-block divButAction"><a class="butAction" href="paiement.php?facid='.$object->id.'&amp;action=create">'.$langs->trans('DoPayment').'</a></div>';
							}
						}
					}

					// Reverse back money or convert to reduction
					if ($object->type == 2 || $object->type == 3)
					{
						// For credit note only
						if ($object->type == 2 && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement)
						{
							print '<div class="inline-block divButAction"><a class="butAction" href="paiement.php?facid='.$object->id.'&amp;action=create">'.$langs->trans('DoPaymentBack').'</a></div>';
						}
						// For credit note
						if ($object->type == 2 && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->creer && $object->getSommePaiement() == 0)
						{
							print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=converttoreduc">'.$langs->trans('ConvertToReduc').'</a></div>';
						}
						// For deposit invoice
						if ($object->type == 3 && $object->statut == 1 && $resteapayer == 0 && $user->rights->facture->creer)
						{
							print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=converttoreduc">'.$langs->trans('ConvertToReduc').'</a></div>';
						}
					}

					// Classify paid (if not deposit and not credit note. Such invoice are "converted")
					if ($object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement &&
						(($object->type != 2 && $object->type != 3 && $resteapayer <= 0) || ($object->type == 2 && $resteapayer >= 0)) )
					{
						print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaid').'</a></div>';
					}

					// Classify 'closed not completely paid' (possible si validee et pas encore classee payee)
					if ($object->statut == 1 && $object->paye == 0 && $resteapayer > 0
						&& $user->rights->facture->paiement)
					{
						if ($totalpaye > 0 || $totalcreditnotes > 0)
						{
							// If one payment or one credit note was linked to this invoice
							print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaidPartially').'</a></div>';
						}
						else
						{
							if ($objectidnext)
							{
								print '<div class="inline-block divButAction"><span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('ClassifyCanceled').'</span></div>';
							}
							else
							{
								print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=canceled">'.$langs->trans('ClassifyCanceled').'</a></div>';
							}
						}
					}

					// Clone
					if (($object->type == 0 || $object->type == 3 || $object->type == 4) && $user->rights->facture->creer)
					{
						print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=clone&amp;object=invoice">'.$langs->trans("ToClone").'</a></div>';
					}

					// Clone as predefined
					if (($object->type == 0 || $object->type == 3 || $object->type == 4) && $object->statut == 0 && $user->rights->facture->creer)
					{
						if (! $objectidnext)
						{
							print '<div class="inline-block divButAction"><a class="butAction" href="facture/fiche-rec.php?facid='.$object->id.'&amp;action=create">'.$langs->trans("ChangeIntoRepeatableInvoice").'</a></div>';
						}
					}

					// Delete
					if ($user->rights->facture->supprimer)
					{
						if (! $object->is_erasable())
						{
							print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("DisabledBecauseNotErasable").'">'.$langs->trans('Delete').'</a></div>';
						}
						else if ($objectidnext)
						{
							print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('Delete').'</a></div>';
						}
						elseif ($object->getSommePaiement())
						{
							print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("DisabledBecausePayments").'">'.$langs->trans('Delete').'</a></div>';
						}
						else
						{
							print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a></div>';
						}
					}
					else
					{
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a></div>';
					}

					print '</div>';
				}
			}
			print '<br>';

			if ($action != 'prerelance' && $action != 'presend')
			{
				print '<div class="fichecenter"><div class="fichehalfleft">';
				print '<a name="builddoc"></a>'; // ancre

				// Documents generes
				$filename=dol_sanitizeFileName($object->ref);
				$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?facid='.$object->id;
				$genallowed=$user->rights->facture->creer;
				$delallowed=$user->rights->facture->supprimer;

				print $formfile->showdocuments('facture',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang);
				$somethingshown=$formfile->numoffiles;

				// Linked object block
				$somethingshown=$object->showLinkedObjectBlock();

				if (empty($somethingshown) && $object->statut > 0)
				{
					print '<br><a href="#" id="linktoorder">'.$langs->trans('LinkedOrder').'</a>';

					print '<div id="commande" style="display:none">';

					$sql = "SELECT s.rowid as socid, s.nom as name, s.client, c.rowid, c.ref, c.ref_client, c.total_ht";
					$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
					$sql.= ", ".MAIN_DB_PREFIX."commande as c";
					$sql.= ' WHERE c.fk_soc = '.$soc->id.'';

					$resqlorderlist = $db->query($sql);
					if ($resqlorderlist)
					{
						$num = $db->num_rows($resqlorderlist);
						$i = 0;

						print '<form action="" method="POST" name="LinkedOrder">';
						print '<table class="noborder">';
						print '<tr class="liste_titre">';
						print '<td class="nowrap"></td>';
						print '<td align="center">'.$langs->trans("Ref").'</td>';
						print '<td align="left">'.$langs->trans("RefCustomer").'</td>';
						print '<td align="left">'.$langs->trans("AmountHTShort").'</td>';
						print '<td align="left">'.$langs->trans("Company").'</td>';
						print '</tr>';
						while ($i < $num)
						{
							$objp = $db->fetch_object($resqlorderlist);
							if ($objp->socid == $soc->id)
							{
								$var=!$var;
								print '<tr '.$bc[$var].'>';
								print '<td aling="left">';
								print '<input type="radio" name="linkedOrder" value='.$objp->rowid.'>';
								print '<td align="center">'.$objp->ref.'</td>';
								print '<td>'.$objp->ref_client.'</td>';
								print '<td>'.price($objp->total_ht).'</td>';
								print '<td>'.$objp->name.'</td>';
								print '</td>';
								print '</tr>';
							}
	
							$i++;
						}
						print '</table>';
						print '<br><center><input type="submit" class="button" value="'.$langs->trans('ToLink').'"></center>';
						print '</form>';
						$db->free($resqlorderlist);
					}
					else
					{
						dol_print_error($db);
					}
					
					print '</div>';
				}
				
				// Link for paypal payment
				if (! empty($conf->paypal->enabled) && $object->statut != 0)
				{
					include_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
					print showPaypalPaymentUrl('invoice',$object->ref);
				}

				print '</div><div class="fichehalfright"><div class="ficheaddleft">';

				// List of actions on element
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
				$formactions=new FormActions($db);
				$somethingshown=$formactions->showactions($object,'invoice',$socid);

				print '</div></div></div>';
			}
			else
			{
				/*
				 * Affiche formulaire mail
				*/

				// By default if $action=='presend'
				$titreform='SendBillByMail';
				$topicmail='SendBillRef';
				$action='send';
				$modelmail='facture_send';

				if ($action == 'prerelance')	// For backward compatibility
				{
					$titrefrom='SendReminderBillByMail';
					$topicmail='SendReminderBillRef';
					$action='relance';
					$modelmail='facture_relance';
				}

				$ref = dol_sanitizeFileName($object->ref);
				include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref,'/'));
				$file=$fileparams['fullname'];

				// Build document if it not exists
				if (! $file || ! is_readable($file))
				{
					// Define output language
					$outputlangs = $langs;
					$newlang='';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
					if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
					if (! empty($newlang))
					{
						$outputlangs = new Translate("",$conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$result=facture_pdf_create($db, $object, GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($result <= 0)
					{
						dol_print_error($db,$result);
						exit;
					}
					$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref,'/'));
					$file=$fileparams['fullname'];
				}

				print '<br>';
				print_titre($langs->trans($titreform));

				// Cree l'objet formulaire mail
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				$formmail = new FormMail($db);
				$formmail->fromtype = 'user';
				$formmail->fromid   = $user->id;
				$formmail->fromname = $user->getFullName($langs);
				$formmail->frommail = $user->email;
				$formmail->withfrom=1;
				$liste=array();
				foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key=>$value)	$liste[$key]=$value;
				$formmail->withto=GETPOST('sendto')?GETPOST('sendto'):$liste;
				$formmail->withtocc=$liste;
				$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
				if(empty($object->ref_client))
				{
					$formmail->withtopic=$langs->transnoentities($topicmail,'__FACREF__');
				}
				else if(!empty($object->ref_client))
				{
					$formmail->withtopic=$langs->transnoentities($topicmail,'__FACREF__(__REFCLIENT__)');
				}
				$formmail->withfile=2;
				$formmail->withbody=1;
				$formmail->withdeliveryreceipt=1;
				$formmail->withcancel=1;
				// Tableau des substitutions
				$formmail->substit['__FACREF__']=$object->ref;
				$formmail->substit['__SIGNATURE__']=$user->signature;
				$formmail->substit['__REFCLIENT__']=$object->ref_client;
				$formmail->substit['__PERSONALIZED__']='';
				$formmail->substit['__CONTACTCIVNAME__']='';

				//Find the good contact adress
				$custcontact='';
				$contactarr=array();
				$contactarr=$object->liste_contact(-1,'external');

				if (is_array($contactarr) && count($contactarr)>0) {
					foreach($contactarr as $contact) {
						if ($contact['libelle']==$langs->trans('TypeContact_facture_external_BILLING')) {

							require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

							$contactstatic=new Contact($db);
							$contactstatic->fetch($contact['id']);
							$custcontact=$contactstatic->getFullName($langs,1);
						}
					}

					if (!empty($custcontact)) {
						$formmail->substit['__CONTACTCIVNAME__']=$custcontact;
					}
				}


				// Tableau des parametres complementaires du post
				$formmail->param['action']=$action;
				$formmail->param['models']=$modelmail;
				$formmail->param['facid']=$object->id;
				$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

				// Init list of files
				if (GETPOST("mode")=='init')
				{
					$formmail->clear_attached_files();
					$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
				}

				$formmail->show_form();

				print '<br>';
			}
	}
	else
	{
		dol_print_error($db,$object->error);
	}
}

dol_htmloutput_mesg('',$mesgs);

llxFooter();
$db->close();
?>
