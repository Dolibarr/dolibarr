<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2011 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
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
 *	\file       htdocs/compta/facture.php
 *	\ingroup    facture
 *	\brief      Page to create/see an invoice
 */

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');

$langs->load('bills');
//print 'ee'.$langs->trans('BillsCustomer');exit;

$langs->load('companies');
$langs->load('products');
$langs->load('main');

if (GETPOST('mesg','int',1) && isset($_SESSION['message'])) $mesg=$_SESSION['message'];

$sall=trim(GETPOST('sall'));
$projectid=(GETPOST('projectid')?GETPOST('projectid','int'):0);

$id=(GETPOST('id')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
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

// Security check
$fieldid = (! empty($ref)?'facnumber':'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id,'','','fk_soc',$fieldid);

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;

$usehm=$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;

$object=new Facture($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
$hookmanager=new HookManager($db);
$hookmanager->callHooks(array('invoicecard'));


/*
 * Actions
 */

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes')
{
    if (1==0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
    {
        $mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
    }
    else
    {
    	if ($object->fetch($id) > 0)
    	{
    		$result=$object->createFromClone($socid, $hookmanager);
    		if ($result > 0)
    		{
    			header("Location: ".$_SERVER['PHP_SELF'].'?facid='.$result);
    			exit;
    		}
    		else
    		{
    			$mesg=$object->error;
    			$action='';
    		}
    	}
    }
}

// Change status of invoice
if ($action == 'reopen' && $user->rights->facture->creer)
{
    $result = $object->fetch($id);
    if ($object->statut == 2
    || ($object->statut == 3 && $object->close_code != 'replaced'))
    {
        $result = $object->set_unpaid($user);
        if ($result > 0)
        {
            Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
            exit;
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
}

// Delete invoice
if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->facture->supprimer)
{
    if ($user->rights->facture->supprimer)
    {
        $result = $object->fetch($id);
        $result = $object->delete();
        if ($result > 0)
        {
            Header('Location: '.$_SERVER["PHP_SELF"]);
            exit;
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
}

// Delete line
if ($action == 'confirm_deleteline' && $confirm == 'yes')
{
    if ($user->rights->facture->creer)
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
                $result=facture_pdf_create($db, $object, '', $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
            }
            if ($result >= 0)
            {
                Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
                exit;
            }
        }
        else
        {
            $mesg='<div clas="error">'.$object->error.'</div>';
            $action='';
        }
    }
}

// Delete link of credit note to invoice
if ($action == 'unlinkdiscount')
{
    if ($user->rights->facture->creer)
    {
        $discount=new DiscountAbsolute($db);
        $result=$discount->fetch($_GET["discountid"]);
        $discount->unlink_invoice();
    }
}

// Validation
if ($action == 'valid')
{
    $object->fetch($id);

    // On verifie signe facture
    if ($object->type == 2)
    {
        // Si avoir, le signe doit etre negatif
        if ($object->total_ht >= 0)
        {
            $mesg='<div class="error">'.$langs->trans("ErrorInvoiceAvoirMustBeNegative").'</div>';
            $action='';
        }
    }
    else
    {
        // Si non avoir, le signe doit etre positif
        if ($object->total_ht < 0)
        {
            $mesg='<div class="error">'.$langs->trans("ErrorInvoiceOfThisTypeMustBePositive").'</div>';
            $action='';
        }
    }
}

if ($action == 'set_thirdparty')
{
    $object->fetch($id);
    $object->setValueFrom('fk_soc',$socid);

    Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
    exit;
}

if ($action == 'classin')
{
    $object->fetch($id);
    $object->setProject($_POST['projectid']);
}

if ($action == 'setmode')
{
    $object->fetch($id);
    $result=$object->mode_reglement($_POST['mode_reglement_id']);
    if ($result < 0) dol_print_error($db,$object->error);
}

if ($action == 'setinvoicedate')
{
    $object->fetch($id);
    $object->date=dol_mktime(12,0,0,$_POST['invoicedatemonth'],$_POST['invoicedateday'],$_POST['invoicedateyear']);
    if ($object->date_lim_reglement < $object->date) $object->date_lim_reglement=$object->date;
    $result=$object->update($user);
    if ($result < 0) dol_print_error($db,$object->error);
}

if ($action == 'setpaymentterm')
{
    $object->fetch($id);
    $date_lim_reglement=dol_mktime(12,0,0,$_POST['paymenttermmonth'],$_POST['paymenttermday'],$_POST['paymenttermyear']);
    $result=$object->cond_reglement($object->cond_reglement_id,$date_lim_reglement);
    if ($result < 0) dol_print_error($db,$object->error);
}

if ($action == 'setconditions')
{
    $object->fetch($id);
    $result=$object->cond_reglement($_POST['cond_reglement_id']);
    if ($result < 0) dol_print_error($db,$object->error);
}

if ($action == 'setremisepercent' && $user->rights->facture->creer)
{
    $object->fetch($id);
    $result = $object->set_remise($user, $_POST['remise_percent']);
}

if ($action == "setabsolutediscount" && $user->rights->facture->creer)
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
                $mesg='<div class="error">'.$object->error.'</div>';
            }
        }
        else
        {
            dol_print_error($db,$object->error);
        }
    }
    if (! empty($_POST["remise_id_for_payment"]))
    {
        require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
        $discount = new DiscountAbsolute($db);
        $discount->fetch($_POST["remise_id_for_payment"]);

        $result=$discount->link_to_invoice(0,$id);
        if ($result < 0)
        {
            $mesg='<div class="error">'.$discount->error.'</div>';
        }
    }
}

if ($action == 'set_ref_client')
{
    $object->fetch($id);
    $object->set_ref_client($_POST['ref_client']);
}

// Classify to validated
if ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->facture->valider)
{
    $idwarehouse=GETPOST('idwarehouse');

    $object->fetch($id);
    $object->fetch_thirdparty();

    // Check parameters
    if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->hasProductsOrServices(1))
    {
        if (! $idwarehouse || $idwarehouse == -1)
        {
            $error++;
            $errors[]=$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse"));
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
                facture_pdf_create($db, $object, '', $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
            }
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
}

// Go back to draft status (unvalidate)
if ($action == 'confirm_modif' && ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->valider) || $user->rights->facture->invoice_advance->unvalidate))
{
    $idwarehouse=GETPOST('idwarehouse');

    $object->fetch($id);
    $object->fetch_thirdparty();

    // Check parameters
    if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->hasProductsOrServices(1))
    {
        if (! $idwarehouse || $idwarehouse == -1)
        {
            $error++;
            $errors[]=$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse"));
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
	            facture_pdf_create($db, $object, '', $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
	        }
	    }
    }
}

// Classify "paid"
if ($action == 'confirm_paid' && $confirm == 'yes' && $user->rights->facture->paiement)
{
    $object->fetch($id);
    $result = $object->set_paid($user);
}
// Classif  "paid partialy"
if ($action == 'confirm_paid_partially' && $confirm == 'yes' && $user->rights->facture->paiement)
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
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Reason")).'</div>';
    }
}
// Classify "abandoned"
if ($action == 'confirm_canceled' && $confirm == 'yes')
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
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Reason")).'</div>';
    }
}

// Convertir en reduc
if ($action == 'confirm_converttoreduc' && $confirm == 'yes' && $user->rights->facture->creer)
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
                //$mesg='OK'.$discount->id;
                $db->commit();
            }
            else
            {
                $mesg='<div class="error">'.$object->error.'</div>';
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
 * Insert new invoice in database
 */
if ($action == 'add' && $user->rights->facture->creer)
{
    $object->socid=GETPOST('socid');

    $db->begin();

    $error=0;

    // Replacement invoice
    if ($_POST['type'] == 1)
    {
        $datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
        if (empty($datefacture))
        {
            $error++;
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Date")).'</div>';
        }

        if (! ($_POST['fac_replacement'] > 0))
        {
            $error++;
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("ReplaceInvoice")).'</div>';
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
            if ($id <= 0) $mesg=$object->error;
        }
    }

    // Credit note invoice
    if ($_POST['type'] == 2)
    {
        if (! $_POST['fac_avoir'] > 0)
        {
            $error++;
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("CorrectInvoice")).'</div>';
        }

        $datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
        if (empty($datefacture))
        {
            $error++;
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Date")).'</div>';
        }

        if (! $error)
        {
            // Si facture avoir
            $datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

            //$result=$object->fetch($_POST['fac_avoir']);

            $object->socid				= $_POST['socid'];
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
                    $result=$object->addline($id,$product->description,$product->price, $_POST['qty'.$i], $product->tva_tx, $product->localtax1_tx, $product->localtax2_tx, $_POST['idprod'.$i], $_POST['remise_percent'.$i], $startday, $endday, 0, 0, '', $product->price_base_type, $product->price_ttc, $product->type);
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
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Date")).'</div>';
        }

        if (! $error)
        {
            $object->socid			= $_POST['socid'];
            $object->type           = $_POST['type'];
            $object->number         = $_POST['facnumber'];
            $object->date           = $datefacture;
            $object->note_public	= trim($_POST['note_public']);
            $object->note           = trim($_POST['note']);
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
        $datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
        if (empty($datefacture))
        {
            $error++;
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Date")).'</div>';
        }

        if (! $error)
        {
            // Si facture standard
            $object->socid				= $_POST['socid'];
            $object->type				= $_POST['type'];
            $object->number				= $_POST['facnumber'];
            $object->date				= $datefacture;
            $object->note_public		= trim($_POST['note_public']);
            $object->note				= trim($_POST['note']);
            $object->ref_client			= $_POST['ref_client'];
            $object->ref_int			= $_POST['ref_int'];
            $object->modelpdf			= $_POST['model'];
            $object->fk_project			= $_POST['projectid'];
            $object->cond_reglement_id	= ($_POST['type'] == 3?1:$_POST['cond_reglement_id']);
            $object->mode_reglement_id	= $_POST['mode_reglement_id'];
            $object->amount				= $_POST['amount'];
            $object->remise_absolue		= $_POST['remise_absolue'];
            $object->remise_percent		= $_POST['remise_percent'];

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
                if ($element == 'order')    { $element = $subelement = 'commande'; }
                if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }
                if ($element == 'contract') { $element = $subelement = 'contrat'; }
                if ($element == 'inter')    { $element = $subelement = 'ficheinter'; }

                $object->origin    = $_POST['origin'];
                $object->origin_id = $_POST['originid'];

                $id = $object->create($user);

                if ($id > 0)
                {
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
                            $desc=($lines[$i]->desc?$lines[$i]->desc:$lines[$i]->libelle);

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
                                    $mesg=$discount->error;
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

                                $result = $object->addline(
                                    $id,
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
                                    $fk_parent_line
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
                        $reshook=$hookmanager->executeHooks('createfrom',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
                        if ($reshook < 0) $error++;
                    }
                    else
                    {
                        $mesg=$srcobject->error;
                        $error++;
                    }
                }
                else
                {
                    $mesg=$object->error;
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
                        $result=$object->addline($id,$product->description,$product->price, $_POST['qty'.$i], $product->tva_tx, $product->localtax1_tx, $product->localtax2_tx, $_POST['idprod'.$i], $_POST['remise_percent'.$i], $startday, $endday, 0, 0, '', $product->price_base_type, $product->price_ttc, $product->type);
                    }
                }
            }
        }
    }

    // End of object creation, we show it
    if ($id > 0 && ! $error)
    {
        $db->commit();
        Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
        exit;
    }
    else
    {
        $db->rollback();
        $action='create';
        $_GET["origin"]=$_POST["origin"];
        $_GET["originid"]=$_POST["originid"];
        if (! $mesg) $mesg='<div class="error">'.$object->error.'</div>';
    }
}

// Add a new line
if (($action == 'addline' || $action == 'addline_predef') && $user->rights->facture->creer)
{
    $result=0;

    if ($_POST['np_price'] < 0 && $_POST["qty"] < 0)
    {
    	$langs->load("errors");
    	$mesg='<div class="error">'.$langs->trans("ErrorBothFieldCantBeNegative",$langs->transnoentitiesnoconv("UnitPriceHT"),$langs->transnoentitiesnoconv("Qty")).'</div>';
    	$result = -1 ;
    }
    if (empty($_POST['idprod']) && $_POST["type"] < 0)
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")).'</div>';
        $result = -1 ;
    }
    if (empty($_POST['idprod']) && (! isset($_POST["np_price"]) || $_POST["np_price"]==''))	// Unit price can be 0 but not ''
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("UnitPriceHT")).'</div>';
        $result = -1 ;
    }
    if (empty($_POST['idprod']) && empty($_POST["np_desc"]) && empty($_POST["dp_desc"]))
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Description")).'</div>';
        $result = -1 ;
    }
    if (! isset($_POST['qty']) || $_POST['qty']=='')
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv('Qty')).'</div>';
        $result = -1 ;
    }
    if ($result >= 0 && ( ($_POST['np_price']!='' && ($_POST['np_desc'] || $_POST['dp_desc'])) || $_POST['idprod'] ) )
    {
        $ret=$object->fetch($id);
        if ($ret < 0)
        {
            dol_print_error($db,$object->error);
            exit;
        }
        $ret=$object->fetch_thirdparty();

        $suffixe = $_POST['idprod'] ? '_predef' : '';
        $date_start=dol_mktime($_POST['date_start'.$suffixe.'hour'],$_POST['date_start'.$suffixe.'min'],$_POST['date_start'.$suffixe.'sec'],$_POST['date_start'.$suffixe.'month'],$_POST['date_start'.$suffixe.'day'],$_POST['date_start'.$suffixe.'year']);
        $date_end=dol_mktime($_POST['date_end'.$suffixe.'hour'],$_POST['date_end'.$suffixe.'min'],$_POST['date_end'.$suffixe.'sec'],$_POST['date_end'.$suffixe.'month'],$_POST['date_end'.$suffixe.'day'],$_POST['date_end'.$suffixe.'year']);

        $price_base_type = 'HT';

        // Ecrase $pu par celui du produit
        // Ecrase $desc par celui du produit
        // Ecrase $txtva par celui du produit
        // Ecrase $base_price_type par celui du produit
        if ($_POST['idprod'])
        {
            $prod = new Product($db);
            $prod->fetch($_POST['idprod']);

            $tva_tx = get_default_tva($mysoc,$object->client,$prod->id);
            $tva_npr = get_default_npr($mysoc,$object->client,$prod->id);

            // We define price for product
            if ($conf->global->PRODUIT_MULTIPRICES && $object->client->price_level)
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
            
            // Define output language
			if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_DESC_IN_THIRDPARTY_LANGUAGE))
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

            $desc.= ($desc && $_POST['np_desc']) ? ((dol_textishtml($desc) || dol_textishtml($_POST['np_desc']))?"<br>\n":"\n") : "";
            $desc.= $_POST['np_desc'];
            if (! empty($prod->customcode) || ! empty($prod->country_code))
            {
                $tmptxt='(';
                if (! empty($prod->customcode)) $tmptxt.=$langs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
                if (! empty($prod->customcode) && ! empty($prod->country_code)) $tmptxt.=' - ';
                if (! empty($prod->country_code)) $tmptxt.=$langs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code,0,$db,$langs,0);
                $tmptxt.=')';
                $desc.= (dol_textishtml($desc)?"<br>\n":"\n").$tmptxt;
            }
            $type = $prod->type;
        }
        else
        {
            $pu_ht=$_POST['np_price'];
            $tva_tx=str_replace('*','',$_POST['np_tva_tx']);
            $tva_npr=preg_match('/\*/',$_POST['np_tva_tx'])?1:0;
            $desc=$_POST['dp_desc'];
            $type=$_POST["type"];
        }

        $localtax1_tx=get_localtax($tva_tx,1,$object->client);
        $localtax2_tx=get_localtax($tva_tx,2,$object->client);

        $info_bits=0;
        if ($tva_npr) $info_bits |= 0x01;

        if ($result >= 0)
        {
            if($price_min && (price2num($pu_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($price_min)))
            {
                $object->error = $langs->trans("CantBeLessThanMinPrice",price2num($price_min,'MU').' '.$langs->trans("Currency".$conf->currency));
                $result = -1 ;
            }
            else
            {
            	// Insert line
                $result = $object->addline(
                    $id,
                    $desc,
                    $pu_ht,
                    $_POST['qty'],
                    $tva_tx,
                    $localtax1_tx,
                    $localtax2_tx,
                    $_POST['idprod'],
                    $_POST['remise_percent'],
                    $date_start,
                    $date_end,
                    0,
                    $info_bits,
    				'',
                    $price_base_type,
                    $pu_ttc,
                    $type,
                    -1,
                    0,
                    '',
                    0,
                    GETPOST('fk_parent_line')
                );
            }
        }
    }

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
            facture_pdf_create($db, $object, '', $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
        }
        unset($_POST['qty']);
        unset($_POST['type']);
        unset($_POST['idprod']);
        unset($_POST['remmise_percent']);
        unset($_POST['dp_desc']);
        unset($_POST['np_desc']);
        unset($_POST['np_price']);
        unset($_POST['np_tva_tx']);
    }
    else
    {
        if (empty($mesg)) $mesg='<div class="error">'.$object->error.'</div>';
    }

    $action='';
}

if ($action == 'updateligne' && $user->rights->facture->creer && $_POST['save'] == $langs->trans('Save'))
{
    if (! $object->fetch($id) > 0) dol_print_error($db);
    $object->fetch_thirdparty();

    // Clean parameters
    $date_start='';
    $date_end='';
    $date_start=dol_mktime($_POST['date_start'.$suffixe.'hour'],$_POST['date_start'.$suffixe.'min'],$_POST['date_start'.$suffixe.'sec'],$_POST['date_start'.$suffixe.'month'],$_POST['date_start'.$suffixe.'day'],$_POST['date_start'.$suffixe.'year']);
    $date_end=dol_mktime($_POST['date_end'.$suffixe.'hour'],$_POST['date_end'.$suffixe.'min'],$_POST['date_end'.$suffixe.'sec'],$_POST['date_end'.$suffixe.'month'],$_POST['date_end'.$suffixe.'day'],$_POST['date_end'.$suffixe.'year']);
    $description=dol_htmlcleanlastbr($_POST['desc']);
    $up_ht=GETPOST('pu')?GETPOST('pu'):GETPOST('subprice');

    // Define info_bits
    $info_bits=0;
    if (preg_match('/\*/',$_POST['tva_tx'])) $info_bits |= 0x01;

    // Define vat_rate
    $vat_rate=$_POST['tva_tx'];
    $vat_rate=str_replace('*','',$vat_rate);
    $localtax1_rate=get_localtax($vat_rate,1,$object->client);
    $localtax2_rate=get_localtax($vat_rate,2,$object->client);

    // Check parameters
    if (! GETPOST('productid') && GETPOST("type") < 0)
    {
        $mesg = '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")).'</div>';
        $result = -1 ;
    }
    // Check minimum price
    if (GETPOST('productid'))
    {
        $productid = GETPOST('productid');
        $product = new Product($db);
        $product->fetch($productid);
        $type=$product->type;
        $price_min = $product->price_min;
        if ($conf->global->PRODUIT_MULTIPRICES && $object->client->price_level)	$price_min = $product->multiprices_min[$object->client->price_level];
    }
    if ($object->type!=2 && $price_min && GETPOST('productid') && (price2num($up_ht)*(1-price2num(GETPOST('remise_percent'))/100) < price2num($price_min)))
    {
        //print "CantBeLessThanMinPrice ".$up_ht." - ".GETPOST('remise_percent')." - ".$product->price_min;
        $mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($price_min,'MU').' '.$langs->trans("Currency".$conf->currency)).'</div>';
        $result=-1;
    }

    // Define params
    if (GETPOST('productid')) $type=$product->type;
    else $type=GETPOST("type");

    // Update line
    if ($result >= 0)
    {
        $result = $object->updateline(
            GETPOST('lineid'),
            $description,
            $up_ht,
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
            GETPOST('fk_parent_line')
        );

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
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
        {
            $ret=$object->fetch($id);    // Reload to get new records
            facture_pdf_create($db, $object, '', $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
        }
    }
}

if ($action == 'updateligne' && $user->rights->facture->creer && $_POST['cancel'] == $langs->trans('Cancel'))
{
    Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);   // Pour reaffichage de la fiche en cours d'edition
    exit;
}


// Modify line position (up)
if ($action == 'up' && $user->rights->facture->creer)
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
    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) facture_pdf_create($db, $object, '', $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);

    Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id.'#'.$_GET['rowid']);
    exit;
}
// Modify line position (down)
if ($action == 'down' && $user->rights->facture->creer)
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
    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) facture_pdf_create($db, $object, '', $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);

    Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id.'#'.$_GET['rowid']);
    exit;
}

/*
 * Add file in email form
 */
if ($_POST['addfile'])
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    // Set tmp user directory
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

    $mesg=dol_add_file_process($upload_dir_tmp,0,0);

    $action='presend';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']))
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    // Set tmp user directory
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

	// TODO Delete only files that was uploaded from email form
    $mesg=dol_remove_file_process($_POST['removedfile'],0);

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
        $ref = dol_sanitizeFileName($object->ref);
        $file = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';

        if (is_readable($file))
        {
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
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
                $formmail = new FormMail($db);

                $attachedfiles=$formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Send mail
                require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
                $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt,-1);
                if ($mailfile->error)
                {
                    $mesg='<div class="error">'.$mailfile->error.'</div>';
                }
                else
                {
                    $result=$mailfile->sendfile();
                    if ($result)
                    {
                        $mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));		// Must not contain "

                        $error=0;

                        // Initialisation donnees
                        $object->sendtoid		= $sendtoid;
                        $object->actiontypecode	= $actiontypecode;
                        $object->actionmsg		= $actionmsg;  // Long text
                        $object->actionmsg2		= $actionmsg2; // Short text
                        $object->fk_element		= $object->id;
                        $object->elementtype	= $object->element;

                        // Appel des triggers
                        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('BILL_SENTBYMAIL',$object,$user,$langs,$conf);
                        if ($result < 0) { $error++; $this->errors=$interface->errors; }
                        // Fin appel triggers

                        if ($error)
                        {
                            dol_print_error($db);
                        }
                        else
                        {
                            // Redirect here
                            // This avoid sending mail twice if going out and then back to page
                            $_SESSION['message'] = $mesg;
                            Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&mesg=1');
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
                dol_syslog('Recipient email is empty');
            }
        }
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
            dol_syslog('Failed to read file: '.$file);
        }
    }
    else
    {
        $langs->load("other");
        $mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")).'</div>';
        dol_syslog('Impossible de lire les donnees de la facture. Le fichier facture n\'a peut-etre pas ete genere.');
    }

    $action = 'presend';
}

/*
 * Generate document
 */
if (GETPOST('action') == 'builddoc')	// En get ou en post
{
    $object->fetch($id);
    $object->fetch_thirdparty();

    if (GETPOST('model'))
    {
        $object->setDocModel($user, GETPOST('model'));
    }

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
    $result=facture_pdf_create($db, $object, '', $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
    if ($result <= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
    else
    {
        Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
        exit;
    }
}



/*
 * View
 */

llxHeader('',$langs->trans('Bill'),'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');

$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$now=dol_now();


/*********************************************************************
 *
 * Mode creation
 *
 **********************************************************************/
if ($action == 'create')
{
    $facturestatic=new Facture($db);

    print_fiche_titre($langs->trans('NewBill'));

    dol_htmloutput_mesg($mesg);
    dol_htmloutput_errors('',$errors);

    $soc = new Societe($db);
    if ($socid) $res=$soc->fetch($socid);

    if (GETPOST('origin') && GETPOST('originid'))
    {
        // Parse element/subelement (ex: project_task)
        $element = $subelement = GETPOST('origin');
        if (preg_match('/^([^_]+)_([^_]+)/i',GETPOST('origin'),$regs))
        {
            $element = $regs[1];
            $subelement = $regs[2];
        }

        if ($element == 'project')
        {
            $projectid=GETPOST('originid');
        }
        else
        {
            // For compatibility
            if ($element == 'order' || $element == 'commande')    { $element = $subelement = 'commande'; }
            if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }
            if ($element == 'contract') { $element = $subelement = 'contrat'; }
            if ($element == 'shipping') { $element = $subelement = 'expedition'; }

            dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

            $classname = ucfirst($subelement);
            $objectsrc = new $classname($db);
            $objectsrc->fetch(GETPOST('originid'));
            if (empty($objectsrc->lines) && method_exists($objectsrc,'fetch_lines'))  $objectsrc->fetch_lines();
            $objectsrc->fetch_thirdparty();

            $projectid			= (!empty($objectsrc->fk_project)?$objectsrc->fk_project:'');
            $ref_client			= (!empty($objectsrc->ref_client)?$objectsrc->ref_client:'');
            $ref_int			= (!empty($objectsrc->ref_int)?$objectsrc->ref_int:'');

            $soc = $objectsrc->client;
            $cond_reglement_id 	= (!empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(!empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
            $mode_reglement_id 	= (!empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(!empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
            $remise_percent 	= (!empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
            $remise_absolue 	= (!empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
            $dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
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


    if ($conf->use_javascript_ajax)
    {
        print ajax_combobox('fac_replacement');
        print ajax_combobox('fac_avoir');
    }

    print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
    print '<input name="facnumber" type="hidden" value="provisoire">';
    print '<input name="ref_client" type="hidden" value="'.$ref_client.'">';
    print '<input name="ref_int" type="hidden" value="'.$ref_int.'">';
    print '<input type="hidden" name="origin" value="'.GETPOST('origin').'">';
    print '<input type="hidden" name="originid" value="'.GETPOST('originid').'">';

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans('Draft').'</td></tr>';

    // Factures predefinies
    if (empty($_GET['propalid']) && empty($_GET['commandeid']) && empty($_GET['contratid']) && empty($_GET['originid']))
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
                    if ($_POST["fac_rec"] == $objp->rowid) print ' selected="selected"';
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

    // Tiers
    print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td><td colspan="2">';
    print $soc->getNomUrl(1);
    print '<input type="hidden" name="socid" value="'.$soc->id.'">';
    print '</td>';
    print '</tr>'."\n";

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
    foreach ($facids as $key => $value)
    {
        $newinvoice=new Facture($db);
        $newinvoice->fetch($key);
        $optionsav.='<option value="'.$key.'"';
        if ($key == $_POST['fac_avoir']) $optionsav.=' selected="selected"';
        $optionsav.='>';
        $optionsav.=$newinvoice->ref;
        $optionsav.=' ('.$newinvoice->getLibStatut(1,$value).')';
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

    // Deposit
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="3"'.(GETPOST('type')==3?' checked="checked"':'').'>';
    print '</td><td valign="middle">';
    $desc=$form->textwithpicto($langs->trans("InvoiceDeposit"),$langs->transnoentities("InvoiceDepositDesc"),1);
    print $desc;
    print '</td></tr>'."\n";

    // Proforma
    if ($conf->global->FACTURE_USE_PROFORMAT)
    {
        print '<tr height="18"><td width="16px" valign="middle">';
        print '<input type="radio" name="type" value="4"'.(GETPOST('type')==4?' checked="checked"':'').'>';
        print '</td><td valign="middle">';
        $desc=$form->textwithpicto($langs->trans("InvoiceProForma"),$langs->transnoentities("InvoiceProFormaDesc"),1);
        print $desc;
        print '</td></tr>'."\n";
    }

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
    //.' ('.$langs->trans("FeatureNotYetAvailable").')',$langs->transnoentities("InvoiceAvoirDesc"),1);
    print $desc;
    print '</td></tr>'."\n";

    print '</table>';
    print '</td></tr>';

    // Discounts for third party
    print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
    if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",'<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">'.$soc->remise_client.'</a>');
    else print $langs->trans("CompanyHasNoRelativeDiscount");
    print ' <a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">('.$langs->trans("EditRelativeDiscount").')</a>';
    print '. ';
    print '<br>';
    if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",'<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">'.price($absolute_discount).'</a>',$langs->trans("Currency".$conf->currency));
    else print $langs->trans("CompanyHasNoAbsoluteDiscount");
    print ' <a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">('.$langs->trans("EditGlobalDiscounts").')</a>';
    print '.';
    print '</td></tr>';

    // Date invoice
    print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
    $form->select_date($dateinvoice,'','','','',"add",1,1);
    print '</td></tr>';

    // Payment term
    print '<tr><td nowrap>'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
    $form->select_conditions_paiements(isset($_POST['cond_reglement_id'])?$_POST['cond_reglement_id']:$cond_reglement_id,'cond_reglement_id');
    print '</td></tr>';

    // Payment mode
    print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
    $form->select_types_paiements(isset($_POST['mode_reglement_id'])?$_POST['mode_reglement_id']:$mode_reglement_id,'mode_reglement_id');
    print '</td></tr>';

    // Project
    if ($conf->projet->enabled)
    {
        $langs->load('projects');
        print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
        select_projects($soc->id, $projectid, 'projectid');
        print '</td></tr>';
    }

    // Insert hooks
    $parameters=array();
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

    // Modele PDF
    print '<tr><td>'.$langs->trans('Model').'</td>';
    print '<td>';
    include_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
    $liste=ModelePDFFactures::liste_modeles($db);
    print $form->selectarray('model',$liste,$conf->global->FACTURE_ADDON_PDF);
    print "</td></tr>";

    // Public note
    print '<tr>';
    print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
    print '<td valign="top" colspan="2">';
    print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';
    if (is_object($objectsrc))    // Take value from source object
    {
        print $objectsrc->note_public;
    }
    print '</textarea></td></tr>';

    // Private note
    if (! $user->societe_id)
    {
        print '<tr>';
        print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
        print '<td valign="top" colspan="2">';
        print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">';
        if (is_object($objectsrc))    // Take value from source object
        {
            print $objectsrc->note;
        }
        print '</textarea></td></tr>';
    }

    if (is_object($objectsrc))
    {
        // TODO for compatibility
        if ($_GET['origin'] == 'contrat')
        {
            // Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
            $objectsrc->remise_absolue=$remise_absolue;
            $objectsrc->remise_percent=$remise_percent;
            $objectsrc->update_price(1);
        }

        print "\n<!-- ".$classname." info -->";
        print "\n";
        print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
        print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
        print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
        print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
        print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

        $newclassname=$classname;
        if ($newclassname=='Propal') $newclassname='CommercialProposal';
        print '<tr><td>'.$langs->trans($newclassname).'</td><td colspan="2">'.$objectsrc->getNomUrl(1).'</td></tr>';
        print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($objectsrc->total_ht).'</td></tr>';
        print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($objectsrc->total_tva)."</td></tr>";
        if ($mysoc->pays_code=='ES')
        {
            if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
            {
                print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->pays_code).'</td><td colspan="2">'.price($objectsrc->total_localtax1)."</td></tr>";
            }

            if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
            {
                print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->pays_code).'</td><td colspan="2">'.price($objectsrc->total_localtax2)."</td></tr>";
            }
        }
        print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($objectsrc->total_ttc)."</td></tr>";
    }
    else
    {
        // Show deprecated optional form to add product line here
        if ($conf->global->PRODUCT_SHOW_WHEN_CREATE)
        {
            print '<tr><td colspan="3">';

            // Zone de choix des produits predefinis a la creation
            print '<table class="noborder" width="100%">';
            print '<tr>';
            print '<td>'.$langs->trans('ProductsAndServices').'</td>';
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
                if($conf->global->PRODUIT_MULTIPRICES)
                $form->select_produits('','idprod'.$i,'',$conf->product->limit_size,$soc->price_level);
                else
                $form->select_produits('','idprod'.$i,'',$conf->product->limit_size);
                print '</td>';
                print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td>';
                print '<td nowrap="nowrap"><input type="text" size="1" name="remise_percent'.$i.'" value="'.$soc->remise_client.'">%</td>';
                print '<td>&nbsp;</td>';
                // Si le module service est actif, on propose des dates de debut et fin a la ligne
                if ($conf->service->enabled)
                {
                    print '<td nowrap="nowrap">';
                    print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                    print '<td class="nobordernopadding" nowrap="nowrap">';
                    print $langs->trans('From').' ';
                    print '</td><td class="nobordernopadding" nowrap="nowrap">';
                    print $form->select_date('','date_start'.$i,$usehm,$usehm,1,"add");
                    print '</td></tr>';
                    print '<td class="nobordernopadding" nowrap="nowrap">';
                    print $langs->trans('to').' ';
                    print '</td><td class="nobordernopadding" nowrap="nowrap">';
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
    if (is_object($objectsrc))
    {
        print '<br>';

        $title=$langs->trans('ProductsAndServices');
        print_titre($title);

        print '<table class="noborder" width="100%">';

        $objectsrc->printOriginLinesList($hookmanager);

        print '</table>';
    }

}
else
{
    /*
     * Show object in view mode
     */
    if ($id > 0 || ! empty($ref))
    {
        dol_htmloutput_mesg($mesg);
        dol_htmloutput_errors('',$errors);

        $result=$object->fetch($id,$ref);
        if ($result > 0)
        {
            if ($user->societe_id>0 && $user->societe_id!=$object->socid)  accessforbidden('',0);

            $result=$object->fetch_thirdparty();

            $soc = new Societe($db);
            $soc->fetch($object->socid);

            $totalpaye  = $object->getSommePaiement();
            $totalcreditnotes = $object->getSumCreditNotesUsed();
            $totaldeposits = $object->getSumDepositsUsed();
            //print "totalpaye=".$totalpaye." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits;

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
                $text=$langs->trans('ConfirmDeleteBill');
                $formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id,$langs->trans('DeleteBill'),$text,'confirm_delete','',0,1);
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
                if ($conf->notification->enabled)
                {
                    require_once(DOL_DOCUMENT_ROOT ."/core/class/notify.class.php");
                    $notify=new Notify($db);
                    $text.='<br>';
                    $text.=$notify->confirmMessage('NOTIFY_VAL_FAC',$object->socid);
                }
                $formquestion=array();
                if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->hasProductsOrServices(1))
                {
                    $langs->load("stocks");
                    require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
                    $formproduct=new FormProduct($db);
                    $formquestion=array(
                    //'text' => $langs->trans("ConfirmClone"),
                    //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
                    //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
                    array('type' => 'other', 'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"),   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1)));
                }

                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('ValidateBill'),$text,'confirm_valid',$formquestion,"yes",($conf->notification->enabled?0:2));
            }

            // Confirm back to draft status
            if ($action == 'modif')
            {
                $text=$langs->trans('ConfirmUnvalidateBill',$object->ref);
                $formquestion=array();
                if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->hasProductsOrServices(1))
                {
                    $langs->load("stocks");
                    require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
                    $formproduct=new FormProduct($db);
                    $formquestion=array(
                    //'text' => $langs->trans("ConfirmClone"),
                    //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
                    //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
                    array('type' => 'other', 'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockIncrease"),   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1)));
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
                $formconfirm=$hookmanager->executeHooks('formconfirm',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            }

            // Print form confirm
            print $formconfirm;


            // Invoice content

            print '<table class="border" width="100%">';

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
            print $form->showrefnav($object,'ref','',1,'facnumber','ref',$morehtmlref);
            print '</td></tr>';

            // Third party
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%">';
            print '<tr><td>'.$langs->trans('Company').'</td>';
            print '</td><td colspan="5">';
            if ($conf->global->FACTURE_CHANGE_THIRDPARTY && $action != 'editthirdparty' && $object->brouillon && $user->rights->facture->creer)
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
                print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$object->socid.'">'.$langs->trans('OtherBills').'</a>)';
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
            if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
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
                    else print '.';
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
                    $form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filtercreditnote, $resteapayer);
                }
            }
            if (! $absolute_discount && ! $absolute_creditnote)
            {
                print $langs->trans("CompanyHasNoAbsoluteDiscount");
                if ($object->statut == 0 && $object->type != 2 && $object->type != 3) print ' ('.$addabsolutediscount.')<br>';
                else print '. ';
            }
            /*if ($object->statut == 0 && $object->type != 2 && $object->type != 3)
             {
             if (! $absolute_discount && ! $absolute_creditnote) print '<br>';
             //print ' &nbsp; - &nbsp; ';
             print $addabsolutediscount;
             //print ' &nbsp; - &nbsp; '.$addcreditnote;      // We disbale link to credit note
             }*/
            print '</td></tr>';

            // Date invoice
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('Date');
            print '</td>';
            if ($object->type != 2 && $action != 'editinvoicedate' && $object->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editinvoicedate&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
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


            /*
             * List of payments
             */

            $nbrows=8;
            if ($conf->projet->enabled) $nbrows++;

            //Local taxes
            if ($mysoc->pays_code=='ES')
            {
                if($mysoc->localtax1_assuj=="1") $nbrows++;
                if($mysoc->localtax2_assuj=="1") $nbrows++;
            }

            print '<td rowspan="'.$nbrows.'" colspan="2" valign="top">';

            print '<table class="nobordernopadding" width="100%">';

            // List of payments already done
            print '<tr class="liste_titre">';
            print '<td>'.($object->type == 2 ? $langs->trans("PaymentsBack") : $langs->trans('Payments')).'</td>';
            print '<td>'.$langs->trans('Type').'</td>';
            print '<td align="right">'.$langs->trans('Amount').'</td>';
            print '<td width="18">&nbsp;</td>';
            print '</tr>';

            $var=true;

            // Payments already done (from payment on this invoice)
            $sql = 'SELECT p.datep as dp, p.num_paiement, p.rowid,';
            $sql.= ' c.code as payment_code, c.libelle as payment_label,';
            $sql.= ' pf.amount';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement_facture as pf';
            $sql.= ' WHERE pf.fk_facture = '.$object->id.' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
            $sql.= ' ORDER BY dp, tms';

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);
                $i = 0;

                if ($object->type != 2)
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
                        print '<td align="right">'.price($objp->amount).'</td>';
                        print '<td>&nbsp;</td>';
                        print '</tr>';
                        $i++;
                    }
                }
                $db->free($result);
            }
            else
            {
                dol_print_error($db);
            }

            if ($object->type != 2)
            {
                // Total already paid
                print '<tr><td colspan="2" align="right">';
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
                        print '<tr><td colspan="2" align="right">';
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
                    print '<tr><td colspan="2" align="right" nowrap="1">';
                    print $form->textwithpicto($langs->trans("Escompte").':',$langs->trans("HelpEscompte"),-1);
                    print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
                    $resteapayeraffiche=0;
                }
                // Paye partiellement ou Abandon 'badcustomer'
                if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'badcustomer')
                {
                    print '<tr><td colspan="2" align="right" nowrap="1">';
                    print $form->textwithpicto($langs->trans("Abandoned").':',$langs->trans("HelpAbandonBadCustomer"),-1);
                    print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
                    //$resteapayeraffiche=0;
                }
                // Paye partiellement ou Abandon 'product_returned'
                if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'product_returned')
                {
                    print '<tr><td colspan="2" align="right" nowrap="1">';
                    print $form->textwithpicto($langs->trans("ProductReturned").':',$langs->trans("HelpAbandonProductReturned"),-1);
                    print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
                    $resteapayeraffiche=0;
                }
                // Paye partiellement ou Abandon 'abandon'
                if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'abandon')
                {
                    print '<tr><td colspan="2" align="right" nowrap="1">';
                    $text=$langs->trans("HelpAbandonOther");
                    if ($object->close_note) $text.='<br><br><b>'.$langs->trans("Reason").'</b>:'.$object->close_note;
                    print $form->textwithpicto($langs->trans("Abandoned").':',$text,-1);
                    print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
                    $resteapayeraffiche=0;
                }

                // Billed
                print '<tr><td colspan="2" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($object->total_ttc).'</td><td>&nbsp;</td></tr>';

                // Remainder to pay
                print '<tr><td colspan="2" align="right">';
                if ($resteapayeraffiche >= 0) print $langs->trans('RemainderToPay');
                else print $langs->trans('ExcessReceived');
                print ' :</td>';
                print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayeraffiche).'</b></td>';
                print '<td nowrap="nowrap">&nbsp;</td></tr>';
            }
            else
            {
                // Sold credit note
                print '<tr><td colspan="2" align="right">'.$langs->trans('TotalTTC').' :</td>';
                print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price(abs($object->total_ttc)).'</b></td><td>&nbsp;</td></tr>';
            }

            print '</table>';

            print '</td></tr>';

            // Date payment term
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('DateMaxPayment');
            print '</td>';
            if ($object->type != 2 && $action != 'editpaymentterm' && $object->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editpaymentterm&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
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
                    if ($object->date_lim_reglement < ($now - $conf->facture->client->warning_delay) && ! $object->paye && $object->statut == 1 && ! $object->am) print img_warning($langs->trans('Late'));
                }
            }
            else
            {
                print '&nbsp;';
            }
            print '</td></tr>';

            // Conditions de reglement
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('PaymentConditionsShort');
            print '</td>';
            if ($object->type != 2 && $action != 'editconditions' && $object->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
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

            // Mode de reglement
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('PaymentMode');
            print '</td>';
            if ($action != 'editmode' && $object->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
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

            // Amount
            print '<tr><td>'.$langs->trans('AmountHT').'</td>';
            print '<td align="right" colspan="2" nowrap>'.price($object->total_ht).'</td>';
            print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';
            print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2" nowrap>'.price($object->total_tva).'</td>';
            print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

            // Amount Local Taxes
            if ($mysoc->pays_code=='ES')
            {
                if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
                {
                    print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->pays_code).'</td>';
                    print '<td align="right" colspan="2" nowrap>'.price($object->total_localtax1).'</td>';
                    print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
                }
                if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
                {
                    print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->pays_code).'</td>';
                    print '<td align="right" colspan="2" nowrap>'.price($object->total_localtax2).'</td>';
                    print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
                }
            }

            print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($object->total_ttc).'</td>';
            print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

            // Statut
            print '<tr><td>'.$langs->trans('Status').'</td>';
            print '<td align="left" colspan="3">'.($object->getLibStatut(4,$totalpaye)).'</td></tr>';

            // Project
            if ($conf->projet->enabled)
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

            // Insert hooks
            $parameters=array('colspan'=>' colspan="3"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

            print '</table><br>';


            /*
             * Lines
             */
            $result = $object->getLinesArray();

            if ($conf->use_javascript_ajax && $object->statut == 0)
            {
                include(DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php');
            }

            print '<table id="tablelines" class="noborder" width="100%">';

            // Show object lines
            if (! empty($object->lines)) $object->printObjectLines($action,$mysoc,$soc,$lineid,1,$hookmanager);

            /*
             * Form to add new line
             */
            if ($object->statut == 0 && $user->rights->facture->creer && $action <> 'valid' && $action <> 'editline')
            {
                $var=true;

                $object->formAddFreeProduct(1,$mysoc,$soc,$hookmanager);

                // Add predefined products/services
                if ($conf->product->enabled || $conf->service->enabled)
                {
                    $var=!$var;
                    $object->formAddPredefinedProduct(1,$mysoc,$soc,$hookmanager);
                }

                $parameters=array();
                $reshook=$hookmanager->executeHooks('formAddObject',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            }

            print "</table>\n";

            print "</div>\n";


            /*
             * Boutons actions
             */

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
                                    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
                                }
                                else
                                {
                                    print '<span class="butActionRefused" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('Modify').'</span>';
                                }
                            }
                            else
                            {
                                print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('Modify').'</span>';
                            }
                        }
                    }

                    // Reopen a standard paid invoice
                    if (($object->type == 0 || $object->type == 1) && ($object->statut == 2 || $object->statut == 3))				// A paid invoice (partially or completely)
                    {
                        if (! $objectidnext && $object->close_code != 'replaced')	// Not replaced by another invoice
                        {
                            print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=reopen">'.$langs->trans('ReOpen').'</a>';
                        }
                        else
                        {
                            print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('ReOpen').'</span>';
                        }
                    }

                    // Validate
                    if ($object->statut == 0 && count($object->lines) > 0 &&
                    (
                    (($object->type == 0 || $object->type == 1 || $object->type == 3 || $object->type == 4) && $object->total_ttc >= 0)
                    || ($object->type == 2 && $object->total_ttc <= 0))
                    )
                    {
                        if ($user->rights->facture->valider)
                        {
                            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=valid"';
                            print '>'.$langs->trans('Validate').'</a>';
                        }
                    }

                    // Send by mail
                    if (($object->statut == 1 || $object->statut == 2))
                    {
                        if ($objectidnext)
                        {
                            print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('SendByMail').'</span>';
                        }
                        else
                        {
                            if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send)
                            {
                                print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
                            }
                            else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
                        }
                    }

                    if ($conf->global->FACTURE_SHOW_SEND_REMINDER)	// For backward compatibility
                    {
                        if (($object->statut == 1 || $object->statut == 2) && $resteapayer > 0)
                        {
                            if ($objectidnext)
                            {
                                print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('SendRemindByMail').'</span>';
                            }
                            else
                            {
                                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send)
                                {
                                    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=prerelance&amp;mode=init">'.$langs->trans('SendRemindByMail').'</a>';
                                }
                                else print '<a class="butActionRefused" href="#">'.$langs->trans('SendRemindByMail').'</a>';
                            }
                        }
                    }

                    // Create payment
                    if ($object->type != 2 && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement)
                    {
                        if ($objectidnext)
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
                                print '<a class="butAction" href="paiement.php?facid='.$object->id.'&amp;action=create">'.$langs->trans('DoPayment').'</a>';
                            }
                        }
                    }

                    // Reverse back money or convert to reduction
                    if ($object->type == 2 || $object->type == 3)
                    {
                        // For credit note only
                        if ($object->type == 2 && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement)
                        {
                            print '<a class="butAction" href="paiement.php?facid='.$object->id.'&amp;action=create">'.$langs->trans('DoPaymentBack').'</a>';
                        }
                        // For credit note
                        if ($object->type == 2 && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->creer && $object->getSommePaiement() == 0)
                        {
                            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=converttoreduc">'.$langs->trans('ConvertToReduc').'</a>';
                        }
                        // For deposit invoice
                        if ($object->type == 3 && $object->statut == 1 && $resteapayer == 0 && $user->rights->facture->creer)
                        {
                            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=converttoreduc">'.$langs->trans('ConvertToReduc').'</a>';
                        }
                    }

                    // Classify paid (if not deposit and not credit note. Such invoice are "converted")
                    if ($object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement &&
                    (($object->type != 2 && $object->type != 3 && $resteapayer <= 0) || ($object->type == 2 && $resteapayer >= 0)) )
                    {
                        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaid').'</a>';
                    }

                    // Classify 'closed not completely paid' (possible si validee et pas encore classee payee)
                    if ($object->statut == 1 && $object->paye == 0 && $resteapayer > 0
                    && $user->rights->facture->paiement)
                    {
                        if ($totalpaye > 0 || $totalcreditnotes > 0)
                        {
                            // If one payment or one credit note was linked to this invoice
                            print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaidPartially').'</a>';
                        }
                        else
                        {
                            if ($objectidnext)
                            {
                                print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('ClassifyCanceled').'</span>';
                            }
                            else
                            {
                                print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=canceled">'.$langs->trans('ClassifyCanceled').'</a>';
                            }
                        }
                    }

                    // Clone
                    if (($object->type == 0 || $object->type == 3 || $object->type == 4) && $user->rights->facture->creer)
                    {
                        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=clone&amp;object=invoice">'.$langs->trans("ToClone").'</a>';
                    }

                    // Clone as predefined
                    if (($object->type == 0 || $object->type == 3 || $object->type == 4) && $object->statut == 0 && $user->rights->facture->creer)
                    {
                        if (! $objectidnext)
                        {
                            print '<a class="butAction" href="facture/fiche-rec.php?facid='.$object->id.'&amp;action=create">'.$langs->trans("ChangeIntoRepeatableInvoice").'</a>';
                        }
                    }

                    // Delete
                    if ($user->rights->facture->supprimer)
                    {
                        if (! $object->is_erasable())
                        {
                            print '<a class="butActionRefused" href="#" title="'.$langs->trans("DisabledBecauseNotErasable").'">'.$langs->trans('Delete').'</a>';
                        }
                        else if ($objectidnext)
                        {
                            print '<a class="butActionRefused" href="#" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('Delete').'</a>';
                        }
                        elseif ($object->getSommePaiement())
                        {
                            print '<a class="butActionRefused" href="#" title="'.$langs->trans("DisabledBecausePayments").'">'.$langs->trans('Delete').'</a>';
                        }
                        else
                        {
                            print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
                        }
                    }
                    else
                    {
                        print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
                    }

                    print '</div>';
                }
            }


            if ($action != 'prerelance' && $action != 'presend')
            {
                print '<table width="100%"><tr><td width="50%" valign="top">';
                print '<a name="builddoc"></a>'; // ancre

                /*
                 * Documents generes
                 */
                $filename=dol_sanitizeFileName($object->ref);
                $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
                $urlsource=$_SERVER['PHP_SELF'].'?facid='.$object->id;
                $genallowed=$user->rights->facture->creer;
                $delallowed=$user->rights->facture->supprimer;

                print '<br>';
                print $formfile->showdocuments('facture',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang,$hookmanager);
                $somethingshown=$formfile->numoffiles;

                /*
                 * Linked object block
                 */
                $somethingshown=$object->showLinkedObjectBlock();

                // Link for paypal payment
                if ($conf->paypal->enabled && $object->statut != 0)
                {
                    include_once(DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php');
                    print showPaypalPaymentUrl('invoice',$object->ref);
                }

                print '</td><td valign="top" width="50%">';

                print '<br>';

                // List of actions on element
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
                $formactions=new FormActions($db);
                $somethingshown=$formactions->showactions($object,'invoice',$socid);

                print '</td></tr></table>';
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
                $file = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';

                // Construit PDF si non existant
                if (! is_readable($file))
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
                    $result=facture_pdf_create($db, $object, '', $_REQUEST['model'], $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
                    if ($result <= 0)
                    {
                        dol_print_error($db,$result);
                        exit;
                    }
                }

                print '<br>';
                print_titre($langs->trans($titreform));

                // Cree l'objet formulaire mail
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
                $formmail = new FormMail($db);
                $formmail->fromtype = 'user';
                $formmail->fromid   = $user->id;
                $formmail->fromname = $user->getFullName($langs);
                $formmail->frommail = $user->email;
                $formmail->withfrom=1;
                $formmail->withto=empty($_POST["sendto"])?1:$_POST["sendto"];
                $formmail->withtosocid=$soc->id;
                $formmail->withtocc=1;
                $formmail->withtoccsocid=0;
                $formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
                $formmail->withtocccsocid=0;
                $formmail->withtopic=$langs->transnoentities($topicmail,'__FACREF__');
                $formmail->withfile=2;
                $formmail->withbody=1;
                $formmail->withdeliveryreceipt=1;
                $formmail->withcancel=1;
                // Tableau des substitutions
                $formmail->substit['__FACREF__']=$object->ref;
                // Tableau des parametres complementaires du post
                $formmail->param['action']=$action;
                $formmail->param['models']=$modelmail;
                $formmail->param['facid']=$object->id;
                $formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

                // Init list of files
                if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
                {
                    $formmail->clear_attached_files();
                    $formmail->add_attached_files($file,dol_sanitizeFilename($ref.'.pdf'),'application/pdf');
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
    else
    {
        /***************************************************************************
         *                                                                         *
         *                      Mode Liste                                         *
         *                                                                         *
         ***************************************************************************/
        $now=dol_now();

        $sortfield = GETPOST("sortfield",'alpha');
        $sortorder = GETPOST("sortorder",'alpha');
        $page = GETPOST("page",'int');
        if ($page == -1) { $page = 0; }
        $offset = $conf->liste_limit * $page;
        if (! $sortorder) $sortorder='DESC';
        if (! $sortfield) $sortfield='f.datef';
        $limit = $conf->liste_limit;

        $pageprev = $page - 1;
        $pagenext = $page + 1;

        $day	= GETPOST('day','int');
        $month	= GETPOST('month','int');
        $year	= GETPOST('year','int');

        $facturestatic=new Facture($db);

        if (! $sall) $sql = 'SELECT';
        else $sql = 'SELECT DISTINCT';
        $sql.= ' f.rowid as facid, f.facnumber, f.type, f.increment, f.total, f.total_ttc,';
        $sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
        $sql.= ' f.paye as paye, f.fk_statut,';
        $sql.= ' s.nom, s.rowid as socid';
        if (! $sall) $sql.= ', SUM(pf.amount) as am';   // To be able to sort on status
        $sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
        if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= ', '.MAIN_DB_PREFIX.'facture as f';
        if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
        else $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid';
        $sql.= ' WHERE f.fk_soc = s.rowid';
        $sql.= " AND f.entity = ".$conf->entity;
        if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($socid) $sql.= ' AND s.rowid = '.$socid;
        if ($userid)
        {
            if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
            else $sql.=' AND f.fk_user_author = '.$user->id;
        }
        if ($_GET['filtre'])
        {
            $filtrearr = explode(',', $_GET['filtre']);
            foreach ($filtrearr as $fil)
            {
                $filt = explode(':', $fil);
                $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
            }
        }
        if ($search_ref)
        {
            $sql.= ' AND f.facnumber LIKE \'%'.$db->escape(trim($search_ref)).'%\'';
        }
        if ($search_societe)
        {
            $sql.= ' AND s.nom LIKE \'%'.$db->escape(trim($search_societe)).'%\'';
        }
        if ($search_montant_ht)
        {
            $sql.= ' AND f.total = \''.$db->escape(trim($search_montant_ht)).'\'';
        }
        if ($search_montant_ttc)
        {
            $sql.= ' AND f.total_ttc = \''.$db->escape(trim($search_montant_ttc)).'\'';
        }
        if ($month > 0)
        {
            if ($year > 0 && empty($day))
            $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
            else if ($year > 0 && ! empty($day))
            $sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
            else
            $sql.= " AND date_format(f.datef, '%m') = '".$month."'";
        }
        else if ($year > 0)
        {
            $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
        }
        if (! $sall)
        {
            $sql.= ' GROUP BY f.rowid, f.facnumber, f.type, f.increment, f.total, f.total_ttc,';
            $sql.= ' f.datef, f.date_lim_reglement,';
            $sql.= ' f.paye, f.fk_statut,';
            $sql.= ' s.nom, s.rowid';
        }
        else
        {
        	$sql.= ' AND (s.nom LIKE \'%'.$db->escape($sall).'%\' OR f.facnumber LIKE \'%'.$db->escape($sall).'%\' OR f.note LIKE \'%'.$db->escape($sall).'%\' OR fd.description LIKE \'%'.$db->escape($sall).'%\')';
        }
        $sql.= ' ORDER BY ';
        $listfield=explode(',',$sortfield);
        foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.$sortorder.',';
        $sql.= ' f.rowid DESC ';
        $sql.= $db->plimit($limit+1,$offset);
        //print $sql;

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);

            if ($socid)
            {
                $soc = new Societe($db);
                $soc->fetch($socid);
            }

            $param='&amp;socid='.$socid;
            if ($month) $param.='&amp;month='.$month;
            if ($year)  $param.='&amp;year=' .$year;

            print_barre_liste($langs->trans('BillsCustomers').' '.($socid?' '.$soc->nom:''),$page,'facture.php',$param,$sortfield,$sortorder,'',$num);

            $i = 0;
            print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
            print '<table class="liste" width="100%">';
            print '<tr class="liste_titre">';
            print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'f.facnumber','',$param,'',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'f.datef','',$param,'align="center"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("DateDue"),$_SERVER['PHP_SELF'],"f.date_lim_reglement","&amp;socid=$socid","",'align="center"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('Company'),$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'f.total','',$param,'align="right"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'am','',$param,'align="right"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye,am','',$param,'align="right"',$sortfield,$sortorder);
            //print '<td class="liste_titre">&nbsp;</td>';
            print '</tr>';

            // Filters lines
            print '<tr class="liste_titre">';
            print '<td class="liste_titre" align="left">';
            print '<input class="flat" size="10" type="text" name="search_ref" value="'.$search_ref.'">';
            print '</td>';
            print '<td class="liste_titre" align="center">';
            if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
            print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
            $htmlother->select_year($year?$year:-1,'year',1, 20, 5);
            print '</td>';
            print '<td class="liste_titre" align="left">&nbsp;</td>';
            print '<td class="liste_titre" align="left">';
            print '<input class="flat" type="text" name="search_societe" value="'.$search_societe.'">';
            print '</td><td class="liste_titre" align="right">';
            print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$search_montant_ht.'">';
            print '</td><td class="liste_titre" align="right">';
            print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$search_montant_ttc.'">';
            print '</td>';
            print '<td class="liste_titre" align="right">';
            print '&nbsp;';
            print '</td>';
            print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
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

                    $datelimit=$db->jdate($objp->datelimite);

                    print '<tr '.$bc[$var].'>';
                    print '<td nowrap="nowrap">';

                    $facturestatic->id=$objp->facid;
                    $facturestatic->ref=$objp->facnumber;
                    $facturestatic->type=$objp->type;
                    $paiement = $facturestatic->getSommePaiement();

                    print '<table class="nobordernopadding"><tr class="nocellnopadd">';

                    print '<td class="nobordernopadding" nowrap="nowrap">';
                    print $facturestatic->getNomUrl(1);
                    print $objp->increment;
                    print '</td>';

                    print '<td width="16" align="right" class="nobordernopadding">';
                    $filename=dol_sanitizeFileName($objp->facnumber);
                    $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->facnumber);
                    $urlsource=$_SERVER['PHP_SELF'].'?facid='.$objp->facid;
                    $formfile->show_documents('facture',$filename,$filedir,$urlsource,'','','',1,'',1);
                    print '</td>';
                    print '</tr></table>';

                    print "</td>\n";

                    // Date
                    print '<td align="center" nowrap>';
                    print dol_print_date($db->jdate($objp->df),'day');
                    print '</td>';

                    // Date limit
                    print '<td align="center" nowrap="1">'.dol_print_date($datelimit,'day');
                    if ($datelimit < ($now - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1 && ! $paiement)
                    {
                        print img_warning($langs->trans('Late'));
                    }
                    print '</td>';

                    print '<td>';
                    $thirdparty=new Societe($db);
                    $thirdparty->id=$objp->socid;
                    $thirdparty->nom=$objp->nom;
                    print $thirdparty->getNomUrl(1,'customer');
                    print '</td>';

                    print '<td align="right">'.price($objp->total).'</td>';

                    print '<td align="right">'.price($objp->total_ttc).'</td>';

                    print '<td align="right">'.price($paiement).'</td>';

                    // Affiche statut de la facture
                    print '<td align="right" nowrap="nowrap">';
                    print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$paiement,$objp->type);
                    print "</td>";
                    //print "<td>&nbsp;</td>";
                    print "</tr>\n";
                    $total+=$objp->total;
                    $total_ttc+=$objp->total_ttc;
                    $totalrecu+=$paiement;
                    $i++;
                }

                if (($offset + $num) <= $limit)
                {
                    // Print total
                    print '<tr class="liste_total">';
                    print '<td class="liste_total" colspan="4" align="left">'.$langs->trans('Total').'</td>';
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
            dol_print_error($db);
        }
    }
}

$db->close();

llxFooter();
?>
