<?php
/* Copyright (C) 2014-2016  Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2014-2025  Frédéric France      	<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *  \ingroup        core
 *  \brief          Code for actions print_file to print file (with calling trigger) when using the Direct Print feature.
 *  				The relative filename to print must be provided into GETPOST('file', 'alpha') parameter
 */


/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var Translate $langs
 * @var User $user
 *
 * @var string $action
 */

// Print file
if ($action == 'print_file' && $user->hasRight('printing', 'read')) {
	$langs->load("printing");
	require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/modules_printing.php';
	$objectprint = new PrintingDriver($db);
	$list = $objectprint->listDrivers($db, 10);
	$dirmodels = array_merge(array('/core/modules/printing/'), (array) $conf->modules_parts['printing']);
	if (!empty($list)) {
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
			/** @var PrintingDriver $printer */
			$langs->load('printing');
			// print '<pre>'.print_r($printer, true).'</pre>';

			if (getDolGlobalString($printer->active)) {
				$printerfound++;

				$subdir = '';
				$module = GETPOST('printer', 'alpha');
				// TODO make conversion in printing module
				switch ($module) {
					case 'livraison':
						$subdir = 'receipt';
						$module = 'expedition';
						break;
					case 'expedition':
						$subdir = 'sending';
						break;
					case 'commande_fournisseur':
						$module = 'commande_fournisseur';
						$subdir = 'commande';
						break;
				}
				try {
					// Case of printing an invoice
					$filetoprint = GETPOST('file', 'alpha');		//Example FAYYMM-123/FAYYMM-123-xxx.pdf
					if ($module == 'facture') {
						require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
						$refinvoice = preg_replace('/[\/\\\\].*$/', '', $filetoprint);
						$tmpinvoice = new Facture($db);
						$tmpinvoice->fetch(0, $refinvoice);
						if ($tmpinvoice->id > 0) {
							// Increase counter by 1
							$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET pos_print_counter = pos_print_counter + 1";
							$sql .= " WHERE rowid = ".((int) $tmpinvoice->id);
							$db->query($sql);

							//$tmpinvoice->pos_print_counter += 1;
							//$tmpinvoice->update($user, 1);	// We disable trigger here because we already call the trigger $action = DOC_PREVIEW or DOC_DOWNLOAD just after
						}
					}


					$ret = $printer->printFile($filetoprint, $module, $subdir);
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
