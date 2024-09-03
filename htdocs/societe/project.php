<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2021		Frédéric France		<frederic.france@netlogic.fr>
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
 *  \file       htdocs/societe/project.php
 *  \ingroup    societe
 *  \brief      Page of third party projects
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$form  = new Form($db);

// Load translation files required by the page
$langs->loadLangs(array('companies', 'projects'));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$toselect = GETPOST('toselect', 'array');


// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('projectthirdparty'));

$result = restrictedArea($user, 'societe', $socid, '&societe');

$object = new Societe($db);
$permissiontodelete = $user->hasRight('societe', 'supprimer');



/*
 *	Actions
 */


if (GETPOST('cancel', 'alpha')) {
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
	$massactionbutton = '';
}


$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$parameters = array('id'=>$socid);

// List of mass actions available
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (in_array($massaction, array('presend', 'predelete','preaffecttag'))) {
	$arrayofmassactions = array();
}

if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete', 'preaffecttag', 'preenable', 'preclose'))) {
	$arrayofmassactions = array();
}

// Mass actions
$objectclass = 'Project';
$objectlabel = 'Project';
$uploaddir = $conf->societe->dir_output;
include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

$massactionbutton = $form->selectMassAction('', $arrayofmassactions);


/*
 *	View
 */

unset($_SESSION['pageforbacktolist']['project']);
if ($socid) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");

	$result = $object->fetch($socid);

	$title = $langs->trans("Projects");
	if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
		$title = $object->name." - ".$title;
	}
	llxHeader('', $title);

	$head = societe_prepare_head($object);

	print dol_get_fiche_head($head, 'project', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';


	// Type Prospect/Customer/Supplier
	print '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
	print $object->getTypeUrl(1);
	print '</td></tr>';

	if (getDolGlobalString('SOCIETE_USEPREFIX')) {  // Old not used prefix field
		print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
	}

	if ($object->client) {
		print '<tr><td class="titlefield">';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_client));
		$tmpcheck = $object->check_codeclient();
		if ($tmpcheck != 0 && $tmpcheck != -5) {
			print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
		}
		print '</td></tr>';
	}

	if ($object->fournisseur) {
		print '<tr><td class="titlefield">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_fournisseur));
		$tmpcheck = $object->check_codefournisseur();
		if ($tmpcheck != 0 && $tmpcheck != -5) {
			print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
		}
		print '</td></tr>';
	}

	print '</table>';
	print '</div>';

	print dol_get_fiche_end();

	print '<br>';
	$params = '';
	$backtopage = $_SERVER['PHP_SELF'].'?socid='.$object->id;
	$newcardbutton = dolGetButtonTitle($langs->trans("NewProject"), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/projet/card.php?action=create&socid='.$object->id.'&backtopageforcancel='.urlencode($backtopage), '', 1, $params);

	if (empty($conf->dol_optimize_smallscreen)) {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<div class="nobordernopadding center valignmiddle col-center">'.$massactionbutton.'</div>';
	}


	// Projects list
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';
	$arrayofselected = is_array($toselect) ? $toselect : array();
	$result = show_projects($conf, $langs, $db, $object, $_SERVER["PHP_SELF"].'?socid='.$object->id, 1, $newcardbutton);

	if (empty($conf->dol_optimize_smallscreen)) {
		print '</form>';
	}
}

// End of page
llxFooter();
$db->close();
