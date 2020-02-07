<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Peter Fontaine       <contact@peterfontaine.fr>
 * Copyright (C) 2015-2016 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018      ptibogxiv            <support@ptibogxiv.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/societe/paymentmodes.php
 *      \ingroup    societe
 *		\brief      Tab of payment modes for the customer
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companypaymentmode.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

$langs->loadLangs(array("companies", "commercial", "banks", "bills", 'paypal', 'stripe', 'withdrawals'));


// Security check
$socid = GETPOST("socid", "int");
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'societe', '', '');

$id = GETPOST("id", "int");
$source = GETPOST("source", "alpha"); // source can be a source or a paymentmode
$ribid = GETPOST("ribid", "int");
$action = GETPOST("action", 'alpha', 3);
$cancel = GETPOST('cancel', 'alpha');

$object = new Societe($db);
$object->fetch($socid);

$companybankaccount = new CompanyBankAccount($db);
$companypaymentmode = new CompanyPaymentMode($db);
$prelevement = new BonPrelevement($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartybancard', 'globalcard'));


if (!empty($conf->stripe->enabled))
{
	$service = 'StripeTest';
	$servicestatus = 0;
	if (!empty($conf->global->STRIPE_LIVE) && !GETPOST('forcesandbox', 'alpha'))
	{
		$service = 'StripeLive';
		$servicestatus = 1;
	}

	// Force to use the correct API key
	global $stripearrayofkeysbyenv;
	$site_account = $stripearrayofkeysbyenv[$servicestatus]['publishable_key'];

	$stripe = new Stripe($db);
	$stripeacc = $stripe->getStripeAccount($service); // Get Stripe OAuth connect account (no remote access to Stripe here)
	$stripecu = $stripe->getStripeCustomerAccount($object->id, $servicestatus, $site_account); // Get remote Stripe customer 'cus_...' (no remote access to Stripe here)
}



/*
 *	Actions
 */

if ($cancel)
{
	$action = '';
}

$parameters = array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel)
	{
		$action = '';
		if (!empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
	}

	if ($action == 'update')
	{
		// Modification
		if (!GETPOST('label', 'alpha') || !GETPOST('bank', 'alpha'))
		{
			if (!GETPOST('label', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
			if (!GETPOST('bank', 'alpha'))  setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankName")), null, 'errors');
			$action = 'edit';
			$error++;
		}
		if ($companybankaccount->needIBAN() == 1)
		{
			if (!GETPOST('iban'))
			{
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("IBAN")), null, 'errors');
				$action = 'edit';
				$error++;
			}
			if (!GETPOST('bic'))
			{
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BIC")), null, 'errors');
				$action = 'edit';
				$error++;
			}
		}

		$companybankaccount->fetch($id);
		if (!$error)
		{
			$companybankaccount->socid           = $object->id;

			$companybankaccount->bank            = GETPOST('bank', 'alpha');
			$companybankaccount->label           = GETPOST('label', 'alpha');
			$companybankaccount->courant         = GETPOST('courant', 'alpha');
			$companybankaccount->clos            = GETPOST('clos', 'alpha');
			$companybankaccount->code_banque     = GETPOST('code_banque', 'alpha');
			$companybankaccount->code_guichet    = GETPOST('code_guichet', 'alpha');
			$companybankaccount->number          = GETPOST('number', 'alpha');
			$companybankaccount->cle_rib         = GETPOST('cle_rib', 'alpha');
			$companybankaccount->bic             = GETPOST('bic', 'alpha');
			$companybankaccount->iban            = GETPOST('iban', 'alpha');
			$companybankaccount->domiciliation   = GETPOST('domiciliation', 'alpha');
			$companybankaccount->proprio         = GETPOST('proprio', 'alpha');
			$companybankaccount->owner_address   = GETPOST('owner_address', 'alpha');
			$companybankaccount->frstrecur       = GETPOST('frstrecur', 'alpha');
			$companybankaccount->rum             = GETPOST('rum', 'alpha');
			$companybankaccount->date_rum        = dol_mktime(0, 0, 0, GETPOST('date_rummonth'), GETPOST('date_rumday'), GETPOST('date_rumyear'));
			if (empty($companybankaccount->rum))
			{
				$companybankaccount->rum = $prelevement->buildRumNumber($object->code_client, $companybankaccount->datec, $companybankaccount->id);
			}
			if (empty($companybankaccount->date_rum))
			{
				$companybankaccount->date_rum = dol_now();
			}

			$result = $companybankaccount->update($user);
			if (!$result)
			{
				setEventMessages($companybankaccount->error, $companybankaccount->errors, 'errors');
			}
			else
			{
				// If this account is the default bank account, we disable others
				if ($companybankaccount->default_rib)
				{
					$companybankaccount->setAsDefault($id); // This will make sure there is only one default rib
				}

				$url = $_SERVER["PHP_SELF"].'?socid='.$object->id;
				header('Location: '.$url);
				exit;
			}
		}
	}

	if ($action == 'updatecard')
	{
		// Modification
		if (!GETPOST('label', 'alpha') || !GETPOST('proprio', 'alpha') || !GETPOST('cardnumber', 'alpha') || !GETPOST('exp_date_month', 'alpha') || !GETPOST('exp_date_year', 'alpha') || !GETPOST('cvn', 'alpha'))
		{
			if (!GETPOST('label', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
			if (!GETPOST('proprio', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NameOnCard")), null, 'errors');
			if (!GETPOST('cardnumber', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CardNumber")), null, 'errors');
			if (!(GETPOST('exp_date_month', 'alpha') > 0) || !(GETPOST('exp_date_year', 'alpha') > 0)) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExpiryDate")), null, 'errors');
			if (!GETPOST('cvn', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CVN")), null, 'errors');
			$action = 'createcard';
			$error++;
		}

		$companypaymentmode->fetch($id);
		if (!$error)
		{
			$companypaymentmode->fk_soc          = $object->id;

			$companypaymentmode->bank            = GETPOST('bank', 'alpha');
			$companypaymentmode->label           = GETPOST('label', 'alpha');
			$companypaymentmode->number          = GETPOST('cardnumber', 'alpha');
			$companypaymentmode->last_four       = substr(GETPOST('cardnumber', 'alpha'), -4);
			$companypaymentmode->proprio         = GETPOST('proprio', 'alpha');
			$companypaymentmode->exp_date_month  = GETPOST('exp_date_month', 'int');
			$companypaymentmode->exp_date_year   = GETPOST('exp_date_year', 'int');
			$companypaymentmode->cvn             = GETPOST('cvn', 'alpha');
			$companypaymentmode->country_code    = $object->country_code;

			$companypaymentmode->stripe_card_ref = GETPOST('stripe_card_ref', 'alpha');

			$result = $companypaymentmode->update($user);
			if (!$result)
			{
				setEventMessages($companypaymentmode->error, $companypaymentmode->errors, 'errors');
			}
			else
			{
				// If this account is the default bank account, we disable others
				if ($companypaymentmode->default_rib)
				{
					$companypaymentmode->setAsDefault($id); // This will make sure there is only one default rib
				}

				$url = $_SERVER["PHP_SELF"].'?socid='.$object->id;
				header('Location: '.$url);
				exit;
			}
		}
	}

	if ($action == 'add')
	{
		$error = 0;

		if (!GETPOST('label', 'alpha') || !GETPOST('bank', 'alpha'))
		{
			if (!GETPOST('label', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
			if (!GETPOST('bank', 'alpha'))  setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankName")), null, 'errors');
			$action = 'create';
			$error++;
		}

		if (!$error)
		{
			// Ajout
			$companybankaccount = new CompanyBankAccount($db);

			$companybankaccount->socid           = $object->id;

			$companybankaccount->bank            = GETPOST('bank', 'alpha');
			$companybankaccount->label           = GETPOST('label', 'alpha');
			$companybankaccount->courant         = GETPOST('courant', 'alpha');
			$companybankaccount->clos            = GETPOST('clos', 'alpha');
			$companybankaccount->code_banque     = GETPOST('code_banque', 'alpha');
			$companybankaccount->code_guichet    = GETPOST('code_guichet', 'alpha');
			$companybankaccount->number          = GETPOST('number', 'alpha');
			$companybankaccount->cle_rib         = GETPOST('cle_rib', 'alpha');
			$companybankaccount->bic             = GETPOST('bic', 'alpha');
			$companybankaccount->iban            = GETPOST('iban', 'alpha');
			$companybankaccount->domiciliation   = GETPOST('domiciliation', 'alpha');
			$companybankaccount->proprio         = GETPOST('proprio', 'alpha');
			$companybankaccount->owner_address   = GETPOST('owner_address', 'alpha');
			$companybankaccount->frstrecur       = GETPOST('frstrecur');
			$companybankaccount->rum             = GETPOST('rum', 'alpha');
			$companybankaccount->date_rum        = dol_mktime(0, 0, 0, GETPOST('date_rummonth'), GETPOST('date_rumday'), GETPOST('date_rumyear'));
			$companybankaccount->datec = dol_now();
			$companybankaccount->status          = 1;

			$db->begin();

			// This test can be done only once properties were set
			if ($companybankaccount->needIBAN() == 1)
			{
				if (!GETPOST('iban'))
				{
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("IBAN")), null, 'errors');
					$action = 'create';
					$error++;
				}
				if (!GETPOST('bic'))
				{
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BIC")), null, 'errors');
					$action = 'create';
					$error++;
				}
			}

			if (!$error)
			{
				$result = $companybankaccount->create($user);
				if ($result < 0)
				{
					$error++;
					setEventMessages($companybankaccount->error, $companybankaccount->errors, 'errors');
					$action = 'create'; // Force chargement page création
				}

				if (empty($companybankaccount->rum))
				{
					$companybankaccount->rum = $prelevement->buildRumNumber($object->code_client, $companybankaccount->datec, $companybankaccount->id);
					$companybankaccount->date_rum = dol_now();
				}
			}

			if (!$error)
			{
				$result = $companybankaccount->update($user); // This will set the UMR number.
				if ($result < 0)
				{
					$error++;
					setEventMessages($companybankaccount->error, $companybankaccount->errors, 'errors');
					$action = 'create';
				}
			}

			if (!$error)
			{
				$db->commit();

				$url = $_SERVER["PHP_SELF"].'?socid='.$object->id;
				header('Location: '.$url);
				exit;
			}
			else
			{
				$db->rollback();
			}
		}
	}

	if ($action == 'addcard')
	{
		$error = 0;

		if (!GETPOST('label', 'alpha') || !GETPOST('proprio', 'alpha') || !GETPOST('cardnumber', 'alpha') || !GETPOST('exp_date_month', 'alpha') || !GETPOST('exp_date_year', 'alpha') || !GETPOST('cvn', 'alpha'))
		{
			if (!GETPOST('label', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
			if (!GETPOST('proprio', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NameOnCard")), null, 'errors');
			if (!GETPOST('cardnumber', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CardNumber")), null, 'errors');
			if (!(GETPOST('exp_date_month', 'alpha') > 0) || !(GETPOST('exp_date_year', 'alpha') > 0)) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExpiryDate")), null, 'errors');
			if (!GETPOST('cvn', 'alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CVN")), null, 'errors');
			$action = 'createcard';
			$error++;
		}

		if (!$error)
		{
			// Ajout
			$companypaymentmode = new CompanyPaymentMode($db);

			$companypaymentmode->fk_soc          = $object->id;
			$companypaymentmode->bank            = GETPOST('bank', 'alpha');
			$companypaymentmode->label           = GETPOST('label', 'alpha');
			$companypaymentmode->number          = GETPOST('cardnumber', 'alpha');
			$companypaymentmode->last_four       = substr(GETPOST('cardnumber', 'alpha'), -4);
			$companypaymentmode->proprio         = GETPOST('proprio', 'alpha');
			$companypaymentmode->exp_date_month  = GETPOST('exp_date_month', 'int');
			$companypaymentmode->exp_date_year   = GETPOST('exp_date_year', 'int');
			$companypaymentmode->cvn             = GETPOST('cvn', 'alpha');
			$companypaymentmode->datec           = dol_now();
			$companypaymentmode->default_rib     = 0;
			$companypaymentmode->type            = 'card';
			$companypaymentmode->country_code    = $object->country_code;
			$companypaymentmode->status          = $servicestatus;

			$companypaymentmode->stripe_card_ref = GETPOST('stripe_card_ref', 'alpha');

			$db->begin();

			if (!$error)
			{
				$result = $companypaymentmode->create($user);
				if ($result < 0)
				{
					$error++;
					setEventMessages($companypaymentmode->error, $companypaymentmode->errors, 'errors');
					$action = 'createcard'; // Force chargement page création
				}
			}

			if (!$error)
			{
				$db->commit();

				$url = $_SERVER["PHP_SELF"].'?socid='.$object->id;
				header('Location: '.$url);
				exit;
			}
			else
			{
				$db->rollback();
			}
		}
	}

	if ($action == 'setasbankdefault' && GETPOST('ribid', 'int') > 0)
	{
		$companybankaccount = new CompanyBankAccount($db);
		$res = $companybankaccount->setAsDefault(GETPOST('ribid', 'int'));
		if ($res)
		{
			$url = DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id;
			header('Location: '.$url);
			exit;
		}
		else
		{
			setEventMessages($db->lasterror, null, 'errors');
		}
	}

	if ($action == 'confirm_deletecard' && GETPOST('confirm', 'alpha') == 'yes')
	{
		$companypaymentmode = new CompanyPaymentMode($db);
		if ($companypaymentmode->fetch($ribid ? $ribid : $id))
		{
			/*if ($companypaymentmode->stripe_card_ref && preg_match('/pm_/', $companypaymentmode->stripe_card_ref))
			{
				$payment_method = \Stripe\PaymentMethod::retrieve($companypaymentmode->stripe_card_ref);
				if ($payment_method)
				{
					$payment_method->detach();
				}
			}*/

			$result = $companypaymentmode->delete($user);
			if ($result > 0)
			{
				$url = $_SERVER['PHP_SELF']."?socid=".$object->id;
				header('Location: '.$url);
				exit;
			}
			else
			{
				setEventMessages($companypaymentmode->error, $companypaymentmode->errors, 'errors');
			}
		}
		else
		{
			setEventMessages($companypaymentmode->error, $companypaymentmode->errors, 'errors');
		}
	}
	if ($action == 'confirm_delete' && GETPOST('confirm', 'alpha') == 'yes')
	{
		$companybankaccount = new CompanyBankAccount($db);
		if ($companybankaccount->fetch($ribid ? $ribid : $id))
		{
			$result = $companybankaccount->delete($user);
			if ($result > 0)
			{
				$url = $_SERVER['PHP_SELF']."?socid=".$object->id;
				header('Location: '.$url);
				exit;
			}
			else
			{
				setEventMessages($companybankaccount->error, $companybankaccount->errors, 'errors');
			}
		}
		else
		{
			setEventMessages($companybankaccount->error, $companybankaccount->errors, 'errors');
		}
	}

	$savid = $id;

	// Actions to build doc
	if ($action == 'builddocrib')
	{
		$action = 'builddoc';
		$moreparams = array(
			'use_companybankid'=>GETPOST('companybankid'),
			'force_dir_output'=>$conf->societe->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->id)
		);
		$_POST['lang_id'] = GETPOST('lang_idrib'.GETPOST('companybankid', 'int'), 'alpha');
		$_POST['model'] = GETPOST('modelrib'.GETPOST('companybankid', 'int'), 'alpha');
	}

	$id = $socid;
	$upload_dir = $conf->societe->multidir_output[$object->entity];
	$permissiontoadd = $user->rights->societe->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	$id = $savid;

	// Action for stripe
	if (!empty($conf->stripe->enabled) && class_exists('Stripe'))
	{
		if ($action == 'synccustomertostripe')
		{
			if ($object->client == 0)
			{
				$error++;
				setEventMessages('ThisThirdpartyIsNotACustomer', null, 'errors');
			}
			else
			{
				// Creation of Stripe customer + update of societe_account
				$cu = $stripe->customerStripe($object, $stripeacc, $servicestatus, 1);
				if (!$cu)
				{
					$error++;
					setEventMessages($stripe->error, $stripe->errors, 'errors');
				}
				else
				{
					$stripecu = $cu->id;
				}
			}
		}
		if ($action == 'synccardtostripe')
		{
			$companypaymentmode = new CompanyPaymentMode($db);
			$companypaymentmode->fetch($id);

			if ($companypaymentmode->type != 'card')
			{
				$error++;
				setEventMessages('ThisPaymentModeIsNotACard', null, 'errors');
			}
			else
			{
				// Get the Stripe customer
				$cu = $stripe->customerStripe($object, $stripeacc, $servicestatus);
				if (!$cu)
				{
					$error++;
					setEventMessages($stripe->error, $stripe->errors, 'errors');
				}

				if (!$error)
				{
					// Creation of Stripe card + update of societe_account
					// Note that with the new Stripe API, option to create a card is no more available, instead an error message will be returned to
					// ask to create the crdit card from Stripe backoffice.
					$card = $stripe->cardStripe($cu, $companypaymentmode, $stripeacc, $servicestatus, 1);
					if (!$card)
					{
						$error++;
						setEventMessages($stripe->error, $stripe->errors, 'errors');
					}
				}
			}
		}

		if ($action == 'setkey_account')
		{
			$error = 0;

			$newcu = GETPOST('key_account', 'alpha');

			$db->begin();

            if (empty($newcu)) {
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_account WHERE site = 'stripe' AND (site_account IS NULL or site_account = '' or site_account = '".$site_account."') AND fk_soc = ".$object->id." AND status = ".$servicestatus." AND entity = ".$conf->entity;
            } else {
                $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX."societe_account";
                $sql .= " WHERE site = 'stripe' AND (site_account IS NULL or site_account = '' or site_account = '".$site_account."') AND fk_soc = ".$object->id." AND status = ".$servicestatus." AND entity = ".$conf->entity; // Keep = here for entity. Only 1 record must be modified !
            }

			$resql = $db->query($sql);
			$num = $db->num_rows($resql);		// Note: $num is always 0 on an update and delete, it is defined for select only.
			if (!empty($newcu)) {
				if (empty($num))
				{
					$societeaccount = new SocieteAccount($db);
					$societeaccount->fk_soc = $object->id;
					$societeaccount->login = '';
					$societeaccount->pass_encoding = '';
					$societeaccount->site = 'stripe';
					$societeaccount->status = $servicestatus;
					$societeaccount->key_account = $newcu;
					$societeaccount->site_account = $site_account;
					$result = $societeaccount->create($user);
					if ($result < 0)
					{
						$error++;
					}
				} else {
					$sql = 'UPDATE '.MAIN_DB_PREFIX."societe_account";
					$sql .= " SET key_account = '".$db->escape(GETPOST('key_account', 'alpha'))."', site_account = '".$site_account."'";
					$sql .= " WHERE site = 'stripe' AND (site_account IS NULL or site_account = '' or site_account = '".$site_account."') AND fk_soc = ".$object->id." AND status = ".$servicestatus." AND entity = ".$conf->entity; // Keep = here for entity. Only 1 record must be modified !
					$resql = $db->query($sql);
				}
			}
			//var_dump($sql);	var_dump($newcu);		var_dump($num); exit;

			if (!$error)
			{
				$stripecu = $newcu;
				$db->commit();
			}
			else
			{
				$db->rollback();
			}
		}

		if ($action == 'setkey_account_supplier')
		{
			$error = 0;

			$newsup = GETPOST('key_account_supplier', 'alpha');

			$db->begin();

            if (empty($newsup)) {
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."oauth_token WHERE fk_soc = ".$object->id." AND service = '".$service."' AND entity = ".$conf->entity;
                // TODO Add site and site_account on oauth_token table
                //$sql = "DELETE FROM ".MAIN_DB_PREFIX."oauth_token WHERE site = 'stripe' AND (site_account IS NULL or site_account = '".$site_account."') AND fk_soc = ".$object->id." AND service = '".$service."' AND entity = ".$conf->entity;
            } else {
                try {
                    $stripesup = \Stripe\Account::retrieve($db->escape(GETPOST('key_account_supplier', 'alpha')));
                    $tokenstring['stripe_user_id'] = $stripesup->id;
                    $tokenstring['type'] = $stripesup->type;
                    $sql = "UPDATE ".MAIN_DB_PREFIX."oauth_token";
                    $sql .= " SET tokenstring = '".dol_json_encode($tokenstring)."'";
                    $sql .= " WHERE site = 'stripe' AND (site_account IS NULL or site_account = '".$site_account."') AND fk_soc = ".$object->id." AND service = '".$service."' AND entity = ".$conf->entity; // Keep = here for entity. Only 1 record must be modified !
                    // TODO Add site and site_account on oauth_token table
                    $sql .= " WHERE fk_soc = ".$object->id." AND service = '".$service."' AND entity = ".$conf->entity; // Keep = here for entity. Only 1 record must be modified !
                } catch (Exception $e) {
					$error++;
					setEventMessages($e->getMessage(), null, 'errors');
				}
			}

			$resql = $db->query($sql);
			$num = $db->num_rows($resql);
			if (empty($num) && !empty($newsup))
			{
                try {
                    $stripesup = \Stripe\Account::retrieve($db->escape(GETPOST('key_account_supplier', 'alpha')));
                    $tokenstring['stripe_user_id'] = $stripesup->id;
                    $tokenstring['type'] = $stripesup->type;
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."oauth_token (service, fk_soc, entity, tokenstring)";
                    $sql .= " VALUES ('".$service."', ".$object->id.", ".$conf->entity.", '".dol_json_encode($tokenstring)."')";
                    // TODO Add site and site_account on oauth_token table
                } catch (Exception $e) {
					$error++;
					setEventMessages($e->getMessage(), null, 'errors');
				}
				$resql = $db->query($sql);
			}

			if (!$error)
			{
				$stripesupplieracc = $newsup;
				$db->commit();
			}
			else
			{
				$db->rollback();
			}
		}

		if ($action == 'setlocalassourcedefault')	// Set as default when payment mode defined locally (and may be also remotely)
		{
			try {
				$companypaymentmode->setAsDefault($id);

				$url = DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id;
				header('Location: '.$url);
				exit;
			}
			catch (Exception $e)
			{
				$error++;
				setEventMessages($e->getMessage(), null, 'errors');
			}
		}
		elseif ($action == 'setassourcedefault')	// Set as default when payment mode defined remotely only
		{
			try {
				$cu = $stripe->customerStripe($object, $stripeacc, $servicestatus);
				if (preg_match('/pm_/', $source))
				{
					$cu->invoice_settings->default_payment_method = (string) $source; // New
				}
				else
				{
					$cu->default_source = (string) $source; // Old
				}
				$result = $cu->save();

				$url = DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id;
				header('Location: '.$url);
				exit;
			}
			catch (Exception $e)
			{
				$error++;
				setEventMessages($e->getMessage(), null, 'errors');
			}
		}
		elseif ($action == 'deletecard' && $source)
		{
			try {
				if (preg_match('/pm_/', $source))
				{
                    $payment_method = \Stripe\PaymentMethod::retrieve($source, array("stripe_account" => $stripeacc));
                    if ($payment_method)
			        {
					    $payment_method->detach();
				    }
				}
				else
				{
				    $cu = $stripe->customerStripe($object, $stripeacc, $servicestatus);
				    $card = $cu->sources->retrieve("$source");
				    if ($card) {
					    // $card->detach();  Does not work with card_, only with src_
					    if (method_exists($card, 'detach')) {
							$card->detach();
						} else {
							$card->delete();
						}
				    }
				}

				$url = DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id;
				header('Location: '.$url);
				exit;
			}
			catch (Exception $e)
			{
				$error++;
				setEventMessages($e->getMessage(), null, 'errors');
			}
		}
	}
}



/*
 *	View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

llxHeader();

$head = societe_prepare_head($object);

// Show sandbox warning
/*if (! empty($conf->paypal->enabled) && (! empty($conf->global->PAYPAL_API_SANDBOX) || GETPOST('forcesandbox','alpha')))		// We can force sand box with param 'forcesandbox'
{
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode','Paypal'),'','warning');
}*/
if (!empty($conf->stripe->enabled) && (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'alpha')))
{
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), '', 'warning');
}

// Load Bank account
if (!$id)
{
	$companybankaccount->fetch(0, $object->id);
	$companypaymentmode->fetch(0, null, $object->id, 'card');
}
else
{
	$companybankaccount->fetch($id);
	$companypaymentmode->fetch($id);
}
if (empty($companybankaccount->socid)) $companybankaccount->socid = $object->id;

if ($socid && ($action == 'edit' || $action == 'editcard') && $user->rights->societe->creer)
{
	print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	$actionforadd = 'update';
	if ($action == 'editcard') $actionforadd = 'updatecard';
	print '<input type="hidden" name="action" value="'.$actionforadd.'">';
	print '<input type="hidden" name="id" value="'.GETPOST("id", "int").'">';
}
if ($socid && ($action == 'create' || $action == 'createcard') && $user->rights->societe->creer)
{
	print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	$actionforadd = 'add';
	if ($action == 'createcard') $actionforadd = 'addcard';
	print '<input type="hidden" name="action" value="'.$actionforadd.'">';
}


// View
if ($socid && $action != 'edit' && $action != 'create' && $action != 'editcard' && $action != 'createcard')
{
	dol_fiche_head($head, 'rib', $langs->trans("ThirdParty"), -1, 'company');

	// Confirm delete ban
	if ($action == 'delete')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$object->id."&ribid=".($ribid ? $ribid : $id), $langs->trans("DeleteARib"), $langs->trans("ConfirmDeleteRib", $companybankaccount->getRibLabel()), "confirm_delete", '', 0, 1);
	}
	// Confirm delete card
	if ($action == 'deletecard')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$object->id."&ribid=".($ribid ? $ribid : $id), $langs->trans("DeleteACard"), $langs->trans("ConfirmDeleteCard", $companybankaccount->getRibLabel()), "confirm_deletecard", '', 0, 1);
	}

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');


	if (!empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
	{
		print '<tr><td class="titlefield">'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
	}

	//if ($conf->agenda->enabled && $user->rights->agenda->myactions->read) $elementTypeArray['action']=$langs->transnoentitiesnoconv('Events');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield centpercent">';

	if ($object->client)
	{
		print '<tr><td class="titlefield">';
		print $langs->trans('CustomerCode').'</td><td colspan="2">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
		$sql = "SELECT count(*) as nb from ".MAIN_DB_PREFIX."facture where fk_soc = ".$socid;
		$resql = $db->query($sql);
		if (!$resql) dol_print_error($db);

		$obj = $db->fetch_object($resql);
		$nbFactsClient = $obj->nb;
		$thirdTypeArray['customer'] = $langs->trans("customer");
		if ($conf->propal->enabled && $user->rights->propal->lire) $elementTypeArray['propal'] = $langs->transnoentitiesnoconv('Proposals');
		if ($conf->commande->enabled && $user->rights->commande->lire) $elementTypeArray['order'] = $langs->transnoentitiesnoconv('Orders');
		if ($conf->facture->enabled && $user->rights->facture->lire) $elementTypeArray['invoice'] = $langs->transnoentitiesnoconv('Invoices');
		if ($conf->contrat->enabled && $user->rights->contrat->lire) $elementTypeArray['contract'] = $langs->transnoentitiesnoconv('Contracts');

		if (!empty($conf->stripe->enabled))
		{
			$permissiontowrite = $user->rights->societe->creer;
			// Stripe customer key 'cu_....' stored into llx_societe_account
			print '<tr><td class="titlefield">';
			print $form->editfieldkey("StripeCustomerId", 'key_account', $stripecu, $object, $permissiontowrite, 'string', '', 0, 2, 'socid', 'Publishable key '.$site_account);
			print '</td><td>';
			print $form->editfieldval("StripeCustomerId", 'key_account', $stripecu, $object, $permissiontowrite, 'string', '', null, null, '', 2, '', 'socid');
			if (!empty($conf->stripe->enabled) && $stripecu && $action != 'editkey_account')
			{
			    $connect = '';
				if (!empty($stripeacc)) $connect = $stripeacc.'/';
				$url = 'https://dashboard.stripe.com/'.$connect.'test/customers/'.$stripecu;
				if ($servicestatus)
				{
					$url = 'https://dashboard.stripe.com/'.$connect.'customers/'.$stripecu;
				}
				print ' <a href="'.$url.'" target="_stripe">'.img_picto($langs->trans('ShowInStripe'), 'globe').'</a>';
			}
			print '</td><td class="right">';
			if (empty($stripecu))
			{
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
				print '<input type="hidden" name="action" value="synccustomertostripe">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="socid" value="'.$object->id.'">';
				print '<input type="submit" class="button" name="syncstripecustomer" value="'.$langs->trans("CreateCustomerOnStripe").'">';
				print '</form>';
			}
			print '</td></tr>';
		}
    }

	if ($object->fournisseur)
	{
		print '<tr><td class="titlefield">';
		print $langs->trans('SupplierCode').'</td><td colspan="2">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
		$sql = "SELECT count(*) as nb from ".MAIN_DB_PREFIX."facture where fk_soc = ".$socid;
		$resql = $db->query($sql);
		if (!$resql) dol_print_error($db);
		$obj = $db->fetch_object($resql);
		$nbFactsClient = $obj->nb;
		$thirdTypeArray['customer'] = $langs->trans("customer");
		if ($conf->propal->enabled && $user->rights->propal->lire) $elementTypeArray['propal'] = $langs->transnoentitiesnoconv('Proposals');
		if ($conf->commande->enabled && $user->rights->commande->lire) $elementTypeArray['order'] = $langs->transnoentitiesnoconv('Orders');
		if ($conf->facture->enabled && $user->rights->facture->lire) $elementTypeArray['invoice'] = $langs->transnoentitiesnoconv('Invoices');
		if ($conf->contrat->enabled && $user->rights->contrat->lire) $elementTypeArray['contract'] = $langs->transnoentitiesnoconv('Contracts');
	}

	if (!empty($conf->stripe->enabled) && !empty($conf->stripeconnect->enabled) && $conf->global->MAIN_FEATURES_LEVEL >= 2)
	{
		$permissiontowrite = $user->rights->societe->creer;
		$stripesupplieracc = $stripe->getStripeAccount($service, $object->id); // Get Stripe OAuth connect account (no network access here)

		// Stripe customer key 'cu_....' stored into llx_societe_account
		print '<tr><td class="titlefield">';
		print $form->editfieldkey("StripeConnectAccount", 'key_account_supplier', $stripesupplieracc, $object, $permissiontowrite, 'string', '', 0, 2, 'socid');
		print '</td><td>';
		print $form->editfieldval("StripeConnectAccount", 'key_account_supplier', $stripesupplieracc, $object, $permissiontowrite, 'string', '', null, null, '', 2, '', 'socid');
		if (!empty($conf->stripe->enabled) && $stripesupplieracc && $action != 'editkey_account_supplier')
		{
		    $connect = '';

			$url = 'https://dashboard.stripe.com/test/connect/accounts/'.$stripesupplieracc;
			if ($servicestatus)
			{
				$url = 'https://dashboard.stripe.com/connect/accounts/'.$stripesupplieracc;
			}
			print ' <a href="'.$url.'" target="_stripe">'.img_picto($langs->trans('ShowInStripe'), 'globe').'</a>';
		}
		print '</td><td class="right">';
		if (empty($stripesupplieracc))
		{
			print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="action" value="syncsuppliertostripe">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="socid" value="'.$object->id.'">';
			print '<input type="hidden" name="companybankid" value="'.$rib->id.'">';
			//print '<input type="submit" class="button" name="syncstripecustomer" value="'.$langs->trans("CreateSupplierOnStripe").'">';
			print '</form>';
		}
		print '</td></tr>';
	}

	print '</table>';
	print '</div>';

	dol_fiche_end();

	print '<br>';

	// List of Stripe payment modes
	if (!(empty($conf->stripe->enabled)) && $object->client)
	{
		$morehtmlright = '';
		if (!empty($conf->global->STRIPE_ALLOW_LOCAL_CARD))
		{
            $morehtmlright .= dolGetButtonTitle($langs->trans('Add'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;action=createcard');
		}
		print load_fiche_titre($langs->trans('StripePaymentModes').($stripeacc ? ' (Stripe connection with StripeConnect account '.$stripeacc.')' : ' (Stripe connection with keys from Stripe module setup)'), $morehtmlright, '');

		$listofsources = array();
		if (is_object($stripe))
		{
			try {
				$customerstripe = $stripe->customerStripe($object, $stripeacc, $servicestatus);
				if ($customerstripe->id) {
					// When using the Charge API architecture
					if (empty($conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION))
					{
						$listofsources = $customerstripe->sources->data;
					}
					else
					{
						$service = 'StripeTest';
						$servicestatus = 0;
						if (!empty($conf->global->STRIPE_LIVE) && !GETPOST('forcesandbox', 'alpha'))
						{
							$service = 'StripeLive';
							$servicestatus = 1;
						}

						// Force to use the correct API key
						global $stripearrayofkeysbyenv;
						\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$servicestatus]['secret_key']);

						try {
							if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
								$paymentmethodobjs = \Stripe\PaymentMethod::all(array("customer" => $customerstripe->id, "type" => "card"));
							} else {
								$paymentmethodobjs = \Stripe\PaymentMethod::all(array("customer" => $customerstripe->id, "type" => "card"), array("stripe_account" => $stripeacc));
							}

							$listofsources = $paymentmethodobjs->data;
						}
						catch (Exception $e)
						{
							$error++;
							setEventMessages($e->getMessage(), null, 'errors');
						}
					}
				}
			}
			catch(Exception $e)
			{
				dol_syslog("Error when searching/loading Stripe customer for thirdparty id =".$object->id);
			}
		}

		print '<!-- List of stripe payments -->'."\n";
		print '<div class="div-table-responsive-no-min">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
		print '<table class="liste centpercent">'."\n";
		print '<tr class="liste_titre">';
		if (! empty($conf->global->STRIPE_ALLOW_LOCAL_CARD))
		{
			print '<td>'.$langs->trans('LocalID').'</td>';
		}
		print '<td>'.$langs->trans('StripeID').'</td>';
		print '<td>'.$langs->trans('Type').'</td>';
		print '<td>'.$langs->trans('Informations').'</td>';
		print '<td></td>';
		print '<td class="center">'.$langs->trans('Default').'</td>';
		print '<td>'.$langs->trans('Note').'</td>';
		print '<td>'.$langs->trans('DateModification').'</td>';
		// Hook fields
		$parameters = array('arrayfields'=>array(), 'param'=>'', 'sortfield'=>'', 'sortorder'=>'', 'linetype'=>'stripetitle');
		$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Action column
		print "<td></td>";
		print "</tr>\n";

		$nbremote = 0;
		$nblocal = 0;
		$arrayofstripecard = array();

		// Show local sources
		if (!empty($conf->global->STRIPE_ALLOW_LOCAL_CARD))
		{
			//$societeaccount = new SocieteAccount($db);
			$companypaymentmodetemp = new CompanyPaymentMode($db);

			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX."societe_rib";
			$sql .= " WHERE type in ('card')";
			$sql .= " AND fk_soc = ".$object->id;
			$sql .= " AND status = ".$servicestatus;

			$resql = $db->query($sql);
			if ($resql)
			{
				$num_rows = $db->num_rows($resql);
				if ($num_rows)
				{
					$i = 0;
					while ($i < $num_rows)
					{
						$nblocal++;

						$obj = $db->fetch_object($resql);
						if ($obj)
						{
							$companypaymentmodetemp->fetch($obj->rowid);

							$arrayofstripecard[$companypaymentmodetemp->stripe_card_ref] = $companypaymentmodetemp->stripe_card_ref;

							print '<tr class="oddeven">';
							print '<td>';
							print $companypaymentmodetemp->id;
							print '</td>';
							print '<td>';
							print $companypaymentmodetemp->stripe_card_ref;
							if ($companypaymentmodetemp->stripe_card_ref)
							{
							    $connect = '';
							    if (!empty($stripeacc)) $connect = $stripeacc.'/';
							    $url = 'https://dashboard.stripe.com/'.$connect.'test/search?query='.$companypaymentmodetemp->stripe_card_ref;
								if ($servicestatus)
								{
									$url = 'https://dashboard.stripe.com/'.$connect.'search?query='.$companypaymentmodetemp->stripe_card_ref;
								}
								print ' <a href="'.$url.'" target="_stripe">'.img_picto($langs->trans('ShowInStripe'), 'globe').'</a>';
							}
							print '</td>';
							print '<td>';
							print img_credit_card($companypaymentmodetemp->type);
							print '</td>';
							print '<td>';
							if ($companypaymentmodetemp->last_four) print '....'.$companypaymentmodetemp->last_four;
							if ($companypaymentmodetemp->exp_date_month || $companypaymentmodetemp->exp_date_year) print ' - '.sprintf("%02d", $companypaymentmodetemp->exp_date_month).'/'.$companypaymentmodetemp->exp_date_year.'';
							print '</td><td>';
							if ($companypaymentmodetemp->country_code)
							{
								$img = picto_from_langcode($companypaymentmodetemp->country_code);
								print $img ? $img.' ' : '';
								print getCountry($companypaymentmodetemp->country_code, 1);
							}
							else print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
							print '</td>';
							// Default
							print '<td class="center">';
							if (empty($companypaymentmodetemp->default_rib))
							{
								print '<a href="'.DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id.'&id='.$companypaymentmodetemp->id.'&action=setlocalassourcedefault">';
								print img_picto($langs->trans("Default"), 'off');
								print '</a>';
							} else {
								print img_picto($langs->trans("Default"), 'on');
							}
							print '</td>';
							print '<td>';
							if (empty($companypaymentmodetemp->stripe_card_ref)) print $langs->trans("Local");
							else print $langs->trans("LocalAndRemote");
							print '</td>';
							print '<td>';
							print dol_print_date($companypaymentmodetemp->tms, 'dayhour');
							print '</td>';
							// Fields from hook
							$parameters = array('arrayfields'=>array(), 'obj'=>$obj, 'linetype'=>'stripecard');
							$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
							print $hookmanager->resPrint;
							// Action column
							print '<td class="right nowraponall">';
							if ($user->rights->societe->creer)
							{
								if ($stripecu && empty($companypaymentmodetemp->stripe_card_ref))
								{
									print '<a href="'.$_SERVER['PHP_SELF'].'?action=synccardtostripe&socid='.$object->id.'&id='.$companypaymentmodetemp->id.'" class="button">'.$langs->trans("CreateCardOnStripe").'</a>';
								}

								print '<a href="'.DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id.'&id='.$companypaymentmodetemp->id.'&action=editcard">';
								print img_picto($langs->trans("Modify"), 'edit');
								print '</a>';
								print '&nbsp;';
								print '<a href="'.DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id.'&id='.$companypaymentmodetemp->id.'&action=deletecard">'; // source='.$companypaymentmodetemp->stripe_card_ref.'&
								print img_picto($langs->trans("Delete"), 'delete');
								print '</a>';
							}
							print '</td>';
							print '</tr>';
						}
						$i++;
					}
				}
			}
			else dol_print_error($db);
		}

		// Show remote sources (not already shown as local source)
		if (is_array($listofsources) && count($listofsources))
		{
			foreach ($listofsources as $src)
			{
				if (!empty($arrayofstripecard[$src->id])) continue; // Already in previous list

				$nbremote++;

				print '<tr class="oddeven">';
				// Local ID
				if (!empty($conf->global->STRIPE_ALLOW_LOCAL_CARD))
				{
					print '<td>';
					print '</td>';
				}
				// Src ID
				print '<td>';
				$connect = '';
				print $src->id;
				if (!empty($stripeacc)) $connect = $stripeacc.'/';
				//$url='https://dashboard.stripe.com/'.$connect.'test/sources/'.$src->id;
				$url = 'https://dashboard.stripe.com/'.$connect.'test/search?query='.$src->id;
				if ($servicestatus)
				{
				    //$url='https://dashboard.stripe.com/'.$connect.'sources/'.$src->id;
				    $url = 'https://dashboard.stripe.com/'.$connect.'search?query='.$src->id;
				}
				print " <a href='".$url."' target='_stripe'>".img_picto($langs->trans('ShowInStripe'), 'globe')."</a>";
				print '</td>';
				// Img of credit card
				print '<td>';
				if ($src->object == 'card')
				{
					print img_credit_card($src->brand);
				}
				elseif ($src->object == 'source' && $src->type == 'card')
				{
					print img_credit_card($src->card->brand);
				}
				elseif ($src->object == 'source' && $src->type == 'sepa_debit')
				{
					print '<span class="fa fa-university fa-2x fa-fw"></span>';
				}
				elseif ($src->object == 'payment_method' && $src->type == 'card')
				{
					print img_credit_card($src->card->brand);
				}
				elseif ($src->object == 'payment_method' && $src->type == 'sepa_debit')
				{
					print '<span class="fa fa-university fa-2x fa-fw"></span>';
				}
				print'</td>';
				print '<td valign="middle">';
				if ($src->object == 'card')
				{
					print '....'.$src->last4.' - '.$src->exp_month.'/'.$src->exp_year.'';
					print '</td><td>';
					if ($src->country)
					{
						$img = picto_from_langcode($src->country);
						print $img ? $img.' ' : '';
						print getCountry($src->country, 1);
					}
					else print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
				}
				elseif ($src->object == 'source' && $src->type == 'card')
				{
					print $src->owner->name.'<br>....'.$src->card->last4.' - '.$src->card->exp_month.'/'.$src->card->exp_year.'';
					print '</td><td>';

				 	if ($src->card->country)
					{
						$img = picto_from_langcode($src->card->country);
						print $img ? $img.' ' : '';
						print getCountry($src->card->country, 1);
					}
					else print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
				}
				elseif ($src->object == 'source' && $src->type == 'sepa_debit')
				{
					print 'info sepa';
					print '</td><td>';
					if ($src->sepa_debit->country)
					{
							$img = picto_from_langcode($src->sepa_debit->country);
							print $img ? $img.' ' : '';
							print getCountry($src->sepa_debit->country, 1);
					}
					else print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
				}
				elseif ($src->object == 'payment_method' && $src->type == 'card')
				{
					print $src->billing_details->name.'<br>....'.$src->card->last4.' - '.$src->card->exp_month.'/'.$src->card->exp_year.'';
					print '</td><td>';

					if ($src->card->country)
					{
						$img = picto_from_langcode($src->card->country);
						print $img ? $img.' ' : '';
						print getCountry($src->card->country, 1);
					}
					else print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
				}
				elseif ($src->object == 'payment_method' && $src->type == 'sepa_debit')
				{
					print 'info sepa';
					print '</td><td>';
					if ($src->sepa_debit->country)
					{
						$img = picto_from_langcode($src->sepa_debit->country);
						print $img ? $img.' ' : '';
						print getCountry($src->sepa_debit->country, 1);
					}
					else print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
				}
				else {
					print '</td><td>';
				}
				print '</td>';
				// Default
				print '<td class="center" width="50">';
				if ((empty($customerstripe->invoice_settings) && $customerstripe->default_source != $src->id) ||
					(!empty($customerstripe->invoice_settings) && $customerstripe->invoice_settings->default_payment_method != $src->id))
				{
					print '<a href="'.DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id.'&source='.$src->id.'&action=setassourcedefault">';
					print img_picto($langs->trans("Default"), 'off');
					print '</a>';
				} else {
					print img_picto($langs->trans("Default"), 'on');
				}
				print '</td>';
				print '<td>';
				print $langs->trans("Remote");
				//if ($src->cvc_check == 'fail') print ' - CVC check fail';
				print '</td>';
				print '<td>';
				//var_dump($src);
				print '';
				print '</td>';
				// Fields from hook
				$parameters = array('arrayfields'=>array(), 'stripesource'=>$src, 'linetype'=>'stripecardremoteonly');
				$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;
				// Action column
				print '<td class="right nowraponall">';
				if ($user->rights->societe->creer)
				{
					print '<a href="'.DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id.'&source='.$src->id.'&action=deletecard">';
					print img_picto($langs->trans("Delete"), 'delete');
					print '</a>';
				}
				print '</td>';

				print '</tr>';
			}
		}

		if ($nbremote == 0 && $nblocal == 0)
		{
			$colspan = 8;
			if (!empty($conf->global->STRIPE_ALLOW_LOCAL_CARD)) $colspan++;
			print '<tr><td class="opacitymedium" colspan="'.$colspan.'">'.$langs->trans("None").'</td></tr>';
		}
		print "</table>";
		print "</div>";
        print '<br>';
	}

  	// List of Stripe payment modes
	if (!empty($conf->stripe->enabled) && !empty($conf->stripeconnect->enabled) && $object->fournisseur && !empty($stripesupplieracc))
	{
        print load_fiche_titre($langs->trans('StripeBalance').($stripesupplieracc ? ' (Stripe connection with StripeConnect account '.$stripesupplieracc.')' : ' (Stripe connection with keys from Stripe module setup)'), $morehtmlright, '');
        $balance = \Stripe\Balance::retrieve(array("stripe_account" => $stripesupplieracc));
		print '<table class="liste centpercent">'."\n";
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Currency').'</td>';
		print '<td>'.$langs->trans('Available').'</td>';
		print '<td>'.$langs->trans('Pending').'</td>';
        print '<td>'.$langs->trans('Total').'</td>';
        print '</tr>';

        $currencybalance = array();
		if (is_array($balance->available) && count($balance->available))
		{
			foreach ($balance->available as $cpt)
			{
		        $arrayzerounitcurrency=array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
		        if (!in_array($cpt->currency, $arrayzerounitcurrency)) {
    					$currencybalance[$cpt->currency]['available'] = $cpt->amount / 100;
				} else {
					$currencybalance[$cpt->currency]['available'] = $cpt->amount;
				}
				$currencybalance[$cpt->currency]['currency'] = $cpt->currency;
			}
		}

        if (is_array($balance->pending) && count($balance->pending))
		{
			foreach ($balance->pending as $cpt)
			{
				$arrayzerounitcurrency=array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
				if (!in_array($cpt->currency, $arrayzerounitcurrency)) {
					$currencybalance[$cpt->currency]['pending'] = $currencybalance[$cpt->currency]['available']+$cpt->amount / 100;
				} else {
					$currencybalance[$cpt->currency]['pending'] = $currencybalance[$cpt->currency]['available']+$cpt->amount;
				}
			}
        }

		if (is_array($currencybalance))
		{
			foreach ($currencybalance as $cpt)
			{
				print '<tr><td>'.$langs->trans("Currency".strtoupper($cpt['currency'])).'</td><td>'.price($cpt['available'], 0, '', 1, - 1, - 1, strtoupper($cpt['currency'])).'</td><td>'.price($cpt->pending, 0, '', 1, - 1, - 1, strtoupper($cpt['currency'])).'</td><td>'.price($cpt['available']+$cpt->pending, 0, '', 1, - 1, - 1, strtoupper($cpt['currency'])).'</td></tr>';
			}
		}

        print '</table>';
        print '<br>';
	}

	// List of bank accounts

    $morehtmlright= dolGetButtonTitle($langs->trans('Add'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;action=create');

	print load_fiche_titre($langs->trans("BankAccounts"), $morehtmlright, '');

	$rib_list = $object->get_all_rib();
	if (is_array($rib_list))
	{
		print '<div class="div-table-responsive-no-min">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
		print '<table class="liste centpercent">';

		print '<tr class="liste_titre">';
		print_liste_field_titre("LabelRIB");
		print_liste_field_titre("Bank");
		print_liste_field_titre("RIB");
		print_liste_field_titre("IBAN");
		print_liste_field_titre("BIC");
		if (!empty($conf->prelevement->enabled))
		{
			print_liste_field_titre("RUM");
			print_liste_field_titre("DateRUM");
			print_liste_field_titre("WithdrawMode");
		}
		print_liste_field_titre("DefaultRIB", '', '', '', '', '', '', '', 'center ');
		print_liste_field_titre('', '', '', '', '', '', '', '', 'center ');
		print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
		print "</tr>\n";

		foreach ($rib_list as $rib)
		{
			print '<tr class="oddeven">';
			// Label
			print '<td>'.$rib->label.'</td>';
			// Bank name
			print '<td>'.$rib->bank.'</td>';
			// Account number
			print '<td>';
			$string = '';
			foreach ($rib->getFieldsToShow() as $val) {
				if ($val == 'BankCode') {
					$string .= $rib->code_banque.' ';
				} elseif ($val == 'BankAccountNumber') {
					$string .= $rib->number.' ';
				} elseif ($val == 'DeskCode') {
					$string .= $rib->code_guichet.' ';
				} elseif ($val == 'BankAccountNumberKey') {
					$string .= $rib->cle_rib.' ';
				}
                // Already output after
                // } elseif ($val == 'BIC') {
                //     $string .= $rib->bic.' ';
                // } elseif ($val == 'IBAN') {
                //     $string .= $rib->iban.' ';*/
				//}
			}
			if (!empty($rib->label) && $rib->number) {
				if (!checkBanForAccount($rib)) {
					$string .= ' '.img_picto($langs->trans("ValueIsNotValid"), 'warning');
				} else {
					$string .= ' '.img_picto($langs->trans("ValueIsValid"), 'info');
				}
			}

			print $string;
			print '</td>';
			// IBAN
			print '<td>'.$rib->iban;
   			if (!empty($rib->iban)) {
				if (!checkIbanForAccount($rib)) {
					print ' '.img_picto($langs->trans("IbanNotValid"), 'warning');
				}
			}
			print '</td>';
			// BIC
			print '<td>'.$rib->bic;
			if (!empty($rib->bic)) {
				if (!checkSwiftForAccount($rib)) {
					print ' '.img_picto($langs->trans("SwiftNotValid"), 'warning');
				}
			}
			print '</td>';

			if (!empty($conf->prelevement->enabled))
			{
				// RUM
				//print '<td>'.$prelevement->buildRumNumber($object->code_client, $rib->datec, $rib->id).'</td>';
				print '<td>'.$rib->rum.'</td>';

				print '<td>'.dol_print_date($rib->date_rum, 'day').'</td>';

				// FRSTRECUR
				print '<td>'.$rib->frstrecur.'</td>';
			}

			// Default
			print '<td class="center" width="70">';
			if (!$rib->default_rib) {
				print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&ribid='.$rib->id.'&action=setasbankdefault">';
				print img_picto($langs->trans("Disabled"), 'off');
				print '</a>';
			} else {
				print img_picto($langs->trans("Enabled"), 'on');
			}
			print '</td>';

			// Generate doc
			print '<td class="center">';

			$buttonlabel = $langs->trans("BuildDoc");
			$forname = 'builddocrib'.$rib->id;

			include_once DOL_DOCUMENT_ROOT.'/core/modules/bank/modules_bank.php';
			$modellist = ModeleBankAccountDoc::liste_modeles($db);

			$out = '';
			if (is_array($modellist) && count($modellist))
			{
				$out .= '<form action="'.$urlsource.(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc').'" name="'.$forname.'" id="'.$forname.'_form" method="post">';
				$out .= '<input type="hidden" name="action" value="builddocrib">';
				$out .= '<input type="hidden" name="token" value="'.newToken().'">';
				$out .= '<input type="hidden" name="socid" value="'.$object->id.'">';
				$out .= '<input type="hidden" name="companybankid" value="'.$rib->id.'">';

				if (is_array($modellist) && count($modellist) == 1)    // If there is only one element
				{
					$arraykeys = array_keys($modellist);
					$modelselected = $arraykeys[0];
				}
				if (!empty($conf->global->BANKADDON_PDF)) $modelselected = $conf->global->BANKADDON_PDF;

				$out .= $form->selectarray('modelrib'.$rib->id, $modellist, $modelselected, $showempty, 0, 0, '', 0, 0, 0, '', 'minwidth100');
				$out .= ajax_combobox('modelrib'.$rib->id);

				// Language code (if multilang)
				if ($conf->global->MAIN_MULTILANGS)
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
					$formadmin = new FormAdmin($db);
					$defaultlang = $codelang ? $codelang : $langs->getDefaultLang();
					$morecss = 'maxwidth150';
					if ($conf->browser->layout == 'phone') $morecss = 'maxwidth100';
					$out .= $formadmin->select_language($defaultlang, 'lang_idrib'.$rib->id, 0, 0, 0, 0, 0, $morecss);
				}
				// Button
				$genbutton = '<input class="button buttongen" id="'.$forname.'_generatebutton" name="'.$forname.'_generatebutton"';
				$genbutton .= ' type="submit" value="'.$buttonlabel.'"';
				if (!$allowgenifempty && !is_array($modellist) && empty($modellist)) $genbutton .= ' disabled';
				$genbutton .= '>';
				if ($allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid')
				{
					$langs->load("errors");
					$genbutton .= ' '.img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
				}
				if (!$allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') $genbutton = '';
				if (empty($modellist) && !$showempty && $modulepart != 'unpaid') $genbutton = '';
				$out .= $genbutton;
				$out .= '</form>';
			}
			print $out;
			print '</td>';

			// Edit/Delete
			print '<td class="right nowraponall">';
			if ($user->rights->societe->creer)
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&id='.$rib->id.'&action=edit">';
				print img_picto($langs->trans("Modify"), 'edit');
				print '</a>';

		   		print '&nbsp;';

		   		print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&id='.$rib->id.'&action=delete">';
		   		print img_picto($langs->trans("Delete"), 'delete');
		   		print '</a>';
			}
			print '</td>';

			print '</tr>';
		}

		if (count($rib_list) == 0)
		{
			$colspan = 9;
			if (!empty($conf->prelevement->enabled)) $colspan += 2;
			print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoBANRecord").'</td></tr>';
		}

		print '</table>';
		print '</div>';
	} else {
		dol_print_error($db);
	}


	if (empty($conf->global->SOCIETE_DISABLE_BUILDDOC))
	{
		print '<br>';

		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		/*
         * Documents generes
         */
		$filedir = $conf->societe->multidir_output[$object->entity].'/'.$object->id;
		$urlsource = $_SERVER["PHP_SELF"]."?socid=".$object->id;
		$genallowed = $user->rights->societe->lire;
		$delallowed = $user->rights->societe->creer;

		print $formfile->showdocuments('company', $object->id, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 0, 0, 0, 28, 0, 'entity='.$object->entity, 0, '', $object->default_lang);

		// Show direct download link
		if (!empty($conf->global->BANK_ACCOUNT_ALLOW_EXTERNAL_DOWNLOAD))
		{
			$companybankaccounttemp = new CompanyBankAccount($db);
			$companypaymentmodetemp = new CompanyPaymentMode($db);
			$result = $companypaymentmodetemp->fetch(0, null, $object->id, 'ban');

			include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
			$ecmfile = new EcmFiles($db);
			$result = $ecmfile->fetch(0, '', '', '', '', $companybankaccounttemp->table_element, $companypaymentmodetemp->id);
			if ($result > 0)
			{
				$companybankaccounttemp->last_main_doc = $ecmfile->filepath.'/'.$ecmfile->filename;
				print '<br><!-- Link to download main doc -->'."\n";
				print showDirectDownloadLink($companybankaccounttemp).'<br>';
			}
		}

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';


		print '</div></div></div>';

		print '<br>';
	}
	/*
    include_once DOL_DOCUMENT_ROOT.'/core/modules/bank/modules_bank.php';
    $modellist=ModeleBankAccountDoc::liste_modeles($db);
    //print '<td>';
    if (is_array($modellist) && count($modellist) == 1)    // If there is only one element
    {
        $arraykeys=array_keys($modellist);
        $modelselected=$arraykeys[0];
    }
    $out.= $form->selectarray('model', $modellist, $modelselected, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
    $out.= ajax_combobox('model');
    //print $out;
    $buttonlabel=$langs->trans("Generate");
    $genbutton = '<input class="button buttongen" id="'.$forname.'_generatebutton" name="'.$forname.'_generatebutton"';
    $genbutton.= ' type="submit" value="'.$buttonlabel.'"';
    $genbutton.= '>';
    print $genbutton;
    //print '</td>';     // TODO Add link to generate doc
    */
}

// Edit BAN
if ($socid && $action == 'edit' && $user->rights->societe->creer)
{
	dol_fiche_head($head, 'rib', $langs->trans("ThirdParty"), 0, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="underbanner clearboth"></div>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefield fieldrequired">'.$langs->trans("LabelRIB").'</td>';
	print '<td><input class="minwidth300" type="text" name="label" value="'.$companybankaccount->label.'"></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("BankName").'</td>';
	print '<td><input class="minwidth200" type="text" name="bank" value="'.$companybankaccount->bank.'"></td></tr>';

	// Show fields of bank account
	foreach ($companybankaccount->getFieldsToShow(1) as $val) {
		$require = false;
		if ($val == 'BankCode') {
			$name = 'code_banque';
			$size = 8;
			$content = $companybankaccount->code_banque;
		} elseif ($val == 'DeskCode') {
			$name = 'code_guichet';
			$size = 8;
			$content = $companybankaccount->code_guichet;
		} elseif ($val == 'BankAccountNumber') {
			$name = 'number';
			$size = 18;
			$content = $companybankaccount->number;
		} elseif ($val == 'BankAccountNumberKey') {
			$name = 'cle_rib';
			$size = 3;
			$content = $companybankaccount->cle_rib;
		} elseif ($val == 'IBAN') {
			$name = 'iban';
			$size = 30;
			$content = $companybankaccount->iban;
			if ($companybankaccount->needIBAN()) $require = true;
		} elseif ($val == 'BIC') {
			$name = 'bic';
			$size = 12;
			$content = $companybankaccount->bic;
			if ($companybankaccount->needIBAN()) $require = true;
		}

		print '<tr><td'.($require ? ' class="fieldrequired" ' : '').'>'.$langs->trans($val).'</td>';
		print '<td><input size="'.$size.'" type="text" class="flat" name="'.$name.'" value="'.$content.'"></td>';
		print '</tr>';
	}

	print '<tr><td>'.$langs->trans("BankAccountDomiciliation").'</td><td>';
	print '<textarea name="domiciliation" rows="4" cols="40" maxlength="255">';
	print $companybankaccount->domiciliation;
	print "</textarea></td></tr>";

	print '<tr><td>'.$langs->trans("BankAccountOwner").'</td>';
	print '<td><input class="minwidth300" type="text" name="proprio" value="'.$companybankaccount->proprio.'"></td></tr>';
	print "</td></tr>\n";

	print '<tr><td>'.$langs->trans("BankAccountOwnerAddress").'</td><td>';
	print '<textarea name="owner_address" rows="'.ROWS_4.'" cols="40" maxlength="255">';
	print $companybankaccount->owner_address;
	print "</textarea></td></tr>";

	print '</table>';
	print '</div>';

	if ($conf->prelevement->enabled)
	{
		print '<br>';

		print '<div class="div-table-responsive-no-min">';
		print '<table class="border centpercent">';

		if (empty($companybankaccount->rum)) $companybankaccount->rum = $prelevement->buildRumNumber($object->code_client, $companybankaccount->datec, $companybankaccount->id);

		// RUM
		print '<tr><td class="titlefield">'.$langs->trans("RUM").'</td>';
		print '<td><input class="minwidth300" type="text" name="rum" value="'.dol_escape_htmltag($companybankaccount->rum).'"></td></tr>';

		print '<tr><td class="titlefield">'.$langs->trans("DateRUM").'</td>';
		print '<td>'.$form->selectDate(GETPOST('date_rum') ?GETPOST('date_rum') : $companybankaccount->date_rum, 'date_rum', 0, 0, 1, 'date_rum').'</td></tr>';

		print '<tr><td>'.$langs->trans("WithdrawMode").'</td><td>';
		$tblArraychoice = array("FRST" => $langs->trans("FRST"), "RECUR" => $langs->trans("RECUR"));
		print $form->selectarray("frstrecur", $tblArraychoice, dol_escape_htmltag(GETPOST('frstrecur', 'alpha') ?GETPOST('frstrecur', 'alpha') : $companybankaccount->frstrecur), 0);
		print '</td></tr>';

		print '</table>';
		print '</div>';
	}


	dol_fiche_end();

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("Modify").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';
}

// Edit Card
if ($socid && $action == 'editcard' && $user->rights->societe->creer)
{
	dol_fiche_head($head, 'rib', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td>';
	print '<td><input class="minwidth300" type="text" id="label" name="label" value="'.$companypaymentmode->label.'"></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("NameOnCard").'</td>';
	print '<td><input class="minwidth200" type="text" name="proprio" value="'.$companypaymentmode->proprio.'"></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("CardNumber").'</td>';
	print '<td><input class="minwidth200" type="text" name="cardnumber" value="'.$companypaymentmode->number.'"></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("ExpiryDate").'</td>';
	print '<td>';
	print $formother->select_month($companypaymentmode->exp_date_month, 'exp_date_month', 1);
	print $formother->select_year($companypaymentmode->exp_date_year, 'exp_date_year', 1, 5, 10, 0, 0, '', 'marginleftonly');
	print '</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("CVN").'</td>';
	print '<td><input size="8" type="text" name="cvn" value="'.$companypaymentmode->cvn.'"></td></tr>';

	print '<tr><td>'.$langs->trans("StripeID")." ('card_....')</td>";
	print '<td><input class="minwidth300" type="text" name="stripe_card_ref" value="'.$companypaymentmode->stripe_card_ref.'"></td></tr>';

	print '</table>';
	print '</div>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("Modify").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';
}


// Create BAN
if ($socid && $action == 'create' && $user->rights->societe->creer)
{
	dol_fiche_head($head, 'rib', $langs->trans("ThirdParty"), 0, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="nofichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("LabelRIB").'</td>';
	print '<td><input class="minwidth200" type="text" id="label" name="label" value="'.GETPOST('label').'"></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("Bank").'</td>';
	print '<td><input class="minwidth200" type="text" name="bank" value="'.GETPOST('bank').'"></td></tr>';

	// Show fields of bank account
	foreach ($companybankaccount->getFieldsToShow(1) as $val) {
		$require = false;
		if ($val == 'BankCode') {
			$name = 'code_banque';
			$size = 8;
		} elseif ($val == 'DeskCode') {
			$name = 'code_guichet';
			$size = 8;
		} elseif ($val == 'BankAccountNumber') {
			$name = 'number';
			$size = 18;
		} elseif ($val == 'BankAccountNumberKey') {
			$name = 'cle_rib';
			$size = 3;
		} elseif ($val == 'IBAN') {
			$name = 'iban';
			$size = 30;
			if ($companybankaccount->needIBAN()) $require = true;
		} elseif ($val == 'BIC') {
			$name = 'bic';
			$size = 12;
			if ($companybankaccount->needIBAN()) $require = true;
		}

		print '<tr><td'.($require ? ' class="fieldrequired" ' : '').'>'.$langs->trans($val).'</td>';
		print '<td><input size="'.$size.'" type="text" class="flat" name="'.$name.'" value="'.GETPOST($name).'"></td>';
		print '</tr>';
	}

	print '<tr><td>'.$langs->trans("BankAccountDomiciliation").'</td><td>';
	print '<textarea name="domiciliation" rows="'.ROWS_4.'" class="quatrevingtpercent" maxlength="255">';
	print GETPOST('domiciliation');
	print "</textarea></td></tr>";

	print '<tr><td>'.$langs->trans("BankAccountOwner").'</td>';
	print '<td><input class="minwidth200" type="text" name="proprio" value="'.GETPOST('proprio').'"></td></tr>';
	print "</td></tr>\n";

	print '<tr><td>'.$langs->trans("BankAccountOwnerAddress").'</td><td>';
	print '<textarea name="owner_address" rows="'.ROWS_4.'" class="quatrevingtpercent" maxlength="255">';
	print GETPOST('owner_address');
	print "</textarea></td></tr>";

	print '</table>';

	if ($conf->prelevement->enabled)
	{
		print '<br>';

		print '<table class="border centpercent">';

		// RUM
		print '<tr><td class="titlefieldcreate">'.$langs->trans("RUM").'</td>';
		print '<td colspan="4"><input type="text" class="minwidth300" name="rum" value="'.GETPOST('rum', 'alpha').'"> <div class="opacitymedium">'.$langs->trans("RUMWillBeGenerated").'</div></td></tr>';

		print '<tr><td class="titlefieldcreate">'.$langs->trans("DateRUM").'</td>';
		print '<td colspan="4">'.$form->selectDate(GETPOST('date_rum'), 'date_rum', 0, 0, 1, 'date_rum').'</td></tr>';

		print '<tr><td>'.$langs->trans("WithdrawMode").'</td><td>';
		$tblArraychoice = array("FRST" => $langs->trans("FRST"), "RECUR" => $langs->trans("RECUR"));
		print $form->selectarray("frstrecur", $tblArraychoice, (isset($_POST['frstrecur']) ?GETPOST('frstrecur') : 'FRST'), 0);
		print '</td></tr>';

		print '</table>';
	}

	print '</div>';

	dol_fiche_end();

	dol_set_focus('#label');

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("Add").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';
}

// Create Card
if ($socid && $action == 'createcard' && $user->rights->societe->creer)
{
	dol_fiche_head($head, 'rib', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="nofichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td>';
	print '<td><input class="minwidth200" type="text" id="label" name="label" value="'.GETPOST('label', 'alpha').'"></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("NameOnCard").'</td>';
	print '<td><input class="minwidth200" type="text" name="proprio" value="'.GETPOST('proprio', 'alpha').'"></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("CardNumber").'</td>';
	print '<td><input class="minwidth200" type="text" name="cardnumber" value="'.GETPOST('cardnumber', 'alpha').'"></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("ExpiryDate").'</td>';
	print '<td>';
	print $formother->select_month(GETPOST('exp_date_month', 'int'), 'exp_date_month', 1);
	print $formother->select_year(GETPOST('exp_date_year', 'int'), 'exp_date_year', 1, 5, 10, 0, 0, '', 'marginleftonly');
	print '</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("CVN").'</td>';
	print '<td><input size="8" type="text" name="cvn" value="'.GETPOST('cvn', 'alpha').'"></td></tr>';

	print '<tr><td>'.$langs->trans("StripeID")." ('card_....')</td>";
	print '<td><input class="minwidth300" type="text" name="stripe_card_ref" value="'.GETPOST('stripe_card_ref', 'alpha').'"></td></tr>';

	print '</table>';

	print '</div>';

	dol_fiche_end();

	dol_set_focus('#label');

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("Add").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';
}

if ($socid && ($action == 'edit' || $action == 'editcard') && $user->rights->societe->creer)
{
	print '</form>';
}
if ($socid && ($action == 'create' || $action == 'createcard') && $user->rights->societe->creer)
{
	print '</form>';
}

// End of page
llxFooter();
$db->close();
