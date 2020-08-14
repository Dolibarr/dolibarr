<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne                 <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Andre Cianfarani            <acianfa@free.fr>
 * Copyright (C) 2005-2017 Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014 Juanjo Menent               <jmenent@2byte.es>
 * Copyright (C) 2013      Alexandre Spangaro          <aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2019 Frédéric France             <frederic.france@netlogic.fr>
 * Copyright (C) 2015      Marcos García               <marcosgdf@gmail.com>
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
 *       \file       htdocs/comm/card.php
 *       \ingroup    commercial compta
 *       \brief      Page to show customer card of a third party
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (! empty($conf->facture->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->facture->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
if (! empty($conf->propal->enabled)) require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->expedition->enabled)) require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
if (! empty($conf->contrat->enabled)) require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->ficheinter->enabled)) require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'banks'));

if (! empty($conf->contrat->enabled))  $langs->load("contracts");
if (! empty($conf->commande->enabled)) $langs->load("orders");
if (! empty($conf->expedition->enabled)) $langs->load("sendings");
if (! empty($conf->facture->enabled)) $langs->load("bills");
if (! empty($conf->projet->enabled))  $langs->load("projects");
if (! empty($conf->ficheinter->enabled)) $langs->load("interventions");
if (! empty($conf->notification->enabled)) $langs->load("mails");

// Security check
$id = (GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int'));
if ($user->societe_id > 0) $id=$user->societe_id;
$result = restrictedArea($user, 'societe', $id, '&societe');

$action		= GETPOST('action', 'aZ09');
$mode		= GETPOST("mode");

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";
$cancelbutton = GETPOST('cancel', 'alpha');

$object = new Client($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartycomm','globalcard'));


/*
 * Actions
 */

$parameters = array('id' => $id, 'socid' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancelbutton)
	{
		$action="";
	}

	// set accountancy code
	if ($action == 'setcustomeraccountancycode')
	{
		$result=$object->fetch($id);
		$object->code_compta=$_POST["customeraccountancycode"];
		$result=$object->update($object->id, $user, 1, 1, 0);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// terms of the settlement
	if ($action == 'setconditions' && $user->rights->societe->creer)
	{
		$object->fetch($id);
		$result=$object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// mode de reglement
	if ($action == 'setmode' && $user->rights->societe->creer)
	{
		$object->fetch($id);
		$result=$object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// Bank account
	if ($action == 'setbankaccount' && $user->rights->societe->creer)
	{
		$object->fetch($id);
		$result=$object->setBankAccount(GETPOST('fk_account', 'int'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// customer preferred shipping method
    if ($action == 'setshippingmethod' && $user->rights->societe->creer)
    {
        $object->fetch($id);
        $result = $object->setShippingMethod(GETPOST('shipping_method_id', 'int'));
        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }

	// assujetissement a la TVA
	if ($action == 'setassujtva' && $user->rights->societe->creer)
	{
		$object->fetch($id);
		$object->tva_assuj=$_POST['assujtva_value'];
		$result=$object->update($object->id);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// set prospect level
	if ($action == 'setprospectlevel' && $user->rights->societe->creer)
	{
		$object->fetch($id);
		$object->fk_prospectlevel=GETPOST('prospect_level_id', 'alpha');
		$result=$object->update($object->id, $user);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// set communication status
	if ($action == 'setstcomm')
	{
		$object->fetch($id);
		$object->stcomm_id=dol_getIdFromCode($db, GETPOST('stcomm', 'alpha'), 'c_stcomm');
		$result=$object->update($object->id, $user);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// update outstandng limit
	if ($action == 'setoutstanding_limit')
	{
		$object->fetch($id);
		$object->outstanding_limit=GETPOST('outstanding_limit');
		$result=$object->update($object->id, $user);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// update order min amount
	if ($action == 'setorder_min_amount')
	{
		$object->fetch($id);
		$object->order_min_amount=price2num(GETPOST('order_min_amount', 'alpha'));
		$result=$object->update($object->id, $user);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	if ($action == 'update_extras') {
        $object->fetch($id);

        $object->oldcopy = dol_clone($object);

        // Fill array 'array_options' with data from update form
        $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
        $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute', 'none'));
        if ($ret < 0) $error++;
        if (! $error)
        {
        	$result = $object->insertExtraFields('COMPANY_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
        }
        if ($error) $action = 'edit_extras';
    }
}


/*
 * View
 */

$contactstatic = new Contact($db);
$userstatic=new User($db);
$form = new Form($db);
$formcompany=new FormCompany($db);

if ($id > 0 && empty($object->id))
{
	// Load data of third party
	$res=$object->fetch($id);
	if ($object->id < 0) dol_print_error($db, $object->error, $object->errors);
}

$title=$langs->trans("CustomerCard");
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name;
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);


if ($object->id > 0)
{
	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'customer', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

	print '<div class="fichecenter"><div class="fichehalfleft">';

    print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Prospect/Customer
	print '<tr><td class="titlefield">'.$langs->trans('ProspectCustomer').'</td><td>';
	print $object->getLibCustProspStatut();
	print '</td></tr>';

	// Prefix
    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans("Prefix").'</td><td>';
	    print ($object->prefix_comm?$object->prefix_comm:'&nbsp;');
	    print '</td></tr>';
    }

	if ($object->client)
	{
        $langs->load("compta");

		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td>';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';

		print '<tr>';
		print '<td>';
		print $form->editfieldkey("CustomerAccountancyCode", 'customeraccountancycode', $object->code_compta, $object, $user->rights->societe->creer);
		print '</td><td>';
		print $form->editfieldval("CustomerAccountancyCode", 'customeraccountancycode', $object->code_compta, $object, $user->rights->societe->creer);
		print '</td>';
		print '</tr>';
	}

	// This fields are used to know VAT to include in an invoice when the thirdparty is making a sale, so when it is a supplier.
	// We don't need them into customer profile.
	// Except for spain and localtax where localtax depends on buyer and not seller

	// VAT is used
	/*
	print '<tr>';
	print '<td class="nowrap">';
	print $form->textwithpicto($langs->trans('VATIsUsed'),$langs->trans('VATIsUsedWhenSelling'));
	print '</td>';
	print '<td>';
	print yn($object->tva_assuj);
	print '</td>';
	print '</tr>';
	*/

	if ($mysoc->country_code == 'ES')
	{
		// Local Taxes
		if ($mysoc->localtax1_assuj=="1")
		{
			print '<tr><td class="nowrap">'.$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code).'</td><td>';
			print yn($object->localtax1_assuj);
			print '</td></tr>';
		}
		if ($mysoc->localtax1_assuj=="1")
		{
			print '<tr><td class="nowrap">'.$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code).'</td><td>';
			print yn($object->localtax2_assuj);
			print '</td></tr>';
		}
	}

	// TVA Intra
	print '<tr><td class="nowrap">'.$langs->trans('VATIntra').'</td><td>';
	print $object->tva_intra;
	print '</td></tr>';

	// default terms of the settlement
	$langs->load('bills');
	print '<tr><td>';
	print '<table width="100%" class="nobordernopadding"><tr><td>';
	print $langs->trans('PaymentConditions');
	print '<td>';
	if (($action != 'editconditions') && $user->rights->societe->creer) print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;socid='.$object->id.'">'.img_edit($langs->trans('SetConditions'), 1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editconditions')
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->cond_reglement_id, 'cond_reglement_id', 1);
	}
	else
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->cond_reglement_id, 'none');
	}
	print "</td>";
	print '</tr>';

	// Mode de reglement par defaut
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('PaymentMode');
	print '<td>';
	if (($action != 'editmode') && $user->rights->societe->creer) print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;socid='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editmode')
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT', 1, 1);
	}
	else
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->mode_reglement_id, 'none');
	}
	print "</td>";
	print '</tr>';

	if (! empty($conf->banque->enabled))
	{
		// Compte bancaire par défaut
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('PaymentBankAccount');
		print '<td>';
		if (($action != 'editbankaccount') && $user->rights->societe->creer) print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;socid='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbankaccount')
		{
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->fk_account, 'fk_account', 1);
		}
		else
		{
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->fk_account, 'none');
		}
		print "</td>";
		print '</tr>';
	}

	$isCustomer = ($object->client == 1 || $object->client == 3);

	// Relative discounts (Discounts-Drawbacks-Rebates)
	if ($isCustomer)
	{
    	print '<tr><td class="nowrap">';
    	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
    	print $langs->trans("CustomerRelativeDiscountShort");
    	print '<td><td class="right">';
    	if ($user->rights->societe->creer && !$user->societe_id > 0)
    	{
    		print '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$object->id.'">'.img_edit($langs->trans("Modify")).'</a>';
    	}
    	print '</td></tr></table>';
    	print '</td><td>'.($object->remise_percent?'<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$object->id.'">'.$object->remise_percent.'%</a>':'').'</td>';
    	print '</tr>';

    	// Absolute discounts (Discounts-Drawbacks-Rebates)
    	print '<tr><td class="nowrap">';
    	print '<table width="100%" class="nobordernopadding">';
    	print '<tr><td class="nowrap">';
    	print $langs->trans("CustomerAbsoluteDiscountShort");
    	print '<td><td class="right">';
    	if ($user->rights->societe->creer && !$user->societe_id > 0)
    	{
    		print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$object->id).'">'.img_edit($langs->trans("Modify")).'</a>';
    	}
    	print '</td></tr></table>';
    	print '</td>';
    	print '<td>';
    	$amount_discount=$object->getAvailableDiscounts();
    	if ($amount_discount < 0) dol_print_error($db, $object->error);
    	if ($amount_discount > 0) print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$object->id).'">'.price($amount_discount, 1, $langs, 1, -1, -1, $conf->currency).'</a>';
    	//else print $langs->trans("DiscountNone");
    	print '</td>';
    	print '</tr>';
	}

	// Max outstanding bill
	if ($object->client)
	{
	    print '<tr class="nowrap">';
	    print '<td>';
	    print $form->editfieldkey("OutstandingBill", 'outstanding_limit', $object->outstanding_limit, $object, $user->rights->societe->creer);
	    print '</td><td>';
	    $limit_field_type = (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE)) ? 'numeric' : 'amount';
	    print $form->editfieldval("OutstandingBill", 'outstanding_limit', $object->outstanding_limit, $object, $user->rights->societe->creer, $limit_field_type, ($object->outstanding_limit != '' ? price($object->outstanding_limit) : ''));
	    //if (empty($object->outstanding_limit)) print $langs->trans("NoLimit");

	    print '</td>';
	    print '</tr>';
	}

	if ($object->client)
	{
		if (! empty($conf->commande->enabled) && ! empty($conf->global->ORDER_MANAGE_MIN_AMOUNT))
		{
		    print '<!-- Minimim amount for orders -->'."\n";
		    print '<tr class="nowrap">';
		    print '<td>';
		    print $form->editfieldkey("OrderMinAmount", 'order_min_amount', $object->order_min_amount, $object, $user->rights->societe->creer);
		    print '</td><td>';
		    print $form->editfieldval("OrderMinAmount", 'order_min_amount', $object->order_min_amount, $object, $user->rights->societe->creer, $limit_field_type, ($object->order_min_amount != '' ? price($object->order_min_amount) : ''));
		    print '</td>';
		    print '</tr>';
		}
	}


	// Multiprice level
	if (! empty($conf->global->PRODUIT_MULTIPRICES) || ! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES))
	{
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans("PriceLevel");
		print '<td><td class="right">';
		if ($user->rights->societe->creer)
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/multiprix.php?id='.$object->id.'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td><td>';
		print $object->price_level;
		$keyforlabel='PRODUIT_MULTIPRICES_LABEL'.$object->price_level;
		if (! empty($conf->global->$keyforlabel)) print ' - '.$langs->trans($conf->global->$keyforlabel);
		print "</td>";
		print '</tr>';
	}

    // Preferred shipping Method
    if (! empty($conf->global->SOCIETE_ASK_FOR_SHIPPING_METHOD)) {
        print '<tr><td class="nowrap">';
        print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
        print $langs->trans('SendingMethod');
        print '<td>';
        if (($action != 'editshipping') && $user->rights->societe->creer) print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editshipping&amp;socid='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editshipping')
        {
            $form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->shipping_method_id, 'shipping_method_id', 1);
        }
        else
        {
            $form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->shipping_method_id, 'none');
        }
        print "</td>";
        print '</tr>';
    }

	// Categories
	if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire)) {
		$langs->load("categories");
		print '<tr><td>' . $langs->trans("CustomersCategoriesShort") . '</td>';
		print '<td>';
		print $form->showCategories($object->id, 'customer', 1);
		print "</td></tr>";
	}

	// Other attributes
	$parameters=array('socid'=>$object->id);
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    // Sales representative
	include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';

    // Module Adherent
    if (! empty($conf->adherent->enabled))
    {
        $langs->load("members");
        $langs->load("users");

        print '<tr><td class="titlefield">'.$langs->trans("LinkedToDolibarrMember").'</td>';
        print '<td>';
        $adh=new Adherent($db);
        $result=$adh->fetch('', '', $object->id);
        if ($result > 0)
        {
            $adh->ref=$adh->getFullName($langs);
            print $adh->getNomUrl(1);
        }
        else
        {
            print '<span class="opacitymedium">'.$langs->trans("ThirdpartyNotLinkedToMember").'</span>';
        }
        print '</td>';
        print "</tr>\n";
    }

	print "</table>";

	if ($object->client == 2 || $object->client == 3)
	{
    	print '<br>';

    	print '<div class="underbanner clearboth"></div>';
    	print '<table class="border centpercent tableforfield">';

	    // Level of prospect
	    print '<tr><td class="titlefield nowrap">';
	    print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	    print $langs->trans('ProspectLevel');
	    print '<td>';
	    if ($action != 'editlevel' && $user->rights->societe->creer) print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editlevel&amp;socid='.$object->id.'">'.img_edit($langs->trans('Modify'), 1).'</a></td>';
	    print '</tr></table>';
	    print '</td><td>';
	    if ($action == 'editlevel')
	    {
	        $formcompany->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->fk_prospectlevel, 'prospect_level_id', 1);
	    }
	    else
	    {
	        print $object->getLibProspLevel();
	    }
        print "</td>";
        print '</tr>';

        // Status
        $object->loadCacheOfProspStatus();
        print '<tr><td>'.$langs->trans("StatusProsp").'</td><td>'.$object->getLibProspCommStatut(4, $object->cacheprospectstatus[$object->stcomm_id]['label']);
        print ' &nbsp; &nbsp; ';
        print '<div class="floatright">';
        foreach($object->cacheprospectstatus as $key => $val)
        {
            $titlealt='default';
            if (! empty($val['code']) && ! in_array($val['code'], array('ST_NO', 'ST_NEVER', 'ST_TODO', 'ST_PEND', 'ST_DONE'))) $titlealt=$val['label'];
            if ($object->stcomm_id != $val['id']) print '<a class="pictosubstatus" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&stcomm='.$val['code'].'&action=setstcomm">'.img_action($titlealt, $val['code']).'</a>';
        }
        print '</div></td></tr>';
	   print "</table>";
	}

	print '</div><div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	$boxstat = '';

	// Nbre max d'elements des petites listes
	$MAXLIST=$conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

	// Lien recap
	$boxstat.='<div class="box">';
	$boxstat.='<table summary="'.dol_escape_htmltag($langs->trans("DolibarrStateBoard")).'" class="border boxtable boxtablenobottom boxtablenotop" width="100%">';
	$boxstat.='<tr class="impair"><td colspan="2" class="tdboxstats nohover">';

	if (! empty($conf->propal->enabled))
	{
		// Box proposals
		$tmp = $object->getOutstandingProposals();
		$outstandingOpened=$tmp['opened'];
		$outstandingTotal=$tmp['total_ht'];
		$outstandingTotalIncTax=$tmp['total_ttc'];
		$text=$langs->trans("OverAllProposals");
		$link=DOL_URL_ROOT.'/comm/propal/list.php?socid='.$object->id;
		$icon='bill';
		if ($link) $boxstat.='<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
		$boxstat.='<div class="boxstats" title="'.dol_escape_htmltag($text).'">';
		$boxstat.='<span class="boxstatstext">'.img_object("", $icon).' '.$text.'</span><br>';
		$boxstat.='<span class="boxstatsindicator">'.price($outstandingTotal, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
		$boxstat.='</div>';
		if ($link) $boxstat.='</a>';
	}

	if (! empty($conf->commande->enabled))
	{
		// Box commandes
		$tmp = $object->getOutstandingOrders();
		$outstandingOpened=$tmp['opened'];
		$outstandingTotal=$tmp['total_ht'];
		$outstandingTotalIncTax=$tmp['total_ttc'];
		$text=$langs->trans("OverAllOrders");
		$link=DOL_URL_ROOT.'/commande/list.php?socid='.$object->id;
		$icon='bill';
		if ($link) $boxstat.='<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
		$boxstat.='<div class="boxstats" title="'.dol_escape_htmltag($text).'">';
		$boxstat.='<span class="boxstatstext">'.img_object("", $icon).' '.$text.'</span><br>';
		$boxstat.='<span class="boxstatsindicator">'.price($outstandingTotal, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
		$boxstat.='</div>';
		if ($link) $boxstat.='</a>';
	}

	if (! empty($conf->facture->enabled))
	{
		// Box factures
		$tmp = $object->getOutstandingBills();
		$outstandingOpened=$tmp['opened'];
		$outstandingTotal=$tmp['total_ht'];
		$outstandingTotalIncTax=$tmp['total_ttc'];
		$text=$langs->trans("OverAllInvoices");
		$link=DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->id;
		$icon='bill';
		if ($link) $boxstat.='<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
		$boxstat.='<div class="boxstats" title="'.dol_escape_htmltag($text).'">';
		$boxstat.='<span class="boxstatstext">'.img_object("", $icon).' '.$text.'</span><br>';
		$boxstat.='<span class="boxstatsindicator">'.price($outstandingTotal, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
		$boxstat.='</div>';
		if ($link) $boxstat.='</a>';

		// Box outstanding bill
		$warn = '';
		if ($object->outstanding_limit != '' && $object->outstanding_limit < $outstandingOpened)
		{
			$warn = ' '.img_warning($langs->trans("OutstandingBillReached"));
		}
		$text=$langs->trans("CurrentOutstandingBill");
		$link=DOL_URL_ROOT.'/compta/recap-compta.php?socid='.$object->id;
		$icon='bill';
		if ($link) $boxstat.='<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
		$boxstat.='<div class="boxstats" title="'.dol_escape_htmltag($text).'">';
		$boxstat.='<span class="boxstatstext">'.img_object("", $icon).' '.$text.'</span><br>';
		$boxstat.='<span class="boxstatsindicator'.($outstandingOpened>0?' amountremaintopay':'').'">'.price($outstandingOpened, 1, $langs, 1, -1, -1, $conf->currency).$warn.'</span>';
		$boxstat.='</div>';
		if ($link) $boxstat.='</a>';
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreBoxStatsCustomer', $parameters, $object, $action);
	if(empty($reshook)){
		$boxstat.= $hookmanager->resPrint;
	}

	$boxstat.='</td></tr>';
	$boxstat.='</table>';
	$boxstat.='</div>';

	print $boxstat;

	$now=dol_now();

	/*
	 * Last proposals
	 */
	if (! empty($conf->propal->enabled) && $user->rights->propal->lire)
	{
		$langs->load("propal");

		$sql = "SELECT s.nom, s.rowid, p.rowid as propalid, p.fk_statut, p.total_ht";
        $sql.= ", p.tva as total_tva";
        $sql.= ", p.total as total_ttc";
        $sql.= ", p.ref, p.ref_client, p.remise";
		$sql.= ", p.datep as dp, p.fin_validite as datelimite";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
		$sql.= " WHERE p.fk_soc = s.rowid AND p.fk_statut = c.id";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND p.entity IN (".getEntity('propal').")";
		$sql.= " ORDER BY p.datep DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$propal_static = new Propal($db);

			$num = $db->num_rows($resql);
            if ($num > 0)
            {
            	print '<div class="div-table-responsive-no-min">';
            	print '<table class="noborder centpercent lastrecordtable">';

                print '<tr class="liste_titre">';
    			print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastPropals", ($num<=$MAXLIST?"":$MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/comm/propal/list.php?socid='.$object->id.'">'.$langs->trans("AllPropals").' <span class="badge">'.$num.'</span></a></td>';
                print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/comm/propal/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
    			print '</tr></table></td>';
    			print '</tr>';
            }

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				print '<tr class="oddeven">';
                print '<td class="nowrap">';
                $propal_static->id = $objp->propalid;
                $propal_static->ref = $objp->ref;
                $propal_static->ref_client = $objp->ref_client;
                $propal_static->total_ht = $objp->total_ht;
                $propal_static->total_tva = $objp->total_tva;
                $propal_static->total_ttc = $objp->total_ttc;
                print $propal_static->getNomUrl(1);
                if ( ($db->jdate($objp->datelimite) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == 1 ) {
                    print " ".img_warning();
                }
				print '</td><td class="right" width="80px">'.dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
				print '<td class="right" style="min-width: 60px">'.price($objp->total_ht).'</td>';
				print '<td class="right" style="min-width: 60px" class="nowrap">'.$propal_static->LibStatut($objp->fk_statut, 5).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num > 0)
			{
				print "</table>";
				print '</div>';
			}
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 * Last orders
	 */
	if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
	{
        $sql = "SELECT s.nom, s.rowid";
        $sql.= ", c.rowid as cid, c.total_ht";
        $sql.= ", c.tva as total_tva";
        $sql.= ", c.total_ttc";
        $sql.= ", c.ref, c.ref_client, c.fk_statut, c.facture";
        $sql.= ", c.date_commande as dc";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
		$sql.= " WHERE c.fk_soc = s.rowid ";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " ORDER BY c.date_commande DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$commande_static=new Commande($db);

			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				// Check if there are orders billable
				$sql2 = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
				$sql2.= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut, c.facture as billed';
				$sql2.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
				$sql2.= ', '.MAIN_DB_PREFIX.'commande as c';
				$sql2.= ' WHERE c.fk_soc = s.rowid';
				$sql2.= ' AND s.rowid = '.$object->id;
				// Show orders with status validated, shipping started and delivered (well any order we can bill)
				$sql2.= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))";

				$resql2=$db->query($sql2);
				$orders2invoice = $db->num_rows($resql2);
				$db->free($resql2);

				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastCustomerOrders", ($num<=$MAXLIST?"":$MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->id.'">'.$langs->trans("AllOrders").' <span class="badge">'.$num.'</span></a></td>';
				print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/commande/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				//if($num2 > 0) print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$object->id.'">'.img_picto($langs->trans("CreateInvoiceForThisCustomer"),'object_bill').'</a></td>';
				//else print '<td width="20px" class="right"><a href="#">'.img_picto($langs->trans("NoOrdersToInvoice"),'object_bill').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				$commande_static->id = $objp->cid;
				$commande_static->ref = $objp->ref;
				$commande_static->ref_client=$objp->ref_client;
				$commande_static->total_ht = $objp->total_ht;
				$commande_static->total_tva = $objp->total_tva;
				$commande_static->total_ttc = $objp->total_ttc;
				$commande_static->billed = $objp->billed;

				print '<tr class="oddeven">';
                print '<td class="nowrap">';
                print $commande_static->getNomUrl(1);
				print '</td><td class="right" width="80px">'.dol_print_date($db->jdate($objp->dc), 'day')."</td>\n";
				print '<td class="right" style="min-width: 60px">'.price($objp->total_ht).'</td>';
				print '<td class="right" style="min-width: 60px" class="nowrap">'.$commande_static->LibStatut($objp->fk_statut, $objp->facture, 5).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num >0)
			{
				print "</table>";
				print '</div>';
			}
		}
		else
		{
			dol_print_error($db);
		}
	}

    /*
     *   Last shipments
     */
    if (! empty($conf->expedition->enabled) && $user->rights->expedition->lire)
    {
        $sql = 'SELECT e.rowid as id';
        $sql.= ', e.ref';
        $sql.= ', e.date_creation';
        $sql.= ', e.fk_statut as statut';
        $sql.= ', s.nom';
        $sql.= ', s.rowid as socid';
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."expedition as e";
        $sql.= " WHERE e.fk_soc = s.rowid AND s.rowid = ".$object->id;
        $sql.= " AND e.entity IN (".getEntity('expedition').")";
        $sql.= ' GROUP BY e.rowid';
        $sql.= ', e.ref';
        $sql.= ', e.date_creation';
        $sql.= ', e.fk_statut';
        $sql.= ', s.nom';
        $sql.= ', s.rowid';
        $sql.= " ORDER BY e.date_creation DESC";

        $resql = $db->query($sql);
        if ($resql)
        {
        	$sendingstatic = new Expedition($db);

        	$num = $db->num_rows($resql);
            if ($num > 0) {
            	print '<div class="div-table-responsive-no-min">';
            	print '<table class="noborder centpercent lastrecordtable">';

                print '<tr class="liste_titre">';
                print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastSendings", ($num<=$MAXLIST?"":$MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/expedition/list.php?socid='.$object->id.'">'.$langs->trans("AllSendings").' <span class="badge">'.$num.'</span></a></td>';
                print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/expedition/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
                print '</tr></table></td>';
                print '</tr>';
            }

            $i = 0;
            while ($i < $num && $i < $MAXLIST)
            {
                $objp = $db->fetch_object($resql);

                $sendingstatic->id = $objp->id;
                $sendingstatic->ref = $objp->ref;

                print '<tr class="oddeven">';
                print '<td class="nowrap">';
                print $sendingstatic->getNomUrl(1);
                print '</td>';
                if ($objp->date_creation > 0) {
                    print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->date_creation), 'day').'</td>';
                } else {
                    print '<td class="right"><b>!!!</b></td>';
                }

                print '<td class="nowrap right" width="100" >' . $sendingstatic->LibStatut($objp->statut, 5) . '</td>';
                print "</tr>\n";
                $i++;
            }
            $db->free($resql);

            if ($num > 0)
            {
                print "</table>";
                print '</div>';
            }
        } else {
            dol_print_error($db);
        }
    }

	/*
	 * Last linked contracts
	 */
	if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
	{
		$sql = "SELECT s.nom, s.rowid, c.rowid as id, c.ref as ref, c.statut, c.datec as dc, c.date_contrat as dcon, c.ref_customer as refcus, c.ref_supplier as refsup";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
		$sql.= " WHERE c.fk_soc = s.rowid ";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " ORDER BY c.datec DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$contrat=new Contrat($db);

			$num = $db->num_rows($resql);
			if ($num >0)
			{
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

			    print '<tr class="liste_titre">';
				print '<td colspan="6"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastContracts", ($num<=$MAXLIST?"":$MAXLIST)).'</td>';
				print '<td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/contrat/list.php?socid='.$object->id.'">'.$langs->trans("AllContracts").' <span class="badge">'.$num.'</span></a></td>';
				//print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/contract/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				$contrat->id=$objp->id;
				$contrat->ref=$objp->ref?$objp->ref:$objp->id;
				$contrat->ref_customer=$objp->refcus;
				$contrat->ref_supplier=$objp->refsup;
				$contrat->fetch_lines();

				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $contrat->getNomUrl(1, 12);
				print "</td>\n";
				print '<td class="nowrap">'.dol_trunc($objp->refsup, 12)."</td>\n";
				print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->dc), 'day')."</td>\n";
				print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->dcon), 'day')."</td>\n";
				print '<td width="20">&nbsp;</td>';
				print '<td class="nowraponall right">';
				print $contrat->getLibStatut(4);
				print "</td>\n";
				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num > 0)
			{
				print "</table>";
				print '</div>';
			}
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 * Last interventions
	 */
	if (! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->lire)
	{
		$sql = "SELECT s.nom, s.rowid, f.rowid as id, f.ref, f.fk_statut, f.duree as duration, f.datei as startdate";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f";
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " ORDER BY f.tms DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$fichinter_static=new Fichinter($db);

			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

			    print '<tr class="liste_titre">';
				print '<td colspan="3"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastInterventions", ($num<=$MAXLIST?"":$MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/fichinter/list.php?socid='.$object->id.'">'.$langs->trans("AllInterventions").' <span class="badge">'.$num.'</span></td>';
				print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/fichinter/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				$fichinter_static->id=$objp->id;
                $fichinter_static->statut=$objp->fk_statut;

				print '<tr class="oddeven">';
				print '<td class="nowrap"><a href="'.DOL_URL_ROOT.'/fichinter/card.php?id='.$objp->id.'">'.img_object($langs->trans("ShowPropal"), "propal").' '.$objp->ref.'</a></td>'."\n";
                //print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->startdate)).'</td>'."\n";
				print '<td class="right" style="min-width: 60px">'.convertSecondToTime($objp->duration).'</td>'."\n";
				print '<td class="nowrap right" style="min-width: 60px">'.$fichinter_static->getLibStatut(5).'</td>'."\n";
				print '</tr>';

				$i++;
			}
			$db->free($resql);

			if ($num > 0)
			{
				print "</table>";
				print '</div>';
			}
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 *   Last invoices templates
	 */
	if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
	{
		$sql = 'SELECT f.rowid as id, f.titre as ref, f.amount';
		$sql.= ', f.total as total_ht';
		$sql.= ', f.tva as total_tva';
		$sql.= ', f.total_ttc';
		$sql.= ', f.datec as dc';
		$sql.= ', f.date_last_gen, f.date_when';
		$sql.= ', f.frequency';
		$sql.= ', f.unit_frequency';
		$sql.= ', f.suspended as suspended';
		$sql.= ', s.nom, s.rowid as socid';
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_rec as f";
		$sql.= " WHERE f.fk_soc = s.rowid AND s.rowid = ".$object->id;
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= ' GROUP BY f.rowid, f.titre, f.amount, f.total, f.tva, f.total_ttc,';
		$sql.= ' f.date_last_gen, f.datec, f.frequency, f.unit_frequency,';
		$sql.= ' f.suspended,';
		$sql.= ' s.nom, s.rowid';
		$sql.= " ORDER BY f.date_last_gen, f.datec DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$invoicetemplate = new FactureRec($db);

			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LatestCustomerTemplateInvoices", ($num<=$MAXLIST?"":$MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/compta/facture/invoicetemplate_list.php?socid='.$object->id.'">'.$langs->trans("AllCustomerTemplateInvoices").' <span class="badge">'.$num.'</span></a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				$invoicetemplate->id = $objp->id;
				$invoicetemplate->ref = $objp->ref;
				$invoicetemplate->suspended = $objp->suspended;
				$invoicetemplate->frequency = $objp->frequency;
				$invoicetemplate->unit_frequency = $objp->unit_frequency;
				$invoicetemplate->total_ht = $objp->total_ht;
				$invoicetemplate->total_tva = $objp->total_tva;
				$invoicetemplate->total_ttc = $objp->total_ttc;
				$invoicetemplate->date_last_gen = $objp->date_last_gen;
				$invoicetemplate->date_when = $objp->date_when;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $invoicetemplate->getNomUrl(1);
				print '</td>';
				if ($objp->frequency && $objp->date_last_gen > 0)
				{
					print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->date_last_gen), 'day').'</td>';
				}
				else
				{
					if ($objp->dc > 0)
					{
						print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->dc), 'day').'</td>';
					}
					else
					{
						print '<td class="right"><b>!!!</b></td>';
					}
				}
				print '<td class="right" style="min-width: 60px">';
				print price($objp->total_ht);
				print '</td>';

				if (! empty($conf->global->MAIN_SHOW_PRICE_WITH_TAX_IN_SUMMARIES))
				{
					print '<td class="right" style="min-width: 60px">';
					print price($objp->total_ttc);
					print '</td>';
				}

				print '<td class="nowrap right" style="min-width: 60px">';
				print $langs->trans('FrequencyPer_'.$invoicetemplate->unit_frequency, $invoicetemplate->frequency).' - ';
				print ($invoicetemplate->LibStatut($invoicetemplate->frequency, $invoicetemplate->suspended, 5, 0));
				print '</td>';
				print "</tr>\n";
				$i++;
			}
			$db->free($resql);

			if ($num > 0)
			{
				print "</table>";
				print '</div>';
			}
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 *   Last invoices
	 */
	if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
	{
        $sql = 'SELECT f.rowid as facid, f.ref, f.type, f.amount';
        $sql.= ', f.total as total_ht';
        $sql.= ', f.tva as total_tva';
        $sql.= ', f.total_ttc';
		$sql.= ', f.datef as df, f.datec as dc, f.paye as paye, f.fk_statut as statut';
		$sql.= ', s.nom, s.rowid as socid';
		$sql.= ', SUM(pf.amount) as am';
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture';
		$sql.= " WHERE f.fk_soc = s.rowid AND s.rowid = ".$object->id;
		$sql.= " AND f.entity IN (".getEntity('invoice').")";
		$sql.= ' GROUP BY f.rowid, f.ref, f.type, f.amount, f.total, f.tva, f.total_ttc,';
		$sql.= ' f.datef, f.datec, f.paye, f.fk_statut,';
		$sql.= ' s.nom, s.rowid';
		$sql.= " ORDER BY f.datef DESC, f.datec DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$facturestatic = new Facture($db);

			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="5"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastCustomersBills", ($num<=$MAXLIST?"":$MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->id.'">'.$langs->trans("AllBills").' <span class="badge">'.$num.'</span></a></td>';
                print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				$facturestatic->id = $objp->facid;
				$facturestatic->ref = $objp->ref;
				$facturestatic->type = $objp->type;
				$facturestatic->total_ht = $objp->total_ht;
				$facturestatic->total_tva = $objp->total_tva;
				$facturestatic->total_ttc = $objp->total_ttc;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $facturestatic->getNomUrl(1);
				print '</td>';
				if ($objp->df > 0)
				{
					print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->df), 'day').'</td>';
				}
				else
				{
					print '<td class="right"><b>!!!</b></td>';
				}
				print '<td class="right" style="min-width: 60px">';
				print price($objp->total_ht);
				print '</td>';

				if (! empty($conf->global->MAIN_SHOW_PRICE_WITH_TAX_IN_SUMMARIES))
				{
    				print '<td class="right" style="min-width: 60px">';
    				print price($objp->total_ttc);
    				print '</td>';
				}

				print '<td class="nowrap right" style="min-width: 60px">'.($facturestatic->LibStatut($objp->paye, $objp->statut, 5, $objp->am)).'</td>';
				print "</tr>\n";
				$i++;
			}
			$db->free($resql);

			if ($num > 0)
			{
				print "</table>";
				print '</div>';
			}
		}
		else
		{
			dol_print_error($db);
		}
	}

	print '</div></div></div>';
	print '<div style="clear:both"></div>';

	dol_fiche_end();


	/*
	 * Barre d'actions
	 */

	print '<div class="tabsAction">';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been

    if (empty($reshook))
    {
        if ($object->status != 1)
        {
            print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("ThirdPartyIsClosed")).'" href="#">'.$langs->trans("ThirdPartyIsClosed").'</a></div>';
        }

    	if (! empty($conf->propal->enabled) && $user->rights->propal->creer && $object->status==1)
    	{
    		$langs->load("propal");
    		print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddProp").'</a></div>';
    	}

    	if (! empty($conf->commande->enabled) && $user->rights->commande->creer && $object->status==1)
    	{
    		$langs->load("orders");
    		print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/commande/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddOrder").'</a></div>';
    	}

    	if ($user->rights->contrat->creer && $object->status==1)
    	{
    		$langs->load("contracts");
    		print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/contrat/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddContract").'</a></div>';
    	}

    	if (! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->creer && $object->status==1)
    	{
    		$langs->load("fichinter");
    		print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddIntervention").'</a></div>';
    	}

    	// Add invoice
    	if ($user->societe_id == 0)
    	{
    		if (! empty($conf->deplacement->enabled) && $object->status==1)
    		{
    			$langs->load("trips");
    			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/deplacement/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddTrip").'</a></div>';
    		}

    		if (! empty($conf->facture->enabled) && $object->status==1)
    		{
    			if (empty($user->rights->facture->creer))
    			{
    			    print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddBill").'</a></div>';
    			}
    			else
    			{
    				$langs->load("bills");
    				$langs->load("orders");

    				if (! empty($conf->commande->enabled))
    				{
    				    if ($object->client != 0 && $object->client != 2)
    				    {
    					   if (! empty($orders2invoice) && $orders2invoice > 0) print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$object->id.'">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
    					   else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("NoOrdersToInvoice")).'" href="#">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
    				    }
    				    else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a></div>';
    				}

    				if ($object->client != 0 && $object->client != 2) print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddBill").'</a></div>';
    				else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a></div>';
    			}
    		}
    	}

    	// Add action
    	if (! empty($conf->agenda->enabled) && ! empty($conf->global->MAIN_REPEATTASKONEACHTAB) && $object->status==1)
    	{
    		if ($user->rights->agenda->myactions->create)
    		{
    			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddAction").'</a></div>';
    		}
    		else
    		{
    			print '<div class="inline-block divButAction"><a class="butAction" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a></div>';
    		}
    	}
    }

	print '</div>';

	if (! empty($conf->global->MAIN_DUPLICATE_CONTACTS_TAB_ON_CUSTOMER_CARD))
	{
		// List of contacts
		show_contacts($conf, $langs, $db, $object, $_SERVER["PHP_SELF"].'?socid='.$object->id);
	}

    if (! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
    {
        print load_fiche_titre($langs->trans("ActionsOnCompany"), '', '');

        // List of todo actions
		show_actions_todo($conf, $langs, $db, $object);

        // List of done actions
		show_actions_done($conf, $langs, $db, $object);
	}
}
else
{
	$langs->load("errors");
	print $langs->trans('ErrorRecordNotFound');
}

// End of page
llxFooter();
$db->close();
