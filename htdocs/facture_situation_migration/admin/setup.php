<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 SuperAdmin
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
 * \file    facturesituationmigration/admin/setup.php
 * \ingroup facturesituationmigration
 * \brief   FactureSituationMigration setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/facturesituationmigration.lib.php';

// Classes
dol_include_once('custom/facturesituationmigration/class/facturesituationmigration.class.php');

// Translations
$langs->loadLangs(array("admin", "facturesituationmigration@facturesituationmigration"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('facturesituationmigrationsetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';


$error = 0;
$setupnotempty = 0;


$migration = new FactureSituationMigration($db);
$step_migration = getDolGlobalInt('MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP');
/*
 * Actions */

switch ($action) :
	// ETAPE 1
	case 'doStep1':
		$result = $migration->migration_step_1();

		// OK
		if ($result > 0) :
			dolibarr_set_const($db, 'MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP', '1', 'chaine', 0, '', $conf->entity);
			$step_migration = 1;
			setEventMessage('Step1 done', 'mesgs');
			dol_syslog('constant MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP=1', LOG_DEBUG, 0, '_situationmigration');
			// CAN'T CREATE TABLE
		elseif ($result == -1) :
			setEventMessage($langs->trans('Error1'), 'errors');
			// CAN'T BACKUP DATA
		elseif ($result == -2) :
			setEventMessage($langs->trans('Error2'), 'errors');
		elseif ($result == -3) :
			setEventMessage($langs->trans('AlreadyDone'), 'warnings');
		endif;

	break;

	// ETAPE 2
	case 'doStep2':
		$result = $migration->migration_step_2();

		// OK
		if ($result > 0) :
			dolibarr_set_const($db, 'MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP', '2', 'chaine', 0, '', $conf->entity);
			$step_migration = 2;
			setEventMessage('Step2 done', 'mesgs');
			dol_syslog('constant MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP=2', LOG_DEBUG, 0, '_situationmigration');
			// AUCUNE MIGRATION NECESSAIRE
		elseif ($result == 0) :
			setEventMessage('No Migration needed', 'mesgs');
			// ERROR SQL
		elseif ($result == -1) :
			setEventMessage($langs->trans('Error'), 'errors');
			// ERROR INSERT
		elseif ($result == -2) :
			setEventMessage($langs->trans('Error'), 'errors');
		endif;
	break;

	// ETAPE 3
	case 'doStep3':
		$errorList = [];
		$result = $migration->migration_step_3($errorList);

		// Si fonction ok
		if ($result >= 0) :
			//
			setEventMessage('Success', 'mesgs');

			// On check combien il en reste
			$count_todo = $migration->countMigrationToDo();

			if ($count_todo == 0) :
				dolibarr_set_const($db, 'MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP', '3', 'chaine', 0, '', $conf->entity);
				dolibarr_set_const($db, 'FACTURESITUATIONMIGRATION_ISDONE', '1', 'chaine', 0, '', $conf->entity);
				dolibarr_set_const($db, 'INVOICE_USE_SITUATION', '2', 'chaine', 0, '', $conf->entity);

				dol_syslog('constant MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP=3', LOG_DEBUG, 0, '_situationmigration');
				dol_syslog('constant FACTURESITUATIONMIGRATION_ISDONE=1', LOG_DEBUG, 0, '_situationmigration');
				dol_syslog('constant INVOICE_USE_SITUATION=2', LOG_DEBUG, 0, '_situationmigration');

				$step_migration = 3;
				setEventMessage('Step3 done', 'mesgs');
			endif;
		elseif ($result == -1) :
			setEventMessage($langs->trans('RetryAllIsNotDone') . implode(', ', $errorList), 'warnings');
		elseif ($result == -2) :
			setEventMessage($langs->trans('Error') . implode(', ', $errorList), 'errors');
		endif;
	break;

	case 'doStep4':
		$setrollback = GETPOSTISSET('setrollback')?1:0;
		$removebackup = GETPOSTISSET('removebackup')?1:0;

		if ($setrollback) :
			$result_rollback = $migration->rollbackMigration();
			if ($result_rollback > 0) : header('Location:'.$_SERVER['PHP_SELF']); endif;
		elseif ($removebackup) :
			setEventMessages('REMOVE BACKUP TO DO', '', 'warnings');
		endif;
	break;
endswitch;



//
if ($step_migration >= 2) :
	$count_todo = $migration->countMigrationToDo();
	$count_all = $migration->countMigrationAll();
	$count_done = $count_all - $count_todo;
endif;

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "FactureSituationMigrationSetup";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = facturesituationmigrationAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "facturesituationmigration@facturesituationmigration");
echo '<span class="opacitymedium">'.$langs->trans("FactureSituationMigrationSetupPage").'</span><br><br>';
?>

<table class="noborder centpercent">
	<tbody>
		<tr class="liste_titre">
			<td>Nom</td>
			<td>Description</td>
			<td class="right">État</td>
			<!-- <td align="center" width="16">Infos</td> -->
		</tr>

		<!-- STEP 1 -->
		<tr class="oddeven">
			<td><?php echo $langs->trans('StepNb', 1); ?></td>
			<td><?php echo $langs->trans('FactureSituationMigrationStep1Desc'); ?></td>
			
			<td class="right">
				<?php if ($step_migration == 0) : ?>
					<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
						<input type="hidden" name="token" value="<?php echo newtoken(); ?>">
						<input type="hidden" name="action" value="doStep1">
						<input type="submit" class="button small reposition" value="<?php echo $langs->trans('StepNb', 1); ?>">
					</form>
				<?php elseif ($step_migration > 0) : echo $langs->trans('ActionDoneShort').' <i class="fas fa-check" style="color:green"></i>'; endif; ?>
			</td>
			
			<!-- <td class="center">
				<span class="classfortooltip" style="padding: 0px; padding: 0px; padding-right: 3px;" title="Version: <b>18.0.0-alpha</b><br>Prochaine valeur: PR2305-0001<br>">
					<span class="fas fa-info-circle em088 opacityhigh" style=" vertical-align: middle;"></span>
				</span>
			</td> -->
		</tr>

		<!-- STEP 2 -->
		<?php if ($step_migration >= 1) : ?>
		<tr class="oddeven">
			<td><?php echo $langs->trans('StepNb', 2); ?></td>
			<td><?php echo $langs->trans('FactureSituationMigrationStep2Desc'); ?></td>
			<td class="right">
				<?php if ($step_migration == 1) : ?>
					<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
						<input type="hidden" name="token" value="<?php echo newtoken(); ?>">
						<input type="hidden" name="action" value="doStep2">
						<input type="submit" class="button small reposition" value="<?php echo $langs->trans('StepNb', 2); ?>">
					</form>
				<?php elseif ($step_migration > 1) : echo $langs->trans('ActionDoneShort').' <i class="fas fa-check" style="color:green"></i>'; endif; ?>
			</td>
		</tr>
		<?php endif; ?>

		<!-- STEP 3 -->
		<?php if ($step_migration >= 2) : ?>
		<tr class="oddeven">
			<td><?php echo $langs->trans('StepNb', 3); ?></td>
			<td><?php echo $langs->trans('FactureSituationMigrationStep3Desc'); ?></td>
			<td class="right">
				<span class="paddingright"><?php echo $langs->trans('FactureSituationMigrationCyclesDone').': '.$count_done.' / '.$count_all; ?></span>
				<?php if ($step_migration == 2) : ?>
					<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="display:inline-block;">
						<input type="hidden" name="token" value="<?php echo newtoken(); ?>">
						<input type="hidden" name="action" value="doStep3">
						<input type="submit" class="button small reposition" value="<?php echo $langs->trans('StepNb', 3); ?>">
					</form>
				<?php elseif ($step_migration > 2) : echo $langs->trans('ActionDoneShort').' <i class="fas fa-check" style="color:green"></i>'; endif; ?>
			</td>
		</tr>
		<?php endif; ?>

		<!-- STEP 4 -->
		<?php if ($step_migration >= 3) : ?>
		<tr class="oddeven">
			<td><?php echo $langs->trans('StepNb', 4); ?></td>
			<td><?php echo $langs->trans('FactureSituationMigrationStep4Desc'); ?></td>
			<td class="right">
				<?php if ($step_migration == 3) : ?>
					<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
						<input type="hidden" name="token" value="<?php echo newtoken(); ?>">
						<input type="hidden" name="action" value="doStep4">
						<input type="submit" name="setrollback" class="button small reposition" value="<?php echo $langs->trans('Rollback'); ?>">
						<input type="submit" name="removebackup" class="button small reposition" value="<?php echo $langs->trans('Supprimer données backup / Terminer'); ?>">
					</form>
				<?php elseif ($step_migration > 3) : echo $langs->trans('ActionDoneShort').' <i class="fas fa-check" style="color:green"></i>'; endif; ?>
			</td>
		</tr>
		<?php endif; ?>

	</tbody>
</table>

<?php

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
