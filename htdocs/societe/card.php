<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Brian Fraval            <brian@fraval.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Patrick Raguin          <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2020  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2023  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Nicolas ZABOURI	        <info@inovea-conseil.com>
 * Copyright (C) 2018       Ferran Marcet		    <fmarcet@2byte.es.com>
 * Copyright (C) 2018-2022  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022-2023  George Gkantinas	    <info@geowv.eu>
 * Copyright (C) 2023       Nick Fragoulis
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
 *  \file       htdocs/societe/card.php
 *  \ingroup    societe
 *  \brief      Third party card page
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (isModEnabled('adherent')) {
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
}
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
}
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
}
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
}
if (isModEnabled('eventorganization')) {
	require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
}

if ($mysoc->country_code == 'GR') {
	$u = getDolGlobalString('AADE_WEBSERVICE_USER');
	$p = getDolGlobalString('AADE_WEBSERVICE_KEY');
	$myafm = getDolGlobalString('MAIN_INFO_TVAINTRA');
}


// Load translation files required by the page

$langs->loadLangs(array("companies", "commercial", "bills", "banks", "users"));

if (isModEnabled('adherent')) {
	$langs->load("members");
}
if (isModEnabled('categorie')) {
	$langs->load("categories");
}
if (isModEnabled('incoterm')) {
	$langs->load("incoterm");
}
if (isModEnabled('notification')) {
	$langs->load("mails");
}
if (isModEnabled('accounting')) {
	$langs->load("products");
}

$error = 0; $errors = array();


// Get parameters
$action		= (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view');
$cancel		= GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
$confirm 	= GETPOST('confirm', 'alpha');

$dol_openinpopup = '';
if (!empty($backtopagejsfields)) {
	$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
	$dol_openinpopup = $tmpbacktopagejsfields[0];
}

$socid = GETPOST('socid', 'int') ?GETPOST('socid', 'int') : GETPOST('id', 'int');
if ($user->socid) {
	$socid = $user->socid;
}
if (empty($socid) && $action == 'view') {
	$action = 'create';
}

$id = $socid;

$object = new Societe($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$socialnetworks = getArrayOfSocialNetworks();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartycard', 'globalcard'));

if ($socid > 0) {
	$object->fetch($socid);
}

if (!($object->id > 0) && $action == 'view') {
	$langs->load("errors");
	print($langs->trans('ErrorRecordNotFound'));
	exit;
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = $object->canvas ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('thirdparty', 'card', $canvas);
}

// Permissions
$permissiontoread 	= $user->hasRight('societe', 'lire');
$permissiontoadd 	= $user->hasRight('societe', 'creer'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('societe', 'supprimer') || ($permissiontoadd && isset($object->status) && $object->status == 0);
$permissionnote 	= $user->hasRight('societe', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink 	= $user->hasRight('societe', 'creer'); // Used by the include of actions_dellink.inc.php
$upload_dir 		= $conf->societe->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', 0);



/*
 * Actions
 */

$parameters = array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/societe/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/societe/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	if ($action == 'confirm_merge' && $confirm == 'yes' && $user->hasRight('societe', 'creer')) {
		$error = 0;
		$soc_origin_id = GETPOST('soc_origin', 'int');
		$soc_origin = new Societe($db);		// The thirdparty that we will delete

		if ($soc_origin_id <= 0) {
			$langs->load('errors');
			setEventMessages($langs->trans('ErrorThirdPartyIdIsMandatory', $langs->transnoentitiesnoconv('MergeOriginThirdparty')), null, 'errors');
		} else {
			if (!$error && $soc_origin->fetch($soc_origin_id) < 1) {
				setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
				$error++;
			}

			if (!$error) {
				// TODO Move the merge function into class of object.

				$db->begin();

				// Recopy some data
				$object->client = $object->client | $soc_origin->client;
				$object->fournisseur = $object->fournisseur | $soc_origin->fournisseur;
				$listofproperties = array(
					'address', 'zip', 'town', 'state_id', 'country_id', 'phone', 'phone_pro', 'fax', 'email', 'socialnetworks', 'url', 'barcode',
					'idprof1', 'idprof2', 'idprof3', 'idprof4', 'idprof5', 'idprof6',
					'tva_intra', 'effectif_id', 'forme_juridique', 'remise_percent', 'remise_supplier_percent', 'mode_reglement_supplier_id', 'cond_reglement_supplier_id', 'name_bis',
					'stcomm_id', 'outstanding_limit', 'price_level', 'parent', 'default_lang', 'ref', 'ref_ext', 'import_key', 'fk_incoterms', 'fk_multicurrency',
					'code_client', 'code_fournisseur', 'code_compta', 'code_compta_fournisseur',
					'model_pdf', 'fk_projet'
				);
				foreach ($listofproperties as $property) {
					if (empty($object->$property)) {
						$object->$property = $soc_origin->$property;
					}
				}

				// Concat some data
				$listofproperties = array(
					'note_public', 'note_private'
				);
				foreach ($listofproperties as $property) {
					$object->$property = dol_concatdesc($object->$property, $soc_origin->$property);
				}

				// Merge extrafields
				if (is_array($soc_origin->array_options)) {
					foreach ($soc_origin->array_options as $key => $val) {
						if (empty($object->array_options[$key])) {
							$object->array_options[$key] = $val;
						}
					}
				}

				// If alias name is not defined on target thirdparty, we can store in it the old name of company.
				if (empty($object->name_bis) && $object->name != $soc_origin->name) {
					$object->name_bis = $soc_origin->name;
				}

				// Merge categories
				$static_cat = new Categorie($db);

				$custcats_ori = $static_cat->containing($soc_origin->id, 'customer', 'id');
				$custcats = $static_cat->containing($object->id, 'customer', 'id');
				$custcats = array_merge($custcats, $custcats_ori);
				$object->setCategories($custcats, 'customer');

				$suppcats_ori = $static_cat->containing($soc_origin->id, 'supplier', 'id');
				$suppcats = $static_cat->containing($object->id, 'supplier', 'id');
				$suppcats = array_merge($suppcats, $suppcats_ori);
				$object->setCategories($suppcats, 'supplier');

				// If thirdparty has a new code that is same than origin, we clean origin code to avoid duplicate key from database unique keys.
				if ($soc_origin->code_client == $object->code_client
					|| $soc_origin->code_fournisseur == $object->code_fournisseur
					|| $soc_origin->barcode == $object->barcode) {
					dol_syslog("We clean customer and supplier code so we will be able to make the update of target");
					$soc_origin->code_client = '';
					$soc_origin->code_fournisseur = '';
					$soc_origin->barcode = '';
					$soc_origin->update($soc_origin->id, $user, 0, 1, 1, 'merge');
				}

				// Update
				$result = $object->update($object->id, $user, 0, 1, 1, 'merge');

				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}

				// Move links
				if (!$error) {
					// This list is also into the api_thirdparties.class.php
					// TODO Mutualise the list into object societe.class.php
					$objects = array(
						'Adherent' => '/adherents/class/adherent.class.php',
						'Don' => array('file' => '/don/class/don.class.php', 'enabled' => isModEnabled('don')),
						'Societe' => '/societe/class/societe.class.php',
						//'Categorie' => '/categories/class/categorie.class.php',
						'ActionComm' => '/comm/action/class/actioncomm.class.php',
						'Propal' => '/comm/propal/class/propal.class.php',
						'Commande' => '/commande/class/commande.class.php',
						'Facture' => '/compta/facture/class/facture.class.php',
						'FactureRec' => '/compta/facture/class/facture-rec.class.php',
						'LignePrelevement' => '/compta/prelevement/class/ligneprelevement.class.php',
						'Mo' => '/mrp/class/mo.class.php',
						'Contact' => '/contact/class/contact.class.php',
						'Contrat' => '/contrat/class/contrat.class.php',
						'Expedition' => '/expedition/class/expedition.class.php',
						'Fichinter' => '/fichinter/class/fichinter.class.php',
						'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
						'FactureFournisseur' => '/fourn/class/fournisseur.facture.class.php',
						'SupplierProposal' => '/supplier_proposal/class/supplier_proposal.class.php',
						'ProductFournisseur' => '/fourn/class/fournisseur.product.class.php',
						'Delivery' => '/delivery/class/delivery.class.php',
						'Product' => '/product/class/product.class.php',
						'Project' => '/projet/class/project.class.php',
						'Ticket' => array('file' => '/ticket/class/ticket.class.php', 'enabled' => isModEnabled('ticket')),
						'User' => '/user/class/user.class.php',
						'Account' => '/compta/bank/class/account.class.php',
						'ConferenceOrBoothAttendee' => '/eventorganization/class/conferenceorboothattendee.class.php'
					);

					//First, all core objects must update their tables
					foreach ($objects as $object_name => $object_file) {
						if (is_array($object_file)) {
							if (empty($object_file['enabled'])) {
								continue;
							}
							$object_file = $object_file['file'];
						}

						require_once DOL_DOCUMENT_ROOT.$object_file;

						if (!$error && !$object_name::replaceThirdparty($db, $soc_origin->id, $object->id)) {
							$error++;
							setEventMessages($db->lasterror(), null, 'errors');
							break;
						}
					}
				}

				// External modules should update their ones too
				if (!$error) {
					$parameters = array('soc_origin' => $soc_origin->id, 'soc_dest' => $object->id);
					$reshook = $hookmanager->executeHooks('replaceThirdparty', $parameters, $object, $action);

					if ($reshook < 0) {
						setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						$error++;
					}
				}


				if (!$error) {
					$object->context = array('merge'=>1, 'mergefromid'=>$soc_origin->id, 'mergefromname'=>$soc_origin->name);

					// Call trigger
					$result = $object->call_trigger('COMPANY_MODIFY', $user);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
					// End call triggers
				}

				if (!$error) {
					// We finally remove the old thirdparty
					if ($soc_origin->delete($soc_origin->id, $user) < 1) {
						setEventMessages($soc_origin->error, $soc_origin->errors, 'errors');
						$error++;
					}
				}

				if (!$error) {
					setEventMessages($langs->trans('ThirdpartiesMergeSuccess'), null, 'mesgs');
					$db->commit();
				} else {
					$langs->load("errors");
					setEventMessages($langs->trans('ErrorsThirdpartyMerge'), null, 'errors');
					$db->rollback();
				}
			}
		}
	}

	if (GETPOST('getcustomercode')) {
		// We defined value code_client
		$_POST["customer_code"] = "Acompleter";
	}

	if (GETPOST('getsuppliercode')) {
		// We defined value code_fournisseur
		$_POST["supplier_code"] = "Acompleter";
	}

	if ($action == 'set_localtax1') {
		//get selected from combobox
		$value = GETPOST('lt1');
		$object->fetch($socid);
		$res = $object->setValueFrom('localtax1_value', $value, '', null, 'text', '', $user, 'COMPANY_MODIFY');
	}
	if ($action == 'set_localtax2') {
		//get selected from combobox
		$value = GETPOST('lt2');
		$object->fetch($socid);
		$res = $object->setValueFrom('localtax2_value', $value, '', null, 'text', '', $user, 'COMPANY_MODIFY');
	}

	if ($action == 'update_extras') {
		$object->fetch($socid);

		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$extrafields->fetch_name_optionals_label($object->table_element);

		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			$result = $object->insertExtraFields('COMPANY_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	// Add new or update third party
	if ((!GETPOST('getcustomercode') && !GETPOST('getsuppliercode'))
	&& ($action == 'add' || $action == 'update') && $user->hasRight('societe', 'creer')) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		if (!GETPOST('name')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ThirdPartyName")), null, 'errors');
			$error++;
		}
		if (GETPOST('client', 'int') && GETPOST('client', 'int') < 0) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProspectCustomer")), null, 'errors');
			$error++;
		}
		if (GETPOSTISSET('fournisseur') && GETPOST('fournisseur', 'int') < 0) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Supplier")), null, 'errors');
			$error++;
		}

		if (isModEnabled('mailing') && !empty($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS) && $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2 && GETPOST('contact_no_email', 'int')==-1 && !empty(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL))) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("No_Email")), null, 'errors');
		}

		if (isModEnabled('mailing') && GETPOST("private", 'int') == 1 && !empty($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS) && $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2 && GETPOST('contact_no_email', 'int')==-1 && !empty(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL))) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("No_Email")), null, 'errors');
		}

		if (!$error) {
			if ($action == 'update') {
				$ret = $object->fetch($socid);
				$object->oldcopy = clone $object;
			} else {
				$object->canvas = $canvas;
			}

			if (GETPOST("private", 'int') == 1) {	// Ask to create a contact
				$object->particulier		= GETPOST("private", 'int');

				$object->name = dolGetFirstLastname(GETPOST('firstname', 'alphanohtml'), GETPOST('name', 'alphanohtml'));
				$object->civility_id		= GETPOST('civility_id', 'alphanohtml'); // Note: civility id is a code, not an int
				// Add non official properties
				$object->name_bis			= GETPOST('name', 'alphanohtml');
				$object->firstname			= GETPOST('firstname', 'alphanohtml');
			} else {
				$object->name				= GETPOST('name', 'alphanohtml');
			}
			$object->entity					= (GETPOSTISSET('entity') ? GETPOST('entity', 'int') : $conf->entity);
			$object->name_alias				= GETPOST('name_alias', 'alphanohtml');
			$object->parent					= GETPOST('parent_company_id', 'int');
			$object->address				= GETPOST('address', 'alphanohtml');
			$object->zip					= GETPOST('zipcode', 'alphanohtml');
			$object->town					= GETPOST('town', 'alphanohtml');
			$object->country_id				= GETPOST('country_id', 'int');
			$object->state_id				= GETPOST('state_id', 'int');

			$object->socialnetworks = array();
			if (isModEnabled('socialnetworks')) {
				foreach ($socialnetworks as $key => $value) {
					if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
						$object->socialnetworks[$key] = GETPOST($key, 'alphanohtml');
					}
				}
			}

			$object->phone					= GETPOST('phone', 'alpha');
			$object->fax					= GETPOST('fax', 'alpha');
			$object->email					= trim(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL));
			$object->no_email 				= GETPOST("no_email", "int");
			$object->url					= trim(GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL));
			$object->idprof1				= trim(GETPOST('idprof1', 'alphanohtml'));
			$object->idprof2				= trim(GETPOST('idprof2', 'alphanohtml'));
			$object->idprof3				= trim(GETPOST('idprof3', 'alphanohtml'));
			$object->idprof4				= trim(GETPOST('idprof4', 'alphanohtml'));
			$object->idprof5				= trim(GETPOST('idprof5', 'alphanohtml'));
			$object->idprof6				= trim(GETPOST('idprof6', 'alphanohtml'));
			$object->prefix_comm			= GETPOST('prefix_comm', 'alphanohtml');
			$object->code_client			= GETPOSTISSET('customer_code') ?GETPOST('customer_code', 'alpha') : GETPOST('code_client', 'alpha');
			$object->code_fournisseur		= GETPOSTISSET('supplier_code') ?GETPOST('supplier_code', 'alpha') : GETPOST('code_fournisseur', 'alpha');
			$object->capital				= GETPOST('capital', 'alphanohtml');
			$object->barcode				= GETPOST('barcode', 'alphanohtml');

			$object->tva_intra				= GETPOST('tva_intra', 'alphanohtml');
			$object->tva_assuj				= GETPOST('assujtva_value', 'alpha');
			$object->vat_reverse_charge		= GETPOST('vat_reverse_charge') == 'on' ? 1 : 0;
			$object->status = GETPOST('status', 'alpha');

			// Local Taxes
			$object->localtax1_assuj		= GETPOST('localtax1assuj_value', 'alpha');
			$object->localtax2_assuj		= GETPOST('localtax2assuj_value', 'alpha');

			$object->localtax1_value		= GETPOST('lt1', 'alpha');
			$object->localtax2_value		= GETPOST('lt2', 'alpha');

			$object->forme_juridique_code	= GETPOST('forme_juridique_code', 'int');
			$object->effectif_id			= GETPOST('effectif_id', 'int');
			$object->typent_id				= GETPOST('typent_id', 'int');

			$object->typent_code			= dol_getIdFromCode($db, $object->typent_id, 'c_typent', 'id', 'code'); // Force typent_code too so check in verify() will be done on new type

			$object->client					= GETPOST('client', 'int');
			$object->fournisseur			= GETPOST('fournisseur', 'int');

			$object->commercial_id			= GETPOST('commercial_id', 'int');
			$object->default_lang			= GETPOST('default_lang');

			// Webservices url/key
			$object->webservices_url		= GETPOST('webservices_url', 'custom', 0, FILTER_SANITIZE_URL);
			$object->webservices_key		= GETPOST('webservices_key', 'san_alpha');

			if (GETPOSTISSET('accountancy_code_sell')) {
				$accountancy_code_sell		= GETPOST('accountancy_code_sell', 'alpha');

				if (empty($accountancy_code_sell) || $accountancy_code_sell == '-1') {
					$object->accountancy_code_sell = '';
				} else {
					$object->accountancy_code_sell = $accountancy_code_sell;
				}
			}
			if (GETPOSTISSET('accountancy_code_buy')) {
				$accountancy_code_buy		= GETPOST('accountancy_code_buy', 'alpha');

				if (empty($accountancy_code_buy) || $accountancy_code_buy == '-1') {
					$object->accountancy_code_buy = '';
				} else {
					$object->accountancy_code_buy = $accountancy_code_buy;
				}
			}

			// Incoterms
			if (isModEnabled('incoterm')) {
				$object->fk_incoterms = GETPOST('incoterm_id', 'int');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
			}

			// Multicurrency
			if (isModEnabled("multicurrency")) {
				$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
			}

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				 $error++;
			}

			// Fill array 'array_languages' with data from add form
			$ret = $object->setValuesForExtraLanguages();
			if ($ret < 0) {
				$error++;
			}
			//var_dump($object->array_languages);exit;

			if (!empty($_FILES['photo']['name'])) {
				$current_logo = $object->logo;
				$object->logo = dol_sanitizeFileName($_FILES['photo']['name']);
			}

			// Check parameters
			if (!GETPOST('cancel', 'alpha')) {
				if (!empty($object->email) && !isValidEMail($object->email)) {
					$langs->load("errors");
					$error++;
					setEventMessages($langs->trans("ErrorBadEMail", $object->email), null, 'errors');
				}
				if (!empty($object->url) && !isValidUrl($object->url)) {
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorBadUrl", $object->url), null, 'errors');
				}
				if (!empty($object->webservices_url)) {
					//Check if has transport, without any the soap client will give error
					if (strpos($object->webservices_url, "http") === false) {
						$object->webservices_url = "http://".$object->webservices_url;
					}
					if (!isValidUrl($object->webservices_url)) {
						$langs->load("errors");
						$error++; $errors[] = $langs->trans("ErrorBadUrl", $object->webservices_url);
					}
				}

				// We set country_id, country_code and country for the selected country
				$object->country_id = GETPOST('country_id', 'int') != '' ? GETPOST('country_id', 'int') : $mysoc->country_id;
				if ($object->country_id) {
					$tmparray = getCountry($object->country_id, 'all');
					$object->country_code = $tmparray['code'];
					$object->country = $tmparray['label'];
				}
			}
		}

		if (!$error) {
			if ($action == 'add') {
				$error = 0;

				$db->begin();

				if (empty($object->client)) {
					$object->code_client = '';
				}
				if (empty($object->fournisseur)) {
					$object->code_fournisseur = '';
				}

				$result = $object->create($user);

				if ($result >= 0 && isModEnabled('mailing') && !empty($object->email) && $object->no_email == 1) {
					// Add mass emailing flag into table mailing_unsubscribe
					$resultnoemail = $object->setNoEmail($object->no_email);
					if ($resultnoemail < 0) {
						$error++;
						$errors = array_merge($errors, ($object->error ? array($object->error) : $object->errors));
						$action = 'create';
					}
				}

				if ($result >= 0) {
					if ($object->particulier) {
						dol_syslog("We ask to create a contact/address too", LOG_DEBUG);
						$contcats = GETPOST('contcats', 'array');
						$no_email = GETPOST('contact_no_email', 'int');
						$result = $object->create_individual($user, $no_email, $contcats);
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						}
					}

					// Links with users
					$salesreps = GETPOST('commercial', 'array');
					$result = $object->setSalesRep($salesreps, true);
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}

					// Customer categories association
					$custcats = GETPOST('custcats', 'array');
					$result = $object->setCategories($custcats, 'customer');
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}

					// Supplier categories association
					$suppcats = GETPOST('suppcats', 'array');
					$result = $object->setCategories($suppcats, 'supplier');
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}

					// Logo/Photo save
					$dir = $conf->societe->multidir_output[$conf->entity]."/".$object->id."/logos/";
					$file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
					if ($file_OK) {
						if (image_format_supported($_FILES['photo']['name'])) {
							dol_mkdir($dir);

							if (@is_dir($dir)) {
								$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
								$result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

								if (!($result > 0)) {
									$errors[] = "ErrorFailedToSaveFile";
								} else {
									// Create thumbs
									$object->addThumbs($newfile);
								}
							}
						}
					} else {
						switch ($_FILES['photo']['error']) {
							case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
							case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
								$errors[] = "ErrorFileSizeTooLarge";
								break;
							case 3: //uploaded file was only partially uploaded
								$errors[] = "ErrorFilePartiallyUploaded";
								break;
						}
					}
				} else {
					if ($result == -3 && in_array('ErrorCustomerCodeAlreadyUsed', $object->errors)) {
						$duplicate_code_error = true;
						$object->code_client = null;
					}

					if ($result == -3 && in_array('ErrorSupplierCodeAlreadyUsed', $object->errors)) {
						$duplicate_code_error = true;
						$object->code_fournisseur = null;
					}

					if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {	// TODO Sometime errors on duplicate on profid and not on code, so we must manage this case
						$duplicate_code_error = true;
					}

					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}

				if ($result >= 0 && !$error) {
					$db->commit();

					if ($backtopagejsfields) {
						llxHeader('', '', '');

						$retstring = '<script>';
						$retstring .= 'jQuery(document).ready(function() {
												console.log(\'We execute action to create. We save id and go back - '.$dol_openinpopup.'\');
												console.log(\'id = '.$object->id.'\');
												$(\'#varforreturndialogid'.$dol_openinpopup.'\', window.parent.document).text(\''.$object->id.'\');
												$(\'#varforreturndialoglabel'.$dol_openinpopup.'\', window.parent.document).text(\''.$object->name.'\');
												window.parent.jQuery(\'#idfordialog'.$dol_openinpopup.'\').dialog(\'close\');
				 							});';
						$retstring .= '</script>';
						print $retstring;

						llxFooter();
						exit;
					}

					if (!empty($backtopage)) {
						$backtopage = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $backtopage); // New method to autoselect project after a New on another form object creation
						if (preg_match('/\?/', $backtopage)) {
							$backtopage .= '&socid='.$object->id; // Old method
						}
						header("Location: ".$backtopage);
						exit;
					} else {
						$url = $_SERVER["PHP_SELF"]."?socid=".$object->id; // Old method
						if (($object->client == 1 || $object->client == 3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) {
							$url = DOL_URL_ROOT."/comm/card.php?socid=".$object->id;
						} elseif ($object->fournisseur == 1) {
							$url = DOL_URL_ROOT."/fourn/card.php?socid=".$object->id;
						}

						header("Location: ".$url);
						exit;
					}
				} else {
					$db->rollback();
					$action = 'create';
				}
			}

			if ($action == 'update') {
				$error = 0;

				if (GETPOST('cancel', 'alpha')) {
					if (!empty($backtopage)) {
						header("Location: ".$backtopage);
						exit;
					} else {
						header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
						exit;
					}
				}

				// To not set code if third party is not concerned. But if it had values, we keep them.
				if (empty($object->client) && empty($object->oldcopy->code_client)) {
					$object->code_client = '';
				}
				if (empty($object->fournisseur) && empty($object->oldcopy->code_fournisseur)) {
					$object->code_fournisseur = '';
				}
				//var_dump($object);exit;

				$result = $object->update($socid, $user, 1, $object->oldcopy->codeclient_modifiable(), $object->oldcopy->codefournisseur_modifiable(), 'update', 0);

				if ($result > 0) {
					// Update mass emailing flag into table mailing_unsubscribe
					if (GETPOSTISSET('no_email') && $object->email) {
						$no_email = GETPOST('no_email', 'int');
						$result = $object->setNoEmail($no_email);
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$action = 'edit';
						}
					}

					$action = 'view';
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'edit';
				}

				if ($result <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}

				// Links with users
				$salesreps = GETPOST('commercial', 'array');
				$result = $object->setSalesRep($salesreps);
				if ($result < 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}

				// Prevent thirdparty's emptying if a user hasn't rights $user->rights->categorie->lire (in such a case, post of 'custcats' is not defined)
				if (!$error && $user->hasRight('categorie', 'lire')) {
					// Customer categories association
					$categories = GETPOST('custcats', 'array');
					$result = $object->setCategories($categories, 'customer');
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}

					// Supplier categories association
					$categories = GETPOST('suppcats', 'array');
					$result = $object->setCategories($categories, 'supplier');
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}

				// Logo/Photo save
				$dir     = $conf->societe->multidir_output[$object->entity]."/".$object->id."/logos";
				$file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
				if (GETPOST('deletephoto') && $object->logo) {
					$fileimg = $dir.'/'.$object->logo;
					$dirthumbs = $dir.'/thumbs';
					dol_delete_file($fileimg);
					dol_delete_dir_recursive($dirthumbs);
				}
				if ($file_OK) {
					if (image_format_supported($_FILES['photo']['name']) > 0) {
						if ($current_logo != $object->logo) {
							$fileimg = $dir.'/'.$current_logo;
							$dirthumbs = $dir.'/thumbs';
							dol_delete_file($fileimg);
							dol_delete_dir_recursive($dirthumbs);
						}

						dol_mkdir($dir);

						if (@is_dir($dir)) {
							$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
							$result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

							if (!($result > 0)) {
								$errors[] = "ErrorFailedToSaveFile";
							} else {
								// Create thumbs
								$object->addThumbs($newfile);

								// Index file in database
								if (!empty($conf->global->THIRDPARTY_LOGO_ALLOW_EXTERNAL_DOWNLOAD)) {
									require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
									// the dir dirname($newfile) is directory of logo, so we should have only one file at once into index, so we delete indexes for the dir
									deleteFilesIntoDatabaseIndex(dirname($newfile), '', '');
									// now we index the uploaded logo file
									addFileIntoDatabaseIndex(dirname($newfile), basename($newfile), '', 'uploaded', 1);
								}
							}
						}
					} else {
						$errors[] = "ErrorBadImageFormat";
					}
				} else {
					switch ($_FILES['photo']['error']) {
						case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
						case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
							$errors[] = "ErrorFileSizeTooLarge";
							break;
						case 3: //uploaded file was only partially uploaded
							$errors[] = "ErrorFilePartiallyUploaded";
							break;
					}
				}
				// Company logo management


				// Update linked member
				if (!$error && $object->fk_soc > 0) {
					$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
					$sql .= " SET fk_soc = NULL WHERE fk_soc = ".((int) $socid);
					if (!$object->db->query($sql)) {
						$error++;
						$object->error .= $object->db->lasterror();
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}

				if (!$error && !count($errors)) {
					if (!empty($backtopage)) {
						header("Location: ".$backtopage);
						exit;
					} else {
						header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
						exit;
					}
				} else {
					$object->id = $socid;
					$action = "edit";
				}
			}
		} else {
			$action = ($action == 'add' ? 'create' : 'edit');
		}
	}

	// Delete third party
	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('societe', 'supprimer')) {
		$object->fetch($socid);
		$object->oldcopy = clone $object;
		$result = $object->delete($socid, $user);

		if ($result > 0) {
			header("Location: ".DOL_URL_ROOT."/societe/list.php?restore_lastsearch_values=1&delsoc=".urlencode($object->name));
			exit;
		} else {
			$langs->load("errors");
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
			$action = '';
		}
	}

	// Set third-party type
	if ($action == 'set_thirdpartytype' && $user->hasRight('societe', 'creer')) {
		$object->fetch($socid);
		$result = $object->setThirdpartyType(GETPOST('typent_id', 'int'));
	}

	// Set incoterm
	if ($action == 'set_incoterms' && $user->hasRight('societe', 'creer') && isModEnabled('incoterm')) {
		$object->fetch($socid);
		$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
	}

	// Set parent company
	if ($action == 'set_thirdparty' && $user->hasRight('societe', 'creer')) {
		$object->fetch($socid);
		$result = $object->setParent(GETPOST('parent_id', 'int'));
	}

	// Set sales representatives
	if ($action == 'set_salesrepresentatives' && $user->hasRight('societe', 'creer')) {
		$object->fetch($socid);
		$result = $object->setSalesRep(GETPOST('commercial', 'array'));
	}

	// warehouse
	if ($action == 'setwarehouse' && $user->hasRight('societe', 'creer')) {
		$result = $object->setWarehouse(GETPOST('fk_warehouse', 'int'));
	}

	$id = $socid;
	$object->fetch($socid);

	// Selection of new fields
	if (!empty($conf->global->MAIN_DUPLICATE_CONTACTS_TAB_ON_MAIN_CARD) && (empty($conf->global->SOCIETE_DISABLE_CONTACTS) || !empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT))) {
		include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
	}

	// Actions to send emails
	$triggersendname = 'COMPANY_SENTBYMAIL';
	$paramname = 'socid';
	$mode = 'emailfromthirdparty';
	$trackid = 'thi'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$id = $socid;
	$upload_dir = !empty($conf->societe->multidir_output[$object->entity])?$conf->societe->multidir_output[$object->entity]:$conf->societe->dir_output;
	$permissiontoadd = $user->hasRight('societe', 'creer');
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 *  View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$formcompany = new FormCompany($db);
if (isModEnabled('accounting')) {
	$formaccounting = new FormAccounting($db);
}

if ($socid > 0 && empty($object->id)) {
	$result = $object->fetch($socid);
	if ($result <= 0) {
		dol_print_error('', $object->error);
		exit(-1);
	}
}

$title = $langs->trans("ThirdParty");
if ($action == 'create') {
	$title = $langs->trans("NewThirdParty");
}
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) {
	$title = $object->name." - ".$langs->trans('Card');
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas|DE:Modul_Geschäftspartner';

llxHeader('', $title, $help_url);

$countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	$objcanvas->assign_values($action, $object->id, $object->ref); // Set value for templates
	$objcanvas->display_canvas($action); // Show template
} else {
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------
	if ($action == 'create') {
		/*
		 *  Creation
		 */
		$private = GETPOST("private", "int");
		if (!empty($conf->global->THIRDPARTY_DEFAULT_CREATE_CONTACT) && !GETPOSTISSET('private')) {
			$private = 1;
		}
		if (empty($private)) {
			$private = 0;
		}

		// Load object modCodeTiers
		$module = (!empty($conf->global->SOCIETE_CODECLIENT_ADDON) ? $conf->global->SOCIETE_CODECLIENT_ADDON : 'mod_codeclient_leopard');
		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
			$module = substr($module, 0, dol_strlen($module) - 4);
		}
		$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
		foreach ($dirsociete as $dirroot) {
			$res = dol_include_once($dirroot.$module.'.php');
			if ($res) {
				break;
			}
		}
		$modCodeClient = new $module;
		// Load object modCodeFournisseur
		$module = (!empty($conf->global->SOCIETE_CODECLIENT_ADDON) ? $conf->global->SOCIETE_CODECLIENT_ADDON : 'mod_codeclient_leopard');
		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
			$module = substr($module, 0, dol_strlen($module) - 4);
		}
		$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
		foreach ($dirsociete as $dirroot) {
			$res = dol_include_once($dirroot.$module.'.php');
			if ($res) {
				break;
			}
		}
		$modCodeFournisseur = new $module;

		// Define if customer/prospect or supplier status is set or not
		if (GETPOST("type", 'aZ') != 'f') {
			$object->client = -1;
			if (!empty($conf->global->THIRDPARTY_CUSTOMERPROSPECT_BY_DEFAULT)) {
				$object->client = 3;
			}
		}
		// Prospect / Customer
		if (GETPOST("type", 'aZ') == 'c') {
			if (!empty($conf->global->THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT)) {
				$object->client = $conf->global->THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT;
			} else {
				$object->client = 3;
			}
		}
		if (GETPOST("type", 'aZ') == 'p') {
			$object->client = 2;
		}

		if (!empty($conf->global->SOCIETE_DISABLE_PROSPECTSCUSTOMERS) && $object->client == 3) {
			$object->client = 1;
		}

		if ((isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) && (GETPOST("type") == 'f' || (GETPOST("type") == '' && !empty($conf->global->THIRDPARTY_SUPPLIER_BY_DEFAULT)))) {
			$object->fournisseur = 1;
		}

		$object->name = GETPOST('name', 'alphanohtml');
		$object->name_alias = GETPOST('name_alias', 'alphanohtml');
		$object->firstname = GETPOST('firstname', 'alphanohtml');
		$object->particulier		= $private;
		$object->prefix_comm		= GETPOST('prefix_comm', 'alphanohtml');
		$object->client = GETPOST('client', 'int') ?GETPOST('client', 'int') : $object->client;

		if (empty($duplicate_code_error)) {
			$object->code_client		= GETPOST('customer_code', 'alpha');
			$object->fournisseur		= GETPOST('fournisseur') ? GETPOST('fournisseur', 'int') : $object->fournisseur;
			$object->code_fournisseur = GETPOST('supplier_code', 'alpha');
		} else {
			setEventMessages($langs->trans('NewCustomerSupplierCodeProposed'), null, 'warnings');
		}

		$object->address = GETPOST('address', 'alphanohtml');
		$object->zip = GETPOST('zipcode', 'alphanohtml');
		$object->town = GETPOST('town', 'alphanohtml');
		$object->state_id = GETPOST('state_id', 'int');

		$object->socialnetworks = array();
		if (isModEnabled('socialnetworks')) {
			foreach ($socialnetworks as $key => $value) {
				if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
					$object->socialnetworks[$key] = GETPOST($key, 'alphanohtml');
				}
			}
		}

		$object->phone				= GETPOST('phone', 'alpha');
		$object->fax				= GETPOST('fax', 'alpha');
		$object->email				= GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
		$object->url				= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
		$object->capital			= GETPOST('capital', 'alphanohtml');
		$object->barcode			= GETPOST('barcode', 'alphanohtml');
		$object->idprof1			= GETPOST('idprof1', 'alphanohtml');
		$object->idprof2			= GETPOST('idprof2', 'alphanohtml');
		$object->idprof3			= GETPOST('idprof3', 'alphanohtml');
		$object->idprof4			= GETPOST('idprof4', 'alphanohtml');
		$object->idprof5			= GETPOST('idprof5', 'alphanohtml');
		$object->idprof6			= GETPOST('idprof6', 'alphanohtml');
		$object->typent_id = GETPOST('typent_id', 'int');
		$object->effectif_id		= GETPOST('effectif_id', 'int');
		$object->civility_id		= GETPOST('civility_id', 'alpha');

		$object->tva_assuj = GETPOST('assujtva_value', 'int');
		$object->vat_reverse_charge	= GETPOST('vat_reverse_charge') == 'on' ? 1 : 0;
		$object->status = GETPOST('status', 'int');

		//Local Taxes
		$object->localtax1_assuj	= GETPOST('localtax1assuj_value', 'int');
		$object->localtax2_assuj	= GETPOST('localtax2assuj_value', 'int');

		$object->localtax1_value	= GETPOST('lt1', 'int');
		$object->localtax2_value	= GETPOST('lt2', 'int');

		$object->tva_intra = GETPOST('tva_intra', 'alphanohtml');

		$object->commercial_id = GETPOST('commercial_id', 'int');
		$object->default_lang = GETPOST('default_lang');

		if (GETPOSTISSET('accountancy_code_sell')) {
			$accountancy_code_sell  = GETPOST('accountancy_code_sell', 'alpha');

			if (empty($accountancy_code_sell) || $accountancy_code_sell == '-1') {
				$object->accountancy_code_sell = '';
			} else {
				$object->accountancy_code_sell = $accountancy_code_sell;
			}
		}
		if (GETPOSTISSET('accountancy_code_buy')) {
			$accountancy_code_buy   = GETPOST('accountancy_code_buy', 'alpha');

			if (empty($accountancy_code_buy) || $accountancy_code_buy == '-1') {
				$object->accountancy_code_buy = '';
			} else {
				$object->accountancy_code_buy = $accountancy_code_buy;
			}
		}

		$object->logo = (isset($_FILES['photo']) ?dol_sanitizeFileName($_FILES['photo']['name']) : '');

		// Company logo management
		$dir     = $conf->societe->multidir_output[$conf->entity]."/".$object->id."/logos";
		$file_OK = (isset($_FILES['photo']) ?is_uploaded_file($_FILES['photo']['tmp_name']) : false);
		if ($file_OK) {
			if (image_format_supported($_FILES['photo']['name'])) {
				dol_mkdir($dir);

				if (@is_dir($dir)) {
					$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
					$result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

					if (!($result > 0)) {
						$errors[] = "ErrorFailedToSaveFile";
					} else {
						// Create thumbs
						$object->addThumbs($newfile);
					}
				}
			}
		}

		// We set country_id, country_code and country for the selected country
		$object->country_id = GETPOST('country_id') ?GETPOST('country_id') : $mysoc->country_id;
		if ($object->country_id) {
			$tmparray = getCountry($object->country_id, 'all');
			$object->country_code = $tmparray['code'];
			$object->country = $tmparray['label'];
		}
		$object->forme_juridique_code = GETPOST('forme_juridique_code');

		// We set multicurrency_code if enabled
		if (isModEnabled("multicurrency")) {
			$object->multicurrency_code = GETPOST('multicurrency_code') ? GETPOST('multicurrency_code') : $conf->currency;
		}
		/* Show create form */

		$linkback = "";
		print load_fiche_titre($langs->trans("NewThirdParty"), $linkback, 'building');

		if (!empty($conf->use_javascript_ajax)) {
			if (!empty($conf->global->THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION)) {
				print "\n".'<script type="text/javascript">';
				print '$(document).ready(function () {
						id_te_private=8;
                        id_ef15=1;
                        is_private=' . $private.';
						if (is_private) {
							$(".individualline").show();
						} else {
							$(".individualline").hide();
						}
                        $("#radiocompany").click(function() {
                        	$(".individualline").hide();
                        	$("#typent_id").val(0);
                        	$("#typent_id").change();
                        	$("#effectif_id").val(0);
                        	$("#effectif_id").change();
                        	$("#TypeName").html(document.formsoc.ThirdPartyName.value);
                        	document.formsoc.private.value=0;
                        });
                        $("#radioprivate").click(function() {
                        	$(".individualline").show();
                        	$("#typent_id").val(id_te_private);
                        	$("#typent_id").change();
                        	$("#effectif_id").val(id_ef15);
                        	$("#effectif_id").change();
							/* Force to recompute the width of a select2 field when it was hidden and then shown programatically */
							if ($("#civility_id").data("select2")) {
								$("#civility_id").select2({width: "resolve"});
							}
                        	$("#TypeName").html(document.formsoc.LastName.value);
                        	document.formsoc.private.value=1;
                        });

						var canHaveCategoryIfNotCustomerProspectSupplier = ' . (empty($conf->global->THIRDPARTY_CAN_HAVE_CATEGORY_EVEN_IF_NOT_CUSTOMER_PROSPECT) ? '0' : '1') . ';

						init_customer_categ();
			  			$("#customerprospect").change(function() {
								init_customer_categ();
						});
						function init_customer_categ() {
								console.log("is customer or prospect = "+jQuery("#customerprospect").val());
								if (jQuery("#customerprospect").val() == 0 && !canHaveCategoryIfNotCustomerProspectSupplier)
								{
									jQuery(".visibleifcustomer").hide();
								}
								else
								{
									jQuery(".visibleifcustomer").show();
								}
						}

						init_supplier_categ();
			       		$("#fournisseur").change(function() {
							init_supplier_categ();
						});
						function init_supplier_categ() {
								console.log("is supplier = "+jQuery("#fournisseur").val());
								if (jQuery("#fournisseur").val() == 0)
								{
									jQuery(".visibleifsupplier").hide();
								}
								else
								{
									jQuery(".visibleifsupplier").show();
								}
						}

                        $("#selectcountry_id").change(function() {
                        	document.formsoc.action.value="create";
                        	document.formsoc.submit();
                        });';
				if ($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2) {
					print '
						function init_check_no_email(input) {
							if (input.val()!="") {
								$(".noemail").addClass("fieldrequired");
							} else {
								$(".noemail").removeClass("fieldrequired");
							}
						}
						$("#email").keyup(function() {
							init_check_no_email($(this));
						});
						init_check_no_email($("#email"));';
				}
				print '});';
				print '</script>'."\n";

				print '<div id="selectthirdpartytype">';
				print '<div class="hideonsmartphone float">';
				print $langs->trans("ThirdPartyType").': &nbsp; &nbsp; ';
				print '</div>';
				print '<label for="radiocompany" class="radiocompany">';
				print '<input type="radio" id="radiocompany" class="flat" name="private"  value="0"'.($private ? '' : ' checked').'>';
				print '&nbsp;';
				print $langs->trans("CreateThirdPartyOnly");
				print '</label>';
				print ' &nbsp; &nbsp; ';
				print '<label for="radioprivate" class="radioprivate">';
				$text = '<input type="radio" id="radioprivate" class="flat" name="private" value="1"'.($private ? ' checked' : '').'>';
				$text .= '&nbsp;';
				$text .= $langs->trans("CreateThirdPartyAndContact");
				$htmltext = $langs->trans("ToCreateContactWithSameName");
				print $form->textwithpicto($text, $htmltext, 1, 'help', '', 0, 3);
				print '</label>';
				print '</div>';
				print "<br>\n";
			} else {
				print '<script type="text/javascript">';
				print '$(document).ready(function () {
                        $("#selectcountry_id").change(function() {
                        	document.formsoc.action.value="create";
                        	document.formsoc.submit();
                        });
                     });';
				print '</script>'."\n";
			}
		}

		dol_htmloutput_mesg(is_numeric($error) ? '' : $error, $errors, 'error');

		print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formsoc" autocomplete="off">'; // Chrome ignor autocomplete

		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<input type="hidden" name="backtopagejsfields" value="'.$backtopagejsfields.'">';
		print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';
		print '<input type="hidden" name="private" value='.$object->particulier.'>';
		print '<input type="hidden" name="type" value='.GETPOST("type", 'alpha').'>';
		print '<input type="hidden" name="LastName" value="'.$langs->trans('ThirdPartyName').' / '.$langs->trans('LastName').'">';
		print '<input type="hidden" name="ThirdPartyName" value="'.$langs->trans('ThirdPartyName').'">';
		if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) {
			print '<input type="hidden" name="code_auto" value="1">';
		}

		print dol_get_fiche_head(null, 'card', '', 0, '');

		print '<table class="border centpercent">';

		// Name, firstname
		print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
		if ($object->particulier || $private) {
			print '<span id="TypeName" class="fieldrequired">'.$langs->trans('ThirdPartyName').' / '.$langs->trans('LastName', 'name').'</span>';
		} else {
			print '<span id="TypeName" class="fieldrequired">'.$form->editfieldkey('ThirdPartyName', 'name', '', $object, 0).'</span>';
		}
		print '</td><td'.(empty($conf->global->SOCIETE_USEPREFIX) ? ' colspan="3"' : '').'>';

		print '<input type="text" class="minwidth300" maxlength="128" name="name" id="name" value="'.dol_escape_htmltag($object->name).'" autofocus="autofocus">';
		print $form->widgetForTranslation("name", $object, $permissiontoadd, 'string', 'alpahnohtml', 'minwidth300');	// For some countries that need the company name in 2 languages
		// This implementation of the feature to search already existing company has been disabled. It must be implemented by keeping the "input text" and we must call the search ajax societe/ajax/ajaxcompanies.php
		// on a keydown of the input. We should show data about a duplicate found if we found less than 5 answers into a div under the input.
		/*
		print '<select class="name" name="name" id="name" style="min-width:500px"></select>';
		print "\n".'<script type="text/javascript">';
		print '$(document).ready(function () {
			$("#name").select2({
				ajax: {
				  url: "' . DOL_URL_ROOT . '/core/ajax/ajaxcompanies.php",
				  dataType: "json",
				  delay: 250,
				  data: function (params) {
						return {
							newcompany: params.term // search term
						}
				  },
				  processResults: function (data, params) {
					  return {
						results: data
					  }
				  },
				  cache: true
				},

				placeholder: "' . $langs->trans('Name of the new third party. In the meantime we check if it already exists...') . '",
				allowClear: true,
				minimumInputLength: 3,
				language: select2arrayoflanguage,
				containerCssClass: ":all:",
				selectionCssClass: ":all:",
				tags: true,
				templateResult: formatCustomer,
				templateSelection: formatCustomerSelection
			});

			function formatCustomer (Customer) {
				if(Customer.label === undefined) {
					return Customer.text;
				}

				if(Customer.logo !== null ) {
					logo = \'<img src="\';
					logo += \'' . DOL_URL_ROOT . '/viewimage.php?modulepart=societe&amp;entity=1&amp;file=\' + Customer.key + "%2Flogos%2Fthumbs%2F" + Customer.logo.replace(".", "_mini.") + "&amp;cache=0";
					logo += \'" /></div>\';
				} else {
					logo = \'<div class="floatleft inline-block valigntop photowithmargin" style="padding:0 10px"><div class="photosociete photoref" alt="No photo"><span class="fas fa-building" style="color: #6c6aa8;"></span></div></div>\';
				}

				var $container = $("<div class=\'select2-result-repository clearfix\'>" +
					 "<div class=\'select2-result-repository__avatar floatleft inline-block valigntop\'>" + logo +
					  "<div class=\'select2-result-repository__meta floatleft inline-block valigntop\'>" +
						"<div class=\'select2-result-repository__title\'></div>" +
						"<div class=\'select2-result-repository__name_alias\'></div>" +
						"<div class=\'select2-result-repository__code_client\'></div>" +
						"<div class=\'select2-result-repository__code_fournisseur\'></div>" +
						"<div class=\'select2-result-repository__companies_info\'>" +
						  "<div class=\'select2-result-repository__email\'><i class=\'fa fa-at\'></i> </div>" +
						  "<div class=\'select2-result-repository__address\'><i class=\'fa fa-flag\'></i> </div>" +
						  "<div class=\'select2-result-repository__zip\'><i class=\'fa fa-circle-o\'></i> </div>" +
						  "<div class=\'select2-result-repository__country\'><i class=\'fa fa-globe-americas\'></i> </div>" +
						  "<div class=\'select2-result-repository__departement\'><i class=\'fa fa-circle-o\'></i> </div>" +
						  "<div class=\'select2-result-repository__town\'><i class=\'fa fa-circle-o\'></i> </div>" +
						  "<div class=\'select2-result-repository__siren\'><i class=\'fa fa-circle-o\'></i> </div>" +
						  "<div class=\'select2-result-repository__datec\'><i class=\'fa fa-calendar\'></i> </div>" +
						"</div>" +
					  "</div>" +
					"</div>"
				);

				$container.find(".select2-result-repository__title").text(Customer.label);
				$container.find(".select2-result-repository__name_alias").text(Customer.name_alias ? Customer.name_alias : "");
				$container.find(".select2-result-repository__code_client").text(Customer.code_client ? Customer.code_client  : "");
				$container.find(".select2-result-repository__code_fournisseur").text((Customer.code_fournisseur!==null) ? Customer.code_fournisseur : "");
				$container.find(".select2-result-repository__email").append("' . $langs->trans('EMail') . ': " + (Customer.email !== null ? Customer.email : ""));
				$container.find(".select2-result-repository__address").append("' . $langs->trans('Address') . ': " + (Customer.address !== null ? Customer.address : ""));
				$container.find(".select2-result-repository__country").append("' . $langs->trans('Country') . ': " + (Customer.country !== null ? Customer.country : ""));
				$container.find(".select2-result-repository__departement").append("' . $langs->trans('Region-State') . ': " + (Customer.departement !== null ? Customer.departement : ""));
				$container.find(".select2-result-repository__zip").append("' . $langs->trans('Zip') . ': " + (Customer.zip !== null ? Customer.zip : ""));
				$container.find(".select2-result-repository__town").append("' . $langs->trans('Town') . ': " + (Customer.town !== null ? Customer.town : ""));
				$container.find(".select2-result-repository__siren").append("' . $langs->trans('Siren') . ': " + (Customer.siren !== null ? Customer.siren : ""));
				$container.find(".select2-result-repository__datec").append("' . $langs->trans('Created') . ': " + (Customer.datec !== null ? Customer.datec : ""));

				return $container;
			}

			function formatCustomerSelection (selection) {
				return selection.label || selection.text;
			}
		});
		</script>
		';
		*/
		print '</td>';
		if (!empty($conf->global->SOCIETE_USEPREFIX)) {  // Old not used prefix field
			print '<td>'.$langs->trans('Prefix').'</td><td><input type="text" size="5" maxlength="5" name="prefix_comm" value="'.dol_escape_htmltag($object->prefix_comm).'"></td>';
		}
		print '</tr>';

		// If javascript on, we show option individual
		if ($conf->use_javascript_ajax) {
			if (!empty($conf->global->THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION)) {
				// Firstname
				print '<tr class="individualline"><td>'.$form->editfieldkey('FirstName', 'firstname', '', $object, 0).'</td>';
				print '<td colspan="3"><input type="text" class="minwidth300" maxlength="128" name="firstname" id="firstname" value="'.dol_escape_htmltag($object->firstname).'"></td>';
				print '</tr>';

				// Title
				print '<tr class="individualline"><td>'.$form->editfieldkey('UserTitle', 'civility_id', '', $object, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
				print $formcompany->select_civility($object->civility_id, 'civility_id', 'maxwidth100').'</td>';
				print '</tr>';
			}
		}

		// Alias names (commercial, trademark or alias names)
		print '<tr id="name_alias"><td><label for="name_alias_input">'.$langs->trans('AliasNames').'</label></td>';
		print '<td colspan="3"><input type="text" class="minwidth300" name="name_alias" id="name_alias_input" value="'.dol_escape_htmltag($object->name_alias).'"></td></tr>';

		// Prospect/Customer
		print '<tr><td class="titlefieldcreate">'.$form->editfieldkey('ProspectCustomer', 'customerprospect', '', $object, 0, 'string', '', 1).'</td>';
		print '<td class="maxwidthonsmartphone">';
		$selected = (GETPOSTISSET('client') ?GETPOST('client', 'int') : $object->client);
		print $formcompany->selectProspectCustomerType($selected);
		print '</td>';

		if ($conf->browser->layout == 'phone') {
			print '</tr><tr>';
		}

		print '<td>'.$form->editfieldkey('CustomerCode', 'customer_code', '', $object, 0).'</td><td>';
		print '<table class="nobordernopadding"><tr><td>';
		$tmpcode = $object->code_client;
		if (empty($tmpcode) && !empty($modCodeClient->code_auto)) {
			$tmpcode = $modCodeClient->getNextValue($object, 0);
		}
		print '<input type="text" name="customer_code" id="customer_code" class="maxwidthonsmartphone" value="'.dol_escape_htmltag($tmpcode).'" maxlength="24">';
		print '</td><td>';
		$s = $modCodeClient->getToolTip($langs, $object, 0);
		print $form->textwithpicto('', $s, 1);
		print '</td></tr></table>';
		print '</td></tr>';

		if ((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire))
			|| (isModEnabled('supplier_proposal') && !empty($user->rights->supplier_proposal->lire))) {
			// Supplier
			print '<tr>';
			print '<td>'.$form->editfieldkey('Vendor', 'fournisseur', '', $object, 0, 'string', '', 1).'</td><td>';
			$default = -1;
			if (!empty($conf->global->THIRDPARTY_SUPPLIER_BY_DEFAULT)) {
				$default = 1;
			}
			print $form->selectyesno("fournisseur", (GETPOST('fournisseur', 'int') != '' ? GETPOST('fournisseur', 'int') : (GETPOST("type", 'alpha') == '' ? $default : $object->fournisseur)), 1, 0, (GETPOST("type", 'alpha') == '' ? 1 : 0), 1);
			print '</td>';


			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}

			print '<td>';
			if ((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire))) {
				print $form->editfieldkey('SupplierCode', 'supplier_code', '', $object, 0);
			}
			print '</td><td>';
			if ((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire))) {
				print '<table class="nobordernopadding"><tr><td>';
				$tmpcode = $object->code_fournisseur;
				if (empty($tmpcode) && !empty($modCodeFournisseur->code_auto)) {
					$tmpcode = $modCodeFournisseur->getNextValue($object, 1);
				}
				print '<input type="text" name="supplier_code" id="supplier_code" class="maxwidthonsmartphone" value="'.dol_escape_htmltag($tmpcode).'" maxlength="24">';
				print '</td><td>';
				$s = $modCodeFournisseur->getToolTip($langs, $object, 1);
				print $form->textwithpicto('', $s, 1);
				print '</td></tr></table>';
			}
			print '</td></tr>';
		}

		// Status
		print '<tr><td>'.$form->editfieldkey('Status', 'status', '', $object, 0).'</td><td colspan="3">';
		print $form->selectarray('status', array('0'=>$langs->trans('ActivityCeased'), '1'=>$langs->trans('InActivity')), 1, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
		print '</td></tr>';

		// Barcode
		if (isModEnabled('barcode')) {
			print '<tr><td>'.$form->editfieldkey('Gencod', 'barcode', '', $object, 0).'</td>';
			print '<td colspan="3">';
			print img_picto('', 'barcode', 'class="pictofixedwidth"');
			print '<input type="text" name="barcode" id="barcode" value="'.dol_escape_htmltag($object->barcode).'">';
			print '</td></tr>';
		}

		// Address
		print '<tr><td class="tdtop">';
		print $form->editfieldkey('Address', 'address', '', $object, 0);
		print '</td>';
		print '<td colspan="3">';
		print '<textarea name="address" id="address" class="quatrevingtpercent" rows="'.ROWS_2.'" wrap="soft">';
		print dol_escape_htmltag($object->address, 0, 1);
		print '</textarea>';
		print $form->widgetForTranslation("address", $object, $permissiontoadd, 'textarea', 'alphanohtml', 'quatrevingtpercent');
		print '</td></tr>';

		// Zip / Town
		print '<tr><td>'.$form->editfieldkey('Zip', 'zipcode', '', $object, 0).'</td><td>';
		print $formcompany->select_ziptown($object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 0, 0, '', 'maxwidth100');
		print '</td>';
		if ($conf->browser->layout == 'phone') {
			print '</tr><tr>';
		}
		print '<td class="tdtop">'.$form->editfieldkey('Town', 'town', '', $object, 0).'</td><td>';
		print $formcompany->select_ziptown($object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 0, '', 'maxwidth150 quatrevingtpercent');
		print $form->widgetForTranslation("town", $object, $permissiontoadd, 'string', 'alphanohtml', 'maxwidth100 quatrevingtpercent');
		print '</td></tr>';

		// Country
		print '<tr><td>'.$form->editfieldkey('Country', 'selectcountry_id', '', $object, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
		print img_picto('', 'country', 'class="pictofixedwidth"');
		print $form->select_country((GETPOSTISSET('country_id') ? GETPOST('country_id') : $object->country_id), 'country_id', '', 0, 'minwidth300 maxwidth500 widthcentpercentminusx');
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td></tr>';

		// State
		if (empty($conf->global->SOCIETE_DISABLE_STATE)) {
			if (!empty($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT) && ($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 1 || $conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 2)) {
				print '<tr><td>'.$form->editfieldkey('Region-State', 'state_id', '', $object, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
			} else {
				print '<tr><td>'.$form->editfieldkey('State', 'state_id', '', $object, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
			}

			if ($object->country_id) {
				print img_picto('', 'state', 'class="pictofixedwidth"');
				print $formcompany->select_state($object->state_id, $object->country_code);
			} else {
				print $countrynotdefined;
			}
			print '</td></tr>';
		}

		// Phone / Fax
		print '<tr><td>'.$form->editfieldkey('Phone', 'phone', '', $object, 0).'</td>';
		print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>'.img_picto('', 'object_phoning', 'class="pictofixedwidth"').' <input type="text" name="phone" id="phone" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('phone') ?GETPOST('phone', 'alpha') : $object->phone).'"></td>';
		if ($conf->browser->layout == 'phone') {
			print '</tr><tr>';
		}
		print '<td>'.$form->editfieldkey('Fax', 'fax', '', $object, 0).'</td>';
		print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>'.img_picto('', 'object_phoning_fax', 'class="pictofixedwidth"').' <input type="text" name="fax" id="fax" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('fax') ?GETPOST('fax', 'alpha') : $object->fax).'"></td></tr>';

		// Email / Web
		print '<tr><td>'.$form->editfieldkey('EMail', 'email', '', $object, 0, 'string', '', empty($conf->global->SOCIETE_EMAIL_MANDATORY) ? '' : $conf->global->SOCIETE_EMAIL_MANDATORY).'</td>';
		print '<td'.(($conf->browser->layout == 'phone') || !isModEnabled('mailing') ? ' colspan="3"' : '').'>'.img_picto('', 'object_email', 'class="pictofixedwidth"').' <input type="text" class="maxwidth200 widthcentpercentminusx" name="email" id="email" value="'.$object->email.'"></td>';
		if (isModEnabled('mailing') && !empty($conf->global->THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION)) {
			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}
			print '<td class="individualline noemail">'.$form->editfieldkey($langs->trans('No_Email') .' ('.$langs->trans('Contact').')', 'contact_no_email', '', $object, 0).'</td>';
			print '<td class="individualline" '.(($conf->browser->layout == 'phone') || !isModEnabled('mailing') ? ' colspan="3"' : '').'>'.$form->selectyesno('contact_no_email', (GETPOSTISSET("contact_no_email") ? GETPOST("contact_no_email", 'alpha') : (empty($object->no_email) ? 0 : 1)), 1, false, 1).'</td>';
		}
		print '</tr>';
		print '<tr><td>'.$form->editfieldkey('Web', 'url', '', $object, 0).'</td>';
		print '<td colspan="3">'.img_picto('', 'globe', 'class="pictofixedwidth"').' <input type="text" class="maxwidth500 widthcentpercentminusx" name="url" id="url" value="'.$object->url.'"></td></tr>';

		// Unsubscribe
		if (isModEnabled('mailing')) {
			if ($conf->use_javascript_ajax && $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2) {
				print "\n".'<script type="text/javascript">'."\n";
				print '$(document).ready(function () {
							$("#email").keyup(function() {
								if ($(this).val()!="") {
									$(".noemail").addClass("fieldrequired");
								} else {
									$(".noemail").removeClass("fieldrequired");
								}
							});
						})'."\n";
				print '</script>'."\n";
			}
			if (!GETPOSTISSET("no_email") && !empty($object->email)) {
				$result = $object->getNoEmail();
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
			print '<tr>';
			print '<td class="noemail"><label for="no_email">'.$langs->trans("No_Email").'</label></td>';
			print '<td>';
			print $form->selectyesno('no_email', (GETPOSTISSET("no_email") ? GETPOST("no_email", 'int') : $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS), 1, false, ($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2));
			print '</td>';
			print '</tr>';
		}

		// Social networks
		if (isModEnabled('socialnetworks')) {
			$object->showSocialNetwork($socialnetworks, ($conf->browser->layout == 'phone' ? 2 : 4));
		}

		// Prof ids
		$i = 1; $j = 0; $NBCOLS = ($conf->browser->layout == 'phone' ? 1 : 2);
		while ($i <= 6) {
			$idprof = $langs->transcountry('ProfId'.$i, $object->country_code);
			if ($idprof != '-')	{
				$key = 'idprof'.$i;

				if (($j % $NBCOLS) == 0) {
					print '<tr>';
				}

				$idprof_mandatory = 'SOCIETE_IDPROF'.($i).'_MANDATORY';
				print '<td>'.$form->editfieldkey($idprof, $key, '', $object, 0, 'string', '', (empty($conf->global->$idprof_mandatory) ? 0 : 1)).'</td><td>';

				print $formcompany->get_input_id_prof($i, $key, $object->$key, $object->country_code);
				print '</td>';
				if (($j % $NBCOLS) == ($NBCOLS - 1)) {
					print '</tr>';
				}
				$j++;
			}
			$i++;
		}
		if ($NBCOLS > 1 && ($j % 2 == 1)) {
			print '<td colspan="2"></td></tr>';
		}

		// Vat is used
		print '<tr><td>'.$form->editfieldkey('VATIsUsed', 'assujtva_value', '', $object, 0).'</td>';
		print '<td>';
		print $form->selectyesno('assujtva_value', GETPOSTISSET('assujtva_value') ?GETPOST('assujtva_value', 'int') : 1, 1); // Assujeti par defaut en creation
		print '</td>';
		if ($conf->browser->layout == 'phone') {
			print '</tr><tr>';
		}
		print '<td class="nowrap">'.$form->editfieldkey('VATIntra', 'intra_vat', '', $object, 0).'</td>';
		print '<td class="nowrap">';
		$s = '<input type="text" class="flat maxwidthonsmartphone" name="tva_intra" id="intra_vat" maxlength="20" value="'.$object->tva_intra.'">';

		if (empty($conf->global->MAIN_DISABLEVATCHECK) && isInEEC($object)) {
			$s .= ' ';

			if (!empty($conf->use_javascript_ajax))	{
				$widthpopup = 600;
				if (!empty($conf->dol_use_jmobile)) {
					$widthpopup = 350;
				}
				$heightpopup = 400;
				print "\n";
				print '<script type="text/javascript">';
				print "function CheckVAT(a) {\n";
				if ($mysoc->country_code == 'GR' && $object->country_code == 'GR' && !empty($u)) {
					print "GRVAT(a,'{$u}','{$p}','{$myafm}');\n";
				} else {
					print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a, '".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."', ".$widthpopup.", ".$heightpopup.");\n";
				}
				print "}\n";
				print '</script>';
				print "\n";
				$s .= '<a href="#" class="hideonsmartphone" onclick="CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
				$s = $form->textwithpicto($s, $langs->trans("VATIntraCheckDesc", $langs->transnoentitiesnoconv("VATIntraCheck")), 1);
			} else {
				$s .= '<a href="'.$langs->transcountry("VATIntraCheckURL", $object->country_id).'" target="_blank" rel="noopener noreferrer">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"), 'help').'</a>';
			}
		}
		print $s;
		print '</td>';
		print '</tr>';

		// VAT reverse charge by default
		if (!empty($conf->global->ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE)) {
			print '<tr><td>' . $form->editfieldkey('VATReverseChargeByDefault', 'vat_reverse_charge', '', $object, 0) . '</td><td colspan="3">';
			print '<input type="checkbox" name="vat_reverse_charge" '.($object->vat_reverse_charge == '1' ? ' checked' : '').'>';
			print '</td></tr>';
		}

		// Local Taxes
		//TODO: Place into a function to control showing by country or study better option
		if ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj == "1") {
			print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code).'</td><td>';
			print $form->selectyesno('localtax1assuj_value', (isset($conf->global->THIRDPARTY_DEFAULT_USELOCALTAX1) ? $conf->global->THIRDPARTY_DEFAULT_USELOCALTAX1 : 0), 1);
			print '</td>';
			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}
			print '<td>'.$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code).'</td><td>';
			print $form->selectyesno('localtax2assuj_value', (isset($conf->global->THIRDPARTY_DEFAULT_USELOCALTAX2) ? $conf->global->THIRDPARTY_DEFAULT_USELOCALTAX2 : 0), 1);
			print '</td></tr>';
		} elseif ($mysoc->localtax1_assuj == "1") {
			print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code).'</td><td colspan="3">';
			print $form->selectyesno('localtax1assuj_value', (isset($conf->global->THIRDPARTY_DEFAULT_USELOCALTAX1) ? $conf->global->THIRDPARTY_DEFAULT_USELOCALTAX1 : 0), 1);
			print '</td></tr>';
		} elseif ($mysoc->localtax2_assuj == "1") {
			print '<tr><td>'.$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code).'</td><td colspan="3">';
			print $form->selectyesno('localtax2assuj_value', (isset($conf->global->THIRDPARTY_DEFAULT_USELOCALTAX2) ? $conf->global->THIRDPARTY_DEFAULT_USELOCALTAX2 : 0), 1);
			print '</td></tr>';
		}

		// Type - Workforce/Staff
		print '<tr><td>'.$form->editfieldkey('ThirdPartyType', 'typent_id', '', $object, 0).'</td><td class="maxwidthonsmartphone"'.( ($conf->browser->layout == 'phone' || !empty($conf->global->SOCIETE_DISABLE_WORKFORCE)) ? ' colspan="3"' : '').'>'."\n";
		$sortparam = (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT); // NONE means we keep sort of original array, so we sort on position. ASC, means next function will sort on label.
		print $form->selectarray("typent_id", $formcompany->typent_array(0), $object->typent_id, 1, 0, 0, '', 0, 0, 0, $sortparam, '', 1);
		if ($user->admin) {
			print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		if (empty($conf->global->SOCIETE_DISABLE_WORKFORCE)) {
			print '</td>';
			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}
			print '<td>'.$form->editfieldkey('Workforce', 'effectif_id', '', $object, 0).'</td><td class="maxwidthonsmartphone"'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>';
			print $form->selectarray("effectif_id", $formcompany->effectif_array(0), $object->effectif_id, 0, 0, 0, '', 0, 0, 0, '', '', 1);
			if ($user->admin) {
				print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}
		} else {
			print '<input type="hidden" name="effectif_id" id="effectif_id" value="'.$object->effectif_id.'">';
		}
		print '</td></tr>';

		// Legal Form
		print '<tr><td>'.$form->editfieldkey('JuridicalStatus', 'forme_juridique_code', '', $object, 0).'</td>';
		print '<td colspan="3" class="maxwidthonsmartphone">';
		if ($object->country_id) {
			print $formcompany->select_juridicalstatus($object->forme_juridique_code, $object->country_code, '', 'forme_juridique_code');
		} else {
			print $countrynotdefined;
		}
		print '</td></tr>';

		// Capital
		print '<tr><td>'.$form->editfieldkey('Capital', 'capital', '', $object, 0).'</td>';
		print '<td colspan="3"><input type="text" name="capital" id="capital" class="maxwidth100" value="'.$object->capital.'"> ';
		if (isModEnabled("multicurrency")) {
			print '<span class="hideonsmartphone">'.$langs->trans("Currency".$object->multicurrency_code).'</span></td></tr>';
		} else {
			print '<span class="hideonsmartphone">'.$langs->trans("Currency".$conf->currency).'</span></td></tr>';
		}
		if (getDolGlobalInt('MAIN_MULTILANGS')) {
			print '<tr><td>'.$form->editfieldkey('DefaultLang', 'default_lang', '', $object, 0).'</td><td colspan="3" class="maxwidthonsmartphone">'."\n";
			print img_picto('', 'language', 'class="pictofixedwidth"').$formadmin->select_language(GETPOST('default_lang', 'alpha') ? GETPOST('default_lang', 'alpha') : ($object->default_lang ? $object->default_lang : ''), 'default_lang', 0, 0, 1, 0, 0, 'maxwidth200onsmartphone');
			print '</td>';
			print '</tr>';
		}

		// Incoterms
		if (isModEnabled('incoterm')) {
			print '<tr>';
			print '<td>'.$form->editfieldkey('IncotermLabel', 'incoterm_id', '', $object, 0).'</td>';
			print '<td colspan="3" class="maxwidthonsmartphone">';
			print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''));
			print '</td></tr>';
		}

		// Categories
		if (isModEnabled('categorie') && $user->hasRight('categorie', 'lire')) {
			$langs->load('categories');

			// Customer
			print '<tr class="visibleifcustomer"><td class="toptd">'.$form->editfieldkey('CustomersProspectsCategoriesShort', 'custcats', '', $object, 0).'</td><td colspan="3">';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, 'parent', null, null, 1);
			print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('custcats', $cate_arbo, GETPOST('custcats', 'array'), null, null, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
			print "</td></tr>";

			if (!empty($conf->global->THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION)) {
				print '<tr class="individualline"><td class="toptd">'.$form->editfieldkey('ContactCategoriesShort', 'contcats', '', $object, 0).'</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_CONTACT, null, 'parent', null, null, 1);
				print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('contcats', $cate_arbo, GETPOST('contcats', 'array'), null, null, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
				print "</td></tr>";
			}

			// Supplier
			if (isModEnabled("supplier_proposal") || isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) {
				print '<tr class="visibleifsupplier"><td class="toptd">'.$form->editfieldkey('SuppliersCategoriesShort', 'suppcats', '', $object, 0).'</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_SUPPLIER, null, 'parent', null, null, 1);
				print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('suppcats', $cate_arbo, GETPOST('suppcats', 'array'), null, null, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
				print "</td></tr>";
			}
		}

		// Multicurrency
		if (isModEnabled("multicurrency")) {
			print '<tr>';
			print '<td>'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
			print '<td colspan="3" class="maxwidthonsmartphone">';
			print img_picto('', 'currency', 'class="pictofixedwidth"');
			print $form->selectMultiCurrency((GETPOSTISSET('multicurrency_code') ? GETPOST('multicurrency_code') : ($object->multicurrency_code ? $object->multicurrency_code : $conf->currency)), 'multicurrency_code', 1, '', false, 'maxwidth150 widthcentpercentminusx');
			print '</td></tr>';
		}

		// Other attributes
		$parameters = array('socid'=>$socid, 'colspan' => ' colspan="3"', 'colspanvalue' => '3');
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

		// Parent company
		if (empty($conf->global->SOCIETE_DISABLE_PARENTCOMPANY)) {
			print '<tr>';
			print '<td>'.$langs->trans('ParentCompany').'</td>';
			print '<td colspan="3" class="maxwidthonsmartphone">';
			print img_picto('', 'company', 'class="paddingrightonly"');
			print $form->select_company(GETPOST('parent_company_id'), 'parent_company_id', '', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300 maxwidth500 widthcentpercentminusxx');
			print '</td></tr>';
		}

		// Assign a sale representative
		print '<tr>';
		print '<td>'.$form->editfieldkey('AllocateCommercial', 'commercial_id', '', $object, 0).'</td>';
		print '<td colspan="3" class="maxwidthonsmartphone">';
		$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', '0', 0, 0, 'AND u.statut = 1', 0, '', '', 0, 2);
		// Note: If user has no right to "see all thirdparties", we force selection of sale representative to him, so after creation he can see the record.
		$selected = (count(GETPOST('commercial', 'array')) > 0 ? GETPOST('commercial', 'array') : (GETPOST('commercial', 'int') > 0 ? array(GETPOST('commercial', 'int')) : (empty($user->rights->societe->client->voir) ? array($user->id) : array())));
		print img_picto('', 'user').$form->multiselectarray('commercial', $userlist, $selected, null, null, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
		print '</td></tr>';

		// Add logo
		print '<tr class="hideonsmartphone">';
		print '<td>'.$form->editfieldkey('Logo', 'photoinput', '', $object, 0).'</td>';
		print '<td colspan="3">';
		print '<input class="flat" type="file" name="photo" id="photoinput" />';
		print '</td>';
		print '</tr>';

		print '</table>'."\n";

		// Accountancy codes
		if (!empty($conf->global->ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY)) {
			print '<table class="border centpercent">';

			if (isModEnabled('accounting')) {
				// Accountancy_code_sell
				print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellCode").'</td>';
				print '<td>';
				$accountancy_code_sell = GETPOST('accountancy_code_sell', 'alpha');
				print $formaccounting->select_account($accountancy_code_sell, 'accountancy_code_sell', 1, null, 1, 1, '');
				print '</td></tr>';

				// Accountancy_code_buy
				print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancyBuyCode").'</td>';
				print '<td>';
				$accountancy_code_buy = GETPOST('accountancy_code_buy', 'alpha');
				print $formaccounting->select_account($accountancy_code_buy, 'accountancy_code_buy', 1, null, 1, 1, '');
				print '</td></tr>';
			} else { // For external software
				// Accountancy_code_sell
				print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellCode").'</td>';
				print '<td class="maxwidthonsmartphone"><input class="minwidth100" name="accountancy_code_sell" value="'.$object->accountancy_code_sell.'">';
				print '</td></tr>';

				// Accountancy_code_buy
				print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancyBuyCode").'</td>';
				print '<td class="maxwidthonsmartphone"><input class="minwidth100" name="accountancy_code_buy" value="'.$object->accountancy_code_buy.'">';
				print '</td></tr>';
			}

			print '</table>';
		}

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("AddThirdParty", 'Cancel', null, 0, '', $dol_openinpopup);

		print '</form>'."\n";
	} elseif ($action == 'edit') {
		//print load_fiche_titre($langs->trans("EditCompany"));

		if ($socid) {
			$res = $object->fetch_optionals();
			//if ($res < 0) { dol_print_error($db); exit; }

			$head = societe_prepare_head($object);

			// Load object modCodeTiers
			$module = (!empty($conf->global->SOCIETE_CODECLIENT_ADDON) ? $conf->global->SOCIETE_CODECLIENT_ADDON : 'mod_codeclient_leopard');
			if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
				$module = substr($module, 0, dol_strlen($module) - 4);
			}
			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}
			$modCodeClient = new $module($db);
			// We check if the prefix tag is used
			if ($modCodeClient->code_auto) {
				$prefixCustomerIsUsed = $modCodeClient->verif_prefixIsUsed();
			}
			$module = $conf->global->SOCIETE_CODECLIENT_ADDON;
			if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
				$module = substr($module, 0, dol_strlen($module) - 4);
			}
			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}
			$modCodeFournisseur = new $module($db);
			// We check if the prefix tag is used
			if ($modCodeFournisseur->code_auto) {
				$prefixSupplierIsUsed = $modCodeFournisseur->verif_prefixIsUsed();
			}

			$object->oldcopy = clone $object;

			if (GETPOSTISSET('name')) {
				// We overwrite with values if posted
				$object->name = GETPOST('name', 'alphanohtml');
				$object->name_alias = GETPOST('name_alias', 'alphanohtml');
				$object->prefix_comm = GETPOST('prefix_comm', 'alphanohtml');
				$object->client = GETPOST('client', 'int');
				$object->code_client = GETPOST('customer_code', 'alpha');
				$object->fournisseur = GETPOST('fournisseur', 'int');
				$object->code_fournisseur = GETPOST('supplier_code', 'alpha');
				$object->address = GETPOST('address', 'alphanohtml');
				$object->zip = GETPOST('zipcode', 'alphanohtml');
				$object->town = GETPOST('town', 'alphanohtml');
				$object->country_id = GETPOST('country_id') ?GETPOST('country_id', 'int') : $mysoc->country_id;
				$object->state_id = GETPOST('state_id', 'int');
				$object->parent = GETPOST('parent_company_id', 'int');

				$object->socialnetworks = array();
				if (isModEnabled('socialnetworks')) {
					foreach ($socialnetworks as $key => $value) {
						if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
							$object->socialnetworks[$key] = GETPOST($key, 'alphanohtml');
						}
					}
				}

				$object->phone					= GETPOST('phone', 'alpha');
				$object->fax					= GETPOST('fax', 'alpha');
				$object->email					= GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
				$object->no_email				= GETPOST("no_email", "int");
				$object->url					= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
				$object->capital				= GETPOST('capital', 'alphanohtml');
				$object->idprof1				= GETPOST('idprof1', 'alphanohtml');
				$object->idprof2				= GETPOST('idprof2', 'alphanohtml');
				$object->idprof3				= GETPOST('idprof3', 'alphanohtml');
				$object->idprof4				= GETPOST('idprof4', 'alphanohtml');
				$object->idprof5				= GETPOST('idprof5', 'alphanohtml');
				$object->idprof6				= GETPOST('idprof6', 'alphanohtml');
				$object->typent_id = GETPOST('typent_id', 'int');
				$object->effectif_id = GETPOST('effectif_id', 'int');
				$object->barcode				= GETPOST('barcode', 'alphanohtml');
				$object->forme_juridique_code = GETPOST('forme_juridique_code', 'int');
				$object->default_lang = GETPOST('default_lang', 'alpha');

				$object->tva_assuj				= GETPOST('assujtva_value', 'int');
				$object->vat_reverse_charge		= GETPOST('vat_reverse_charge') == 'on' ? 1 : 0;
				$object->tva_intra				= GETPOST('tva_intra', 'alphanohtml');
				$object->status =				GETPOST('status', 'int');

				// Webservices url/key
				$object->webservices_url        = GETPOST('webservices_url', 'custom', 0, FILTER_SANITIZE_URL);
				$object->webservices_key        = GETPOST('webservices_key', 'san_alpha');

				if (GETPOSTISSET('accountancy_code_sell')) {
					$accountancy_code_sell  = GETPOST('accountancy_code_sell', 'alpha');

					if (empty($accountancy_code_sell) || $accountancy_code_sell == '-1') {
						$object->accountancy_code_sell = '';
					} else {
						$object->accountancy_code_sell = $accountancy_code_sell;
					}
				}
				if (GETPOSTISSET('accountancy_code_buy')) {
					$accountancy_code_buy   = GETPOST('accountancy_code_buy', 'alpha');

					if (empty($accountancy_code_buy) || $accountancy_code_buy == '-1') {
						$object->accountancy_code_buy = '';
					} else {
						$object->accountancy_code_buy = $accountancy_code_buy;
					}
				}

				//Incoterms
				if (isModEnabled('incoterm')) {
					$object->fk_incoterms = GETPOST('incoterm_id', 'int');
					$object->location_incoterms = GETPOST('lcoation_incoterms', 'alpha');
				}

				//Local Taxes
				$object->localtax1_assuj		= GETPOST('localtax1assuj_value');
				$object->localtax2_assuj		= GETPOST('localtax2assuj_value');

				$object->localtax1_value		= GETPOST('lt1');
				$object->localtax2_value		= GETPOST('lt2');

				// We set country_id, and country_code label of the chosen country
				if ($object->country_id > 0) {
					$tmparray = getCountry($object->country_id, 'all');
					$object->country_code = $tmparray['code'];
					$object->country = $tmparray['label'];
				}

				// We set multicurrency_code if enabled
				if (isModEnabled("multicurrency")) {
					$object->multicurrency_code = GETPOST('multicurrency_code') ? GETPOST('multicurrency_code') : $object->multicurrency_code;
				}
			}

			if ($object->localtax1_assuj == 0) {
				$sub = 0;
			} else {
				$sub = 1;
			}
			if ($object->localtax2_assuj == 0) {
				$sub2 = 0;
			} else {
				$sub2 = 1;
			}

			if (!empty($conf->use_javascript_ajax)) {
				print "\n".'<script type="text/javascript">';
				print '$(document).ready(function () {
    			var val='.$sub.';
    			var val2='.$sub2.';
    			if("#localtax1assuj_value".value==undefined){
    				if(val==1){
    					$(".cblt1").show();
    				}else{
    					$(".cblt1").hide();
    				}
    			}
    			if("#localtax2assuj_value".value==undefined){
    				if(val2==1){
    					$(".cblt2").show();
    				}else{
    					$(".cblt2").hide();
    				}
    			}
    			$("#localtax1assuj_value").change(function() {
               		var value=document.getElementById("localtax1assuj_value").value;
    				if(value==1){
    					$(".cblt1").show();
    				}else{
    					$(".cblt1").hide();
    				}
    			});
    			$("#localtax2assuj_value").change(function() {
    				var value=document.getElementById("localtax2assuj_value").value;
    				if(value==1){
    					$(".cblt2").show();
    				}else{
    					$(".cblt2").hide();
    				}
    			});

				var canHaveCategoryIfNotCustomerProspectSupplier = ' . (empty($conf->global->THIRDPARTY_CAN_HAVE_CUSTOMER_CATEGORY_EVEN_IF_NOT_CUSTOMER_PROSPECT) ? '0' : '1') . ';

				init_customer_categ();
	  			$("#customerprospect").change(function() {
					init_customer_categ();
				});
       			function init_customer_categ() {
					console.log("is customer or prospect = "+jQuery("#customerprospect").val());
					if (jQuery("#customerprospect").val() == 0 && !canHaveCategoryIfNotCustomerProspectSupplier)
					{
						jQuery(".visibleifcustomer").hide();
					}
					else
					{
						jQuery(".visibleifcustomer").show();
					}
				}

				init_supplier_categ();
	  			$("#fournisseur").change(function() {
					init_supplier_categ();
				});
       			function init_supplier_categ() {
					console.log("is supplier = "+jQuery("#fournisseur").val());
					if (jQuery("#fournisseur").val() == 0)
					{
						jQuery(".visibleifsupplier").hide();
					}
					else
					{
						jQuery(".visibleifsupplier").show();
					}
				}

       			$("#selectcountry_id").change(function() {
       				document.formsoc.action.value="edit";
      				document.formsoc.submit();
        			});

                })';
				print '</script>'."\n";
			}

			print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="post" name="formsoc">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="socid" value="'.$object->id.'">';
			print '<input type="hidden" name="entity" value="'.$object->entity.'">';
			if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) {
				print '<input type="hidden" name="code_auto" value="1">';
			}


			print dol_get_fiche_head($head, 'card', $langs->trans("ThirdParty"), 0, 'company');

			print '<div class="fichecenter2">';
			print '<table class="border centpercent">';

			// Ref/ID
			if (!empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) {
				print '<tr><td class="titlefieldcreate">'.$langs->trans("ID").'</td><td colspan="3">';
				print $object->ref;
				print '</td></tr>';
			}

			// Name
			print '<tr><td class="titlefieldcreate">'.$form->editfieldkey('ThirdPartyName', 'name', '', $object, 0, 'string', '', 1).'</td>';
			print '<td colspan="3"><input type="text" class="minwidth300" maxlength="128" name="name" id="name" value="'.dol_escape_htmltag($object->name).'" autofocus="autofocus">';
			print $form->widgetForTranslation("name", $object, $permissiontoadd, 'string', 'alpahnohtml', 'minwidth300');
			print '</td></tr>';

			// Alias names (commercial, trademark or alias names)
			print '<tr id="name_alias"><td><label for="name_alias_input">'.$langs->trans('AliasNames').'</label></td>';
			print '<td colspan="3"><input type="text" class="minwidth300" name="name_alias" id="name_alias_input" value="'.dol_escape_htmltag($object->name_alias).'"></td></tr>';

			// Prefix
			if (!empty($conf->global->SOCIETE_USEPREFIX)) {  // Old not used prefix field
				print '<tr><td>'.$form->editfieldkey('Prefix', 'prefix', '', $object, 0).'</td><td colspan="3">';
				// It does not change the prefix mode using the auto numbering prefix
				if (($prefixCustomerIsUsed || $prefixSupplierIsUsed) && $object->prefix_comm) {
					print '<input type="hidden" name="prefix_comm" value="'.dol_escape_htmltag($object->prefix_comm).'">';
					print $object->prefix_comm;
				} else {
					print '<input type="text" size="5" maxlength="5" name="prefix_comm" id="prefix" value="'.dol_escape_htmltag($object->prefix_comm).'">';
				}
				print '</td>';
			}

			// Prospect/Customer
			print '<tr><td>'.$form->editfieldkey('ProspectCustomer', 'customerprospect', '', $object, 0, 'string', '', 1).'</td>';
			print '<td class="maxwidthonsmartphone">';
			print $formcompany->selectProspectCustomerType($object->client);
			print '</td>';
			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}
			print '<td>'.$form->editfieldkey('CustomerCode', 'customer_code', '', $object, 0).'</td><td>';

			print '<table class="nobordernopadding"><tr><td>';
			if ((!$object->code_client || $object->code_client == -1) && $modCodeClient->code_auto) {
				$tmpcode = $object->code_client;
				if (empty($tmpcode) && !empty($object->oldcopy->code_client)) {
					$tmpcode = $object->oldcopy->code_client; // When there is an error to update a thirdparty, the number for supplier and customer code is kept to old value.
				}
				if (empty($tmpcode) && !empty($modCodeClient->code_auto)) {
					$tmpcode = $modCodeClient->getNextValue($object, 0);
				}
				print '<input type="text" name="customer_code" id="customer_code" size="16" value="'.dol_escape_htmltag($tmpcode).'" maxlength="24">';
			} elseif ($object->codeclient_modifiable()) {
				print '<input type="text" name="customer_code" id="customer_code" size="16" value="'.dol_escape_htmltag($object->code_client).'" maxlength="24">';
			} else {
				print $object->code_client;
				print '<input type="hidden" name="customer_code" value="'.dol_escape_htmltag($object->code_client).'">';
			}
			print '</td><td>';
			$s = $modCodeClient->getToolTip($langs, $object, 0);
			print $form->textwithpicto('', $s, 1);
			print '</td></tr></table>';

			print '</td></tr>';

			// Supplier
			if (((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire)))
				|| (isModEnabled('supplier_proposal') && !empty($user->rights->supplier_proposal->lire))) {
				print '<tr>';
				print '<td>'.$form->editfieldkey('Supplier', 'fournisseur', '', $object, 0, 'string', '', 1).'</td>';
				print '<td class="maxwidthonsmartphone">';
				print $form->selectyesno("fournisseur", $object->fournisseur, 1, false, 0, 1);
				print '</td>';
				if ($conf->browser->layout == 'phone') {
					print '</tr><tr>';
				}
				print '<td>';
				if ((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire))) {
					print $form->editfieldkey('SupplierCode', 'supplier_code', '', $object, 0);
				}
				print '</td>';
				print '<td>';
				print '<table class="nobordernopadding"><tr><td>';
				if ((!$object->code_fournisseur || $object->code_fournisseur == -1) && $modCodeFournisseur->code_auto) {
					$tmpcode = $object->code_fournisseur;
					if (empty($tmpcode) && !empty($object->oldcopy->code_fournisseur)) {
						$tmpcode = $object->oldcopy->code_fournisseur; // When there is an error to update a thirdparty, the number for supplier and customer code is kept to old value.
					}
					if (empty($tmpcode) && !empty($modCodeFournisseur->code_auto)) {
						$tmpcode = $modCodeFournisseur->getNextValue($object, 1);
					}
					print '<input type="text" name="supplier_code" id="supplier_code" size="16" value="'.dol_escape_htmltag($tmpcode).'" maxlength="24">';
				} elseif ($object->codefournisseur_modifiable()) {
					print '<input type="text" name="supplier_code" id="supplier_code" size="16" value="'.dol_escape_htmltag($object->code_fournisseur).'" maxlength="24">';
				} else {
					print $object->code_fournisseur;
					print '<input type="hidden" name="supplier_code" value="'.$object->code_fournisseur.'">';
				}
				print '</td><td>';
				$s = $modCodeFournisseur->getToolTip($langs, $object, 1);
				print $form->textwithpicto('', $s, 1);
				print '</td></tr></table>';
				print '</td></tr>';
			}

			// Barcode
			if (isModEnabled('barcode')) {
				print '<tr><td class="tdtop">'.$form->editfieldkey('Gencod', 'barcode', '', $object, 0).'</td>';
				print '<td colspan="3">';
				print img_picto('', 'barcode');
				print '<input type="text" name="barcode" id="barcode" value="'.dol_escape_htmltag($object->barcode).'">';
				print '</td></tr>';
			}

			// Status
			print '<tr><td>'.$form->editfieldkey('Status', 'status', '', $object, 0).'</td><td colspan="3">';
			print $form->selectarray('status', array('0'=>$langs->trans('ActivityCeased'), '1'=>$langs->trans('InActivity')), $object->status, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
			print '</td></tr>';

			// Address
			print '<tr><td class="tdtop">'.$form->editfieldkey('Address', 'address', '', $object, 0).'</td>';
			print '<td colspan="3"><textarea name="address" id="address" class="quatrevingtpercent" rows="3" wrap="soft">';
			print dol_escape_htmltag($object->address, 0, 1);
			print '</textarea>';
			print $form->widgetForTranslation("address", $object, $permissiontoadd, 'textarea', 'alphanohtml', 'quatrevingtpercent');
			print '</td></tr>';

			// Zip / Town
			print '<tr><td>'.$form->editfieldkey('Zip', 'zipcode', '', $object, 0).'</td><td'.($conf->browser->layout == 'phone' ? ' colspan="3"': '').'>';
			print $formcompany->select_ziptown($object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 0, 0, '', 'maxwidth100');
			print '</td>';
			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}
			print '<td>'.$form->editfieldkey('Town', 'town', '', $object, 0).'</td><td'.($conf->browser->layout == 'phone' ? ' colspan="3"': '').'>';
			print $formcompany->select_ziptown($object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));
			print $form->widgetForTranslation("town", $object, $permissiontoadd, 'string', 'alphanohtml', 'maxwidth100 quatrevingtpercent');
			print '</td></tr>';

			// Country
			print '<tr><td>'.$form->editfieldkey('Country', 'selectcounty_id', '', $object, 0).'</td><td colspan="3">';
			print img_picto('', 'globe-americas', 'class="paddingrightonly"');
			print $form->select_country((GETPOSTISSET('country_id') ? GETPOST('country_id') : $object->country_id), 'country_id', '', 0, 'minwidth300 maxwidth500 widthcentpercentminusx');
			if ($user->admin) {
				print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}
			print '</td></tr>';

			// State
			if (empty($conf->global->SOCIETE_DISABLE_STATE)) {
				if (!empty($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT) && ($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 1 || $conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 2)) {
					print '<tr><td>'.$form->editfieldkey('Region-State', 'state_id', '', $object, 0).'</td><td colspan="3">';
				} else {
					print '<tr><td>'.$form->editfieldkey('State', 'state_id', '', $object, 0).'</td><td colspan="3">';
				}

				print img_picto('', 'state', 'class="pictofixedwidth"');
				print $formcompany->select_state($object->state_id, $object->country_code);
				print '</td></tr>';
			}

			// Phone / Fax
			print '<tr><td>'.$form->editfieldkey('Phone', 'phone', GETPOST('phone', 'alpha'), $object, 0).'</td>';
			print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"': '').'>'.img_picto('', 'object_phoning', 'class="pictofixedwidth"').' <input type="text" name="phone" id="phone" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('phone') ? GETPOST('phone', 'alpha') : $object->phone).'"></td>';
			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}
			print '<td>'.$form->editfieldkey('Fax', 'fax', GETPOST('fax', 'alpha'), $object, 0).'</td>';
			print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"': '').'>'.img_picto('', 'object_phoning_fax', 'class="pictofixedwidth"').' <input type="text" name="fax" id="fax" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('fax') ? GETPOST('fax', 'alpha') : $object->fax).'"></td>';
			print '</tr>';

			// Web
			print '<tr><td>'.$form->editfieldkey('Web', 'url', GETPOST('url', 'alpha'), $object, 0).'</td>';
			print '<td colspan="3">'.img_picto('', 'globe', 'class="pictofixedwidth"').' <input type="text" name="url" id="url" class="maxwidth200onsmartphone maxwidth300 widthcentpercentminusx " value="'.(GETPOSTISSET('url') ?GETPOST('url', 'alpha') : $object->url).'"></td></tr>';

			// EMail
			print '<tr><td>'.$form->editfieldkey('EMail', 'email', GETPOST('email', 'alpha'), $object, 0, 'string', '', (!empty($conf->global->SOCIETE_EMAIL_MANDATORY))).'</td>';
			print '<td colspan="3">';
			print img_picto('', 'object_email', 'class="pictofixedwidth"');
			print '<input type="text" name="email" id="email" class="maxwidth500 widthcentpercentminusx" value="'.(GETPOSTISSET('email') ?GETPOST('email', 'alpha') : $object->email).'">';
			print '</td></tr>';

			// Unsubscribe
			if (isModEnabled('mailing')) {
				if ($conf->use_javascript_ajax && isset($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS) && $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2) {
					print "\n".'<script type="text/javascript">'."\n";

					print '
					jQuery(document).ready(function () {
						function init_check_no_email(input) {
							if (input.val()!="") {
								$(".noemail").addClass("fieldrequired");
							} else {
								$(".noemail").removeClass("fieldrequired");
							}
						}
						$("#email").keyup(function() {
							init_check_no_email($(this));
						});
						init_check_no_email($("#email"));
					})'."\n";
					print '</script>'."\n";
				}
				if (!GETPOSTISSET("no_email") && !empty($object->email)) {
					$result = $object->getNoEmail();
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
				print '<tr>';
				print '<td class="noemail"><label for="no_email">'.$langs->trans("No_Email").'</label></td>';
				print '<td>';
				$useempty = (isset($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS) && ($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2));
				print $form->selectyesno('no_email', (GETPOSTISSET("no_email") ? GETPOST("no_email", 'int') : $object->no_email), 1, false, $useempty);
				print '</td>';
				print '</tr>';
			}

			// Social network
			if (isModEnabled('socialnetworks')) {
				$object->showSocialNetwork($socialnetworks, ($conf->browser->layout == 'phone' ? 2 : 4));
			}

			// Prof ids
			$i = 1;
			$j = 0;
			$NBCOLS = ($conf->browser->layout == 'phone' ? 1 : 2);
			while ($i <= 6) {
				$idprof = $langs->transcountry('ProfId'.$i, $object->country_code);
				if ($idprof != '-') {
					$key = 'idprof'.$i;

					if (($j % $NBCOLS) == 0) {
						print '<tr>';
					}

					$idprof_mandatory = 'SOCIETE_IDPROF'.($i).'_MANDATORY';
					print '<td>'.$form->editfieldkey($idprof, $key, '', $object, 0, 'string', '', !(empty($conf->global->$idprof_mandatory) || !$object->isACompany())).'</td><td>';
					print $formcompany->get_input_id_prof($i, $key, $object->$key, $object->country_code);
					print '</td>';
					if (($j % $NBCOLS) == ($NBCOLS - 1)) {
						print '</tr>';
					}
					$j++;
				}
				$i++;
			}
			if ($NBCOLS > 0 && $j % 2 == 1) {
				print '<td colspan="2"></td></tr>';
			}

			// VAT is used
			print '<tr><td>'.$form->editfieldkey('VATIsUsed', 'assujtva_value', '', $object, 0).'</td><td colspan="3">';
			print $form->selectyesno('assujtva_value', $object->tva_assuj, 1);
			print '</td></tr>';

			// Local Taxes
			//TODO: Place into a function to control showing by country or study better option
			if ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj == "1") {
				print '<tr><td>'.$form->editfieldkey($langs->transcountry("LocalTax1IsUsed", $mysoc->country_code), 'localtax1assuj_value', '', $object, 0).'</td><td>';
				print $form->selectyesno('localtax1assuj_value', $object->localtax1_assuj, 1);
				if (!isOnlyOneLocalTax(1)) {
					print '<span class="cblt1">     '.$langs->transcountry("Type", $mysoc->country_code).': ';
					$formcompany->select_localtax(1, $object->localtax1_value, "lt1");
					print '</span>';
				}
				print '</td>';
				print '</tr><tr>';
				print '<td>'.$form->editfieldkey($langs->transcountry("LocalTax2IsUsed", $mysoc->country_code), 'localtax2assuj_value', '', $object, 0).'</td><td>';
				print $form->selectyesno('localtax2assuj_value', $object->localtax2_assuj, 1);
				if (!isOnlyOneLocalTax(2)) {
					print '<span class="cblt2">     '.$langs->transcountry("Type", $mysoc->country_code).': ';
					$formcompany->select_localtax(2, $object->localtax2_value, "lt2");
					print '</span>';
				}
				print '</td></tr>';
			} elseif ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj != "1") {
				print '<tr><td>'.$form->editfieldkey($langs->transcountry("LocalTax1IsUsed", $mysoc->country_code), 'localtax1assuj_value', '', $object, 0).'</td><td colspan="3">';
				print $form->selectyesno('localtax1assuj_value', $object->localtax1_assuj, 1);
				if (!isOnlyOneLocalTax(1)) {
					print '<span class="cblt1">     '.$langs->transcountry("Type", $mysoc->country_code).': ';
					$formcompany->select_localtax(1, $object->localtax1_value, "lt1");
					print '</span>';
				}
				print '</td></tr>';
			} elseif ($mysoc->localtax2_assuj == "1" && $mysoc->localtax1_assuj != "1") {
				print '<tr><td>'.$form->editfieldkey($langs->transcountry("LocalTax2IsUsed", $mysoc->country_code), 'localtax2assuj_value', '', $object, 0).'</td><td colspan="3">';
				print $form->selectyesno('localtax2assuj_value', $object->localtax2_assuj, 1);
				if (!isOnlyOneLocalTax(2)) {
					print '<span class="cblt2">     '.$langs->transcountry("Type", $mysoc->country_code).': ';
					$formcompany->select_localtax(2, $object->localtax2_value, "lt2");
					print '</span>';
				}
				print '</td></tr>';
			}

			// VAT reverse charge by default
			if (!empty($conf->global->ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE)) {
				print '<tr><td>' . $form->editfieldkey('VATReverseChargeByDefault', 'vat_reverse_charge', '', $object, 0) . '</td><td colspan="3">';
				print '<input type="checkbox" name="vat_reverse_charge" '.($object->vat_reverse_charge == '1' ? ' checked' : '').'>';
				print '</td></tr>';
			}

			// VAT Code
			print '<tr><td>'.$form->editfieldkey('VATIntra', 'intra_vat', '', $object, 0).'</td>';
			print '<td colspan="3">';
			$s = '<input type="text" class="flat maxwidthonsmartphone" name="tva_intra" id="intra_vat" maxlength="20" value="'.$object->tva_intra.'">';

			if (empty($conf->global->MAIN_DISABLEVATCHECK) && isInEEC($object)) {
				$s .= ' &nbsp; ';

				if ($conf->use_javascript_ajax) {
					$widthpopup = 600;
					if (!empty($conf->dol_use_jmobile)) {
						$widthpopup = 350;
					}
					$heightpopup = 400;
					print "\n";
					print '<script type="text/javascript">';
					print "function CheckVAT(a) {\n";
					if ($mysoc->country_code == 'GR' && $object->country_code == 'GR' && !empty($u)) {
						print "GRVAT(a,'{$u}','{$p}','{$myafm}');\n";
					} else {
						print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a, '".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."', ".$widthpopup.", ".$heightpopup.");\n";
					}
					print "}\n";
					print '</script>';
					print "\n";
					$s .= '<a href="#" class="hideonsmartphone" onclick="CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
					$s = $form->textwithpicto($s, $langs->trans("VATIntraCheckDesc", $langs->transnoentitiesnoconv("VATIntraCheck")), 1);
				} else {
					$s .= '<a href="'.$langs->transcountry("VATIntraCheckURL", $object->country_id).'" class="hideonsmartphone" target="_blank" rel="noopener noreferrer">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"), 'help').'</a>';
				}
			}
			print $s;
			print '</td>';
			print '</tr>';

			// Type - Workforce/Staff
			print '<tr><td>'.$form->editfieldkey('ThirdPartyType', 'typent_id', '', $object, 0).'</td><td class="maxwidthonsmartphone"'.( ($conf->browser->layout == 'phone' || !empty($conf->global->SOCIETE_DISABLE_WORKFORCE)) ? ' colspan="3"' : '').'>';
			print $form->selectarray("typent_id", $formcompany->typent_array(0), $object->typent_id, 1, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), '', 1);
			if ($user->admin) {
				print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}
			if (empty($conf->global->SOCIETE_DISABLE_WORKFORCE)) {
				print '</td>';
				if ($conf->browser->layout == 'phone') {
					print '</tr><tr>';
				}
				print '<td>'.$form->editfieldkey('Workforce', 'effectif_id', '', $object, 0).'</td><td class="maxwidthonsmartphone">';
				print $form->selectarray("effectif_id", $formcompany->effectif_array(0), $object->effectif_id, 0, 0, 0, '', 0, 0, 0, '', '', 1);
				if ($user->admin) {
					print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
				}
			} else {
				print '<input type="hidden" name="effectif_id" id="effectif_id" value="'.$object->effectif_id.'">';
			}
			print '</td></tr>';

			// Juridical type
			print '<tr><td>'.$form->editfieldkey('JuridicalStatus', 'forme_juridique_code', '', $object, 0).'</td><td class="maxwidthonsmartphone" colspan="3">';
			print $formcompany->select_juridicalstatus($object->forme_juridique_code, $object->country_code, '', 'forme_juridique_code');
			print '</td></tr>';

			// Capital
			print '<tr><td>'.$form->editfieldkey('Capital', 'capital', '', $object, 0).'</td>';
			print '<td colspan="3"><input type="text" name="capital" id="capital" size="10" value="';
			print $object->capital != '' ? dol_escape_htmltag(price($object->capital)) : '';
			if (isModEnabled("multicurrency")) {
				print '"> <span class="hideonsmartphone">'.$langs->trans("Currency".$object->multicurrency_code).'</span></td></tr>';
			} else {
				print '"> <span class="hideonsmartphone">'.$langs->trans("Currency".$conf->currency).'</span></td></tr>';
			}

			// Default language
			if (getDolGlobalInt('MAIN_MULTILANGS')) {
				print '<tr><td>'.$form->editfieldkey('DefaultLang', 'default_lang', '', $object, 0).'</td><td colspan="3">'."\n";
				print img_picto('', 'language', 'class="pictofixedwidth"').$formadmin->select_language($object->default_lang, 'default_lang', 0, null, '1', 0, 0, 'maxwidth300 widthcentpercentminusx');
				print '</td>';
				print '</tr>';
			}

			// Incoterms
			if (isModEnabled('incoterm')) {
				print '<tr>';
					print '<td>'.$form->editfieldkey('IncotermLabel', 'incoterm_id', '', $object, 0).'</td>';
				print '<td colspan="3" class="maxwidthonsmartphone">';
				print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''));
				print '</td></tr>';
			}

			// Categories
			if (isModEnabled('categorie') && $user->hasRight('categorie', 'lire')) {
				// Customer
				print '<tr class="visibleifcustomer"><td>'.$form->editfieldkey('CustomersCategoriesShort', 'custcats', '', $object, 0).'</td>';
				print '<td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, null, null, null, 1);
				$c = new Categorie($db);
				$cats = $c->containing($object->id, Categorie::TYPE_CUSTOMER);
				$arrayselected = array();
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
				print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('custcats', $cate_arbo, $arrayselected, 0, 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
				print "</td></tr>";

				// Supplier
				if ((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire))) {
					print '<tr class="visibleifsupplier"><td>'.$form->editfieldkey('SuppliersCategoriesShort', 'suppcats', '', $object, 0).'</td>';
					print '<td colspan="3">';
					$cate_arbo = $form->select_all_categories(Categorie::TYPE_SUPPLIER, null, null, null, null, 1);
					$c = new Categorie($db);
					$cats = $c->containing($object->id, Categorie::TYPE_SUPPLIER);
					$arrayselected = array();
					foreach ($cats as $cat) {
						$arrayselected[] = $cat->id;
					}
					print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('suppcats', $cate_arbo, $arrayselected, 0, 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
					print "</td></tr>";
				}
			}

			// Multicurrency
			if (isModEnabled("multicurrency")) {
				print '<tr>';
				print '<td>'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
				print '<td colspan="3" class="maxwidthonsmartphone">';
				print img_picto('', 'currency', 'class="pictofixedwidth"');
				print $form->selectMultiCurrency((GETPOSTISSET('multicurrency_code') ? GETPOST('multicurrency_code') : ($object->multicurrency_code ? $object->multicurrency_code : $conf->currency)), 'multicurrency_code', 1, '', false, 'maxwidth150 widthcentpercentminusx');
				print '</td></tr>';
			}

			// Other attributes
			$parameters = array('socid'=>$socid, 'colspan' => ' colspan="3"', 'colspanvalue' => '3');
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

			// Parent company
			if (empty($conf->global->SOCIETE_DISABLE_PARENTCOMPANY)) {
				print '<tr>';
				print '<td>'.$langs->trans('ParentCompany').'</td>';
				print '<td colspan="3" class="maxwidthonsmartphone">';
				print img_picto('', 'company', 'class="pictofixedwidth"');
				print $form->select_company(GETPOST('parent_company_id') ? GETPOST('parent_company_id') : $object->parent, 'parent_company_id', '', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300 maxwidth500 widthcentpercentminusxx');
				print '</td></tr>';
			}

			// Webservices url/key
			if (!empty($conf->syncsupplierwebservices->enabled)) {
				print '<tr><td>'.$form->editfieldkey('WebServiceURL', 'webservices_url', '', $object, 0).'</td>';
				print '<td><input type="text" name="webservices_url" id="webservices_url" size="32" value="'.$object->webservices_url.'"></td>';
				print '<td>'.$form->editfieldkey('WebServiceKey', 'webservices_key', '', $object, 0).'</td>';
				print '<td><input type="text" name="webservices_key" id="webservices_key" size="32" value="'.$object->webservices_key.'"></td></tr>';
			}

			// Logo
			print '<tr class="hideonsmartphone">';
			print '<td>'.$form->editfieldkey('Logo', 'photoinput', '', $object, 0).'</td>';
			print '<td colspan="3">';
			if ($object->logo) {
				print $form->showphoto('societe', $object);
			}
			$caneditfield = 1;
			if ($caneditfield) {
				if ($object->logo) {
					print "<br>\n";
				}
				print '<table class="nobordernopadding">';
				if ($object->logo) {
					print '<tr><td><input type="checkbox" class="flat photodelete" name="deletephoto" id="photodelete"> <label for="photodelete">'.$langs->trans("Delete").'</photo><br><br></td></tr>';
				}
				//print '<tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
				print '<tr><td>';
				$maxfilesizearray = getMaxFileSizeArray();
				$maxmin = $maxfilesizearray['maxmin'];
				if ($maxmin > 0) {
					print '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
				}
				print '<input type="file" class="flat" name="photo" id="photoinput">';
				print '</td></tr>';
				print '</table>';
			}
			print '</td>';
			print '</tr>';

			// Assign sale representative
			print '<tr>';
			print '<td>'.$form->editfieldkey('AllocateCommercial', 'commercial_id', '', $object, 0).'</td>';
			print '<td colspan="3" class="maxwidthonsmartphone">';
			$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
			$arrayselected = GETPOST('commercial', 'array');
			if (empty($arrayselected)) {
				$arrayselected = $object->getSalesRepresentatives($user, 1);
			}
			print img_picto('', 'user', 'class="pictofixedwidth"').$form->multiselectarray('commercial', $userlist, $arrayselected, 0, 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0, '', '', '', 1);
			print '</td></tr>';

			print '</table>';

			if (!empty($conf->global->ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY)) {
				print '<br>';
				print '<table class="border centpercent">';

				if (isModEnabled('accounting')) {
					// Accountancy_code_sell
					print '<tr><td class="titlefield">'.$langs->trans("ProductAccountancySellCode").'</td>';
					print '<td>';
					print $formaccounting->select_account($object->accountancy_code_sell, 'accountancy_code_sell', 1, '', 1, 1);
					print '</td></tr>';

					// Accountancy_code_buy
					print '<tr><td>'.$langs->trans("ProductAccountancyBuyCode").'</td>';
					print '<td>';
					print $formaccounting->select_account($object->accountancy_code_buy, 'accountancy_code_buy', 1, '', 1, 1);
					print '</td></tr>';
				} else { // For external software
					// Accountancy_code_sell
					print '<tr><td class="titlefield">'.$langs->trans("ProductAccountancySellCode").'</td>';
					print '<td><input name="accountancy_code_sell" class="maxwidth200" value="'.$object->accountancy_code_sell.'">';
					print '</td></tr>';

					// Accountancy_code_buy
					print '<tr><td>'.$langs->trans("ProductAccountancyBuyCode").'</td>';
					print '<td><input name="accountancy_code_buy" class="maxwidth200" value="'.$object->accountancy_code_buy.'">';
					print '</td></tr>';
				}
				print '</table>';
			}

			print '</div>';

			print dol_get_fiche_end();

			print $form->buttonsSaveCancel();

			print '</form>';
		}
	} else {
		/*
		 * View
		 */

		if (!empty($object->id)) {
			$res = $object->fetch_optionals();
		}
		//if ($res < 0) { dol_print_error($db); exit; }


		$head = societe_prepare_head($object);

		print dol_get_fiche_head($head, 'card', $langs->trans("ThirdParty"), -1, 'company');

		$formconfirm = '';

		// Confirm delete third party
		if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$object->id, $langs->trans("DeleteACompany"), $langs->trans("ConfirmDeleteCompany"), "confirm_delete", '', 0, "action-delete");
		}

		if ($action == 'merge') {
			$formquestion = array(
				array(
					'name' => 'soc_origin',
					'label' => $langs->trans('MergeOriginThirdparty'),
					'type' => 'other',
					'value' => $form->select_company('', 'soc_origin', '', 'SelectThirdParty', 0, 0, array(), 0, 'minwidth200', '', '', 1, null, false, array($object->id))
				)
			);

			$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$object->id, $langs->trans("MergeThirdparties"), $langs->trans("ConfirmMergeThirdparties"), "confirm_merge", $formquestion, 'no', 1, 250);
		}

		// Call Hook formConfirm
		$parameters = array('formConfirm' => $formconfirm);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$formconfirm .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$formconfirm = $hookmanager->resPrint;
		}

		// Print form confirm
		print $formconfirm;

		dol_htmloutput_mesg(is_numeric($error) ? '' : $error, $errors, 'error');

		$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');


		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield centpercent">';

		// Type Prospect/Customer/Supplier
		print '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
		print $object->getTypeUrl(1);
		print '</td></tr>';

		// Prefix
		if (!empty($conf->global->SOCIETE_USEPREFIX)) {  // Old not used prefix field
			print '<tr><td>'.$langs->trans('Prefix').'</td><td>'.dol_escape_htmltag($object->prefix_comm).'</td>';
			print '</tr>';
		}

		// Customer code
		if ($object->client) {
			print '<tr><td>';
			print $langs->trans('CustomerCode');
			print '</td>';
			print '<td>';
			print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_client));
			$tmpcheck = $object->check_codeclient();
			if ($tmpcheck != 0 && $tmpcheck != -5) {
				print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
			}
			print '</td>';
			print '</tr>';
		}

		// Supplier code
		if (((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire))) && $object->fournisseur) {
			print '<tr><td>';
			print $langs->trans('SupplierCode').'</td><td>';
			print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_fournisseur));
			$tmpcheck = $object->check_codefournisseur();
			if ($tmpcheck != 0 && $tmpcheck != -5) {
				print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
			}
			print '</td>';
			print '</tr>';
		}

		// Barcode
		if (isModEnabled('barcode')) {
			print '<tr><td>';
			print $langs->trans('Gencod').'</td><td>'.showValueWithClipboardCPButton(dol_escape_htmltag($object->barcode));
			print '</td>';
			print '</tr>';
		}

		// Prof ids
		$i = 1; $j = 0;
		while ($i <= 6) {
			$idprof = $langs->transcountry('ProfId'.$i, $object->country_code);
			if ($idprof != '-') {
				//if (($j % 2) == 0) print '<tr>';
				print '<tr>';
				print '<td>'.$idprof.'</td><td>';
				$key = 'idprof'.$i;
				print dol_print_profids($object->$key, 'ProfId'.$i, $object->country_code, 1);
				if ($object->$key) {
					if ($object->id_prof_check($i, $object) > 0) {
						if (!empty($object->id_prof_url($i, $object))) {
							print ' &nbsp; '.$object->id_prof_url($i, $object);
						}
					} else {
						print ' <span class="error">('.$langs->trans("ErrorWrongValue").')</span>';
					}
				}
				print '</td>';
				//if (($j % 2) == 1) print '</tr>';
				print '</tr>';
				$j++;
			}
			$i++;
		}
		//if ($j % 2 == 1)  print '<td colspan="2"></td></tr>';


		// This fields are used to know VAT to include in an invoice when the thirdparty is making a sale, so when it is a supplier.
		// We don't need them into customer profile.
		// Except for spain and localtax where localtax depends on buyer and not seller

		if ($object->fournisseur) {
			// VAT is used
			print '<tr><td>';
			print $form->textwithpicto($langs->trans('VATIsUsed'), $langs->trans('VATIsUsedWhenSelling'));
			print '</td><td>';
			print yn($object->tva_assuj);
			print '</td>';
			print '</tr>';

			if (!empty($conf->global->ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE)) {
				// VAT reverse charge by default
				print '<tr><td>';
				print $form->textwithpicto($langs->trans('VATReverseChargeByDefault'), $langs->trans('VATReverseChargeByDefaultDesc'));
				print '</td><td>';
				print '<input type="checkbox" name="vat_reverse_charge" ' . ($object->vat_reverse_charge == '1' ? ' checked' : '') . ' disabled>';
				print '</td>';
				print '</tr>';
			}
		}

		// Local Taxes
		if ($object->fournisseur || $mysoc->country_code == 'ES') {
			if ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj == "1") {
				print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code).'</td><td>';
				print yn($object->localtax1_assuj);
				print '</td></tr><tr><td>'.$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code).'</td><td>';
				print yn($object->localtax2_assuj);
				print '</td></tr>';

				if ($object->localtax1_assuj == "1" && (!isOnlyOneLocalTax(1))) {
					print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
					print '<input type="hidden" name="action" value="set_localtax1">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<tr><td>'.$langs->transcountry("Localtax1", $mysoc->country_code).' <a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editRE&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</td>';
					if ($action == 'editRE') {
						print '<td class="left">';
						$formcompany->select_localtax(1, $object->localtax1_value, "lt1");
						print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
					} else {
						print '<td>'.$object->localtax1_value.'</td>';
					}
					print '</tr></form>';
				}
				if ($object->localtax2_assuj == "1" && (!isOnlyOneLocalTax(2))) {
					print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
					print '<input type="hidden" name="action" value="set_localtax2">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<tr><td>'.$langs->transcountry("Localtax2", $mysoc->country_code).'<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editIRPF&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</td>';
					if ($action == 'editIRPF') {
						print '<td class="left">';
						$formcompany->select_localtax(2, $object->localtax2_value, "lt2");
						print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
					} else {
						print '<td>'.$object->localtax2_value.'</td>';
					}
					print '</tr></form>';
				}
			} elseif ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj != "1") {
				print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code).'</td><td>';
				print yn($object->localtax1_assuj);
				print '</td></tr>';
				if ($object->localtax1_assuj == "1" && (!isOnlyOneLocalTax(1))) {
					print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
					print '<input type="hidden" name="action" value="set_localtax1">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<tr><td> '.$langs->transcountry("Localtax1", $mysoc->country_code).'<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editRE&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</td>';
					if ($action == 'editRE') {
						print '<td class="left">';
						$formcompany->select_localtax(1, $object->localtax1_value, "lt1");
						print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
					} else {
						print '<td>'.$object->localtax1_value.'</td>';
					}
					print '</tr></form>';
				}
			} elseif ($mysoc->localtax2_assuj == "1" && $mysoc->localtax1_assuj != "1") {
				print '<tr><td>'.$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code).'</td><td>';
				print yn($object->localtax2_assuj);
				print '</td></tr>';
				if ($object->localtax2_assuj == "1" && (!isOnlyOneLocalTax(2))) {
					print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
					print '<input type="hidden" name="action" value="set_localtax2">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<tr><td> '.$langs->transcountry("Localtax2", $mysoc->country_code).' <a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editIRPF&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</td>';
					if ($action == 'editIRPF') {
						print '<td class="left">';
						$formcompany->select_localtax(2, $object->localtax2_value, "lt2");
						print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
					} else {
						print '<td>'.$object->localtax2_value.'</td>';
					}
					print '</tr></form>';
				}
			}
		}

		// Sale tax code (VAT code)
		print '<tr>';
		print '<td class="nowrap">'.$langs->trans('VATIntra').'</td><td>';
		if ($object->tva_intra) {
			$s = '';
			$s .= dol_print_profids($object->tva_intra, 'VAT', $object->country_code, 1);
			$s .= '<input type="hidden" id="tva_intra" name="tva_intra" maxlength="20" value="'.$object->tva_intra.'">';

			if (empty($conf->global->MAIN_DISABLEVATCHECK) && isInEEC($object)) {
				$s .= ' &nbsp; ';

				if ($conf->use_javascript_ajax) {
					$widthpopup = 600;
					if (!empty($conf->dol_use_jmobile)) {
						$widthpopup = 350;
					}
					$heightpopup = 400;
					print "\n";
					print '<script type="text/javascript">';
					print "function CheckVAT(a) {\n";
					if ($mysoc->country_code == 'GR' && $object->country_code == 'GR' && !empty($u)) {
						print "GRVAT(a,'{$u}','{$p}','{$myafm}');\n";
					} else {
						print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a, '".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."', ".$widthpopup.", ".$heightpopup.");\n";
					}
					print "}\n";
					print '</script>';
					print "\n";
					$s .= '<a href="#" class="hideonsmartphone" onclick="CheckVAT( $(\'#tva_intra\').val() );">'.$langs->trans("VATIntraCheck").'</a>';
					$s = $form->textwithpicto($s, $langs->trans("VATIntraCheckDesc", $langs->transnoentitiesnoconv("VATIntraCheck")), 1);
				} else {
					$s .= '<a href="'.$langs->transcountry("VATIntraCheckURL", $object->country_id).'" class="hideonsmartphone" target="_blank" rel="noopener noreferrer">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"), 'help').'</a>';
				}
			}
			print $s;
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';

		// Warehouse
		if (isModEnabled('stock') && !empty($conf->global->SOCIETE_ASK_FOR_WAREHOUSE)) {
			$langs->load('stocks');
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			print '<tr class="nowrap">';
			print '<td>';
			print $form->editfieldkey("Warehouse", 'warehouse', '', $object, $user->rights->societe->creer);
			print '</td><td>';
			if ($action == 'editwarehouse') {
				$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_warehouse, 'fk_warehouse', 1);
			} else {
				if ($object->fk_warehouse > 0) {
					print img_picto('', 'stock', 'class="paddingrightonly"');
				}
				$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_warehouse, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		print '</table>';
		print '</div>';

		print '<div class="fichehalfright">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield centpercent">';

		// Tags / categories
		if (isModEnabled('categorie') && $user->hasRight('categorie', 'lire')) {
			// Customer
			if ($object->prospect || $object->client || !empty($conf->global->THIRDPARTY_CAN_HAVE_CUSTOMER_CATEGORY_EVEN_IF_NOT_CUSTOMER_PROSPECT)) {
				print '<tr><td>'.$langs->trans("CustomersCategoriesShort").'</td>';
				print '<td>';
				print $form->showCategories($object->id, Categorie::TYPE_CUSTOMER, 1);
				print "</td></tr>";
			}

			// Supplier
			if (((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire))) && $object->fournisseur) {
				print '<tr><td>'.$langs->trans("SuppliersCategoriesShort").'</td>';
				print '<td>';
				print $form->showCategories($object->id, Categorie::TYPE_SUPPLIER, 1);
				print "</td></tr>";
			}
		}


		// Third-Party Type
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans('ThirdPartyType').'</td>';
		if ($action != 'editthirdpartytype' && $user->hasRight('societe', 'creer')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editthirdpartytype&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		$html_name = ($action == 'editthirdpartytype') ? 'typent_id' : 'none';
		$formcompany->formThirdpartyType($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->typent_id, $html_name, '');
		print '</td></tr>';

		// Workforce/Staff
		if (empty($conf->global->SOCIETE_DISABLE_WORKFORCE)) {
			print '<tr><td>'.$langs->trans("Workforce").'</td><td>'.$object->effectif.'</td></tr>';
		}

		// Legal
		print '<tr><td class="titlefield">'.$langs->trans('JuridicalStatus').'</td><td>'.$object->forme_juridique.'</td></tr>';

		// Capital
		print '<tr><td>'.$langs->trans('Capital').'</td><td>';
		if ($object->capital) {
			if (isModEnabled("multicurrency") && !empty($object->multicurrency_code)) {
				print price($object->capital, '', $langs, 0, -1, -1, $object->multicurrency_code);
			} else {
				print price($object->capital, '', $langs, 0, -1, -1, $conf->currency);
			}
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';

		// Unsubscribe opt-out
		if (isModEnabled('mailing')) {
			$result = $object->getNoEmail();
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
			print '<tr><td>'.$langs->trans("No_Email").'</td><td>';
			if ($object->email) {
				print yn($object->no_email);
			} else {
				print '<span class="opacitymedium">'.$langs->trans("EMailNotDefined").'</span>';
			}

			$langs->load("mails");
			print ' &nbsp; <span class="badge badge-secondary" title="'.dol_escape_htmltag($langs->trans("NbOfEMailingsSend")).'">'.$object->getNbOfEMailings().'</span>';

			print '</td></tr>';
		}

		// Default language
		if (getDolGlobalInt('MAIN_MULTILANGS')) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			print '<tr><td>'.$langs->trans("DefaultLang").'</td><td>';
			//$s=picto_from_langcode($object->default_lang);
			//print ($s?$s.' ':'');
			$langs->load("languages");
			$labellang = ($object->default_lang ? $langs->trans('Language_'.$object->default_lang) : '');
			print picto_from_langcode($object->default_lang, 'class="paddingrightonly saturatemedium opacitylow"');
			print $labellang;
			print '</td></tr>';
		}

		// Incoterms
		if (isModEnabled('incoterm')) {
			print '<tr><td>';
			print '<table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans('IncotermLabel').'</td>';
			if ($action != 'editincoterm' && $user->hasRight('societe', 'creer')) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=editincoterm&token='.newToken().'">'.img_edit('', 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($action != 'editincoterm') {
				print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
			} else {
				print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?socid='.$object->id);
			}
			print '</td></tr>';
		}

		// Multicurrency
		if (isModEnabled("multicurrency")) {
			print '<tr>';
			print '<td>'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
			print '<td>';
			print !empty($object->multicurrency_code) ? currency_name($object->multicurrency_code, 1) : '';
			print '</td></tr>';
		}

		if (!empty($conf->global->ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY)) {
			// Accountancy sell code
			print '<tr><td class="nowrap">';
			print $langs->trans("ProductAccountancySellCode");
			print '</td><td colspan="2">';
			if (isModEnabled('accounting')) {
				if (!empty($object->accountancy_code_sell)) {
					$accountingaccount = new AccountingAccount($db);
					$accountingaccount->fetch('', $object->accountancy_code_sell, 1);

					print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
				}
			} else {
				print $object->accountancy_code_sell;
			}
			print '</td></tr>';

			// Accountancy buy code
			print '<tr><td class="nowrap">';
			print $langs->trans("ProductAccountancyBuyCode");
			print '</td><td colspan="2">';
			if (isModEnabled('accounting')) {
				if (!empty($object->accountancy_code_buy)) {
					$accountingaccount2 = new AccountingAccount($db);
					$accountingaccount2->fetch('', $object->accountancy_code_buy, 1);

					print $accountingaccount2->getNomUrl(0, 1, 1, '', 1);
				}
			} else {
				print $object->accountancy_code_buy;
			}
			print '</td></tr>';
		}

		// Other attributes
		$parameters = array('socid'=>$socid, 'colspan' => ' colspan="3"', 'colspanvalue' => '3');
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		// Parent company
		if (empty($conf->global->SOCIETE_DISABLE_PARENTCOMPANY)) {
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans('ParentCompany').'</td>';
			if ($action != 'editparentcompany' && $user->hasRight('societe', 'creer')) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editparentcompany&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td>';
			$html_name = ($action == 'editparentcompany') ? 'parent_id' : 'none';
			$form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->parent, $html_name, '', 1, 0, 0, null, 0, array($object->id));
			print '</td></tr>';
		}

		// Sales representative
		include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';

		// Module Adherent
		if (isModEnabled('adherent')) {
			$langs->load("members");
			print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
			print '<td>';
			$adh = new Adherent($db);
			$result = $adh->fetch('', '', $object->id);
			if ($result > 0) {
				$adh->ref = $adh->getFullName($langs);
				print $adh->getNomUrl(-1);
			} else {
				print '<span class="opacitymedium">'.$langs->trans("ThirdpartyNotLinkedToMember").'</span>';
			}
			print "</td></tr>\n";
		}

		// Link user (you must create a contact to get a user)
		/*
		print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
		if ($object->user_id) {
			$dolibarr_user = new User($db);
			$result = $dolibarr_user->fetch($object->user_id);
			print $dolibarr_user->getLoginUrl(-1);
		} else {
			//print '<span class="opacitymedium">'.$langs->trans("NoDolibarrAccess").'</span>';
			if (!$object->user_id && $user->hasRight('user', 'user', 'creer')) {
				print '<a class="aaa" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=create_user&token='.newToken().'">'.img_picto($langs->trans("CreateDolibarrLogin"), 'add').' '.$langs->trans("CreateDolibarrLogin").'</a>';
			}
		}
		print '</td></tr>';
		*/

		// Webservices url/key
		if (!empty($conf->syncsupplierwebservices->enabled)) {
			print '<tr><td>'.$langs->trans("WebServiceURL").'</td><td>'.dol_print_url($object->webservices_url).'</td>';
			print '<td class="nowrap">'.$langs->trans('WebServiceKey').'</td><td>'.$object->webservices_key.'</td></tr>';
		}

		print '</table>';
		print '</div>';

		print '</div>';
		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();


		/*
		 *  Actions
		 */
		if ($action != 'presend') {
			print '<div class="tabsAction">'."\n";

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook)) {
				$at_least_one_email_contact = false;
				$TContact = $object->contact_array_objects();
				foreach ($TContact as &$contact) {
					if (!empty($contact->email)) {
						$at_least_one_email_contact = true;
						break;
					}
				}

				if (empty($user->socid)) {
					$langs->load("mails");
					$title = '';
					if (empty($object->email) && !$at_least_one_email_contact) { $title = $langs->trans('NoEMail'); }
					print dolGetButtonAction($title, $langs->trans('SendMail'), 'default', $_SERVER['PHP_SELF'].'?socid='.$object->id.'&action=presend&mode=init#formmailbeforetitle', 'btn-send-mail', !empty($object->email) || $at_least_one_email_contact);
				}

				print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

				if (isModEnabled('adherent')) {
					$adh = new Adherent($db);
					$result = $adh->fetch('', '', $object->id);
					if ($result == 0 && ($object->client == 1 || $object->client == 3) && !empty($conf->global->MEMBER_CAN_CONVERT_CUSTOMERS_TO_MEMBERS)) {
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/adherents/card.php?&action=create&socid='.$object->id.'" title="'.dol_escape_htmltag($langs->trans("NewMember")).'">'.$langs->trans("NewMember").'</a>'."\n";
					}
				}

				print dolGetButtonAction($langs->trans('MergeThirdparties'), $langs->trans('Merge'), 'danger', $_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=merge&token='.newToken(), '', $permissiontodelete);

				if ($user->hasRight('societe', 'supprimer')) {
					$deleteUrl = $_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=delete&token='.newToken();
					$buttonId = 'action-delete-no-ajax';
					if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) {	// We can't use preloaded confirm form with jmobile
						$deleteUrl = '';
						$buttonId = 'action-delete';
					}
					print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $deleteUrl, $buttonId, $permissiontodelete);
				}
			}

			print '</div>'."\n";
		}

		//Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'presend') {
			print '<div class="fichecenter"><div class="fichehalfleft">';

			if (empty($conf->global->SOCIETE_DISABLE_BUILDDOC)) {
				print '<a name="builddoc"></a>'; // ancre

				/*
				 * Generated documents
				 */
				$filedir = $conf->societe->multidir_output[$object->entity].'/'.$object->id;
				$urlsource = $_SERVER["PHP_SELF"]."?socid=".$object->id;
				$genallowed = $user->hasRight('societe', 'lire');
				$delallowed = $user->hasRight('societe', 'creer');

				print $formfile->showdocuments('company', $object->id, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 0, 0, 0, 28, 0, 'entity='.$object->entity, 0, '', $object->default_lang);
			}

			// Subsidiaries list
			if (empty($conf->global->SOCIETE_DISABLE_PARENTCOMPANY) && empty($conf->global->SOCIETE_DISABLE_SHOW_SUBSIDIARIES)) {
				$result = show_subsidiaries($conf, $langs, $db, $object);
			}

			print '</div><div class="fichehalfright">';

			$MAXEVENT = 10;

			$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/societe/agenda.php?socid='.$object->id);

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, '', $socid, 1, '', $MAXEVENT, '', $morehtmlcenter); // Show all action for thirdparty

			print '</div></div>';

			if (!empty($conf->global->MAIN_DUPLICATE_CONTACTS_TAB_ON_MAIN_CARD)) {
				// Contacts list
				if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
					$result = show_contacts($conf, $langs, $db, $object, $_SERVER["PHP_SELF"].'?socid='.$object->id);
				}
			}
		}

		// Presend form
		$modelmail = 'thirdparty';
		$defaulttopic = 'Information';
		$diroutput = $conf->societe->multidir_output[$object->entity];
		$trackid = 'thi'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	}
}


// End of page
llxFooter();
$db->close();

?>

<script>
// Function to retrieve VAT details from the Greek Ministry of Finance GSIS SOAP web service
function GRVAT(a, u, p, myafm) {
  var afm = a.replace(/\D/g, ""); // Remove non-digit characters from 'a'

  $.ajax({
	type: "GET",
	url: "/societe/checkvat/checkVatGr.php",
	data: { afm }, // Set request parameters
	success: function(data) {
		var obj = data; // Parse response data as JSON

		// Update form fields based on retrieved data
		if (obj.RgWsPublicBasicRt_out.afm === null) {
			alert(obj.pErrorRec_out.errorDescr); // Display error message if AFM is null
		} else {
			$("#name").val(obj.RgWsPublicBasicRt_out.onomasia); // Set 'name' field value
			$("#zipcode").val(obj.RgWsPublicBasicRt_out.postalZipCode); // Set 'zipcode' field value
			$("#town").val(obj.RgWsPublicBasicRt_out.postalAreaDescription); // Set 'town' field value
			$("#idprof2").val(obj.RgWsPublicBasicRt_out.doyDescr); // Set 'idprof2' field value
			$("#name_alias_input").val(obj.RgWsPublicBasicRt_out.commerTitle); // Set 'name_alias' field value

		if (obj.arrayOfRgWsPublicFirmActRt_out.RgWsPublicFirmActRtUser) {
			var firmActUser = obj.arrayOfRgWsPublicFirmActRt_out.RgWsPublicFirmActRtUser;
			
		if (Array.isArray(firmActUser)) {
			var primaryFirmAct = firmActUser.find(item => item.firmActKindDescr === "ΚΥΡΙΑ"); // Find primary client activity
			if (primaryFirmAct) {
				$("#idprof1").val(primaryFirmAct.firmActDescr); // Set 'idprof1' field value
			}
		} else {
			$("#idprof1").val(firmActUser.firmActDescr); // Set 'idprof1' field value
			}
		}
		}
	}
	});
}

</script>
