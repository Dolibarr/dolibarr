<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2024      MDW                  <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/datapolicy/admin/setup.php
 * \ingroup datapolicy
 * \brief   Datapolicy setup page to define duration of data keeping.
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/datapolicy/lib/datapolicy.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array('admin', 'companies', 'members', 'datapolicy'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

if (empty($action)) {
	$action = 'edit';
}

$arrayofparameters = array();
// ThirdParty
$arrayofparameters['ThirdParty'] = array(
		'DATAPOLICY_TIERS_CLIENT'=>array('css'=>'minwidth200', 'picto'=>img_picto('', 'company', 'class="pictofixedwidth"')),
		'DATAPOLICY_TIERS_PROSPECT'=>array('css'=>'minwidth200', 'picto'=>img_picto('', 'company', 'class="pictofixedwidth"')),
		'DATAPOLICY_TIERS_PROSPECT_CLIENT'=>array('css'=>'minwidth200', 'picto'=>img_picto('', 'company', 'class="pictofixedwidth"')),
		'DATAPOLICY_TIERS_NIPROSPECT_NICLIENT'=>array('css'=>'minwidth200', 'picto'=>img_picto('', 'company', 'class="pictofixedwidth"')),
		'DATAPOLICY_TIERS_FOURNISSEUR'=>array('css'=>'minwidth200', 'picto'=>img_picto('', 'supplier', 'class="pictofixedwidth"')),
	);
// Contact
if (getDolGlobalString('DATAPOLICY_USE_SPECIFIC_DELAY_FOR_CONTACT')) {
	$arrayofparameters['Contact'] = array(
		'DATAPOLICY_CONTACT_CLIENT' => array('css' => 'minwidth200', 'picto' => img_picto('', 'contact', 'class="pictofixedwidth"')),
		'DATAPOLICY_CONTACT_PROSPECT' => array('css' => 'minwidth200', 'picto' => img_picto('', 'contact', 'class="pictofixedwidth"')),
		'DATAPOLICY_CONTACT_PROSPECT_CLIENT' => array('css' => 'minwidth200', 'picto' => img_picto('', 'contact', 'class="pictofixedwidth"')),
		'DATAPOLICY_CONTACT_NIPROSPECT_NICLIENT' => array('css' => 'minwidth200', 'picto' => img_picto('', 'contact', 'class="pictofixedwidth"')),
		'DATAPOLICY_CONTACT_FOURNISSEUR' => array('css' => 'minwidth200', 'picto' => img_picto('', 'contact', 'class="pictofixedwidth"')),
	);
}
// Member
if (isModEnabled('member')) {
	$arrayofparameters['Member'] = array(
		'DATAPOLICY_ADHERENT' => array('css' => 'minwidth200', 'picto' => img_picto('', 'member', 'class="pictofixedwidth"')),
	);
}

$valTab = array(
	   '' => $langs->trans('Never'),
	  '6' => $langs->trans('NB_MONTHS', 6),
	 '12' => $langs->trans('ONE_YEAR'),
	 '24' => $langs->trans('NB_YEARS', 2),
	 '36' => $langs->trans('NB_YEARS', 3),
	 '48' => $langs->trans('NB_YEARS', 4),
	 '60' => $langs->trans('NB_YEARS', 5),
	'120' => $langs->trans('NB_YEARS', 10),
	'180' => $langs->trans('NB_YEARS', 15),
	'240' => $langs->trans('NB_YEARS', 20),
);

// Security
if (!isModEnabled("datapolicy")) {
	accessforbidden();
}
if (!$user->admin) {
	accessforbidden();
}


'@phan-var-force array<string,array<string,array{type?:string,css?:string,picto?:string}>> $arrayofparameters';

/*
 * Actions
 */

$nbdone = 0;
$error = 0;

foreach ($arrayofparameters as $title => $tab) {
	foreach ($tab as $key => $val) {
		// Modify constant only if key was posted (avoid resetting key to the null value)
		if (GETPOSTISSET($key)) {
			if (preg_match('/category:/', (string) $val['type'])) {
				if (GETPOSTINT($key) == '-1') {
					$val_const = '';
				} else {
					$val_const = GETPOSTINT($key);
				}
			} else {
				$val_const = GETPOST($key, 'alpha');
			}

			$result = dolibarr_set_const($db, $key, $val_const, 'chaine', 0, '', $conf->entity);
			if ($result < 0) {
				$error++;
				break;
			} else {
				$nbdone++;
			}
		}
	}
}

if ($nbdone) {
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}
if ($action == 'update') {
	$action = 'edit';
}


/*
 * View
 */

$page_name = "datapolicySetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'generic');

// Configuration header
$head = datapolicyAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', '', -1, '');

// Setup page goes here
print '<span class="opacitymedium">'.$langs->trans("datapolicySetupPage").'</span>';
print $form->textwithpicto('', $langs->trans('DATAPOLICY_Tooltip_SETUP', $langs->trans("DATAPOLICYJob"), $langs->transnoentities("CronList")));
print '<br>';
print '<br>';
print '<br>';

// TODO Show the last date of execution of the job DATAPOLICYJob

if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="tagtable nobottomiftotal liste">';

	print '<tr class="liste_titre"><td class="titlefield"></td>';
	print '<td>'.$langs->trans("DelayForAnonymization").'</td>';
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		print '<td>'.$langs->trans("DelayForDeletion").'</td>';
	}
	print '</tr>';

	foreach ($arrayofparameters as $title => $tab) {
		print '<tr class="trforbreak liste_titre"><td class="titlefield trforbreak">'.$langs->trans($title).'</td>';
		print '<td></td>';
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
			print '<td></td>';
		}
		print '</tr>';

		foreach ($tab as $key => $val) {
			print '<tr class="oddeven"><td>';
			print $val['picto'];
			print $langs->trans($key);
			print '</td><td>';
			print '<select name="'.$key.'" id="'.$key.'" class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'">';
			foreach ($valTab as $key1 => $val1) {
				print '<option value="'.$key1.'" '.(getDolGlobalString($key) == $key1 ? 'selected="selected"' : '').'>';
				print $val1;
				print '</option>';
			}
			print '</select>';
			print ajax_combobox($key);
			print '</td>';
			if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
				print '<td>';
				print $langs->trans("FeatureNotYetAvailable");
				print '</td>';
			}

			print '</tr>';
		}
	}

	print '</table>';
	print '</div>';

	print $form->buttonsSaveCancel("Save", '');

	print '</form>';
	print '<br>';
} else {
	print '<table class="noborder centpercent">';

	foreach ($arrayofparameters as $title => $tab) {
		print '<tr class="trforbreak"><td class="titlefield trforbreak" colspan="2">'.$langs->trans($title).'</td></tr>';
		foreach ($tab as $key => $val) {
			print '<tr class="oddeven"><td>';
			print $val['picto'];
			print $langs->trans($key);
			print '</td><td>'.(getDolGlobalString($key) == '' ? '<span class="opacitymedium">'.$valTab[''].'</span>' : $valTab[getDolGlobalString($key)]).'</td></tr>';
		}
	}

	print '</table>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
	print '</div>';
}


// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
