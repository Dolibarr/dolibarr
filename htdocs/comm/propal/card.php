<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2016 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2021 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013 Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2014       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2016       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020	    Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2022	    Gauthier VERDOL     <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2022	    Abbes Bahfir        <contact@ab1consult.com>
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
 * \file 		htdocs/comm/propal/card.php
 * \ingroup 	propale
 * \brief 		Page of commercial proposals card and list
 */

require '../../main.inc.php';
global$hookmanager,$langs,$db,$user;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('propalcard', 'globalcard'));
//Init hook
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
$object = new Propal($db);
$reshook = $hookmanager->executeHooks('init', '', $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
elseif (empty($reshook)) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formpropal.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
	require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
	require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/signature.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
	if (!empty($conf->projet->enabled)) {
		require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
	}

	if (!empty($conf->variants->enabled)) {
		require_once DOL_DOCUMENT_ROOT . '/variants/class/ProductCombination.class.php';
	}
	require_once DOL_DOCUMENT_ROOT . '/core/class/abstractactions.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/security.class.php';
	class Actions extends AbstractActions {}

// Load translation files required by the page
	$langs->loadLangs(array('companies', 'propal', 'compta', 'bills', 'orders', 'products', 'deliveries', 'sendings', 'other'));
	if (!empty($conf->incoterm->enabled)) {
		$langs->load('incoterm');
	}
	if (!empty($conf->margin->enabled)) {
		$langs->load('margins');
	}

	$error = 0;

	$id = GETPOST('id', 'int');
	$ref = GETPOST('ref', 'alpha');
	$socid = GETPOST('socid', 'int');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel', 'alpha');
	$origin = GETPOST('origin', 'alpha');
	$originid = GETPOST('originid', 'int');
	$confirm = GETPOST('confirm', 'alpha');
	$lineid = GETPOST('lineid', 'int');
	$contactid = GETPOST('contactid', 'int');
	$projectid = GETPOST('projectid', 'int');

// PDF
	$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
	$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
	$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));
	$parameters = array('socid' => $socid);


// Nombre de ligne pour choix de produit/service predefinis
	$NBLINES = 4;

	$object = new Propal($db);
	$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
	if ($id > 0 || !empty($ref)) {
		$ret = $object->fetch($id, $ref);
		if ($ret > 0) {
			$ret = $object->fetch_thirdparty();
		}
		if ($ret <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	}

	$usercanread = $user->rights->propal->lire;
	$usercancreate = $user->rights->propal->creer;
	$usercandelete = $user->rights->propal->supprimer;

	$usercanclose = ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $usercancreate) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->propal->propal_advance->close)));
	$usercanvalidate = ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $usercancreate) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->propal->propal_advance->validate)));
	$usercansend = (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->propal->propal_advance->send);

	$usercancreateorder = $user->rights->commande->creer;
	$usercancreateinvoice = $user->rights->facture->creer;
	$usercancreatecontract = $user->rights->contrat->creer;
	$usercancreateintervention = $user->rights->ficheinter->creer;
	$usercancreatepurchaseorder = ($user->rights->fournisseur->commande->creer || $user->rights->supplier_order->creer);

	$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $usercancreate; // Used by the include of actions_dellink.inc.php
	$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdown.inc.php

	$form = new Form($db);
	$formfile = new FormFile($db);
	$formpropal = new FormPropal($db);
	$formmargin = new FormMargin($db);
	if (!empty($conf->projet->enabled)) {
		$formproject = new FormProjets($db);
	}

	$title = $langs->trans('Proposal')." - ".$langs->trans('Card');
	$help_url = 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos|DE:Modul_Angebote';

	$now = dol_now();
}

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
	$object->id = $user->socid;
}
Security::restrictedArea($user, 'propal', $object->id);


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	Actions::actions_setnotes();
	Actions::actions_dellink();
	Actions::actions_lineupdown();
	Actions::actions_other1();
	Actions::actions_printing();

	// Actions to send emails
	$actiontypecode = 'AC_OTH_AUTO';
	$triggersendname = 'PROPAL_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_PROPOSAL_TO';
	$trackid = 'pro'.$object->id;
	Actions::actions_sendmails();

	Actions::actions_other2();

	// Actions to build doc
	$upload_dir = $conf->propal->multidir_output[$object->entity];
	$permissiontoadd = $usercancreate;
	Actions::actions_builddoc();
}

/*
 * View
 */

llxHeader('', $title, $help_url);
Actions::propal_card_actions();
// End of page
llxFooter();
$db->close();
