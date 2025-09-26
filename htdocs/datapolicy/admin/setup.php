<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2024      MDW                  <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France      <frederic.france@free.fr>
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
require_once DOL_DOCUMENT_ROOT.'/datapolicy/class/datapolicycron.class.php';
require_once DOL_DOCUMENT_ROOT.'/cron/class/cronjob.class.php';

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

// Get the array $arrayofparameters from _getDataPolicies
$arrayofparameters = array();
$dataPolicyCron = new DataPolicyCron($db);
$arrayofelem = $dataPolicyCron->getDataPolicies();
$arrayofparameters = array();
foreach ($arrayofelem as $key => $val) {
	$arrayofparameters[$val['group']][$key] = array(
		'label_key' => $val['label_key'],
		'picto' => $val['picto'],
		'config_keys' => array(
			'anonymize' => $val['const_anonymize'],
			'delete'    => $val['const_delete']
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

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'generic');

$head = datapolicyAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', '', -1, '');

print '<span class="opacitymedium">'.$langs->trans("datapolicySetupPage").'</span>';
print $form->textwithpicto('', $langs->trans('DATAPOLICY_Tooltip_SETUP', $langs->transnoentitiesnoconv("DATAPOLICYJob"), $langs->transnoentitiesnoconv("CronList")));
if (!isModEnabled('cron')) {
	print info_admin($langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("CronList")), 0, 0, 'warning');
} else {
	$tmpjob = new Cronjob($db);
	$tmpjob->fetch(0, '', '', 'DATAPOLICYJob');
	if ($tmpjob->status != $tmpjob::STATUS_ENABLED) {
		print info_admin($langs->trans("JobMustBeEnabledFirst", $langs->transnoentitiesnoconv("DATAPOLICYJob"), $langs->transnoentitiesnoconv("CronList")), 0, 0, 'warning');
	} else {
		// TODO Show last date/result of execution of the cron job
	}
}
print '<br><br>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="page_y" value="">';

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">';

// Table Headers
print '<tr class="liste_titre"><td class="titlefield"></td>';
print '<td>'.$langs->trans("DelayForAnonymization").'</td>';
print '<td>'.$langs->trans("DelayForDeletion").'</td>';
print '</tr>';

// ==============================================================================
// == DYNAMIC VIEW RENDERING
// ==============================================================================

// Loop through each configuration group (e.g., ThirdParty, Member).
foreach ($arrayofparameters as $title => $tab) {
	print '<tr class="trforbreak liste_titre"><td class="titlefield trforbreak">'.$langs->trans($title).'</td>';
	print '<td></td>';
	print '<td></td>';
	print '</tr>';

	// Loop through each entity within the group to create a table row.
	foreach ($tab as $logicalKey => $val) {
		print '<tr class="oddeven"><td>';
		print $val['picto'];
		print $langs->trans($val['label_key']);
		print '</td>';

		// Column 1: Anonymization
		print '<td class="nowraponall">';
		// Display the dropdown only if a constant key is defined for the 'anonymize' action.
		if (!empty($val['config_keys']['anonymize'])) {
			$selectedvalue = getDolGlobalString($val['config_keys']['anonymize']);

			print Form::selectarray($val['config_keys']['anonymize'], $valTab, $selectedvalue);

			//var_dump($val);
			$listoffieldsid = '';
			foreach ($arrayofelem[$logicalKey]['anonymize_fields'] as $tmpkey => $tmpval) {
				if ($tmpval === 'MAKEANONYMOUS') {
					$listoffieldsid .= ($listoffieldsid ? ', ' : '').$tmpkey.' -> field-anon-ID';
				}
			}
			$otherfields = '';
			foreach ($arrayofelem[$logicalKey]['anonymize_fields'] as $tmpkey => $tmpval) {
				if ($tmpval !== 'MAKEANONYMOUS') {
					$otherfields .= ($otherfields ? ', ' : '').$tmpkey.' -> '.json_encode($tmpval);
				}
			}

			$sql = $arrayofelem[$logicalKey]['sql_template'];
			$sql = preg_replace('/__ENTITY__/', (string) (int) $conf->entity, $sql);
			$sql = preg_replace('/__DELAY__/', (string) (int) $selectedvalue, $sql);
			$sql = preg_replace('/__NOW__/', "'".dol_print_date(dol_now(), 'standard')."'", $sql);
			$sql = preg_replace('/^SELECT [\w+\s+\._]+ FROM/', 'SELECT COUNT(*) as nb FROM', $sql);

			$htmltooltip = $langs->transnoentitiesnoconv("TheFollowingFieldsAreReplaceWith");
			$htmltooltip .= '<br><small>'.$listoffieldsid.'</small>';
			$htmltooltip .= '<br><br>'.$langs->transnoentitiesnoconv("OtherFieldsAreReplaceWithStaticValues");
			$htmltooltip .= '<br><small>'.$otherfields.'</small>';
			$htmltooltip .= '<br><br>'.$langs->transnoentitiesnoconv("TechnicalInformation").' - SQL:';
			$htmltooltip .= '<br><small>'.$sql.'</small>';
			print $form->textwithpicto('', $htmltooltip);

			print ' &nbsp; ';
			if ($action == 'count' && GETPOST('group') == $logicalKey) {
				print '<span class="opacitymedium valignmiddle">'.$langs->trans("QualifiedNumber").' : </span>';

				$estimatednumber = 0;
				if ($sql) {
					$resql = $db->query($sql);
					if ($resql) {
						$obj = $db->fetch_object($resql);
						if ($obj) {
							$estimatednumber = $obj->nb;
						}
					} else {
						dol_print_error($db);
					}
				} else {
					print 'Error, bad definition of the array of data policies profiles';
				}

				print '<span class="valignmiddle badge badge-info">'.$estimatednumber.'</span>';
			} elseif ($selectedvalue) {
				print '<span class="opacitymedium valignmiddle">'.$langs->trans("QualifiedNumber").' : </span>';

				print '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=count&group='.urlencode($logicalKey).'">';
				print $langs->trans("Calculate");
				print '</a>';
			}
		}
		print '</td>';

		// Column 2: Deletion
		print '<td>';
		if (!empty($val['config_keys']['delete'])) {
			$selectedvalue = getDolGlobalString($val['config_keys']['delete']);

			// Display the dropdown only if a constant key is defined for the 'delete' action.
			print Form::selectarray($val['config_keys']['delete'], $valTab, $selectedvalue);

			$sql = $arrayofelem[$logicalKey]['sql_template_delete'];
			$sql = preg_replace('/__ENTITY__/', (string) (int) $conf->entity, $sql);
			$sql = preg_replace('/__DELAY__/', (string) (int) $selectedvalue, $sql);
			$sql = preg_replace('/__NOW__/', "'".dol_print_date(dol_now(), 'standard')."'", $sql);
			$sql = preg_replace('/^SELECT [\w+\s+\._]+ FROM/', 'SELECT COUNT(*) as nb FROM', $sql);

			$htmltooltip = $langs->transnoentitiesnoconv("TechnicalInformation").' - SQL:';
			$htmltooltip .= '<br><small>'.$sql.'</small>';
			print $form->textwithpicto('', $htmltooltip);

			print ' &nbsp; ';
			if ($action == 'countdelete' && GETPOST('group') == $logicalKey) {
				print '<span class="opacitymedium valignmiddle">'.$langs->trans("QualifiedNumber").' : </span>';

				$estimatednumber = 0;
				if ($sql) {
					$resql = $db->query($sql);
					if ($resql) {
						$obj = $db->fetch_object($resql);
						if ($obj) {
							$estimatednumber = $obj->nb;
						}
					} else {
						dol_print_error($db);
					}
				} else {
					print 'Error, bad definition of the array of data policies profiles';
				}

				print '<span class="valignmiddle badge badge-info">'.$estimatednumber.'</span>';
			} elseif ($selectedvalue) {
				print '<span class="opacitymedium valignmiddle">'.$langs->trans("QualifiedNumber").' : </span>';

				print '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=countdelete&group='.urlencode($logicalKey).'">';
				print $langs->trans("Calculate");
				print '</a>';
			}
		}
		print '</td>';

		print '</tr>';
	}
}

print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '', array(), false, 'reposition');

print '</form>';
print '<br>';


print dol_get_fiche_end();
llxFooter();
$db->close();
