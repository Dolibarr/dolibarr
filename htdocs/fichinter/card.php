<?php
/* Copyright (C) 2002-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2020	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2013		Florian Henry				<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2018	Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2014-2022	Charlene Benke				<charlene@patas-monkey.com>
 * Copyright (C) 2015-2016	Abbes Bahfir				<bafbes@gmail.com>
 * Copyright (C) 2018-2022	Philippe Grand				<philippe.grand@atoo-net.com>
 * Copyright (C) 2020-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2023       Benjamin Grembi				<benjamin@oarces.fr>
 * Copyright (C) 2023-2024	William Mead				<william.mead@manchenumerique.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *	\file       htdocs/fichinter/card.php
 *	\brief      Page of intervention
 *	\ingroup    ficheinter
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
if (isModEnabled('contract')) {
	require_once DOL_DOCUMENT_ROOT."/core/class/html.formcontract.class.php";
	require_once DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php";
}
if (getDolGlobalString('FICHEINTER_ADDON') && is_readable(DOL_DOCUMENT_ROOT."/core/modules/fichinter/mod_" . getDolGlobalString('FICHEINTER_ADDON').".php")) {
	require_once DOL_DOCUMENT_ROOT."/core/modules/fichinter/mod_" . getDolGlobalString('FICHEINTER_ADDON').'.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'interventions', 'stocks'));

$id			= GETPOSTINT('id');
$ref		= GETPOST('ref', 'alpha');
$ref_client	= GETPOST('ref_client', 'alpha');
$socid = GETPOSTINT('socid');
$contratid = GETPOSTINT('contratid');
$action		= GETPOST('action', 'alpha');
$cancel		= GETPOST('cancel', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$mesg = GETPOST('msg', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originid = (GETPOSTINT('originid') ? GETPOSTINT('originid') : GETPOSTINT('origin_id')); // For backward compatibility
$note_public = GETPOST('note_public', 'restricthtml');
$note_private = GETPOST('note_private', 'restricthtml');
$lineid = GETPOSTINT('line_id');

$error = 0;

//PDF
$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('interventioncard', 'globalcard'));

$object = new Fichinter($db);
$extrafields = new ExtraFields($db);
$objectsrc = null;

$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$ret = $object->fetch_thirdparty();
	}
	if ($ret < 0) {
		dol_print_error(null, $object->error);
	}
}

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

$permissionnote = $user->hasRight('ficheinter', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('ficheinter', 'creer'); // Used by the include of actions_dellink.inc.php
$permissiontodelete = (($object->statut == Fichinter::STATUS_DRAFT && $user->hasRight('ficheinter', 'creer')) || $user->hasRight('ficheinter', 'supprimer'));

$usercancreate = $user->hasRight('ficheinter', 'creer');

// Load actions
require_once DOL_DOCUMENT_ROOT.'/fichinter/card_actions.php';
// Load view
require_once DOL_DOCUMENT_ROOT.'/fichinter/card_view.php';
