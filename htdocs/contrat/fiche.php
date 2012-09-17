<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis@dolibarr.fr>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2010-2012	Juanjo Menent			<jmenent@2byte.es>
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
 *       \file       htdocs/contrat/fiche.php
 *       \ingroup    contrat
 *       \brief      Page of a contract
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
if (! empty($conf->produit->enabled) || ! empty($conf->service->enabled))  require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (! empty($conf->propal->enabled))  require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
}

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");
$langs->load("bills");
$langs->load("products");

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$socid = GETPOST('socid','int');
$id = GETPOST('id','int');
$ref=GETPOST('ref','alpha');

$datecontrat='';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'contrat',$id);

$usehm=(! empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE:0);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('contractcard'));

$object = new Contrat($db);


/*
 * Actions
 */

if ($action == 'confirm_active' && $confirm == 'yes' && $user->rights->contrat->activer)
{
    $object->fetch($id);
    $result = $object->active_line($user, GETPOST('ligne'), GETPOST('date'), GETPOST('dateend'), GETPOST('comment'));

    if ($result > 0)
    {
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
    else {
        $mesg=$object->error;
    }
}

else if ($action == 'confirm_closeline' && $confirm == 'yes' && $user->rights->contrat->activer)
{
    $object->fetch($id);
    $result = $object->close_line($user, GETPOST('ligne'), GETPOST('dateend'), urldecode(GETPOST('comment')));

    if ($result > 0)
    {
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
    else {
        $mesg=$object->error;
    }
}

// Si ajout champ produit predefini
if (GETPOST('mode')=='predefined')
{
    $date_start='';
    $date_end='';
    if (GETPOST('date_startmonth') && GETPOST('date_startday') && GETPOST('date_startyear'))
    {
        $date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
    }
    if (GETPOST('date_endmonth') && GETPOST('date_endday') && GETPOST('date_endyear'))
    {
        $date_end=dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), 0, GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
    }
}

// Si ajout champ produit libre
if (GETPOST('mode')=='libre')
{
    $date_start_sl='';
    $date_end_sl='';
    if (GETPOST('date_start_slmonth') && GETPOST('date_start_slday') && GETPOST('date_start_slyear'))
    {
        $date_start_sl=dol_mktime(GETPOST('date_start_slhour'), GETPOST('date_start_slmin'), 0, GETPOST('date_start_slmonth'), GETPOST('date_start_slday'), GETPOST('date_start_slyear'));
    }
    if (GETPOST('date_end_slmonth') && GETPOST('date_end_slday') && GETPOST('date_end_slyear'))
    {
        $date_end_sl=dol_mktime(GETPOST('date_end_slhour'), GETPOST('date_end_slmin'), 0, GETPOST('date_end_slmonth'), GETPOST('date_end_slday'), GETPOST('date_end_slyear'));
    }
}

// Param dates
$date_contrat='';
$date_start_update='';
$date_end_update='';
$date_start_real_update='';
$date_end_real_update='';
if (GETPOST('date_start_updatemonth') && GETPOST('date_start_updateday') && GETPOST('date_start_updateyear'))
{
    $date_start_update=dol_mktime(GETPOST('date_start_updatehour'), GETPOST('date_start_updatemin'), 0, GETPOST('date_start_updatemonth'), GETPOST('date_start_updateday'), GETPOST('date_start_updateyear'));
}
if (GETPOST('date_end_updatemonth') && GETPOST('date_end_updateday') && GETPOST('date_end_updateyear'))
{
    $date_end_update=dol_mktime(GETPOST('date_end_updatehour'), GETPOST('date_end_updatemin'), 0, GETPOST('date_end_updatemonth'), GETPOST('date_end_updateday'), GETPOST('date_end_updateyear'));
}
if (GETPOST('date_start_real_updatemonth') && GETPOST('date_start_real_updateday') && GETPOST('date_start_real_updateyear'))
{
    $date_start_real_update=dol_mktime(GETPOST('date_start_real_updatehour'), GETPOST('date_start_real_updatemin'), 0, GETPOST('date_start_real_updatemonth'), GETPOST('date_start_real_updateday'), GETPOST('date_start_real_updateyear'));
}
if (GETPOST('date_end_real_updatemonth') && GETPOST('date_end_real_updateday') && GETPOST('date_end_real_updateyear'))
{
    $date_end_real_update=dol_mktime(GETPOST('date_end_real_updatehour'), GETPOST('date_end_real_updatemin'), 0, GETPOST('date_end_real_updatemonth'), GETPOST('date_end_real_updateday'), GETPOST('date_end_real_updateyear'));
}
if (GETPOST('remonth') && GETPOST('reday') && GETPOST('reyear'))
{
    $datecontrat = dol_mktime(GETPOST('rehour'), GETPOST('remin'), 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
}

if ($action == 'add' && $user->rights->contrat->creer)
{
    $object->socid						= $socid;
    $object->date_contrat				= $datecontrat;

    $object->commercial_suivi_id		= GETPOST('commercial_suivi_id','int');
    $object->commercial_signature_id	= GETPOST('commercial_signature_id','int');

    $object->note						= GETPOST('note','alpha');
    $object->fk_project					= GETPOST('projectid','int');
    $object->remise_percent				= GETPOST('remise_percent','alpha');
    $object->ref						= GETPOST('ref','alpha');

    // Check
    if (empty($datecontrat))
    {
        $error++;
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")).'</div>';
        $action='create';
    }

    if (! $error)
    {
        $result = $object->create($user,$langs,$conf);
        if ($result > 0)
        {
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
            exit;
        }
        else {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
        $action='create';
    }
}

else if ($action == 'classin' && $user->rights->contrat->creer)
{
    $object->fetch($id);
    $object->setProject(GETPOST('projectid'));
}

else if ($action == 'addline' && $user->rights->contrat->creer)
{
    if (GETPOST('pqty') && ((GETPOST('pu') != '' && GETPOST('desc')) || GETPOST('idprod')))
    {
        $ret=$object->fetch($id);
        if ($ret < 0)
        {
            dol_print_error($db,$object->error);
            exit;
        }
        $ret=$object->fetch_thirdparty();

        $date_start='';
        $date_end='';
        // Si ajout champ produit libre
        if (GETPOST('mode') == 'libre')
        {
            if (GETPOST('date_start_slmonth') && GETPOST('date_start_slday') && GETPOST('date_start_slyear'))
            {
                $date_start=dol_mktime(GETPOST('date_start_slhour'), GETPOST('date_start_slmin'), 0, GETPOST('date_start_slmonth'), GETPOST('date_start_slday'), GETPOST('date_start_slyear'));
            }
            if (GETPOST('date_end_slmonth') && GETPOST('date_end_slday') && GETPOST('date_end_slyear'))
            {
                $date_end=dol_mktime(GETPOST('date_end_slhour'), GETPOST('date_end_slmin'), 0, GETPOST('date_end_slmonth'), GETPOST('date_end_slday'), GETPOST('date_end_slyear'));
            }
        }
        // Si ajout champ produit predefini
        if (GETPOST('mode') == 'predefined')
        {
            if (GETPOST('date_startmonth') && GETPOST('date_startday') && GETPOST('date_startyear'))
            {
                $date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
            }
            if (GETPOST('date_endmonth') && GETPOST('date_endday') && GETPOST('date_endyear'))
            {
                $date_end=dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), 0, GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
            }
        }

        // Ecrase $pu par celui du produit
        // Ecrase $desc par celui du produit
        // Ecrase $txtva par celui du produit
        // Ecrase $base_price_type par celui du produit
        if (GETPOST('idprod'))
        {
            $prod = new Product($db);
            $prod->fetch(GETPOST('idprod'));

            $tva_tx = get_default_tva($mysoc,$object->thirdparty,$prod->id);
            $tva_npr = get_default_npr($mysoc,$object->thirdparty,$prod->id);

            // On defini prix unitaire
            if ($conf->global->PRODUIT_MULTIPRICES && $object->thirdparty->price_level)
            {
                $pu_ht = $prod->multiprices[$object->thirdparty->price_level];
                $pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
                $price_min = $prod->multiprices_min[$object->thirdparty->price_level];
                $price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
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

           	$desc = $prod->description;
           	$desc.= $prod->description && GETPOST('desc') ? "\n" : "";
           	$desc.= GETPOST('desc');
        }
        else
        {
            $pu_ht=GETPOST('pu');
            $price_base_type = 'HT';
            $tva_tx=str_replace('*','',GETPOST('tva_tx'));
            $tva_npr=preg_match('/\*/',GETPOST('tva_tx'))?1:0;
            $desc=GETPOST('desc');
        }

        $localtax1_tx=get_localtax($tva_tx,1,$object->societe);
        $localtax2_tx=get_localtax($tva_tx,2,$object->societe);

        $info_bits=0;
        if ($tva_npr) $info_bits |= 0x01;

        if($price_min && (price2num($pu_ht)*(1-price2num(GETPOST('remise_percent'))/100) < price2num($price_min)))
        {
            $object->error = $langs->trans("CantBeLessThanMinPrice",price2num($price_min,'MU').' '.$langs->trans("Currency".$conf->currency));
            $result = -1 ;
        }
        else
        {
            // Insert line
            $result = $object->addline(
                $desc,
                $pu_ht,
                GETPOST('pqty'),
                $tva_tx,
                $localtax1_tx,
                $localtax2_tx,
                GETPOST('idprod'),
                GETPOST('premise'),
                $date_start,
                $date_end,
                $price_base_type,
                $pu_ttc,
                $info_bits
            );
        }

        if ($result > 0)
        {
            /*
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
             	contrat_pdf_create($db, $object->id, $object->modelpdf, $outputlangs);
             }
             */
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
}

else if ($action == 'updateligne' && $user->rights->contrat->creer && ! GETPOST('cancel'))
{
	$ret=$object->fetch($id);
	if ($ret < 0)
	{
		dol_print_error($db,$object->error);
		exit;
	}

	$object->fetch_thirdparty();
    $objectline = new ContratLigne($db);
    if ($objectline->fetch(GETPOST('elrowid')))
    {
        $db->begin();

        if ($date_start_real_update == '') $date_start_real_update=$objectline->date_ouverture;
        if ($date_end_real_update == '')   $date_end_real_update=$objectline->date_cloture;

		$localtax1_tx=get_localtax(GETPOST('eltva_tx'),1,$object->thirdparty);
        $localtax2_tx=get_localtax(GETPOST('eltva_tx'),2,$object->thirdparty);

        $objectline->description=GETPOST('eldesc');
        $objectline->price_ht=GETPOST('elprice');
        $objectline->subprice=GETPOST('elprice');
        $objectline->qty=GETPOST('elqty');
        $objectline->remise_percent=GETPOST('elremise_percent');
        $objectline->tva_tx=GETPOST('eltva_tx');
        $objectline->localtax1_tx=$localtax1_tx;
        $objectline->localtax2_tx=$localtax2_tx;
        $objectline->date_ouverture_prevue=$date_start_update;
        $objectline->date_ouverture=$date_start_real_update;
        $objectline->date_fin_validite=$date_end_update;
        $objectline->date_cloture=$date_end_real_update;
        $objectline->fk_user_cloture=$user->id;

        // TODO verifier price_min si fk_product et multiprix

        $result=$objectline->update($user);
        if ($result > 0)
        {
            $db->commit();
        }
        else
        {
            dol_print_error($db,'Failed to update contrat_det');
            $db->rollback();
        }
    }
    else
    {
        dol_print_error($db);
    }
}

else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->contrat->creer)
{
    $object->fetch($id);
    $result = $object->deleteline(GETPOST('lineid'),$user);

    if ($result >= 0)
    {
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
    else
    {
        $mesg=$object->error;
    }
}

else if ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->contrat->creer)
{
    $object->fetch($id);
    $result = $object->validate($user);
}

// Close all lines
else if ($action == 'confirm_close' && $confirm == 'yes' && $user->rights->contrat->creer)
{
    $object->fetch($id);
    $result = $object->cloture($user);
}

else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->contrat->supprimer)
{
	$object->fetch($id);
	$object->fetch_thirdparty();
	$result=$object->delete($user);
	if ($result >= 0)
	{
		header("Location: index.php");
		return;
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}

else if ($action == 'confirm_move' && $confirm == 'yes' && $user->rights->contrat->creer)
{
	if (GETPOST('newcid') > 0)
	{
		$contractline = new ContratLigne($db);
		$result=$contractline->fetch(GETPOST('lineid'));
		$contractline->fk_contrat = GETPOST('newcid');
		$result=$contractline->update($user,1);
		if ($result >= 0)
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			return;
		}
		else
		{
			$mesg='<div class="error">'.$object->error.'</div>';
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("RefNewContract")).'</div>';
	}
}

else if ($action == 'setnote_public' && $user->rights->contrat->creer)
{
	$result=$object->update_note_public(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES));
	if ($result < 0) dol_print_error($db,$object->error);
}

else if ($action == 'setnote' && $user->rights->contrat->creer)
{
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note'), ENT_QUOTES));
	if ($result < 0) dol_print_error($db,$object->error);
}

if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
{
	if ($action == 'addcontact' && $user->rights->contrat->creer)
	{
		$result = $object->fetch($id);

		if ($result > 0 && $id > 0)
		{
			$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
			$result = $result = $object->add_contact($contactid, GETPOST('type'), GETPOST('source'));
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
				$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
			}
			else
			{
				$mesg = '<div class="error">'.$object->error.'</div>';
			}
		}
	}

	// bascule du statut d'un contact
	else if ($action == 'swapstatut' && $user->rights->contrat->creer)
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
	else if ($action == 'deletecontact' && $user->rights->contrat->creer)
	{
		$object->fetch($id);
		$result = $object->delete_contact(GETPOST('lineid'));

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


/*
 * View
 */

llxHeader('',$langs->trans("ContractCard"),"Contrat");

$form = new Form($db);

$objectlignestatic=new ContratLigne($db);


/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($action == 'create')
{
    dol_fiche_head('', '', $langs->trans("AddContract"), 0, 'contract');

    dol_htmloutput_errors($mesg,'');

    $soc = new Societe($db);
    $soc->fetch($socid);

    $object->date_contrat = dol_now();

    $numct = $object->getNextNumRef($soc);

    print '<form name="form_contract" action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="socid" value="'.$soc->id.'">'."\n";
    print '<input type="hidden" name="remise_percent" value="0">';

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td>'.$langs->trans("Ref").'</td>';
    print '<td><input type="text" maxlength="30" name="ref" size="20" value="'.$numct.'"></td></tr>';

    // Customer
    print '<tr><td>'.$langs->trans("Customer").'</td><td>'.$soc->getNomUrl(1).'</td></tr>';

    // Ligne info remises tiers
    print '<tr><td>'.$langs->trans('Discount').'</td><td>';
    if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
    else print $langs->trans("CompanyHasNoRelativeDiscount");
    $absolute_discount=$soc->getAvailableDiscounts();
    print '. ';
    if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
    else print $langs->trans("CompanyHasNoAbsoluteDiscount");
    print '.';
    print '</td></tr>';

    // Commercial suivi
    print '<tr><td width="20%" nowrap><span class="fieldrequired">'.$langs->trans("TypeContact_contrat_internal_SALESREPFOLL").'</span></td><td>';
    print $form->select_users(GETPOST("commercial_suivi_id")?GETPOST("commercial_suivi_id"):$user->id,'commercial_suivi_id',1,'');
    print '</td></tr>';

    // Commercial signature
    print '<tr><td width="20%" nowrap><span class="fieldrequired">'.$langs->trans("TypeContact_contrat_internal_SALESREPSIGN").'</span></td><td>';
    print $form->select_users(GETPOST("commercial_signature_id")?GETPOST("commercial_signature_id"):$user->id,'commercial_signature_id',1,'');
    print '</td></tr>';

    print '<tr><td><span class="fieldrequired">'.$langs->trans("Date").'</span></td><td>';
    $form->select_date($datecontrat,'',0,0,'',"contrat");
    print "</td></tr>";

    if (! empty($conf->projet->enabled))
    {
        print '<tr><td>'.$langs->trans("Project").'</td><td>';
        select_projects($soc->id,GETPOST("projectid"),"projectid");
        print "</td></tr>";
    }

    print '<tr><td>'.$langs->trans("NotePublic").'</td><td valign="top">';
    print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';
    print GETPOST("note_public");
    print '</textarea></td></tr>';

    if (! $user->societe_id)
    {
        print '<tr><td>'.$langs->trans("NotePrivate").'</td><td valign="top">';
        print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">';
        print GETPOST("note");
        print '</textarea></td></tr>';
    }

    // Other attributes
    $parameters=array('colspan' => ' colspan="3"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

    print "</table>\n";

    print '<br><center><input type="submit" class="button" value="'.$langs->trans("Create").'"></center>';

    print "</form>\n";

    dol_fiche_end();
}
else
/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
{
    $now=dol_now();

    if ($id > 0 || ! empty($ref))
    {
        $result=$object->fetch($id,$ref);
        if ($result > 0)
        {
            $result=$object->fetch_lines();
        }
        if ($result < 0)
        {
            dol_print_error($db,$object->error);
            exit;
        }

        dol_htmloutput_errors($mesg,'');

        $object->fetch_thirdparty();

        $nbofservices=count($object->lines);

        $author = new User($db);
        $author->fetch($object->user_author_id);

        $commercial_signature = new User($db);
        $commercial_signature->fetch($object->commercial_signature_id);

        $commercial_suivi = new User($db);
        $commercial_suivi->fetch($object->commercial_suivi_id);

        $head = contract_prepare_head($object);

        $hselected = 0;

        dol_fiche_head($head, $hselected, $langs->trans("Contract"), 0, 'contract');


        /*
         * Confirmation de la suppression du contrat
         */
        if ($action == 'delete')
        {
            $ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$object->id,$langs->trans("DeleteAContract"),$langs->trans("ConfirmDeleteAContract"),"confirm_delete",'',0,1);
            if ($ret == 'html') print '<br>';
        }

        /*
         * Confirmation de la validation
         */
        if ($action == 'valid')
        {
            //$numfa = contrat_get_num($soc);
            $ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$object->id,$langs->trans("ValidateAContract"),$langs->trans("ConfirmValidateContract"),"confirm_valid",'',0,1);
            if ($ret == 'html') print '<br>';
        }

        /*
         * Confirmation de la fermeture
         */
        if ($action == 'close')
        {
            $ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$object->id,$langs->trans("CloseAContract"),$langs->trans("ConfirmCloseContract"),"confirm_close",'',0,1);
            if ($ret == 'html') print '<br>';
        }

        /*
         *   Contrat
         */
        if (! empty($object->brouillon) && $user->rights->contrat->creer)
        {
            print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="setremise">';
        }

        print '<table class="border" width="100%">';

        $linkback = '<a href="'.DOL_URL_ROOT.'/contrat/liste.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

        // Ref du contrat
        print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
        print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
        print "</td></tr>";

        // Customer
        print "<tr><td>".$langs->trans("Customer")."</td>";
        print '<td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

        // Ligne info remises tiers
        print '<tr><td>'.$langs->trans('Discount').'</td><td colspan="3">';
        if ($object->thirdparty->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$object->thirdparty->remise_client);
        else print $langs->trans("CompanyHasNoRelativeDiscount");
        $absolute_discount=$object->thirdparty->getAvailableDiscounts();
        print '. ';
        if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
        else print $langs->trans("CompanyHasNoAbsoluteDiscount");
        print '.';
        print '</td></tr>';

        // Statut contrat
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
        if ($object->statut==0) print $object->getLibStatut(2);
        else print $object->getLibStatut(4);
        print "</td></tr>";

        // Date
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td colspan="3">'.dol_print_date($object->date_contrat,"dayhour")."</td></tr>\n";

        // Projet
        if (! empty($conf->projet->enabled))
        {
            $langs->load("projects");
            print '<tr><td>';
            print '<table width="100%" class="nobordernopadding"><tr><td>';
            print $langs->trans("Project");
            print '</td>';
            if ($action != "classify" && $user->rights->projet->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->trans("SetProject")).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($action == "classify")
            {
                $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id,$object->socid,$object->fk_project,"projectid");
            }
            else
            {
                $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id,$object->socid,$object->fk_project,"none");
            }
            print "</td></tr>";
        }

        // Other attributes
        $parameters=array('colspan' => ' colspan="3"');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

        print "</table>";

        if (! empty($object->brouillon) && $user->rights->contrat->creer)
        {
            print '</form>';
        }

        echo '<br>';

        if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
        {
        	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
        	$formcompany= new FormCompany($db);

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


        $servicepos=(isset($_REQUEST["servicepos"])?$_REQUEST["servicepos"]:1);
        $colorb='666666';

        $arrayothercontracts=$object->getListOfContracts('others');

        /*
         * Lines of contracts
         */
        $productstatic=new Product($db);

        // TODO move css and DAO

        // Title line for service
        print '<table class="notopnoleft allwidth">';	// Array with (n*2)+1 lines
        $cursorline=1;
        while ($cursorline <= $nbofservices)
        {
            print '<tr height="16" '.$bc[false].'>';
            print '<td class="liste_titre" width="90" style="border-left: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';">';
            print $langs->trans("ServiceNb",$cursorline).'</td>';

            print '<td class="tab" style="border-right: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';" rowspan="2">';

            // Area with common detail of line
            print '<table class="notopnoleft" width="100%">';

            $sql = "SELECT cd.rowid, cd.statut, cd.label as label_det, cd.fk_product, cd.description, cd.price_ht, cd.qty,";
            $sql.= " cd.tva_tx, cd.remise_percent, cd.info_bits, cd.subprice,";
            $sql.= " cd.date_ouverture_prevue as date_debut, cd.date_ouverture as date_debut_reelle,";
            $sql.= " cd.date_fin_validite as date_fin, cd.date_cloture as date_fin_reelle,";
            $sql.= " cd.commentaire as comment,";
            $sql.= " p.rowid as pid, p.ref as pref, p.label as label, p.fk_product_type as ptype";
            $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
            $sql.= " WHERE cd.rowid = ".$object->lines[$cursorline-1]->id;

            $result = $db->query($sql);
            if ($result)
            {
                $total = 0;

                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("Service").'</td>';
                print '<td width="50" align="center">'.$langs->trans("VAT").'</td>';
                print '<td width="50" align="right">'.$langs->trans("PriceUHT").'</td>';
                print '<td width="30" align="center">'.$langs->trans("Qty").'</td>';
                print '<td width="50" align="right">'.$langs->trans("ReductionShort").'</td>';
                print '<td width="30">&nbsp;</td>';
                print "</tr>\n";

                $var=true;

                $objp = $db->fetch_object($result);

                $var=!$var;

                if ($action != 'editline' || GETPOST('rowid') != $objp->rowid)
                {
                    print '<tr '.$bc[$var].' valign="top">';
                    // Libelle
                    if ($objp->fk_product > 0)
                    {
                        print '<td>';
                        $productstatic->id=$objp->fk_product;
                        $productstatic->type=$objp->ptype;
                        $productstatic->ref=$objp->pref;
                        print $productstatic->getNomUrl(1,'',20);
                        print $objp->label?' - '.dol_trunc($objp->label,16):'';
                        if ($objp->description) print '<br>'.dol_nl2br($objp->description);
                        print '</td>';
                    }
                    else
                    {
                        print "<td>".nl2br($objp->description)."</td>\n";
                    }
                    // TVA
                    print '<td align="center">'.vatrate($objp->tva_tx,'%',$objp->info_bits).'</td>';
                    // Prix
                    print '<td align="right">'.price($objp->subprice)."</td>\n";
                    // Quantite
                    print '<td align="center">'.$objp->qty.'</td>';
                    // Remise
                    if ($objp->remise_percent > 0)
                    {
                        print '<td align="right">'.$objp->remise_percent."%</td>\n";
                    }
                    else
                    {
                        print '<td>&nbsp;</td>';
                    }
                    // Icon move, update et delete (statut contrat 0=brouillon,1=valide,2=ferme)
                    print '<td align="right" nowrap="nowrap">';
                    if ($user->rights->contrat->creer && count($arrayothercontracts) && ($object->statut >= 0))
                    {
                        print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=move&amp;rowid='.$objp->rowid.'">';
                        print img_picto($langs->trans("MoveToAnotherContract"),'uparrow');
                        print '</a>';
                    }
                    else {
                        print '&nbsp;';
                    }
                    if ($user->rights->contrat->creer && ($object->statut >= 0))
                    {
                        print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'">';
                        print img_edit();
                        print '</a>';
                    }
                    else {
                        print '&nbsp;';
                    }
                    if ( $user->rights->contrat->creer && ($object->statut >= 0))
                    {
                        print '&nbsp;';
                        print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=deleteline&amp;rowid='.$objp->rowid.'">';
                        print img_delete();
                        print '</a>';
                    }
                    print '</td>';

                    print "</tr>\n";

                    // Dates de en service prevues et effectives
                    if ($objp->subprice >= 0)
                    {
                        print '<tr '.$bc[$var].'>';
                        print '<td colspan="6">';

                        // Date planned
                        print $langs->trans("DateStartPlanned").': ';
                        if ($objp->date_debut)
                        {
                            print dol_print_date($db->jdate($objp->date_debut));
                            // Warning si date prevu passee et pas en service
                            if ($objp->statut == 0 && $db->jdate($objp->date_debut) < ($now - $conf->contrat->services->inactifs->warning_delay)) { print " ".img_warning($langs->trans("Late")); }
                        }
                        else print $langs->trans("Unknown");
                        print ' &nbsp;-&nbsp; ';
                        print $langs->trans("DateEndPlanned").': ';
                        if ($objp->date_fin)
                        {
                            print dol_print_date($db->jdate($objp->date_fin));
                            if ($objp->statut == 4 && $db->jdate($objp->date_fin) < ($now - $conf->contrat->services->expires->warning_delay)) { print " ".img_warning($langs->trans("Late")); }
                        }
                        else print $langs->trans("Unknown");

                        print '</td>';
                        print '</tr>';
                    }
                }
                // Ligne en mode update
                else
                {
                    print '<form name="update" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="action" value="updateligne">';
                    print '<input type="hidden" name="elrowid" value="'.GETPOST('rowid').'">';
                    // Ligne carac
                    print "<tr $bc[$var]>";
                    print '<td>';
                    if ($objp->fk_product)
                    {
                        $productstatic->id=$objp->fk_product;
                        $productstatic->type=$objp->ptype;
                        $productstatic->ref=$objp->pref;
                        print $productstatic->getNomUrl(1,'',20);
                        print $objp->label?' - '.dol_trunc($objp->label,16):'';
                        print '<br>';
                    }
                    else
                    {
                        print $objp->label?$objp->label.'<br>':'';
                    }
                    print '<textarea name="eldesc" cols="70" rows="1">'.$objp->description.'</textarea></td>';
                    print '<td align="right">';
                    print $form->load_tva("eltva_tx",$objp->tva_tx,$mysoc,$object->thirdparty);
                    print '</td>';
                    print '<td align="right"><input size="5" type="text" name="elprice" value="'.price($objp->subprice).'"></td>';
                    print '<td align="center"><input size="2" type="text" name="elqty" value="'.$objp->qty.'"></td>';
                    print '<td align="right"><input size="1" type="text" name="elremise_percent" value="'.$objp->remise_percent.'">%</td>';
                    print '<td align="center" rowspan="2" valign="middle"><input type="submit" class="button" name="save" value="'.$langs->trans("Modify").'">';
                    print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                    print '</td>';
                    // Ligne dates prevues
                    print "<tr $bc[$var]>";
                    print '<td colspan="5">';
                    print $langs->trans("DateStartPlanned").' ';
                    $form->select_date($db->jdate($objp->date_debut),"date_start_update",$usehm,$usehm,($db->jdate($objp->date_debut)>0?0:1),"update");
                    print '<br>'.$langs->trans("DateEndPlanned").' ';
                    $form->select_date($db->jdate($objp->date_fin),"date_end_update",$usehm,$usehm,($db->jdate($objp->date_fin)>0?0:1),"update");
                    print '</td>';
                    print '</tr>';

                    print "</form>\n";
                }

                $db->free($result);
            }
            else
            {
                dol_print_error($db);
            }

            if ($object->statut > 0)
            {
                print '<tr '.$bc[false].'>';
                print '<td colspan="6"><hr></td>';
                print "</tr>\n";
            }

            print "</table>";


            /*
             * Confirmation to delete service line of contract
             */
            if ($action == 'deleteline' && ! $_REQUEST["cancel"] && $user->rights->contrat->creer && $object->lines[$cursorline-1]->id == GETPOST('rowid'))
            {
                $ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id."&lineid=".GETPOST('rowid'),$langs->trans("DeleteContractLine"),$langs->trans("ConfirmDeleteContractLine"),"confirm_deleteline",'',0,1);
                if ($ret == 'html') print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[false].' height="6"><td></td></tr></table>';
            }

            /*
             * Confirmation to move service toward another contract
             */
            if ($action == 'move' && ! $_REQUEST["cancel"] && $user->rights->contrat->creer && $object->lines[$cursorline-1]->id == GETPOST('rowid'))
            {
                $arraycontractid=array();
                foreach($arrayothercontracts as $contractcursor)
                {
                    $arraycontractid[$contractcursor->id]=$contractcursor->ref;
                }
                //var_dump($arraycontractid);
                // Cree un tableau formulaire
                $formquestion=array(
				'text' => $langs->trans("ConfirmMoveToAnotherContractQuestion"),
                array('type' => 'select', 'name' => 'newcid', 'values' => $arraycontractid));

                $form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id."&lineid=".GETPOST('rowid'),$langs->trans("MoveToAnotherContract"),$langs->trans("ConfirmMoveToAnotherContract"),"confirm_move",$formquestion);
                print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[false].' height="6"><td></td></tr></table>';
            }

            /*
             * Confirmation de la validation activation
             */
            if ($action == 'active' && ! $_REQUEST["cancel"] && $user->rights->contrat->activer && $object->lines[$cursorline-1]->id == GETPOST('ligne'))
            {
                $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
                $dateactend   = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
                $comment      = GETPOST('comment');
                $form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id."&ligne=".GETPOST('ligne')."&date=".$dateactstart."&dateend=".$dateactend."&comment=".urlencode($comment),$langs->trans("ActivateService"),$langs->trans("ConfirmActivateService",dol_print_date($dateactstart,"%A %d %B %Y")),"confirm_active", '', 0, 1);
                print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[false].' height="6"><td></td></tr></table>';
            }

            /*
             * Confirmation de la validation fermeture
             */
            if ($action == 'closeline' && ! $_REQUEST["cancel"] && $user->rights->contrat->activer && $object->lines[$cursorline-1]->id == GETPOST('ligne'))
            {
                $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
                $dateactend   = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
                $comment      = GETPOST('comment');
                $form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id."&ligne=".GETPOST('ligne')."&date=".$dateactstart."&dateend=".$dateactend."&comment=".urlencode($comment), $langs->trans("CloseService"), $langs->trans("ConfirmCloseService",dol_print_date($dateactend,"%A %d %B %Y")), "confirm_closeline", '', 0, 1);
                print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[false].' height="6"><td></td></tr></table>';
            }


            // Area with status and activation info of line
            if ($object->statut > 0)
            {
                print '<table class="notopnoleft" width="100%">';

                print '<tr '.$bc[false].'>';
                print '<td>'.$langs->trans("ServiceStatus").': '.$object->lines[$cursorline-1]->getLibStatut(4).'</td>';
                print '<td width="30" align="right">';
                if ($user->societe_id == 0)
                {
                    if ($object->statut > 0 && $action != 'activateline' && $action != 'unactivateline')
                    {
                        $tmpaction='activateline';
                        if ($objp->statut == 4) $tmpaction='unactivateline';
                        print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.$object->lines[$cursorline-1]->id.'&amp;action='.$tmpaction.'">';
                        print img_edit();
                        print '</a>';
                    }
                }
                print '</td>';
                print "</tr>\n";

                print '<tr '.$bc[false].'>';

                print '<td>';
                // Si pas encore active
                if (! $objp->date_debut_reelle) {
                    print $langs->trans("DateStartReal").': ';
                    if ($objp->date_debut_reelle) print dol_print_date($objp->date_debut_reelle);
                    else print $langs->trans("ContractStatusNotRunning");
                }
                // Si active et en cours
                if ($objp->date_debut_reelle && ! $objp->date_fin_reelle) {
                    print $langs->trans("DateStartReal").': ';
                    print dol_print_date($objp->date_debut_reelle);
                }
                // Si desactive
                if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
                    print $langs->trans("DateStartReal").': ';
                    print dol_print_date($objp->date_debut_reelle);
                    print ' &nbsp;-&nbsp; ';
                    print $langs->trans("DateEndReal").': ';
                    print dol_print_date($objp->date_fin_reelle);
                }
                if (! empty($objp->comment)) print "<br>".$objp->comment;
                print '</td>';

                print '<td align="center">&nbsp;</td>';

                print '</tr>';
                print '</table>';
            }

            if ($user->rights->contrat->activer && $action == 'activateline' && $object->lines[$cursorline-1]->id == GETPOST('ligne'))
            {
                /**
                 * Activer la ligne de contrat
                 */
                print '<form name="active" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.GETPOST('ligne').'&amp;action=active" method="post">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

                print '<table class="noborder" width="100%">';
                //print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("Status").'</td></tr>';

                // Definie date debut et fin par defaut
                $dateactstart = $objp->date_debut;
                if (GETPOST('remonth')) $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
                elseif (! $dateactstart) $dateactstart = time();

                $dateactend = $objp->date_fin;
                if (GETPOST('endmonth')) $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
                elseif (! $dateactend)
                {
                    if ($objp->fk_product > 0)
                    {
                        $product=new Product($db);
                        $product->fetch($objp->fk_product);
                        $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                    }
                }

                print '<tr '.$bc[$var].'><td>'.$langs->trans("DateServiceActivate").'</td><td>';
                print $form->select_date($dateactstart,'',$usehm,$usehm,'',"active");
                print '</td>';

                print '<td>'.$langs->trans("DateEndPlanned").'</td><td>';
                print $form->select_date($dateactend,"end",$usehm,$usehm,'',"active");
                print '</td>';

                print '<td align="center" rowspan="2" valign="middle">';
                print '<input type="submit" class="button" name="activate" value="'.$langs->trans("Activate").'"><br>';
                print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                print '</td>';

                print '</tr>';

                print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td colspan="3"><input size="80" type="text" name="comment" value="'.GETPOST('comment').'"></td></tr>';

                print '</table>';

                print '</form>';
            }

            if ($user->rights->contrat->activer && $action == 'unactivateline' && $object->lines[$cursorline-1]->id == GETPOST('ligne'))
            {
                /**
                 * Desactiver la ligne de contrat
                 */
                print '<form name="closeline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.$object->lines[$cursorline-1]->id.'&amp;action=closeline" method="post">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

                print '<table class="noborder" width="100%">';

                // Definie date debut et fin par defaut
                $dateactstart = $objp->date_debut_reelle;
                if (GETPOST('remonth')) $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
                elseif (! $dateactstart) $dateactstart = time();

                $dateactend = $objp->date_fin_reelle;
                if (GETPOST('endmonth')) $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
                elseif (! $dateactend)
                {
                    if ($objp->fk_product > 0)
                    {
                        $product=new Product($db);
                        $product->fetch($objp->fk_product);
                        $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                    }
                }
                $now=dol_now();
                if ($dateactend > $now) $dateactend=$now;

                print '<tr '.$bc[$var].'><td colspan="2">';
                if ($objp->statut >= 4)
                {
                    if ($objp->statut == 4)
                    {
                        print $langs->trans("DateEndReal").' ';
                        $form->select_date($dateactend,"end",$usehm,$usehm,($objp->date_fin_reelle>0?0:1),"closeline");
                    }
                }
                print '</td>';

                print '<td align="right" rowspan="2"><input type="submit" class="button" name="close" value="'.$langs->trans("Close").'"><br>';
                print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                print '</td></tr>';

                print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td><input size="70" type="text" class="flat" name="comment" value="'.GETPOST('comment').'"></td></tr>';
                print '</table>';

                print '</form>';
            }

            print '</td>';	// End td if line is 1

            print '</tr>';
            print '<tr><td style="border-right: 1px solid #'.$colorb.'">&nbsp;</td></tr>';
            $cursorline++;
        }
        print '</table>';

        /*
         * Ajouter une ligne produit/service
         */
        if ($user->rights->contrat->creer && ($object->statut >= 0))
        {
            print '<br>';
            print '<table class="noborder" width="100%">';	// Array with (n*2)+1 lines

            print "<tr class=\"liste_titre\">";
            print '<td>'.$langs->trans("Service").'</td>';
            print '<td align="center">'.$langs->trans("VAT").'</td>';
            print '<td align="right">'.$langs->trans("PriceUHT").'</td>';
            print '<td align="center">'.$langs->trans("Qty").'</td>';
            print '<td align="right">'.$langs->trans("ReductionShort").'</td>';
            print '<td>&nbsp;</td>';
            print '<td>&nbsp;</td>';
            print "</tr>\n";

            $var=false;

            // Service sur produit predefini
            print '<form name="addline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="addline">';
            print '<input type="hidden" name="mode" value="predefined">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';

            print "<tr ".$bc[$var].">";
            print '<td colspan="3">';
            // multiprix
            if (! empty($conf->global->PRODUIT_MULTIPRICES))
            	$form->select_produits('','idprod',1,$conf->product->limit_size,$object->thirdparty->price_level);
            else
				$form->select_produits('','idprod',1,$conf->product->limit_size);
            print '<br>';
            print '<textarea name="desc" cols="70" rows="'.ROWS_2.'"></textarea>';
            print '</td>';

            print '<td align="center"><input type="text" class="flat" size="2" name="pqty" value="1"></td>';
            print '<td align="right" nowrap><input type="text" class="flat" size="1" name="premise" value="'.$object->thirdparty->remise_client.'">%</td>';
            print '<td align="center" colspan="2" rowspan="2"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
            print '</tr>'."\n";

            print "<tr ".$bc[$var].">";
            print '<td colspan="8">';
            print $langs->trans("DateStartPlanned").' ';
            $form->select_date('',"date_start",$usehm,$usehm,1,"addline");
            print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
            $form->select_date('',"date_end",$usehm,$usehm,1,"addline");
            print '</td>';
            print '</tr>';

            print '</form>';

            $var=!$var;

            // Service libre
            print '<form name="addline_sl" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="addline">';
            print '<input type="hidden" name="mode" value="libre">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';

            print "<tr $bc[$var]>";
            print '<td><textarea name="desc" cols="70" rows="'.ROWS_2.'"></textarea></td>';

            print '<td>';
            print $form->load_tva("tva_tx",-1,$mysoc,$object->thirdparty);
            print '</td>';
            print '<td align="right"><input type="text" class="flat" size="4" name="pu" value=""></td>';
            print '<td align="center"><input type="text" class="flat" size="2" name="pqty" value="1"></td>';
            print '<td align="right" nowrap><input type="text" class="flat" size="1" name="premise" value="'.$object->thirdparty->remise_client.'">%</td>';
            print '<td align="center" rowspan="2" colspan="2"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';

            print '</tr>'."\n";

            print "<tr $bc[$var]>";
            print '<td colspan="8">';
            print $langs->trans("DateStartPlanned").' ';
            $form->select_date('',"date_start_sl",$usehm,$usehm,1,"addline_sl");
            print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
            $form->select_date('',"date_end_sl",$usehm,$usehm,1,"addline_sl");
            print '</td>';
            print '</tr>';

            print '</form>';

            print '</table>';
        }



        //print '</td><td align="center" class="tab" style="padding: 4px; border-right: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';">';

        //print '</td></tr></table>';

        print '</div>';


        /*************************************************************
         * Boutons Actions
         *************************************************************/

        if ($user->societe_id == 0)
        {
            print '<div class="tabsAction">';

            if ($object->statut == 0 && $nbofservices)
            {
                if ($user->rights->contrat->creer) print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
                else print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Validate").'</a>';
            }

            if (! empty($conf->facture->enabled) && $object->statut > 0 && $object->nbofservicesclosed < $nbofservices)
            {
                $langs->load("bills");
                if ($user->rights->facture->creer) print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->thirdparty->id.'">'.$langs->trans("CreateBill").'</a>';
                else print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CreateBill").'</a>';
            }

            if ($object->nbofservicesclosed < $nbofservices)
            {
                //if (! $numactive)
                //{
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=close">'.$langs->trans("CloseAllContracts").'</a>';
                //}
                //else
                //{
                //	print '<a class="butActionRefused" href="#" title="'.$langs->trans("CloseRefusedBecauseOneServiceActive").'">'.$langs->trans("Close").'</a>';
                //}
            }

            // On peut supprimer entite si
            // - Droit de creer + mode brouillon (erreur creation)
            // - Droit de supprimer
            if (($user->rights->contrat->creer && $object->statut == 0) || $user->rights->contrat->supprimer)
            {
                print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
            }

            print "</div>";
            print '<br>';
        }

        print '<table width="100%"><tr><td width="50%" valign="top">';

        /*
         * Linked object block
         */
        $somethingshown=$object->showLinkedObjectBlock();

        print '</td><td valign="top" width="50%">';
        print '</td></tr></table>';
    }
}


llxFooter();
$db->close();
?>
