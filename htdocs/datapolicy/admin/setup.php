<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2024      MDW                  <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024      Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2025      Quentin VIAL--GOUTEYRON   <quentin.vial-gouteyron@atm-consulting.fr>
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

if (!$langs instanceof Translate) {
	trigger_error("Langs object was not initialized correctly.", E_USER_ERROR);
}

// Translations
$langs->loadLangs(array('admin', 'companies', 'members', 'cron', 'datapolicy', 'recruitment'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

if (empty($action)) {
	$action = 'edit';
}

// ==============================================================================
// == DATA-DRIVEN CONFIGURATION STRUCTURE
// ==============================================================================
// This array drives the entire page logic (saving and rendering).
// It is indexed by a logical key (e.g., 'tiers_client') for each data entity.
// Each entry defines:
// - 'label_key': The translation key for the row label.
// - 'picto': The icon to display.
// - 'config_keys': An associative array mapping an action ('anonymize', 'delete')
//                  to the specific constant name stored in the database.
// This structure allows defining anonymization, deletion, or both for any entity.
$arrayofparameters = array();

// ThirdParty
$arrayofparameters['ThirdParty'] = array(
	'tiers_client' => array(
		'label_key' => 'DATAPOLICY_TIERS_CLIENT',
		'picto' => img_picto('', 'company', 'class="pictofixedwidth"'),
		'config_keys' => array(
			'anonymize' => 'DATAPOLICY_TIERS_CLIENT_ANONYMIZE_DELAY',
			'delete'    => 'DATAPOLICY_TIERS_CLIENT_DELETE_DELAY'
		)
	),
	'tiers_prospect' => array(
		'label_key' => 'DATAPOLICY_TIERS_PROSPECT',
		'picto' => img_picto('', 'company', 'class="pictofixedwidth"'),
		'config_keys' => array(
			'anonymize' => 'DATAPOLICY_TIERS_PROSPECT_ANONYMIZE_DELAY',
			'delete'    => 'DATAPOLICY_TIERS_PROSPECT_DELETE_DELAY'
		)
	),
	'tiers_prospect_client' => array(
		'label_key' => 'DATAPOLICY_TIERS_PROSPECT_CLIENT',
		'picto' => img_picto('', 'company', 'class="pictofixedwidth"'),
		'config_keys' => array(
			'anonymize' => 'DATAPOLICY_TIERS_PROSPECT_CLIENT_ANONYMIZE_DELAY',
			'delete'    => 'DATAPOLICY_TIERS_PROSPECT_CLIENT_DELETE_DELAY'
		)
	),
	'tiers_niprosp_niclient' => array(
		'label_key' => 'DATAPOLICY_TIERS_NIPROSPECT_NICLIENT',
		'picto' => img_picto('', 'company', 'class="pictofixedwidth"'),
		'config_keys' => array(
			'anonymize' => 'DATAPOLICY_TIERS_NIPROSPECT_NICLIENT_ANONYMIZE_DELAY',
			'delete'    => 'DATAPOLICY_TIERS_NIPROSPECT_NICLIENT_DELETE_DELAY'
		)
	),
	'tiers_fournisseur' => array(
		'label_key' => 'DATAPOLICY_TIERS_FOURNISSEUR',
		'picto' => img_picto('', 'supplier', 'class="pictofixedwidth"'),
		'config_keys' => array(
			'anonymize' => 'DATAPOLICY_TIERS_FOURNISSEUR_ANONYMIZE_DELAY',
			'delete'    => 'DATAPOLICY_TIERS_FOURNISSEUR_DELETE_DELAY'
		)
	)
);
// Contact
if (getDolGlobalString('DATAPOLICY_USE_SPECIFIC_DELAY_FOR_CONTACT')) {
	$arrayofparameters['Contact'] = array(
		'contact_client' => array(
			'label_key' => 'DATAPOLICY_CONTACT_CLIENT',
			'picto' => img_picto('', 'contact', 'class="pictofixedwidth"'),
			'config_keys' => array(
				'anonymize' => 'DATAPOLICY_CONTACT_CLIENT_ANONYMIZE_DELAY',
				'delete'    => 'DATAPOLICY_CONTACT_CLIENT_DELETE_DELAY'
			)
		),
		'contact_prospect' => array(
			'label_key' => 'DATAPOLICY_CONTACT_PROSPECT',
			'picto' => img_picto('', 'contact', 'class="pictofixedwidth"'),
			'config_keys' => array(
				'anonymize' => 'DATAPOLICY_CONTACT_PROSPECT_ANONYMIZE_DELAY',
				'delete'    => 'DATAPOLICY_CONTACT_PROSPECT_DELETE_DELAY'
			)
		),
		'contact_prospect_client' => array(
			'label_key' => 'DATAPOLICY_CONTACT_PROSPECT_CLIENT',
			'picto' => img_picto('', 'contact', 'class="pictofixedwidth"'),
			'config_keys' => array(
				'anonymize' => 'DATAPOLICY_CONTACT_PROSPECT_CLIENT_ANONYMIZE_DELAY',
				'delete'    => 'DATAPOLICY_CONTACT_PROSPECT_CLIENT_DELETE_DELAY'
			)
		),
		'contact_niprosp_niclient' => array(
			'label_key' => 'DATAPOLICY_CONTACT_NIPROSPECT_NICLIENT',
			'picto' => img_picto('', 'contact', 'class="pictofixedwidth"'),
			'config_keys' => array(
				'anonymize' => 'DATAPOLICY_CONTACT_NIPROSPECT_NICLIENT_ANONYMIZE_DELAY',
				'delete'    => 'DATAPOLICY_CONTACT_NIPROSPECT_NICLIENT_DELETE_DELAY'
			)
		),
		'contact_fournisseur' => array(
			'label_key' => 'DATAPOLICY_CONTACT_FOURNISSEUR',
			'picto' => img_picto('', 'contact', 'class="pictofixedwidth"'),
			'config_keys' => array(
				'anonymize' => 'DATAPOLICY_CONTACT_FOURNISSEUR_ANONYMIZE_DELAY',
				'delete'    => 'DATAPOLICY_CONTACT_FOURNISSEUR_DELETE_DELAY'
			)
		)
	);
}
// Member
if (isModEnabled('member')) {
	$arrayofparameters['Member'] = array(
		'adherent' => array(
			'label_key' => 'DATAPOLICY_ADHERENT',
			'picto' => img_picto('', 'member', 'class="pictofixedwidth"'),
			'config_keys' => array(
				'anonymize' => 'DATAPOLICY_ADHERENT_ANONYMIZE_DELAY',
				'delete'    => 'DATAPOLICY_ADHERENT_DELETE_DELAY'
			)
		)
	);
}
// Recruitment: This entry demonstrates flexibility. Only a 'delete' action is defined.
// The rendering logic will automatically leave the 'anonymize' column empty for this row.
if (isModEnabled('recruitment')) {
	$arrayofparameters['Recruitment'] = array(
		'recruitment_candidature' => array(
			'label_key' => 'DATAPOLICY_RECRUITMENT_CANDIDATURE',
			'picto' => img_picto('', 'recruitmentcandidature', 'class="pictofixedwidth"'),
			'config_keys' => array(
				'delete' => 'DATAPOLICY_RECRUITMENT_CANDIDATURE_DELETE_DELAY'
			)
		)
	);
}

// Dropdown options for delay selection
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

/*
 * Actions
 */
// Handle form submission to update constants
if ($action == 'update') {
	$nbdone = 0;
	$error = 0;

	// Loop through the data structure to find all possible constants to save.
	foreach ($arrayofparameters as $tab) {
		foreach ($tab as $logicalKey => $val) {
			// Iterate through defined actions ('anonymize', 'delete') for the entity.
			foreach ($val['config_keys'] as $actionType => $constKey) {
				// Save the constant only if its value was submitted in the form.
				if (GETPOSTISSET($constKey)) {
					$val_const = GETPOST($constKey, 'alpha');
					if (dolibarr_set_const($db, $constKey, $val_const, 'chaine', 0, '', $conf->entity) >= 0) {
						$nbdone++;
					} else {
						$error++;
					}
				}
			}
		}
	}

	if ($nbdone > 0) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	if ($error > 0) {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
	$action = 'edit';
}


/*
 * View
 */

$page_name = "datapolicySetup";
llxHeader('', $langs->trans($page_name));

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'generic');

$head = datapolicyAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', '', -1, '');

print '<span class="opacitymedium">'.$langs->trans("datapolicySetupPage").'</span>';
print $form->textwithpicto('', $langs->trans('DATAPOLICY_Tooltip_SETUP', $langs->trans("DATAPOLICYJob"), $langs->transnoentities("CronList")));
print '<br><br><br>';

if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable nobottomiftotal liste">';

	// Table Headers
	print '<tr class="liste_titre"><td class="titlefield"></td>';
	print '<td>'.$langs->trans("DelayForAnonymization").'</td>';
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		print '<td>'.$langs->trans("DelayForDeletion").'</td>';
	}
	print '</tr>';

	// ==============================================================================
	// == DYNAMIC VIEW RENDERING
	// ==============================================================================


	// Loop through each configuration group (e.g., ThirdParty, Member).
	foreach ($arrayofparameters as $title => $tab) {
		print '<tr class="trforbreak liste_titre"><td class="titlefield trforbreak">'.$langs->trans($title).'</td>';
		print '<td></td>';
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
			print '<td></td>';
		}
		print '</tr>';

		// Loop through each entity within the group to create a table row.
		foreach ($tab as $logicalKey => $val) {
			print '<tr class="oddeven"><td>';
			print $val['picto'];
			print $langs->trans($val['label_key']);
			print '</td>';

			// Column 1: Anonymization
			print '<td>';
			// Display the dropdown only if a constant key is defined for the 'anonymize' action.
			if (isset($val['config_keys']['anonymize'])) {
				print Form::selectarray($val['config_keys']['anonymize'], $valTab, getDolGlobalString($val['config_keys']['anonymize']));
			}
			print '</td>';

			// Column 2: Deletion
			if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
				print '<td>';
				// Display the dropdown only if a constant key is defined for the 'delete' action.
				print Form::selectarray($val['config_keys']['delete'], $valTab, getDolGlobalString($val['config_keys']['delete']));
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
}

print dol_get_fiche_end();
llxFooter();
$db->close();
