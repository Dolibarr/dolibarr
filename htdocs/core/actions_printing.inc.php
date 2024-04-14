<?php
/* Copyright (C) 2014-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2018 Frederic France      <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file           htdocs/core/actions_printing.inc.php
 *  \brief          Code for actions print_file to print file with calling trigger
 */


// $action must be defined
// $db, $user, $conf, $langs must be defined
// Filename to print must be provided into 'file' parameter

// Print file
if ($action == 'print_file' && $user->hasRight('printing', 'read')) {
	$langs->load("printing");
	require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/modules_printing.php';
	$objectprint = new PrintingDriver($db);
	$list = $objectprint->listDrivers($db, 10);
	$dirmodels = array_merge(array('/core/modules/printing/'), (array) $conf->modules_parts['printing']);
	if (!empty($list)) {
		$errorprint = 0;
		$printerfound = 0;
		foreach ($list as $driver) {
			foreach ($dirmodels as $dir) {
				if (file_exists(dol_buildpath($dir, 0).$driver.'.modules.php')) {
					$classfile = dol_buildpath($dir, 0).$driver.'.modules.php';
					break;
				}
			}
			require_once $classfile;
			$classname = 'printing_'.$driver;
			$printer = new $classname($db);
			'@phan-var-force PrintingDriver $printer';
			$langs->load('printing');
			//print '<pre>'.print_r($printer, true).'</pre>';

			if (getDolGlobalString($printer->active)) {
				$printerfound++;

				$subdir = '';
				$module = GETPOST('printer', 'alpha');
				switch ($module) {
					case 'livraison':
						$subdir = 'receipt';
						$module = 'expedition';
						break;
					case 'expedition':
						$subdir = 'sending';
						break;
					case 'commande_fournisseur':
						$module = 'fournisseur';
						$subdir = 'commande';
						break;
				}
				try {
					$ret = $printer->printFile(GETPOST('file', 'alpha'), $module, $subdir);
					if ($ret > 0) {
						//print '<pre>'.print_r($printer->errors, true).'</pre>';
						setEventMessages($printer->error, $printer->errors, 'errors');
					}
					if ($ret == 0) {
						//print '<pre>'.print_r($printer->errors, true).'</pre>';
						setEventMessages($printer->error, $printer->errors);
						setEventMessages($langs->transnoentitiesnoconv("FileWasSentToPrinter", basename(GETPOST('file', 'alpha'))).' '.$langs->transnoentitiesnoconv("ViaModule").' '.$printer->name, null);
					}
				} catch (Exception $e) {
					$ret = 1;
					setEventMessages($e->getMessage(), null, 'errors');
				}
			}
		}
		if ($printerfound == 0) {
			setEventMessages($langs->trans("NoActivePrintingModuleFound", $langs->transnoentities("Module64000Name")), null, 'warnings');
		}
	} else {
		setEventMessages($langs->trans("NoModuleFound"), null, 'warnings');
	}
	$action = '';
}
